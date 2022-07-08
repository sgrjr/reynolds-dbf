<?php

return [
    "root_paths" => [
        env("DBF_ROOT_PATH_RW",'C:\\resources\\data\\Stephen_Reynolds\\WEBINFO\\RWDATA'),
        env("DBF_ROOT_PATH_R",'C:\\resources\\data\\Stephen_Reynolds\\WEBINFO\\rDATA')
    ],

    "find_dbfs" => false,
    
    "files" => [
      "inventories"=> ["invent.DBF", 0],
      "vendors"=> ["VENDOR.DBF",0],
      "passwords"=> ["password.dbf", 0],
      "backdetails"=> ["backdetail.DBF", 1],
      "backheads"=> ["backhead.DBF", 1],
      "booktexts"=> ["booktext.dbf", 1],
      "brodetails"=> ["brodetail.DBF", 1],
      "broheads"=> ["brohead.dbf", 1],
      "passfiles"=> ["passfile.DBF", 1],
      "standing_orders"=> ["standing.DBF", 1],
      "webheads"=> ["webhead.DBF", 0],
      "webdetails"=> ["webdetail.DBF", 0],
      "alldetails"=> ["alldetail.DBF", 1],
      "allheads"=> ["allhead.DBF", 1],
      "ancientdetails"=> ["ancientdetail.dbf", 1],
      "ancientheads"=> ["ancienthead.DBF", 1]
    ],

    "seeds" => []
];