$(function() {
	//pagination options
	var PaginationOptions = {
		containerClass: "pagination",
		pageUrl: function(type,page) {
			return null;
		},
		shouldShowPage: function(type, page, current) {
			switch(type) {
				case "first":
				case "last":
					return false;
				default:
					return true;
			}
		},
		onPageClicked: function(event, originalEvent, type, page) {
			//search by pagination button
			callSearch(page, false);
		},
		onPageChanged: null,
		itemTexts: function (type, page, current) {
			switch (type) {
				case "first":
					return "First";
				case "prev":
					return "<i class='icon-angle-left'></i>";
				case "next":
					return "<i class='icon-angle-right'></i>";
				case "last":
					return "Last";
				case "page":
					return page;
			}
		}
	};

    // search by click
    $('#update_search').click(function() {
	    callSearch(1, true);
    });

	// Autoselect room types (by url params)
	if (location.search.indexOf('&') != -1) {
		var parts = location.search.split('&');

		parts.forEach(function(value) {
			if (value.indexOf('=') != -1) {
				var params = value.split('=');

				if (parseInt(params[1])) {
					$('.input-' + params[0]).val(1);
				}
			}
		});
	}

	// Autoselect room types (by form elements)
	$('.cm-button').each(function () {
		var checkbox = $(this).find('.cm-checkbox'),
			input = $(this).find('input');

		if (parseInt(input.val())) {
			checkbox.addClass('cm-checkbox-checked');
		} else {
			checkbox.removeClass('cm-checkbox-checked');
		}
	});

	// Manually select room type
	$('.floating-row .cm-button').click(function (e) {
		e.preventDefault();

		var checkbox = $(this).find('.cm-checkbox'),
			input = $(this).find('input');

		if (parseInt(input.val())) {
			input.val(0);
			checkbox.removeClass('cm-checkbox-checked')
		} else {
			input.val(1);
			checkbox.addClass('cm-checkbox-checked');
		}

		$('#update_search').trigger('click');
	});

    //search method
    function callSearch(page, clickButton) {

        $('.search-main').addClass('loading');

        if (!$('#destionation').val() && $('#apartment_name').val()) {
            var url = 'apartment/' + $('#apartment_name').val();

            if ($('#start').val() != '' && $('#end').val() != '') {
                var urlParams = [
	                changeDateFormat($('#start').val()) ? 'arrival=' + changeDateFormat($('#start').val()) : '',
	                changeDateFormat($('#end').val()) ? 'departure=' + changeDateFormat($('#end').val()) : '',
                    'guest=' + $('#capacity').val(),
                    parseInt($('.input-studio').val()) ? 'studio=' + $('.input-studio').val() : '',
	                parseInt($('.input-onebedroom').val()) ? 'onebedroom=' + $('.input-onebedroom').val() : '',
	                parseInt($('.input-twobedroom').val()) ? 'twobedroom=' + $('.input-twobedroom').val() : ''
                ].join('&');

                url += '?' + urlParams;
            }

	        url = url.replace(/&+/g, '&');
	        url = url.replace(/&$/g, '');

            location.href = url;

            return;
        }

        var combine = ''
        if ($('#apartel_url').val()) {
            combine = 'apartel=' + $('#apartel_url').val();
        } else {
            combine = 'city=' + $('#destionation').val();
        }


        var sendData = [
            combine,
	        changeDateFormat($('#start').val()) ? 'arrival=' + changeDateFormat($('#start').val()) : '',
	        changeDateFormat($('#end').val()) ? 'departure=' + changeDateFormat($('#end').val()) : '',
            'guest=' + $('#capacity').val(),
	        parseInt($('.input-studio').val()) ? 'studio=' + $('.input-studio').val() : '',
	        parseInt($('.input-onebedroom').val()) ? 'onebedroom=' + $('.input-onebedroom').val() : '',
	        parseInt($('.input-twobedroom').val()) ? 'twobedroom=' + $('.input-twobedroom').val() : ''
        ].join('&');

	    sendData = sendData.replace(/&+/g, '&');
	    sendData = sendData.replace(/&$/g, '');

        if (page > 1) {
            sendData = [
                sendData, 'page=' + page
            ].join('&');
        }

        $.ajax({
            type: "GET",
            url: GLOBAL_SEARCH,
            data: sendData,
            dataType: "json",
            success: function (data) {
                var scrollTop = (clickButton) ? 0 : $(".search-results").offset().top - 10;

                $('html, body').animate({scrollTop: scrollTop}, 100, function () {
                    $('.search-main').removeClass('loading');

                    if (data.status == 'success') {
                        $('.search-results').html(data.result);
                        $('#search_error').remove();

                        if (data.totalPages == 1) {
                            $('#pagination_view').html('');
                        } else {
                            $('#pagination_view').html(data.paginatinView);
                            PaginationOptions.currentPage = page;
                            PaginationOptions.totalPages = data.totalPages;
                            $('#search_pagination').show();
                            $('#search_pagination').bootstrapPaginator(PaginationOptions);
                        }
                    } else {
                        var search_error = data.result;

                        $('.search-results').html('');
                        $('#pagination_view').html('');

                        if ($('#search_error').length > 0) {
                            $('#search_error').html(search_error);
                        } else {
                            $('.search-results').append('<div class="alert alert-danger col-sm-6 col-sm-offset-3" id="search_error">' + search_error + '</div>');
                        }
                    }
                });

                setNewUrl(sendData, page);
                document.title = 'Apartments in ' + $("#search_autocomplete").val() + ' | ginosi.com';
            }
        });
    }

    window.onpopstate = function (event) {
        if (event.state && event.state.page > 1) {
            callSearch(event.state.page, false);
        } else {
            callSearch(1, false);
        }
    };

    if ($('#viewAllApartment').val() != 1) {
        callSearch(parseInt($('#current_page').val()), true);
    }
});
