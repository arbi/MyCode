$('#product-type').validate({
	rules: {
		productName: {
			required: true
		},
		floor: {
			required: true,
			digits: true,
			min: 0
		},
		rackRate: {
			required: true,
			number: true,
			min: 1
		},
		active: {
			required: true,
			digits: true
		},
		generalDescription: {
			maxlength: 500
		}

	},
	messages: {
		productName: "Please enter product type name",
		floor: {
			required: "Required ...",
			digits: "Only digits",
			min: "Min - 0"
		},
		rackRate: {
			required: "Required ...",
			number: "Fill amount ..",
			min: "Min - 0"
		},
		active: {
			required: "Required",
			digits: "Only digits"
		},
		generalDescription: 'Max length - 500'
	},
	highlight: function (element, errorClass, validClass) {
		$(element).closest('.control-group').removeClass('success').addClass('error');
	},
	unhighlight: function (element, errorClass, validClass) {
		$(element).closest('.control-group').removeClass('error').addClass('success');
	},
	success: function (label) {
		$(label).closest('form').find('.valid').removeClass("invalid");
	},
	errorPlacement: function (error, element) {
		element.closest('.control-group').find('.help-block').html(error.text());
	}
});
