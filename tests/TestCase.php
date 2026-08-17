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
        $this->ensureSafeTestingMail();

        URL::defaults(['locale' => 'en']);
    }

    private function ensureSafeTestingMail(): void
    {
        // AppServiceProvider overrides cached config before the mailer resolves.
        // Repeat the assignment here and discard any previously resolved mailer
        // so every test has a fail-closed, non-delivering transport.
        config([
            'mail.default' => 'array',
            'mail.mailers.array' => ['transport' => 'array'],
        ]);
        app('mail.manager')->forgetMailers();

        if (config('mail.default') !== 'array') {
            throw new \RuntimeException('Refusing to run tests with a delivering mail transport.');
        }
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
