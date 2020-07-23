<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiaryUserMembership extends Model
{
    public $table = "diary_user_membership";
    protected $fillable = [
        'user_id','card_number','expiry_date','cvv','plan'
    ];
}
