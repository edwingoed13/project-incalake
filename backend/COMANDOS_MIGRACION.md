# Comandos de Migración - Referencia Rápida

## Análisis de Base de Datos Antigua

### Ver todas las tablas

```bash
php artisan analyze:old-database
```

Salida:
```
┌─────────────────┬────────────┐
│ Tabla           │ Registros  │
├─────────────────┼────────────┤
│ categories      │ 5          │
│ products        │ 251        │
│ galleries       │ 150        │
│ product_prices  │ 180        │
│ ...             │ ...        │
└─────────────────┴────────────┘
```

### Analizar tabla específica

```bash
php artisan analyze:old-database products
php artisan analyze:old-database categories
php artisan analyze:old-database galleries
```

Salida detallada:
```
Tabla: products

Estructura:
┌────────────────────┬──────────────┬──────┬───────┬─────────┐
│ Campo              │ Tipo         │ Nulo │ Clave │ Default │
├────────────────────┼──────────────┼──────┼───────┼─────────┤
│ id                 │ bigint       │ NO   │ PRI   │ NULL    │
│ title              │ varchar(255) │ YES  │       │ NULL    │
│ code               │ varchar(50)  │ YES  │       │ NULL    │
│ category_id        │ bigint       │ YES  │ MUL   │ NULL    │
│ ...                │ ...          │ ...  │ ...   │ ...     │
└────────────────────┴──────────────┴──────┴───────┴─────────┘

Total de registros: 251

Muestra de datos (primeros 5 registros):
Registro #1:
  id: 1
  title: Machu Picchu Full Day Tour
  code: TOUR-MP-001
  ...
```

### Ver más muestras

```bash
php artisan analyze:old-database products --sample=10
```

## Migración de Datos

### 1. Modo Dry-Run (Recomendado primero)

```bash
php artisan migrate:old-data --dry-run
```

**¿Qué hace?**
- Conecta a ambas bases de datos
- Lee todos los datos
- Simula la migración
- NO guarda nada
- Muestra estadísticas

**Salida esperada:**
```
🚀 Migrando datos del sistema antiguo...

✓ Conectado a BD antigua: incalake_new
✓ Conectado a BD nueva: inc0910d_cms_incalake

⚠️  MODO DRY-RUN: No se guardarán datos en la BD

[1/6] Migrando categorías...
  ████████████████████ 5/5 [100%]
  ✓ 5 categorías migradas correctamente
...

⚠️  MODO DRY-RUN: No se guardaron cambios en la base de datos
```

### 2. Migración Normal

```bash
php artisan migrate:old-data
```

**¿Qué hace?**
- Migra TODOS los datos
- Guarda en la base de datos
- Mantiene datos existentes (NO los elimina)

**ADVERTENCIA:** Si ejecutas este comando 2 veces, duplicará los datos.

### 3. Migración con Limpieza (Fresh)

```bash
php artisan migrate:old-data --fresh
```

**¿Qué hace?**
- Elimina TODOS los datos existentes
- Migra los datos desde cero
- Solicita confirmación antes de ejecutar

**Confirmación:**
```
¿Estás seguro de limpiar todos los datos existentes? (yes/no) [no]:
```

**ADVERTENCIA:** Esto ELIMINA todos los productos, categorías, galerías, etc.

## Flujo de Trabajo Recomendado

### Primera Vez

```bash
# 1. Analizar estructura antigua
php artisan analyze:old-database

# 2. Ver detalles de tablas importantes
php artisan analyze:old-database products
php artisan analyze:old-database categories
php artisan analyze:old-database galleries

# 3. Probar migración sin guardar
php artisan migrate:old-data --dry-run

# 4. Si todo está OK, migrar
php artisan migrate:old-data
```

### Re-migración (después de correcciones)

```bash
# 1. Limpiar y migrar de nuevo
php artisan migrate:old-data --fresh

# Confirmar con: yes
```

### Solo probar sin afectar BD

```bash
# Siempre seguro
php artisan migrate:old-data --dry-run
```

## Verificación Post-Migración

### Verificar conteos

