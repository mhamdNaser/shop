<?php
  /**
  * @desc YogWpAdminObjectUiBog
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogWpAdminObjectUiBog extends YogWpAdminObjectUiAbstract
  {
    /**
    * @desc Get the post type
    *
    * @param void
    * @return string
    */
    public function getPostType()
    {
      return YOG_POST_TYPE_BOG;
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
        'dlm'           => 'Laatste wijziging',
	        'date'           => 'Aangemaakt'
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
	    add_meta_box('yog-standard-meta',       'Basis gegevens',       array($this, 'renderBasicMetaBox'),           YOG_POST_TYPE_BOG, 'normal', 'low');
	    add_meta_box('yog-price-meta',          'Prijs',                array($this, 'renderPriceMetaBox'),           YOG_POST_TYPE_BOG, 'normal', 'low');
      add_meta_box('yog-belegging-meta',      'Belegging',            array($this, 'renderBeleggingMetaBox'),       YOG_POST_TYPE_BOG, 'normal', 'low');

      add_meta_box('yog-bouwgrond-meta',      'Bouwgrond',            array($this, 'renderBouwgrondMetaBox'),       YOG_POST_TYPE_BOG, 'normal', 'low');
      add_meta_box('yog-gebouw-meta',         'Gebouw',               array($this, 'renderGebouwMetaBox'),          YOG_POST_TYPE_BOG, 'normal', 'low');
      add_meta_box('yog-bedrijfsruimte-meta', 'Bedrijfsruimte',       array($this, 'renderBedrijfsruimteMetaBox'),  YOG_POST_TYPE_BOG, 'normal', 'low');
      add_meta_box('yog-kantoorruimte-meta',   'Kantoorruimte',       array($this, 'renderKantoorruimteMetaBox'),   YOG_POST_TYPE_BOG, 'normal', 'low');
      add_meta_box('yog-winkelruimte-meta',   'Winkelruimte',         array($this, 'renderWinkelruimteMetaBox'),    YOG_POST_TYPE_BOG, 'normal', 'low');
      add_meta_box('yog-horeca-meta',         'Horeca',               array($this, 'renderHorecaMetaBox'),          YOG_POST_TYPE_BOG, 'normal', 'low');

	    add_meta_box('yog-movies',              'Video',                array($this, 'renderMoviesMetaBox'),          YOG_POST_TYPE_BOG, 'normal', 'low');
	    add_meta_box('yog-documents',           'Documenten',           array($this, 'renderDocumentsMetaBox'),       YOG_POST_TYPE_BOG, 'normal', 'low');
	    add_meta_box('yog-links',               'Externe koppelingen',  array($this, 'renderLinksMetaBox'),           YOG_POST_TYPE_BOG, 'normal', 'low');

      // Only show parent object container when there is a parent object
      if (!empty($_REQUEST['post']) && yog_hasParentObject((int) $_REQUEST['post']))
        add_meta_box('yog-parent-meta',       'Project',              array($this, 'renderParentMetaBox') ,       $this->getPostType(), 'side', 'high');

      add_meta_box('yog-meta-sync',           'Synchronisatie',       array($this, 'renderSyncMetaBox') ,         YOG_POST_TYPE_BOG, 'side', 'low');
      add_meta_box('yog-location',            'Locatie',              array($this, 'renderMapsMetaBox'),          YOG_POST_TYPE_BOG, 'side', 'low');
      add_meta_box('yog-relations',           'Relaties',             array($this, 'renderRelationsMetaBox'),     YOG_POST_TYPE_BOG, 'side', 'low');
      add_meta_box('yog-images',              'Afbeeldingen',         array($this, 'renderImagesMetaBox'),        YOG_POST_TYPE_BOG, 'side', 'low');
      add_meta_box('yog-dossier',             'Dossier items',        array($this, 'renderDossierMetaBox'),       YOG_POST_TYPE_BOG, 'side', 'low');
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
	    echo $this->retrieveInputs($post->ID, array('Naam', 'Straat', 'Huisnummer', 'NummerreeksStart', 'NummerreeksEind', 'Postcode', 'Wijk', 'Buurt', 'Plaats', 'Gemeente', 'Provincie', 'Land', 'Status', 'DatumVoorbehoudTot', 'Aanmelding', 'Aanvaarding', 'PerceelOppervlakte', 'Hoofdbestemming', 'Nevenbestemming', 'WoonruimteSituatie', 'WoonruimteStatus', 'Type'));
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
	    echo '<th colspan="2"><b>Koop</b></th>';
	    echo '</tr>';
	    echo $this->retrieveInputs($post->ID, array('KoopPrijs', 'KoopPrijsConditie', 'KoopPrijsVervanging', 'Bouwrente', 'Veilingdatum', 'Erfpacht', 'ErfpachtDuur'));

	    // Huur
	    echo '<tr>';
	    echo '<th colspan="2"><b>Huur</b></th>';
	    echo '</tr>';
	    echo $this->retrieveInputs($post->ID, array('HuurPrijs', 'HuurPrijsMin', 'HuurPrijsMax', 'HuurPrijsConditie', 'HuurPrijsVervanging', 'Servicekosten', 'ServicekostenConditie'));

	    echo '</table>';
    }

    /**
    * @desc Render belegging meta box
    *
    * @param object $post
    * @return void
    */
    public function renderBeleggingMetaBox($post)
    {
	    echo '<table class="form-table">';
	    echo $this->retrieveInputs($post->ID, array('AantalHuurders', 'BeleggingExpiratieDatum', 'Huuropbrengst'));
	    echo '</table>';
    }

    /**
    * @desc Render bouwgrond meta box
    *
    * @param object $post
    * @return void
    */
    public function renderBouwgrondMetaBox($post)
    {
	    echo '<table class="form-table">';
	    echo $this->retrieveInputs($post->ID, array('BouwgrondBebouwingsmogelijkheid', 'BouwgrondBouwhoogte', 'BouwgrondInUnitsVanaf', 'BouwgrondVloerOppervlakte', 'BouwgrondVloerOppervlakteProcentueel'));
	    echo '</table>';
    }

    /**
    * @desc Render gebouw meta box
    *
    * @param object $post
    * @return void
    */
    public function renderGebouwMetaBox($post)
    {
	    echo '<table class="form-table">';
	    echo $this->retrieveInputs($post->ID, array('GebouwNaam', 'InAanbouw', 'Bouwjaar', 'Oppervlakte'));
	    echo '<tr><th colspan="2"><b>Onderhoud</b></th></tr>';
      echo $this->retrieveInputs($post->ID, array('OnderhoudBinnen', 'OnderhoudBinnenOmschrijving', 'OnderhoudBuiten', 'OnderhoudBuitenOmschrijving', 'EnergielabelKlasse'));
      echo '<tr><th colspan="2"><b>Lokatie / ligging</b></th></tr>';
      echo $this->retrieveInputs($post->ID, array('LokatieOmschrijving', 'Ligging', 'SnelwegAfrit', 'NsStation', 'NsVoorhalte', 'BusKnooppunt', 'TramKnooppunt', 'MetroKnooppunt', 'Bushalte', 'Tramhalte', 'Metrohalte', 'BankAfstand', 'BankAantal', 'OntspanningAfstand', 'OntspanningAantal', 'RestaurantAfstand', 'RestaurantAantal', 'WinkelAfstand', 'WinkelAantal'));
      echo '<tr><th colspan="2"><b>Parkeren</b></th></tr>';
      echo $this->retrieveInputs($post->ID, array('ParkerenOmschrijving', 'AantalParkeerplaatsen', 'AantalParkeerplaatsenOverdekt', 'AantalParkeerplaatsenNietOverdekt'));
	    echo '</table>';
    }

    /**
    * @desc Render bedrijfsruimte meta box
    *
    * @param object $post
    * @return void
    */
    public function renderBedrijfsruimteMetaBox($post)
    {
      echo '<table class="form-table">';
      echo '<tr><th colspan="2"><b>Bedrijfshal</b></th></tr>';
      echo $this->retrieveInputs($post->ID, array('BedrijfshalOppervlakte', 'BedrijfshalInUnitsVanaf', 'BedrijfshalVrijeHoogte', 'BedrijfshalVrijeOverspanning', 'BedrijfshalVloerbelasting', 'BedrijfshalVoorzieningen', 'BedrijfshalPrijs'));
      echo '<tr><th colspan="2"><b>Kantoorruimte</b></th></tr>';
      echo $this->retrieveInputs($post->ID, array('KantoorruimteOppervlakte', 'KantoorruimteAantalVerdiepingen', 'KantoorruimteVoorzieningen', 'KantoorruimtePrijs'));
      echo '<tr><th colspan="2"><b>Terrein</b></th></tr>';
      echo $this->retrieveInputs($post->ID, array('TerreinOppervlakte', 'TerreinBouwvolumeBouwhoogte', 'TerreinBouwvolumeVloerOppervlakte', 'TerreinPrijs'));
      echo '</table>';
    }

    /**
    * @desc Render kantoorruimte meta box
    *
    * @param object $post
    * @return void
    */
    public function renderKantoorruimteMetaBox($post)
    {
      echo '<table class="form-table">';
      echo $this->retrieveInputs($post->ID, array('KantoorruimteOppervlakte', 'KantoorruimteAantalVerdiepingen', 'KantoorruimteVoorzieningen', 'KantoorruimteInUnitsVanaf', 'KantoorruimteTurnKey'));
      echo '</table>';
    }

    /**
    * @desc Render winkelruimte meta box
    *
    * @param object $post
    * @return void
    */
    public function renderWinkelruimteMetaBox($post)
    {
      echo '<table class="form-table">';
      echo $this->retrieveInputs($post->ID, array('WinkelruimteOppervlakte', 'WinkelruimteVerkoopVloerOppervlakte', 'WinkelruimteInUnitsVanaf', 'WinkelruimteFrontBreedte', 'WinkelruimteAantalVerdiepingen', 'WinkelruimteWelstandsklasse', 'WinkelruimteBrancheBeperking', 'WinkelruimteHorecaToegestaan', 'WinkelruimteBijdrageWinkeliersvereniging', 'WinkelruimtePersoneelTerOvername', 'WinkelruimtePrijsInventarisGoodwill'));
      echo '</table>';
    }

    /**
    * @desc Render horeca meta box
    *
    * @param object $post
    * @return void
    */
    public function renderHorecaMetaBox($post)
    {
      echo '<table class="form-table">';
      echo $this->retrieveInputs($post->ID, array('HorecaType', 'HorecaOppervlakte', 'HorecaVerkoopVloerOppervlakte', 'HorecaAantalVerdiepingen', 'HorecaWelstandsklasse', 'HorecaConcentratieGebied', 'HorecaRegio', 'HorecaPersoneelTerOvername', 'HorecaPrijsInventarisGoodwill'));
      echo '</table>';
    }

    /**
      * @desc Extend saving of bog post type with storing of custom fields
      *
      * @param int $postId
      * @param StdClass $post
      * @return void
      */
    public function extendSave($postId, $post)
    {
      // Check if post is of type bog
	    if ($post->post_type != POST_TYPE_BOG)
        return $postId;

      // Verify nonce
	    if (!isset($_POST['yog_nonce']) || !wp_verify_nonce($_POST['yog_nonce'], $this->getBaseName()))
		    return $postId;

	    // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
	    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
	      return $postId;

	    // Check permissions
		  if (!current_user_can( 'edit_page', $postId ) )
		    return $postId;

		  // Handle meta data
      $fieldsSettings = YogFieldsSettingsAbstract::create($post->post_type);
      $objectType     = !empty($_POST['bedrijf_Type']) ? sanitize_text_field($_POST['bedrijf_Type']) : '';

      // Handle fields
		  foreach ($fieldsSettings->getFields() as $fieldName => $options)
		  {
        if (!empty($options['object']) && !in_array($objectType, $options['object']))
          delete_post_meta($postId, $fieldName);
        else if (!empty($options['type']) && $options['type'] == 'bool')
          update_post_meta($postId, $fieldName, (empty($_POST[$fieldName]) ? 'nee' : sanitize_text_field($_POST[$fieldName])));
			  else if (empty($_POST[$fieldName]))
			    delete_post_meta($postId, $fieldName);
			  else
			    update_post_meta($postId, $fieldName, sanitize_textarea_field($_POST[$fieldName]));
		  }
    }
  }