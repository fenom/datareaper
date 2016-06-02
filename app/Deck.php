<?php

namespace DataReaper;

use Jenssegers\Mongodb\Eloquent\HybridRelations;
use Illuminate\Database\Eloquent\Model;

class Deck extends \Moloquent
{
    use HybridRelations;
}
