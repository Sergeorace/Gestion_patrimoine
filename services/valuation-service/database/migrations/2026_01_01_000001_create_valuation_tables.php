<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valorisations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bien_id')->index();
            $table->integer('exercice');
            $table->decimal('valeur_acquisition', 15, 2)->default(0);
            $table->decimal('amortissement_cumule', 15, 2)->default(0);
            $table->decimal('vnc', 15, 2)->default(0);
            $table->date('date_calcul');
            $table->timestamps();
            $table->unique(['bien_id', 'exercice']);
        });

        Schema::create('amortissements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bien_id')->index();
            $table->integer('exercice');
            $table->decimal('dotation', 15, 2);
            $table->string('methode');
            $table->decimal('taux_applique', 6, 4)->nullable();
            $table->jsonb('detail_calcul')->nullable();
            $table->timestamps();
        });

        Schema::create('reevaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bien_id')->index();
            $table->date('date_reevaluation');
            $table->decimal('ancienne_valeur', 15, 2);
            $table->decimal('nouvelle_valeur', 15, 2);
            $table->text('motif');
            $table->uuid('autorise_par')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reevaluations');
        Schema::dropIfExists('amortissements');
        Schema::dropIfExists('valorisations');
    }
};
