$(function(){
    jQuery.validator.addMethod("alphanumericandspaceonly", function(value, element) {
        return this.optional(element) || /^[A-Za-z0-9\s]+$/.test(value)
    }, "Alphanumeric values only");

	$('#parking-general').validate({
        ignore: '',
		rules: {
			name: {
				required: true,
                maxlength: 45,
                alphanumericandspaceonly: true
			},
            lock_id: {
                min: 1
            },
            country_id: {
                required: true,
                min: 1
            },
            province_id: {
                required: true,
                min: 1
            },
            city_id: {
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
		errorPlacement: function (error, element) {
			element.closest('.form-group').find('.help-block').html(error.text());
		}
	});
});
