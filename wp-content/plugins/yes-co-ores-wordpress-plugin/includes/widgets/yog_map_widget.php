<?php
/**
* @desc YogMapWidget
* @author Stefan van Zanden - Yes-co Nederland
*/
class YogMapWidget extends WP_Widget
{
  const NAME                = 'Yes-co Map';
  const DESCRIPTION         = 'Map van je eigen objecten / vestiging.';
  const CLASSNAME           = 'yog-map';
  const WIDGET_ID_PREFIX    = 'yogmapwidget-';

  /**
  * @desc Constructor
  *
  * @param void
  * @return YogRecentObjectsWidget
  */
  public function __construct()
  {
    $options = array( 'classsname'  => self::CLASSNAME,
                      'description' => self::DESCRIPTION);

    parent::__construct(false, $name = self::NAME, $options);
  }

  /**
  * @desc Display widget
  *
  * @param array $args
  * @param array $instance
  * @return void
  */
  public function widget($args, $instance)
  {
		// Render map
    $shortcode      = empty($instance['shortcode']) ? '' : $instance['shortcode'];

    echo do_shortcode($shortcode);
  }

  /**
   * @desc Method shortcodeToSettings
   *
   * @param {String} $shortcode
   * @return {}
   */
  public function shortcodeToSettings($shortcode)
  {
     $shortcode = str_replace(array('[yog-map ', ']', '\"', '"'), '', $shortcode);
     $shortcode = str_replace('"', '', $shortcode);
     $atts      = shortcode_parse_atts( $shortcode );

     $settings  = $this->shortcodeAttributesToSettings($atts);

     return $settings;
  }

  /**
   * @desc Method shortcodeAttributesToSettings
   *
   * @param {Array} $atts
   * @return {
   */
  public function shortcodeAttributesToSettings($atts)
  {
    $settings               = array();

    $width                  = 100;
    $widthUnit              = 'px';
    $height                 = 100;
    $heightUnit             = 'px';

    $latitude               = '52.02';
    $longitude              = '5.5496';
    $mapType                = 'hybrid';
    $zoomLevel              = 2;
    $controlZoomPosition    = 'top_left';
    $controlPanPosition     = 'top_left';
    $controlMapTypePosition = 'top_right';
    $postTypes              = yog_getAllPostTypes();

    // PostTypes
    if (!empty($atts['post_types']))
      $postTypes = explode(',', $atts['post_types']);

    // Width
    if (!empty($atts['width']))
      $width = (int)$atts['width'];

    // WidthUnit
    if (!empty($atts['width_unit']))
      $widthUnit = $atts['width_unit'];

    // Height
    if (!empty($atts['height']))
      $height = (int)$atts['height'];

    // HeightUnit
    if (!empty($atts['height_unit']))
      $heightUnit = $atts['height_unit'];

    // MapType
    if (!empty($atts['map_type']))
      $mapType = $atts['map_type'];

    // Zoomlevel
    if (!empty($atts['zoomlevel']))
      $zoomLevel = (int)$atts['zoomlevel'];

    // Latitude
    if (isset($atts['center_latitude']) && strlen(trim($atts['center_latitude'])) > 0)
      $latitude = $atts['center_latitude'];

    // Longitude
    if (isset($atts['center_longitude']) && strlen(trim($atts['center_longitude'])) > 0)
      $longitude = $atts['center_longitude'];

    // ControlZoom
    if (isset($atts['control_zoom_position']) && strlen(trim($atts['control_zoom_position'])) > 0)
    {
      $settings['control_zoom'] = array( 'position' => $atts['control_zoom_position'] );
    }

    // ControlPan
    if (isset($atts['control_pan_position']) && strlen(trim($atts['control_pan_position'])) > 0)
    {
      $settings['control_pan'] = array( 'position' => $atts['control_pan_position'] );
    }

    // ControlMapType
    if (isset($atts['control_map_type_position']) && strlen(trim($atts['control_map_type_position'])) > 0)
    {
      $settings['control_map_type'] = array( 'position' => $atts['control_map_type_position'] );
    }

    // Disable scroll wheel
    if (isset($atts['disable_scroll_wheel']) && $atts['disable_scroll_wheel'] == 'true')
      $settings['disable_scroll_wheel'] = true;

    $settings['postTypes']  = $postTypes;
    $settings['width']      = $width;
    $settings['widthUnit']  = $widthUnit;
    $settings['height']     = $height;
    $settings['heightUnit'] = $heightUnit;
    $settings['latitude']   = $latitude;
    $settings['longitude']  = $longitude;
    $settings['mapType']    = $mapType;
    $settings['zoomLevel']  = $zoomLevel;

    return $settings;
  }

