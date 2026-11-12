<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import { api } from '../../api';
import { Link } from '@inertiajs/vue3';
const items = ref([]), err = ref('');
onMounted(async () => { try { const r = await api.get('/movements?per_page=50'); items.value = r.data ?? r; } catch (e) { err.value = e.message; } });
</script>
<template>
  <AppLayout>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Affectations &amp; mouvements</h1>
      <Link href="/mouvements/nouveau" class="bg-blue-700 text-white px-4 py-2 rounded">+ Nouvelle demande</Link>
    </div>
    <p v-if="err" class="text-red-600">{{ err }}</p>
    <div class="bg-white rounded-lg shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50"><tr>
          <th class="text-left p-3">Référence</th><th class="text-left p-3">Type</th>
          <th class="text-left p-3">Bien</th><th class="text-left p-3">Statut</th><th class="text-left p-3">Date</th>
        </tr></thead>
        <tbody>
          <tr v-for="m in items" :key="m.id" class="border-t">
            <td class="p-3 font-mono">{{ m.reference }}</td><td class="p-3">{{ m.type }}</td>
            <td class="p-3 font-mono text-xs">{{ m.bien_id?.slice(0, 8) }}…</td>
            <td class="p-3"><span class="px-2 py-1 rounded text-xs bg-amber-100 text-amber-800">{{ m.statut }}</span></td>
            <td class="p-3">{{ m.date_demande }}</td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="p-6 text-center text-gray-400">Aucun mouvement</td></tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>
