<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNotificationsTable extends AbstractMigration
{
    public function change(): void
    {
        // Create notifications table
        $notifications = $this->table('notifications');
        $notifications
            ->addColumn('title', 'string', ['limit' => 500])
            ->addColumn('notification_number', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Optional reference, e.g. Est/407-A/DTK/14'])
            ->addColumn('description', 'text')
            ->addColumn('icon', 'string', ['limit' => 20, 'default' => '📄'])
            ->addColumn('show_arrow', 'boolean', ['default' => true])
            ->addColumn('priority', 'enum', [
                'values' => ['critical', 'high', 'medium', 'low'],
                'default' => 'medium'
            ])
            ->addColumn('publish_date', 'date')
            ->addColumn('expiry_date', 'date', ['null' => true])
            ->addColumn('category', 'string', ['limit' => 200, 'default' => 'Official'])
            ->addColumn('status', 'enum', [
                'values' => ['draft', 'published', 'archived'],
                'default' => 'draft'
            ])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['status'])
            ->addIndex(['priority'])
            ->addIndex(['publish_date'])
            ->addIndex(['expiry_date'])
            ->addIndex(['category'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();

        // Create notification_documents table
        $documents = $this->table('notification_documents');
        $documents
            ->addColumn('notification_id', 'integer', ['signed' => false])
            ->addColumn('name', 'string', ['limit' => 300])
            ->addColumn('file_path', 'string', ['limit' => 500])
            ->addColumn('file_type', 'string', ['limit' => 50, 'default' => 'pdf'])
            ->addColumn('file_size', 'integer', ['null' => true, 'comment' => 'Size in bytes'])
            ->addColumn('sort_order', 'integer', ['default' => 0])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['notification_id'])
            ->addForeignKey('notification_id', 'notifications', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}
