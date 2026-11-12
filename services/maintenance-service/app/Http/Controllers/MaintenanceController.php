<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MaintenanceController extends Controller
{
    use ApiResponse;

    // ---------- PLANS ----------
    public function plans(Request $request)
    {
        $q = DB::table('plans_maintenance')->where('actif', true);
        if ($b = $request->query('bien_id')) $q->where('bien_id', $b);
        return $this->ok($q->orderBy('prochaine_echeance')->paginate(50));
    }

    public function storePlan(Request $request)
    {
        $d = $request->validate([
            'bien_id' => 'required|uuid',
            'type' => 'required|in:preventive,curative',
            'description' => 'nullable|string',
            'periodicite_mois' => 'required|integer|min:1|max:120',
            'prochaine_echeance' => 'required|date',
        ]);
        $id = (string) Str::uuid();
        DB::table('plans_maintenance')->insert($d + ['id' => $id, 'created_at' => now(), 'updated_at' => now()]);
        return $this->created(DB::table('plans_maintenance')->find($id));
    }

    public function updatePlan(Request $request, string $id)
    {
        $d = $request->validate([
            'description' => 'nullable|string',
            'periodicite_mois' => 'sometimes|integer|min:1|max:120',
            'prochaine_echeance' => 'sometimes|date',
            'actif' => 'sometimes|boolean',
        ]);
        DB::table('plans_maintenance')->where('id', $id)->update($d + ['updated_at' => now()]);
        return $this->ok(DB::table('plans_maintenance')->find($id));
    }

    // ---------- INTERVENTIONS ----------
    public function interventions(Request $request)
    {
        $q = DB::table('interventions')->orderByDesc('created_at');
        foreach (['bien_id', 'statut', 'type', 'agent_id', 'prestataire_id'] as $f) {
            if ($v = $request->query($f)) $q->where($f, $v);
        }
        if ($from = $request->query('date_from')) $q->where('date_prevue', '>=', $from);
        if ($to = $request->query('date_to')) $q->where('date_prevue', '<=', $to);
        return $this->ok($q->paginate(50));
    }

    public function show(string $id)
    {
        $i = DB::table('interventions')->find($id);
        if (! $i) return $this->error('NOT_FOUND', 'Intervention introuvable.', 404);
        return $this->ok($i);
    }

    /** POST /api/v1/maintenance/pannes - declaration de panne */
    public function declarePanne(Request $request)
    {
        $claims = $request->attributes->get('jwt', []);
        $d = $request->validate([
            'bien_id' => 'required|uuid',
            'description' => 'required|string|max:2000',
            'gravite' => 'required|in:faible,moyenne,forte,critique',
            'photos' => 'nullable|array',
        ]);
        $id = (string) Str::uuid();
        DB::transaction(function () use ($d, $id, $claims) {
            DB::table('pannes')->insert([
                'id' => $id, 'bien_id' => $d['bien_id'], 'declare_par' => $claims['sub'] ?? null,
                'description' => $d['description'], 'gravite' => $d['gravite'],
                'declare_le' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('interventions')->insert([
                'id' => (string) Str::uuid(), 'bien_id' => $d['bien_id'], 'type' => 'panne',
                'statut' => 'declaree', 'description' => $d['description'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        $this->patchAsset($d['bien_id'], ['etat' => 'degrade']);
        $this->publish('maintenance.panne', ['bien_id' => $d['bien_id'], 'panne_id' => $id]);
        return $this->created(DB::table('pannes')->find($id));
    }

    /** POST /api/v1/maintenance/interventions - ordre d'intervention */
    public function storeIntervention(Request $request)
    {
        $d = $request->validate([
            'bien_id' => 'required|uuid',
            'type' => 'required|in:preventive,curative,panne',
            'description' => 'nullable|string',
            'date_prevue' => 'required|date',
            'agent_id' => 'nullable|uuid',
            'prestataire_id' => 'nullable|uuid|exists:prestataires,id',
            'plan_id' => 'nullable|uuid',
            'cout_main_oeuvre' => 'nullable|numeric|min:0',
        ]);
        $id = (string) Str::uuid();
        DB::table('interventions')->insert($d + ['id' => $id, 'statut' => 'planifiee', 'created_at' => now(), 'updated_at' => now()]);
        $this->patchAsset($d['bien_id'], ['statut' => 'en_maintenance']);
        return $this->created(DB::table('interventions')->find($id));
    }

    /** POST /api/v1/maintenance/interventions/{id}/start */
    public function start(string $id)
    {
        $i = DB::table('interventions')->find($id);
        if (! $i || ! in_array($i->statut, ['declaree', 'planifiee'], true)) {
            return $this->error('INVALID_STATE', 'Intervention non demarrable.', 409);
        }
        DB::table('interventions')->where('id', $id)->update(['statut' => 'en_cours', 'updated_at' => now()]);
        return $this->ok(DB::table('interventions')->find($id));
    }

    /** POST /api/v1/maintenance/interventions/{id}/close - compte-rendu + cout */
    public function close(Request $request, string $id)
    {
        $i = DB::table('interventions')->find($id);
        if (! $i || $i->statut !== 'en_cours') {
            return $this->error('INVALID_STATE', 'Seule une intervention en cours peut etre cloturee.', 409);
        }
        $d = $request->validate([
            'compte_rendu' => 'required|string',
            'cout_main_oeuvre' => 'nullable|numeric|min:0',
            'cout_pieces' => 'nullable|numeric|min:0',
        ]);
        DB::table('interventions')->where('id', $id)->update($d + [
            'statut' => 'cloturee', 'date_realisee' => now()->toDateString(), 'updated_at' => now(),
        ]);
        // Le bien repasse en etat disponible/affecte - on notifie asset-service
        $this->patchAsset($i->bien_id, ['statut' => 'disponible', 'etat' => 'bon']);
        if ($i->plan_id) {
            $plan = DB::table('plans_maintenance')->find($i->plan_id);
            if ($plan) {
                DB::table('plans_maintenance')->where('id', $plan->id)->update([
                    'prochaine_echeance' => now()->addMonths($plan->periodicite_mois)->toDateString(),
                    'updated_at' => now(),
                ]);
            }
        }
        $this->publish('maintenance.cloturee', ['intervention_id' => $id, 'bien_id' => $i->bien_id]);
        return $this->ok(DB::table('interventions')->find($id));
    }

    // ---------- PRESTATAIRES ----------
    public function prestataires()
    {
        return $this->ok(DB::table('prestataires')->where('actif', true)->get());
    }

    public function storePrestataire(Request $request)
    {
        $d = $request->validate([
            'nom' => 'required|string|max:200',
            'specialite' => 'nullable|string|max:100',
            'contact' => 'nullable|string|max:200',
            'adresse' => 'nullable|string',
        ]);
        $id = (string) Str::uuid();
        DB::table('prestataires')->insert($d + ['id' => $id, 'created_at' => now(), 'updated_at' => now()]);
        return $this->created(DB::table('prestataires')->find($id));
    }

    private function patchAsset(string $bienId, array $payload): void
    {
        try {
            Http::timeout(5)->withToken(env('SERVICE_TOKEN', ''))->patch(
                env('ASSET_SERVICE_URL')."/api/v1/assets/$bienId/etat", $payload);
        } catch (\Throwable) {}
    }

    private function publish(string $event, array $payload): void
    {
        try { app(\App\Support\EventBus::class)->publish($event, $payload); } catch (\Throwable $e) { report($e); }
    }
}
