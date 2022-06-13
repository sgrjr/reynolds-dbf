<?php

if(!function_exists('config') ){
     function config($name){
        return file_get_contents('../config/reynolds-dbf.php');
     }
}

return [
    "root_paths" => [
        env("DBF_ROOT_PATH_RW",'C:\\resources\\data\\Stephen_Reynolds\\WEBINFO\\RWDATA'),
        env("DBF_ROOT_PATH_R",'C:\\resources\\data\\Stephen_Reynolds\\WEBINFO\\rDATA')
    ],

    "find_dbfs" => false,
    
    "files" => [
      "vendor"=> ["VENDOR.DBF",0],
      "inventory"=> ["invent.DBF", 0],
      "users"=> ["password.dbf", 0],
      "alldetail"=> ["alldetail.DBF", 1],
      "allhead"=> ["allhead.DBF", 1],
      "ancientdetail"=> ["ancientdetail.dbf", 1],
      "ancienthead"=> ["ancienthead.DBF", 1],
      "backdetail"=> ["backdetail.DBF", 1],
      "backhead"=> ["backhead.DBF", 1],
      "booktext"=> ["booktext.dbf", 1],
      "brodetail"=> ["brodetail.DBF", 1],
      "brohead"=> ["brohead.dbf", 1],
      "passfile"=> ["passfile.DBF", 1],
      "standing_order"=> ["standing.DBF", 1],
      "webhead"=> ["webhead.DBF", 0],
      "webdetail"=> ["webdetail.DBF", 0]
    ]
];


