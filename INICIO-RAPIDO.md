# 🚀 Inicio Rápido - Control de Eventos

Guía rápida para iniciar el proyecto en menos de 5 minutos.

## Prerequisitos

✅ PHP 8.2+  
✅ Composer  
✅ Node.js 18+  
✅ Git  

## Instalación Express (Método Automático)

Si tienes todos los prerequisitos instalados, puedes usar el script de instalación automática:

```bash
# 1. Clonar el repositorio
git clone https://github.com/PedroMar2024/controlEventos2.git
cd controlEventos2

# 2. Ejecutar el script de setup automático
composer setup
```

Este comando ejecutará automáticamente:
- ✅ Instalación de dependencias PHP
- ✅ Creación del archivo .env
- ✅ Generación de clave de aplicación
- ✅ Ejecución de migraciones
- ✅ Instalación de dependencias Node.js
- ✅ Compilación de assets

## Instalación Manual (Paso a Paso)

Si prefieres hacerlo manualmente o el método automático falla:

```bash
# 1. Clonar repositorio
git clone https://github.com/PedroMar2024/controlEventos2.git
cd controlEventos2

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias Node.js
npm install

# 4. Configurar entorno
cp .env.example .env

# 5. Generar clave de aplicación
php artisan key:generate

# 6. Crear base de datos SQLite
touch database/database.sqlite

# 7. Ejecutar migraciones
php artisan migrate

# 8. Compilar assets
npm run build
```

## Ejecutar el Proyecto

### Opción 1: Modo Desarrollo Completo (Recomendado)

```bash
composer dev
```

Esto inicia todo lo necesario en una sola terminal:
- 🌐 Servidor web (http://localhost:8000)
- ⚡ Vite (compilador de assets en vivo)
- 📋 Cola de trabajos
- 📝 Monitor de logs

### Opción 2: Solo Servidor Web

```bash
# Terminal 1
php artisan serve

# Terminal 2 (en otra terminal)
npm run dev
```

## Acceder a la Aplicación

🌐 **URL:** http://localhost:8000

### Primeros Pasos

1. **Registrar un usuario:** http://localhost:8000/register
2. **Iniciar sesión:** http://localhost:8000/login
3. **Dashboard:** Acceder al panel de control

## Comandos Útiles

```bash
# Limpiar todo el caché
php artisan optimize:clear

# Ver todas las rutas
php artisan route:list

# Ejecutar tests
php artisan test

# Ver logs en tiempo real
php artisan pail
```

## Solución de Problemas Rápidos

### ❌ "No application encryption key"
```bash
php artisan key:generate
```

### ❌ Error de base de datos SQLite
```bash
touch database/database.sqlite
php artisan migrate
```

### ❌ Error de permisos
```bash
chmod -R 775 storage bootstrap/cache
```

### ❌ Puerto 8000 ocupado
```bash
php artisan serve --port=8001
```

### ❌ Assets no se cargan
```bash
npm run build
```

## Datos de Prueba (Opcional)

Si quieres poblar la base de datos con datos de prueba:

```bash
php artisan db:seed
```

## Detener la Aplicación

Si usaste `composer dev`:
- Presiona `Ctrl + C` en la terminal

Si usaste comandos separados:
- Presiona `Ctrl + C` en cada terminal

## Próximos Pasos

📖 Lee el [README.md](README.md) completo para documentación detallada  
🔧 Personaliza el archivo `.env` según tus necesidades  
👥 Configura roles y permisos en la aplicación  
🎫 Comienza a crear tus primeros eventos  

## Estructura Básica

```
📁 app/Models/          → Modelos de datos (Evento, Persona, User)
📁 app/Http/Controllers/ → Lógica de negocio
📁 database/migrations/ → Estructura de base de datos
📁 resources/views/     → Vistas Blade
📁 routes/web.php       → Rutas de la aplicación
```

## Soporte

¿Problemas durante la instalación?
- Revisa los [Requisitos Previos](README.md#requisitos-previos)
- Consulta la sección de [Solución de Problemas](README.md#solución-de-problemas)
- Verifica que todos los servicios requeridos estén instalados

---

**¡Listo! 🎉** Ya puedes comenzar a trabajar con Control de Eventos.
