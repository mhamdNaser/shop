<?php
function yog_format_timestamp($format, $timestamp)
{
	$dateTime = \DateTime::createFromFormat('U', $timestamp, new \DateTimeZone('UTC'));

	if (function_exists('wp_timezone'))
	{
		$dateTime->setTimezone(wp_timezone());
	}
	else
	{
		$timezoneString = get_option('timezone_string');
		if (!empty($timezoneString))
			$dateTime->setTimezone(new \DateTimeZone($timezoneString));
	}

	return $dateTime->format($format);
}

  /**
   * @desc function that generates a dynamic map google maps
   *
   * @param int $postId
   * @param float|false $latitude
   * @param float|false $longitude
   * @param string $mapType (optional, default hybrid)
   * @param integer $zoomLevel (optional, default 18)
   * @param integer width (optional, default 486)
   * @param integer height (optional, default 400)
   * @param string $extraAfterOnLoad (optional, default empty string)
   * @param bool $adminMode (optional, default false)

   * @return string
   */
  function yog_retrieveDynamicMap_googleMaps($postId, $latitude, $longitude, $mapType = 'hybrid', $zoomLevel = 18, $width = 486, $height = 400, $extraAfterOnLoad = '', $adminMode = false)
  {
    if ($latitude !== false && $longitude !== false)
    {
      $googleMapsApiKey = get_option('yog_google_maps_api_key');

      if (!empty($googleMapsApiKey))
      {
        if (!in_array($mapType, array('roadmap', 'satellite', 'hybrid', 'terrain')))
          throw new \InvalidArgumentException(__METHOD__ . '; Invalid map type (supported types: roadmap, satellite, hybrid, terrain)');

        $initFunctionName = 'yogInitMap' . $postId;
        $mapId            = 'map' . $postId;
        $widthStyle       = 'width:' . $width . ((strpos($width, '%') !== false) ? '' : 'px') . ';';
        $heightStyle      = 'height:' . $height . ((strpos($height, '%') !== false) ? '' : 'px') . ';';

        // Determine marker
        $postType         = get_post_type($postId);
        $markerOptions    = get_option('yog-marker-type-' . $postType);

        //        delete_option('yog-marker-type-' . $postType);

        // Create Inline script
        $inlineScript = 'var map' . $postId . ';';
        $inlineScript .= 'function ' . $initFunctionName . '() {';
          $inlineScript .= 'var position' . $postId . ' = { lat: ' . $latitude . ', lng: ' . $longitude . '};';
          $inlineScript .= 'map' . $postId . ' = new google.maps.Map(document.getElementById("' . $mapId . '"), {';
            $inlineScript .= 'center: position' . $postId . ',';
            $inlineScript .= 'zoom: ' . $zoomLevel . ',';
            $inlineScript .= 'mapTypeId: \'' . $mapType . '\',';
            // Hide POI's
            $inlineScript .= 'styles: [';
              $inlineScript .= '{';
                $inlineScript .= 'featureType: "poi",';
                $inlineScript .= 'stylers: [{ visibility: "off" }]';
              $inlineScript .= '}';
              // Black & white
              /*
              $inlineScript .= ',{';
                $inlineScript .= 'featureType: "all",';
                $inlineScript .= 'stylers: [{ "saturation": -100 }]';
              $inlineScript .= '}';
              */
            $inlineScript .= ']';
          $inlineScript .= '});';
          $inlineScript .= 'var marker' . $postId . ' = new google.maps.Marker({';
            $inlineScript .= 'position: position' . $postId;
            $inlineScript .= ',map: map' . $postId;

            // Use custom image for the marker?
            if ($markerOptions !== false && !empty($markerOptions['url']))
              $inlineScript .= ',icon: \'' . $markerOptions['url'] . '\'';

            // Marker draggable?
            if ($adminMode === true)
              $inlineScript .= ',draggable:true';

          $inlineScript .= '});';

					// Add extra onload js
					if (!empty($extraAfterOnLoad))
						$inlineScript .= 'window.onload = function() {' . $extraAfterOnLoad . '}';

        $inlineScript .= '}';

        // Create HTML
        $html = '<div id="' . $mapId . '" style="' . $heightStyle . $widthStyle .'"></div>';

        // Enqueue scripts
        wp_enqueue_script('yog-googlemaps-js', 'https://maps.googleapis.com/maps/api/js?key=' . $googleMapsApiKey . '&language=nl&callback=' . $initFunctionName . '&v=weekly', array(), YOG_PLUGIN_VERSION, true);
        wp_add_inline_script('yog-googlemaps-js', $inlineScript, 'before');
      }
    }

    return $html;
  }

  /**
   * Function that generates a OpenStreetMap based on LeafletJS
   *
   * @param int $postId (optional)
   * @param float|false $latitude
   * @param float|false $longitude
   * @param integer $zoomLevel (optional, default 18, max 18)
   * @param integer width (optional, default 486)
   * @param integer height (optional, default 400)
   * @param string $extraAfterOnLoad (optional, default empty string)
   * @param bool $adminMode (optional, default false)
   * @return string
   */
  function yog_retrieveDynamicMap_openStreetMap($postId, $latitude, $longitude, $zoomLevel = 18, $width = 486, $height = 400, $extraAfterOnLoad = '', $adminMode = false)
  {
    $html = '';

    if ($latitude !== false && $longitude !== false)
    {
      $mapId        = 'yog-openstreetmap-' . $postId;
      $widthStyle   = 'width:' . $width . ((strpos($width, '%') !== false) ? '' : 'px') . ';';
      $heightStyle  = 'height:' . $height . ((strpos($height, '%') !== false) ? '' : 'px') . ';';

      // Create Inline script
      $inlineScript = 'var lat = ' . $latitude . ';';
      $inlineScript .= 'var lon = ' . $longitude . ';';
      $inlineScript .= 'var map = L.map(\'' . $mapId . '\').setView([lat, lon], ' . $zoomLevel . ');';

      // set map tiles source
      $inlineScript .= 'L.tileLayer(\'https://tile.openstreetmap.org/{z}/{x}/{y}.png\', {attribution: \'Map data &copy; <a href="https://www.openstreetmap.org/" target="_blank">OpenStreetMap</a> contributors\', maxZoom: 18}).addTo(map);';
      // add marker to the map
      $inlineScript .= 'var marker = L.marker([lat, lon]).addTo(map);';

      // Create HTML
      $html = '<div id="' . $mapId . '" style="' . $heightStyle . $widthStyle .'"></div>';

      // Enqueue scripts / styles
      wp_enqueue_script('yog-leafletjs-js', YOG_PLUGIN_URL .'/inc/leaflet/leaflet.js', array(), YOG_PLUGIN_VERSION, true);
      wp_enqueue_style('yog-leafletjs', YOG_PLUGIN_URL . '/inc/leaflet/leaflet.css', array(), YOG_PLUGIN_VERSION);
      wp_add_inline_script('yog-leafletjs-js', $inlineScript, 'after');
    }

    return $html;
  }