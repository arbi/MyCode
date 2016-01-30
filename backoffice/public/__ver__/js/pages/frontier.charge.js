$(function() {

    $(window).on("beforeunload", function (e) {

       if((hasPendingCharge() && $('#chargeClick').val() != 'click')) {
            var confirmationMessage = 'You have penging charge';
            (e || window.event).returnValue = confirmationMessage;     //Gecko + IE
            return confirmationMessage;    //Webkit, Safari, Chrome etc.
        }

    });

    if (parseFloat($('#transaction_amount_apartment_currency').val()) == 0) {
        $('#chargingProcess').attr('disabled', 'disabled');
    }

    $('#transaction_amount_apartment_currency').keyup(function () {

        if (parseFloat($(this).val()) <= 0 || !$.isNumeric($(this).val())) {
            disableCharging();
        } else {
            enableCharging();

            if (parseFloat($('#balance-amount').val())) {
                if (parseFloat($('#balance-amount').val()) + LIMIT_AMOUNT < parseFloat($(this).val())) {
                    disableCharging();
                }
            } else {
                if (parseFloat($(this).val()) > (Math.abs(GUEST_BALANCE) + LIMIT_AMOUNT)) {
                    disableCharging();
                }
            }
        }

    });
    // Credit Cards
    var ccSetFirstActive = function() {
        var selected_card = $('.credit-card-list li.active').eq(0), card_id = 0;

        // Set Card id
        if(selected_card.hasClass('fraud-cc')) {
            card_id = 0;
        } else {
            card_id = selected_card.attr('data-card-id');
        }

        $('#cc_id').val(card_id);
    };

    ccSetFirstActive();
    $('.credit-card-list li').click(function(e) {
        e.preventDefault();

        $('.credit-card-list li.active').removeClass('active');

        $(this).addClass('active');
        ccSetFirstActive();
    });

    // New Credit Card validation

    jQuery.validator.addMethod("expdate", function(value, element) {
        var selected = [$('#form-cc-exp-year').val(), numLeftPadZero($('#form-cc-exp-month').val())].join('');

        return (
            numLeftPadZero(parseInt(selected)) >= parseInt($(element).attr('data-today'))
            );
    }, "Expiration date is wrong");

    jQuery.validator.addMethod("cctype", function(value, element) {
        var cardReader = {
            "visa":         [1, /^4/],
            "mastercard":   [2, /^(51|52|53|54|55)/],
            "amex":         [3, /^(34|37)/],
            "discover":     [4, /^(6011|622126|622127|622128|622129|62213|62214|62215|62216|62217|62218|62219|6222|6223|6224|6225|6226|6227|6228|62290|62291|622920|622921|622922|622923|622924|622925|644|645|646|647|648|649|65)/],
            "jcb":          [5, /^(1800|2131|3528|3529|353|354|355|356|357|358)/],
            "diners-club":  [6, /^(300|301|302|303|304|305|309|36|38|39)/]
        };

        $('#cc-new-form .credit-cards li').toggleClass('off', true);

        for (var card in cardReader) {
            if (cardReader[card][1].test(value)) {
                var cardType = cardReader[card][0];

                $('#credit_card_type').val(cardType);
                $('#cc-new-form .credit-cards li.' + card).toggleClass('off', false);
            }
        }

        return true;
    }, "Your entered data is not a credit card number");

    $("#cc-new-form").validate({
        rules: {
            "number": {
                required: true,
                cctype: true,
                creditcard: true,
                minlength: 12,
                maxlength: 16
            },
            "holder": {
                required: true
            },
            "month": {
                required: true,
                digits: true,
                min: 1,
                expdate: true
            },
            "year": {
                required: true,
                digits: true,
                min: 1,
                expdate: true
            },
            "cvc": {
                required: false,
                minlength: 3,
                maxlength: 4,
                digits: true
            }
        },
        highlight: function(element) {
            $(element).parent().addClass('has-error');
        },
        unhighlight: function(element) {
            $(element).parent().removeClass('has-error');
        },
        errorElement: 'span',
        errorClass: 'help-block',
        errorPlacement: function(error, element) {},
        onfocusout: function(element) {
            $(element).valid();

            if ($(element).hasClass('expdate')) {
                $('.expdate').valid();
            }
        },
        onkeyup: function(element) {
            $(element).valid();

            if ($(element).hasClass('expdate')) {
                $('.expdate').valid();
            }
        },
        onclick: function(element) {
            $(element).valid();

            if ($(element).hasClass('expdate')) {
                $('.expdate').valid();
            }
        }
    });


    // end


});

