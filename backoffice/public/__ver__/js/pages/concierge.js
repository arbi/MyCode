$(function () {
    $('#switch-view .btn').on('click', function (e) {
        e.preventDefault();

        $('#switch-view .btn').removeClass('active');
        $(this).addClass('active');

        var other_day_panels = ['#arrivalsYesterday', '#arrivalsTomorrow', '#checkoutsYesterday', '#checkoutsTomorrow'];

        switch ($(this).attr('data-view')) {
            case 'all':
                $.each(other_day_panels, function (index, panelBody) {
                    $(panelBody).parent().show();
                });
                break;
            case 'today':
                $.each(other_day_panels, function (index, panelBody) {
                    $(panelBody).parent().hide();
                });
        }

        localStorage.setItem('concierge_item_viewType', $(this).attr('data-view'));
    });

    if (localStorage.getItem('concierge_item_viewType') == 'today') {
        $('#switch-view .btn:last-child').click();
    }

    if (typeof collapsed != 'undefined') {
        panelCollapse();
    }

    $("#user").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: GLOBAL_USER_AUTOCOMPLATE,
                data: {
                    txt: $("#user").val(),
                    user_id: $("#user_id").val()
                },
                dataType: "json",
                type: "POST",
                success: function (data) {
                    var obj = [];
                    if (data && data.rc == '00') {
                        for (var row in data.result) {
                            var item = data.result[row],
                                    new_obj = {};

                            new_obj.value = item.name;
                            new_obj.id = item.id;
                            obj.push(new_obj);
                        }
                    }

                    response(obj);
                }
            });
        },
        max: 10,
        minLength: 1,
        autoFocus: true,
        select: function (event, ui) {
            if (ui.item) {
                $('#user_id').val(ui.item.id);
            }
        }
    });

    $('.change-status-button').click(function () {
        var $btn = $(this);
        var resId = $btn.attr('data-res-id');
        var status = $btn.attr('data-status');

        var iconClass = '';
        var $tooltipText = '';
        switch (parseInt(status)) {
            case 1: // check in
                iconClass = 'glyphicon glyphicon-log-in text-success';
                $tooltipText = 'Checked-in';

                if (checkInBadEmailList($btn.attr('data-res-email'))) {
                    $('#reservation-id').val(resId);
                    $('#email-modal').modal('show');
                    return;
                }

                break;
            case 2: // check out
                iconClass = 'glyphicon glyphicon-log-out text-success';
                $tooltipText = 'Checked-out';
                break;
            case 4: // no show
                iconClass = 'glyphicon glyphicon-ban-circle text-danger';
                $tooltipText = 'No Show';
                break;
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
                    $btn.closest('td').prev().html('<span id="standing_' + resId + '" class="' + iconClass + '" data-toggle="tooltip" data-original-title="' + $tooltipText + '"></span>');
                    $('#standing_' + resId).tooltip();
                    $btn.closest('.dropdown-menu').find('.change-status-button').parent().remove();
                }
                if (typeof data.status != 'undefined') {
                    notification(data);
                }
            }
        });
    });

    $('#submit-email').click(function (e) {
        var email = $.trim($("#guest-email").val()),
            res_id = $("#reservation-id").val();
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
                        var getElement = $('[data-only-check-in="' + res_id + '"]');
                        getElement.attr('data-res-email', email);
                        getElement.trigger('click');
                    }
                }
            });
        } else {
            $(this).closest('.modal-content').removeClass('has-success').addClass('has-error');
        }
    });
});

function checkInBadEmailList (email) {
    for (var row in GLOBAL_BAD_EMAIL_LIST) {
        var item = GLOBAL_BAD_EMAIL_LIST[row];
        if (email == '' || email == item) {
            return true;
        }
    }
    return false;
}

$('#filter').keyup(function (e) {
    var text = $(this).val().toLowerCase(),
            containers = $("#checkoutsYesterday, #checkoutsToday, #checkoutsTomorrow, #arrivalsYesterday, #arrivalsToday, #arrivalsTomorrow, #currentStays");

    filterTable(containers, text);
});

$('.concierge-row-active').click(function (e) {
    var oTarget = $(e.target);

    if (oTarget.hasClass('btn') || oTarget.closest('.btn-group').length)
        return;
    var data_toggle = $(this).attr('data-toggle');
    var row_comment_id = $(this).attr('data-row-id') + '-comment';
    if (data_toggle == 'toggled') {
        $(this).attr('data-toggle', 'not-toggled');
        $('#' + row_comment_id).hide('fast');
    } else {
        $(this).attr('data-toggle', 'toggled');
        $('#' + row_comment_id).show('fast');
    }

    var row_id = $(this).attr('data-row-id');
});

