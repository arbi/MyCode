$('#product-rate').validate({
	rules: {
		rateName: {
			required: true
		},
		active: {
			required: true,
			digits: true
		},
		defaultAvailability: {
			required: true,
			digits: true,
			min: 0
		},
		capacity: {
			digits: true,
			min: 0
		},
		weekNightPrice: {
			number: true,
			min: 0
		},
		weekendPrice: {
			number: true,
			min: 0
		},
		minimumStay: {
			digits: true,
			min: 0
		},
		maxStay: {
			digits: true,
			min: 0,
			max: 366
		},
		releaseFromToday: {
			digits: true,
			min: 0
		},
		releaseTo: {
			digits: true,
			min: 0
		}

	},
	messages: {
		rateName: "Please enter rate name",
		active: {
			required: "Required ...",
			digits: "Digits only"
		},
		defaultAvailability: "Number required",
		rackRate: "Must be number and more than 0"
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
