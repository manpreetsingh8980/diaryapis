<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiaryFat extends Model
{
	
	public $table = "diary_fat";
	
    protected $fillable = [
        'fat'
    ];
}
