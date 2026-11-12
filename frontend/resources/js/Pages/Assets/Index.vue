<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import { api } from '../../api';
import { Link } from '@inertiajs/vue3';
const items = ref([]), total = ref(0), page = ref(1), q = ref(''), err = ref('');
async function load(p = 1) {
  try {
    const r = await api.get(`/assets?search=${encodeURIComponent(q.value)}&page=${p}&per_page=20`);
    items.value = r.data ?? r; total.value = r.total ?? 0; page.value = p;
  } catch (e) { err.value = e.message; }
}
onMounted(() => load());
</script>
<template>
  <AppLayout>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Biens du patrimoine</h1>
      <Link href="/biens/nouveau" class="bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-800">+ Nouveau bien</Link>
    </div>
    <form @submit.prevent="load(1)" class="mb-4 flex gap-2">
      <input v-model="q" placeholder="Rechercher (code, désignation)…" class="border rounded px-3 py-2 flex-1">
      <button class="bg-slate-700 text-white px-4 py-2 rounded">Rechercher</button>
    </form>
    <p v-if="err" class="text-red-600 mb-3">{{ err }}</p>
    <div class="bg-white rounded-lg shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50"><tr>
          <th class="text-left p-3">Code inventaire</th><th class="text-left p-3">Désignation</th>
          <th class="text-left p-3">Catégorie</th><th class="text-left p-3">Statut</th>
          <th class="text-right p-3">Valeur</th>
        </tr></thead>
        <tbody>
          <tr v-for="b in items" :key="b.id" class="border-t hover:bg-slate-50">
            <td class="p-3 font-mono"><Link :href="`/biens/${b.id}`" class="text-blue-700">{{ b.code_inventaire }}</Link></td>
            <td class="p-3">{{ b.designation }}</td><td class="p-3">{{ b.categorie_libelle ?? '—' }}</td>
            <td class="p-3"><span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">{{ b.statut }}</span></td>
            <td class="p-3 text-right">{{ (b.valeur_acquisition ?? 0).toLocaleString('fr-FR') }}</td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="p-6 text-center text-gray-400">Aucun bien</td></tr>
        </tbody>
      </table>
    </div>
    <div class="mt-4 flex gap-2">
      <button :disabled="page<=1" @click="load(page-1)" class="px-3 py-1 border rounded disabled:opacity-40">Préc.</button>
      <span class="px-3 py-1">Page {{ page }} — {{ total }} biens</span>
      <button @click="load(page+1)" class="px-3 py-1 border rounded">Suiv.</button>
    </div>
  </AppLayout>
</template>
