$(function() {
	$('.list-group').hide();

	$('.destination-panels .panel').mouseover(function() {
		var elem = $(this).hasClass('.panel')
			? $(this)
			: $(this).closest('.panel');

		elem.addClass('panel-primary');
	}).mouseout(function() {
		var elem = $(this).hasClass('.panel')
			? $(this)
			: $(this).closest('.panel');

		elem.removeClass('panel-primary');
	});

	$('.panel-heading').click(function() {
		$(this).parent().find('.list-group').slideToggle('fast');
		$(this).parent().find('.glyphicon').toggleClass('glyphicon-minus');
	});
});
