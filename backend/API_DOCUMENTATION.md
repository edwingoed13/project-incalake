# API REST - Incalake Laravel 12

Documentacion completa de la API REST para el proyecto Incalake.

## Informacion General

- **Base URL**: `http://your-domain.com/api`
- **Formato de respuesta**: JSON
- **Autenticacion**: Laravel Sanctum (Token-based)
- **Codificacion**: UTF-8

## Tabla de Contenidos

1. [Autenticacion](#autenticacion)
2. [Productos](#productos)
3. [Categorias](#categorias)
4. [Reservas](#reservas)
5. [Idiomas](#idiomas)
6. [Errores](#errores)

---

## Autenticacion

### Registro de Usuario

**Endpoint**: `POST /api/auth/register`

**Acceso**: Publico

**Request Body**:
```json
{
    "name": "Juan Perez",
    "email": "juan@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response Success (201)**:
```json
{
    "success": true,
    "message": "Usuario registrado exitosamente.",
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Perez",
            "email": "juan@example.com"
        },
        "token": "1|abc123def456...",
        "token_type": "Bearer"
    }
}
```

---

## Archivos Creados

La siguiente es la lista de todos los archivos creados para la API:

### Form Requests
- `app/Http/Requests/LoginRequest.php`
- `app/Http/Requests/RegisterRequest.php`
- `app/Http/Requests/StoreProductRequest.php`
- `app/Http/Requests/UpdateProductRequest.php`
- `app/Http/Requests/StoreBookingRequest.php`
- `app/Http/Requests/StoreCategoryRequest.php`
- `app/Http/Requests/UpdateCategoryRequest.php`

### API Resources
- `app/Http/Resources/UserResource.php`
- `app/Http/Resources/ProductResource.php`
- `app/Http/Resources/CategoryResource.php`
- `app/Http/Resources/BookingResource.php`
- `app/Http/Resources/BookingDetailResource.php`
- `app/Http/Resources/LanguageResource.php`
- `app/Http/Resources/ServiceResource.php`
- `app/Http/Resources/PriceDetailResource.php`

### Controllers
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/Api/ProductController.php`
- `app/Http/Controllers/Api/CategoryController.php`
- `app/Http/Controllers/Api/BookingController.php`
- `app/Http/Controllers/Api/LanguageController.php`

### Routes
- `routes/api.php`
- `bootstrap/app.php` (actualizado para incluir rutas API)

