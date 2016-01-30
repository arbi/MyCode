jQuery.validator.addMethod("isStartDateLessThenDueDate", function(value, element) {
    var startDate = $('#start_date').val();
    var endDate = $('#end_date').val();
    var DateToValue = new Date(startDate);
    var DateFromValue = new Date(endDate);

    if (Date.parse(DateToValue) < Date.parse(DateFromValue)) {
       return true;
    }
    return false;
}, "");


$(function(){
    $('#task-form').validate({
        rules: {
            title: {
                required: true
            },
            task_type:{
                required: true,
                number: true,
                min:1
            },
            start_date: {
                required: true,
                date: true,
                isStartDateLessThenDueDate: true
            },
            end_date: {
                required: true,
                date: true,
                isStartDateLessThenDueDate: true
            },
            budget: {
                number: true
            },
            currency: {
                number: true,
                min: {
                    depends: function(element) {
                        return (
                            $('#budget').val() > 0 ? 1 : 0
                            );
                    }
                }
            },
            res_number: {
                required: false,
                remote: {
                    url: GLOBAL_CHECK_RES,
                    type: "post"
                }
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
            //element.closest('.control-group').find('.help-block').html(error.text());
        }
    });
});