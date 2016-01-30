$(function () {
    var $scheduleTable = $('#schedule-table');

    $('.timepicker').datetimepicker({
        datepicker: false,
        format: 'H:i'
    });

    if (jQuery().daterangepicker) {
        var $dateRangePickerOptions = {
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'This Week': [moment().startOf('week'), moment().endOf('week')],
                    'Next Week': [moment().add(1, 'week').startOf('week'), moment().add(1, 'week').endOf('week')],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Next Month': [moment().add(1, 'month').startOf('month'), moment().add(1, 'month').endOf('month')]
                },
                startDate: moment().startOf('week'),
                endDate: moment().endOf('week'),
                format: 'll',
                minDate: moment().subtract(2, 'month'),
                maxDate: moment().add(2, 'month')
            };

        $('#date-range').daterangepicker(
            $dateRangePickerOptions
        );
    }

    $('#show-btn').click(function(e) {
        e.preventDefault();
        showSchedule();
    });

    $('.schedule-day-toggler').bootstrapToggle({
        onstyle  : 'success',
        offstyle : 'danger',
        size     : 'small'
    });

    $scheduleTable.delegate('td.editable', 'click', function (e) {
        e.preventDefault();
        var $modal = $('#change-modal');

        if (0 < parseFloat($(this).attr('data-availability'))) {
            $modal.find('.schedule-day-toggler').bootstrapToggle('on');
        } else {
            $modal.find('.schedule-day-toggler').bootstrapToggle('off');
        }

        $modal.find('#change-day').text($(this).attr('data-day'));
        $modal.find('#change-user').text($(this).attr('data-user'));
        $modal.find('#time_from1').val($(this).attr('data-from1'));
        $modal.find('#time_to1').val($(this).attr('data-to1'));
        $modal.find('#time_from2').val($(this).attr('data-from2'));
        $modal.find('#time_to2').val($(this).attr('data-to2'));
        $modal.find('#schedule-day-id').val($(this).attr('data-id'));
        $modal.find('#schedule-next-day-id').val($(this).attr('data-next-id'));
        $modal.find('#note').val($(this).attr('data-note'));

        $('#color-id').val($(this).attr('data-color-id'));
        $modal.find('#color-' + $(this).attr('data-color-id')).css('border', '3px dashed #333');

        $('.color-box').on('click', function (e) {
            e.preventDefault();

            $('.color-box').css('border', '1px solid #CCC');
            $('#' + this.id).css('border', '3px dashed #333');

            $('#color-id').val(this.id.substring(6));
        });

        $('#schedule-modal-close').on('click', function (e) {
            e.preventDefault();
            $('.color-box').css('border', '1px solid #CCC');
        });

        var officeSelectize = $('#office-id').selectize();
        officeSelectize[0].selectize.addItem($(this).attr('data-office-id'));

        $modal.modal('show');
    });

    $('#save-schedule-day-btn').click(function (e) {
        e.preventDefault();
        var $modal = $(this).closest('.modal');
        var btn = $(this);
        var data;

        btn.button('loading');

        data = {
            day: $modal.find('#change-day').text(),
            from1: $modal.find('#time_from1').val(),
            from2: $modal.find('#time_from2').val(),
            to1: $modal.find('#time_to1').val(),
            to2: $modal.find('#time_to2').val(),
            id: $modal.find('#schedule-day-id').val(),
            next_id: $modal.find('#schedule-next-day-id').val(),
            office: $modal.find('#office-id').val(),
            availability: $modal.find('.schedule-day-toggler').prop('checked') ? 1 : 0,
            color_id: $('#color-id').val(),
            note: $('#note').val()
        };

        if (data.availability && (!data.from1 || !data.to1)) {
            btn.button('reset');
            notification({
                status: 'error',
                msg: 'When marking day as working, at least first time-range should be specified.'
            });
            return;
        }

        if ($('#time_from2').val() != '' && $('#time_to1').val() > $('#time_from2').val()) {
            notification({
                status: 'error',
                msg: '"Date To" in first line cannot be more than "Date From" on second'
            });

            btn.button('reset');
            return;
        }

        $.ajax({
            type: "POST",
            url: UPDATE_URL,
            data: data,
            dataType: "json",
            success: function (msg) {
                if (msg.status == 'success') {
                    $modal.modal('hide');
                    $('#show-btn').click();

                    $('.color-box').css('border', '1px solid #CCC');
                }
                notification(msg);
                btn.button('reset');
            }
        });
    });

    $scheduleTable.delegate('td .timebox-working, td .timebox-partday', 'mouseenter mouseleave', function() {
        var $cell = $(this).closest('td');
        var from1 = $cell.attr('data-from1');
        var from2 = $cell.attr('data-from2');
        var day   = $cell.attr('data-day');

        $('td[data-day="' + day + '"][data-from1="' + from1 + '"][data-from2="' + from2 + '"] .schedule-timebox').toggleClass('active');
    });

    $('#team_id').selectize({
        render: {
            option: function(item, escape) {
                if (item.value === 'disabled') {
                    return '<div style="pointer-events: none; color: #aaa;">' + escape(item.text) + '</div>';
                }

                return '<div>' + escape(item.text) + '</div>';
            }
        }
    });

    $('#clear-btn').on('click', function (e) {
        e.preventDefault();

        var teamSelectize           = $('#team_id').selectize();
        var officeSelectize         = $('#office_id').selectize();
        var scheduleTypeSelectize   = $('#schedule_type_id').selectize();

        teamSelectize[0].selectize.clear();
        officeSelectize[0].selectize.clear();
        scheduleTypeSelectize[0].selectize.clear();
    });
});

function showSchedule(date) {
    var btn = $('#show-btn');

    var data = {
        team_id: $('#team_id').val(),
        city_id: $('#city_id').val(),
        schedule_type_id: $('#schedule_type_id').val(),
        office_id: $('#office_id').val(),
        date_range: $('#date-range').val(),
        date_sort: date
    };

    if (
        data.team_id == ''
        &&
        data.city_id == ''
        &&
        data.schedule_type_id == ''
        &&
        data.date_range == ''
        &&
        data.office_id == ''
    ) {
        notification({
            status: 'warning',
            msg: 'Please fill at least one of the filtering fields.'
        });

        return false;
    }

    btn.button('loading');

    $.ajax({
        type: "POST",
        url: SEARCH_URL,
        data: data,
        dataType: "json",
        success: function(data) {
            if (data.status == 'success') {
                $('#schedule-table').html(data.tableContent);

                $('#schedule-table-container').addClass('scrollable');
                // callback for popover
                $('*[data-toggle="popover"]').popover({
                    delay: {
                        show: 300,
                        hide: 150
                    },
                    trigger: "hover",
                    html: true
                });

                $('html, body').animate({
                    scrollTop: $("#schedule-table-container").offset().top - 50
                }, 500);
            } else {
                notification(data);
            }

            btn.button('reset');
        }
    });
}