<?php namespace Sreynoldsjr\ReynoldsDbf\Interface;

interface Column
{
    public function setVariable($name, $var);
    public function getHtml($template);
}