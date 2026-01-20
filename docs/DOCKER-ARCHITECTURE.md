# JKTDC Docker Architecture Diagram

## High-Level Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Host Machine (macOS)                        │
│                                                                     │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │                    Docker Desktop                              │ │
│  │                                                                │ │
│  │  ┌──────────────────────────────────────────────────────────┐ │ │
│  │  │            jktdc-network (Bridge Network)                 │ │ │
│  │  │                                                           │ │ │
│  │  │  ┌─────────────┐    ┌──────────────┐    ┌─────────────┐ │ │ │
│  │  │  │   Apache    │    │   PHP-FPM    │    │   MySQL     │ │ │ │
│  │  │  │  :80 (8080) │───▶│   :9000      │───▶│   :3306     │ │ │ │
│  │  │  │             │    │              │    │             │ │ │ │
│  │  │  │ • Frontend  │    │ • Slim API   │    │ • jktdc_    │ │ │ │
│  │  │  │ • .htaccess │    │ • JWT Auth   │    │   admin DB  │ │ │ │
│  │  │  │ • Proxy     │    │ • Eloquent   │    │ • users     │ │ │ │
│  │  │  └─────────────┘    └──────────────┘    └─────────────┘ │ │ │
│  │  │         │                    │                  ▲         │ │ │
│  │  │         │                    │                  │         │ │ │
│  │  │         │                    │           ┌──────┴──────┐  │ │ │
│  │  │         │                    │           │ PHPMyAdmin  │  │ │ │
│  │  │         │                    │           │  :80 (8081) │  │ │ │
│  │  │         │                    │           │             │  │ │ │
│  │  │         │                    │           │ • DB Admin  │  │ │ │
│  │  │         │                    │           └─────────────┘  │ │ │
│  │  │         │                    │                            │ │ │
│  │  └─────────┼────────────────────┼────────────────────────────┘ │ │
│  │            │                    │                              │ │
│  │     ┌──────▼────────────────────▼──────────────────┐           │ │
│  │     │         Volume Mounts (Cached)               │           │ │
│  │     │  • JKTDC/        → Apache htdocs             │           │ │
│  │     │  • admin/        → PHP working dir           │           │ │
│  │     │  • mysql_data    → Persistent storage        │           │ │
│  │     └──────────────────────────────────────────────┘           │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                     │
│  Ports Exposed to Host:                                            │
│    • 8080 → Apache (Frontend + Admin)                              │
│    • 3306 → MySQL                                                  │
│    • 8081 → PHPMyAdmin                                             │
└─────────────────────────────────────────────────────────────────────┘
```

## Request Flow

### Frontend Request (Static Files)
```
Browser                Apache              File System
   │                      │                     │
   │──GET /index.html────▶│                     │
   │                      │──Read file─────────▶│
   │                      │◀───Return file──────│
   │◀─200 OK (HTML)───────│                     │
   │                      │                     │
```

### Admin API Request (Dynamic)
```
Browser       Apache        PHP-FPM         MySQL        JSON Files
   │             │             │              │               │
   │─POST────────▶│             │              │               │
   │ /admin/     │             │              │               │
   │ /login      │             │              │               │
   │             │─Forward─────▶│              │               │
   │             │ (FastCGI)   │              │               │
   │             │             │─Verify JWT──▶│               │
   │             │             │◀─User data───│               │
   │             │             │              │               │
   │             │             │─Read notifications.json──────▶│
   │             │             │◀─Data────────────────────────│
   │             │◀─Response───│              │               │
   │◀─200 OK─────│             │              │               │
   │  (JSON)     │             │              │               │
```

## Data Flow

### Authentication Flow
```
1. User Login
   ┌──────┐
   │Client│
   └───┬──┘
       │ POST /admin/public/login
       │ {email, password}
       ▼
   ┌────────────┐
   │  PHP-FPM   │
   │ (Slim API) │
   └─────┬──────┘
         │ Query user
         ▼
   ┌──────────┐
   │  MySQL   │
   │  users   │
   └─────┬────┘
         │ Return user
         ▼
   ┌────────────┐
   │  PHP-FPM   │
   │ Generate   │
   │ JWT Token  │
   └─────┬──────┘
         │ Store session
         ▼
   ┌──────────┐
   │  MySQL   │
   │ sessions │
   └─────┬────┘
         │ Success
         ▼
   ┌──────┐
   │Client│
   │ Gets │
   │ JWT  │
   └──────┘

