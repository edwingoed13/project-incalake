# Sistema de Migración de Datos - Incalake CMS

## Descripción General

Sistema completo de migración de datos desde la base de datos antigua (`incalake_new`) al nuevo CMS Laravel de Incalake (`inc0910d_cms_incalake`).

**Capacidad:** Migrar 251 productos, 5 categorías, galerías, precios e itinerarios completos.

## Archivos Creados

### Comandos Artisan

1. **`MigrateOldDataCommand.php`** - Comando principal de migración
   - Ubicación: `app/Console/Commands/MigrateOldDataCommand.php`
   - Comando: `php artisan migrate:old-data`

2. **`AnalyzeOldDatabaseCommand.php`** - Analiza estructura de BD antigua
   - Ubicación: `app/Console/Commands/AnalyzeOldDatabaseCommand.php`
   - Comando: `php artisan analyze:old-database`

### Documentación

3. **`MIGRATION_GUIDE.md`** - Guía completa de migración
4. **`COMANDOS_MIGRACION.md`** - Referencia rápida de comandos
5. **`sql-verification-queries.sql`** - Queries de verificación SQL
6. **`README_MIGRACION.md`** - Este archivo

## Inicio Rápido

### 1. Pre-requisitos

```bash
# Verificar que ambas BDs existan
mysql -u root -e "SHOW DATABASES LIKE 'incalake_new';"
mysql -u root -e "SHOW DATABASES LIKE 'inc0910d_cms_incalake';"

# Ejecutar migraciones
cd c:\xampp\htdocs\web\laravel-incalake-v12
php artisan migrate

# Ejecutar seeders
php artisan db:seed
```

### 2. Analizar BD Antigua (Opcional pero recomendado)

```bash
# Ver todas las tablas
php artisan analyze:old-database

# Ver detalles de productos
php artisan analyze:old-database products

# Ver detalles de categorías
php artisan analyze:old-database categories
```

### 3. Probar Migración (Dry-run)

```bash
# NO guarda datos, solo prueba
php artisan migrate:old-data --dry-run
```

### 4. Migrar Datos

```bash
# Primera vez
php artisan migrate:old-data

# O con limpieza previa
php artisan migrate:old-data --fresh
```

### 5. Verificar Resultados

```bash
# Usar tinker
php artisan tinker --execute="echo 'Products: ' . Product::count();"

# O usar SQL queries
mysql -u root inc0910d_cms_incalake < sql-verification-queries.sql
```

## Características del Sistema

### Comando: `migrate:old-data`

#### Opciones

- `--dry-run` - Simula migración sin guardar
- `--fresh` - Limpia datos antes de migrar

#### Proceso de Migración

1. **Categorías** (paso 1/6)
   - Crea `category_codes` con slugs
   - Crea categorías en inglés y español
   - Mapea IDs antiguos → nuevos

2. **Servicios** (paso 2/6)
   - Pendiente de implementar
   - Marcado como opcional

3. **Productos** (paso 3/6)
   - Crea `product_codes`
   - Migra 251 productos
   - Asocia con categorías

4. **Galerías** (paso 4/6)
   - Migra imágenes
   - Asocia con productos

5. **Precios** (paso 5/6)
   - Crea `price_details`
   - Crea `prices`

6. **Tabs/Itinerarios** (paso 6/6)
   - Migra descripciones
   - Migra itinerarios
   - Crea highlights como tabs adicionales

#### Características Técnicas

- **Transacciones:** Cada sección usa transacciones
- **Progress Bar:** Muestra progreso visual
- **Logging:** Errores en `storage/logs/laravel.log`
- **Mapeo de IDs:** Mantiene relaciones antiguas → nuevas
- **Manejo de errores:** Continúa si un registro falla
- **Estadísticas:** Muestra resumen al final

### Comando: `analyze:old-database`

#### Uso

```bash
# Ver todas las tablas
php artisan analyze:old-database

# Analizar tabla específica
php artisan analyze:old-database {tabla}

# Ver más muestras
php artisan analyze:old-database {tabla} --sample=10
```

#### Información Mostrada

- Estructura de tabla (campos, tipos, claves)
- Conteo total de registros
- Registros activos vs eliminados (soft deletes)
- Muestras de datos
- Estadísticas de relaciones
- Campos JSON/texto detectados

## Estructura de Mapeo

### Categorías

```
BD Antigua (incalake_new)
└─ categories
   ├─ id
   ├─ name_en / name_es
   └─ description_en / description_es

BD Nueva (inc0910d_cms_incalake)
├─ category_codes (1 por categoría)
│  ├─ id
│  └─ code (slug)
│
└─ categories (2 por categoría: en + es)
   ├─ id
   ├─ name
   ├─ description
   ├─ language_id
   └─ category_code_id
```

### Productos

```
BD Antigua
└─ products
   ├─ id
   ├─ title
   ├─ code
   ├─ category_id
   └─ [otros campos]

BD Nueva
├─ product_codes (1 por producto)
│  ├─ id
│  └─ code
│
├─ products (1 por producto)
│  ├─ id
│  ├─ title
│  ├─ product_code_id
│  └─ [otros campos]
│
└─ product_category (many-to-many)
   ├─ product_id
   └─ category_id
```

### Precios

