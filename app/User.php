<?php

namespace DataReaper;

use Jenssegers\Mongodb\Eloquent\HybridRelations;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HybridRelations;

    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Get the trackobot account record associated with the user.
     */
    public function trackobot_account()
    {
        return $this->hasOne('DataReaper\TrackobotAccount');
    }
}
