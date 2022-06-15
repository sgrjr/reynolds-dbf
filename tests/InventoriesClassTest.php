<?php namespace Sreynoldsjr\ReynoldsDbf\Test;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Sreynoldsjr\ReynoldsDbf\Models\Inventories;

/*
assertTrue()
assertFalse()
assertEquals()
assertNull()
assertContains()
assertCount()
assertEmpty()
*/

class InventoriesClassTest extends TestCase
{

        public function testStaticFirstFunction()
    {
        $inventory = Inventories::query()->where('INDEX',"==", 5)->first();
        $this->assertEquals(5, $inventory->INDEX);
    }


    public function testStaticLastFunction()
    {
        $inventory = Inventories::query()->last();
        $this->assertGreaterThanOrEqual(9, $inventory->INDEX);
    }

    public function testGetDeletedRecords()
    { //THIS TEST IS FAILING and returning 
        $inventory = Inventories::query()->where("DELETED","==", true)->limit(21)->get();
        $this->assertEquals($inventory->first()->DELETED === true, true);
    }

   public function testStaticIndexFunction()
    {
        $inventory = Inventories::index(88);
        $this->assertEquals(88, $inventory->INDEX);
    }


   public function testPaginateFunction()
    {
        //$perPage = 15, $columns = [], $pageName = 'page', $page = null
        $inventory = Inventories::query()->paginate(3,[],'page',2)['data']->first();
        $this->assertEquals(true, $inventory->INDEX > 2);
    }

        public function testStaticAllFunction()
    {
        $inventory =  Inventories::all();
        $this->assertGreaterThanOrEqual(2475, $inventory->count());     
    }

    public function testGraphqlQueryFunction()
    {   
        $query = ["first"=> 1, "page"=> 1, "filter"=>[
            "INDEX" => ">_100"
        ]];
        $result = Inventories::query()->graphql($query);


        $this->assertSame($result->first()->INDEX, 101);
    }

}
       