# JKTDC Docker - Quick Reference Card

## 🚀 Quick Start
```bash
./docker-start.sh           # First time setup (automatic)
docker-compose up -d        # Start services
docker-compose down         # Stop services
```

## 🌐 Access URLs
| Service | URL | Credentials |
|---------|-----|-------------|
| Frontend | http://localhost:8080 | - |
| Admin API | http://localhost:8080/admin/public/api | - |
| Health Check | http://localhost:8080/admin/public/health | - |
| PHPMyAdmin | http://localhost:8081 | root / root_password_change_me |

## 🔑 Default Credentials
**Admin User:**
- Email: `admin@jktdc.gov.in`
- Password: `admin123`

**MySQL:**
- Database: `jktdc_admin`
- User: `jktdc_admin_user`
- Password: `jktdc_admin_pass`

## 📋 Common Commands

### Service Management
```bash
docker-compose up -d          # Start all services
docker-compose down           # Stop all services
docker-compose down -v        # Stop + delete volumes (reset DB)
docker-compose restart        # Restart all services
docker-compose restart php-fpm # Restart specific service
docker-compose ps             # Show running services
docker-compose up -d --build  # Rebuild and start
```

### Logs
```bash
docker-compose logs -f                # All logs (follow)
docker-compose logs -f php-fpm        # PHP logs only
docker-compose logs -f mysql          # MySQL logs only
docker-compose logs --tail=100 apache # Last 100 lines
```

### Execute Commands
```bash
# PHP Container
docker-compose exec php-fpm bash              # Shell access
docker-compose exec php-fpm composer install  # Install packages
docker-compose exec php-fpm php -v            # Check PHP version

# MySQL Container
docker-compose exec mysql bash                         # Shell access
docker-compose exec mysql mysql -u root -p             # MySQL CLI
docker-compose exec mysql mysqldump -u root -p > backup.sql # Backup
```

### Database Operations
```bash
# Connect to MySQL
docker-compose exec mysql mysql -u jktdc_admin_user -pjktdc_admin_pass jktdc_admin

# Backup database
docker-compose exec mysql mysqldump -u root -proot_password_change_me jktdc_admin > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u root -proot_password_change_me jktdc_admin < backup.sql

# Show tables
docker-compose exec mysql mysql -u root -proot_password_change_me -e "SHOW TABLES;" jktdc_admin

# Count users
docker-compose exec mysql mysql -u root -proot_password_change_me -e "SELECT COUNT(*) FROM users;" jktdc_admin
```

### Troubleshooting
```bash
# Check service status
docker-compose ps

# Check resource usage
docker stats

# View container details
docker inspect jktdc-php-fpm

# Check network
docker network inspect jktdc_jktdc-network

# Clean up everything
docker-compose down -v
docker system prune -a

# Rebuild specific service
docker-compose up -d --build php-fpm
```

## 🔧 File Locations

| Purpose | Path |
|---------|------|
| Main config | `docker-compose.yml` |
| PHP Dockerfile | `admin/Dockerfile` |
| Admin env | `admin/.env` (auto-created from `.env.docker`) |
| MySQL init | `admin/docker/mysql-init/01-init.sql` |
| Apache config | `httpd.conf` |
| Quick start | `docker-start.sh` |

## 📊 Service Ports

| Container | Internal Port | External Port | Access |
|-----------|---------------|---------------|--------|
| Apache | 80 | 8080 | localhost:8080 |
| PHP-FPM | 9000 | - | Internal only |
| MySQL | 3306 | 3306 | localhost:3306 |
| PHPMyAdmin | 80 | 8081 | localhost:8081 |

## 🐛 Quick Fixes

### Port already in use
```bash
# Check what's using port
lsof -i :8080

# Change port in docker-compose.yml
# ports: - "8081:80"  # instead of 8080
```

### Services won't start
```bash
docker-compose down -v
docker-compose up -d --build
```

### Admin .env missing
```bash
docker-compose exec php-fpm cp .env.docker .env
docker-compose restart php-fpm
```

### Permission errors
```bash
docker-compose exec php-fpm chmod -R 775 storage
docker-compose exec php-fpm chown -R www-data:www-data storage
```

### MySQL not ready
```bash
# Wait for initialization
docker-compose logs -f mysql | grep "ready for connections"

# Takes ~30 seconds on first run
```

## 🧪 Test Endpoints

```bash
# Frontend
curl http://localhost:8080

# Admin health check
curl http://localhost:8080/admin/public/health

# API info
curl http://localhost:8080/admin/public/api

# Expected health response:
# {"status":"ok","timestamp":"...","service":"JKTDC Admin API","version":"1.0.0"}
```

## 📚 Documentation

- **Full Guide:** [README-DOCKER.md](../README-DOCKER.md)
- **Architecture:** [docs/DOCKER-ARCHITECTURE.md](DOCKER-ARCHITECTURE.md)
- **Setup Guide:** [docs/DOCKER-SETUP-COMPLETE.md](DOCKER-SETUP-COMPLETE.md)
- **Project Plan:** [admin/PROJECT-PLAN.md](../admin/PROJECT-PLAN.md)

## ⚠️ Production Checklist

Before deploying to production:

- [ ] Change MySQL root password
- [ ] Change MySQL user password
- [ ] Generate new JWT secret: `openssl rand -base64 64`
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Remove PHPMyAdmin service
- [ ] Enable SSL/TLS
- [ ] Set up automated backups
- [ ] Restrict MySQL to internal network
- [ ] Use secrets management (not hardcoded values)

---

**Need more help?** See [README-DOCKER.md](../README-DOCKER.md) for detailed instructions.
