$(function() {
	$('#global-language').change(function() {
		var origin = location.origin,
			path = location.pathname,
			query = location.search,
			hash = location.hash,
			lang = $(this).val(),
			connector = query == '' ? '?' : '&';

		document.location.href = [origin, path, query, connector, 'lang=', lang, hash].join('');
	});



    $('.go-search').click(function() {
		var this_form = $(this).closest('form');

	    if (this_form.find('.destionation').val() == '') {
		    this_form.find('input[data-toggle=dropdown]').dropdown('toggle');
		    return false;
	    } else {
	        var action = this_form.attr('action'),
		        query  = action + '?';

	        this_form.find('input[type=text],input[type=hidden], select').each(function(i, field) {
	            var name = $(this).attr('name');

	            if (name) {
	                var val  = $(this).val();

	                if (name == 'arrival' || name == 'departure') {
	                    val = changeDateFormat(val);
	                }

	                var item = [name, val].join('=');
	                query    = query + (i ? '&' : '') + item;
	            }
	        });

	        document.location.href = query;
	    }
	});

	$('.print').click(function(e) {
		e.preventDefault();
		window.print();
	})

	$('.open-chat-window').click(function(e) {
		e.preventDefault();

		window.open($(this).attr('data-url'), "Live Chat", "location=no,menubar=no,width=300,height=490,toolbar=no,resizable=no");

		return false;
	});

    $('.search i.glyphicon-map-marker').click(function() {
		$(this).closest('div').find('input[data-toggle=dropdown]').dropdown('toggle').blur();
        return false;
	});

    $('.search-general i.glyphicon-calendar').click(function() {
        $(this).closest('div').find('input').focus();
	});

	changeRightPositionOfChatTrigger();
	$(window).resize(function(){
		changeRightPositionOfChatTrigger();
	});

	chooseRightImageSizeForHomepageIcons();
	$(window).resize(function(){
		chooseRightImageSizeForHomepageIcons();
	});

	if (jQuery().selectize) {
        var $globalCurrency = $('#global-currency');
		if ($globalCurrency.length) {
			var $select = $globalCurrency;
				var selectedOption = $select.attr('data-value');
				$selectize = $select.selectize({
					create: false,
					valueField: 'code',
					labelField: 'name',
					searchField: ['name'],
					sortField: [
						{
							field: 'name'
						}
					],
					options: GLOBAL_CURRENCIES,
					render: {
						option: function (option, escape) {
							return '<div>'
								+ '<span class="span-currency-sign">' + escape(option.symbol) + '</span> '
								+ '<span> ' + escape(option.code) + ' </span>'
								+ '</div>';
						},
						item: function (option, escape) {
							return '<div>'
								+ '<span class="span-currency-sign">' + escape(option.symbol) + '</span> '
                                + '<span> ' + escape(option.code) + ' </span>'
								+ '</div>';
						}
					},
					onInitialize: function() {
						$select[0].selectize.setValue(selectedOption);
					}
				});

				$selectize.change(function() {
                    if ($(this).val()) {
                        var origin = location.origin,
                            path = location.pathname,
                            query = location.search,
                            hash = location.hash,
                            curr = $(this).val(),
                            connector = query == '' ? '?' : '&';

                        document.location.href = [origin, path, query, connector, 'cur=', curr, hash].join('');
                    }
				});
		}
	}


});

function chooseRightImageSizeForHomepageIcons(){
	$('.homepage-icons').each(function(index){
		var $img = $(this).find('.grid-sep img');
		var currentSource = $img.attr('src');
        if (currentSource) {
            var currentSourceLength = currentSource.length;
            var currentSourceWithoutSize = currentSource.slice(0,currentSourceLength-7);
            var WindowWidth = parseInt($(window).width());
            var actualSize = (WindowWidth<720) ? 480 : 184;
            var actualSrc = currentSourceWithoutSize + actualSize + '.jpg';
            $img.attr('src',actualSrc);
        }
	});
}

function changeRightPositionOfChatTrigger(){
	var windowWidth = $(window).width();
	var containerWidth = $('.container').width();
	var widthDiffHalf = Math.ceil((windowWidth - containerWidth)/2);
	var rightCss = widthDiffHalf + 'px';
	$('#chat-opener').css('right',rightCss).removeClass('hidden');
}

function changeDateFormat(date){
    var d = new Date(date);
    if(!isNaN(d.getFullYear()) && !isNaN(d.getMonth()) && !isNaN(d.getDate())) {
        var year  = d.getFullYear();
        var month = parseInt(d.getMonth() + 1);
            month = month.toString();
        var date  = d.getDate().toString();

        var month = (month.length  == 1 ) ? '0' + month: month;
        var date  = (date.length == 1 ) ? '0' + date: date;
        return date + '-' +  month + '-' + year;
    }
    return '';
}

//set url from ajax call
function setNewUrl(obj, pageId) {
    var URL = document.URL;
    var newURL = "";
    var tempArray = URL.split("?");
    var baseURL = tempArray[0];
    baseURL = baseURL.replace("#", "");
    newURL = baseURL + '?' + obj;
    // state object and title for history

    if (newURL != location.href) {
        history.pushState({page: pageId}, "page " + pageId, newURL);
    }
}

function numLeftPadZero(num) {
	if (num < 10) {
		num = [0, num].join('');
	}

	return num;
}

function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ')
            c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0)
            return c.substring(nameEQ.length, c.length);
    }
    return null;
}


function precise_round(num, decimals) {
    decimals = decimals||2;
    var sign = num >= 0 ? 1 : -1;
    return (Math.round((num*Math.pow(10,decimals))+(sign*0.001))/Math.pow(10,decimals)).toFixed(decimals);
}
