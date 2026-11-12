<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const page = usePage();
const user = computed(() => page.props.auth?.user);
const nav = [
  ['Tableau de bord', '/tableau-de-bord'],
  ['Biens', '/biens'],
  ['Affectations', '/mouvements'],
  ['Maintenance', '/maintenance'],
  ['Inventaires', '/inventaires'],
  ['Rapports', '/rapports'],
  ['Administration', '/admin'],
];
function logout() { localStorage.removeItem('jwt'); window.location.href = '/connexion'; }
</script>
<template>
  <div class="min-h-screen flex">
    <aside class="w-56 bg-slate-900 text-slate-200 flex flex-col fixed inset-y-0">
      <div class="p-4 font-bold text-white border-b border-slate-700">Patrimoine État</div>
      <nav class="flex-1 p-2 space-y-1">
        <Link v-for="[label, href] in nav" :key="href" :href="href"
          class="block px-3 py-2 rounded hover:bg-slate-800">{{ label }}</Link>
      </nav>
      <div class="p-4 text-xs border-t border-slate-700">
        <div class="truncate">{{ user?.email }}</div>
        <button @click="logout" class="mt-2 text-red-300 hover:text-red-200">Déconnexion</button>
      </div>
    </aside>
    <main class="flex-1 ml-56 p-6"><slot /></main>
  </div>
</template>
