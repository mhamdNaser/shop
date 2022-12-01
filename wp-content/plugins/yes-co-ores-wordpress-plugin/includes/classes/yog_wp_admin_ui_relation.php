<?php
  /**
  * @desc YogWpAdminObjectUiWonen
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogWpAdminUiRelation extends YogWpAdminUiAbstract
  {
    /**
    * @desc Get the post type
    *
    * @param void
    * @return string
    */
    public function getPostType()
    {
      return YOG_POST_TYPE_RELATION;
    }

    /**
    * @desc Determine columns used in overview
    *
    * @param array $columns
    * @return array
    */
    public function determineColumns($columns)
    {
	    return array(
	      'cb'            => '<input type="checkbox" />',
	      'title'         => 'Naam',
				'menu_order'		=> 'Volgorde',
	      'type'          => 'Type',
				'subtype'				=> 'Sub type',
				'function'			=> 'Functie'
	    );
    }

    /**
    * @desc Determine content of a single column in overview
    *
    * @param string $columnId
    * @return void
    */
    public function generateColumnContent($columnId)
    {
      switch ($columnId)
      {
				case 'menu_order':
					
					$order = get_post_field('menu_order');
					if (!empty($order))
						echo $order;
					
					break;
        case 'type':
          $type = yog_retrieveSpec('type');
          switch ($type)
          {
            case 'Business':
              echo 'Bedrijf';
              break;
            case 'Person':
              echo 'Persoon';
              break;
          }
          break;
				case 'subtype':
					$subType = yog_retrieveSpec('subtype');

					switch ($subType)
					{
						case 'company':
							echo 'Bedrijf';
							break;
						case 'office':
							echo 'Kantoor';
							break;
						case 'contact':
							echo 'Contact persoon';
							break;
						case 'employee':
							echo 'Medewerker';
							break;
						case 'individual':
							echo 'Individu';
							break;
					}
					break;
				case 'function':
					$function = yog_retrieveSpec('Functie');
					if (!empty($function))
						echo $function;

					break;
      }
    }

    /**
    * @desc Add containers to project screen
    *
    * @param void
    * @return void
    */
    public function addMetaBoxes()
    {
			add_meta_box('yog-relation-meta',       'Relatie',					array($this, 'renderRelationMetaBox'),				YOG_POST_TYPE_RELATION, 'normal', 'low');
      add_meta_box('yog-main-address-meta',   'Hoofd adres',      array($this, 'renderMainAddressMetaBox'),     YOG_POST_TYPE_RELATION, 'normal', 'low');
      add_meta_box('yog-postal-address-meta', 'Post adres',       array($this, 'renderPostalAddressMetaBox'),   YOG_POST_TYPE_RELATION, 'normal', 'low');
			add_meta_box('yog-sync',								'Synchronisatie',   array($this, 'renderSyncMetaBox'),            YOG_POST_TYPE_RELATION, 'side', 'low');
      add_meta_box('yog-location',            'Locatie',          array($this, 'renderMapsMetaBox'),            YOG_POST_TYPE_RELATION, 'side', 'low');
    }

    /**
    * @desc Render synchronization meta box
    *
    * @param object $post
    * @return void
    */
    public function renderSyncMetaBox($post)
    {
			$noDelete = (bool) get_post_meta($post->ID, YOG_POST_TYPE_RELATION . '_nodelete', true);

			echo '<label>';
				echo '<input name="' . YOG_POST_TYPE_RELATION . '_nodelete" type="checkbox" value="Y"' . ($noDelete ? ' checked="checked"' : '') . ' /> Nooit verwijderen tijdens synchronisatie';
			echo '</label>';
    }

    /**
    * @desc Render relation basic meta box
    *
    * @param object $post
    * @return void
    */
    public function renderRelationMetaBox($post)
    {
			$type			= yog_retrieveSpec('type', $post->ID);
			$subtype	= yog_retrieveSpec('subtype', $post->ID);

			echo '<input type="hidden" name="yog_nonce" id="myplugin_noncename" value="' .wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
			echo '<table class="form-table">';

			if ($type == 'Business')
			{
				// Show type (not through retrieveInputs because of custom handling)
				echo '<tr>';
					echo '<th scope="row">Type</th>';
					echo '<td>';
						echo '<input type="hidden" name="relatie_type" value="Business" />';
						echo 'Bedrijf';
					echo '</td>';
				echo '</tr>';

				// Show subtype (not through retrieveInputs because of custom handling)
				if (empty($subtype))
					$subtype = 'office';

				echo '<tr>';
					echo '<th scope="row">Sub type</th>';
					echo '<td>';
						echo '<select name="relatie_subtype">';
							foreach (array('company' => 'Bedrijf', 'office' => 'Kantoor') as $key => $label)
							{
							  echo '<option value="' . esc_attr($key) . '"' . ($key === $subtype ? ' selected="selected"' : '') . '>' . esc_html($label) . '</option>';
							}
						echo '</select>';
					echo '</td>';
				echo '</tr>';

				echo $this->retrieveInputs($post->ID, array('Emailadres', 'Website', 'Telefoonnummer', 'Faxnummer'));
			}
			else
			{
				// Show type (not through retrieveInputs because of custom handling)
				echo '<tr>';
					echo '<th scope="row">Type</th>';
					echo '<td>';
						echo '<input type="hidden" name="relatie_type" value="Person" />';
						echo 'Persoon';
					echo '</td>';
				echo '</tr>';

				// Show subtype (not through retrieveInputs because of custom handling)
				if (empty($subtype))
					$subtype = 'employee';

				echo '<tr>';
					echo '<th scope="row">Sub type</th>';
					echo '<td>';
						echo '<select name="relatie_subtype">';
							foreach (array('contact' => 'Contact persoon', 'employee' => 'Medewerker', 'individual' => 'Individu') as $key => $label)
							{
							  echo '<option value="' . esc_attr($key) . '"' . ($key === $subtype ? ' selected="selected"' : '') . '>' . esc_html($label) . '</option>';
							}
						echo '</select>';
					echo '</td>';
				echo '</tr>';

				echo $this->retrieveInputs($post->ID, array('Titel', 'Initialen', 'Voornaam', 'Voornamen', 'Tussenvoegsel', 'Achternaam', 'Geslacht', 'Functie', 'Emailadres', 'Website', 'Telefoonnummer', 'Telefoonnummerwerk', 'Telefoonnummermobiel', 'Faxnummer'));
			}

			echo '</table>';
    }

    /**
    * @desc Render main address meta box
    *
    * @param object $post
    * @return void
    */
    public function renderMainAddressMetaBox($post)
    {
	    echo '<table class="form-table">';
	    echo $this->retrieveInputs($post->ID, array('Hoofdadres_land', 'Hoofdadres_provincie', 'Hoofdadres_gemeente', 'Hoofdadres_stad', 'Hoofdadres_wijk', 'Hoofdadres_buurt', 'Hoofdadres_straat', 'Hoofdadres_postcode', 'Hoofdadres_huisnummer'));
	    echo '</table>';
    }

    /**
    * @desc Render postal address meta box
    *
    * @param object $post
    * @return void
    */
    public function renderPostalAddressMetaBox($post)
    {
	    echo '<table class="form-table">';
	    echo $this->retrieveInputs($post->ID, array('Postadres_land', 'Postadres_provincie', 'Postadres_gemeente', 'Postadres_stad', 'Postadres_wijk', 'Postadres_buurt', 'Postadres_straat', 'Postadres_postcode', 'Postadres_huisnummer'));
	    echo '</table>';
    }

    /**
      * @desc Extend saving of huis post type with storing of custom fields
      *
      * @param int $postId
      * @param StdClass $post
      * @return void
      */
    public function extendSave($postId, $post)
    {
      // Check if post is of type wonen
	    if ($post->post_type != YOG_POST_TYPE_RELATION)
        return $postId;

      // Verify nonce
	    if ( !wp_verify_nonce($_POST['yog_nonce'], plugin_basename(__FILE__) ))
		    return $postId;

	    // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
	    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
	      return $postId;

	    // Check permissions
		  if (!current_user_can( 'edit_page', $postId ) )
		    return $postId;

		  // Handle meta data
      $fieldsSettings = YogFieldsSettingsAbstract::create($post->post_type);

      // Handle normal fields
		  foreach ($fieldsSettings->getFieldNames() as $fieldName)
		  {
			  if (empty($_POST[$fieldName]))
			    delete_post_meta($postId, $fieldName);
			  else
			    update_post_meta($postId, $fieldName, sanitize_textarea_field($_POST[$fieldName]));
		  }

			// Also store nodelete
			update_post_meta($postId, YOG_POST_TYPE_RELATION . '_nodelete', !empty($_POST[YOG_POST_TYPE_RELATION . '_nodelete']));
    }
  }