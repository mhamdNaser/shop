<?php
$mapType = get_option('yog_map_type', 'google-maps');
?>
<div class="wrap">
  <div class="icon32 icon32-config-yog"><br /></div>
  <h2>Yes-co Open Real Estate System instellingen</h2>
  <?php require_once(__DIR__ . '/parts/menu.php'); ?>
  <p>Hiermee kun je snel een shortcode voor een map genereren die je kan plaatsen in een Page of Post.</p>
  <?php
  if ($mapType !== 'google-maps')
  {
    echo '<div id="message" class="error below-h2" style="padding: 5px 10px;background-color:#feffd1;border-color:#d5d738;">';
      echo '<p>De Map shortcode is enkel bruikbaar als er gebruik gemaakt wordt van kaarten via Google Maps.<br />Welk soort kaarten er gebruikt worden kan je <a href="options-general.php?page=yesco_OG_googlemaps">hier</a> instellen.</p>';
    echo '</div>';
  }
  else
  {
    ?>
    <p>Shortcode: <br /><b id="yogShortcode" class="bold">[yog-map]</b></p>
    <?php
    $html = '<table class="form-table"><tbody>';

    // Types
    $checkboxesHtml = '';

    foreach ($postTypes as $postTypeTmp)
    {
      $checked        = '';

      if (in_array($postTypeTmp, $settings['postTypes']))
        $checked = ' checked="checked"';

      $id             = 'shortcode_PostTypes_' . $postTypeTmp;
      $label          = '';

      $postTypeObject = get_post_type_object($postTypeTmp);

      $label          = $postTypeObject->labels->singular_name;

      $checkboxesHtml .= '<input type="checkbox" id="' . $id . '" name="shortcode_PostTypes[]" value="' . esc_attr($postTypeTmp) . '"' . $checked . ' />&nbsp;<label for="' . $id . '">' . esc_html($label) . '</label><br />';
    }

    $html .= $this->renderRow('<label for="shortcode_PostTypes">Post types: </label>', $checkboxesHtml);

    // Latitude
    $html .= $this->renderRow('<label for="shortcode_Latitude">Latitude: </label>', '<input id="shortcode_Latitude" name="shortcode_Latitude" type="text" value="' . esc_attr($settings['latitude']) . '" />');

    // Longitude
    $html .= $this->renderRow('<label for="shortcode_Longitude">Longitude: </label>', '<input id="shortcode_Longitude" name="shortcode_Longitude" type="text" value="' . esc_attr($settings['longitude']) . '" />');

    // Width
    $html .= $this->renderRow('<label for="shortcode_Width">Breedte (Geheel getal): </label>', '<input id="shortcode_Width" name="shortcode_Width" type="text" value="' . esc_attr($settings['width']) . '" />');

    // Width Unit
    $selectHtml = '';
    $selectHtml .= '<select id="shortcode_WidthUnit" name="shortcode_WidthUnit">';
    $selectHtml .= '<option value="px"' . ($settings['widthUnit'] == 'px' ? ' selected="selected"' : '')  . '>px</option>';
    $selectHtml .= '<option value="%"' . ($settings['widthUnit'] == '%' ? ' selected="selected"' : '')  . '>%</option>';
    $selectHtml .= '<option value="vw"' . ($settings['widthUnit'] == 'vw' ? ' selected="selected"' : '')  . '>vw</option>';
    $selectHtml .= '</select>';

    $html .= $this->renderRow('<label for="shortcode_WidthUnit">Breedte in ...: </label>', $selectHtml);

    // Width
    $html .= $this->renderRow('<label for="shortcode_Width">Hoogte (Geheel getal): </label>', '<input id="shortcode_Height" name="shortcode_Height" type="text" value="' . esc_attr($settings['height']) . '" />');

    // Height Unit
    $selectHtml = '';
    $selectHtml .= '<select id="shortcode_HeightUnit" name="shortcode_HeightUnit">';
    $selectHtml .= '<option value="px"' . ($settings['heightUnit'] == 'px' ? ' selected="selected"' : '')  . '>px</option>';
    $selectHtml .= '<option value="%"' . ($settings['heightUnit'] == '%' ? ' selected="selected"' : '')  . '>%</option>';
    $selectHtml .= '<option value="vh"' . ($settings['heightUnit'] == 'vh' ? ' selected="selected"' : '')  . '>vh</option>';
    $selectHtml .= '</select>';

    $html .= $this->renderRow('<label for="shortcode_HeightUnit">Hoogte in ...: </label>', $selectHtml);

    $html .= '</tbody></table>';

    echo $html;

    echo '<br /><br />';

    // @todo: Rewrite shortcode js so no dojo is needed
    $extraOnLoad = '
                require(["dojo/ready", "yog/admin/Shortcode" ], function(ready) {

                    ready(function() {

                      var yogAdminShortcode = new yog.admin.Shortcode(mapDynamic, marker);

                    });
                });';

    $settings['width']      = 800;
    $settings['widthUnit']  = 'px';
    $settings['height']     = 480;
    $settings['heightUnit'] = 'px';

    if (!YogPlugin::isDojoLoaded())
    {
      YogPlugin::loadDojo(false);
    }

    echo $yogMapWidget->generate($settings, $extraOnLoad, true);
  ?>
  </div>
  <?php
}