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
        $model = Webheads::create(["REMOTEADDR"=>"66666666666"]);
        $this->assertSame($model->REMOTEADDR ===  "66666666666", true);
    }

   public function test_deleting_from_webhead()
    {   
        $model = Webheads::query()->asObject()->first();
        $this->assertSame($model->delete()->trashed(), true);
    }

    public function test_restoring_from_webhead()
    {   
        $model = Webheads::query()->asObject()->first();
        $this->assertSame($model->restore()->trashed(), false);
    }

}
       