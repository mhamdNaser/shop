<?php
require_once(YOG_PLUGIN_DIR . '/includes/config/config.php');
require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_fields_settings.php');
require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_object_search_manager.php');
require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_search_form_widget_abstract.php');

/**
* @desc YogSearchFormNBtyWidget
* @author Kees Brandenburg - Yes-co Nederland
*/
class YogSearchFormNBtyWidget extends YogSearchFormWidgetAbstract
{
  const NAME        = 'Yes-co Nieuwbouw Types zoeken';
  const DESCRIPTION = 'Zoek formulier voor nieuwbouw types';
  const CLASSNAME   = 'yog-object-search';

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

    parent::__construct(false, self::NAME, $options);

    if (!is_admin())
    {
      // Do ajax search when needed
      add_action('get_header', array($this, 'retrieveNumberOfPosts'));
    }
  }

  /**
  * @desc Get the widget classname
  *
  * @param void
  * @return string
  */
  protected function getClassName()
  {
    return self::CLASSNAME;
  }

  /**
  * @desc Get the post type
  *
  * @param void
  * @return string
  */
  protected function getPostType()
  {
    return YOG_POST_TYPE_NBTY;
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
		// Enqueue scripts / css
		$minifyExtension = (YOG_DEBUG_MODE === true) ? '' : '.min';
		wp_enqueue_script('yog-form-slider', YOG_PLUGIN_URL .'/inc/js/form-slider' . $minifyExtension . '.js', array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-touch-punch', 'jquery-ui-slider'), YOG_PLUGIN_VERSION, true);
		wp_enqueue_script('yog-search-form-widget', YOG_PLUGIN_URL .'/inc/js/yog_search_form_widget' . $minifyExtension . '.js', array('jquery'), YOG_PLUGIN_VERSION, true);
		wp_enqueue_style('yog-widgets-css', YOG_PLUGIN_URL . '/inc/css/widgets.css', array(), YOG_PLUGIN_VERSION);

    // Retrieve managers
    $searchManager    = YogObjectSearchManager::getInstance();

    // Retrieve arguments
    $beforeWidget     = isset($args['before_widget']) ? $args['before_widget'] : '';
    $afterWidget      = isset($args['after_widget']) ? $args['after_widget'] : '';
    $beforeTitle      = isset($args['before_title']) ? $args['before_title'] : '';
    $afterTitle       = isset($args['after_title']) ? $args['after_title'] : '';

    // Retrieve settings
    $title            = empty($instance['title']) ? '' : esc_attr($instance['title']);
    $useCurrentCat    = empty($instance['use_cur_cat']) ? false : true;
    $showPriceType    = empty($instance['show_price_type']) ? false : true;
    $showPrice        = empty($instance['show_price']) ? false : true;
    $showCity         = empty($instance['show_city']) ? false : true;
    $showObjectKind   = empty($instance['show_object_kind']) ? false : true;
    $showObjectType   = empty($instance['show_object_type']) ? false : true;
    $showLivingSpace  = empty($instance['show_living_space']) ? false : true;
    $showVolume       = empty($instance['show_volume']) ? false : true;
    $showOrder        = empty($instance['show_order']) ? false : true;
    $useSelect        = empty($instance['use_select']) ? false : true;
    $params           = array();

    // Use current category name in widget title?
    if (is_category())
    {
      $title = str_replace('%category%', single_cat_title('', false), $title);
    }

    // Output widget
    echo $beforeWidget;
    echo $beforeTitle . esc_html($title) . $afterTitle;
    echo '<form method="get" class="yog-search-form-widget ' . self::CLASSNAME . '" id="yog-nbty-search-form-widget" action="' . get_bloginfo('url') . '/">';
    echo '<div style="display:none;">';
      echo '<input type="hidden" name="s" value=" " />';
      echo '<input type="hidden" name="object_type" value="' . esc_attr($this->getPostType()) . '" />';

    // Only use object specs of current category?
    if (is_category() && $useCurrentCat)
    {
      echo '<input type="hidden" name="cat" value="' . esc_attr(get_query_var('cat')) . '" />';
      $params['cat'] = get_query_var('cat');
    }
		else if (is_tax('yog_category') && $useCurrentCat)
		{
		  echo '<input type="hidden" name="objecten" value="' . esc_attr(get_query_var('objecten')) . '" />';
      $params['objecten'] = get_query_var('objecten');
		}

    echo '</div>';

    // Prijs type
    if ($showPriceType === true)
      echo $this->renderElement('yog-nbty_PrijsType', $this->renderCheckBoxes('PrijsType', array('Koop', 'Huur')), 'price-type-holder');

    // Prijs
    if ($showPrice === true)
    {
      $maxOption  = $searchManager->retrieveMaxMetaValue(array('yog-nbty_KoopPrijsMax', 'yog-nbty_HuurPrijsMax'), $params);

      if ($useSelect)
      {
        $availableOptions = array(0 => '&euro; 0', 100 => '&euro; 100', 200 => '&euro; 200', 300 => '&euro; 300', 400 => '&euro; 400', 500 => '&euro; 500', 600 => '&euro; 600', 700 => '&euro; 700', 800 => '&euro; 800', 900 => '&euro; 900', 1000 => '&euro; 1.000', 1250 => '&euro; 1.250', 1500 => '&euro; 1.500', 1750 => '&euro; 1.750', 2000 => '&euro; 2.000', 2500 => '&euro; 2.500', 3000 => '&euro; 3.000', 4000 => '&euro; 4.000', 5000 => '&euro; 5.000', 6000 => '&euro; 6.000',
                                  50000 => '&euro; 50.000', 75000 => '&euro; 75.000', 100000 => '&euro; 100.000', 125000 => '&euro; 125.000', 150000 => '&euro; 150.000', 175000 => '&euro; 175.000', 200000 => '&euro; 200.000', 225000 => '&euro; 225.000', 250000 => '&euro; 250.000', 275000 => '&euro; 275.000', 300000 => '&euro; 300.000', 325000 => '&euro; 325.000', 350000 => '&euro; 350.000', 375000 => '&euro; 375.000', 400000 => '&euro; 400.000', 450000 => '&euro; 450.000',
                                  500000 => '&euro; 500.000', 550000 => '&euro; 550.000', 600000 => '&euro; 600.000', 650000 => '&euro; 650.000', 700000 => '&euro; 700.000', 750000 => '&euro; 750.000', 800000 => '&euro; 800.000', 900000 => '&euro; 900.000', 1000000 => '&euro; 1.000.000', 1250000 => '&euro; 1.250.000', 1500000 => '&euro; 1.500.000', 20000000 => '&euro; 2.000.000');
        $minOptions       = $this->filterIntMaxOptions($availableOptions, $maxOption);

        if (count($minOptions) > 1)
        {
          $maxOptions       = $minOptions;
          unset($maxOptions[0]);
          $maxOptions[$maxOption]    = 'Geen maximum';

          echo $this->renderElement('Prijs', $this->renderSelect('Prijs_min', $minOptions, 0, 'value-min') .
                                            '<span class="' . $this->getClassName() . '-sep"> t/m </span>' .
                                            $this->renderSelect('Prijs_max', $maxOptions, $maxOption, 'value-max'), 'minmax-holder');
        }
      }
      else
      {
        echo $this->renderElement('Prijs', $this->renderSlider('Prijs', $searchManager->retrieveMinMetaValue(array('yog-nbty_KoopPrijsMin', 'yog-nbty_HuurPrijsMin'), $params), $maxOption, '&euro; '));
      }
    }

    // Plaats (display cities of NBpr's, cause NBty doesn't contain city)
    if ($showCity === true)
      echo $this->renderElement('yog-nbpr_Plaats', $this->renderMultiSelect('Plaats', $searchManager->retrieveMetaList('yog-nbpr_Plaats', $params)));

    // Soort Woning
    if ($showObjectKind === true)
      echo $this->renderElement('yog-nbty_SoortWoning', $this->renderCheckBoxes('SoortWoning', $searchManager->retrieveMetaList('yog-nbty_SoortWoning', $params)));

    // Type woning
    if ($showObjectType === true)
      echo $this->renderElement('yog-nbty_TypeWoning', $this->renderCheckBoxes('TypeWoning', $searchManager->retrieveMetaList('yog-nbty_TypeWoning', $params)));

    // Oppervlakte
    if ($showLivingSpace === true)
    {
      $maxOption  = $searchManager->retrieveMaxMetaValue('yog-nbty_WoonOppervlakteMax', $params);

      if ($useSelect)
      {
        $options = $this->filterIntMaxOptions(array(0 => 'Geen voorkeur', 50 => '50+ m&sup2;', 75 => '75+ m&sup2;', 100 => '100+ m&sup2;', 150 => '150+ m&sup2;', 250 => '250+ m&sup2;'), $maxOption);
        if (count($options) > 1)
        {
          echo '<input type="hidden" name="WoonOppervlakte_max" value="' . esc_attr($maxOption) . '" />';
          echo $this->renderElement('Woon oppervlakte', $this->renderSelect('WoonOppervlakte_min', $options));
        }
      }
      else
      {
        echo $this->renderElement('Woon oppervlakte', $this->renderSlider('WoonOppervlakte', $searchManager->retrieveMinMetaValue('yog-nbty_WoonOppervlakteMin', $params), $maxOption, '', ' m&sup2;'));
      }
    }

    // Inhoud
    if ($showVolume === true)
    {
      $maxOption  = $searchManager->retrieveMaxMetaValue('yog-nbty_InhoudMax', $params);

      if ($useSelect)
      {
        $options = $this->filterIntMaxOptions(array(0 => 'Geen voorkeur', 150 => '150+ m&sup3;', 200 => '200+ m&sup3;', 300 => '300+ m&sup3;', 400 => '400+ m&sup3;', 500 => '500+ m&sup3;', 750 => '750+ m&sup3;', 1000 => '1000+ m&sup3;'), $maxOption);
        if (count($options) > 1)
        {
          echo '<input type="hidden" name="Inhoud_max" value="' . esc_attr($maxOption) . '" />';
          echo $this->renderElement('Inhoud', $this->renderSelect('Inhoud_min', $options));
        }
      }
      else
      {
        echo $this->renderElement('Inhoud', $this->renderSlider('Inhoud', $searchManager->retrieveMinMetaValue('yog-nbty_InhoudMin', $params), $maxOption, '', ' &sup3;'));
      }
    }

    // Order
    if ($showOrder === true)
      $this->renderOrderElement();

    echo '<p class="' . self::CLASSNAME . '-result">Er zijn <span class="object-search-result-num"></span> nieuwbouw types die voldoen aan deze criteria</p>';
    echo '<div><input type="submit" class="' . self::CLASSNAME . '-button" value=" Tonen " /></div>';
    echo '</form>';
    echo $afterWidget;
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
    $instance                       = $old_instance;
    $instance['title']              = empty($new_instance['title']) ? '' : $new_instance['title'];
    $instance['use_cur_cat']        = empty($new_instance['use_cur_cat']) ? 0 : 1;
    $instance['show_price_type']    = empty($new_instance['show_price_type']) ? 0 : 1;
    $instance['show_price']         = empty($new_instance['show_price']) ? 0 : 1;
    $instance['show_city']          = empty($new_instance['show_city']) ? 0 : 1;
    $instance['show_object_kind']   = empty($new_instance['show_object_kind']) ? 0 : 1;
    $instance['show_object_type']   = empty($new_instance['show_object_type']) ? 0 : 1;
    $instance['show_living_space']  = empty($new_instance['show_living_space']) ? 0 : 1;
    $instance['show_volume']        = empty($new_instance['show_volume']) ? 0 : 1;
    $instance['show_order']         = empty($new_instance['show_order']) ? 0 : 1;
    $instance['use_select']         = empty($new_instance['use_select']) ? 0 : 1;

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
    $title            = empty($instance['title']) ? '' : esc_attr($instance['title']);
    $useCurrentCat    = empty($instance['use_cur_cat']) ? false : true;
    $showPriceType    = empty($instance['show_price_type']) ? false : true;
    $showPrice        = empty($instance['show_price']) ? false : true;
    $showCity         = empty($instance['show_city']) ? false : true;
    $showObjectKind   = empty($instance['show_object_kind']) ? false : true;
    $showObjectType   = empty($instance['show_object_type']) ? false : true;
    $showLivingSpace  = empty($instance['show_living_space']) ? false : true;
    $showVolume       = empty($instance['show_volume']) ? false : true;
    $showOrder        = empty($instance['show_order']) ? false : true;
    $useSelect        = empty($instance['use_select']) ? false : true;

    // Title
    echo '<p>';
    echo '<label for="' . esc_attr($this->get_field_id('title')) . '">' . _e('Titel') . ': </label>';
    echo '<input class="widefat" id="' . esc_attr($this->get_field_id('title')) . '" name="' . esc_attr($this->get_field_name('title')) . '" type="text" value="' . esc_attr($title) . '" />';
    echo '</p>';
    echo '<table>';
    // Use current category
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('use_cur_cat')) . '">' . __('Alleen objecten uit huidige category gebruiken') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('use_cur_cat')) . '" name="' . esc_attr($this->get_field_name('use_cur_cat')) . '" type="checkbox" value="1" ' . ($useCurrentCat === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Use select instead of range?
    echo '<tr>';
    echo '<td><label for="' . esc_attr($this->get_field_id('use_select')) . '">' . __('Gebruik dropdown i.p.v. range selectie') . ': </label></td>';
    echo '<td><input id="' . esc_attr($this->get_field_id('use_select')) . '" name="' . esc_attr($this->get_field_name('use_select')) . '" type="checkbox" value="1" ' . ($useSelect === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';

    // Seperator
    echo '<tr><td colspan="2">&nbsp;</td></tr>';

    // Show price type
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_price_type')) . '">' . __('Prijs soort tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_price_type')) . '" name="' . esc_attr($this->get_field_name('show_price_type')) . '" type="checkbox" value="1" ' . ($showPriceType === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show price
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_price')) . '">' . __('Prijs tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_price')) . '" name="' . esc_attr($this->get_field_name('show_price')) . '" type="checkbox" value="1" ' . ($showPrice === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show city
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_city')) . '">' . __('Plaats tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_city')) . '" name="' . esc_attr($this->get_field_name('show_city')) . '" type="checkbox" value="1" ' . ($showCity === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show 'Soort woning'
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_object_kind')) . '">' . __('Soort woning tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_object_kind')) . '" name="' . esc_attr($this->get_field_name('show_object_kind')) . '" type="checkbox" value="1" ' . ($showObjectKind === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show 'Type woning'
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_object_type')) . '">' . __('Type woning tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_object_type')) . '" name="' . esc_attr($this->get_field_name('show_object_type')) . '" type="checkbox" value="1" ' . ($showObjectType === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show living space
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_living_space')) . '">' . __('Woon oppervlakte tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_living_space')) . '" name="' . esc_attr($this->get_field_name('show_living_space')) . '" type="checkbox" value="1" ' . ($showLivingSpace === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show Volume
		echo '<tr>';
		echo '<td>B<label for="' . esc_attr($this->get_field_id('show_volume')) . '">' . __('Inhoud tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_volume')) . '" name="' . esc_attr($this->get_field_name('show_volume')) . '" type="checkbox" value="1" ' . ($showVolume === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show order
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_order')) . '">' . __('Sortering tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_order')) . '" name="' . esc_attr($this->get_field_name('show_order')) . '" type="checkbox" value="1" ' . ($showOrder === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    echo '</table>';

    if (!empty($this->number) && is_numeric($this->number))
      echo '<p>Shortcode: [yog-widget type="searchnbty" id="' . $this->number . '"]</p>';
  }
}