<?php namespace Sreynoldsjr\ReynoldsDbf\Test;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Sreynoldsjr\ReynoldsDbf\Models\Allheads;
use Sreynoldsjr\ReynoldsDbf\Models\Ancientheads;
use Sreynoldsjr\ReynoldsDbf\Models\Backheads;
use Sreynoldsjr\ReynoldsDbf\Models\Broheads;
use Sreynoldsjr\ReynoldsDbf\Models\Webheads;
use Sreynoldsjr\ReynoldsDbf\Models\Vendors;
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

    public function testNewRemoteaddr()
    {   
        $key = "0090100000020";
        $id = Webheads::generateRemoteAddr($key);
        $this->assertTrue(str_contains($id,substr($key,0,5)));
    }

    public function testNewRemoteaddrNoKey()
    {   
        $id = Webheads::generateRemoteAddr();
        $this->assertTrue($id != null);
    }

    public function testUniqueFunction()
    {   

        $result = [Broheads::unique('REMOTEADDR'),
            Backheads::unique('REMOTEADDR'),
            Webheads::unique('REMOTEADDR'),
            Allheads::unique('REMOTEADDR'),
            Ancientheads::unique('REMOTEADDR')
        ];

        $this->assertTrue($result != null);
    }
    
    public function testMakingNewWebhead()
    {   
        $model = Webheads::make(["KEY"=>"0082000000020"]);
        $this->assertSame($model->KEY === "0082000000020", true);
    }

    public function testWritingNewToWebhead()
    {   //error: duplicate fileds names when opening webhead
        $model = Webheads::create(["KEY"=>"0767500000001"]);
        $this->assertSame($model->KEY ===  "0767500000001", true);
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

    public function disabled_testCacheMethod()
    {   
        $this->assertTrue(Webheads::cache());
    }

    public function disabled_compare_before_after_write_webhead(){
        //Webheads::cache();

        Webheads::rebuildFromCache();
    }
}
       