<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapports_generees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type')->index(); // inventaire_global, par_structure, valorisation, mouvements, maintenance
            $table->jsonb('parametres')->nullable();
            $table->string('format')->default('pdf'); // pdf, xlsx, csv
            $table->string('chemin')->nullable();
            $table->uuid('genere_par')->nullable();
            $table->timestamp('genere_le')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapports_generees');
    }
};