$('.amount').blur(function() {
    if ($(this).valid()) {
        var val = $(this).val();
        if (val != '') {
            val = parseFloat(val);
            val = precise_round(val);

            $(this).val(val);
        }
    }
});

function numLeftPadZero(num) {
    if (num < 10) {
        num = [0, num].join('');
    }

    return num;
}
var accCurrencyRate = $('#acc_currency_rate').val();
var accAmount = $('#accPrice').val();
var apartmentNum = 0;
var taxTypePercent = 1;

var chargeRow = function() {
    var $lastRow = $('#chargesTable>tbody');
    var html = '<tr class="charge_tr">\
                    <td class="form-group form-inline margin-0 addons-name">'+getAddonsList()+'</td>\
                    <td class="text-center addons-night"></td>\
                    <td class="form-group addons-value text-right hidden-xs"></td>\
                    <td class="form-group addons-price text-right"></td>\
                    <td><a href="javascript:void(0)" class="btn btn-danger btn-sm chargeRemoveRow">Remove</a></td>\
                </tr>';
    $lastRow.append(html);
    $( ".chargeRemoveRow" ).click(function() {
        removeRow(this);
    });
    //call pending text
    setTimeout(function(){
        addTextAboutPending();
    }, 10);
};

if($("#addNewChargeRow").length > 0) {
    $( "#addNewChargeRow" ).click(chargeRow);
}

function getAddonsList() {
    var html = '<select class="form-control notZero addon-choose" name="new_addons[]" onchange="changeAddonstype(this)">';
       html += '<option value="0">-- Choose --</option>';
    for(var v in GLOBAL_ADDONS_LIST){
        var item = GLOBAL_ADDONS_LIST[v];
        if(!item.location_join &&  $.inArray(parseInt(item.id), CHARGE_LIST) !== -1)
        {
            html += '<option value="'+item.id+'">'+item.name+'</option>';
        }
    }
    html += '</select>';
    return html;
}

