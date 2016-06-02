<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cards', function (Blueprint $collection) {
            $collection->unique('id');
            $collection->index('name');
            $collection->index('mana');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cards', function (Blueprint $collection) {
            $collection->dropIndex('id');
            $collection->dropIndex('name');
            $collection->dropIndex('mana');
        });
    }
}
