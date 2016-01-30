$(function() {
	$('.top-destinations .panel-body').each(function() {
		$(this).css('background-image', 'url(' + $(this).attr('data-background') + ')');
	});

	$('.index-search').each(function() {
		$(this).css('background-image', 'url(' + $(this).attr('data-background') + ')');
	});
});
