<?php namespace Sreynoldsjr\ReynoldsDbf\Test;

use Sreynoldsjr\ReynoldsDbf\Models\Model;
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
        $model = ReynoldsDbf::model('webhead');
        $this->assertSame(count($model->columns) > 0, true);
    }

    public function testQueryFunctionClass()
    {   
        $query = '{"query": "query { echo(message: \"Hello World\") }" }';
        $result = ReynoldsDbf::query($query);
        $this->assertSame($result != null, true);
    }

}