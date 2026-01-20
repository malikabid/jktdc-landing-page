# DOTK - Docker Setup Guide

This Docker setup provides a complete development environment for both the frontend (DOTK Tourism website) and backend (Admin Panel).

## Services

The docker-compose.yml configures the following services:

1. **MySQL 8.0** - Database for admin authentication and sessions
   - Port: 3306
   - Database: `dotk_admin`
   - User: `dotk_admin_user`
   - Password: `dotk_admin_pass`

2. **PHP-FPM 8.3** - Backend API server (Slim Framework)
   - Serves admin panel API endpoints
   - Connected to MySQL

3. **Apache 2.4** - Web server
   - Port: 8080
   - Serves frontend static files
   - Proxies admin API requests to PHP-FPM

4. **PHPMyAdmin** (Optional)
   - Port: 8081
   - Web interface for MySQL database management

## Quick Start

### First Time Setup

1. **Start all services:**
   ```bash
   docker-compose up -d
   ```

2. **Wait for MySQL to initialize** (first run takes ~30 seconds):
   ```bash
   docker-compose logs -f mysql
   # Wait for "ready for connections" message
   ```

3. **Copy Docker environment file for admin:**
   ```bash
   cp admin/.env.docker admin/.env
   ```

4. **Verify services are running:**
   ```bash
   docker-compose ps
   ```

5. **Access the application:**
   - Frontend: http://localhost:8080
   - Admin Panel: http://localhost:8080/admin
   - PHPMyAdmin: http://localhost:8081

### Daily Development

**Start services:**
```bash
docker-compose up -d
```

**Stop services:**
```bash
docker-compose down
```

**View logs:**
```bash
docker-compose logs -f
docker-compose logs -f php-fpm
docker-compose logs -f mysql
```

**Rebuild after code changes:**
```bash
docker-compose up -d --build
```

## Database Access

### Default Admin User
- Email: `admin@dotk.gov.in`
- Password: `admin123`

### Via PHPMyAdmin
1. Open http://localhost:8081
2. Server: `mysql`
3. Username: `root`
4. Password: `root_password_change_me`

### Via MySQL Client
```bash
mysql -h 127.0.0.1 -P 3306 -u dotk_admin_user -p
# Password: dotk_admin_pass
```

### Via Docker
```bash
docker-compose exec mysql mysql -u root -p
# Password: root_password_change_me
```

## Troubleshooting

### Services won't start
```bash
# Check if ports are already in use
lsof -i :8080 -i :3306 -i :8081

# Stop and remove all containers
docker-compose down -v

# Start fresh
docker-compose up -d
```

### Database connection errors
```bash
# Check MySQL is ready
docker-compose exec mysql mysqladmin ping -h localhost

# Check if database exists
docker-compose exec mysql mysql -u root -proot_password_change_me -e "SHOW DATABASES;"

# Re-run initialization script
docker-compose down -v
docker-compose up -d
```

### PHP errors
```bash
# View PHP logs
docker-compose logs -f php-fpm

# Check PHP version
docker-compose exec php-fpm php -v

# Install dependencies manually
docker-compose exec php-fpm composer install
```

### Admin panel not loading
```bash
# Check if .env exists
docker-compose exec php-fpm cat .env

# Copy Docker env file
docker-compose exec php-fpm cp .env.docker .env

# Check admin API health
curl http://localhost:8080/admin/public/health
```

### Reset everything
```bash
# Stop and remove all containers, volumes, and networks
docker-compose down -v

# Remove built images
docker rmi dotk-php-fpm

# Start fresh
docker-compose up -d --build
```

### Port already in use
If ports are already in use, edit `docker-compose.yml` and change:
```yaml
ports:
  - "8081:80"  # Change 8080 to any available port
  - "3307:3306"  # Change 3306 if MySQL port conflicts
  - "8082:80"  # Change 8081 for PHPMyAdmin
```

## File Permissions

If you encounter permission issues:

```bash
# Fix storage permissions
docker-compose exec php-fpm chmod -R 775 storage
docker-compose exec php-fpm chown -R www-data:www-data storage
```

## Production Deployment

⚠️ **Before deploying to production:**

1. **Change all default passwords** in docker-compose.yml:
   - MySQL root password
   - MySQL user password

2. **Update JWT secret** in admin/.env:
   ```bash
   JWT_SECRET=$(openssl rand -base64 64)
   ```

3. **Disable debug mode** in admin/.env:
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   ```

4. **Remove PHPMyAdmin** service from docker-compose.yml

5. **Use environment variables** instead of hardcoded values

## Useful Commands

**Execute commands inside containers:**
```bash
# PHP container
docker-compose exec php-fpm bash
docker-compose exec php-fpm composer install

# MySQL container
docker-compose exec mysql bash
docker-compose exec mysql mysqldump -u root -p dotk_admin > backup.sql
```

**Check container resource usage:**
```bash
docker stats
```

**View network configuration:**
```bash
docker network inspect dotk_dotk-network
```

## Development Workflow

1. Start services: `docker-compose up -d`
2. Make code changes (changes sync automatically via volumes)
3. Test: http://localhost:8080
4. View logs: `docker-compose logs -f`
5. Stop: `docker-compose down`

## Notes

- All changes to files are automatically synced to containers (via volume mounts)
- MySQL data persists in a Docker volume (survives container restarts)
- First startup takes longer due to database initialization
- Composer dependencies are installed during image build

### Changes not reflecting
```bash
docker-compose restart
```

### View Apache error logs
```bash
docker-compose logs apache
```

### Remove and rebuild
```bash
docker-compose down
docker-compose up -d --force-recreate
```
