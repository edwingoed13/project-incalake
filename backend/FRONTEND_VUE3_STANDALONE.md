# Frontend Vue 3 - Proyecto Standalone (Mobile First)
## Arquitectura Separada del CMS Laravel

**Fecha:** 29 de enero de 2026
**Objetivo:** Crear un frontend Vue 3 independiente que se conecte a la base de datos Laravel

---

## 1. Entendiendo la Arquitectura Actual

### 1.1 Sistema Viejo (apps-incalake/web)

**Stack:**
- **Framework:** CodeIgniter 2.x (PHP)
- **Conexión:** Directa a MySQL (`inc0910d_cms_incalake`)
- **Base de datos:** `localhost` / `root` / sin password
- **Frontend:** jQuery, JavaScript vanilla
- **Ubicación:** `public_html/apps-incalake/web/`

**Estructura:**
```
public_html/apps-incalake/web/
├── index.php              # Entry point CodeIgniter
├── application/           # Lógica del negocio
│   ├── config/
│   │   └── database.php   # Conexión DB directa
│   ├── controllers/       # Controladores CI
│   ├── models/            # Modelos CI (Query directos)
│   └── views/             # Vistas PHP
├── assets/                # CSS, JS, imágenes
│   └── resources/
│       └── js/            # JavaScript del frontend
└── system/                # Core CodeIgniter
```

**Conexión a DB:**
```php
// CodeIgniter - Conexión directa a MySQL
$db['default']['hostname'] = 'localhost';
$db['default']['username'] = 'root';
$db['default']['password'] = '';
$db['default']['database'] = 'inc0910d_cms_incalake';
$db['default']['dbdriver'] = 'mysqli';
```

---

## 2. Propuesta de Arquitectura para Nuevo Frontend

### 2.1 Estructura General

```
public_html/
├── apps-incalake/
│   └── web/                    # ⬅️ Sistema viejo (CodeIgniter)
│
├── laravel-incalake-v12/       # ⬅️ CMS Laravel (Admin)
│   ├── app/
│   ├── database/
│   │   └── database.sqlite     # Laravel usa su propia DB
│   └── routes/
│       └── api.php             # ✅ API REST disponible
│
└── tour-frontend-vue3/         # ⬅️ NUEVO - Frontend Vue 3 Standalone
    ├── index.html
    ├── package.json
    ├── vite.config.js
    ├── public/
    │   └── assets/
    ├── src/
    │   ├── main.js
    │   ├── App.vue
    │   ├── router/
    │   ├── stores/
    │   ├── components/
    │   ├── views/
    │   └── services/         # ⬅️ Capa de comunicación con backend
    │       ├── api.js        # API REST (Laravel)
    │       └── database.js   # ⬅️ Conexión directa DB (si se requiere)
    └── .env
```

---

## 3. Dos Opciones de Conexión al Backend

### Opción A: API REST (Recomendada) ✅

**Arquitectura:**
```
Vue 3 Frontend → HTTP/API → Laravel CMS → MySQL Database
```

**Ventajas:**
- ✅ Separación clara de responsabilidades
- ✅ Laravel maneja autenticación, validación, seguridad
- ✅ API ya está implementada (`/api/tours`)
- ✅ Escalable (frontend puede estar en otro servidor)
- ✅ Cacheable (Varnish, CDN)
- ✅ Mejor seguridad (no expone credenciales de DB)

**Desventajas:**
- ❌ Requiere que Laravel esté corriendo
- ❌ Dependencia entre proyectos

**Implementación:**
```javascript
// src/services/api.js
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://tudominio.com/laravel-incalake-v12/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

export const tourService = {
  // Listar tours
  async getAll(filters = {}) {
    const response = await api.get('/tours', { params: filters });
    return response.data;
  },

  // Obtener tour por ID
  async getById(id, language = 'ES') {
    const response = await api.get(`/tours/${id}`, {
      params: { language }
    });
    return response.data;
  },

  // Calcular precio
  async calculatePrice(tourId, participants, couponCode = null, date = null) {
    const response = await api.post(`/tours/${tourId}/calculate-price`, {
      participants,
      coupon_code: couponCode,
      date
    });
    return response.data;
  }
};

export default api;
```

