// set currency code from addons
$(document).on('change', '#currencyId', setCurrencyCode);
$(document).ready(function() {

    // Javascript to enable link to tab
    var url = document.location.toString();
    if (url.match('#')) {
        $('.nav-tabs a[href=#'+url.split('#')[1]+']').tab('show') ;
    }

    // Change hash for page-reload
    $('.nav-tabs a').on('shown.bs.tab', function (e) {
        window.location.hash = e.target.hash;
    });

    setCurrencyCode();

    if (!GLOBAL_IS_VENUE_MANAGER) {
        $('input').closest('.form-control').attr("disabled", true);
        $('select').closest('.form-control').attr("disabled", true);
    }
    var validationRules = {
        name: {
            required: true
        },
        cityId: {
            required: true,
            min: 1,
            number: true
        },
        currencyId: {
            required: true,
            min: 1,
            number: true
        },
        account_id: {
            required: true,
        },
        thresholdPrice: {
            required: false,
            number: true
        },
        discountPrice: {
            required: false,
            number: true
        },
        perdayMinPrice: {
            required: false,
            number: true
        },
        perdayMaxPrice: {
            required: false,
            number: true
        }
    };
    if (GLOBAL_VENUE_ID == 0) {
        validationRules.type = {
            required: true,
            min: 1,
            number: true
        }
    }
    $('#venue-form').validate({
        ignore: [],
        rules: validationRules,
        highlight: function (element, errorClass, validClass) {
            $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function(error, element) {
        }
    });

    // Supplier
    var $account = $('#account_id');

    $account.selectize({
        valueField: 'unique_id',
        labelField: 'name',
        searchField: ['name'],
        render: {
            option: function(item, escape) {
                // Account type definition: 3 - affiliate, 4 - supplier, 5 - people
                var label = (item.type == 3 ? 'primary' : (item.type == 4 ? 'warning' : 'success'));

                return '<div>'
                    + '<span class="label label-' + label + '">' + escape(item.label) + '</span> '
                    + escape(item.name)
                    + '</div>';
            },
            item: function(item, escape) {
                return '<div data-name="' + escape(item.name)
                        + '" data-type="' + escape(item.type)
                        + '" data-id="' + escape(item.account_id)
                        + '">'
                    + '<span class="label label-primary">' + escape(item.label) + '</span> '
                    + escape(item.name)
                    + '</div>';
            }
        },
        load: function(query, callback) {
            if (query.length < 2) {
                return callback();
            }

            $.ajax({
                url: GLOBAL_VENUE_CHARGE_GET_SUPPLIERS_URI,
                type: 'POST',
                data: {'q': encodeURIComponent(query)},
                error: function() {
                    callback();
                },
                success: function(res) {
                    callback(res.data);
                }
            });
        },
        persist: false,
        hideSelected: true,
        highlight: false
    });

    var uniqueId = $account.attr('data-unique-id'),
        accountId = $account.attr('data-account-id'),
        accountName = $account.attr('data-account-name'),
        accountType = $account.attr('data-account-type');

    if (accountId && accountName && accountType) {
        var label = accountType == 1 ? 'Partner' : (accountType == 2 ? 'Supplier' : 'People');

        $account[0].selectize.addOption({
            unique_id: uniqueId,
            account_id: accountId,
            type: accountType,
            name: accountName,
            label: label,
        });
        $account[0].selectize.setValue(uniqueId);
    }

    // City
    var $city = $('#cityId');
    $city.selectize();
    if (GLOBAL_VENUE_ID == 0) {
        $city[0].selectize.clear();
    }

    // Manager
    var $manager = $('#managerId');
    $manager.selectize();
    if (GLOBAL_VENUE_ID == 0 || $manager.attr('data-id') == 0) {
        $manager[0].selectize.clear();
    }

    // Cashier
    var $cashier = $('#cashierId');
    $cashier.selectize();
    if (GLOBAL_VENUE_ID == 0 || $cashier.attr('data-id') == 0) {
        $cashier[0].selectize.clear();
    }

    // Currency
    var $currency = $('#currencyId');
    $currency.selectize();
    if (GLOBAL_VENUE_ID == 0) {
        $currency[0].selectize.clear();
    }

    // Accept Orders
    var $acceptOrders = $('#acceptOrders');
    $acceptOrders.selectize();
    if (GLOBAL_VENUE_ID == 0 || $acceptOrders.attr('data-id') == 0) {
        $acceptOrders[0].selectize.clear();
    }

    if (CHARGES_DATA.length > 0) {
        $('#charges_clean').hide();

        $('#datatable_charges').DataTable({
            bFilter: true,
            bInfo: true,
            bServerSide: false,
            bProcessing: false,
            bPaginate: true,
            bAutoWidth: false,
            bStateSave: false,
            iDisplayLength: 10,
            sAjaxSource: false,
            sPaginationType: "bootstrap",
            aaSorting: [[0, 'desc']],
            aaData: CHARGES_DATA,
            sDom: 'l<"enabled">frti<"bottom"p><"clear">',
            aoColumns: [
                {
                    "name": "status",
                    "width": "60px"
                }, {
                    "name": "order_status",
                    "width": "80px"
                }, {
                    "name": "creator",
                    "width": "150px"
                }, {
                    "name": "charged_user",
                    "width": "150px"
                }, {
                    "name": "description",
                    "sortable": false
                }, {
                    "name": "amount",
                    "width": "100px",
                    "class": "text-right"
                }, {
                    "name": "edit",
                    "sortable": false
                }
            ]
        });
    } else {
        $('#datatable_charges').hide();
        $('#charges_clean').show();
    }

    $("#add-item").click(function() {

        if($('#venue-items-add-form').valid()) {

            var $tr = $(this).closest('tr');

            var $itemTitle          = $tr.find('#add-item-title');
            var $itemDescription    = $tr.find('#add-item-description');
            var $itemPrice          = $tr.find('#add-item-price');
            var $itemAvailability   = $tr.find('#add-item-availability');

            var inputFormTitle = '<div class="input-prepend input-append form-group margin-0" id="title_0">' +
                    '<div class="col-sm-12">' +
                        '<input name="titles[]" type="text" class="form-control venue-item-title" id="title_0" maxlength="50" value="' +
                        $itemTitle.val() +
                        '" data-id="" />' +
                    '</div>' +
                '</div>';

            var inputFormDescription = '<div class="input-prepend input-append form-group margin-0" id="description_0">' +
                '<div class="col-sm-12">' +
                '<input name="descriptions[]" type="text" class="form-control venue-item-description" id="description_0" maxlength="50" value="' +
                $itemDescription.val() +
                '" data-id="" />' +
                '</div>' +
                '</div>';

            var inputFormPrice = '<div class="input-prepend input-append form-group margin-0" id="price_0">' +
                '<div class="col-sm-12">' +
                '<input name="prices[]" type="text" class="form-control venue-item-price" id="price_0" maxlength="50" value="' +
                $itemPrice.val() +
                '" data-id="" />' +
                '</div>' +
                '</div>';

            var av_1 = 'selected';
            var av_2 = '';

            if($itemAvailability.val() == 2) {
                var av_1 = '';
                var av_2 = 'selected';
            }

            var inputFormAvailability = '<div class="input-prepend input-append form-group margin-0" id="availability_0">' +
                '<div class="col-sm-12">' +
                '<select name="availabilities[]" class="form-control venue-item-availability" id="availability_0" value="' +
                $itemAvailability.val() +
                '" data-id="">' +
                    '<option value="1"' + av_1 + '>Available</option>' +
                    '<option value="2"' + av_2 + '>Not Available</option>' +
                '</select>' +
                '</div>' +
                '</div>';

            var html = '<tr class="item_tr">\
                        <td>' + inputFormTitle + '</td>\
                        <td>' + inputFormDescription + '</td>\
                        <td>' + inputFormPrice + '</td>\
                        <td>' + inputFormAvailability + '</td>\
                        <td>\
                            <a href="javascript:void(0)" class="btn btn-sm btn-danger btn-block itemRemoveRow">Delete</a> <input value="0" type="hidden" name="deletedItems[]" class="removeRow"/>\
                        </td>\
                    </tr>';
            $('#items-list').prepend(html);

            $itemTitle.val('');
            $itemDescription.val('');
            $itemPrice.val('');
            $itemAvailability.val(1);

        } else {
            notification({"status": "error", "msg": "Fill required filed"});
        }
    });

    $("#items_table").delegate('.itemRemoveRow', 'click', function () {
        removeItemRow(this);
    });

    $('#venue-items-add-form').validate({
        rules: {
            'titles[]': {
                required: true
            },
            'prices[]': {
                required: true,
                number: true,
            },
            'availabilities[]': {
                required: false
            }
        },
        highlight: function (element, errorClass, validClass) {
            $(element).closest('.form-control').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('.form-control').removeClass('has-error').addClass('has-success');
        },
        success: function (label) {
            $(label).closest('form').find('.valid').removeClass("invalid");
        },
        errorPlacement: function(error, element) {
        }
    });
});

/**
 * Set currency code
 */
function setCurrencyCode() {
    var currencyCode = $('#currencyId option:selected').text();
    var currencyid   = $('#currencyId').val();

    if (currencyid > 0) {
        $('#venue-form .input-group-addon').each(function () {
            $(this).text(currencyCode);
        });
    }
}

/**
 * Save data
 *
 * @param data
 */
function saveData(data) {
    $.ajax({
        type: "POST",
        url: GENERAL_SAVE_VENUE,
        data: data,
        dataType: "json",
        success: function(data) {
            if(data.url != '' && data.status == 'success'){
                window.location.href = data.url;
            } else {
                notification(data);
                $("#venue-form #save_data").button('reset');
            }
        }
    });
}

$(document).on('click', '#save_data', function () {
    $(this).button('loading');

    if($('#venue-form').valid()) {
        var data = $('#venue-form').serializeArray();
        saveData(data);
    } else {
        $(this).button('reset');
    }
});

function saveItems(data) {
    $.ajax({
        type: "POST",
        url: GLOBAL_SAVE_ITEMS,
        data: data,
        dataType: "json",
        success: function(data) {
            if(data.url != '' && data.status == 'success'){
                window.location.reload();
            } else {
                notification(data);
                $("#venue-items-form #save_items").button('reset');
            }
        }
    });
}

$(document).on('click', '#save_items', function () {
    $(this).button('loading');

    var data = $('#venue-items-form').serializeArray();
    saveItems(data);

    $(this).button('reset');
});

function removeItemRow(obj){
    var parent = $(obj).closest('.item_tr');
    $(parent).remove();
}
