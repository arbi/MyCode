$(function() {
	var app_version = $('.account-wall').attr('data-version'),
		container = $('.row'),
		site = {
			resize: function() {
				var new_margin = Math.ceil(($(window).height() - container.height()) / 2);
				container.css('margin-top', new_margin + 'px');
			}
		};

	if (window.innerWidth > 980) {
		$.backstretch([
			"../img/login/bg1.jpg",
			"../img/login/bg2.jpg",
			"../img/login/bg3.jpg",
			"../img/login/bg4.jpg",
			"../img/login/bg5.jpg"
	    ], {
			duration: 5000,
			fade: 2000
		});
	}

	$('input[name=identity]').focus();

	site.resize();

	$(window).resize(function() {
		site.resize();
	});
});
