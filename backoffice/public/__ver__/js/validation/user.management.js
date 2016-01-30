$(function() {
    $.validator.addMethod("dateEx", function(value, element) {
        return this.optional(element) || /^[0-9]{2}\s(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s[0-9]{4}$/i.test(value);
    }, "Date is invalid.");

    $.validator.addMethod("amount", function(value, element) {
        return this.optional(element) || /^[0-9]{2}\s(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s[0-9]{4}$/i.test(value);
    }, "Date is invalid.");

    if (parseInt(GLOBAL_EDIT) > 0) {
        var pass_object = {
            required: false,
            minlength: 6
        };
    } else {
        var pass_object = {
            required: true,
            minlength: 6
        };
    }
    $.validator.addMethod("vacation", function(value, element) {
        return this.optional(element) || /^[0-9-]+(\.[0-9]{1,12})?$/i.test(value);
    }, "Amount is invalid");

    $('#user-management').validate({
        ignore: [],
        rules: {
            password: pass_object,
            email: {
                required: true,
                email: true,
                remote: {
                    url: GLOBAL_BASE_PATH + 'user/ajaxcheckusername',
                    type: "post",
                    data: {
//                                    username: function() {
//                                      return $( "#email" ).val();
//                                    },
                        id: function() {
                            return $("#user_hidden_id").val();
                        }
                    }
                }
            },
            internal_number: {
                required: false,
                number: true
            },
            alt_email: {
                required: false,
                email: true
            },
            firstname: {
                required: true
            },
            lastname: {
                required: true
            },
            manager: {
                required: true,
                digits: true,
                min: 1
            },
            period_length: {
                required: true,
                digits: true,
                min: 0,
                max: 30
            },
            country: {
                maxlength: 500,
                number: true,
                min: -1
            },
            city: {
                required: true,
                maxlength: 500,
                number: true,
                min: 1
            },
            timezone: {
            },
            startdate: {
                dateEx: true
            },
            vacationdays: {
                vacation: true
            },
            vacation_days_per_year: {
                vacation: true
            },
            personalphone: {
                digits: true,
                minlength: 10
            },
            businessphone: {
                digits: true,
                minlength: 10
            },
            emergencyphone: {
                digits: true,
                minlength: 10
            },
            housephone: {
                digits: true,
                minlength: 10
            },
	        asana_id: {
		        digits: true
	        },
            shift: {
                number: true,
                min: -1
            },
            groups: {
                digits: true,
                min: 0
            },
            accounts: {
                digits: true,
                min: 0
            },
            department: {
                required: true,
                digits: true,
                min: 0
            },
            reporting_office_id: {
                required: true,
                digits: true,
                min: 0
            },
            position: {
                required: true
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
            generalDescription: 'Max length - 500',
            email: {
                remote: "Email is in use"
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
        },
        success: function(label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function(error, element) {
        }
    });

    var shift = $('#shift'),
            workingHours = $('#workingHours input[type="checkbox"]'),
            timeTrigger = $('#workingHours select[data-rel="time-trigger"]'),
            weekType = $('.week-type'),
            timeSibling = timeType = timeSpanShift = null;

    shift.change(function() {
        shift.find("option:selected").each(function() {
            if ($(this).val() == 2) {
                $('.working-hours').text('Available Hours');
            } else {
                $('.working-hours').text('Working Hours');
            }
        });
    });
    weekType.change(function() {

        var spanShift = $(this).parent().find('span[data-rel="time-shift"]');
        if ($(this).val() == 1) {
            spanShift.hide();
        } else {
            spanShift.show();
        }
    });

    timeTrigger.change(function() {
        var self = $(this);
        timeSibling = $(this).parent().find('span[data-rel="time-sibling"]');
        timeType = $(this).parent().find('span[data-rel="time-type"]');
        timeSpanShift = $(this).parent().find('span[data-rel="time-shift"]');
        timeTypeSelect = $(this).parent().find('.week-type');
        timeSibling.hide();
        timeType.hide();
        $(this).find("option:selected").each(function() {
            var selfTrigger = $(this);
            var val = $(this).val();
            if (val != 0) {
                var parent_d = val.split(":");
                var parent_date = new Date();
                parent_date.setHours(parent_d[0]);
                parent_date.setMinutes(parent_d[1]);

                self.closest('.form-group').find('label').removeClass('text-muted');
                timeSibling.show();
                timeType.show();
                if (timeTypeSelect.val() == 1) {
                    timeSpanShift.hide();
                } else {
                    timeSpanShift.show();
                }
            } else {
                self.closest('.form-group').find('label').addClass('text-muted');
            }
        });
    });

    shift.trigger('change');
    timeTrigger.trigger('change');
});
