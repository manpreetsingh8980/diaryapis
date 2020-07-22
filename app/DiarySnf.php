<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiarySnf extends Model
{
	public $table = "diary_snf";
    protected $fillable = [
        'snf'
    ];
}
