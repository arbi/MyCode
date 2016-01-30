var cardReservation = '1';
var cardApartment   = '2';
var cardBuilding    = '3';
var superSearchSelectize;
var checkTaskType;
$(function() {
    var $cards = $('#cards');

    superSearchSelectize = $('#super-search').selectize({
        valueField: 'id',
        labelField: 'text',
        searchField: ['text', 'info'],
        sortField: [
            {
                field: 'type'
            }
        ],
        render: {
            option: function(option, escape) {
                var label;
                switch (escape(option.label)) {
                    case 'apartment-card':
                        label = '<span class="label label-success">Apartment</span>';
                        break;
                    case 'reservation-card':
                        label = '<span class="label label-primary">Reservation</span>';
                        break;
                    case 'building-card':
                        label = '<span class="label label-info">Building</span>';
                        break;
                }
                return '<div>'
                + label
                + '<span> ' + escape(option.text) + ' </span>'
                + '<small class="text-muted">' + escape(option.info) + '</small>'
                + '</div>'
            },
            item: function(option, escape) {
                var label;
                switch (escape(option.label)) {
                    case 'apartment-card':
                        label = '<span class="label label-success">Apartment</span>';
                        break;
                    case 'reservation-card':
                        label = '<span class="label label-primary">Reservation</span>';
                        break;
                    case 'building-card':
                        label = '<span class="label label-info">Building</span>';
                        break;
                }
                return '<div>'
                + label
                + '<span> ' + escape(option.text) + ' </span>'
                + '<small class="text-muted">' + escape(option.info) + '</small>'
                + '</div>'
            }
        },
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            $.ajax({
                url: GLOBAL_CARD_SEARCH,
                type: 'POST',
                dataType: 'json',
                data: {
                    query: query
                },
                error: function() {
                    callback();
                },
                success: function(res) {
                    if (res.status == 'error') {
                        notification(res);
                    } else {
                        callback(res);
                        $('#super-search')[0].selectize.refreshOptions();
                    }
                }
            });
        },
        onChange: function(value) {
            if (value) {
                var item = value.split('_');
                var type = item[0];
                var id = item[1];
                showTheCard(type, id);
            }
            superSearchSelectize[0].selectize.clear();
        }
    });

    $cards.delegate('.collapse-trigger', 'click', function(event) {
        event.preventDefault();
        var $self = $(this);

        if ($self.hasClass('collapsed')) {
            $('#building-apartments tr').show();
            $self.removeClass('collapsed');
            $self.text('Show less');
        } else {
            hideArtmentTrsAfterLimit();
            $self.addClass('collapsed');
            $self.text('Show more');
        }
    });

    $cards.delegate('#search-apartment', 'keyup', function() {
        var search = $(this).val().toLowerCase();
        $.ajax({
            type: "POST",
            url: GLOBAL_GET_APARTMENTS,
            data: {search: search, apartment_group_id: $('#building-id').val()},
            dataType: "json",
            success: function (data) {
                if (data.status == 'success') {
                    $('#apartments-coming-via-ajax').html(data.html);
                } else {
                    notification(data);
                }

            }
        });

    });


    $cards.delegate('.card-entity-link', 'click', function(e) {
        var type = $(this).attr('data-entity-type');
        var id = $(this).attr('data-entity-id');
        if (id != '0') {
            e.preventDefault();
            superSearchSelectize[0].selectize.clear();
            showTheCard(type, id);
        }
    });


    $('#submit-comment').click(function (e) {
        var txt = $.trim($("#comment-field").val()),
            res_id = $("#res-id").val();
        if (txt != '' && res_id > 0) {
            $(this).closest('.modal-content').removeClass('has-error').addClass('has-success');
            $.ajax({
                type: "POST",
                url: GLOBAL_SEND_COMMENT,
                data: {txt: txt, res_id: res_id},
                dataType: "json",
                success: function (data) {
                    if (data.status == 'success') {
                        $('#comment-modal').modal('hide');
                        showTheCard(cardReservation, res_id);
                    }
                    notification(data);
                }
            });
        } else {
            $(this).closest('.modal-content').removeClass('has-success').addClass('has-error');
        }
    });


    $('#submit-task').click(function (e) {
        var task_name = $.trim($("#task-name").val()),
            task_type = $("#task-type").val()
            taskDate = $('#task-due-date').val();
        if (task_name != '' && task_type > 0) {
            $(this).closest('.modal-content').removeClass('has-error').addClass('has-success');
            var data = getTaskData();
            data.task_name = task_name;
            data.task_type = task_type;
            data.check_task_type = checkTaskType;
            if (taskDate) {
                data.end_date = taskDate;
            }
            $.ajax({
                type: "POST",
                url: GLOBAL_QUICK_CREATE_TASK,
                data: data,
                dataType: "json",
                success: function (data) {
                    if (data.status == 'error') {
                        notification(data);
                    } else {
                        var entityType = $('#tasks-entity-type').val(), itemId;
                        $('#quick-task-modal').modal('hide');
                        switch (entityType) {
                            case cardReservation:
                                itemId = $('#res-id').val();
                                break;
                            case cardApartment:
                                itemId = $('#apartment-id').val();
                                break;
                            case cardBuilding:
                                itemId = $('#building-id').val();
                                break;
                        }
                        $('.hand-task-button').button('reset');
                        showTheCard(entityType, itemId);
                    }
                }
            });
        } else {
            $(this).closest('.modal-content').removeClass('has-success').addClass('has-error');
        }
    });



});

