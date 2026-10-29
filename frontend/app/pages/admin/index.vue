<script setup lang="ts">
/** Panel admin: daftar semua booking + filter status, approve/reject dengan alasan. */
import { parseApiError } from '~/utils/parseApiError'
import { formatDateIndo, formatRupiah } from '~/utils/format'

definePageMeta({ middleware: 'admin' })
useHead({ title: 'Panel Admin — SmashArena' })

interface AdminBooking {
  id: number
  booking_date: string
  start_time: string
  duration_hours: number
  status: string
  total_price: number
  user?: { id: number; name: string; email: string }
  court?: { id: number; name: string }
  invoice?: { id: number; invoice_number: string; status: string } | null
}

const api = useApi()
const bookings = ref<AdminBooking[]>([])
const page = ref(1)
const lastPage = ref(1)
const loading = ref(true)
const errorMsg = ref('')
const statusFilter = ref('')

// Approve/reject
const actingId = ref<number | null>(null)
const rejectId = ref<number | null>(null)
const rejectReason = ref('')
const actionMsg = ref('')
const actionError = ref('')
const busy = ref(false)

const FILTERS = [
  { value: '', label: 'Semua' },
  { value: 'pending', label: 'Menunggu' },
  { value: 'approved', label: 'Disetujui' },
  { value: 'rejected', label: 'Ditolak' },
  { value: 'cancelled', label: 'Dibatalkan' },
]

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

async function load(p = 1) {
  loading.value = true
  errorMsg.value = ''
  try {
    const params: Record<string, unknown> = { page: p }
    if (statusFilter.value) params.status = statusFilter.value
    const res = await api.get<{ data: AdminBooking[]; current_page: number; last_page: number }>(
      '/admin/bookings',
      params,
    )
    bookings.value = res.data
    page.value = res.current_page
    lastPage.value = res.last_page
  } catch (e) {
    errorMsg.value = parseApiError(e)
  } finally {
    loading.value = false
  }
}

async function approve(id: number) {
  actingId.value = id
  actionError.value = ''
  actionMsg.value = ''
  busy.value = true
  try {
    const res = await api.post<{ message: string }>(`/admin/bookings/${id}/approve`)
    actionMsg.value = res.message
    await load(page.value)
  } catch (e) {
    actionError.value = parseApiError(e)
  } finally {
    busy.value = false
    actingId.value = null
  }
}

async function confirmReject() {
  if (rejectId.value === null) return
  busy.value = true
  actionError.value = ''
  actionMsg.value = ''
  try {
    const res = await api.post<{ message: string }>(`/admin/bookings/${rejectId.value}/reject`, {
      reason: rejectReason.value,
    })
    actionMsg.value = res.message
    rejectId.value = null
    rejectReason.value = ''
    await load(page.value)
  } catch (e) {
    actionError.value = parseApiError(e)
  } finally {
    busy.value = false
  }
}

function openReject(id: number) {
  rejectId.value = id
  rejectReason.value = ''
  actionError.value = ''
  actionMsg.value = ''
}

watch(statusFilter, () => load(1))
onMounted(() => load())
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-extrabold tracking-tight">Panel Admin — Verifikasi Booking</h1>
      <p class="mt-1 text-sm text-gray-500">Setujui atau tolak booking member. Menyetujui akan membuat invoice.</p>
    </div>

    <!-- Filter status -->
    <div class="mb-4 flex flex-wrap gap-2">
      <button
        v-for="f in FILTERS"
        :key="f.value"
        type="button"
        class="rounded-full border px-4 py-1.5 text-sm font-semibold transition"
        :class="statusFilter === f.value
          ? 'border-green-600 bg-green-600 text-white'
          : 'border-gray-200 bg-white hover:border-green-400'"
        @click="statusFilter = f.value"
      >{{ f.label }}</button>
    </div>

    <p v-if="actionMsg" class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ actionMsg }}</p>
    <p v-if="actionError" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ actionError }}</p>
    <p v-if="errorMsg" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ errorMsg }}</p>
    <div v-if="loading" class="rounded-2xl border border-gray-200 p-10 text-center text-sm text-gray-500">Memuat…</div>

    <div v-else-if="bookings.length === 0" class="rounded-2xl border border-dashed border-gray-300 p-10 text-center text-gray-500">
      Tidak ada booking pada filter ini.
    </div>

    <div v-else class="space-y-3">
      <div v-for="b in bookings" :key="b.id" class="rounded-2xl border border-gray-200 p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="font-bold">#{{ b.id }} · {{ b.court?.name ?? '—' }}</p>
            <p class="mt-0.5 text-sm text-gray-500">{{ b.user?.name ?? '—' }} ({{ b.user?.email ?? '—' }})</p>
            <p class="mt-0.5 text-sm text-gray-500">
              {{ formatDateIndo(b.booking_date) }} · {{ b.start_time.slice(0, 5) }} · {{ b.duration_hours }} jam ·
              {{ formatRupiah(b.total_price) }}
            </p>
            <p v-if="b.invoice" class="mt-1 text-xs text-gray-500">
              Invoice {{ b.invoice.invoice_number }} ·
              <span :class="b.invoice.status === 'paid' ? 'font-semibold text-green-700' : 'font-semibold text-red-600'">
                {{ b.invoice.status === 'paid' ? 'Lunas' : 'Belum dibayar' }}
              </span>
            </p>
          </div>
          <div class="flex flex-col items-end gap-2">
            <span :class="['rounded-full px-3 py-1 text-xs font-bold', statusInfo(b.status).class]">{{ statusInfo(b.status).label }}</span>
            <div v-if="b.status === 'pending'" class="flex gap-2">
              <button
                type="button"
                class="rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-60"
                :disabled="busy"
                @click="approve(b.id)"
              >
                {{ actingId === b.id ? 'Memproses…' : 'Setujui' }}
              </button>
              <button
                type="button"
                class="rounded-xl border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-60"
                :disabled="busy"
                @click="openReject(b.id)"
              >Tolak</button>
            </div>
          </div>
        </div>

        <!-- Form alasan penolakan inline -->
        <div v-if="rejectId === b.id" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4">
          <label class="mb-1.5 block text-sm font-semibold text-red-700">Alasan Penolakan (wajib, min. 5 karakter)</label>
          <textarea
            v-model="rejectReason"
            rows="3"
            class="w-full rounded-xl border border-red-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100"
            placeholder="Contoh: Jadwal bentrok dengan acara klub bulu tangkis sekolah."
          />
          <div class="mt-3 flex gap-2">
            <button
              type="button"
              class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60"
              :disabled="busy || rejectReason.trim().length < 5"
              @click="confirmReject"
            >{{ busy ? 'Mengirim…' : 'Konfirmasi Tolak' }}</button>
            <button
              type="button"
              class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-gray-50"
              @click="rejectId = null"
            >Batal</button>
          </div>
        </div>
      </div>

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

    <!-- Overlay gelap saat form penolakan terbuka (mobile-friendly) -->
    <div v-if="rejectId !== null" class="fixed inset-0 z-40 bg-black/30" @click="rejectId = null" />
  </div>
</template>
