<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestataires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('specialite')->nullable();
            $table->string('contact')->nullable();
            $table->text('adresse')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('plans_maintenance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bien_id')->index();
            $table->string('type'); // preventive, curative
            $table->text('description')->nullable();
            $table->integer('periodicite_mois');
            $table->date('prochaine_echeance')->index();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('interventions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bien_id')->index();
            $table->uuid('plan_id')->nullable();
            $table->string('type'); // preventive, panne, curative
            $table->string('statut')->default('declaree')->index(); // declaree, planifiee, en_cours, en_attente_pieces, cloturee, annulee
            $table->text('description')->nullable();
            $table->date('date_prevue')->nullable();
            $table->date('date_realisee')->nullable();
            $table->uuid('agent_id')->nullable();
            $table->uuid('prestataire_id')->nullable();
            $table->numeric('cout_main_oeuvre', 15, 2)->nullable();
            $table->numeric('cout_pieces', 15, 2)->default(0);
            $table->text('compte_rendu')->nullable();
            $table->timestamps();
        });

        Schema::create('pannes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bien_id')->index();
            $table->uuid('declare_par');
            $table->text('description');
            $table->string('gravite')->default('moyenne'); // faible, moyenne, forte, critique
            $table->timestamp('declare_le')->useCurrent();
            $table->uuid('intervention_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pannes');
        Schema::dropIfExists('interventions');
        Schema::dropIfExists('plans_maintenance');
        Schema::dropIfExists('prestataires');
    }
};
