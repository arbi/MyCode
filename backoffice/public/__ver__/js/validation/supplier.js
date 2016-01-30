$(function() {
    $('#supplier-form').validate({
        rules: {
            name: {
                required: true,
                minlength: 1,
                maxlength: 128
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

function initUserAccountValidation()
{
    $('#supplier-account-form').validate({
        ignore: [],
        rules: {
            name: {
                required: true
            },
            type: {
                required: true,
                min: 1
            },
            fullLegalName: {
                required: true
            },
            mailingAddress: {
                required: true
            },
            countryId: {
                required: true,
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
}