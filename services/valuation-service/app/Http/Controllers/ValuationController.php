<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ValuationController extends Controller
{
    use ApiResponse;

    /** GET /api/v1/valorisation - liste des valorisations */
    public function index(Request $request)
    {
        $q = DB::table('valorisations')->orderByDesc('exercice');
        if ($b = $request->query('bien_id')) $q->where('bien_id', $b);
        if ($e = $request->query('exercice')) $q->where('exercice', $e);
        return $this->ok($q->paginate(50));
    }

    /** GET /api/v1/valorisation/{bienId} - VNC courante d'un bien */
    public function show(string $bienId)
    {
        $v = DB::table('valorisations')->where('bien_id', $bienId)->orderByDesc('exercice')->first();
        if (! $v) return $this->error('NOT_FOUND', 'Aucune valorisation pour ce bien.', 404);
        $v->amortissements = DB::table('amortissements')->where('bien_id', $bienId)->orderBy('exercice')->get();
        return $this->ok($v);
    }

    /**
     * POST /api/v1/valorisation/calculate - calcul annuel (lineaire / degressif)
     * Lit les biens via asset-service, applique la duree de vie de la categorie.
     */
    public function calculate(Request $request)
    {
        $d = $request->validate(['exercice' => 'required|integer|min:2000|max:2100']);
        $exercice = (int) $d['exercice'];

        // Biens actifs via asset-service
        $biens = collect();
        try {
            $r = Http::timeout(15)->withToken(env('SERVICE_TOKEN', ''))->get(env('ASSET_SERVICE_URL').'/api/v1/assets', ['per_page' => 5000]);
            $biens = collect($r->json('data') ?? []);
        } catch (\Throwable $e) {
            return $this->error('UPSTREAM', 'Asset-service indisponible.', 502);
        }

        // Categories via organisation-service (duree de vie)
        $categories = collect();
        try {
            $r = Http::timeout(10)->withToken(env('SERVICE_TOKEN', ''))->get(env('ORGANISATION_SERVICE_URL').'/api/v1/categories', ['arbre' => 1]);
            $categories = collect($r->json('data') ?? [])->keyBy('id');
        } catch (\Throwable) {}

        $crees = 0;
        foreach ($biens as $bien) {
            if (in_array($bien->statut, ['sorti'], true)) continue;
            $cat = $categories->get($bien->categorie_id);
            $duree = $cat->duree_vie_mois ?? 120; // defaut 10 ans
            $methode = $cat->methode_amortissement ?? 'lineaire';
            $acquisition = (float) $bien->valeur_acquisition;
            if ($acquisition <= 0) continue;

            // Amortissement deja comptabilise avant cet exercice
            $cumulAvant = (float) DB::table('amortissements')
                ->where('bien_id', $bien->id)->where('exercice', '<', $exercice)->sum('dotation');
            $base = max(0, $acquisition - $cumulAvant);

            if ($methode === 'degressif') {
                $taux = 1.25 / ($duree / 12); // coefficient 1.25
                $dotation = min($base, $base * $taux);
            } else {
                $dotation = min($base, $acquisition / ($duree / 12));
            }

            DB::table('amortissements')->updateOrInsert(
                ['bien_id' => $bien->id, 'exercice' => $exercice],
                ['id' => DB::table('amortissements')->where('bien_id', $bien->id)->where('exercice', $exercice)->value('id') ?? (string) Str::uuid(),
                 'dotation' => round($dotation, 2), 'methode' => $methode, 'taux_applique' => null,
                 'created_at' => now(), 'updated_at' => now()]
            );

            $cumul = $cumulAvant + $dotation;
            DB::table('valorisations')->updateOrInsert(
                ['bien_id' => $bien->id, 'exercice' => $exercice],
                ['id' => DB::table('valorisations')->where('bien_id', $bien->id)->where('exercice', $exercice)->value('id') ?? (string) Str::uuid(),
                 'valeur_acquisition' => $acquisition, 'amortissement_cumule' => round($cumul, 2),
                 'vnc' => round(max(0, $acquisition - $cumul), 2), 'date_calcul' => now()->toDateString(),
                 'created_at' => now(), 'updated_at' => now()]
            );
            $crees++;
        }
        $this->publish('valorisation.calculee', ['exercice' => $exercice, 'biens' => $crees]);
        return $this->ok(['exercice' => $exercice, 'biens_traites' => $crees]);
    }

    /** POST /api/v1/valorisation/reevaluations */
    public function reevaluation(Request $request)
    {
        $d = $request->validate([
            'bien_id' => 'required|uuid',
            'nouvelle_valeur' => 'required|numeric|min:0',
            'motif' => 'required|string|max:1000',
        ]);
        $claims = $request->attributes->get('jwt', []);
        $actuelle = DB::table('valorisations')->where('bien_id', $d['bien_id'])->orderByDesc('exercice')->value('vnc') ?? 0;
        $id = (string) Str::uuid();
        DB::table('reevaluations')->insert([
            'id' => $id, 'bien_id' => $d['bien_id'], 'date_reevaluation' => now()->toDateString(),
            'ancienne_valeur' => $actuelle, 'nouvelle_valeur' => $d['nouvelle_valeur'],
            'motif' => $d['motif'], 'autorise_par' => $claims['sub'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $this->created(DB::table('reevaluations')->find($id));
    }

    /** GET /api/v1/valorisation/stats */
    public function stats()
    {
        return $this->ok([
            'total_vnc' => DB::table('valorisations')->where('exercice', DB::table('valorisations')->max('exercice'))->sum('vnc'),
            'total_acquisition' => DB::table('valorisations')->where('exercice', DB::table('valorisations')->max('exercice'))->sum('valeur_acquisition'),
            'total_amortissements' => DB::table('amortissements')->sum('dotation'),
            'nombre_biens' => DB::table('valorisations')->distinct('bien_id')->count('bien_id'),
        ]);
    }

    private function publish(string $event, array $payload): void
    {
        try { app(\App\Support\EventBus::class)->publish($event, $payload); } catch (\Throwable $e) { report($e); }
    }
}
