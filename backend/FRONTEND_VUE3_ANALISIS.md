# Análisis Frontend Vue 3 - Mobile First
## Tour Display Application

**Fecha:** 29 de enero de 2026
**Objetivo:** Crear un frontend Vue 3 mobile-first para mostrar los tours creados mediante el Tour Wizard

---

## 1. Estado Actual del Proyecto

### 1.1 Stack Tecnológico Backend
- **Framework:** Laravel 12.45.0
- **PHP:** 8.2.12
- **Frontend Build:** Vite 5.4.21
- **CSS Framework:** Tailwind CSS 3.4.19
- **JavaScript Framework:** Alpine.js 3.15.4 (Admin panel)
- **Editor:** Tiptap 3.16.0
- **Calendario:** FullCalendar 6.1.20

### 1.2 Estado del Frontend Actual

**❌ NO hay Vue.js instalado actualmente**

El proyecto actual usa:
- **Livewire 3.7.3** para el panel administrativo (componentes reactivos server-side)
- **Alpine.js 3.15.4** para interactividad ligera del cliente
- **Vanilla JavaScript** para componentes específicos:
  - `tour-map.js` - Integración Google Maps
  - `availability-calendar.js` - Calendario de disponibilidad
  - `tiptap-standalone.js` - Editor de texto enriquecido

**Archivos JavaScript actuales:**
```
resources/js/
├── app.js              # Entry point
├── bootstrap.js        # Axios config
├── tour-map.js         # Google Maps integration
├── availability-calendar.js
└── tiptap-standalone.js
```

### 1.3 Configuración de Build Actual

**vite.config.js:**
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

---

## 2. API REST Disponible

### 2.1 Endpoints Públicos para Tours

✅ **GET** `/api/tours` - Listar tours
- **Query params:**
  - `status` - Filtrar por estado
  - `active` - Solo tours activos (boolean)
  - `service_type` - Tipo de servicio
  - `difficulty` - Nivel de dificultad
  - `city_id` - Filtrar por ciudad
  - `category_id` - Filtrar por categoría
  - `search` - Búsqueda en título y descripción
  - `min_price` / `max_price` - Rango de precios
  - `sort_by` - Campo de ordenamiento (default: created_at)
  - `sort_order` - Orden (asc/desc)
  - `per_page` - Resultados por página (default: 15)
  - `language` - Código de idioma (default: ES)

- **Respuesta:**
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 15,
    "to": 15,
    "total": 45
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

✅ **GET** `/api/tours/{id}` - Detalle de tour
- **Query params:**
  - `language` - Código de idioma (default: ES)

- **Respuesta:** Ver estructura completa en `TourDetailResource`

✅ **POST** `/api/tours/{id}/calculate-price` - Calcular precio
- **Body:**
```json
{
  "participants": [
    {
      "age_stage_id": 1,
      "quantity": 2
    }
  ],
  "coupon_code": "DESCUENTO10",
  "date": "2026-02-15"
}
```

✅ **POST** `/api/tours/validate-coupon` - Validar cupón
✅ **GET** `/api/tours/{id}/available-dates` - Fechas disponibles
- **Query params:**
  - `start_date` - Fecha inicio (default: hoy)
  - `end_date` - Fecha fin (default: +3 meses)

### 2.2 Rate Limiting
- Tours públicos: **60 requests/minuto**
- Admin tours: **30 requests/minuto** (requiere autenticación)

---

## 3. Estructura de Datos del Tour (API Response)

### 3.1 TourResource (Lista)
```json
{
  "id": 1,
  "code": "TOUR-2026-001",
  "title": "Tour Lago Titicaca - Islas Flotantes",
  "slug": "tour-lago-titicaca-islas-flotantes",
  "short_description": "Explora las increíbles...",
  "city": {
    "id": 1,
    "name": "Puno"
  },
  "service_type": "turistico",
  "difficulty": "facil",
  "status": "published",
  "active": true,
  "duration_days": 1,
  "duration_hours": 8,
  "capacity": 20,
  "cupos": 15,
  "departure_time": "08:00:00",
  "departure_period": "morning",
  "timezone": "America/Lima",
  "featured_image": "tours/featured/...",
  "thumbnail": "tours/thumbnails/...",
  "min_price": 45.00,
  "is_bookable": true,
  "categories": [...],
  "created_at": "2026-01-28T10:00:00.000000Z",
  "updated_at": "2026-01-29T15:30:00.000000Z"
}
```

### 3.2 TourDetailResource (Detalle Completo)

