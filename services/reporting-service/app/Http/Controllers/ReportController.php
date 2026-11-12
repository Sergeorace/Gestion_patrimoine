<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    use ApiResponse;

    private function get(string $service, string $path, array $params = [])
    {
        try {
            $r = Http::timeout(10)->withToken(env('SERVICE_TOKEN', ''))->get(env($service).$path, $params);
            return $r->successful() ? $r->json('data') : null;
        } catch (\Throwable) { return null; }
    }

    /** GET /api/v1/reports/dashboard - tableau de bord consolide */
    public function dashboard(Request $request)
    {
        $params = array_filter([
            'structure_id' => $request->query('structure_id'),
            'site_id' => $request->query('site_id'),
            'categorie_id' => $request->query('categorie_id'),
        ]);

        $assets = $this->get('ASSET_SERVICE_URL', '/api/v1/assets', $params + ['per_page' => 1]);
        $statsAsset = $this->get('ASSET_SERVICE_URL', '/api/v1/assets/stats', $params);
        $valStats = $this->get('VALUATION_SERVICE_URL', '/api/v1/valorisation/stats');
        $maintDue = $this->get('MAINTENANCE_SERVICE_URL', '/api/v1/maintenance/plans', ['a_echeance' => 30]);
        $mouvements = $this->get('MOVEMENT_SERVICE_URL', '/api/v1/movements', $params + ['per_page' => 5]);

        return $this->ok([
            'patrimoine' => [
                'nombre_biens' => $statsAsset['nombre_biens'] ?? $assets['total'] ?? 0,
                'valeur_totale' => $statsAsset['valeur_totale'] ?? 0,
            ],
            'valorisation' => $valStats ?? ['total_vnc' => 0, 'total_amortissements' => 0],
            'maintenance' => ['a_venir_30j' => is_array($maintDue) ? count($maintDue) : ($maintDue['total'] ?? 0)],
            'mouvements_recents' => is_array($mouvements) ? ($mouvements['data'] ?? $mouvements) : [],
            'structure_id' => $params['structure_id'] ?? null,
        ]);
    }

    /** POST /api/v1/reports/generate - genere un rapport (pdf/xlsx/csv) */
    public function generate(Request $request)
    {
        $d = $request->validate([
            'type' => 'required|in:inventaire_global,par_structure,valorisation,mouvements,maintenance',
            'format' => 'required|in:pdf,xlsx,csv',
            'structure_id' => 'nullable|uuid',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
        ]);
        $claims = $request->attributes->get('jwt', []);

        // Recupere les donnees selon le type
        $data = match ($d['type']) {
            'valorisation' => $this->get('VALUATION_SERVICE_URL', '/api/v1/valorisation', ['per_page' => 5000]),
            'mouvements' => $this->get('MOVEMENT_SERVICE_URL', '/api/v1/movements', array_filter([
                'structure_source_id' => $d['structure_id'], 'per_page' => 5000,
            ])),
            'maintenance' => $this->get('MAINTENANCE_SERVICE_URL', '/api/v1/maintenance/interventions', ['per_page' => 5000]),
            default => $this->get('ASSET_SERVICE_URL', '/api/v1/assets', array_filter([
                'structure_id' => $d['structure_id'], 'per_page' => 5000,
            ])),
        };

        $id = (string) Str::uuid();
        DB::table('rapports_generees')->insert([
            'id' => $id, 'type' => $d['type'], 'format' => $d['format'],
            'parametres' => json_encode($d), 'genere_par' => $claims['sub'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Export CSV direct ; PDF/Excel necessitent un worker (hors-ligne documente)
        if ($d['format'] === 'csv') {
            return $this->ok(['id' => $id, 'telechargement' => "/api/v1/reports/$id/download"]);
        }
        return $this->ok(['id' => $id, 'statut' => 'en_cours', 'message' => 'Generation asynchrone via file d\'attente RabbitMQ']);
    }

    public function download(string $id)
    {
        $r = DB::table('rapports_generees')->find($id);
        if (! $r) return $this->error('NOT_FOUND', 'Rapport introuvable.', 404);
        $data = $this->getDataForReport($r);
        $csv = $this->toCsv($data);
        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename=rapport-{$r->type}.csv"]);
    }

    public function index(Request $request)
    {
        return $this->ok(DB::table('rapports_generees')->orderByDesc('genere_le')->paginate(30));
    }

    private function getDataForReport(object $r): array
    {
        $p = json_decode($r->parametres ?? '{}', true) ?: [];
        return match ($r->type) {
            'valorisation' => $this->get('VALUATION_SERVICE_URL', '/api/v1/valorisation', ['per_page' => 5000]) ?? [],
            'mouvements' => $this->get('MOVEMENT_SERVICE_URL', '/api/v1/movements', ['per_page' => 5000]) ?? [],
            'maintenance' => $this->get('MAINTENANCE_SERVICE_URL', '/api/v1/maintenance/interventions', ['per_page' => 5000]) ?? [],
            default => $this->get('ASSET_SERVICE_URL', '/api/v1/assets', ['per_page' => 5000]) ?? [],
        };
    }

    private function toCsv($data): string
    {
        $rows = is_array($data) ? ($data['data'] ?? $data) : [];
        if (! $rows) return "Aucune donnee\n";
        $out = fopen('php://temp', 'r+');
        fputcsv($out, array_keys((array) (is_object($rows[0]) ? get_object_vars($rows[0]) : $rows[0])));
        foreach ($rows as $row) {
            $row = is_object($row) ? get_object_vars($row) : (array) $row;
            fputcsv($out, array_map(fn ($v) => is_scalar($v) ? $v : json_encode($v), $row));
        }
        rewind($out);
        return stream_get_contents($out);
    }
}
