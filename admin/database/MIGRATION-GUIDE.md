# Database Migration Guide

This project uses **Phinx** for database schema management. Migrations allow you to version control your database schema changes.

## Why Phinx?

- **Version Control**: Track database schema changes in Git
- **Automated Deployment**: Migrations run automatically on deployment
- **Rollback Support**: Revert changes if needed
- **Team Collaboration**: Multiple developers can work on schema changes without conflicts

## Quick Reference

### Check Migration Status

```bash
# Local Docker
docker exec dotk-php-fpm /bin/sh -c "cd /var/www/admin && vendor/bin/phinx status -e development"

# Production (GoDaddy SSH)
cd ~/staging.kashmirtourismofficial.com/admin
vendor/bin/phinx status -e production
```

### Run Pending Migrations

```bash
# Local Docker
docker exec dotk-php-fpm /bin/sh -c "cd /var/www/admin && vendor/bin/phinx migrate -e development"

# Production (GoDaddy SSH)
cd ~/staging.kashmirtourismofficial.com/admin
vendor/bin/phinx migrate -e production
```

### Create New Migration

```bash
# From admin/ directory
docker exec dotk-php-fpm /bin/sh -c "cd /var/www/admin && vendor/bin/phinx create DescriptiveMigrationName"
```

This creates a new migration file in `database/migrations/` with a timestamp prefix.

### Rollback Last Migration

```bash
# Local Docker
docker exec dotk-php-fpm /bin/sh -c "cd /var/www/admin && vendor/bin/phinx rollback -e development"

# Production
cd ~/staging.kashmirtourismofficial.com/admin
vendor/bin/phinx rollback -e production
```

## Deployment Flow

### Automated on GoDaddy

When you push to the `feature/admin-site` branch:

1. GitHub Actions uploads files via FTP
2. SSH connects to server
3. Runs `composer install --no-dev`
4. Runs `vendor/bin/phinx migrate -e production` automatically
5. Deployment complete!

### Manual SSH Deployment

If you need to manually deploy:

```bash
# SSH into server
ssh nw9v11i4x4i5@118.139.182.160

# Navigate to admin directory
cd ~/staging.kashmirtourismofficial.com/admin

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
vendor/bin/phinx migrate -e production
```

## Creating Migrations

### Example: Add New Table

```bash
docker exec dotk-php-fpm /bin/sh -c "cd /var/www/admin && vendor/bin/phinx create AddActivityLogsTable"
```

Edit the generated file in `database/migrations/`:

```php
<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddActivityLogsTable extends AbstractMigration
{
    public function change(): void
    {
        // Check if table exists (for idempotency)
        if ($this->hasTable('activity_logs')) {
            $this->output->writeln('<info>Table "activity_logs" already exists, skipping...</info>');
            return;
        }

        $table = $this->table('activity_logs');
        $table->addColumn('user_id', 'integer', ['signed' => false])
              ->addColumn('action', 'string', ['limit' => 100])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('ip_address', 'string', ['limit' => 45])
              ->addColumn('user_agent', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', [
                  'delete' => 'CASCADE',
                  'update' => 'CASCADE'
              ])
              ->addIndex(['user_id'])
              ->addIndex(['action'])
              ->create();
    }
}
```

### Example: Modify Existing Table

```bash
docker exec dotk-php-fpm /bin/sh -c "cd /var/www/admin && vendor/bin/phinx create AddProfilePictureToUsers"
```

```php
<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProfilePictureToUsers extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        
        // Check if column exists
        if (!$table->hasColumn('profile_picture')) {
            $table->addColumn('profile_picture', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'full_name'
            ])->update();
        }
    }
}
```

## Best Practices

### 1. Always Check Existence

Make migrations idempotent by checking if tables/columns exist:

```php
if ($this->hasTable('table_name')) {
    return;
}

if (!$table->hasColumn('column_name')) {
    $table->addColumn(...)->update();
}
```

### 2. Use Descriptive Names

Good: `AddEmailVerificationToUsers`  
Bad: `UpdateUsers2`

### 3. Test Locally First

Always test migrations in Docker before deploying:

```bash
# Run migration
docker exec dotk-php-fpm /bin/sh -c "cd /var/www/admin && vendor/bin/phinx migrate -e development"

# Verify
docker exec dotk-php-fpm /bin/sh -c "cd /var/www/admin && vendor/bin/phinx status -e development"

# Test rollback
docker exec dotk-php-fpm /bin/sh -c "cd /var/www/admin && vendor/bin/phinx rollback -e development"
```

### 4. Don't Modify Old Migrations

Once a migration is deployed to production, don't modify it. Create a new migration instead.

### 5. Commit Migrations with Code

Always commit migrations in the same commit as the code that uses the new schema.

## Troubleshooting

### "Table already exists" Error

If you see this error, it means the table was created manually. Update your migration to check existence:

```php
if ($this->hasTable('users')) {
    $this->output->writeln('<info>Table already exists, skipping...</info>');
    return;
}
```

### Connection Error

Check your `.env` file has correct database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=dokt_staging
DB_USERNAME=dokt_staging_admin
DB_PASSWORD=W@AmHZ7tPMq0
```

### Migration Stuck "Pending"

If a migration shows as pending but should be complete:

```bash
# Force mark as completed (use with caution)
vendor/bin/phinx migrate --fake
```

## Configuration

Phinx configuration is in `phinx.php`:

- **Development**: Uses Docker MySQL container (`DB_HOST=mysql`)
- **Production**: Uses localhost (`DB_HOST=localhost`)

Both environments load from `.env` file.

## Additional Resources

- [Phinx Documentation](https://book.cakephp.org/phinx/0/en/index.html)
- [Migration Commands](https://book.cakephp.org/phinx/0/en/commands.html)
- [Writing Migrations](https://book.cakephp.org/phinx/0/en/migrations.html)

## Summary

✅ Migrations run automatically on GitHub Actions deployment  
✅ Local testing via Docker container  
✅ Idempotent migrations (safe to run multiple times)  
✅ Full rollback support  
✅ Version controlled schema changes  

For questions or issues, check the main `database/README.md` file.
