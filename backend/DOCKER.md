# Docker Setup for Laravel Incalake V12

This Docker setup includes PHP 8.2, MySQL 8.0, Redis 7, and Nginx with a queue worker.

## Quick Start

1. Build and start all services:
   ```bash
   docker-compose up -d --build
   ```

2. Install dependencies:
   ```bash
   docker-compose exec app composer install
   docker-compose exec app npm install
   ```

3. Generate APP_KEY:
   ```bash
   docker-compose exec app php artisan key:generate
   ```

4. Run migrations:
   ```bash
   docker-compose exec app php artisan migrate
   ```

5. Build frontend assets:
   ```bash
   docker-compose exec app npm run build
   ```

6. Link storage:
   ```bash
   docker-compose exec app php artisan storage:link
   ```

7. Access your application:
   - Main App: http://localhost
   - API: http://localhost/api

## Services

- **app**: PHP 8.2 with Laravel (Port 9000 internally)
- **mysql**: MySQL 8.0 (Port 3306)
- **redis**: Redis 7 (Port 6379)
- **nginx**: Nginx web server (Port 80)
- **queue-worker**: Laravel Queue Worker

## Docker Compose Commands

Stop all services:
```bash
docker-compose down
```

View logs:
```bash
 docker-compose logs -f
 docker-compose logs -f app
```

Restart services:
```bash
docker-compose restart
```

Run artisan commands:
```bash
docker-compose exec app php artisan [command]
```

Run tests:
```bash
docker-compose exec app php artisan test
```

Clear caches:
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

## Environment Variables

Update `.env` with:
- `DB_HOST=mysql`
- `DB_DATABASE=laravel`
- `DB_USERNAME=laravel`
- `DB_PASSWORD=secret`
- `REDIS_HOST=redis`
- `REDIS_PORT=6379`
- `CACHE_DRIVER=redis`
- `QUEUE_CONNECTION=redis`

## Troubleshooting

### Permission issues
Inside container, run:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Database connection issues
Ensure MySQL is running and accessible:
```bash
docker-compose ps mysql
docker-compose logs mysql
```

### Queue worker not processing
Check queue status:
```bash
docker-compose logs queue-worker
docker-compose exec app php artisan queue:listen
```

## Production Build

For production, update:
- `APP_ENV=production`
- `APP_DEBUG=false`
- Set proper mail driver
- Set up SSL certificate in Nginx
- Use proper storage driver (S3, etc.)