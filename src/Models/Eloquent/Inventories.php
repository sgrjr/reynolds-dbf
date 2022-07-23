<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Attribute, Cache, Schema;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\CacheTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\LevelTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeInventoriesTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\EloquentInventoriesTrait;
use Sreynoldsjr\ReynoldsDbf\Helpers\Misc;
//use Illuminate\Database\Eloquent\Model as BaseModel;

class Inventories extends BaseModel implements ModelInterface {

    use CacheTrait, LevelTrait, InitializeInventoriesTrait, EloquentInventoriesTrait, SoftDeletes;
    public $connection = 'mysql';
    protected $table = 'inventories';
    protected $dbfPrimaryKey = 'ISBN';
    protected $appends = ['coverArt','marcLink','purchased','title','isClearance','user'];
    public $migration = "2022_00_00_03_inventories.php";
    protected $indexes = ["ISBN"];
    public $timestamps = false;
    public $fillable = ["INDEX","FASTAVAIL","ISBN","AUTHOR","TITLE",'PUBDATE',"STATUS","CAT","FCAT","SCAT","FORMAT","PAGES","SERIES","SOPLAN","INVNATURE", "AUTHORKEY","TITLEKEY", "SUBTITLE", "HIGHLIGHT", "MARC", "PUBLISHER", "deleted_at"];

    protected $seed = ['dbf_inventories'];

  protected $_attributeTypes = [ 
        "_config"=>"inventory",
        "INDEX"=>["name"=>"INDEX","type"=>"Int","length"=>50],
        "FASTAVAIL"=>["name"=>"FASTAVAIL","type"=>"Char","length"=>3],
        "ISBN"=>["name"=>"ISBN","type"=>"Char","length"=>13],
        "ONHAND"=>["name"=>"ONHAND","type"=>"String","length"=>5],
        "AUTHOR"=>["name"=>"AUTHOR","type"=>"Char","length"=>34],
        "TITLE"=>["name"=>"TITLE","type"=>"Char","length"=>45],
        "PUBDATE"=>["name"=>"PUBDATE","type"=>"Int","length"=>8],
        "STATUS"=>["name"=>"STATUS","type"=>"Char","length"=>20],
        "CAT"=>["name"=>"CAT","type"=>"Char","length"=>26],
        "FCAT"=>["name"=>"FCAT","type"=>"Char","length"=>2],
        "SCAT"=>["name"=>"SCAT","type"=>"Char","length"=>2],
        "FORMAT"=>["name"=>"FORMAT","type"=>"Char","length"=>15],
        "PAGES"=>["name"=>"PAGES","type"=>"Integer","length"=>4],
        "LISTPRICE"=>["name"=>"LISTPRICE","type"=>"Integer","length"=>7],
        "SERIES"=>["name"=>"SERIES","type"=>"Char","length"=>28],
        "SOPLAN"=>["name"=>"SOPLAN","type"=>"Char","length"=>30],
        "INVNATURE"=>["name"=>"INVNATURE","type"=>"Char","length"=>5],
        "AUTHORKEY"=>["name"=>"AUTHORKEY","type"=>"Char","length"=>20],
        "TITLEKEY"=>["name"=>"TITLEKEY","type"=>"Char","length"=>20],
        "SUBTITLE"=>["name"=>"SUBTITLE","type"=>"VarBinary","length"=>100],
        "HIGHLIGHT"=>["name"=>"HIGHLIGHT","type"=>"Char","length"=>100],
        "MARC"=>["name"=>"MARC","type"=>"Char","length"=>4],
        "PUBLISHER"=>["name"=>"PUBLISHER","type"=>"Char","length"=>40]
  ];
     
}
