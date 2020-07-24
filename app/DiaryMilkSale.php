<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiaryMilkSale extends Model
{
    public $table = "diary_milk_sale";
    protected $fillable = [
        'user_id','customer_id','weight','fat','snf','type','time'
    ];
	
	public function customer_info(){
        return $this->belongsTo('App\DiaryUsercustomers','customer_id','id');
    }

}
