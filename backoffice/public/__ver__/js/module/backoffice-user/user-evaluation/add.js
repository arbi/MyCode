/*
 * Evaluation Type Selector - Change
 */
$('#evaluation_type_id').on('change', function () {
    if ($('#evaluation_type_id').val() == '3') {
        $('#evaluation-items').show();
        $('#score_sum').show();
    } else {
        $('#evaluation-items').hide();
        $('#score_sum').hide();
    }
});