$(function() {
	$('#team_manage_table').validate({
        ignore: [],
		rules: {
			name: {
				required: true,
				remote: {
					url: GLOBAL_CHECK_TEAM_NAME,
					type: "post",
					data: {
						id: function() {
							return $("#team_id").val();
						}
					}
				}
			},

			description: {
				required: true
			},
            'managers[]': {
                required: true
			},
			'director': {
				required: true,
                digits: true,
                min: 1
            },
			'timezone': {
				required: false
			}
		},
		messages: {
			name: {
				remote: "Team Name is in use"
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
