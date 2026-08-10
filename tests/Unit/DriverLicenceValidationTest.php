<?php

namespace Tests\Unit;

use App\Http\Controllers\StripeCheckoutController;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class DriverLicenceValidationTest extends TestCase
{
    private function isValid(string $value): bool
    {
        $method = new ReflectionMethod(StripeCheckoutController::class, 'isValidDriverLicenceNumber');
        $method->setAccessible(true);

        return $method->invoke(app(StripeCheckoutController::class), $value);
    }

    #[Test]
    public function junk_licence_numbers_are_rejected(): void
    {
        foreach (['13;', '13', '', '   ', 'a;', 'ab', '1;2', '!!', '12'] as $bad) {
            $this->assertFalse($this->isValid($bad), "Expected '{$bad}' to be invalid");
        }
    }

    #[Test]
    public function real_licence_numbers_are_accepted(): void
    {
        foreach (['8801015800083', 'V04FGH12', 'ABC-1234', '12345', 'AB 123 CD', 'D1234567'] as $good) {
            $this->assertTrue($this->isValid($good), "Expected '{$good}' to be valid");
        }
    }
}
