$(function(){
	$('#login-form').validate({
		rules: {
			'user_email': {
				required: true,
                email: true
			},
			'user_password': {
				required: true
			}
		},
		highlight: function (element, errorClass, validClass) {
			$(element).closest('.control-group').removeClass('success').addClass('error');
		},
		unhighlight: function (element, errorClass, validClass) {
			$(element).closest('.control-group').removeClass('error').addClass('success');
		},
		success: function (label) {
			$(label).closest('form').find('.valid').removeClass("invalid");
		},
		errorPlacement: function (error, element) {
			element.closest('.control-group').find('.help-block').html(error.text());
		}
	});
    
    $('#login-form').submit(function (){
        return $('#login-form').valid();
    });
    
    $('#user_email').focus();
});
