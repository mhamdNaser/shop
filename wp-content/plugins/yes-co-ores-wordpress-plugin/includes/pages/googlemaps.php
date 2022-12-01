<div class="wrap">
  <div class="icon32 icon32-config-yog"><br /></div>
  <h2>Yes-co Open Real Estate System instellingen</h2>
  <?php require_once(__DIR__ . '/parts/menu.php'); ?>
  <?php
  if (!empty($errors))
  {
    echo '<div id="message" class="error below-h2" style=" padding: 5px 10px;">';
      echo '<b>Er zijn fouten geconstateerd waardoor de Yes-co ORES plugin niet naar behoren kan functioneren</b>:';
      echo '<ul style="padding-left:15px;list-style-type:circle"><li>' . implode('</li><li>', $errors) . '</li></ul>';
    echo '</div>';
  }

  if (!empty($warnings))
  {
    echo '<div id="message" class="error below-h2" style="padding: 5px 10px; background-color:#feffd1;border-color:#d5d738;">';
      echo '<ul style="padding-left:15px;list-style-type:circle"><li>' . implode('</li><li>', $warnings) . '</li></ul>';
    echo '</div>';
  }

  if (empty($errors))
  {
    // BEGIN YOG MAP MARKER SETTINGS
    $mapType = get_option('yog_map_type', 'google-maps');

    ?>
    <form method="post" action="options-general.php?page=yesco_OG_googlemaps" name="maps-form" enctype="multipart/form-data">
      <?php wp_nonce_field('yog-settings'); ?>

      <h3>Soort kaart</h3>
      <div class="yog-setting">
        <label><input type="radio"<?php echo ($mapType === 'google-maps' ?' checked':'');?> name="yog_map_type" value="google-maps" onchange="toggleMapSettings();" /> Google Maps</label><br />
        <label><input type="radio"<?php echo ($mapType === 'open-street-map' ?' checked':'');?> name="yog_map_type"  value="open-street-map" onchange="toggleMapSettings();" /> OpenStreetMap</label>
      </div>
      <?php
      // Google maps setting
      ?>
      <div id="map-google-maps"<?php echo ($mapType !== 'google-maps' ? ' style="display:none;"' : '');?>>
        <h3>Google Maps</h3>
        <div class="notice-inline notice-info">
          <p>Voor Google Maps is een API sleutel verplicht, ook dien je betaal gegevens gekoppeld te hebben. <a href="https://developers.google.com/maps/gmp-get-started" target="_blank">Lees hier</a> hoe je een API sleutel aan kan vragen.</p>
          <p>Ondanks dat je betaal gegevens dient te koppelen is Google Maps voor de meeste websites echter nog steeds gratis, <a href="https://cloud.google.com/maps-platform/pricing/?hl=nl" target="_blank">je krijgt namelijk maandelijks $200 aan gratis tegoed</a>.</p>
        </div>
        <br />
        <table class="form-table" role="presentation">
          <tbody>
            <tr>
              <th scope="row">
                <label for="yog_google_maps_api_key">API key:</label>
              </th>
              <td>
                <input type="text" value="<?php echo (get_option('yog_google_maps_api_key') ? esc_attr(get_option('yog_google_maps_api_key')) :''); ?>" name="yog_google_maps_api_key" id="yog_google_maps_api_key" style="min-width:340px;" />
              </td>
            </tr>
          </tbody>
        </table>
        <?php

        // Marker settings
        register_setting($this->optionGroup, $this->optionGroup);

        $settingsSectionId = 'markerSettings';
        $settingsMarkerPage = 'page-marker-settings';

        add_settings_section($settingsSectionId, 'Marker Settings', array($this, 'section'), $settingsMarkerPage);

        $postTypes    = yog_getAllPostTypes();

        foreach ($postTypes as $postType)
        {
          $postTypeObject = get_post_type_object($postType);
          $optionName     = 'yog-marker-type-' . $postType;
          $logoOptions    = get_option($optionName);

          add_settings_field('markerSettings_' . $postType, $postTypeObject->labels->singular_name, array($this, 'inputFile'), $settingsMarkerPage, $settingsSectionId, array($logoOptions, $postType, $optionName));
        }

        // Render the section and fields to the screen of the provided page
        do_settings_sections($settingsMarkerPage);
        ?>
      </div>
      <?php
      // OpenStreetMap setting
      ?>
      <div id="map-open-street-map"<?php echo ($mapType !== 'open-street-map' ? ' style="display:none;"' : '');?>>
        <h3>OpenStreetMap</h3>
        <div class="notice-inline notice-info">
          <p><a target="_blank" href="https://www.openstreetmap.org">OpenStreetMap</a> is een gratis alternatief voor Google Maps. Het biedt echter geen zaken als Street View.</p>
          <p>Het gebruik van OpenStreetMap is op dit moment enkel geimplementeerd wanneer de kaart via de functie <a href="http://dev.yes-co.com/wordpress/#yog_retrievedynamicmap" target="_blank">yog_retrieveDynamicMap()</a> ingeladen wordt.</p>
        </div>
      </div>

      <?php submit_button();?>
    </form>
    <script>
    function toggleMapSettings() {
      var mapTypeElement = document.querySelector('input[name=yog_map_type]:checked');
      if (mapTypeElement)
      {
        var googleMapsSettingsHolder    = document.getElementById('map-google-maps');
        var openStreetMapSettingsHolder = document.getElementById('map-open-street-map');

        if (mapTypeElement.value === 'open-street-map')
        {
          googleMapsSettingsHolder.style.display    = 'none';
          openStreetMapSettingsHolder.style.display = 'block';
        }
        else
        {
          googleMapsSettingsHolder.style.display    = 'block';
          openStreetMapSettingsHolder.style.display = 'none';
        }
      }
    }
    </script>
    <?php
  }
  ?>
</div>