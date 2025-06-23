# Guía para Probar las APIs

## 📋 Endpoints Disponibles

### 🔐 Autenticación
```bash
# Login
POST /api/login
{
    "email": "tu@email.com",
    "password": "tu_password"
}

# Registro
POST /api/register
{
    "name": "Tu Nombre",
    "email": "tu@email.com",
    "password": "tu_password"
}

# Obtener usuario autenticado
GET /api/user

# Logout
POST /api/logout
```

### 📊 Dashboard
```bash
# Dashboard principal
GET /api/dashboard

# Resumen de tareas
GET /api/dashboard/tasks-summary

# Rendimiento del equipo
GET /api/dashboard/team-performance
```

### ✅ Tareas (Tasks)
```bash
# Listar tareas
GET /api/tasks

# Crear tarea
POST /api/tasks
{
    "title": "Nueva tarea",
    "description": "Descripción de la tarea",
    "priority": "low|medium|high",
    "status": "pending|in_progress|completed|cancelled",
    "due_date": "2024-12-31",
    "category_id": 1,
    "assigned_to": 1
}

# Ver tarea específica
GET /api/tasks/{id}

# Actualizar tarea
PUT /api/tasks/{id}
{
    "title": "Tarea actualizada",
    "description": "Nueva descripción"
}

# Eliminar tarea
DELETE /api/tasks/{id}

# Marcar como completada
PATCH /api/tasks/{id}/complete

# Marcar en progreso
PATCH /api/tasks/{id}/in-progress

# Cancelar tarea
PATCH /api/tasks/{id}/cancel
```

### 👥 Equipos (Teams)
```bash
# Listar equipos
GET /api/teams

# Crear equipo
POST /api/teams
{
    "name": "Nuevo equipo",
    "description": "Descripción del equipo"
}

# Ver equipo específico
GET /api/teams/{id}

# Actualizar equipo
PUT /api/teams/{id}
{
    "name": "Equipo actualizado"
}

# Eliminar equipo
DELETE /api/teams/{id}

# Agregar miembro
POST /api/teams/{id}/members
{
    "user_id": 1,
    "role": "member"
}

# Remover miembro
DELETE /api/teams/{id}/members
{
    "user_id": 1
}

# Cambiar rol de miembro
PATCH /api/teams/{id}/members/{member_id}/role
{
    "role": "admin"
}

# Transferir propiedad
POST /api/teams/{id}/transfer-ownership
{
    "new_owner_id": 1
}
```

### 👤 Usuarios (Users)
```bash
# Listar usuarios
GET /api/users

# Ver perfil
GET /api/users/profile

# Actualizar perfil
PUT /api/users/profile
{
    "name": "Nuevo nombre",
    "email": "nuevo@email.com"
}
```

### 📂 Categorías (Categories)
```bash
# Listar categorías
GET /api/categories

# Crear categoría
POST /api/categories
{
    "name": "Nueva categoría",
    "description": "Descripción",
    "color": "#3B82F6"
}

# Ver categoría específica
GET /api/categories/{id}

# Actualizar categoría
PUT /api/categories/{id}
{
    "name": "Categoría actualizada"
}

# Eliminar categoría
DELETE /api/categories/{id}
```

### 🔑 Roles
```bash
# Listar roles
GET /api/roles

# Listar permisos
GET /api/roles/permissions/list
```

## 🛠️ Herramientas para Probar

### 1. cURL
```bash
# Ejemplo básico
curl -X GET "http://localhost:8000/api/dashboard" \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

### 2. Postman
1. Importa la colección `Todo_API_Collection.postman_collection.json`
2. Configura la variable de entorno `base_url` como `http://localhost:8000`
3. Configura las variables `email` y `password` con tus credenciales
4. Ejecuta primero el endpoint "Login" para obtener el token automáticamente
5. Usa los demás endpoints que ya tienen el token configurado

### 3. Insomnia
Similar a Postman, pero más ligero

### 4. Script Automatizado
```bash
# Ejecutar el script de prueba
./test_apis.sh

# O con parámetros personalizados
./test_apis.sh http://localhost:8000 tu@email.com tu_password
```

## 🔧 Configuración Inicial

### 1. Iniciar el servidor
```bash
php artisan serve
```

### 2. Crear usuario de prueba
```bash
php artisan tinker
```
```php
User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now()
]);
```

### 3. Ejecutar migraciones y seeders
```bash
php artisan migrate:fresh --seed
```

## 📝 Ejemplos de Uso

### Flujo completo de autenticación y creación de tarea:
```bash
# 1. Login
curl -X POST "http://localhost:8000/api/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Guardar el token de la respuesta
TOKEN="tu_token_aqui"

# 2. Crear categoría
curl -X POST "http://localhost:8000/api/categories" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Trabajo","description":"Tareas laborales","color":"#3B82F6"}'

# 3. Crear tarea
curl -X POST "http://localhost:8000/api/tasks" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Reunión importante","description":"Preparar presentación","priority":"high","due_date":"2024-12-31"}'

# 4. Ver tareas
curl -X GET "http://localhost:8000/api/tasks" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

### Registro de nuevo usuario:
```bash
curl -X POST "http://localhost:8000/api/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Nuevo Usuario",
    "email": "nuevo@example.com",
    "password": "password123"
  }'
```

## 🚨 Códigos de Estado HTTP

- `200` - Éxito
- `201` - Creado exitosamente
- `400` - Error en la solicitud
- `401` - No autorizado (token inválido)
- `403` - Prohibido (sin permisos)
- `404` - No encontrado
- `422` - Error de validación
- `500` - Error del servidor

## 💡 Consejos

1. **Siempre incluye headers**:
   - `Authorization: Bearer TU_TOKEN`
   - `Content-Type: application/json`
   - `Accept: application/json`

2. **Verifica el token** después del login

3. **Usa IDs válidos** para las relaciones (categorías, usuarios, etc.)

4. **Revisa las validaciones** en los controladores si tienes errores 422

5. **Prueba primero los endpoints GET** para verificar la autenticación

6. **Para Postman**: El token se guarda automáticamente después del login
