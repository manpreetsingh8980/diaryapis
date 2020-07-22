jQuery( document ).ready( function() {
	
	$.ajaxSetup({
		headers: {
		  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});
	
	/******submit change password******/
	$('#passwordReset').submit(function(event){
			event.preventDefault();
			
			var new_password = $(this).find('#new_password').val();
			if(new_password == "") {
				$(this).find(".new_password_error").css('display','inline-block');
				$(this).find(".new_password_error").text('');
				$(this).find(".new_password_error").text("Please enter new password");
				return false;
			}else if((new_password.length < 6) || (new_password.length > 12)){
				$(this).find(".new_password_error").css('display','inline-block');
				$(this).find(".new_password_error").text('');
				$(this).find(".new_password_error").text("Please enter password between 6 to 12 digits");
				return false;
			}else{
				$(this).find(".new_password_error").css('display','none');
				$(this).find(".new_password_error").text('');
			}
			
			var confirm_password = $(this).find('#confirm_password').val();
			if(confirm_password == "") {
				$(this).find(".confirm_password_error").css('display','inline-block');
				$(this).find(".confirm_password_error").text('');
				$(this).find(".confirm_password_error").text("Please enter confirm password");
				return false;
			}else if((confirm_password.length < 6) || (confirm_password.length > 12)){
				$(this).find(".confirm_password_error").css('display','inline-block');
				$(this).find(".confirm_password_error").text('');
				$(this).find(".confirm_password_error").text("Please enter password between 6 to 12 digits");
				return false;
			}else{
				$(this).find(".confirm_password_error").css('display','none');
				$(this).find(".confirm_password_error").text('');
			}
			
			if(new_password != confirm_password){
				$('#passwordReset').find('.main_error').css('display','inline-block');
				$('#passwordReset').find('.main_error').text("Passwords do not match.");
				return false;
			}else{
				var resetPwdurl = $('#resetPwd').val();
				var user_email = $('#passwordReset').find('#user_email').val();
				
				$.ajax({
					url: resetPwdurl,
					type: "POST",
					data: { useremail : user_email,newpassword : new_password },
					dataType:'json',
					success: function( response ) {
						
						if(response['success'] == true){
							$('#passwordReset').find('.main_error').css('display','none');
							$('#passwordReset').find('.main_error').text('');
							$('#passwordReset').find('.success_msg').css('display','inline-block');
							$('#passwordReset').find('.success_msg').text(response.message);
							$('#passwordReset').find('.new_password').val('');
							$('#passwordReset').find('.confirm_password').val('');
						}
						
						if(response['success'] == false){
							$('#passwordReset').find('.main_error').css('display','inline-block');
							$('#passwordReset').find('.main_error').text(response.message);
						}
						
						if(response['success'] == 0){
							$('#passwordReset').find('.main_error').css('display','inline-block');
							alert(response.message);
						}
					}
				});/***ajax ends***/
			}
			
			
	});
	
	
});



function errorCheck(obj){
	
	$('#loginForm').find('.main_error').css('display','none');
	$('#loginForm').find('.main_error').text('');
	
	$('#EmailForm').find('.main_error').css('display','none');
	$('#EmailForm').find('.main_error').text('');
	
	$('#passwordReset').find('.main_error').css('display','none');
	$('#passwordReset').find('.main_error').text('');
	
	
	var errorClass = $(obj).attr('data-error_class');
	$("."+errorClass).css('display','none');
	$("."+errorClass).text('');
	
	$("."+errorClass).css('display','none');
	$("."+errorClass).text('');
	
}

function validateEmail($email) {
  var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
  return emailReg.test( $email );
}