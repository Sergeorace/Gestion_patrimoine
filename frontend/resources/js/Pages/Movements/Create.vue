<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { api } from '../../api';
const f = ref({ type: 'affectation', bien_id: '', structure_dest_id: '', motif: '' });
const err = ref(''), loading = ref(false);
async function submit() {
  loading.value = true; err.value = '';
  try { await api.post('/movements', f.value); router.visit('/mouvements'); }
  catch (e) { err.value = e.message; } finally { loading.value = false; }
}
</script>
<template>
  <AppLayout>
    <h1 class="text-2xl font-bold mb-6">Nouvelle demande de mouvement</h1>
    <form @submit.prevent="submit" class="bg-white rounded-lg shadow p-6 max-w-xl space-y-4">
      <label>Type
        <select v-model="f.type" class="w-full border rounded px-3 py-2 mt-1">
          <option value="affectation">Affectation</option><option value="transfert">Transfert</option>
          <option value="entree">Entrée</option><option value="sortie">Sortie</option>
        </select>
      </label>
      <label>ID du bien *<input v-model="f.bien_id" required placeholder="UUID" class="w-full border rounded px-3 py-2 mt-1"></label>
      <label v-if="f.type !== 'entree'">Structure de destination *<input v-model="f.structure_dest_id" required placeholder="UUID" class="w-full border rounded px-3 py-2 mt-1"></label>
      <label>Motif<textarea v-model="f.motif" rows="3" class="w-full border rounded px-3 py-2 mt-1"></textarea></label>
      <p v-if="err" class="text-red-600 text-sm">{{ err }}</p>
      <button :disabled="loading" class="bg-blue-700 text-white px-6 py-2 rounded hover:bg-blue-800 disabled:opacity-50">Soumettre pour validation</button>
    </form>
  </AppLayout>
</template>
