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
            $collection->unique('cardId');
            $collection->index('name');
            $collection->index('cardSet');
            $collection->index('type');
            $collection->index('faction');
            $collection->index('rarity');
            $collection->index('cost');
            $collection->index('attack');
            $collection->index('health');
            $collection->index('durability');
            $collection->index('collectible');
            $collection->index('race');
            $collection->index('playerClass');
            $collection->index('mechanics');
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
            $collection->dropIndex('cardId');
            $collection->dropIndex('name');
            $collection->dropIndex('cardSet');
            $collection->dropIndex('type');
            $collection->dropIndex('faction');
            $collection->dropIndex('rarity');
            $collection->dropIndex('cost');
            $collection->dropIndex('attack');
            $collection->dropIndex('health');
            $collection->dropIndex('durability');
            $collection->dropIndex('collectible');
            $collection->dropIndex('race');
            $collection->dropIndex('playerClass');
            $collection->dropIndex('mechanics');
        });
    }
}
