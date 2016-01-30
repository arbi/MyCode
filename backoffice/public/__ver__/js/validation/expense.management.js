$(function(){
	$.validator.addMethod("dateEx", function(value, element) {
		return this.optional(element) || /^[0-9]{2}\s(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s[0-9]{4}$/i.test(value);
	}, "Date is invalid.");

	$.validator.addMethod("amount", function(value, element) {
		return this.optional(element) || /^[0-9]+(\.[0-9]{1,2})?$/i.test(value);
	}, "Amount is invalid.");

	$.validator.setDefaults({ ignore: ":hidden:not(select)" });

	$('#add-expense').validate({
		rules: {
			'transaction_date': {
				required: true,
				dateEx: true
			},
			'amount': {
				required: true,
				min: 0.01,
				amount: true
			},
			'currency': {
				required: true,
				min: 1
			},
			'entered_for': {
				required: true,
				min: 1
			},
			'purpose': {
				required: true
			},
			'expected_date': {
				dateEx: true,
				required: function() {
					return $('#deposit').is(':checked');
				}
			},
			'expected_amount': {
				amount: true,
				min: 0.01,
				required: function() {
					return $('#deposit').is(':checked');
				}
			},
			'actual_amount': {
				amount: true,
				min: 0.01
			},
            'bank_account': {
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

	$('#add-expense').submit(costCenterCheck);
	$('#usage_cost_center').change(costCenterCheck);
});


function costCenterCheck(e) {
	var control_group = $('#usage_cost_center').closest('.form-group');

	if (!$('#global_cost').is(':checked')) {
		if (!$('#usage_cost_center option:selected').length) {
			control_group.removeClass('has-success');
			control_group.addClass('has-error');

			e.preventDefault();
		} else {
			control_group.removeClass('has-error');
			control_group.addClass('has-success');
		}
	}


	if ($('#fsGeneral\\[attachment').closest('.form-group').hasClass('has-error')) {
		e.preventDefault();
	}
}
