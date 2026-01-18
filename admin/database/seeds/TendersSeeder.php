<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class TendersSeeder extends AbstractSeed
{
    public function run(): void
    {
        // Read existing tenders from JSON file
        // Try multiple paths
        $paths = [
            '/tmp/tenders.json',
            '/var/www/admin/../pub/data/tenders.json',
            dirname(__DIR__, 3) . '/pub/data/tenders.json',
        ];
        
        $jsonPath = null;
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $jsonPath = $path;
                break;
            }
        }
        
        if (!$jsonPath) {
            echo "tenders.json not found in any of the expected paths\n";
            return;
        }
        
        $jsonContent = file_get_contents($jsonPath);
        $tenders = json_decode($jsonContent, true);
        
        if (!$tenders) {
            echo "No tenders found in JSON file\n";
            return;
        }
        
        echo "Found " . count($tenders) . " tenders to import\n";
        
        $tendersTable = $this->table('tenders');
        $documentsTable = $this->table('tender_documents');
        
        foreach ($tenders as $tender) {
            // Parse estimated value (remove ₹ and commas, convert to paise)
            $estimatedValue = null;
            if (!empty($tender['estimatedValue'])) {
                $value = preg_replace('/[₹,\s]/', '', $tender['estimatedValue']);
                if (is_numeric($value)) {
                    $estimatedValue = (int)($value * 100);
                }
            }
            
            // Map status
            $status = $tender['status'] ?? 'active';
            if ($status === 'active') {
                // Check if it's past closing date
                $closingDate = new DateTime($tender['closingDate']);
                $now = new DateTime();
                if ($closingDate < $now) {
                    $status = 'closed';
                }
            }
            
            // Insert tender
            $tenderData = [
                'title' => $tender['title'],
                'description' => $tender['description'] ?? null,
                'tender_number' => $tender['tenderNumber'],
                'publish_date' => $tender['publishDate'],
                'closing_date' => $tender['closingDate'],
                'estimated_value' => $estimatedValue,
                'category' => $tender['category'] ?? 'Other',
                'status' => $status,
                'department' => $tender['department'] ?? 'Directorate of Tourism Kashmir',
                'contact_person' => $tender['contactPerson'] ?? null,
                'contact_email' => $tender['contactEmail'] ?? null,
                'contact_phone' => $tender['contactPhone'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            $this->insert('tenders', $tenderData);
            
            // Get the inserted tender ID
            $tenderId = $this->getAdapter()->getConnection()->lastInsertId();
            
            // Insert documents
            if (!empty($tender['documents'])) {
                foreach ($tender['documents'] as $index => $doc) {
                    $this->insert('tender_documents', [
                        'tender_id' => $tenderId,
                        'name' => $doc['name'],
                        'file_path' => $doc['url'],
                        'file_type' => $doc['type'] ?? 'pdf',
                        'sort_order' => $index,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
            
            echo "Imported: {$tender['tenderNumber']}\n";
        }
        
        echo "Import completed!\n";
    }
}
