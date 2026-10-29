<script setup lang="ts">
/** Riwayat booking milik user yang sedang login (paginasi Laravel). */
import { formatDateIndo, formatRupiah } from '~/utils/format'

definePageMeta({ middleware: 'auth' })
useHead({ title: 'Riwayat Booking — SmashArena' })

interface BookingRow {
  id: number
  booking_date: string
  start_time: string
  duration_hours: number
  status: string
  total_price: number
  court?: { id: number; name: string; floor_type: string }
  items?: { id: number; quantity: number; unit_price: number; equipment?: { id: number; name: string } }[]
  invoice?: { id: number; invoice_number: string; status: string; amount: number } | null
}

const api = useApi()
const bookings = ref<BookingRow[]>([])
const page = ref(1)
const lastPage = ref(1)
const loading = ref(true)
const errorMsg = ref('')

const STATUS: Record<string, { label: string; class: string }> = {
  pending: { label: 'Menunggu', class: 'bg-gray-100 text-gray-700' },
  approved: { label: 'Disetujui', class: 'bg-green-100 text-green-700' },
  rejected: { label: 'Ditolak', class: 'bg-red-100 text-red-700' },
  cancelled: { label: 'Dibatalkan', class: 'bg-yellow-100 text-yellow-800' },
  completed: { label: 'Selesai', class: 'bg-blue-100 text-blue-700' },
}

function statusInfo(s: string) {
  return STATUS[s] ?? { label: s, class: 'bg-gray-100 text-gray-700' }
}

function hourLabel(t: string): string {
  // start_time "08:00:00" -> "08:00"
  return t.slice(0, 5)
}

async function load(p = 1) {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await api.get<{ data: BookingRow[]; current_page: number; last_page: number }>('/bookings', { page: p })
    bookings.value = res.data
    page.value = res.current_page
    lastPage.value = res.last_page
  } catch (e) {
    errorMsg.value = parseApiError(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => load())
</script>

<template>
  <div class="page-enter">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight">Riwayat Booking</h1>
        <p class="mt-1 text-sm text-gray-500">Semua booking Anda, terbaru duluan.</p>
      </div>
      <NuxtLink
        to="/booking"
        class="rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700"
      >+ Booking Baru</NuxtLink>
    </div>

    <p v-if="errorMsg" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ errorMsg }}</p>
    <div v-if="loading" class="rounded-2xl border border-gray-200 p-10 text-center text-sm text-gray-500">Memuat…</div>

    <div v-else-if="bookings.length === 0" class="rounded-2xl border border-dashed border-gray-300 p-10 text-center text-gray-500">
      Belum ada booking. <NuxtLink to="/booking" class="font-semibold text-green-700 hover:underline">Pesan lapangan pertama Anda</NuxtLink>.
    </div>

    <div v-else class="space-y-3">
      <NuxtLink
        v-for="b in bookings"
        :key="b.id"
        :to="`/riwayat/${b.id}`"
        class="card-hover block rounded-2xl border border-gray-200 bg-white p-5 hover:border-green-400"
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <p class="font-bold">{{ b.court?.name ?? '—' }} · {{ formatDateIndo(b.booking_date) }}</p>
            <p class="mt-0.5 text-sm text-gray-500">
              {{ hourLabel(b.start_time) }} – {{ String(Number(hourLabel(b.start_time).slice(0, 2)) + b.duration_hours).padStart(2, '0') }}:00
              · {{ b.duration_hours }} jam
            </p>
          </div>
          <div class="text-right">
            <span :class="['rounded-full px-3 py-1 text-xs font-bold', statusInfo(b.status).class]">{{ statusInfo(b.status).label }}</span>
            <p class="mt-1 text-sm font-bold">{{ formatRupiah(b.total_price) }}</p>
          </div>
        </div>
        <p v-if="b.status === 'approved' && b.invoice" class="mt-2 text-xs text-gray-500">
          Invoice {{ b.invoice.invoice_number }} ·
          <span :class="b.invoice.status === 'paid' ? 'font-semibold text-green-700' : 'font-semibold text-red-600'">
            {{ b.invoice.status === 'paid' ? 'Lunas' : 'Belum dibayar' }}
          </span>
        </p>
      </NuxtLink>

      <!-- Paginasi -->
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
