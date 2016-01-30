$(function(){
    $('#partner').validate({
		rules: {
			'partner_name': {
				required: true,
                minlength: 1
			},
			'contact_name': {
				required: true,
                minlength: 1
			},
			'email': {
				required: true,
                email: true
			},
			'mobile': {
				minlength: 10
			},
            'phone': {
				minlength: 10
			},
            'password': {
                required: true,
                minlength: 6
            },
            'discount_num': {
                required: false,
                number: true,
                max: 15
            }
		},
		messages: {
            required: "Required input"
		},
		highlight: function (element, errorClass, validClass) {
			$(element).closest('.form-group').removeClass('has-success').addClass('has-error');
		},
		unhighlight: function (element, errorClass, validClass) {
			$(element).closest('.form-group').removeClass('has-error').addClass('has-success');
		},
		success: function (label) {
			$(label).closest('form').find('.valid').removeClass("invalid");
		},
		errorPlacement: function (error, element) {
			element.closest('.form-group').find('.help-block').html(error.text());
		}
	});
});


function initUserAccountValidation()
{
	$('#partner-account-form').validate({
		ignore: [],
		rules: {
			name: {
				required: true
			},
			type: {
				required: true,
				min: 1
			},
			fullLegalName: {
				required: true
			},
			mailingAddress: {
				required: true
			},
			countryId: {
				required: true,
				min: 1
			}
		},
		highlight: function (element, errorClass, validClass) {
			$(element).closest('.form-group').removeClass('has-success').addClass('has-error');
		},
		unhighlight: function (element, errorClass, validClass) {
			$(element).closest('.form-group').removeClass('has-error').addClass('has-success');
		},
		success: function (label) {
			$(label).closest('form').find('.valid').removeClass("invalid");
		},
		errorPlacement: function(error, element) {
		}
	});
}