2. Authenticated Request
   ┌──────┐
   │Client│
   └───┬──┘
       │ GET /admin/public/notifications
       │ Authorization: Bearer <token>
       ▼
   ┌────────────┐
   │  PHP-FPM   │
   │  Verify    │
   │  JWT       │
   └─────┬──────┘
         │ Check session
         ▼
   ┌──────────┐
   │  MySQL   │
   │ sessions │
   └─────┬────┘
         │ Valid
         ▼
   ┌────────────┐
   │  PHP-FPM   │
   │ Read JSON  │
   └─────┬──────┘
         │ 
         ▼
   ┌─────────────────┐
   │ pub/data/       │
   │ notifications   │
   │ .json           │
   └─────┬───────────┘
         │ Return data
         ▼
   ┌──────┐
   │Client│
   └──────┘
```

## Service Dependencies

```
mysql (Healthcheck)
  │
  ├─▶ php-fpm (depends_on)
  │     │
  │     └─▶ apache (depends_on)
  │
  └─▶ phpmyadmin (depends_on)
```

**Startup Order:**
1. MySQL starts first
2. MySQL healthcheck runs (waits for "ready for connections")
3. PHP-FPM starts (after MySQL is healthy)
4. PHPMyAdmin starts (after MySQL is healthy)
5. Apache starts (after PHP-FPM is ready)

## Volume Mounts

```
Host                                Container
────────────────────────────────────────────────────────────
/Users/.../JKTDC/                 → /usr/local/apache2/htdocs/
  ├── index.html                    (Apache reads directly)
  ├── pub/                          
  │   ├── css/
  │   ├── js/
  │   └── images/
  └── admin/                      → /var/www/admin/
      ├── public/index.php          (PHP-FPM executes)
      ├── config/
      ├── src/
      └── storage/                  (Writable by www-data)

mysql_data (Docker Volume)        → /var/lib/mysql/
  └── jktdc_admin/                  (Persistent database files)
```

## Network Communication

**Internal (Container-to-Container):**
- Uses service names: `mysql`, `php-fpm`, `apache`
- Example: PHP connects to `mysql:3306`
- All on `jktdc-network` bridge

**External (Host-to-Container):**
- Uses exposed ports on `localhost`
- 8080 → Apache
- 3306 → MySQL
- 8081 → PHPMyAdmin

## Storage Strategy

### Persistent (Survives restarts)
```
mysql_data volume
  └── MySQL data files (InnoDB)
      ├── users.ibd
      ├── sessions.ibd
      └── activity_logs.ibd

Host filesystem (via volume mounts)
  └── admin/storage/
      ├── logs/
      ├── cache/
      ├── uploads/
      └── backups/

pub/data/ (JSON files)
  ├── notifications.json
  ├── events.json
  └── tenders.json
```

### Ephemeral (Reset on rebuild)
```
Container filesystems
  ├── PHP-FPM /tmp/
  ├── Apache /var/log/
  └── MySQL /var/run/
```

## Resource Allocation

**Default Resources:**
```
┌────────────┬──────────┬──────────┬─────────┐
│ Service    │ CPU      │ Memory   │ Disk    │
├────────────┼──────────┼──────────┼─────────┤
│ Apache     │ ~0.1 CPU │ ~50MB    │ Minimal │
│ PHP-FPM    │ ~0.3 CPU │ ~128MB   │ ~200MB  │
│ MySQL      │ ~0.2 CPU │ ~512MB   │ ~1GB    │
│ PHPMyAdmin │ ~0.1 CPU │ ~64MB    │ Minimal │
├────────────┼──────────┼──────────┼─────────┤
│ Total      │ ~0.7 CPU │ ~754MB   │ ~1.5GB  │
└────────────┴──────────┴──────────┴─────────┘
```

## Environment Configuration

```
┌─────────────────┬──────────────────┬──────────────────────┐
│ Variable        │ Set In           │ Used By              │
├─────────────────┼──────────────────┼──────────────────────┤
│ DB_CONNECTION   │ docker-compose   │ PHP-FPM              │
│ DB_HOST         │ docker-compose   │ PHP-FPM              │
│ DB_DATABASE     │ docker-compose   │ PHP-FPM, MySQL       │
│ DB_USERNAME     │ docker-compose   │ PHP-FPM, MySQL       │
│ DB_PASSWORD     │ docker-compose   │ PHP-FPM, MySQL       │
│ MYSQL_ROOT_PWD  │ docker-compose   │ MySQL                │
│ JWT_SECRET      │ admin/.env       │ PHP-FPM              │
│ APP_ENV         │ admin/.env       │ PHP-FPM              │
└─────────────────┴──────────────────┴──────────────────────┘
```

---

**Legend:**
- `→` Data flow direction
- `──▶` Network request
- `◀──` Network response
- `│` Dependency relationship
- `┌──┐` Service/Component boundary
