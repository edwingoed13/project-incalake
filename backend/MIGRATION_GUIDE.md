# Guía de Migración de Datos - Incalake CMS

## Comando Artisan de Migración

Este comando migra datos desde la base de datos antigua (`incalake_new`) al nuevo CMS de Laravel.

### Uso Básico

```bash
# Migración normal
php artisan migrate:old-data

# Modo dry-run (prueba sin guardar)
php artisan migrate:old-data --dry-run

# Limpiar datos existentes antes de migrar
php artisan migrate:old-data --fresh
```

## Pre-requisitos

### 1. Verificar Conexión de Base de Datos

Asegúrate de que la conexión `mysql_old` esté configurada en `config/database.php`:

```php
'mysql_old' => [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'incalake_new',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
],
```

### 2. Ejecutar Migraciones y Seeders

Antes de migrar datos, ejecuta:

```bash
# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders (para idiomas y datos iniciales)
php artisan db:seed
```

### 3. Verificar Usuario Admin

El comando necesita un usuario admin. Verifica que exista:

```php
User::where('email', 'admin@incalake.com')->first();
```

Si no existe, el comando usará `user_id = 1` por defecto.

## Proceso de Migración

El comando migra datos en el siguiente orden:

### 1. Categorías (5 registros esperados)
- Crea `category_codes` con slugs únicos
- Crea categorías en inglés y español
- Mapea IDs antiguos a nuevos

### 2. Servicios
- Actualmente marcado como pendiente
- Se implementará cuando se conozca la estructura

### 3. Productos/Tours (251 registros esperados)
- Crea `product_codes` para cada producto
- Migra todos los campos del producto
- Asocia productos con categorías
- Mapea IDs para uso posterior

### 4. Galerías
- Migra imágenes del sistema antiguo
- Asocia imágenes con productos mediante tabla pivote
- Preserva URLs y metadatos

### 5. Precios
- Crea `price_details` para cada precio
- Asocia con productos migrados
- Configura rangos de edad por defecto

### 6. Tabs e Itinerarios
- Migra descripciones e itinerarios a tabla `tabs`
- Convierte highlights en `additional_tabs`
- Preserva toda la información del tour

## Características del Comando

### Opciones Disponibles

#### `--dry-run`
Ejecuta la migración sin guardar datos. Útil para:
- Probar la conexión a ambas bases de datos
- Verificar que los datos se pueden leer correctamente
- Identificar errores antes de la migración real

```bash
php artisan migrate:old-data --dry-run
```

#### `--fresh`
Limpia todos los datos existentes antes de migrar:
- Elimina productos, categorías, galerías, precios, etc.
- Respeta integridad referencial (elimina en orden correcto)
- Solicita confirmación antes de ejecutar

```bash
php artisan migrate:old-data --fresh
```

**ADVERTENCIA:** Esta opción elimina TODOS los datos. Úsala solo si estás seguro.

### Manejo de Errores

- **Transacciones:** Cada sección usa transacciones para rollback en caso de error
- **Logging:** Todos los errores se registran en `storage/logs/laravel.log`
- **Contadores:** Muestra estadísticas de éxitos y fallos
- **Continuación:** Si un registro falla, continúa con el siguiente

### Mapeo de IDs

El comando mantiene mapeos internos:
- `categoryMapping`: ID antiguo → ID nuevo de categorías
- `productMapping`: ID antiguo → ID nuevo de productos
- `galleryMapping`: ID antiguo → ID nuevo de galerías

Esto permite mantener relaciones entre tablas.

## Mapeo de Datos

### Categorías

**Antigua estructura:**
```
categories:
  - id
  - name / name_en / name_es
  - description / description_en / description_es
```

**Nueva estructura:**
```
category_codes:
  - id
  - code (slug generado)
  - image_id

categories (2 registros por categoría: en, es):
  - id
  - name
  - description
  - language_id
  - category_code_id
  - user_id
```

### Productos

**Campos mapeados:**
- `title` / `name` → `title`
- `code` → `code` (o generado como `TOUR-{id}`)
- `category_id` → relación many-to-many con categorías
- Todos los campos de configuración (`capacity`, `duration`, etc.)

### Galerías

**Campos mapeados:**
- `file_url` / `image_url` → `file_url`
- `file_details` / `description` → `file_details`
- `product_id` → relación many-to-many

