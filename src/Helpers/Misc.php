<?php namespace Sreynoldsjr\ReynoldsDbf\Helpers;

use Illuminate\Support\Str;
use Carbon\Carbon;

use Sreynoldsjr\ReynoldsDbf\Models\Inventories;

class Misc
{
  public static function firstOrNull($array){
        if($array !== null && count($array) > 0){
        return $array[0];
      }else{
        return null;
      }
  }

  public static function figureCost($detail){
    $number = $detail->SALEPRICE * $detail->REQUESTED;
    return number_format($number, 2,'.','');
  }

  public static  function makeSearchUrl($text, $col, $title = false, $compare = false){

        $base = "/search/";

      
      $link = new \stdclass;
      if($title){
        $link->text = $title;
      }else{
        $link->text = $text;
      }
      
      $link->url = $base . str_replace(" ", "+",str_replace("  ", " ", trim(str_replace("-","",$text) ?? ''))) . "/" . $col;
      return $link;

  }

  public static function getErrors(){
    $error = "ERRORS:\n";
    $log = "";
    $total_lines = 0;

    $error_file_name = base_path() . "/storage/addToCartFailure.txt";
    $log_file = @fopen(base_path() . "/storage/logs/laravel.log", "r");
    
    $exists = file_exists($error_file_name);

    if ($exists) {
        $error_file = @fopen($error_file_name, "r");

        while (($buffer = fgets($error_file, 4096)) !== false && $total_lines < 30000) {
            $error .= $buffer;
            $total_lines++;
        }
        if (!feof($log_file)) {
            $error .= "Error: unexpected fgets() fail\n";
        }
        $error .= "Number of Lines: " . $total_lines . "\n";
        fclose($error_file);
    }

    
    $total_lines = 0;
    $regex = "/[[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9]]/m";
    if ($log_file) {
        while (($buffer = fgets($log_file, 4096)) !== false) {
            if(preg_match($regex,$buffer)){
                $log .= $buffer;
                $total_lines++;
            }
            
            
        }
        if (!feof($log_file)) {
            $log .= "Error: unexpected fgets() fail\n";
        }
        $log .= "Number of Lines: " . $total_lines . "\n";
        fclose($log_file);
    }

    return $error . "\nLOG: \n" . $log;
  }

  public static function getYearMonth($int = false){

    $effectiveDate = date("Y").date("m");

        if(!$int){
            $effectiveDate = date('Ym', strtotime("+0 months", strtotime($effectiveDate)));
            return [
                "machine"=> $effectiveDate, 
                "human" => date("F") . " " . date("Y")
            ];
        }else{
            $effectiveDate = date('Ym', strtotime((string) $int ." months", strtotime($effectiveDate)));
            $readable = date('M Y', strtotime((string) $int ." months", strtotime($effectiveDate)));

            return [
                "machine"=> $effectiveDate,
                "human" => $readable
            ];
        }

    }

  public static function getMonth($increment = false, $int = false){
	if(!$increment){
        if($int){
            return (int) date("m");
		}else{
            return date("m");
		}
	}else{
		$month = date("m") - $increment;
		if($month <= 0){
			$month = $month + 12;
		}

        if($int){
            return (int) $month;
		}else{
            return sprintf("%02d",$month);
		}
		
	}
}


public static function getDay($month = null, $increment = false){
	if(!$increment){
		return date("d");
	}else{
        
        $thirty_day_months = ["09","04","06","11"]; // September, April, June, November


		$day = date("d") - $increment;
		if($month == "02" && $day > 28){
			$day = 0;
		}else if(in_array($month, $thirty_day_months) && $day > 30){
            $day = 0;
		}

		return sprintf("%02d",$day);
	}
}

  public static function getYear($increment = false){
	if(!$increment){
		return date("Y");
	}else{
		return sprintf("%04d", date("Y") - $increment);
	}
}


