var inbound = {
	option: {},
	defaultOptions: {
		debug: false,
		stays: true,
		windows: true
	},
	months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct',  'Nov', 'Dec'],
	populatedData: [
		'rate.id',
		'rate.name',
		'capacity',
		'price',
		'total_price',
		'currency.sign',
		'policy.name',
		'policy.description',
        'discount.price',
        'discount.total'
	],
	firstPush: false,
	buffer: 0,

	today: new Date(),

	target: null,
	inputStart: null,
	inputEnd: null,
	capacity: null,

	/**
	 *
	 * @param element eg. #searchBox, .searchBox, section
	 * @param options Default:
	 * <pre>
	 * {
	 *     debug: false,
	 *     stays: true,
	 *     windows: true,
	 * }
	 * </pre>
	 */
	init: function(element, options) {
		this.target = $(element);

		// Setup
		this.setup();

		// Activate Date Picker
		this.activate();

		// Take into account min stay, max stay, relise window start, etc.
		this.implementLogic();

		// Dynamic rate update
		this.implementEvents();
	},

	setup: function() {
		this.inputStart = this.target.find('input[id=date_from]');
		this.inputEnd = this.target.find('input[id=date_to]');
		this.capacity = $('#capacity');

		var today = this.target.attr('data-today'),
			todayParts = today.split('-');

		this.today.setYear(todayParts[2]);
		this.today.setMonth(parseInt(todayParts[1]) - 1);
		this.today.setDate(parseInt(todayParts[0]));

    },

	activate: function() {
		var self = this,
			today = this.target.attr('data-today'),
			todayParts = today.split('-'),
			tomorrow = new Date();

		tomorrow.setYear(todayParts[2]);
		tomorrow.setMonth(parseInt(todayParts[1]) - 1);
		tomorrow.setDate(parseInt(todayParts[0]) + 1);

		this.target.find('.input-daterange').datepicker({
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
	},

	checkForActivateButton: function() {

	},

	implementEvents: function() {
		var self = this;

		$('.button-check-dates').click(function(e) {
			e.preventDefault();

            if (self.inputStart.val() == '') {
                self.inputStart.focus();
                return false;
            } else if( self.inputEnd.val() == ''){
                 self.inputEnd.focus();
                return false;
            }

			var checkButton = $('.button-check-dates');
			self.elementWait(checkButton.attr('disabled', 'disabled'));

			self.sendRequest(function() {
				self.firstPush = true;
			});
		});


        this.inputStart.datepicker().on('changeDate', function () {
            self.implementBufferLogic(self);
        });

        this.inputEnd.datepicker().on('changeDate', function () {
            self.implementBufferLogic(self);
        });


//		this.inputStart.on('change', function() {
//			self.implementBufferLogic(self);
//		});

//		this.inputEnd.on('change', function() {
//			self.implementBufferLogic(self);
//		});

		this.capacity.on('change', function(){
			if (self.firstPush) {
				self.sendRequest();
			}
		});
	},

	implementBufferLogic: function(self) {
		if (!self.firstPush) {
			//self.checkForActivateButton();
		} else {
			self.buffer++;

			setTimeout(function() {
				if (self.buffer > 0) {
					self.sendRequest();

					self.buffer = 0;
				}
			}, 500);
		}
	},

	sendRequest: function(callback) {
		var template = $('.template.template-rate').html(),
			self = this;

		if (self.firstPush) {
			self.target.find('.rate-storage').html('');
			self.target.find('.rate-storage').append('<div class="well text-center">loading. please wait...</div>')
		}

        var sendUrl = [
            'arrival=' + changeDateFormat($('#date_from').val()),
            'departure=' + changeDateFormat($('#date_to').val()),
            'guest=' + $('#capacity').val()
        ].join('&');

        if ($('#apartel_id').val() > 0) {
            sendUrl = [
                sendUrl,
                'apartel_id=' + $('#apartel_id').val()
            ].join('&');
        }

        var sendOther = [
            'apartment=' + $('#apartment').val(),
            'city=' + $('#city').val()
        ].join('&');



        var sendData = [
            sendUrl,
            sendOther
        ].join('&');

        $.ajax({
            type: "POST",
            url: this.target.attr('data-action'),
            data: sendData,
            dataType: "json",
            success: function(data) {
                if (self.firstPush) {
                    self.target.find('.rate-storage').html('');
                } else {
                    $('.before-check').hide();
                }

                if (data.status == 'success') {
                    for (var index in data.result) {
                        var section = template;
                        var rate = data.result[index];

                        section = section.replace(new RegExp('\n', 'g'), '');

                        for (var i in self.populatedData) {
                            if (self.populatedData[i] == 'rate.id') {
                                section = section.replace(new RegExp('{{' + self.populatedData[i] + '}}', 'g'), 'booking_process(' + eval('rate.' + self.populatedData[i]) + ')');
                            } if (self.populatedData[i] == 'price' && parseFloat(eval('rate.discount.price'))) {
                                section = section.replace(new RegExp('{{price}}', 'g'), '<span style="color: #f00; text-decoration: line-through"><span style="color:black; display: inline;">' + eval('rate.price') + '</span></span>&nbsp; <span style="color:#f00; display: inline;"><sup> ' + eval('rate.currency.sign') + ' </sup>' + eval('rate.discount.price') + '</span>');
                                section = section.replace(new RegExp('{{currency.sign}}', 'g'), '<span style="color: #f00; text-decoration: line-through"><span style="color:black; display: inline;">' + eval('rate.currency.sign') + '</span></span>');
                                section = section.replace(new RegExp('{{total_price}}', 'g'), '<span style="color: #f00; text-decoration: line-through"><span style="color:black; display: inline;">' + eval('rate.total_price') + '</span></span>&nbsp; <span style="color:#f00; display: block;"><sup> ' + eval('rate.currency.sign') + ' </sup>' + eval('rate.discount.total') + '</span>');
                            } else {
                                section = section.replace(new RegExp('{{' + self.populatedData[i] + '}}', 'g'), eval('rate.' + self.populatedData[i]));
                            }
                        }

                        section = rate.primary
                            ? section.replace(new RegExp('{{pointer.button}}', 'g'), '')
                            : section.replace(new RegExp('{{pointer.button}}', 'g'), ' btn-xs');


                        self.target.find('.rate-storage').append(section);
                    }

                    self.target.find('i[data-toggle=tooltip]').tooltip();
                    self.elementWaitStop();

                    setNewUrl(sendUrl);

                    $('[data-toggle="popover"]').popover();

                } else {
                    var search_button = '<p>'+self.target.attr('data-for-link')+'</p><a class="searchButton btn-block button-check-dates before-check smallButton" href="'+data.result+'">'+self.target.attr('data-t-link')+'</a>';
                    self.target.find('.rate-storage').html(search_button);
                }

                if (callback && typeof(callback) === "function") {
                    callback();
                }
            }
         });

	},

	getDateAsString: function(date) {
		return date.getDate() + ' ' + this.months[date.getMonth()] + ' ' + date.getFullYear();
	},

	elementWait: function(element) {
		var message = 'loading',
			dot = '.',
			counter = 0,
			maxCount = 3,
			value = message,
			space = '&nbsp;';

		element.html(message + space + space + space);

		this.loop = setInterval(function() {
			if (counter++ < maxCount) {
				value = value + dot;
			} else {
				value = message;
				counter = 0;
			}

			element.html(value);
		}, 200);
	},

	elementWaitStop: function() {
		clearInterval(this.loop);
	},

	debug: function() {

	}
};

function booking_process(rateId) {
    if (parseInt(rateId) > 0) {
        $('#rate-for-booking').val(rateId);
        $('#search-inbound').submit();
    }
}

$('body').on('click', function (e) {
    $('[data-toggle=popover]').each(function () {
        // hide any open popovers when the anywhere else in the body is clicked
        if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
            $(this).popover('hide');
        }
    });
});
