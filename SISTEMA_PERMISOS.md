# Sistema de Permisos y Roles

## Resumen

Este proyecto implementa un **sistema de permisos basado en roles** simple y eficiente, sin usar la base de datos de Spatie Permission. Los permisos se definen en un archivo de configuración y se asignan según el rol del usuario.

## Roles Disponibles

### 1. **Admin (Administrador)**
- Acceso completo al sistema
- Puede gestionar: tours, reservas, categorías, idiomas, usuarios y configuraciones
- Tiene todos los permisos habilitados

### 2. **Staff**
- Gestión de tours y reservas
- **Puede hacer:**
  - Ver y editar tours
  - Ver y gestionar reservas (confirmar, cancelar)
  - Ver categorías e idiomas (solo lectura)
  - Clonar tours
- **NO puede hacer:**
  - Eliminar tours
  - Gestionar categorías, idiomas o usuarios
  - Acceder a configuraciones

### 3. **Customer (Cliente)**
- Solo acceso al frontend
- No tiene acceso al panel de administración
- Solo puede ver sus propias reservas

## Archivos del Sistema

### Backend

1. **`config/permissions.php`**
   - Define todos los permisos por rol
   - Estructura clara de permisos por recurso
   ```php
   'admin' => [
       'permissions' => [
           'tours.view' => true,
           'tours.create' => true,
           'tours.edit' => true,
           'tours.delete' => true,
           // ...
       ]
   ]
   ```

2. **`app/Traits/HasPermissions.php`**
   - Trait que se usa en el modelo User
   - Métodos disponibles:
     - `hasPermission($permission)` - Verifica un permiso
     - `hasAnyPermission($permissions)` - Verifica si tiene alguno
     - `hasAllPermissions($permissions)` - Verifica si tiene todos
     - `getPermissions()` - Obtiene todos los permisos
     - `isAdmin()`, `isStaff()`, `isCustomer()` - Helpers de rol

3. **`app/Http/Middleware/CheckPermission.php`**
   - Middleware para proteger rutas según permisos
   - Uso: `->middleware('permission:tours.delete')`

4. **`app/Http/Controllers/Api/AuthController.php`**
   - Método `permissions()` retorna los permisos del usuario
   - Endpoint: `GET /api/auth/permissions`

### Frontend

1. **`admin/app/composables/usePermissions.ts`**
   - Composable de Vue para manejar permisos
   - Funciones disponibles:
     ```typescript
     const { hasPermission, isAdmin, isStaff } = usePermissions()

     if (hasPermission('tours.delete')) {
       // Mostrar botón eliminar
     }
     ```

## Cómo Usar

### En el Backend (Laravel)

#### Verificar permisos en controladores:
```php
public function destroy($id) {
    $user = auth()->user();

    if (!$user->hasPermission('tours.delete')) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes permiso para eliminar tours'
        ], 403);
    }

    // Proceder con eliminación...
}
```

#### Proteger rutas con middleware:
```php
// En routes/api.php
Route::delete('/tours/{id}', [TourController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'permission:tours.delete']);
```

#### Usar helpers en blade o lógica:
```php
@if(auth()->user()->isAdmin())
    <button>Configuración Avanzada</button>
@endif

if ($user->isStaff()) {
    // Lógica específica para staff
}
```

### En el Frontend (Nuxt/Vue)

#### Cargar permisos al iniciar sesión:
```typescript
// En el componente de login después de autenticar
import { usePermissions } from '~/composables/usePermissions'

const { loadPermissions } = usePermissions()

async function handleLogin() {
    // ... login logic
    await loadPermissions()
}
```

#### Mostrar/ocultar elementos según permisos:
```vue
<template>
  <div>
    <!-- Solo admin ve esto -->
    <button v-if="isAdmin" @click="deleteUser">
      Eliminar Usuario
    </button>

    <!-- Admin y Staff ven esto -->
    <button v-if="hasPermission('tours.edit')" @click="editTour">
      Editar Tour
    </button>

    <!-- Verificar múltiples permisos -->
    <div v-if="hasAnyPermission(['tours.create', 'tours.edit'])">
      Gestionar Tours
    </div>
  </div>
</template>

<script setup lang="ts">
import { usePermissions } from '~/composables/usePermissions'

const { hasPermission, isAdmin, hasAnyPermission } = usePermissions()
</script>
```

#### En el sidebar del admin:
```vue
<template>
  <nav>
    <!-- Siempre visible para admin y staff -->
    <NuxtLink to="/admin/dashboard">Dashboard</NuxtLink>
    <NuxtLink to="/admin/tours">Tours</NuxtLink>
    <NuxtLink to="/admin/bookings">Reservas</NuxtLink>

    <!-- Solo admin -->
    <NuxtLink v-if="isAdmin" to="/admin/categories">
      Categorías
    </NuxtLink>
    <NuxtLink v-if="isAdmin" to="/admin/languages">
      Idiomas
    </NuxtLink>
    <NuxtLink v-if="isAdmin" to="/admin/users">
      Usuarios
    </NuxtLink>
    <NuxtLink v-if="isAdmin" to="/admin/settings">
      Configuración
    </NuxtLink>
  </nav>
</template>

<script setup lang="ts">
import { usePermissions } from '~/composables/usePermissions'

const { isAdmin, loadPermissions } = usePermissions()

onMounted(() => {
  loadPermissions()
})
</script>
```

