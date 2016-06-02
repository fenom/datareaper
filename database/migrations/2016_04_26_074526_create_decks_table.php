<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDecksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('decks', function (Blueprint $collection) {
            $collection->index(['class', 'archetype']);
            $collection->index('cards');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('decks', function (Blueprint $collection) {
            $collection->dropIndex(['class', 'archetype']);
            $collection->dropIndex('cards');
        });
    }
}
