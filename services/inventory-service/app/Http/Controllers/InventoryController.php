<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $q = DB::table('campagnes')->orderByDesc('created_at');
        if ($s = $request->query('statut')) $q->where('statut', $s);
        if ($st = $request->query('structure_id')) $q->where('structure_id', $st);
        return $this->ok($q->paginate(20));
    }

    public function show(string $id)
    {
        $c = DB::table('campagnes')->find($id);
        if (! $c) return $this->error('NOT_FOUND', 'Campagne introuvable.', 404);
        $c->equipes = DB::table('equipes')->where('campagne_id', $id)->get();
        $c->constats_count = DB::table('constates')->where('campagne_id', $id)->count();
        $c->rapport = DB::table('rapports_reconciliation')->where('campagne_id', $id)->first();
        return $this->ok($c);
    }

    public function store(Request $request)
    {
        $claims = $request->attributes->get('jwt', []);
        $d = $request->validate([
            'libelle' => 'required|string|max:200',
            'structure_id' => 'nullable|uuid',
            'site_id' => 'nullable|uuid',
            'categorie_id' => 'nullable|uuid',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'equipes' => 'nullable|array',
            'equipes.*.nom' => 'required_with:equipes|string',
            'equipes.*.agents' => 'nullable|array',
        ]);
        $id = (string) Str::uuid();
        DB::transaction(function () use ($d, $id, $claims) {
            DB::table('campagnes')->insert([
                'id' => $id, 'code' => 'INV-'.now()->format('Y').'-'.strtoupper(Str::random(5)),
                'libelle' => $d['libelle'], 'structure_id' => $d['structure_id'] ?? null,
                'site_id' => $d['site_id'] ?? null, 'categorie_id' => $d['categorie_id'] ?? null,
                'date_debut' => $d['date_debut'], 'date_fin' => $d['date_fin'],
                'statut' => 'planifiee', 'cree_par' => $claims['sub'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($d['equipes'] ?? [] as $e) {
                $eid = (string) Str::uuid();
                DB::table('equipes')->insert(['id' => $eid, 'campagne_id' => $id, 'nom' => $e['nom'], 'role' => $e['role'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
                foreach ($e['agents'] ?? [] as $agentId) {
                    DB::table('membres_equipe')->insert(['id' => (string) Str::uuid(), 'equipe_id' => $eid, 'agent_id' => $agentId, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        });
        return $this->created(DB::table('campagnes')->find($id));
    }

    /** POST /api/v1/inventories/{id}/start */
    public function start(string $id)
    {
        $c = DB::table('campagnes')->find($id);
        if (! $c || $c->statut !== 'planifiee') return $this->error('INVALID_STATE', 'Campagne non demarrable.', 409);
        DB::table('campagnes')->where('id', $id)->update(['statut' => 'en_cours', 'updated_at' => now()]);
        return $this->ok(DB::table('campagnes')->find($id));
    }

    /** GET /api/v1/inventories/{id}/assets - biens attendus (via asset-service) */
    public function assets(Request $request, string $id)
    {
        $c = DB::table('campagnes')->find($id);
        if (! $c) return $this->error('NOT_FOUND', 'Campagne introuvable.', 404);
        try {
            $r = Http::timeout(8)->withToken($this->serviceToken())->get(env('ASSET_SERVICE_URL').'/api/v1/assets', [
                'structure_id' => $c->structure_id, 'site_id' => $c->site_id,
                'categorie_id' => $c->categorie_id, 'per_page' => 500,
            ]);
            return $this->ok($r->json('data') ?? []);
        } catch (\Throwable $e) {
            return $this->error('UPSTREAM', 'Asset-service indisponible.', 502);
        }
    }

    /** POST /api/v1/inventories/{id}/constats - constat unitaire ou batch (sync hors-ligne) */
    public function constats(Request $request, string $id)
    {
        $c = DB::table('campagnes')->find($id);
        if (! $c || $c->statut !== 'en_cours') {
            return $this->error('INVALID_STATE', 'Campagne non en cours.', 409);
        }
        $claims = $request->attributes->get('jwt', []);
        $items = $request->input('constats', []);
        if (! is_array($items) || ! $items) {
            $items = [$request->only(['bien_id', 'present', 'etat_constate', 'commentaire', 'latitude', 'longitude', 'synchronise'])];
        }
        $ok = 0; $ko = [];
        foreach ($items as $i => $item) {
            $v = validator($item, [
                'bien_id' => 'required|uuid',
                'present' => 'boolean',
                'etat_constate' => 'nullable|in:bon,degrade,hors_service',
                'commentaire' => 'nullable|string|max:1000',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'synchronise' => 'boolean',
            ]);
            if ($v->fails()) { $ko[] = ['index' => $i, 'errors' => $v->errors()->all()]; continue; }
            $d = $v->validated();
            DB::table('constates')->updateOrInsert(
                ['campagne_id' => $id, 'bien_id' => $d['bien_id']],
                [
                    'id' => DB::table('constates')->where('campagne_id', $id)->where('bien_id', $d['bien_id'])->value('id') ?? (string) Str::uuid(),
                    'present' => $d['present'] ?? false,
                    'etat_constate' => $d['etat_constate'] ?? null,
                    'commentaire' => $d['commentaire'] ?? null,
                    'agent_id' => $claims['sub'] ?? null,
                    'date_constate' => now(),
                    'latitude' => $d['latitude'] ?? null,
                    'longitude' => $d['longitude'] ?? null,
                    'synchronise' => $d['synchronise'] ?? true,
                    'created_at' => now(), 'updated_at' => now(),
                ]
            );
            $ok++;
        }
        return $this->ok(['enregistres' => $ok, 'erreurs' => $ko], $ko ? 207 : 200);
    }

    /** GET /api/v1/inventories/{id}/constats */
    public function constatsList(string $id)
    {
        return $this->ok(DB::table('constates')->where('campagne_id', $id)->latest('date_constate')->paginate(100));
    }

    /** POST /api/v1/inventories/{id}/close - cloture + ecarts + rapport (RG-08) */
    public function close(Request $request, string $id)
    {
        $c = DB::table('campagnes')->find($id);
        if (! $c || $c->statut !== 'en_cours') {
            return $this->error('INVALID_STATE', 'Seule une campagne en cours peut etre cloturee.', 409);
        }

        // Liste des biens attendus
        $attendus = collect();
        try {
            $r = Http::timeout(15)->withToken($this->serviceToken())->get(env('ASSET_SERVICE_URL').'/api/v1/assets', [
                'structure_id' => $c->structure_id, 'site_id' => $c->site_id,
                'categorie_id' => $c->categorie_id, 'per_page' => 5000,
            ]);
            $attendus = collect($r->json('data') ?? [])->pluck('id');
        } catch (\Throwable) {}

        $constates = DB::table('constates')->where('campagne_id', $id)->get()->keyBy('bien_id');
        $presents = $constates->where('present', true);
        $manquants = $attendus->diff($constates->keys());
        $degrades = $present = $presents->whereIn('etat_constate', ['degrade', 'hors_service'])->count();

        DB::transaction(function () use ($id, $manquants, $constates, $presents, $attendus, $degrades) {
            foreach ($manquants as $bienId) {
                DB::table('ecarts')->insert(['id' => (string) Str::uuid(), 'campagne_id' => $id, 'bien_id' => $bienId, 'type' => 'manquant', 'created_at' => now(), 'updated_at' => now()]);
            }
            foreach ($presents as $bienId => $ct) {
                if (in_array($ct->etat_constate, ['degrade', 'hors_service'], true)) {
                    DB::table('ecarts')->insert(['id' => (string) Str::uuid(), 'campagne_id' => $id, 'bien_id' => $bienId, 'type' => 'degrade', 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            $total = $attendus->count();
            DB::table('rapports_reconciliation')->updateOrInsert(
                ['campagne_id' => $id],
                [
                    'id' => DB::table('rapports_reconciliation')->where('campagne_id', $id)->value('id') ?? (string) Str::uuid(),
                    'total_attendus' => $total,
                    'total_presents' => $presents->count(),
                    'manquants' => $manquants->count(),
                    'excdentaires' => max(0, $constates->count() - $attendus->count()),
                    'degrades' => $degrades,
                    'taux_couverture' => $total ? round($presents->count() / $total * 100, 2) : 0,
                    'genere_le' => now(),
                ]
            );
            DB::table('campagnes')->where('id', $id)->update(['statut' => 'cloturee', 'cloture_le' => now(), 'updated_at' => now()]);
        });

        $this->publish('inventaire.cloture', ['campagne_id' => $id]);
        return $this->ok(DB::table('rapports_reconciliation')->where('campagne_id', $id)->first());
    }

    private function publish(string $event, array $payload): void
    {
        try { app(\App\Support\EventBus::class)->publish($event, $payload); } catch (\Throwable $e) { report($e); }
    }

    private function serviceToken(): string
    {
        return env('SERVICE_TOKEN', '');
    }
}
