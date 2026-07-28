#!/bin/bash

# JKTDC Quick Restart — stops all containers and starts them WITHOUT rebuilding.
# Use this for day-to-day starts. Use docker-start.sh only for first-time / full setup.

echo "🛑 Stopping containers..."
docker-compose down

echo "🚀 Starting containers..."
docker-compose up -d

echo ""
docker-compose ps
echo ""
echo "✅ Up:  http://localhost:8080  |  Admin: http://localhost:8080/admin  |  PHPMyAdmin: http://localhost:8081"
