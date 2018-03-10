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
        'humidity','temperature','device_id'
    ];

}
