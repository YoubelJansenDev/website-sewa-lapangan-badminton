<script setup lang="ts">
import { parseApiError } from '~/utils/parseApiError'

definePageMeta({ layout: 'auth', middleware: 'guest' })
useHead({ title: 'Lupa Kata Sandi — SmashArena' })

const auth = useAuth()

const email = ref('')
const formError = ref('')
const successMessage = ref('')
const loading = ref(false)

async function handleSubmit() {
  formError.value = ''
  successMessage.value = ''
  loading.value = true
  try {
    successMessage.value = await auth.forgotPassword(email.value)
  } catch (e: unknown) {
    formError.value = parseApiError(e)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <h1 class="text-xl font-extrabold text-black">Lupa Kata Sandi</h1>
    <p class="mt-1 mb-6 text-sm text-gray-500">
      Masukkan email Anda. Kami akan mengirim tautan untuk mengatur ulang kata sandi.
    </p>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <p
        v-if="formError"
        class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
      >{{ formError }}</p>
      <p
        v-if="successMessage"
        class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
      >{{ successMessage }}</p>

      <FormInput v-model="email" label="Email" type="email" placeholder="nama@email.com" required />

      <button
        type="submit"
        class="w-full rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Mengirim…' : 'Kirim Tautan Reset' }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
      Ingat kata sandi Anda?
      <NuxtLink to="/login" class="font-semibold text-green-700 hover:underline">Masuk</NuxtLink>
    </p>
  </div>
</template>
