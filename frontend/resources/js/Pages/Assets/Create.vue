<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { api } from '../../api';
const f = ref({ designation: '', categorie_id: '', marque: '', modele: '', numero_serie: '', valeur_acquisition: 0, date_acquisition: '', etat: 'bon' });
const err = ref(''), loading = ref(false);
async function submit() {
  loading.value = true; err.value = '';
  try {
    const r = await api.post('/assets', f.value);
    router.visit(`/biens/${r.id ?? r.data?.id}`);
  } catch (e) { err.value = e.message; } finally { loading.value = false; }
}
</script>
<template>
  <AppLayout>
    <h1 class="text-2xl font-bold mb-6">Enregistrer un bien</h1>
    <form @submit.prevent="submit" class="bg-white rounded-lg shadow p-6 max-w-2xl grid grid-cols-2 gap-4">
      <label class="col-span-2">Désignation *<input v-model="f.designation" required class="w-full border rounded px-3 py-2 mt-1"></label>
      <label>ID catégorie *<input v-model="f.categorie_id" required placeholder="UUID" class="w-full border rounded px-3 py-2 mt-1"></label>
      <label>Marque<input v-model="f.marque" class="w-full border rounded px-3 py-2 mt-1"></label>
      <label>Modèle<input v-model="f.modele" class="w-full border rounded px-3 py-2 mt-1"></label>
      <label>N° série<input v-model="f.numero_serie" class="w-full border rounded px-3 py-2 mt-1"></label>
      <label>Valeur d'acquisition (FCFA)<input v-model.number="f.valeur_acquisition" type="number" min="0" class="w-full border rounded px-3 py-2 mt-1"></label>
      <label>Date d'acquisition<input v-model="f.date_acquisition" type="date" class="w-full border rounded px-3 py-2 mt-1"></label>
      <label>État
        <select v-model="f.etat" class="w-full border rounded px-3 py-2 mt-1">
          <option value="bon">Bon</option><option value="degrade">Dégradé</option><option value="hors_service">Hors service</option>
        </select>
      </label>
      <p v-if="err" class="col-span-2 text-red-600 text-sm">{{ err }}</p>
      <div class="col-span-2"><button :disabled="loading" class="bg-blue-700 text-white px-6 py-2 rounded hover:bg-blue-800 disabled:opacity-50">Enregistrer</button></div>
    </form>
  </AppLayout>
</template>
