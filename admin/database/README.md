# Database Migrations with Phinx

This project uses [Phinx](https://phinx.org/) for database migrations.

## Commands

### Run Migrations (Production)
```bash
vendor/bin/phinx migrate -e production
```

### Run Migrations (Development/Docker)
```bash
vendor/bin/phinx migrate -e development
```

### Create New Migration
```bash
vendor/bin/phinx create MigrationName
```

Example:
```bash
vendor/bin/phinx create AddPhoneToUsers
```

### Rollback Last Migration
```bash
vendor/bin/phinx rollback -e production
```

### Check Migration Status
```bash
vendor/bin/phinx status -e production
```

### Seed Database (Optional)
```bash
vendor/bin/phinx seed:run -e production
```

## Migration Structure

Migrations are located in `database/migrations/` directory.

Each migration file must:
- Have a unique timestamp-based filename
- Extend `Phinx\Migration\AbstractMigration`
- Implement the `change()` method

Example migration:
```php
<?php

use Phinx\Migration\AbstractMigration;

class AddPhoneToUsers extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('users');
        $table->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
              ->update();
    }
}
```

## Automated Deployment

Migrations run automatically during GitHub Actions deployment:
1. Code is deployed via FTP
2. Composer dependencies are installed
3. **Phinx migrations run automatically** ✅

## Manual Migration on Server

SSH into the server:
```bash
ssh nw9v11i4x4i5@118.139.182.160 -p22
cd ~/staging.kashmirtourismofficial.com/admin
vendor/bin/phinx migrate -e production
```

## Important Notes

- **Never delete migration files** - this breaks the migration history
- **Always test migrations locally** before pushing to production
- **Rollback capability**: Phinx supports rolling back migrations
- **Version tracking**: Phinx maintains a `phinxlog` table to track which migrations have run

## Environment Configuration

Phinx reads database credentials from `.env` file:
- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_PORT`
- `APP_ENV` (determines which environment to use)
