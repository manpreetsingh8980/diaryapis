<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiaryUsers extends Model
{
    protected $fillable = [
        'id','first_name', 'last_name','email','password','phone_number','address'
    ];
}
