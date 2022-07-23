<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Inventories;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeBooktextsTrait;

class Booktexts extends BaseModel implements ModelInterface {

use SoftDeletes, InitializeBooktextsTrait;

protected $appends = ['body'];
public $fillable = ['COMPUTER','DATESTAMP', 'deleted_at', 'FILENAME', 'INDEX', 'ISTHERE', 'KEY', 'LASTDATE', 'LASTTIME', 'LASTTOUCH', 'PUBDATE', 'SUBJECT', 'SYNOPSIS', 'TIMESTAMP'];

protected $table = "booktexts";
   protected $seed = [
    'dbf_booktexts'
  ];
  public $migration = "2022_00_00_12_booktexts.php";
  protected $indexes = [];
	protected $dbfPrimaryKey = 'INDEX';

      protected $attributeTypes = [
        
        "_config"=>"booktexts",

       "created_at"=>[
            "name" => "created_at",
            "type" => "TIMESTAMP",
            "length" => 19
           ],
       "updated_at"=>[
            "name" => "updated_at",
            "type" => "TIMESTAMP",
            "length" => 19
       ],
      ];
  
    public $foreignKeys = [
        ["KEY","ISBN","inventories"] //KEY references ISBN on inventories
    ];


  public function title()
  {
      return $this->belongsTo(Inventories::class,'KEY','ISBN');
  }
  
public function getBodyAttribute(){
  $x = new \stdclass;
      
  switch($this->SUBJECT){
          case "@PLCOMMENTA:":
              $x->type = "commenta";
              $x->subject = "About Author";
              break;

          case "@PLBOOKCOPY:":
              $x->type = "bookcopy";
              $x->subject = "Summary";
              break;
          case "@JOBBERTEXT:":
            $x->type = "jobber";
            $x->subject = "Synopsis";
            break;
          case "@PLREVIEWAA:":
            $x->type = "review";
            $x->subject = "Review";
            break;

          default:
              $x->type = "summary";
              $x->subject = $this->SUBJECT;
      }

      $x->body = $this->SYNOPSIS_MEMO;

      return $x;

}

	public function getObjectByName($name){
		return $this->$name;
	}

}
