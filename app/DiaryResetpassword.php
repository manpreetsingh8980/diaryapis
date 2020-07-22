<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiaryResetpassword extends Model
{
	
	public $table = "diary_resetpassword";
	
    protected $fillable = [
        'id','email', 'token', 'expires_at', 'created_at'
    ];
}
