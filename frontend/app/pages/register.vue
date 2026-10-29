<script setup lang="ts">
import { parseApiError } from '~/utils/parseApiError'

definePageMeta({ layout: 'auth', middleware: 'guest' })
useHead({ title: 'Daftar — SmashArena' })

const auth = useAuth()
const router = useRouter()

const name = ref('')
const email = ref('')
const phone = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const formError = ref('')
const loading = ref(false)

async function handleSubmit() {
  formError.value = ''
  if (password.value !== passwordConfirmation.value) {
    formError.value = 'Konfirmasi kata sandi tidak sama.'
    return
  }
  loading.value = true
  try {
    await auth.register({
      name: name.value,
      email: email.value,
      phone: phone.value || undefined,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    // Set register berhasil, arahkan ke login dengan pesan sukses
    router.push({ path: '/login', query: { registered: '1' } })
  } catch (e: unknown) {
    formError.value = parseApiError(e)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <h1 class="text-xl font-extrabold text-black">Daftar Akun</h1>
    <p class="mt-1 mb-6 text-sm text-gray-500">Buat akun untuk mulai memesan lapangan.</p>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <p
        v-if="formError"
        class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
      >{{ formError }}</p>

      <FormInput v-model="name" label="Nama Lengkap" placeholder="Budi Santoso" required />
      <FormInput v-model="email" label="Email" type="email" placeholder="nama@email.com" required />
      <FormInput v-model="phone" label="No. HP (opsional)" type="tel" placeholder="0812xxxxxxx" />
      <FormInput v-model="password" label="Kata Sandi" type="password" hint="Minimal 8 karakter." required />
      <FormInput v-model="passwordConfirmation" label="Konfirmasi Kata Sandi" type="password" required />

      <button
        type="submit"
        class="w-full rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Memproses…' : 'Daftar' }}
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
      Sudah punya akun?
      <NuxtLink to="/login" class="font-semibold text-green-700 hover:underline">Masuk</NuxtLink>
    </p>
  </div>
</template>
