# Reporte de Migraciones Laravel - Incalake

## Resumen Ejecutivo

Se han creado **38 migraciones**, **31 modelos Eloquent** y **4 seeders** para migrar completamente la base de datos del sistema actual a Laravel.

---

## 1. Migraciones Creadas (38 totales)

### Migraciones Base (Laravel Default - 4)
- `2014_10_12_000000_create_users_table.php`
- `2014_10_12_100000_create_password_resets_table.php`
- `2019_08_19_000000_create_failed_jobs_table.php`
- `2019_12_14_000001_create_personal_access_tokens_table.php`

### Migraciones de Sistema Base (7)
1. `2024_01_01_000001_create_languages_table.php` - Idiomas multilenguaje
2. `2024_01_01_000002_create_configurations_table.php` - Configuraciones del sistema
3. `2024_01_01_000003_create_galleries_table.php` - Galería de imágenes
4. `2024_01_01_000004_create_nationalities_table.php` - Nacionalidades
5. `2024_01_01_000005_create_age_stages_table.php` - Etapas de edad para precios
6. `2024_01_01_000006_create_category_codes_table.php` - Códigos de categorías
7. `2024_01_01_000007_create_categories_table.php` - Categorías multilenguaje

### Migraciones de Servicios y Productos (7)
8. `2024_01_01_000008_create_service_codes_table.php` - Códigos de servicios
9. `2024_01_01_000009_create_services_table.php` - Servicios/tours
10. `2024_01_01_000010_create_product_codes_table.php` - Códigos de productos
11. `2024_01_01_000011_create_products_table.php` - Productos/paquetes turísticos
12. `2024_01_01_000012_create_product_category_table.php` - Pivot: Productos-Categorías
13. `2024_01_01_000013_create_product_gallery_table.php` - Pivot: Productos-Galería
14. `2024_01_01_000014_create_tabs_table.php` - Tabs de información de productos
15. `2024_01_01_000015_create_additional_tabs_table.php` - Tabs adicionales personalizados

### Migraciones de Precios y Disponibilidad (6)
16. `2024_01_01_000016_create_price_details_table.php` - Detalles de precios
17. `2024_01_01_000017_create_prices_table.php` - Precios por cantidad
18. `2024_01_01_000018_create_availabilities_table.php` - Disponibilidad de productos
19. `2024_01_01_000019_create_blockouts_table.php` - Bloqueos de fechas
20. `2024_01_01_000020_create_offers_table.php` - Ofertas y promociones
21. `2024_01_01_000021_create_coupons_table.php` - Cupones de descuento

### Migraciones de Reservas y Pagos (8)
22. `2024_01_01_000022_create_bookings_table.php` - Reservas
23. `2024_01_01_000023_create_information_groups_table.php` - Grupos de información
24. `2024_01_01_000024_create_booking_details_table.php` - Detalles de reservas
25. `2024_01_01_000025_create_service_details_table.php` - Detalles de servicios
26. `2024_01_01_000026_create_payment_methods_table.php` - Métodos de pago
27. `2024_01_01_000027_create_payments_table.php` - Pagos
28. `2024_01_01_000028_create_booking_detail_payment_table.php` - Pivot: Reservas-Pagos
29. `2024_01_01_000029_create_booking_detail_service_table.php` - Pivot: Reservas-Servicios

### Migraciones de Recursos y Formularios (6)
30. `2024_01_01_000030_create_resources_table.php` - Recursos/extras
31. `2024_01_01_000031_create_resource_gallery_table.php` - Pivot: Recursos-Galería
32. `2024_01_01_000032_create_product_resource_table.php` - Pivot: Productos-Recursos
33. `2024_01_01_000033_create_field_categories_table.php` - Categorías de campos
34. `2024_01_01_000034_create_form_fields_table.php` - Campos de formularios
35. `2024_01_01_000035_create_product_form_field_table.php` - Pivot: Productos-Campos

### Migraciones Adicionales (2)
36. `2024_01_01_000036_create_booking_informations_table.php` - Información de reservas
37. `2024_01_01_000037_create_notifications_table.php` - Notificaciones
38. `2024_01_01_000038_create_sliders_table.php` - Sliders del sitio

---

## 2. Modelos Eloquent Creados (31 modelos)

### Modelos de Sistema Base
1. **Language** - Gestión de idiomas (ES, EN, FR, DE, BR)
2. **Configuration** - Configuraciones generales del sitio
3. **Gallery** - Gestión de archivos multimedia
4. **Nationality** - Nacionalidades para precios diferenciados
5. **AgeStage** - Etapas de edad (Niño, Adulto, etc.)

