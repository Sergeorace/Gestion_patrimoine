<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import { api } from '../api';
const items = ref([]), err = ref('');
onMounted(async () => { try { const r = await api.get('/inventories?per_page=50'); items.value = r.data ?? r; } catch (e) { err.value = e.message; } });
</script>
<template>
  <AppLayout>
    <h1 class="text-2xl font-bold mb-6">Campagnes d'inventaire</h1>
    <p v-if="err" class="text-red-600">{{ err }}</p>
    <div class="bg-white rounded-lg shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50"><tr><th class="text-left p-3">Code</th><th class="text-left p-3">Libellé</th><th class="text-left p-3">Période</th><th class="text-left p-3">Statut</th></tr></thead>
        <tbody>
          <tr v-for="c in items" :key="c.id" class="border-t">
            <td class="p-3 font-mono">{{ c.code }}</td><td class="p-3">{{ c.libelle }}</td>
            <td class="p-3">{{ c.date_debut }} → {{ c.date_fin }}</td>
            <td class="p-3"><span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800">{{ c.statut }}</span></td>
          </tr>
          <tr v-if="!items.length"><td colspan="4" class="p-6 text-center text-gray-400">Aucune campagne</td></tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>
