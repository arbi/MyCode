$(function () {
    checkout.init();

    $('.amount_to_pay').click(function () {
        $('#price_tax').toggle('slow');
    });


    var onClass = "on";
    var showClass = "show";

    $("input, textarea, select").bind("checkval", function () {
        var label = $(this).prev("label");

        if ($(this).val() !== "" && $(this).val() !== "0") {
            label.addClass(showClass);
        } else {
            label.removeClass(showClass);
        }
    }).on("keyup change", function () {
        $(this).trigger("checkval");
    }).on("focus", function () {
        $(this).prev("label").addClass(onClass);
    }).on("blur", function () {
        $(this).prev("label").removeClass(onClass);
    }).trigger("checkval");

    $('[data-toggle="popover"]').popover();
    $('body').on('click', function (e) {
        $('[data-toggle=popover]').each(function () {
            // hide any open popovers when the anywhere else in the body is clicked
            if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                $(this).popover('hide');
            }
        });
    });

    if (!parseInt(IS_AFFILIATE_CHOOSER)) {
        $('#aff-id').closest('div.row').fadeOut();
    }

    $('#submit-guest-details').focusout(function () {
        checkDiscount();
    });
});

function checkDiscount() {

    var data = {
        email: $('#email').val(),
        aff_id: $('#aff-id').val(),
        partner_name: $('#aff-id option:selected').text(),
        aff_reference: $('#aff-ref').val(),
        accommodation_price: $('#accommodation-price').html(),
        total_amount: $('#total-amount').html()
    };
    $.ajax({
        type: "POST",
        url: URL_CHECK_DISCOUNT,
        data: data,
        dataType: "json",
        cache: false,
        success: function(data){
            if (data.status == 'success') {
                if ($('#calculated-discount').val() === undefined  || (data.discount_amount != precise_round($('#discount-amount').html()))) {
                    $('#aff-id').val(parseInt(data.affId));
                    $('.payment-details input[name=aff-id]').val($('#aff-id').val());


                    $('div.price-calculation > p.discount-price').remove();
                    $('#calculated-discount, #discount-amount').remove();

                    if (data.discount_amount != undefined) {
                        var deductedPrice = (parseFloat($('#accommodation-price').html()) - precise_round(data.discount_amount));
                        $('.night-price').after('<p id="calculated-discount">' + data.partnerName + ' Discount <span class="pull-right">' +
                            $('#currency').html() + ' - ' + data.discount_amount + '</span></p>');
                    } else {
                        var deductedPrice = precise_round($('#accommodation-price').html());
                    }

                    if (data.affId == GINOSIK_DISCOUNT_ID) {
                        $('#reservation-data').after(
                            '<div class="row"><div class="col-sm-12 text-center" id="discount-alert">\n\
                                <div class=" margin-0 alert ">\n\
                                    <h3 class="margin-0 discount-text">\n\
                                        ' + TXT_GINOSIK_DISCOUNT + '\n\
                                    </h3>\n\
                                   <p ><span style="color:red">NOTE: </span>\n\
                                        ' + TXT_GINOSIK_DISCOUNT_SECONDARY + '\n\
                                    </p>\n\
                                </div>\n\
                            </div></div>'
                        );

                        $("html, body").animate({ scrollTop: 0 }, "slow");
                    } else {
                        $('#discount-alert').remove();
                    }

                    if (data.discount_amount != undefined) {
                        $('#total-amount').after('<span class="hidden" id="discount-amount">' + data.discount_amount + '</span>');
                    }
                    $('#total-amount').after('<span class="hidden" id="deducted-price">' + deductedPrice + '</span>');

                    var $discountAmount = parseFloat(data.total_amount_with_discount);

                    $discountAmount = parseFloat(deductedPrice);
                    $('span.flexible-tax').each(function () {
                        //var taxValueArray = $(this).next().html().split(' ');
                        var taxValueArray = $(this).next().html().slice(0, 1);
                        var taxPercent    = $(this).data('tax-percent');
                        var $newTaxValue;

                        if (taxPercent > 0) {
                            $newTaxValue  = precise_round(($(this).data('tax-percent') * deductedPrice * 0.01 * $(this).data('duration') / NIGHT_COUNT));
                        } else {
                            $newTaxValue  = precise_round($(this).data('price'));
                        }

                        $(this).next().html(taxValueArray[0] + ' ' + $newTaxValue);

                        $discountAmount += parseFloat($newTaxValue);
                    });

                    if ($('span.cleaning-fee').length) {
                        $discountAmount += parseFloat($('span.cleaning-fee').data('price'));
                    }

                    $('#total-price-amount').html('<b>' + $('#currency').html() + precise_round($discountAmount) + '</b>');

                } else {
                    $('#aff-id').val(parseInt(data.affId));
                }
            }
            // change partner name
            $('span.partner-name').html(data.partnerName);
        }
    });
}
