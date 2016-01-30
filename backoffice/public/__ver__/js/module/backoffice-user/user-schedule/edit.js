var timepickerMapping = [
    'time_from1', 'time_to1', 'time_from2', 'time_to2'
];

$(function () {
    var $scheduleItems = $('#schedule-items');

    $scheduleItems.find('.timepicker').datetimepicker({
        datepicker: false,
        format: 'H:i'
    });

    $scheduleItems.find('.schedule-day-toggler').bootstrapToggle({
        onstyle  : 'success',
        offstyle : 'danger',
        size     : 'small'
    });

    $('#apply-from').daterangepicker(
        {
            'singleDatePicker': true,
            'minDate': moment().subtract(1, 'months'),
            'format': globalDateFormat
        }
    );

    $scheduleItems.delegate('.add-time-interval-btn', 'click', function(e) {
        e.preventDefault();

        var $self = $(this);
        var $newTimeInterval = $('#time-interval-sample').children('.row').clone();
        var $timeIntervals = $(this).closest('.schedule-item').find('.time-intervals');

        $newTimeInterval.find('.timepicker').datetimepicker({
            datepicker: false,
            format: 'H:i'
        });

        $newTimeInterval.appendTo($timeIntervals).hide().show('slideDown');
        $self.closest('.row').hide('slideDown');
        $timeIntervals.find('.btn').removeClass('disabled');
    });

    $scheduleItems.delegate('.remove-time-interval', 'click', function(e) {
        e.preventDefault();

        var $timeIntervals = $(this).closest('.schedule-item').find('.time-intervals');

        if ($timeIntervals.children().length == 2) {
            $(this).closest('.row').hide('slideUp', function () {
                $(this).remove();
            });

            $timeIntervals.next().show('slideUp');
            $timeIntervals.find('.btn').addClass('disabled');
        }
    });

    $('#period-length').change(function() {
        var period = Math.min(parseInt($(this).val()), 30);

        applyPeriod(period);
    });

    $scheduleItems.delegate('.schedule-day-toggler', 'change', function() {
        var $scheduleItem = $(this).closest('.schedule-item');
        var $removeIntervalBtns = $scheduleItem.find('.remove-time-interval');
        var $addIntervalBtn     = $scheduleItem.find('.add-time-interval-btn');
        var $timepickers        = $scheduleItem.find('.timepicker');
        var $disableLayer       = $scheduleItem.find('.schedule-item-disable-layer');
        if ($(this).prop('checked')) {
            if ( 1 < $removeIntervalBtns.length ) {
                $removeIntervalBtns.toggleClass('disabled', false);
            }
            $addIntervalBtn.toggleClass('disabled', false);
            $timepickers.prop('disabled', false);
            $disableLayer.fadeOut();
        } else {
            $removeIntervalBtns.toggleClass('disabled', true);
            $addIntervalBtn.toggleClass('disabled', true);
            $timepickers.prop('disabled', true);
            $disableLayer.show().fadeTo( "normal", 0.5 );
        }
    });

    $('#save-schedule').click(function(e) {
        e.preventDefault();

        var applyFromDate = $('#apply-from').val(),
            todayDate = $('#apply-from').attr('today-value'),
            todayDateObj = new Date(todayDate),
            applyFromDateObj = new Date(applyFromDate);

        if (applyFromDateObj < moment().subtract(1, 'months')) {
            notification({
                msg: 'Chosen apply date is too old, please choose a newer one.',
                status: 'error'
            });
        } else if (applyFromDateObj < todayDateObj) {
            $('#schedule-alert-date').html(applyFromDate);
            $('#applyScheduleAlert').modal();
        } else {
            saveSchedule();
        }
    });

    $('#applyScheduleConfidently').click(function (e) {
        e.preventDefault();

        saveSchedule();
        $('#applyScheduleAlert').modal('hide');
    })
});

function saveSchedule() {
    var btn = $('#save-schedule');
    var saveData = {
        office_id: $('#reporting_office_id').val(),
        user_id: $('#schedule-user-id').val(),
        schedule_type : $('#schedule-type').val(),
        schedule_start: $('#apply-from').val(),
        days: {}
    };

    $('#save-schedule').button('loading');

    $('#schedule-items').find('.schedule-item').each(function(index) {
        var day = index + 1;
        saveData.days[day] = {
            active: $(this).find('.schedule-day-toggler').prop('checked') ? 1 : 0
        };

        $(this).find('.timepicker').each(function(index) {
            // index % 2 means that the field is "Time To". In case it's set to "00:00", save 24:00 instead
            if (index % 2 && '00:00' == $(this).val()) {
                saveData.days[day][timepickerMapping[index]] = '24:00';
            } else {
                saveData.days[day][timepickerMapping[index]] = $(this).val();
            }
        })
    });

    $.ajax({
        url: SAVE_SCHEDULE,
        type: "POST",
        data: saveData,
        dataType: "json",
        success: function(data) {
            notification(data);
            btn.button('reset');
        }
    });
}

function applyPeriod(period) {
    var $scheduleItems = $('#schedule-items');

    if ( 0 >= period ) {
        return false;
    }

    var currentPeriod = $scheduleItems.children().length;
    var periodDifference = period - currentPeriod;

    if ( 0 < periodDifference ) {
        for ( var i = 1; i <= periodDifference; i++ ) {
            addScheduleDate(currentPeriod + i);
        }
    } else if ( 0 > periodDifference ) {
        // Remove last abs(periodDifference) days
        $scheduleItems
            .find('.schedule-item-container:nth-last-child(-n+' + (-periodDifference) + ')')
                .hide('slide', { direction: 'left' }, function() {
                    $(this).remove();
                });
    } else {
        return false;
    }
}

function addScheduleDate(day) {
    var $newScheduleDay = $('#day-schedule-sample').children('.schedule-item-container').clone();

    $newScheduleDay.find('.schedule-day-toggler').bootstrapToggle({
        onstyle  : 'success',
        offstyle : 'danger',
        size     : 'small'
    });
    $newScheduleDay.find('.day-text').text('Day ' + day);
    $newScheduleDay.hide().appendTo('#schedule-items').show('slide', { direction: 'left' });
    $newScheduleDay.find('.timepicker').datetimepicker({
        datepicker: false,
        format: 'H:i'
    });
}