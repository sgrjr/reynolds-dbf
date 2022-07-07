<?php namespace Sreynoldsjr\ReynoldsDbf\Helpers;

use Config;

class Application {

  public static function dbfToJSON($name){

    $class_name = '\\App\\' . ucfirst(strtolower($name));

    $file_name = $name . '.json';

    if (file_exists($file_name) ){
        $data = json_decode(file_get_contents($file_name));
    } else {
        $inv = $class_name::dbf()->all();
        $inventoryJSON = fopen($file_name, "w") or die("Unable to open file!");
        fwrite($inventoryJSON, json_encode($inv));
        fclose($inventoryJSON);
        $data = $inv;
    }

  }

	public static function props($user = null){

		return [
			"browse" => static::browse(),
			"catalog" => static::catalog(),
			"searchFilters" => static::searchFilters(),
			"slider" => static::slider(),
			"links" => static::links($user),
      "client" => "cp",
      "domain" => "centerpointlargeprint.com",
      "appDescription" => "The Smart Choice for Large Print! | 1-800-929-9108",
      "siteName" => "Center Point Large Print"
		];
	}

	private static function browse(){
    \Cache::forget('browse_products');
		return \Cache::rememberForever('browse_products', function () {
	          return static::calcBrowseProducts();
	      }); 
	}

	 private static function calcBrowseProducts(){
          
        $cats = [
            "Romance",
            "Romance - Christian",
            "Romance - Historical",
            "Romance - Suspense",
            "Fiction",
            "Fiction - History", 
            "Fiction - General",
            "Fiction - Historical",
            "Fiction - Women",
            "Fiction - Adventure",
            "Fiction - Science",
            "Fiction - Christian",
            "Fiction - Inspirational",
            "Nonfiction",
            "Nonfiction - Biography",
            "Nonfiction - History",
            "Mystery",
            "Mystery - Thriller",
            "Mystery - Christian",
            "Mystery - Cozy",
            "Western"
        ];
        
        $genre_items = [];

        foreach($cats AS $cat){
            $genre_items[] = \App\Helpers\Misc::makeSearchUrl($cat, "category");
        }
        
        $now = \App\Helpers\Misc::getYearMonth();
        $now2 = \App\Helpers\Misc::getYearMonth(1);
        $now3 = \App\Helpers\Misc::getYearMonth(2);

        $months = [
          \App\Helpers\Misc::makeSearchUrl($now["machine"], "DATE", $now["human"]),
          \App\Helpers\Misc::makeSearchUrl($now2["machine"], "DATE", $now2["human"]),
          \App\Helpers\Misc::makeSearchUrl($now3["machine"], "DATE", $now3["human"])
        ];

        return [
                    ["title"=>"Search By Month","items"=>$months],
                    ["title"=>"Genre", "items"=> $genre_items]
                ];
  }

	public static function catalog($args = ["id"=>"current"]){
	  $config = config('cp');

      $cat = new \stdclass;
      $cat->id = null;
      $cat->image_root = $config["CATALOG_COVERS_PATH"];
      $cat->pdf_root = $config["CATALOG_PATH"];
      $cat->image_link = "/img/promotions/current";
      $cat->image_path = null;
      $cat->image_ext = null;
      $cat->pdf_link = null;
      $cat->pdf_path = null;
      $cat->year = null;
      $cat->month = null;
      $cat->template = "original";
      
      if(isset($args['id'])){
        $cat->id = $args['id'];
      }

      $cat->list = [
        "01" => "01_02",
        "02" => "01_02",
        "03" => "03_04",
        "04" => "03_04",
        "05" => "05_06",
        "06" => "05_06",
        "07" => "07_08",
        "08" => "07_08",
        "09" => "09_10",
        "10" => "09_10",
        "11" => "11_12",
        "12" => "11_12"
      ];

      switch($cat->id){
        case "current":
        case "current_catalog":
        case "current_catalog_image":

        $search = \Cache::remember('catalog', 360, function () use ($cat) {
            return Misc::findFileByDate($cat->image_root, $cat->list);
        });

          $cat->image_link = "/img/promotions/current_catalog";
          $cat->image_path = $search->image;
          $cat->year = null;
          $cat->month = null;
          $cat->image_ext = null;

          if(isset($search->year)){$cat->year = $search->year;}	
          if(isset($search->month)){$cat->month = $search->month;}  
          if(isset($search->ext)){$cat->image_ext = $search->ext;}

          $cat->pdf_link = "/static/current_catalog";
          $cat->pdf_path = $cat->pdf_root . $cat->year . "_" . $cat->month . ".pdf";
          break;
        
        case "next":
        case "next_catalog":
        case "next_catalog_image":
          $first = [
            sprintf("%04d",date("Y")),
            sprintf("%02d",date("m")+1)
          ];
          
          $search = \Cache::remember('next_catalog', 360, function () use ($cat) {
             return Misc::findFileByDate($cat->image_root, $cat->list);
          });
          
          $cat->image_link = "/img/promotions/next_catalog";
          $cat->image_path = $search->image;	
          $cat->year = $search->year;
          $cat->month = $search->month;
          $cat->image_ext = $search->ext;
          $cat->pdf_link = "/static/next_catalog";
          $cat->pdf_path = $cat->pdf_root . $cat->year . "_" . $cat->month . ".pdf";

        break;

      case "All_Series_Christian_catalog":
      case "All_Series_Sterling_catalog":
      case "All_Series_Trade_catalog":
      case "All_Series_Western_catalog":
      case "All_Series_Premier_catalog":
      case "All_Series_Platnum_catalog":
      case "All_Series_Choice_catalog":
      case "All_Series_Bestseller_catalog":
      case "Premier_Series_Romance_catalog":
      case "Premier_Series_Mystery_catalog":
      case "Premier_Series_Fiction_catalog":
      case "Platinum_Series_Nonfiction_catalog":
      case "Platinum_Series_Romance_catalog":
      case "Platinum_Series_Mystery_catalog":
      case "Platinum_Series_Fiction_catalog":

          $cat->pdf_root = $config["CATALOG_SERIES_PATH"];
          $cat->image_link = null;
          $cat->image_path = null;
          $cat->year = null;
          $cat->month = null;
          $cat->image_ext = null;
          $cat->pdf_link = "/static/".$cat->id;
          $cat->pdf_path = $cat->pdf_root . str_replace("_catalog","",$cat->id) . ".pdf";
        break;
  
        default:
          $cat->image_link = "/img/promotions/" . $cat->id;
          $cat->image_path = $config["CATALOG_PATH"] . "/" . $cat->id;	
          //$cat->year = $search->year;
          //$cat->month = $search->month;
          //$cat->image_ext = $search->ext;
          $cat->pdf_link = "/static/".$cat->id;
          //$cat->pdf_path = $cat->pdf_root . $cat->year . "_" . $cat->month . ".pdf";

            if(!file_exists($cat->image_path)){
              
              foreach($config['image_extensions'] AS $ext){
                if(file_exists($cat->image_path . $ext)){
                  $cat->image_path = $cat->image_path . $ext;
                  break;
                }else{
                  $cat->image_path = false;
                }
                
              }
              
            }
      
          
      } 
      return $cat;
	}

