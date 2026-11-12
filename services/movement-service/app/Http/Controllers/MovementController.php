<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MovementController extends Controller
{
    use ApiResponse;

    /** GET /api/v1/movements */
    public function index(Request $request)
    {
        $q = DB::table('mouvements')->whereNull('deleted_at');
        foreach (['type', 'statut', 'bien_id', 'structure_source_id', 'structure_dest_id'] as $f) {
            if ($v = $request->query($f)) $q->where($f, $v);
        }
        if ($s = $request->query('search')) $q->where('reference', 'ilike', "%$s%");
        return $this->ok($q->latest('date_demande')->paginate(min((int) $request->query('per_page', 20), 100)));
    }

    public function show(string $id)
    {
        $m = DB::table('mouvements')->whereNull('deleted_at')->find($id);
        if (! $m) return $this->error('NOT_FOUND', 'Mouvement introuvable.', 404);
        $m->validations = DB::table('validations')->where('mouvement_id', $id)->orderBy('niveau')->get();
        return $this->ok($m);
    }

    /** POST /api/v1/movements - demande de mouvement (affectation, transfert, entree, sortie) */
    public function store(Request $request)
    {
        $claims = $request->attributes->get('jwt', []);
        $d = $request->validate([
            'type' => 'required|in:entree,affectation,transfert,sortie',
            'bien_id' => 'required|uuid',
            'structure_dest_id' => 'required_unless:type,entree|uuid',
            'site_dest_id' => 'nullable|uuid',
            'motif' => 'nullable|string|max:2000',
            'motif_sortie_id' => 'nullable|uuid',
            'date_effet' => 'nullable|date',
            'pieces' => 'nullable|array',
        ]);

        // Verification du bien via asset-service
        $bien = $this->getAsset($d['bien_id']);
        if (! $bien) return $this->error('NOT_FOUND', 'Bien introuvable.', 404);

        // Regles de gestion
        if ($d['type'] !== 'entree' && $bien->statut === 'sorti') {
            return $this->error('BIEN_SORTI', 'Impossible de mouvoir un bien sorti (RG-04).', 409);
        }
        if (in_array($d['type'], ['affectation', 'transfert']) && in_array($bien->statut, ['en_maintenance'])) {
            return $this->error('BIEN_INDISPONIBLE', 'Le bien est en maintenance.', 409);
        }

        // Perimetre RG-06 : le demandeur ne cree que pour son perimetre (sauf roles centraux)
        $perms = $claims['permissions'] ?? [];
        $central = in_array('*', $perms, true) || in_array('mouvement.valider', $perms, true);
        if (! $central && ($bien->structure_id ?? null) !== ($claims['structure_id'] ?? null)) {
            return $this->error('FORBIDDEN_PERIMETRE', 'Ce bien est hors de votre perimetre (RG-06).', 403);
        }

        $id = (string) Str::uuid();
        DB::table('mouvements')->insert([
            'id' => $id,
            'reference' => 'MVT-'.now()->format('Y').'-'.strtoupper(Str::random(6)),
            'type' => $d['type'],
            'statut' => 'en_attente',
            'bien_id' => $d['bien_id'],
            'structure_source_id' => $bien->structure_id ?? null,
            'structure_dest_id' => $d['structure_dest_id'] ?? null,
            'site_dest_id' => $d['site_dest_id'] ?? null,
            'motif' => $d['motif'] ?? null,
            'motif_sortie_id' => $d['motif_sortie_id'] ?? null,
            'pieces' => isset($d['pieces']) ? json_encode($d['pieces']) : null,
            'demandeur_id' => $claims['sub'] ?? null,
            'niveau_validation' => 1,
            'date_demande' => now()->toDateString(),
            'date_effet' => $d['date_effet'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->publish('mouvement.demande', ['mouvement_id' => $id, 'type' => $d['type'], 'bien_id' => $d['bien_id']]);
        $this->notify($claims, 'Nouvelle demande '.$d['type'].' - '.$bien->code_inventaire, $id);

        return $this->created(DB::table('mouvements')->find($id));
    }

    /** POST /api/v1/movements/{id}/decision - valide ou rejette (RG-03) */
    public function decide(Request $request, string $id)
    {
        $m = DB::table('mouvements')->whereNull('deleted_at')->find($id);
        if (! $m) return $this->error('NOT_FOUND', 'Mouvement introuvable.', 404);
        if ($m->statut !== 'en_attente') {
            return $this->error('INVALID_STATE', 'Seule une demande en attente peut etre decidee.', 409);
        }

        $d = $request->validate([
            'decision' => 'required|in:validee,rejetee',
            'commentaire' => 'required_if:decision,rejetee|nullable|string|max:1000',
        ]);
        $claims = $request->attributes->get('jwt', []);

        $newStatut = $d['decision'] === 'validee' ? 'validee' : 'rejetee';
        DB::transaction(function () use ($m, $newStatut, $d, $claims) {
            DB::table('validations')->insert([
                'id' => (string) Str::uuid(),
                'mouvement_id' => $m->id,
                'niveau' => $m->niveau_validation,
                'decision' => $newStatut === 'validee' ? 'approuvee' : 'rejetee',
                'commentaire' => $d['commentaire'] ?? null,
                'valideur_id' => $claims['sub'] ?? null,
                'date_decision' => now(),
            ]);
            DB::table('mouvements')->where('id', $m->id)->update([
                'statut' => $newStatut,
                'valideur_id' => $claims['sub'] ?? null,
                'date_validation' => now(),
                'updated_at' => now(),
            ]);
        });

        if ($newStatut === 'validee') {
            $this->execute($m->id); // applique l'effet sur le bien
        } else {
            $this->notifyDemandeur($m, 'Demande rejetee', $d['commentaire'] ?? '');
        }
        return $this->ok(DB::table('mouvements')->find($m->id));
    }

    /** POST /api/v1/movements/{id}/cancel - annulation par le demandeur */
    public function cancel(Request $request, string $id)
    {
        $m = DB::table('mouvements')->whereNull('deleted_at')->find($id);
        if (! $m || $m->statut !== 'en_attente') {
            return $this->error('INVALID_STATE', 'Annulation impossible.', 409);
        }
        DB::table('mouvements')->where('id', $id)->update(['statut' => 'annulee', 'updated_at' => now()]);
        return $this->ok(DB::table('mouvements')->find($id));
    }

    /** GET /api/v1/movements/{id}/history */
    public function history(string $id)
    {
        return $this->ok(DB::table('mouvements')->where('bien_id', $id)->whereNull('deleted_at')->latest('date_demande')->get());
    }

    /** GET /api/v1/motifs-sortie */
    public function motifs()
    {
        return $this->ok(DB::table('motifs_sortie')->where('actif', true)->get());
    }

    /** Applique l'effet d'un mouvement valide sur le bien (asset-service) */
    private function execute(string $id): void
    {
        $m = DB::table('mouvements')->find($id);
        $payload = match ($m->type) {
            'affectation', 'transfert' => [
                'structure_id' => $m->structure_dest_id,
                'site_id' => $m->site_dest_id,
                'statut' => 'affecte',
            ],
            'entree' => ['statut' => 'disponible'],
            'sortie' => ['statut' => 'sorti'],
            default => ['statut' => 'disponible'],
        };
        try {
            $r = Http::timeout(5)->withToken($this->serviceToken())->patch(
                env('ASSET_SERVICE_URL')."/api/v1/assets/{$m->bien_id}/affectation", $payload);
            DB::table('mouvements')->where('id', $id)->update([
                'statut' => $r->successful() ? 'executee' : 'echouee',
                'date_effet' => now()->toDateString(),
                'updated_at' => now(),
            ]);
            if ($r->successful()) {
                $this->publish('bien.'.$m->type, ['mouvement_id' => $id, 'bien_id' => $m->bien_id]);
            }
        } catch (\Throwable $e) {
            DB::table('mouvements')->where('id', $id)->update(['statut' => 'echouee', 'updated_at' => now()]);
            report($e);
        }
    }

    private function getAsset(string $id)
    {
        try {
            $r = Http::timeout(5)->withToken($this->serviceToken())->get(env('ASSET_SERVICE_URL')."/api/v1/assets/$id");
            return $r->successful() ? $r->json('data') : null;
        } catch (\Throwable) { return null; }
    }

    /** JWT de service pour les appels inter-services (claims minimaux) */
    private function serviceToken(): string
    {
        // Reutilise la cle privee pour signer un jeton de service si configure ;
        // sinon token statique SERVICE_TOKEN partage.
        return env('SERVICE_TOKEN', '');
    }

    private function publish(string $event, array $payload): void
    {
        try { app(\App\Support\EventBus::class)->publish($event, $payload); } catch (\Throwable $e) { report($e); }
    }

    private function notify(array $claims, string $message, string $mouvementId): void
    {
        try {
            Http::timeout(3)->post(env('NOTIFICATION_SERVICE_URL').'/api/v1/notifications', [
                'utilisateur_id' => $claims['sub'] ?? null,
                'type' => 'mouvement',
                'message' => $message,
                'entite_type' => 'mouvement', 'entite_id' => $mouvementId,
            ]);
        } catch (\Throwable) {}
    }

    private function notifyDemandeur(object $m, string $titre, string $detail): void
    {
        try {
            Http::timeout(3)->post(env('NOTIFICATION_SERVICE_URL').'/api/v1/notifications', [
                'utilisateur_id' => $m->demandeur_id,
                'type' => 'mouvement',
                'message' => "$titre : {$m->reference} - $detail",
                'entite_type' => 'mouvement', 'entite_id' => $m->id,
            ]);
        } catch (\Throwable) {}
    }
}
