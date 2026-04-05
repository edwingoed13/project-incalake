# Deploy en Railway - Incalake Full-Stack

## Paso 1: Crear cuenta y proyecto

1. Ir a [railway.com](https://railway.com) → Sign up con GitHub
2. Subscribirse al **Hobby Plan ($5/mes)**
3. Crear un nuevo proyecto: **New Project → Empty Project**

## Paso 2: Agregar MySQL

1. En el proyecto → **+ New** → **Database** → **MySQL**
2. Railway crea la DB automáticamente
3. Click en el servicio MySQL → **Variables** → copia estos valores:
   - `MYSQL_URL` (la usaremos después)
   - `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`

## Paso 3: Deploy Backend (Laravel)

1. **+ New** → **GitHub Repo** → selecciona `project-incalake`
2. En Settings del servicio:
   - **Root Directory:** `backend`
   - **Builder:** Dockerfile
3. En **Variables**, agregar:

```env
APP_NAME=Incalake
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERAR_CON_php_artisan_key:generate
APP_URL=https://backend-production-XXXX.up.railway.app
FRONTEND_URL=https://frontend-production-XXXX.up.railway.app

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

FILESYSTEM_DISK=public
STORAGE_URL=${APP_URL}/storage

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=reservas@incalake.com
MAIL_FROM_NAME="Inca Lake"

CULQI_PUBLIC_KEY=pk_test_xxx
CULQI_SECRET_KEY=sk_test_xxx

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

4. En **Settings** → **Networking** → **Generate Domain** (obtendras URL como `backend-xxx.up.railway.app`)

## Paso 4: Deploy Frontend (Nuxt)

1. **+ New** → **GitHub Repo** → mismo repo `project-incalake`
2. En Settings:
   - **Root Directory:** `frontend`
   - **Builder:** Dockerfile
3. En **Variables**:

```env
NUXT_PUBLIC_API_BASE=https://backend-xxx.up.railway.app/api
NUXT_PUBLIC_STORAGE_BASE=https://backend-xxx.up.railway.app/storage
PORT=3000
```

4. **Generate Domain** (obtendras `frontend-xxx.up.railway.app`)

## Paso 5: Deploy Admin (Nuxt)

1. **+ New** → **GitHub Repo** → mismo repo
2. Settings:
   - **Root Directory:** `admin`
   - **Builder:** Dockerfile
3. Variables:

```env
NUXT_PUBLIC_API_URL=https://backend-xxx.up.railway.app/api
PORT=3000
```

4. **Generate Domain**

## Paso 6: Actualizar URLs cruzadas

Después de obtener los dominios de Railway, actualizar:

1. En **Backend** variables:
   - `APP_URL` = URL del backend
   - `FRONTEND_URL` = URL del frontend

2. En **Frontend** variables:
   - `NUXT_PUBLIC_API_BASE` = URL del backend + `/api`
   - `NUXT_PUBLIC_STORAGE_BASE` = URL del backend + `/storage`

3. En **Admin** variables:
   - `NUXT_PUBLIC_API_URL` = URL del backend + `/api`

## Paso 7: Migrar base de datos

Opción A — Desde Railway CLI:
```bash
railway run php artisan migrate --force
```

Opción B — Se ejecuta automáticamente al deploy (incluido en Dockerfile CMD)

## Paso 8: Importar datos (opcional para test)

Si quieres los tours de tu DB local:

1. Exportar MySQL local:
```bash
mysqldump -u root inc0910d_cms_incalake > backup.sql
```

2. Importar en Railway:
```bash
railway run mysql < backup.sql
```

O usar el plugin de Railway: **MySQL → Connect → Import**

## Custom Domain (opcional)

En cada servicio → Settings → Custom Domain:
- `api.incalake.com` → Backend
- `incalake.com` → Frontend
- `admin.incalake.com` → Admin

Configurar CNAME en Cloudflare/DNS apuntando a Railway.

## Costos estimados

| Servicio | RAM | Costo/mes |
|----------|-----|-----------|
| MySQL | ~100MB | ~$0.50 |
| Backend | ~200MB | ~$1.50 |
| Frontend | ~150MB | ~$1.00 |
| Admin | ~100MB | ~$0.50 |
| **Total** | | **~$3.50** (dentro del crédito de $5) |
