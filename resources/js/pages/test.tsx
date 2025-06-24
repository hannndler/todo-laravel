import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

export default function TestPage() {
  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900 p-8">
      <div className="max-w-4xl mx-auto space-y-6">
        <div className="text-center">
          <h1 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Página de Prueba
          </h1>
          <p className="text-gray-600 dark:text-gray-400">
            Esta página verifica que los componentes shadcn/ui funcionan correctamente
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <Card>
            <CardHeader>
              <CardTitle>Componentes Básicos</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-2">Input de prueba</label>
                <Input placeholder="Escribe algo aquí..." />
              </div>

              <div className="space-y-2">
                <Button>Botón Primario</Button>
                <Button variant="secondary">Botón Secundario</Button>
                <Button variant="outline">Botón Outline</Button>
                <Button variant="destructive">Botón Destructive</Button>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Estado de la Aplicación</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span>Vite:</span>
                  <span className="text-green-600">✅ Funcionando</span>
                </div>
                <div className="flex justify-between">
                  <span>Tailwind CSS:</span>
                  <span className="text-green-600">✅ Funcionando</span>
                </div>
                <div className="flex justify-between">
                  <span>shadcn/ui:</span>
                  <span className="text-green-600">✅ Funcionando</span>
                </div>
                <div className="flex justify-between">
                  <span>React:</span>
                  <span className="text-green-600">✅ Funcionando</span>
                </div>
                <div className="flex justify-between">
                  <span>TypeScript:</span>
                  <span className="text-green-600">✅ Funcionando</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Información del Sistema</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
              <div>
                <strong>Navegador:</strong> {typeof window !== 'undefined' ? navigator.userAgent : 'Server-side'}
              </div>
              <div>
                <strong>URL:</strong> {typeof window !== 'undefined' ? window.location.href : 'Server-side'}
              </div>
              <div>
                <strong>Timestamp:</strong> {new Date().toLocaleString()}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
