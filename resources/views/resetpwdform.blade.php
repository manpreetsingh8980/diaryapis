@extends('layout.main')

@section('title', 'Reset Password')

@section('content')
	@if($errors->any())
		{{ implode('', $errors->all(':message')) }}
	@else
	
		<form id="passwordReset" >
	
		<span class="main_error" style="color:red; display:none"></span></br>
		<input type="hidden" id="resetPwd" value="{{url('/resetpassword')}}"/>
		<input type="hidden" id="user_email" value="{{ $email }}"/>
			@csrf

			New Password : <input type="password" id="new_password" name="new_password" value="" data-error_class="new_password_error" onkeyup="errorCheck(this)"/> <span style="color:red; display:none;" class="new_password_error"></span></br>
			Confirm Password : <input type="password" id="confirm_password" name="confirm_password" value="" data-error_class="confirm_password_error" onkeyup="errorCheck(this)"/> <span style="color:red; display:none;" class="confirm_password_error"></span></br>
			<button id="submit" name="submit"/>Submit</button>
			<span class="success_msg" style="color:green; display:none"></span></br>
		</form>
	@endif
	
	
@stop


