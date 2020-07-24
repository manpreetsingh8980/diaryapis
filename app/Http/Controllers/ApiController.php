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
use App\DiaryFat;
use App\DiarySnf;
use App\DiaryRates;
use App\DiaryMilkEntries;
use App\DiaryMilkSale;
use App\DiaryCustomerBill;
use App\DiaryUserMembership;
use Hash;
use Str;
use View;
use Mail;
use Illuminate\Support\Facades\DB;
use PDF;

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
					
					return Response::json(['success'=>'1','message'=>'Login Successful.','logged_in_user'=>$data ],200);exit;
					
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
			
			return Response::json(['success'=>'1','message'=>'list successfull','dashboard'=>$array_imgs ],200);exit;
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
 
            $check_token = DiaryLoginSessions::select('user_id')->where(['api_token'=>$this->header_api_token])
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
					return Response::json(['success'=>'1','message'=>'List Successfully.','list_customers'=>$listcustomers],200);
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
						
						//validations
						$userData = $update_array;
						$rules = array('phone_number' => 'regex:/^([0-9\s\-\+\(\)]*)$/|min:10');
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
							
							$update = DiaryUsercustomers::where(['id'=>$request->id,'user_id'=>$user_id->user_id])->update($update_array);
						
							if($update == 1){
								return Response::json(['success' => '1','message'=>'Customer updated successfully'],200);
							}else{
								return Response::json(['success' => '0','message'=>'Please try again'],200);
							}
						
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
			return Response::json(['success' => '0','message'=>'User is not logged in.'],200);
		}
		
	}/***edit customer fn ends here***/
	
	
	/********delete customer**********/
	public function deleteCustomer($customer_id){
		
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
			
			if(!isset($customer_id) || $customer_id == ''){
 
				return Response::json(['success' => '0','message'=>'Customer id is missing.'],200);
			}
			
			
			try{
	 
				$check_customerid = DiaryUsercustomers::where('id',$customer_id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_customerid)){
				
				#get user id from the login token $this->header_api_token
				try{
					
					$delete = DiaryUsercustomers::where(['id'=>$customer_id,'user_id'=>$user_id->user_id])->delete();
					
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
			return Response::json(['success' => '0','message'=>'User is not logged in.'],200);
		}
	}/****dlt customer ends****/
	
	/*****fn to get customer*********/
	public function getCustomer($customer_id){
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
			
			if(!isset($customer_id) || $customer_id == ''){
 
				return Response::json(['success' => '0','message'=>'Customer id is missing.'],200);
			}
			
			
			try{
				$check_customerid = DiaryUsercustomers::where('id',$customer_id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_customerid)){
				
				#get user id from the login token $this->header_api_token
				try{
					
					$data = DiaryUsercustomers::select('name','address','phone_number','category')->where(['id'=>$customer_id,'user_id'=>$user_id->user_id])->first();
					
					if(!empty($data)){
						return Response::json(['success' => '1','message'=>'Success','get_customer'=>$data],200);
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
			return Response::json(['success' => '0','message'=>'User is not logged in.'],200);
		}
		
	}/***get customer ends here***/
	
	/*****fn to get customer*********/
	public function getProfile(){
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
			
			
			try{
				$check_userid = DiaryUsers::where('id',$user_id->user_id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_userid)){
				
				#get user id from the login token $this->header_api_token
				try{
					
					$data = DiaryUsers::where(['id'=>$user_id->user_id])->first();
					
					if(!empty($data)){
						return Response::json(['success' => '1','message'=>'Success','get_profile'=>$data],200);
					}else{
						return Response::json(['success' => '0','message'=>'Data not found.'],200);
					}
					
					
				}catch(\Exception $e){
		 
					return Response::json(['success' => '0','message'=>$e->getMessage()],200);
				}
			
			}else{
	 
				return Response::json(['success' => '0','message'=>'User does not exists.'],200);
			}
		}else{
			return Response::json(['success' => '0','message'=>'User is not logged in.'],200);
		}
		
	}/***get customer ends here***/
	
	#fn to update logged in user profile
	public function updateProfile(Request $request){
		
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
			
			if(!isset($user_id->user_id) || $user_id->user_id == ''){
 
				return Response::json(['success' => '0','message'=>'User id is missing.'],200);
			}
			
			
			try{
	 
				$check_userid = DiaryUsers::where('id',$user_id->user_id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_userid)){
				
				#get user id from the login token $this->header_api_token
				try{
					
					$update_array = array();
					
					if(isset($request->first_name) || $request->first_name != ''){
						$update_array['first_name'] = $request->first_name;
					}
					if(isset($request->last_name) || $request->last_name != ''){
						$update_array['last_name'] = $request->last_name;
					}
					if(isset($request->phone_number) || $request->phone_number != ''){
						$update_array['phone_number'] = $request->phone_number;
					}
					if(isset($request->address) || $request->address != ''){
						$update_array['address'] = $request->address;
					} 	
				
					
					if(!empty($update_array)){
						
						//validations
						$userData = $update_array;
						$rules = array('phone_number' => 'regex:/^([0-9\s\-\+\(\)]*)$/|min:10');
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
							
							$update = DiaryUsers::where(['id'=>$user_id->user_id])->update($update_array);
						
							if($update == 1){
								return Response::json(['success' => '1','message'=>'User updated successfully'],200);
							}else{
								return Response::json(['success' => '0','message'=>'Please try again'],200);
							}
						
						}
						
						
					}else{
						return Response::json(['success' => '0','message'=>'Please add any parameter to edit'],200);
					}
					
				}catch(\Exception $e){
		 
					return Response::json(['success' => '0','message'=>$e->getMessage()],200);
				}
			
			}else{
	 
				return Response::json(['success' => '0','message'=>'User does not exists.'],200);
			}
		}else{
			return Response::json(['success' => '0','message'=>'User is not logged in.'],200);
		}
		
	}#end update profile
	
	#fn to get fat
	public function getFat(){
		try{
	 
			$get_fat_list = DiaryFat::get()->toArray();
 
		}catch(\Exception $e){
 
			return Response::json(['success' => '0','message'=>$e->getMessage()],200);
		}
		if(!empty($get_fat_list)){
			return Response::json(['success' => '1','message'=>'Fat list successfully','fat_list'=>$get_fat_list],200);
		}else{
			return Response::json(['success' => '0','message'=>'No list found.'],200);
		}
			
		
	}#fn ends here
	
	#fn to get snf
	public function getSNF(){
		try{
	 
			$get_snf_list = DiarySnf::get()->toArray();
 
		}catch(\Exception $e){
 
			return Response::json(['success' => '0','message'=>$e->getMessage()],200);
		}
		if(!empty($get_snf_list)){
			return Response::json(['success' => '1','message'=>'SNF list successfully','snf_list'=>$get_snf_list],200);
		}else{
			return Response::json(['success' => '0','message'=>'No list found.'],200);
		}
		
	}#fn ends here
	
	#fn to get snf
	public function getRates(){
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
			
			
			try{
				$check_userid = DiaryUsers::where('id',$user_id->user_id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_userid)){
				
				#get user id from the login token $this->header_api_token
				try{
					
					$data = DiaryRates::with(['getFatValue','getSnfValue'])->where(['user_id'=>$user_id->user_id])->get()->toArray();
					
					if(!empty($data)){
						return Response::json(['success' => '1','message'=>'Success','get_rates'=>$data],200);
					}else{
						return Response::json(['success' => '0','message'=>'Data not found.'],200);
					}
					
					
				}catch(\Exception $e){
		 
					return Response::json(['success' => '0','message'=>$e->getMessage()],200);
				}
			
			}else{
	 
				return Response::json(['success' => '0','message'=>'User does not exists.'],200);
			}
		}else{
			return Response::json(['success' => '0','message'=>'User is not logged in.'],200);
		}
		
	}#fn ends here
	
	#fn to add/update fat rate
	public function addRates(Request $request){
		
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
			
			
			try{
				$check_userid = DiaryUsers::where('id',$user_id->user_id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_userid)){
				
				if(!isset($request['snf']) || $request['snf']==''){
					return Response::json(['success'=>'0','message'=>'Please provide snf in array.'],200);
					exit;
				}else{
					
					$snf = $request['snf'];
					if(is_numeric($snf)){
						#snf_id
						$get_snf_id = DiarySnf::select('id')->where('snf',$snf)->first();
						
						if(!empty($get_snf_id)){
							$snf_id = $get_snf_id->id;
							
							if(isset($request['fat_rate'])){
								$fat_array = $request['fat_rate'];
								if(!empty($fat_array)){
									
									
									foreach($fat_array as $key=>$value){
										
										$check_fat = DiaryFat::select('id')->where('fat',$key)->first();
										if(!empty($check_fat)){
											$fat_id = $check_fat->id;
											
											$check_rate = DiaryRates::select('id')->where(['fat_id'=>$fat_id,'snf_id'=>$snf_id,'user_id'=>$user_id->user_id])->first();
											
											if(!empty($check_rate)){
												#update
												$rate_id = $check_rate->id;
												$update_array = array('rate'=>$value);
												
												try{
													DiaryRates::where(['fat_id'=>$fat_id,'snf_id'=>$snf_id,'user_id'=>$user_id->user_id])->update($update_array);
													
												}catch(\Exception $e){
													return Response::json(['success' => '0','message'=>$e->getMessage()],200);
												}
												
												
											}else{
												$t=time();
												$date = date("Y-m-d",$t);
												#add
												try{
													$id = DiaryRates::insertGetId([
														'user_id'=>$user_id->user_id,
														'fat_id' =>$fat_id,     
														'snf_id' =>$snf_id, 					
														'rate'=>$value,
														'created_at'=>$date,
														'updated_at'=>$date,
													]);
												}catch(\Exception $e){
													return Response::json(['success' => '0','message'=>$e->getMessage()],200);
												}
											}
										}
										
									}
									return Response::json(['success'=>'1','message'=>'Data Added.'],200);
									exit;
									
								}else{#enf_if_not_empty_fat_array
									return Response::json(['success'=>'0','message'=>'Please add any fat rate.'],200);
									exit;
								}

							}else{
								return Response::json(['success'=>'0','message'=>'Please provide a fat_rate array.'],200);
								exit;
							}
						}else{#end_if-not-empty_snf
							return Response::json(['success'=>'0','message'=>'SNF value does not exists.'],200);
							exit;
						}
						
					}else{#numeric_else
						return Response::json(['success'=>'0','message'=>'Please provide numeric snf value.'],200);
						exit;
					}
					
				}#end isset snf
			}else{#check user
	 
				return Response::json(['success' => '0','message'=>'User does not exists.'],200);
			}
		}else{
			return Response::json(['success' => '0','message'=>'User is not logged in'],200);
		}
		
		
	}#add rate ends here
	
	#Add milk Entroes
	public function addMilkEntries(Request $request){
		
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
			
			
			try{
				$check_userid = DiaryUsers::where('id',$user_id->user_id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_userid)){
				
				$customer_id = ((isset($request->customer_id)) ? ($request->customer_id) : '');
				if($customer_id == ""){
					return Response::json(['success'=>'0','message'=>'Please provide a customer id.'],200);
					exit;
				}
				
				$check_customer = DiaryUsercustomers::where(['user_id'=>$user_id->user_id,'id'=>$customer_id])->get();
				if(!empty($check_userid)){
					$weight = ((isset($request->weight)) ? ($request->weight) : '');
					if($weight == ""){
						return Response::json(['success'=>'0','message'=>'Please provide weight.'],200);
						exit;
					}
					
					$fat = ((isset($request->fat)) ? ($request->fat) : '');
					
					if($fat == ""){
						return Response::json(['success'=>'0','message'=>'Please provide fat.'],200);
						exit;
					}
					
					$time = ((isset($request->time)) ? ($request->time) : '');
					if($time == ""){
						return Response::json(['success'=>'0','message'=>'Please provide time.'],200);
						exit;
					}
					
					$type = ((isset($request->type)) ? ($request->type) : '');
					if($type == ""){
						return Response::json(['success'=>'0','message'=>'Please provide type.'],200);
						exit;
					}
					
					$snf = ((isset($request->snf)) ? ($request->snf) : '');
					if($snf == ""){
						$snf = "8";
					}
					
					#inset into the table
	 
					try{
						
						$fat_id = DiaryFat::select('id')->where('fat',$fat)->first();
						if(empty($fat_id)){
							return Response::json(['success'=>'0','message'=>'Fat does not exists.'],200);
							exit;
						}
						
						$fatId = $fat_id->id;
						
						$snf_id = DiarySnf::select('id')->where('snf',$snf)->first();
						if(empty($snf_id)){
							return Response::json(['success'=>'0','message'=>'SNF does not exists.'],200);
							exit;
						}
						
						$snfId = $snf_id->id;
						
						$get_amount = DiaryRates::select('rate')->where(['fat_id'=>$fatId,'snf_id'=>$snfId,'user_id'=>$user_id->user_id])->first();
						//echo "<pre>";print_r($get_amount->rate);die;
						
						if(!empty($get_amount)){
							$t=time();
							$date = date("Y-m-d",$t);
							
							#check if already added data
							$checkData = DiaryMilkEntries::where(['user_id'=>$user_id->user_id,'customer_id' =>$request->customer_id,'time'=>$request->time,'created_at'=>$date])->first();
							
							if(!empty($checkData)){
								$update_data = array('weight' =>$request->weight,'fat'=>$request->fat,'type'=>$request->type,'snf'=>$snf,'total_amount'=>$get_amount->rate);
								
								$update = DiaryMilkEntries::where(['user_id'=>$user_id->user_id,'customer_id' =>$request->customer_id,'time'=>$request->time,'created_at'=>$date])->update($update_data);
								
								if($update == 1){
									return Response::json(['success'=>'1','message'=>'Update milk Entry Successfully.'],200);
									exit;
								}
							}else{
								$id = DiaryMilkEntries::insertGetId([
									'user_id'=>$user_id->user_id,
									'customer_id' =>$request->customer_id,     
									'weight' =>$request->weight, 					
									'fat'=>$request->fat,
									'snf'=>$snf,
									'total_amount'=>$get_amount->rate,
									'time'=>$request->time,
									'type'=>$request->type,
									'created_at'=>$date,
									'updated_at'=>$date,
								]);
								
								#json final response
								return Response::json(['success'=>'1','message'=>'Enter milk Successfully.','id'=>(string)$id],200);
								exit;
							}
						}else{
							return Response::json(['success' => '0','message'=>'Rate not Found'],200);
						}
						
					}catch(\Exception $e){
						return Response::json(['success' => '0','message'=>$e->getMessage()],200);
					}
		 
					#json final response
					return Response::json(['success'=>'1','message'=>'Enter milk Successfully.','id'=>(string)$id],200);
					exit;
				}else{
					return Response::json(['success' => '0','message'=>'This Customer is not added for logged in user.'],200);
				}
				
					
			}else{#check user
	 
				return Response::json(['success' => '0','message'=>'User does not exists.'],200);
			}
		}else{
			return Response::json(['success' => '0','message'=>'User is not logged in'],200);
		}
	}
	
	#Milk sale fn
	public function milkSaleEntries(Request $request){
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
			
			
			try{
				$check_userid = DiaryUsers::where('id',$user_id->user_id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_userid)){
				
				$customer_id = ((isset($request->customer_id)) ? ($request->customer_id) : '');
				if($customer_id == ""){
					return Response::json(['success'=>'0','message'=>'Please provide a customer id.'],200);
					exit;
				}
				
				$check_customer = DiaryUsercustomers::where(['user_id'=>$user_id->user_id,'id'=>$customer_id])->get();
				if(!empty($check_userid)){
					$weight = ((isset($request->weight)) ? ($request->weight) : '');
					if($weight == ""){
						return Response::json(['success'=>'0','message'=>'Please provide weight.'],200);
						exit;
					}
					
					$fat = ((isset($request->fat)) ? ($request->fat) : '');
					
					if($fat == ""){
						return Response::json(['success'=>'0','message'=>'Please provide fat.'],200);
						exit;
					}
					
					$time = ((isset($request->time)) ? ($request->time) : '');
					if($time == ""){
						return Response::json(['success'=>'0','message'=>'Please provide time.'],200);
						exit;
					}
					
					$type = ((isset($request->type)) ? ($request->type) : '');
					if($type == ""){
						return Response::json(['success'=>'0','message'=>'Please provide type.'],200);
						exit;
					}
					
					
					#inset into the table
	 
					try{
						
						$t=time();
						$date = date("Y-m-d",$t);
						
						#check if already added data
						$checkData = DiaryMilkSale::where(['user_id'=>$user_id->user_id,'customer_id' =>$request->customer_id,'time'=>$request->time,'created_at'=>$date])->first();
						
						if(!empty($checkData)){
							$update_data = array('weight' =>$request->weight,'fat'=>$request->fat,'type'=>$request->type);
							
							$update = DiaryMilkSale::where(['user_id'=>$user_id->user_id,'customer_id' =>$request->customer_id,'time'=>$request->time,'created_at'=>$date])->update($update_data);
							
							if($update == 1){
								return Response::json(['success'=>'1','message'=>'Update milk sale Successfully.'],200);
								exit;
							}
						}else{
							$id = DiaryMilkSale::insertGetId([
								'user_id'=>$user_id->user_id,
								'customer_id' =>$request->customer_id,     
								'weight' =>$request->weight, 					
								'fat'=>$request->fat,
								'time'=>$request->time,
								'type'=>$request->type,
								'created_at'=>$date,
								'updated_at'=>$date,
							]);
							
							#json final response
							return Response::json(['success'=>'1','message'=>'Enter milk sale Successfully.','id'=>(string)$id],200);
							exit;
						}
						
					}catch(\Exception $e){
						return Response::json(['success' => '0','message'=>$e->getMessage()],200);
					}
		 
				}else{
					return Response::json(['success' => '0','message'=>'This Customer is not added for logged in user.'],200);
				}
				
					
			}else{#check user
	 
				return Response::json(['success' => '0','message'=>'User does not exists.'],200);
			}
		}else{
			return Response::json(['success' => '0','message'=>'User is not logged in'],200);
		}
	}#milk sale ends here
	
	#milk sale list
	public function milkSaleList(Request $request){
		
		
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
			
			
			try{
				$check_userid = DiaryUsers::where('id',$user_id->user_id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_userid)){
				
				$time = ((isset($request->time)) ? ($request->time) : '');
				if($time == ""){
					return Response::json(['success'=>'0','message'=>'Please provide time(1=morning or 2=evening).'],200);
					exit;
				}
				
				if($time == "1"){
					$time = "1";
				}elseif($time == "2"){
					$time = "2";
				}else{
					return Response::json(['success'=>'0','message'=>'Please provide time(1=morning or 2=evening).'],200);
					exit;
				}
				
				$date = ((isset($request->date)) ? ($request->date) : '');
				if($date == "" || strtotime($date) === false){
					return Response::json(['success'=>'0','message'=>'Please provide a date.'],200);
					exit;
				}
				
				$newDate = date("Y-m-d", strtotime($date));  
				$get_sale = DiaryMilkSale::where(['time'=>$time,'created_at'=>$newDate])->get()->toArray();
				
				if(!empty($get_sale)){
					return Response::json(['success'=>'1','message'=>'Get List Successfully.','milk_sale_list'=>$get_sale],200);
					exit;
				}else{
					return Response::json(['success'=>'0','message'=>'Nothing Found!'],200);
					exit;
				}
					
			}else{#check user
	 
				return Response::json(['success' => '0','message'=>'User does not exists.'],200);
			}
		}else{
			return Response::json(['success' => '0','message'=>'User is not logged in'],200);
		}
		
	}#milk sale list ends
	
	#fn to get milk list of customer
	public function customerMilkList($customer_id){
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
			
			
			try{
				$check_userid = DiaryUsers::where('id',$user_id->user_id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_userid)){
				
				
				if($customer_id == ""){
					return Response::json(['success'=>'0','message'=>'Customer id is missing.'],200);
					exit;
				}
				
				$get_sale = DiaryMilkSale::where('customer_id',$customer_id)->get()->toArray();
				//echo "<pre>";print_r($get_sale);die;
				if(!empty($get_sale)){
					return Response::json(['success'=>'1','message'=>'Get List Successfully.','customer_milk_sale_list'=>$get_sale],200);
					exit;
				}else{
					return Response::json(['success'=>'0','message'=>'Nothing Found!'],200);
					exit;
				}
					
			}else{#check user
	 
				return Response::json(['success' => '0','message'=>'User does not exists.'],200);
			}
		}else{
			return Response::json(['success' => '0','message'=>'User is not logged in'],200);
		}
	}#fn ends
	
	#fn to generta bill of customer
	public function billGenerate(Request $request){
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
			
			
			try{
				$check_userid = DiaryUsers::where('id',$user_id->user_id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_userid)){
				
				#check customer_id
				$customer_id = ((isset($request->customer_id)) ? ($request->customer_id) : '');
				if($customer_id == ""){
					return Response::json(['success'=>'0','message'=>'Please provide Customer id.'],200);
					exit;
				}
				
				$start_date = ((isset($request->start_date)) ? ($request->start_date) : '');
				if($start_date == "" || strtotime($start_date) === false){
					return Response::json(['success'=>'0','message'=>'Please provide a start date.'],200);
					exit;
				}
				
				$startDate = date("Y-m-d", strtotime($start_date));
				
				$end_date = ((isset($request->end_date)) ? ($request->end_date) : '');
				if($end_date == "" || strtotime($end_date) === false){
					return Response::json(['success'=>'0','message'=>'Please provide a end date.'],200);
					exit;
				}
				
				$endDate = date("Y-m-d", strtotime($end_date));
				
				//$get_milk_count = DiaryMilkEntries::select('sum(weight)')->where('customer_id',$customer_id)->whereBetween('created_at', [$startDate,$endDate])->groupBy('customer_id')->get();
				
				$get_milk_count = DB::table('diary_milk_enteries')
									->where('customer_id',$request->customer_id)
									->whereBetween('created_at', [$startDate, $endDate])
									->selectRaw('sum(weight) as total_milk,sum(total_amount) as total_amount')
									->first(); 
				
				$get_allentries = DiaryMilkEntries::where('customer_id',$customer_id)->whereBetween('created_at', [$startDate, $endDate])->get()->toArray();
				
				$get_customer_details = DiaryUsercustomers::where('id',$customer_id)->first();
				

				//	echo "<pre>";print_r($get_customer_details);die;
				if(!empty($get_milk_count)){
					$t=time();
					$date = date("Y-m-d",$t);
					
					#check if already exists
					$check_bill = DiaryCustomerBill::where(['user_id'=>$user_id->user_id,'customer_id'=>$request->customer_id,'start_date'=>$startDate,'end_date'=>$endDate])->first();
					
					if(!empty($check_bill)){
						
						$update_array = array('total_weight' =>$get_milk_count->total_milk,'total_amount'=>$get_milk_count->total_amount);
						
						$update = DiaryCustomerBill::where(['user_id'=>$user_id->user_id,'customer_id'=>$request->customer_id,'start_date'=>$startDate,'end_date'=>$endDate])->update($update_array);
						
						$bill = array('customer_id'=>$request->customer_id,'total_weight' =>$get_milk_count->total_milk,'total_amount'=>$get_milk_count->total_amount,'start_date'=>$startDate,'end_date'=>$endDate);
						
						$pdf = PDF::loadView('bill_pdf', compact('bill','get_allentries','get_customer_details'));
						
						try{
							#send_text_message_to_custoer
							$basic  = new \Nexmo\Client\Credentials\Basic('67045646', '3tUjsUQByMHlg7yg');
							$client = new \Nexmo\Client($basic);
							
							$text = "Hello ".$get_customer_details->name." your bill is generated from ".$startDate." to ".$endDate.". Your total kg of milk is ".$get_milk_count->total_milk."kg and total amount is INR ".$get_milk_count->total_amount." ..";
							$message = $client->message()->send([
								//'to' => $get_customer_details->phone_number,
								'to' => '919815570855',
								'from' => 'Milk Dairy',
								'text' => $text
							]);
						}catch(\Exception $e){
					 
							return Response::json(['success' => '0','message'=>$e->getMessage()],200);
						}
					
				
						//return $pdf->download('bill.pdf');
						//echo Response::json(['success' => '1','message'=>'Bill Generated Successfully','bill'=>$bill],200);
					
						return Response::json(['success' => '1','message'=>'Bill Generated Successfully','bill'=>$bill],200);
					
					}else{
						
						try{
							$id = DiaryCustomerBill::insertGetId([
								'user_id'=>$user_id->user_id,
								'customer_id' =>$request->customer_id,     
								'total_weight' =>$get_milk_count->total_milk, 					
								'total_amount'=>$get_milk_count->total_amount,
								'start_date'=>$startDate,
								'end_date'=>$endDate,
								'created_at'=>$date,
								'updated_at'=>$date,
							]);
				 
						}catch(\Exception $e){
				 
							return Response::json(['success' => '0','message'=>$e->getMessage()],200);
						}
					}
					
					
					$bill = array('customer_id'=>$request->customer_id,'total_weight' =>$get_milk_count->total_milk,'total_amount'=>$get_milk_count->total_amount,'start_date'=>$startDate,'end_date'=>$endDate);
					
					$pdf = PDF::loadView('bill_pdf', compact('bill','get_allentries','get_customer_details'));
					
					try{
						#send_text_message_to_custoer
						$basic  = new \Nexmo\Client\Credentials\Basic('67045646', '3tUjsUQByMHlg7yg');
						$client = new \Nexmo\Client($basic);
						
						$text = "Hello ".$get_customer_details->name." your bill is generated from ".$startDate." to ".$endDate.". Your total kg of milk is ".$get_milk_count->total_milk."kg and total amount is INR ".$get_milk_count->total_amount." ..";
						$message = $client->message()->send([
							//'to' => $get_customer_details->phone_number,
							'to' => '919815570855',
							'from' => 'Milk Dairy',
							'text' => $text
						]);
					}catch(\Exception $e){
				 
						return Response::json(['success' => '0','message'=>$e->getMessage()],200);
					}
					
						
					//return $pdf->download('bill.pdf');
		
					
					return Response::json(['success' => '1','message'=>'Bill Generated Successfully','bill'=>$bill],200);
					
				}else{
					return Response::json(['success' => '0','message'=>'Milk Entry not found for this customer.'],200);
				}
				
							
							
			}else{#check user
	 
				return Response::json(['success' => '0','message'=>'User does not exists.'],200);
			}
		}else{
			return Response::json(['success' => '0','message'=>'User is not logged in'],200);
		}
	}#fn ends
	
	
	#fn for membership
	public function membership(Request $request){
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
			
			
			try{
				$check_userid = DiaryUsers::where('id',$user_id->user_id)
								->first();
	 
			}catch(\Exception $e){
	 
				return Response::json(['success' => '0','message'=>$e->getMessage()],200);
			}
			
			if(!empty($check_userid)){
				
				#check card_number
				$card_number = ((isset($request->card_number)) ? ($request->card_number) : '');
				if($card_number == ""){
					return Response::json(['success'=>'0','message'=>'Please provide Card number.'],200);
					exit;
				}
				
				$expiry_date = ((isset($request->expiry_date)) ? ($request->expiry_date) : '');
				if($expiry_date == ""){
					return Response::json(['success'=>'0','message'=>'Please provide Expiry date.'],200);
					exit;
				}
				
				$cvv = ((isset($request->cvv)) ? ($request->cvv) : '');
				if($cvv == ""){
					return Response::json(['success'=>'0','message'=>'Please provide cvv.'],200);
					exit;
				}
				
				$plan = ((isset($request->plan)) ? ($request->plan) : '');
				if($plan == ""){
					return Response::json(['success'=>'0','message'=>'Please provide plan.'],200);
					exit;
				}
				
				
				
				try{
					
					$t=time();
					$date = date("Y-m-d",$t);
												
 
					$id = DiaryUserMembership::insertGetId([
						'user_id'=>$user_id->user_id,
						'card_number' =>$request->card_number,     
						'expiry_date' =>$request->expiry_date, 					
						'cvv'=>$request->cvv,
						'plan'=>$request->plan,
						'created_at'=>$date,
						'updated_at'=>$date,
					]);
				}catch(\Exception $e){
					return Response::json(['success' => '0','message'=>$e->getMessage()],200);
				}
	 
				#json final response
				return Response::json(['success'=>'1','message'=>'Card details saved.','user_id'=>(string)$id],200);
				exit;
				
							
							
			}else{#check user
	 
				return Response::json(['success' => '0','message'=>'User does not exists.'],200);
			}
		}else{
			return Response::json(['success' => '0','message'=>'User is not logged in'],200);
		}
		
	}#fn ends
	
}
