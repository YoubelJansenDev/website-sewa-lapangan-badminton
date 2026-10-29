/**
 * Mengubah error dari API (error respons Laravel via $fetch) menjadi pesan
 * Bahasa Indonesia yang siap ditampilkan ke pengguna.
 */

/** Bentuk minimal error yang dikirim ofetch/FetchError (tanpa impor paket). */
interface ApiFetchError {
  response?: {
    status?: number
    _data?: { message?: string; errors?: Record<string, string[]> }
  }
}

export function parseApiError(error: unknown): string {
  const err = error as ApiFetchError | null

  if (!err || !err.response) {
    return 'Tidak dapat terhubung ke server. Periksa koneksi Anda lalu coba lagi.'
  }

  const status: number = err.response?.status ?? 0
  const data = err.response?._data

  // 422: error validasi Laravel { message, errors } -> ambil pesan field pertama
  if (status === 422 && data?.errors) {
    const firstField = Object.values(data.errors)[0]
    if (firstField && firstField.length > 0) return firstField[0]
  }

  switch (status) {
    case 401:
      return 'Sesi Anda berakhir. Silakan login kembali.'
    case 403:
      return 'Anda tidak memiliki izin untuk melakukan aksi ini.'
    case 404:
      return 'Data tidak ditemukan.'
    case 409:
      return data?.message || 'Terjadi konflik data.'
    case 429:
      return 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.'
    default:
      return data?.message || `Terjadi kesalahan pada server (${status}).`
  }
}
