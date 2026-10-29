<script setup lang="ts">
import { parseApiError } from '~/utils/parseApiError'

definePageMeta({ layout: 'auth', middleware: 'guest' })
useHead({ title: 'Atur Ulang Kata Sandi — SmashArena' })

const auth = useAuth()
const route = useRoute()
const router = useRouter()

// Token reset dikirim backend lewat URL, contoh: /reset-password?token=...&email=...
const token = computed(() => String(route.query.token ?? ''))
const email = ref(String(route.query.email ?? ''))
const password = ref('')
const passwordConfirmation = ref('')
const formError = ref('')
const loading = ref(false)

const tokenMissing = computed(() => !token.value)

async function handleSubmit() {
  formError.value = ''
  if (password.value !== passwordConfirmation.value) {
    formError.value = 'Konfirmasi kata sandi tidak sama.'
    return
  }
  loading.value = true
  try {
    await auth.resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    router.push({ path: '/login', query: { reset: '1' } })
  } catch (e: unknown) {
    formError.value = parseApiError(e)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <h1 class="text-xl font-extrabold text-black">Atur Ulang Kata Sandi</h1>
    <p class="mt-1 mb-6 text-sm text-gray-500">Buat kata sandi baru untuk akun Anda.</p>

    <p
      v-if="tokenMissing"
      class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
    >
      Token reset tidak ditemukan. Buka tautan reset dari email Anda, atau
      <NuxtLink to="/forgot-password" class="font-semibold underline">minta tautan baru</NuxtLink>.
    </p>

    <form v-else class="space-y-4" @submit.prevent="handleSubmit">
      <p
        v-if="formError"
        class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
      >{{ formError }}</p>

      <FormInput v-model="email" label="Email" type="email" required />
      <FormInput v-model="password" label="Kata Sandi Baru" type="password" hint="Minimal 8 karakter." required />
      <FormInput v-model="passwordConfirmation" label="Konfirmasi Kata Sandi" type="password" required />

      <button
        type="submit"
        class="w-full rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Menyimpan…' : 'Simpan Kata Sandi Baru' }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
      <NuxtLink to="/login" class="font-semibold text-green-700 hover:underline">Kembali ke halaman masuk</NuxtLink>
    </p>
  </div>
</template>
