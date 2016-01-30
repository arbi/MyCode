function charging(type) {
    type = type || '';
    var btn = $('#chargingProcess');
    btn.prop('disabled', true);
    btn.button('loading');
    if (type == 'c') {
        $('#chargingCollection').hide();
    }
    if(!hasPendingCharge ()) {
        var msg = {
            status: "warning",
            msg: "No charges were added."
        }
        notification(msg);
        btn.prop('disabled', false);
        btn.button('reset');
        if (type == 'c') {
            $('#chargingCollection').show();
        }
        return;
    }

    var productTotal       = 0;
    var discountTotal      = 0;
    var productExistTotal  = 0;
    var discountExistTotal = 0;

    var invalidDiscount    = false;

    var tdObj    = $('.discount_value').closest('td');
    var inputObj = $(tdObj).find("input[name='addonstype[]']");

    $(".product_value_exist").each(function() {

        var productExistVal = this.value;
        productExistTotal += parseFloat(productExistVal);
    });

    $(".discount_value_exist").each(function() {

        var discountExistVal = this.value;
        discountExistTotal += Math.abs(parseFloat(discountExistVal));
    });

    $(".product_value").each(function() {

        var productVal = this.value;
        productTotal += parseFloat(productVal);
    });

    $(".discount_value").each(function() {

        if (parseInt(this.value) == 0) {
            var data = {
                "status": "error",
                "msg"   : "Invalid discount number"
            };
            notification(data);
            $(tdObj).addClass('has-error');
            invalidDiscount = true;
        }

        var discountVal = this.value;
        discountTotal += parseFloat(discountVal);
    });

    if (invalidDiscount) {
        btn.prop('disabled', false);
        btn.button('reset');
        if (type == 'c') {
            $('#chargingCollection').show();
        }
        return;
    }

    productTotal += parseFloat(productExistTotal);

    discountTotal += discountExistTotal;

    productTotal  = (parseFloat(productTotal)*GLOBAL_DISCOUNT_VALUE/100);
    productTotal  = precise_round(productTotal);



    if (inputObj.val() == 10) {
        if (discountTotal > productTotal) {
            var data = {
                "status": "error",
                "msg"   : "The total amount of discount cannot exceed more than " + GLOBAL_DISCOUNT_VALUE + "% of product value."
            };
            notification(data);
            $(tdObj).addClass('has-error');
            btn.prop('disabled', false);
            btn.button('reset');
            if (type == 'c') {
                $('#chargingCollection').show();
            }
            return;
        }
    }

    var validate = $('#charge-form').validate({});
    if(!$('#charge-form').valid() ||
        ($('.percent_valid').length > 0 && !$('.percent_valid').valid()) ||
        ($('.acc_amount').length > 0 && !$('.acc_amount').valid()) ||
        ($('.money_direction').length > 0 && !$('.money_direction').valid())) {

        validate.focusInvalid();
        btn.prop('disabled', false);
        btn.button('reset');
        if (type == 'c') {
            $('#chargingCollection').show();
        }
        return;
    }

    $('#product_total_value').val(productTotal);
    $('#discount_total_value').val(discountTotal);

    var obj = $('#charge-form').serializeArray();
    obj.push({name: 'reservation_id', value: $('#booking_id').val()});
    obj.push({name: 'res_number', value: $('#booking_res_number').val()});
    obj.push({name: 'acc_currency_rate', value: $('#acc_currency_rate').val()});
    obj.push({name: 'customer_currency_rate', value: $('#customer_currency_rate').val()});
    obj.push({name: 'customerCurrency', value: $('#customerCurrency').val()});
    obj.push({name: 'accommodationCurrency', value: $('#accommodationCurrency').val()});
    obj.push({name: 'booking_statuses', value: $('#booking_statuses').val()});
    obj.push({name: 'accId', value: $('#accId').val()});

    $.ajax({
        type: "POST",
        url: GLOBAL_CHARGE,
        data: obj,
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                $('#chargeClick').val('click');
                if (type == 'c') {
                    setCookie('checkCollection', '1', 0);
                }

                window.location.hash = '#financial_details';
                location.reload();
            } else {
                notification(data);
                btn.prop('disabled', false);
                btn.button('reset');
                if (type == 'c') {
                    $('#chargingCollection').show();
                }
            }
        }
    });
}

