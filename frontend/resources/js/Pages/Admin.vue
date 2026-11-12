<script setup>
import AppLayout from '../Layouts/AppLayout.vue';
import { api } from '../api';
import { onMounted, ref } from 'vue';
const users = ref([]), roles = ref([]), err = ref('');
onMounted(async () => {
  try {
    users.value = await api.get('/users?per_page=50');
    roles.value = await api.get('/roles');
  } catch (e) { err.value = e.message; }
});
</script>
<template>
  <AppLayout>
    <h1 class="text-2xl font-bold mb-6">Administration</h1>
    <p v-if="err" class="text-red-600">{{ err }}</p>
    <div class="grid md:grid-cols-2 gap-6">
      <div class="bg-white rounded-lg shadow overflow-x-auto">
        <h2 class="font-semibold p-4 border-b">Utilisateurs</h2>
        <table class="w-full text-sm">
          <tr v-for="u in (users.data ?? users)" :key="u.id" class="border-t">
            <td class="p-3">{{ u.nom_complet ?? u.email }}</td><td class="p-3">{{ u.email }}</td>
            <td class="p-3">{{ (u.roles ?? []).map(r => r.nom).join(', ') }}</td>
          </tr>
        </table>
      </div>
      <div class="bg-white rounded-lg shadow overflow-x-auto">
        <h2 class="font-semibold p-4 border-b">Rôles &amp; permissions</h2>
        <ul class="p-4 text-sm space-y-1">
          <li v-for="r in (roles.data ?? roles)" :key="r.id">{{ r.nom }} <span class="text-gray-400">({{ (r.permissions ?? []).length }} permissions)</span></li>
        </ul>
      </div>
    </div>
  </AppLayout>
</template>