**Campos principales:**
- `id`, `code`
- `primary_language` - Idioma principal del tour
- `city` - Ciudad de origen
- `basic_info` - service_type, difficulty, target_audience, status, active, capacity, cupos
- `duration` - days, hours, departure_time, departure_period, timezone, booking_anticipation_hours
- `payment` - methods, data_requirement
- `seo` - index_status, follow_status

**Traducciones (array):**
```json
"translations": [
  {
    "language_id": 1,
    "language_code": "ES",
    "is_primary": true,
    "title": "Tour Lago Titicaca - Islas Flotantes",
    "slug": "tour-lago-titicaca-islas-flotantes",
    "meta_title": "Tour Lago Titicaca | Islas Flotantes Uros",
    "meta_description": "Descubre las islas...",
    "short_description": "Explora...",
    "long_description": "<p>HTML content...</p>",
    "itinerary": "<p>8:00 AM - Recojo...</p>",
    "what_includes": "<ul><li>Transporte</li>...</ul>",
    "what_not_includes": "<ul><li>Comidas</li>...</ul>",
    "recommendations": "<p>Llevar protector solar...</p>",
    "what_to_bring": "<ul><li>Cámara</li>...</ul>",
    "policies": "<p>Cancelación 24h...</p>",
    "cancellation_policy": "<p>Reembolso completo...</p>",
    "og_title": "Tour Lago Titicaca...",
    "og_description": "Descubre...",
    "twitter_title": "...",
    "twitter_description": "...",
    "ads_headline": "...",
    "ads_description": "...",
    "cta_text": "Reservar Ahora"
  }
]
```

**Precios (array):**
```json
"prices": [
  {
    "id": 1,
    "age_stage_id": 1,
    "age_stage_name": "Adulto",
    "nationality_id": null,
    "nationality_name": null,
    "amount": 45.00,
    "currency": "USD",
    "quantity": 1,
    "active": true
  }
]
```

**Multimedia:**
```json
"media": [
  {
    "id": 1,
    "language_id": 1,
    "url": "/storage/tours/gallery/...",
    "path": "tours/gallery/...",
    "alt_text": "Vista panorámica del Lago Titicaca",
    "title_text": "Lago Titicaca desde Puno",
    "order": 1
  }
]
```

**⚠️ IMPORTANTE:** El campo `description` (añadido recientemente) NO está en el API Resource actual.

**Puntos del Mapa:**
```json
"map_points": [
  {
    "id": 1,
    "name": "Plaza de Armas de Puno",
    "description": "Punto de partida del tour",
    "coordinates": "-15.8402,-70.0219",
    "type": "punto_reunion",
    "order": 1
  }
]
```

**Categorías, FAQs, Schema Markup** también disponibles.

**⚠️ IMPORTANTE:** El campo `youtube_url` (añadido recientemente) NO está en el API Resource actual.

---

## 4. Campos Faltantes en API Resources

### 4.1 TourMediaResource
**Falta agregar:**
- ✅ `description` - Campo recién agregado a la tabla `tour_media_gallery`

**Actualizar:**
```php
// app/Http/Resources/TourMediaResource.php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'language_id' => $this->language_id,
        'url' => \Storage::url($this->image_path),
        'path' => $this->image_path,
        'alt_text' => $this->alt_text,
        'title_text' => $this->title_text,
        'description' => $this->description, // ⬅️ AGREGAR
        'order' => $this->order,
    ];
}
```

### 4.2 TourDetailResource
**Falta agregar:**
- ✅ `youtube_url` - Campo recién agregado a la tabla `tours`

**Actualizar:**
```php
// app/Http/Resources/TourDetailResource.php
public function toArray(Request $request): array
{
    return [
        // ... existing fields ...

        'youtube_url' => $this->youtube_url, // ⬅️ AGREGAR (después de 'seo' o 'media')

        // ... rest of fields ...
    ];
}
```

---

## 5. Propuesta de Arquitectura Vue 3

### 5.1 Instalación de Dependencias

**Ejecutar:**
```bash
npm install vue@^3.4.0 vue-router@^4.2.0 pinia@^2.1.0 axios@^1.6.0
npm install -D @vitejs/plugin-vue
```

**Dependencias adicionales recomendadas:**
```bash
# UI Components
npm install @headlessui/vue@^1.7.0

# Date handling
npm install dayjs@^1.11.0

# Google Maps (si se usa en frontend)
npm install @fawmi/vue-google-maps@^0.9.0

# Image optimization/lightbox
npm install vue-easy-lightbox@^1.17.0

# HTML sanitization (para contenido del editor)
npm install dompurify@^3.0.0
```

