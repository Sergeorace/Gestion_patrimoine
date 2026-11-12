<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganisationController extends Controller
{
    use ApiResponse;

    // ---------- STRUCTURES ----------
    public function structures(Request $request)
    {
        $q = DB::table('structures')->whereNull('deleted_at');
        if ($t = $request->query('type')) $q->where('type', $t);
        if ($p = $request->query('parent_id')) $q->where('parent_id', $p);
        if ($s = $request->query('search')) $q->where(fn ($x) => $x->where('libelle', 'ilike', "%$s%")->orWhere('code', 'ilike', "%$s%"));
        return $this->ok($q->orderBy('type')->orderBy('libelle')->paginate(min((int) $request->query('per_page', 50), 200)));
    }

    public function storeStructure(Request $request)
    {
        $d = $request->validate([
            'code' => 'required|string|max:30|unique:structures,code',
            'libelle' => 'required|string|max:200',
            'type' => 'required|in:ministere,direction,service,unite',
            'parent_id' => 'nullable|uuid|exists:structures,id',
            'adresse' => 'nullable|string',
        ]);
        $id = (string) Str::uuid();
        DB::table('structures')->insert($d + ['id' => $id, 'created_at' => now(), 'updated_at' => now()]);
        return $this->created(DB::table('structures')->find($id));
    }

    public function updateStructure(Request $request, string $id)
    {
        $d = $request->validate([
            'libelle' => 'sometimes|string|max:200',
            'statut' => 'sometimes|in:actif,suspendu',
            'adresse' => 'nullable|string',
            'parent_id' => 'nullable|uuid|exists:structures,id',
        ]);
        DB::table('structures')->where('id', $id)->update($d + ['updated_at' => now()]);
        return $this->ok(DB::table('structures')->find($id));
    }

    public function destroyStructure(string $id)
    {
        DB::table('structures')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        return $this->ok(['message' => 'Structure archivee.']);
    }

    // ---------- SITES ----------
    public function sites(Request $request)
    {
        $q = DB::table('sites')->whereNull('deleted_at');
        if ($st = $request->query('structure_id')) $q->where('structure_id', $st);
        if ($s = $request->query('search')) $q->where('libelle', 'ilike', "%$s%");
        return $this->ok($q->orderBy('libelle')->paginate(min((int) $request->query('per_page', 50), 200)));
    }

    public function storeSite(Request $request)
    {
        $d = $request->validate([
            'code' => 'required|string|max:30|unique:sites,code',
            'structure_id' => 'required|uuid|exists:structures,id',
            'libelle' => 'required|string|max:200',
            'region' => 'nullable|string|max:100',
            'prefecture' => 'nullable|string|max:100',
            'commune' => 'nullable|string|max:100',
            'adresse' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);
        $id = (string) Str::uuid();
        DB::table('sites')->insert($d + ['id' => $id, 'created_at' => now(), 'updated_at' => now()]);
        return $this->created(DB::table('sites')->find($id));
    }

    public function updateSite(Request $request, string $id)
    {
        $d = $request->validate([
            'libelle' => 'sometimes|string|max:200',
            'region' => 'nullable|string|max:100',
            'prefecture' => 'nullable|string|max:100',
            'commune' => 'nullable|string|max:100',
            'adresse' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);
        DB::table('sites')->where('id', $id)->update($d + ['updated_at' => now()]);
        return $this->ok(DB::table('sites')->find($id));
    }

    // ---------- CATEGORIES ----------
    public function categories(Request $request)
    {
        $q = DB::table('categories')->whereNull('deleted_at');
        if ($request->boolean('arbre', false)) {
            return $this->ok($q->orderBy('libelle')->get());
        }
        return $this->ok($q->orderBy('libelle')->paginate(100));
    }

    public function storeCategory(Request $request)
    {
        $d = $request->validate([
            'code' => 'required|string|max:30|unique:categories,code',
            'libelle' => 'required|string|max:200',
            'parent_id' => 'nullable|uuid|exists:categories,id',
            'duree_vie_mois' => 'nullable|integer|min:1|max:1200',
            'methode_amortissement' => 'nullable|in:lineaire,degressif',
            'description' => 'nullable|string',
        ]);
        $id = (string) Str::uuid();
        DB::table('categories')->insert($d + ['id' => $id, 'created_at' => now(), 'updated_at' => now()]);
        return $this->created(DB::table('categories')->find($id));
    }

    public function updateCategory(Request $request, string $id)
    {
        $d = $request->validate([
            'libelle' => 'sometimes|string|max:200',
            'duree_vie_mois' => 'nullable|integer|min:1|max:1200',
            'methode_amortissement' => 'nullable|in:lineaire,degressif',
            'actif' => 'sometimes|boolean',
        ]);
        DB::table('categories')->where('id', $id)->update($d + ['updated_at' => now()]);
        return $this->ok(DB::table('categories')->find($id));
    }

    // ---------- NOMENCLATURES ----------
    public function nomenclatures(Request $request)
    {
        $q = DB::table('nomenclatures');
        if ($t = $request->query('type')) $q->where('type', $t);
        return $this->ok($q->orderBy('type')->orderBy('ordre')->get());
    }

    public function storeNomenclature(Request $request)
    {
        $d = $request->validate([
            'type' => 'required|string|max:50',
            'code' => 'required|string|max:50',
            'libelle' => 'required|string|max:200',
            'ordre' => 'nullable|integer',
        ]);
        $id = (string) Str::uuid();
        DB::table('nomenclatures')->insert($d + ['id' => $id, 'created_at' => now(), 'updated_at' => now()]);
        return $this->created(DB::table('nomenclatures')->find($id));
    }
}
