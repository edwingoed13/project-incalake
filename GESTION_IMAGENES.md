# Gestión de Imágenes del Proyecto

## 📁 Estructura de Almacenamiento

Las imágenes del proyecto están organizadas de la siguiente manera:

```
incalake-full-stack/
└── backend/
    ├── public/
    │   └── storage/ (enlace simbólico → storage/app/public)
    └── storage/
        └── app/
            └── public/
                ├── tours/
                │   ├── 3/
                │   │   └── *.jpeg (imágenes del tour ID 3)
                │   ├── 4/
                │   │   └── *.jpeg (imágenes del tour ID 4)
                │   └── ...
                └── temp/ (archivos temporales)
```

## ✅ Estado Actual

- **✅ Las imágenes están DENTRO del proyecto** en `backend/storage/app/public/`
- **✅ Las imágenes SE INCLUYEN en Git** (no están ignoradas en .gitignore)
- **✅ El enlace simbólico existe** (`public/storage` → `storage/app/public`)
- **✅ Total de imágenes**: 12 archivos en la carpeta tours

## 🔗 Cómo Funcionan las Imágenes

### 1. Almacenamiento
- Las imágenes se guardan en: `backend/storage/app/public/tours/{tour_id}/`
- Cada tour tiene su propia carpeta con su ID
- Los nombres de archivo incluyen un hash único para evitar conflictos

### 2. Acceso desde la Web
- URL pública: `http://localhost:8001/storage/tours/{tour_id}/{filename}.jpeg`
- El enlace simbólico `public/storage` permite el acceso web a las imágenes

### 3. Base de Datos
- La tabla `tour_media_gallery` guarda las rutas relativas
- Ejemplo: `tours/3/69726349ba820_extreme_close_up.jpeg`
- Incluye metadatos: `alt_text`, `title_text`, `description`, `order`

## 🚀 Configuración para Nuevos Desarrolladores

Cuando alguien clone el repositorio, debe:

### 1. Clonar el repositorio (las imágenes ya vienen incluidas)
```bash
git clone https://github.com/tuusuario/incalake-full-stack.git
cd incalake-full-stack
```

### 2. Configurar el backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

### 3. Crear el enlace simbólico de storage
```bash
# IMPORTANTE: Ejecutar este comando
php artisan storage:link
```

Este comando crea el enlace simbólico: `public/storage` → `storage/app/public`

### 4. Verificar permisos (Linux/Mac)
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### 5. Importar la base de datos
```sql
-- El archivo SQL incluye las rutas de las imágenes
mysql -u root -p incalake < database.sql
```

## 📝 Tabla de Base de Datos

### tour_media_gallery
```sql
CREATE TABLE `tour_media_gallery` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` bigint(20) unsigned NOT NULL,
  `language_id` bigint(20) unsigned DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `title_text` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tour_id` (`tour_id`),
  KEY `language_id` (`language_id`)
);
```

## 🔧 Comandos Útiles

### Ver todas las imágenes
```bash
find backend/storage/app/public/tours -type f \( -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" \)
```

### Contar imágenes
```bash
find backend/storage/app/public/tours -type f \( -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" \) | wc -l
```

### Verificar enlace simbólico
```bash
ls -la backend/public/ | grep storage
```

### Recrear enlace simbólico (si es necesario)
```bash
cd backend
rm -rf public/storage
php artisan storage:link
```

## ⚠️ Problemas Comunes

### Problema 1: Las imágenes no se ven (404)
**Solución**: Crear el enlace simbólico
```bash
cd backend
php artisan storage:link
```

### Problema 2: Error de permisos al subir imágenes
**Solución**: Dar permisos a la carpeta storage
```bash
chmod -R 775 backend/storage
```

### Problema 3: Imágenes perdidas después de clonar
**Causa**: Las imágenes no se subieron a Git
**Solución**:
1. Verificar que las imágenes existan en `backend/storage/app/public/tours/`
2. Si no están, pedirlas al desarrollador original
3. Asegurarse de hacer commit de las imágenes:
```bash
git add backend/storage/app/public/tours/
git commit -m "Add tour images"
git push
```

## 📦 Incluir Imágenes en Git

Para asegurarte de que las imágenes se incluyan en Git:

### 1. Verificar que NO estén ignoradas
```bash
# Ver el .gitignore
cat backend/.gitignore | grep storage
# Debe mostrar:
# /public/storage (enlace simbólico - OK ignorarlo)
# /storage/*.key (archivos .key - OK ignorarlos)
# NO debe ignorar: /storage/app/public
```

### 2. Agregar las imágenes a Git
```bash
cd incalake-full-stack
git add backend/storage/app/public/tours/
git status # Verificar que aparezcan los archivos
git commit -m "Add tour images to repository"
git push
```

### 3. Verificar en GitHub
- Ve a tu repositorio en GitHub
- Navega a: `backend/storage/app/public/tours/`
- Deberías ver las carpetas con las imágenes

## 💡 Recomendaciones

1. **Imágenes pequeñas**: Optimiza las imágenes antes de subirlas (< 500KB)
2. **Formato recomendado**: JPEG para fotos, PNG para logos
3. **Nombres descriptivos**: Usa nombres que describan el contenido
4. **Backup**: Mantén un backup de las imágenes fuera del proyecto

## 📊 Estado del Proyecto

| Componente | Estado | Ubicación |
|------------|--------|-----------|
| Imágenes de Tours | ✅ Incluidas | `backend/storage/app/public/tours/` |
| Enlace Simbólico | ✅ Creado | `backend/public/storage` |
| Base de Datos | ✅ Configurada | Tabla `tour_media_gallery` |
| Git | ✅ Configurado | Imágenes incluidas en repositorio |

## 🔄 Proceso de Migración Completo

Si necesitas mover el proyecto a otro servidor:

1. **Exportar la base de datos**
```bash
mysqldump -u root -p inc0910d_cms_incalake > database.sql
```

2. **Comprimir el proyecto (con imágenes)**
```bash
tar -czf incalake-full-stack.tar.gz incalake-full-stack/
```

3. **En el nuevo servidor**
```bash
# Descomprimir
tar -xzf incalake-full-stack.tar.gz

# Instalar dependencias
cd incalake-full-stack/backend
composer install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Crear enlace simbólico
php artisan storage:link

# Importar base de datos
mysql -u root -p incalake < database.sql
```

---

**NOTA IMPORTANTE**: Las imágenes ya están incluidas en el proyecto y se subirán a Git. No necesitas moverlas a otra ubicación. El sistema está correctamente configurado para que cualquier persona que clone el repositorio tenga acceso a todas las imágenes.