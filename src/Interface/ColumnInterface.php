<?php namespace Sreynoldsjr\ReynoldsDbf\Interface;

interface ColumnInterface
{
    public function setVariable($name, $var);
    public function getHtml($template);
}