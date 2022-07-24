<?php namespace Sreynoldsjr\ReynoldsDbf\Test\Unit;

use Sreynoldsjr\ReynoldsDbf\Models\Model;
use Sreynoldsjr\ReynoldsDbf\Models\Webheads;
use Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;
use Sreynoldsjr\ReynoldsDbf\Models\Ancientdetails;

class ModelTest extends TestCase
{
    /**
     * Check that the multiply method returns correct result
     * @return void
     */
    public function testWebheadModelColumnsClass()
    {   
        foreach(ReynoldsDbf::all() AS $model){
            $this->assertSame(count($model->database()->getMeta()) > 0, true);
        }
    }
    
    public function testWebheadClass()
    {   
        $model = ReynoldsDbf::model('webheads');
        $this->assertTrue(count($model->database()->getMeta()) > 0);
    }

    public function testVendorClass()
    {   
        $model = ReynoldsDbf::model('vendors');
        $m = $model->asObject()->where("INDEX","==",0)->first();

        $this->assertSame(count($model->database()->getMeta()) > 0, true);
    }

    public function testVendorMetaClass()
    {   
        $model = ReynoldsDbf::model('vendors');
        $m = $model->asObject()->where("INDEX","==",0)->first();

        $m->database()->getMeta();
        $this->assertSame(count($model->database()->getMeta()) > 0, true);
    }

    public function testNewVendorMetaClass()
    {   
        $model = ReynoldsDbf::model('vendors');
        $attributes = ["KEY"=>99999954];
        $m = $model->database()->make($attributes);
        $this->assertSame(count($m->meta()) > 0, true);
    }

}