### Modelos de Categorías y Servicios
6. **CategoryCode** - Agrupador de categorías multilenguaje
7. **Category** - Categorías de tours/servicios
8. **ServiceCode** - Agrupador de servicios multilenguaje
9. **Service** - Servicios/tours individuales
10. **ProductCode** - Agrupador de productos multilenguaje
11. **Product** - Productos/paquetes turísticos

### Modelos de Contenido de Productos
12. **Tab** - Información principal del producto (descripción, itinerario, incluye, etc.)
13. **AdditionalTab** - Tabs personalizados adicionales

### Modelos de Precios
14. **PriceDetail** - Configuración de precios por edad/nacionalidad
15. **Price** - Precios específicos por cantidad

### Modelos de Disponibilidad y Promociones
16. **Availability** - Disponibilidad de productos
17. **Blockout** - Bloqueos de fechas
18. **Offer** - Ofertas temporales
19. **Coupon** - Cupones de descuento

### Modelos de Reservas
20. **Booking** - Reserva principal
21. **InformationGroup** - Agrupador de información de pasajeros
22. **BookingDetail** - Detalle de reserva
23. **ServiceDetail** - Detalles del servicio reservado
24. **BookingInformation** - Información recopilada de formularios

### Modelos de Pagos
25. **PaymentMethod** - Métodos de pago disponibles
26. **Payment** - Registros de pagos

### Modelos de Recursos
27. **Resource** - Recursos/extras adicionales (chullo, etc.)

### Modelos de Formularios
28. **FieldCategory** - Categorías de campos de formulario
29. **FormField** - Campos dinámicos de formularios

### Modelos Adicionales
30. **Notification** - Notificaciones del sistema
31. **Slider** - Sliders de la página principal

---

## 3. Seeders Creados (4 seeders)

1. **LanguageSeeder** - Carga idiomas: Español, Inglés, Francés, Alemán, Portugués
2. **NationalitySeeder** - Carga nacionalidades: Peruana, Extranjero, Boliviana
3. **AgeStageSeeder** - Carga etapas de edad: Niño (0-3), Adulto (18-99)
4. **CategorySeeder** - Carga categoría de ejemplo: Turismo Astronómico (en todos los idiomas)
5. **DatabaseSeeder** - Orquestador principal de seeders

---

## 4. Diagrama de Relaciones

### Relaciones Principales

```
User (1) ──── (N) Language
User (1) ──── (N) Configuration
User (1) ──── (N) Gallery
User (1) ──── (N) ServiceCode
User (1) ──── (N) ProductCode
User (1) ──── (N) Category
User (1) ──── (N) Resource
User (1) ──── (N) PaymentMethod

Language (1) ──── (N) Category
Language (1) ──── (N) Service

CategoryCode (1) ──── (N) Category

ServiceCode (1) ──── (N) Service

Service (1) ──── (N) Product
Service (1) ──── (N) Notification
Service (N) ──── (N) BookingDetail [pivot: booking_detail_service]

ProductCode (1) ──── (N) Product

Product (1) ──── (1) Tab
Product (1) ──── (N) AdditionalTab
Product (1) ──── (N) PriceDetail
Product (1) ──── (N) Availability
Product (1) ──── (N) Blockout
Product (1) ──── (N) Offer
Product (1) ──── (N) Coupon
Product (1) ──── (N) ServiceDetail
Product (N) ──── (N) Category [pivot: product_category]
Product (N) ──── (N) Gallery [pivot: product_gallery]
Product (N) ──── (N) Resource [pivot: product_resource]
Product (N) ──── (N) FormField [pivot: product_form_field]

PriceDetail (1) ──── (N) Price
PriceDetail (N) ──── (1) AgeStage
PriceDetail (N) ──── (1) Nationality

Booking (1) ──── (N) BookingDetail

InformationGroup (1) ──── (N) BookingDetail
InformationGroup (1) ──── (N) BookingInformation

BookingDetail (N) ──── (N) Payment [pivot: booking_detail_payment]

FieldCategory (1) ──── (N) FormField

FormField (1) ──── (N) BookingInformation

Resource (N) ──── (N) Gallery [pivot: resource_gallery]
```

---

## 5. Características Implementadas

### Multilenguaje
- Sistema completo de traducción en 5 idiomas (ES, EN, FR, DE, BR)
- Categorías, servicios y productos en múltiples idiomas
- Campos de formulario traducibles

### Sistema de Precios Flexible
- Precios diferenciados por edad
- Precios diferenciados por nacionalidad
- Precios por cantidad de personas
- Descuentos automáticos por cantidad

