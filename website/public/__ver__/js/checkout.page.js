var checkout = {
	step: 1,
	attempt: 0,
	storage: {
		personal: {
			first: null,
			last: null,
			email: null,
			address: null,
			city: null,
			zip: null,
			country_id: null,
			country: null,
			phone: null
		},
		cc: {
			type: null,
			number: null,
			holder: null,
			month: null,
			year: null,
			cvc: null
		}
	},

	init: function() {
		// Setup
		this.setup();

		// Implement Logic
		this.implementLogic();
	},

	setup: function() {
		var self = this;

		if (this.step == 1) {
			// Activate guest details block, disable payment part
			$('.guest-details .icon').addClass('text-primary');
			$('.payment-details .icon').removeClass('text-primary');

			$('.payment-details .form-control, .payment-details .btn-block').attr('disabled', 'disabled');
			$('.guest-details .form-control, .guest-details .btn-block').removeAttr('disabled');

			$('#make-reservation').hide();

			$('#change-guest-details').fadeOut('fast', function() {
				$('#submit-guest-details').fadeIn('fast');
			});
		} else {
            $('.payment-details').show();
            $('.payment-details .credit-cards li').removeClass('off');
			// Activate guest details block, disable payment part
			$('.payment-details .icon').addClass('text-primary');

			$('.guest-details .form-control').attr('disabled', 'disabled');
			$('.payment-details .form-control, .payment-details .btn-block').removeAttr('disabled');

			$('#make-reservation').show();

			$('#submit-guest-details').fadeOut('fast', function() {
				$('#change-guest-details').fadeIn('fast');
			});

			var addr = $('#address');

			if (addr.val() == '') {
				setTimeout(function() {
					addr[0].focus();
				}, 0);
			}
		}

		$('select[name=country]').on('change', function() {
			var postalCodeStatus = null;

			if (zipJson.hasOwnProperty($(this).val())) {
				postalCodeStatus = zipJson[$(this).val()];
			} else {
				postalCodeStatus = 3;
			}

			self.processPostalCode(postalCodeStatus);
		}).trigger('change');
	},

	processPostalCode: function(status) {
		var zip = $('input[name=zip]');

		if (status == 1) {
			zip.hide();
			zip.rules('remove');
		} else if (status == 2) {
			zip.show();
			zip.rules('remove');
			zip.rules('add', {
				required: false,
				minlength: 3
			});
		} else if (status == 3) {
			zip.show();
			zip.rules('remove');
			zip.rules('add', {
				required: true,
				minlength: 3
			});
		}
	},

	implementLogic: function() {
		var self = this;

		$('#submit-guest-details').click(function(e) {
			e.preventDefault();

			if (self.isValid('guest-details')) {
				self.step++;

                var name = $('.guest-details input[name=name]').val().split(' ');
                var firstName = name.shift();
                var lastName  = name.join(' ');


				$('.payment-details input[name=first-name]').val(firstName);

				$('.payment-details input[name=last-name]').val(lastName);

				$('.payment-details input[name=email]').val(
					$('.guest-details input[name=email]').val()
				);
                var phone = $('.guest-details input[name=phone]').val();
                var phoneCode = $('.guest-details select[name=phone-code]').val();
                var basePhone = '';
                if(phone) {
                   basePhone = (parseInt(phoneCode) > 0 ? phoneCode : '') +phone;
                }
                $('.payment-details input[name=phone]').val(basePhone);

				$('.payment-details input[name=remarks]').val(
					$('.guest-details textarea[name=remarks]').val()
				);

				$('.payment-details input[name=aff-ref]').val(
					$('.guest-details input[name=aff-ref]').val()
				);

                $('.payment-details .apartel-id').val(
                    $('.guest-details .apartel-id').val()
                );

				if($('#notSendMail').is(':checked')) {
                    $('#not_send_mail').val(1);
                } else {
                    $('#not_send_mail').val(0);
                }

                var holderName = firstName + ' ' + lastName;

                if ($('#aff-id').val() != 1144) { // Expedia (Virtual Card)
                    $('#form-cc-holder-name').val(
                        holderName.toUpperCase()
                    );
                }

				self.setup();
			}
		});

		$('#change-guest-details').click(function() {
			self.step--;
			self.setup();
		});

		$('#make-reservation').click(function(e) {
			e.preventDefault();

			if (self.isValid('payment-details')) {
                $('#booking-form').submit();
			}
		});
	},

	isValid: function(name) {
		return $('.' + name + ' form').valid();
	}
};

if ($("#aff-id").length > 0) {
    $("#aff-id").change(function() {
        //1050 is partner id of "Booking", 1054 is partner id of "Expedia", 1118 is partner id of "AGoda"
		if (   $(this).val() == 1050 || $(this).val() == 1054 || $(this).val() == 1118
            || $(this).val() == 1146 || $(this).val() == 1140 || $(this).val() == 1144
        ) {
            $('#apartel').closest('div').show();
        } else {
            $('#apartel').closest('div').hide();
            //$('#apartel').val(0);
        }
	});

    $("#aff-id").trigger('change');

    $('#noCreditCard').click(function() {
        if ($(this).is(':checked')) {
          $('#creditCardPart').hide();
        } else {
           $('#creditCardPart').show();
        }
    });
}

$(function() {
    $('.payment-details .apartel-id').html(
        $('.guest-details .apartel-id').html()
    );

	$('.phone-code').on('change', function() {
		var codeVal = $('.phone-code option:selected').attr('data-code');
		$('.phone-code-prefix').html('').text('');
		if (codeVal != '0') {
			codeVal = '+' + codeVal;
		} else {
			codeVal = '';
		}
        if(codeVal != ''){
			$('.phone-code-prefix').text(codeVal);
		}
		else{
			$('.phone-code-prefix').html('<i class="glyphicon glyphicon-phone-alt"></i>');
		}

	}).trigger('change');
});
