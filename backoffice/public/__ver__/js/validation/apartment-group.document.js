$(function(){
    jQuery.validator.addMethod("isStartDateLessThenDueDate", function(value, element) {
                var startDate = $('#valid_from').val();
                var endDate = $('#valid_to').val();
                var DateToValue = new Date(startDate);
                var DateFromValue = new Date(endDate);
                 if(startDate == '' && endDate == '') return true;
                    if (Date.parse(DateToValue) < Date.parse(DateFromValue)) {
                        return true;
                    }
                return false;
            }, "Valid To must be bigger than Valid From");


    $('#document-form').validate({
        ignore: '',
        rules: {
            description: {
                required: false
            },
            url: {
                required: false,
                url:true
            },
            security_level: {
                required: true,
                min: 1
            },
            valid_from:{
                isStartDateLessThenDueDate: true
            },
            valid_to: {
                isStartDateLessThenDueDate: true
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
