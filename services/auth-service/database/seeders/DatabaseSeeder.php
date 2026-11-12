<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Permissions (matrice RBAC du CDC §3.11) ----
        $permissions = [
            ['code' => 'utilisateur.gerer', 'module' => 'admin', 'action' => 'gerer'],
            ['code' => 'role.gerer', 'module' => 'admin', 'action' => 'gerer'],
            ['code' => 'referentiel.gerer', 'module' => 'organisation', 'action' => 'gerer'],
            ['code' => 'bien.lire', 'module' => 'patrimoine', 'action' => 'lire'],
            ['code' => 'bien.creer', 'module' => 'patrimoine', 'action' => 'creer'],
            ['code' => 'bien.modifier', 'module' => 'patrimoine', 'action' => 'modifier'],
            ['code' => 'mouvement.creer', 'module' => 'mouvements', 'action' => 'creer'],
            ['code' => 'mouvement.valider', 'module' => 'mouvements', 'action' => 'valider'],
            ['code' => 'inventaire.gerer', 'module' => 'inventaire', 'action' => 'gerer'],
            ['code' => 'inventaire.saisir', 'module' => 'inventaire', 'action' => 'saisir'],
            ['code' => 'maintenance.gerer', 'module' => 'maintenance', 'action' => 'gerer'],
            ['code' => 'valorisation.lire', 'module' => 'valorisation', 'action' => 'lire'],
            ['code' => 'valorisation.gerer', 'module' => 'valorisation', 'action' => 'gerer'],
            ['code' => 'document.gerer', 'module' => 'documents', 'action' => 'gerer'],
            ['code' => 'audit.lire', 'module' => 'audit', 'action' => 'lire'],
            ['code' => 'rapport.lire', 'module' => 'reporting', 'action' => 'lire'],
            ['code' => '*', 'module' => 'systeme', 'action' => 'all'],
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['code' => $p['code']], $p);
        }

        // ---- Roles ----
        $roles = [
            'admin' => ['libelle' => 'Administrateur systeme', 'perms' => ['*']],
            'gestionnaire' => ['libelle' => 'Gestionnaire central du patrimoine', 'perms' => [
                'referentiel.gerer', 'bien.lire', 'bien.creer', 'bien.modifier',
                'mouvement.creer', 'mouvement.valider', 'inventaire.gerer',
                'maintenance.gerer', 'valorisation.lire', 'valorisation.gerer',
                'document.gerer', 'audit.lire', 'rapport.lire',
            ]],
            'responsable' => ['libelle' => 'Responsable de structure', 'perms' => [
                'bien.lire', 'bien.creer', 'bien.modifier', 'mouvement.creer',
                'inventaire.gerer', 'maintenance.gerer', 'document.gerer',
                'valorisation.lire', 'rapport.lire',
            ]],
            'inventoriste' => ['libelle' => 'Agent inventorialiste', 'perms' => [
                'bien.lire', 'inventaire.saisir', 'document.gerer',
            ]],
            'comptable' => ['libelle' => 'Comptable matieres', 'perms' => [
                'bien.lire', 'valorisation.lire', 'valorisation.gerer',
                'mouvement.lire', 'rapport.lire', 'audit.lire',
            ]],
            'maintenance' => ['libelle' => 'Agent de maintenance', 'perms' => [
                'bien.lire', 'maintenance.gerer', 'document.gerer',
            ]],
            'auditeur' => ['libelle' => 'Auditeur / Controleur', 'perms' => [
                'bien.lire', 'audit.lire', 'rapport.lire', 'valorisation.lire',
            ]],
            'decideur' => ['libelle' => 'Decideur', 'perms' => ['rapport.lire', 'bien.lire']],
        ];
        foreach ($roles as $code => $def) {
            $role = Role::firstOrCreate(['code' => $code], [
                'libelle' => $def['libelle'], 'description' => null,
            ]);
            $permIds = Permission::whereIn('code', $def['perms'])->pluck('id');
            $role->permissions()->sync($permIds);
        }

        // ---- Utilisateurs de demonstration ----
        $users = [
            ['email' => 'admin@etat.tg', 'nom' => 'AGBEKO', 'prenom' => 'Kossi', 'roles' => ['admin']],
            ['email' => 'gestionnaire@etat.tg', 'nom' => 'MENSAH', 'prenom' => 'Komi', 'roles' => ['gestionnaire']],
            ['email' => 'resp.defense@etat.tg', 'nom' => 'LAWSON', 'prenom' => 'Ama', 'roles' => ['responsable']],
            ['email' => 'inventoriste@etat.tg', 'nom' => 'TETOU', 'prenom' => 'Efoe', 'roles' => ['inventoriste']],
            ['email' => 'comptable@etat.tg', 'nom' => 'AKPAN', 'prenom' => 'Yao', 'roles' => ['comptable']],
            ['email' => 'maintenance@etat.tg', 'nom' => 'GBEFO', 'prenom' => 'Sena', 'roles' => ['maintenance']],
            ['email' => 'auditeur@courdescomptes.tg', 'nom' => 'DOSSOU', 'prenom' => 'Mawuli', 'roles' => ['auditeur']],
            ['email' => 'decideur@primature.tg', 'nom' => 'KOFFI', 'prenom' => 'Nukunya', 'roles' => ['decideur']],
        ];
        foreach ($users as $u) {
            $user = User::firstOrCreate(['email' => $u['email']], [
                'nom' => $u['nom'], 'prenom' => $u['prenom'],
                'mot_de_passe_hash' => Hash::make('Patrimoine2026!'),
                'statut' => 'actif',
            ]);
            $user->roles()->sync(Role::whereIn('code', $u['roles'])->pluck('id'));
        }
    }
}
