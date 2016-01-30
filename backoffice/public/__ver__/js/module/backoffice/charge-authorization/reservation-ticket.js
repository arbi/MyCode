if ($("#reservation_action_send_ccca").length > 0){
    $( "#reservation_action_send_ccca" ).click(function(e) {
        $('#sendCccaModal').modal();
    });
}

function generateAndSendCccaForm() {
    $('.generateAndSendCccaForm').hide();
    var btn = $('#reservation_action_send_ccca_confirm');
    btn.button('loading');
    var reservationId = $('#booking_id').val();
    var ccId          = $('#send_ccca_cc_id').val();
    var customEmail   = $('#selected-email').val();
    var amount        = $('#amount-for-ccca').val();
    $.ajax({
        type: "POST",
        url: GLOBAL_GENERATE_CCCA_PAGE,
        data: {
            reservation_id:reservationId,
            cc_id:ccId,
            custom_email: customEmail,
            amount: amount
        },
        dataType: "json",
        success: function(data) {
            $('#sendCccaModal').modal('hide');
            if (data['success']) {
                location.reload();
            } else {
                btn.button('reset');
                $('.generateAndSendCccaForm').show();
                notification(data);
            }
        }
    });
}