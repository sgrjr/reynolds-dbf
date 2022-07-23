<?php namespace Sreynoldsjr\ReynoldsDbf\Test;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Sreynoldsjr\ReynoldsDbf\Models\Passwords;

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

    public function test_cache_all_previously_purchased()
    {
        $this->assertTrue(has_method(Passwords::class, 'buildCache'));
    }


   
}
       