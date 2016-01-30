$(function(){
    $('#interview-edit-modal form').validate({
        rules: {
            participants: {
                required: true
            },
            from: {
                required: true
            },
            to: {
                required: false
            },
            place: {
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
        errorPlacement: function (error, element) {
        }
    });

    $("#applicant_manage_table").validate({
        rules: {
            comment: {
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
        errorPlacement: function (error, element) {
        }
    });
});
