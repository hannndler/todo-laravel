export default function SimpleTestPage() {
  return (
    <div className="min-h-screen bg-blue-100 p-8">
      <div className="max-w-4xl mx-auto">
        <h1 className="text-4xl font-bold text-blue-900 mb-4">
          Página de Prueba Simple
        </h1>
        <p className="text-blue-700">
          Si puedes ver esta página, significa que React y Tailwind CSS están funcionando correctamente.
        </p>
        <div className="mt-4 p-4 bg-white rounded-lg shadow">
          <h2 className="text-xl font-semibold mb-2">Estado del Sistema:</h2>
          <ul className="space-y-1">
            <li>✅ React: Funcionando</li>
            <li>✅ Tailwind CSS: Funcionando</li>
            <li>✅ Inertia.js: Funcionando</li>
            <li>✅ Laravel: Funcionando</li>
          </ul>
        </div>
      </div>
    </div>
  )
}