if ($("#chargingProcess").length > 0) {
    $('#chargingProcess').click(function() {
        charging();
    });
}

if ($("#chargingCollection").length > 0) {
    $('#chargingCollection').click(function() {
        charging('c');
    });
}

// charge part
function removeRow(obj){
    var parent = $(obj).closest('.charge_tr');
    $(parent).remove();
    calculateSum();
//    var selectInput = parent.find('input[type=text]');
//    if(selectInput.hasClass('accInput')){
//        var getApartmentId = selectInput.attr('id');
//         if(getApartmentId.indexOf('accommodation_amount_') != -1){
//           var num  = getApartmentId.replace('accommodation_amount_', '');
//           $( ".addonsPercent_" + num ).each(function() {
//                $(this).attr('readonly', true);
//           });
//        }
//    }
    //call pending text
    setTimeout(function(){
        addTextAboutPending('charge');
    }, 10);
}

if ($(".chargeRemoveRow").length > 0){
    $( ".chargeRemoveRow" ).click(function() {
        removeRow(this);
    });
}

if ($(".chargedRemoveRow").length > 0) {
    $( ".chargedRemoveRow" ).click(function() {
        var parent = $(this).closest('.charged_tr');
        $(parent).addClass('deleted');
        $(this).remove();
        $(parent).find('input:hidden').val(0);
        var removedId = $(this).attr('data-id');
        $(parent).find('.removedRow').val(removedId);

        if (parseInt($(this).attr('data-is-parent'))) {
            var nightlyId = $(this).attr('data-nightly-id');
            $("#activeChargesTable a[data-nightly-id='"+nightlyId+"']").each(function() {
                var childParentTr = $(this).closest('.charged_tr');
                $(childParentTr).addClass('deleted');
                $(this).remove();
                $(childParentTr).find('input:hidden').val(0);
                $(childParentTr).find('.removedRow').val($(this).attr('data-id'));
            });
        }
        calculateSum();
        //call pending text
        setTimeout(function(){
            addTextAboutPending('charge');
        }, 10);
    });
}

function nightList(apartmentNum, commissionPartner) {
    var html = '<select class="form-control notZero addon-choose" onchange="changeDateNight(this, ' + apartmentNum + ', ' + commissionPartner + ')">';
    html += '<option value="0">-- Choose --</option>';
    for(var v in CHARGE_DATE_LIST) {
        var item = CHARGE_DATE_LIST[v];
        html += '<option value="' + item + '">' + item + '</option>';
    }
    html += '</select>';
    return html;
}

function changeDateNight(obj, apartmentNum, commissionPartner) {
    for (var nd in GLOBAL_NIGHT_DATA) {
        var night = GLOBAL_NIGHT_DATA[nd];
        if (obj.value == night.date) {
            $(obj).closest('tr').remove();
            nightCharge(night, apartmentNum, commissionPartner);
        }
    }
    return false;
}

function changeParkingDateNight(obj, parkingNum) {

    for (var nd in GLOBAL_NIGHT_DATA) {
        var night = GLOBAL_NIGHT_DATA[nd];
        if (obj.value == night.date) {
            $(obj).closest('tr').remove();
            var availableParkingForThisPeriod = getAvailableSpotsForApartment(obj.value);
            if (availableParkingForThisPeriod['allAvailableSpots'] == false) {
                notification(
                    {
                        'status': 'error',
                        'msg' : 'There are no available parking spots for selected Date'
                    }
                );
                chargeRow();
                break;
            } else {
                parkingNightCharge(night, parkingNum, availableParkingForThisPeriod);
            }
        }
    }
    return false;
}

function parkingNightList(parkingNum) {
    var html = '<select class="form-control notZero addon-choose" onchange="changeParkingDateNight(this, ' + parkingNum + ')">';
    html += '<option value="0">-- Choose --</option>';
    for(var v in CHARGE_DATE_LIST) {
        var item = CHARGE_DATE_LIST[v];
        html += '<option value="' + item + '">' + item + '</option>';
    }
    html += '</select>';
    return html;
}

