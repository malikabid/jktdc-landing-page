# JKTDC Admin Panel - Slim 4 Implementation Plan

## 🎯 Complete Step-by-Step Working Plan

This document provides a detailed, actionable roadmap for building the JKTDC admin panel using Slim Framework 4.

---

## Phase 1: Project Setup & Foundation (Days 1-2)

### Step 1.1: Environment Setup
**Time: 1-2 hours**

- [ ] Verify PHP version (7.4+ required, 8.0+ recommended)
  ```bash
  php -v
  ```
- [ ] Install Composer (if not installed)
  ```bash
  # macOS
  brew install composer
  
  # Or download from getcomposer.org
  ```
- [ ] Install MySQL (if not installed)
  ```bash
  # macOS
  brew install mysql
  brew services start mysql
  ```

### Step 1.2: Create Project Structure
**Time: 30 minutes**

- [ ] Initialize Composer project in admin directory
  ```bash
  cd /Users/abidhussainmalik/Sites/JKTDC/admin
  composer init
  ```

- [ ] Create base folder structure:
  ```
  admin/
  ├── public/              # Web root (index.php)
  ├── src/
  │   ├── Controllers/     # Request handlers
  │   ├── Middleware/      # Auth, CSRF, etc.
  │   ├── Models/          # Database models
  │   ├── Services/        # Business logic
  │   └── Validators/      # Input validation
  ├── config/              # Configuration files
  ├── templates/           # Twig templates
  │   ├── layouts/
  │   ├── partials/
  │   └── pages/
  ├── storage/
  │   ├── logs/
  │   ├── cache/
  │   └── uploads/
  ├── routes/              # Route definitions
  └── vendor/              # Composer dependencies
  ```

### Step 1.3: Install Core Dependencies
**Time: 15 minutes**

- [ ] Install Slim Framework and essentials:
  ```bash
  composer require slim/slim:"4.*"
  composer require slim/psr7
  composer require slim/twig-view
  composer require slim-select/php-view
  ```

- [ ] Install security packages:
  ```bash
  composer require slim/csrf
  composer require respect/validation
  composer require paragonie/halite
  ```

- [ ] Install database and utilities:
  ```bash
  composer require illuminate/database
  composer require monolog/monolog
  composer require vlucas/phpdotenv
  ```

### Step 1.4: Create Configuration Files
**Time: 1 hour**

- [ ] Create `.env` file for environment variables:
  ```env
  # Database
  DB_HOST=localhost
  DB_PORT=3306
  DB_DATABASE=jktdc_admin
  DB_USERNAME=root
  DB_PASSWORD=
  
  # App
  APP_ENV=development
  APP_DEBUG=true
  APP_URL=http://localhost/admin
  
  # Security
  APP_KEY=generate_random_32_char_string
  SESSION_LIFETIME=1800
  ```

- [ ] Create `config/settings.php`:
  ```php
  <?php
  return [
      'displayErrorDetails' => $_ENV['APP_DEBUG'] ?? false,
      'logErrors' => true,
      'logErrorDetails' => true,
      'db' => [
          'driver' => 'mysql',
          'host' => $_ENV['DB_HOST'],
          'database' => $_ENV['DB_DATABASE'],
          'username' => $_ENV['DB_USERNAME'],
          'password' => $_ENV['DB_PASSWORD'],
          'charset' => 'utf8mb4',
          'collation' => 'utf8mb4_unicode_ci',
      ],
      'twig' => [
          'path' => __DIR__ . '/../templates',
          'cache' => __DIR__ . '/../storage/cache/twig',
      ],
  ];
  ```

- [ ] Create `public/index.php` (application entry point)
- [ ] Create `public/.htaccess` for URL rewriting

**Deliverable:** Working Slim skeleton with "Hello World" response

---

## Phase 2: Database & Models (Days 3-4)

### Step 2.1: Database Setup
**Time: 30 minutes**

- [ ] Create database:
  ```bash
  mysql -u root -p
  CREATE DATABASE jktdc_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```

- [ ] Install Phinx (migration tool):
  ```bash
  composer require robmorgan/phinx
  ```

- [ ] Initialize Phinx:
  ```bash
  vendor/bin/phinx init
  ```

- [ ] Configure `phinx.php` with database credentials

### Step 2.2: Create Migrations
**Time: 2 hours**

