$(function() {
	google.maps.event.addDomListener(window, 'load', function() {
		var myLatlng = new google.maps.LatLng($('#anchor_map').attr('data-lattitude'), $('#anchor_map').attr('data-longitude')),
			apartmentName = $('#anchor_map').attr('data-apartment-name'),
			homeController = function HomeControl(controlDiv, map) {
				// Set CSS styles for the DIV containing the control
				// Setting padding to 5 px will offset the control
				// from the edge of the map
				controlDiv.style.padding = '5px';

				// Set CSS for the control border
				var controlUI = document.createElement('div');
				controlUI.style.cursor = 'pointer';
				controlUI.title = 'Click to set the map to Home';
				controlDiv.appendChild(controlUI);

				// Set CSS for the control interior
				var controlText = document.createElement('div');
				controlText.className = 'btn btn-success btn-xs';
				controlText.style.fontFamily = 'Arial,sans-serif';
				controlText.style.fontSize = '12px';
				controlText.style.paddingLeft = '4px';
				controlText.style.paddingRight = '4px';
				controlText.innerHTML = '<i class="glyphicon glyphicon-map-marker"></i> ' + apartmentName;
				controlUI.appendChild(controlText);

				// Setup the click event listeners: simply set the map to
				// Chicago
				google.maps.event.addDomListener(controlUI, 'click', function() {
					map.setCenter(myLatlng)
				});
			},
			map = new google.maps.Map(document.getElementById('map-canvas'), {
				zoom: 16,
				center: myLatlng,
				mapTypeControl: false,
				scrollwheel: false
			}),
			marker = new google.maps.Marker({
				position: myLatlng,
				map: map,
				title: apartmentName
			}),
			homeControlDiv = document.createElement('div'),
			homeControl = new homeController(homeControlDiv, map);

		homeControlDiv.index = 1;
		map.controls[google.maps.ControlPosition.TOP_RIGHT].push(homeControlDiv);
	});
});
