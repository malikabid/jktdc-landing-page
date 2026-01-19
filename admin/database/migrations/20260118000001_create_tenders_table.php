<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTendersTable extends AbstractMigration
{
    public function change(): void
    {
        // Create tenders table
        $tenders = $this->table('tenders');
        $tenders
            ->addColumn('title', 'string', ['limit' => 500])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('tender_number', 'string', ['limit' => 100])
            ->addColumn('reference_number', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('publish_date', 'date')
            ->addColumn('closing_date', 'date')
            ->addColumn('extended_date', 'date', ['null' => true])
            ->addColumn('estimated_value', 'biginteger', ['null' => true, 'comment' => 'Value in paise (INR * 100)'])
            ->addColumn('category', 'string', ['limit' => 200])
            ->addColumn('status', 'enum', [
                'values' => ['draft', 'active', 'extended', 'closed', 'cancelled'],
                'default' => 'draft'
            ])
            ->addColumn('department', 'string', ['limit' => 300, 'default' => 'Directorate of Tourism Kashmir'])
            ->addColumn('contact_person', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('contact_email', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('contact_phone', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['tender_number'], ['unique' => true])
            ->addIndex(['status'])
            ->addIndex(['publish_date'])
            ->addIndex(['closing_date'])
            ->addIndex(['category'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();

        // Create tender_documents table
        $documents = $this->table('tender_documents');
        $documents
            ->addColumn('tender_id', 'integer', ['signed' => false])
            ->addColumn('name', 'string', ['limit' => 300])
            ->addColumn('file_path', 'string', ['limit' => 500])
            ->addColumn('file_type', 'string', ['limit' => 50, 'default' => 'pdf'])
            ->addColumn('file_size', 'integer', ['null' => true, 'comment' => 'Size in bytes'])
            ->addColumn('sort_order', 'integer', ['default' => 0])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['tender_id'])
            ->addForeignKey('tender_id', 'tenders', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}
