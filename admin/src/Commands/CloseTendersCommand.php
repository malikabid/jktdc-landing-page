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
    }
    
    /**
     * Execute the command to close expired tenders
     */
    public function execute()
    {
        try {
            $this->logger->info('Starting tender closure check');
            
            // Get today's date at midnight
            $today = date('Y-m-d');
            
            // Find all tenders with closing date < today that are not already closed/cancelled
            $expiredTenders = Tender::whereIn('status', ['draft', 'active', 'extended'])
                ->where('closing_date', '<', $today)
                ->get();
            
            if ($expiredTenders->isEmpty()) {
                $this->logger->info('No expired tenders found to close');
                echo "✓ No expired tenders found.\n";
                return true;
            }
            
            $closedCount = 0;
            
            foreach ($expiredTenders as $tender) {
                try {
                    $tender->status = Tender::STATUS_CLOSED;
                    $tender->save();
                    
                    $this->logger->info("Closed tender #{$tender->id}: {$tender->title} (Closing date: {$tender->closing_date})");
                    $closedCount++;
                    
                    echo "✓ Closed: [{$tender->id}] {$tender->title}\n";
                } catch (\Exception $e) {
                    $this->logger->error("Failed to close tender #{$tender->id}: " . $e->getMessage());
                    echo "✗ Failed to close tender #{$tender->id}: {$e->getMessage()}\n";
                }
            }
            
            $this->logger->info("Tender closure check completed. {$closedCount} tender(s) closed.");
            echo "\n✓ Completed: {$closedCount} tender(s) closed.\n";
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Fatal error in tender closure command: ' . $e->getMessage());
            echo "✗ Error: {$e->getMessage()}\n";
            return false;
        }
    }
}
