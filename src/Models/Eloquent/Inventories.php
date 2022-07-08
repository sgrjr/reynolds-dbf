<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Ancientdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Alldetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Brodetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Backdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webdetails;
use Sreynolds\ReynoldsDbf\Models\Eloquent\Booktexts;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Sreynoldsjr\ReynoldsDbf\Helpers\UserTitleData;
use Sreynoldsjr\ReynoldsDbf\Helpers\Misc;
use Attribute, Cache, Schema;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\LevelTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeInventoriesTrait;

class Inventories extends BaseModel implements ModelInterface{

    use LevelTrait, SoftDeletes, InitializeInventoriesTrait;

    protected $table = 'inventories';
    protected $dbfPrimaryKey = 'ISBN';
    protected $appends = ['coverArt','marcLink','purchasedCount','title'];
    public $migration = "2022_00_00_03_inventories.php";
    protected $indexes = ["ISBN"];
    public $timestamps = false;
    public $fillable = ["INDEX","FASTAVAIL","ISBN","AUTHOR","TITLE",'PUBDATE',"STATUS","CAT","FCAT","SCAT","FORMAT","PAGES","SERIES","SOPLAN","INVNATURE", "AUTHORKEY","TITLEKEY", "SUBTITLE", "HIGHLIGHT", "MARC", "PUBLISHER", "deleted_at"];

    protected $seed = ['dbf_inventories'];

  /*protected $_attributeTypes = [ 
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
*/
  public function getCoverArtAttribute(){
    return url("/img/small/" . $this->attributes['ISBN'] . ".jpg");
  }

  public function getTitleAttribute(){
    if($this->attributes['ARTICLE'] != ''){
        return $this->attributes['ARTICLE'] . ' ' .$this->attributes['TITLE'];
    }else{
        return $this->attributes['TITLE'];
    }
  }

  public function getCategoryAttribute(){
    $atts = $this->attributes;

    if(isset($atts["CAT"]) ){
      return $atts["CAT"];

    }else{
      return false;
    }
  }

  public function getImageAttribute(){return $this->getImgAttribute($atts);}
  public function getUrlAttribute(){return "/isbn/" . $this->ISBN;}
  public function getMarcLinkAttribute(){

    if($this->MARC === "MARC"){
          return [
        "view" => url("/files/marc/".$this->ISBN.".txt"),
        "download" => url("/files/marc/".$this->ISBN.".mrc")
      ];
    }
    return null;
  }

  public function referenceStandingOrderList($vendorKey, $list=false){
    return Misc::referenceStandingOrderList($vendorKey, $this, $list);
  }


    public function text(){
        return $this->hasMany(Booktext::class,"KEY","ISBN")->where('SUBJECT','LIKE','%PL%');
	  }

   public function getUserData($user = false)
    {

      if($user === false){
        $user = request()->user();
      }

      if($user !== null){
        $u = new UserTitleData($this, $user);

         return (object) [
          "price"=> $u->price,
          "purchased"=>$u->purchased,
          "onstandingorder"=>$u->onstandingorder,
          "discount"=>$u->discount,
          "isbn"=>$u->isbn
        ];
      }else{
        return new \stdclass;
      }
    	
    }

    public function inventoriesSchema($table){
  		$table->string("ALLSALES")->nullable()->change();
      $table->string("ISBN")->unique()->change();
      
        /// write indexes someday to optimize up mysql queries ["AUTHORKEY","CAT"];
  		return $table;
  	}


    public function byAuthor(){
      return $this->hasMany(static::class,"AUTHORKEY","AUTHORKEY");
    }
    public function byCategory(){
      return $this->hasMany(static::class,"CAT","CAT");
    }

    public function byPubdate(){
      return $this->hasMany(static::class,"PUBDATE","PUBDATE");
    }

    public function byInvnature(){
      return $this->hasMany(static::class,"INVNATURE","INVNATURE");
    }

    public function byFormat(){
      return $this->hasMany(static::class,"FORMAT","FORMAT");
    }

    public function bySeries(){
      return $this->hasMany(static::class,"SERIES","SERIES");
    }

    public function bySoplan(){
      return $this->hasMany(static::class,"SOPLAN","SOPLAN");
    }

    public function byPublisher(){
      return $this->hasMany(static::class,"PUBLISHER","PUBLISHER");
    }