function extraPersonNum()
{
    var html = '<select class="form-control extra-person">';
    html += '<option value="0">-- People --</option>';
    for (var i=1; i <= POSSIBLE_EXTRA_PERSON; i++ ) {
        html += '<option value="' + i + '">' + i + '</option>';
    }

    html += '</select>';

    var $addonsValue = '<span class="blue_color"></span>'+
        '<input type="hidden" value="0" name="rateIds[]">'+
        '<input class="addons-value" type="hidden" value="0" name="addons_value[]">' +
        '<input type="hidden" value="0" name="reservation_nightly_ids[]">'+
        '<input type="hidden" value="" name="rateNames[]">'+
        '<input type="hidden" value="0" name="entityId[]">'+
        '<input type="hidden" value="" name="nightDate[]">';

    return html + $addonsValue ;
}

$('tbody').on('change', 'select.extra-person', function() {
    $(this).closest('td').find('input.addons-value').val($(this).val());
});

function getAddonsList() {
    var html = '<select class="form-control notZero addon-choose" name="new_addons[]" onchange="changeAddonsType(this)">';
    html += '<option value="0">-- Choose --</option>';

    for(var v in GLOBAL_ADDONS_LIST){
        var item = GLOBAL_ADDONS_LIST[v];
        if($.inArray(parseInt(item.id), GLOBAL_DEPRECATED_ADDONS_LIST ) !== -1) {
            continue;
        }
        if(!item.location_join)
        {
            html += '<option value="'+item.id+'">'+item.name+'</option>';
        }
    }
    html += '</select>';
    return html;
}

var apartmentNum = 0;
var parkingNum = 0;
function changeAddonsType(obj) {
    var id = obj.value,
        thisObj = $(obj).closest('tr');

    for (var v in GLOBAL_ADDONS_LIST) {
        var item = GLOBAL_ADDONS_LIST[v],
            chargeAddonsValue = chargeAddonsSimbol = addonsValue = addonsAcc = classAcc = '',
            price = 0,
            isApartment;
        if (item.id == id) {
            var commissionPartner = 0;
            if (item.default_commission == 1) {
                commissionPartner = GLOBAL_AFFILIATE_COMMISSION;
                //make default commission zero for parking if the partner is Expidia
                //if (id == 6 && (parseInt($('#booking_partners').val()) == EXPIDIA_EXPIDIA_COLLECT_PARTNER_ID || parseInt($('#booking_partners').val()) == EXPIDIA_EXPIDIA_OR_GINOSI_COLLECT_PARTNER_ID || parseInt($('#booking_partners').val()) == EXPIDIA_GINOSI_COLLECT_PARTNER_ID)) {
                //    commissionPartner = 0;
                //}
            }
            if (item.cname == null) {
                item.cname = accCurrencySign;
                item.currency_rate = accCurrencyRate;
            }

            chargeAddonsValue = item.value;
            chargeAddonsSimbol = item.cname;
            var obj_last_tr;
            if (id == CHARGE_APARTMENT) {
                for (var nd in GLOBAL_NIGHT_DATA) {
                    var night = GLOBAL_NIGHT_DATA[nd];
                    apartmentNum++;
                    thisObj.remove();
                    nightCharge(night, apartmentNum, commissionPartner);
                }
            } else if (id == CHARGE_NIGHT) {
                apartmentNum++;
                thisObj.find('.addons-night').html(nightList(apartmentNum, commissionPartner));
            } else if (id == CHARGE_PARKING) {
                var availableParkingForThisPeriod = getAvailableSpotsForApartment();

                if (availableParkingForThisPeriod['allAvailableSpots'] == false) {
                    notification(
                        {
                            'status': 'error',
                            'msg' : 'There are no available parking spots for the whole period'
                        }
                    );
                    break;
                }

                for (var nd in GLOBAL_PARKING_NIGHT_DATA) {
                    var night = GLOBAL_PARKING_NIGHT_DATA[nd];
                    parkingNum++;
                    thisObj.remove();
                    parkingNightCharge(night, parkingNum, availableParkingForThisPeriod);
                }
            } else if (id == CHARGE_PARKING_NIGHT) {
                parkingNum++;
                thisObj.find('.addons-night').html(parkingNightList(parkingNum));
            } else {
                var addonsView = '';
                if (chargeAddonsValue > 0) {
                    price = parseFloat(chargeAddonsValue) * (parseFloat(accCurrencyRate) / parseFloat(item.currency_rate));
                    addonsView = '(' + chargeAddonsValue + ' ' + chargeAddonsSimbol + ')';
                }

                price = precise_round(parseFloat(price));
                addonsValue = '<span class="blue_color">' + addonsView + '</span>'+
                    '<input type="hidden" value="0" name="rateIds[]">'+
                    '<input type="hidden" value="'+chargeAddonsValue+'" name="addons_value[]">' +
                    '<input type="hidden" value="0" name="reservation_nightly_ids[]">'+
                    '<input type="hidden" value="" name="rateNames[]">'+
                    '<input type="hidden" value="0" name="entityId[]">'+
                    '<input type="hidden" value="" name="nightDate[]">';
                var discountVal = '';
                if (id == 10) {
                    discountVal = 'discount_value';
                }

                if (id == CHARGE_PARKING) {
                    price *= GLOBAL_NIGHTS_COUNT;
                    price = price.toFixed(2);
                }

                if (id == CHARGE_EXTRA_PERSON && parseInt(POSSIBLE_EXTRA_PERSON) <= 0) {
                    notification(
                        {
                            'status': 'error',
                            'msg' : 'Impossible to add extra person.'
                        }
                    );
                    break;
                }

                if (id == CHARGE_EXTRA_PERSON) {
                    addonsValue = extraPersonNum();
                }

                addonsAcc = '<input value="' + price + '" type="text" name="accommodation_amount[]" class="form-control acc_amount charge_valid text-right' + classAcc + ' ' + discountVal +'"  onkeyup="changeAccField(this, false, ' + apartmentNum + ')">'+
                    '<input type="hidden" value="' + item.id + '" name="addonstype[]">'+
                    '<input type="hidden" value="0" name="taxtype[]">';

                if (id == 9 || id == 10) {
                    thisObj.find('.percent_valid').val(0).prop('readonly', true);
                    thisObj.find('.money_direction').val(2);
                }


                thisObj.find('.addons-price').html(addonsAcc);
                thisObj.find('.addons-name').html(item.name);

                thisObj.find('.addons-value').html(addonsValue);
                thisObj.find('.addons-commission input').val(commissionPartner);

            }

            if (id == 1 || id == 6 || id == 8 || id == 9) {
                thisObj.find('.addons-price>.acc_amount').addClass('product_value');
            }

            if (BUSINESS_MODEL != BUSINESS_MODEL_GINOSI_COLLECT) {
                $('.money_direction').val(CHARGE_PARTNER_COLLECT);
            }

            calculateSum();
        }
    }
}

