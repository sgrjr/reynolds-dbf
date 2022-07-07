<?php namespace Sreynoldsjr\ReynoldsDbf\Helpers;

class DataEntry{
    public function __construct($name, $value, $type, $length, $decimal_count, $mysql_type, $original = true){
        $this->name = $name;

        if(str_contains($value,"\xE9")) $value = str_replace("\xE9","e",$value);

        $this->value = $value;
        $this->type = $type;
        $this->length = $length;
        $this->original = $original;
        $this->nullable = true;
        $this->decimal_count = $decimal_count;
        $this->mysql_type = $mysql_type;
    }

    public static function makeIt($name, $value, $type, $length, $decimal_count, $mysql_type, $original = true){
        return new static($name, $value, $type, $length, $decimal_count, $mysql_type, $original);
    }

    public static function make($value, Object $col){
        return static::makeIt($col->getName(), $value, $col->getType(), $col->getDataLength(), $col->getDecimalCount(), $col->getMySQLType(), $col->original);
    }

    public function toArray(){
        return [
            'name' => $this->name,
            'value' => $this->value,
            'type' => $this->type,
            'length' => $this->length,
            'original' => $this->original,
            'nullable' => $this->nullable,
            'decimal_count' => $this->decimal_count,
            'mysql_type' => $this->mysql_type
        ];
    }
}