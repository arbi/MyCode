$(function() {
	$('#form_edit_apartment_group').validate({
		rules: {
			name: {
				required: true,
			},
			timezone: {
				required: true
			},
			country_id: {
				required: true,
				number: true
			},
			group_manager_id: {
				required: false,
				number: true
			}
		},
		messages: {
			name: {
				remote: "Group Name is in use"
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
