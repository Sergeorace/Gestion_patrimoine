<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->string('mot_de_passe_hash');
            $table->uuid('structure_id')->nullable()->index();
            $table->string('statut')->default('actif'); // actif, suspendu, desactive
            $table->boolean('two_factor_actif')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->string('two_factor_backup')->nullable();
            $table->integer('echecs_connexion')->default(0);
            $table->timestamp('verrouille_jusqu_a')->nullable();
            $table->timestamp('dernier_login_at')->nullable();
            $table->timestamp('must_change_password_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
