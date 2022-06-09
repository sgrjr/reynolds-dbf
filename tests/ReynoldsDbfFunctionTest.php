<?php namespace Sreynoldsjr\ReynoldsDbf\Test;

use ReynoldsDbf;

class ReynoldsDbfFunctionTest extends TestCase
{
    /**
     * Check that the multiply method returns correct result
     * @return void
     */
    public function testMultiplyReturnsCorrectValue()
    {
        $this->assertSame(ReynoldsDbf::multiply(4, 4), 16);
        $this->assertSame(ReynoldsDbf::multiply(2, 9), 18);
    }
}