<?php
/**
* @desc YogLinkedRelationsWidget
* @author Kees Brandenburg - Yes-co Nederland
*/
class YogLinkedRelationsWidget extends WP_Widget
{
  const NAME                    = 'Yes-co gelinkte relaties';
  const DESCRIPTION             = 'Toont de relaties die aan een object gekoppeld zijn.';
  const CLASSNAME               = 'yog-linked-relations';
  const DEFAULT_SPECS_BUSINESS  = 'Telefoonnummer,Faxnummer,Emailadres,Website,Hoofdadres';
  const DEFAULT_SPECS_PERSON    = 'Telefoonnummer,Faxnummer,Emailadres,Website';
  const DEFAULT_SUPPORTED_ROLES = 'Makelaarskantoor,Binnendienst 1;Makelaar 1';

  /**
  * @desc Constructor
  *
  * @param void
  * @return YogObjectAttachmentsWidget
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
    if (!(is_single() && yog_isObject()))
      return;

    // Retrieve linked relations
    $relations = yog_retrieveRelations();

    if (!empty($relations))
    {
      // Retrieve widget settings
      $title            = apply_filters('widget_title', $instance['title']);
      $specsBusiness    = explode(',', empty($instance['specs_business']) ? self::DEFAULT_SPECS_BUSINESS : $instance['specs_business']);
      $specsPerson      = explode(',', empty($instance['specs_person']) ? self::DEFAULT_SPECS_PERSON : $instance['specs_person']);
      $supportedRoles   = explode(',', empty($instance['supported_roles']) ? self::DEFAULT_SUPPORTED_ROLES : $instance['supported_roles']);
      $beforeWidget     = isset($args['before_widget']) ? $args['before_widget'] : '';
      $afterWidget      = isset($args['after_widget']) ? $args['after_widget'] : '';
      $beforeTitle      = isset($args['before_title']) ? $args['before_title'] : '';
      $afterTitle       = isset($args['after_title']) ? $args['after_title'] : '';

      // Specs to show
      $usedSpecs  = array('Business' => $specsBusiness, 'Person' => $specsPerson);

			// Remove relations with unsupported roles
			$usedRelations	= array();
			foreach ($relations as $role => $relation)
			{
				if (in_array($role, $supportedRoles))
					$usedRelations[$role] = $relation;
			}

      // Show relations
      if (!empty($usedRelations))
      {
				$customTemplate = locate_template('object-relation.php');

				// Show widget start
				echo $beforeWidget;
				echo $beforeTitle . esc_html($title) . $afterTitle;

				// Backup original post
				if ($customTemplate != '')
				{
					global $post;
					$orgPost	= clone $post;
				}
				// Enqueue style
				else
				{
					wp_enqueue_style('yog-widgets-css', YOG_PLUGIN_URL . '/inc/css/widgets.css', array(), YOG_PLUGIN_VERSION);
				}

        echo '<div class="yog-relations-holder">';

				$counter			= 0;
				$numRelations	= count($usedRelations);

        foreach ($usedRelations as $role => $relation)
        {
					$counter++;

					// Retrieve specs
					$type   = yog_retrieveSpec('type', $relation->ID);
					$specs  = yog_retrieveSpecs($usedSpecs[$type], $relation->ID);

					// Handle relation with default styling
					if ($customTemplate == '')
					{
					  echo '<div class="yog-relation yog-relation-' . esc_attr(strtolower($role)) . ' yog-relation-' . esc_attr(strtolower($type)) . '">';
							echo '<h5 class="yog-relation-role">' . esc_html($role) . ':</h5>';
							echo '<div class="yog-relation-value yog-title">' . esc_html(get_the_title($relation->ID)) . '</div>';

							foreach ($specs as $label => $value)
							{
								$label = strtolower($label);

								switch ($label)
								{
									case 'emailadres':
									  $value = '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
										break;
									case 'website':
										$link = $value;
										if (strpos($link, 'http://') === false && strpos($link, 'https://') === false)
											$link = 'http://' . $link;

									  $value = '<a href="' . esc_url($link) . '" rel="external">' . esc_html($value) . '</a>';
										break;
								}

								echo '<div class="yog-relation-value yog-' . esc_attr($label) . '" title="' . esc_attr($label) . '">' . esc_html($value) . '</div>';
							}

							// Handle addresses
							$addressTypes = array('Hoofdadres', 'Postadres');
							foreach ($addressTypes as $addressType)
							{
								if (in_array($addressType, $usedSpecs[$type]))
								{
									$specs   = yog_retrieveSpecs(array($addressType . '_straat', $addressType .'_huisnummer', $addressType .'_stad', $addressType . '_postcode'), $relation->ID);
									if (!empty($specs))
									{
									  echo '<div class="yog-relation-value yog-relation-address yog-' . esc_attr(strtolower($addressType)) . '" title="' . esc_attr($addressType) . '">';

										if (!empty($specs['Straat']))
											echo esc_html($specs['Straat'] . ' ');
										if (!empty($specs['Huisnummer']))
											echo esc_html($specs['Huisnummer']);

										echo '<br />';

										if (!empty($specs['Postcode']))
											echo esc_html($specs['Postcode']) . '&nbsp;&nbsp;';
										if (!empty($specs['Stad']))
											echo esc_html($specs['Stad']);

										echo '</div>';
									}
								}
							}

						echo '</div>';
					}
					else
					{
						// Include the template, instead of using get_template_part so variables are also useable
						setup_postdata($post);
						include($customTemplate);
					}
        }

        echo '</div>';

				// Restore original post
				if (isset($orgPost))
				{
					$post = $orgPost;
					setup_postdata($orgPost);
				}

				// Show widget end
				echo $afterWidget;
      }
    }
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
    $instance['specs_business']   = empty($new_instance['specs_business']) ? '' : implode(',', $new_instance['specs_business']);
    $instance['specs_person']     = empty($new_instance['specs_person']) ? '' : implode(',', $new_instance['specs_person']);
    $instance['supported_roles']  = empty($new_instance['supported_roles']) ? '' : implode(',', $new_instance['supported_roles']);

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
    $specsBusiness      = explode(',', empty($instance['specs_business']) ? self::DEFAULT_SPECS_BUSINESS : $instance['specs_business']);
    $specsPerson        = explode(',', empty($instance['specs_person']) ? self::DEFAULT_SPECS_PERSON : $instance['specs_person']);
    $supportedRoles     = explode(',', empty($instance['supported_roles']) ? self::DEFAULT_SUPPORTED_ROLES : $instance['supported_roles']);
    $availableSpecs     = array('Telefoonnummer', 'Faxnummer', 'Emailadres', 'Website', 'Hoofdadres', 'Postadres');
    $availableRoles     = array('Makelaarskantoor', 'Binnendienst 1', 'Makelaar 1', 'Verkoper 1');

    echo '<p>';
    echo '<label for="' . esc_attr($this->get_field_id('title')) . '">' . __('Titel') . ': </label>';
    echo '<input class="widefat" id="' . esc_attr($this->get_field_id('title')) . '" name="' . esc_attr($this->get_field_name('title')) . '" type="text" value="' . esc_attr($title) . '" />';
    echo '</p>';

    $name = $this->get_field_name('specs_business');
    $id   = $this->get_field_id('specs_business');
    echo '<p>';
      echo '<label>' . __('Te tonen kenmerken bedrijf') . ': </label><br />';
      foreach ($availableSpecs as $availableSpec)
      {
        echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($availableSpec) . '" id="' . esc_attr($id) . esc_attr($availableSpec) . '"' . (in_array($availableSpec, $specsBusiness) ? ' checked="checked"' : '') . '  /><label for="' . esc_attr($id . $availableSpec) . '">' . esc_html($availableSpec) . '</label><br />';
      }
    echo '</p>';

    $name = $this->get_field_name('specs_person');
    $id   = $this->get_field_id('specs_person') . 'Business';
    echo '<p>';
      echo '<label>' . __('Te tonen kenmerken persoon') . ': </label><br />';
      foreach ($availableSpecs as $availableSpec)
      {
        echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($availableSpec) . '" id="' . esc_attr($id . $availableSpec) . '"' . (in_array($availableSpec, $specsPerson) ? ' checked="checked"' : '') . '  /><label for="' . esc_attr($id . $availableSpec) . '">' . esc_html($availableSpec) . '</label><br />';
      }
    echo '</p>';

    $name = $this->get_field_name('supported_roles');
    echo '<p>';
      echo '<label>' . __('Te gebruiken rollen') . ': </label><br />';
      foreach ($availableRoles as $availableRole)
      {
        $id = $this->get_field_id('supported_roles') . str_replace(' ', '_', $availableRole);
        echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($availableRole) . '" id="' . esc_attr($id) . '"' . (in_array($availableRole, $supportedRoles) ? ' checked="checked"' : '') . '  /><label for="' . esc_attr($id) . '">' . esc_attr($availableRole) . '</label><br />';
      }
    echo '</p>';
  }
}