function hideArtmentTrsAfterLimit()
{
    var i = 0;
    $('#building-apartments tr').each(function(){
        if (++i > GLOBAL_LIMIT_APARTMENT_SHOW_COUNT_BUILDING_CARD) {
            $(this).hide();
        }
    });
}

$(document).on('click','.related-task-button',function() {
    var url;
    var data = getTaskData();
    url = GLOBAL_CREATE_TASK + (data ? '?' + $.param(data) : '');

    var win = window.open(url, '_blank');
    if(win){
        //Browser has allowed it to be opened
        win.focus();
    }else{
        //Browser has blocked it
        alert('Please allow popups for this site');
    }
});

$(document).on('click',".quick-task-button",function (e) {
    $('#quick-task-modal').modal('show');
    $('#task-name').val('');
    $('#task-type').val(0);
    $('#task-due-date').val('');
    checkTaskType = $(this).attr('data-status');
    setTimeout(function(){ $("#task-name").focus(); }, 500);
});

$(document).on('click','#submit-email',function (e) {
    var email = $.trim($("#guest-email").val()),
        res_id = $("#res-id").val();
    if (email != '' && validateEmail(email) && res_id > 0 && !checkInBadEmailList(email)) {
        $(this).closest('.modal-content').removeClass('has-error').addClass('has-success');
        $.ajax({
            type: "POST",
            url: GLOBAL_SAVE_EMAIL,
            data: {email: email, res_id: res_id},
            dataType: "json",
            success: function (data) {
                if (data.status == 'error') {
                    notification(data);
                } else {
                    notification(data);
                    $('#email-modal').modal('hide');
                    $('#res-email').val(email);
                    $('#reservation-actions .check-in-button').trigger('click');
                }
            }
        });
    } else {
        $(this).closest('.modal-content').removeClass('has-success').addClass('has-error');
    }
});

$(document).on('click', '.change-status-button', function () {
    var $btn = $(this);
    var resId = $('#res-id').val();
    var status = $btn.attr('data-status');
    if (checkInBadEmailList($('#res-email').val())) {
        $('#email-modal').modal('show');
        return;
    }
    $btn.button('loading');
    var obj = {status: status, resId: resId};
    $.ajax({
        type: "POST",
        url: GLOBAL_CHANGE_STATUS,
        data: obj,
        dataType: "json",
        success: function (data) {
            if (data.status == 'success') {
                showTheCard(cardReservation, resId);
            }
            if (typeof data.status != 'undefined') {
                notification(data);
            }
        }
    });
});

$(document).on('click', ".hand-task-button", function (e) {
    var dataStatus = $(this).attr('data-status');
    var dateTo = $('#date-to').text();
    var date = new Date(dateTo);
    var month;
    checkTaskType = dataStatus;
    if (dataStatus == GLOBAL_TASK_TYPE_STATUS_FOB) {
        $('#task-name').val(GLOBAL_TASK_TITLE_FOR_FOB);
    } else {
        $('#task-name').val(GLOBAL_TASK_TITLE_FOR_KEY);
    }

    //in javascript months are counted from 0 to 11, so we need to increment it
    month = date.getMonth() - (-1);
    month = (month < 10) ? ('0' + month) : month;

    dateTo = date.getFullYear() + '-' + month + '-' + date.getDate() + ' 12:00';

    $('#task-type').val(GLOBAL_TASK_FOB_KEY_TYPE);
    $('#task-due-date').val(dateTo);
    $(this).button('loading');
    $('#submit-task').trigger('click');
});

$(document).on('click', ".add-comment-btn", function (e) {
    $("#comment-field").val('');
    $("#comment-modal .modal-title").html($(this).attr("data-guest"));
    setTimeout(function(){ $("#comment-field").focus(); }, 500);

});