  public static function findFileByDate($root, $list, $first = false){

  $file = new \stdclass;

	if(!$first){
		$years = [static::getYear(),static::getYear(1)];
		$months = [static::getMonth(), static::getMonth(1), static::getMonth(2), static::getMonth(3), static::getMonth(4) ];
		$extensions = [".jpg",".png",".svg",".jpeg",".bmp",".gif"];
	}else{
		$years = [$first[0], static::getYear(),static::getYear(1)];
		$months = [$first[1], static::getMonth(), static::getMonth(1), static::getMonth(2), static::getMonth(3), static::getMonth(4) ];
		$extensions = [".jpg",".png",".svg",".jpeg",".bmp",".gif"];
	}

	foreach($years AS $y){
		foreach($months AS $m){
			foreach($extensions AS $ext){

				$file->image = $root . $y . "_" . $list[$m] . $ext;

				if(file_exists($file->image)){
                  $file->root = $root;
                  $file->year = $y;
                  $file->month = $list[$m];
                  $file->ext = $ext;
                  return $file;
                }
			}
		}
	}

	return $file;

}

  public static function pubdateMonthsPast($dec = 0){

    if($dec > 12 ){
        $dec = 12;
	}else if($dec == 0){
        $dec = false;
	}
    
    $month = static::getMonth(false, true);
    $month_prev = static::getMonth($dec, true);
   
    if($month_prev >= $month ){
        $year = static::getYear(1);
	}else{
        $year = static::getYear();
	}

    $ans = $year. static::getMonth($dec) . "00";
    return (int) $ans;
  }

    public static function pubdateYearsPast($dec = 0){
        $year = static::getYear($dec);
        $ans = $year . "0000";
        return (int) $ans;
  }

  public static function pubdateNow(){
    $ans = static::getYear() . static::getMonth() . static::getDay(static::getMonth());
    return (int) $ans;
  }
 
 public static function bookByPubdate($pubdate, $count, $nature){
    
    return Inventories::skip(0)
            ->take($count)
            ->where("PUBDATE",">=", $pubdate)
            ->where("INVNATURE",$nature)
            ->orderBy("PUBDATE","DESC")
            ->get();
}

public static function gauranteedBooksCount($count, $dates, $nature = "CENTE"){
    
    $results = static::bookByPubdate($dates[0], $count, $nature);

    if($results->count() < $count){
                    
            $results2 = static::bookByPubdate($dates[1], $count, $nature); 

            $results2 = $results->concat($results2);

            if($results2->count() >= $count){
                return $results2->splice(0,$count);
			}else{
                $results3 = static::bookByPubdate($dates[2], $count, $nature);

                $results3 = $results2->concat($results3);

                if($results3->count() >= $count){
                    return $results3->splice(0,$count);
				}else {
                    $results4 = static::bookByPubdate($dates[3], $count, $nature);
                    $results4 = $results3->concat($results4);
                    return $results4->splice(0,$count);
				}
            }
                    
	}
     return $results;   
}

