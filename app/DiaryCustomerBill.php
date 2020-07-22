<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiaryCustomerBill extends Model
{
    public $table = "diary_customer_bill";
    protected $fillable = [
        'user_id','customer_id','total_weight','total_amount','start_date','end_date'
    ];
}
