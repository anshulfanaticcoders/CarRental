<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        $this->ensureSafeTestingDatabase($_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: null);

        parent::setUp();

        $this->ensureSafeTestingDatabase(
            config('database.connections.'.config('database.default').'.database')
        );

        URL::defaults(['locale' => 'en']);
    }

    private function ensureSafeTestingDatabase(?string $databaseName): void
    {
        $databaseName = strtolower((string) $databaseName);

        if ($databaseName === ':memory:' || str_contains($databaseName, 'test')) {
            return;
        }

        throw new \RuntimeException("Refusing to run tests against non-test database [{$databaseName}].");
    }
}
