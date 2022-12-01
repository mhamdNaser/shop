<?php
require_once(YOG_PLUGIN_DIR . '/includes/config/config.php');
require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_fields_settings.php');
require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_object_search_manager.php');
require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_search_form_widget_abstract.php');

/**
* @desc YogSearchFormBogWidget
* @author Kees Brandenburg - Yes-co Nederland
*/
class YogSearchFormBogWidget extends YogSearchFormWidgetAbstract
{
  const NAME        = 'Yes-co BOG Objecten zoeken';
  const DESCRIPTION = 'Zoek formulier voor bedrijfs onroerend goed objecten';
  const CLASSNAME   = 'yog-object-search';

  private $availableRentOptionsPm     = array(0 => '&euro; 0', 500 => '&euro; 500', 750 => '&euro; 750', 1000 => '&euro; 1.000', 1250 => '&euro; 1.250', 1500 => '&euro; 1.500', 1750 => '&euro; 1.750', 2000 => '&euro; 2.000', 2500 => '&euro; 2.500', 3000 => '&euro; 3.000',  3500 => '&euro; 3.500', 4000 => '&euro; 4.000', 4500 => '&euro; 4.500', 5000 => '&euro; 5.000', 7500 => '&euro; 7.500', 10000 => '&euro; 10.000');
  private $availableRentOptionsPj     = array(0 => '&euro; 0', 5000 => '&euro; 5.000', 7500 => '&euro; 7.500', 10000 => '&euro; 10.000', 12500 => '&euro; 12.500', 15000 => '&euro; 15.000', 17500 => '&euro; 17.500', 20000 => '&euro; 20.000', 25000 => '&euro; 25.000', 30000 => '&euro; 30.000',  35000 => '&euro; 35.000', 40000 => '&euro; 40.000', 45000 => '&euro; 45.000', 50000 => '&euro; 50.000', 75000 => '&euro; 75.000', 100000 => '&euro; 100.000');
  private $availableRentOptionsPm2pj  = array(0 => '&euro; 0', 50 => '&euro; 50', 75 => '&euro; 75', 100 => '&euro; 100', 125 => '&euro; 125', 150 => '&euro; 150', 175 => '&euro; 175', 200 => '&euro; 200', 225 => '&euro; 225', 250 => '&euro; 250',  275 => '&euro; 275', 300 => '&euro; 300', 325 => '&euro; 325', 350 => '&euro; 350', 375 => '&euro; 375', 400 => '&euro; 400', 425 => '&euro; 425', 450 => '&euro; 450', 475 => '&euro; 475', 500 => '&euro; 500');

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
    else
    {
      add_action('wp_ajax_nopriv_bogminmaxrentalprices', array($this, 'ajaxRetrieveMinMaxRentalPrices'));
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
    return POST_TYPE_BOG;
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
    $title              = empty($instance['title']) ? '' : esc_attr($instance['title']);
    $useCurrentCat      = empty($instance['use_cur_cat']) ? false : true;
    $showPriceType      = empty($instance['show_price_type']) ? false : true;
    $showPrice          = empty($instance['show_price']) ? false : true;
    $showPriceCondition = empty($instance['show_price_condition']) ? false : true;
    $showKoopPrice      = empty($instance['show_koop_price']) ? false : true;
    $showRentalPrice    = empty($instance['show_rental_price']) ? false : true;
    $showCity           = empty($instance['show_city']) ? false : true;
    $showType           = empty($instance['show_type']) ? false : true;
    $showSurface        = empty($instance['show_surface']) ? false : true;
    $showOrder          = empty($instance['show_order']) ? false : true;
    $useSelect          = empty($instance['use_select']) ? false : true;
    $params             = array();

    // Use current category name in widget title?
    if (is_category())
    {
      $title = str_replace('%category%', single_cat_title('', false), $title);
    }

    // Output widget
    echo $beforeWidget;
    echo $beforeTitle . esc_html($title) . $afterTitle;
    echo '<form method="get" class="yog-search-form-widget ' . self::CLASSNAME . '" id="yog-bog-search-form-widget" action="' . get_bloginfo('url') . '/">';
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
      echo $this->renderElement('bedrijf_PrijsType', $this->renderCheckBoxes('PrijsType', array('Koop', 'Huur')), 'price-type-holder');

    // Prijs conditie
    if ($showPriceCondition === true)
    {
      $values = array_merge($searchManager->retrieveMetaList('bedrijf_KoopPrijsConditie', $params), $searchManager->retrieveMetaList('bedrijf_HuurPrijsConditie', $params));

      echo $this->renderElement('bedrijf_PrijsConditie', $this->renderCheckBoxes('PrijsConditie', $values), 'price-condition-holder');
    }

    // Prijs (koop + huur)
    if ($showPrice === true)
    {
      $maxOption  = $searchManager->retrieveMaxMetaValue(array('bedrijf_KoopPrijs', 'bedrijf_HuurPrijs', 'bedrijf_HuurPrijsMax'), $params);

      if ($useSelect)
      {
        $availableOptions = array(0 => '&euro; 0', 500 => '&euro; 500', 750 => '&euro; 750', 1000 => '&euro; 1.000', 1250 => '&euro; 1.250', 1500 => '&euro; 1.500', 1750 => '&euro; 1.750', 2000 => '&euro; 2.000', 3500 => '&euro; 3.500', 4000 => '&euro; 4.000', 4500 => '&euro; 4.500', 5000 => '&euro; 5.000', 7500 => '&euro; 7.500', 10000 => '&euro; 10.000',
                                  100000 => '&euro; 100.000', 150000 => '&euro; 150.000', 200000 => '&euro; 200.000', 250000 => '&euro; 250.000', 300000 => '&euro; 300.000', 350000 => '&euro; 350.000', 400000 => '&euro; 400.000', 450000 => '&euro; 450.000', 500000 => '&euro; 500.000', 550000 => '&euro; 550.000', 600000 => '&euro; 600.000', 650000 => '&euro; 650.000', 700000 => '&euro; 700.000', 750000 => '&euro; 750.000', 800000 => '&euro; 800.000', 850000 => '&euro; 850.000', 900000 => '&euro; 900.000', 950000 => '&euro; 950.000', 1000000 => '&euro; 1.000.000');
        $minOptions       = $this->filterIntMaxOptions($availableOptions, $maxOption);

        if (count($minOptions) > 1)
        {
          $maxOptions       = $minOptions;
          unset($maxOptions[0]);
          $maxOptions[0]    = 'Geen maximum';

          echo $this->renderElement('Prijs', $this->renderSelect('Prijs_min', $minOptions, null, 'value-min') .
                                            '<span class="' . $this->getClassName() . '-sep"> t/m </span>' .
                                            $this->renderSelect('Prijs_max', $maxOptions, null, 'value-max'), 'minmax-holder');
        }
      }
      else
      {
        echo $this->renderElement('Prijs', $this->renderSlider('Prijs', $searchManager->retrieveMinMetaValue(array('bedrijf_KoopPrijs', 'bedrijf_HuurPrijs'), $params), $maxOption, '&euro; '));
      }
    }

    // Koop Prijs
    if ($showKoopPrice === true)
    {
      $maxOption  = $searchManager->retrieveMaxMetaValue('bedrijf_KoopPrijs', $params);

      if ($useSelect)
      {
        $availableOptions = array(0 => '&euro; 0', 100000 => '&euro; 100.000', 150000 => '&euro; 150.000', 200000 => '&euro; 200.000', 250000 => '&euro; 250.000', 300000 => '&euro; 300.000', 350000 => '&euro; 350.000', 400000 => '&euro; 400.000', 450000 => '&euro; 450.000', 500000 => '&euro; 500.000', 550000 => '&euro; 550.000', 600000 => '&euro; 600.000', 650000 => '&euro; 650.000', 700000 => '&euro; 700.000', 750000 => '&euro; 750.000', 800000 => '&euro; 800.000', 850000 => '&euro; 850.000', 900000 => '&euro; 900.000', 950000 => '&euro; 950.000', 1000000 => '&euro; 1.000.000');
        $minOptions       = $this->filterIntMaxOptions($availableOptions, $maxOption);

        if (count($minOptions) > 1)
        {
          $maxOptions       = $minOptions;
          unset($maxOptions[0]);
          $maxOptions[0]    = 'Geen maximum';

          echo $this->renderElement('Koopprijs', $this->renderSelect('KoopPrijs_min', $minOptions, null, 'value-min') .
                                            '<span class="' . $this->getClassName() . '-sep"> t/m </span>' .
                                            $this->renderSelect('KoopPrijs_max', $maxOptions, null, 'value-max'), 'minmax-holder');
        }
      }
      else
      {
        echo $this->renderElement('Koopprijs', $this->renderSlider('KoopPrijs', $searchManager->retrieveMinMetaValue('bedrijf_KoopPrijs', $params), $maxOption, '&euro; '));
      }
    }

    // Huur Prijs
    if ($showRentalPrice === true)
    {
      $maxOption  = $searchManager->retrieveMaxMetaValue(array('bedrijf_HuurPrijs', 'bedrijf_HuurPrijsMax'), $params);

      if ($useSelect)
      {
        $minOptions       = $this->filterIntMaxOptions($this->availableRentOptionsPm, $maxOption);

        if (count($minOptions) > 1)
        {
          $maxOptions       = $minOptions;
          unset($maxOptions[0]);
          $maxOptions[0]    = 'Geen maximum';

          echo $this->renderElement('Huurprijs', $this->renderSelect('HuurPrijs_min', $minOptions, null, 'value-min') .
                                            '<span class="' . $this->getClassName() . '-sep"> t/m </span>' .
                                            $this->renderSelect('HuurPrijs_max', $maxOptions, null, 'value-max'), 'minmax-holder');
        }
      }
      else
      {
        echo $this->renderElement('Huurprijs', $this->renderSlider('HuurPrijs', $searchManager->retrieveMinMetaValue('bedrijf_HuurPrijs', $params), $maxOption, '&euro; '));
      }
    }

    // Plaats
    if ($showCity === true)
      echo $this->renderElement('bedrijf_Plaats', $this->renderMultiSelect('Plaats', $searchManager->retrieveMetaList('bedrijf_Plaats', $params)));

    // Type woning
    if ($showType === true)
      echo $this->renderElement('bedrijf_Type', $this->renderCheckBoxes('Type', $searchManager->retrieveMetaList('bedrijf_Type', $params)));

    // Oppervlakte
    if ($showSurface === true)
    {
      $maxOption  = $searchManager->retrieveMaxMetaValue('bedrijf_Oppervlakte', $params);

      if ($useSelect)
      {
        $options = $this->filterIntMaxOptions(array(0 => 'Geen minimum', 50 => '50 m&sup2;', 75 => '75 m&sup2;', 100 => '100 m&sup2;', 150 => '150 m&sup2;',
                                                    200 => '200 m&sup2;', 250 => '250 m&sup2;', 300 => '300 m&sup2;', 400 => '400 m&sup2;', 500 => '500 m&sup2;',
                                                    750 => '750 m&sup2;', 1000 => '1.000 m&sup2;', 1500 => '1.500 m&sup2;', 3000 => '3.000 m&sup2;',
                                                    5000 => '5.000 m&sup2;', 7500 => '7.500 m&sup2;'), $maxOption);
        if (count($options) > 1)
        {
          $maxOptions       = $options;
          unset($maxOptions[0]);
          $maxOptions[0]    = 'Geen maximum';

          echo $this->renderElement('bedrijf_Oppervlakte', $this->renderSelect('Oppervlakte_min', $options, null, 'value-min') .
                                                            '<span class="' . $this->getClassName() . '-sep"> t/m </span>' .
                                                            $this->renderSelect('Oppervlakte_max', $maxOptions, null, 'value-max'), 'minmax-holder');
        }
      }
      else
      {
        echo $this->renderElement('bedrijf_Oppervlakte', $this->renderSlider('Oppervlakte', $searchManager->retrieveMinMetaValue('bedrijf_Oppervlakte', $params), $maxOption, '', ' m&sup2;'));
      }
    }

    // Order
    if ($showOrder === true)
      $this->renderOrderElement(array('bog_surface_asc' => 'Oppervlakte oplopend', 'bog_surface_desc' => 'Oppervlakte aflopend'));

    echo '<p class="' . self::CLASSNAME . '-result">Er zijn <span class="object-search-result-num"></span> objecten die voldoen aan deze criteria</p>';
    echo '<div><input type="submit" class="' . self::CLASSNAME . '-button" value=" Tonen " /></div>';
    echo '</form>';
    echo $afterWidget;
  }

