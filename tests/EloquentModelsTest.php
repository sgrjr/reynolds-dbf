<?php namespace Sreynoldsjr\ReynoldsDbf\Test;

use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Allheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Alldetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Ancientheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Ancientdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Backheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Backdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Booktexts;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Broheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Brodetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Inventories;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Passfiles;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Passwords;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Standing_orders;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Vendors;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webdetails;

class EloquentModelsTest extends TestCase
{
    /**
     * Check that the multiply method returns correct result
     * @return void
     */
    public function testNewUpAllClass()
    {   
        $this->assertTrue(new Allheads != null);
        $this->assertTrue(new Alldetails != null);
        $this->assertTrue(new Ancientheads != null);
        $this->assertTrue(new Ancientdetails != null);
        $this->assertTrue(new Backheads != null);
        $this->assertTrue(new Backdetails != null);
        $this->assertTrue(new Booktexts != null);
        $this->assertTrue(new Broheads != null);
        $this->assertTrue(new Brodetails != null);
        $this->assertTrue(new Inventories != null);
        $this->assertTrue(new Passfiles != null);
        $this->assertTrue(new Passwords != null);
        $this->assertTrue(new Standing_orders != null);
        $this->assertTrue(new Vendors != null);
        $this->assertTrue(new Webheads != null);
        $this->assertTrue(new Webdetails != null);
    }
    
    public function testPasswordsDbfConnection()
    {   
        $p = new Passwords(["KEY"=>"8484848484848", "EMAIL"=>"fake@mail.com", "INDEX"=> 5]);
        $this->assertSame($p->KEY, "8484848484848");
        $this->assertSame($p->INDEX, 5);
    }

}