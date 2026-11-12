<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code_inventaire')->unique();
            $table->uuid('uuid')->unique();
            $table->string('designation');
            $table->text('description')->nullable();
            $table->uuid('categorie_id')->index();
            $table->string('type_specifique')->nullable(); // immobilier, vehicule, informatique, mobilier, autre
            $table->jsonb('caracteristiques')->nullable();
            $table->date('date_acquisition');
            $table->string('mode_acquisition')->default('achat');
            $table->decimal('valeur_acquisition', 15, 2);
            $table->string('devise')->default('XOF');
            $table->uuid('structure_id')->index();
            $table->uuid('site_id')->nullable();
            $table->uuid('localisation_id')->nullable();
            $table->string('etat')->default('bon'); // bon, degrade, hors_service
            $table->string('statut')->default('enregistre')->index(); // enregistre, disponible, affecte, en_maintenance, propose_sortie, sorti
            $table->uuid('cree_par')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['structure_id', 'statut']);
            $table->index('date_acquisition');
        });

        Schema::create('specifiques_immobiliers', function (Blueprint $table) {
            $table->uuid('bien_id')->primary();
            $table->decimal('superficie', 12, 2)->nullable();
            $table->string('reference_cadastrale')->nullable();
            $table->string('titre_foncier')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
        Schema::create('specifiques_vehicules', function (Blueprint $table) {
            $table->uuid('bien_id')->primary();
            $table->string('immatriculation')->unique();
            $table->string('chassis')->nullable();
            $table->string('marque')->nullable();
            $table->string('modele')->nullable();
            $table->smallInteger('annee')->nullable();
            $table->integer('kilometrage')->nullable();
        });
        Schema::create('specifiques_informatique', function (Blueprint $table) {
            $table->uuid('bien_id')->primary();
            $table->string('numero_serie')->unique();
            $table->string('configuration')->nullable();
            $table->string('os')->nullable();
            $table->string('processeur')->nullable();
            $table->integer('ram_go')->nullable();
        });
        Schema::create('photos_biens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bien_id')->index();
            $table->string('chemin');
            $table->string('legende')->nullable();
            $table->boolean('principale')->default(false);
            $table->timestamp('ajoute_le')->useCurrent();
        });
        Schema::create('imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom_fichier');
            $table->uuid('lance_par');
            $table->integer('total_lignes')->default(0);
            $table->integer('lignes_valides')->default(0);
            $table->integer('lignes_en_erreur')->default(0);
            $table->string('statut')->default('en_cours');
            $table->timestamp('termine_le')->nullable();
            $table->timestamps();
        });
        Schema::create('import_anomalies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('import_id')->index();
            $table->integer('ligne');
            $table->string('champ')->nullable();
            $table->string('valeur')->nullable();
            $table->string('message');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_anomalies');
        Schema::dropIfExists('imports');
        Schema::dropIfExists('photos_biens');
        Schema::dropIfExists('specifiques_informatique');
        Schema::dropIfExists('specifiques_vehicules');
        Schema::dropIfExists('specifiques_immobiliers');
        Schema::dropIfExists('biens');
    }
};
