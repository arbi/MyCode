state('save_button', function() {
	var validate = $('#vacationdays-form').validate();

	if ($('#vacationdays-form').valid()) {
		var btn = $('#save_button');
		btn.button('loading');

        if (/^[0-9]{4}-[0-9]{2}-[0-9]{2}\s-\s[0-9]{4}-[0-9]{2}-[0-9]{2}$/.test($("#interval").val())) {
            var parsed = $("#interval").val().split(" - ");
        }

        var start = parsed[0],
            end   = parsed[1];

        $("#from").val(start);
        $("#to").val(end);

		var obj = $('#vacationdays-form').serialize();

		$.ajax({
			type: "POST",
			url: GLOBAL_VAC_SAVE,
			data: obj,
			dataType: "json",
			success: function(data) {
				if (data.status == 'success') {
					window.location.href = GLOBAL_BASE_PATH + 'profile/index';
				} else {
					notification(data);
				}

				btn.button('reset');
			}
		});
	} else {
		validate.focusInvalid();
	}
});

$(function() {

	$('#vacation_type').change(function() {
        //1- vacation, 2- personal, 3- sick, 4-unpaid leave
        if (this.value == 3 && TOTAL_SICK_DAYS > 0) {
            $('div.vacation').hide();
            $('div.sickdays').show();
            $('label.sickdays').show();
            $('label.vacation').hide();
            $( "#total_number" ).rules( "add", {digits: true});
        } else {
            $('div.vacation').show();
            $('div.sickdays').hide();
            $('label.sickdays').hide();
            $('label.vacation').show();
            $( "#total_number" ).rules( "remove", 'digits');
        }

        if (this.value == 4) {
            $('label.vacation span[data-toggle="popover"]').text('Working Days Deducted');
        } else {
            $('label.vacation span[data-toggle="popover"]').text('Total days deducted');
        }
	});

    $('#total_number').on("keyup", function() {

        var warningValue = 5;
        var vacationDays = $(this).val();
        var willRemain   = Math.round((VACATION_LEFT - vacationDays) * 200) / 200; // Round to 2 point
        var elementName  = '#vacationWillRemain';

        if(isNaN(willRemain)) {
            willRemain = VACATION_LEFT; // If user input was impossible to be converted to float.
        }

        if ($('#vacation_type').val() == 3) {

            var warningValue     = 1;
            var elementName      = 'div.sickdays #vacationWillRemain';
            var sickDays         = $(this).val();
            var remainedSickDays = TOTAL_SICK_DAYS - TAKEN_SICK_DAYS;
            var willRemain       = Math.round((remainedSickDays - sickDays) * 200) / 200; // Round to 2 point

            if(isNaN(willRemain)) {
                willRemain = remainedSickDays; // If user input was impossible to be converted to float.
            }
        }

        $(elementName).text(willRemain + ' days');
        if (willRemain >= warningValue) {
            $(elementName).removeClass('progress-bar-danger');
            $(elementName).removeClass('progress-bar-warning');
            $(elementName).addClass('progress-bar-success');
        } else if(willRemain <= 0) {
            willRemain = 0;
        } else {
            $(elementName).removeClass('progress-bar-success');
            $(elementName).removeClass('progress-bar-danger');
            $(elementName).addClass('progress-bar-warning');
        }
        $(elementName).attr('aria-valuenow', willRemain);

        if ($('#vacation_type').val() == 3) {
            $(elementName).css('width', (willRemain / TOTAL_SICK_DAYS * 100) + '%' );
        } else {
            $(elementName).css('width', (willRemain / VACATION_OVERALL * 100) + '%' );
        }
    });

    if (jQuery().daterangepicker) {
        $dateRangePickeroptions = {
            startDate: moment(),
            endDate: moment(),
            format: 'YYYY-MM-DD'
        };

        $('#interval').daterangepicker(
            $dateRangePickeroptions
        );

        $("#interval").change(function() {
            if (/^[a-z]+$/.test($(this).val())) {
                $(this).val('');
                return false;
            }

            if (!/^[0-9]{4}-[0-9]{2}-[0-9]{2}\s-\s[0-9]{4}-[0-9]{2}-[0-9]{2}$/.test($(this).val())) {
                $(this).data('daterangepicker').setStartDate(moment());
                $(this).data('daterangepicker').setEndDate(moment());
                $("#total-number-hint .text-muted").html(" ( <b>"+(workingDaysInInterval(moment(), moment()))+"</b> Work Days) ");
                return false;
            }
        });

        $('#interval').on('apply.daterangepicker', function(ev, picker) {
            var parsed = $(this).val().split(" - ");
            var start = parsed[0],
                end   = parsed[1];
            $("#total-number-hint .text-muted").html(" ( <b>"+(workingDaysInInterval(start, end))+"</b> Work Days) ");
        });
    }
});

function workingDaysInInterval(start, end) {
    var employmentPercent = $('#employmentPercent').data('value');

    var startDate = new Date(start),
        endDate = new Date(end);

    // Validate input
    if (endDate < startDate)
        return 0;

    // Calculate days between dates
    var millisecondsPerDay = 86400 * 1000; // Day in milliseconds
    startDate.setHours(0,0,0,1);  // Start just after midnight
    endDate.setHours(23,59,59,999);  // End just before midnight
    var diff = endDate - startDate;  // Milliseconds between datetime objects
    var days = Math.ceil(diff / millisecondsPerDay);

    // Subtract two weekend days for every week in between
    var weeks = Math.floor(days / 7);
    days      = days - (weeks * 2);

    // Handle special cases
    var startDay = startDate.getDay();
    var endDay = endDate.getDay();

    // Remove weekend not previously removed.
    if (startDay - endDay > 1)
        days = days - 2;

    // Remove start day if span starts on Sunday but ends before Saturday
    if (startDay == 0 && endDay != 6)
        days = days - 1;

    // Remove end day if span ends on Saturday but starts after Sunday
    if (endDay == 6 && startDay != 0)
        days = days - 1;

    var dayCount = days * parseFloat(employmentPercent);

    if (dayCount % 1 === 0)
        return dayCount;
    else
        return dayCount.toFixed(2);
}
