<?php

return [
    'displayErrorDetails' => $_ENV['APP_DEBUG'] === 'true',
    'logErrors' => true,
    'logErrorDetails' => true,
    
    'app' => [
        'version' => $_ENV['APP_VERSION'] ?? '1.0.0',
        'name' => 'DOTK Admin',
    ],
    
    'db' => [
        'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? 'dotk_admin',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],
    
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'],
        'algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
        'expiration' => (int)($_ENV['JWT_EXPIRATION'] ?? 86400), // 24 hours
    ],
    
    'twoFactor' => [
        'issuer' => $_ENV['TWO_FACTOR_ISSUER'] ?? 'DOTK Admin',
    ],
    
    'twig' => [
        'path' => __DIR__ . '/../templates',
        'cache' => $_ENV['APP_ENV'] === 'production' 
            ? __DIR__ . '/../storage/cache'
            : false,
    ],
    
    'logger' => [
        'name' => 'app',
        'path' => __DIR__ . '/' . ($_ENV['LOG_PATH'] ?? '../storage/logs/app.log'),
        'level' => \Monolog\Logger::toMonologLevel($_ENV['LOG_LEVEL'] ?? 'debug'),
    ],
];
