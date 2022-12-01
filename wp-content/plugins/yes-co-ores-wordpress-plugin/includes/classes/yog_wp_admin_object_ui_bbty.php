<?php
  /**
  * @desc YogWpAdminObjectUiBBty
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogWpAdminObjectUiBBty extends YogWpAdminObjectUiAbstract
  {
    /**
    * @desc Get the post type
    *
    * @param void
    * @return string
    */
    public function getPostType()
    {
      return YOG_POST_TYPE_BBTY;
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
            $content = htmlentities(substr($content, 0, 100)) . '...';

          echo $content;
          break;
        case 'location':
          $specs = yog_retrieveSpecs(array('Postcode', 'Plaats', 'Land'));
          echo implode(', ', $specs);
          break;
        case 'dlm':
          echo get_the_modified_date() . ' ' . get_the_modified_time();
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
      echo 'jQuery(document).ready(yogActivateComplexAdminMenu);';
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
      add_meta_box('yog-childprojects-meta','Objecten',             array($this, 'renderChildProjectsMetaBox'), $this->getPostType(), 'normal', 'high');
	    add_meta_box('yog-standard-meta',     'Basis gegevens',       array($this, 'renderBasicMetaBox'),         $this->getPostType(), 'normal', 'low');
	    add_meta_box('yog-price-meta',        'Prijs',                array($this, 'renderPriceMetaBox'),         $this->getPostType(), 'normal', 'low');
	    add_meta_box('yog-measurements-meta', 'Maten',                array($this, 'renderMeasurementsMetaBox'),  $this->getPostType(), 'normal', 'low');
      add_meta_box('yog-type-meta',         'Gegevens type',        array($this, 'renderTypeMetaBox'),          $this->getPostType(), 'normal', 'low');
      add_meta_box('yog-cv-meta',           'C.V.',                 array($this, 'renderCvMetaBox'),            $this->getPostType(), 'normal', 'low');
      add_meta_box('yog-garage-meta',       'Garage',               array($this, 'renderGarageMetaBox'),        $this->getPostType(), 'normal', 'low');
      add_meta_box('yog-garden-meta',       'Tuin',                 array($this, 'renderGardenMetaBox'),        $this->getPostType(), 'normal', 'low');
      add_meta_box('yog-berging-meta',      'Berging',              array($this, 'renderBergingMetaBox'),       $this->getPostType(), 'normal', 'low');
	    add_meta_box('yog-movies',            'Video',                array($this, 'renderMoviesMetaBox'),        $this->getPostType(), 'normal', 'low');
	    add_meta_box('yog-documents',         'Documenten',           array($this, 'renderDocumentsMetaBox'),     $this->getPostType(), 'normal', 'low');
	    add_meta_box('yog-links',             'Externe koppelingen',  array($this, 'renderLinksMetaBox'),         $this->getPostType(), 'normal', 'low');

      add_meta_box('yog-parent-meta',       'Complex',              array($this, 'renderParentMetaBox') ,       $this->getPostType(), 'side', 'high');
      add_meta_box('yog-meta-sync',         'Synchronisatie',       array($this, 'renderSyncMetaBox') ,         $this->getPostType(), 'side', 'low');
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
	    echo $this->retrieveInputs($post->ID, array('Naam', 'Status'));
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
	    echo '<th colspan="2"><b>Koop prijs</b></th>';
	    echo '</tr>';
	    echo $this->retrieveInputs($post->ID, array('KoopPrijsMin', 'KoopPrijsMax'));

	    // Huur
	    echo '<tr>';
	    echo '<th colspan="2"><b>Huur prijs</b></th>';
	    echo '</tr>';
	    echo $this->retrieveInputs($post->ID, array('HuurPrijsMin', 'HuurPrijsMax', 'HuurPrijsConditie'));

	    echo '</table>';
    }

    /**
    * @desc Render object measurements meta box
    *
    * @param object $post
    * @return void
    */
    public function renderMeasurementsMetaBox($post)
    {
	    echo '<table class="form-table">';

      // Perceel oppervlakte
	    echo '<tr>';
	    echo '<th colspan="2"><b>Perceel oppervlakte</b></th>';
	    echo '</tr>';
	    echo $this->retrieveInputs($post->ID, array('PerceelOppervlakteMin', 'PerceelOppervlakteMax'));

      // Woon oppervlakte
	    echo '<tr>';
	    echo '<th colspan="2"><b>Woon oppervlakte</b></th>';
	    echo '</tr>';
	    echo $this->retrieveInputs($post->ID, array('WoonOppervlakteMin', 'WoonOppervlakteMax'));

      // Inhoud
	    echo '<tr>';
	    echo '<th colspan="2"><b>Inhoud</b></th>';
	    echo '</tr>';
	    echo $this->retrieveInputs($post->ID, array('InhoudMin', 'InhoudMax'));

	    echo '</table>';
    }

    /**
    * @desc Render type meta box
    *
    * @param StdClass $post
    * @return void
    */
    public function renderTypeMetaBox($post)
    {
      echo '<table class="form-table">';
      echo $this->retrieveInputs($post->ID, array('Type', 'SoortWoning', 'TypeWoning', 'KenmerkWoning', 'PermanenteBewoning', 'Recreatiewoning', 'Aantalkamers', 'Verwarming', 'WarmWater', 'Dak', 'DakMaterialen'));
      echo '</table>';
    }

    /**
    * @desc Render C.V. meta box
    *
    * @param StdClass $post
    * @param void
    */
    public function renderCvMetaBox($post)
    {
      echo '<table class="form-table">';
      echo $this->retrieveInputs($post->ID, array('CvKetel', 'CvKetelBouwjaar', 'CvKetelBrandstof', 'CvKetelEigendom', 'CvCombiketel'));
      echo '</table>';
    }

    /**
    * @desc Render garage meta box
    *
    * @param StdClass $post
    * @param void
    */
    public function renderGarageMetaBox($post)
    {
      echo '<table class="form-table">';
      echo $this->retrieveInputs($post->ID, array('GarageType', 'GarageCapaciteit', 'GarageVoorzieningen', 'GarageIsolatievormen'));
      echo '</table>';
    }

    /**
    * @desc Render garden meta box
    *
    * @param StdClass $post
    * @param void
    */
    public function renderGardenMetaBox($post)
    {
      echo '<table class="form-table">';
      echo $this->retrieveInputs($post->ID, array('TuinType', 'TuinTotaleOppervlakte', 'HoofdTuinType', 'HoofdTuinAchterom', 'HoofdTuinDiepte', 'HoofdTuinBreedte', 'HoofdTuinTotaleOppervlakte', 'TuinLigging'));
      echo '</table>';
    }

    /**
    * @desc Render berging meta box
    *
    * @param StdClass $post
    * @param void
    */
    public function renderBergingMetaBox($post)
    {
      echo '<table class="form-table">';
      echo $this->retrieveInputs($post->ID, array('BergingType', 'BergingVoorzieningen', 'BergingIsolatievormen'));
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