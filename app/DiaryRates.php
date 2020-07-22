<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiaryRates extends Model
{
	
	public $table = "diary_rates";
    protected $fillable = [
        'user_id','fat_id','snf_id','rate'
    ];
}
