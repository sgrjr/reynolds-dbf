<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces;

interface ModelInterface {
    public function createTable();
    public function seedTable();
    public function dropTable();
    public function emptyTable();
    public function delete();
    public function hasOwner($user);
    public function dbf();
    public function initialize();
}
