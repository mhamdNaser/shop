<?php
/**
* @desc YogSearchFormWidgetAbstract
* @author Kees Brandenburg - Yes-co Nederland
*/
abstract class YogSearchFormWidgetAbstract extends WP_Widget
{
  private $fieldsSettings;

  /**
  * @desc Constructor
  *
  * @param mixed $id_base
  * @param string $name
  * @param array $widget_options
  * @param array $control_options
  */
  public function __construct($id_base = false, $name, $widget_options = array(), $control_options = array())
  {
    parent::__construct($id_base, $name, $widget_options, $control_options);

    $searchManager = YogObjectSearchManager::getInstance();
    $searchManager->extendSearch();
  }

  /**
  * @desc Retrieve the number of posts that would be found be the selected criteria
  *
  * @param void
  * @return void
  */
  public function retrieveNumberOfPosts()
  {
		$objectType	= empty($_REQUEST['object_type']) ? null : sanitize_text_field($_REQUEST['object_type']);

    if (!empty($_REQUEST['yog-search-form-widget-ajax-search']) && !is_null($objectType) && $objectType == $this->getPostType())
    {
      global $wp_query;

      $foundPosts = 0;
      if (!empty($wp_query->found_posts))
        $foundPosts = $wp_query->found_posts;
      else if (!empty($wp_query->post_count))
        $foundPosts = $wp_query->post_count;

      switch ($objectType)
      {
        case YOG_POST_TYPE_NBPR:
          $objectsText  = 'nieuwbouw projecten';
          $objectText   = 'nieuwbouw project';
          break;
        case YOG_POST_TYPE_NBTY:
          $objectsText  = 'nieuwbouw types';
          $objectText   = 'nieuwbouw type';
          break;
        case YOG_POST_TYPE_BBPR:
          $objectsText  = 'complexen';
          $objectText   = 'complex';
          break;
        default:
          $objectsText  = 'objecten';
          $objectText   = 'object';
          break;
      }

      switch ($foundPosts)
      {
        case 0:

            if (defined('YOG_SEARCH_MSG_FOUND_POSTS_0'))
                $msg = sprintf(YOG_SEARCH_MSG_FOUND_POSTS_0, $objectsText);
            else
                $msg = 'Er zijn geen ' . $objectsText . ' die voldoen aan deze criteria';

          break;
        case 1:

            if (defined('YOG_SEARCH_MSG_FOUND_POSTS_1'))
                $msg = sprintf(YOG_SEARCH_MSG_FOUND_POSTS_1, $foundPosts, $objectsText);
            else
                $msg = 'Er is ' . $foundPosts . ' ' . $objectText . ' wat voldoet aan deze criteria';

          break;
        default:

            if (defined('YOG_SEARCH_MSG_FOUND_POSTS_2'))
                $msg = sprintf(YOG_SEARCH_MSG_FOUND_POSTS_2, $foundPosts, $objectsText);
            else
                $msg = 'Er zijn ' . $foundPosts . ' ' . $objectsText . ' die voldoen aan deze criteria';

          break;
      }

      echo json_encode(array('posts' => $foundPosts, 'msg' => $msg, 'formId' => sanitize_text_field($_REQUEST['form_id'])));
      exit;
    }
  }

  /**
  * @desc Retrieve fields setting
  *
  * @param void
  * @return YogFieldsSettingsAbstract
  */
  protected function retrieveFieldsSettings()
  {
    if (is_null($this->fieldsSettings))
      $this->fieldsSettings = YogFieldsSettingsAbstract::create($this->getPostType());

    return $this->fieldsSettings;
  }

  /**
   * Render order element
   *
   * @param void
   * @return void
   */
  protected function renderOrderElement($extraOptions = array())
  {
    $options  = array('date_asc'  => 'datum oplopend', '' => 'datum aflopend',
                      'title_asc' => 'titel oplopend', 'title_desc' => 'titel aflopend',
											'city_asc'	=> 'plaats oplopend', 'city_desc'	=> 'plaats aflopend',
                      'price_asc' => 'prijs oplopend', 'price_desc' => 'prijs aflopend');
    if (!empty($extraOptions))
      $options += $extraOptions;

    $defaultValue = is_tax('yog_category') ? get_option('yog_order') : '';

    echo $this->renderElement('Sorteren op', $this->renderSelect('order', $options, $defaultValue));
  }