### Precios

**Transformación:**
```
product_prices (antigua) →
  price_details (edad, nacionalidad)
  + prices (montos, fechas)
```

## Salida del Comando

### Ejemplo de Ejecución Exitosa

```
🚀 Migrando datos del sistema antiguo...

✓ Conectado a BD antigua: incalake_new
✓ Conectado a BD nueva: inc0910d_cms_incalake

[1/6] Migrando categorías...
  ████████████████████ 5/5 [100%]
  ✓ 5 categorías migradas correctamente

[2/6] Migrando servicios...
  ⚠ No se encontró tabla 'services' en BD antigua

[3/6] Migrando productos/tours...
  ████████████████████ 251/251 [100%]
  ✓ 251 productos migrados

[4/6] Migrando galerías...
  ████████████████████ 150/150 [100%]
  ✓ 150 imágenes asociadas

[5/6] Migrando precios...
  ████████████████████ 180/180 [100%]
  ✓ 180 precios migrados

[6/6] Migrando itinerarios y highlights...
  ████████████████████ 251/251 [100%]
  ✓ 251 tabs migrados

✓ Migración completada exitosamente!

Resumen:
┌────────────┬──────────┬─────────┬───────┐
│ Tipo       │ Exitosos │ Errores │ Total │
├────────────┼──────────┼─────────┼───────┤
│ Categorías │ 5        │ 0       │ 5     │
│ Servicios  │ 0        │ 0       │ 0     │
│ Productos  │ 251      │ 0       │ 251   │
│ Galerías   │ 150      │ 0       │ 150   │
│ Precios    │ 180      │ 0       │ 180   │
│ Tabs       │ 251      │ 0       │ 251   │
└────────────┴──────────┴─────────┴───────┘

Total de registros: 837
Tiempo transcurrido: 12.5s
```

## Resolución de Problemas

### Error: "Idiomas no encontrados"

**Solución:** Ejecuta el seeder de idiomas
```bash
php artisan db:seed --class=LanguageSeeder
```

### Error: "Connection refused"

**Solución:** Verifica que:
1. MySQL esté corriendo
2. Las credenciales en `config/database.php` sean correctas
3. La base de datos `incalake_new` exista

### Error: "Foreign key constraint"

**Solución:**
1. Usa `--fresh` para limpiar datos en orden correcto
2. Verifica que las migraciones estén actualizadas

### Errores Parciales

Si algunos registros fallan:
1. Revisa `storage/logs/laravel.log` para detalles
2. Identifica el problema (campo faltante, tipo incorrecto, etc.)
3. Corrige y vuelve a ejecutar con `--fresh`

## Verificación Post-Migración

Después de migrar, verifica:

```bash
# Contar categorías
php artisan tinker --execute="echo Category::count() . ' categorías';"

# Contar productos
php artisan tinker --execute="echo Product::count() . ' productos';"

# Contar galerías
php artisan tinker --execute="echo Gallery::count() . ' galerías';"

# Verificar relaciones
php artisan tinker --execute="\$p = Product::with('categories', 'galleries')->first(); echo 'Producto: ' . \$p->title . '\n'; echo 'Categorías: ' . \$p->categories->count() . '\n'; echo 'Galerías: ' . \$p->galleries->count();"
```

## Notas Importantes

1. **Backup:** Siempre haz backup antes de ejecutar `--fresh`
2. **Dry-run primero:** Ejecuta con `--dry-run` antes de la migración real
3. **Logs:** Revisa los logs después de cada migración
4. **Idempotencia:** El comando NO es idempotente. Ejecutarlo dos veces duplicará datos (a menos que uses `--fresh`)
5. **Rendimiento:** Para 251 productos, la migración toma ~10-15 segundos

## Siguientes Pasos

Después de la migración:

1. Verificar que todos los datos se migraron correctamente
2. Revisar las imágenes y asegurarse de que las URLs sean accesibles
3. Probar la funcionalidad del sistema con los datos migrados
4. Implementar migración de servicios (si aplica)
5. Ajustar precios y disponibilidades según necesidad

## Soporte

Para problemas o dudas:
- Revisa los logs en `storage/logs/laravel.log`
- Verifica la estructura de la BD antigua vs nueva
- Contacta al desarrollador con los detalles del error
