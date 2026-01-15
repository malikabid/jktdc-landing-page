# JKTDC Docker Environment - What's Included

## Overview
Complete Docker-based development environment for the JKTDC Tourism website (frontend) and Admin Panel (backend API).

## Architecture

```
┌─────────────────────────────────────────────────┐
│         docker-compose.yml (Orchestrator)       │
└─────────────────────────────────────────────────┘
                       │
        ┌──────────────┼──────────────┬──────────────┐
        │              │               │              │
    ┌───▼───┐     ┌───▼───┐      ┌───▼────┐    ┌───▼───────┐
    │Apache │     │PHP-FPM│      │ MySQL  │    │PHPMyAdmin │
    │ :8080 │────▶│  :9000│─────▶│ :3306  │◀───│   :8081   │
    └───────┘     └───────┘      └────────┘    └───────────┘
        │              │               │
    Frontend      Admin API        Database
```

## Services Breakdown

### 1. Apache (httpd:2.4)
**Purpose:** Web server for frontend and proxy for admin
- **Port:** 8080
- **Serves:** 
  - Static frontend files (HTML, CSS, JS)
  - Routes `/admin/*` to PHP-FPM
- **Config:** `httpd.conf` with mod_rewrite, mod_deflate
- **Volume Mounts:** Entire project directory

### 2. PHP-FPM (php:8.3-fpm)
**Purpose:** Backend API server (Slim Framework)
- **Port:** 9000 (internal)
- **Framework:** Slim 4.15.1
- **Extensions:** pdo_mysql, mbstring, zip, gd, bcmath
- **Composer:** Auto-installs dependencies on build
- **Volume Mounts:** `admin/` directory
- **Environment:** Reads from `admin/.env`

### 3. MySQL (mysql:8.0)
**Purpose:** Database for authentication & sessions
- **Port:** 3306
- **Database:** `jktdc_admin`
- **User:** `jktdc_admin_user` / `jktdc_admin_pass`
- **Root Password:** `root_password_change_me`
- **Init Scripts:** `admin/docker/mysql-init/01-init.sql`
- **Tables Created:**
  - `users` - Admin users with roles & 2FA
  - `sessions` - JWT sessions
  - `activity_logs` - Audit trail
- **Default Admin:** admin@jktdc.gov.in / admin123

### 4. PHPMyAdmin (phpmyadmin:latest)
**Purpose:** Web-based database management
- **Port:** 8081
- **Access:** http://localhost:8081
- **Credentials:** root / root_password_change_me

## File Structure

```
JKTDC/
├── docker-compose.yml          # Main orchestration file
├── docker-start.sh             # Quick start script
├── README-DOCKER.md            # Full documentation
├── httpd.conf                  # Apache configuration
├── .env.docker                 # Docker environment template
│
├── admin/
│   ├── Dockerfile              # PHP-FPM image definition
│   ├── .dockerignore           # Build exclusions
│   ├── .env.docker             # Admin environment template
│   ├── .env                    # Active environment (auto-created)
│   ├── composer.json           # PHP dependencies
│   ├── public/index.php        # Slim app entry point
│   ├── config/                 # App configuration
│   ├── src/                    # Application code
│   └── docker/
│       └── mysql-init/
│           └── 01-init.sql     # Database initialization
│
└── pub/                        # Frontend static files
```

## Data Persistence

### Persistent (Survives `docker-compose down`)
- **MySQL Data:** `mysql_data` volume
- **Code Changes:** Via volume mounts (instant sync)
- **Uploaded Files:** `admin/storage/uploads/`
- **Logs:** `admin/storage/logs/`

### Ephemeral (Reset on `docker-compose down -v`)
- Container filesystems
- MySQL volume (with `-v` flag only)

## Network Configuration

- **Network Name:** `jktdc-network`
- **Type:** Bridge network
- **DNS:** Services resolve by name (e.g., `mysql`, `php-fpm`)
- **Isolation:** Containers communicate internally, ports exposed to host

## Environment Variables

### docker-compose.yml
```yaml
MYSQL_ROOT_PASSWORD=root_password_change_me
MYSQL_DATABASE=jktdc_admin
MYSQL_USER=jktdc_admin_user
MYSQL_PASSWORD=jktdc_admin_pass
```

### admin/.env.docker
```bash
DB_HOST=mysql                  # Container name
DB_DATABASE=jktdc_admin
DB_USERNAME=jktdc_admin_user
DB_PASSWORD=jktdc_admin_pass
JWT_SECRET=<random-64-chars>
```

## Development Workflow

1. **First Time:**
   ```bash
   ./docker-start.sh
   ```

2. **Daily Work:**
   ```bash
   docker-compose up -d        # Start
   # Make code changes (auto-synced)
   docker-compose logs -f      # View logs
   docker-compose down         # Stop
   ```

3. **After Dependency Changes:**
   ```bash
   docker-compose up -d --build
   ```

## Common Tasks

### Database Operations
```bash
# Backup
docker-compose exec mysql mysqldump -u root -p jktdc_admin > backup.sql

# Restore
docker-compose exec -T mysql mysql -u root -p jktdc_admin < backup.sql

# Reset database
docker-compose down -v
docker-compose up -d
```

### PHP Operations
```bash
# Install new package
docker-compose exec php-fpm composer require vendor/package

# Run tests
docker-compose exec php-fpm vendor/bin/phpunit

# Shell access
docker-compose exec php-fpm bash
```

### Debugging
```bash
# View all logs
docker-compose logs -f

# View specific service
docker-compose logs -f php-fpm

# Check service status
docker-compose ps

# Restart single service
docker-compose restart php-fpm
```

## Security Considerations

### Development (Current)
- Default passwords (MUST CHANGE for production)
- Debug mode enabled
- PHPMyAdmin exposed
- Root access available

### Production Checklist
- [ ] Change all default passwords
- [ ] Generate new JWT secret: `openssl rand -base64 64`
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Remove PHPMyAdmin service
- [ ] Use environment variables (not hardcoded values)
- [ ] Enable SSL/TLS
- [ ] Restrict MySQL to internal network only
- [ ] Set up proper backup strategy

## Performance Optimization

### Build Time
- `.dockerignore` excludes unnecessary files
- Composer dependencies cached in image
- Multi-stage build possible for smaller images

### Runtime
- PHP-FPM optimized for development
- MySQL with InnoDB for transactions
- Apache with mod_deflate for compression
- Volume caching (`:cached` flag on macOS)

## Troubleshooting Quick Reference

| Issue | Solution |
|-------|----------|
| Port conflict | Change ports in docker-compose.yml |
| MySQL won't start | `docker-compose down -v && docker-compose up -d` |
| Composer errors | `docker-compose exec php-fpm composer install` |
| Permission denied | `docker-compose exec php-fpm chmod -R 775 storage` |
| 404 on admin | Check `.env` exists: `docker-compose exec php-fpm cat .env` |
| Slow on macOS | Already using `:cached` volumes for performance |

## What's Next

After Docker setup completes:
1. ✅ All services running
2. ✅ Database tables created
3. ✅ Admin user seeded
4. ⏭️ Implement JWT authentication (Milestone 1.3)
5. ⏭️ Build admin UI (Milestone 2.1)
6. ⏭️ Create CRUD modules (Milestone 2.2-2.5)

## Resources

- **Documentation:** README-DOCKER.md
- **Quick Start:** ./docker-start.sh
- **Project Plan:** admin/PROJECT-PLAN.md
- **Implementation Guide:** admin/IMPLEMENTATION-PLAN.md