---

### Opción B: Conexión Directa a MySQL (Como sistema viejo)

**Arquitectura:**
```
Vue 3 Frontend → PHP API Bridge → MySQL Database
```

**Ventajas:**
- ✅ Independiente de Laravel
- ✅ Más rápido (sin middleware Laravel)
- ✅ Similar al sistema viejo

**Desventajas:**
- ❌ Duplicación de lógica de negocio
- ❌ Menos seguro (credenciales expuestas)
- ❌ Sin validaciones Laravel
- ❌ Mantenimiento duplicado

**Implementación:**

**1. Crear API Bridge en PHP puro:**
```
tour-frontend-vue3/
├── api/                    # ⬅️ API PHP Bridge
│   ├── config/
│   │   └── database.php
│   ├── controllers/
│   │   └── TourController.php
│   ├── models/
│   │   └── Tour.php
│   └── index.php           # Router
└── src/                    # Vue 3 app
```

**2. Archivo de conexión DB:**
```php
// api/config/database.php
<?php
class Database {
    private $host = "localhost";
    private $db_name = "inc0910d_cms_incalake"; // ⬅️ Misma DB de Laravel
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            echo "Error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
```

**3. API Endpoint:**
```php
// api/index.php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'config/database.php';
require_once 'controllers/TourController.php';

$database = new Database();
$db = $database->getConnection();

$tourController = new TourController($db);

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Routing simple
if (preg_match('/\/api\/tours\/(\d+)/', $request_uri, $matches)) {
    $tourId = $matches[1];

    if ($method === 'GET') {
        echo json_encode($tourController->getById($tourId));
    }
} elseif (preg_match('/\/api\/tours/', $request_uri)) {
    if ($method === 'GET') {
        echo json_encode($tourController->getAll($_GET));
    }
}
```

**4. Controlador:**
```php
// api/controllers/TourController.php
<?php
class TourController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($filters = []) {
        $query = "SELECT t.*,
                         tt.h1_title as title,
                         tt.slug,
                         tt.short_description,
                         c.name as city_name
                  FROM tours t
                  LEFT JOIN tour_translations tt ON t.id = tt.tour_id
                    AND tt.language_id = :language_id
                  LEFT JOIN cities c ON t.city_id = c.id
                  WHERE t.active = 1";

        // Filtros dinámicos
        $params = ['language_id' => $filters['language_id'] ?? 1];

        if (!empty($filters['search'])) {
            $query .= " AND tt.h1_title LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['city_id'])) {
            $query .= " AND t.city_id = :city_id";
            $params['city_id'] = $filters['city_id'];
        }

        $query .= " ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";

        $params['limit'] = (int)($filters['per_page'] ?? 15);
        $params['offset'] = (int)(($filters['page'] ?? 1) - 1) * $params['limit'];

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            if (in_array($key, ['limit', 'offset'])) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }

        $stmt->execute();

        return [
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    public function getById($id) {
        $query = "SELECT t.*,
                         tt.h1_title,
                         tt.slug,
                         tt.short_description,
                         tt.long_description,
                         tt.itinerary,
                         tt.what_includes,
                         tt.what_not_includes,
                         t.youtube_url
                  FROM tours t
                  LEFT JOIN tour_translations tt ON t.id = tt.tour_id
                  WHERE t.id = :id AND t.active = 1
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $tour = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tour) {
            return ['success' => false, 'message' => 'Tour no encontrado'];
        }

        // Obtener galería de imágenes
        $query_images = "SELECT id, image_path, alt_text, title_text, description, `order`
                         FROM tour_media_gallery
                         WHERE tour_id = :tour_id
                         ORDER BY `order` ASC";
        $stmt_images = $this->conn->prepare($query_images);
        $stmt_images->bindParam(':tour_id', $id, PDO::PARAM_INT);
        $stmt_images->execute();
        $tour['images'] = $stmt_images->fetchAll(PDO::FETCH_ASSOC);

        // Obtener precios
        $query_prices = "SELECT pd.*,
                                a.name as age_stage_name,
                                n.name as nationality_name
                         FROM price_details pd
                         LEFT JOIN age_stages a ON pd.age_stage_id = a.id
                         LEFT JOIN nationalities n ON pd.nationality_id = n.id
                         WHERE pd.tour_id = :tour_id AND pd.active = 1";
        $stmt_prices = $this->conn->prepare($query_prices);
        $stmt_prices->bindParam(':tour_id', $id, PDO::PARAM_INT);
        $stmt_prices->execute();
        $tour['prices'] = $stmt_prices->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'data' => $tour];
    }
}
```

