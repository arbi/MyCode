$(function() {
    $(".day-inventory-actions a[data-action]").tooltip();

    $( ".spot-list-item" ).click(function() {
        var number = $(this).attr('data-color-number');
        var display = $(".color" + number).css('visibility');

        if (display == 'hidden') {
            $(".color" + number).css('visibility', 'visible');
            $(".color" + number).animate({
                    opacity: 1
                },
                100
            );
        } else {
            $(".color" + number).animate({
                    opacity: 0
                },
                100,
                function() {
                    $(".color" + number).css('visibility', 'hidden');
                }
            );
        }
    });

    // Something else
    $('.calendar-day-item').hover(function() {
        $(this).find('.spot-color').removeClass('spot-color-important');
    }, function() {
        $(this).find('.spot-color').addClass('spot-color-important');
    });


    $('.spots-container').click(function() {

        var action = $(this).attr('data-action');
        var base = $(this).closest('.calendar-day-item');
        var date = base.attr('data-date');

        $('#spot-availabilities-form input[name=date]').val(date);

        base.find('.spot-information').each(function() {
            var spotId = $(this).attr('data-spot-id');
            var status = $(this).attr('data-status');
            var price = precise_round($(this).find('.spot-price').attr('data-price-decimal'));
            var percent = $(this).attr('data-price-percent');

            $('#spot_' + spotId).html(price);

            $('input[name=availability\\[' + spotId + '\\]]').each(function() {
                $(this).prop("checked", false);
                $(this).closest('label').removeClass('badge-success');
                $(this).closest('label').removeClass('badge-important');
                if ($(this).attr('data-status') == status) {
                    $(this).prop("checked", true);
                    if ($(this).attr('data-status') == 'open') {
                        $(this).closest('label').addClass('badge-success');
                    } else {
                        $(this).closest('label').addClass('badge-important');
                    }
                } else {
                    $(this).prop("checked", false);
                }
            });
        });

        $('.modal-selected-date').text(base.attr('data-date'));
        $('#update-availabilities').modal('show');
    });

    var $updateButton = $('#spot-availabilities-form-update');
    $updateButton.click(function() {
        $updateButton.button('loading');

        $.post(GLOBAL_UPDATE_AVAILABILITIES, $('#spot-availabilities-form').serialize()).done(function(data) {
            if (data.bo === undefined) {
                return;
            }

            if (data.bo.status == 'success') {
                location.reload();
            } else {
                notification(data.bo);
                $('#locker').fadeOut('fast');
            }
        }).always(function() {
            $updateButton.button('reset');
        })
    });
});