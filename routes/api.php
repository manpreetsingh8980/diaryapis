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
Route::get('/delete_customer/{customer_id}','ApiController@deleteCustomer');
#get customer
Route::get('/getcustomer/{customer_id}','ApiController@getCustomer');
#get profile
Route::get('/getprofile','ApiController@getProfile');
#update profile
Route::post('/updateprofile','ApiController@updateProfile');
#get fat
Route::get('/getfat','ApiController@getFat');
#get snf
Route::get('/getsnf','ApiController@getSNF');
#get rates
Route::get('/getrates','ApiController@getRates');
#add/update rate
Route::post('/addrates','ApiController@addRates');
#milk entry
Route::post('/addmilk','ApiController@addMilkEntries');
Route::post('/milksale','ApiController@milkSaleEntries');
Route::post('/milksalelist','ApiController@milkSaleList');
Route::get('/milk_list/{customer_id}','ApiController@customerMilkList');
Route::post('/generate_bill','ApiController@billGenerate');