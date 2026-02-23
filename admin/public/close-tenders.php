<?php

/**
 * Web Script to trigger tender auto-closure
 * 
 * Access: https://yourdomain.com/admin/close-tenders.php
 * 
 * For cron: curl https://yourdomain.com/admin/close-tenders.php?cron_token=YOUR_TOKEN
 */

define('APP_PATH', dirname(__DIR__));
define('IS_CRON', !empty($_GET['cron_token']) || !empty($_SERVER['HTTP_X_CRON_TOKEN']));

// Cron authentication
if (IS_CRON) {
    $provided_token = $_GET['cron_token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? '';
    $expected_token = getenv('CRON_SECRET_TOKEN');
    
    if (empty($expected_token) || $provided_token !== $expected_token) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid cron token']));
    }
} else {
    // Browser access - require console output (for manual testing)
    // In production, you might want to add authentication here
}

// Load environment
try {
    require APP_PATH . '/vendor/autoload.php';
    
    $dotenv = \Dotenv\Dotenv::createImmutable(APP_PATH);
    $dotenv->load();
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Failed to load environment: ' . $e->getMessage()]));
}

// Setup database
use Illuminate\Database\Capsule\Manager as Capsule;

try {
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
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// Load models
use App\Models\Tender;

// Execute closure logic
try {
    $today = date('Y-m-d');
    
    // Find expired tenders
    $expiredTenders = Tender::whereIn('status', ['draft', 'active', 'extended'])
        ->where('closing_date', '<', $today)
        ->get();
    
    if ($expiredTenders->isEmpty()) {
        $response = [
            'success' => true,
            'message' => 'No expired tenders found',
            'closed_count' => 0,
            'tenders' => []
        ];
        
        if (!IS_CRON) {
            echo "✓ No expired tenders found.\n";
        }
    } else {
        $closedTenders = [];
        $closedCount = 0;
        
        foreach ($expiredTenders as $tender) {
            $tender->status = Tender::STATUS_CLOSED;
            $tender->save();
            
            $closedTenders[] = [
                'id' => $tender->id,
                'title' => $tender->title,
                'tender_number' => $tender->tender_number,
                'closing_date' => $tender->closing_date->format('Y-m-d'),
            ];
            
            if (!IS_CRON) {
                echo "✓ Closed: [{$tender->id}] {$tender->title}\n";
            }
            
            $closedCount++;
        }
        
        $response = [
            'success' => true,
            'message' => "{$closedCount} tender(s) closed successfully",
            'closed_count' => $closedCount,
            'tenders' => $closedTenders
        ];
        
        if (!IS_CRON) {
            echo "\n✓ Completed: {$closedCount} tender(s) closed.\n";
        }
    }
    
    // Return JSON for cron, text for browser
    if (IS_CRON || (isset($_GET['format']) && $_GET['format'] === 'json')) {
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    
    if (IS_CRON || (isset($_GET['format']) && $_GET['format'] === 'json')) {
        http_response_code(500);
        header('Content-Type: application/json');
        die(json_encode([
            'success' => false,
            'error' => 'Failed to auto-close tenders: ' . $error_message
        ]));
    } else {
        http_response_code(500);
        die("✗ Error: {$error_message}\n");
    }
}
