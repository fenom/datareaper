<?php

namespace DataReaper;

use Jenssegers\Mongodb\Eloquent\HybridRelations;
use Illuminate\Database\Eloquent\Model;

class Game extends \Moloquent
{
    use HybridRelations;
}
