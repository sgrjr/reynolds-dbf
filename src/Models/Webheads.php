<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\ModelHeads;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeHeadTrait;

class Webheads extends ModelHeads {

     use InitializeHeadTrait;

     public $table = 'webheads';
     public $fillable = ["INDEX","KEY","ATTENTION", "DATE","BILL_1","BILL_2","BILL_3","BILL_4","COMPANY","STREET","CITY","STATE","POSTCODE","VOICEPHONE","OSOURCE","ISCOMPLETE", "ROOM","DEPT","COUNTRY","FAXPHONE","EMAIL","SENDEMCONF","PO_NUMBER","CINOTE","CXNOTE","TRANSNO","DATESTAMP","TIMESTAMP","LASTDATE","LASTTIME","LASTTOUCH","REMOTEADDR","deleted_at"];

     public function items(){
          return Webdetails::x()->where("REMOTEADDR", "==", $this->REMOTEADDR)->all();
     }

}