    public function byTitle(){

      $query = \DB::table('inventories');
      
      if($this->TITLE !== null) {
        
        $words = explode(" ", trim($this->TITLE));

        if(count($words) > 1){
          abort(404, json_encode($words));
        }
        $ctr = 0;
        foreach($words AS $w){

          if(trim($w ?? '') !== ''){
            if($ctr === 0){
              $query->where('TITLE','like',"%".trim($w ?? '')."%");
              $ctr++;
            }else{
              $query->orWhere('TITLE','like',"%".trim($w ?? '')."%");
            }
            
          }
          
        }
        
      }else{
        $query->where('id','z1');
      }
      
      return $query->get();
    }

    public static function getCPTitles(){

      return static::
        where("PUBDATE",">=", Misc::getYearMonth()["machine"]."00")
        ->where("INVNATURE","CENTE")
        ->where("PUBDATE","<", Misc::getYearMonth(1)["machine"]."00");

      /*
      $data = Misc::gauranteedBooksCount(15, [
        Misc::getYearMonth()["machine"]."00", 
        Misc::getYearMonth(-2)["machine"]."00",
        Misc::getYearMonth(-6)["machine"]."00", 
        Misc::getYearMonth(-12)["machine"]."00"
      ]);

      return $data;

      return Misc::dataToPaginator($data); */ 
    }

    public static  function getTradeTitles(){ 

      return static::
        where("PUBDATE",">=", Misc::getYearMonth()["machine"]."00")
        ->where("INVNATURE","TRADE")->orderBy("PUBDATE","asc");

      /*
      $data = Misc::gauranteedBooksCount(15, [
        Misc::getYearMonth()["machine"]."00", 
        Misc::getYearMonth(-1)["machine"]."00", 
        Misc::getYearMonth(-2)["machine"]."00",
        Misc::getYearMonth(-3)["machine"]."00"
      ], "TRADE"); 
      return Misc::dataToPaginator($data);*/  
    }

    public static  function getAdvancedTitles(){ 

    return static::
        where("PUBDATE",">=", Misc::getYearMonth(1)["machine"]."00")->where("INVNATURE","CENTE");
       /*  
      $data = Misc::gauranteedBooksCount(30, [ 
        Misc::getYearMonth(1)["machine"]."00", 
        Misc::getYearMonth()["machine"]."00",
        Misc::getYearMonth(-1)["machine"]."00",
        Misc::getYearMonth(-2)["machine"]."00"
      ]);
      return Misc::dataToPaginator($data);  */
    }


    public function getMarcs($_, $args){
      $ds = DIRECTORY_SEPARATOR;

      $zip_file_base = $ds . 'marcs'.$ds.'compiled_marc_'.Carbon::now()->timestamp.'.zip';

      $matches = false;
      $zip_file = public_path() . $zip_file_base;

      $zip = new \ZipArchive();
      $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

      if($args["text"] && $args["text"] === true){
        $path = \Config::get('cp')['text_marc_records_path'];
        $file_type = ".txt";
      }else{
        $path = \Config::get('cp')['marc_records_path'];
        $file_type = ".mrc";
      }
     
      foreach ($args["isbns"] as $isbn)
      {
        if($isbn !== null){
              $filePath = $path .$ds.$isbn.$file_type;
              if(file_exists($filePath)){
                $matches = true;
                $relativePath = $isbn.$file_type;
                $zip->addFile($filePath, $relativePath); 
              }else{
                $missing_path = public_path() .$ds."marcs".$ds."missing.mrc";

                file_put_contents(storage_path('logs') . $ds."missing_marcs.txt", 'Cannot find MARC record: ' . $filePath . "\n", FILE_APPEND);

                if(file_exists($missing_path)){
                  $relativePath = 'MARC_NOT_YET_AVAILABLE_FOR_'.$isbn.'.txt';
                  $zip->addFile($missing_path, $relativePath); 
                }
              }
            }
      }

      $zip->close();

      return [
        "zip" => $zip_file_base,
        "isbns" => $args["isbns"]
      ];

    }

  public function scopeCustomer(Builder $query): Builder {
    return $query->where('id', ">=", 37);
  }

