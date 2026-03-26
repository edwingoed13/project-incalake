# Esquema de Base de Datos - Incalake Tourism Platform

## Arquitectura General

```
┌─────────────────────────────────────────────────────────────────┐
│                    SISTEMA MULTILENGUAJE                         │
│  Languages (ES, EN, FR, DE, BR) → Categories → Services → Products│
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    GESTIÓN DE CONTENIDO                          │
│  Products → Tabs → Galleries → Resources → FormFields           │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    SISTEMA DE PRECIOS                            │
│  Products → PriceDetails (Age/Nationality) → Prices              │
│           → Availabilities → Offers → Coupons → Blockouts       │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    SISTEMA DE RESERVAS                           │
│  Bookings → BookingDetails → Payments → ServiceDetails          │
│           → InformationGroups → BookingInformations             │
└─────────────────────────────────────────────────────────────────┘
```

---

## Tablas por Módulo

### MÓDULO 1: SISTEMA BASE (7 tablas)

#### users
```sql
id, name, email, password, remember_token, timestamps
```
Usuario administrador del sistema

#### languages
```sql
id, country, code, user_id, timestamps, soft_deletes
```
Idiomas disponibles: ES, EN, FR, DE, BR

#### configurations
```sql
id, company_name, index_title, index_keywords, index_description,
index_logo_id, index_favicon_id, google_analytics_code, zoopim_code,
user_id, timestamps, soft_deletes
```
Configuración general del sitio web

#### galleries
```sql
id, file_url, file_details, file_type, file_folder, user_id,
timestamps, soft_deletes
```
Galería multimedia (imágenes, videos)

#### nationalities
```sql
id, description, translations, timestamps, soft_deletes
```
Nacionalidades para precios diferenciados

#### age_stages
```sql
id, description, min_age, max_age, editable, translations,
timestamps, soft_deletes
```
Etapas de edad: Niño, Adulto, etc.

#### sliders
```sql
id, title, description, destination_url, gallery_id,
timestamps, soft_deletes
```
Sliders del home page

---

### MÓDULO 2: CATEGORÍAS Y SERVICIOS (6 tablas)

#### category_codes
```sql
id, code, image_id, timestamps, soft_deletes
```
Agrupador de categorías multiidioma

#### categories
```sql
id, name, description, language_id, category_code_id, user_id,
timestamps, soft_deletes
```
Categorías de tours (ej: Turismo Astronómico)

#### service_codes
```sql
id, code, user_id, timestamps, soft_deletes
```
Agrupador de servicios multiidioma

#### services
```sql
id, url, page_title, page_description, main_image, show_slider,
thumbnail, rating, reviews, language_id, service_code_id, location,
uri, timestamps, soft_deletes
```
Servicios/Tours (ej: Tour a Uros, Tour a Sillustani)

#### product_codes
```sql
id, code, user_id, timestamps, soft_deletes
```
Agrupador de productos multiidioma

#### products
```sql
id, title, subtitle, code, nearest_city, nearest_airport, service_id,
start_time, duration, capacity, attachments, product_code_id, status,
policies, booking_anticipation, data_requirement, multiple_forms,
timestamps, soft_deletes
```
Productos/Paquetes turísticos específicos

---

### MÓDULO 3: CONTENIDO DE PRODUCTOS (3 tablas)

#### tabs
```sql
id, description, itinerary, includes, information, map,
recommendations, departure_return, product_id, timestamps, soft_deletes
```
Información principal del producto (7 pestañas estándar)

#### additional_tabs
```sql
id, icon, name, content, product_id, timestamps, soft_deletes
```
Pestañas personalizadas adicionales

#### notifications
```sql
id, start_date, end_date, notification, service_id,
timestamps, soft_deletes
```
Notificaciones temporales por servicio

---

### MÓDULO 4: SISTEMA DE PRECIOS (6 tablas)

#### price_details
```sql
id, product_id, age_stage_id, nationality_id, min_age, max_age,
timestamps, soft_deletes
```
Configuración de estructura de precios

