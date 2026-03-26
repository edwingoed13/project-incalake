# 🏔️ Incalake Full Stack - Tour Management System

Sistema completo de gestión de tours y experiencias turísticas con arquitectura moderna y multiidioma.

## 🚀 Stack Tecnológico

### Backend
- **Laravel 12** - API RESTful principal
- PHP 8.2+
- MySQL 8.0
- Redis para caché

### Frontend
- **Nuxt 4.4.2** - Aplicación de tours (cliente)
- **Vue 3** - Framework reactivo
- **Tailwind CSS 3** - Estilos utility-first
- **Pinia** - Gestión de estado

### Admin Panel
- **Nuxt 4.4.2** - Panel administrativo
- **Vue 3 Composition API**
- **Chart.js** - Visualización de datos

## 📁 Estructura del Proyecto

```
incalake-full-stack/
├── backend/                    # Laravel API Backend
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   └── ...
├── frontend/                   # Nuxt Tour Frontend
│   ├── app/
│   ├── components/
│   ├── pages/
│   ├── stores/
│   └── ...
├── admin/                      # Nuxt Admin Panel
│   ├── app/
│   ├── components/
│   ├── pages/
│   ├── stores/
│   └── ...
├── docker/                     # Configuración Docker
│   ├── nginx/
│   ├── php/
│   └── mysql/
├── docs/                       # Documentación
├── scripts/                    # Scripts de utilidad
├── docker-compose.yml
├── Makefile
└── README.md
```

## 🛠️ Requisitos Previos

- Node.js 18+ y npm 9+
- PHP 8.2+ y Composer 2+
- MySQL 8.0+
- Redis (opcional)
- Docker y Docker Compose (opcional)

## 🔧 Instalación

### Opción 1: Instalación Manual

#### 1. Clonar el repositorio
```bash
git clone https://github.com/tu-usuario/incalake-full-stack.git
cd incalake-full-stack
```

#### 2. Configurar Backend (Laravel)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

#### 3. Configurar Frontend (Nuxt)
```bash
cd ../frontend
npm install
cp .env.example .env
```

#### 4. Configurar Admin Panel
```bash
cd ../admin
npm install
cp .env.example .env
```

### Opción 2: Instalación con Docker

```bash
# Construir y levantar todos los servicios
docker-compose up -d

# Ejecutar migraciones
docker-compose exec backend php artisan migrate --seed
```

## 🚀 Ejecución

### Desarrollo Local

#### Con scripts npm (desde la raíz)
```bash
# Instalar dependencias de todos los proyectos
npm run install:all

# Iniciar todos los servicios
npm run dev

# O iniciar servicios individualmente
npm run dev:backend
npm run dev:frontend
npm run dev:admin
```

#### Manual
```bash
# Terminal 1 - Backend
cd backend && php artisan serve --port=8001

# Terminal 2 - Frontend
cd frontend && npm run dev -- --port 3001

# Terminal 3 - Admin
cd admin && npm run dev -- --port 54112
```

### Con Docker
```bash
docker-compose up
```

## 🌐 URLs de Acceso

- **API Backend**: http://localhost:8001
- **Frontend Tours**: http://localhost:3001
- **Admin Panel**: http://localhost:54112
- **API Documentation**: http://localhost:8001/api/documentation

## 🔑 Variables de Entorno

### Backend (.env)
```env
APP_URL=http://localhost:8001
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=incalake_tours
DB_USERNAME=root
DB_PASSWORD=

# AI Translation
OPENAI_API_KEY=your-key
ANTHROPIC_API_KEY=your-key
GEMINI_API_KEY=your-key
```

### Frontend & Admin (.env)
```env
NUXT_PUBLIC_API_BASE=http://localhost:8001/api
NUXT_PUBLIC_STORAGE_BASE=http://localhost:8001/storage
NUXT_PUBLIC_GOOGLE_MAPS_KEY=your-key
```

## 🏗️ Características Principales

### Sistema de Tours
- ✅ Gestión completa de tours y experiencias
- ✅ Sistema de reservas y pagos
- ✅ Calendario de disponibilidad
- ✅ Gestión de precios dinámicos
- ✅ Sistema de ofertas y descuentos

### Multiidioma
- ✅ Soporte para ES, EN, PT, FR, IT, DE
- ✅ Traducción automática con IA (OpenAI, Anthropic, Gemini)
- ✅ URLs amigables por idioma
- ✅ Detección automática del idioma del navegador

### Panel Administrativo
- ✅ Dashboard con métricas en tiempo real
- ✅ Gestión de productos y categorías
- ✅ Sistema de usuarios y permisos
- ✅ Reportes y analytics
- ✅ Configuración de IA para traducciones

### SEO & Performance
- ✅ SSR/SSG con Nuxt
- ✅ Lazy loading de imágenes
- ✅ Optimización de assets
- ✅ Sitemap automático
- ✅ Schema.org markup

## 📝 Scripts Disponibles

```json
{
  "scripts": {
    "dev": "concurrently \"npm:dev:*\"",
    "dev:backend": "cd backend && php artisan serve --port=8001",
    "dev:frontend": "cd frontend && npm run dev",
    "dev:admin": "cd admin && npm run dev",
    "build": "npm run build:frontend && npm run build:admin",
    "build:frontend": "cd frontend && npm run build",
    "build:admin": "cd admin && npm run build",
    "test": "npm run test:backend && npm run test:frontend",
    "lint": "npm run lint:frontend && npm run lint:admin",
    "install:all": "npm install && cd backend && composer install && cd ../frontend && npm install && cd ../admin && npm install"
  }
}
```

## 🧪 Testing

```bash
# Backend tests
cd backend && php artisan test

# Frontend tests
cd frontend && npm run test

# E2E tests
npm run test:e2e
```

## 📦 Deployment

### Production Build

```bash
# Build frontend
cd frontend && npm run build

# Build admin
cd admin && npm run build

# Optimize backend
cd backend
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Docker Production

```bash
docker-compose -f docker-compose.prod.yml up -d
```

## 🤝 Contribuir

1. Fork el proyecto
2. Crea tu Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 Licencia

Distribuido bajo la Licencia MIT. Ver `LICENSE` para más información.

## 👥 Equipo

- **Backend Development** - Laravel API
- **Frontend Development** - Nuxt/Vue.js
- **UI/UX Design** - Tailwind CSS
- **DevOps** - Docker, CI/CD

## 📞 Soporte

Para soporte, email a support@incalake.com o abre un issue en GitHub.

---

**Desarrollado con ❤️ para la industria turística**