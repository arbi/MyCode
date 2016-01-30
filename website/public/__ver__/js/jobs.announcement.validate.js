$.validator.setDefaults({
	highlight: function(element) {
		$(element).parent().addClass('has-error');
	},
	unhighlight: function(element) {
		$(element).parent().removeClass('has-error');
	},
	errorElement: 'span',
	errorClass: 'help-block',
	errorPlacement: function(error, element) {}
});

jQuery.validator.addMethod("phone", function(value, element) {
	return this.optional(element) || /^\+?\d{8,}$/.test(value);
}, "Your entered data is not a phone number");

$(function() {
	$("#job-form-dialog form").validate({
		rules: {
			"firstname": {
				required: true,
				maxlength: 25
			},
			"lastname": {
				required: true,
				maxlength: 25
			},
			"email": {
				required: true,
				email: true
			},
			"phone": {
				required: true,
				minlength: 7
			},
			"referred_by" : {
				required: false,
				maxlength: 50
			},
			"skype" : {
				required: false,
				maxlength: 25
			},
			"cv": {
				required: Boolean(CV_REQUIRED)
			}
		},
        highlight: function (element, errorClass, validClass) {
            $(element).parent().removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).parent().removeClass('has-error').addClass('has-success');
        },
        success: function (label) {
            $(label).parent().find('.valid').removeClass("invalid");
        },
        errorPlacement: function (error, element) {
            element.parent().find('.help-block').html(error.text());
        }
	});
});