function nightCharge (night, apartmentNum, commissionPartner) {

    var $lastRow = chargeRow();
    var chargeAddonsValue;
    var partnerTaxCommission = parseInt($('#is-partner-tax-commission').val());

    var price = nightPrice = night.price;
    apartmentNum++;
    var classAcc = ' accInput apartment' + apartmentNum;
    var addonsValue = getRatesByDate(night.date, night.rate_id) +
        '<input type="hidden" value="0" name="addons_value[]">'+
        '<input type="hidden" value="' + night.reservation_nightly_id + '" name="reservation_nightly_ids[]">'+
        '<input type="hidden" value="' + night.rate_name + '" name="rateNames[]" class="rate-name">'+
        '<input type="hidden" value="0" name="entityId[]" >'+
        '<input type="hidden" value="' + night.date + '" name="nightDate[]">';

    var addonsAcc = '<input value="' + price + '" type="text" name="accommodation_amount[]" class="form-control acc_amount charge_valid text-right' + classAcc +'"  oninput="changeAccField(this, true, ' + apartmentNum + ')">'+
        '<input type="hidden" value="' + CHARGE_APARTMENT + '" name="addonstype[]">'+
        '<input type="hidden" value="0" name="taxtype[]">';

    fillChargeRow($lastRow, {
        name: 'Apartment',
        date: night.date,
        value: addonsValue,
        price: addonsAcc,
        commission: commissionPartner
    });

    var apartmentTaxesClass = 'tax' + apartmentNum;
    for (var r in GLOBAL_ADDONS_LIST) {
        var itm = GLOBAL_ADDONS_LIST[r];
        var $taxDetails = $('#' + itm.location_join + '-details');
        var chargeAddonsAdditionalValue = parseFloat($taxDetails.data('additional-value'));
        var exactTaxValue;
        chargeAddonsValue = parseFloat($taxDetails.data('value'));


        var commissionTaxPartner = 0;
        if (itm.default_commission == 1) {
            commissionTaxPartner = GLOBAL_AFFILIATE_COMMISSION;
        }
        // Taxes Included
        if (itm.location_join) {
            if (parseInt($taxDetails.data('included')) == 1) {
                chargeAddonsValue = 0;
            } else {
                var taxMaxDuration = parseInt($taxDetails.data('max-duration'));
                var currentDuration = $('#activeChargesTable')
                    .find('tr:not(.deleted)[data-type="' + itm.id + '"]')
                    .length;

                if (partnerTaxCommission) {
                    currentDuration /= 2;
                }

                if (
                    taxMaxDuration > 0 && currentDuration >= taxMaxDuration
                ) {
                    chargeAddonsValue = 0;
                }

                // Do we give partner commission from additional tax charges
                if (!partnerTaxCommission) {
                    chargeAddonsValue += chargeAddonsAdditionalValue;
                    chargeAddonsAdditionalValue = 0;
                }
            }

            if (chargeAddonsValue > 0) {
                $lastRow = chargeRow();
                $lastRow.addClass(apartmentTaxesClass);
                $lastRow.attr('data-type', itm.id);

                // Taxes
                var taxType = $taxDetails.data('type');
                addonsValue = '<input type="hidden" value="0" name="rateIds[]">';
                if (parseInt(taxType) == taxTypePercent) { // percent
                    addonsValue += '<div class="input-prepend input-append form-inline input-group margin-0">'+
                        '<input type="text" value="' + chargeAddonsValue + '" name="addons_value[]" class="form-control percent_valid tax-field" data-tax-type="' + taxType + '" onkeyup="changeTaxField(this, ' + apartmentNum + ')" data-apartment-num="' + apartmentNum + '"  maxlength="5">'+
                        '<span class="input-group-addon">%</span>'+
                        '</div>';
                    price = parseFloat(nightPrice) * parseFloat(chargeAddonsValue) / 100;
                    price = precise_round(parseFloat(price));
                } else {
                    price = precise_round(parseFloat(chargeAddonsValue));
                    exactTaxValue = parseFloat($taxDetails.data('exact-value'));
                    if (!partnerTaxCommission) {
                        exactTaxValue += $taxDetails.data('additional-exact-value');
                    }
                    addonsValue += '<input type="text" value="' + exactTaxValue + '" name="addons_value[]" class="form-control charge_valid tax-field" data-tax-type="' + taxType + '" onkeyup="changeTaxField(this, ' + apartmentNum + ')" data-apartment-num="' + apartmentNum + '">';
                }
                addonsValue += '<input type="hidden" value="' + night.reservation_nightly_id + '" name="reservation_nightly_ids[]">' +
                    '<input type="hidden" value="" name="rateNames[]">'+
                    '<input type="hidden" value="0" name="entityId[]">'+
                    '<input type="hidden" value="' + night.date + '" name="nightDate[]">';
                price = precise_round(parseFloat(price));
                addonsAcc = '<span  class="taxApartment text-right">' + price + '</span>'+
                    '<input value="' + price + '" type="hidden" name="accommodation_amount[]" class="acc_amount">'+
                    '<input type="hidden" value="' + itm.id + '" name="addonstype[]">'+
                    '<input type="hidden" value="' + taxType + '" name="taxtype[]">';

                fillChargeRow($lastRow, {
                    name: itm.name,
                    date: night.date,
                    value: addonsValue,
                    price: addonsAcc,
                    commission: commissionTaxPartner
                });

                if (chargeAddonsAdditionalValue) {
                    $lastRow = chargeRow();

                    $lastRow.addClass(apartmentTaxesClass + ' tax-additional');
                    $lastRow.attr('data-type', itm.id);

                    // Taxes
                    addonsValue = '<input type="hidden" value="0" name="rateIds[]">';
                    if (parseInt(taxType) == taxTypePercent) { // percent
                        addonsValue += '<div class="input-prepend input-append form-inline input-group margin-0">'+
                            '<input type="text" value="' + chargeAddonsAdditionalValue + '" name="addons_value[]" class="form-control percent_valid tax-field" data-tax-type="' + taxType + '" onkeyup="changeTaxField(this, ' + apartmentNum + ')" data-apartment-num="' + apartmentNum + '"  maxlength="5">'+
                            '<span class="input-group-addon">%</span>'+
                            '</div>';
                        price = parseFloat(nightPrice) * parseFloat(chargeAddonsAdditionalValue) / 100;
                        price = precise_round(parseFloat(price));
                    } else {
                        price = precise_round(parseFloat(chargeAddonsAdditionalValue));
                        exactTaxValue = parseFloat($taxDetails.data('additional-exact-value'));
                        addonsValue += '<input type="text" value="' + exactTaxValue + '" name="addons_value[]" class="form-control charge_valid tax-field" data-tax-type="' + taxType + '" onkeyup="changeTaxField(this, ' + apartmentNum + ')" data-apartment-num="' + apartmentNum + '">';
                    }
                    addonsValue += '<input type="hidden" value="' + night.reservation_nightly_id + '" name="reservation_nightly_ids[]">' +
                        '<input type="hidden" value="" name="rateNames[]">'+
                        '<input type="hidden" value="0" name="entityId[]">'+
                        '<input type="hidden" value="' + night.date + '" name="nightDate[]">';
                    price = precise_round(parseFloat(price));
                    addonsAcc = '<span  class="taxApartment text-right">' + price + '</span>'+
                        '<input value="' + price + '" type="hidden" name="accommodation_amount[]" class="acc_amount">'+
                        '<input type="hidden" value="' + itm.id + '" name="addonstype[]">'+
                        '<input type="hidden" value="' + taxType + '" name="taxtype[]">';

                    fillChargeRow($lastRow, {
                        name: itm.name + ' (Additional)',
                        date: night.date,
                        value: addonsValue,
                        price: addonsAcc,
                        commission: GLOBAL_AFFILIATE_COMMISSION
                    });
                }
            }
        }
    }
}

