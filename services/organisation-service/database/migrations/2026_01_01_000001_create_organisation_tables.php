<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->string('type'); // ministere, direction, service, unite
            $table->uuid('parent_id')->nullable()->index();
            $table->string('statut')->default('actif');
            $table->text('adresse')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->uuid('structure_id')->index();
            $table->string('libelle');
            $table->string('region')->nullable();
            $table->string('prefecture')->nullable();
            $table->string('commune')->nullable();
            $table->text('adresse')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('localisations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('site_id')->index();
            $table->string('batiment')->nullable();
            $table->string('etage')->nullable();
            $table->string('bureau')->nullable();
            $table->text('precision')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->uuid('parent_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->integer('duree_vie_mois')->nullable();
            $table->string('methode_amortissement')->default('lineaire'); // lineaire, degressif
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('nomenclatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // etat, mode_acquisition, motif_sortie, type_mouvement
            $table->string('code');
            $table->string('libelle');
            $table->integer('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['type', 'code']);
        });

        Schema::create('parametres_amortissement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('categorie_id')->index();
            $table->string('methode');
            $table->numeric('taux', 6, 4)->nullable();
            $table->integer('duree_vie_mois');
            $table->date('date_effet');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres_amortissement');
        Schema::dropIfExists('nomenclatures');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('localisations');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('structures');
    }
};
