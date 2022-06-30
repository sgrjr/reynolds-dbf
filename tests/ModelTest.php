<?php namespace Sreynoldsjr\ReynoldsDbf\Test;

use Sreynoldsjr\ReynoldsDbf\Models\Model;
use Sreynoldsjr\ReynoldsDbf\Models\Webheads;
use Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;

class ModelTest extends TestCase
{
    /**
     * Check that the multiply method returns correct result
     * @return void
     */
    public function testWebheadModelColumnsClass()
    {   
        foreach(ReynoldsDbf::all() AS $model){
            $this->assertSame(count($model->columns) > 0, true);
        }
    }
    
    public function testWebheadClass()
    {   
        $model = ReynoldsDbf::model('webheads');
        $this->assertSame(count($model->columns) > 0, true);
    }

    public function testVendorClass()
    {   
        $model = ReynoldsDbf::model('vendors');
        $m = $model->asObject()->where("INDEX","==",0)->first();
        $this->assertSame(count($model->columns) > 0, true);
    }

}