**5. Frontend Vue consume API Bridge:**
```javascript
// src/services/database.js
import axios from 'axios';

const dbApi = axios.create({
  baseURL: 'https://tudominio.com/tour-frontend-vue3/api',
  timeout: 10000
});

export const tourService = {
  async getAll(filters = {}) {
    const response = await dbApi.get('/tours', { params: filters });
    return response.data;
  },

  async getById(id) {
    const response = await dbApi.get(`/tours/${id}`);
    return response.data;
  }
};
```

---

## 4. Comparación de Opciones

| Aspecto | Opción A: API REST Laravel | Opción B: PHP Bridge Directo |
|---------|----------------------------|------------------------------|
| **Seguridad** | ✅ Alta (Laravel Sanctum) | ⚠️ Media (manual) |
| **Mantenimiento** | ✅ Centralizado en Laravel | ❌ Duplicado |
| **Performance** | ⚠️ Medio (Laravel overhead) | ✅ Rápido (PDO directo) |
| **Independencia** | ❌ Depende de Laravel | ✅ Totalmente independiente |
| **Escalabilidad** | ✅ Excelente | ⚠️ Media |
| **Validaciones** | ✅ Reutiliza Laravel | ❌ Debe reimplementar |
| **Complejidad** | ✅ Baja (API lista) | ⚠️ Media (crear API) |
| **Caching** | ✅ Built-in Laravel | ❌ Manual |

---

## 5. Estructura del Proyecto Vue 3 Standalone

### 5.1 Inicialización del Proyecto

```bash
# Crear proyecto Vue 3 con Vite
cd public_html
npm create vue@latest tour-frontend-vue3

# Opciones:
# ✅ TypeScript: No
# ✅ JSX Support: No
# ✅ Vue Router: Yes
# ✅ Pinia: Yes
# ✅ Vitest: No
# ✅ ESLint: Yes
# ✅ Prettier: Yes

cd tour-frontend-vue3
npm install

# Instalar dependencias adicionales
npm install axios dayjs dompurify
npm install @headlessui/vue @heroicons/vue
npm install vue-easy-lightbox
npm install @fawmi/vue-google-maps
```

### 5.2 Estructura de Archivos

```
tour-frontend-vue3/
├── public/
│   ├── favicon.ico
│   └── assets/
│       └── images/
│
├── src/
│   ├── main.js                 # Entry point
│   ├── App.vue                 # Root component
│   │
│   ├── router/
│   │   └── index.js            # Vue Router config
│   │
│   ├── stores/
│   │   ├── tours.js            # Pinia store - Tours
│   │   ├── filters.js          # Filtros de búsqueda
│   │   └── app.js              # Estado global (idioma, etc)
│   │
│   ├── services/
│   │   ├── api.js              # ⬅️ Si usa Opción A (Laravel API)
│   │   ├── database.js         # ⬅️ Si usa Opción B (PHP Bridge)
│   │   └── tourService.js      # Abstracción agnóstica
│   │
│   ├── views/                  # Páginas
│   │   ├── HomePage.vue
│   │   ├── TourListPage.vue
│   │   ├── TourDetailPage.vue
│   │   └── NotFoundPage.vue
│   │
│   ├── components/
│   │   ├── layout/
│   │   │   ├── AppHeader.vue
│   │   │   ├── AppFooter.vue
│   │   │   ├── MobileMenu.vue
│   │   │   └── LanguageSwitch.vue
│   │   │
│   │   ├── tour/
│   │   │   ├── TourCard.vue
│   │   │   ├── TourHero.vue
│   │   │   ├── TourGallery.vue
│   │   │   ├── TourYouTube.vue
│   │   │   ├── TourInfo.vue
│   │   │   ├── TourItinerary.vue
│   │   │   ├── TourPricing.vue
│   │   │   ├── TourMap.vue
│   │   │   └── TourBookingForm.vue
│   │   │
│   │   ├── filters/
│   │   │   ├── SearchBar.vue
│   │   │   ├── FilterPanel.vue
│   │   │   └── PriceSlider.vue
│   │   │
│   │   └── common/
│   │       ├── LoadingSpinner.vue
│   │       ├── ErrorAlert.vue
│   │       ├── Pagination.vue
│   │       └── Badge.vue
│   │
│   ├── composables/
│   │   ├── useTours.js
│   │   ├── useFilters.js
│   │   ├── usePricing.js
│   │   └── useResponsive.js
│   │
│   ├── utils/
│   │   ├── formatters.js       # Formateo de fechas, moneda
│   │   ├── sanitize.js         # DOMPurify wrapper
│   │   └── constants.js
│   │
│   ├── assets/
│   │   ├── css/
│   │   │   └── main.css        # Tailwind + custom
│   │   └── images/
│   │
│   └── styles/
│       └── tailwind.css
│
├── api/                        # ⬅️ Solo si usa Opción B
│   ├── config/
│   │   └── database.php
│   ├── controllers/
│   │   └── TourController.php
│   └── index.php
│
├── .env                        # Variables de entorno
├── .env.production
├── index.html
├── package.json
├── vite.config.js
├── tailwind.config.js
└── README.md
```