### 5.2 Actualizar vite.config.js

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/vue-app.js' // ⬅️ Nueva entrada para Vue
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
            '@': '/resources/js',
        },
    },
});
```

### 5.3 Estructura de Directorios Propuesta

```
resources/js/
├── app.js                    # Admin/Livewire (existente)
├── bootstrap.js              # Axios config (existente)
├── vue-app.js               # ⬅️ NUEVO - Entry point Vue 3
│
├── vue/                      # ⬅️ NUEVO - Aplicación Vue
│   ├── main.js              # Vue initialization
│   ├── router.js            # Vue Router
│   ├── stores/              # Pinia stores
│   │   ├── tours.js         # Tour state management
│   │   ├── filters.js       # Filter state
│   │   └── cart.js          # Shopping cart (futuro)
│   │
│   ├── views/               # Páginas principales
│   │   ├── Home.vue         # Landing page
│   │   ├── TourList.vue     # Lista de tours (con filtros)
│   │   └── TourDetail.vue   # Detalle del tour
│   │
│   ├── components/          # Componentes reutilizables
│   │   ├── layout/
│   │   │   ├── Header.vue
│   │   │   ├── Footer.vue
│   │   │   └── MobileNav.vue
│   │   │
│   │   ├── tour/
│   │   │   ├── TourCard.vue           # Card para lista
│   │   │   ├── TourHero.vue           # Hero del detalle
│   │   │   ├── TourGallery.vue        # Galería de imágenes
│   │   │   ├── TourYouTubePlayer.vue  # Player de YouTube
│   │   │   ├── TourInfo.vue           # Info básica
│   │   │   ├── TourItinerary.vue      # Itinerario
│   │   │   ├── TourPricing.vue        # Tabla de precios
│   │   │   ├── TourMap.vue            # Mapa con puntos
│   │   │   ├── TourIncludes.vue       # Qué incluye/no incluye
│   │   │   ├── TourRecommendations.vue
│   │   │   ├── TourPolicies.vue
│   │   │   └── TourFAQ.vue
│   │   │
│   │   ├── filters/
│   │   │   ├── SearchBar.vue
│   │   │   ├── FilterSidebar.vue
│   │   │   ├── CategoryFilter.vue
│   │   │   ├── PriceRangeFilter.vue
│   │   │   └── DifficultyFilter.vue
│   │   │
│   │   └── common/
│   │       ├── Loading.vue
│   │       ├── ErrorMessage.vue
│   │       ├── Pagination.vue
│   │       └── Badge.vue
│   │
│   ├── composables/         # Vue 3 Composition API
│   │   ├── useTours.js      # API calls para tours
│   │   ├── useFilters.js    # Lógica de filtros
│   │   ├── usePricing.js    # Cálculo de precios
│   │   └── useResponsive.js # Detección mobile/desktop
│   │
│   └── utils/
│       ├── api.js           # Axios instance configurada
│       ├── formatters.js    # Formateo de fechas, moneda, etc.
│       ├── sanitize.js      # DOMPurify wrapper
│       └── constants.js     # Constantes globales
│
└── tour-map.js              # Existente (puede integrarse con Vue)
```

---

## 6. Rutas Propuestas (Vue Router)

```javascript
// resources/js/vue/router.js
import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  {
    path: '/',
    name: 'home',
    component: () => import('./views/Home.vue'),
    meta: { title: 'Inicio - Tours Puno' }
  },
  {
    path: '/tours',
    name: 'tours',
    component: () => import('./views/TourList.vue'),
    meta: { title: 'Nuestros Tours' }
  },
  {
    path: '/tours/:slug',
    name: 'tour-detail',
    component: () => import('./views/TourDetail.vue'),
    meta: { title: 'Tour' }
  },
  // Futuro
  {
    path: '/tours/:slug/booking',
    name: 'tour-booking',
    component: () => import('./views/TourBooking.vue'),
    meta: { title: 'Reservar Tour', requiresAuth: false }
  }
];