- [ ] Create migration files:
  ```bash
  vendor/bin/phinx create CreateUsersTable
  vendor/bin/phinx create CreateRolesTable
  vendor/bin/phinx create CreateSessionsTable
  vendor/bin/phinx create CreateSliderContentTable
  vendor/bin/phinx create CreateOfficialsTable
  vendor/bin/phinx create CreateNotificationsTable
  vendor/bin/phinx create CreateEventsTable
  vendor/bin/phinx create CreateSiteSettingsTable
  vendor/bin/phinx create CreateAuditLogTable
  ```

- [ ] Write migration logic for each table (define columns, indexes, foreign keys)

### Step 2.3: Run Migrations
**Time: 15 minutes**

- [ ] Execute migrations to create all tables:
  ```bash
  vendor/bin/phinx migrate
  ```

- [ ] Verify tables were created:
  ```bash
  mysql -u root -p jktdc_admin -e "SHOW TABLES;"
  ```

### Step 2.4: Create Seeders
**Time: 1 hour**

- [ ] Create seeder for default roles:
  ```bash
  vendor/bin/phinx seed:create RolesSeeder
  ```

- [ ] Create seeder for first admin user:
  ```bash
  vendor/bin/phinx seed:create AdminUserSeeder
  ```

- [ ] Run seeders:
  ```bash
  vendor/bin/phinx seed:run
  ```

### Step 2.5: Create Eloquent Models
**Time: 2 hours**

- [ ] Create `src/Models/User.php`
- [ ] Create `src/Models/Role.php`
- [ ] Create `src/Models/SliderContent.php`
- [ ] Create `src/Models/Official.php`
- [ ] Create `src/Models/Notification.php`
- [ ] Create `src/Models/Event.php`
- [ ] Create `src/Models/SiteSetting.php`
- [ ] Create `src/Models/AuditLog.php`

- [ ] Test models with test queries

**Deliverable:** Functional database with migrations, seeders, and models

**Benefits of Migrations:**
- ✅ Schema changes tracked in version control
- ✅ Easy to rollback: `vendor/bin/phinx rollback`
- ✅ Team members just run: `vendor/bin/phinx migrate`
- ✅ Production deployment: Same migration command
- ✅ No manual SQL imports needed

---

## Phase 3: Authentication System (Days 5-7)

### Step 3.1: Session Management
**Time: 2 hours**

- [ ] Install session middleware:
  ```bash
  composer require slim/session
  ```

- [ ] Create `src/Middleware/SessionMiddleware.php`
- [ ] Configure secure session settings
- [ ] Test session persistence

### Step 3.2: Login System
**Time: 4 hours**

- [ ] Create `src/Controllers/AuthController.php`:
  - `showLogin()` - Display login form
  - `login()` - Process login
  - `logout()` - Handle logout

- [ ] Create `templates/auth/login.twig`:
  - Email/username field
  - Password field
  - Remember me checkbox
  - CSRF protection
  - Error messages

- [ ] Implement password verification
- [ ] Create session on successful login
- [ ] Add "Remember Me" functionality

### Step 3.3: Authentication Middleware
**Time: 2 hours**

- [ ] Create `src/Middleware/AuthMiddleware.php`:
  - Check if user is logged in
  - Redirect to login if not
  - Store user in request attribute

- [ ] Apply middleware to protected routes

### Step 3.4: Password Reset
**Time: 3 hours**

- [ ] Create forgot password form
- [ ] Generate secure reset tokens
- [ ] Send reset email (configure mailer)
- [ ] Create reset password form
- [ ] Validate and update password

### Step 3.5: Account Security
**Time: 2 hours**

- [ ] Implement login attempt tracking
- [ ] Add account lockout (5 failed attempts)
- [ ] Log all authentication events
- [ ] Add session timeout (30 min inactivity)

**Deliverable:** Complete authentication system with security features

---

## Phase 4: Role-Based Access Control (Days 8-9)

### Step 4.1: Permission System
**Time: 3 hours**

- [ ] Create `src/Services/PermissionService.php`
- [ ] Define permission structure (JSON in roles table)
- [ ] Create helper methods:
  - `can($user, $permission)`
  - `hasRole($user, $role)`
  - `authorize($permission)`

### Step 4.2: Authorization Middleware
**Time: 2 hours**

- [ ] Create `src/Middleware/AuthorizeMiddleware.php`
- [ ] Check user permissions for routes
- [ ] Return 403 if unauthorized
- [ ] Log unauthorized access attempts

### Step 4.3: Role Management
**Time: 3 hours**

- [ ] Create role CRUD controller
- [ ] Create role management UI
- [ ] Allow permission assignment
- [ ] Protect with Super Admin role

**Deliverable:** Working RBAC system with role management

---

## Phase 5: Admin Dashboard & Layout (Days 10-11)

