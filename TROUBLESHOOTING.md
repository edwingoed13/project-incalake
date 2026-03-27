# 🔧 Guía de Solución de Problemas - Incalake Full Stack

Esta guía te ayudará a resolver problemas comunes al configurar el proyecto en una nueva PC.

---

## 📋 Requisitos Previos

Antes de comenzar, asegúrate de tener instalado:

- **Node.js** >= 18.x ([Descargar aquí](https://nodejs.org/))
- **PHP** >= 8.2 ([XAMPP 8.2](https://www.apachefriends.org/) recomendado)
- **Composer** >= 2.x ([Descargar aquí](https://getcomposer.org/))
- **MySQL/MariaDB** (incluido en XAMPP)
- **Git** ([Descargar aquí](https://git-scm.com/))

---

## 🚀 Instalación Inicial

### 1. Clonar el Repositorio

```bash
git clone https://github.com/edwingoed13/project-incalake.git
cd project-incalake/incalake-full-stack
```

### 2. Configurar el Backend (Laravel)

```bash
cd backend

# Instalar dependencias de PHP
composer install

# Copiar el archivo de configuración
cp .env.example .env

# Generar la clave de la aplicación
php artisan key:generate

# Configurar la base de datos en .env
# Edita el archivo .env y configura:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=incalake_db
DB_USERNAME=root
DB_PASSWORD=

# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders (opcional, para datos de prueba)
php artisan db:seed

# Crear enlace simbólico para storage
php artisan storage:link
```

### 3. Configurar el Frontend (Nuxt)

```bash
cd ../frontend

# Instalar dependencias de Node
npm install

# Copiar el archivo de configuración
cp .env.example .env

# Configurar las variables de entorno en .env
NUXT_PUBLIC_API_BASE=http://localhost:8001/api
NUXT_PUBLIC_STORAGE_BASE=http://localhost:8001/storage
```

### 4. Configurar el Admin (Nuxt)

```bash
cd ../admin

# Instalar dependencias de Node
npm install

# Copiar el archivo de configuración
cp .env.example .env

# Configurar las variables de entorno en .env
NUXT_PUBLIC_API_URL=http://localhost:8001/api
```

---

## 🐛 Problemas Comunes y Soluciones

### ❌ Problema 1: Errores de CSS / Tailwind no funciona

**Síntomas:**
- Estilos no se aplican correctamente
- Página se ve sin diseño
- Errores de PostCSS en consola

**Solución:**

```bash
# En frontend o admin (según donde ocurra el problema)
cd frontend  # o cd admin

# Limpiar caché de Node
rm -rf node_modules
rm package-lock.json

# Reinstalar dependencias
npm install

# Limpiar caché de Nuxt
rm -rf .nuxt
rm -rf .output

# Reiniciar el servidor
npm run dev
```

**Si persiste el problema (Windows):**

```bash
# Eliminar carpetas manualmente
rmdir /s /q node_modules
rmdir /s /q .nuxt
rmdir /s /q .output
del package-lock.json

# Reinstalar
npm install
```

---

### ❌ Problema 2: Error "Class does not exist" en Tailwind

**Síntomas:**
```
The `text-primary-light` class does not exist
The `text-secondary-dark` class does not exist
```

**Solución:**

Este error ya está corregido en la última versión del código. Si aún lo ves:

```bash
git pull origin master
cd frontend
npm install
```

Las clases personalizadas fueron reemplazadas por clases estándar de Tailwind (`text-slate-600`, `text-slate-800`, etc.).

---

### ❌ Problema 3: Errores de instalación de dependencias npm

**Síntomas:**
- `npm install` falla con errores de permisos
- Errores de "ERESOLVE" o conflictos de dependencias

**Solución 1 - Limpiar caché de npm:**

```bash
npm cache clean --force
npm install
```

**Solución 2 - Usar force o legacy peer deps:**

```bash
npm install --legacy-peer-deps
# o
npm install --force
```

**Solución 3 - Verificar versión de Node:**

```bash
node -v  # Debe ser >= 18.x

# Si tienes una versión antigua, descarga la última LTS de nodejs.org
```

---

### ❌ Problema 4: Error de PHP "Class not found" o Composer

**Síntomas:**
- `Class 'Illuminate\...' not found`
- Errores al ejecutar `php artisan`

**Solución:**

```bash
cd backend

# Limpiar caché de Composer
composer clear-cache

# Reinstalar dependencias
rm -rf vendor
composer install

# Regenerar autoload
composer dump-autoload

# Limpiar caché de Laravel
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

### ❌ Problema 5: Error "Your PHP version is not supported"

**Síntomas:**
```
Composer detected issues in your platform: Your Composer dependencies require a PHP version '>= 8.2.0'
```

**Solución:**

1. **Verificar versión de PHP:**

```bash
php -v
```

2. **Si tienes PHP 8.2 en otra ubicación (ej: C:\xampp82):**

```bash
# Windows
C:\xampp82\php\php.exe -v
C:\xampp82\php\php.exe artisan serve --port=8001

# Agregar a PATH (opcional)
# Panel de Control > Sistema > Variables de Entorno
# Agregar C:\xampp82\php a la variable PATH
```

3. **Actualizar Composer para usar PHP 8.2:**

```bash
composer config platform.php 8.2.0
composer install
```

---

### ❌ Problema 6: MySQL no se conecta

**Síntomas:**
```
SQLSTATE[HY000] [2002] No connection could be made
Access denied for user 'root'@'localhost'
```

**Solución:**

1. **Verificar que MySQL está corriendo:**
   - Abrir XAMPP Control Panel
   - Iniciar módulo MySQL
   - Verificar que el puerto 3306 esté disponible

2. **Verificar credenciales en `.env`:**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=incalake_db
DB_USERNAME=root
DB_PASSWORD=          # Dejar vacío si no tiene contraseña
```

3. **Crear la base de datos manualmente:**

```bash
# Abrir phpMyAdmin (http://localhost/phpmyadmin)
# O usar línea de comandos:
mysql -u root -p
CREATE DATABASE incalake_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

4. **Ejecutar migraciones nuevamente:**

```bash
cd backend
php artisan migrate:fresh --seed
```

---

### ❌ Problema 7: Puerto ya en uso

**Síntomas:**
```
Error: listen EADDRINUSE: address already in use :::3001
Port 8001 is already in use
```

**Solución:**

**Windows:**

```bash
# Encontrar el proceso que usa el puerto
netstat -ano | findstr :3001
netstat -ano | findstr :8001

# Matar el proceso (usar el PID de la salida anterior)
taskkill /PID <PID> /F

# O cambiar el puerto
npm run dev -- --port 3002  # Para Nuxt
php artisan serve --port=8002  # Para Laravel
```

**Linux/Mac:**

```bash
# Encontrar el proceso
lsof -i :3001
lsof -i :8001

# Matar el proceso
kill -9 <PID>
```

---

### ❌ Problema 8: Modo oscuro no respeta preferencia

**Síntomas:**
- Algunas páginas aparecen en modo oscuro
- Otras en modo claro
- La preferencia no se guarda

**Solución:**

Este problema ya está corregido en la última versión. Si aún lo ves:

```bash
git pull origin master

# Limpiar localStorage del navegador
# Abrir DevTools (F12) > Console:
localStorage.clear()

# Recargar la página
```

Por defecto, todo está en **modo claro**. El usuario puede cambiar a modo oscuro usando el botón de tema (sol/luna).

---

### ❌ Problema 9: Sesión de admin se pierde al recargar

**Síntomas:**
- Después de hacer login, al refrescar (F5) te saca al login
- No mantiene la sesión

**Solución:**

Este problema ya está corregido. El sistema ahora guarda el token en localStorage. Si persiste:

```bash
cd admin
git pull origin master
npm install

# Limpiar caché del navegador
# DevTools (F12) > Application > Clear storage > Clear site data
```

---

### ❌ Problema 10: Páginas de tours muestran spinner infinito

**Síntomas:**
- Spinner de carga no desaparece
- Página blanca antes de cargar contenido

**Solución:**

Este problema ya está corregido (se removió `lazy: true`). Si persiste:

```bash
cd frontend
git pull origin master
npm install

# Limpiar caché
rm -rf .nuxt
npm run dev
```

---

## 🏃 Levantar el Proyecto

Una vez configurado todo, usar estos comandos para levantar los servicios:

### Terminal 1 - Backend (Laravel)

```bash
cd backend

# Si tienes PHP 8.2 en C:\xampp82 (Windows)
C:\xampp82\php\php.exe artisan serve --port=8001

# Si PHP 8.2 está en PATH
php artisan serve --port=8001
```

### Terminal 2 - Frontend (Nuxt)

```bash
cd frontend
npm run dev -- --port 3001
```

### Terminal 3 - Admin (Nuxt)

```bash
cd admin
npm run dev -- --port 54112
```

### URLs de acceso:

- 🌐 **Frontend**: http://localhost:3001
- 🔐 **Admin**: http://localhost:54112
- 🔌 **API Backend**: http://localhost:8001/api

---

## 📦 Comandos Útiles

### Backend (Laravel)

```bash
# Limpiar todas las cachés
php artisan optimize:clear

# Recrear base de datos con datos de prueba
php artisan migrate:fresh --seed

# Ver rutas disponibles
php artisan route:list

# Generar nuevo token de aplicación
php artisan key:generate
```

### Frontend/Admin (Nuxt)

```bash
# Construir para producción
npm run build

# Previsualizar build de producción
npm run preview

# Analizar bundle size
npm run analyze

# Limpiar completamente
rm -rf node_modules .nuxt .output
npm install
```

---

## 🔍 Verificación de Instalación

Ejecuta estos comandos para verificar que todo está instalado correctamente:

```bash
# Verificar versiones
node -v        # Debe ser >= 18.x
npm -v         # Debe ser >= 9.x
php -v         # Debe ser >= 8.2
composer -V    # Debe ser >= 2.x
mysql --version

# Verificar servicios
# MySQL debe estar corriendo en puerto 3306
# Backend debe responder en http://localhost:8001
# Frontend debe responder en http://localhost:3001
# Admin debe responder en http://localhost:54112
```

---

## 💡 Tips Adicionales

### Windows + XAMPP

1. **Ejecutar como Administrador**: Abre la terminal/PowerShell como administrador para evitar problemas de permisos
2. **Deshabilitar Antivirus temporalmente**: A veces bloquea la instalación de dependencias
3. **Usar Git Bash**: Mejor compatibilidad con comandos Linux

### Usar Visual Studio Code

Extensiones recomendadas:
- Vue Language Features (Volar)
- PHP Intelephense
- Tailwind CSS IntelliSense
- Laravel Blade Snippets
- ESLint

### Performance

Si el proyecto va lento en desarrollo:

```bash
# Frontend/Admin - Aumentar memoria de Node
$env:NODE_OPTIONS="--max-old-space-size=4096"  # Windows PowerShell
export NODE_OPTIONS="--max-old-space-size=4096"  # Linux/Mac

npm run dev
```

---

## 📞 Soporte

Si ninguna de estas soluciones funciona:

1. Revisa los logs de error completos
2. Verifica que todos los puertos estén disponibles
3. Asegúrate de tener las versiones correctas de PHP y Node.js
4. Intenta en un navegador diferente (Chrome/Firefox)
5. Revisa los issues en GitHub: https://github.com/edwingoed13/project-incalake/issues

---

## 📝 Notas de la Última Actualización

**Versión**: 2026-03-27

**Cambios importantes:**
- ✅ Modo claro por defecto (frontend y admin)
- ✅ Autenticación persistente con localStorage
- ✅ CSS de Tailwind corregido en componentes de tours
- ✅ Navbar con mejor contraste
- ✅ Carga rápida sin spinners
- ✅ Login simplificado con toggle de contraseña

**Migrar desde versión anterior:**

```bash
git pull origin master
cd frontend && npm install
cd ../admin && npm install
cd ../backend && composer install
```

---

¡Buena suerte! 🚀
