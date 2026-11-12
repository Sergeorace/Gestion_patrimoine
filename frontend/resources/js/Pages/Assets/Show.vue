<script setup>
import { onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { api } from '../../api';
const page = usePage();
const id = page.props.assetId;
const bien = ref(null), historique = ref([]), err = ref('');
onMounted(async () => {
  try {
    bien.value = await api.get(`/assets/${id}`);
    historique.value = await api.get(`/movements?bien_id=${id}`).catch(() => []);
  } catch (e) { err.value = e.message; }
});
</script>
<template>
  <AppLayout>
    <p v-if="err" class="text-red-600">{{ err }}</p>
    <div v-if="bien" class="bg-white rounded-lg shadow p-6 max-w-3xl">
      <h1 class="text-2xl font-bold mb-1">{{ bien.designation }}</h1>
      <p class="text-gray-500 font-mono mb-6">{{ bien.code_inventaire }}</p>
      <dl class="grid grid-cols-2 gap-4 text-sm">
        <div><dt class="text-gray-500">Catégorie</dt><dd>{{ bien.categorie_libelle ?? '—' }}</dd></div>
        <div><dt class="text-gray-500">Marque / Modèle</dt><dd>{{ bien.marque ?? '—' }} {{ bien.modele ?? '' }}</dd></div>
        <div><dt class="text-gray-500">N° série</dt><dd>{{ bien.numero_serie ?? '—' }}</dd></div>
        <div><dt class="text-gray-500">Statut</dt><dd><span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">{{ bien.statut }}</span></dd></div>
        <div><dt class="text-gray-500">État</dt><dd>{{ bien.etat }}</dd></div>
        <div><dt class="text-gray-500">Valeur d'acquisition</dt><dd>{{ (bien.valeur_acquisition ?? 0).toLocaleString('fr-FR') }} FCFA</dd></div>
        <div><dt class="text-gray-500">Date d'acquisition</dt><dd>{{ bien.date_acquisition ?? '—' }}</dd></div>
        <div><dt class="text-gray-500">Garantie jusqu'au</dt><dd>{{ bien.date_fin_garantie ?? '—' }}</dd></div>
      </dl>
      <h2 class="font-semibold mt-8 mb-2">Historique des mouvements</h2>
      <table class="w-full text-sm">
        <tr v-for="m in historique" :key="m.id" class="border-t">
          <td class="py-2">{{ m.reference }}</td><td>{{ m.type }}</td><td>{{ m.statut }}</td><td>{{ m.date_demande }}</td>
        </tr>
        <tr v-if="!historique.length"><td class="py-3 text-gray-400">Aucun mouvement</td></tr>
      </table>
    </div>
  </AppLayout>
</template>
