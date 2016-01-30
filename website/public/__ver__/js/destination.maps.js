$(function() {
	google.maps.event.addDomListener(window, 'load', function() {
		var myLatlng = new google.maps.LatLng($('#map-canvas').attr('data-lattitude'), $('#map-canvas').attr('data-longitude')),
			map = new google.maps.Map(document.getElementById('map-canvas'), {
				zoom: 10,
				center: myLatlng,
				mapTypeControl: false,
				scrollwheel: false
			});
	});
});
