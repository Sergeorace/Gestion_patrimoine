<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $motifs = [
            ['code' => 'REFORME', 'libelle' => 'Réforme (fin de vie / obsolescence)'],
            ['code' => 'CESSION', 'libelle' => 'Cession / vente'],
            ['code' => 'REFORME_ACC', 'libelle' => 'Réforme après accident'],
            ['code' => 'PERTE', 'libelle' => 'Perte'],
            ['code' => 'VOL', 'libelle' => 'Vol'],
            ['code' => 'DESTRUCTION', 'libelle' => 'Destruction'],
        ];
        foreach ($motifs as $m) {
            DB::table('motifs_sortie')->updateOrInsert(
                ['code' => $m['code']],
                $m + ['id' => (string) Str::uuid(), 'actif' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
