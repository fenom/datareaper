<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackobotAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trackobot_accounts', function (Blueprint $collection) {
            $collection->unique('username');
            $collection->index('region');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trackobot_accounts', function (Blueprint $collection) {
            $collection->dropIndex('username');
            $collection->dropIndex('region');
        });
    }
}
