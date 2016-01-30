$(function(){
    jQuery.validator.addMethod("alphanumericspaceonly", function(value, element) {
        return this.optional(element) || /^[A-Za-z0-9\s]+$/.test(value)
    }, "Alphanumeric and space only");

	$('#parking-spot').validate({
        ignore: '',
		rules: {
			unit: {
				required: true,
                maxlength: 45,
                alphanumericspaceonly: true
			},
            price: {
                required: true,
                number: true
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
		errorPlacement: function (error, element) {
			element.closest('.form-group').find('.help-block').html(error.text());
		}
	});
});
