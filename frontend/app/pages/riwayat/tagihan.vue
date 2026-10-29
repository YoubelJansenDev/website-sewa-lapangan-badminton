<script setup lang="ts">
/** Daftar tagihan (invoice) milik member: nomor, jumlah, status bayar, link ke detail booking. */
import { parseApiError } from '~/utils/parseApiError'
import { formatRupiah } from '~/utils/format'

definePageMeta({ middleware: 'auth' })
useHead({ title: 'Tagihan Saya — SmashArena' })

interface InvoiceRow {
  id: number
  invoice_number: string
  amount: number
  status: string
  method?: string | null
  paid_at?: string | null
  booking_id: number
}

const api = useApi()
const invoices = ref<InvoiceRow[]>([])
const page = ref(1)
const lastPage = ref(1)
const loading = ref(true)
const errorMsg = ref('')
const busy = ref(false)
const actionMsg = ref('')
const actionError = ref('')
const payingId = ref<number | null>(null)

async function load(p = 1) {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await api.get<{ data: InvoiceRow[]; current_page: number; last_page: number }>(
      '/invoices',
      { page: p },
    )
    invoices.value = res.data
    page.value = res.current_page
    lastPage.value = res.last_page
  } catch (e) {
    errorMsg.value = parseApiError(e)
  } finally {
    loading.value = false
  }
}

async function pay(id: number) {
  payingId.value = id
  busy.value = true
  actionMsg.value = ''
  actionError.value = ''
  try {
    const res = await api.post<{ message: string }>(`/invoices/${id}/pay`)
    actionMsg.value = res.message
    await load(page.value)
  } catch (e) {
    actionError.value = parseApiError(e)
  } finally {
    busy.value = false
    payingId.value = null
  }
}

onMounted(() => load())
</script>

<template>
  <div class="page-enter">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-2">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight">Tagihan Saya</h1>
        <p class="mt-1 text-sm text-gray-500">Semua invoice dari booking yang telah disetujui admin.</p>
      </div>
      <NuxtLink to="/riwayat" class="text-sm font-semibold text-green-700 hover:underline">&larr; Riwayat Booking</NuxtLink>
    </div>

    <p v-if="actionMsg" class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ actionMsg }}</p>
    <p v-if="actionError" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ actionError }}</p>
    <p v-if="errorMsg" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ errorMsg }}</p>
    <div v-if="loading" class="rounded-2xl border border-gray-200 p-10 text-center text-sm text-gray-500">Memuat…</div>

    <div v-else-if="invoices.length === 0" class="rounded-2xl border border-dashed border-gray-300 p-10 text-center text-gray-500">
      Belum ada tagihan. Invoice terbit otomatis setelah booking Anda disetujui admin.
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="inv in invoices"
        :key="inv.id"
        class="card-hover rounded-2xl border border-gray-200 bg-white p-5"
        :class="inv.status === 'unpaid' ? 'border-red-200' : ''"
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="font-bold">{{ inv.invoice_number }}</p>
            <p class="mt-0.5 text-sm text-gray-500">
              Booking #{{ inv.booking_id }}
              <template v-if="inv.paid_at"> · dibayar {{ new Date(inv.paid_at).toLocaleString('id-ID') }}</template>
            </p>
          </div>
          <div class="flex flex-col items-end gap-2">
            <span
              :class="['rounded-full px-3 py-1 text-xs font-bold',
                inv.status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']"
            >
              {{ inv.status === 'paid' ? 'Lunas' : 'Belum Dibayar' }}
            </span>
            <p class="text-sm font-bold">{{ formatRupiah(inv.amount) }}</p>
            <div class="flex gap-2">
              <NuxtLink
                :to="`/riwayat/${inv.booking_id}`"
                class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold hover:bg-gray-50"
              >Detail</NuxtLink>
              <button
                v-if="inv.status === 'unpaid'"
                type="button"
                class="rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-60"
                :disabled="busy"
                @click="pay(inv.id)"
              >
                {{ payingId === inv.id ? 'Membayar…' : 'Bayar Sekarang' }}
              </button>
            </div>
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
  </div>
</template>
