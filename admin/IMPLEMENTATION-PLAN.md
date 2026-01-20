# DOTK Admin CMS - Implementation Plan

## 📋 Overview

This document provides a **step-by-step implementation guide** for building the DOTK Admin CMS using Slim Framework. Follow this plan sequentially for best results.

**Related Documents**:
- [PROJECT-PLAN.md](PROJECT-PLAN.md) - Timeline and effort estimates
- [README-ADMIN-AUTH.md](README-ADMIN-AUTH.md) - Current authentication setup

---

## 🎯 Implementation Strategy

### **Approach**: Iterative & Testable
- Build in small, testable increments
- Each feature must be tested before moving to next
- Commit frequently with meaningful messages
- Deploy to staging after each milestone

### **Git Workflow**:
```bash
main                    # Production-ready code
  ├── staging          # Testing environment
  └── feature/*        # Feature branches
      ├── feature/auth-system
      ├── feature/notifications-module
      ├── feature/2fa
      └── feature/admin-ui
```

---

## 📦 Phase 0: Environment Setup

### Step 0.1: Development Environment (1 hour)

#### Prerequisites Check:
```bash
# Verify PHP version (8.0+)
php -v

# Verify Composer
composer --version

# Verify Git
git --version

# Verify Node.js & npm (for asset compilation)
node -v
npm -v
```

#### Install Required Tools:
```bash
# Install PHP extensions (if missing)
# For Mac:
brew install php@8.1
brew install composer

# For Ubuntu:
sudo apt install php8.1 php8.1-mbstring php8.1-xml php8.1-sqlite3 php8.1-curl
```

#### IDE Setup:
```bash
# VS Code extensions (recommended)
code --install-extension bmewburn.vscode-intelephense-client
code --install-extension xdebug.php-debug
code --install-extension mblode.twig-language-2
code --install-extension mikestead.dotenv
```

---

### Step 0.2: Initialize Slim Project (1 hour)

```bash
cd /Users/abidhussainmalik/Sites/DOTK/admin

# Initialize composer
composer init

# Install Slim and dependencies
composer require slim/slim:"4.*"
composer require slim/psr7
composer require php-di/php-di
composer require monolog/monolog

# Install authentication
composer require tuupola/slim-jwt-auth
composer require firebase/php-jwt

# Install 2FA
composer require robthree/twofactorauth

# Install database
composer require illuminate/database
composer require vlucas/phpdotenv

# Install validation
composer require respect/validation

# Install Twig (for later)
composer require slim/twig-view

# Development dependencies
composer require --dev phpunit/phpunit
composer require --dev squizlabs/php_codesniffer
```

---

### Step 0.3: Project Structure Setup (30 min)

```bash
# Create directory structure
mkdir -p public
mkdir -p src/{Controllers,Middleware,Models,Modules,Services,Database}
mkdir -p config
mkdir -p templates/{layout,auth,notifications,events,tenders,officials,errors}
mkdir -p storage/{cache,logs,uploads,backups}
mkdir -p tests/{Unit,Integration}

# Create essential files
touch public/index.php
touch public/.htaccess
touch src/bootstrap.php
touch config/settings.php
touch config/dependencies.php
touch config/routes.php
touch .env.example
touch .env
touch .gitignore
```

**Directory Structure**:
```
admin/
├── public/              # Web root
│   ├── index.php       # Entry point
│   ├── .htaccess       # Apache rewrite rules
│   └── assets/         # CSS, JS, images
├── src/                # Application code
│   ├── Controllers/    # Route controllers
│   ├── Middleware/     # Custom middleware
│   ├── Models/         # Eloquent models
│   ├── Modules/        # Feature modules
│   ├── Services/       # Business logic
│   └── Database/       # Migrations, seeds
├── config/             # Configuration files
│   ├── settings.php    # App settings
│   ├── dependencies.php # DI container
│   └── routes.php      # Route definitions
├── templates/          # Twig templates
├── storage/            # Writable storage
├── tests/              # PHPUnit tests
├── composer.json       # Dependencies
└── .env               # Environment config
```

---

## 🚀 Phase 1: Core Foundation

### Milestone 1.1: Basic Slim App (2 hours)

#### Task 1.1.1: Create Entry Point

**File**: `public/index.php`
```php
<?php
use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create DI Container
$container = new Container();

// Register dependencies
require __DIR__ . '/../config/dependencies.php';

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Register middleware
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(
    $_ENV['APP_ENV'] !== 'production',
    true,
    true
);

// Load routes
require __DIR__ . '/../config/routes.php';

// Run app
$app->run();
```