#### prices
```sql
id, quantity, amount, price_detail_id, timestamps, soft_deletes
```
Precios específicos por cantidad de personas

#### availabilities
```sql
id, description, start_date, end_date, color, active_days,
inactive_days, product_id, timestamps, soft_deletes
```
Disponibilidad de productos por fechas y días

#### blockouts
```sql
id, description, start_date, end_date, product_id, color,
timestamps, soft_deletes
```
Bloqueos de fechas (huelgas, feriados, etc.)

#### offers
```sql
id, product_id, value, type, start_date, end_date, color,
description, timestamps, soft_deletes
```
Ofertas y promociones temporales (% o monto)

#### coupons
```sql
id, description, code, discount, discount_type, product_id,
timestamps, soft_deletes
```
Cupones de descuento

---

### MÓDULO 5: RESERVAS Y PAGOS (8 tablas)

#### bookings
```sql
id, created_at_booking, code, timestamps, soft_deletes
```
Reserva principal

#### information_groups
```sql
id, group_code, group_date, timestamps, soft_deletes
```
Agrupador de información de pasajeros

#### booking_details
```sql
id, email, phone, leader_name, booking_id, information_group_id,
timestamps, soft_deletes
```
Detalles de cada reserva

#### service_details
```sql
id, service_date, quantity, total_price, discount, product_id,
timestamps, soft_deletes
```
Detalles del servicio reservado

#### payment_methods
```sql
id, name, description, user_id, timestamps, soft_deletes
```
Métodos de pago disponibles (Tarjeta, PayPal, etc.)

#### payments
```sql
id, amount, description, timestamps, soft_deletes
```
Registros de pagos realizados

#### booking_detail_payment (pivot)
```sql
payment_id, booking_detail_id, timestamps
```
Relación muchos a muchos: Reservas ↔ Pagos

#### booking_detail_service (pivot)
```sql
service_id, booking_detail_id, timestamps
```
Relación muchos a muchos: Reservas ↔ Servicios

---

### MÓDULO 6: RECURSOS Y FORMULARIOS (8 tablas)

#### resources
```sql
id, name, description, price, is_gift, user_id, timestamps, soft_deletes
```
Recursos/extras adicionales (chullo, souvenirs, etc.)
Campos name, description, price son JSON multiidioma

#### field_categories
```sql
id, name, timestamps, soft_deletes
```
Categorías de campos de formulario

#### form_fields
```sql
id, field_name, field_name_attr, field_type, field_placeholder,
field_value, field_values, field_priority, field_category_id,
timestamps, soft_deletes
```
Campos dinámicos de formularios (nombre, email, edad, etc.)

#### booking_informations
```sql
id, information_value, form_field_id, information_group_id,
timestamps, soft_deletes
```
Información recopilada de formularios de reserva

#### product_category (pivot)
```sql
id, product_id, category_id, timestamps
```
Relación muchos a muchos: Productos ↔ Categorías

#### product_gallery (pivot)
```sql
id, gallery_id, product_id, timestamps
```
Relación muchos a muchos: Productos ↔ Galería

#### resource_gallery (pivot)
```sql
id, resource_id, gallery_id, timestamps
```
Relación muchos a muchos: Recursos ↔ Galería

#### product_resource (pivot)
```sql
id, resource_id, product_id, timestamps
```
Relación muchos a muchos: Productos ↔ Recursos

#### product_form_field (pivot)
```sql
id, product_id, form_field_id, timestamps
```
Relación muchos a muchos: Productos ↔ Campos de Formulario

---

## Flujo de Datos Completo

### 1. CREACIÓN DE PRODUCTO

```
1. Crear ServiceCode (agrupador)
2. Crear Service en cada idioma → ServiceCode
3. Crear ProductCode (agrupador)
4. Crear Product → Service + ProductCode
5. Crear Tab (información) → Product
6. Asociar Categories → Product (many-to-many)
7. Asociar Galleries → Product (many-to-many)
8. Crear PriceDetails → Product + AgeStage + Nationality
9. Crear Prices → PriceDetail
10. Crear Availabilities → Product
11. Crear Offers/Coupons → Product (opcional)
```

