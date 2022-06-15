<?php namespace Sreynoldsjr\ReynoldsDbf\Test;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Sreynoldsjr\ReynoldsDbf\Models\Webheads;
use Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;
/*
assertTrue()
assertFalse()
assertEquals()
assertNull()
assertContains()
assertCount()
assertEmpty()
*/

class WebheadsClassTest extends TestCase
{

    public function testWebheadClass()
    {   
        $model = new Webheads();
        $this->assertSame(count($model->columns) > 0, true);
    }
    
    public function testWritingNewToWebhead()
    {   
        $model = Webheads::create(["REMOTEADDR"=>"888238848832833"]);
        $this->assertSame($model->REMOTEADDR ===  "888238848832833", true);
    }

   public function testDeletingFromWebhead()
    {   
        $model = Webheads::query()->asObject()->last();
        $this->assertSame($model->delete()->DELETED, true);
    }

}
       