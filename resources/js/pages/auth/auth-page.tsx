import { useState, useEffect } from 'react'

import { LoginForm } from '@/components/auth/login-form'
import { RegisterForm } from '@/components/auth/register-form'
import { useAuthStore } from '@/stores/auth-store'
import { useNavigation } from '@/hooks/use-navigation'

export default function AuthPage() {
  const [isLogin, setIsLogin] = useState(true)
  const { navigateToDashboard } = useNavigation()
  const { isAuthenticated, initializeAuth } = useAuthStore()

  // Inicializar autenticación al cargar la página
  useEffect(() => {
    initializeAuth()
  }, [initializeAuth])

  // Redirigir si ya está autenticado
  useEffect(() => {
    if (isAuthenticated) {
      navigateToDashboard()
    }
  }, [isAuthenticated, navigateToDashboard])

  const handleSwitchToRegister = () => {
    setIsLogin(false)
  }

  const handleSwitchToLogin = () => {
    setIsLogin(true)
  }

  const handleSwitchToForgotPassword = () => {
    // TODO: Implementar página de recuperar contraseña
    console.log('Ir a recuperar contraseña')
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 p-4">
      <div className="w-full max-w-md">
        {/* Logo o título de la aplicación */}
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            Todo App
          </h1>
          <p className="text-gray-600 dark:text-gray-400">
            Organiza tus tareas de manera eficiente
          </p>
        </div>

        {/* Formulario de autenticación */}
        {isLogin ? (
          <LoginForm
            onSwitchToRegister={handleSwitchToRegister}
            onSwitchToForgotPassword={handleSwitchToForgotPassword}
          />
        ) : (
          <RegisterForm onSwitchToLogin={handleSwitchToLogin} />
        )}

        {/* Información adicional */}
        <div className="mt-8 text-center">
          <p className="text-sm text-gray-500 dark:text-gray-400">
            Al continuar, aceptas nuestros{' '}
            <a href="#" className="text-blue-600 hover:text-blue-500 underline">
              Términos de Servicio
            </a>{' '}
            y{' '}
            <a href="#" className="text-blue-600 hover:text-blue-500 underline">
              Política de Privacidad
            </a>
          </p>
        </div>
      </div>
    </div>
  )
}
