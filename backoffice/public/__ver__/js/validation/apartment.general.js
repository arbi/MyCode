$(function(){
    jQuery.validator.addMethod("alphaspaceonly", function(value, element) {
        return this.optional(element) || /^[A-Za-z\s]+$/.test(value)
    }, "Alpha and space only");

    jQuery.validator.addMethod("validBedroom", function(value, element) {
        return (parseInt($('#room_count').val()) >= parseInt(value)) ? true : false;
    }, "Bedroom count should less than or equal to room count.");

	$('#apartment_general').validate({
		rules: {
			'apartment_name': {
				required: true,
                maxlength: 40,
                alphaspaceonly: true
			},
            'building_id': {
                    required: true,
                    number: true,
                    min:1
            },
			'building_section': {
				required: true,
				number: true,
				min:1
			},
			'square_meters': {
				required: false,
				digits: true
			},
			'room_count': {
				required: true,
				digits: true
			},
			'max_capacity': {
				required: true,
				digits: true,
				min: 1
			},
			'bedrooms': {
				required: true,
				digits: true,
                validBedroom: true
			},
			'bathrooms': {
				required: false,
				digits: true
			},
            'general_description': {
                required: true
            },
            'chekin_time': {
                required: true
            },
            'chekout_time': {
                required: true
            }
		},
        messages: {
            'general_description': "Description field is empty."
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
            if ((element.attr('id') == 'general_description') && (element.val() == '')) {
                var msg = {
                    status: "error",
                    msg: error.text()
                }
                notification(msg);
            }
		}
	});

    $.validator.addMethod("notZero", function(value, element) {
		return this.optional(element) || ((value == 0) ? false:true);
	}, "Invalid data");
});
