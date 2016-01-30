$(function(){
    $.validator.setDefaults({
        highlight: function(element) {
            $(element).parent().addClass('has-error');
        },
        unhighlight: function(element) {
            $(element).parent().removeClass('has-error');
        },
        errorElement: 'span',
        errorClass: 'help-block',
        errorPlacement: function(error, element) {},
        onfocusout: function(element) {
            $(element).valid();

            if ($(element).hasClass('expdate')) {
                $('.expdate').valid();
            }
        },
        onkeyup: function(element) {
            $(element).valid();

            if ($(element).hasClass('expdate')) {
                $('.expdate').valid();
            }
        },
        onclick: function(element) {
            $(element).valid();

            if ($(element).hasClass('expdate')) {
                $('.expdate').valid();
            }
        }
    });
	$("#review-form").validate({
		rules: {
			"like": {
				required: true
			}
		}
	});
    

    $('#submit').click(function(e) {
        
        e.preventDefault();
        var form = $('#review-form');
        var validate = form.validate();
         if($('#stars').val() > 0) {
            $('.rating h3 .has-error').remove();
            $('.rating .br-widget a').removeClass('error');
            
        } else {
            $('.rating .br-widget a').addClass('error');
            $('.rating h3').append('<span class="has-error"> (required)</span>');
        }
        if (form.valid() && $('#stars').val() > 0) {
            var ajaxUrl = $('#review-form').attr('action');
            var dataForm = form.serialize();
            $.ajax({
                type: "POST",
                url: ajaxUrl,
                data: dataForm,
                success: function(data) {
                    
                    if (data.status == 'success') {
                        window.location.href = thankYouUrl;
                    } else {
                        // TODO: here need implement error handling
                        //window.location.href = thankYouUrl;
                        if($('#search_error').length > 0){
                            $('#search_error').html(data.result);
                        } else {
                            $('#review-form').closest('div').prepend('<div class="alert alert-danger col-sm-12" id="search_error">'+data.result+'</div>');
                        }
                    }
                }
            });
        } else {
            validate.focusInvalid();
        }
        
    });

	$('#stars').barrating('show', {
		showValues: false,
		showSelectedRating: false
	});
});
