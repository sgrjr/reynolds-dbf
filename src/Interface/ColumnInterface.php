<?php \App\Ask\Interface;

interface Column
{
    public function setVariable($name, $var);
    public function getHtml($template);
}