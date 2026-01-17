<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration
{
    /**
     * Create users table
     */
    public function change(): void
    {
        // Check if table exists
        if ($this->hasTable('users')) {
            $this->output->writeln('<info>Table "users" already exists, skipping creation...</info>');
            return;
        }

        $table = $this->table('users', ['id' => false, 'primary_key' => 'id']);
        
        $table->addColumn('id', 'integer', [
                'identity' => true,
                'signed' => false,
            ])
            ->addColumn('username', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('email', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('password', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('full_name', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('role', 'enum', [
                'values' => ['super_admin', 'admin', 'editor'],
                'default' => 'editor',
                'null' => false,
            ])
            ->addColumn('is_active', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('last_login_at', 'timestamp', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ])
            ->addIndex(['username'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->addIndex(['role'])
            ->addIndex(['is_active'])
            ->create();
        
        // Insert default super admin user (password: admin123)
        $this->execute("
            INSERT INTO users (username, email, password, full_name, role, is_active) 
            VALUES ('admin', 'admin@dotk.gov.in', '$2y$10\$CVJpXYm0KweK8jLvLmLgUeUxzxOLOBa23pzVHGkJH197k23P5e/Hy', 'Super Administrator', 'super_admin', 1)
        ");
    }
}