  public function ajaxRetrieveMinMaxRentalPrices()
  {
    $searchManager  = YogObjectSearchManager::getInstance();

    // TODO: use current category?

    if (empty($_POST['HuurPrijsConditie']) || in_array('p.m.', $_POST['HuurPrijsConditie']))
      $availableOptions = $this->availableRentOptionsPm;
    else if (in_array('p.j.', $_POST['HuurPrijsConditie']))
      $availableOptions = $this->availableRentOptionsPj;
    else
      $availableOptions = $this->availableRentOptionsPm2pj;
		
		// Sanitize params
		$params = array();
		
    if (!empty($_POST['cat']))
			$params['cat'] = (int) $_POST['cat'];

		if (!empty($_POST['objecten']))
			$params['objecten'] = sanitize_text_field($_POST['objecten']);

    if (!empty($_POST['object_type']))
			$params['object_type'] = sanitize_text_field($_POST['object_type']);

    if (!empty($_POST['HuurPrijsConditie']))
		{
			$params['HuurPrijsConditie'] = array();
			
      if (!is_array($_POST['HuurPrijsConditie']))
			{
        $params['HuurPrijsConditie'][] = sanitize_text_field($_POST['HuurPrijsConditie']);
			}
			else
			{
				foreach ($_POST['HuurPrijsConditie'] as $conditie)
				{
					$params['HuurPrijsConditie'][] = sanitize_text_field($conditie);
				}
			}
		}

    $maxOption  = $searchManager->retrieveMaxMetaValue('bedrijf_HuurPrijs', $params);

    $minOptions = $this->filterIntMaxOptions($availableOptions, $maxOption);

    if (count($minOptions) > 1)
    {
      $maxOptions       = $minOptions;
      unset($maxOptions[0]);
      $maxOptions[0]    = 'Geen maximum';
    }

    echo json_encode(array('from' => $minOptions, 'till' => $maxOptions));
    wp_die();
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
    $instance                         = $old_instance;
    $instance['title']                = empty($new_instance['title']) ? '' : $new_instance['title'];
    $instance['use_cur_cat']          = empty($new_instance['use_cur_cat']) ? 0 : 1;
    $instance['show_price_type']      = empty($new_instance['show_price_type']) ? 0 : 1;
    $instance['show_price']           = empty($new_instance['show_price']) ? 0 : 1;
    $instance['show_price_condition'] = empty($new_instance['show_price_condition']) ? 0 : 1;
    $instance['show_koop_price']      = empty($new_instance['show_koop_price']) ? 0 : 1;
    $instance['show_rental_price']    = empty($new_instance['show_rental_price']) ? 0 : 1;
    $instance['show_city']            = empty($new_instance['show_city']) ? 0 : 1;
    $instance['show_type']            = empty($new_instance['show_type']) ? 0 : 1;
    $instance['show_surface']         = empty($new_instance['show_surface']) ? 0 : 1;
    $instance['show_order']           = empty($new_instance['show_order']) ? 0 : 1;
    $instance['use_select']           = empty($new_instance['use_select']) ? 0 : 1;

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
    $title              = empty($instance['title']) ? '' : esc_attr($instance['title']);
    $useCurrentCat      = empty($instance['use_cur_cat']) ? false : true;
    $showPriceType      = empty($instance['show_price_type']) ? false : true;
    $showPrice          = empty($instance['show_price']) ? false : true;
    $showPriceCondition = empty($instance['show_price_condition']) ? false : true;
    $showKoopPrice      = empty($instance['show_koop_price']) ? false : true;
    $showRentalPrice    = empty($instance['show_rental_price']) ? false : true;
    $showCity           = empty($instance['show_city']) ? false : true;
    $showSurface        = empty($instance['show_surface']) ? false : true;
    $showType           = empty($instance['show_type']) ? false : true;
    $showOrder          = empty($instance['show_order']) ? false : true;
    $useSelect          = empty($instance['use_select']) ? false : true;

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
    // Show price condition
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_price_condition')) . '">' . __('Prijs conditie tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_price_condition')) . '" name="' . esc_attr($this->get_field_name('show_price_condition')) . '" type="checkbox" value="1" ' . ($showPriceCondition === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show price
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_price')) . '">' . __('Prijs tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_price')) . '" name="' . esc_attr($this->get_field_name('show_price')) . '" type="checkbox" value="1" ' . ($showPrice === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show koop price
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_koop_price')) . '">' . __('Koopprijs tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_koop_price')) . '" name="' . esc_attr($this->get_field_name('show_koop_price')) . '" type="checkbox" value="1" ' . ($showKoopPrice === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show rental price
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_rental_price')) . '">' . __('Huurprijs tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_rental_price')) . '" name="' . esc_attr($this->get_field_name('show_rental_price')) . '" type="checkbox" value="1" ' . ($showRentalPrice === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show city
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_city')) . '">' . __('Plaats tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_city')) . '" name="' . esc_attr($this->get_field_name('show_city')) . '" type="checkbox" value="1" ' . ($showCity === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show 'Type'
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_type')) . '">' . __('Type tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_type')) . '" name="' . esc_attr($this->get_field_name('show_type')) . '" type="checkbox" value="1" ' . ($showType === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show 'Surface'
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_surface')) . '">' . __('Oppervlakte tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_surface')) . '" name="' . esc_attr($this->get_field_name('show_surface')) . '" type="checkbox" value="1" ' . ($showSurface === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    // Show order
		echo '<tr>';
		echo '<td><label for="' . esc_attr($this->get_field_id('show_order')) . '">' . __('Sortering tonen') . '</label>: </td>';
		echo '<td><input id="' . esc_attr($this->get_field_id('show_order')) . '" name="' . esc_attr($this->get_field_name('show_order')) . '" type="checkbox" value="1" ' . ($showOrder === true ? 'checked="checked" ' : '') . '/></td>';
    echo '</tr>';
    echo '</table>';

    if (!empty($this->number) && is_numeric($this->number))
      echo '<p>Shortcode: [yog-widget type="searchbog" id="' . $this->number . '"]</p>';
  }
}