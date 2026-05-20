<?php

namespace Tests\Unit;

use App\Support\CurrencyRegistry;
use Tests\TestCase;

class CurrencyRegistryTest extends TestCase
{
    public function test_registry_contains_current_and_legacy_currency_codes(): void
    {
        $registry = app(CurrencyRegistry::class);

        $this->assertTrue($registry->isKnown('EUR'));
        $this->assertTrue($registry->isKnown('DKK'));
        $this->assertTrue($registry->isKnown('KES'));
        $this->assertTrue($registry->isKnown('XCG'));
        $this->assertTrue($registry->isKnown('CNH'));
        $this->assertTrue($registry->isKnown('SLL'));
    }

    public function test_registry_normalizes_symbols_and_aliases(): void
    {
        $registry = app(CurrencyRegistry::class);

        $this->assertSame('EUR', $registry->normalize('€'));
        $this->assertSame('USD', $registry->normalize('$'));
        $this->assertSame('TRY', $registry->normalize('TL'));
        $this->assertSame('EUR', $registry->normalize('EURO'));
    }

    public function test_registry_uses_currency_minor_units(): void
    {
        $registry = app(CurrencyRegistry::class);

        $this->assertSame(2, $registry->minorUnit('EUR'));
        $this->assertSame(0, $registry->minorUnit('JPY'));
        $this->assertSame(3, $registry->minorUnit('KWD'));
    }
}
