<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelperBsTest extends TestCase
{
    public function test_formatea_cero(): void
    {
        $this->assertSame('Bs. 0,00', bs(0));
    }

    public function test_formatea_monto_entero(): void
    {
        $this->assertSame('Bs. 25,00', bs(25));
    }

    public function test_formatea_monto_con_decimales_y_miles(): void
    {
        $this->assertSame('Bs. 1.234,50', bs(1234.5));
    }

    public function test_formatea_null_como_cero(): void
    {
        $this->assertSame('Bs. 0,00', bs(null));
    }
}