function changeAddonstype(obj){
    var id = obj.value;
    var thisObj = $(obj).closest('tr');

    for(var v in GLOBAL_ADDONS_LIST){
        var item = GLOBAL_ADDONS_LIST[v], chargeAddonsValue = '', chargeAddonsSimbol = '',
            aAmount = 0, addonsValue = '', addonsAcc = '', classAcc = '', addonsView = '';
        if(item.id == id && $.inArray(parseInt(id), CHARGE_LIST) !== -1 ) {
            if (item.id != PARKING_FEE) {
                chargeAddonsValue = item.value;
                chargeAddonsSimbol = item.cname;
                if (chargeAddonsValue > 0) {
                    aAmount = parseFloat(chargeAddonsValue)*(parseFloat(accCurrencyRate)/parseFloat(item.currency_rate));
                    addonsView = '(' + chargeAddonsValue + ' ' + chargeAddonsSimbol + ')';

                }

                aAmount = precise_round(parseFloat(aAmount));
                addonsValue    = '<span>' + addonsView + '</span>\
                              <input type="hidden" value="'+chargeAddonsValue+'" name="addons_value[]"/>' +
                              '<input type="hidden" name="rateNames[]" value="">' +
                              '<input type="hidden" name="entityId[]" value="0">' +
                              '<input type="hidden" value="0" name="reservation_nightly_ids[]">' +
                              '<input type="hidden" value="" name="nightDate[]">';
                addonsAcc      = '<input value="'+aAmount+'" type="text" name="accommodation_amount[]" class="form-control acc_amount charge_valid'+classAcc+'"  onkeyup="changeAccField()" />\
                              <input type="hidden" value="'+item.id+'" name="addonstype[]" />\
                              <input type="hidden" value="0" name="taxtype[]" />';
                thisObj.find('.addons-price').html(addonsAcc);
                thisObj.find('.addons-name').html(item.name);
                thisObj.find('.addons-value').html(addonsValue);
                calculateSum();
            } else {
                var allNightlyIdsAndDates = [];
                $('td[data-nightly-id]').each(function(index){
                    var nightId = $(this).attr('data-nightly-id');
                    var nightDate = $(this).attr('data-night-date');
                    allNightlyIdsAndDates[nightId] = nightDate;
                });
                var allNightlyDates = [];
                var allNightlyIds   = [];
                $('td[data-nightly-id]').each(function(index){
                    var nightId   = $(this).attr('data-nightly-id');
                    var nightDate = $(this).attr('data-night-date');
                    allNightlyDates.push(nightDate);
                    allNightlyIds.push(nightId);
                });
                var apartmentId = $('#apartmentId').val();
                var availableParkingSpots = getAvailableParkingSpots(allNightlyDates, apartmentId);
                if (availableParkingSpots['allAvailableSpots'] == false) {
                    notification(
                        {
                            'status': 'error',
                            'msg' : 'There are no available parking spots for the whole period'
                        }
                    );
                    break;
                } else {
                    addParkingRows(thisObj, availableParkingSpots);
                    calculateSum();
                }
            }

        }
    }
    enableCharging();
}
function createSelectElementForAvailableSpots(availableParkingSpots)
{
    var html = '<select name="entityId[]" class="form-control parking-spots-select spotIds" onchange="changeParkingSpot(this)">';
    $.each(availableParkingSpots, function(index, value){
        html += '<option data-price="'+ value.price +'" value="' + value.id + '">' + value.name + '</option>';
    })
    html += '</select>';
    return html;
}

function changeParkingSpot(obj)
{
    var spotName = $(obj).find('option:selected').text();
    var spotPrice    = $(obj).find('option:selected').attr('data-price');
    var $trObj = $(obj).closest('tr');
    var spotId = $(obj).val();
    $trObj.find('.spot-name').val(spotName);
    $trObj.find('.acc_amount').val(spotPrice).trigger('keyup');
    $trObj.find('.spot-price-hidden').val(spotPrice);
    $trObj.find('.entity-id').val(spotId);
    calculateSum();

}

