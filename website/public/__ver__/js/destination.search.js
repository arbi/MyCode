var withoutbound = {
	months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],

	getDateAsString: function(date) {
		return date.getDate() + ' ' + this.months[date.getMonth()] + ' ' + date.getFullYear();
	}
};

$(function() {
	$('.search-general').each(function() {
		var target = $(this),
			inputStart = target.find('.input-daterange input:first'),
			inputEnd = target.find('.input-daterange input:last'),
			today = new Date(),
			tomorrow = new Date(),
			todayStr = target.attr('data-today'),
			todayParts = todayStr.split('-');

		today.setYear(todayParts[2]);
		today.setMonth(parseInt(todayParts[1]) - 1);
		today.setDate(parseInt(todayParts[0]));

		target.find('.input-daterange').datepicker({
			format: "dd M yyyy",
			autoclose: true
		});

		inputStart.datepicker('setStartDate', withoutbound.getDateAsString(today));
		inputEnd.datepicker('setStartDate', withoutbound.getDateAsString(tomorrow));

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
		});
	});
})
