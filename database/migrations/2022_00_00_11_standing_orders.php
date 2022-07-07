<?php

use Illuminate\Database\Migrations\Migration;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Standing_orders;

class StandingOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        (new Standing_orders)->createTable();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        (new Standing_orders)->dropTable();
    }
}
