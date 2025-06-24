import { useEffect } from 'react'
import { useAuthStore } from '@/stores/auth-store'
import { useNavigation } from '@/hooks/use-navigation'

interface ProtectedRouteProps {
  children: React.ReactNode
}

export function ProtectedRoute({ children }: ProtectedRouteProps) {
  const { token, user } = useAuthStore()
  const { navigateToAuth } = useNavigation()

  useEffect(() => {
    if (!token || !user) {
      navigateToAuth()
    }
  }, [token, user, navigateToAuth])

  if (!token || !user) {
    return null
  }

  return <>{children}</>
}
