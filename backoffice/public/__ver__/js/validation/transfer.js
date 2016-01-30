$(function() {
	$.validator.addMethod("dateRange", function(value, element) {
		return this.optional(element) || /^\d{4}-\d{2}-\d{2}\s-\s\d{4}-\d{2}-\d{2}$/i.test(value);
	}, "Date range is invalid.");

	$.validator.addMethod("dateEx", function(value, element) {
		return this.optional(element) || /^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s[0-9]{1,2},\s[0-9]{4}$/i.test(value);
	}, "Date is invalid.");

	$.validator.addMethod("amount", function(value, element) {
		return this.optional(element) || /^-?[0-9]+(\.[0-9]{1,2})?$/i.test(value);
	}, "Amount is invalid.");

	$.validator.setDefaults({
		ignore: ":hidden:not(*)"
	});
	$('.add-transfer-form').validate({
		rules: {
			supplier_from: {
				required: function() {
					return ($('.tab-receive').hasClass('active'));
				},
				number: true,
				min: 1
			},
			money_account_from: {
				required: function() {
					return ($('.tab-transfer').hasClass('active') || $('.tab-pay').hasClass('active') || $('.tab-partner-payment').hasClass('active'));
				},
				number: true,
				min: 1
			},
			money_account_to: {
				required: function() {
					return $('.tab-money-account').hasClass('active');
				},
				number: true,
				min: 1
			},
			supplier_to: {
				required: function() {
					return $('.tab-account').hasClass('active');
				},
				number: true,
				min: 1
			},
			amount_from: {
				required: function() {
					return ($('.tab-transfer').hasClass('active') || $('.tab-partner-payment').hasClass('active'));
				},
				amount: true
			},
			amount_to: {
				required: function() {
					return $('.tab-money-account').hasClass('active');
				},
				amount: true
			},
			date_from: {
				required: function() {
					return ($('.tab-transfer').hasClass('active') || $('.tab-pay').hasClass('active') || $('.tab-partner-payment').hasClass('active'));
				},
				dateEx: true
			},
			date_to: {
				required: function() {
					return ($('.tab-money-account').hasClass('active'));
				},
				dateEx: true
			},
			'expense_id_list[]': {
				required: function (element) {
					return (!$(element).closest('.template').hasClass('hide') && $('.tab-pay').hasClass('active'));
				},
				number: true,
				min: 1
			},
			'expense_amount_list[]': {
				required: function (element) {
					return (!$(element).closest('.template').hasClass('hide') && $('.tab-pay').hasClass('active'));
				},
				amount: true
			},
			collection_period: {
				required: function () {
					return $('.tab-psp').hasClass('active');
				},
				dateRange: true
			},
			psp: {
				required: function () {
					return $('.tab-psp').hasClass('active');
				},
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
