/**
 * Satu-satunya pintu pemanggilan API di seluruh frontend (aturan .clinerules #1).
 * JWT tidak pernah disimpan/dibaca JavaScript: cookie httpOnly "token" dikirim
 * otomatis browser lewat credentials: 'include'.
 */
import type { FetchError } from 'ofetch'

interface ApiOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  body?: Record<string, unknown>
  params?: Record<string, unknown>
}

export function useApi() {
  const config = useRuntimeConfig()
  const baseURL = config.public.apiBase

  async function request<T>(path: string, options: ApiOptions = {}): Promise<T> {
    return await $fetch<T>(path, {
      baseURL,
      method: options.method ?? 'GET',
      body: options.body,
      params: options.params,
      credentials: 'include',
      headers: { Accept: 'application/json' },
    })
  }

  return {
    get: <T>(path: string, params?: Record<string, unknown>) =>
      request<T>(path, { params }),
    post: <T>(path: string, body?: Record<string, unknown>) =>
      request<T>(path, { method: 'POST', body }),
  }
}
