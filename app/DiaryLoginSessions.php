<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiaryLoginSessions extends Model
{
    protected $fillable = [
        'user_id','device_type', 'device_token','header_api_token','api_token','token_status'
    ];
}
