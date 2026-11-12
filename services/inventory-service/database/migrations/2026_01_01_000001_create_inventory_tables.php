<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campagnes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->uuid('structure_id')->nullable();
            $table->uuid('site_id')->nullable();
            $table->uuid('categorie_id')->nullable();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('statut')->default('planifiee')->index(); // planifiee, en_cours, cloturee, annulee
            $table->uuid('cree_par')->nullable();
            $table->timestamp('cloture_le')->nullable();
            $table->timestamps();
        });

        Schema::create('equipes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campagne_id')->index();
            $table->string('nom');
            $table->text('role')->nullable();
            $table->timestamps();
        });

        Schema::create('membres_equipe', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('equipe_id')->index();
            $table->uuid('agent_id');
            $table->string('role')->nullable();
            $table->timestamps();
        });

        Schema::create('constates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campagne_id')->index();
            $table->uuid('bien_id')->index();
            $table->boolean('present')->default(false);
            $table->string('etat_constate')->nullable(); // bon, degrade, hors_service
            $table->text('commentaire')->nullable();
            $table->uuid('agent_id')->nullable();
            $table->timestamp('date_constate')->useCurrent();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('synchronise')->default(true);
            $table->timestamps();
            $table->unique(['campagne_id', 'bien_id']);
        });

        Schema::create('ecarts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campagne_id')->index();
            $table->uuid('bien_id')->nullable();
            $table->string('type'); // manquant, excédentaire, degrade
            $table->string('statut_traitement')->default('ouvert');
            $table->text('resolution')->nullable();
            $table->timestamps();
        });

        Schema::create('rapports_reconciliation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campagne_id')->unique();
            $table->integer('total_attendus')->default(0);
            $table->integer('total_presents')->default(0);
            $table->integer('manquants')->default(0);
            $table->integer('excdentaires')->default(0);
            $table->integer('degrades')->default(0);
            $table->decimal('taux_couverture', 5, 2)->default(0);
            $table->timestamp('genere_le')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapports_reconciliation');
        Schema::dropIfExists('ecarts');
        Schema::dropIfExists('constates');
        Schema::dropIfExists('membres_equipe');
        Schema::dropIfExists('equipes');
        Schema::dropIfExists('campagnes');
    }
};
