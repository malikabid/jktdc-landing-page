<?php

/**
 * Cron Script to trigger tender auto-closure
 * 
 * For cron: curl https://yourdomain.com/admin/close-tenders.php
 */

define('APP_PATH', dirname(__DIR__));

// Load autoloader
try {
    require APP_PATH . '/vendor/autoload.php';
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Failed to load autoloader: ' . $e->getMessage()]));
}

// Load models and command
use App\Commands\CloseTendersCommand;

// Execute closure logic
try {
    $command = new CloseTendersCommand();
    $result = $command->closeExpiredTenders();
    
    $response = [
        'success' => true,
        'message' => $result['count'] > 0 ? "{$result['count']} tender(s) closed successfully" : 'No expired tenders found',
        'closed_count' => $result['count'],
        'tenders' => $result['tenders']
    ];
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]));
}
