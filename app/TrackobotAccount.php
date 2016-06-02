<?php

namespace DataReaper;

use Illuminate\Database\Eloquent\Model;

class TrackobotAccount extends \Moloquent
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['username', 'token'];
}
