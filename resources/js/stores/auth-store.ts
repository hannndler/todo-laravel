import { create } from 'zustand'
import { persist } from 'zustand/middleware'

// Tipos para el usuario
export interface User {
  id: number
  name: string
  email: string
  username?: string
  avatar?: string
  bio?: string
  phone?: string
  position?: string
  department?: string
  is_active: boolean
  last_login_at?: string
  email_verified_at?: string
  created_at: string
  updated_at: string
}

// Tipos para la autenticación
export interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  error: string | null
}

// Tipos para las acciones
export interface AuthActions {
  // Acciones de autenticación
  login: (email: string, password: string) => Promise<void>
  register: (name: string, email: string, password: string) => Promise<void>
  logout: () => void
  setUser: (user: User) => void
  setToken: (token: string) => void
  clearError: () => void
  setLoading: (loading: boolean) => void

  // Acciones de persistencia
  initializeAuth: () => void
  clearAuth: () => void
}

// Store combinado
export type AuthStore = AuthState & AuthActions

// API base URL
const API_BASE_URL = '/api'

// Store de autenticación
export const useAuthStore = create<AuthStore>()(
  persist(
    (set, get) => ({
      // Estado inicial
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: false,
      error: null,

      // Acciones
      setUser: (user: User) => {
        set({ user, isAuthenticated: true })
      },

      setToken: (token: string) => {
        set({ token })
        // Guardar token en localStorage para persistencia
        localStorage.setItem('auth_token', token)
      },

      setLoading: (loading: boolean) => {
        set({ isLoading: loading })
      },

      clearError: () => {
        set({ error: null })
      },

      // Login
      login: async (email: string, password: string) => {
        set({ isLoading: true, error: null })

        try {
          const response = await fetch(`${API_BASE_URL}/login`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
            body: JSON.stringify({ email, password }),
          })

          const data = await response.json()

          if (!response.ok) {
            throw new Error(data.message || 'Error en el login')
          }

          // Guardar datos de autenticación
          set({
            user: data.user,
            token: data.token,
            isAuthenticated: true,
            isLoading: false,
            error: null,
          })

          // Guardar token en localStorage
          localStorage.setItem('auth_token', data.token)

        } catch (error) {
          set({
            isLoading: false,
            error: error instanceof Error ? error.message : 'Error desconocido',
          })
          throw error
        }
      },

      // Registro
      register: async (name: string, email: string, password: string) => {
        set({ isLoading: true, error: null })

        try {
          const response = await fetch(`${API_BASE_URL}/register`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
            body: JSON.stringify({ name, email, password }),
          })

          const data = await response.json()

          if (!response.ok) {
            throw new Error(data.message || 'Error en el registro')
          }

          // Guardar datos de autenticación
          set({
            user: data.user,
            token: data.token,
            isAuthenticated: true,
            isLoading: false,
            error: null,
          })

          // Guardar token en localStorage
          localStorage.setItem('auth_token', data.token)

        } catch (error) {
          set({
            isLoading: false,
            error: error instanceof Error ? error.message : 'Error desconocido',
          })
          throw error
        }
      },

      // Logout
      logout: () => {
        const { token } = get()

        // Llamar al endpoint de logout si hay token
        if (token) {
          fetch(`${API_BASE_URL}/logout`, {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${token}`,
              'Accept': 'application/json',
            },
          }).catch(console.error) // Ignorar errores en logout
        }

        // Limpiar estado
        get().clearAuth()
      },

      // Inicializar autenticación desde localStorage
      initializeAuth: () => {
        const token = localStorage.getItem('auth_token')
        if (token) {
          set({ token, isAuthenticated: true })

          // Opcional: Verificar token con el servidor
          fetch(`${API_BASE_URL}/user`, {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Accept': 'application/json',
            },
          })
          .then(response => {
            if (response.ok) {
              return response.json()
            }
            throw new Error('Token inválido')
          })
          .then(data => {
            set({ user: data })
          })
          .catch(() => {
            // Token inválido, limpiar
            get().clearAuth()
          })
        }
      },

      // Limpiar autenticación
      clearAuth: () => {
        set({
          user: null,
          token: null,
          isAuthenticated: false,
          isLoading: false,
          error: null,
        })
        localStorage.removeItem('auth_token')
      },
    }),
    {
      name: 'auth-storage', // Nombre para localStorage
      partialize: (state) => ({
        token: state.token,
        user: state.user,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
)

// Hook personalizado para obtener headers de autorización
export const useAuthHeaders = () => {
  const token = useAuthStore((state) => state.token)

  return {
    'Authorization': token ? `Bearer ${token}` : '',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
}

// Hook para verificar si el usuario está autenticado
export const useIsAuthenticated = () => {
  return useAuthStore((state) => state.isAuthenticated)
}

// Hook para obtener el usuario actual
export const useCurrentUser = () => {
  return useAuthStore((state) => state.user)
}