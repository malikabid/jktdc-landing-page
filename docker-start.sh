#!/bin/bash

# JKTDC Docker Quick Start Script
set -e

echo "========================================"
echo "JKTDC Docker Environment Setup"
echo "========================================"
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop first."
    exit 1
fi

echo "✅ Docker is running"
echo ""

# Check if docker-compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ docker-compose is not installed"
    echo "Install: brew install docker-compose"
    exit 1
fi

echo "✅ docker-compose is installed"
echo ""

# Stop any existing containers
echo "🛑 Stopping existing containers..."
docker-compose down 2>/dev/null || true
echo ""

# Build and start services
echo "🏗️  Building and starting services..."
docker-compose up -d --build
echo ""

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to initialize (this may take 30-60 seconds on first run)..."
until docker-compose exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; do
    echo "   MySQL is initializing..."
    sleep 5
done
echo "✅ MySQL is ready"
echo ""

# Copy environment file for admin if it doesn't exist
if [ ! -f admin/.env ]; then
    echo "📝 Creating admin/.env from .env.docker..."
    cp admin/.env.docker admin/.env
    echo "✅ Created admin/.env"
else
    echo "✅ admin/.env already exists"
fi
echo ""

# Check if composer dependencies are installed
if [ ! -d admin/vendor ]; then
    echo "📦 Installing Composer dependencies..."
    docker-compose exec -T php-fpm composer install --no-interaction
    echo "✅ Composer dependencies installed"
else
    echo "✅ Composer dependencies already installed"
fi
echo ""

# Show status
echo "🔍 Checking service status..."
docker-compose ps
echo ""

# Test connections
echo "🧪 Testing services..."

# Test MySQL
if docker-compose exec -T mysql mysql -u dotk_admin_user -pdotk_admin_pass -e "SELECT 1;" dotk_admin > /dev/null 2>&1; then
    echo "✅ MySQL connection successful"
else
    echo "⚠️  MySQL connection failed (may need more time to initialize)"
fi

# Test Apache
if curl -s http://localhost:8080 > /dev/null 2>&1; then
    echo "✅ Apache frontend accessible"
else
    echo "⚠️  Apache frontend not responding"
fi

# Test Admin API
if curl -s http://localhost:8080/admin/public/health > /dev/null 2>&1; then
    echo "✅ Admin API accessible"
else
    echo "⚠️  Admin API not responding"
fi

echo ""
echo "========================================"
echo "✅ Setup Complete!"
echo "========================================"
echo ""
echo "Access your application:"
echo "  🌐 Frontend:    http://localhost:8080"
echo "  🔐 Admin Panel: http://localhost:8080/admin"
echo "  💾 PHPMyAdmin:  http://localhost:8081"
echo ""
echo "Default admin credentials:"
echo "  Email:    admin@dotk.gov.in"
echo "  Password: admin123"
echo ""
echo "Useful commands:"
echo "  View logs:     docker-compose logs -f"
echo "  Stop:          docker-compose down"
echo "  Restart:       docker-compose restart"
echo "  Rebuild:       docker-compose up -d --build"
echo ""
echo "📖 Full documentation: README-DOCKER.md"
echo ""
