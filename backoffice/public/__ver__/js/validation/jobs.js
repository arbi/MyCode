$(function() {
    $('#job_manage_table').validate({
        rules: {
            'title': {
                required: true
            },
            'sub_title': {
                required: false
            },
            'description': {
                required: true
            },

            'requirement': {
                required: false
            },

            'country_id': {
                required: true,
                number: true,
                min: 1
            },

            'province_id': {
                required: true,
                number: true,
                min: 1
            },

            'city_id': {
                required: true,
                number: true,
                min: 1
            },
            'hiring_manager_id': {
                required: true,
                number: true,
                min: 1
            },

            'department_id': {
                required: true,
                number: true,
                min: 1
            },

            'start_date': {
                required: true
            },

            'meta_description': {
                required: true,
                maxlength: 70
            }

        },
        messages: {
            'description': "Description field is empty."
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
        errorPlacement: function(error, element) {
            if ((element.attr('id') == 'description') && (element.val() == '')) {
                var msg = {
                    status: "error",
                    msg: "Description field is empty."
                }
                notification(msg);
            }
        }
    });
});
