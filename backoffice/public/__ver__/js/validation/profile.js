    $(function(){
        $.validator.addMethod("dateEx", function(value, element) {
            return this.optional(element) || /^[0-9]{2}\s(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s[0-9]{4}$/i.test(value);
        }, "Date is invalid.");

        $('#changeDetailsForm').validate(
        {
            rules: {
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
	            birthday: {
	            },
                address: {
                    maxlength: 250
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

        $('#changePasswordForm').validate({
            rules: {
                currentPassword: {
                    required: true,
                    minlength: 6
                },
                password: {
                    required: true,
                    minlength: 6
                },
                passwordVerify: {
                    required: true,
                    minlength: 6,
                    equalTo: "#password"
                }
            },
            messages: {
                password: {
                    minlength: "Minimum 6 characters"
                },
                passwordVerify: {
                    minlength: "Minimum 6 characters"
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
