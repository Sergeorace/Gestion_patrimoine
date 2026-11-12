<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;

class HealthController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->ok([
            'service' => config('app.name', 'service'),
            'status' => 'ok',
            'time' => now()->toIso8601String(),
        ]);
    }
}
