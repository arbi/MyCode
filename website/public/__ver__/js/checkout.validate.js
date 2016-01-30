$.validator.setDefaults({
	highlight: function(element) {
		$(element).parent().addClass('has-error');
	},
	unhighlight: function(element) {
		$(element).parent().removeClass('has-error');
	},
	errorElement: 'span',
	errorClass: 'help-block',
	errorPlacement: function(error, element) {},
	onfocusout: function(element) {
		$(element).valid();

		if ($(element).hasClass('expdate')) {
			$('.expdate').valid();
		}
	},
	onkeyup: function(element) {
		$(element).valid();

		if ($(element).hasClass('expdate')) {
			$('.expdate').valid();
		}
	},
	onclick: function(element) {
		$(element).valid();

		if ($(element).hasClass('expdate')) {
			$('.expdate').valid();
		}
	}
});

jQuery.validator.addMethod("isFullName", function(value, element) {
    return this.optional(element) || (value.indexOf(' ')>0 && value.indexOf(' ') != value.length - 1)?true:false;
}, "Please provide last name as well.");

jQuery.validator.addMethod("phone", function(value, element) {
	return this.optional(element) || /^\+?\d{8,}$/.test(value);
}, "Your entered data is not a phone number");

jQuery.validator.addMethod("expdate", function(value, element) {
	var selected = [$('#form-cc-exp-year').val(), numLeftPadZero($('#form-cc-exp-month').val())].join('');

	return (
		numLeftPadZero(parseInt(selected)) >= parseInt($(element).attr('data-today'))
	);
}, "Expiration date is wrong");

jQuery.validator.addMethod("cctype", function(value, element) {
	var cardReader = {
		"visa":         [1, /^4/],
        "diners-club":  [6, /^(300|301|302|303|304|305|309|36|38|39)/],
		"mastercard":   [2, /^(51|52|53|54|55)/],
		"amex":         [3, /^(34|37)/],
		"discover":     [4, /^(6011|622126|622127|622128|622129|62213|62214|62215|62216|62217|62218|62219|6222|6223|6224|6225|6226|6227|6228|62290|62291|622920|622921|622922|622923|622924|622925|644|645|646|647|648|649|65)/],
		"jcb":          [5, /^(1800|2131|3528|3529|353|354|355|356|357|358)/]
	};

	$('.payment-details .credit-cards li').toggleClass('off', true);

	for (var card in cardReader) {
		if (cardReader[card][1].test(value)) {
            var cardType = cardReader[card][0];

            $('#credit_card_type').val(cardType);
			$('.payment-details .credit-cards li.' + card).toggleClass('off', false);
            return true;
		}
	}

	return false;
}, "Your entered data is not a credit card number");

var isBu = $('.page-checkout').attr('data-bu');
$(function() {
	$(".guest-details form").validate({
		rules: {
			"name": {
				required: true,
                isFullName: true
			},
			"email": {
				required: true,
				email: true
			},
			"phone": {
				required: (isBu) ? false : true,
				minlength: 7
			},
			"phone-code": {
				required: (isBu) ? false : true,
				digits: true,
				min: (isBu) ? 0 : 1
			},
			"remarks": {
				required: false
			},
			"aff-id": {
				required: true,
				digits: true,
				min: 1
			},
			"aff-ref": {
				required: false
			}
		}
	});

	$(".payment-details form").validate({
		rules: {
			"address": {
				required: true,
				minlength: 2
			},
			"country": {
				required: true,
				digits: true,
				min: 1
			},
			"city": {
				required: true
			},
			"zip": {
				required: false,
				minlength: 3
			},
			"number": {
				required: true,
				cctype: true,
				creditcard: true,
				minlength: 12,
				maxlength: 16
			},
			"holder": {
				required: true
			},
			"month": {
				required: true,
				digits: true,
				min: 1,
				expdate: true
			},
			"year": {
				required: true,
				digits: true,
				min: 1,
				expdate: true
			},
			"cvc": {
				//required: true,
				minlength: 3,
				maxlength: 4,
                digits: true
			}
		}
	});
});
