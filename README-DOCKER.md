# Docker Setup for JKTDC Landing Page

## Quick Start

### Start the server
```bash
docker-compose up -d
```

### Stop the server
```bash
docker-compose down
```

### View logs
```bash
docker-compose logs -f
```

### Restart the server
```bash
docker-compose restart
```

## Access the Site

Once started, access the site at:
- **Homepage**: http://localhost:8080/
- **Events**: http://localhost:8080/events.html
- **Organizational Chart**: http://localhost:8080/organizational-chart.html
- **Coming Soon**: http://localhost:8080/coming-soon.html

## Features

- ✅ Apache 2.4 with mod_rewrite enabled
- ✅ .htaccess support for URL rewriting
- ✅ Compression enabled (mod_deflate)
- ✅ Cache control enabled (mod_expires)
- ✅ Auto-restart on crash
- ✅ Live file changes (no rebuild needed)

## Troubleshooting

### Port already in use
If port 8080 is already in use, edit `docker-compose.yml` and change:
```yaml
ports:
  - "8081:80"  # Change 8080 to any available port
```

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
