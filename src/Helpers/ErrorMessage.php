<?php namespace Sreynoldsjr\ReynoldsDbf\Helpers;

class ErrorMessage
{
  public function __construct($message="Success", $extensions=null, $debugMessage=null, $severity="info", $field=null){
    $this->field = $field;
    $this->message = $message;
    $this->severity = $severity;
    $this->debugMessage = new \stdclass;
    $this->debugMessage->id = 1;
    $this->debugMessage->message = $debugMessage;
    $this->extensions = $extensions;
  }

  public static function make($message="Success", $extensions=null, $debugMessage=null, $severity="info", $field=null){
    return new static($message, $extensions, $debugMessage, $severity, $field);
  }
}
