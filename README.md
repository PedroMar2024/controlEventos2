# Control de Eventos

Sistema de gestión de eventos desarrollado con Laravel 12, que permite administrar eventos, personas, tickets y roles.

## Características

- 🎫 Gestión de eventos
- 👥 Gestión de personas y asistentes
- 🎟️ Sistema de tickets para eventos
- 🔐 Sistema de autenticación y autorización con roles
- 📧 Notificaciones por correo
- 🎨 Interfaz moderna con Tailwind CSS y Alpine.js

## Requisitos Previos

Antes de comenzar, asegúrate de tener instalado en tu sistema:

- **PHP** >= 8.2 ([Descargar PHP](https://www.php.net/downloads.php))
- **Composer** >= 2.0 ([Descargar Composer](https://getcomposer.org/download/))
- **Node.js** >= 18.0 y **npm** >= 9.0 ([Descargar Node.js](https://nodejs.org/))
- **SQLite** (incluido por defecto en PHP) o **MySQL/PostgreSQL** (opcional)
- **Git** ([Descargar Git](https://git-scm.com/downloads))

### Verificar instalaciones

```bash
php --version    # Debe mostrar PHP 8.2 o superior
composer --version
node --version   # Debe mostrar Node 18 o superior
npm --version
```

## Instalación desde Cero

Sigue estos pasos para configurar el proyecto en tu máquina local:

### 1. Clonar el repositorio

```bash
git clone https://github.com/PedroMar2024/controlEventos2.git
cd controlEventos2
```

### 2. Instalar dependencias de PHP

```bash
composer install
```

Este comando instalará todas las dependencias de PHP definidas en `composer.json`, incluyendo:
- Laravel Framework 12
- Laravel Breeze (autenticación)
- Spatie Laravel Permission (roles y permisos)
- Y más...

### 3. Instalar dependencias de JavaScript

```bash
npm install
```

Este comando instalará:
- Vite (bundler de assets)
- Tailwind CSS
- Alpine.js
- Y otras dependencias frontend

### 4. Configurar el archivo de entorno

Copia el archivo de ejemplo de configuración:

```bash
cp .env.example .env
```

Edita el archivo `.env` si necesitas personalizar alguna configuración (por defecto usa SQLite):

```env
APP_NAME="Control de Eventos"
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# Para usar MySQL/PostgreSQL, descomenta y configura:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=control_eventos
# DB_USERNAME=root
# DB_PASSWORD=
```

### 5. Generar la clave de la aplicación

```bash
php artisan key:generate
```

Este comando genera una clave de cifrado única para tu aplicación.

### 6. Crear la base de datos

Si usas SQLite (configuración por defecto):

```bash
touch database/database.sqlite
```

Si usas MySQL o PostgreSQL, crea la base de datos manualmente:

```bash
# Para MySQL
mysql -u root -p
CREATE DATABASE control_eventos;
exit;
```

### 7. Ejecutar las migraciones

```bash
php artisan migrate
```

Este comando creará todas las tablas necesarias:
- users (usuarios)
- personas (personas/asistentes)
- eventos (eventos)
- evento_tickets (tickets de eventos)
- event_persona_roles (roles de personas en eventos)
- permissions y roles (sistema de permisos)
- cache, jobs, sessions (tablas del sistema)

### 8. (Opcional) Ejecutar los seeders

Si el proyecto tiene datos de prueba:

```bash
php artisan db:seed
```

### 9. Compilar los assets

Para desarrollo:

```bash
npm run dev
```

Para producción:

```bash
npm run build
```

### 10. Configurar permisos de almacenamiento

```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

## Ejecutar la Aplicación

### Método 1: Usando el script de desarrollo (Recomendado)

Este comando inicia todos los servicios necesarios en paralelo:

```bash
composer dev
```

Esto iniciará:
- Servidor web en http://localhost:8000
- Cola de trabajos (queue worker)
- Monitor de logs (Pail)
- Compilador de assets (Vite)

### Método 2: Usando comandos individuales

En terminales separadas, ejecuta:

**Terminal 1 - Servidor web:**
```bash
php artisan serve
```

**Terminal 2 - Compilador de assets:**
```bash
npm run dev
```

**Terminal 3 (opcional) - Cola de trabajos:**
```bash
php artisan queue:work
```

### Acceder a la aplicación

Abre tu navegador y visita:
- **Aplicación:** http://localhost:8000
- **Registro/Login:** http://localhost:8000/register

## Comandos Útiles

### Desarrollo

```bash
# Limpiar cachés
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Ver rutas disponibles
php artisan route:list

# Crear un nuevo controlador
php artisan make:controller NombreController

# Crear un nuevo modelo con migración
php artisan make:model Nombre -m

# Crear un seeder
php artisan make:seeder NombreSeeder
```

### Testing

```bash
# Ejecutar todos los tests
composer test

# O directamente con artisan
php artisan test

# Ejecutar un test específico
php artisan test --filter NombreDelTest
```

### Formato de código

```bash
# Formatear código con Laravel Pint
./vendor/bin/pint

# O usando composer
composer pint
```

## Estructura del Proyecto

```
controlEventos2/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Controladores
│   │   │   ├── EventoController.php
│   │   │   ├── PersonaController.php
│   │   │   └── ...
│   │   └── Requests/        # Validaciones
│   ├── Models/              # Modelos Eloquent
│   │   ├── Evento.php
│   │   ├── Persona.php
│   │   ├── EventoTicket.php
│   │   └── User.php
│   └── Policies/            # Políticas de autorización
├── database/
│   ├── migrations/          # Migraciones de base de datos
│   └── seeders/             # Datos de prueba
├── resources/
│   ├── views/               # Plantillas Blade
│   └── js/                  # JavaScript/Alpine.js
├── routes/
│   ├── web.php              # Rutas web
│   └── auth.php             # Rutas de autenticación
├── public/                  # Archivos públicos
├── storage/                 # Almacenamiento
└── tests/                   # Tests automatizados
```

## Solución de Problemas

### Error: "No application encryption key has been specified"

```bash
php artisan key:generate
```

### Error de permisos en storage/

```bash
chmod -R 775 storage bootstrap/cache
# En Linux/Mac, también:
sudo chown -R $USER:www-data storage bootstrap/cache
```

### Error: "SQLSTATE[HY000] [14] unable to open database file"

```bash
# Crea el archivo de base de datos
touch database/database.sqlite
# Dale permisos
chmod 664 database/database.sqlite
```

### Los assets no se compilan

```bash
# Limpia la caché de npm
npm cache clean --force
# Reinstala dependencias
rm -rf node_modules package-lock.json
npm install
npm run dev
```

### Puerto 8000 ya en uso

```bash
# Usa otro puerto
php artisan serve --port=8001
```

## Tecnologías Utilizadas

- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Blade, Tailwind CSS, Alpine.js
- **Base de datos:** SQLite (desarrollo) / MySQL/PostgreSQL (producción)
- **Autenticación:** Laravel Breeze
- **Permisos:** Spatie Laravel Permission
- **Build tool:** Vite
- **Testing:** PHPUnit

## Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## Licencia

Este proyecto es de código abierto bajo la licencia MIT.
