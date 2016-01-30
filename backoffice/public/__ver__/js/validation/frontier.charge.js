$(function() {
    $('#frontier-charge-form').validate({
        rules: {

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
        errorPlacement: function (error, element) {}
    });
});

$.validator.addMethod("amountCharge", function(value, element) {
    return this.optional(element) || /^[0-9]+(\.[0-9]{1,2})?$/i.test(value);
}, "Amount is invalid");

$.validator.addMethod("amount", function(value, element) {
    return this.optional(element) || /^[0-9]+(\.[0-9]{1,2})?$/i.test(value);
}, "Amount is invalid");

$.validator.addMethod("notZero", function(value, element) {
    return this.optional(element) || ((value == 0) ? false:true);
}, "Invalid data");


$.validator.addMethod("checkBeckoffiseUser", function(value, element) {
    var userId = parseInt($('#userCache_id').val());
    return this.optional(element) || ((userId > 0) ? true:false);
}, "Not Backoffice User");

$.validator.addMethod("percentValid", function(value, element) {
    return this.optional(element) ||  /(^100([.]0{1,2})?)$|(^\d{1,2}([.]\d{1,2})?)$/i.test(value);
}, "Percent Field");

jQuery.validator.addClassRules('charge_valid', {
    amount:true
});

jQuery.validator.addClassRules('percent_valid', {
    percentValid:true
});

jQuery.validator.addClassRules('charge_required', {
    required: true
});

jQuery.validator.addClassRules('notZero', {
    required: true,
    notZero:true
});