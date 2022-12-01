<?php
  /**
  * @desc YogWpAdminObjectUiBOpr
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogWpAdminObjectUiBOpr extends YogWpAdminObjectUiAbstract
  {
    /**
    * @desc Get the post type
    *
    * @param void
    * @return string
    */
    public function getPostType()
    {
      return YOG_POST_TYPE_BOPR;
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
	      'address'       => 'Adres',
        'dlm'           => 'Laatste wijziging'
	    );
    }

    /**
    * @desc Add containers to project screen
    *
    * @param void
    * @return void
    */
    public function addMetaBoxes()
    {
      add_meta_box('yog-childprojects-meta','Objecten',             array($this, 'renderChildProjectsMetaBox'), $this->getPostType(), 'normal', 'high');
	    add_meta_box('yog-standard-meta',     'Basis gegevens',       array($this, 'renderBasicMetaBox'),         $this->getPostType(), 'normal', 'low');
	    add_meta_box('yog-price-meta',        'Prijs',                array($this, 'renderPriceMetaBox'),         $this->getPostType(), 'normal', 'low');
      add_meta_box('yog-development-meta',  'Ontwikkeling',         array($this, 'renderDevelopmentMetaBox'),   $this->getPostType(), 'normal', 'low');
      add_meta_box('yog-building-meta',     'Gebouw',               array($this, 'renderBuildingMetaBox'),      $this->getPostType(), 'normal', 'low');

	    add_meta_box('yog-movies',            'Video',                array($this, 'renderMoviesMetaBox'),        $this->getPostType(), 'normal', 'low');
	    add_meta_box('yog-documents',         'Documenten',           array($this, 'renderDocumentsMetaBox'),     $this->getPostType(), 'normal', 'low');
	    add_meta_box('yog-links',             'Externe koppelingen',  array($this, 'renderLinksMetaBox'),         $this->getPostType(), 'normal', 'low');

      add_meta_box('yog-meta-sync',         'Synchronisatie',       array($this, 'renderSyncMetaBox') ,         $this->getPostType(), 'side', 'low');
      add_meta_box('yog-location',          'Locatie',              array($this, 'renderMapsMetaBox'),          $this->getPostType(), 'side', 'low');
      add_meta_box('yog-relations',         'Relaties',             array($this, 'renderRelationsMetaBox'),     $this->getPostType(), 'side', 'low');
      add_meta_box('yog-images',            'Afbeeldingen',         array($this, 'renderImagesMetaBox'),        $this->getPostType(), 'side', 'low');
      add_meta_box('yog-dossier',           'Dossier items',        array($this, 'renderDossierMetaBox'),       $this->getPostType(), 'side', 'low');
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
	    echo $this->retrieveInputs($post->ID, array('Naam', 'Straat', 'Huisnummer', 'NummerreeksStart', 'NummerreeksEind', 'Postcode', 'Wijk', 'Buurt', 'Plaats', 'Gemeente', 'Provincie', 'Land', 'Status'));
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

	    // Koop
	    echo '<tr>';
	    echo '<th colspan="2"><b>Koopprijs</b></th>';
	    echo '</tr>';
	    echo $this->retrieveInputs($post->ID, array('KoopPrijsMin', 'KoopPrijsMax'));

	    // Huur
	    echo '<tr>';
	    echo '<th colspan="2"><b>Huurprijs</b></th>';
	    echo '</tr>';
	    echo $this->retrieveInputs($post->ID, array('HuurPrijsMin', 'HuurPrijsMax'));

	    echo '</table>';
    }

    /**
    * @desc Render object development meta box
    *
    * @param object $post
    * @return void
    */
    public function renderDevelopmentMetaBox($post)
    {
      echo '<table class="form-table">';
      echo $this->retrieveInputs($post->ID, array('DatumStartBouw', 'DatumOplevering', 'DatumStartVerkoop'));
      echo '</table>';
    }

    /**
    * @desc Render building meta box
    *
    * @param object $post
    * @return void
    */
    public function renderBuildingMetaBox($post)
    {
      echo '<table class="form-table">';
      echo $this->retrieveInputs($post->ID, array('GebouwNaam', 'AantalVerdiepingen'));
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
	    if (!isset($_POST['yog_nonce']) || !wp_verify_nonce($_POST['yog_nonce'], plugin_basename(__FILE__) ))
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