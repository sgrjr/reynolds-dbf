<?php

use Illuminate\Database\Migrations\Migration;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webheads as Model;

class Webheads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        (new Model)->createTable();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        (new Model)->dropTable();
    }
}
