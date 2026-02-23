<?php

namespace App\Commands;

use App\Models\Tender;
use Illuminate\Database\Capsule\Manager as Capsule;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class CloseTendersCommand
{
    private $logger;
    
    public function __construct()
    {
        // Initialize logger
        $logPath = __DIR__ . '/../../storage/logs/tender-cron.log';
        $this->logger = new Logger('TenderCron');
        $this->logger->pushHandler(new StreamHandler($logPath, Logger::INFO));
        
        // Setup database if not already configured
        $this->initializeDatabase();
    }
    
    /**
     * Initialize database connection if not already set up
     */
    private function initializeDatabase()
    {
        try {
            // Load environment variables
            $appPath = __DIR__ . '/../../';
            if (file_exists($appPath . '.env')) {
                $dotenv = \Dotenv\Dotenv::createImmutable($appPath);
                $dotenv->load();
            }
            
            // Configure database (safe to call multiple times)
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
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize database: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Close expired tenders and return result data
     * Used by CLI, web, and API endpoints
     */
    public function closeExpiredTenders(): array
    {
        $today = date('Y-m-d');
        
        // Find all tenders with closing date < today that are not already closed/cancelled
        $expiredTenders = Tender::whereIn('status', ['draft', 'active', 'extended'])
            ->where('closing_date', '<', $today)
            ->get();
        
        if ($expiredTenders->isEmpty()) {
            return ['count' => 0, 'tenders' => []];
        }
        
        $closedTenders = [];
        foreach ($expiredTenders as $tender) {
            $tender->status = Tender::STATUS_CLOSED;
            $tender->save();
            
            $closedTenders[] = [
                'id' => $tender->id,
                'title' => $tender->title,
                'tender_number' => $tender->tender_number,
                'closing_date' => $tender->closing_date->format('Y-m-d'),
            ];
        }
        
        return ['count' => count($closedTenders), 'tenders' => $closedTenders];
    }
    
    /**
     * Execute the command to close expired tenders (CLI usage)
     */
    public function execute()
    {
        try {
            $this->logger->info('Starting tender closure check');
            
            $result = $this->closeExpiredTenders();
            
            if ($result['count'] === 0) {
                $this->logger->info('No expired tenders found to close');
                echo "✓ No expired tenders found.\n";
                return true;
            }
            
            foreach ($result['tenders'] as $tender) {
                $this->logger->info("Closed tender #{$tender['id']}: {$tender['title']} (Closing date: {$tender['closing_date']})");
                echo "✓ Closed: [{$tender['id']}] {$tender['title']}\n";
            }
            
            $this->logger->info("Tender closure check completed. {$result['count']} tender(s) closed.");
            echo "\n✓ Completed: {$result['count']} tender(s) closed.\n";
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fatal error in tender closure command: ' . $e->getMessage());
            echo "✗ Error: {$e->getMessage()}\n";
            return false;
        }
    }
}
