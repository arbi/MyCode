$(function() {
	if ($('#map-desktop').length) {
		google.maps.event.addDomListener(window, 'load', function() {
			var myLatlng = new google.maps.LatLng($('#map-desktop').attr('data-lattitude'), $('#map-desktop').attr('data-longitude')),
				apartmentName = $('#map-desktop').attr('data-apartment-name'),
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
				map = new google.maps.Map(document.getElementById('map-desktop'), {
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

	    google.maps.event.addDomListener(window, 'load', function() {
		var myLatlng = new google.maps.LatLng($('#map-mobile').attr('data-lattitude'), $('#map-mobile').attr('data-longitude')),
			apartmentName = $('#map-mobile').attr('data-apartment-name'),
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
			map = new google.maps.Map(document.getElementById('map-mobile'), {
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
	}

	$('.confirm').click(function(e) {
		e.preventDefault();

		var $self = $(this);

		if ($('.additional-email').valid()) {
			$self.prop('disabled', true);

			$.ajax({
				url: $(this).attr('data-url'),
				type: 'POST',
				data: {
					email: $('.primary-email').val(),
					phone: $('.travel-phone').val(),
					subscribe: $('.subscribe').is(':checked') ? 1 : 0,
					code: $('.code').val()
				},
				error: function() {
					$self.prop('disabled', false);
					removeOverlay();
				},
				success: function(data) {
					$self.prop('disabled', false);

					if (data.status == 'success') {
						removeOverlay();
					} else {

					}
				}
			});
		}
	});

	$('.additional-email').validate({
		onfocusout: false,
		rules: {
			'primary_email': {
				required: true
			}
		},
		invalidHandler: function(form, validator) {
			var errors = validator.numberOfInvalids();

			if (errors) {
				validator.errorList[0].element.focus();
			}
		},
		highlight: function (element, errorClass, validClass) {
			$(element).parent().addClass('has-error');
		},
		unhighlight: function (element, errorClass, validClass) {
			$(element).parent().removeClass('has-error');
		},
		success: function (label) {
			$(label).closest('form').find('.valid').removeClass('invalid');
		},
		errorPlacement: function (error, element) {
			// do nothing
		}
	});

	if (!parseInt($('.modal-overlay').attr('data-ki-viewed'))) {
		$('.modal-overlay').modal('show');
	}
});

function removeOverlay() {
	$('.modal-overlay').modal('hide');
	$('.provide-email').parent().remove();
}
