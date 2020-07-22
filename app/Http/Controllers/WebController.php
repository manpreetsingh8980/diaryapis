<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Response;
use App\DiaryUsers;
use App\DiaryLoginSessions;
use App\DiaryResetpassword;
use Hash;
use Str;
use view;
use Carbon\Carbon;

class WebController extends BaseController
{
   
	/***reset fn starts*****/
	public function resetPassword(Request $request,$token){
		
		$token = ((isset($token)) ? ($token) : '');
		if($token == ""){
			return Response::json(['success'=>'0','message'=>'Token missing.'],200);
            exit;
		}
		
		try{    
			$user_detail = DiaryResetpassword::select("email","expires_at")->where("token",$token)->first();
			if(!empty($user_detail->email)) { 
				
				$expires_at = $user_detail->expires_at;
				
				if($expires_at == 1){
					
					return view("resetpwdform")->withErrors('Link Expired!!');
					
				}else{
					$user_details = DiaryUsers::select("email")->where("email",$user_detail->email)->first();
					if(!empty($user_details->email)) { 
					
						$email = $user_details->email;
						return view("resetpwdform")->with('email',$email);
					
					}else{
						return view("resetpwdform")->withErrors('Email does not exists');
					}
				}
				
				
			}else{
				return view("resetpwdform")->withErrors('Link Expired!!');
            } 
		}catch(\Exception $e){
			return Response::json(['success' => '0','message'=>$e->getMessage()],200);
		}
			
	}/***reset fn ends here***/
	
	/************************
	fn to change the password
	*************************/
	public function updatePassword(Request $request){
		
		$new_password = $request->newpassword;
		$useremail = $request->useremail;
		
		$check_email_exists = DiaryUsers::where('email',$useremail)->get()->toArray();
		
		if(!empty($check_email_exists)){
			$update_array = array('password'=>Hash::make($new_password));
		
			$update_pass = DiaryUsers::where('email',$useremail)->update($update_array);
			
			$update_arrayreset = array('expires_at'=>1);
			
			DiaryResetpassword::where('email',$useremail)->update($update_arrayreset);
			
			if($update_pass == 1){
				return response([
						'success' => true,
						'message' => "Password Updated",
					]);
			}else{
				return response([
						'success' => false,
						'message' => "Please try again later!!",
					]);
			}
		}else{
			return response([
					'success' => 0,
					'message' => "Email doesn't exists. Please try again",
				]);
		}
					
		
	}/****change password ends here****/
	
	
}
