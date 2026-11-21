<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Records that a one-time legacy import has completed, so seeders never re-import
 * records that an admin has since edited or deleted.
 */
final class CreateLegacyImportsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('legacy_imports', ['id' => false, 'primary_key' => ['name']])
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('imported_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->create();
    }
}
