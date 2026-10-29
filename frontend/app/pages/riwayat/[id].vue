<script setup lang="ts">
/** Detail booking milik user: rincian slot, alat, invoice + tombol bayar (simulasi). */
import { parseApiError } from '~/utils/parseApiError'
import { formatDateIndo, formatRupiah } from '~/utils/format'

definePageMeta({ middleware: 'auth' })
useHead({ title: 'Detail Booking — SmashArena' })

const route = useRoute()
const api = useApi()
const router = useRouter()

interface Booking {
  id: number
  booking_date: string
  start_time: string
  duration_hours: number
  status: string
  rejection_reason?: string | null
  total_price: number
  verified_at?: string | null
  court?: { id: number; name: string; floor_type: string }
  items?: { id: number; quantity: number; unit_price: number; equipment?: { id: number; name: string } }[]
  invoice?: {
    id: number
    invoice_number: string
    amount: number
    status: string
    method?: string | null
    paid_at?: string | null
  } | null
}

const booking = ref<Booking | null>(null)
const loading = ref(true)
const errorMsg = ref('')
const paying = ref(false)
const payMsg = ref('')

const STATUS: Record<string, { label: string; class: string }> = {
  pending: { label: 'Menunggu Verifikasi', class: 'bg-gray-100 text-gray-700' },
  approved: { label: 'Disetujui', class: 'bg-green-100 text-green-700' },
  rejected: { label: 'Ditolak', class: 'bg-red-100 text-red-700' },
  cancelled: { label: 'Dibatalkan', class: 'bg-yellow-100 text-yellow-800' },
  completed: { label: 'Selesai', class: 'bg-blue-100 text-blue-700' },
}

function statusInfo(s: string) {
  return STATUS[s] ?? { label: s, class: 'bg-gray-100 text-gray-700' }
}

function hourLabel(t: string): string {
  return t.slice(0, 5)
}

function endTime(b: Booking): string {
  const h = Number(hourLabel(b.start_time).slice(0, 2)) + b.duration_hours
  return String(h).padStart(2, '0') + ':00'
}

async function load() {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await api.get<{ booking: Booking }>(`/bookings/${route.params.id}`)
    booking.value = res.booking
  } catch (e) {
    errorMsg.value = parseApiError(e)
  } finally {
    loading.value = false
  }
}

async function payInvoice() {
  if (!booking.value?.invoice) return
  paying.value = true
  payMsg.value = ''
  try {
    const res = await api.post<{ message: string }>(`/invoices/${booking.value.invoice.id}/pay`)
    payMsg.value = res.message
    await load()
  } catch (e) {
    payMsg.value = parseApiError(e)
  } finally {
    paying.value = false
  }
}

onMounted(() => load())
</script>

<template>
  <div class="mx-auto max-w-3xl">
    <button type="button" class="mb-4 text-sm font-semibold text-green-700 hover:underline" @click="router.back()">
      &larr; Kembali
    </button>

    <p v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ errorMsg }}</p>
    <div v-if="loading" class="rounded-2xl border border-gray-200 p-10 text-center text-sm text-gray-500">Memuat…</div>

    <template v-else-if="booking">
      <div class="page-enter rounded-2xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h1 class="text-xl font-extrabold tracking-tight">{{ booking.court?.name ?? 'Lapangan' }}</h1>
          <span :class="['rounded-full px-3 py-1 text-xs font-bold', statusInfo(booking.status).class]">
            {{ statusInfo(booking.status).label }}
          </span>
        </div>

        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
          <div class="flex justify-between gap-2 rounded-xl bg-gray-50 px-4 py-2.5">
            <dt class="text-gray-500">Tanggal</dt>
            <dd class="font-semibold">{{ formatDateIndo(booking.booking_date) }}</dd>
          </div>
          <div class="flex justify-between gap-2 rounded-xl bg-gray-50 px-4 py-2.5">
            <dt class="text-gray-500">Jam</dt>
            <dd class="font-semibold">{{ hourLabel(booking.start_time) }} – {{ endTime(booking) }}</dd>
          </div>
          <div class="flex justify-between gap-2 rounded-xl bg-gray-50 px-4 py-2.5">
            <dt class="text-gray-500">Durasi</dt>
            <dd class="font-semibold">{{ booking.duration_hours }} jam</dd>
          </div>
          <div class="flex justify-between gap-2 rounded-xl bg-gray-50 px-4 py-2.5">
            <dt class="text-gray-500">Jenis lantai</dt>
            <dd class="font-semibold">{{ booking.court?.floor_type ?? '—' }}</dd>
          </div>
        </dl>

        <p
          v-if="booking.status === 'rejected' && booking.rejection_reason"
          class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
          Alasan penolakan: {{ booking.rejection_reason }}
        </p>

        <div v-if="booking.items && booking.items.length" class="mt-6">
          <h2 class="font-bold">Rincian Alat Sewa</h2>
          <ul class="mt-2 divide-y divide-gray-100 rounded-xl border border-gray-200">
            <li v-for="item in booking.items" :key="item.id" class="flex items-center justify-between px-4 py-2.5 text-sm">
              <span>{{ item.equipment?.name ?? '—' }} × {{ item.quantity }}</span>
              <span class="font-semibold">{{ formatRupiah(item.unit_price * item.quantity) }}</span>
            </li>
          </ul>
        </div>

        <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
          <p class="font-bold">Total</p>
          <p class="text-lg font-extrabold text-green-700">{{ formatRupiah(booking.total_price) }}</p>
        </div>
      </div>

      <!-- Invoice -->
      <div v-if="booking.invoice" class="mt-6 rounded-2xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="font-bold">Invoice {{ booking.invoice.invoice_number }}</h2>
          <span
            :class="['rounded-full px-3 py-1 text-xs font-bold',
              booking.invoice.status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']"
          >
            {{ booking.invoice.status === 'paid' ? 'Lunas' : 'Belum Dibayar' }}
          </span>
        </div>

        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
          <div class="flex justify-between gap-2 rounded-xl bg-gray-50 px-4 py-2.5">
            <dt class="text-gray-500">Jumlah tagihan</dt>
            <dd class="font-semibold">{{ formatRupiah(booking.invoice.amount) }}</dd>
          </div>
          <div class="flex justify-between gap-2 rounded-xl bg-gray-50 px-4 py-2.5">
            <dt class="text-gray-500">Metode</dt>
            <dd class="font-semibold">{{ booking.invoice.method ?? '—' }}</dd>
          </div>
          <div v-if="booking.invoice.paid_at" class="flex justify-between gap-2 rounded-xl bg-gray-50 px-4 py-2.5 sm:col-span-2">
            <dt class="text-gray-500">Dibayar pada</dt>
            <dd class="font-semibold">{{ new Date(booking.invoice.paid_at).toLocaleString('id-ID') }}</dd>
          </div>
        </dl>

        <p v-if="payMsg" class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ payMsg }}</p>

        <button
          v-if="booking.invoice.status !== 'paid'"
          type="button"
          class="mt-4 w-full rounded-xl bg-green-600 px-4 py-3 text-sm font-bold text-white hover:bg-green-700 disabled:opacity-60"
          :disabled="paying"
          @click="payInvoice"
        >
          {{ paying ? 'Memproses…' : 'Bayar Sekarang (Simulasi)' }}
        </button>
      </div>

      <p v-else class="mt-6 rounded-2xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
        Invoice akan muncul setelah booking disetujui admin.
      </p>
    </template>
  </div>
</template>
