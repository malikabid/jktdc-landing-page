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
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('notification_no', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('icon', 'string', ['limit' => 10, 'default' => '📄'])
            ->addColumn('show_arrow', 'boolean', ['default' => true])
            ->addColumn('priority', 'enum', [
                'values' => ['low', 'medium', 'high', 'critical'],
                'default' => 'medium'
            ])
            ->addColumn('publish_date', 'date')
            ->addColumn('expiry_date', 'date', ['null' => true])
            ->addColumn('category', 'string', ['limit' => 100, 'default' => 'General'])
            ->addColumn('file_url', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('file_name', 'string', ['limit' => 300, 'null' => true])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['priority'])
            ->addIndex(['publish_date'])
            ->addIndex(['expiry_date'])
            ->addIndex(['category'])
            ->addIndex(['is_active'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }
}