	 private function getCurrentCatalog()
    {
         return static::promo()->image;
    }

    private function getCurrentCatalogLink()
    {
         return static::promo()->pdf;
    }



    private function getNextCatalog()
    {
         return '/img/promotions/next_catalog_image';
    }

	private static function searchFilters(){
		return [["title","Title"],["isbns","ISBN"],["author","Author"],["price","Price"],["genre","Genre"],["series","Series"]];
	}

	private static function slider(){
		return config('slider_welcome');
	}

	public static function links($user){

		$links = new \stdclass;
  
        $links->drawer = collect([]);
        $links->main = collect([]);
        $links->shortCuts = collect([]);

        $links->shortCuts->push(["url"=>"/promotions", "text"=> 'Catalogs and Flyers',"icon"=>""]);
        $links->shortCuts->push(["url"=>"/search/top-25-titles/list", "text"=> 'Top 25 for ' . date("F"),"icon"=>""]);
        $links->shortCuts->push(["url"=>"/search/upcoming-titles/list", "text"=> 'Upcoming Titles',"icon"=>""]);
        $links->shortCuts->push(["url"=>"/search/clearance-titles/list", "text"=> 'Clearance Titles',"icon"=>""]);

        $links->main->push(["url"=>"/", "text"=> 'Home',"icon"=>"home"]);
        $links->drawer->push([ "url"=>"/promotions", "text"=> 'Catalogues, Flyers',"icon"=>"paw"]);

        if(!$user || $user === null){
          $links->drawer->push([ "url"=>"/login", "text"=> 'Login',"icon"=>"lockOpen"]);
          $links->main->push([ "url"=>"/login", "text"=> 'Login',"icon"=>"lockOpen"]);
          return $links;
        }

          $links->drawer->push([ "url"=>"/logout", "text"=> 'Logout',"icon"=>"lock"]);
          $links->main->push([ "url"=>"/logout", "text"=> 'Logout',"icon"=>"lock"]);

         if ($user->can("VIEW_DASHBOARD")){
          $links->drawer->push(["url"=>"/dashboard", "text"=> $user->name,"icon"=>"home"]);
          $links->main->push(["url"=>"/dashboard", "text"=> $user->name, "name"=>"brand","icon"=>"person"]);
         } 
  
        if ($user->can("VIEW_REGISTER_USER")){
          $links->drawer->push([ "url"=>"/register", "text"=> 'Register New User',"icon"=>"howToReg"]);
        }
  
        if ($user->can("ADMIN_APP")){
          $links->drawer->push([ "url"=>"/dashboard/admin", "text"=> 'Admin',"icon"=>"HEADING"]);
          $links->drawer->push([ "url"=>"/dashboard/admin/users", "text"=> 'Users',"icon"=>"personSearch"]);
          $links->drawer->push([ "url"=>"/dashboard/admin/titles", "text"=> 'Inventory',"icon"=>"books"]);
          $links->drawer->push([ "url"=>"/dashboard/admin/vendors", "text"=> 'Vendors',"icon"=>"store"]);
          $links->drawer->push([ "url"=>"/dashboard/admin/db", "text"=> 'Database',"icon"=>"dashboard"]);
          $links->drawer->push([ "url"=>"/dashboard/admin/orders", "text"=> 'Orders',"icon"=>"paid"]);
          $links->drawer->push([ "url"=>"/dashboard/admin/application", "text"=> 'Application',"icon"=>"settings"]);
          $links->drawer->push([ "url"=>"/setup", "text"=> 'Setup',"icon"=>"restart"]);
        }
  
        return $links;
	}
}
