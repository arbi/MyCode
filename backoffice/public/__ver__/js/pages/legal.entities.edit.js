$(function() {
    var $name = $('#name');
    if (GLOBAL_NOT_UNIQUE_NAME == 1) {
        $name.closest('.form-group').removeClass('has-success').addClass('has-error');
        $name.focus();
    }
});