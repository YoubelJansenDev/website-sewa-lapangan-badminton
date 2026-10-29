<script setup lang="ts">
/** Admin: kelola lapangan & alat sewa (CRUD via /admin/courts dan /admin/equipment). */
import { parseApiError } from '~/utils/parseApiError'
import { formatRupiah } from '~/utils/format'

definePageMeta({ middleware: 'admin' })
useHead({ title: 'Kelola Lapangan & Alat — SmashArena' })

interface Court {
  id: number
  name: string
  floor_type: string
  price_per_hour: number
  is_active: boolean
}

interface Equipment {
  id: number
  name: string
  price: number
  stock: number
  is_active: boolean
}

const api = useApi()
const tab = ref<'courts' | 'equipment'>('courts')
const courts = ref<Court[]>([])
const equipment = ref<Equipment[]>([])
const loading = ref(true)
const errorMsg = ref('')
const actionMsg = ref('')
const actionError = ref('')

// Form lapangan
const showCourtForm = ref(false)
const editingCourtId = ref<number | null>(null)
const courtForm = reactive({ name: '', floor_type: '', price_per_hour: 0, is_active: true })

// Form alat
const showEquipmentForm = ref(false)
const editingEquipmentId = ref<number | null>(null)
const equipmentForm = reactive({ name: '', price: 0, stock: 0, is_active: true })

const busy = ref(false)

function resetMsgs() {
  actionMsg.value = ''
  actionError.value = ''
  errorMsg.value = ''
}

async function loadAll() {
  loading.value = true
  try {
    const [c, e] = await Promise.all([
      api.get<{ courts: Court[] }>('/admin/courts'),
      api.get<{ equipment: Equipment[] }>('/admin/equipment'),
    ])
    courts.value = c.courts
    equipment.value = e.equipment
  } catch (err) {
    errorMsg.value = parseApiError(err)
  } finally {
    loading.value = false
  }
}

// ---------- Lapangan ----------
function openCourtForm(c?: Court) {
  resetMsgs()
  editingCourtId.value = c?.id ?? null
  courtForm.name = c?.name ?? ''
  courtForm.floor_type = c?.floor_type ?? ''
  courtForm.price_per_hour = c?.price_per_hour ?? 0
  courtForm.is_active = c?.is_active ?? true
  showCourtForm.value = true
}

async function submitCourt() {
  busy.value = true
  resetMsgs()
  try {
    if (editingCourtId.value === null) {
      const res = await api.post<{ message: string }>('/admin/courts', {
        name: courtForm.name,
        floor_type: courtForm.floor_type,
        price_per_hour: Number(courtForm.price_per_hour),
      })
      actionMsg.value = res.message
    } else {
      const res = await api.put<{ message: string }>(`/admin/courts/${editingCourtId.value}`, {
        name: courtForm.name,
        floor_type: courtForm.floor_type,
        price_per_hour: Number(courtForm.price_per_hour),
        is_active: courtForm.is_active,
      })
      actionMsg.value = res.message
    }
    showCourtForm.value = false
    await loadAll()
  } catch (err) {
    actionError.value = parseApiError(err)
  } finally {
    busy.value = false
  }
}

async function deactivateCourt(id: number) {
  busy.value = true
  resetMsgs()
  try {
    const res = await api.delete<{ message: string }>(`/admin/courts/${id}`)
    actionMsg.value = res.message
    await loadAll()
  } catch (err) {
    actionError.value = parseApiError(err)
  } finally {
    busy.value = false
  }
}

// ---------- Alat ----------
function openEquipmentForm(e?: Equipment) {
  resetMsgs()
  editingEquipmentId.value = e?.id ?? null
  equipmentForm.name = e?.name ?? ''
  equipmentForm.price = e?.price ?? 0
  equipmentForm.stock = e?.stock ?? 0
  equipmentForm.is_active = e?.is_active ?? true
  showEquipmentForm.value = true
}

