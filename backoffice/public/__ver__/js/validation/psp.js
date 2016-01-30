$(function() {
    $('#psp').validate({
        rules: {
            name: {
                required: true
            },
            short_name: {
                    required: true
            },
            money_account_id: {
                    required: true,
                    number: true,
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
        errorPlacement: function(error, element) {
        }
    });
});
