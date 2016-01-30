var months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

var globalDateFormat = 'MMM D, YYYY';
var globalDateTimeFormat = 'MMM D, YYYY HH:mm:ss';
//var globalDateSaveFormat = 'YYYY-MM-DD';
//var globalDateSaveTimeFormat = 'YYYY-MM-DD HH:mm:ss';

PNotify.prototype.options.styling = "bootstrap3";
$(function () {
    setMaxHeightForAsanaFeedback();

    $(window).resize(function() {
        setMaxHeightForAsanaFeedback();
    });

    centrAlignUDSearchFilterForMibile();

    $(window).resize(function() {
        centrAlignUDSearchFilterForMibile();
    });

    var navigationMaxHeight = $(window).height() - 56;
    $('.navbar-fixed-top .navbar-collapse').css('max-height',navigationMaxHeight);

    if (jQuery().tooltip) {
        $('*[data-toggle="tooltip"]').tooltip({
            container: 'body'
        });
    }

    if (jQuery().popover) {
        $('*[data-toggle="popover"]').popover({
            delay: {
                show: 300,
                hide: 150
            },
            trigger: "hover",
            html: true
        });
    }

    $('.self-submitter').click(function () {
        location.href = $(this).val();
    });

    // @ToDo Remove this
    if (jQuery().datepicker) {
        var dp = $('.datepicker').datepicker({
            autoclose: true,
            format: 'dd M yyyy'
        });
        dp.on('changeDate', function (e) {
            $(this).focus();
        }).on('blur', function (e) {
            var val = $(this).val(),
                    matches = val.match(/^([0-9]{2})[.:-\\\/]([0-9]{2})[.:-\\\/]([0-9]{4})$/);

            if ($.isArray(matches) && matches.length == 4) {
                var day = matches[1],
                        month = parseInt(matches[2]),
                        year = matches[3];

                if (day > 0 && day < 31 && month > 0 && month < 13 && year > 2000 && year < 3000) {
                    $(this).val(matches[1] + ' ' + months[parseInt(matches[2])] + ' ' + matches[3]).focus();
                }
            }
        });
    }

    $('#login-form').ajaxForm({
        dataType: 'json',
        success: processAuthnticationResult
    });

    $('#dynamicLoginForm').on('hidden', function () {
        $('a').button('reset');
        $('button').button('reset');
    });

    if (jQuery().selectize) {
        $('.selectize:not(.selectized)').each(function(index){
            $(this).selectize({
                plugins: ['remove_button'],
                selectOnTab: true
            });
        });
    }

	// Redraw Notification for Mobile view
	~(function() {
		if ($(document).width() < 768) {
			$('.navbar-brand').after(
				$('.navbar-notifications')
					.removeClass('nav')
					.removeClass('navbar-right')
					.removeClass('navbar-nav')
					.addClass('list-unstyled')
					.addClass('pull-right')
					.addClass('notifications-mobile-view')
			);
		}
	})();
});

function centrAlignUDSearchFilterForMibile() {
    if ($('.universal-dashboard .dataTables_filter').length > 0) {
        $('.universal-dashboard .dataTables_filter').each(function(index) {
            var $self = $(this).parent();

            if ($(window).width() <= 760) {
                $self.removeClass('pull-right').css('width', '260px').css('margin', '0 auto');
            } else {
                $self.addClass('pull-right').removeAttr('style');
            }
        });
    }
}

function setMaxHeightForAsanaFeedback() {
    var windowHeight = $(window).height();
    $('#widgets .feedback-content').css('max-height',(windowHeight - 50) + 'px');
}

function precise_round(num, decimals) {
    decimals = decimals||2;
    var sign = num >= 0 ? 1 : -1;
    return (Math.round((num*Math.pow(10,decimals))+(sign*0.001))/Math.pow(10,decimals)).toFixed(decimals);
}

function submitReLogin(e) {
    if (e.which == 13 || e.keyCode == 13) {
        $('#login-form').submit();
    }
}

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

/*************** Top Menu Navigation *********************/
window.onload            = generateTopNavigation;
window.onresize          = generateTopNavigation;
window.orientationchange = generateTopNavigation;

function generateTopNavigation() {
    var mainNavigationContainer = $('#mainNavigationContainer');

    if ($(document).width() > 767) {
        if (mainNavigationContainer.hasClass('large')) {
            return false;
        }

        var menus = mainNavigationContainer.find('.dropdown-menu li.nav-separator');
        menus.each(function () {
            var subMenus = $(this).nextAll();
            var parent   = $(this);
            parent.html('<span>'+ parent.text() +'<i class="glyphicon glyphicon-chevron-right"></i></span><ul></ul>');
            subMenus.each(function () {
                if ($(this).hasClass('nav-separator')) {
                    return false;
                }

                if ($(this).hasClass('active')) {
                    parent.addClass('active');
                }

                parent.find('ul').append($(this).clone());
                $(this).remove();
            });
        });
        mainNavigationContainer.addClass('large');
    } else {
        var hasSub = mainNavigationContainer.find('.dropdown-menu li.nav-separator ul');
        if (hasSub.length == 0) {
            return false;
        }

        var menus  = mainNavigationContainer.find('.dropdown-menu li.nav-separator');

        menus.each(function () {
            var subMenus = $(this).find('ul');
            var title    = $(this).find('span');
            title.find('i').remove();

            $(this).after(subMenus.html());
            $(this).html(title.html());
            $(this).removeClass('active');

            subMenus.remove();
            title.remove();
        });
        mainNavigationContainer.removeClass('large');
    }
}

$(document).on('click', '#mainNavigationContainer .dropdown-menu li.nav-separator > span', function () {
    $(this).parent().parent().trigger('open');
    return false;
});