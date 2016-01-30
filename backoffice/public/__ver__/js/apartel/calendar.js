$(function() {
    $(".day-inventory-actions a[data-action]").tooltip();

    // right rate open close
    $( ".rate-list-item" ).click(function() {
        if (GLOBAL_MANAGER == 'no') {
            return false;
        }

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

    // calendar hover
    $('.calendar-day-item').hover(function() {
        $(this).find('.rate-color').removeClass('rate-color-important');
    }, function() {
        $(this).find('.rate-color').addClass('rate-color-important');
    });

    $('#parent_price').on('input', function() {
        if (GLOBAL_MANAGER == 'no') {
            return false;
        }
        var parentPrice = $('#parent_price').val();
        var base = $('#rate-price-form');
        base.find('.child-part').each(function() {
            var percent = $(this).find('.child-percent').text();
            var childPrice = parseFloat(parentPrice) + parseFloat(parentPrice * percent / 100);
            childPrice = precise_round(childPrice);
            $(this).find('.child-price').html(childPrice);

        });
    });

    // open price dialog
    $('.rates-container').click(function() {
        if (GLOBAL_MANAGER == 'no') {
            return false;
        }

        var action = $(this).attr('data-action');
        var base = $(this).closest('.calendar-day-item');
        var date = base.attr('data-date');

        $('#rate-price-form input[name=date]').val(date);

        base.find('.rate-information').each(function() {
            var rateId = $(this).attr('data-rate-id');
            var status = $(this).attr('data-status');
            var price = precise_round($(this).find('.rate-price').attr('data-price-decimal'));
            var percent = $(this).attr('data-price-percent');
            var rateType = $(this).attr('data-type');
            var isLockPrice = $(this).attr('date-lock-price');

            if (rateType == 1) {
                $('#parent_price').val(price);
            } else {
                $('#rate_' + rateId).html(price);
                var childObj = $('#percent_' + rateId);
                childObj.html(percent);
                childObj.removeClass('text-success');
                childObj.removeClass('text-danger');
                if (parseFloat(percent) > 0) {
                    childObj.addClass('text-success');
                } else {
                    childObj.addClass('text-danger');
                }
            }

            if (isLockPrice == 1) {
                $('#lock_price').prop('checked', true);
            } else {
                $('#lock_price').prop('checked', false);
            }
        });

        $('.modal-selected-date').text(base.attr('data-date'));
        $('#update-prices').modal('show');
    });

    // update price
    var update_button = $('#rate-price-form-update');
    update_button.click(function() {
        update_button.button('loading');

        if (GLOBAL_MANAGER == 'no') {
            return false;
        }

        var masterRate = null;
        var ratesSummary = 0;
        var master = 0;
        $('#rate-price-form :checked').each(function() {
            master = parseInt($(this).data("master"));
            if (master == 1) {
                masterRate = parseInt($(this).val());
            }
            ratesSummary += parseInt($(this).val());
        });
        // Opened
        if (!masterRate && ratesSummary > 0) {
            update_button.button('reset');
            notification({
                status: 'warning',
                msg: 'Child rate availability cannot be higher than parent rate availability!'
            });
        } else {
            $.post(GLOBAL_UPDATE_PRICES, $('#rate-price-form').serialize()).done(function(data) {
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
                update_button.button('reset');
            })
        }
    });

    // synchronize month
    if ($('#synchronizeMonth').length) {
        $('#synchronizeMonth').click(function(){
            var obj = $(this);
            obj.button('loading');
            $.ajax({
                url: obj.attr('data-url'),
                type: "POST",
                data: {
                    date_from: obj.attr('data-from'),
                    date_to: obj.attr('data-to')
                },
                dataType: "json",
                cache: false,
                success: function(data) {
                    notification(data);
                    if (data.status == 'success') {
                        notification({
                            status: 'warning',
                            msg: 'The process of synchronization will take some time.'
                        })
                    }
                    obj.button('reset');
                }
            });
        });
    }

});