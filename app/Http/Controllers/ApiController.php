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
use App\DiaryProductimgs;
use App\DiaryUsercustomers;
use Hash;
use Str;
use View;
use Mail;

class ApiController extends BaseController
{
    //use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
	
	public function __construct(Request $header_request){
       
        $this->device_type      = strtoupper($header_request->header('device-type'));
        $this->device_token     = $header_request->header('device-token');
        $this->header_api_token = $header_request->header('api-token');
    }    
	
	/*****fn to signup*****/
	public function registerApi(Request $request){
		
		$first_name = ((isset($request->first_name)) ? ($request->first_name) : '');
		
		if($first_name == ""){
			return Response::json(['success'=>'0','message'=>'Please provide a first name.'],200);
            exit;
		}
		
		$last_name = ((isset($request->last_name)) ? ($request->last_name) : '');
		
		if($last_name == ""){
			return Response::json(['success'=>'0','message'=>'Please provide a last name.'],200);
            exit;
		}
		
		$email = ((isset($request->email)) ? ($request->email) : '');
		
		if($email == ""){
			return Response::json(['success'=>'0','message'=>'Please provide an email.'],200);
            exit;
		}
		
		$password = ((isset($request->password)) ? ($request->password) : '');
		
		if($password == ""){
			return Response::json(['success'=>'0','message'=>'Please provide a password.'],200);
            exit;
		}
		
		$phone_number = ((isset($request->phone_number)) ? ($request->phone_number) : '');
		
		if($phone_number == ""){
			return Response::json(['success'=>'0','message'=>'Please provide a phone number.'],200);
            exit;
		}
		
		//validations
		$userData = array('email' =>  $request->email,'password'  =>  $request->password, 'phone_number'=> $request->phone_number);
        $rules = array('email' =>'required|email|unique:diary_users','password'  =>'required|min:6',  'phone_number' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10');
        $validator = Validator::make($userData,$rules);
		
		#if validation error 
        if($validator->fails()){
            $main_errors = $validator->getMessageBag()->toArray();
 
            $errors = array();
            foreach($main_errors as $key=>$value)
            {
                if($key == "password")
                {
                    $main_errors[$key][0] = $value;
                }
                if($key == "email")
                {
                    $main_errors[$key][0] = $value;
                }
				if($key == "phone_number")
                {
                    $main_errors[$key][0] = $value;
                }
                
                return Response::json([
                    'success' => '0',
                    'message' => $value[0]
                ],200);
            }
        }#if no validation error then save the user
        else{
            #inset into the table
 
			try{
 
                $id = DiaryUsers::insertGetId([
                    'first_name' =>$request->first_name,     
					'last_name' =>$request->last_name, 					
                    'email'=>$request->email,
                    'password'=>Hash::make($request->password),
                    'phone_number'=>$request->phone_number,
                ]);
            }catch(\Exception $e){
                return Response::json(['success' => '0','message'=>$e->getMessage()],200);
            }
 
            #json final response
            return Response::json(['success'=>'1','message'=>'Register Successfully.','user_id'=>(string)$id],200);
            exit;
            
        }
	}#end register function
	
	/*******login fn starts here*********/
	public function loginApi(Request $request){
		
		if( $this->device_token=='' ){
            return Response::json(['success' => '0','message'=>'Insufficient data in headers'],200);
        }
		
		$email = ((isset($request->email)) ? ($request->email) : '');
		
		if($email == ""){
			return Response::json(['success'=>'0','message'=>'Please provide an email.'],200);
            exit;
		}
		
		$password = ((isset($request->password)) ? ($request->password) : '');
		
		if($password == ""){
			return Response::json(['success'=>'0','message'=>'Please provide a password.'],200);
            exit;
		}
		
		
		##validation
        $userData = array('email' =>  $request->email,'password'  =>  $request->password);
        $rules = array('email' =>'required|email','password'  =>'required|min:6');
        $validator = Validator::make($userData,$rules);
		
		#if validation error 
        if($validator->fails()){
            $main_errors = $validator->getMessageBag()->toArray();
 
            $errors = array();
            foreach($main_errors as $key=>$value){
 
                if($key == "password"){
 
                    $main_errors[$key][0] = $value;
                }
                if($key == "email"){
 
                    $main_errors[$key][0] = $value;
                }
 
                return Response::json([
                    'success' => '0',
                    'message' => $value[0]
                ],200);
            }
 
        }else{
            
			#check if email exists into the table
            try{
                $check_email = DiaryUsers::where('email',$request->email)->first();
 
            }catch(\Exception $e){
                return Response::json(['success' => '0','message'=>$e->getMessage()],200);
            }
			
			#if email exists get the user password
            if(empty($check_email)){
                return Response::json(['success'=>'0','message'=>'Invalid Email id.'],200);
                exit;
            }else{
				
				#getting the password.
                try{
                    $user_details = DiaryUsers::select('id','password')->where('email',$request->email)->first();
                }catch(\Exception $e){
                    return Response::json(['success' => '0','message'=>$e->getMessage()],200);
                }
				
				#removing object class
                $user_details=json_decode(json_encode($user_details),true);
				
				#checking the db password with post password
				if(Hash::check($request->password,$user_details['password'])){

					DiaryLoginSessions::where('user_id', $user_details['id'])->update(['token_status' => 0]);
 
					#password matches, get the login_token from login_sessions
					/**Create API token**/
					$api_token = Str::random(60);
					 
					try{
						DiaryLoginSessions::insert([
									'user_id' => $user_details['id'],
									'api_token' => $api_token,
									'device_type' => $this->device_type,
									'device_token'=>$this->device_token,
									'header_api_token'=> '',
									'token_status'=>1
									]);
					}catch(\Exception $e){
						return Response::json(['success' => '0','message'=>$e->getMessage()],200);
					}
 
					$data = array('api_token'=>$api_token,'user_id'=>(string)$user_details['id']);
					
					return Response::json(['success'=>'1','message'=>'Login Successful.','data'=>$data ],200);exit;
					
				}else{
					return Response::json(['success'=>'0','message'=>'Incorrect Password.'],200);exit;
				}
			}#check email ends
 
        }#validation else ends
		
	}#login fn ends here
	
	/*******Forgot password api starts**********/
	public function forgotPassword(Request $request){
		
		$email = ((isset($request->email)) ? ($request->email) : '');
		if($email == ""){
			return Response::json(['success'=>'0','message'=>'Please provide an email.'],200);
            exit;
		}
		
		##validation
        $userData = array('email' =>  $request->email);
        $rules = array('email' =>'required|email');
        $validator = Validator::make($userData,$rules);
		
		#if validation error 
        if($validator->fails()){
            $main_errors = $validator->getMessageBag()->toArray();
 
            $errors = array();
            foreach($main_errors as $key=>$value){
				
                if($key == "email"){
 
                    $main_errors[$key][0] = $value;
                }
 
                return Response::json([
                    'success' => '0',
                    'message' => $value[0]
                ],200);
            }
 
        }else{
			
			#inset into the table

			try{    
				$user_detail = DiaryUsers::select("first_name","email")->where("email",$request->email)->first();

				if(!empty($user_detail->first_name)) {                
					$name = $user_detail->first_name;
					
					#check if already exists email
					$user_details = DiaryResetpassword::select("email")->where("email",$request->email)->first();
					
					if(!empty($user_details->email)) { 
						$update_array = array('token'=>Str::Random(60),'expires_at' => 0);
			
						$update_pass = DiaryResetpassword::where('email',$request->email)->update($update_array);
						
					}else{
						
						DiaryResetpassword::insert([
							'email' => $request->email,
							'token' => Str::Random(60),
							'created_at' => strtotime('now'),
							'expires_at' => 0
						]);

					}
					
					 

					$tokenData = DiaryResetpassword::where('email', $request->email)->first();

					if(!empty($tokenData->token)){
						$link = url('/') . '/password/reset/' . $tokenData->token . '?email=' . encrypt($user_detail->email);
						$email = $request->email;
						$data = array('name'=>$name, 'link'=>$link);
						//mail("someone@example.com","My subject",$msg);
						//Mail::send('mail', $data, function($message) use($name,$email){
							////$message->to($email, $name)->subject('Forgot Password Mail');
							//$message->from($_ENV['MAIL_USERNAME']);
						//});
						
						$to = $email;

						$subject = 'Forgot Password Mail';

						$headers = "From: " . strip_tags('pandon.php@gmail.com') . "\r\n";
						$headers .= "Reply-To: ". strip_tags('pandon.php@gmail.com') . "\r\n";
						$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

						$message = '<p><strong>Click here to reset password</strong> : '.$link.'.</p>';


						mail($to, $subject, $message, $headers);
						
						
						//Users::where("email",$_POST['email'])->update(['password'=>Hash::make($password)]);
						#json final response
						return Response::json(['success'=>'1','message'=>'An email has been send on your email id.'],200);
						exit;
					}else{
						return Response::json(['success'=>'0','message'=>'Token does not exists.'],200);
						exit;
					}                
				}else{
					return Response::json(['success'=>'0','message'=>'User does not exists.'],200);
					exit;
				}

			}catch(\Exception $e){
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
		}#validation else ends
		
		
		
		
	}/*****forgot pass ends here****/
	
	/****list of product imgs******/
	public function dashboard(Request $request){
		
		$product_imgs = glob(public_path()."/images/diary/*.*");
		
		
		if(!empty($product_imgs)){
			$array_imgs = [];
			$count = 0;
			foreach($product_imgs as $key=>$value){
				$image_name = basename($value);
				$array_imgs[] = url('/').'/public/images/diary/'.$image_name;
				$count++;
			}
			
			return Response::json(['success'=>'1','message'=>'list successfull','data'=>$array_imgs ],200);exit;
		}else{
			return Response::json(['success'=>'o','message'=>'No image Found.'],200);exit;
		}
		
		
	}/***dashboard fn ends here***/
	
	/******fn to add cutsomers*****/
	public function addCustomer(Request $request){
		
		if( $this->header_api_token==''){
            return Response::json(['success' => '0','message'=>'Please provide api-token in headers'],200);
        }
		
		#get user id from the login token $this->header_api_token
		try{
 
            $check_token = DiaryLoginSessions::select('user_id')->where(['api_token'=>$this->header_api_token,'token_status'=>1])
                           ->first();
					   
			if(!empty($check_token)){
 
                $user_id = $check_token->user_id;
				
				$name = ((isset($request->name)) ? ($request->name) : '');
		
				if($name == ""){
					return Response::json(['success'=>'0','message'=>'Please provide a name.'],200);
					exit;
				}
				
				$phone_number = ((isset($request->phone_number)) ? ($request->phone_number) : '');
				
				if($phone_number == ""){
					return Response::json(['success'=>'0','message'=>'Please provide a phone number.'],200);
					exit;
				}
				
				
				$address = ((isset($request->address)) ? ($request->address) : '');
				
				if($address == ""){
					return Response::json(['success'=>'0','message'=>'Please provide a address.'],200);
					exit;
				}
				
				$category = ((isset($request->category)) ? ($request->category) : '');
				
				if($category == ""){
					return Response::json(['success'=>'0','message'=>'Please provide a customer category.'],200);
					exit;
				}
				
				//validations
				$userData = array( 'phone_number'=> $request->phone_number);
				$rules = array('phone_number' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10');
				$validator = Validator::make($userData,$rules);
				
				#if validation error 
				if($validator->fails()){
					$main_errors = $validator->getMessageBag()->toArray();
		 
					$errors = array();
					foreach($main_errors as $key=>$value)
					{
						
						if($key == "phone_number")
						{
							$main_errors[$key][0] = $value;
						}
						
						return Response::json([
							'success' => '0',
							'message' => $value[0]
						],200);
					}
				}#if no validation error then save the user
				else{
					
					#inset into the table
		 
					try{
		 
						$id = DiaryUsercustomers::insertGetId([
							'user_id'=>$user_id,
							'name' =>$request->name, 
							'phone_number'=>$request->phone_number,    
							'address' =>$request->address,
							'category'=>$request->category,
						]);
					}catch(\Exception $e){
						return Response::json(['success' => '0','message'=>$e->getMessage()],200);
					}
		 
					#json final response
					return Response::json(['success'=>'1','message'=>'Customer Register Successfully.','customer_id'=>(string)$id],200);
					exit;
					
				}#validation else ends
				
            }else{
 
                return Response::json(['success' => '0','message'=>'API Token does not exists.'],200);
            }    			   
 
        }catch(\Exception $e){
 
            return Response::json(['success' => '0','message'=>$e->getMessage()],200);
        }
		
		
		
	}/***add customer ends here***/
	
	
	/*****fn to get list of all customners******/
	public function allcustomers(Request $request){
		if( $this->header_api_token==''){
            return Response::json(['success' => '0','message'=>'Please provide api-token in headers'],200);
        }
		
		if(!isset($request->user_id) || $request->user_id == ''){
 
            return Response::json(['success' => '0','message'=>'User id is missing.'],200);
        }
		
		
		try{
 
            $check_userid = DiaryUsers::where('id',$request->user_id)
                            ->first();
 
        }catch(\Exception $e){
 
            return Response::json(['success' => '0','message'=>$e->getMessage()],200);
        }
		
		if(!empty($check_userid)){
			
			#get user id from the login token $this->header_api_token
			try{
	 
				$check_token = DiaryLoginSessions::where(['user_id'=>$request->user_id,'api_token'=>$this->header_api_token])
                           ->get();
						   
				if(count($check_token) > 0){
					
					#inset into the table
					try{
		 
						$listcustomers = DiaryUsercustomers::select('id','name','address','phone_number','category')->where('user_id','=',$request->user_id)->get()->toArray();
						
					}catch(\Exception $e){
						return Response::json(['success' => '0','message'=>$e->getMessage()],200);
					}
					
					#json final response
					return Response::json(['success'=>'1','message'=>'List Successfully.','data'=>$listcustomers],200);
					exit;
					
				}else{
	 
					return Response::json(['success' => '0','message'=>'API Token does not exists.'],200);
				}    			   
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
		
		}else{
 
            return Response::json(['success' => '0','message'=>'User does not exists.'],200);
        }
		
		
	}/***all customers list fn ends here***/
	
	/*******Edit customer*******/
	public function editCustomer(Request $request){
		
		if( $this->header_api_token==''){
            return Response::json(['success' => '0','message'=>'Please provide api-token in headers'],200);
        }
		
		#check logged in user id
		try{
			
			$user_id = DiaryLoginSessions::select('user_id')->where(['api_token'=>$this->header_api_token,'token_status'=>1])->first();
					
		}catch(\Exception $e){
			return Response::json(['success' => '0','message'=>$e->getMessage()],200);
		}
		
		if(!empty($user_id)){
			
			if(!isset($request->id) || $request->id == ''){
 
				return Response::json(['success' => '0','message'=>'Customer id is missing.'],200);
			}
			
			
			try{
	 
				$check_customerid = DiaryUsercustomers::where('id',$request->id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_customerid)){
				
				#get user id from the login token $this->header_api_token
				try{
					
					$update_array = array();
					
					if(isset($request->name) || $request->name != ''){
						$update_array['name'] = $request->name;
					}
					if(isset($request->address) || $request->address != ''){
						$update_array['address'] = $request->address;
					}
					if(isset($request->phone_number) || $request->phone_number != ''){
						$update_array['phone_number'] = $request->phone_number;
					}
					if(isset($request->category) || $request->category != ''){
						$update_array['category'] = $request->category;
					} 	
				
					
					if(!empty($update_array)){
						$update = DiaryUsercustomers::where(['id'=>$request->id,'user_id'=>$user_id->user_id])->update($update_array);
						
						if($update == 1){
							return Response::json(['success' => '1','message'=>'Customer updated successfully'],200);
						}else{
							return Response::json(['success' => '0','message'=>'Please try again'],200);
						}
						
					}else{
						return Response::json(['success' => '0','message'=>'Please add any parameter to edit'],200);
					}
					
				}catch(\Exception $e){
		 
					return Response::json(['success' => '0','message'=>$e->getMessage()],200);
				}
			
			}else{
	 
				return Response::json(['success' => '0','message'=>'Customer does not exists.'],200);
			}
		}else{
			return Response::json(['success' => '0','message'=>'Logged in User does not exists.'],200);
		}
		
	}/***edit customer fn ends here***/
	
	
	/********delete customer**********/
	public function deleteCustomer(Request $request){
		
		if( $this->header_api_token==''){
            return Response::json(['success' => '0','message'=>'Please provide api-token in headers'],200);
        }
		
		#check logged in user id
		try{
			
			$user_id = DiaryLoginSessions::select('user_id')->where(['api_token'=>$this->header_api_token,'token_status'=>1])->first();
					
		}catch(\Exception $e){
			return Response::json(['success' => '0','message'=>$e->getMessage()],200);
		}
		
		if(!empty($user_id)){
			
			if(!isset($request->id) || $request->id == ''){
 
				return Response::json(['success' => '0','message'=>'Customer id is missing.'],200);
			}
			
			
			try{
	 
				$check_customerid = DiaryUsercustomers::where('id',$request->id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_customerid)){
				
				#get user id from the login token $this->header_api_token
				try{
					
					$delete = DiaryUsercustomers::where(['id'=>$request->id,'user_id'=>$user_id->user_id])->delete();
					
					if($delete == 1){
						return Response::json(['success' => '1','message'=>'Customer deleted successfully'],200);
					}else{
						return Response::json(['success' => '0','message'=>'Please try again'],200);
					}
						
					
					
				}catch(\Exception $e){
		 
					return Response::json(['success' => '0','message'=>$e->getMessage()],200);
				}
			
			}else{
	 
				return Response::json(['success' => '0','message'=>'Customer does not exists.'],200);
			}
		}else{
			return Response::json(['success' => '0','message'=>'Logged in User does not exists.'],200);
		}
	}/****dlt customer ends****/
	
	/*****fn to get customer*********/
	public function getCustomer(Request $request){
		if( $this->header_api_token==''){
            return Response::json(['success' => '0','message'=>'Please provide api-token in headers'],200);
        }
		
		#check logged in user id
		try{
			
			$user_id = DiaryLoginSessions::select('user_id')->where(['api_token'=>$this->header_api_token,'token_status'=>1])->first();
					
		}catch(\Exception $e){
			return Response::json(['success' => '0','message'=>$e->getMessage()],200);
		}
		
		if(!empty($user_id)){
			
			if(!isset($request->id) || $request->id == ''){
 
				return Response::json(['success' => '0','message'=>'Customer id is missing.'],200);
			}
			
			
			try{
				$check_customerid = DiaryUsercustomers::where('id',$request->id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_customerid)){
				
				#get user id from the login token $this->header_api_token
				try{
					
					$data = DiaryUsercustomers::select('name','address','phone_number','category')->where(['id'=>$request->id,'user_id'=>$user_id->user_id])->first();
					
					if(!empty($data)){
						return Response::json(['success' => '1','message'=>'Success','data'=>$data],200);
					}else{
						return Response::json(['success' => '0','message'=>'Data not found.'],200);
					}
					
					
				}catch(\Exception $e){
		 
					return Response::json(['success' => '0','message'=>$e->getMessage()],200);
				}
			
			}else{
	 
				return Response::json(['success' => '0','message'=>'Customer does not exists.'],200);
			}
		}else{
			return Response::json(['success' => '0','message'=>'Logged in User does not exists.'],200);
		}
		
	}/***get customer ends here***/
	
}
