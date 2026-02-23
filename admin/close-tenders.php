#!/usr/bin/env php
<?php

/**
 * CLI Script to close expired tenders
 * 
 * Usage: php close-tenders.php
 * 
 * Set up in crontab:
 * 0 0 * * * /bin/bash /path/to/close-tenders.sh
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Setup database connection
use Illuminate\Database\Capsule\Manager as Capsule;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'dotk_admin',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

// Load models
require_once __DIR__ . '/src/Models/Tender.php';

// Execute the command
use App\Commands\CloseTendersCommand;

$command = new CloseTendersCommand();
$result = $command->execute();

exit($result ? 0 : 1);