### 5.3 Variables de Entorno

**Opción A (Laravel API):**
```bash
# .env
VITE_API_BASE_URL=https://tudominio.com/laravel-incalake-v12/api
VITE_STORAGE_BASE_URL=https://tudominio.com/laravel-incalake-v12/storage
VITE_APP_NAME=Tours Puno
VITE_DEFAULT_LANGUAGE=ES
```

**Opción B (PHP Bridge):**
```bash
# .env
VITE_API_BASE_URL=https://tudominio.com/tour-frontend-vue3/api
VITE_STORAGE_BASE_URL=https://tudominio.com/tour-frontend-vue3/storage
VITE_APP_NAME=Tours Puno
VITE_DEFAULT_LANGUAGE=ES
```

### 5.4 Configuración Vite

```javascript
// vite.config.js
import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  server: {
    port: 3000,
    proxy: {
      // Solo si API está en el mismo servidor
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  },
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    sourcemap: false,
    minify: 'terser',
    rollupOptions: {
      output: {
        manualChunks: {
          'vue-vendor': ['vue', 'vue-router', 'pinia'],
          'ui-vendor': ['@headlessui/vue', '@heroicons/vue']
        }
      }
    }
  }
})
```

### 5.5 Servicio Agnóstico (Abstracción)

```javascript
// src/services/tourService.js
// Abstracción que funciona con ambas opciones

const API_TYPE = import.meta.env.VITE_API_TYPE || 'laravel'; // 'laravel' o 'bridge'

// Importar el servicio correspondiente
const apiService = API_TYPE === 'laravel'
  ? await import('./api.js')
  : await import('./database.js');

// Exportar interfaz unificada
export const tourService = {
  async getAllTours(filters = {}) {
    return await apiService.tourService.getAll(filters);
  },

  async getTourById(id, language = 'ES') {
    return await apiService.tourService.getById(id, language);
  },

  async calculatePrice(tourId, participants, couponCode, date) {
    return await apiService.tourService.calculatePrice(
      tourId, participants, couponCode, date
    );
  }
};
```

---

## 6. Integración con la Misma Base de Datos

### 6.1 Esquema de Base de Datos

**Laravel usa:**
- Base de datos: `inc0910d_cms_incalake` (MySQL) o `database.sqlite` (SQLite)
- Tablas con prefijo: ninguno
- Tablas principales:
  - `tours`
  - `tour_translations`
  - `tour_media_gallery` ← Con campos nuevos: `description`, `youtube_url`
  - `price_details`
  - `age_stages`
  - `nationalities`
  - `cities`
  - `categories_new`

**Frontend accede:**
- Misma base de datos: `inc0910d_cms_incalake`
- Solo lectura (SELECT)
- Queries optimizados con JOINs

### 6.2 Consideraciones de Seguridad

**Si usa Opción B (Conexión directa):**

