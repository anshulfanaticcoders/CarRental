<?php

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use Illuminate\Mail\Transport\ArrayTransport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestingMailIsolationTest extends TestCase
{
    #[Test]
    public function the_test_environment_cannot_resolve_a_delivering_mail_transport(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('array', config('mail.default'));
        $this->assertInstanceOf(
            ArrayTransport::class,
            app('mail.manager')->mailer()->getSymfonyTransport()
        );
    }

    #[Test]
    public function a_test_named_database_disables_delivery_even_if_the_environment_is_mislabelled(): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'carrental_testing',
            'mail.default' => 'smtp',
        ]);

        (new AppServiceProvider(app()))->register();
        app('mail.manager')->forgetMailers();

        $this->assertSame('array', config('mail.default'));
        $this->assertInstanceOf(
            ArrayTransport::class,
            app('mail.manager')->mailer()->getSymfonyTransport()
        );
    }
}