    public static function resolveTypeToMysqlFunc($h){
        $length = isset($h["length"])? $h["length"]:128;
        $type = isset($h["mysql_type"])? $h["mysql_type"]:"Char";
        $decimal_count = isset($h["decimal_count"])? $h["decimal_count"]:0;

                    switch($type){
                        
                        case "Double":
                            return ['double',[$length,2]];
                          // $table->double($n, $l, 2);//	DOUBLE equivalent with precision, $digits in total and $digits after the decimal point 
                           break;

                        case "Char": //C	N	-	Character field of width n
                            return ['char',[$length]];
                            //$table->char($n, $l);//	CHAR equivalent with a length
                            break;

                        case "Decimal"://Y	-	-	Currency;
                            return ['decimal',[$length, $decimal_count]];
                            //$table->decimal($n, $l, 2);//	DECIMAL equivalent with a precision and scale
                            break;

                        case "Float": //F	N	d	Floating numeric field of width n with d decimal places,
                            return ['float',[$length, $decimal_count]];
                            break;

                        case "Date": //D	-	-	Date
                            return ['date',[]];
                            //$table->date($n);//	DATE equivalent to the table
                            break;

                        case "Blob": //G	-	-	General
                            return ['binary',[]];
                            //$table->binary($n);//	BLOB equivalent to the table
                            break;

                        case "Integer": //I	-	-	Index
                            return ['integer',[]];
                            //$table->integer($n);//	INTEGER equivalent to the table
                           // $table->smallInteger('votes');//	SMALLINT equivalent to the table 
                            break;

                        case "TinyInt": //L	-	-	Logical - ? Y y N n T t F f (? when not initialized).
                            return ['boolean',[]];
                            //$table->boolean($n);//	BOOLEAN equivalent to the table
                            //$table->tinyInteger('numbers');//	TINYINT equivalent to the table
                            // $table->mediumInteger('numbers');//	MEDIUMINT equivalent to the table
                            break;

                        case "Text": //M	-	-	Memo
                        case "TEXT": //M    -   -   Memo
                            return ['text',[]];
                            //$table->text($n);//	TEXT equivalent to the table
                            break;

                        case "Integer": //N	N	d	Numeric field of width n with d decimal places
                            return ['integer',[]];
                            //$table->integer($n);//	INTEGER equivalent to the table
                            break;

                        case "Datetime": //T	-	-	DateTime,
                             return ['dateTime',[]];
                             $table->dateTime($n);//	DATETIME equivalent to the table
                             break;

                        case "IGNORE": //// ignore this field
                            return ['',[]];
                            break;

                        case 'String':
                        case 'string':
                          return ['string',[$length]];  
                            break;
                        
                        case 'LongText':
                          return ['longText'];
                            //$table->mediumText('description');//	MEDIUMTEXT equivalent to the table     
                            break;

                        case 'MediumText':
                          return ['mediumText'];  
                            break;

                        case 'Binary':
                          return ['binary',[$length]];  
                            break;

                         default:
                             return ['char',[$length]];
                             //$table->char($n, $l);   
                             /*
                             unused functions:
                            $table->enum('choices', ['foo', 'bar']);//	ENUM equivalent to the table
                            $table->json('options');//	JSON equivalent to the table
                            $table->jsonb('options');//	JSONB equivalent to the table
                            $table->time('sunrise');//	TIME equivalent to the table
                            $table->timestamp('added_on');//	TIMESTAMP equivalent to the table
                            */

					}
    }

    public static function isIndex($name, $model){
        return in_array($name, $model->indexes);
    }

    public static function setUpTableFromHeaders($table, $headers, $model){

        // overriding to compensate for encoding errors on input
        // maybe someday figure a clean way to do this
        $binary = [
            "inventories" => ["SUBTITLE"],
            "backdetails" => ["TITLE"],
            "webdetails" => ["TITLE"],
            'alldetails' => ['SUBTITLE'],
            'allheads' => ['CINOTE'],
            'ancientdetails'=>['TITLE','SUBTITLE','AUTHOR']
        ];

        foreach($headers AS $h){

        /* OVERRIDES */
         if(is_array($h) && isset($binary[$model->getTable()]) && in_array($h['name'], $binary[$model->getTable()])) {
            $h['mysql_type'] = "Binary";
            $h['type'] = 'G';
         };

          $funcArray = Misc::resolveTypeToMysqlFunc($h);
          $func = $funcArray[0];

          if(!isset($funcArray[1])){$p = [];}else{$p = $funcArray[1];}
         
          if(strpos($h["name"], '_id') !== false){
              isset($h["index"])? $table->unsignedInteger($h["name"])->index():$table->unsignedInteger($h["name"]);
          }else if(count($p) === 0){
            $table->$func($h["name"])->nullable();
            if(static::isIndex($h["name"], $model)){ $table->index($h["name"]);} 
    	  }else if(count($p) === 1){   
            $table->$func($h["name"], $p[0])->nullable();
            if(static::isIndex($h["name"], $model)){$table->index($h["name"]);} 
    	  }else if(count($p) === 2){
            $table->$func($h["name"], $p[0], $p[1])->nullable();
            if(static::isIndex($h["name"], $model)){$table->index($h["name"]);} 
    	  }else{
            $table->$func($h["name"])->nullable();
            if(static::isIndex($h["name"], $model)){$table->index($h["name"]);} 
    	  }

    	}

          return $table;
      }

