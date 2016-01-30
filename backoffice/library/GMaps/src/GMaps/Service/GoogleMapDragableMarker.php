<?php

namespace GMaps\Service;

/**
 * GMaps\Service\GoogleMapDragableMarker
 *
 * Google Map Class  (Google Maps API v3)
 * This class enables the creation of google maps with dragable marker
 */
class GoogleMapDragableMarker {

    var $api_key = '';
    var $sensor = 'false';
    var $div_id = '';
    var $div_class = '';
    var $zoom = 10;
    var $lat = -300;
    var $lon = 300;
    var $height = "100px";
    var $width = "100px";

    /**
     * Constructor
     * @param string $apiKey
     */
    function __construct($apiKey) {
        $this->api_key = $apiKey;
    }

    /**
     * Initialize the user preferences
     *
     * Accepts an associative array as input, containing display preferences
     *
     * @access	public
     * @param	array	config preferences
     * @return	void
     */
    function initialize($config = array()) {
        foreach ($config as $key => $val) {
            if (isset($this->$key)) {
                $this->$key = $val;
            }
        }
    }

    /**
     * Generate the google map
     *
     * @access	public
     * @return	string
     */
    function generate() {

        $out = '';

        $out .= '	<div id="' . $this->div_id . '" class="' . $this->div_class . '" style="height:' . $this->height . ';width:' . $this->width . ';"></div>';

        $out .= '	<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=' . $this->api_key . '&sensor=' . $this->sensor . '"></script>';

        $out .= '	<script type="text/javascript"> 
    	
        				var geocoder = new google.maps.Geocoder();
        		
        				function geocodePosition(pos) {
					        geocoder.geocode({
					            latLng: pos
					        }, function (responses) {
					        });
					    }
        		
		        		function updateMarkerPosition(latLng) {
					        $("#longitude").val(latLng.lat());
					        $("#latitude").val(latLng.lng());
					    }
		
    					function initialize()
    					{
        					var latLng = new google.maps.LatLng(' . $this->lat . ',' . $this->lon . ');
        							
        					var map = new google.maps.Map(document.getElementById("' . $this->div_id . '"), {
					            zoom:' . $this->zoom . ',
					            center: latLng,
					            scrollwheel: false,
					            mapTypeId: google.maps.MapTypeId.ROADMAP
					        });
					            		
					        var marker = new google.maps.Marker({
					            position: latLng,
					            title: "Point A",
					            map: map,
					            draggable: true
					        });
					            		
					        // Update current position info.
					        updateMarkerPosition(latLng);
					        geocodePosition(latLng);
					            		
					        google.maps.event.addListener(marker, "drag", function () {
					            //updateMarkerStatus("Dragging...");
					            updateMarkerPosition(marker.getPosition());
					        });
					
					        google.maps.event.addListener(marker, "dragend", function () {
					            //updateMarkerStatus("Drag ended");
					            geocodePosition(marker.getPosition());
					        });';
        

        $out .= '		} 

        				// Onload handler to fire off the app.
    					google.maps.event.addDomListener(window, "load", initialize);
        		
						initialize();
					
					</script>';

        return $out;
    }

}