#### Task 1.1.2: Apache Configuration

**File**: `public/.htaccess`
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

#### Task 1.1.3: Environment Configuration

**File**: `.env.example`
```env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/admin

DB_CONNECTION=sqlite
DB_DATABASE=../storage/database.sqlite

JWT_SECRET=your-secret-key-here-change-in-production
JWT_ALGORITHM=HS256
JWT_EXPIRATION=3600

LOG_LEVEL=debug
```

**Copy to .env**:
```bash
cp .env.example .env
# Update JWT_SECRET with random string
```

#### Task 1.1.4: Basic Settings

**File**: `config/settings.php`
```php
<?php
return [
    'displayErrorDetails' => $_ENV['APP_DEBUG'] === 'true',
    'logErrors' => true,
    'logErrorDetails' => true,
    
    'db' => [
        'driver' => $_ENV['DB_CONNECTION'] ?? 'sqlite',
        'database' => $_ENV['DB_DATABASE'] ?? '../storage/database.sqlite',
    ],
    
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'],
        'algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
        'expiration' => (int)$_ENV['JWT_EXPIRATION'] ?? 3600,
    ],
    
    'twig' => [
        'path' => __DIR__ . '/../templates',
        'cache' => __DIR__ . '/../storage/cache',
    ],
];
```

#### Task 1.1.5: Test Basic Route

**File**: `config/routes.php`
```php
<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Health check
$app->get('/health', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'timestamp' => time()
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// API routes
$app->group('/api', function ($group) {
    $group->get('', function (Request $request, Response $response) {
        $response->getBody()->write(json_encode([
            'message' => 'DOTK Admin API',
            'version' => '1.0.0'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });
});
```

#### Task 1.1.6: Test Setup

```bash
# Start PHP development server
cd public
php -S localhost:8080

# Test in browser or curl
curl http://localhost:8080/health
curl http://localhost:8080/api
```

**Expected Response**:
```json
{"status":"ok","timestamp":1737100000}
{"message":"DOTK Admin API","version":"1.0.0"}
```

✅ **Checkpoint**: Basic Slim app running successfully

---

### Milestone 1.2: Database Setup (2 hours)

#### Task 1.2.1: Configure Eloquent ORM

**File**: `config/dependencies.php`
```php
<?php
use Illuminate\Database\Capsule\Manager as Capsule;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Slim\Views\Twig;

return function ($container) {
    $settings = require __DIR__ . '/settings.php';
    
    // Database
    $capsule = new Capsule;
    $capsule->addConnection($settings['db']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    
    $container->set('db', function () use ($capsule) {
        return $capsule;
    });
    
    // Logger
    $container->set('logger', function () {
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler(
            __DIR__ . '/../storage/logs/app.log',
            Logger::DEBUG
        ));
        return $logger;
    });
    
    // Twig (for later)
    $container->set('view', function () use ($settings) {
        return Twig::create($settings['twig']['path'], [
            'cache' => $settings['twig']['cache']
        ]);
    });
};
```

#### Task 1.2.2: Create Migration System

**File**: `src/Database/Migration.php`
```php
<?php
namespace App\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

abstract class Migration
{
    protected $schema;
    
    public function __construct()
    {
        $this->schema = Capsule::schema();
    }
    
    abstract public function up();
    abstract public function down();
}
```

#### Task 1.2.3: Users Table Migration

**File**: `src/Database/Migrations/CreateUsersTable.php`
```php
<?php
namespace App\Database\Migrations;

use App\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->schema->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('viewer');
            $table->boolean('is_active')->default(true);
            
            // 2FA fields
            $table->string('two_factor_secret')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_recovery_codes')->nullable();
            
            $table->timestamps();
        });
    }
    
    public function down()
    {
        $this->schema->dropIfExists('users');
    }
}
```

#### Task 1.2.4: Sessions Table (for JWT blacklist)

**File**: `src/Database/Migrations/CreateSessionsTable.php`
```php
<?php
namespace App\Database\Migrations;

use App\Database\Migration;

class CreateSessionsTable extends Migration
{
    public function up()
    {
        $this->schema->create('sessions', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('token');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }
    
    public function down()
    {
        $this->schema->dropIfExists('sessions');
    }
}
```

#### Task 1.2.5: Run Migrations

