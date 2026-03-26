# Estructura de Base de Datos Antigua - Referencia

## Base de Datos: `incalake_new`

Esta documentación describe la estructura esperada de la base de datos antigua para que el comando de migración funcione correctamente.

## Tablas y Campos Esperados

### 1. `categories`

Tabla de categorías de tours.

```sql
CREATE TABLE categories (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),              -- Nombre genérico o en inglés
    name_en VARCHAR(255),            -- Nombre en inglés (preferido)
    name_es VARCHAR(255),            -- Nombre en español (preferido)
    description TEXT,                -- Descripción genérica
    description_en TEXT,             -- Descripción en inglés (preferido)
    description_es TEXT,             -- Descripción en español (preferido)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL        -- Soft deletes
);
```

**Campos utilizados en migración:**
- `id` → Mapeado a nuevo ID
- `name_en` / `name` → `categories.name` (EN)
- `name_es` / `name` → `categories.name` (ES)
- `description_en` / `description` → `categories.description` (EN)
- `description_es` / `description` → `categories.description` (ES)

**Ejemplo de registro:**
```json
{
    "id": 1,
    "name_en": "Cultural Tours",
    "name_es": "Tours Culturales",
    "description_en": "Explore Peru's rich cultural heritage",
    "description_es": "Explora la rica herencia cultural del Perú",
    "deleted_at": null
}
```

### 2. `products`

Tabla principal de productos/tours.

