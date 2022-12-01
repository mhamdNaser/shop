<?php
  /**
  * @desc YogWpAdminObjectUiNBbn
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogWpAdminObjectUiNBbn extends YogWpAdminObjectUiAbstract
  {
    /**
    * @desc Get the post type
    *
    * @param void
    * @return string
    */
    public function getPostType()
    {
      return YOG_POST_TYPE_NBBN;
    }

    /**
    * @desc Get base name
    *
    * @param void
    * @return string
    */
    public function getBaseName()
    {
      return plugin_basename(__FILE__);
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
        'thumbnail'     => '',
	      'title'         => 'Object',
	      'description'   => 'Omschrijving',
	      'location'      => 'Locatie',
        'dlm'           => 'Laatste wijziging'
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
        case 'thumbnail':
          echo get_the_post_thumbnail(null, 'thumbnail');
          break;
        case 'description':
          $content = get_the_excerpt();
          if (strlen($content) > 100)
            $content = substr($content, 0, 100) . '...';

          echo esc_html($content);
          break;
        case 'location':
          $specs = yog_retrieveSpecs(array('Postcode', 'Plaats', 'Land'));
          echo esc_html(implode(', ', $specs));
          break;
        case 'dlm':
          echo esc_html(get_the_modified_date() . ' ' . get_the_modified_time());
          break;
      }
    }

    /**
    * @desc Add javascript actions
    *
    * @param void
    * @return void
    */
    public function addAjaxActions()
    {
      add_action('admin_head',      array($this, 'addOnload'));
      add_action('trashed_post',    array($this, 'redirectToParent'));
      add_action('untrashed_post',  array($this, 'redirectToParent'));
      parent::addAjaxActions();
    }

    public function addOnload()
    {
      echo '<script type="text/javascript">';
      echo 'jQuery(document).ready(yogActivateNbAdminMenu);';
      echo '</script>';
    }

    /**
    * @desc Add containers to project screen
    *
    * @param void
    * @return void
    */
    public function addMetaBoxes()
    {
	    add_meta_box('yog-standard-meta',     'Basis gegevens',       array($this, 'renderBasicMetaBox'),         $this->getPostType(), 'normal', 'low');
      add_meta_box('yog-address-meta',      'Adres',                array($this, 'renderAddressMetaBox'),       $this->getPostType(), 'normal', 'low');
	    add_meta_box('yog-price-meta',        'Prijs',                array($this, 'renderPriceMetaBox'),         $this->getPostType(), 'normal', 'low');
	    add_meta_box('yog-object-meta',       'Object',               array($this, 'renderObjectDetailsMetaBox'), $this->getPostType(), 'normal', 'low');

      add_meta_box('yog-parent-meta',       'Type',                 array($this, 'renderParentMetaBox') ,       $this->getPostType(), 'side', 'high');
      add_meta_box('yog-meta-sync',         'Synchronisatie',       array($this, 'renderSyncMetaBox') ,         $this->getPostType(), 'side', 'low');
      add_meta_box('yog-location',          'Locatie',              array($this, 'renderMapsMetaBox'),          $this->getPostType(), 'side', 'low');
      add_meta_box('yog-relations',         'Relaties',             array($this, 'renderRelationsMetaBox'),     $this->getPostType(), 'side', 'low');
    }

    /**
    * @desc Render basic meta box
    *
    * @param object $post
    * @return void
    */
    public function renderBasicMetaBox($post)
    {
	    echo '<table class="form-table">';
	    echo $this->retrieveInputs($post->ID, array('Naam', 'Bouwnummer', 'Status'));
	    echo '</table>';
    }

    /**
    * @desc Render price meta box
    *
    * @param object $post
    * @return void
    */
    public function renderPriceMetaBox($post)
    {
	    echo '<table class="form-table">';
	    echo $this->retrieveInputs($post->ID, array('GrondPrijs', 'AanneemSom', 'KoopAanneemSom'));
	    echo '</table>';
    }

    /**
    * @desc Render object details meta box
    *
    * @param object $post
    * @return void
    */
    public function renderObjectDetailsMetaBox($post)
    {
	    echo '<table class="form-table">';
	    echo $this->retrieveInputs($post->ID, array('WoonOppervlakte', 'Inhoud', 'PerceelOppervlakte', 'AantalKamers'));
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
	    if ($post->post_type != $this->getPostType())
        return $postId;

      // Verify nonce
	    if (!isset($_POST['yog_nonce']) || !isset($_POST['yog_nonce']) || !wp_verify_nonce($_POST['yog_nonce'], plugin_basename(__FILE__) ))
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
    }
  }