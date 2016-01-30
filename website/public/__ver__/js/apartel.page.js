var withoutbound = {
    months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],

    getDateAsString: function(date) {
        return date.getDate() + ' ' + this.months[date.getMonth()] + ' ' + date.getFullYear();
    }
};

$(function($) {
    var $reviewsModal = $('#all-reviews');
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

    $('#roomType').change(function () {
        $(this).attr('name', $(this).find(":selected").attr('data-search-name'));
    });

    if ('show' == showReviews) {
        $reviewsModal.modal();
    }

    $('#show-all-reviews').click(function() {
        $reviewsModal.modal();
    });

    $reviewsModal.on('show.bs.modal', function() {
        var url = window.location.href;

        if (url.indexOf("?") < 0) {
            url += "?reviews=show";
        } else {
            url += "&reviews=show";
        }

        window.history.replaceState(null, null, url);
    });

    $reviewsModal.on('hide.bs.modal', function() {
        var url = window.location.href;
        url = url.replace(/&?\??reviews=([^&]$|[^&]*)/i, "");
        window.history.replaceState(null, null, url);
    });

    var reviewsLoading = false;

    $("#all-reviews-body").scroll( function() {
        if($(this)[0].scrollHeight - $(this).scrollTop() == $(this).outerHeight()) {
            var currentCount = $('#reviews-current-count').val();

            if (currentCount != 'end' && !reviewsLoading) {

                reviewsLoading = true;
                $('#reviews-loader').show();

                $.ajax({
                    type: "POST",
                    url: GLOBAL_MORE_REVIEW_URL,
                    data: {
                        apartel_id:     $('#apartel-id').val(),
                        current_count:  currentCount
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 'success') {
                            $('#reviews-loader').before(data.result);
                            $('#reviews-current-count').val(data.reviews_count);
                        }

                        if (!data.the_end) {
                            reviewsLoading = false;
                        }

                        $('#reviews-loader').hide();
                    }
                });
            }
        }
    });

    $('.royalGallery').royalSlider({
        fullscreen: {
            enabled: true,
            nativeFS: true
        },
        controlNavigation: 'thumbnails',
        autoScaleSlider: true,
        autoScaleSliderWidth: 780,
        autoScaleSliderHeight: 520,
        loop: true,
        imageScaleMode: 'fill',
        navigateByClick: true,
        numImagesToPreload:4,
        arrowsNav:true,
        arrowsNavAutoHide: true,
        arrowsNavHideOnTouch: true,
        keyboardNavEnabled: true,
        fadeinLoadedSlide: true,
        globalCaption: true,
        globalCaptionInside: false,
        thumbs: {
            appendSpan: true,
            firstMargin: true,
            paddingBottom: 4
        }
    });
});