```sql
CREATE TABLE products (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),              -- Título del tour
    name VARCHAR(255),               -- Alternativa a title
    subtitle VARCHAR(255),           -- Subtítulo
    code VARCHAR(50),                -- Código único del producto
    category_id BIGINT,              -- ID de categoría (o CSV de IDs)
    nearest_city VARCHAR(255),       -- Ciudad más cercana
    nearest_airport VARCHAR(255),    -- Aeropuerto más cercano
    start_time TIME,                 -- Hora de inicio
    duration VARCHAR(100),           -- Duración (ej: "1 day", "8 hours")
    capacity INT,                    -- Capacidad máxima
    attachments TEXT,                -- Archivos adjuntos (JSON)
    status TINYINT,                  -- Estado (1=activo, 0=inactivo)
    policies TEXT,                   -- Políticas del tour
    booking_anticipation INT,        -- Horas de anticipación para reservar
    data_requirement INT,            -- Requisitos de datos
    multiple_forms BOOLEAN,          -- Permite múltiples formularios

    -- Campos de contenido (para tabs)
    description TEXT,                -- Descripción principal
    itinerary TEXT,                  -- Itinerario del tour
    includes TEXT,                   -- Qué incluye
    information TEXT,                -- Información adicional
    map TEXT,                        -- Mapa o coordenadas
    recommendations TEXT,            -- Recomendaciones
    departure_return TEXT,           -- Info de salida/retorno
    highlights TEXT,                 -- Puntos destacados

    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

**Campos utilizados en migración:**
- `id` → Mapeado a nuevo ID
- `title` / `name` → `products.title`
- `subtitle` → `products.subtitle`
- `code` → `products.code` + `product_codes.code`
- `category_id` → Relación `product_category`
- `nearest_city` → `products.nearest_city`
- `nearest_airport` → `products.nearest_airport`
- `start_time` → `products.start_time`
- `duration` → `products.duration`
- `capacity` → `products.capacity`
- `attachments` → `products.attachments`
- `status` → `products.status`
- `policies` → `products.policies`
- `booking_anticipation` → `products.booking_anticipation`
- `data_requirement` → `products.data_requirement`
- `multiple_forms` → `products.multiple_forms`
- `description` → `tabs.description`
- `itinerary` → `tabs.itinerary`
- `includes` → `tabs.includes`
- `information` → `tabs.information`
- `map` → `tabs.map`
- `recommendations` → `tabs.recommendations`
- `departure_return` → `tabs.departure_return`
- `highlights` → `additional_tabs.content`

**Ejemplo de registro:**
```json
{
    "id": 1,
    "title": "Machu Picchu Full Day Tour",
    "code": "TOUR-MP-001",
    "category_id": "1,3",
    "nearest_city": "Cusco",
    "nearest_airport": "Alejandro Velasco Astete",
    "start_time": "05:00:00",
    "duration": "1 day",
    "capacity": 16,
    "status": 1,
    "description": "Visit the lost city of the Incas...",
    "itinerary": "05:00 - Pickup from hotel...",
    "highlights": "Visit Machu Picchu, Train ride...",
    "deleted_at": null
}
```

### 3. `galleries`

Tabla de imágenes y archivos multimedia.

```sql
CREATE TABLE galleries (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT,               -- ID del producto asociado
    file_url VARCHAR(500),           -- URL del archivo
    image_url VARCHAR(500),          -- Alternativa a file_url
    file_details TEXT,               -- Detalles/descripción
    description TEXT,                -- Alternativa a file_details
    file_type INT,                   -- Tipo de archivo (1=imagen, 2=video)
    file_folder VARCHAR(255),        -- Carpeta de almacenamiento
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

**Campos utilizados en migración:**
- `id` → Mapeado a nuevo ID
- `product_id` → Relación `product_gallery`
- `file_url` / `image_url` → `galleries.file_url`
- `file_details` / `description` → `galleries.file_details`
- `file_type` → `galleries.file_type`
- `file_folder` → `galleries.file_folder`

**Ejemplo de registro:**
```json
{
    "id": 1,
    "product_id": 1,
    "file_url": "/storage/products/machu-picchu-01.jpg",
    "file_details": "Vista panorámica de Machu Picchu",
    "file_type": 1,
    "file_folder": "products",
    "deleted_at": null
}
```

### 4. `product_prices`

Tabla de precios de productos.

```sql
CREATE TABLE product_prices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT,               -- ID del producto
    amount DECIMAL(10,2),            -- Monto del precio
    price DECIMAL(10,2),             -- Alternativa a amount
    min_age INT,                     -- Edad mínima
    max_age INT,                     -- Edad máxima
    effective_from DATE,             -- Válido desde
    effective_to DATE,               -- Válido hasta
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

**Campos utilizados en migración:**
- `id` → No se mapea directamente
- `product_id` → `price_details.product_id`
- `amount` / `price` → `prices.amount`
- `min_age` → `price_details.min_age`
- `max_age` → `price_details.max_age`
- `effective_from` → `prices.effective_from`
- `effective_to` → `prices.effective_to`

**Nota:** Se crea automáticamente un `price_detail` con:
- `age_stage_id` = 1 (Adult por defecto)
- `nationality_id` = NULL (Todas las nacionalidades)

**Ejemplo de registro:**
```json
{
    "id": 1,
    "product_id": 1,
    "amount": 89.00,
    "min_age": 18,
    "max_age": 65,
    "effective_from": "2025-01-01",
    "effective_to": "2025-12-31",
    "deleted_at": null
}
```

### 5. `services` (Opcional)

Tabla de servicios adicionales. Actualmente no implementada en migración.

```sql
CREATE TABLE services (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    description TEXT,
    price DECIMAL(10,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

**Estado:** Pendiente de implementación en el comando de migración.

## Relaciones entre Tablas

### Categorías → Productos

```
categories (1)  ←──→  (many) products
```

En BD antigua: `products.category_id` puede ser:
- Un solo ID: `1`
- Múltiples IDs separados por comas: `1,3,5`

En BD nueva: Tabla pivote `product_category`

### Productos → Galerías

```
products (1)  ←──→  (many) galleries
```

En BD antigua: `galleries.product_id`
En BD nueva: Tabla pivote `product_gallery`

### Productos → Precios

```
products (1)  ←──→  (many) product_prices
```

En BD antigua: `product_prices.product_id`
En BD nueva: `price_details.product_id` → `prices.price_detail_id`

## Datos Esperados

### Conteos Aproximados

| Tabla | Registros Activos | Registros Eliminados |
|-------|-------------------|----------------------|
| categories | 5 | 0 |
| products | 251 | ? |
| galleries | 150+ | ? |
| product_prices | 180+ | ? |

### Campos Multiidioma

El comando espera campos en este orden de prioridad:

1. **Campos específicos de idioma** (preferido):
   - `name_en`, `name_es`
   - `description_en`, `description_es`

2. **Campos genéricos** (fallback):
   - `name`
   - `description`

Si solo existen campos genéricos, se duplican para ambos idiomas.

## Casos Especiales

### 1. Categorías Múltiples

Si `products.category_id` contiene múltiples IDs:
```
category_id = "1,3,5"
```

El comando:
1. Divide el string por comas
2. Mapea cada ID antiguo al nuevo ID
3. Asocia el producto con todas las categorías

### 2. Productos sin Categoría

Si `products.category_id` es NULL o vacío:
- El producto se migra sin categorías
- No genera error
- Se puede asociar categorías después

### 3. Galerías sin Producto

Si `galleries.product_id` es NULL:
- La galería se migra
- No se asocia con ningún producto
- Queda disponible para uso futuro

### 4. Soft Deletes

El comando solo migra registros donde `deleted_at IS NULL`.

Registros con `deleted_at` no nulo son ignorados.

## Validaciones del Comando

### Antes de Migrar

- ✓ Conexión a BD antigua exitosa
- ✓ Conexión a BD nueva exitosa
- ✓ Idiomas EN y ES existen en BD nueva
- ✓ Usuario admin existe o se usa ID=1

### Durante Migración

- ✓ Categorías existen antes de asociar con productos
- ✓ Productos existen antes de asociar galerías
- ✓ IDs mapeados correctamente

### Después de Migrar

- ✓ Todas las relaciones intactas
- ✓ Integridad referencial mantenida
- ✓ Conteos coinciden con BD antigua

## Customización

### Si la estructura es diferente

Si tu BD antigua tiene nombres de campos diferentes:

1. Abrir `app/Console/Commands/MigrateOldDataCommand.php`
2. Buscar el método correspondiente (ej: `migrateProducts`)
3. Ajustar los nombres de campos:

```php
// Ejemplo: Si el campo se llama 'product_name' en vez de 'title'
'title' => $oldProduct->product_name ?? $oldProduct->title ?? 'Untitled'
```

### Si faltan campos

El comando usa operadores de coalescencia nula (`??`) para valores por defecto:

```php
'duration' => $oldProduct->duration ?? '1 day',
'capacity' => $oldProduct->capacity ?? 10,
```

Si un campo no existe, se usa el valor por defecto y no genera error.

## Queries de Verificación Pre-Migración

Ejecutar estas queries en la BD antigua para verificar estructura:

```sql
-- Ver estructura de tabla
DESCRIBE categories;
DESCRIBE products;
DESCRIBE galleries;
DESCRIBE product_prices;

-- Contar registros activos
SELECT COUNT(*) FROM categories WHERE deleted_at IS NULL;
SELECT COUNT(*) FROM products WHERE deleted_at IS NULL;
SELECT COUNT(*) FROM galleries WHERE deleted_at IS NULL;
SELECT COUNT(*) FROM product_prices WHERE deleted_at IS NULL;

-- Ver campos disponibles
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'incalake_new'
  AND TABLE_NAME = 'products';

-- Ver sample de datos
SELECT * FROM products LIMIT 1;
SELECT * FROM categories LIMIT 1;
```

## Troubleshooting

### Error: Campo no existe

```
Error: Column 'name_en' not found
```

**Solución:** La BD antigua usa solo `name` (no `name_en`, `name_es`).
Editar comando para usar solo `name`.

### Error: Tipo de dato incorrecto

```
Error: Incorrect integer value
```

**Solución:** Verificar que los tipos de datos coincidan.
Ejemplo: Si `capacity` es TEXT en lugar de INT, convertir:

```php
'capacity' => (int)($oldProduct->capacity ?? 10),
```

### Error: Relación no encontrada

```
Error: product_id not found in mapping
```

**Solución:** El producto no se migró correctamente.
Revisar logs para ver por qué falló la migración del producto.

## Conclusión

Esta estructura es una referencia basada en el análisis del sistema antiguo de Incalake. Si tu estructura difiere, el comando es flexible y puede ajustarse editando los métodos de migración correspondientes.

Para analizar tu estructura real, usa:
```bash
php artisan analyze:old-database {tabla}
```
