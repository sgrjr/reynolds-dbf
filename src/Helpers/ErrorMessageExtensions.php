<?php namespace Sreynoldsjr\ReynoldsDbf\Helpers;

class ErrorMessageExtensions {
    public function __construct($category){
    $this->category = $category;
    $this->$category = new \stdclass;
  }

  public function add($field, $message){
    $n = $this->category;
    
    if(isset($this->$n->$field)){
      $this->$n->$field[] = $message;
    }else{
       $this->$n->$field = [$message];
    }

  }
}