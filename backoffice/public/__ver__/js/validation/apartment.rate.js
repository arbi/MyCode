$(function(){
	$.validator.addMethod("amount", function(value, element) {
		return this.optional(element) || /^[0-9]+(\.[0-9]{1,2})?$/i.test(value);
	}, "Amount is invalid.");

	$.validator.addMethod("min_num", function(value, element) {
		var result = 1;
		if ($("#max_stay").val() >= 1) {
			result = parseInt($("#min_stay").val()) <= parseInt($("#max_stay").val());
		}
		return (result);
		}, "Number is invalid."
	);

	$.validator.addMethod("max_num", function(value, element) {
		var result = 1;
		if ($("#min_stay").val() >= 1) {
			result = parseInt($("#min_stay").val()) <= parseInt($("#max_stay").val());
		}
		return (result);
		}, "Number is invalid."
	);

	$.validator.addMethod("rel_start", function(value, element) {
		var result = 1;
		if ($("#release_window_end").val() >= 1) {
			result = parseInt($("#release_window_start").val()) <= parseInt($("#release_window_end").val());
		}
		return (result);
		}, "Number is invalid."
	);

	$.validator.addMethod("rel_end", function(value, element) {
		var result = 1;
		if ($("#release_window_start").val() >= 1) {
			result = parseInt($("#release_window_start").val()) <= parseInt($("#release_window_end").val());
		}
		return (result);
		}, "Number is invalid."
	);


	var apartmentMaxPAX = $('input[name=capacity]').attr('data-max-capacity');

    var weekPrice = {
        required: true,
        amount: true,
        min: 0
    };

    if ($('#is_parent').val() == 0) {
        weekPrice = {
            required: true,
            amount: true,
            min: 0,
            max: 500
        }
    }

	$('#apartment_rate').validate({
		rules: {
			'rate_name': {
				required: true,
				remote: {
					url: GLOBAL_CHECK_RATE_NAME,
					type: "POST",
					data: {
						id: function() {
							return $("#rate_id").val();
						},
						name: function() {
							return $("#rate_name").val();
						}
					}
				}
			},
            'type': {
                required: true,
                digits: true,
                min: 1
            },
			'default_availability': {
				required: true,
				digits: true,
				min: 0
			},
			'capacity': {
				required: true,
				digits: true,
				min: 0,
				max: parseInt(apartmentMaxPAX)
			},
			'weekday_price': weekPrice,
			'weekend_price': weekPrice,
			'min_stay': {
				required: true,
				digits: true,
				min: 1,
				min_num: true
			},
			'max_stay': {
				required: true,
				digits: true,
				max_num: true,
				min: 1,
				max: 366
			},
			'release_window_start': {
				required: true,
				digits: true,
				rel_start: true
			},
			'release_window_end': {
				required: true,
				digits: true,
				rel_end: true,
				max: 365
			},
			'penalty_percent': {
				required:{
					depends: function(element) {
						return (
							$('input[name="refundable"]:checked').val() == 2 && $('input[name="penalty_type"]:checked').val() == 1
						);
					}
				},
				digits: true,
				min: 0,
				max: 100
			},
			'penalty_fixed_amount': {
				required:{
					depends: function(element) {
						return (
							$('input[name="refundable"]:checked').val() == 2 && $('input[name="penalty_type"]:checked').val() == 2
						);
					}
				},
				amount: true,
				min: 0
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