**File**: `src/Database/migrate.php`
```php
<?php
require __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Initialize database
$container = new DI\Container();
require __DIR__ . '/../../config/dependencies.php';

// Run migrations
$migrations = [
    new \App\Database\Migrations\CreateUsersTable(),
    new \App\Database\Migrations\CreateSessionsTable(),
];

foreach ($migrations as $migration) {
    echo "Running: " . get_class($migration) . "\n";
    $migration->up();
}

echo "Migrations completed!\n";
```

**Run**:
```bash
php src/Database/migrate.php
```

#### Task 1.2.6: Create User Model

**File**: `src/Models/User.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active'
    ];
    
    protected $hidden = [
        'password', 'two_factor_secret', 'two_factor_recovery_codes'
    ];
    
    protected $casts = [
        'two_factor_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];
    
    public function sessions()
    {
        return $this->hasMany(Session::class);
    }
    
    public function hasRole($role)
    {
        return $this->role === $role;
    }
    
    public function hasAnyRole(array $roles)
    {
        return in_array($this->role, $roles);
    }
}
```

#### Task 1.2.7: Seed Admin User

**File**: `src/Database/seed.php`
```php
<?php
require __DIR__ . '/../../vendor/autoload.php';

use App\Models\User;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$container = new DI\Container();
require __DIR__ . '/../../config/dependencies.php';

// Create admin user
User::create([
    'name' => 'Admin User',
    'email' => 'admin@dotk.gov.in',
    'password' => password_hash('admin123', PASSWORD_BCRYPT),
    'role' => 'admin',
    'is_active' => true,
]);

echo "Admin user created!\n";
echo "Email: admin@dotk.gov.in\n";
echo "Password: admin123\n";
echo "⚠️  Change password immediately!\n";
```

**Run**:
```bash
php src/Database/seed.php
```

✅ **Checkpoint**: Database tables created, admin user seeded

---

### Milestone 1.3: Authentication System (4 hours)

#### Task 1.3.1: JWT Service

**File**: `src/Services/JwtService.php`
```php
<?php
namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use App\Models\Session;

class JwtService
{
    private $secret;
    private $algorithm;
    private $expiration;
    
    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'];
        $this->algorithm = $_ENV['JWT_ALGORITHM'] ?? 'HS256';
        $this->expiration = (int)$_ENV['JWT_EXPIRATION'] ?? 3600;
    }
    
    public function generateToken(User $user)
    {
        $payload = [
            'iss' => $_ENV['APP_URL'],
            'iat' => time(),
            'exp' => time() + $this->expiration,
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
        ];
        
        $token = JWT::encode($payload, $this->secret, $this->algorithm);
        
        // Store session
        Session::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', $payload['exp']),
        ]);
        
        return $token;
    }
    
    public function verifyToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            
            // Check if token is revoked
            $tokenHash = hash('sha256', $token);
            $session = Session::where('token', $tokenHash)
                              ->whereNull('revoked_at')
                              ->first();
            
            if (!$session) {
                return null;
            }
            
            return (array)$decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function revokeToken($token)
    {
        $tokenHash = hash('sha256', $token);
        Session::where('token', $tokenHash)->update([
            'revoked_at' => now()
        ]);
    }
}
```

#### Task 1.3.2: Auth Middleware

**File**: `src/Middleware/AuthMiddleware.php`
```php
<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use App\Services\JwtService;
use App\Models\User;

class AuthMiddleware
{
    private $jwtService;
    
    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }
    
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorized();
        }
        
        $token = $matches[1];
        $payload = $this->jwtService->verifyToken($token);
        
        if (!$payload) {
            return $this->unauthorized();
        }
        
        $user = User::find($payload['sub']);
        
        if (!$user || !$user->is_active) {
            return $this->unauthorized();
        }
        
        // Add user to request
        $request = $request->withAttribute('user', $user);
        $request = $request->withAttribute('token', $token);
        
        return $handler->handle($request);
    }
    
    private function unauthorized(): Response
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => 'Unauthorized'
        ]));
        return $response->withStatus(401)
                       ->withHeader('Content-Type', 'application/json');
    }
}
```

#### Task 1.3.3: RBAC Middleware