1. **Usuario de solo lectura:**
```sql
-- Crear usuario MySQL de solo lectura
CREATE USER 'frontend_readonly'@'localhost' IDENTIFIED BY 'password_seguro';

-- Otorgar permisos solo de SELECT
GRANT SELECT ON inc0910d_cms_incalake.tours TO 'frontend_readonly'@'localhost';
GRANT SELECT ON inc0910d_cms_incalake.tour_translations TO 'frontend_readonly'@'localhost';
GRANT SELECT ON inc0910d_cms_incalake.tour_media_gallery TO 'frontend_readonly'@'localhost';
GRANT SELECT ON inc0910d_cms_incalake.price_details TO 'frontend_readonly'@'localhost';
GRANT SELECT ON inc0910d_cms_incalake.age_stages TO 'frontend_readonly'@'localhost';
GRANT SELECT ON inc0910d_cms_incalake.nationalities TO 'frontend_readonly'@'localhost';
GRANT SELECT ON inc0910d_cms_incalake.cities TO 'frontend_readonly'@'localhost';
GRANT SELECT ON inc0910d_cms_incalake.categories_new TO 'frontend_readonly'@'localhost';

FLUSH PRIVILEGES;
```

2. **Actualizar conexión:**
```php
// api/config/database.php
private $username = "frontend_readonly";  // ⬅️ Usuario de solo lectura
private $password = "password_seguro";
```

---

## 7. Deployment

### 7.1 Build de Producción

```bash
# En directorio tour-frontend-vue3
npm run build

# Output en: dist/
```

### 7.2 Estructura en Servidor

```
public_html/
├── tour-frontend-vue3/
│   ├── index.html              # ⬅️ Desde dist/
│   ├── assets/
│   │   ├── index-abc123.js     # ⬅️ Desde dist/assets/
│   │   ├── index-def456.css
│   │   └── images/
│   ├── api/                    # ⬅️ Solo si usa Opción B
│   │   └── index.php
│   └── .env.production
│
├── laravel-incalake-v12/       # CMS Laravel
│   └── public/
│       └── api/                # ⬅️ Si usa Opción A
└── apps-incalake/
    └── web/                    # Sistema viejo
```

### 7.3 Configuración Apache

```apache
# .htaccess en tour-frontend-vue3/
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /tour-frontend-vue3/

  # Redirigir a index.html (Vue Router SPA)
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /tour-frontend-vue3/index.html [L]
</IfModule>
```

---

## 8. Recomendación Final

### ⭐ Opción Recomendada: **Híbrida**

1. **Fase 1:** Usar **Opción A (API REST Laravel)**
   - Más rápido de implementar (API ya existe)
   - Mejor seguridad
   - Reutiliza validaciones de Laravel

2. **Fase 2 (Futuro):** Si se requiere más performance
   - Agregar cache en Laravel (Redis)
   - O implementar **Opción B** solo para endpoints críticos

### Arquitectura Híbrida:

```
Vue 3 Frontend
  ├── Operaciones de lectura (95%) → Laravel API REST
  └── Cálculos complejos (5%) → PHP Bridge directo (futuro)
```

---

## 9. Próximos Pasos

### Paso 1: Crear Proyecto Vue 3
```bash
cd c:\xampp\htdocs\web\public_html
npm create vue@latest tour-frontend-vue3
cd tour-frontend-vue3
npm install
```

### Paso 2: Configurar Servicio API
```bash
# Crear archivos de servicio
touch src/services/api.js
touch .env
```

### Paso 3: Actualizar API Resources de Laravel
- Agregar `description` a `TourMediaResource`
- Agregar `youtube_url` a `TourDetailResource`

### Paso 4: Crear Componentes Base
- Layout (Header, Footer)
- TourCard
- TourDetail

### Paso 5: Testing Local
```bash
npm run dev
# Frontend: http://localhost:3000
# Laravel API: http://localhost:8000/api
```

---

## ¿Qué opción prefieres?

**A)** API REST Laravel (recomendada, más rápida de implementar)
**B)** PHP Bridge directo a MySQL (más independiente)
**C)** Híbrida (empezar con A, migrar a B si se necesita)

¿Procedo con la implementación de la opción que prefieras?
