<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


#Signup
Route::post('registerapi','ApiController@registerApi'); 
#login
Route::post('loginapi','ApiController@loginApi');
#forgot password
Route::post('forgot_password','ApiController@forgotPassword');
#dashboard
Route::get('/dashboard','ApiController@dashboard');
#add customer
Route::post('/addcustomer','ApiController@addCustomer');
#list customers
Route::post('/allcustomers','ApiController@allcustomers');
#edit customer
Route::post('/editcustomer','ApiController@editCustomer');
#delete customer
Route::post('/delete_customer','ApiController@deleteCustomer');
#get customer
Route::post('/getcustomer','ApiController@getCustomer');