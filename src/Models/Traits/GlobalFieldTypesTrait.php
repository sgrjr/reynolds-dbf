<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

use Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Sreynoldsjr\ReynoldsDbf\Events\FailedWritingToDbf;
use Sreynoldsjr\ReynoldsDbf\Events\NewDbfEntryCreated;
use Sreynoldsjr\ReynoldsDbf\Events\ExistingDbfEntryUpdated;

trait GlobalFieldTypesTrait {
	public function getGlobalFieldTypes(){
	 	$types = new \stdclass;
        $types->DBFFIELD_TYPE_MEMO = "M";      // Memo type field.
        $types->DBFFIELD_TYPE_CHAR = "C";      // Character field.
        $types->DBFFIELD_TYPE_NUMERIC = "N";   // Numeric
        $types->DBFFIELD_TYPE_FLOATING = "F";  // Floating point
        $types->DBFFIELD_TYPE_DATE = "D";      // Date
        $types->DBFFIELD_TYPE_LOGICAL = "L";   // Logical - ? Y y N n T t F f (? when not initialized).
        $types->DBFFIELD_TYPE_DATETIME = "T";  // DateTime
        $types->DBFFIELD_TYPE_INDEX = "I";    // Index 
        $types->DBFFIELD_IGNORE_0 = "0";       // ignore this field
        return $types;
	}
}

 