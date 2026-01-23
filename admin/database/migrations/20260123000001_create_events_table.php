<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEventsTable extends AbstractMigration
{
    public function change(): void
    {
        // Create events table - matches pub/data/events.json structure
        $events = $this->table('events');
        $events
            ->addColumn('title', 'string', ['limit' => 255])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('startDate', 'date')
            ->addColumn('endDate', 'date', ['null' => true])
            ->addColumn('location', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('category', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('videoUrl', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('thumbnail', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('file_path', 'string', ['limit' => 500, 'null' => true, 'after' => 'thumbnail'])
            ->addColumn('file_type', 'string', ['limit' => 100, 'null' => true, 'after' => 'file_path'])
            ->addColumn('cta_text', 'string', ['limit' => 100, 'null' => true, 'after' => 'file_type'])
            ->addColumn('cta_link', 'string', ['limit' => 500, 'null' => true, 'after' => 'cta_text'])
            ->addColumn('showOnHomepage', 'boolean', ['default' => false])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['startDate'])
            ->addIndex(['category'])
            ->addIndex(['showOnHomepage'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }
}
