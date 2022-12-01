<?php
/**
* @desc YogContactFormWidget
* @author Kees Brandenburg - Yes-co Nederland
*/
class YogContactFormWidget extends WP_Widget
{
  const NAME                = 'Yes-co Contact formulier';
  const DESCRIPTION         = 'Contact formulier wat direct in je eigen Yes-co systeem binnen komt.';
  const CLASSNAME           = 'yog-contact-form';
  const FORM_ACTION         = 'https://api.yes-co.com/1.0/response';

  const DEFAULT_THANKS_MSG  = 'Het formulier is verzonden, we nemen zo spoedig mogelijk contact met u op.';
  const WIDGET_ID_PREFIX    = 'yogcontactformwidget-';

  /**
  * @desc Constructor
  *
  * @param void
  * @return YogContactFormWidget
  */
  public function __construct()
  {
    $options = array( 'classsname'  => self::CLASSNAME,
                      'description' => self::DESCRIPTION);

    parent::__construct(false, $name = self::NAME, $options);
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



    return $settings;
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
    $title          = apply_filters('widget_title', $instance['title']);
    $yescoKey       = empty($instance['yesco_key']) ? '' : $instance['yesco_key'];
    $placeholder    = (!empty($instance['placeholder']) && $instance['placeholder'] == '1') ? true : false;
    $actions        = empty($instance['actions']) ? '' : $instance['actions'];
    $tagObject      = empty($instance['tag_object']) ? '' : $instance['tag_object'];
    $tagRelation    = empty($instance['tag_relation']) ? '' : $instance['tag_relation'];
    $roleRelation   = empty($instance['role_relation']) ? '' : esc_attr($instance['role_relation']);
    $thanksMsg      = empty($instance['thanks_msg']) ? self::DEFAULT_THANKS_MSG : $instance['thanks_msg'];
    $showFirstname  = empty($instance['show_firstname']) ? false : true;
    $showLastname   = empty($instance['show_lastname']) ? false : true;
    $showEmail      = empty($instance['show_email']) ? false : true;
    $showPhone      = empty($instance['show_phone']) ? false : true;
    $showAddress    = empty($instance['show_address']) ? false : true;
    $showRemarks    = empty($instance['show_remarks']) ? false : true;
    $showNewsletter = empty($instance['show_newsletter']) ? false : true;
    $showAvg        = empty($instance['show_avg']) ? false : true;
    $widgetId       = empty($args['widget_id']) ? 0 : str_replace(self::WIDGET_ID_PREFIX, '', $args['widget_id']);
    $jsShow         = empty($instance['js_show']) ? '' : esc_attr($instance['js_show']);
    $jsSend         = empty($instance['js_send']) ? '' : esc_attr($instance['js_send']);
		$sendWidgetId		= empty($_GET['send']) ? null : sanitize_text_field($_GET['send']);

    // Use the default key if it is not overwritten
    $yescoDefaultKey = (get_option('yog_response_forms_api_key') ? get_option('yog_response_forms_api_key') :'');

    if (empty($yescoKey))
      $yescoKey = $yescoDefaultKey;

    $htmlStyle      = (get_option('yog_html_style') ? get_option('yog_html_style') : 'basic');

    // Input classes
    $inputClass               = '';
    $inputCheckboxClass       = '';
    $inputCheckboxLabelClass  = '';
    $isBootstrap4             = false;

    if ($htmlStyle == 'bootstrap4')
    {
      $inputClass               = ' class="form-control"';
      $inputCheckboxClass       = ' class="form-check-input"';
      $inputCheckboxLabelClass  = ' class="form-check-label"';
      $isBootstrap4             = true;
    }

    if (!is_null($sendWidgetId) && $sendWidgetId == $widgetId)
    {
      // Show thank you page
      echo $args['before_widget'];
      if (!empty($title))
        echo $args['before_title'] . esc_html($title) . $args['after_title'];

      if ($isBootstrap4)
        echo '<div class="alert alert-success" role="alert"><i class="fas fa-check"></i></span> ' . esc_html($thanksMsg) . '</div>';
      else
        echo '<p>' . esc_html($thanksMsg) . '</p>';

      echo $args['after_widget'];

      if (!empty($jsSend))
        wp_enqueue_script('widget-' . $widgetId . '-send-js', $jsSend);
    }
    else if (!empty($yescoKey))
    {
      if (!empty($jsShow))
        wp_enqueue_script('widget-' . $widgetId . '-show-js', $jsShow);

      // Show form
      if (!empty($_SERVER['HTTP_HOST']))
      {
				$ssl					 = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
        $thankYouPage  = ($ssl === true ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $thankYouPage .= ((strpos($thankYouPage, '?') === false) ? '?' : '&amp;') . 'send=' . $widgetId;
      }

      echo $args['before_widget'];
      if (!empty($title))
        echo $args['before_title'] . esc_html($title) . $args['after_title'];

      echo '<form method="post" action="#" onsubmit="this.action = \'' . self::FORM_ACTION . '\';">';
        echo '<input type="hidden" name="yesco_key" value="' . esc_attr($yescoKey) . '" />';
				echo '<input type="hidden" name="charset" value="' . esc_attr(get_bloginfo('charset')) . '" />';
        echo '<input type="hidden" name="title" value="' . esc_attr($title) . '" />';
        echo '<input type="hidden" name="source" value="' . esc_attr(get_bloginfo('name')) . '" />';

        if (!empty($tagObject))
          echo '<input type="hidden" name="project_tags[]" value="' . esc_attr($tagObject) . '" />';

        if (!empty($tagRelation))
          echo '<input type="hidden" name="person_tags[]" value="' . esc_attr($tagRelation) . '" />';

        if (!empty($roleRelation))
          echo '<input type="hidden" name="project_role" value="' . esc_attr($roleRelation) . '" />';

        if (!empty($thankYouPage))
          echo '<input type="hidden" name="thank_you_page" value="' . esc_url($thankYouPage) . '" />';

        if (is_single() && yog_isObject())
        {
          $projectApiKey = yog_retrieveSpec('ApiKey');
          if (!empty($projectApiKey))
            echo '<input type="hidden" name="project_id"  value="' . esc_attr($projectApiKey). '" />';
        }

        // First name
        if ($showFirstname)
        {
          $label = __('Voornaam', YOG_TRANSLATION_TEXT_DOMAIN);

          $extraParams = '';

          if ($placeholder)
          {
            $extraParams = 'placeholder="' . esc_attr($label) . '" ';
            $label = '';
          }
          else
          {
            $label = '<label for="person[firstname]">' . esc_html($label) . ':</label>';
          }

          $value = '<input ' . $inputClass . ' type="text" name="person[firstname]" id="person[firstname]" value="" ' . $extraParams . '/>';

          echo $this->renderRow($label, $value, $htmlStyle, false);
        }
        // Achternaam
        if ($showLastname)
        {
          $label = __('Achternaam', YOG_TRANSLATION_TEXT_DOMAIN);

          $extraParams = '';

          if ($placeholder)
          {
            $extraParams = 'placeholder="' . esc_attr($label) . '" ';
            $label = '';
          }
          else
          {
            $label = '<label for="person[lastname]">' . esc_html($label) . ':</label>';
          }

          $value = '<input ' . $inputClass . ' type="text" name="person[lastname]" id="person[lastname]" value="" required ' . $extraParams . '/>';

          echo $this->renderRow($label, $value, $htmlStyle, false);
        }
        // E-mail
        if ($showEmail)
        {
          $label = __('E-mail', YOG_TRANSLATION_TEXT_DOMAIN);

          $extraParams = '';

          if ($placeholder)
          {
            $extraParams = 'placeholder="' . esc_attr($label) . '" ';
            $label = '';
          }
          else
          {
            $label = '<label for="person[email]">' . esc_html($label) . ':</label>';
          }

          $value = '<input ' . $inputClass . ' type="text" name="person[email]" id="person[email]" value="" required ' . $extraParams . '/>';

          echo $this->renderRow($label, $value, $htmlStyle, false);
        }
        // Telephone
        if ($showPhone)
        {
          $label = __('Telefoon', YOG_TRANSLATION_TEXT_DOMAIN);

          $extraParams = '';

          if ($placeholder)
          {
            $extraParams = 'placeholder="' . esc_attr($label) . '" ';
            $label = '';
          }
          else
          {
            $label = '<label for="person[phone]">' . esc_html($label) . ':</label>';
          }

          $value = '<input ' . $inputClass . ' type="text" name="person[phone]" id="person[phone]" value="" ' . $extraParams . '/>';

          echo $this->renderRow($label, $value, $htmlStyle, false);
        }
        // Address
        if ($showAddress)
        {
          $label = __('Straat', YOG_TRANSLATION_TEXT_DOMAIN);

          $extraParams = '';

          if ($placeholder)
          {
            $extraParams = 'placeholder="' . esc_attr($label) . '" ';
            $label = '';
          }
          else
          {
            $label = '<label for="person[street]">' . esc_html($label) . ':</label>';
          }

          $value = '<input ' . $inputClass . ' type="text" name="person[street]" id="person[street]" value="" ' . $extraParams . '/>';

          echo $this->renderRow($label, $value, $htmlStyle, false);


          // Housenumber
          $label = __('Huisnummer / postcode', YOG_TRANSLATION_TEXT_DOMAIN);

          $extraParams = '';
          $extraParams2 = '';

          if ($placeholder)
          {
            $extraParams = 'placeholder="' . __('Huisnr', YOG_TRANSLATION_TEXT_DOMAIN) . '" ';
            $extraParams2 = 'placeholder="' . __('Postcode', YOG_TRANSLATION_TEXT_DOMAIN) . '" ';

            $label = '';
          }
          else
          {
            $label = '';

            if ($isBootstrap4)
              $label .= '<div class="col-6">';

              $label .= '<label for="personHousenumber" class="label-housenumber">' . __('Huisnummer', YOG_TRANSLATION_TEXT_DOMAIN) . ':</label>';

            if ($isBootstrap4)
              $label .= '</div><div class="col-6">';

              $label .= '<label for="personZipcode" class="label-zipcode">' . ($isBootstrap4 ? __('Postcode', YOG_TRANSLATION_TEXT_DOMAIN) . ':' : '/ ' . __('postcode', YOG_TRANSLATION_TEXT_DOMAIN) . ':') . '</label><br />';

            if ($isBootstrap4)
              $label .= '</div>';

          }

            //echo '<label for="personHousenumber" class="label-housenumber">Huisnummer</label><label for="personZipcode" class="label-zipcode"> / Postcode:</label><br />';

          $value = '';

          if ($isBootstrap4)
            $value .= '<div class="col-6">';

          $value .= '<input ' . $inputClass . ' type="text" name="person[housenumber]" id="personHousenumber" value="" ' . $extraParams . '/>';

          if ($isBootstrap4)
            $value .= '</div><div class="col-6">';

            $value .= '<input ' . $inputClass . ' type="text" name="person[zipcode]" id="personZipcode" value="" ' . $extraParams2 . '/>';

          if ($isBootstrap4)
            $value .= '</div>';

          echo $this->renderRow($label, $value, $htmlStyle, true);


          // City
          $label = __('Plaats', YOG_TRANSLATION_TEXT_DOMAIN);

          $extraParams = '';

          if ($placeholder)
          {
            $extraParams = 'placeholder="' . esc_attr($label) . '" ';
            $label = '';
          }
          else
          {
            $label = '<label for="person[city]">' . esc_html($label) . ':</label>';
          }

          $value = '<input ' . $inputClass . ' type="text" name="person[city]" id="person[city]" value="" ' . $extraParams . '/>';

          echo $this->renderRow($label, $value, $htmlStyle, false);
        }
        // Actions
        if (!empty($actions))
        {
          $actions = explode("\n", $actions);

          if ($isBootstrap4)
          {
            echo '<div class="form-group"><span>' . __('Acties', YOG_TRANSLATION_TEXT_DOMAIN) . ':</span><br />';
          }
          else
          {
            echo '<p>';
            echo '<label>' . __('Acties', YOG_TRANSLATION_TEXT_DOMAIN) . ':</label><br />';
          }

            foreach ($actions as $key => $action)
            {
              $label = '<label ' . $inputCheckboxLabelClass . ' for="actions_' . esc_attr($key) . '">' . esc_html($action) . '</label><br />';
              $value = '<input ' . $inputCheckboxClass . 'type="checkbox" name="actions[]" id="actions_' . esc_attr($key) . '" value="' . esc_attr($action) . '" /> ';

              echo $this->renderRowCheckbox($label, $value, $htmlStyle);
            }

          if ($isBootstrap4)
          {
            echo '</div>';
          }
          else
          {
            echo '</p>';
          }
        }
        // Opmerkingen
        if ($showRemarks)
        {
          $label = __('Opmerkingen', YOG_TRANSLATION_TEXT_DOMAIN);

          $extraParams = '';

          if ($placeholder)
          {
            $extraParams = 'placeholder="' . esc_attr($label) . '" ';
            $label = '';
          }
          else
          {
            $label = '<label for="comments">' . esc_html($label) . ':</label>';
          }

          $value = '<textarea ' . $inputClass . ' name="comments" id="comments" ' . $extraParams . '></textarea>';

          echo $this->renderRow($label, $value, $htmlStyle, false);
        }

        // Newsletter
        if ($showNewsletter)
        {
          if ($isBootstrap4)
            echo '<div class="form-group">';

            $label = '<label ' . $inputCheckboxLabelClass . ' for="person_tag_nieuwsbrief">' . __('Schrijf mij in voor uw nieuwbrief', YOG_TRANSLATION_TEXT_DOMAIN) . '</label>';
          $value = '<input ' . $inputCheckboxClass . ' type="checkbox" name="person_tags[]" id="person_tag_nieuwsbrief" value="nieuwsbrief" />';

          echo $this->renderRowCheckbox($label, $value, $htmlStyle);

          if ($isBootstrap4)
            echo '</div>';

        }

        // Avg
        if ($showAvg)
        {
          if ($isBootstrap4)
            echo '<div class="form-group">';

          $label = '<label ' . $inputCheckboxLabelClass . ' for="avg_agreement">' . __('AVG: Ik sta toe dat %s contact met mij opneemt a.d.h.v. bovenstaande gegevens', YOG_TRANSLATION_TEXT_DOMAIN) . '</label>';

          if (preg_match('/%s/', $label))
            $label = str_replace('%s', esc_html(get_bloginfo('name')), $label);

          $value = '<input ' . $inputCheckboxClass . ' type="checkbox" name="avg_agreement" id="avg_agreement" value="yes" required />';

          echo $this->renderRowCheckbox($label, $value, $htmlStyle, true);

          if ($isBootstrap4)
            echo '</div>';

        }

        if ($isBootstrap4)
          echo '<input class="btn btn-primary" type="submit" value="' . __('Versturen', YOG_TRANSLATION_TEXT_DOMAIN) . '" />';
        else
          echo '<p><label>&nbsp;</label><input type="submit" value="' . __('Versturen', YOG_TRANSLATION_TEXT_DOMAIN) . '" /></p>';

      echo '</form>';

      echo $args['after_widget'];

			// wp_enqueue_script('yog-response-forms', self::JS_LOCATION, array(), false, true);
    }
  }

  /**
   * Method renderRow
   *
   * @param unknown $label
   * @param unknown $value
   * @param string $htmlStyle
   * @param boolean $multiColumns
   */
  public function renderRow($label, $value, $htmlStyle, $multiColumns = false)
  {
    $html = '';

    switch ($htmlStyle)
    {
      case 'bootstrap4':

        if ($multiColumns)
          $html .= '<div class="form-group row">';
        else
          $html .= '<div class="form-group">';

        // Don't escape, input should be escaped and can contain html by purpose
        $html .= $label;
        $html .= $value;

        $html .= '</div>';

        break;


      default:

        $html .= '<p>';

        // Don't escape, input should be escaped and can contain html by purpose
        $html .= $label;
        $html .= $value;

        $html .= '</p>';

        break;
    }

    return $html;
  }

  /**
   * Method renderRowCheckbox
   *
   * @param unknown $label
   * @param unknown $value
   */
  public function renderRowCheckbox($label, $value, $htmlStyle, $required = false)
  {
    $html = '';

    switch ($htmlStyle)
    {
      case 'bootstrap4':

        $html .= '<div class="form-check"' . ($required ? ' data-validate="checkbox-required"' : '') . '>';

        // Don't escape, input should be escaped and can contain html by purpose
        $html .= $value;
        $html .= $label;


        $html .= '</div>';

        break;

      default:

        // Don't escape, input should be escaped and can contain html by purpose
        $html .= $value;
        $html .= $label;

        break;
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
    $instance['title']            = empty($new_instance['title']) ? '' : $new_instance['title'];
    $instance['yesco_key']        = empty($new_instance['yesco_key']) ? '' : $new_instance['yesco_key'];
    $instance['placeholder']      = empty($new_instance['placeholder']) ? '' : $new_instance['placeholder'];
    $instance['actions']          = empty($new_instance['actions']) ? '' : $new_instance['actions'];
    $instance['thanks_msg']       = empty($new_instance['thanks_msg']) ? '' : $new_instance['thanks_msg'];
    $instance['tag_object']       = empty($new_instance['tag_object']) ? '' : trim($new_instance['tag_object']);
    $instance['tag_relation']     = empty($new_instance['tag_relation']) ? '' : trim($new_instance['tag_relation']);
    $instance['role_relation']    = empty($new_instance['role_relation']) ? '' : trim($new_instance['role_relation']);
    $instance['show_firstname']   = empty($new_instance['show_firstname']) ? 0 : 1;
    $instance['show_lastname']    = empty($new_instance['show_lastname']) ? 0 : 1;
    $instance['show_email']       = empty($new_instance['show_email']) ? 0 : 1;
    $instance['show_phone']       = empty($new_instance['show_phone']) ? 0 : 1;
    $instance['show_address']     = empty($new_instance['show_address']) ? 0 : 1;
    $instance['show_remarks']     = empty($new_instance['show_remarks']) ? 0 : 1;
    $instance['show_newsletter']  = empty($new_instance['show_newsletter']) ? 0 : 1;
    $instance['show_avg']         = empty($new_instance['show_avg']) ? 0 : 1;

    $filterJsShow = filter_var($new_instance['js_show'], FILTER_VALIDATE_URL);
    $filterJsSend = filter_var($new_instance['js_send'], FILTER_VALIDATE_URL);

    $instance['js_show']          = ($filterJsShow === false) ? '' : $filterJsShow;
    $instance['js_send']          = ($filterJsSend === false) ? '' : $filterJsSend;

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
    $title          = empty($instance['title']) ? '' : $instance['title'];
    $yescoKey       = empty($instance['yesco_key']) ? '' : $instance['yesco_key'];
    $placeholder    = empty($instance['placeholder']) ? '' : $instance['placeholder'];
    $actions        = empty($instance['actions']) ? '' : $instance['actions'];
    $thanksMsg      = empty($instance['thanks_msg']) ? self::DEFAULT_THANKS_MSG : $instance['thanks_msg'];
    $tagObject      = empty($instance['tag_object']) ? '' : $instance['tag_object'];
    $tagRelation    = empty($instance['tag_relation']) ? '' : $instance['tag_relation'];
    $roleRelation   = empty($instance['role_relation']) ? '' : $instance['role_relation'];
    $jsShow         = empty($instance['js_show']) ? '' : $instance['js_show'];
    $jsSend         = empty($instance['js_send']) ? '' : $instance['js_send'];

    $showFields = array('show_firstname'  => 'Voornaam',
                        'show_lastname'   => 'Achternaam',
                        'show_email'      => 'E-mail',
                        'show_phone'      => 'Telefoon nummer',
                        'show_address'    => 'Adres',
                        'show_remarks'    => 'Opmerkingen',
                        'show_newsletter' => 'Inschrijven nieuwsbrief',
                        'show_avg' => 'Avg overeenkomst'
    );

    $roles  = array('Geïnteresseerde',
                    'Reserve optant');

    $yescoDefaultKey = (get_option('yog_response_forms_api_key') ? get_option('yog_response_forms_api_key') :'');

    // Widget title
    echo '<p>';
      echo '<label for="' . esc_attr($this->get_field_id('title')) . '">' . __('Titel') . ': </label>';
      echo '<input class="widefat" id="' . esc_attr($this->get_field_id('title')) . '" name="' . esc_attr($this->get_field_name('title')) . '" type="text" value="' . esc_attr($title) . '" />';
    echo '</p>';

    // Yes-co Key
    echo '<p>';
    echo '<label for="' . esc_attr($this->get_field_id('yesco_key')) . '">' . __('Standaard yes-co key overschrijven') . ': </label>';
    echo '<input class="widefat" id="' . esc_attr($this->get_field_id('yesco_key')) . '" name="' . esc_attr($this->get_field_name('yesco_key')) . '" type="text" value="' . esc_attr($yescoKey) . '" />';
      echo '<small>' . __('Te achterhalen in Yes-co App Market') . '</small>';

      if (!empty($yescoDefaultKey))
        echo '<small><br /><i>Er is al een ingestelde standaard key in de plugin <b>' . \esc_html($yescoDefaultKey) . '</b></i></small>';

    echo '</p>';

    // Placeholder?
    $show = empty($placeholder) ? false : true;
    echo '<p>';
    echo '<label for="' . esc_attr($this->get_field_id('placeholder')) . '">' . __('Toon labels in velden') . ': </label>';
    echo '<input id="' . esc_attr($this->get_field_id('placeholder')) . '" name="' . esc_attr($this->get_field_name('placeholder')) . '" type="checkbox" value="1" ' . ($show === true ? 'checked="checked" ' : '') . '/>';
    echo '</p>';

    // Fields to show
    echo '<strong>Tonen</strong>';
    echo '<table>';
    foreach ($showFields as $field => $label)
    {
      $show = empty($instance[$field]) ? false : true;
		  echo '<tr>';
		  echo '<td><label for="' . esc_attr($this->get_field_id($field)) . '">' . esc_html(__($label)) . '</label>: </td>';
		  echo '<td><input id="' . esc_attr($this->get_field_id($field)) . '" name="' . esc_attr($this->get_field_name($field)) . '" type="checkbox" value="1" ' . ($show === true ? 'checked="checked" ' : '') . '/></td>';
      echo '</tr>';
    }
    echo '</table>';

    // Actions to use
    echo '<p>';
    echo '<label for="' . esc_attr($this->get_field_id('actions')) . '"><strong>' . __('Acties') . '</strong>&nbsp;<small>(1 actie per regel)</small></label>';
    echo '<textarea name="' . esc_attr($this->get_field_name('actions')) . '" id="' . esc_attr($this->get_field_id('actions')) . '" class="widefat">' . esc_html($actions) . '</textarea>';
    echo '</p>';

    // Thank you message to show
    echo '<p>';
    echo '<label for="' . esc_attr($this->get_field_id('thanks_msg')) . '"><strong>' . __('Formulier verstuurd boodschap') . '</strong></label>';
    echo '<textarea name="' . esc_attr($this->get_field_name('thanks_msg')) . '" id="' . esc_attr($this->get_field_id('thanks_msg')) . '" class="widefat">' . esc_html($thanksMsg) . '</textarea>';
    echo '</p>';

    // Tags / role
    echo '<p>';
      echo '<strong>Koppelen</strong>&nbsp;<small>(in Yes-co systeem)</small><br />';
      echo '<label for="' . esc_attr($this->get_field_id('tag_object')) . '">Tag aan object: </label>';
      echo '<input class="widefat" id="' . esc_attr($this->get_field_id('tag_object')) . '" name="' . esc_attr($this->get_field_name('tag_object')) . '" type="text" value="' . esc_attr($tagObject) . '" />';
      echo '<label for="' . esc_attr($this->get_field_id('tag_relation')) . '">Tag aan relatie: </label>';
      echo '<input class="widefat" id="' . esc_attr($this->get_field_id('tag_relation')) . '" name="' . esc_attr($this->get_field_name('tag_relation')) . '" type="text" value="' . esc_attr($tagRelation) . '" />';
      echo '<label for="' . esc_attr($this->get_field_id('role_relation')) . '">Relatie aan object als rol: </label><br />';
      echo '<select name="' . esc_attr($this->get_field_name('role_relation')) . '" id="' . esc_attr($this->get_field_id('role_relation')) . '">';
        echo '<option value=""></option>';
        foreach ($roles as $role)
        {
          echo '<option value="' . esc_attr($role) . '"' . ($roleRelation == $role ? ' selected="selected"' : '') . '>' . esc_html($role) . '</option>';
        }
      echo '</select>';
    echo '</p>';

    // Javascript
    echo '<p>';
      echo '<strong>Javascript</strong>&nbsp;<small>(URL inladen)</small><br />';
      echo '<label for="' . esc_attr($this->get_field_id('js_show')) . '">Bij tonen formulier: </label>';
      echo '<input class="widefat" id="' . esc_attr($this->get_field_id('js_show')) . '" name="' . esc_attr($this->get_field_name('js_show')) . '" type="text" value="' . esc_attr($jsShow) . '" />';
      echo '<label for="' . esc_attr($this->get_field_id('js_send')) . '">Bij versturen van formulier: </label>';
      echo '<input class="widefat" id="' . esc_attr($this->get_field_id('js_send')) . '" name="' . esc_attr($this->get_field_name('js_send')) . '" type="text" value="' . esc_attr($jsSend) . '" />';
    echo '</p>';

    if (!empty($this->number) && is_numeric($this->number))
      echo '<p>Shortcode: [yog-widget type="contact" id="' . $this->number . '"]</p>';
  }
}