  /**
  * @desc Render element for search form
  *
  * @param string $metaKey
  * @param string $content
	* @param string $extraClass (optional)
	* @param string|null $title (optional)
  * @return string
  */
  protected function renderElement($metaKey, $content, $extraClass = '', $title = null)
  {
    $html = '';

    if (!empty($content))
    {
      $fieldsSettings = $this->retrieveFieldsSettings();
      $options        = $fieldsSettings->getField($metaKey);
      $id             = str_replace($this->getPostType() . '_', '', $metaKey) . '-holder';

			if (is_null($title))
				$title        = empty($options['title']) ? str_replace($this->getPostType() . '_', '', $metaKey) : $options['title'];

			// Add title addition for BOG rental price
			if (!empty($options['search']) && $options['search'] === 'bog-rental-price-range')
			{
				switch (get_option('yog_search_bog_rentalprice_type', ''))
				{
					case 'pm':
						$title .= ' per maand';
						break;
					case 'pj':
						$title .= ' per jaar';
						break;
				}
			}

      $html = '<div class="' . $this->getClassName() . '-element' . (empty($extraClass) ? '' : ' ' . $extraClass) . '" id="' . $id . '">';
        $html .= '<h5>' . $title . '</h5>';
        $html .= '<div class="' . $this->getClassName() . '-content">';
        $html .= $content;
        $html .= '</div>';
      $html .= '</div>';
    }

    return $html;
  }

  /**
  * @desc Render a slider element
  *
  * @param string $fieldName
  * @param int $min
  * @param int $max
  * @return string
  */
  protected function renderSlider($fieldName, $min, $max, $labelPrefix = '', $labelSuffix = '')
  {
    $html = '';

    if ($min >= 0 && $max > 0 && $min < $max)
    {
      if (!empty($_REQUEST[$fieldName . '_min']))
        $minValue = (int) $_REQUEST[$fieldName . '_min'];
      else
        $minValue = $min;

      if (!empty($_REQUEST[$fieldName . '_max']))
        $maxValue = (int) $_REQUEST[$fieldName . '_max'];
      else
        $maxValue = $max;

      $html .= '<div class="yog-form-slider-holder" id="' . $this->getPostType() . '-' . $fieldName . '-holder">';
        $html .= '<span class="yog-form-slider-settings" style="display:none;">{"min":' . $min . ', "max":' . $max . '}</span>';
        $html .= '<div class="yog-form-slider-labels"  style="display:none;">';
          $html .= '<span class="yog-form-slider-min-label-prefix">' . $labelPrefix . '</span><span class="yog-form-slider-min-label yog-form-slider-label"></span><span class="yog-form-slider-min-label-suffix">' . $labelSuffix . '</span> - <span class="yog-form-slider-max-label-prefix">' . $labelPrefix . '</span><span class="yog-form-slider-max-label yog-form-slider-label"></span><span class="yog-form-slider-max-label-suffix">' . $labelSuffix . '</span>';
        $html .= '</div>';
        $html .= '<input type="text" name="' . esc_attr($fieldName) . '_min" class="yog-form-slider-min yog-object-form-elem" value="' . esc_attr($minValue) . '" />';
        $html .= '<input type="text" name="' . esc_attr($fieldName) . '_max" class="yog-form-slider-max yog-object-form-elem" value="' . esc_attr($maxValue) . '" />';
        $html .= '<div class="yog-form-slider"></div>';
      $html .= '</div>';
    }

    return $html;
  }

