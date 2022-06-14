<?php namespace Sreynoldsjr\ReynoldsDbf\Interface;

interface DbfQueryBuilderInterface {
    function source();
    function setMemo($val);
    function setWritable($val);
    function getMemo();
    function with($arrayOfRelationshipNames);
    function where($column, $comparison, $value);
    function findByIndex($index, $columns = false);
    function find($primaryKeyValue);
    function index($index, $columns = false);
    function all($columns=['*']);
    function get();
    function test($record);
    function first($columns = false);
    function __get($name);
}