<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
	    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'serial','ip','name','bridge_id'
    ];

    public function devicedatas(){
    	return $this->hasMany('App\Devicedata')
    }

}
