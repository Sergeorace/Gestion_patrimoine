<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('utilisateur_id')->nullable()->index();
            $table->uuid('structure_id')->nullable();
            $table->string('type')->index(); // maintenance, garantie, inventaire, mouvement, systeme
            $table->string('titre');
            $table->text('message');
            $table->string('entite_type')->nullable();
            $table->uuid('entite_id')->nullable();
            $table->boolean('lu')->default(false)->index();
            $table->timestamp('envoye_le')->useCurrent();
            $table->timestamps();
        });

        Schema::create('preferences_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('utilisateur_id')->index();
            $table->string('type');
            $table->string('canal')->default('in_app'); // in_app, email
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['utilisateur_id', 'type', 'canal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preferences_notifications');
        Schema::dropIfExists('notifications');
    }
};