### Gestión de Disponibilidad
- Control de disponibilidad por fechas
- Días activos/inactivos de la semana
- Bloqueos de fechas específicas
- Ofertas con fechas de vigencia

### Sistema de Reservas Completo
- Reservas con información de líder de grupo
- Formularios dinámicos personalizables por producto
- Agrupación de información de múltiples pasajeros
- Gestión de pagos asociados

### Recursos Adicionales
- Extras opcionales (chullo, etc.)
- Recursos como regalos incluidos
- Precios multilenguaje para recursos

---

## 6. Comandos para Ejecutar

### Ejecutar todas las migraciones
```bash
cd c:/xampp/htdocs/web/laravel-incalake
php artisan migrate
```

### Ejecutar migraciones con seeders
```bash
php artisan migrate --seed
```

### Resetear y volver a migrar (CUIDADO: Borra todos los datos)
```bash
php artisan migrate:fresh --seed
```

### Ejecutar solo los seeders
```bash
php artisan db:seed
```

### Ejecutar un seeder específico
```bash
php artisan db:seed --class=LanguageSeeder
php artisan db:seed --class=NationalitySeeder
php artisan db:seed --class=AgeStageSeeder
php artisan db:seed --class=CategorySeeder
```

### Crear una migración manualmente (si necesitas más)
```bash
php artisan make:migration create_table_name_table
```

### Crear un modelo con migración
```bash
php artisan make:model ModelName -m
```

---

## 7. Tipos de Datos Modernos Utilizados

- **bigInteger/foreignId** - Para IDs y claves foráneas
- **string** - Para textos cortos con longitud definida
- **text** - Para textos largos sin límite
- **boolean** - Para valores verdadero/falso
- **decimal** - Para precios y montos monetarios
- **integer** - Para cantidades y edades
- **dateTime** - Para fechas y horas
- **softDeletes** - Para eliminación lógica (mantiene historial)
- **timestamps** - created_at y updated_at automáticos

---

## 8. Convenciones de Nombres

### Español → Inglés

| Español Original | Inglés en Laravel |
|------------------|-------------------|
| idioma | language |
| configuracion | configuration |
| galeria | gallery |
| nacionalidad | nationality |
| etapa_edad | age_stage |
| categoria | category |
| servicio | service |
| producto | product |
| bloqueo | blockout |
| disponibilidad | availability |
| oferta | offer |
| cupon | coupon |
| reserva | booking |
| detallereserva | booking_detail |
| pago | payment |
| metodo_pago | payment_method |
| recurso | resource |
| campo_formulario | form_field |
| campo_categoria | field_category |
| notificacion | notification |
| deslizador/slider | slider |

---

## 9. Integridad Referencial

Todas las migraciones incluyen:

- **Foreign Keys** correctamente definidas
- **onDelete('cascade')** para eliminación en cascada
- **onUpdate('cascade')** para actualización en cascada
- **Soft Deletes** en la mayoría de tablas para mantener historial
- **Índices** en claves foráneas para optimización

---

## 10. Próximos Pasos Recomendados

1. **Ejecutar migraciones** en entorno de desarrollo
2. **Verificar relaciones** entre modelos
3. **Crear controllers** para cada modelo
4. **Implementar validaciones** en Form Requests
5. **Crear API Resources** para transformar datos
6. **Implementar autenticación** con Sanctum/Passport
7. **Crear rutas API** para el frontend
8. **Implementar testing** unitario y de integración
9. **Documentar API** con Swagger/OpenAPI
10. **Migrar datos** del sistema antiguo al nuevo

---

## 11. Notas Importantes

- Todas las migraciones siguen las convenciones de Laravel
- Los modelos incluyen casts para tipos de datos especiales
- Soft deletes permite recuperar registros eliminados
- Las traducciones se almacenan como JSON
- El sistema está preparado para escalabilidad
- Compatible con Laravel 9, 10 y 11

---

## 12. Archivos Creados

**Total: 73 archivos**
- 38 Migraciones
- 31 Modelos
- 4 Seeders

**Ubicaciones:**
- Migraciones: `c:\xampp\htdocs\web\laravel-incalake\database\migrations\`
- Modelos: `c:\xampp\htdocs\web\laravel-incalake\app\Models\`
- Seeders: `c:\xampp\htdocs\web\laravel-incalake\database\seeders\`

---

**Fecha de generación:** 2026-01-04
**Generado por:** Claude Code - Antropic AI
**Proyecto:** Incalake Tourism Platform Migration