function fillChargeRow($row, data)
{
    // Use moment.js to reformat date
    var date = moment(data.date).format(globalDateFormat);
    $row.find('.addons-price').html(data.price);
    $row.find('.addons-name').html(data.name);
    $row.find('.addons-night').html(date);
    $row.find('.addons-value').html(data.value);
    $row.find('.addons-commission input').val(data.commission);
}

function getAvailableSpotsForApartment(selectedDate)
{
    var result = {'allAvailableSpots': false};
    var all_nights_dates = [];
    var is_selected_date = 0;
    var spotsAlreadySelectedInSameChargeSession = [];

    $('.spotIds').each(function(index){
        var val = $(this).val();
        if ($.inArray(parseInt(val), spotsAlreadySelectedInSameChargeSession) == -1) {
            spotsAlreadySelectedInSameChargeSession.push(parseInt(val));
        }
    });

    var apartment_id = 0;

    $.each(GLOBAL_NIGHT_DATA, function(index, value){
        all_nights_dates.push(value.date);
        apartment_id = value.apartment_id;
    });

    if (selectedDate) {
        all_nights_dates = [];
        all_nights_dates.push(selectedDate);
        is_selected_date = 1;
        spotsAlreadySelectedInSameChargeSession = [];
    }

    $.ajax({
        type: "POST",
        url: GLOBAL_GET_PARKING_SPOTS,
        data: {
            apartment_id: apartment_id,
            all_nights_dates: all_nights_dates,
            spots_already_selected_in_this_section: spotsAlreadySelectedInSameChargeSession,
            is_selected_date: is_selected_date
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

function parkingNightCharge (night, parkingNum, availableParkingForThisPeriod) {
    var $lastRow = chargeRow();

    var $isSelected        = '';
    var $availablePerNight = [];
    var $selectedSpotName  = '';
    var $selectedEntityId  = '';
    var parkingSpotsHtml   = '<select class="form-control notZero addon-choose spotIds" name="spotIds[]" onchange="changeSpot(this)">'
    var $price             = 0;
    var $isFirst           = false;

    if (!availableParkingForThisPeriod['allAvailable']) {

        $.each(availableParkingForThisPeriod['allAvailableSpots'], function(index,value){
            parkingSpotsHtml += '<option value="' + value.id + '" data-price="' + value.price +
                '">'+value.name+'</option>';
        });

        $selectedSpotName = availableParkingForThisPeriod['allAvailableSpots'][0].name;
        $selectedEntityId = availableParkingForThisPeriod['allAvailableSpots'][0].id;
        $price            = availableParkingForThisPeriod['allAvailableSpots'][0].price;
    } else {

        $.each(availableParkingForThisPeriod['allAvailableSpots'], function(index,value) {
            if (value.date == night.date) {

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
                parkingSpotsHtml += '<option value="' + value.id + '" data-price="' + value.price + '"' + $isSelected +'>'+value.name+'</option>';
            }
        });

        if (!$selectedSpotName) {
            $selectedSpotName = $availablePerNight[0].name;
        }

        if (!$selectedEntityId) {
            $selectedEntityId = $availablePerNight[0].id;
        }
        $price = availableParkingForThisPeriod['allAvailableSpots'][0].price;
    }

    parkingSpotsHtml += '</select><input type="hidden" value="0" name="rateIds[]">';

    var obj_last_tr = $('#activeChargesTable>tbody tr:last');
    parkingNum++;

    var classParking = ' parkingInput parking' + parkingNum;
    var addonsValue = '' + parkingSpotsHtml +
        '<input type="hidden"  value="0" name="addons_value[]">'+
        '<input type="hidden" value="' + night.reservation_nightly_id + '" name="reservation_nightly_ids[]">'+
        '<input type="hidden" value="' + night.date + '" name="nightDate[]">' +
        '<input type="hidden" class="spot-name" value="' + $selectedSpotName + '" name="rateNames[]">' +
        '<input type="hidden" class="entity-id" value="' + $selectedEntityId + '" name="entityId[]">'
        ;

    var addonsParking = '<input value="' + $price + '" type="text" name="accommodation_amount[]" class="form-control acc_amount charge_valid text-right' + classParking +'"  >'+
        '<input type="hidden" value="' + CHARGE_PARKING + '" name="addonstype[]">'+
        '<input type="hidden" value="0" name="taxtype[]">';

    fillChargeRow($lastRow, {
        name: 'Parking',
        date: night.date,
        price: addonsParking,
        value: addonsValue,
        commission: 0
    });
}

var chargeRow = function() {
    var $tBody = $('#activeChargesTable>tbody');

    var moneyDirectionsSelectHTML = '<select class="form-control notZero money_direction" name="new_addon_money_direction[]" onchange="changeDirection(this)">\
                                        <option value="0">-- Choose --</option>\
                                        <option value="2">Ginosi Collect</option>\
                                        <option value="3">Partner Collect</option>\
                                    </select>';

    var commissionInputHTML = '<div class="input-prepend input-append form-inline input-group margin-0">\
                               <input type="text" name="new_addon_commission[]" value="0" class="form-control percent_valid" min="0">\
                               <span class="input-group-addon">%</span>\
                               </div>';

    var html = '<tr class="charge_tr">\
                    <td class="form-group form-inline margin-0 addons-name">'+getAddonsList()+'</td>\
                    <td class="text-center addons-night"></td>\
                    <td class="text-right addons-value form-inline"></td>\
                    <td class="form-group form-inline margin-0 text-right addons-price"></td>\
                    <td class="form-group form-inline margin-0 addons-direction">' + moneyDirectionsSelectHTML + '</td>\
                    <td class="addons-commission">' + commissionInputHTML + '</td>\
                    <td><a href="javascript:void(0)" class="btn btn-danger btn-sm chargeRemoveRow">Remove</a></td>\
                </tr>';
    $tBody.append(html);

    $( ".chargeRemoveRow" ).click(function() {
        removeRow(this);
    });

    //call pending text
    setTimeout(function(){
        addTextAboutPending('charge');
    }, 10);

    return $tBody.find('tr:last');
};

if ($("#addNewChargeRow").length > 0) {
    $( "#addNewChargeRow" ).click(chargeRow);
}

var calculateSum = function () {
    var totalAmountInApartmentCurrency = 0;

    $( ".acc_amount" ).each(function() {
        var newChargeAddonType = $(this).closest('td').find('input[name="addonstype[]"]').val();

        var newChargeAmountInApartmentCurrency = parseFloat(this.value);

        // ADDON_TYPE_DISCOUNT or ADDON_TYPE_COMPENSATION
        if (newChargeAddonType == 10 || newChargeAddonType == 13) {
            newChargeAmountInApartmentCurrency = -newChargeAmountInApartmentCurrency;
        }

        totalAmountInApartmentCurrency += parseFloat(newChargeAmountInApartmentCurrency);
    });

    $( ".acc_amount_exist" ).each(function() {
        var amountInApartmentCurrency  = this.value;
        totalAmountInApartmentCurrency += parseFloat(amountInApartmentCurrency);
    });

    totalAmountInApartmentCurrency = precise_round(parseFloat(totalAmountInApartmentCurrency));

    $('#total_accommodation_span').html(totalAmountInApartmentCurrency);
    $('#accommodation_total').val(totalAmountInApartmentCurrency);
};

function changeRate(obj){
    var rateName = $(obj).find("option:selected").text();
    var ratePrice = $(obj).find("option:selected").attr('date-price');
    var trObj = $(obj).closest('tr');
    trObj.find('.rate-name').val(rateName);
    trObj.find('.acc_amount').val(ratePrice).trigger('keyup');
    calculateSum();
}

function changeSpot(obj){
    var spotPrice = $(obj).find("option:selected").attr('data-price');
    var spotName = $(obj).find("option:selected").text();
    var spotId = $(obj).val();
    var trObj = $(obj).closest('tr');
    trObj.find('.entity-id').val(spotId);
    trObj.find('.spot-name').val(spotName);
    trObj.find('.acc_amount').val(spotPrice).trigger('keyup');
    calculateSum();
}

function getRatesByDate(date, id){
    var html = '<select class="form-control notZero addon-choose" name="rateIds[]" onchange="changeRate(this)">\
                <option value="0">-- Rates --</option>';
    var selectedCheck = false;
    var selected = '';
    for(var checkDate in GLOBAL_RATES_BY_DATE){
        var item = GLOBAL_RATES_BY_DATE[checkDate];
        if (date == checkDate) {
            for (var k in item) {
                var rate = item[k];
                if (!selectedCheck && id == rate.id) {
                    selected = 'selected';
                    selectedCheck = true;
                } else {
                    selected = '';
                }
                html += '<option value="'+rate.id+'" date-price="' + rate.price + '" '+ selected + '>'+rate.rate_name+'</option>';
            }
        }
    }
    html += '</select>';
    return html;
}

function changeAccField(obj, isApartment, apartmentNum)
{
    var accAmount = obj.value;
    var trObj = $(obj).closest('tr');
    if(isApartment){
        $( ".tax" + apartmentNum ).each(function() {
            changeTaxField(this, apartmentNum);
        });
    }

    calculateSum();
}

function changeTaxField(obj, apartmentNum) {
    var trObj = $(obj).closest('tr');
    var taxType = trObj.find('.tax-field').attr('data-tax-type');
    var taxValue = trObj.find('.tax-field').val();
    var apartmentPrice = 0;
    var accAmaount = $('.apartment'+apartmentNum).val();
    if (taxType == taxTypePercent) {
        apartmentPrice = parseFloat(accAmaount)*parseFloat(taxValue)/100;
    } else {
        return;
    }

    apartmentPrice = precise_round(apartmentPrice);
    trObj.find('.acc_amount').val(apartmentPrice);
    trObj.find('.taxApartment').html(apartmentPrice);

    calculateSum();
}

function changeDirection(obj) {
    var selectedValue = obj.value;
    var parentTr = $(obj).closest('tr');
    var nextAll = parentTr.nextAll();

    nextAll.each(function(index){
        $(this).find('.money_direction').val(selectedValue);
    });
}

function changeAddonsValueAmount(obj)
{
    var $parentTr = $(obj).closest('tr');
    var $field;
    var additional = $parentTr.hasClass('tax-additional');
    if ($parentTr.find('[name="addonstype[]"]').length > 0) {
        var $nextAll = $parentTr.nextAll();
        var  addonstype= $parentTr.find('[name="addonstype[]"]').val();
        if (addonstype != 1) {
            var selectedValue = $(obj).val();
            $nextAll.each(function(index) {
                if (
                    $(this).find('[name="addonstype[]"]').length > 0
                    &&
                    (
                        ($(this).hasClass('tax-additional') && additional)
                        ||
                        (!$(this).hasClass('tax-additional') && !additional)
                    )
                ) {
                    if ($(this).find('[name="addonstype[]"]').val() == addonstype) {
                        $(this).find('input[name="addons_value[]"]').val(selectedValue);
                        $field = $(this).find('input[name="addons_value[]"]');
                        changeTaxField($field, $field.data('apartment-num'));
                    }
                }
            });
        }
        calculateSum();
        }

}

$(document).on('keyup','input[name="addons_value[]"]', function() {
    changeAddonsValueAmount(this);
});
