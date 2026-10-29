/**
 * State autentikasi (UX saja — keamanan tetap di backend, aturan .clinerules #3).
 * Sesi berbasis cookie httpOnly; frontend hanya menyimpan profil user.
 */
export interface AuthUser {
  id: number
  name: string
  email: string
  phone?: string | null
  role: 'user' | 'admin'
}

export function useAuth() {
  const user = useState<AuthUser | null>('auth:user', () => null)
  const initialized = useState<boolean>('auth:initialized', () => false)
  const api = useApi()

  /** Ambil profil dari /auth/me sekali saat aplikasi dimuat. */
  async function fetchUser(): Promise<AuthUser | null> {
    try {
      const res = await api.get<{ user: AuthUser }>('/auth/me')
      user.value = res.user
    } catch {
      user.value = null
    } finally {
      initialized.value = true
    }
    return user.value
  }

  async function login(email: string, password: string): Promise<void> {
    const res = await api.post<{ user: AuthUser }>('/auth/login', { email, password })
    user.value = res.user
  }

  async function register(payload: {
    name: string
    email: string
    phone?: string
    password: string
    password_confirmation: string
  }): Promise<void> {
    await api.post('/auth/register', payload)
  }

  async function forgotPassword(email: string): Promise<string> {
    const res = await api.post<{ message: string }>('/auth/forgot-password', { email })
    return res.message
  }

  async function resetPassword(payload: {
    token: string
    email: string
    password: string
    password_confirmation: string
  }): Promise<string> {
    const res = await api.post<{ message: string }>('/auth/reset-password', payload)
    return res.message
  }

  async function logout(): Promise<void> {
    try {
      await api.post('/auth/logout')
    } finally {
      user.value = null
    }
  }

  const isLoggedIn = computed(() => user.value !== null)
  const isAdmin = computed(() => user.value?.role === 'admin')

  return {
    user,
    initialized,
    isLoggedIn,
    isAdmin,
    fetchUser,
    login,
    register,
    forgotPassword,
    resetPassword,
    logout,
  }
}
