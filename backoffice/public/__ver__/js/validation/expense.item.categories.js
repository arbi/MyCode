$(function() {
	var form = $('#category-form');

	form.validate({
		rules: {
			name: {
				required: true,
				remote: {
					url: GLOBAL_CHECK_CATEGORY_NAME,
					type: "post",
					data: {
						id: function() {
							return $("#category_id").val();
						}
					}
				}
			}
		},
		messages: {
			name: {
				remote: "Category title is already used"
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
		errorPlacement: function(error, element) {}
	});

	// Force validate Name element to allow submit form without any changes
	form.validate().element('#name');
});
