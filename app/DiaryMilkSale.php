<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiaryMilkSale extends Model
{
    public $table = "diary_milk_sale";
    protected $fillable = [
        'customer_id','weight','fat','snf','type','time'
    ];
}
