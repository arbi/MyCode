$(function() {
	$('#office_manage_table').validate({
		rules: {
			name: {
				required: true,
				remote: {
					url: GLOBAL_CHECK_OFFICE_NAME,
					type: "post",
					data: {
						id: function() {
							return $("#office_id").val();
						}
					}
				}
			},

			description: {
				required: true,
			},

			address: {
				required: true,
			},

			country_id: {
				required: true,
                number: true,
                min: 1
			},

			province_id: {
				required: true,
                number: true,
                min: 1
			},

			city_id: {
				required: true,
                number: true,
                min: 1
			},
			office_manager_id: {
				required: false,
                number: true
			},
			it_manager_id: {
				required: false,
                number: true,
			},
			finance_manager_id: {
				required: false,
                number: true
			},

            staff: {
				required: false
			},

            section: {
				required: true,
			},

		},
		messages: {
			name: {
				remote: "Office Name is in use"
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
});
