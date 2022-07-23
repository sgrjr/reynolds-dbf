<?php namespace Sreynoldsjr\ReynoldsDbf\Test;

use Sreynoldsjr\ReynoldsDbf\Console\Commands\Install;

class InstallCommandTest extends TestCase
{
    /**
     * Check that the multiply method returns correct result
     * @return void
     */
    public function testCanNewUpClass()
    {
        $this->assertSame(new Install !== null, true);
    }

    public function testTrueAssetsToTrue()
    {
        $condition = true;
        $this->assertTrue($condition);
    }

}