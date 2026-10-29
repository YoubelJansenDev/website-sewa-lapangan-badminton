/** Helper format tampilan: rupiah, tanggal Indonesia, dan batas tanggal booking. */

const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
const BULAN = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

/** 15000 -> "Rp 15.000" */
export function formatRupiah(value: number): string {
  return 'Rp ' + value.toLocaleString('id-ID')
}

/** "2026-01-31" -> "Sabtu, 31 Januari 2026" (tanpa dependency dayjs). */
export function formatDateIndo(dateStr: string): string {
  const [y, m, d] = dateStr.split('-').map(Number)
  const dt = new Date(y, m - 1, d)
  return `${HARI[dt.getDay()]}, ${d} ${BULAN[m - 1]} ${y}`
}

/** Tanggal hari ini "YYYY-MM-DD" (waktu lokal, bukan UTC). */
export function todayStr(): string {
  const now = new Date()
  const m = String(now.getMonth() + 1).padStart(2, '0')
  const d = String(now.getDate()).padStart(2, '0')
  return `${now.getFullYear()}-${m}-${d}`
}

/** Tanggal maksimal booking "YYYY-MM-DD" (30 hari ke depan, sesuai config backend). */
export function maxBookingDateStr(): string {
  const dt = new Date()
  dt.setDate(dt.getDate() + 30)
  const m = String(dt.getMonth() + 1).padStart(2, '0')
  const d = String(dt.getDate()).padStart(2, '0')
  return `${dt.getFullYear()}-${m}-${d}`
}
