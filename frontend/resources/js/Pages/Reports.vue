<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import { api, apiBase } from '../api';
const d = ref(null), items = ref([]), err = ref('');
onMounted(async () => {
  try {
    d.value = await api.get('/reports/dashboard');
    const r = await api.get('/reports?per_page=20'); items.value = r.data ?? r;
  } catch (e) { err.value = e.message; }
});
</script>
<template>
  <AppLayout>
    <h1 class="text-2xl font-bold mb-6">Rapports</h1>
    <p v-if="err" class="text-red-600">{{ err }}</p>
    <div v-if="d" class="bg-white rounded-lg shadow p-5 max-w-xl">
      <h2 class="font-semibold mb-3">Résumé du patrimoine</h2>
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>Biens : <b>{{ d.patrimoine?.nombre_biens ?? 0 }}</b></div>
        <div>Valeur : <b>{{ (d.patrimoine?.valeur_totale ?? 0).toLocaleString('fr-FR') }} FCFA</b></div>
        <div>Amortissements : <b>{{ (d.valorisation?.total_amortissements ?? 0).toLocaleString('fr-FR') }} FCFA</b></div>
        <div>VNC : <b>{{ (d.valorisation?.total_vnc ?? 0).toLocaleString('fr-FR') }} FCFA</b></div>
      </div>
    </div>
    <div class="bg-white rounded-lg shadow mt-6 overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50"><tr><th class="text-left p-3">Type</th><th class="text-left p-3">Format</th><th class="text-left p-3">Généré le</th><th class="text-right p-3">Action</th></tr></thead>
        <tbody>
          <tr v-for="r in items" :key="r.id" class="border-t">
            <td class="p-3">{{ r.type }}</td><td class="p-3">{{ r.format }}</td><td class="p-3">{{ r.genere_le }}</td>
            <td class="p-3 text-right"><a :href="`${apiBase}/reports/${r.id}/download`" class="text-blue-700">CSV</a></td>
          </tr>
          <tr v-if="!items.length"><td colspan="4" class="p-6 text-center text-gray-400">Aucun rapport</td></tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>