**File**: `src/Middleware/RbacMiddleware.php`
```php
<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class RbacMiddleware
{
    private $allowedRoles;
    
    public function __construct(array $allowedRoles = [])
    {
        $this->allowedRoles = $allowedRoles;
    }
    
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $user = $request->getAttribute('user');
        
        if (!$user) {
            return $this->forbidden();
        }
        
        // Admin has access to everything
        if ($user->role === 'admin') {
            return $handler->handle($request);
        }
        
        // Check role
        if (!empty($this->allowedRoles) && !$user->hasAnyRole($this->allowedRoles)) {
            return $this->forbidden();
        }
        
        return $handler->handle($request);
    }
    
    private function forbidden(): Response
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => 'Forbidden - Insufficient permissions'
        ]));
        return $response->withStatus(403)
                       ->withHeader('Content-Type', 'application/json');
    }
}
```

#### Task 1.3.4: Auth Controller

**File**: `src/Controllers/AuthController.php`
```php
<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use App\Services\JwtService;
use Respect\Validation\Validator as v;

class AuthController
{
    private $jwtService;
    
    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }
    
    public function login(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        
        // Validate input
        $validator = v::key('email', v::email())
                      ->key('password', v::notEmpty());
        
        if (!$validator->validate($data)) {
            return $this->jsonResponse($response, [
                'error' => 'Invalid input'
            ], 400);
        }
        
        // Find user
        $user = User::where('email', $data['email'])->first();
        
        if (!$user || !password_verify($data['password'], $user->password)) {
            return $this->jsonResponse($response, [
                'error' => 'Invalid credentials'
            ], 401);
        }
        
        if (!$user->is_active) {
            return $this->jsonResponse($response, [
                'error' => 'Account is disabled'
            ], 403);
        }
        
        // Check 2FA
        if ($user->two_factor_enabled) {
            // Generate temporary token
            $tempToken = $this->jwtService->generateTempToken($user);
            return $this->jsonResponse($response, [
                'requires_2fa' => true,
                'temp_token' => $tempToken
            ]);
        }
        
        // Generate JWT
        $token = $this->jwtService->generateToken($user);
        
        return $this->jsonResponse($response, [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }
    
    public function logout(Request $request, Response $response)
    {
        $token = $request->getAttribute('token');
        $this->jwtService->revokeToken($token);
        
        return $this->jsonResponse($response, [
            'message' => 'Logged out successfully'
        ]);
    }
    
    public function me(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        
        return $this->jsonResponse($response, [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'two_factor_enabled' => $user->two_factor_enabled,
            ]
        ]);
    }
    
    private function jsonResponse(Response $response, $data, $status = 200)
    {
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status)
                       ->withHeader('Content-Type', 'application/json');
    }
}
```

#### Task 1.3.5: Auth Routes

**Update**: `config/routes.php`
```php
<?php
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;

// ... previous routes ...

// Auth routes (public)
$app->group('/api/auth', function ($group) {
    $group->post('/login', AuthController::class . ':login');
    $group->post('/logout', AuthController::class . ':logout')
          ->add(AuthMiddleware::class);
    $group->get('/me', AuthController::class . ':me')
          ->add(AuthMiddleware::class);
});
```

#### Task 1.3.6: Test Authentication

**Test Login**:
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@dotk.gov.in","password":"admin123"}'
```

**Expected Response**:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@dotk.gov.in",
    "role": "admin"
  }
}
```

**Test Protected Route**:
```bash
TOKEN="your-token-here"
curl http://localhost:8080/api/auth/me \
  -H "Authorization: Bearer $TOKEN"
```

✅ **Checkpoint**: Authentication working with JWT

---

### Milestone 1.4: Notification Module (4 hours)

#### Task 1.4.1: Module Structure

```bash
mkdir -p src/Modules/Notifications/{Controllers,Services,Validators}
```

#### Task 1.4.2: Notification Service

