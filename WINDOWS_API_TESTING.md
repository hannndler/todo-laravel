# 🪟 Probando APIs desde Windows hacia WSL

## 🌐 Configuración de Red WSL

### 1. Obtener la IP de WSL
```bash
# En WSL, ejecutar:
ip addr show eth0 | grep "inet\b" | awk '{print $2}' | cut -d/ -f1
```

O más simple:
```bash
# En WSL
hostname -I
```

### 2. Configurar el servidor para aceptar conexiones externas
En WSL, iniciar el servidor con:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## 🛠️ Opciones para Probar desde Windows

### Opción 1: Postman (Recomendado)

1. **Descargar Postman** desde [postman.com](https://www.postman.com/downloads/)

2. **Importar la colección**:
   - Abrir Postman
   - Click en "Import"
   - Seleccionar `Todo_API_Collection.postman_collection.json`

3. **Configurar variables**:
   - Click en el ícono de engranaje (⚙️) en la esquina superior derecha
   - Agregar variables:
     - `base_url`: `http://IP_DE_WSL:8000` (ej: `http://172.20.0.1:8000`)
     - `email`: `test@example.com`
     - `password`: `password`

4. **Probar**:
   - Ejecutar primero "Login"
   - Luego probar otros endpoints

### Opción 2: PowerShell con cURL

```powershell
# Obtener token
$response = Invoke-RestMethod -Uri "http://IP_DE_WSL:8000/api/login" `
  -Method POST `
  -Headers @{"Content-Type"="application/json"} `
  -Body '{"email":"test@example.com","password":"password"}'

$token = $response.token

# Probar dashboard
Invoke-RestMethod -Uri "http://IP_DE_WSL:8000/api/dashboard" `
  -Method GET `
  -Headers @{
    "Authorization"="Bearer $token"
    "Accept"="application/json"
  }
```

### Opción 3: cURL en Windows

Si tienes cURL instalado en Windows:
```cmd
# Login
curl -X POST "http://IP_DE_WSL:8000/api/login" ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"test@example.com\",\"password\":\"password\"}"

# Usar el token (reemplaza TU_TOKEN con el token obtenido)
curl -X GET "http://IP_DE_WSL:8000/api/dashboard" ^
  -H "Authorization: Bearer TU_TOKEN" ^
  -H "Accept: application/json"
```

### Opción 4: Script PowerShell

Crear archivo `test_apis_windows.ps1`:
```powershell
param(
    [string]$BaseUrl = "http://172.20.0.1:8000",
    [string]$Email = "test@example.com",
    [string]$Password = "password"
)

Write-Host "🚀 Iniciando pruebas de APIs en: $BaseUrl" -ForegroundColor Green
Write-Host "📧 Email: $Email" -ForegroundColor Yellow

# Login
Write-Host "🔐 Obteniendo token..." -ForegroundColor Cyan
$loginBody = @{
    email = $Email
    password = $Password
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$BaseUrl/api/login" `
        -Method POST `
        -Headers @{"Content-Type"="application/json"} `
        -Body $loginBody
    
    $token = $response.token
    Write-Host "✅ Token obtenido: $($token.Substring(0,20))..." -ForegroundColor Green
    
    # Probar dashboard
    Write-Host "📊 Probando Dashboard..." -ForegroundColor Cyan
    $dashboard = Invoke-RestMethod -Uri "$BaseUrl/api/dashboard" `
        -Method GET `
        -Headers @{
            "Authorization"="Bearer $token"
            "Accept"="application/json"
        }
    
    Write-Host "✅ Dashboard: $($dashboard | ConvertTo-Json)" -ForegroundColor Green
    
} catch {
    Write-Host "❌ Error: $($_.Exception.Message)" -ForegroundColor Red
}
```

## 🔧 Configuración Avanzada

### 1. Configurar firewall de Windows
Si tienes problemas de conectividad:
1. Abrir "Firewall de Windows Defender"
2. Permitir conexiones entrantes al puerto 8000

### 2. Usar localhost (si funciona)
Algunas versiones de WSL permiten usar `localhost`:
```bash
# Probar si funciona
curl http://localhost:8000/api/login
```

### 3. Configurar hosts de Windows
Agregar en `C:\Windows\System32\drivers\etc\hosts`:
```
IP_DE_WSL    wsl-todo.local
```

Luego usar: `http://wsl-todo.local:8000`

## 📋 Ejemplos de IPs Comunes

| Versión WSL | IP Típica |
|-------------|-----------|
| WSL1        | `127.0.0.1` |
| WSL2        | `172.20.0.1` o similar |

## 🚨 Solución de Problemas

### Error: "Connection refused"
- Verificar que el servidor esté corriendo en WSL
- Confirmar que use `--host=0.0.0.0`
- Verificar la IP de WSL

### Error: "Timeout"
- Verificar firewall de Windows
- Probar con `localhost` en lugar de IP

### Error: "CORS"
- Agregar middleware CORS en Laravel si es necesario

## 📞 Comandos Útiles en WSL

```bash
# Verificar que el servidor esté corriendo
netstat -tlnp | grep :8000

# Ver logs de Laravel
tail -f storage/logs/laravel.log

# Reiniciar servidor
pkill -f "php artisan serve"
php artisan serve --host=0.0.0.0 --port=8000
``` 