async function submitEquipment() {
  busy.value = true
  resetMsgs()
  try {
    if (editingEquipmentId.value === null) {
      const res = await api.post<{ message: string }>('/admin/equipment', {
        name: equipmentForm.name,
        price: Number(equipmentForm.price),
        stock: Number(equipmentForm.stock),
      })
      actionMsg.value = res.message
    } else {
      const res = await api.put<{ message: string }>(`/admin/equipment/${editingEquipmentId.value}`, {
        name: equipmentForm.name,
        price: Number(equipmentForm.price),
        stock: Number(equipmentForm.stock),
        is_active: equipmentForm.is_active,
      })
      actionMsg.value = res.message
    }
    showEquipmentForm.value = false
    await loadAll()
  } catch (err) {
    actionError.value = parseApiError(err)
  } finally {
    busy.value = false
  }
}

async function deactivateEquipment(id: number) {
  busy.value = true
  resetMsgs()
  try {
    const res = await api.delete<{ message: string }>(`/admin/equipment/${id}`)
    actionMsg.value = res.message
    await loadAll()
  } catch (err) {
    actionError.value = parseApiError(err)
  } finally {
    busy.value = false
  }
}

onMounted(() => loadAll())
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-2">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight">Kelola Lapangan &amp; Alat</h1>
        <p class="mt-1 text-sm text-gray-500">Nonaktifkan, bukan hapus — riwayat booking tetap aman.</p>
      </div>
      <NuxtLink to="/admin" class="text-sm font-semibold text-green-700 hover:underline">&larr; Verifikasi Booking</NuxtLink>
    </div>

    <!-- Tab -->
    <div class="mb-4 flex gap-2">
      <button
        type="button"
        class="rounded-full border px-4 py-1.5 text-sm font-semibold transition"
        :class="tab === 'courts' ? 'border-green-600 bg-green-600 text-white' : 'border-gray-200 bg-white hover:border-green-400'"
        @click="tab = 'courts'"
      >Lapangan ({{ courts.length }})</button>
      <button
        type="button"
        class="rounded-full border px-4 py-1.5 text-sm font-semibold transition"
        :class="tab === 'equipment' ? 'border-green-600 bg-green-600 text-white' : 'border-gray-200 bg-white hover:border-green-400'"
        @click="tab = 'equipment'"
      >Alat Sewa ({{ equipment.length }})</button>
    </div>

    <p v-if="actionMsg" class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ actionMsg }}</p>
    <p v-if="actionError" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ actionError }}</p>
    <p v-if="errorMsg" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ errorMsg }}</p>
    <div v-if="loading" class="rounded-2xl border border-gray-200 p-10 text-center text-sm text-gray-500">Memuat…</div>

    <!-- ================= LAPANGAN ================= -->
    <template v-else-if="tab === 'courts'">
      <button
        type="button"
        class="mb-4 rounded-xl bg-green-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-green-700"
        @click="openCourtForm()"
      >+ Tambah Lapangan</button>

      <div v-if="showCourtForm" class="mb-4 rounded-2xl border border-green-200 bg-green-50/50 p-5">
        <h2 class="font-bold">{{ editingCourtId === null ? 'Tambah Lapangan' : 'Edit Lapangan #'+editingCourtId }}</h2>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
          <label class="block text-sm">
            <span class="mb-1 block font-semibold">Nama</span>
            <input v-model="courtForm.name" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-green-600 focus:ring-2 focus:ring-green-100" placeholder="Lapangan A">
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-semibold">Jenis Lantai</span>
            <input v-model="courtForm.floor_type" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-green-600 focus:ring-2 focus:ring-green-100" placeholder="Matras / Vinil">
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-semibold">Harga per jam (Rp)</span>
            <input v-model.number="courtForm.price_per_hour" type="number" min="0" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-green-600 focus:ring-2 focus:ring-green-100" placeholder="50000">
          </label>
          <label v-if="editingCourtId !== null" class="flex items-center gap-2 self-end text-sm">
            <input v-model="courtForm.is_active" type="checkbox" class="h-4 w-4 accent-green-600">
            <span class="font-semibold">Aktif</span>
          </label>
        </div>
        <div class="mt-4 flex gap-2">
          <button type="button" class="rounded-xl bg-green-600 px-4 py-2 text-sm font-bold text-white hover:bg-green-700 disabled:opacity-60" :disabled="busy" @click="submitCourt">
            {{ busy ? 'Menyimpan…' : 'Simpan' }}
          </button>
          <button type="button" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-gray-50" @click="showCourtForm = false">Batal</button>
        </div>
      </div>

      <div class="space-y-3">
        <div v-for="c in courts" :key="c.id" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 p-5">
          <div>
            <p class="font-bold">{{ c.name }} <span v-if="!c.is_active" class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">Nonaktif</span></p>
            <p class="mt-0.5 text-sm text-gray-500">{{ c.floor_type }} · {{ formatRupiah(c.price_per_hour) }}/jam</p>
          </div>
          <div class="flex gap-2">
            <button type="button" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold hover:bg-gray-50" @click="openCourtForm(c)">Edit</button>
            <button v-if="c.is_active" type="button" class="rounded-xl border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-60" :disabled="busy" @click="deactivateCourt(c.id)">Nonaktifkan</button>
          </div>
        </div>
      </div>
    </template>

    <!-- ================= ALAT SEWA ================= -->
    <template v-else>
      <button
        type="button"
        class="mb-4 rounded-xl bg-green-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-green-700"
        @click="openEquipmentForm()"
      >+ Tambah Alat</button>

      <div v-if="showEquipmentForm" class="mb-4 rounded-2xl border border-green-200 bg-green-50/50 p-5">
        <h2 class="font-bold">{{ editingEquipmentId === null ? 'Tambah Alat' : 'Edit Alat #'+editingEquipmentId }}</h2>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
          <label class="block text-sm">
            <span class="mb-1 block font-semibold">Nama</span>
            <input v-model="equipmentForm.name" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-green-600 focus:ring-2 focus:ring-green-100" placeholder="Raket Yonex">
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-semibold">Harga sewa (Rp)</span>
            <input v-model.number="equipmentForm.price" type="number" min="0" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-green-600 focus:ring-2 focus:ring-green-100" placeholder="10000">
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-semibold">Stok</span>
            <input v-model.number="equipmentForm.stock" type="number" min="0" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-green-600 focus:ring-2 focus:ring-green-100" placeholder="10">
          </label>
          <label v-if="editingEquipmentId !== null" class="flex items-center gap-2 self-end text-sm">
            <input v-model="equipmentForm.is_active" type="checkbox" class="h-4 w-4 accent-green-600">
            <span class="font-semibold">Aktif</span>
          </label>
        </div>
        <div class="mt-4 flex gap-2">
          <button type="button" class="rounded-xl bg-green-600 px-4 py-2 text-sm font-bold text-white hover:bg-green-700 disabled:opacity-60" :disabled="busy" @click="submitEquipment">
            {{ busy ? 'Menyimpan…' : 'Simpan' }}
          </button>
          <button type="button" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-gray-50" @click="showEquipmentForm = false">Batal</button>
        </div>
      </div>

      <div class="space-y-3">
        <div v-for="e in equipment" :key="e.id" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 p-5">
          <div>
            <p class="font-bold">{{ e.name }} <span v-if="!e.is_active" class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">Nonaktif</span></p>
            <p class="mt-0.5 text-sm text-gray-500">{{ formatRupiah(e.price) }} / unit · stok {{ e.stock }}</p>
          </div>
          <div class="flex gap-2">
            <button type="button" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold hover:bg-gray-50" @click="openEquipmentForm(e)">Edit</button>
            <button v-if="e.is_active" type="button" class="rounded-xl border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-60" :disabled="busy" @click="deactivateEquipment(e.id)">Nonaktifkan</button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
