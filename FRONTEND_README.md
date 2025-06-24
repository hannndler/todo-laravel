# Frontend - Todo App

Este proyecto utiliza React con TypeScript, shadcn/ui, Zustand para manejo de estado, y Zod para validaciones.

## 🚀 Tecnologías Utilizadas

- **React 19** con TypeScript
- **shadcn/ui** - Componentes de UI modernos
- **Zustand** - Manejo de estado global
- **Zod** - Validación de esquemas
- **React Hook Form** - Manejo de formularios
- **Lucide React** - Iconos
- **Tailwind CSS** - Estilos
- **Inertia.js** - Navegación SPA

## 📁 Estructura del Proyecto

```
resources/js/
├── components/
│   ├── auth/
│   │   ├── login-form.tsx          # Formulario de login
│   │   ├── register-form.tsx       # Formulario de registro
│   │   └── protected-route.tsx     # Componente de protección de rutas
│   ├── navigation/
│   │   └── main-nav.tsx            # Navegación principal
│   └── ui/                         # Componentes shadcn/ui
├── hooks/
│   └── use-navigation.ts           # Hook para navegación
├── layouts/
│   └── authenticated-layout.tsx    # Layout para páginas autenticadas
├── lib/
│   ├── utils.ts                    # Utilidades
│   └── validations/
│       └── auth.ts                 # Esquemas de validación Zod
├── pages/
│   ├── auth/
│   │   └── auth-page.tsx           # Página de autenticación
│   ├── dashboard.tsx               # Dashboard principal
│   ├── tasks.tsx                   # Gestión de tareas
│   ├── categories.tsx              # Gestión de categorías
│   └── profile.tsx                 # Perfil de usuario
└── stores/
    └── auth-store.ts               # Store de autenticación con Zustand
```

## 🔐 Autenticación

### Store de Autenticación (Zustand)

El store maneja:
- Estado del usuario autenticado
- Token de acceso
- Funciones de login/registro/logout
- Persistencia en localStorage

```typescript
import { useAuthStore } from '@/stores/auth-store'

// Obtener estado
const { user, isAuthenticated, token } = useAuthStore()

// Acciones
const { login, register, logout } = useAuthStore()
```

### Validaciones (Zod)

Esquemas de validación para formularios:

```typescript
import { loginSchema, registerSchema } from '@/lib/validations/auth'

// Validar datos
const validatedData = loginSchema.parse(formData)
```

## 🎨 Componentes UI

### Formularios

Los formularios utilizan React Hook Form con Zod:

```typescript
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'

const {
  register,
  handleSubmit,
  formState: { errors },
} = useForm<FormData>({
  resolver: zodResolver(schema),
})
```

### Componentes shadcn/ui

Componentes reutilizables y accesibles:

- `Button` - Botones con variantes
- `Input` - Campos de entrada
- `Card` - Tarjetas contenedoras
- `Dialog` - Modales
- `DropdownMenu` - Menús desplegables
- `Badge` - Etiquetas
- `Avatar` - Avatares de usuario

## 📱 Páginas Disponibles

### 1. Autenticación (`/auth/auth-page`)
- Login y registro en una sola página
- Validación de formularios con Zod
- Manejo de errores
- Redirección automática

### 2. Dashboard (`/dashboard`)
- Vista general de estadísticas
- Tareas recientes
- Layout autenticado

### 3. Tareas (`/tasks`)
- Lista de tareas con filtros
- Búsqueda por texto
- Filtros por estado y prioridad
- Interfaz responsive

### 4. Categorías (`/categories`)
- Gestión CRUD de categorías
- Selector de colores
- Modales para crear/editar

### 5. Perfil (`/profile`)
- Información personal del usuario
- Modo de edición inline
- Avatar y estado de cuenta

## 🧭 Navegación

### Navegación Principal
- Dashboard
- Tareas
- Categorías
- Equipos
- Perfil
- Configuración

### Responsive
- Menú hamburguesa en móvil
- Navegación horizontal en desktop
- Sheet lateral para móvil

## 🔒 Protección de Rutas

El componente `ProtectedRoute` verifica la autenticación:

```typescript
import { ProtectedRoute } from '@/components/auth/protected-route'

<ProtectedRoute>
  <YourComponent />
</ProtectedRoute>
```

## 🎯 Características Principales

### Estado Global (Zustand)
- ✅ Persistencia automática
- ✅ Tipado completo con TypeScript
- ✅ Acciones asíncronas
- ✅ Manejo de errores

### Validaciones (Zod)
- ✅ Esquemas tipados
- ✅ Mensajes de error personalizados
- ✅ Validaciones complejas
- ✅ Integración con React Hook Form

### UI/UX (shadcn/ui)
- ✅ Componentes accesibles
- ✅ Tema oscuro/claro
- ✅ Responsive design
- ✅ Animaciones suaves

### Navegación (Inertia.js)
- ✅ SPA sin recargas
- ✅ Historial del navegador
- ✅ Estados de carga
- ✅ Manejo de errores

## 🚀 Cómo Usar

### 1. Instalar Dependencias
```bash
npm install
```

### 2. Ejecutar en Desarrollo
```bash
npm run dev
```

### 3. Construir para Producción
```bash
npm run build
```

## 📝 Rutas de la API

El frontend consume las siguientes rutas de la API:

- `POST /api/login` - Autenticación
- `POST /api/register` - Registro
- `POST /api/logout` - Cerrar sesión
- `GET /api/user` - Información del usuario
- `GET /api/tasks` - Listar tareas
- `POST /api/tasks` - Crear tarea
- `PUT /api/tasks/{id}` - Actualizar tarea
- `DELETE /api/tasks/{id}` - Eliminar tarea
- `GET /api/categories` - Listar categorías
- `POST /api/categories` - Crear categoría
- `PUT /api/categories/{id}` - Actualizar categoría
- `DELETE /api/categories/{id}` - Eliminar categoría

## 🎨 Personalización

### Temas
Los componentes utilizan CSS variables para temas:

```css
:root {
  --background: 0 0% 100%;
  --foreground: 222.2 84% 4.9%;
  /* ... más variables */
}
```

### Colores de Categorías
Las categorías tienen colores predefinidos:
- Azul: `#3B82F6`
- Rojo: `#EF4444`
- Verde: `#10B981`
- Amarillo: `#F59E0B`
- Púrpura: `#8B5CF6`
- Naranja: `#F97316`
- Cian: `#06B6D4`
- Rosa: `#EC4899`

## 🔧 Desarrollo

### Agregar Nuevas Páginas
1. Crear componente en `pages/`
2. Usar `AuthenticatedLayout` si requiere autenticación
3. Agregar ruta en navegación si es necesario

### Agregar Nuevos Stores
1. Crear archivo en `stores/`
2. Usar Zustand con persistencia si es necesario
3. Exportar hooks personalizados

### Agregar Validaciones
1. Crear esquema en `lib/validations/`
2. Usar Zod para definir reglas
3. Integrar con React Hook Form

## 📱 Responsive Design

El diseño es completamente responsive:
- **Móvil**: < 768px
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

## 🎯 Próximas Mejoras

- [ ] Notificaciones en tiempo real
- [ ] Drag & drop para tareas
- [ ] Calendario de tareas
- [ ] Reportes y estadísticas
- [ ] Exportación de datos
- [ ] Temas personalizables
- [ ] Modo offline
- [ ] PWA capabilities 
