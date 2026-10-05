<?php

namespace Tests\Unit;

use App\Support\Decimal;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DecimalTest extends TestCase
{
    public function test_compares_exactly_where_floats_would_not(): void
    {
        $this->assertSame(0, Decimal::compare('0.3', '0.300000'));
        $this->assertSame(1, Decimal::compare('0.240001', '0.24'));
        $this->assertSame(-1, Decimal::compare('-0.1', '0'));
        $this->assertSame(0, Decimal::compare('+5', '5.'));
        $this->assertSame(0, Decimal::compare('.5', '0.5'));
    }

    public function test_formats_without_trailing_zeros(): void
    {
        $this->assertSame('0.24', Decimal::format('0.240000'));
        $this->assertSame('355', Decimal::format('355.000000'));
        $this->assertSame('-0.5', Decimal::format('-0.500'));
    }

    public function test_rejects_anything_but_plain_decimals(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Decimal::compare('1e3', '1');
    }
}