### Step 5.1: Admin Template Setup
**Time: 3 hours**

- [ ] Install AdminLTE 3:
  ```bash
  npm install admin-lte@^3.2
  # Or download from adminlte.io
  ```

- [ ] Create base layout `templates/layouts/admin.twig`:
  - Header with user info & logout
  - Sidebar navigation
  - Main content area
  - Footer
  - Include CSS/JS

### Step 5.2: Navigation System
**Time: 2 hours**

- [ ] Create `templates/partials/sidebar.twig`
- [ ] Define menu structure with permissions
- [ ] Highlight active menu item
- [ ] Show/hide menu items based on role

### Step 5.3: Dashboard Controller
**Time: 2 hours**

- [ ] Create `src/Controllers/DashboardController.php`
- [ ] Build dashboard view with stats:
  - Total users
  - Active content items
  - Recent changes
  - Quick actions

- [ ] Create `templates/pages/dashboard.twig`

### Step 5.4: UI Components
**Time: 2 hours**

- [ ] Create alert/flash message component
- [ ] Create data table component
- [ ] Create form components (reusable)
- [ ] Create modal component

**Deliverable:** Professional admin dashboard with navigation

---

## Phase 6: CSRF Protection (Day 12)

### Step 6.1: CSRF Setup
**Time: 2 hours**

- [ ] Configure CSRF middleware globally
- [ ] Add CSRF token to Twig global variables
- [ ] Create Twig function for token fields:
  ```twig
  {{ csrf_token_field() }}
  ```

### Step 6.2: Form Protection
**Time: 1 hour**

- [ ] Add CSRF tokens to all forms
- [ ] Test CSRF validation
- [ ] Handle CSRF failures gracefully

**Deliverable:** All forms protected against CSRF attacks

---

## Phase 7: Slider Management (Days 13-15)

### Step 7.1: Slider CRUD
**Time: 4 hours**

- [ ] Create `src/Controllers/SliderController.php`:
  - `index()` - List all slides
  - `create()` - Show add form
  - `store()` - Save new slide
  - `edit($id)` - Show edit form
  - `update($id)` - Update slide
  - `delete($id)` - Delete slide
  - `reorder()` - Ajax reorder

- [ ] Create Twig templates:
  - `slider/index.twig`
  - `slider/form.twig` (shared add/edit)

### Step 7.2: Image Upload System
**Time: 4 hours**

- [ ] Create `src/Services/ImageService.php`:
  - Validate file type and size
  - Generate unique filename
  - Optimize/compress image
  - Save to storage
  - Delete old images

- [ ] Install image processing:
  ```bash
  composer require intervention/image
  ```

- [ ] Configure upload limits
- [ ] Add image preview in forms

### Step 7.3: Drag & Drop Reordering
**Time: 2 hours**

- [ ] Install Sortable.js or jQuery UI
- [ ] Create Ajax endpoint for reordering
- [ ] Update display_order in database
- [ ] Add visual feedback

**Deliverable:** Complete slider management with image uploads

---

## Phase 8: Officials Management (Days 16-17)

### Step 8.1: Officials CRUD
**Time: 3 hours**

- [ ] Create `src/Controllers/OfficialController.php`
- [ ] Create templates (similar to slider)
- [ ] Add photo upload with cropping
- [ ] Implement reordering

### Step 8.2: Photo Cropping
**Time: 2 hours**

- [ ] Install Cropper.js
- [ ] Add cropping interface
- [ ] Save cropped images
- [ ] Generate consistent aspect ratio

**Deliverable:** Officials management with photo cropping

---

## Phase 9: Notifications & Events (Days 18-19)

### Step 9.1: Notifications Module
**Time: 3 hours**

- [ ] Create `src/Controllers/NotificationController.php`
- [ ] Create notification CRUD
- [ ] Add icon picker (FontAwesome)
- [ ] Add rich text editor (TinyMCE)

### Step 9.2: Events Module
**Time: 3 hours**

- [ ] Create `src/Controllers/EventController.php`
- [ ] Create event CRUD
- [ ] Add date picker (Flatpickr)
- [ ] Auto-extract month and day

### Step 9.3: Rich Text Editor
**Time: 2 hours**

- [ ] Install TinyMCE:
  ```html
  <script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js"></script>
  ```

- [ ] Configure editor settings
- [ ] Add to notification/event forms
- [ ] Sanitize HTML output

**Deliverable:** Notifications and events management

---

## Phase 10: Content Management (Days 20-21)

### Step 10.1: Site Settings
**Time: 4 hours**