## Agregar Nuevos Permisos

### 1. Definir en el backend:
Edita `backend/config/permissions.php`:

```php
'admin' => [
    'permissions' => [
        // ... permisos existentes
        'reports.view' => true,
        'reports.export' => true,
    ]
],
'staff' => [
    'permissions' => [
        // ... permisos existentes
        'reports.view' => true,
        'reports.export' => false, // Staff puede ver pero no exportar
    ]
],
```

### 2. Usar en el frontend:
```vue
<button v-if="hasPermission('reports.export')" @click="exportReport">
  Exportar Reporte
</button>
```

### 3. Proteger ruta API:
```php
Route::get('/reports/export', [ReportController::class, 'export'])
    ->middleware(['auth:sanctum', 'permission:reports.export']);
```

## Ventajas de este Sistema

1. **Simple**: No requiere migraciones ni tablas adicionales
2. **Rápido**: Los permisos se leen de un archivo de configuración (caché)
3. **Flexible**: Fácil agregar o modificar permisos
4. **Centralizado**: Todos los permisos en un solo lugar
5. **Type-safe**: El composable de TypeScript ayuda con autocompletado

## Matriz de Permisos

| Recurso | Admin | Staff | Customer |
|---------|-------|-------|----------|
| **Dashboard** | ✅ Ver + Analytics | ✅ Ver | ❌ |
| **Tours** | ✅ CRUD + Clonar | ✅ Ver, Crear, Editar, Clonar | ❌ |
| **Reservas** | ✅ CRUD + Confirmar/Cancelar | ✅ Ver, Confirmar, Cancelar | ❌ |
| **Categorías** | ✅ CRUD | 👁️ Solo Ver | ❌ |
| **Idiomas** | ✅ CRUD | 👁️ Solo Ver | ❌ |
| **Usuarios** | ✅ CRUD | ❌ | ❌ |
| **Configuración** | ✅ Todo | ❌ | ❌ |

## Ejemplo Completo: Botón Eliminar Tour

### Backend Controller:
```php
public function destroy(Request $request, $id) {
    // El middleware ya verificó el permiso
    $tour = Tour::findOrFail($id);
    $tour->delete();

    return response()->json([
        'success' => true,
        'message' => 'Tour eliminado'
    ]);
}
```

### Backend Route:
```php
Route::delete('/admin/tours/{id}', [TourController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'permission:tours.delete']);
```

### Frontend Component:
```vue
<template>
  <div class="tour-actions">
    <button @click="editTour">Editar</button>

    <!-- Solo admin ve el botón eliminar -->
    <button
      v-if="hasPermission('tours.delete')"
      @click="deleteTour"
      class="btn-danger"
    >
      Eliminar
    </button>
  </div>
</template>

<script setup lang="ts">
import { usePermissions } from '~/composables/usePermissions'

const { hasPermission } = usePermissions()
const config = useRuntimeConfig()

async function deleteTour() {
  if (!confirm('¿Eliminar este tour?')) return

  try {
    const response = await fetch(`${config.public.apiUrl}/admin/tours/${tour.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    })

    if (response.ok) {
      alert('Tour eliminado')
    } else if (response.status === 403) {
      alert('No tienes permiso para eliminar tours')
    }
  } catch (error) {
    console.error(error)
  }
}
</script>
```

## Testing

Para probar el sistema de permisos:

1. **Crea usuarios con diferentes roles:**
   ```sql
   INSERT INTO users (name, email, password, role) VALUES
   ('Admin User', 'admin@test.com', '$2y$10$...', 'admin'),
   ('Staff User', 'staff@test.com', '$2y$10$...', 'staff'),
   ('Customer', 'customer@test.com', '$2y$10$...', 'customer');
   ```

2. **Inicia sesión con cada usuario** y verifica:
   - Admin: Ve todas las opciones del menú
   - Staff: Ve Tours y Bookings, NO ve Users ni Settings
   - Customer: No puede acceder al admin panel

3. **Intenta acciones prohibidas:**
   - Staff intentando eliminar un tour → Error 403
   - Staff intentando acceder a `/admin/users` → Error 403

## Troubleshooting

**Problema:** Los permisos no se actualizan
- **Solución:** Vuelve a cargar permisos: `loadPermissions()`
- O cierra sesión y vuelve a iniciar

**Problema:** Usuario no tiene permisos esperados
- **Solución:** Verifica el rol en la BD: `SELECT role FROM users WHERE id = X`
- Verifica la configuración en `config/permissions.php`

**Problema:** Error 403 en rutas
- **Solución:** Asegúrate que el middleware esté aplicado correctamente
- Verifica que el token de autenticación sea válido