  /**
   * @desc Method generateDetailWindow
   *
   * @param integer $postID
   * @return string
   */
  protected function generateDetailWindow($postID, $outputHtml = false)
  {
    $post     = get_post($postID);

    // Add post to the globals so it can be used in the template
    $GLOBALS['post'] = $post;

    $postType = $post->post_type;

    $html     = '';

    $html .= '<div class="post-' . esc_attr($postType) . '" id="' . $postID . '">';

    $customThemeTemplateName = 'single-map-detail-window-' . $postType . '.php';

		if ( $overridden_template = locate_template(  'single-map-detail-window-all.php' ) )
		{
      // Load the template but capture it's output
        ob_start();

       // locate_template() returns path to file
       // if either the child theme or the parent theme have overridden the template
       load_template($overridden_template, false);

       $html .= ob_get_contents();

       ob_end_clean();
		}
    else if ( $overridden_template = locate_template( $customThemeTemplateName ) )
    {
      // Load the template but capture it's output
        ob_start();

       // locate_template() returns path to file
       // if either the child theme or the parent theme have overridden the template
       load_template($overridden_template, false);

       $html .= ob_get_contents();

       ob_end_clean();

    }
    else // Generate something generic
    {
      switch ($postType)
      {
        case YOG_POST_TYPE_WONEN:
        case YOG_POST_TYPE_BOG:
        case YOG_POST_TYPE_NBBN:
        case YOG_POST_TYPE_NBPR:
        case YOG_POST_TYPE_NBTY:

          $images     = yog_retrieveImages('thumbnail', 3, $postID);
          $title      = yog_retrieveSpec('Naam', $postID);
          $city       = yog_retrieveSpec('Plaats', $postID);
          $prices     = yog_retrievePrices('small', 'small', $postID);
          $status     = '';

          $permaLink  = get_permalink($postID);

          switch ($postType)
          {
            case YOG_POST_TYPE_WONEN:

              $status   = yog_retrieveSpec('Status', $postID);

              if (empty($status) || $status == 'beschikbaar')
                $status = yog_getOpenHouse('Open huis', $postID);

            break;

            case YOG_POST_TYPE_BOG:

              $status   = yog_retrieveSpec('Status', $postID);

            break;
          }

          // Determine state html
          $stateHtml = '';

          if (!empty($status) && $status != 'beschikbaar')
            $stateHtml = '<span class="post-object-state">' . esc_html($status) . '</span>';

          // Images
          if (!empty($images))
          {
            $html .= '<a href="' . esc_url($permaLink) . '" rel="bookmark" title="' . esc_attr($title) . '" class="main-image"><img src="' . esc_url($images[0][0]) . '" width="' . esc_attr($images[0][1]) . '" height="' . esc_attr($images[0][2]) . '" alt="' . esc_attr($title) . '" />' . $stateHtml . '</a>';

            $html .= '<div class="extra-images">';

            if (!empty($images[1]))
              $html .= '<div class="extra-image"><a href="' . esc_url($permaLink) . '"><img alt="' . esc_attr($title) . '" src="' . esc_url($images[1][0]) . '" width="50" /></a></div>';

            if (!empty($images[2]))
              $html .= '<div class="extra-image"><a href="' . esc_url($permaLink) . '"><img alt="' . esc_attr($title) . '" src="' . esc_url($images[2][0]) . '" width="50" /></a></div>';

            $html .= '</div>';
          }

          $html .= '<div class="specs_object">';

          $html .= '<h2><a href="' . esc_url($permaLink) . '">' . esc_attr($title) . '</a></h2>';

          if (!empty($city))
            $html .= '<h3 class="caps">' . esc_html($city). '</h3>';

          $html .= '<p>' . implode('<br />', $prices) . '</p>';

          $html .= '</div>';

        break;

        case YOG_POST_TYPE_RELATION:

          $html       = '';
          $title      = get_the_title($postID);
          $emailAdres = yog_retrieveSpec('Emailadres', $postID);
          $website    = yog_retrieveSpec('Website', $postID);

          $permaLink  = get_permalink($postID);

          $html       .= '<a href="' . esc_url($permaLink) . '">' . esc_html($title) . '</a><br />';

          $html       .= 'Email: ' . esc_html($emailAdres) . '<br />';
          $html       .= 'Website: ' . esc_html($website) . '<br />';

        break;
      }
    }

    $html .= '</div>';

    return $html;
  }

