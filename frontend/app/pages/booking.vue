<script setup lang="ts">
import { parseApiError } from '~/utils/parseApiError'
import { formatRupiah, formatDateIndo, todayStr, maxBookingDateStr } from '~/utils/format'

definePageMeta({ middleware: 'auth' })
useHead({ title: 'Booking Lapangan — SmashArena' })

const api = useApi()
const router = useRouter()

// ---------- Tipe data (sesuai kontrak API docs/PLAN.md) ----------
interface Court {
  id: number
  name: string
  floor_type: string
  price_per_hour: number
}

interface Equipment {
  id: number
  name: string
  price: number
  stock: number
}

interface Slot {
  hour: number
  label: string
  available: boolean
}

// ---------- State ----------
const courts = ref<Court[]>([])
const equipmentList = ref<Equipment[]>([])

const bookingDate = ref(todayStr())
const courtId = ref<number | null>(null)
const slots = ref<Slot[]>([])
const startHour = ref<number | null>(null)
const duration = ref(2)
const equipmentQty = ref<Record<number, number>>({})

const loadingCourts = ref(false)
const loadingSlots = ref(false)
const submitting = ref(false)
const formError = ref('')
const lastTakenHour = ref<number | null>(null)

const MAX_DURATION = 4
const DURATIONS = [1, 2, 3, 4]

// ---------- Computed ----------
const selectedCourt = computed(() =>
  courts.value.find((c) => c.id === courtId.value) ?? null,
)

/** Total estimasi: lapangan x durasi + alat (harga final tetap dihitung backend). */
const estimatedTotal = computed(() => {
  if (!selectedCourt.value) return 0
  let total = selectedCourt.value.price_per_hour * duration.value
  for (const item of equipmentList.value) {
    const qty = equipmentQty.value[item.id] ?? 0
    if (qty > 0) total += item.price * qty
  }
  return total
})

const chosenEquipment = computed(() =>
  equipmentList.value
    .map((item) => ({ ...item, qty: equipmentQty.value[item.id] ?? 0 }))
    .filter((item) => item.qty > 0),
)

// ---------- Aksi ----------
async function loadCourts() {
  loadingCourts.value = true
  try {
    const res = await api.get<{ courts: Court[] }>('/courts')
    courts.value = res.courts
    if (courtId.value === null && courts.value.length > 0) {
      courtId.value = courts.value[0].id
    }
  } catch (e: unknown) {
    formError.value = parseApiError(e)
  } finally {
    loadingCourts.value = false
  }
}

async function loadEquipment() {
  try {
    const res = await api.get<{ equipment: Equipment[] }>('/equipment')
    equipmentList.value = res.equipment
  } catch (e: unknown) {
    formError.value = parseApiError(e)
  }
}

async function loadAvailability() {
  if (courtId.value === null) return
  loadingSlots.value = true
  startHour.value = null
  lastTakenHour.value = null
  formError.value = ''
  try {
    const res = await api.get<{ court: Court; date: string; slots: Slot[] }>(
      `/courts/${courtId.value}/availability`,
      { date: bookingDate.value },
    )
    slots.value = res.slots
  } catch (e: unknown) {
    slots.value = []
    formError.value = parseApiError(e)
  } finally {
    loadingSlots.value = false
  }
}

function selectHour(hour: number) {
  const slot = slots.value.find((s) => s.hour === hour)
  if (!slot || !slot.available) return
  startHour.value = startHour.value === hour ? null : hour
}

/** Slot grid menandai jam tercakup durasi bila user sudah memilih jam mulai. */
function isCoveredByDuration(hour: number): boolean {
  if (startHour.value === null) return false
  return hour >= startHour.value && hour < startHour.value + duration.value
}

/** Cek lokal ringan: semua jam dalam rentang durasi harus tersedia. */
function rangeAvailable(): boolean {
  if (startHour.value === null) return false
  return slots.value
    .filter((s) => s.hour >= startHour.value! && s.hour < startHour.value! + duration.value)
    .every((s) => s.available)
}

function bumpEquipment(id: number, delta: number) {
  const item = equipmentList.value.find((e) => e.id === id)
  if (!item) return
  const current = equipmentQty.value[id] ?? 0
  const next = Math.min(Math.max(current + delta, 0), item.stock)
  equipmentQty.value = { ...equipmentQty.value, [id]: next }
}

