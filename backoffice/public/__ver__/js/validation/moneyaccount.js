$(function() {
    $('#bank-account').validate({
        rules: {
            type: {
                required: true,
	            number: true,
                min: 1
            },
	        name: {
		        required: true,
                maxlength: 50
	        },
	        card_holder_id: {
		        required: true,
                number: true,
		        min: 1
	        },
            responsible_person_id: {
                required: true,
                number: true,
                min: 1
            },
            legal_entity_id: {
                required: true,
                number: true,
                min: 1
            },
            bank_id: {
                required: true,
                number: true,
                min: 1
            },
	        currency_id: {
		        required: true,
		        number: true,
		        min: 1
	        },
	        country_id: {
		        required: true,
		        number: true,
		        min: 1
	        },
	        account_ending: {
		        required: false,
		        number: true,
                maxlength: 4
	        },
	        description: {
		        required: false,
                maxlength: 45
	        },
	        bank_account_number: {
		        required: false,
                maxlength: 255
	        },
	        address: {
		        required: false,
                maxlength: 255
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
});
