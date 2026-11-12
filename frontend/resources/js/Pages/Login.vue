<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { api, setToken } from '../api';
const email = ref(''), password = ref(''), err = ref(''), loading = ref(false);
async function submit() {
  loading.value = true; err.value = '';
  try {
    const r = await api.login(email.value, password.value);
    setToken(r.access_token);
    router.visit('/');
  } catch (e) { err.value = e.message; } finally { loading.value = false; }
}
</script>
<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100">
    <form @submit.prevent="submit" class="bg-white p-8 rounded-lg shadow w-96">
      <h1 class="text-xl font-bold mb-1">Patrimoine État</h1>
      <p class="text-sm text-gray-500 mb-6">Connexion à la plateforme</p>
      <input v-model="email" type="email" required placeholder="Email" class="w-full border rounded px-3 py-2 mb-3">
      <input v-model="password" type="password" required placeholder="Mot de passe" class="w-full border rounded px-3 py-2 mb-4">
      <p v-if="err" class="text-red-600 text-sm mb-3">{{ err }}</p>
      <button :disabled="loading" class="w-full bg-blue-700 text-white py-2 rounded hover:bg-blue-800 disabled:opacity-50">
        {{ loading ? 'Connexion…' : 'Se connecter' }}
      </button>
    </form>
  </div>
</template>