    public static function api($atts, $code = 200){
        return response(
            [
            "data" => $atts
            ] 
            , $code
            );
	}

    public static function dataToPaginator($data){
        $x = new \stdclass;
        $total = count($data);

        $x->paginatorInfo = new \stdclass;
        $x->paginatorInfo->count = $total;
        $x->paginatorInfo->currentPage = 1;
        $x->paginatorInfo->total = $total;
        $x->paginatorInfo->perPage = $total;
        $x->paginatorInfo->firstItem = 1;
        $x->paginatorInfo->hasMorePages = false;

        $x->data = $data;
        return $x;
    }

    public static function dbfLog($message){
        $mytime = Carbon::now();
        file_put_contents(storage_path() . '/logs/dbf_log.log', "[" . $mytime->toDateTimeString() . "] " . $message . "\n", FILE_APPEND);
    }

    public static function getDiscount($series){

        /*
       discount whatever it may qualify for, choice, sale book, Trade or ledger 
       - so choice depends on level but it’s a 40% on choice 100, 35% on Choice 48 and 30% on Choice 24, 
       - Sales books are 10.00 for 1-24 or 7.00 25 or more, 
       - Trade is 25%, Ledger is 25%. 
       - Ledger is an order that a customer does not have any choice plan or Trade and its not a Sale book.
       */

       /* 
       grabbed distinct SO_SERIES from standing orders:
       "2013 - AM INDICATES COMP PLANS"
        "2014 - AM INDICATES COMP PLANS"
        "2016 AM INDICATES COMP PLANS"
        "2017 AM INDICATES COMP PLANS"
        "2018 AM INDICATES COMP PLANS"
        "2020 AM INDICATES COMP PLANS"
        "2021 AM INDICATES COMP PLANS"
        "2022 AM INDICATES COMP PLANS"
        "TRADE SELECT"
        "TRADE PLAN LEVEL   I (24)"
        "TRADE PLAN LEVEL  II (48)"
        "TRADE PLAN LEVEL III (72)"
        "X - TRADE PLAN LEVEL   I (24)"
        "X - TRADE PLAN LEVEL  II (48)"
        "X - TRADE PLAN LEVEL III (72)"
        "CHOICE OPTION (100)"
        "CHOICE OPTION (48)"
        "CHOICE OPTION (24)"
        "X - CHOICE OPTION (100)"
        "X - CHOICE OPTION (48)"
        "X - CHOICE OPTION (24)"
        "CUSTOM CHRISTIAN MIX"
        "CHRISTIAN SERIES LEVEL I (24)"
        "CHRISTIAN SERIES LEVEL II (24)"
        "CHRISTIAN SERIES LEVEL III (24)"
        "X - CHRISTIAN SERIES LEVEL I (24)"
        "X - CHRISTIAN SERIES LEVEL III (24)"
        "X - CHRISTIAN SERIES LEVEL II (24)"
        "X - CUSTOM CHRISTIAN MIX"
        "PLATINUM FICTION SERIES"
        "PLATINUM MYSTERY SERIES"
        "PLATINUM NONFICTION SERIES"
        "PLATINUM ROMANCE SERIES"
        "PLATINUM SPOTLIGHT SERIES"
        "X - PLATINUM FICTION SERIES"
        "X - PLATINUM ROMANCE SERIES"
        "X - PLATINUM SPOTLIGHT SERIES"
        "X - PLATINUM MYSTERY SERIES"
        "X - PLATINUM NONFICTION SERIES"
        "CUSTOM PLATINUM SERIES MIX"
        "X - CUSTOM PLATINUM SERIES MIX"
        "X - PLATNUM SPOTLIGHT SERIES"
        "X - PLATNUM MYSTERY SERIES"
        "PREMIER FICTION SERIES"
        "PREMIER ROMANCE SERIES"
        "PREMIER MYSTERY SERIES"
        "CUSTOM PREMIER SERIES MIX"
        "X - CUSTOM PREMIER SERIES MIX"
        "X - PREMIER ROMANCE SERIES"
        "X - PREMIER MYSTERY SERIES"
        "X - PREMIER FICTION SERIES"
        "X - PREMIER SERIES PLUS (24)"
        "X - WESTERN SERIES LEVEL II (24)"
        "X - WESTERN SERIES LEVEL I (24)"
        "BESTSELLER SERIES (12)"
        "X - STERLING MYSTERY SERIES"
        "STERLING MYSTERY SERIES"
        "X - AGATHA CHRISTIE SERIES"
        "WESTERN SERIES LEVEL I (24)"
        "WESTERN SERIES LEVEL II (24)"
        "WESTERN SERIES LEVEL III (24)"
        "X - WESTERN SERIES LEVEL III (24)"
        "CUSTOM MULTI SERIES MIX"
        "BESTSELLER SERIES (24)"
        "BESTSELLER SERIES (36)"
        "X - BESTSELLER SERIES (12)"
        "X - BESTSELLER SERIES (24)"
        "X - BESTSELLER SERIES (36)
        "X - CUSTOM MULTI SERIES MIX"

     

*/
            $standing_order_series = [
            "CLASS_A" => ["discount"=>.40, "name"=>"Platinum Fiction Series"],
            "CLASS_B" => ["discount"=>.40, "name"=>"Platinum Romance Series"],
            "CLASS_C" => ["discount"=>.40, "name"=>"Premier Fiction Series"],
            "CLASS_D" => ["discount"=>.40, "name"=>"Western Series Level I (24)"],
            "CLASS_E" => ["discount"=>.40, "name"=>"Western Series Level II (24)"],
            "CLASS_F" => ["discount"=>.40, "name"=>"Premier Romance Series"],
            "CLASS_G" => ["discount"=>.40, "name"=>'Premier Mystery Series'],
            "CLASS_H" => ["discount"=>1, "name"=>""],
            "CLASS_I" => ["discount"=>1, "name"=>""],
            "CLASS_J" => ["discount"=>1, "name"=>""],
            "CLASS_K" => ["discount"=>.40, "name"=>"Platinum Mystery Series"],
            "CLASS_L" => ["discount"=>.40, "name"=>'Platinum Spotlight Series'],
            "CLASS_M" => ["discount"=>.40, "name"=>'Christian Series Level I (24)'],
            "CLASS_N" => ["discount"=>.40, "name"=>'Christian Series Level II (24)'],
            "CLASS_O" => ["discount"=>.40, "name"=>'Christian Series Level III (24)'],
            "CLASS_P" => ["discount"=>.40, "name"=>""],
            "CLASS_Q" => ["discount"=>.40, "name"=>'Platinum Nonfiction Series'],
            "CLASS_R" => ["discount"=>.40, "name"=>"Agatha Christie Series"],
            "CLASS_S" => ["discount"=>.40, "name"=>'Sterling Mystery Series'],
            "CLASS_T" => ["discount"=>.40, "name"=>'Western Series Level III (24)']/*,
            "Platinum Mystery Series"//   // from inventories
            "Premier Romance Series"
            "Platinum Spotlight Series"
            "Platinum Nonfiction Series"
           "Christian Series Level II (24)
            Christian Series Level III (24)
            Premier Fiction Series
            Western Series Level II (24)
            Premier Mystery Series
            Christian Series Level I (24)
            Platinum Fiction Series
            Platinum Romance Series
            Western Series Level III (24)
            Western Series Level I (24)
            Sterling Mystery Series
            Christian Series Level II (24]
            Christian Series Level Iii (24*/
            ];


            return $standing_order_series[$series];
    }

}

