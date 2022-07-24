<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits;

use Sreynoldsjr\ReynoldsDbf\Helpers\Misc;
use Attribute, Cache, DB, Schema;

use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Ancientdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Alldetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Brodetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Backdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Booktexts;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Sreynoldsjr\ReynoldsDbf\Helpers\UserTitleData;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Facades\Storage;

trait EloquentInventoriesTrait
{   

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
      return [
        "txt" => url("/files/marc/".$this->ISBN.".txt"),
        "mrc" => url("/files/marc/".$this->ISBN.".mrc")
      ];
  }



  public function referenceStandingOrderList($vendorKey, $list=false){
    return Misc::referenceStandingOrderList($vendorKey, $this, $list);
  }


  public function text(){
      return $this->hasMany(Booktexts::class,"KEY","ISBN")->where('SUBJECT','LIKE','%PL%');
    }

  public function getBodyAttribute(){
      $body = [];
      foreach($this->text AS $text){
        $body[] = $text->body;
      }

      return $body;
  }

  public function getUserAttribute(){
    return $this->getUserData(request()->user);
  }

   public function getUserData($user = false)
    {

      if($user === false){
        $user = request()->user();
      }

        return (new UserTitleData($this, $user))->props();
        
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
            $results = $inventory->where("PUBDATE",">=", Misc::getYearMonth(1)["machine"]."00")->where("INVNATURE","CENTE")->limit($first);

            if($results->count() > 0) return $results;

            $results = $inventory->where("PUBDATE",">=", Misc::getYearMonth()["machine"]."00")->where("INVNATURE","CENTE")->limit($first);

            if($results->count() > 0) return $results;

            $results = $inventory->where("PUBDATE",">=", Misc::getYearMonth(-5)["machine"]."00")->where("INVNATURE","CENTE")->limit($first);

            return $results;

          case 'trade':
            return $inventory->where("PUBDATE",">=", Misc::getYearMonth()["machine"]."00")->where("INVNATURE","TRADE")->orderBy("PUBDATE","DESC")->limit($first);
          case 'clearance':
          case 'clearance-titles':
            // if FLATPRICE is equal to or greater than 1.00 display FLATPRICE as the LISTPRICE WITH NO DISCOUNT APPLIED
            return $inventory->where("FLATPRICE",">=", 1.00)->where("INVNATURE","CENTE")->limit($first);
          case 'top-25-titles':
            $skip = ['0000000084','0000000034','0000000085','0000000071','0000000033','0000000016','0000000025','0000000004','0000000024','0000000028','0000000011','0000000006','0000000009','0000000023','0000000005','0000000032','0000000029','0000000027','0000000010'];
            $purchasedCounts = static::getCache('purchasedCounts');
            $isbns = $purchasedCounts->except($skip)->sortBy('count')->reverse()->take(25)->keys()->toArray();
            $ids = implode(',',$isbns);

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
      return $this->LISTPRICE >= 1.00 && $this->LISTPRICE <= 11.00;
    }

    public static function cachePurchasedCounts(){

      $isbns = (new static)->pluck('ISBN');
 
        $output = new ConsoleOutput();
        $tables = [
          'ancientdetails',
          'alldetails',
          'backdetails',
          'brodetails',
          'webdetails',
        ];
        
        $times_purchased = static::emptyPurchasedCounts();

         foreach($isbns AS $i){
            $times_purchased[$i] = ['isbn'=>$i, 'count'=> 0, 'dates' => []];
          }

        foreach($tables AS $table){

          $t = DB::table($table);
          $per = 200;

          $progressBar = new ProgressBar($output, $t->count()? $t->count():0);
          $progressBar->setBarCharacter('<fg=green>⚬</>');
          $progressBar->setEmptyBarCharacter("<fg=red>⚬</>");
          $progressBar->setProgressCharacter("<fg=green>➤</>");
          $progressBar->setFormat("<fg=white;bg=cyan> %status:-45s%</>\n%current%/%max% [%bar%] %percent:3s%% %estimated:-20s% %memory:20s%", $progressBar->getFormatDefinition('debug')); // the new format
          $progressBar->setMessage($table, 'status');

          $progressBar->start();

          DB::table($table)->select(['id','PROD_NO','REQUESTED','DATESTAMP'])->chunkById($per, function($records) use ($times_purchased, $progressBar) {

              foreach($records AS $row){

                if($times_purchased->has($row->PROD_NO)) {
                  $v = $times_purchased[$row->PROD_NO];
                  $v['dates'][] = $row->DATESTAMP;
                  $times_purchased[$row->PROD_NO] = ['isbn'=> $v['isbn'], 'count' => $v['count'] + ($row->REQUESTED? $row->REQUESTED:1), 'dates'=> $v['dates'] ];
                }
                ////$item['count'] = $item['count'] + ($row->REQUESTED? $row->REQUESTED:1);
                $progressBar->advance();
              }

          });
        }

        $progressBar->finish();

        $result = $times_purchased->sortBy('count')->reverse();

        Storage::put(static::purchasedCountsLocation(), json_encode($result));
    }

    public static function purchasedCountsLocation(){
      return 'titles/purchased_counts.cache';
    }

    public static function emptyPurchasedCounts(){
      return collect([]);
    }

    public static function cacheEverything(){
      static::cacheClearInventories();
      static::cachePurchasedCounts();
    }

    public static function getCachePurchasedCounts(){

      // return a collection of top purchased counts from cache
      //return collect(Cache::rememberForever('top_purchased_counts', function(){
        
        //if purchased counts have not been cached then return empty
        if(!Storage::has(static::purchasedCountsLocation())){
          return static::emptyPurchasedCounts();//static::cachePurchasedCounts();
          }

        //return the purchased counts from file
        return collect(json_decode(Storage::get(static::purchasedCountsLocation())));
      //}));
    }

    public static function cacheClearInventories(){
        Cache::forget('top_purchased_counts');
        Storage::delete(static::purchasedCountsLocation());
     }

     public static function purchasedCounts(){
      return static::getCachePurchasedCounts();
     }

    public function getPurchasedAttribute(){

      return collect(Cache::rememberForever('purchased_' . $this->ISBN, function(){
        $book = static::purchasedCounts()->first(function($item){
          return $item->isbn === $this->ISBN;
        });
        if($book === null) return (Object)['isbn'=> $this->ISBN, 'count'=>0, 'dates'=>[]];
        return $book;
      }));
    }
}