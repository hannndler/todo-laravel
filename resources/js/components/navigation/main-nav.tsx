import { useState } from 'react'
import { Link } from '@inertiajs/react'
import {
  LayoutDashboard,
  CheckSquare,
  Tag,
  Users,
  Settings,
  User,
  Menu
} from 'lucide-react'

import { Button } from '@/components/ui/button'
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet'
import { cn } from '@/lib/utils'
import { useNavigation } from '@/hooks/use-navigation'

interface NavItem {
  title: string
  href: string
  icon: React.ComponentType<{ className?: string }>
  description?: string
}

const navItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
    icon: LayoutDashboard,
    description: 'Vista general de tus tareas'
  },
  {
    title: 'Tareas',
    href: '/tasks',
    icon: CheckSquare,
    description: 'Gestiona tus tareas'
  },
  {
    title: 'Categorías',
    href: '/categories',
    icon: Tag,
    description: 'Organiza por categorías'
  },
  {
    title: 'Equipos',
    href: '/teams',
    icon: Users,
    description: 'Gestiona equipos'
  },
  {
    title: 'Perfil',
    href: '/profile',
    icon: User,
    description: 'Tu información personal'
  },
  {
    title: 'Configuración',
    href: '/settings',
    icon: Settings,
    description: 'Ajustes de la aplicación'
  }
]

interface MainNavProps {
  className?: string
  isMobile?: boolean
}

export function MainNav({ className, isMobile = false }: MainNavProps) {
  const [isOpen, setIsOpen] = useState(false)
  const { navigate } = useNavigation()

  const handleNavClick = (href: string) => {
    navigate(href)
    if (isMobile) {
      setIsOpen(false)
    }
  }

  const NavContent = () => (
    <nav className={cn("flex flex-col space-y-1", className)}>
      {navItems.map((item) => (
        <Button
          key={item.href}
          variant="ghost"
          className="justify-start h-auto p-3"
          onClick={() => handleNavClick(item.href)}
        >
          <item.icon className="mr-3 h-5 w-5" />
          <div className="text-left">
            <div className="font-medium">{item.title}</div>
            {item.description && (
              <div className="text-xs text-muted-foreground">
                {item.description}
              </div>
            )}
          </div>
        </Button>
      ))}
    </nav>
  )

  if (isMobile) {
    return (
      <Sheet open={isOpen} onOpenChange={setIsOpen}>
        <SheetTrigger asChild>
          <Button variant="ghost" size="sm" className="md:hidden">
            <Menu className="h-5 w-5" />
          </Button>
        </SheetTrigger>
        <SheetContent side="left" className="w-80">
          <div className="px-2 py-4">
            <h2 className="mb-4 text-lg font-semibold">Navegación</h2>
            <NavContent />
          </div>
        </SheetContent>
      </Sheet>
    )
  }

  return <NavContent />
}
