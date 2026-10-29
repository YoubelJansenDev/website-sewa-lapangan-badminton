<script setup lang="ts">
import { parseApiError } from '~/utils/parseApiError'

definePageMeta({ layout: 'auth', middleware: 'guest' })
useHead({ title: 'Masuk — SmashArena' })

const auth = useAuth()
const router = useRouter()

const email = ref('')
const password = ref('')
const formError = ref('')
const loading = ref(false)

async function handleSubmit() {
  formError.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push('/')
  } catch (e: unknown) {
    formError.value = parseApiError(e)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <h1 class="text-xl font-extrabold text-black">Masuk</h1>
    <p class="mt-1 mb-6 text-sm text-gray-500">Masuk untuk memesan lapangan badminton.</p>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <p
        v-if="formError"
        class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
      >{{ formError }}</p>

      <FormInput v-model="email" label="Email" type="email" placeholder="nama@email.com" required />
      <FormInput v-model="password" label="Kata Sandi" type="password" placeholder="••••••••" required />

      <div class="flex items-center justify-between">
        <NuxtLink to="/forgot-password" class="text-sm font-medium text-green-700 hover:underline">
          Lupa kata sandi?
        </NuxtLink>
      </div>

      <button
        type="submit"
        class="w-full rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Memproses…' : 'Masuk' }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
      Belum punya akun?
      <NuxtLink to="/register" class="font-semibold text-green-700 hover:underline">Daftar sekarang</NuxtLink>
    </p>
  </div>
</template>