- [ ] Create `src/Controllers/SettingsController.php`
- [ ] Create settings form with tabs:
  - General settings
  - Theme settings
  - SEO settings
  - Social media
  - Advanced

- [ ] Store settings in database
- [ ] Cache settings for performance

### Step 10.2: Menu Management
**Time: 4 hours**

- [ ] Create menu builder interface
- [ ] Add drag-and-drop nesting
- [ ] Store menu structure (JSON or tree table)
- [ ] Generate menu for front-end

**Deliverable:** Site settings and menu management

---

## Phase 11: Media Library (Days 22-23)

### Step 11.1: Media Gallery
**Time: 4 hours**

- [ ] Create `src/Controllers/MediaController.php`
- [ ] Create grid view of all media
- [ ] Add upload functionality (single/bulk)
- [ ] Add search and filter
- [ ] Show image details (size, dimensions, date)

### Step 11.2: Media Management
**Time: 2 hours**

- [ ] Add delete functionality
- [ ] Add bulk operations
- [ ] Optimize images on upload
- [ ] Track media usage

**Deliverable:** Complete media library

---

## Phase 12: User Management (Days 24-25)

### Step 12.1: User CRUD
**Time: 3 hours**

- [ ] Create `src/Controllers/UserController.php`
- [ ] List all users with filters
- [ ] Add/edit user form
- [ ] Assign roles
- [ ] Lock/unlock accounts

### Step 12.2: User Profile
**Time: 2 hours**

- [ ] Create profile edit page
- [ ] Change password functionality
- [ ] Upload profile photo
- [ ] View login history

**Deliverable:** User management system

---

## Phase 13: Activity Logging (Days 26-27)

### Step 13.1: Logging System
**Time: 3 hours**

- [ ] Create `src/Services/AuditService.php`
- [ ] Log all CRUD operations:
  - User who made change
  - Action type
  - Old/new values
  - IP address
  - Timestamp

### Step 13.2: Log Viewer
**Time: 3 hours**

- [ ] Create `src/Controllers/LogController.php`
- [ ] Create log viewing interface
- [ ] Add filters (user, date, action)
- [ ] Add search functionality
- [ ] Export to CSV

**Deliverable:** Complete audit trail system

---

## Phase 14: API Endpoints (Days 28-29)

### Step 14.1: Public API
**Time: 4 hours**

- [ ] Create `routes/api.php`
- [ ] Create API controllers:
  - `GET /api/slider` - Return active slides
  - `GET /api/officials` - Return active officials
  - `GET /api/notifications` - Return notifications
  - `GET /api/events` - Return upcoming events
  - `GET /api/settings` - Return public settings

### Step 14.2: Admin API
**Time: 2 hours**

- [ ] Create authenticated API endpoints:
  - Upload handlers
  - Reorder handlers
  - Quick actions

- [ ] Add API authentication (JWT or session)

**Deliverable:** RESTful API for front-end integration

---

## Phase 15: Security Hardening (Days 30-31)

### Step 15.1: Security Audit
**Time: 4 hours**

- [ ] Review all input validation
- [ ] Test SQL injection prevention
- [ ] Test XSS prevention
- [ ] Test CSRF protection
- [ ] Test file upload security
- [ ] Test authentication bypass attempts

### Step 15.2: Security Headers
**Time: 1 hour**

- [ ] Add security headers middleware:
  ```php
  X-Frame-Options: DENY
  X-Content-Type-Options: nosniff
  X-XSS-Protection: 1; mode=block
  Referrer-Policy: strict-origin-when-cross-origin
  Content-Security-Policy: default-src 'self'
  ```

### Step 15.3: Rate Limiting
**Time: 2 hours**

- [ ] Install rate limiter:
  ```bash
  composer require mezzio/mezzio-session
  ```

- [ ] Add rate limiting to login
- [ ] Add rate limiting to API endpoints

**Deliverable:** Hardened security posture

---

## Phase 16: Testing & Bug Fixes (Days 32-35)

### Step 16.1: Manual Testing
**Time: 8 hours**

- [ ] Test all CRUD operations
- [ ] Test all user roles
- [ ] Test edge cases
- [ ] Test on different browsers
- [ ] Test on mobile devices
- [ ] Test file uploads
- [ ] Test error handling

### Step 16.2: Bug Fixes
**Time: 8 hours**

- [ ] Document all bugs found
- [ ] Prioritize fixes (critical → low)
- [ ] Fix all critical bugs
- [ ] Retest after fixes

### Step 16.3: Performance Testing
**Time: 4 hours**

