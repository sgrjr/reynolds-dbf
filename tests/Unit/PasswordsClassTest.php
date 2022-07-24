<?php namespace Sreynoldsjr\ReynoldsDbf\Test\Unit;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Vendors;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Inventories;
use ReflectionClass;

/*
assertTrue()
assertFalse()
assertEquals()
assertNull()
assertContains()
assertCount()
assertEmpty()
*/

class PasswordsClassTest extends TestCase
{

    public function test_cache_vendor_info()
    {
        $rc = new ReflectionClass(Vendors::class);
        $this->assertTrue($rc->hasMethod('buildCache'));
    }

    public function test_cache_inventory_info()
    {
        $rc = new ReflectionClass(Inventories::class);
        $this->assertTrue($rc->hasMethod('buildCache'));
    }
   
}
       