  /**
   * @desc Method generate
   *
   * @param {Array} $settings
   * @param {String} $extraAfterOnLoad
   * @return {String}
   */
  public function generate($settings = array(), $extraAfterOnLoad = '', $adminMode = false)
  {
    $html = '';

    if (!empty($settings['latitude']) && !empty($settings['longitude']) && !empty($settings['postTypes']))
    {
      $yogMapType       = get_option('yog_map_type', 'google-maps');
      $googleMapsApiKey = get_option('yog_google_maps_api_key');
      $postTypes        = $settings['postTypes'];
      $googleMapsType   = esc_attr($settings['mapType']);
      $latitude         = esc_attr($settings['latitude']);
      $longitude        = esc_attr($settings['longitude']);
      $height           = !empty($settings['height']) ? (int)$settings['height'] : 100;
      $heightUnit       = !empty($settings['heightUnit']) ? esc_attr($settings['heightUnit']) : '%';
      $width            = !empty($settings['width']) ? (int)$settings['width'] : 100;
      $widthUnit        = !empty($settings['widthUnit']) ? esc_attr($settings['widthUnit']) : '%';
      $zoomLevel        = !empty($settings['zoomLevel']) ? (int)$settings['zoomLevel'] : 10;
      $mapId            = 'yesco-og-dynamic-map';
      $initFunctionName = 'yogInitMapDynamic';

      if (!empty($googleMapsApiKey) && $yogMapType === 'google-maps' && in_array($googleMapsType, array('roadmap', 'satellite', 'hybrid', 'terrain')))
      {
        if ($adminMode === false)
        {
          // Retrieve objects to view on map
          $posts          = get_posts(array('numberposts' => 999999, 'post_type' => $postTypes));

          // Retrieve marker options for all used post types
          $markerOptions  = array();
          foreach ($postTypes as $postType)
          {
            $option = get_option('yog-marker-type-' . $postType);
            if (!empty($option))
              $markerOptions[$postType] = $option;
          }
        }

        // Create Inline script
        $inlineScript = 'var mapDynamic;';
        $inlineScript .= 'function ' . $initFunctionName . '() {';
          $inlineScript .= 'var positionDynamic = { lat: ' . $latitude . ', lng: ' . $longitude . '};';
          $inlineScript .= 'mapDynamic = new google.maps.Map(document.getElementById("' . $mapId . '"), {';
            $inlineScript .= 'center: positionDynamic,';
            $inlineScript .= 'zoom: ' . $zoomLevel . ',';
            $inlineScript .= 'mapTypeId: \'' . $googleMapsType . '\',';
            // Hide POI's
            $inlineScript .= 'styles: [';
              $inlineScript .= '{';
                $inlineScript .= 'featureType: "poi",';
                $inlineScript .= 'stylers: [{ visibility: "off" }]';
              $inlineScript .= '}';
            $inlineScript .= ']';
          $inlineScript .= '});';

          // Add marker for each object
          if ($adminMode === false)
          {
            foreach ($posts as $post)
            {
              $postType      = $post->post_type;
              $postLatitude  = yog_retrieveSpec('Latitude', $post->ID);
              $postLongitude = yog_retrieveSpec('Longitude', $post->ID);

              if (strlen(trim($postLatitude)) > 0 && strlen(trim($postLongitude)) > 0)
              {
                $inlineScript .= "\n";

                $inlineScript .= 'var infowindow' . $post->ID . ' = new google.maps.InfoWindow({';
                  $inlineScript .= 'content: \'' . str_replace(["\n", "\t", "\r", "'"], ['', '', '', '&apos;'], $this->generateDetailWindow($post->ID, true)) . '\'';
                $inlineScript .= '});';

                $inlineScript .= "\n";

                $inlineScript .= 'var marker' . $post->ID . ' = new google.maps.Marker({';
                  $inlineScript .= 'position: { lat: ' . $postLatitude . ', lng: ' . $postLongitude . '}';
                  $inlineScript .= ',map: mapDynamic';

                  // Use custom image for the marker?
                  if (!empty($markerOptions[$postType]) && !empty($markerOptions[$postType]['url']))
                    $inlineScript .= ',icon: \'' . $markerOptions['url'] . '\'';

                  // Marker draggable?
                  if ($adminMode === true)
                    $inlineScript .= ',draggable:true';

                $inlineScript .= '});';
                $inlineScript .= 'marker' . $post->ID . '.addListener(\'click\', function() {';
                  $inlineScript .= 'infowindow' . $post->ID . '.open(mapDynamic, marker' . $post->ID . ')';
                $inlineScript .= '});';
              }
            }
          }
          else
          {
            $inlineScript .= 'var marker = new google.maps.Marker({';
              $inlineScript .= 'position: positionDynamic';
              $inlineScript .= ',map: mapDynamic,draggable:true';
            $inlineScript .= '});';
          }

					// Add extra onload js
					if (!empty($extraAfterOnLoad))
						$inlineScript .= 'window.onload = function() {' . $extraAfterOnLoad . '}';

        $inlineScript .= '}';

        $html = '<div id="' . $mapId . '" style="width:' . $width . $widthUnit . ';height:' . $height . $heightUnit . ';"></div>';

        // Enqueue scripts
        wp_enqueue_script('yog-googlemaps-js', 'https://maps.googleapis.com/maps/api/js?key=' . $googleMapsApiKey . '&language=nl&callback=' . $initFunctionName . '&v=weekly', array(), YOG_PLUGIN_VERSION, true);
        wp_add_inline_script('yog-googlemaps-js', $inlineScript, 'before');
      }
    }
    return $html;
  }

