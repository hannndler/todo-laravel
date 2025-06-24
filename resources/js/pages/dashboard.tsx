import { useState, useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { CheckCircle, Clock, AlertCircle, Plus, Users } from 'lucide-react'

import { AuthenticatedLayout } from '@/layouts/authenticated-layout'
import { useAuthStore } from '@/stores/auth-store'

interface TaskStats {
  total: number
  completed: number
  pending: number
  in_progress: number
  overdue: number
  completion_rate: number
}

interface RecentTask {
  id: number
  title: string
  status: string
  priority: string
  due_date?: string
  created_at: string
  updated_at: string
  creator?: { name: string }
  assignee?: { name: string }
  category?: { name: string }
}

interface UpcomingDeadline {
  id: number
  title: string
  status: string
  priority: string
  due_date: string
  creator?: { name: string }
  assignee?: { name: string }
}

interface TeamStats {
  total_teams: number
  teams: Array<{
    id: number
    name: string
    member_count: number
  }>
}

interface DashboardData {
  task_stats: TaskStats
  recent_tasks: RecentTask[]
  upcoming_deadlines: UpcomingDeadline[]
  team_stats: TeamStats
}

export default function Dashboard() {
  const { user, token } = useAuthStore()
  const [loading, setLoading] = useState(true)
  const [dashboardData, setDashboardData] = useState<DashboardData | null>(null)
  const [error, setError] = useState<string | null>(null)

  const loadDashboardData = async () => {
    try {
      setLoading(true)
      setError(null)

      const response = await fetch('/api/dashboard', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      })

      if (response.ok) {
        const data = await response.json()
        setDashboardData(data.data)
      } else {
        setError('Error al cargar los datos del dashboard')
      }
    } catch {
      setError('Error de conexión')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    if (token) {
      loadDashboardData()
    }
  }, [token])

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'completed':
        return <Badge variant="default" className="bg-green-100 text-green-800">Completada</Badge>
      case 'pending':
        return <Badge variant="secondary">Pendiente</Badge>
      case 'in_progress':
        return <Badge variant="default">En Progreso</Badge>
      case 'overdue':
        return <Badge variant="destructive">Vencida</Badge>
      default:
        return <Badge variant="outline">Desconocido</Badge>
    }
  }

  const getPriorityBadge = (priority: string) => {
    switch (priority) {
      case 'high':
        return <Badge variant="destructive">Alta</Badge>
      case 'medium':
        return <Badge variant="secondary">Media</Badge>
      case 'low':
        return <Badge variant="outline">Baja</Badge>
      default:
        return <Badge variant="outline">Normal</Badge>
    }
  }

  if (loading) {
    return (
      <AuthenticatedLayout>
        <div className="space-y-6">
          <div className="text-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p className="text-gray-600">Cargando dashboard...</p>
          </div>
        </div>
      </AuthenticatedLayout>
    )
  }

  if (error) {
    return (
      <AuthenticatedLayout>
        <div className="space-y-6">
          <div className="text-center py-8">
            <p className="text-red-600 mb-4">{error}</p>
            <Button onClick={loadDashboardData}>Reintentar</Button>
          </div>
        </div>
      </AuthenticatedLayout>
    )
  }

  if (!dashboardData) {
    return (
      <AuthenticatedLayout>
        <div className="space-y-6">
          <div className="text-center py-8">
            <p className="text-gray-600">No se pudieron cargar los datos</p>
          </div>
        </div>
      </AuthenticatedLayout>
    )
  }

  const { task_stats, recent_tasks, upcoming_deadlines, team_stats } = dashboardData

  const stats = [
    {
      title: 'Tareas Completadas',
      value: task_stats.completed.toString(),
      description: `${task_stats.completion_rate}% de completado`,
      icon: CheckCircle,
      color: 'text-green-600',
    },
    {
      title: 'Tareas Pendientes',
      value: task_stats.pending.toString(),
      description: 'Por completar',
      icon: Clock,
      color: 'text-yellow-600',
    },
    {
      title: 'Tareas Vencidas',
      value: task_stats.overdue.toString(),
      description: 'Requieren atención',
      icon: AlertCircle,
      color: 'text-red-600',
    },
    {
      title: 'Equipos Activos',
      value: team_stats.total_teams.toString(),
      description: 'Total de equipos',
      icon: Users,
      color: 'text-blue-600',
    },
  ]

  return (
    <AuthenticatedLayout>
      <div className="space-y-6">
        {/* Header */}
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
              Dashboard
            </h1>
            <p className="text-gray-600 dark:text-gray-400">
              Bienvenido de vuelta, {user?.name}
            </p>
          </div>
          <Button>
            <Plus className="mr-2 h-4 w-4" />
            Nueva Tarea
          </Button>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {stats.map((stat, index) => (
            <Card key={index}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">
                  {stat.title}
                </CardTitle>
                <stat.icon className={`h-4 w-4 ${stat.color}`} />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stat.value}</div>
                <p className="text-xs text-muted-foreground">
                  {stat.description}
                </p>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Progress Bar */}
        <Card>
          <CardHeader>
            <CardTitle>Progreso General</CardTitle>
            <CardDescription>
              Progreso de completado de tareas
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span>Completado</span>
                <span>{task_stats.completion_rate}%</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div
                  className="bg-blue-600 h-2.5 rounded-full"
                  style={{ width: `${task_stats.completion_rate}%` }}
                ></div>
              </div>
            </div>
          </CardContent>
        </Card>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Recent Tasks */}
          <Card>
            <CardHeader>
              <CardTitle>Tareas Recientes</CardTitle>
              <CardDescription>
                Las últimas tareas que has creado o actualizado
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {recent_tasks.length === 0 ? (
                  <p className="text-center text-gray-500 py-4">No hay tareas recientes</p>
                ) : (
                  recent_tasks.map((task) => (
                    <div
                      key={task.id}
                      className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                    >
                      <div className="flex-1">
                        <h3 className="font-medium text-gray-900 dark:text-white">
                          {task.title}
                        </h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                          {task.due_date && `Vence: ${new Date(task.due_date).toLocaleDateString()}`}
                          {task.assignee && ` • Asignado a: ${task.assignee.name}`}
                        </p>
                      </div>
                      <div className="flex items-center space-x-2">
                        {getStatusBadge(task.status)}
                        {getPriorityBadge(task.priority)}
                      </div>
                    </div>
                  ))
                )}
              </div>
            </CardContent>
          </Card>

          {/* Upcoming Deadlines */}
          <Card>
            <CardHeader>
              <CardTitle>Próximos Vencimientos</CardTitle>
              <CardDescription>
                Tareas que vencen en los próximos 7 días
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {upcoming_deadlines.length === 0 ? (
                  <p className="text-center text-gray-500 py-4">No hay tareas próximas a vencer</p>
                ) : (
                  upcoming_deadlines.map((task) => (
                    <div
                      key={task.id}
                      className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                    >
                      <div className="flex-1">
                        <h3 className="font-medium text-gray-900 dark:text-white">
                          {task.title}
                        </h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                          Vence: {new Date(task.due_date).toLocaleDateString()}
                          {task.assignee && ` • Asignado a: ${task.assignee.name}`}
                        </p>
                      </div>
                      <div className="flex items-center space-x-2">
                        {getStatusBadge(task.status)}
                        {getPriorityBadge(task.priority)}
                      </div>
                    </div>
                  ))
                )}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Team Stats */}
        {team_stats.total_teams > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>Equipos</CardTitle>
              <CardDescription>
                Resumen de equipos y miembros
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {team_stats.teams.map((team) => (
                  <div key={team.id} className="flex items-center space-x-3 p-3 border rounded-lg">
                    <Users className="h-5 w-5 text-blue-600" />
                    <div>
                      <p className="font-medium">{team.name}</p>
                      <p className="text-sm text-gray-500">{team.member_count} miembros</p>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </AuthenticatedLayout>
  )
}
