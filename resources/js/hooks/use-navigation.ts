import { router } from '@inertiajs/react'

export function useNavigation() {
  const navigate = (url: string) => {
    router.visit(url)
  }

  const navigateToAuth = () => {
    navigate('/auth')
  }

  const navigateToDashboard = () => {
    navigate('/dashboard')
  }

  const navigateToHome = () => {
    navigate('/')
  }

  const navigateToTasks = () => {
    navigate('/tasks')
  }

  const navigateToCategories = () => {
    navigate('/categories')
  }

  const navigateToProfile = () => {
    navigate('/profile')
  }

  return {
    navigate,
    navigateToAuth,
    navigateToDashboard,
    navigateToHome,
    navigateToTasks,
    navigateToCategories,
    navigateToProfile,
  }
}
