<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->string('type'); // entree, affectation, transfert, sortie
            $table->string('statut')->default('brouillon')->index(); // brouillon, en_attente, validee, executee, cloturee, rejetee, annulee, echouee, bloquee
            $table->uuid('bien_id')->index();
            $table->uuid('structure_source_id')->nullable();
            $table->uuid('structure_dest_id')->nullable();
            $table->uuid('site_dest_id')->nullable();
            $table->text('motif')->nullable();
            $table->uuid('motif_sortie_id')->nullable();
            $table->jsonb('pieces')->nullable();
            $table->uuid('demandeur_id');
            $table->uuid('valideur_id')->nullable();
            $table->integer('niveau_validation')->default(1);
            $table->date('date_demande');
            $table->date('date_effet')->nullable();
            $table->timestamp('date_validation')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('validations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('mouvement_id')->index();
            $table->integer('niveau');
            $table->string('decision'); // approuvee, rejetee
            $table->text('commentaire')->nullable();
            $table->uuid('valideur_id');
            $table->timestamp('date_decision')->useCurrent();
        });

        Schema::create('motifs_sortie', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motifs_sortie');
        Schema::dropIfExists('validations');
        Schema::dropIfExists('mouvements');
    }
};
