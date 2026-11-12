<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->string('type')->index(); // facture, titre, pv, photo, contrat, autre
            $table->string('nom_fichier');
            $table->string('chemin'); // chemin dans le volume documents
            $table->string('mime_type')->nullable();
            $table->bigInteger('taille')->nullable();
            $table->uuid('entite_type_id')->nullable()->index(); // uuid du bien/mouvement/campagne
            $table->string('entite_type')->nullable();
            $table->uuid('uploade_par')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('versions_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id')->index();
            $table->integer('version');
            $table->string('chemin');
            $table->uuid('cree_par')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versions_documents');
        Schema::dropIfExists('documents');
    }
};