function getTaskData () {
    var data = {};
    var entityType = $('#tasks-entity-type').val();
    var $resApartment = $('#res-apartment-assigned');
    var $resBuilding = $('#res-building');
    var $apartmentBuilding = $('#apartment-building');
    var $apartmentCurrRes = $('#apartment-cur-res-num');

    switch (entityType) {
        case cardReservation:
            data = {
                res_number: $.trim($('#res-number').text()),
                res_id: $('#res-id').val(),
                apartment_id: $resApartment.attr('data-entity-id'),
                apartment_name: $.trim($resApartment.text()),
                building_id: $resBuilding.attr('data-entity-id'),
                building_name: $.trim($resBuilding.text())
            };
            break;
        case cardApartment:
            data = {
                apartment_id: $('#apartment-id').val(),
                apartment_name: $.trim($('#apartment-name').text()),
                building_id: $apartmentBuilding.attr('data-entity-id'),
                building_name: $.trim($apartmentBuilding.text()),
                res_number: $.trim($apartmentCurrRes.text()),
                res_id: $apartmentCurrRes.attr('data-entity-id')
            };
            break;
        case cardBuilding:
            data = {
                building_id: $('#building-id').val(),
                building_name: $.trim($('#building-name').text())
            };
            break;
    }
    return data;
}



function showTheCard(type, id) {
    var $tasks = $('#tasks');
    var $taskList = $tasks.find('#task-list');
    var $cards = $('#cards');
    $taskList.html('');
    $.ajax({
        url: GLOBAL_GET_THE_CARD,
        type: 'POST',
        dataType: 'json',
        data: {
            type: type,
            id: id
        },
        error: function() {
            notification({
                status: 'error',
                msg: 'Failed to retrieve the card. Please try again.'
            })
        },
        success: function(result) {
            if (result.status == 'error') {
                notification(result)
            } else {
                $cards.hide();
                $tasks.hide();
                $cards.html(result.cardsPartial);
                $cards.fadeIn();
                if (type != GLOBAL_CARD_TYPE_BUILDING) {
                    $tasks.html(result.tasksPartial);
                    $tasks.fadeIn();
                }
            }
        }
    });
}


function checkInBadEmailList (email) {
    for (var row in GLOBAL_BAD_EMAIL_LIST) {
        var item = GLOBAL_BAD_EMAIL_LIST[row];
        if (email == '' || email == item) {
            return true;
        }
    }
    return false;
}

function markTaskDone(elem, e) {
    e.preventDefault();

    var taskId = $(elem).attr('data-task-id'),
        valueOnClick = $(elem).attr('onclick'),
        valueHref = $(elem).attr('onclick'),
        valueChildColor = '#337AB7';

    $(elem)
        .css('cursor', 'wait')
        .removeAttr('onclick')
        .removeAttr('href')
            .children()
            .css('color', '#999');

    $.ajax({
        url: GLOBAL_TASK_MARK_DONE,
        type: 'POST',
        dataType: 'json',
        data: {
            taskId: taskId
        },
        success: function(result) {
            if (result.status == 'success') {

                $(elem)
                    .css('cursor', 'not-allowed')
                        .children()
                        .removeClass('glyphicon-unchecked')
                        .addClass('glyphicon-check');

                setTimeout(function() {
                    $(elem).parent().hide('slow');
                }, 1000);
            } else {
                $(elem)
                    .css('cursor', 'pointer')
                    .attr('onclick', valueOnClick)
                    .attr('href', valueHref)
                        .children()
                        .css('color', valueChildColor);
            }

            notification(result);
        }
    });
}

$(document).on('click', "#generate-ccca-page", function (e) {
    $('#sendCccaModal').modal();
});

function generateCccaPage() {
    $('.generateAndSendCccaForm').hide();
    var btn = $('#reservation_action_send_ccca_confirm');
    btn.button('loading');
    var reservationId = $('#res-id').val();
    var ccId = $('#send_ccca_cc_id').val();
    var amount = $('#amount-for-ccca').val();
    $.ajax({
        type: "POST",
        url: GLOBAL_GENERATE_CCCA_PAGE,
        data: {
            reservation_id:reservationId,
            cc_id:ccId,
            amount: amount
        },
        dataType: "json",
        success: function (data) {
            if (data.status == 'success') {
                $('#sendCccaModal').modal('hide');
                
                showTheCard(cardReservation, reservationId);
            }
            if (typeof data.status != 'undefined') {
                btn.button('reset');
                $('.generateAndSendCccaForm').show();
                notification(data);
            }
        }
    });
}