**File**: `src/Modules/Notifications/Services/NotificationService.php`
```php
<?php
namespace App\Modules\Notifications\Services;

class NotificationService
{
    private $dataFile;
    
    public function __construct()
    {
        $this->dataFile = __DIR__ . '/../../../../pub/data/notifications.json';
    }
    
    public function getAll()
    {
        if (!file_exists($this->dataFile)) {
            return [];
        }
        
        $content = file_get_contents($this->dataFile);
        return json_decode($content, true) ?? [];
    }
    
    public function getById($id)
    {
        $notifications = $this->getAll();
        
        foreach ($notifications as $notification) {
            if ($notification['id'] === $id) {
                return $notification;
            }
        }
        
        return null;
    }
    
    public function create($data)
    {
        $notifications = $this->getAll();
        
        $notification = [
            'id' => $this->generateId(),
            'title' => $data['title'],
            'content' => $data['content'],
            'priority' => $data['priority'] ?? 'medium',
            'link' => $data['link'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $notifications[] = $notification;
        $this->save($notifications);
        
        return $notification;
    }
    
    public function update($id, $data)
    {
        $notifications = $this->getAll();
        
        foreach ($notifications as $key => $notification) {
            if ($notification['id'] === $id) {
                $notifications[$key] = array_merge($notification, [
                    'title' => $data['title'] ?? $notification['title'],
                    'content' => $data['content'] ?? $notification['content'],
                    'priority' => $data['priority'] ?? $notification['priority'],
                    'link' => $data['link'] ?? $notification['link'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                
                $this->save($notifications);
                return $notifications[$key];
            }
        }
        
        return null;
    }
    
    public function delete($id)
    {
        $notifications = $this->getAll();
        
        foreach ($notifications as $key => $notification) {
            if ($notification['id'] === $id) {
                unset($notifications[$key]);
                $this->save(array_values($notifications));
                return true;
            }
        }
        
        return false;
    }
    
    private function save($data)
    {
        $backup = $this->dataFile . '.backup';
        if (file_exists($this->dataFile)) {
            copy($this->dataFile, $backup);
        }
        
        file_put_contents(
            $this->dataFile,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
    
    private function generateId()
    {
        return 'notif_' . bin2hex(random_bytes(8));
    }
}
```

#### Task 1.4.3: Notification Controller

**File**: `src/Modules/Notifications/Controllers/NotificationController.php`
```php
<?php
namespace App\Modules\Notifications\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Modules\Notifications\Services\NotificationService;

class NotificationController
{
    private $service;
    
    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }
    
    public function index(Request $request, Response $response)
    {
        $notifications = $this->service->getAll();
        return $this->json($response, $notifications);
    }
    
    public function show(Request $request, Response $response, $args)
    {
        $notification = $this->service->getById($args['id']);
        
        if (!$notification) {
            return $this->json($response, ['error' => 'Not found'], 404);
        }
        
        return $this->json($response, $notification);
    }
    
    public function store(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        
        // Validate
        if (empty($data['title']) || empty($data['content'])) {
            return $this->json($response, [
                'error' => 'Title and content are required'
            ], 400);
        }
        
        $notification = $this->service->create($data);
        return $this->json($response, $notification, 201);
    }
    
    public function update(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $notification = $this->service->update($args['id'], $data);
        
        if (!$notification) {
            return $this->json($response, ['error' => 'Not found'], 404);
        }
        
        return $this->json($response, $notification);
    }
    
    public function destroy(Request $request, Response $response, $args)
    {
        $deleted = $this->service->delete($args['id']);
        
        if (!$deleted) {
            return $this->json($response, ['error' => 'Not found'], 404);
        }
        
        return $this->json($response, ['message' => 'Deleted successfully']);
    }
    
    private function json(Response $response, $data, $status = 200)
    {
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status)
                       ->withHeader('Content-Type', 'application/json');
    }
}
```

#### Task 1.4.4: Notification Routes

**Update**: `config/routes.php`
```php
<?php
use App\Modules\Notifications\Controllers\NotificationController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

// ... previous routes ...

// Notifications API
$app->group('/api/notifications', function ($group) {
    $group->get('', NotificationController::class . ':index');
    $group->get('/{id}', NotificationController::class . ':show');
    $group->post('', NotificationController::class . ':store')
          ->add(new RbacMiddleware(['admin', 'editor']));
    $group->put('/{id}', NotificationController::class . ':update')
          ->add(new RbacMiddleware(['admin', 'editor']));
    $group->delete('/{id}', NotificationController::class . ':destroy')
          ->add(new RbacMiddleware(['admin']));
})->add(AuthMiddleware::class);
```

#### Task 1.4.5: Test Notification Module

```bash
# Login first
TOKEN=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@dotk.gov.in","password":"admin123"}' \
  | jq -r '.token')

# List notifications
curl http://localhost:8080/api/notifications \
  -H "Authorization: Bearer $TOKEN"

# Create notification
curl -X POST http://localhost:8080/api/notifications \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Notification",
    "content": "This is a test",
    "priority": "high"
  }'
```

✅ **Checkpoint**: Notifications module working

---

## 📋 Implementation Checklist