    public function titlesLists($_, $vars)
    {

      if(isset($vars['page'])){
        $first = $vars['first'] * $vars['page'];
      }else{
        $first = $vars['first'];
      }

      if(isset($vars['dbf']) && $vars['dbf'] === true){
        // query the dbf files directly
        $inventory = (new static)->dbf();
      }else{
        // query mysql
        $inventory = new static;
      }

        switch($vars['name']){

          case 'current':
            return $inventory->where("PUBDATE",">=", Misc::getYearMonth()["machine"]."00")->where("INVNATURE","CENTE")->where("PUBDATE","<", Misc::getYearMonth(1)["machine"]."00")->limit($first);
          case 'advanced':
          case 'upcoming':
          case 'upcoming-titles':
            return $inventory->where("PUBDATE",">=", Misc::getYearMonth(1)["machine"]."00")->where("INVNATURE","CENTE")->limit($first);
          case 'trade':
            return $inventory->where("PUBDATE",">=", Misc::getYearMonth()["machine"]."00")->where("INVNATURE","TRADE")->orderBy("PUBDATE","asc")->limit($first);
          case 'clearance':
          case 'clearance-titles':
            // if FLATPRICE is equal to or greater than 1.00 display FLATPRICE as the LISTPRICE WITH NO DISCOUNT APPLIED
            return $inventory->where("FLATPRICE",">=", 1.00)->where("INVNATURE","CENTE")->limit($first);
          case 'top-25-titles':
            // Has the current month's PUBDATE and of that list which titles have been purchased the most
            $start_pubdate = Misc::getYearMonth(0)["machine"]."00";
            $end_pubdate = Misc::getYearMonth(1)["machine"]."00";
            $isbns = $inventory->where('PUBDATE', '>=', $start_pubdate)->where("PUBDATE","<=", $end_pubdate)->where('INVNATURE',"CENTE")->get()->sortBy('purchasedCount')->reverse()->pluck('ISBN');
            $ids = $isbns->implode(', ');

              if(isset($vars['dbf']) && $vars['dbf'] === true){
                // query the dbf files directly
                //dd($inventory->whereIn('ISBN',$isbns)->orderByRaw("ISBN", $isbns)->get());
               return $inventory->whereIn('ISBN',$isbns)->orderByRaw("ISBN", $isbns);
              //return $inventory->where('id',">",1);
              }else{
                // query mysql
                return $inventory->whereIn('ISBN',$isbns)->orderByRaw("FIELD(ISBN, $ids)"); 
              }
            
          default:
            return $inventory->where('id', 122)
              ->orWhere('id', 55)
              ->orWhere('id', 65)
              ->orWhere('id', 75)
              ->orWhere('id', 85)
              ->orWhere('id', 95)
              ->orWhere('id', 155)
              ->orWhere('id', 255)
              ->orWhere('id', 355)
              ->orWhere('id', 655)
              ->orWhere('id', 755)
              ->orWhere('id', 550);

        }

    }

    public function getIsClearanceAttribute(){
      return $this->FLATPRICE >= 1.00;
    }

    public static function getPurchasedCount($isbns){
       $times_purchased[] = Alldetails::whereIn('PROD_NO', $isbns)->select( \DB::raw('count(*) as total'), 'PROD_NO')->groupBy('PROD_NO')->pluck('total','PROD_NO')->toArray();
       $times_purchased[] = Ancientdetails::whereIn('PROD_NO', $isbns)->select( \DB::raw('count(*) as total'), 'PROD_NO')->groupBy('PROD_NO')->pluck('total','PROD_NO')->toArray();
       $times_purchased[] = Brodetails::whereIn('PROD_NO', $isbns)->select( \DB::raw('count(*) as total'), 'PROD_NO')->groupBy('PROD_NO')->pluck('total','PROD_NO')->toArray();
       $times_purchased[] = Backdetails::whereIn('PROD_NO', $isbns)->select( \DB::raw('count(*) as total'), 'PROD_NO')->groupBy('PROD_NO')->pluck('total','PROD_NO')->toArray();
       $times_purchased[] = Webdetails::whereIn('PROD_NO', $isbns)->select( \DB::raw('count(*) as total'), 'PROD_NO')->groupBy('PROD_NO')->pluck('total','PROD_NO')->toArray();

        $newlist = [];

        foreach($times_purchased AS $tp){
          foreach($tp AS $k=>$v){
            if(isset($newlist[$k])){
              $newlist[$k]["count"] = $newlist[$k]['count'] + $v;
            }else{
              $newlist[$k] = ["count"=>$v, 'isbn'=>$k];
            }
          }
        }
        return collect($newlist)->sortBy('count')->reverse();
    }

    public function getPurchasedCountAttribute(){
      $seconds = 86400;
      return Cache::remember('purchased_count_for_' . $this->ISBN, $seconds, function () {
        $result = $this->getPurchasedCount([$this->ISBN])->first();
          return $result !== null? $result["count"]:0;
      });
    }

}