  /**
  * @desc Update widget settings
  *
  * @param array $new_instance
  * @param array $old_instance
  * @return array
  */
  public function update($new_instance, $old_instance)
  {
    $instance                     = $old_instance;

    $instance['shortcode']        = empty($new_instance['shortcode']) ? '' : $new_instance['shortcode'];

    return $instance;
  }

  /**
  * @desc Display widget form
  *
  * @param array $instance
  * @return void
  */
  public function form($instance)
  {
    // Don't escape it with extra quotes for the quick edit url
    $shortcode      = empty($instance['shortcode']) ? '' : $instance['shortcode'];

    echo '<p>';
    echo '<label for="' . esc_attr($this->get_field_id('shortcode')) . '">' . __('Shortcode') . ': </label>';
    echo '<input class="widefat" id="' . esc_attr($this->get_field_id('shortcode')) . '" name="' . esc_attr($this->get_field_name('shortcode')) . '" type="text" value="' . esc_attr($shortcode) . '" />';
    echo '</p>';

    echo '<p>Gebruik de <a href="' . esc_url(get_admin_url()) . 'options-general.php?page=yesco_OG&shortcode=' . esc_attr(urlencode($shortcode)) . '">shortcode generator</a> op de Instellingen pagina om snel een shortcode te genereren.</p>';
  }
}