### 2. PROCESO DE RESERVA

```
1. Cliente selecciona Product
2. Sistema verifica Availability y Blockouts
3. Calcula precio usando PriceDetails + Prices
4. Aplica Offers/Coupons si existen
5. Crea Booking (reserva principal)
6. Crea InformationGroup (para datos de pasajeros)
7. Cliente llena FormFields asociados al Product
8. Crea BookingInformations con los datos
9. Crea BookingDetail → Booking + InformationGroup
10. Crea ServiceDetail → Product (fecha, cantidad, precio)
11. Asocia Service → BookingDetail (many-to-many)
12. Crea Payment
13. Asocia Payment → BookingDetail (many-to-many)
```

### 3. CÁLCULO DE PRECIO

```
Precio Base:
  1. Product → PriceDetails (filtrar por edad y nacionalidad)
  2. PriceDetail → Prices (buscar por cantidad de personas)
  3. Price.amount × cantidad = Subtotal

Descuentos:
  4. Verificar Offers activas en la fecha
  5. Aplicar descuento de Offer (% o monto)
  6. Verificar Coupons válidos
  7. Aplicar descuento de Coupon (% o monto)

Total Final:
  8. Subtotal - Descuentos = Total a Pagar
```

---

## Índices Importantes

Para optimizar consultas, se recomienda crear estos índices adicionales:

```php
// En productos
$table->index('status');
$table->index('service_id');

// En servicios
$table->index('language_id');
$table->index('service_code_id');

// En categorías
$table->index('language_id');
$table->index('category_code_id');

// En reservas
$table->index('code');
$table->index(['created_at', 'status']);

// En disponibilidades
$table->index(['start_date', 'end_date']);

// En ofertas
$table->index(['start_date', 'end_date']);
```

---

## Consultas SQL Comunes

### Obtener producto con toda su información
```php
$product = Product::with([
    'service.language',
    'productCode',
    'tab',
    'additionalTabs',
    'categories.language',
    'galleries',
    'priceDetails.ageStage',
    'priceDetails.nationality',
    'priceDetails.prices',
    'availabilities',
    'blockouts',
    'offers',
    'resources',
    'formFields'
])->find($id);
```

### Verificar disponibilidad de un producto en una fecha
```php
$isAvailable = Product::whereHas('availabilities', function($q) use ($date) {
    $q->where('start_date', '<=', $date)
      ->where('end_date', '>=', $date);
})->whereDoesntHave('blockouts', function($q) use ($date) {
    $q->where('start_date', '<=', $date)
      ->where('end_date', '>=', $date);
})->exists();
```

### Obtener precio para edad y nacionalidad específica
```php
$priceDetail = PriceDetail::where('product_id', $productId)
    ->where('age_stage_id', $ageStageId)
    ->where('nationality_id', $nationalityId)
    ->orWhereNull('nationality_id')
    ->with('prices')
    ->first();

$price = $priceDetail->prices
    ->where('quantity', $quantity)
    ->first();
```

### Obtener reservas con pagos
```php
$bookings = Booking::with([
    'bookingDetails.payments',
    'bookingDetails.services.products',
    'bookingDetails.informationGroup.bookingInformations'
])->get();
```

---

## Validaciones Recomendadas

### Al crear un producto:
- `title`: required, string, max:255
- `service_id`: required, exists:services,id
- `product_code_id`: required, exists:product_codes,id
- `capacity`: nullable, integer, min:1
- `data_requirement`: required, in:1,2,3

### Al crear una reserva:
- `booking_id`: required, exists:bookings,id
- `email`: required, email
- `phone`: nullable, string, max:64
- `leader_name`: required, string, max:128

### Al crear un precio:
- `product_id`: required, exists:products,id
- `age_stage_id`: required, exists:age_stages,id
- `min_age`: required, integer, min:0
- `max_age`: required, integer, gte:min_age
- `amount`: required, numeric, min:0

---

**Última actualización:** 2026-01-04
**Total de tablas:** 38
**Total de relaciones:** 45+