  /**
  * @desc Render checkboxes for seach form
  *
  * @param string $fieldName
  * @param array $values
  * @return string
  */
  protected function renderCheckBoxes($fieldName, $values, $defaultChecked = array())
  {
    $html = '';

    if (count($values) > 1)
    {
      $checked = array();
      if (!empty($_REQUEST[$fieldName]))
      {
        if (is_array($_REQUEST[$fieldName]))
          $checked = $_REQUEST[$fieldName];
        else
          $checked = array($_REQUEST[$fieldName]);
      }
      else
      {
        $checked = $defaultChecked;
      }

      foreach ($values as $key => $value)
      {
        $html .= '<div class="' . $this->getClassName() . '-row">';
        $html .= '<input type="checkbox" name="' . esc_attr($fieldName) . '[]" id="' . esc_attr($fieldName . '' . $key) . '" value="' . esc_attr($value) . '"' . (in_array($value, $checked) ? ' checked="checked"' : '') . ' class="yog-object-form-elem" /> <label for="' . $fieldName . '' . $key . '">' . esc_html($value) . '</label>';
        $html .= '</div>';
      }
    }

    return $html;
  }

  /**
  * @desc Render a multiselect for search form
  *
  * @param string $fieldName
  * @param array $values
  * @return string
  */
  protected function renderMultiSelect($fieldName, $values)
  {
    $html = '';
    if (count($values) > 0)
    {
      $selected = array();
      if (!empty($_REQUEST[$fieldName]))
      {
        if (is_array($_REQUEST[$fieldName]))
          $selected = $_REQUEST[$fieldName];
        else
          $selected = array($_REQUEST[$fieldName]);
      }

      $html = '<div class="' . $this->getClassName() . '-row">';
      $html .= '<select name="' . esc_attr($fieldName) . '[]" id="' . esc_attr($fieldName) . '" multiple="mulitple" class="yog-object-form-elem ' . $this->getClassName() . '-multiselect">';

        foreach ($values as $key => $value)
        {
          $html .= '<option value="' . esc_attr($value). '"' . (in_array($value, $selected) ? ' selected="selected"' : '') . '>' . esc_html($value) . '</option>';
        }

        $html .= '</select>';
      $html .= '</div>';
    }

    return $html;
  }

  /**
  * @desc Render a select for search form
  *
  * @param string $fieldName
  * @param array $values
  * @return string
  */
  protected function renderSelect($fieldName, $values, $defaultValue = null, $extraClass = '')
  {
    $html = '';
    if (count($values) > 0)
    {
      if (count($values) == 1) // If only 1 value and the value is the select all label don't render
      {
        $keys = array_keys($values);

        if (isset($keys[0]) && empty($keys[0]))
          return '';

      }

      $selected = '';
      if (!empty($_REQUEST[$fieldName]))
        $selected = stripslashes_deep($_REQUEST[$fieldName]);
      else
        $selected = $defaultValue;

      // Fix, some fields might be multiple or single select but in single select mode also return an array
      if (is_array($selected))
        $selected = array_shift($selected);

      if (!empty($extraClass))
        $extraClass = ' ' . $extraClass;

      $html = '<div class="' . $this->getClassName() . '-row">';
      $html .= '<select name="' . esc_attr($fieldName) . '" id="' . esc_attr($fieldName) . '" class="yog-object-form-elem ' . $this->getClassName() . '-select' . $extraClass . '">';

        foreach ($values as $key => $value)
        {
          $html .= '<option value="' . esc_attr($key) . '"' . (($key == $selected) ? ' selected="selected"' : '') . '>' . esc_html(__($value, YOG_TRANSLATION_TEXT_DOMAIN)) . '</option>';
        }

        $html .= '</select>';
      $html .= '</div>';
    }

    return $html;
  }

  /**
   * Filter integer options with the max existing value
   *
   * @param array $possibleOptions
   * @param int $maxValue
   * @return array
   */
  protected function filterIntMaxOptions($possibleOptions, $maxValue)
  {
    $options = array();
    foreach ($possibleOptions as $option => $label)
    {
      if ($option <= $maxValue)
        $options[$option] = $label;
    }

    return $options;
  }

  /**
  * @desc Get widget classname
  *
  * @param void
  * @return string
  */
  abstract protected function getClassName();

  /**
  * @desc Get widget post type
  *
  * @param void
  * @return string
  */
  abstract protected function getPostType();
}