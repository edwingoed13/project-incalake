# 🚀 Setup Instructions - Incalake Full Stack

## Estructura del Monorepo Creada

La estructura del monorepo ha sido creada exitosamente con los siguientes archivos:

```
incalake-full-stack/
├── README.md                 ✅ Documentación completa del proyecto
├── .gitignore               ✅ Configuración de archivos ignorados
├── .env.example             ✅ Variables de entorno de ejemplo
├── package.json             ✅ Scripts de gestión centralizados
├── Makefile                 ✅ Comandos de utilidad
├── docker-compose.yml       ✅ Configuración Docker completa
├── docker/
│   ├── php/
│   │   └── Dockerfile       ✅ Imagen PHP 8.2 + Laravel
│   └── nginx/
│       └── default.conf     ✅ Configuración Nginx
├── backend/                 📁 (Pendiente: copiar laravel-incalake-v12)
├── frontend/                📁 (Pendiente: copiar tour-nuxt4)
└── admin/                   📁 (Pendiente: copiar cms-admin-nuxt4)
```

## ⚠️ Pasos Pendientes para Completar el Setup

### 1. Copiar los Proyectos

Necesitas copiar manualmente los tres proyectos a sus respectivas carpetas:

```bash
# Backend Laravel
xcopy /E /I /Y laravel-incalake-v12\* incalake-full-stack\backend\

# Frontend Nuxt
xcopy /E /I /Y tour-nuxt4\* incalake-full-stack\frontend\

# Admin Panel Nuxt
xcopy /E /I /Y cms-admin-nuxt4\* incalake-full-stack\admin\
```

### 2. Crear Archivos de Entorno

En cada subdirectorio, crear los archivos `.env` basados en los ejemplos:

#### Backend (.env)
```bash
cd incalake-full-stack\backend
copy .env.example .env
# Editar .env con tus credenciales
```

#### Frontend (.env)
```bash
cd incalake-full-stack\frontend
echo NUXT_PUBLIC_API_BASE=http://localhost:8001/api > .env
echo NUXT_PUBLIC_STORAGE_BASE=http://localhost:8001/storage >> .env
```

#### Admin (.env)
```bash
cd incalake-full-stack\admin
echo NUXT_PUBLIC_API_BASE=http://localhost:8001/api > .env
echo NUXT_PUBLIC_STORAGE_BASE=http://localhost:8001/storage >> .env
```

### 3. Instalar Dependencias

Desde la raíz del monorepo:

```bash
cd incalake-full-stack
npm install
npm run install:all
```

### 4. Configurar Base de Datos

```bash
# Ejecutar migraciones
cd backend
php artisan migrate
php artisan db:seed
```

### 5. Iniciar Todos los Servicios

#### Opción A: Con npm (Recomendado)
```bash
npm run dev
```

#### Opción B: Con Make
```bash
make dev
```

#### Opción C: Con Docker
```bash
docker-compose up -d
```

## 📋 Comandos Disponibles

### NPM Scripts
- `npm run dev` - Inicia todos los servicios
- `npm run build` - Construye frontend y admin
- `npm run test` - Ejecuta todos los tests
- `npm run install:all` - Instala todas las dependencias

### Make Commands
- `make help` - Ver todos los comandos disponibles
- `make setup` - Setup inicial completo
- `make dev` - Iniciar desarrollo
- `make clean` - Limpiar archivos temporales

### Docker Commands
- `docker-compose up` - Iniciar contenedores
- `docker-compose down` - Detener contenedores
- `docker-compose logs -f` - Ver logs

## 🌐 URLs de Acceso

- **API Backend**: http://localhost:8001
- **Frontend Tours**: http://localhost:3001
- **Admin Panel**: http://localhost:54112
- **phpMyAdmin** (Docker): http://localhost:8080
- **Mailhog** (Docker): http://localhost:8025

## 🎯 Git - Primer Commit

Una vez copiados los proyectos:

```bash
cd incalake-full-stack
git init
git add .
git commit -m "Initial commit: Monorepo structure with Laravel backend, Nuxt frontend and admin panel"
git branch -M main
git remote add origin https://github.com/tu-usuario/incalake-full-stack.git
git push -u origin main
```

## 📝 Notas Importantes

1. **Archivos .env**: No están incluidos por seguridad. Crear basándose en los `.env.example`
2. **node_modules y vendor**: Se excluyen del git automáticamente
3. **Storage Laravel**: Ejecutar `php artisan storage:link` después de copiar el backend
4. **Permisos**: En Linux/Mac, dar permisos a storage: `chmod -R 775 backend/storage`
5. **Cache**: Limpiar cache si hay problemas: `npm run clean` o `make clean`

## ✅ Verificación Final

Para verificar que todo está funcionando:

1. Acceder a http://localhost:8001 - Debe mostrar Laravel
2. Acceder a http://localhost:3001 - Debe mostrar el frontend de tours
3. Acceder a http://localhost:54112 - Debe mostrar el panel admin
4. Verificar API: http://localhost:8001/api/tours

## 🆘 Troubleshooting

Si encuentras problemas:

1. **Puerto en uso**: Cambiar puertos en los archivos `.env`
2. **Error de permisos**: Ejecutar como administrador en Windows
3. **IPC Error en Nuxt**: Verificar que `ssr: false` esté en `nuxt.config.ts`
4. **Base de datos**: Verificar credenciales en `.env` del backend
5. **Cache issues**: Ejecutar `make clean` y reiniciar

---

¡El monorepo está listo para usar! Solo necesitas copiar los proyectos a sus carpetas correspondientes.