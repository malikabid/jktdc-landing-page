#!/usr/bin/env php
<?php

/**
 * CLI Script to close expired tenders
 * 
 * Usage: php close-tenders.php
 * 
 * Set up in crontab:
 * 0 0 * * * php /path/to/close-tenders.php
 */

use App\Commands\CloseTendersCommand;

try {
    // Load autoloader
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Execute the command (handles database setup internally)
    $command = new CloseTendersCommand();
    $result = $command->execute();
    
    exit($result ? 0 : 1);
    
} catch (Exception $e) {
    fwrite(STDERR, "Error: {$e->getMessage()}\n");
    exit(1);
}
