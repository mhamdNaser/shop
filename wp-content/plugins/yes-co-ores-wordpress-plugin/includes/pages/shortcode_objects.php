<div class="wrap" id="yog-shortcode-generator">
  <div class="icon32 icon32-config-yog"><br /></div>
  <h2>Yes-co Open Real Estate System instellingen</h2>
  <?php require_once(__DIR__ . '/parts/menu.php'); ?>
  <?php
  if (count($templateFiles) == 0)
  {
    echo '<div id="message" class="error below-h2" style="padding: 5px 10px;background-color:#feffd1;border-color:#d5d738;">';
      echo '<p>Het gebruikte theme bevat geen templates die gebruikt kunnen worden voor de weergave van de objecten die door deze shortcode terug gegeven worden. Er wordt een standaard weergave gebruikt.<br />';
      echo 'Templates die gebruikt kunnen worden door deze shortcode dienen een bestandsnaam als <b>object-[.....].php</b> te hebben. De template wordt voor ieder weer te geven object 1x aangeroepen.<br />';
      echo 'Een voorbeeld van zo een template is te vinden in het Yes-co voorbeeld theme (zie bestand object-all.php).';
      echo '</p>';
    echo '</div>';
  }
  ?>
  <p>Hiermee kun je snel een shortcode voor objecten genereren die je kan plaatsen in een Page of Post.</p>
  <p>Shortcode: <br /><b class="bold">[yog-objects<span id="yog-shortcode-result"></span>]</b></p>

  <table class="form-table">
    <tbody>
      <?php
      //type=".." num=".." cat=".." order=".."

      // Show type select
      $selectHtml = '<select name="type" id="type" class="shortcode-elem">';
        $selectHtml .= '<option value="">alles</option>';
        foreach ($postTypes as $postType)
        {
          $postTypeObject = get_post_type_object($postType);
          $label          = $postTypeObject->labels->singular_name;

          $selectHtml .= '<option value="' . $postType . '">' . $label . '</option>';
        }
      $selectHtml .= '</select>';

      echo $this->renderRow('<label for="type">Post type: </label>', $selectHtml);

      // Show number
      echo $this->renderRow('<label for="num">Aantal: </label>', '<input type="number" name="num" id="num" min="1" class="shortcode-elem" />');

      // Show category select
      $selectHtml = '<select name="cat" id="cat" class="shortcode-elem">';
        $selectHtml .= '<option value="">alles</option>';
        foreach ($categories as $category)
        {
          $baseName = $category->name;

          $selectHtml .= '<option value="' . esc_attr($category->slug) . '">' . esc_html($baseName) . '</option>';

					if (!empty($category->childs))
					{
						foreach ($category->childs as $childCategory)
						{
							$childName	= $childCategory->name;

							$selectHtml .= '<option value="' . esc_attr($childCategory->slug) . '">' . esc_html($baseName . ' - ' . $childName) . '</option>';

							if (!empty($childCategory->childs))
							{
								foreach ($childCategory->childs as $childChildCategory)
								{
									$selectHtml .= '<option value="' . esc_attr($childChildCategory->slug) . '">' . esc_html($baseName . ' - ' . $childName . ' - '. $childChildCategory->name) . '</option>';
								}
							}
						}
					}
        }
      $selectHtml .= '</select>';

      echo $this->renderRow('<label for="cat">Category: </label>', $selectHtml);

      // Show order select
      $selectHtml = '<select name="order" id="order" class="shortcode-elem">';
      foreach ($sortOptions as $key => $title)
      {
        $selectHtml .= '<option value="' . esc_attr($key) . '"' . ($sortOption == $key ? ' selected="selected"' : '') . '>' . esc_html($title) . '</option>';
      }
      $selectHtml .= '</select>';

      echo $this->renderRow('<label for="order">Sortering: </label>', $selectHtml);

      // Show template select
      if (count($templateFiles) > 0)
      {
        $selectHtml = '<select name="template" id="template" class="shortcode-elem">';
          $selectHtml .= '<option value=""></option>';
          foreach ($templateFiles as $file)
          {
            $selectHtml .= '<option value="' . esc_attr($file) . '">' . esc_html($file) . '</option>';
          }
        $selectHtml .= '</select>';
        $selectHtml .= '<p>Als er geen template geselecteerd is, wordt er een standaard weergave gebruikt.<br />';
        $selectHtml .= 'Templates die gebruikt kunnen worden door deze shortcode dienen een bestandsnaam als <b>object-[.....].php</b> te hebben. De template wordt voor ieder weer te geven object 1x aangeroepen.<br />';
        $selectHtml .= 'Een voorbeeld van zo een template is te vinden in het Yes-co voorbeeld theme (zie bestand object-all.php).</p>';

        echo $this->renderRow('<label for="template">Template: </label>', $selectHtml);
      }
      ?>
    </tbody>
  </table>
</div>