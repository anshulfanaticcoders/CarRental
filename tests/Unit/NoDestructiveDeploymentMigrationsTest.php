<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoDestructiveDeploymentMigrationsTest extends TestCase
{
    #[Test]
    public function pending_release_migrations_do_not_delete_or_drop_data_in_up(): void
    {
        $migrationFiles = glob(database_path('migrations/2026_08_17_*.php')) ?: [];

        $this->assertNotEmpty($migrationFiles);

        foreach ($migrationFiles as $migrationFile) {
            $contents = (string) file_get_contents($migrationFile);
            $upStart = strpos($contents, 'public function up(): void');
            $downStart = strpos($contents, 'public function down(): void');

            $this->assertNotFalse($upStart, basename($migrationFile).' has no up() method.');
            $this->assertNotFalse($downStart, basename($migrationFile).' has no down() method.');

            $upMethod = substr($contents, $upStart, $downStart - $upStart);

            $this->assertDoesNotMatchRegularExpression(
                '/Schema::drop|->drop(?:Column|Index|Unique|Foreign)?\s*\(|->delete\s*\(|\bDELETE\s+FROM\b|\bDROP\s+TABLE\b|\bTRUNCATE\b/i',
                $upMethod,
                basename($migrationFile).' contains destructive deployment behavior.'
            );
        }
    }
}