async function handleSubmit() {
  formError.value = ''
  lastTakenHour.value = null

  if (courtId.value === null || startHour.value === null) {
    formError.value = 'Pilih tanggal, lapangan, dan jam mulai terlebih dahulu.'
    return
  }
  if (!rangeAvailable()) {
    formError.value = 'Rentang jam yang dipilih tidak semuanya tersedia. Silakan pilih jam lain.'
    return
  }

  submitting.value = true
  try {
    const equipment = chosenEquipment.value.map((item) => ({
      equipment_id: item.id,
      quantity: item.qty,
    }))

    await api.post('/bookings', {
      court_id: courtId.value,
      booking_date: bookingDate.value,
      start_hour: startHour.value,
      duration_hours: duration.value,
      ...(equipment.length > 0 ? { equipment } : {}),
    })

    router.push('/riwayat')
  } catch (e: unknown) {
    formError.value = parseApiError(e)
    // 409: slot sudah diambil orang lain -> refresh grid jam agar kondisi terbaru terlihat
    const err = e as { response?: { status?: number } }
    if (err.response?.status === 409) {
      lastTakenHour.value = startHour.value
      await loadAvailability()
    }
  } finally {
    submitting.value = false
  }
}

// ---------- Inisialisasi ----------
onMounted(() => {
  loadCourts()
  loadEquipment()
})

watch([bookingDate, courtId], () => {
  loadAvailability()
})
</script>