```bash
# Contar registros migrados
php artisan tinker

# En tinker:
Category::count()        // Debería ser 10 (5 x 2 idiomas)
Product::count()         // Debería ser 251
Gallery::count()         // Debería ser 150+
PriceDetail::count()     // Debería ser ~180
```

### Verificar relaciones

```bash
php artisan tinker

# Ver un producto con sus relaciones
$product = Product::with('categories', 'galleries', 'tab')->first();
echo "Título: " . $product->title . "\n";
echo "Categorías: " . $product->categories->count() . "\n";
echo "Galerías: " . $product->galleries->count() . "\n";
echo "Tiene tab: " . ($product->tab ? 'Sí' : 'No') . "\n";
```

## Solución de Problemas

### Error: "Connection refused"

**Problema:** No puede conectar a la BD antigua

**Solución:**
```bash
# Verificar que MySQL esté corriendo
# Verificar config/database.php:
# 'mysql_old' => [
#     'database' => 'incalake_new',
#     'username' => 'root',
#     'password' => '',
# ]
```

### Error: "Idiomas no encontrados"

**Problema:** No existen idiomas en la BD nueva

**Solución:**
```bash
php artisan db:seed --class=LanguageSeeder
```

### Error: "Foreign key constraint"

**Problema:** Integridad referencial

**Solución:**
```bash
# Limpiar en orden correcto
php artisan migrate:old-data --fresh
```

### Ver logs de errores

```bash
# Windows
type storage\logs\laravel.log

# Linux/Mac
tail -f storage/logs/laravel.log
```

## Opciones Combinadas

### Dry-run después de limpieza (para probar)

```bash
# NO FUNCIONA así, dry-run no limpia
# Si quieres probar limpieza + migración, usa:
php artisan migrate:old-data --fresh
# Y responde 'no' cuando pregunte
```

## Tiempos Estimados

| Registros | Tiempo Aproximado |
|-----------|------------------|
| 5 categorías | < 1 segundo |
| 251 productos | ~5-8 segundos |
| 150 galerías | ~2-3 segundos |
| 180 precios | ~3-4 segundos |
| **Total** | **~10-15 segundos** |

## Checklist Pre-Migración

- [ ] MySQL corriendo
- [ ] Base de datos antigua (`incalake_new`) existe
- [ ] Base de datos nueva creada
- [ ] Migraciones ejecutadas (`php artisan migrate`)
- [ ] Seeders ejecutados (`php artisan db:seed`)
- [ ] Conexión `mysql_old` configurada
- [ ] Backup de BD nueva (por si acaso)

## Checklist Post-Migración

- [ ] Verificar conteos de registros
- [ ] Verificar relaciones entre tablas
- [ ] Verificar que las imágenes existan
- [ ] Probar un producto completo en el frontend
- [ ] Revisar logs por errores
- [ ] Documentar cualquier dato que no migró

## Ejemplo Completo de Sesión

```bash
# Terminal 1: Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Terminal 2: Ejecutar comandos
cd c:\xampp\htdocs\web\laravel-incalake-v12

# Analizar BD antigua
php artisan analyze:old-database

# Probar migración
php artisan migrate:old-data --dry-run

# Si todo OK, migrar
php artisan migrate:old-data

# Verificar
php artisan tinker --execute="echo 'Productos: ' . Product::count();"
```

## Notas Importantes

1. **Dry-run es tu amigo:** Úsalo siempre primero
2. **Fresh es peligroso:** Solo úsalo si estás seguro
3. **Logs son importantes:** Revisa `storage/logs/laravel.log`
4. **No es idempotente:** Ejecutar 2 veces duplica datos (usa `--fresh`)
5. **Transaccional:** Si algo falla, hace rollback de esa sección
6. **Mapeo automático:** Mantiene relaciones entre IDs antiguos y nuevos

## Comandos de Utilidad

```bash
# Listar todos los comandos artisan
php artisan list

# Ver ayuda de comando específico
php artisan help migrate:old-data
php artisan help analyze:old-database

# Limpiar cache de artisan
php artisan optimize:clear
```
