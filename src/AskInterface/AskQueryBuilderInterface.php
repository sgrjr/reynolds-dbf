<?php namespace App\Ask\AskInterface;

interface AskQueryBuilderInterface {
    function source();
    function setTable();
    function setRoot($root);
    function setMemo($val);
    function setWritable($val);
    function getMemo();
    function setIndex($newIndex);
    function with($arrayOfRelationshipNames);
    function setParameter($parameterName, $parameterValue);
    function where($column, $comparison, $value);
    function setTests($testsValue);
    function setPerPage($perPageValue);
    function setPage($pageValue);
    function reduceColumns($array_of_property_names);
    function setColumns($array_of_property_names);
    function findByIndex($index, $columns = false);
    function find($primaryKeyValue);
    function index($index, $columns = false);
    function all($limit = 5, $page = false, $columns = false);
    function get();
    function test($record);
    function first($columns = false);
    function __get($name);
}