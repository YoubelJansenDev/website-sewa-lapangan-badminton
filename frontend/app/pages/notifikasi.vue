<script setup lang="ts">
/** Notifikasi milik user: daftar, tandai satu / semua sudah dibaca. */
import { parseApiError } from '~/utils/parseApiError'

definePageMeta({ middleware: 'auth' })
useHead({ title: 'Notifikasi — SmashArena' })

interface Notification {
  id: number
  title: string
  message: string
  is_read: boolean
  created_at: string
}

const api = useApi()
const notifications = ref<Notification[]>([])
const page = ref(1)
const lastPage = ref(1)
const loading = ref(true)
const errorMsg = ref('')

const unreadCount = computed(() => notifications.value.filter((n) => !n.is_read).length)

async function load(p = 1) {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await api.get<{ data: Notification[]; current_page: number; last_page: number }>(
      '/notifications',
      { page: p },
    )
    notifications.value = res.data
    page.value = res.current_page
    lastPage.value = res.last_page
  } catch (e) {
    errorMsg.value = parseApiError(e)
  } finally {
    loading.value = false
  }
}

async function markRead(id: number) {
  try {
    await api.post(`/notifications/${id}/read`)
    const n = notifications.value.find((x) => x.id === id)
    if (n) n.is_read = true
  } catch (e) {
    errorMsg.value = parseApiError(e)
  }
}

async function markAllRead() {
  try {
    await api.post('/notifications/read-all')
    notifications.value = notifications.value.map((n) => ({ ...n, is_read: true }))
  } catch (e) {
    errorMsg.value = parseApiError(e)
  }
}

onMounted(() => load())
</script>

<template>
  <div class="mx-auto max-w-3xl">
    <div class="mb-6 flex items-center justify-between gap-2">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight">Notifikasi</h1>
        <p class="mt-1 text-sm text-gray-500">
          <template v-if="unreadCount > 0">{{ unreadCount }} notifikasi belum dibaca</template>
          <template v-else>Semua notifikasi sudah dibaca</template>
        </p>
      </div>
      <button
        v-if="unreadCount > 0"
        type="button"
        class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold hover:bg-gray-50"
        @click="markAllRead"
      >Tandai Semua Dibaca</button>
    </div>

    <p v-if="errorMsg" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ errorMsg }}</p>
    <div v-if="loading" class="rounded-2xl border border-gray-200 p-10 text-center text-sm text-gray-500">Memuat…</div>

    <div v-else-if="notifications.length === 0" class="rounded-2xl border border-dashed border-gray-300 p-10 text-center text-gray-500">
      Belum ada notifikasi.
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="n in notifications"
        :key="n.id"
        class="rounded-2xl border p-5"
        :class="n.is_read ? 'border-gray-200 bg-white' : 'border-green-300 bg-green-50'"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="font-bold">
              {{ n.title }}
              <span v-if="!n.is_read" class="ml-1 inline-block h-2 w-2 rounded-full bg-green-600 align-middle" />
            </p>
            <p class="mt-1 text-sm text-gray-600">{{ n.message }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ new Date(n.created_at).toLocaleString('id-ID') }}</p>
          </div>
          <button
            v-if="!n.is_read"
            type="button"
            class="shrink-0 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold hover:bg-white"
            @click="markRead(n.id)"
          >Tandai dibaca</button>
        </div>
      </div>

      <div v-if="lastPage > 1" class="flex items-center justify-center gap-2 pt-2">
        <button
          type="button"
          class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold disabled:opacity-40"
          :disabled="page <= 1"
          @click="load(page - 1)"
        >Sebelumnya</button>
        <span class="px-2 text-sm text-gray-500">{{ page }} / {{ lastPage }}</span>
        <button
          type="button"
          class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold disabled:opacity-40"
          :disabled="page >= lastPage"
          @click="load(page + 1)"
        >Berikutnya</button>
      </div>
    </div>
  </div>
</template>