### Phase 1: Core Foundation
- [ ] Environment setup complete
- [ ] Slim framework installed
- [ ] Database tables created
- [ ] Admin user seeded
- [ ] JWT authentication working
- [ ] RBAC middleware functional
- [ ] Notifications module complete
- [ ] Events module complete
- [ ] Tenders module complete
- [ ] Officials module complete
- [ ] API documentation done

### Phase 2: Admin UI
- [ ] Twig templates configured
- [ ] Login page created
- [ ] Dashboard page created
- [ ] Notifications UI done
- [ ] Events UI done
- [ ] Tenders UI done
- [ ] Officials UI done
- [ ] User management UI done
- [ ] 2FA setup page done
- [ ] Responsive design tested

### Phase 3: Advanced Features
- [ ] File uploads working
- [ ] Activity logging enabled
- [ ] Backup system configured
- [ ] Performance optimized
- [ ] Production deployment ready

---

## 🧪 Testing Strategy

### Unit Tests
```bash
# Run PHPUnit tests
./vendor/bin/phpunit tests/Unit
```

### API Testing
```bash
# Use Postman collection
# Import: admin/postman/DOTK-Admin-API.json
```

### Manual Testing Checklist
- [ ] Can login with valid credentials
- [ ] Cannot login with invalid credentials
- [ ] JWT token expires correctly
- [ ] Protected routes require auth
- [ ] RBAC prevents unauthorized access
- [ ] Can CRUD notifications
- [ ] Can CRUD events
- [ ] Can CRUD tenders
- [ ] Can CRUD officials
- [ ] 2FA setup works
- [ ] 2FA login works
- [ ] Recovery codes work
- [ ] File uploads work
- [ ] Forms validate properly

---

## 🚀 Deployment Plan

### Step 1: Prepare Production Environment
```bash
# On GoDaddy server
cd ~/public_html/admin

# Install Composer dependencies (production)
composer install --no-dev --optimize-autoloader

# Set permissions
chmod 755 public
chmod 644 public/.htaccess
chmod 644 public/index.php
chmod -R 755 storage
chmod -R 755 storage/cache
chmod -R 755 storage/logs
```

### Step 2: Environment Configuration
```bash
# Update .env for production
APP_ENV=production
APP_DEBUG=false
JWT_SECRET=<generate-strong-random-key>
```

### Step 3: Database Setup
```bash
# Run migrations on production
php src/Database/migrate.php

# Seed admin user
php src/Database/seed.php
```

### Step 4: Security Checklist
- [ ] Change default admin password
- [ ] Update JWT secret
- [ ] Disable debug mode
- [ ] Enable HTTPS
- [ ] Set secure permissions
- [ ] Enable 2FA for admin
- [ ] Review CORS settings
- [ ] Enable rate limiting

### Step 5: Go Live
```bash
# Test production endpoints
curl https://yourdomain.com/admin/api/health

# Monitor logs
tail -f storage/logs/app.log
```

---

## 📞 Troubleshooting

### Common Issues

**Issue**: 500 Internal Server Error
- Check Apache error logs
- Verify .htaccess configuration
- Check file permissions
- Enable PHP error display temporarily

**Issue**: JWT Token Invalid
- Verify JWT_SECRET in .env
- Check token expiration
- Verify Authorization header format

**Issue**: Database Connection Failed
- Check database file exists
- Verify file permissions
- Check database path in .env

**Issue**: CORS Errors
- Add CORS middleware
- Configure allowed origins
- Check preflight OPTIONS requests

---

## 📚 Resources

### Documentation
- Slim Framework: https://www.slimframework.com/docs/v4/
- JWT: https://jwt.io/
- Eloquent ORM: https://laravel.com/docs/eloquent
- Twig: https://twig.symfony.com/doc/

### Tools
- Postman: https://www.postman.com/
- PHP CodeSniffer: https://github.com/squizlabs/PHP_CodeSniffer
- PHPUnit: https://phpunit.de/

---

## 🎯 Next Steps

1. **Review this implementation plan**
2. **Set up development environment** (Step 0)
3. **Start with Milestone 1.1** (Basic Slim App)
4. **Complete each checkpoint** before moving forward
5. **Test thoroughly** after each milestone
6. **Commit to Git** with meaningful messages

**Ready to start?** Let me know and I'll guide you through each step! 🚀

---

**Last Updated**: January 15, 2026  
**Status**: Implementation Ready  
**Next Action**: Begin Step 0.1 - Development Environment Setup
