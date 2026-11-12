<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import { api } from '../api';
const items = ref([]), err = ref('');
onMounted(async () => { try { const r = await api.get('/maintenance/interventions?per_page=50'); items.value = r.data ?? r; } catch (e) { err.value = e.message; } });
</script>
<template>
  <AppLayout>
    <h1 class="text-2xl font-bold mb-6">Maintenance</h1>
    <p v-if="err" class="text-red-600">{{ err }}</p>
    <div class="bg-white rounded-lg shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50"><tr><th class="text-left p-3">Bien</th><th class="text-left p-3">Type</th><th class="text-left p-3">Statut</th><th class="text-left p-3">Date prévue</th><th class="text-right p-3">Coût</th></tr></thead>
        <tbody>
          <tr v-for="i in items" :key="i.id" class="border-t">
            <td class="p-3 font-mono text-xs">{{ i.bien_id?.slice(0,8) }}…</td><td class="p-3">{{ i.type }}</td>
            <td class="p-3"><span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">{{ i.statut }}</span></td>
            <td class="p-3">{{ i.date_prevue ?? '—' }}</td>
            <td class="p-3 text-right">{{ (Number(i.cout_main_oeuvre ?? 0) + Number(i.cout_pieces ?? 0)).toLocaleString('fr-FR') }}</td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="p-6 text-center text-gray-400">Aucune intervention</td></tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>
