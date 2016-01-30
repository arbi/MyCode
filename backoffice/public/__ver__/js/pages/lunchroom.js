window.orderTotalPrice = 0;
window.orderItems = {};
$(function () {
    $('#venue_id').change(function(){
        $('#cart tbody').html('');
        $('.total-item-order').html('');
        window.orderTotalPrice = 0;
        window.orderItems = {};
        $.ajax({
            url: GLOBAL_VENUE_GET_ITEMS,
            type: 'POST',
            data: {venue_id: $('#venue_id').val()},
            error: function () {
                notification({
                    status: 'error',
                    msg: 'ERROR! Something went wrong'
                });
            },
            success: function (data) {
                if (data.status == 'success') {
                    var $tbody = $('#venue-items tbody');
                    $tbody.html('');
                    if (data.items.length) {
                        $('.no-items-message').addClass('hidden');
                        $('#venue-items').removeClass('hidden');
                        $.each(data.items, function(index, value){
                            $tbody.append(
                                '<tr>' +
                                '<td class="item-title">' + value.title + '</td>' +
                                '<td class="item-description">' + value.description + '</td>' +
                                '<td class="item-price">' + value.price + '</td>'+
                                '<td class="text-center">' + '<a href="#" class="btn btn-primary btn-xs add-to-cart" data-id="' + value.id + '"><i class="glyphicon glyphicon-plus"></i></a>' +
                                '</tr>'
                            )
                        })
                    } else {
                        $('.no-items-message').removeClass('hidden');
                        $('#venue-items').addClass('hidden');
                    }

                } else {
                    notification(data);
                }
            }
        });

    });
    $('#venue_id').change();
});

$(document).on('click','.add-to-cart', function(event){
    event.preventDefault();
    var id = $(this).attr('data-id');
    var $tr = $(this).closest('tr');
    var title = $tr.find('.item-title').text();
    var description = $tr.find('.item-description').text();
    var price = $tr.find('.item-price').text();
    addTocart(id, title, price, description);
});

$(document).on('click', '.decrease-quantity', function(event){
    event.preventDefault();
    decreaseParticularGood($(this).attr('data-id'));
});

$(document).on('click', '.remove-from-cart', function(event){
    event.preventDefault();
    removeFromCart($(this).attr('data-id'));
});

$(document).on('click', '.order-items',function(event){
    event.preventDefault();
    var venue_id = $('#venue_id').val();
    var threshold_price = $('#venue_id option:selected').attr('data-threshold-price');
    var discount_price = $('#venue_id option:selected').attr('data-discount-price');
    $.ajax({
        url: GLOBAL_VENUE_ORDER_ITEMS,
        type: 'POST',
        data: {
            amount: window.orderTotalPrice,
            venue_id: venue_id,
            threshold_price: threshold_price,
            discount_price: discount_price,
            detailed_order: window.orderItems
        },
        error: function () {
            notification({
                status: 'error',
                msg: 'ERROR! Something went wrong'
            });
        },
        success: function (data) {
            if (data.status == 'success') {
                window.location = "/";
            } else {
                notification(data);
            }
        }
    });

});

function addTocart(id, title, price, description)
{
    if (typeof window.orderItems[id] == 'undefined') {
        window.orderItems[id] = {
            title: title,
            price: price,
            description: description,
            quantity: 1
        };
    } else {
        window.orderItems[id].quantity++;
    }
    countTotal();
    drawCartTable();
}

function decreaseParticularGood(id)
{
    window.orderItems[id].quantity--;
    countTotal();
    drawCartTable();
}

function removeFromCart(id)
{
    delete (window.orderItems[id]);
    countTotal();
    drawCartTable();
}

function countTotal()
{
    window.orderTotalPrice = 0;
    $.each(window.orderItems, function(index, value) {
        window.orderTotalPrice += value.price * value.quantity;
    });
}

function drawCartTable()
{
    $('#cart tbody').html('');
    $.each(window.orderItems, function(index, value){
        if (value.quantity > 1) {
            var badge = ' <span class="badge">' + value.quantity + '</span>';
            var decreaseQuantity = '<a href="#" class="btn btn-warning btn-xs decrease-quantity margin-right-2" data-id="' + index + '"><i class="glyphicon glyphicon-minus"></i></a>';
        } else {
            var badge = '';
            var decreaseQuantity = '';
        }
        var remove = '<a href="#" class="btn btn-danger btn-xs remove-from-cart" data-id="' + index + '"><i class="glyphicon glyphicon-remove"></i></a>';
        $('#cart tbody').append(
            '<tr>' +
                '<td>' + value.title + badge + '</td>' +
                '<td>' + value.price +  '</td>' +
                '<td class="text-right">' + decreaseQuantity + remove +  '</td>' +
            '</tr>'
        );
    });
    $('.total-item-order').html(
            '<hr>' +
            '<div class="col-xs-3 col-xs-offset-2">Total:</div>' +
            '<div class="col-xs-3 total-number">' + window.orderTotalPrice + '</div>' +
            '<div class="col-xs-3 pull-right">' +
                '<a href="#" class="btn btn-primary order-items">Order</a>' +
            '</div>'
    );
}
