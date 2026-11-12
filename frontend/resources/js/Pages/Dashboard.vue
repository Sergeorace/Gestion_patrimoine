<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import { api } from '../api';
const d = ref(null), err = ref('');
onMounted(async () => {
  try { d.value = await api.get('/reports/dashboard'); }
  catch (e) { err.value = e.message; }
});
</script>
<template>
  <AppLayout>
    <h1 class="text-2xl font-bold mb-6">Tableau de bord</h1>
    <p v-if="err" class="text-red-600">{{ err }}</p>
    <div v-if="d" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-lg shadow p-5">
        <div class="text-sm text-gray-500">Biens recensés</div>
        <div class="text-3xl font-bold">{{ d.patrimoine?.nombre_biens ?? 0 }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-5">
        <div class="text-sm text-gray-500">Valeur totale (FCFA)</div>
        <div class="text-3xl font-bold">{{ (d.patrimoine?.valeur_totale ?? 0).toLocaleString('fr-FR') }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-5">
        <div class="text-sm text-gray-500">VNC totale (FCFA)</div>
        <div class="text-3xl font-bold">{{ (d.valorisation?.total_vnc ?? 0).toLocaleString('fr-FR') }}</div>
      </div>
    </div>
    <div v-if="d?.mouvements_recents?.length" class="mt-8 bg-white rounded-lg shadow p-5">
      <h2 class="font-semibold mb-3">Mouvements récents</h2>
      <table class="w-full text-sm">
        <tr v-for="m in d.mouvements_recents" :key="m.id" class="border-t">
          <td class="py-2">{{ m.reference }}</td><td>{{ m.type }}</td><td>{{ m.statut }}</td>
        </tr>
      </table>
    </div>
  </AppLayout>
</template>
