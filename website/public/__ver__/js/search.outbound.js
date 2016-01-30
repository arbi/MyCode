$(function() {
	var outbound = {
		option: {},
		defaultOptions: {
			debug: false
		},
		months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		target: null,
		today: new Date(),
		inputStart: null,
		inputEnd: null,
		range: null,

		egg: false,

		init: function(element, options) {
			this.target = $(element);

			// Setup
			this.setup();

			// Activate Date Picker
			this.activate();

			// Implement Logic
			this.implementLogic();
		},

		setup: function() {
			this.inputStart = this.target.find('#start');
			this.inputEnd = this.target.find('#end');

			var today = this.target.attr('data-today'),
				todayParts = today.split('-');

			this.today.setYear(todayParts[2]);
			this.today.setMonth(parseInt(todayParts[1]) - 1);
			this.today.setDate(parseInt(todayParts[0]));
		},

		activate: function() {
			var self = this,
				tomorrow = new Date();

			this.range = this.target.find('.input-daterange').datepicker({
				format: "dd M yyyy",
				autoclose: true
			});

			self.inputStart.datepicker('setStartDate', self.getDateAsString(self.today));
			self.inputEnd.datepicker('setStartDate', self.getDateAsString(tomorrow));

		},

		implementLogic: function() {
			var self = this;
	        var previousEndDate;
	        var previousStartDate;

			this.inputStart.datepicker().on('changeDate', function(e) {
				setTimeout(function() {
					var startDate = e.date,
						tmpDate = e.date;

					if (self.inputEnd.val() == '') {
						tmpDate.setDate(e.date.getDate() + 1);
						self.inputEnd.datepicker('update', tmpDate);
						self.inputEnd.focus();
					}

					if (Date.parse(self.inputEnd.val()) == Date.parse(self.inputStart.val())) {
						startDate.setDate(e.date.getDate() + 1);
						self.inputEnd.datepicker('update', startDate);

						self.inputEnd.focus();
					}

					$('.input-daterange').data('datepicker').updateDates();
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

			this.inputEnd.datepicker().on('changeDate', function(e) {
				setTimeout(function() {
					var startDate = e.date,
						tmpDate = e.date;

					if (self.inputStart.val() == '') {
						tmpDate.setDate(e.date.getDate() - 1);
						self.inputStart.datepicker('update', tmpDate);
						self.inputStart.focus();
					}

					if (Date.parse(self.inputEnd.val()) <= Date.parse(self.inputStart.val())) {
						startDate.setDate(e.date.getDate() - 1);
						self.inputStart.datepicker('update', startDate);

						self.inputStart.focus();
					}

					$('.input-daterange').data('datepicker').updateDates();
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
			$('.input-destination a').click(function() {

				if ($(this).hasClass('extended')) {
					var target = $(this).closest('.col');

					setTimeout(function() {
                        target.find('input').removeAttr('readonly').removeAttr('data-toggle').focus();

						self.egg = !self.egg;

                        self.autocomplate();
					}, 1);
				} else {
                    $('#apartel_url').val('');
                    var city_url = $(this).attr('data-url');
                    $('#destionation').val(city_url);

                    var cityCurrentDate = $(this).attr('data-currentdate'),
	                    cityMaxCapacity = parseInt($(this).attr('data-max-capacity'));

                    self.target.attr('data-today', cityCurrentDate);
                    self.init('.search-general');

                    if (self.inputStart.val() != '') {
                        var startDate = self.inputStart.val();
						var today = self.getDateAsStringForLoaction(cityCurrentDate, false);

                        if (Date.parse(today) > Date.parse(startDate)) {
                            self.inputStart.datepicker('update', today);

                            if (Date.parse(self.inputStart.val()) >= Date.parse(self.inputEnd.val())) {
                                var tomorrow = self.getDateAsStringForLoaction(cityCurrentDate, true);
                                self.inputEnd.datepicker('update', tomorrow);
                            }

                            $('.input-daterange').data('datepicker').updateDates();
                        }
					}

					// Change capacity depends on city
					$(this).closest('form').find('#capacity option').each(function() {
						if (parseInt($(this).val()) > cityMaxCapacity) {
							$(this).addClass('hide');
						} else {
							$(this).removeClass('hide');
						}
					});
                }

				$(this).closest('.input-destination').find('input').val(
					$(this).find('span').text()
				);
			});

			// Highlight input icons
			$('.col input').focus(function() {
				$(this).closest('.col').find('i').addClass('text-primary');
			}).blur(function() {
				$(this).closest('.col').find('i').removeClass('text-primary');
			});
		},

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
			newDate.setMonth(parseInt(parseDate[1]-1));
			newDate.setDate(newday);

            return this.getDateAsString(newDate);
		},

        autocomplate: function() {
            $('#search_autocomplete').autocomplete({
                source: function(request, response) {
                    $.ajax({
	                    url: GLOBAL_AUTOCOMPLATE,
	                    data: {txt: $("#search_autocomplete").val()},
	                    dataType: "json",
	                    type: "POST",
	                    success: function( data ) {
	                        var obj = [];

	                        if (data && data.status == 'success') {
	                            obj = data.result;
	                        }

	                        response(obj);
	                    }
                    });
                },
                minLength: 2,
                autoFocus: true,
                select: function(event, ui) {
                    if (ui.item) {
                        var itm = ui.item;

                        if (itm.type == 'city') {
                            $('#destionation').val(itm.slug);
                            $('#apartment_name').val('');
                        } else if (itm.type == 'apartment') {
                            $('#destionation').val('');
                            $('#apartment_name').val(itm.slug);
                        }
                    }
                }
            });
        }
	};

	outbound.init('.search-general');

	// Autocorrect capacity depends on selected city
	var selectedCityId = parseInt($('#search_autocomplete').attr('data-city-id'));

	$('.search .input-destination a').each(function() {
		if (parseInt($(this).attr('data-id')) == selectedCityId) {
			var capacity = parseInt($(this).attr('data-max-capacity'));

			$('#capacity option').each(function() {
				if (parseInt($(this).val()) > capacity) {
					$(this).addClass('hide');
				} else {
					$(this).removeClass('hide');
				}
			});

			return;
		}
	});
});