$(".add-comment-btn").click(function (e) {
    $("#comment-field").val('');
    $("#res-id").val($(this).attr("data-res-id"));
    $("#comment-modal .modal-title").html($(this).attr("data-guest"));

    setInterval("focus_comment();", 500);

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
                if (data.status == 'error') {
                    notification(data);
                } else {
                    location.reload();
                }
            }
        });
    } else {
        $(this).closest('.modal-content').removeClass('has-success').addClass('has-error');
    }
});

$(".panel-heading").click(function () {
    var id = $(this).attr("href").substring(1);
    collapsed[id] = 1 - collapsed[id];

    localStorage.setItem('collapsed_' + id, collapsed[id]);
    var isCollapsed = $(this).find('.my-chevron').hasClass('glyphicon-chevron-down');
    var $chevron = $(this).find('.my-chevron');

    if(!isCollapsed){
        $chevron
            .removeClass('glyphicon-chevron-up')
            .addClass('glyphicon-chevron-down');
    }
    else{
        $chevron
            .removeClass('glyphicon-chevron-down')
            .addClass('glyphicon-chevron-up');
    }
});

function focus_comment() {
    $("#comment-field").focus();
}

function filterTable(containers, text) {
    if (text != '') {
        $(".concierge-comment-row").hide();
        containers.each(function (container) {
            var has_records = 0;
            $(this).find(".filterable").each(function (row) {
                if ($(this).text().toLowerCase().indexOf(text) == -1) {
                    $(this).hide();
                } else {
                    has_records = 1;
                    $(this).show();
                }
            });
            if (has_records) {
                $(this).addClass("in");
                $(this).css("height", "auto");
            } else {
                $(this).removeClass("in");
                $(this).css("height", "0");
            }
        });
    } else {
        containers.find(".filterable").show();
        panelCollapse();
    }
}

function panelCollapse() {
    var collapsed_default = {
        currentStays: 0,
        arrivalsYesterday: 1,
        arrivalsToday: 0,
        arrivalsTomorrow: 1,
        checkoutsYesterday: 1,
        checkoutsToday: 0,
        checkoutsTomorrow: 1
    };
    $.each(collapsed_default, function (panel, val) {
        collapsed[panel] =
                (localStorage.getItem('collapsed_' + panel) != "NaN")
                ? localStorage.getItem('collapsed_' + panel)
                : val;

        $chevron = $("#" + panel).parent().find('.my-chevron');
        if (collapsed[panel] == "0") {
            $("#" + panel).addClass("in");
            $("#" + panel).css("height", "auto");
            $chevron
                .removeClass('glyphicon-chevron-down')
                .addClass('glyphicon-chevron-up');
        } else {
            $("#" + panel).removeClass("in");
            $("#" + panel).css("height", "0");
            $chevron
                .removeClass('glyphicon-chevron-up')
                .addClass('glyphicon-chevron-down');
        }
    });
}

if ($(".generate_new_link").length > 0) {
    $(".generate_new_link").click(function (e) {
        $("#reservation-id").val($(this).attr("data-res-id"));
        $('#genereteNewLinkModal').modal();
    });
}

function generete_new_link(num) {
    $('.genereteNewLink').hide();

    var btn = $('#generete_nl'),
            reservationId = $('#reservation-id').val();

    btn.button('loading');

    $.ajax({
        type: "POST",
        url: GLOBAL_GENERATE_PAGE,
        data: {
            id: reservationId,
            num: num
        },
        dataType: "json",
        success: function (data) {
            $('#genereteNewLinkModal').modal('hide');

            if (data['success']) {
                location.reload();
            } else {
                $('.genereteNewLink').show();
                notification(data);
            }

            btn.button('reset');
        }
    });
}

if ($(".generate_link_reset").length > 0) {
    $(".generate_link_reset").click(function (e) {
        var btn = $('#generate_link_reset'),
                reservationId = $(this).attr("data-res-id");

        btn.button('loading');

        $.ajax({
            type: "POST",
            url: GLOBAL_GENERATE_RESET,
            data: {
                id: reservationId
            },
            dataType: "json",
            success: function (data) {
                location.reload();
            }
        });
    });
}
