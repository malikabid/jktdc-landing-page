<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class EventsSeeder extends AbstractSeed
{
    public function run(): void
    {
        // Read existing events from JSON file
        // Try multiple paths
        $paths = [
            '/tmp/events.json',
            '/var/www/admin/../pub/data/events.json',
            dirname(__DIR__, 3) . '/pub/data/events.json',
        ];
        
        $jsonPath = null;
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $jsonPath = $path;
                break;
            }
        }
        
        if (!$jsonPath) {
            echo "events.json not found in any of the expected paths\n";
            return;
        }
        
        $jsonContent = file_get_contents($jsonPath);
        $events = json_decode($jsonContent, true);
        
        if (!$events) {
            echo "No events found in JSON file\n";
            return;
        }
        
        echo "Found " . count($events) . " events to import\n";
        
        $eventsTable = $this->table('events');
        
        // Get admin user ID (created_by)
        $adminUser = $this->fetchRow('SELECT id FROM users WHERE role = "super_admin" LIMIT 1');
        $createdBy = $adminUser ? $adminUser['id'] : 1;
        
        $eventData = [];
        foreach ($events as $event) {
            $eventData[] = [
                'title' => $event['title'],
                'description' => $event['description'] ?? null,
                'startDate' => $event['startDate'],
                'endDate' => $event['endDate'] ?? null,
                'location' => $event['location'] ?? null,
                'category' => $event['category'] ?? 'Event',
                'videoUrl' => $event['videoUrl'] ?? null,
                'thumbnail' => $event['thumbnail'] ?? null,
                'showOnHomepage' => $event['showOnHomepage'] ?? false,
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }
        
        // Insert all events in bulk
        $eventsTable->insert($eventData)->save();
        
        echo "Successfully imported " . count($eventData) . " events\n";
    }
}
