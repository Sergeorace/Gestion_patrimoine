<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $q = DB::table('documents')->orderByDesc('created_at');
        foreach (['type', 'entite_type', 'entite_type_id'] as $f) {
            if ($v = $request->query($f)) $q->where($f, $v);
        }
        if ($s = $request->query('search')) $q->where(fn ($w) => $w->where('reference', 'ilike', "%$s%")->orWhere('nom_fichier', 'ilike', "%$s%"));
        return $this->ok($q->paginate(50));
    }

    public function show(string $id)
    {
        $doc = DB::table('documents')->find($id);
        if (! $doc) return $this->error('NOT_FOUND', 'Document introuvable.', 404);
        $doc->versions = DB::table('versions_documents')->where('document_id', $id)->orderByDesc('version')->get();
        return $this->ok($doc);
    }

    /** POST /api/v1/documents - upload multipart (fichier requis) */
    public function store(Request $request)
    {
        $claims = $request->attributes->get('jwt', []);
        $d = $request->validate([
            'type' => 'required|in:facture,titre,pv,photo,contrat,autre',
            'entite_type' => 'nullable|string|max:50',
            'entite_type_id' => 'nullable|uuid',
            'description' => 'nullable|string|max:1000',
            'fichier' => 'required|file|max:20480', // 20 Mo
        ]);

        $file = $request->file('fichier');
        $docId = (string) Str::uuid();
        $dir = "documents/{$docId}";
        $path = $file->storeAs($dir, Str::slug($file->getClientOriginalName()).'.'.$file->getClientOriginalExtension(), 'local');
        // En production : disque 'documents' (volume dedie) ; ici local pour simplicite.

        $id = (string) Str::uuid();
        DB::table('documents')->insert([
            'id' => $id, 'reference' => 'DOC-'.now()->format('Y').'-'.strtoupper(Str::random(6)),
            'type' => $d['type'], 'nom_fichier' => $file->getClientOriginalName(),
            'chemin' => $path, 'mime_type' => $file->getClientMimeType(),
            'taille' => $file->getSize(), 'entite_type' => $d['entite_type'] ?? null,
            'entite_type_id' => $d['entite_type_id'] ?? null,
            'uploade_par' => $claims['sub'] ?? null, 'description' => $d['description'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('versions_documents')->insert([
            'id' => (string) Str::uuid(), 'document_id' => $id, 'version' => 1,
            'chemin' => $path, 'cree_par' => $claims['sub'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $this->created(DB::table('documents')->find($id));
    }

    /** GET /api/v1/documents/{id}/download - telecharge le fichier */
    public function download(string $id)
    {
        $doc = DB::table('documents')->find($id);
        if (! $doc || ! file_exists(storage_path('app/'.$doc->chemin))) {
            return $this->error('NOT_FOUND', 'Fichier introuvable.', 404);
        }
        return response()->download(storage_path('app/'.$doc->chemin), $doc->nom_fichier);
    }

    /** POST /api/v1/documents/{id}/versions - nouvelle version */
    public function newVersion(Request $request, string $id)
    {
        $doc = DB::table('documents')->find($id);
        if (! $doc) return $this->error('NOT_FOUND', 'Document introuvable.', 404);
        $request->validate(['fichier' => 'required|file|max:20480']);
        $file = $request->file('fichier');
        $path = $file->storeAs('documents/'.$id, 'v'.(DB::table('versions_documents')->where('document_id', $id)->count() + 1).'.'.$file->getClientOriginalExtension(), 'local');
        $v = DB::table('versions_documents')->where('document_id', $id)->count() + 1;
        DB::table('versions_documents')->insert([
            'id' => (string) Str::uuid(), 'document_id' => $id, 'version' => $v,
            'chemin' => $path, 'cree_par' => $request->attributes->get('jwt')['sub'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $this->ok(['version' => $v]);
    }

    public function destroy(string $id)
    {
        DB::table('documents')->where('id', $id)->delete();
        DB::table('versions_documents')->where('document_id', $id)->delete();
        return $this->ok(['supprime' => true]);
    }
}