- [ ] Test page load times
- [ ] Optimize slow queries
- [ ] Add database indexes
- [ ] Enable Twig caching
- [ ] Optimize images

**Deliverable:** Bug-free, optimized admin panel

---

## Phase 17: Documentation (Days 36-37)

### Step 17.1: Technical Documentation
**Time: 4 hours**

- [ ] Document code structure
- [ ] Document API endpoints
- [ ] Document database schema
- [ ] Document deployment process
- [ ] Create README.md

### Step 17.2: User Documentation
**Time: 4 hours**

- [ ] Create user manual (PDF)
- [ ] Create video tutorials:
  - How to login
  - How to manage slider
  - How to manage officials
  - How to create notifications/events
  - How to manage users

- [ ] Create FAQ document

**Deliverable:** Complete documentation

---

## Phase 18: Deployment (Days 38-40)

### Step 18.1: Staging Deployment
**Time: 4 hours**

- [ ] Set up staging environment on GoDaddy
- [ ] Upload files via FTP
- [ ] Configure database
- [ ] Test all functionality
- [ ] Fix any hosting-specific issues

### Step 18.2: Production Deployment
**Time: 2 hours**

- [ ] Back up existing admin folder
- [ ] Deploy to production
- [ ] Configure HTTPS/SSL
- [ ] Test all functionality
- [ ] Monitor error logs

### Step 18.3: Post-Deployment
**Time: 2 hours**

- [ ] Create first admin user
- [ ] Import initial data (if needed)
- [ ] Train admin users
- [ ] Set up automated backups
- [ ] Set up monitoring

**Deliverable:** Live admin panel on production

---

## Estimated Timeline

| Phase | Duration | Cumulative |
|-------|----------|------------|
| Setup & Foundation | 2 days | 2 days |
| Database & Models | 2 days | 4 days |
| Authentication | 3 days | 7 days |
| RBAC | 2 days | 9 days |
| Dashboard | 2 days | 11 days |
| CSRF | 1 day | 12 days |
| Slider Management | 3 days | 15 days |
| Officials | 2 days | 17 days |
| Notifications & Events | 2 days | 19 days |
| Content Management | 2 days | 21 days |
| Media Library | 2 days | 23 days |
| User Management | 2 days | 25 days |
| Activity Logging | 2 days | 27 days |
| API Endpoints | 2 days | 29 days |
| Security Hardening | 2 days | 31 days |
| Testing & Fixes | 4 days | 35 days |
| Documentation | 2 days | 37 days |
| Deployment | 3 days | 40 days |

**Total: 40 working days (~8 weeks)**

---

## Quick Start Commands

### Initial Setup
```bash
# Navigate to admin directory
cd /Users/abidhussainmalik/Sites/JKTDC/admin

# Remove old index.php
rm index.php

# Initialize Composer
composer init --name="jktdc/admin-panel" --type=project --require="slim/slim:^4.0"

# Install core dependencies
composer require slim/slim:"4.*" slim/psr7 slim/twig-view

# Install security
composer require slim/csrf respect/validation

# Install database
composer require illuminate/database vlucas/phpdotenv

# Install utilities
composer require monolog/monolog

# Create directories
mkdir -p public src/{Controllers,Middleware,Models,Services,Validators} config templates/{layouts,partials,pages} storage/{logs,cache,uploads} routes database

# Create entry point
touch public/index.php
touch public/.htaccess
touch .env
```

---

## Development Workflow

### Daily Routine
1. **Start of day:**
   - Review tasks for the day
   - Pull latest code (if team)
   - Start local server

2. **During development:**
   - Write code in small, testable chunks
   - Commit frequently with clear messages
   - Test each feature before moving on

3. **End of day:**
   - Test all changes
   - Commit and push code
   - Update task checklist
   - Document any issues

### Git Workflow
```bash
# Create feature branch
git checkout -b feature/authentication-system

# Make changes and commit
git add .
git commit -m "feat: implement login system with session management"

# Merge to main when complete
git checkout main
git merge feature/authentication-system
git push origin main
```

---

## Next Immediate Actions

### What to do right now:

1. **Confirm you want to proceed with Slim 4**
2. **I will then:**
   - Create the complete folder structure
   - Initialize Composer and install dependencies
   - Set up the entry point (index.php)
   - Configure .htaccess for URL rewriting
   - Create basic configuration files
   - Set up Twig templating
   - Create "Hello World" test page

3. **After initial setup works, we'll move to Phase 2 (Database)**

---

**Ready to start? Say "yes" and I'll begin Phase 1 setup!** 🚀
