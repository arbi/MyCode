$(function(){
    $('#apartment_location').validate({
        rules: {
            'longitude': {
                    required: true,
                    number: true
            },
            'latitude': {
                    required: true,
                    number: true
            },
            'country_id': {
                required: true,
                number: true,
                min:1
            },
            'province_id': {
                    required: true,
                    number: true,
                    min:1
            },
            'city_id': {
                    required: true,
                    number: true,
                    min:1
            },
            'building': {
                    required: true,
                    number: true,
                    min:1
            },
            'address': {
                    required: true
            },
            'postal_code': {
                    required: true
            },
            'building_section': {
                required: true,
                number: true,
                min:1
            },
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

    $.validator.addMethod("notZero", function(value, element) {
        return this.optional(element) || value;
    }, "Invalid data");
});