export default createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition;
    return { top: 0 };
  }
});
```

---

## 7. Mobile-First Design Strategy

### 7.1 Breakpoints (Tailwind CSS)

Ya disponibles en el proyecto:
```javascript
// tailwind.config.js (existente)
{
  screens: {
    'sm': '640px',   // Mobile landscape
    'md': '768px',   // Tablet
    'lg': '1024px',  // Desktop
    'xl': '1280px',  // Large desktop
    '2xl': '1536px'  // Extra large
  }
}
```

### 7.2 Componentes Mobile-First

**Principios:**
1. **Base design:** Mobile (< 640px)
2. **Progressive enhancement:** Agregar features para pantallas grandes
3. **Touch-first:** Botones mínimo 44x44px
4. **Performance:** Lazy loading de imágenes y componentes

**Ejemplo TourCard.vue:**
```vue
<template>
  <!-- Mobile: Stack vertical -->
  <!-- Desktop: Grid horizontal -->
  <div class="
    bg-white rounded-lg shadow-md overflow-hidden
    flex flex-col
    md:flex-row md:h-64
  ">
    <!-- Imagen -->
    <div class="
      w-full h-48
      md:w-1/3 md:h-full
    ">
      <img :src="tour.featured_image" :alt="tour.title" class="w-full h-full object-cover" />
    </div>

    <!-- Contenido -->
    <div class="p-4 flex-1">
      <!-- Mobile: Font más grande para legibilidad -->
      <h3 class="text-lg md:text-xl font-bold">{{ tour.title }}</h3>
      <p class="text-sm md:text-base text-gray-600 mt-2">{{ tour.short_description }}</p>

      <!-- Mobile: Stack badges -->
      <div class="flex flex-wrap gap-2 mt-3">
        <Badge>{{ tour.difficulty }}</Badge>
        <Badge>{{ tour.duration_days }}D / {{ tour.duration_hours }}H</Badge>
      </div>

      <!-- Mobile: Full width button -->
      <div class="mt-4 flex items-center justify-between">
        <span class="text-xl font-bold text-indigo-600">
          ${{ tour.min_price }}
        </span>
        <button class="
          bg-indigo-600 text-white px-4 py-2 rounded-lg
          w-full md:w-auto
          touch-manipulation
        ">
          Ver más
        </button>
      </div>
    </div>
  </div>
</template>
```

### 7.3 Navegación Mobile

**Header.vue:**
- Mobile: Hamburger menu (overlay full-screen)
- Desktop: Horizontal nav bar

**Filtros:**
- Mobile: Bottom sheet / Modal
- Desktop: Sidebar fijo

---

## 8. Integración con Backend Laravel

### 8.1 Blade Template Principal

**Crear:** `resources/views/tours/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Tours Puno') }}</title>

    @vite(['resources/css/app.css', 'resources/js/vue-app.js'])
</head>
<body class="antialiased">
    <div id="app"></div>
</body>
</html>
```

### 8.2 Ruta Web

**Agregar en:** `routes/web.php`

```php
// Frontend público (Vue SPA)
Route::get('/{any}', function () {
    return view('tours.app');
})->where('any', '^(?!admin|api|storage|livewire).*$');
```

**Explicación:** Todas las rutas excepto `/admin`, `/api`, `/storage`, `/livewire` se manejan por Vue Router.

---

## 9. Pinia Store Example (State Management)

**resources/js/vue/stores/tours.js:**

```javascript
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../utils/api';

export const useTourStore = defineStore('tours', () => {
  // State
  const tours = ref([]);
  const currentTour = ref(null);
  const loading = ref(false);
  const error = ref(null);
  const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 15,
    total: 0
  });

  // Getters
  const activeTours = computed(() =>
    tours.value.filter(t => t.active && t.is_bookable)
  );

  // Actions
  async function fetchTours(filters = {}) {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.get('/tours', { params: filters });
      tours.value = response.data.data;
      pagination.value = response.data.meta;
    } catch (err) {
      error.value = err.message;
      console.error('Error fetching tours:', err);
    } finally {
      loading.value = false;
    }
  }

  async function fetchTourById(id) {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.get(`/tours/${id}`);
      currentTour.value = response.data.data;
      return response.data.data;
    } catch (err) {
      error.value = err.message;
      console.error('Error fetching tour:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function calculatePrice(tourId, participants, couponCode = null, date = null) {
    try {
      const response = await api.post(`/tours/${tourId}/calculate-price`, {
        participants,
        coupon_code: couponCode,
        date
      });
      return response.data.data;
    } catch (err) {
      console.error('Error calculating price:', err);
      throw err;
    }
  }

  return {
    // State
    tours,
    currentTour,
    loading,
    error,
    pagination,

    // Getters
    activeTours,

    // Actions
    fetchTours,
    fetchTourById,
    calculatePrice
  };
});
```

---

## 10. Composable Example (Reusable Logic)

**resources/js/vue/composables/useTours.js:**

```javascript
import { ref, computed } from 'vue';
import { useTourStore } from '../stores/tours';
import { storeToRefs } from 'pinia';

