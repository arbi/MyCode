$(function(){
	$('*[data-toggle=tooltip]').tooltip();
	$('.anchor').click(function(e) {
		e.preventDefault();

		$('html, body').animate({
			scrollTop: $($(this).attr('href')).offset().top
		}, 400);
	});

	$('.search-inbound').affix({
		offset: {
			top: 135
		}
	});

	inbound.init('.search-inbound', {
		debug: true
	});

	//pagination options
	var PaginationOptions = {
        containerClass:"pagination",
        pageUrl:function(type,page){
          return null;
        },
        shouldShowPage:function(type, page, current) {
              switch(type)               {
                  case "first":
                  case "last":
                      return false;
                  default:
                      return true;
              }
        },
        onPageClicked:function(event, originalEvent, type, page) {
          //search by pagination button
          getReview(page, true);
        },
        onPageChanged:null
    };

    function getReview(page, paginationClick) {
        $('.review-main').addClass('loading');
        var sendData = [
            'apartment_id=' + $('#apartment_id').val(),
            'page=' + page
        ].join('&');

        $.ajax({
           type: "GET",
           url: GLOBAL_APARTMENT_REVIEW,
           data: sendData,
           dataType: "json",
           success: function(data) {
               $('.review-main').removeClass('loading');
               if (data.status == 'success') {
                    $('#review_part').html(data.result);
                    PaginationOptions.currentPage = page;
                    PaginationOptions.totalPages = data.totalPages;
                    if(data.totalPages > 1)
                        $('#review_pagination').show();
                    else
                        $('#review_pagination').hide();
                    $('#review_pagination').bootstrapPaginator(PaginationOptions);
                    if(paginationClick)
                        $('html, body').animate({ scrollTop: $("#review_part").offset().top }, 100);
               }
           }
        });
    }

    var reviewShow = $('#review_part').attr('data-reviews');

    if (reviewShow != 1) {
	    getReview(1, false);
    }
});
