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
        $inventory = Inventories::query()->asObject()->where('INDEX',"==", 5)->first();
        $this->assertEquals(5, $inventory->INDEX);
    }


    public function testStaticLastFunction()
    {
        $inventory = Inventories::query()->asObject()->last();
        $this->assertGreaterThanOrEqual(9, $inventory->INDEX);
    }

    public function testGetDeletedRecords()
    { 
        $inventory = Inventories::query()->asObject()->where("deleted_at","!=", null)->limit(21)->get();
        $this->assertEquals($inventory->first()->deleted_at != null, true);
    }

   public function testStaticIndexFunction()
    {
        $inventory = Inventories::findByIndex(88, ['*'], true);
        $this->assertEquals(88, $inventory->INDEX);
    }

   public function testPaginateFunction()
    {
        //$perPage = 15, $columns = [], $pageName = 'page', $page = null
        $inventory = Inventories::query()->paginate(3,[],'page',2);

        $this->assertEquals(true, $inventory->first()['INDEX'] > 2);
        $this->assertEquals(3, $inventory->first()['INDEX']);
    }

        public function testStaticAllFunction()
    {
        $inventory = Inventories::all();
        $this->assertGreaterThanOrEqual(2475, $inventory->count());     
    }

    public function testGraphqlQueryFunction()
    {   
        $query = ["first"=> 1, "page"=> 1, "filter"=>[
            "INDEX" => ">_100"
        ]];
        $result = Inventories::query()->asObject()->graphql($query);
        $this->assertEquals($result->first()->INDEX, 101);
    }

    public function testGraphqlQueryPaginateFunction()
    {   
        $query = ["first"=> 1, "page"=> 1, "filter"=>[
            "INDEX" => ">_100"
        ]];

        $result = Inventories::query()->graphql($query)->paginate($query["first"],[],'page',$query["page"]);
        $item = $result->first();
         $this->assertEquals($item["INDEX"], 101);
    }

}
       