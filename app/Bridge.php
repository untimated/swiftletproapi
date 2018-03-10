<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bridge extends Model
{
	    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	 protected $fillable = [
        'ip','name','serial','user_id'
    ];

    public function devices(){
    	return $this->hasMany('App\Device');
    }

}
