$(function() {
	$.validator.addMethod("onlyNumber", function(value, element) {
		return this.optional(element) || /^[0-9]+$/i.test(value);
	}, "Amount is invalid");

	$('#apartment_details').validate({
        rules: {
            'assigned_office_id': {
                required: true,
                min: function() {
                    return ($('#key_instruction_page_type').val() == '2' ? 1 : 0);
                }
            },
			'lock_id': {
				required: true,
				min:1
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