<template>
  <div class="mx-auto max-w-6xl">
    <div class="mb-6">
      <h1 class="text-2xl font-extrabold tracking-tight">Booking Lapangan</h1>
      <p class="mt-1 text-sm text-gray-500">
        Pilih tanggal, lapangan, jam, durasi, dan alat sewa. Booking akan menunggu persetujuan admin.
      </p>
    </div>

    <p
      v-if="formError"
      class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
    >
      {{ formError }}
      <template v-if="lastTakenHour !== null">
        Grid jam sudah diperbarui dengan kondisi terbaru.
      </template>
    </p>

    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Kolom kiri: form -->
      <div class="space-y-6 lg:col-span-2">
        <!-- Langkah 1: tanggal -->
        <section class="rounded-2xl border border-gray-200 p-6">
          <h2 class="font-bold">1. Pilih Tanggal</h2>
          <input
            v-model="bookingDate"
            type="date"
            class="mt-3 w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100 sm:w-64"
            :min="todayStr()"
            :max="maxBookingDateStr()"
            @change="loadAvailability"
          >
          <p class="mt-2 text-sm text-gray-500">{{ formatDateIndo(bookingDate) }}</p>
        </section>

        <!-- Langkah 2: lapangan -->
        <section class="rounded-2xl border border-gray-200 p-6">
          <h2 class="font-bold">2. Pilih Lapangan</h2>
          <div v-if="loadingCourts" class="mt-3 text-sm text-gray-500">Memuat lapangan…</div>
          <div v-else class="mt-3 grid gap-3 sm:grid-cols-3">
            <button
              v-for="court in courts"
              :key="court.id"
              type="button"
              class="rounded-xl border p-4 text-left transition"
              :class="courtId === court.id
                ? 'border-green-600 bg-green-50 ring-2 ring-green-100'
                : 'border-gray-200 hover:border-green-300'"
              @click="courtId = court.id"
            >
              <p class="font-semibold">{{ court.name }}</p>
              <p class="mt-0.5 text-xs text-gray-500">{{ court.floor_type }}</p>
              <p class="mt-2 text-sm font-bold text-green-700">{{ formatRupiah(court.price_per_hour) }}/jam</p>
            </button>
          </div>
        </section>

        <!-- Langkah 3: jam mulai -->
        <section class="rounded-2xl border border-gray-200 p-6">
          <h2 class="font-bold">3. Pilih Jam Mulai</h2>
          <p class="mt-1 text-sm text-gray-500">Jam operasional 08:00–23:00. Booking selesai paling lambat 23:00.</p>

          <div v-if="loadingSlots" class="mt-3 text-sm text-gray-500">Memuat ketersediaan…</div>
          <div v-else class="mt-4 grid grid-cols-4 gap-2 sm:grid-cols-7">
            <button
              v-for="slot in slots"
              :key="slot.hour"
              type="button"
              class="rounded-lg border px-2 py-2 text-center text-sm font-semibold transition"
              :class="[
                !slot.available
                  ? 'cursor-not-allowed border-gray-100 bg-gray-100 text-gray-400'
                  : startHour === slot.hour
                    ? 'border-green-600 bg-green-600 text-white'
                    : isCoveredByDuration(slot.hour)
                      ? 'border-green-300 bg-green-50 text-green-700'
                      : 'border-gray-200 hover:border-green-400',
              ]"
              :disabled="!slot.available"
              :title="slot.available ? slot.label : 'Tidak tersedia'"
              @click="selectHour(slot.hour)"
            >
              {{ slot.label }}
            </button>
          </div>
          <p v-if="startHour === null && !loadingSlots" class="mt-3 text-sm text-gray-500">
            Klik salah satu jam untuk memilih jam mulai.
          </p>
        </section>

        <!-- Langkah 4: durasi -->
        <section class="rounded-2xl border border-gray-200 p-6">
          <h2 class="font-bold">4. Durasi Bermain</h2>
          <div class="mt-3 flex gap-2">
            <button
              v-for="d in DURATIONS"
              :key="d"
              type="button"
              class="flex-1 rounded-xl border px-4 py-2.5 text-sm font-semibold transition"
              :class="duration === d
                ? 'border-green-600 bg-green-600 text-white'
                : 'border-gray-200 hover:border-green-400'"
              @click="duration = d"
            >{{ d }} jam</button>
          </div>
          <p class="mt-2 text-sm text-gray-500">Maksimal {{ MAX_DURATION }} jam per booking.</p>
        </section>

        <!-- Langkah 5: alat sewa -->
        <section class="rounded-2xl border border-gray-200 p-6">
          <h2 class="font-bold">5. Sewa Alat <span class="font-normal text-gray-400">(opsional)</span></h2>
          <p class="mt-1 text-sm text-gray-500">Ketersediaan stok alat akan divalidasi ulang oleh sistem.</p>
          <div class="mt-4 space-y-3">
            <div
              v-for="item in equipmentList"
              :key="item.id"
              class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3"
            >
              <div>
                <p class="text-sm font-semibold">{{ item.name }}</p>
                <p class="text-xs text-gray-500">{{ formatRupiah(item.price) }} / unit · stok {{ item.stock }}</p>
              </div>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="grid h-8 w-8 place-items-center rounded-lg border border-gray-300 font-bold hover:bg-gray-100 disabled:opacity-40"
                  :disabled="(equipmentQty[item.id] ?? 0) <= 0"
                  @click="bumpEquipment(item.id, -1)"
                >−</button>
                <span class="w-8 text-center text-sm font-bold">{{ equipmentQty[item.id] ?? 0 }}</span>
                <button
                  type="button"
                  class="grid h-8 w-8 place-items-center rounded-lg border border-gray-300 font-bold hover:bg-gray-100 disabled:opacity-40"
                  :disabled="(equipmentQty[item.id] ?? 0) >= item.stock"
                  @click="bumpEquipment(item.id, 1)"
                >+</button>
              </div>
            </div>
            <p v-if="equipmentList.length === 0" class="text-sm text-gray-500">Tidak ada alat tersedia.</p>
          </div>
        </section>
      </div>

      <!-- Kolom kanan: ringkasan -->
      <aside class="lg:sticky lg:top-24 lg:self-start">
        <div class="rounded-2xl border border-gray-200 p-6">
          <h2 class="font-bold">Ringkasan</h2>

          <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between gap-2">
              <dt class="text-gray-500">Tanggal</dt>
              <dd class="font-semibold">{{ formatDateIndo(bookingDate) }}</dd>
            </div>
            <div class="flex justify-between gap-2">
              <dt class="text-gray-500">Lapangan</dt>
              <dd class="font-semibold">{{ selectedCourt?.name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-2">
              <dt class="text-gray-500">Jam</dt>
              <dd class="font-semibold">
                <template v-if="startHour !== null">
                  {{ String(startHour).padStart(2, '0') }}:00 –
                  {{ String(startHour + duration).padStart(2, '0') }}:00
                </template>
                <template v-else>—</template>
              </dd>
            </div>
            <div class="flex justify-between gap-2">
              <dt class="text-gray-500">Durasi</dt>
              <dd class="font-semibold">{{ duration }} jam</dd>
            </div>
          </dl>

          <div v-if="chosenEquipment.length" class="mt-4 border-t border-gray-200 pt-4">
            <p class="text-sm font-semibold">Alat sewa:</p>
            <ul class="mt-2 space-y-1 text-sm text-gray-600">
              <li v-for="item in chosenEquipment" :key="item.id" class="flex justify-between gap-2">
                <span>{{ item.name }} × {{ item.qty }}</span>
                <span>{{ formatRupiah(item.price * item.qty) }}</span>
              </li>
            </ul>
          </div>

          <div class="mt-4 flex justify-between border-t border-gray-200 pt-4">
            <p class="font-bold">Total estimasi</p>
            <p class="text-lg font-extrabold text-green-700">{{ formatRupiah(estimatedTotal) }}</p>
          </div>
          <p class="mt-1 text-xs text-gray-400">Harga final dihitung ulang oleh sistem saat booking dibuat.</p>

          <button
            type="button"
            class="mt-6 w-full rounded-xl bg-green-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="submitting || startHour === null || courtId === null"
            @click="handleSubmit"
          >
            {{ submitting ? 'Mengirim…' : 'Kirim Booking' }}
          </button>
          <p class="mt-2 text-center text-xs text-gray-400">
            Booking berstatus <span class="font-semibold text-gray-600">pending</span> hingga disetujui admin.
          </p>
        </div>
      </aside>
    </div>
  </div>
</template>