function addParkingRows(temporaryTr, availableParkingSpots)
{
    temporaryTr.remove();
    var allNightlyIdsAndDates = [];
    $('td[data-nightly-id]').each(function(index){
        var nightId = $(this).attr('data-nightly-id');
        var nightDate = $(this).attr('data-night-date');
        allNightlyIdsAndDates[nightDate] = nightId;
    });

    var $lastRow = $('#chargesTable>tbody');
    for (var nightDate in allNightlyIdsAndDates) {
        var $isSelected        = '';
        var $availablePerNight = [];
        var $selectedSpotName  = '';
        var $selectedEntityId  = '';
        var $parkingSpotsHtml  = '<select class="form-control parking-spots-select spotIds" onchange="changeParkingSpot(this)">'
        var $price             = availableParkingSpots['allAvailableSpots'][0].price;
        var $isFirst           = false;

        if (!availableParkingSpots['allAvailable']) {

            $.each(availableParkingSpots['allAvailableSpots'], function(index,value){
                $parkingSpotsHtml += '<option value="' + value.id + '" data-price="' + value.price +
                    '">'+value.name+'</option>';
            });
            var $selectedSpotName  = availableParkingSpots['allAvailableSpots'][0].name;
            var $selectedEntityId  = availableParkingSpots['allAvailableSpots'][0].id;

        } else {

            $.each(availableParkingSpots['allAvailableSpots'], function(index,value) {
                if (value.date == nightDate) {
                    if (PARKING_SPOT_PRIORITY) {
                        if ($.inArray(value.id, PARKING_SPOT_PRIORITY) != -1 && !$isFirst) {
                            $isFirst          = true;
                            $isSelected       = ' selected';
                            $selectedSpotName = value.name;
                            $selectedEntityId = value.id;
                        } else {
                            $isSelected = '';
                        }
                    }
                    $availablePerNight.push(value);
                    $parkingSpotsHtml += '<option value="' + value.id + '" data-price="' + value.price + '"' + $isSelected +'>'+value.name+'</option>';
                }
            });

            if (!$selectedSpotName) {
                $selectedSpotName = $availablePerNight[0].name;
            }

            if (!$selectedEntityId) {
                $selectedEntityId = $availablePerNight[0].id;
            }
        }

        var addonsAcc = '<input value="' + $price + '" type="text" name="accommodation_amount[]" class="form-control acc_amount spot-price charge_valid"  onkeyup="changeAccField()" />\
          <input type="hidden" value="'+PARKING_FEE+'" name="addonstype[]" />\
          <input type="hidden" value="0" name="taxtype[]" />';

        var $html = '<tr class="charge_tr">';
        $html +=    '<td class="form-group form-inline margin-0 addons-name">Parking</td>';
        $html +=    '<td class="text-center addons-night">' + nightDate + '</td>';
        $html +=    '<td class="form-group addons-value text-right hidden-xs">' + $parkingSpotsHtml +
        '<input type="hidden" class="spot-price-hidden" value="' + $price + '" name="addons_value[]">' +
        '<input type="hidden" class="spot-name" value="' + $selectedSpotName + '" name="rateNames[]">' +
        '<input type="hidden" value="'+ allNightlyIdsAndDates[nightDate] +'" name="reservation_nightly_ids[]">' +
        '<input type="hidden" value="'+ nightDate +'" name="nightDate[]">' +
        '<input type="hidden" class="entity-id" value="' + $selectedEntityId + '" name="entityId[]">' +
        '</td>';
        $html +=    '<td class="form-group addons-price  text-right">' + addonsAcc + '</td>';
        $html +=    '<td><a href="javascript:void(0)" class="btn btn-danger btn-sm chargeRemoveRow">Remove</a></td>';
        $html +=    '</tr>';


        $lastRow.append($html);

        $( ".chargeRemoveRow" ).click(function() {
            removeRow(this);
        });
    }

    //call pending text
    setTimeout(function(){
        addTextAboutPending();
    }, 10);
}

function getAvailableParkingSpots(allNightlyIdsAndDates, apartmentId)
{
    var spotsAlreadySelectedInSameChargeSession = [];
    var result = {'allAvailableSpots': false};

    $('.spotIds').each(function(index){
        var val = $(this).val();
        if ($.inArray(parseInt(val), spotsAlreadySelectedInSameChargeSession) == -1) {
            spotsAlreadySelectedInSameChargeSession.push(parseInt(val));
        }
    });

    $.ajax({
        type: "POST",
        url: GLOBAL_GET_PARKING_SPOTS,
        data: {
            apartment_id: apartmentId,
            all_nights_dates: allNightlyIdsAndDates,
            spots_already_selected_in_this_section: spotsAlreadySelectedInSameChargeSession
        },
        dataType: "json",
        async: false,
        success: function(data) {
            if (data.status == 'success') {
                result['allAvailableSpots'] = data.allAvailableSpots;
                result['allAvailable']      = data.allAvailable;
            }
        }
    });
    return result;
}

function changeAccField(){
    calculateSum();
}

var calculateSum = function (){
    var chargeTotal = 0, balance = 0, amount = 0;
    $( '.acc_amount' ).each(function() {
        var valueAddon  = this.value;
        chargeTotal += parseFloat(valueAddon);
    });

    chargeTotal = parseFloat(chargeTotal);
    balance = $('#balance').val();
    balance = parseFloat(balance);

    if(balance > 0) {
        if(balance >= chargeTotal) {
           amount = 0;
        } else {
           amount = chargeTotal - balance;
        }
    } else if(balance < 0) {
        amount = Math.abs(balance) + chargeTotal;
    } else {
        amount = chargeTotal;
    }
    amount = parseFloat(amount);

    if (amount > 0) {
        enableCharging();
    }

    $('#transaction_amount_apartment_currency').val(precise_round(amount));
    $('#total_price_span').html(precise_round(chargeTotal));
    $('#balance-amount').val(precise_round(amount));
};

