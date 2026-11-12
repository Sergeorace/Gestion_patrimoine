<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class AssetController extends Controller
{
    use ApiResponse;

    private const TYPES = ['immobilier', 'vehicule', 'informatique', 'mobilier', 'autre'];

    /** GET /api/v1/assets - recherche multicritere + pagination */
    public function index(Request $request)
    {
        $q = DB::table('biens')->whereNull('deleted_at');

        // Perimetre RG-06 : structure_id restreint sauf role central/audit
        $claims = $request->attributes->get('jwt', []);
        $perms = $claims['permissions'] ?? [];
        $central = in_array('*', $perms, true) || in_array('bien.gerer_central', $perms, true);
        if (! $central && ! empty($claims['structure_id'])) {
            $q->where('structure_id', $claims['structure_id']);
        } elseif (! $central) {
            $q->whereRaw('1 = 0');
        }

        foreach (['statut', 'etat', 'type_specifique', 'structure_id', 'site_id', 'categorie_id'] as $f) {
            if ($v = $request->query($f)) $q->where($f, $v);
        }
        if ($s = $request->query('search')) {
            $q->where(fn ($x) => $x->where('designation', 'ilike', "%$s%")
                ->orWhere('code_inventaire', 'ilike', "%$s%"));
        }
        if ($d = $request->query('date_from')) $q->where('date_acquisition', '>=', $d);
        if ($d = $request->query('date_to')) $q->where('date_acquisition', '<=', $d);

        $sort = $request->query('sort', 'created_at');
        $dir = strtolower($request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'code_inventaire', 'designation', 'valeur_acquisition', 'date_acquisition'];
        $q->orderBy(in_array($sort, $allowed) ? $sort : 'created_at', $dir);

        return $this->ok($q->paginate(min((int) $request->query('per_page', 20), 100)));
    }

    /** GET /api/v1/assets/{id} - fiche complete */
    public function show(Request $request, string $id)
    {
        $bien = DB::table('biens')->whereNull('deleted_at')->find($id);
        if (! $bien) return $this->error('NOT_FOUND', 'Bien introuvable.', 404);

        $bien->specifique = match ($bien->type_specifique) {
            'immobilier' => DB::table('specifiques_immobiliers')->find($id),
            'vehicule' => DB::table('specifiques_vehicules')->find($id),
            'informatique' => DB::table('specifiques_informatique')->find($id),
            default => null,
        };
        $bien->photos = DB::table('photos_biens')->where('bien_id', $id)->get();
        return $this->ok($bien);
    }

    /** POST /api/v1/assets - creation fiche bien (RG-01 code non reutilisable) */
    public function store(Request $request)
    {
        $claims = $request->attributes->get('jwt', []);
        $d = $request->validate([
            'designation' => 'required|string|max:255',
            'description' => 'nullable|string',
            'categorie_id' => 'required|uuid',
            'type_specifique' => 'required|in:'.implode(',', self::TYPES),
            'date_acquisition' => 'required|date',
            'mode_acquisition' => 'required|in:achat,don,transfert,construction,recuperation',
            'valeur_acquisition' => 'required|numeric|min:0',
            'devise' => 'nullable|string|size:3',
            'structure_id' => 'nullable|uuid',
            'site_id' => 'nullable|uuid',
            'localisation_id' => 'nullable|uuid',
            'caracteristiques' => 'nullable|array',
            'immobilier' => 'nullable|array',
            'vehicule' => 'nullable|array',
            'informatique' => 'nullable|array',
        ]);

        $structureId = $d['structure_id'] ?? $claims['structure_id'] ?? null;
        if (! $structureId) {
            return $this->error('VALIDATION', 'Structure responsable requise.', 422);
        }

        $code = $this->generateCode();
        $id = (string) Str::uuid();
        $uuid = (string) Str::uuid();

        DB::transaction(function () use ($d, $id, $uuid, $code, $structureId, $claims) {
            DB::table('biens')->insert([
                'id' => $id, 'code_inventaire' => $code, 'uuid' => $uuid,
                'designation' => $d['designation'], 'description' => $d['description'] ?? null,
                'categorie_id' => $d['categorie_id'], 'type_specifique' => $d['type_specifique'],
                'caracteristiques' => isset($d['caracteristiques']) ? json_encode($d['caracteristiques']) : null,
                'date_acquisition' => $d['date_acquisition'], 'mode_acquisition' => $d['mode_acquisition'],
                'valeur_acquisition' => $d['valeur_acquisition'], 'devise' => $d['devise'] ?? 'XOF',
                'structure_id' => $structureId, 'site_id' => $d['site_id'] ?? null,
                'localisation_id' => $d['localisation_id'] ?? null,
                'statut' => 'enregistre', 'etat' => 'bon',
                'cree_par' => $claims['sub'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            match ($d['type_specifique']) {
                'immobilier' => DB::table('specifiques_immobiliers')->insert(['bien_id' => $id] + ($d['immobilier'] ?? [])),
                'vehicule' => DB::table('specifiques_vehicules')->insert(['bien_id' => $id] + ($d['vehicule'] ?? [])),
                'informatique' => DB::table('specifiques_informatique')->insert(['bien_id' => $id] + ($d['informatique'] ?? [])),
                default => null,
            };
        });

        // Evenement asynchrone
        $this->publish('bien.enregistre', ['bien_id' => $id, 'code' => $code]);

        return $this->created(DB::table('biens')->find($id) + ['qr_code_url' => "/api/v1/assets/$id/qrcode.svg"]);
    }

    /** PUT /api/v1/assets/{id} */
    public function update(Request $request, string $id)
    {
        $bien = DB::table('biens')->whereNull('deleted_at')->find($id);
        if (! $bien) return $this->error('NOT_FOUND', 'Bien introuvable.', 404);
        if ($bien->statut === 'sorti') {
            return $this->error('BIEN_SORTI', 'Un bien sorti n\'est plus modifiable (RG-04).', 409);
        }
        $d = $request->validate([
            'designation' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'etat' => 'sometimes|in:bon,degrade,hors_service',
            'valeur_acquisition' => 'sometimes|numeric|min:0',
            'caracteristiques' => 'nullable|array',
        ]);
        if (isset($d['caracteristiques'])) $d['caracteristiques'] = json_encode($d['caracteristiques']);
        DB::table('biens')->where('id', $id)->update($d + ['updated_at' => now()]);
        $this->audit($request, 'bien.modifier', $id, (array) $bien, $d);
        return $this->ok(DB::table('biens')->find($id));
    }

    /** DELETE /api/v1/assets/{id} (soft delete, sauf si sorti -> archive conserve) */
    public function destroy(Request $request, string $id)
    {
        $bien = DB::table('biens')->whereNull('deleted_at')->find($id);
        if (! $bien) return $this->error('NOT_FOUND', 'Bien introuvable.', 404);
        if ($bien->statut === 'sorti') {
            return $this->error('BIEN_SORTI', 'Suppression interdite : bien sorti conserve pour audit.', 409);
        }
        DB::table('biens')->where('id', $id)->update(['deleted_at' => now()]);
        return $this->ok(['message' => 'Bien supprime.']);
    }

    /** PATCH /api/v1/assets/{id}/affectation - applique par movement-service */
    public function affecter(Request $request, string $id)
    {
        $d = $request->validate([
            'structure_id' => 'required|uuid',
            'site_id' => 'nullable|uuid',
            'localisation_id' => 'nullable|uuid',
            'statut' => 'required|in:disponible,affecte,en_maintenance,sorti',
        ]);
        DB::table('biens')->where('id', $id)->update($d + ['updated_at' => now()]);
        return $this->ok(DB::table('biens')->find($id));
    }

    /** PATCH /api/v1/assets/{id}/etat */
    public function etat(Request $request, string $id)
    {
        $d = $request->validate([
            'etat' => 'required|in:bon,degrade,hors_service',
            'statut' => 'sometimes|in:disponible,affecte,en_maintenance',
        ]);
        DB::table('biens')->where('id', $id)->update($d + ['updated_at' => now()]);
        return $this->ok(DB::table('biens')->find($id));
    }

    /** GET /api/v1/assets/{id}/qrcode.svg */
    public function qrcode(string $id)
    {
        $bien = DB::table('biens')->find($id);
        if (! $bien) return $this->error('NOT_FOUND', 'Bien introuvable.', 404);
        $svg = \SimpleSoftwareIO\QrCode\QrCode::format('svg')->size(300)->generate($bien->code_inventaire);
        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    /** GET /api/v1/assets/stats - statistiques pour reporting */
    public function stats(Request $request)
    {
        $base = DB::table('biens')->whereNull('deleted_at');
        return $this->ok([
            'total' => (clone $base)->count(),
            'par_statut' => (clone $base)->select('statut', DB::raw('count(*) as n'))->groupBy('statut')->pluck('n', 'statut'),
            'par_categorie' => (clone $base)->select('categorie_id', DB::raw('count(*) as n'), DB::raw('sum(valeur_acquisition) as valeur'))->groupBy('categorie_id')->get(),
            'par_structure' => (clone $base)->select('structure_id', DB::raw('count(*) as n'), DB::raw('sum(valeur_acquisition) as valeur'))->groupBy('structure_id')->get(),
            'valeur_totale' => (clone $base)->sum('valeur_acquisition'),
        ]);
    }

    private function generateCode(): string
    {
        $year = now()->format('Y');
        $last = DB::table('biens')->where('code_inventaire', 'like', "PAT-$year-%")
            ->orderByDesc('code_inventaire')->value('code_inventaire');
        $seq = $last ? ((int) substr($last, -6)) + 1 : 1;
        return sprintf('PAT-%s-%06d', $year, $seq);
    }

    private function publish(string $event, array $payload): void
    {
        try {
            app(\App\Support\EventBus::class)->publish($event, $payload);
        } catch (\Throwable $e) {
            report($e); // ne bloque pas le metier si RabbitMQ est indisponible
        }
    }

    private function audit(Request $request, string $action, string $entite, array $avant, array $apres): void
    {
        try {
            Http::timeout(3)->post(env('NOTIFICATION_SERVICE_URL').'/api/v1/audit', [
                'utilisateur_id' => $request->attributes->get('jwt')['sub'] ?? null,
                'service_source' => 'asset-service',
                'action' => $action,
                'entite' => 'bien',
                'entite_id' => $entite,
                'avant' => $avant,
                'apres' => $apres,
                'ip' => $request->ip(),
            ]);
        } catch (\Throwable) { /* audit best-effort */ }
    }
}
