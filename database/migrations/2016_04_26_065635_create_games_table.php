<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('games', function (Blueprint $collection) {
            $collection->unique('id');
            $collection->index('mode');
            $collection->index('hero');
            $collection->index('hero_deck');
            $collection->index('opponent');
            $collection->index('opponent_deck');
            $collection->index('coin');
            $collection->index('result');
            $collection->index('duration');
            $collection->sparse('rank');
            $collection->sparse('legend');
            $collection->index('added');
            $collection->index('card_history.card.name');
            $collection->sparse('format');
            $collection->index('region');
            $collection->index('player');
            $collection->index('username');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('games', function (Blueprint $collection) {
            $collection->dropIndex('id');
            $collection->dropIndex('mode');
            $collection->dropIndex('hero');
            $collection->dropIndex('hero_deck');
            $collection->dropIndex('opponent');
            $collection->dropIndex('opponent_deck');
            $collection->dropIndex('coin');
            $collection->dropIndex('result');
            $collection->dropIndex('duration');
            $collection->dropIndex('rank');
            $collection->dropIndex('legend');
            $collection->dropIndex('added');
            $collection->dropIndex('card_history.card.name');
            $collection->dropIndex('format');
            $collection->dropIndex('region');
            $collection->dropIndex('player');
            $collection->dropIndex('username');
        });
    }
}
