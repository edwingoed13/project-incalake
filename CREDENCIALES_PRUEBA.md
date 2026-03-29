# Credenciales de Prueba - Sistema de Permisos

## Usuarios del Sistema

### 👑 Administrador (Acceso Completo)
- **Email:** `admin@incalake.com`
- **Contraseña:** `password`
- **Rol:** `admin`
- **Permisos:** Acceso completo a todo el sistema

**Puede hacer:**
- ✅ Ver y gestionar Tours (crear, editar, eliminar, clonar)
- ✅ Ver y gestionar Reservas (confirmar, cancelar, eliminar)
- ✅ Ver y gestionar Categorías (crear, editar, eliminar)
- ✅ Ver y gestionar Idiomas (crear, editar, eliminar)
- ✅ Ver y gestionar Usuarios (crear, editar, eliminar)
- ✅ Acceder a todas las Configuraciones
- ✅ Ver Analytics completos

---

### 👤 Staff (Acceso Limitado)
- **Email:** `staff@incalake.com`
- **Contraseña:** `password`
- **Rol:** `staff`
- **Permisos:** Gestión de tours y reservas únicamente

**Puede hacer:**
- ✅ Ver y gestionar Tours (crear, editar, clonar)
- ✅ Ver y gestionar Reservas (confirmar, cancelar)
- ✅ Ver Categorías (solo lectura)
- ✅ Ver Idiomas (solo lectura)

**NO puede hacer:**
- ❌ Eliminar Tours
- ❌ Eliminar Reservas
- ❌ Gestionar Categorías (crear/editar/eliminar)
- ❌ Gestionar Idiomas (crear/editar/eliminar)
- ❌ Ver o gestionar Usuarios
- ❌ Acceder a Configuraciones
- ❌ Ver Analytics avanzados

---

## Pruebas Recomendadas

### 1. Probar con Admin:
1. Inicia sesión con `admin@incalake.com` / `password`
2. Verifica que ves todas las opciones del menú:
   - Dashboard
   - Tours
   - Reservas (Bookings)
   - Categorías
   - Idiomas
   - Usuarios
   - Configuración
3. Verifica que puedes:
   - Crear, editar y **eliminar** tours
   - Crear, editar y **eliminar** categorías
   - Crear, editar y **eliminar** usuarios

### 2. Probar con Staff:
1. Cierra sesión del admin
2. Inicia sesión con `staff@incalake.com` / `password`
3. Verifica que el menú muestra SOLO:
   - Dashboard
   - Tours
   - Reservas (Bookings)
   - Categorías (en menú pero solo lectura)
   - Idiomas (en menú pero solo lectura)
4. Verifica que **NO** ves:
   - ❌ Usuarios
   - ❌ Configuración
5. Intenta acceder a páginas prohibidas:
   - Ve a `/admin/users` manualmente → Debería redirigir al dashboard
   - Ve a `/admin/settings` manualmente → Debería redirigir al dashboard
6. Verifica que puedes:
   - ✅ Crear y editar tours
   - ✅ Ver y gestionar reservas
7. Verifica que **NO puedes**:
   - ❌ Eliminar tours (el botón NO debe aparecer)
   - ❌ Crear/editar categorías (página de solo lectura o sin botones)

### 3. Probar Protección de API:
1. Con usuario Staff, abre la consola del navegador
2. Intenta eliminar un tour:
   ```javascript
   fetch('http://localhost:8001/api/admin/tours/1', {
     method: 'DELETE',
     headers: {
       'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
     }
   }).then(r => r.json()).then(console.log)
   ```
   **Resultado esperado:** Error 403 "No tienes permisos"

---

## Cómo Cambiar el Rol de un Usuario

### Desde phpMyAdmin:
```sql
-- Ver todos los usuarios
SELECT id, name, email, role FROM users;

-- Cambiar un usuario a admin
UPDATE users SET role = 'admin' WHERE email = 'usuario@ejemplo.com';

-- Cambiar un usuario a staff
UPDATE users SET role = 'staff' WHERE email = 'usuario@ejemplo.com';

-- Cambiar un usuario a customer
UPDATE users SET role = 'customer' WHERE email = 'usuario@ejemplo.com';
```

### Desde la interfaz de Admin:
1. Inicia sesión como Admin
2. Ve a "Usuarios y Roles" (solo admin puede ver esto)
3. Edita cualquier usuario
4. Cambia el rol en el selector desplegable
5. Guarda los cambios

---

## Crear Nuevos Usuarios

### Opción 1: Desde la interfaz (solo Admin):
1. Inicia sesión como Admin
2. Ve a `/admin/users`
3. Click en "Nuevo Usuario"
4. Completa el formulario:
   - Nombre
   - Email
   - Contraseña
   - Rol (admin/staff/customer)
5. Guarda

### Opción 2: Desde SQL:
```sql
-- Nota: La contraseña 'password' hasheada con bcrypt
INSERT INTO users (name, email, password, role, created_at, updated_at)
VALUES (
  'Nuevo Usuario',
  'nuevo@incalake.com',
  '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqyPl.r4Z9W2IMhxJmyKxZS',
  'staff',
  NOW(),
  NOW()
);
```

---

## Solución de Problemas

### Problema: No veo los permisos actualizados
**Solución:**
1. Cierra sesión
2. Vuelve a iniciar sesión
3. Los permisos se cargan al iniciar sesión

### Problema: Usuario Staff ve opciones de Admin
**Solución:**
1. Verifica el rol en la base de datos:
   ```sql
   SELECT id, name, email, role FROM users WHERE email = 'staff@incalake.com';
   ```
2. Debe ser `staff`, no `admin`
3. Si está mal, corrígelo:
   ```sql
   UPDATE users SET role = 'staff' WHERE email = 'staff@incalake.com';
   ```
4. Cierra sesión y vuelve a entrar

### Problema: Error 403 en todas las peticiones
**Solución:**
1. Verifica que el token esté guardado:
   ```javascript
   console.log(localStorage.getItem('auth_token'))
   ```
2. Si no hay token, vuelve a iniciar sesión
3. Verifica que los permisos estén cargados:
   ```javascript
   console.log(localStorage.getItem('auth_permissions'))
   ```

---

## Notas Importantes

- 🔒 La contraseña de todos los usuarios de prueba es: **`password`**
- 🔄 Los permisos se cargan automáticamente al iniciar sesión
- 💾 Los permisos se guardan en localStorage para persistencia
- 🚫 El backend valida TODOS los permisos, no solo el frontend
- ⚠️ Un usuario Staff no puede eliminar su propia cuenta ni la de otros
- 👮 El middleware de permisos protege las rutas en el backend

---

## Matriz de Permisos Actual

| Recurso | Admin | Staff | Customer |
|---------|-------|-------|----------|
| **Dashboard** | ✅ Full | ✅ Basic | ❌ |
| **Tours** | ✅ CRUD + Clone | ✅ CRU + Clone | ❌ |
| **Reservas** | ✅ CRUD + Confirm/Cancel | ✅ CRU + Confirm/Cancel | ❌ |
| **Categorías** | ✅ CRUD | 👁️ Read Only | ❌ |
| **Idiomas** | ✅ CRUD | 👁️ Read Only | ❌ |
| **Usuarios** | ✅ CRUD | ❌ | ❌ |
| **Configuración** | ✅ Full | ❌ | ❌ |

**Leyenda:**
- ✅ = Acceso completo
- 👁️ = Solo lectura
- ❌ = Sin acceso
- CRUD = Create, Read, Update, Delete
- CRU = Create, Read, Update (sin Delete)
