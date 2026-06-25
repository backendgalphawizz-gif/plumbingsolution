<?php

/**
 * Repairs orphaned InnoDB tablespaces (MySQL error 1813) for the app database.
 * Usage: php scripts/repair-mysql-database.php
 *
 * Stop MySQL first if DROP DATABASE fails with "Directory not empty".
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$database = DB::getDatabaseName();

try {
    $datadir = DB::selectOne("SHOW VARIABLES LIKE 'datadir'")->Value;
} catch (Throwable $e) {
    echo "Could not read datadir: {$e->getMessage()}\n";
    exit(1);
}

$dbPath = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $datadir), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$database;

echo "Database: {$database}\n";
echo "Data path: {$dbPath}\n\n";

if (! is_dir($dbPath)) {
    echo "Folder missing — creating database...\n";
    DB::statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Done. Run: php artisan migrate --seed\n";
    exit(0);
}

$fileCount = count(scandir($dbPath)) - 2;
echo "Found {$fileCount} file(s) in database folder.\n";
echo "Attempting DROP DATABASE...\n";

try {
    DB::statement("DROP DATABASE IF EXISTS `{$database}`");
    echo "Database dropped.\n";
} catch (Throwable $e) {
    echo "DROP DATABASE failed: {$e->getMessage()}\n\n";
    echo "Stop MySQL in XAMPP, then delete this folder manually:\n";
    echo "  {$dbPath}\n\n";
    echo "Then start MySQL and run:\n";
    echo "  php artisan migrate --seed\n";
    exit(1);
}

if (is_dir($dbPath)) {
    echo "Removing leftover folder...\n";
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dbPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($dbPath);
}

DB::statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "Database recreated.\n";
echo "Run: php artisan migrate --seed\n";
