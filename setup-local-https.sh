#!/bin/bash

# Local HTTPS Setup Script for DOTK Project
# This script sets up local domain with HTTPS using mkcert

set -e

echo "=================================================="
echo "DOTK Local HTTPS Setup"
echo "=================================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Domain names
DOMAIN1="kashmirtourismofficial.test"
DOMAIN2="kashmirtourismofficial.local"

# Step 1: Check if running on macOS
if [[ "$OSTYPE" != "darwin"* ]]; then
    echo -e "${RED}This script is designed for macOS. For other systems, please follow manual instructions.${NC}"
    exit 1
fi

# Step 2: Check if mkcert is installed
echo -e "${YELLOW}Step 1: Checking for mkcert...${NC}"
if ! command -v mkcert &> /dev/null; then
    echo -e "${YELLOW}mkcert not found. Installing via Homebrew...${NC}"
    if ! command -v brew &> /dev/null; then
        echo -e "${RED}Homebrew not found. Please install Homebrew first: https://brew.sh${NC}"
        exit 1
    fi
    brew install mkcert
    brew install nss # for Firefox support
else
    echo -e "${GREEN}✓ mkcert is already installed${NC}"
fi

# Step 3: Install local CA
echo ""
echo -e "${YELLOW}Step 2: Installing local Certificate Authority...${NC}"
mkcert -install
echo -e "${GREEN}✓ Local CA installed${NC}"

# Step 4: Create certs directory
echo ""
echo -e "${YELLOW}Step 3: Creating certs directory...${NC}"
mkdir -p certs
echo -e "${GREEN}✓ Certs directory created${NC}"

# Step 5: Generate SSL certificates
echo ""
echo -e "${YELLOW}Step 4: Generating SSL certificates for $DOMAIN1 and $DOMAIN2...${NC}"
cd certs
mkcert "$DOMAIN1" "$DOMAIN2"
echo -e "${GREEN}✓ SSL certificates generated${NC}"
cd ..

# Step 6: Update /etc/hosts
echo ""
echo -e "${YELLOW}Step 5: Updating /etc/hosts file...${NC}"
echo -e "${YELLOW}This requires sudo/administrator password...${NC}"

# Check if entries already exist
if grep -q "$DOMAIN1" /etc/hosts && grep -q "$DOMAIN2" /etc/hosts; then
    echo -e "${GREEN}✓ Entries already exist in /etc/hosts${NC}"
else
    echo "127.0.0.1   $DOMAIN1" | sudo tee -a /etc/hosts > /dev/null
    echo "127.0.0.1   $DOMAIN2" | sudo tee -a /etc/hosts > /dev/null
    echo -e "${GREEN}✓ Added entries to /etc/hosts${NC}"
fi

# Step 7: Update .gitignore
echo ""
echo -e "${YELLOW}Step 6: Updating .gitignore...${NC}"
if [ -f .gitignore ]; then
    # Add local files to .gitignore if not already present
    grep -qF "docker-compose.override.yml" .gitignore || echo "docker-compose.override.yml" >> .gitignore
    grep -qF "httpd-local.conf" .gitignore || echo "httpd-local.conf" >> .gitignore
    grep -qF "certs/" .gitignore || echo "certs/" >> .gitignore
    grep -qF ".env.local" .gitignore || echo "admin/.env.local" >> .gitignore
    echo -e "${GREEN}✓ .gitignore updated${NC}"
else
    echo -e "${YELLOW}No .gitignore found. Creating one...${NC}"
    cat > .gitignore << EOF
# Local development files
docker-compose.override.yml
httpd-local.conf
certs/
admin/.env.local
EOF
    echo -e "${GREEN}✓ .gitignore created${NC}"
fi

# Step 8: Copy .env.local to .env if .env doesn't exist
echo ""
echo -e "${YELLOW}Step 7: Setting up admin .env file...${NC}"
if [ -f admin/.env ]; then
    echo -e "${YELLOW}admin/.env already exists. Keeping existing file.${NC}"
    echo -e "${YELLOW}To use the local configuration, run: cp admin/.env.local admin/.env${NC}"
else
    cp admin/.env.local admin/.env
    echo -e "${GREEN}✓ Created admin/.env from .env.local${NC}"
fi

# Step 9: Restart Docker containers
echo ""
echo -e "${YELLOW}Step 8: Restarting Docker containers...${NC}"
docker-compose down
docker-compose up -d
echo -e "${GREEN}✓ Docker containers restarted${NC}"

# Success message
echo ""
echo "=================================================="
echo -e "${GREEN}Setup Complete!${NC}"
echo "=================================================="
echo ""
echo "Your site is now available at:"
echo -e "  ${GREEN}https://$DOMAIN1${NC}"
echo -e "  ${GREEN}https://$DOMAIN2${NC}"
echo ""
echo "Admin panel:"
echo -e "  ${GREEN}https://$DOMAIN1/admin${NC}"
echo ""
echo -e "${YELLOW}Note: These changes are local only and will NOT affect GoDaddy staging/production.${NC}"
echo ""
