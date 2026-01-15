#!/bin/bash

# DOTK Admin - MySQL Database Setup Script
# This script helps you set up the MySQL database for the admin panel

echo "======================================"
echo "DOTK Admin - MySQL Database Setup"
echo "======================================"
echo ""

# Check if MySQL is installed
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL is not installed or not in PATH"
    echo ""
    echo "Install MySQL:"
    echo "  - Mac: brew install mysql"
    echo "  - Ubuntu: sudo apt install mysql-server"
    echo ""
    exit 1
fi

echo "✅ MySQL is installed"
echo ""

# Get database credentials
echo "Enter MySQL root password (press Enter if no password):"
read -s MYSQL_ROOT_PASSWORD

# Database details
DB_NAME="dotk_admin"
DB_USER="dotk_admin_user"

echo ""
echo "Creating database: $DB_NAME"
echo "Creating user: $DB_USER"
echo ""

# Generate random password for database user
DB_PASSWORD=$(openssl rand -base64 20 | tr -d "=+/" | cut -c1-25)

# Create database and user
if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
    mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF
else
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF
fi

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Database created successfully!"
    echo ""
    echo "======================================"
    echo "DATABASE CREDENTIALS"
    echo "======================================"
    echo "Database: $DB_NAME"
    echo "Username: $DB_USER"
    echo "Password: $DB_PASSWORD"
    echo "Host: localhost"
    echo "Port: 3306"
    echo ""
    echo "⚠️  SAVE THESE CREDENTIALS!"
    echo ""
    echo "Update your .env file with:"
    echo "======================================"
    echo "DB_CONNECTION=mysql"
    echo "DB_HOST=localhost"
    echo "DB_PORT=3306"
    echo "DB_DATABASE=$DB_NAME"
    echo "DB_USERNAME=$DB_USER"
    echo "DB_PASSWORD=$DB_PASSWORD"
    echo "======================================"
    echo ""
else
    echo ""
    echo "❌ Error creating database"
    echo ""
    echo "Manual setup:"
    echo "1. Login to MySQL: mysql -u root -p"
    echo "2. Run these commands:"
    echo "   CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    echo "   CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY 'your-password';"
    echo "   GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
    echo "   FLUSH PRIVILEGES;"
    echo ""
fi