if($(".chargeRemoveRow").length > 0){
    $( ".chargeRemoveRow" ).click(function() {
	removeRow(this);
    });
}

function removeRow(obj){
    var parent = $(obj).closest('.charge_tr');
    $(parent).remove();
    calculateSum();
    //call pending text
    setTimeout(function(){
        addTextAboutPending('charge');
    }, 10);

}

if ($("#chargingProcess").length > 0) {
    $('#chargingProcess').click(function() {

        if ($('#cc_id').val() <= 0) {
            notification({status:'error', msg:MSG_NO_CARD});
            return;
        }

        var validate = $('#frontier-charge-form').validate();
        if(!$('#frontier-charge-form').valid() ||
             ($('.charge_valid').length > 0 && !$('.charge_valid').valid()) ||
             ($('.percent_valid').length > 0 && !$('.percent_valid').valid())) {
            validate.focusInvalid();
            return;
        }

        $('#chargeClick').val('click');
        var btn = $('#chargingProcess');
        btn.button('loading');

        var obj = $('#frontier-charge-form').serializeArray();
        obj.push({name: 'res_number', value: $('#booking_res_number').val()});
        obj.push({name: 'acc_currency_rate', value: $('#acc_currency_rate').val()});
        obj.push({name: 'accommodationCurrency', value: $('#accommodationCurrency').val()});
        obj.push({name: 'accId', value: $('#accId').val()});
        obj.push({name: 'groupId', value: GROUP_ID});

        $.ajax({
            type: "POST",
            url: GLOBAL_FRONTIER_CHARGE,
            data: obj,
            dataType: "json",
            success: function(data) {
                if (GROUP_ID > 0) {
                    location.href = GLOBAL_GROUP_URL;
                } else {
                    location.href = '/frontier?id=1_' + $('#bookingId').val();
                }

            }
        });

    });
}

$('#cancelChargingProcess').click(function(){
   $('#chargeClick').val('click');
    if (GROUP_ID > 0) {
        location.href = GLOBAL_GROUP_URL;
    } else {
        location.href = '/frontier?id=1_' + $('#bookingId').val();
    }
});

function hasPendingCharge () {
    if($('.chargeRemoveRow').length > 0)
        return true;
    return false;
}

function addTextAboutPending() {
    if(hasPendingCharge()) {
        $('#chargePending').html('Pending...');
        $('#chargePending').closest('legend').addClass('text-danger');
    } else {
        $('#chargePending').html('');
        $('#chargePending').closest('legend').removeClass();
    }
}


$( "#createNewCreditCard" ).click(function() {
    var validate = $('#cc-new-form').validate(),
        btn = $('#createNewCreditCard');

    btn.button('loading');

    if ($('#cc-new-form').valid()) {
        var obj = $('#cc-new-form').serializeArray();
        obj.push({name: 'reservation_id', value: $('#bookingId').val()});

        $.ajax({
            type: "POST",
            url: GLOBAL_NEW_CC,
            data: obj,
            dataType: "json",
            success: function(data) {
                if (data.status == 'success') {
                    btn.button('reset');
                    location.reload();
                } else {
                    notification(data);
                    btn.button('reset');

                    validate.focusInvalid();
                }
            }
        });
    } else {
        validate.focusInvalid();
        btn.button('reset');
    }
});

function disableCharging() {
    $('#transaction_amount_apartment_currency').closest('.form-group').removeClass('has-success').addClass('has-error');
    $('#chargingProcess').attr('disabled', 'disabled');
}

function enableCharging() {
    $('#transaction_amount_apartment_currency').closest('.form-group').removeClass('has-error').addClass('has-success');
    $('#chargingProcess').removeAttr('disabled');
}
