<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiaryMilkEntries extends Model
{
    public $table = "diary_milk_enteries";
    protected $fillable = [
        'customer_id','weight','fat','snf','type','time'
    ];
}
