$(function() {
    $('#apartel-type').validate({
        ignore: [],
        rules: {
            type_name: {
                required: true
            },
            'apartment_list[]': {
                required: true
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

$('#save_button').click(function() {
    var btn = $('#save_button');
    btn.button('loading');
    if($('#apartel-type').valid()) {
        $('#apartel-type').submit();
    } else {
        btn.button('reset');
    }
});
