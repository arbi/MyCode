$(function() {
	var outbound = {
		months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		getDateAsString: function(date) {
			return date.getDate() + ' ' + this.months[date.getMonth()] + ' ' + date.getFullYear();
		},
        getDateAsStringForLoaction: function(date, check) {
            var newDate = new Date(),
                parseDate = date.split('-'),
                newday = parseInt(parseDate[0]);
            if (check) {
                newday = newday + 1;
            }
			newDate.setYear(parseDate[2]);
			newDate.setMonth(parseInt(parseDate[1]) - 1);
			newDate.setDate(newday);
            return this.getDateAsString(newDate);
		},
        init: function(target) {
            var todayParts = target.attr('data-today').split('-'),
                today = new Date(),
                tomorrow = new Date(),
                inputStart = target.find('.input-daterange input:first'),
                inputEnd = target.find('.input-daterange input:last');
                today.setYear(todayParts[2]);
                today.setMonth(parseInt(todayParts[1]) - 1);
                today.setDate(parseInt(todayParts[0]));

                target.find('.input-daterange').datepicker({
                    format: "dd M yyyy",
                    autoclose: true
                });

                inputStart.datepicker('setStartDate', outbound.getDateAsString(today));
                inputEnd.datepicker('setStartDate', outbound.getDateAsString(tomorrow));
        }
	};

	$('.search').each(function() {
		var target = $(this),
		inputStart = target.find('.input-daterange input:first'),
		inputEnd = target.find('.input-daterange input:last');

        outbound.init(target);

        var previousEndDate;
        var previousStartDate;

		inputStart.datepicker().on('changeDate', function(e) {
			setTimeout(function() {
				var startDate = e.date,
					tmpDate = e.date;

				if (inputEnd.val() == '') {
					tmpDate.setDate(e.date.getDate() + 1);
					inputEnd.datepicker('update', tmpDate);
					inputEnd.focus();
				}

				if (Date.parse(inputEnd.val()) == Date.parse(inputStart.val())) {
					startDate.setDate(e.date.getDate() + 1);
					inputEnd.datepicker('update', startDate);

					inputEnd.focus();
				}

				target.find('.input-daterange').data('datepicker').updateDates();
			}, 10);
		})
        .on('show', function (e) {
            previousStartDate = $(this).val();
        })
        .on('hide', function (e) {
            if ($(this).val() === '' || $(this).val() === null) {
                $(this).val(previousStartDate).datepicker('update');
            }
        });

		inputEnd.datepicker().on('changeDate', function(e) {
			setTimeout(function() {
				var startDate = e.date,
					tmpDate = e.date;

				if (inputStart.val() == '') {
					tmpDate.setDate(e.date.getDate() - 1);
					inputStart.datepicker('update', tmpDate);
					inputStart.focus();
				}

				if (Date.parse(inputEnd.val()) <= Date.parse(inputStart.val())) {
					startDate.setDate(e.date.getDate() - 1);
					inputStart.datepicker('update', startDate);

					inputStart.focus();
				}

				target.find('.input-daterange').data('datepicker').updateDates();
			}, 10);
		})
        .on('show', function (e) {
            previousEndDate = $(this).val();
        })
        .on('hide', function (e) {
            if ($(this).val() === '' || $(this).val() === null) {
                $(this).val(previousEndDate).datepicker('update');
            }
        });

		// Detect Destination event
		target.find('.input-destination a').click(function(e) {
			e.preventDefault();

			target.find('.destionation').val(
				$(this).attr('data-url')
			);

			$(this).closest('.input-destination').find('input').val(
				$(this).find('span').text()
			);

			var first_input = $(this).closest('form').find('.input-daterange input:first'),
				cityMaxCapacity = parseInt($(this).attr('data-max-capacity'));

			if (first_input.val() == '') {
				first_input.focus();
			}

            //change location date
            var cityCurrentDate = $(this).attr('data-currentdate');

            target.attr('data-today', cityCurrentDate);
            outbound.init(target);

            if (inputStart.val() != '') {
                var startDate = inputStart.val();
				var today = outbound.getDateAsStringForLoaction(cityCurrentDate, false);

	            if (Date.parse(today) > Date.parse(startDate)) {
                    inputStart.datepicker('update', today);

                    if (Date.parse(inputStart.val()) >= Date.parse(inputEnd.val())) {
                        var tomorrow = outbound.getDateAsStringForLoaction(cityCurrentDate, true);
                        inputEnd.datepicker('update', tomorrow);
                    }

                    target.find('.input-daterange').data('datepicker').updateDates();
                }
            }

			// Change capacity depends on city
			$(this).closest('form').find('.capacity option').each(function() {
				if (parseInt($(this).val()) > cityMaxCapacity) {
					$(this).addClass('hide');
				} else {
					$(this).removeClass('hide');
				}
			});
		});

		// Highlight input icons
		target.find('.col input').focus(function() {
			$(this).closest('.col').find('i').addClass('text-primary');
		}).blur(function() {
			$(this).closest('.col').find('i').removeClass('text-primary');
		});
	});
});
