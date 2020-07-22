<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiaryUsercustomers extends Model
{
    protected $fillable = [
        'id','user_id', 'name', 'phone_number', 'address','category'
    ];
}