export function useTours() {
  const tourStore = useTourStore();
  const { tours, loading, error, pagination } = storeToRefs(tourStore);

  const filters = ref({
    search: '',
    city_id: null,
    category_id: null,
    difficulty: null,
    service_type: null,
    min_price: null,
    max_price: null,
    sort_by: 'created_at',
    sort_order: 'desc',
    per_page: 15,
    page: 1
  });

  const hasActiveFilters = computed(() => {
    return filters.value.search ||
           filters.value.city_id ||
           filters.value.category_id ||
           filters.value.difficulty ||
           filters.value.service_type ||
           filters.value.min_price ||
           filters.value.max_price;
  });

  async function loadTours() {
    await tourStore.fetchTours(filters.value);
  }

  function clearFilters() {
    filters.value = {
      search: '',
      city_id: null,
      category_id: null,
      difficulty: null,
      service_type: null,
      min_price: null,
      max_price: null,
      sort_by: 'created_at',
      sort_order: 'desc',
      per_page: 15,
      page: 1
    };
    loadTours();
  }

  function changePage(page) {
    filters.value.page = page;
    loadTours();
  }

  return {
    tours,
    loading,
    error,
    pagination,
    filters,
    hasActiveFilters,
    loadTours,
    clearFilters,
    changePage
  };
}
```

---

## 11. Próximos Pasos (Implementación)

### Fase 1: Setup Inicial
1. ✅ Actualizar `TourMediaResource` para incluir `description`
2. ✅ Actualizar `TourDetailResource` para incluir `youtube_url`
3. ⬜ Instalar dependencias Vue 3 + Router + Pinia
4. ⬜ Configurar Vite para Vue
5. ⬜ Crear estructura base de directorios

### Fase 2: Componentes Base
1. ⬜ Layout (Header, Footer, MobileNav)
2. ⬜ TourCard component
3. ⬜ Loading & Error states
4. ⬜ Pagination component

### Fase 3: Páginas Principales
1. ⬜ Home page (landing)
2. ⬜ TourList page con filtros
3. ⬜ TourDetail page completa

### Fase 4: Features Avanzadas
1. ⬜ Galería de imágenes con lightbox
2. ⬜ YouTube video player
3. ⬜ Google Maps integration
4. ⬜ Price calculator
5. ⬜ Booking form (futuro)

### Fase 5: Optimización
1. ⬜ Lazy loading de componentes
2. ⬜ Image optimization
3. ⬜ SEO meta tags dinámicos
4. ⬜ PWA (Progressive Web App) capabilities

---

## 12. Consideraciones Importantes

### 12.1 SEO
- Vue Router en modo `history` requiere configuración de servidor
- Pre-render páginas estáticas con `vite-plugin-ssr` o SSR con Inertia.js
- Meta tags dinámicos con `vue-meta` o `@vueuse/head`

### 12.2 Performance
- Code splitting por ruta
- Lazy loading de imágenes (`loading="lazy"`)
- Debounce en búsqueda/filtros
- Cache de API responses con Pinia

### 12.3 Accesibilidad
- ARIA labels en componentes interactivos
- Keyboard navigation
- Screen reader friendly
- Color contrast mínimo AA (WCAG)

### 12.4 Multiidioma
- Detectar idioma del navegador
- Selector de idioma en header
- Llamar API con parámetro `language`
- i18n con `vue-i18n` para UI strings

---

## 13. Alternativas a Considerar

### 13.1 Inertia.js (Híbrido Laravel + Vue)
**Ventajas:**
- Más integrado con Laravel
- SSR automático
- Routing compartido
- Mejor SEO out-of-the-box

**Desventajas:**
- Menos flexible que SPA pura
- Requiere más cambios en backend

### 13.2 Nuxt 3 (Framework Vue SSR)
**Ventajas:**
- SSR/SSG automático
- File-based routing
- Excellent SEO
- Optimizaciones automáticas

**Desventajas:**
- Proyecto separado del backend
- Requiere API como única comunicación
- Mayor complejidad de deployment

---

## Conclusión

El proyecto actualmente NO tiene Vue instalado. Para implementar un frontend Vue 3 mobile-first:

1. **Opción recomendada:** SPA Vue 3 + Router + Pinia dentro de Laravel
   - Menor complejidad
   - Aprovecha assets existentes (Tailwind, Vite)
   - API REST ya está lista

2. **Primeros pasos críticos:**
   - Actualizar API Resources (description, youtube_url)
   - Instalar dependencias Vue
   - Crear estructura base
   - Implementar componentes mobile-first

3. **Timeline estimado:**
   - Setup: 1-2 días
   - Componentes base: 2-3 días
   - Páginas principales: 3-5 días
   - Features avanzadas: 5-7 días
   - **Total:** 2-3 semanas para MVP funcional

¿Deseas proceder con la instalación de Vue 3 y la creación de los componentes base?