```
BD Antigua
└─ product_prices
   ├─ id
   ├─ product_id
   ├─ amount
   └─ [fechas]

BD Nueva
├─ price_details (configuración)
│  ├─ id
│  ├─ product_id
│  ├─ age_stage_id
│  ├─ nationality_id
│  ├─ min_age
│  └─ max_age
│
└─ prices (montos)
   ├─ id
   ├─ price_detail_id
   ├─ effective_from
   ├─ effective_to
   └─ amount
```

## Salida Esperada

```
🚀 Migrando datos del sistema antiguo...

✓ Conectado a BD antigua: incalake_new
✓ Conectado a BD nueva: inc0910d_cms_incalake

[1/6] Migrando categorías...
  ████████████████████ 5/5 [100%]
  ✓ 5 categorías migradas correctamente

[2/6] Migrando servicios...
  ✓ 0 servicios (no hay datos)

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

### Problema: PHP Version Error

```
Error: Your Composer dependencies require a PHP version ">= 8.2.0"
```

**Solución:** Actualizar PHP a 8.2+
```bash
# Verificar versión actual
php -v

# Actualizar PHP en XAMPP
# Descargar XAMPP con PHP 8.2+ desde: https://www.apachefriends.org/
```

### Problema: Connection Refused

```
Error de conexión: SQLSTATE[HY000] [2002] Connection refused
```

**Solución:**
1. Verificar que MySQL esté corriendo
2. Verificar credenciales en `config/database.php`
3. Probar conexión: `mysql -u root -p`

### Problema: Idiomas no encontrados

```
✗ Idiomas no encontrados. Ejecuta los seeders primero.
```

**Solución:**
```bash
php artisan db:seed --class=LanguageSeeder
```

### Problema: Datos duplicados

```
Ejecuté el comando 2 veces y ahora tengo datos duplicados
```

**Solución:**
```bash
php artisan migrate:old-data --fresh
# Confirmar con: yes
```

### Ver logs detallados

```bash
# Windows
type storage\logs\laravel.log | more

# PowerShell
Get-Content storage\logs\laravel.log -Tail 50

# Linux/Mac
tail -f storage/logs/laravel.log
```

## Verificación Post-Migración

### Checklist

- [ ] Conteo de productos coincide (251)
- [ ] Conteo de categorías coincide (5 × 2 = 10)
- [ ] Productos tienen categorías asociadas
- [ ] Productos tienen galerías asociadas
- [ ] Productos tienen precios
- [ ] Productos tienen tabs/itinerarios
- [ ] No hay errores en logs
- [ ] Relaciones funcionan correctamente

### Queries SQL de Verificación

Ver archivo: `sql-verification-queries.sql`

```bash
# Ejecutar todas las queries
mysql -u root inc0910d_cms_incalake < sql-verification-queries.sql
```

### Verificación con Tinker

```bash
php artisan tinker

# Contar registros
Product::count()
Category::count()
Gallery::count()

# Ver primer producto completo
$p = Product::with('categories', 'galleries', 'tab', 'priceDetails')->first();
echo "Título: {$p->title}\n";
echo "Categorías: {$p->categories->count()}\n";
echo "Galerías: {$p->galleries->count()}\n";
echo "Tiene tab: " . ($p->tab ? 'Sí' : 'No') . "\n";
echo "Precios: {$p->priceDetails->count()}\n";
```

## Configuración de Base de Datos

### Archivo: `config/database.php`

```php
'connections' => [
    // BD Nueva (por defecto)
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'inc0910d_cms_incalake'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],

    // BD Antigua (para migración)
    'mysql_old' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'database' => 'incalake_new',
        'username' => 'root',
        'password' => '',
    ],
],
```

### Archivo: `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inc0910d_cms_incalake
DB_USERNAME=root
DB_PASSWORD=
```

## Performance

### Tiempos Estimados

| Operación | Registros | Tiempo |
|-----------|-----------|--------|
| Categorías | 5 | < 1s |
| Productos | 251 | 5-8s |
| Galerías | 150 | 2-3s |
| Precios | 180 | 3-4s |
| Tabs | 251 | 2-3s |
| **TOTAL** | **837** | **~12-15s** |

### Optimizaciones Implementadas

- Transacciones por sección (no por registro)
- Bulk inserts donde es posible
- Mapeo en memoria (no queries repetidas)
- Lazy loading de relaciones

## Mantenimiento

### Actualizar estructura antigua

Si la BD antigua cambia estructura:

1. Ejecutar análisis: `php artisan analyze:old-database {tabla}`
2. Actualizar código en `MigrateOldDataCommand.php`
3. Probar con `--dry-run`
4. Migrar con `--fresh`

### Agregar nuevas tablas

Para migrar nuevas entidades:

1. Crear método `migrateTablaNueva()`
2. Agregar al método `handle()`
3. Agregar estadísticas al array `$stats`
4. Actualizar `showSummary()`

### Logs

Los logs se guardan en:
- `storage/logs/laravel.log` - Log principal
- Formato: `[timestamp] level.message context`

## Soporte y Contacto

Para problemas o preguntas:

1. Revisar documentación en este README
2. Revisar logs en `storage/logs/laravel.log`
3. Ejecutar queries de verificación SQL
4. Contactar al desarrollador con:
   - Comando ejecutado
   - Error completo
   - Logs relevantes

## Licencia y Créditos

Desarrollado para: **Incalake Tourism CMS**
Versión: **1.0.0**
Fecha: **Enero 2025**

---

**Nota:** Este sistema de migración fue diseñado específicamente para migrar datos de la BD antigua de Incalake al nuevo CMS Laravel. No es un sistema genérico de migración.
