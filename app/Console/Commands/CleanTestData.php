<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanTestData extends Command
{
    protected $signature = 'app:clean-test-data
                            {--seed : Re-seed demo data after cleanup}
                            {--fresh : Drop all tables and rebuild schema (use when DB tables are corrupted)}
                            {--force : Skip confirmation outside local environment}';

    protected $description = 'Truncate users, vendors, providers, products, services, orders, bookings, payments, and related test data';

    /** @var list<string> */
    private array $tables = [
        'wallet_transactions',
        'order_returns',
        'user_notifications',
        'ticket_messages',
        'tickets',
        'refunds',
        'transactions',
        'payments',
        'order_status_logs',
        'order_items',
        'orders',
        'booking_logs',
        'booking_images',
        'service_bookings',
        'service_provider_reviews',
        'product_reviews',
        'service_provider_service',
        'service_images',
        'service_provider_images',
        'provider_documents',
        'vendor_documents',
        'product_images',
        'product_variants',
        'cart_items',
        'user_addresses',
        'bulk_order_files',
        'quotations',
        'bulk_orders',
        'provider_withdrawals',
        'vendor_withdrawals',
        'user_withdrawals',
        'notifications',
        'personal_access_tokens',
        'products',
        'services',
        'service_providers',
        'vendors',
        'users',
        'password_reset_tokens',
        'sessions',
        'coupons',
        'subcategories',
        'categories',
        'service_categories',
    ];

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to clean test data in production. Use --force if you are sure.');

            return self::FAILURE;
        }

        if (! app()->environment(['local', 'testing']) && ! $this->option('force')) {
            if (! $this->confirm('This will permanently delete all app test data. Continue?')) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }
        }

        if ($this->option('fresh') || ! $this->migrationsTableUsable()) {
            return $this->freshDatabase();
        }

        $this->info('Cleaning test data...');

        Schema::disableForeignKeyConstraints();

        $needsMigrate = false;

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            try {
                DB::table($table)->truncate();
                $this->line("  truncated {$table}");
            } catch (QueryException $e) {
                if ($this->isBrokenTableError($e)) {
                    DB::statement("DROP TABLE IF EXISTS `{$table}`");
                    $needsMigrate = true;
                    $this->warn("  dropped broken table {$table}");
                } else {
                    throw $e;
                }
            }
        }

        Schema::enableForeignKeyConstraints();

        if ($needsMigrate) {
            $this->info('Repairing dropped tables...');
            Artisan::call('migrate', ['--force' => true]);
            $this->line(trim(Artisan::output()));
        }

        $this->info('Test data cleaned. Admins, settings, CMS, FAQs, and banners were kept.');

        if ($this->option('seed')) {
            $this->seedDemoData();
        } else {
            $this->comment('Run with --seed to reload demo categories, users, vendors, providers, orders, and bookings.');
        }

        return self::SUCCESS;
    }

    private function freshDatabase(): int
    {
        $this->warn('Running migrate:fresh — all tables will be dropped and recreated.');

        try {
            $this->runMigrateFresh();
        } catch (QueryException $e) {
            if (! $this->isCorruptedDatabaseError($e)) {
                throw $e;
            }

            $this->warn('Database engine is corrupted — dropping and recreating the database...');
            $this->recreateDatabase();
            Artisan::call('migrate', ['--force' => true]);
            $this->line(trim(Artisan::output()));

            if ($this->option('seed')) {
                $this->seedDemoData();
            }
        }

        $this->info('Database rebuilt.');

        if (! $this->option('seed')) {
            $this->comment('Run with --seed to load admin account and demo data.');
        }

        return self::SUCCESS;
    }

    private function runMigrateFresh(): void
    {
        $params = ['--force' => true];

        if ($this->option('seed')) {
            $params['--seed'] = true;
        }

        Artisan::call('migrate:fresh', $params);
        $this->line(trim(Artisan::output()));
    }

    private function recreateDatabase(): void
    {
        $database = DB::getDatabaseName();

        try {
            DB::statement("DROP DATABASE IF EXISTS `{$database}`");
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), '1010') || str_contains($e->getMessage(), 'Directory not empty')) {
                $this->printOrphanedTablespaceInstructions($database);

                throw $e;
            }

            throw $e;
        }

        DB::statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        DB::purge();
        DB::reconnect();
    }

    private function printOrphanedTablespaceInstructions(string $database): void
    {
        $this->newLine();
        $this->error('MySQL cannot drop the database because orphaned InnoDB files remain on disk.');
        $this->line('Fix on Windows (XAMPP/WAMP/Laragon):');
        $this->line('  1. Stop MySQL from your stack (XAMPP Control Panel → Stop MySQL).');
        $this->line("  2. Delete the folder: <mysql-data>/{$database}");
        $this->line('     Common paths: C:\\xampp\\mysql\\data\\'.$database);
        $this->line('                   C:\\laragon\\data\\mysql\\'.$database);
        $this->line('  3. Start MySQL again.');
        $this->line("  4. Run: php artisan app:clean-test-data --fresh --seed --force");
        $this->newLine();
    }

    private function isCorruptedDatabaseError(QueryException $e): bool
    {
        $message = $e->getMessage();

        return $this->isBrokenTableError($e)
            || str_contains($message, '1813')
            || str_contains($message, 'Tablespace for table');
    }

    private function migrationsTableUsable(): bool
    {
        if (! Schema::hasTable('migrations')) {
            return false;
        }

        try {
            DB::table('migrations')->limit(1)->get();

            return true;
        } catch (QueryException $e) {
            return ! $this->isBrokenTableError($e);
        }
    }

    private function seedDemoData(): void
    {
        $this->info('Re-seeding demo data...');
        Artisan::call('db:seed', ['--force' => true]);
        $this->line(trim(Artisan::output()));
        $this->info('Demo data seeded.');
    }

    private function isBrokenTableError(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, "doesn't exist in engine")
            || str_contains($message, 'Base table or view not found');
    }
}
