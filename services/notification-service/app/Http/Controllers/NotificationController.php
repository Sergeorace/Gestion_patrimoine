<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    use ApiResponse;

    /** GET /api/v1/notifications - mes notifications (JWT) */
    public function index(Request $request)
    {
        $claims = $request->attributes->get('jwt', []);
        $q = DB::table('notifications')->orderByDesc('envoye_le');
        if ($uid = $request->query('utilisateur_id', $claims['sub'] ?? null)) $q->where('utilisateur_id', $uid);
        if ($request->has('non_lues')) $q->where('lu', false);
        if ($t = $request->query('type')) $q->where('type', $t);
        return $this->ok($q->paginate(30));
    }

    public function unreadCount(Request $request)
    {
        $claims = $request->attributes->get('jwt', []);
        return $this->ok(['non_lues' => DB::table('notifications')->where('utilisateur_id', $claims['sub'] ?? '')->where('lu', false)->count()]);
    }

    /** POST /api/v1/notifications - creation (aussi appele par les autres services) */
    public function store(Request $request)
    {
        $d = $request->validate([
            'utilisateur_id' => 'nullable|uuid',
            'structure_id' => 'nullable|uuid',
            'type' => 'required|string|max:50',
            'titre' => 'nullable|string|max:200',
            'message' => 'required|string|max:2000',
            'entite_type' => 'nullable|string|max:50',
            'entite_id' => 'nullable|uuid',
        ]);
        $id = (string) Str::uuid();
        DB::table('notifications')->insert($d + [
            'id' => $id, 'titre' => $d['titre'] ?? $d['type'],
            'envoye_le' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $this->created(DB::table('notifications')->find($id));
    }

    /** POST /api/v1/notifications/{id}/read */
    public function markRead(string $id)
    {
        DB::table('notifications')->where('id', $id)->update(['lu' => true, 'updated_at' => now()]);
        return $this->ok(['lu' => true]);
    }

    /** POST /api/v1/notifications/read-all */
    public function markAllRead(Request $request)
    {
        $claims = $request->attributes->get('jwt', []);
        DB::table('notifications')->where('utilisateur_id', $claims['sub'] ?? '')->where('lu', false)->update(['lu' => true, 'updated_at' => now()]);
        return $this->ok(['ok' => true]);
    }
}
