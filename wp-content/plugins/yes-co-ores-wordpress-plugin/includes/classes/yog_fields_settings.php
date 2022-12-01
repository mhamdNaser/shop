<?php
  require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_exception.php');

  /**
  * @desc YogFieldsSettingsAbstract
  * @author Kees Brandenburg - Yes-co Nederland
  */
  abstract class YogFieldsSettingsAbstract
  {
    protected $fieldsSettings;

    /**
    * @desc Create YogTypMappingAbstract
    *
    * @param string $postType
    * @return YogTypMappingAbstract
    */
    static public function create($postType)
    {
      switch ($postType)
      {
        case YOG_POST_TYPE_WONEN:
          return new YogWonenFieldsSettings();
          break;
        case YOG_POST_TYPE_BOG:
          return new YogBogFieldsSettings();
          break;
        case YOG_POST_TYPE_BOPR:
          return new YogBOprFieldsSettings();
          break;
        case YOG_POST_TYPE_NBPR:
          return new YogNBprFieldsSettings();
          break;
        case YOG_POST_TYPE_NBTY:
          return new YogNBtyFieldsSettings();
          break;
        case YOG_POST_TYPE_NBBN:
          return new YogNBbnFieldsSettings();
          break;
        case YOG_POST_TYPE_BBPR:
          return new YogBBprFieldsSettings();
          break;
        case YOG_POST_TYPE_BBTY:
          return new YogBBtyFieldsSettings();
          break;
        case YOG_POST_TYPE_RELATION:
          return new YogRelationFieldsSettings();
          break;
        default:
          throw new YogException(__METHOD__ . '; Unknown post type (' . $postType . ')', YogException::GLOBAL_ERROR);
          break;
      }
    }

    /**
    * @desc Get all field names
    *
    * @param void
    * @return array
    */
    public function getFieldNames()
    {
      return array_keys($this->fieldsSettings);
    }

    /**
    * @desc Check if mapping contains a specific field
    *
    * @param string $field
    * @return bool
    */
    public function containsField($field)
    {
      return array_key_exists($field, $this->fieldsSettings);
    }

    /**
    * @desc Get field settings
    *
    * @param string $field
    * @return array
    */
    public function getField($field)
    {
      if (!$this->containsField($field))
        return array();

      return $this->fieldsSettings[$field];
    }

    /**
    * @desc Get all field settings
    *
    * @param void
    * @return array
    */
    public function getFields()
    {
      return $this->fieldsSettings;
    }
  }

/**
 * @desc YogWonenFieldsSettings
 * @author Kees Brandenburg - Yes-co Nederland
 */
class YogWonenFieldsSettings extends YogFieldsSettingsAbstract
{
    public function __construct()
    {
        $this->fieldsSettings = [
            'huis_Updated'                      => ['title' => 'Laatste update via sync'],
            'huis_uuid'                         => ['title' => 'UUID'],
            'huis_scenario'                     => ['title' => 'Scenario'],
            'huis_Naam'                         => ['title' => 'Titel van object', 'width' => 450],
            'huis_Straat'                       => [],
            'huis_Huisnummer'                   => ['width' => 100],
            'huis_Postcode'                     => ['width' => 100],
            'huis_Wijk'                         => ['search' => 'exact', 'replacementMeta' => 'huis_WijkCustom'],
            'huis_Buurt'                        => ['search' => 'exact', 'replacementMeta' => 'huis_BuurtCustom'],
            'huis_Plaats'                       => ['search' => 'exact'],
            'huis_Gemeente'                     => [],
            'huis_Provincie'                    => [],
            'huis_Land'                         => [],
            'huis_Longitude'                    => [],
            'huis_Latitude'                     => [],
            'huis_Status'                       => [],
            'huis_Oppervlakte'                  => ['type' => 'oppervlakte', 'title' => 'Woonopp.', 'width' => 100, 'search' => 'range'],
            'huis_OppervlaktePerceel'           => ['type' => 'oppervlakte', 'title' => 'Perceelopp.', 'width' => 100],
            'huis_Inhoud'                       => ['type' => 'inhoud', 'width' => 100, 'search' => 'range'],
            'huis_KoopPrijsSoort'               => ['title' => 'Prijs soort'],
            'huis_KoopPrijs'                    => ['type' => 'price', 'title' => 'Prijs', 'width' => 100, 'search' => 'range'],
            'huis_KoopPrijsConditie'            => ['title' => 'Prijs conditie', 'width' => 100],
            'huis_Servicekosten'                => ['title' => 'Servicekosten', 'type' => 'price', 'addition' => ' per maand'],
            'huis_KoopPrijsVervanging'          => ['title' => 'Prijs vervanging'],
            'huis_HuurPrijs'                    => ['type' => 'price', 'title' => 'Prijs', 'width' => 100, 'search' => 'range'],
            'huis_HuurPrijsConditie'            => ['title' => 'Prijs conditie', 'width' => 100],
            'huis_PrijsType'                    => ['title' => 'Prijs soort'],
            'huis_Type'                         => ['title' => 'Type', 'search' => 'exact'],
            'huis_SoortWoning'                  => ['title' => 'Soort woning', 'search' => 'exact'],
            'huis_TypeWoning'                   => ['title' => 'Type woning', 'search' => 'exact'],
            'huis_KenmerkWoning'                => ['title' => 'Kenmerk'],
            'huis_Aantalkamers'                 => ['title' => 'Kamers', 'width' => 100, 'search' => 'range'],
            'huis_AantalSlaapkamers'            => ['title' => 'Slaapkamers', 'width' => 100, 'search' => 'range'],
            'huis_Bouwjaar'                     => [],
            'huis_OnderhoudBinnen'              => ['title' => 'Onderhoud binnen'],
            'huis_OnderhoudBuiten'              => ['title' => 'Onderhoud buiten'],
            'huis_Ligging'                      => ['multipleSplitByComma' => true],
            'huis_GarageType'                   => ['title' => 'Garage', 'multipleSplitByComma' => true],
            'huis_GarageCapaciteit'             => ['title' => 'Garage capactiteit'],
            'huis_TuinType'                     => ['title' => 'Tuin', 'multipleSplitByComma' => true],
            'huis_TuinTotaleOppervlakte'        => ['title' => 'Tuin oppervlakte'],
            'huis_HoofdTuinType'                => ['title' => 'Hoofd tuin'],
            'huis_HoofdTuinTotaleOppervlakte'   => ['title' => 'Hoofd tuin opp.', 'type' => 'oppervlakte'],
            'huis_TuinLigging'                  => ['title' => 'Tuin ligging', 'multipleSplitByComma' => true],
            'huis_BergingType'                  => ['title' => 'Type berging'],
            'huis_WarmWater'                    => ['title' => 'Warm water'],
            'huis_CvKetel'                      => ['title' => 'Type C.V.'],
            'huis_CvKetelBouwjaar'              => ['title' => 'Bouwjaar C.V.'],
            'huis_PraktijkruimteType'           => ['title' => 'Praktijkruimte'],
            'huis_EnergielabelKlasse'           => ['title' => 'Energielabel', 'width' => 100, 'search' => 'energielabel'],
            'huis_Bijzonderheden'               => ['title' => 'Bijzonderheden', 'readonly' => true],
            'huis_KeukenVernieuwd'              => ['title' => 'Keuken vernieuwd'],
            'huis_OpenHuisVan'                  => ['title' => 'Open huis begin', 'type' => 'datetime'],
            'huis_OpenHuisTot'                  => ['title' => 'Open huis eind', 'type' => 'datetime'],
            'huis_DatumVoorbehoudTot'           => ['type' => 'date', 'title' => 'Voorbehoud tot']
        ];
    }
}

  /**
  * @desc YogBogFieldsSettings
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogBogFieldsSettings extends YogFieldsSettingsAbstract
  {
    public function __construct()
    {
      $this->fieldsSettings = array(
        'bedrijf_Updated'                                   => array( 'title' => 'Laatste update via sync'),
        'bedrijf_uuid'                                      => array( 'title' => 'UUID'),
        'bedrijf_scenario'                                  => array( 'title' => 'Scenario'),
        'bedrijf_Naam'                                      => array( 'title' => 'Titel van object',
                                                                      'width' => 450),
        'bedrijf_Straat'                                    => array(),
        'bedrijf_Huisnummer'                                => array( 'width' => 100),
        'bedrijf_NummerreeksStart'                          => array( 'title' => 'Nummerreeks start',
                                                                      'width' => 50),
        'bedrijf_NummerreeksEind'                           => array( 'title' => 'Nummerreeks eind',
                                                                      'width' => 50),
        'bedrijf_Postcode'                                  => array( 'width' => 100),
        'bedrijf_Wijk'                                      => array( 'search'	=> 'exact', 'replacementMeta' => 'bedrijf_WijkCustom'),
        'bedrijf_Buurt'                                     => array( 'search'	=> 'exact', 'replacementMeta' => 'bedrijf_BuurtCustom'),
        'bedrijf_Plaats'                                    => array( 'search' => 'exact'),
        'bedrijf_Gemeente'                                  => array(),
        'bedrijf_Provincie'                                 => array(),
        'bedrijf_Land'                                      => array(),
        'bedrijf_Longitude'                                 => array(),
        'bedrijf_Latitude'                                  => array(),
        'bedrijf_Status'                                    => array(),
        'bedrijf_Aanmelding'                                => array(),
        'bedrijf_Aanvaarding'                               => array(),
        'bedrijf_DatumVoorbehoudTot'                        => array( 'type'      => 'date',
                                                                      'title'     => 'Voorbehoud tot'),
        'bedrijf_Hoofdbestemming'                           => array( 'title'     => 'Hoofd bestemming'),
        'bedrijf_Nevenbestemming'                           => array( 'title'     => 'Neven bestemming'),
        'bedrijf_PrijsType'                                 => array( 'title'     => 'Prijs soort'),
        'bedrijf_PrijsConditie'                             => array( 'title'     => 'Prijs conditie'),
        'bedrijf_KoopPrijs'                                 => array( 'title'     => 'Prijs',
                                                                      'type'      => 'priceBtw',
                                                                      'search'    => 'range'),
        'bedrijf_KoopPrijsConditie'                         => array( 'title'     => 'Prijs conditie',
                                                                      'width'     => 100),
        'bedrijf_KoopPrijsVervanging'                       => array( 'title'     => 'Prijs vervanging'),
        'bedrijf_Bouwrente'                                 => array( 'type'      => 'bool'),
        'bedrijf_Erfpacht'                                  => array( 'type'      => 'price',
                                                                      'addition'  => 'per jaar',
                                                                      'width'     => 100),
        'bedrijf_ErfpachtDuur'                              => array( 'title'     => 'Erfpacht duur'),
        'bedrijf_HuurPrijs'                                 => array( 'title'				=> 'Prijs',
                                                                      'type'				=> 'priceBtw',
                                                                      'search'			=> 'bog-rental-price-range'),
				'bedrijf_HuurPrijsMin'															=> array( 'title'			=> 'Prijs van',
																																			'type'			=> 'priceBtw',
																																			'search'		=> 'range'),
				'bedrijf_HuurPrijsMax'															=> array( 'title'			=> 'Prijs tot',
																																			'type'			=> 'priceBtw',
																																			'search'		=> 'range'),
        'bedrijf_HuurPrijsConditie'                         => array( 'title'     => 'Prijs conditie',
                                                                      'width'     => 100),
        'bedrijf_HuurPrijsVervanging'                       => array( 'title'     => 'Prijs vervanging'),
        'bedrijf_Servicekosten'                             => array( 'title'     => 'Servicekosten',
                                                                      'type'      => 'priceBtw'),
        'bedrijf_ServicekostenConditie'                     => array( 'title'     => 'Servicekosten conditie',
                                                                      'width'     => 100),
        'bedrijf_PerceelOppervlakte'                        => array( 'title'     => 'Perceel oppervlakte',
                                                                      'type'      => 'oppervlakte',
                                                                      'width'     => 100,
                                                                      'search'    => 'range'),
        'bedrijf_WoonruimteSituatie'                        => array( 'title'     => 'Woonruimte situatie'),
        'bedrijf_WoonruimteStatus'                          => array( 'title'     => 'Woonruimte status'),
        'bedrijf_AantalHuurders'                            => array( 'title'     => 'Aantal huurders'),
        'bedrijf_BeleggingExpiratieDatum'                   => array( 'title'     => 'Expiratie datum'),
        'bedrijf_Huuropbrengst'                             => array( 'type'      => 'priceBtw'),
        'bedrijf_Type'                                      => array( 'type'      => 'select',
                                                                      'options'   => array('Bedrijfsruimte', 'Bouwgrond', 'Horeca', 'Kantoorruimte', 'Winkelruimte', 'Garagebox', 'Leisure', 'Maatschappelijk vastgoed', 'Praktijkruimte', 'Verhard buitenterrein'),
                                                                      'search'    => 'exact'),
        'bedrijf_Oppervlakte'                               => array( 'type'    => 'oppervlakte',
                                                                      'title'   => 'Oppervlakte',
                                                                      'width'   => 100,
                                                                      'search'  => 'range'),
        'bedrijf_BouwgrondBebouwingsmogelijkheid'           => array( 'title'     => 'Bebouwingsmogelijkheid',
                                                                      'object'    => array('Bouwgrond')),
        'bedrijf_BouwgrondBouwhoogte'                       => array( 'title'     => 'Bouwhoogte',
                                                                      'width'     => 100,
                                                                      'type'      => 'cmToMeter',
                                                                      'object'    => array('Bouwgrond')),
        'bedrijf_BouwgrondInUnitsVanaf'                     => array( 'title'     => 'In units vanaf',
                                                                      'width'     => 100,
                                                                      'object'    => array('Bouwgrond')),
        'bedrijf_BouwgrondVloerOppervlakte'                 => array( 'title'     => 'Vloer oppervlakte',
                                                                      'width'     => 100,
                                                                      'object'    => array('Bouwgrond')),
        'bedrijf_BouwgrondVloerOppervlakteProcentueel'      => array( 'title'     => 'Vloer oppervlakte procentueel',
                                                                      'object'    => array('Bouwgrond')),
				'bedrijf_GebouwNaam'																=> array( 'title'			=> 'Naam gebouw'),
        'bedrijf_InAanbouw'                                 => array( 'title'     => 'In aanbouw',
                                                                      'type'      => 'bool',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_Bouwjaar'                                  => array( 'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_OnderhoudBinnen'                           => array( 'title'     => 'Binnen',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_OnderhoudBinnenOmschrijving'               => array( 'title'     => 'Binnen omschrijving',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_OnderhoudBuiten'                           => array( 'title'     => 'Buiten',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_OnderhoudBuitenOmschrijving'               => array( 'title'     => 'Buiten omschrijving',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_EnergielabelKlasse'                        => array( 'title'     => 'Energielabel',
                                                                      'width'     => 100,
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_LokatieOmschrijving'                       => array( 'title'     => 'Omschrijving lokatie',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_SnelwegAfrit'                              => array( 'title'     => 'Afstand afrit snelweg',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_NsStation'                                 => array( 'title'     => 'Afstand NS station',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_NsVoorhalte'                               => array( 'title'     => 'Afstand NS voorhalte',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_BusKnooppunt'                              => array( 'title'     => 'Afstand bus knooppunt',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_TramKnooppunt'                             => array( 'title'     => 'Afstand tram knooppunt',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_MetroKnooppunt'                            => array( 'title'     => 'Afstand metro knooppunt',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_Bushalte'                                  => array( 'title'     => 'Afstand bushalte',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_Tramhalte'                                 => array( 'title'     => 'Afstand tramhalte',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_Metrohalte'                                => array( 'title'     => 'Afstand metrohalte',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_BankAfstand'                               => array( 'title'     => 'Afstand bank',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_BankAantal'                                => array( 'title'     => 'Aantal banken',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_OntspanningAfstand'                        => array( 'title'     => 'Afstand ontspanning',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_OntspanningAantal'                         => array( 'title'     => 'Aantal ontspanning',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_RestaurantAfstand'                         => array( 'title'     => 'Afstand restaurant',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_RestaurantAantal'                          => array( 'title'     => 'Aantal restaurants',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_WinkelAfstand'                             => array( 'title'     => 'Afstand winkel',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_WinkelAantal'                              => array( 'title'     => 'Aantal winkels',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_ParkerenOmschrijving'                      => array( 'title'     => 'Omschrijving',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_AantalParkeerplaatsen'                     => array( 'title'     => 'Aantal',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_AantalParkeerplaatsenOverdekt'             => array( 'title'     => 'Aantal overdekt',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_PrijsParkeerplaatsenOverdekt'              => array( 'title'     => 'Prijs overdekt',
                                                                      'type'      => 'priceBtw',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_AantalParkeerplaatsenNietOverdekt'         => array( 'title'     => 'Aantal niet overdekt',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_PrijsParkeerplaatsenNietOverdekt'          => array( 'title'     => 'Prijs niet overdekt',
                                                                      'type'      => 'priceBtw',
                                                                      'object'    => array('Bedrijfsruimte', 'Horeca', 'Kantoorruimte', 'Winkelruimte')),
        'bedrijf_BedrijfshalOppervlakte'                    => array( 'title'     => 'Oppervlakte',
                                                                      'type'      => 'oppervlakte',
                                                                      'width'     => 100,
                                                                      'object'    => array('Bedrijfsruimte')),
        'bedrijf_BedrijfshalInUnitsVanaf'                   => array( 'title'     => 'In units vanaf',
                                                                      'type'      => 'oppervlakte',
                                                                      'width'     => 100,
                                                                      'object'    => array('Bedrijfsruimte')),
        'bedrijf_BedrijfshalVrijeHoogte'                    => array( 'title'     => 'Vrije hoogte',
                                                                      'type'      => 'cm',
                                                                      'width'     => 100,
                                                                      'object'    => array('Bedrijfsruimte')),
        'bedrijf_BedrijfshalVrijeOverspanning'              => array( 'title'     => 'Vrije overspanning',
                                                                      'type'      => 'meter',
                                                                      'width'     => 100,
                                                                      'object'    => array('Bedrijfsruimte')),
        'bedrijf_BedrijfshalVloerbelasting'                 => array( 'title'     => 'Vloerbelasting',
                                                                      'addition'  => ' kg / m2',
                                                                      'width'     => 100,
                                                                      'object'    => array('Bedrijfsruimte')),
        'bedrijf_BedrijfshalVoorzieningen'                  => array( 'title'     => 'Voorzieningen',
                                                                      'object'    => array('Bedrijfsruimte')),
        'bedrijf_BedrijfshalPrijs'                          => array( 'title'     => 'Prijs',
                                                                      'type'      => 'priceBtw',
                                                                      'object'    => array('Bedrijfsruimte')),
        'bedrijf_KantoorruimteOppervlakte'                  => array( 'title'     => 'Oppervlakte',
                                                                      'type'      => 'oppervlakte',
                                                                      'width'     => 100,
                                                                      'object'    => array('Bedrijfsruimte', 'Kantoorruimte')),
        'bedrijf_KantoorruimteAantalVerdiepingen'            => array( 'title'     => 'Aantal verdiepingen',
                                                                      'width'     => 100,
                                                                      'object'    => array('Bedrijfsruimte', 'Kantoorruimte')),
        'bedrijf_KantoorruimteVoorzieningen'                 => array( 'title'     => 'Voorzieningen',
                                                                      'object'    => array('Bedrijfsruimte', 'Kantoorruimte')),
        'bedrijf_KantoorruimtePrijs'                         => array( 'title'     => 'Prijs',
                                                                      'type'      => 'priceBtw',
                                                                      'object'    => array('Bedrijfsruimte')),
        'bedrijf_KantoorruimteInUnitsVanaf'                  => array( 'title'     => 'In units vanaf',
                                                                      'width'     => 100,
                                                                      'object'    => array('Kantoorruimte')),
        'bedrijf_KantoorruimteTurnKey'                       => array( 'title'     => 'Turnkey',
                                                                      'type'      => 'bool',
                                                                      'object'    => array('Kantoorruimte')),
        'bedrijf_TerreinOppervlakte'                        => array( 'title'     => 'Oppervlakte',
                                                                      'type'      => 'oppervlakte',
                                                                      'width'     => 100,
                                                                      'object'    => array('Bedrijfsruimte')),
        'bedrijf_TerreinBouwvolumeBouwhoogte'               => array( 'title'     => 'Bouwhoogte',
                                                                      'type'      => 'meter',
                                                                      'width'     => 100,
                                                                      'object'    => array('Bedrijfsruimte')),
        'bedrijf_TerreinBouwvolumeVloerOppervlakte'         => array( 'title'     => 'Bruto vloeroppervlak',
                                                                      'type'      => 'oppervlakte',
                                                                      'width'     => 100,
                                                                      'object'    => array('Bedrijfsruimte')),
        'bedrijf_TerreinPrijs'                              => array( 'title'     => 'Prijs',
                                                                      'type'      => 'priceBtw',
                                                                      'object'    => array('Bedrijfsruimte')),
        'bedrijf_WinkelruimteOppervlakte'                   => array( 'title'     => 'Oppervlakte',
                                                                      'type'      => 'oppervlakte',
                                                                      'width'     => 100,
                                                                      'object'    => array('Winkelruimte')),
        'bedrijf_WinkelruimteVerkoopVloerOppervlakte'       => array( 'title'     => 'Verkoop vloer oppervlakte',
                                                                      'type'      => 'oppervlakte',
                                                                      'width'     => 100,
                                                                      'object'    => array('Winkelruimte')),
        'bedrijf_WinkelruimteInUnitsVanaf'                  => array( 'title'     => 'In units vanaf',
                                                                      'width'     => 100,
                                                                      'object'    => array('Winkelruimte')),
        'bedrijf_WinkelruimteFrontBreedte'                  => array( 'title'     => 'Front breedte',
                                                                      'type'      => 'cm',
                                                                      'width'     => 100,
                                                                      'object'    => array('Winkelruimte')),
        'bedrijf_WinkelruimteAantalVerdiepingen'            => array( 'title'     => 'Aantal verdiepingen',
                                                                      'width'     => 100,
                                                                      'object'    => array('Winkelruimte')),
        'bedrijf_WinkelruimteWelstandsklasse'               => array( 'title'     => 'Welstandsklasse',
                                                                      'object'    => array('Winkelruimte')),
        'bedrijf_WinkelruimteBrancheBeperking'              => array( 'title'     => 'Branche beperking',
                                                                      'type'      => 'bool',
                                                                      'object'    => array('Winkelruimte')),
        'bedrijf_WinkelruimteHorecaToegestaan'              => array( 'title'     => 'Horeca toegestaan',
                                                                      'type'      => 'bool',
                                                                      'object'    => array('Winkelruimte')),
        'bedrijf_WinkelruimteBijdrageWinkeliersvereniging'  => array( 'title'     => 'Bijdrage winkeliers vereniging',
                                                                      'type'      => 'bool',
                                                                      'object'    => array('Winkelruimte')),
        'bedrijf_WinkelruimtePersoneelTerOvername'          => array( 'title'     => 'Personeel ter overname',
                                                                      'type'      => 'bool',
                                                                      'object'    => array('Winkelruimte')),
        'bedrijf_WinkelruimtePrijsInventarisGoodwill'       => array( 'title'     => 'Prijs inventaris & goodwill',
                                                                      'type'      => 'priceBtw',
                                                                      'object'    => array('Winkelruimte')),
        'bedrijf_HorecaType'                                => array( 'title'     => 'Type',
                                                                      'object'    => array('Horeca')),
        'bedrijf_HorecaOppervlakte'                         => array( 'title'     => 'Oppervlakte',
                                                                      'type'      => 'oppervlakte',
                                                                      'width'     => 100,
                                                                      'object'    => array('Horeca')),
        'bedrijf_HorecaVerkoopVloerOppervlakte'             => array( 'title'     => 'Verkoop vloer oppervlakte',
                                                                      'type'      => 'oppervlakte',
                                                                      'width'     => 100,
                                                                      'object'    => array('Horeca')),
        'bedrijf_HorecaAantalVerdiepingen'                  => array( 'title'     => 'Aantal verdiepingen',
                                                                      'width'     => 100,
                                                                      'object'    => array('Horeca')),
        'bedrijf_HorecaWelstandsklasse'                     => array( 'title'     => 'Welstandsklasse',
                                                                      'object'    => array('Horeca')),
        'bedrijf_HorecaConcentratieGebied'                  => array( 'title'     => 'Concentratie gebied',
                                                                      'type'      => 'bool',
                                                                      'object'    => array('Horeca')),
        'bedrijf_HorecaRegio'                               => array( 'title'     => 'Regio',
                                                                      'object'    => array('Horeca')),
        'bedrijf_HorecaPersoneelTerOvername'                => array( 'title'     => 'Persoon ter overname',
                                                                      'type'      => 'bool',
                                                                      'object'    => array('Horeca')),
        'bedrijf_HorecaPrijsInventarisGoodwill'             => array( 'title'     => 'Prijs inventaris & goodwill',
                                                                      'type'      => 'priceBtw',
                                                                      'object'    => array('Horeca')),
				'bedrijf_PraktijkruimteOppervlakte'									=> array( 'title'			=> 'Oppervlakte',
																																			'type'			=> 'oppervlakte',
																																			'object'		=> array('Praktijkruimte')),
				'bedrijf_PraktijkruimteInUnitsVanaf'								=> array( 'title'			=> 'In units vanaf',
																																			'type'			=> 'oppervlakte',
																																			'object'		=> array('Praktijkruimte')),
				'bedrijf_GarageboxOppervlakte'											=> array( 'title'			=> 'Oppervlakte',
																																			'type'			=> 'oppervlakte',
																																			'object'		=> array('Garagebox')),
				'bedrijf_GarageboxInUnitsVanaf'											=> array( 'title'			=> 'In units vanaf',
																																			'type'			=> 'oppervlakte',
																																			'object'		=> array('Garagebox')),
				'bedrijf_LeisureOppervlakte'												=> array( 'title'			=> 'Oppervlakte',
																																			'type'			=> 'oppervlakte',
																																			'object'		=> array('Leisure'))
      );
    }
  }

  /**
  * @desc YogBOprFieldsSettings
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogBOprFieldsSettings extends YogFieldsSettingsAbstract
  {
    public function __construct()
    {
      $this->fieldsSettings = array(
        'yog-bopr_Updated'                  => array( 'title' => 'Laatste update via sync'),
        'yog-bopr_uuid'                     => array( 'title' => 'UUID'),
        'yog-bopr_scenario'                 => array( 'title' => 'Scenario'),
        'yog-bopr_Naam'                     => array( 'title' => 'Titel van object',
                                                      'width' => 450),
        'yog-bopr_Straat'                   => array(),
        'yog-bopr_Huisnummer'               => array( 'width' => 100),
        'yog-bopr_NummerreeksStart'         => array( 'title' => 'Nummerreeks start',
                                                      'width' => 50),
        'yog-bopr_NummerreeksEind'          => array( 'title' => 'Nummerreeks eind',
                                                      'width' => 50),
        'yog-bopr_Postcode'                 => array( 'width' => 100),
        'yog-nbpr_Wijk'                     => array( 'search'					=> 'exact',
																											'replacementMeta' => 'yog-bopr_WijkCustom'),
        'yog-bopr_Buurt'                    => array( 'search'					=> 'exact',
																											'replacementMeta' => 'yog-bopr_BuurtCustom'),
        'yog-bopr_Plaats'                   => array( 'search' => 'exact'),
        'yog-bopr_Gemeente'                 => array(),
        'yog-bopr_Provincie'                => array(),
        'yog-bopr_Land'                     => array(),
        'yog-bopr_Longitude'                => array(),
        'yog-bopr_Latitude'                 => array(),
        'yog-bopr_KoopPrijsMin'             => array( 'title' => 'Min.',
                                                      'type'  => 'price'),
        'yog-bopr_KoopPrijsMax'             => array( 'title' => 'Max.',
                                                      'type'  => 'price'),
        'yog-bopr_HuurPrijsMin'             => array( 'title' => 'Min.',
                                                      'type'  => 'price'),
        'yog-bopr_HuurPrijsMax'             => array( 'title' => 'Max.',
                                                      'type'  => 'price'),
        'yog-bopr_DatumStartBouw'           => array( 'title' => 'Datum start bouw'),
        'yog-bopr_DatumOplevering'          => array( 'title' => 'Datum oplevering'),
        'yog-bopr_DatumStartVerkoop'        => array( 'title' => 'Datum start verkoop'),
        'yog-bopr_GebouwNaam'               => array( 'title' => 'Gebouw naam'),
        'yog-bopr_AantalVerdiepingen'       => array( 'title' => 'Aantal verdiepingen')
      );
    }
  }

  /**
  * @desc YogNBprFieldsSettings
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogNBprFieldsSettings extends YogFieldsSettingsAbstract
  {
    public function __construct()
    {
      $this->fieldsSettings = array(
        'yog-nbpr_Updated'                  => array( 'title' => 'Laatste update via sync'),
        'yog-nbpr_uuid'                     => array( 'title' => 'UUID'),
        'yog-nbpr_scenario'                 => array( 'title' => 'Scenario'),
        'yog-nbpr_Naam'                     => array( 'title' => 'Titel van object',
                                                      'width' => 450),
        'yog-nbpr_Straat'                   => array(),
        'yog-nbpr_Huisnummer'               => array( 'width' => 100),
        'yog-nbpr_Postcode'                 => array( 'width' => 100),
        'yog-nbpr_Wijk'                     => array( 'search'					=> 'exact',
																											'replacementMeta' => 'yog-nbpr_WijkCustom'),
        'yog-nbpr_Buurt'                    => array( 'search'					=> 'exact',
																											'replacementMeta' => 'yog-nbpr_BuurtCustom'),
        'yog-nbpr_Plaats'                   => array( 'search' => 'exact'),
        'yog-nbpr_Gemeente'                 => array(),
        'yog-nbpr_Provincie'                => array(),
        'yog-nbpr_Land'                     => array(),
        'yog-nbpr_Longitude'                => array(),
        'yog-nbpr_Latitude'                 => array(),
        'yog-nbpr_PrijsType'                => array( 'title' => 'Prijs soort'),
        'yog-nbpr_KoopAanneemSomMin'        => array( 'title' => 'Min.',
                                                      'type'  => 'price'),
        'yog-nbpr_KoopAanneemSomMax'        => array( 'title' => 'Max.',
                                                      'type'  => 'price'),
        'yog-nbpr_HuurPrijsMin'             => array( 'title' => 'Min.',
                                                      'type'  => 'price'),
        'yog-nbpr_HuurPrijsMax'             => array( 'title' => 'Max.',
                                                      'type'  => 'price'),
        'yog-nbpr_HuurPrijsConditie'        => array( 'title' => 'Prijs conditie'),
        'yog-nbpr_PerceelOppervlakteMinMax' => array( 'title' => 'Perceel oppervlakte',
                                                      'type'  => 'oppervlakte'),
        'yog-nbpr_PerceelOppervlakteMin'    => array( 'title' => 'Min.',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100),
        'yog-nbpr_PerceelOppervlakteMax'    => array( 'title' => 'Max.',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100),
        'yog-nbpr_WoonOppervlakteMinMax'    => array( 'title' => 'Woon oppervlakte',
                                                      'type'  => 'oppervlakte'),
        'yog-nbpr_WoonOppervlakteMin'       => array( 'title' => 'Min.',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100,
                                                      'search'=> 'minmax-range'),
        'yog-nbpr_WoonOppervlakteMax'       => array( 'title' => 'Max.',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100),
        'yog-nbpr_InhoudMinMax'             => array( 'title' => 'Inhoud',
                                                      'type'  => 'inhoud'),
        'yog-nbpr_InhoudMin'                => array( 'title' => 'Min.',
                                                      'type'  => 'inhoud',
                                                      'width' => 100,
                                                      'search'=> 'minmax-range'),
        'yog-nbpr_InhoudMax'                => array( 'title' => 'Max.',
                                                      'type'  => 'inhoud',
                                                      'width' => 100),
        'yog-nbpr_Fase'                     => array(),
        'yog-nbpr_Status'                   => array(),
        'yog-nbpr_ProjectSoort'             => array( 'title' => 'Project soort'),
        'yog-nbpr_AantalEenheden'           => array( 'title' => 'Aantal eenheden'),
        'yog-nbpr_StartBouw'                => array( 'title' => 'Start bouw'),
        'yog-nbpr_DatumStartBouw'           => array( 'title' => 'Datum start bouw'),
        'yog-nbpr_Oplevering'               => array(),
        'yog-nbpr_DatumOplevering'          => array( 'title' => 'Datum oplevering')
      );
    }
  }

  /**
  * @desc YogNBtyFieldsSettings
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogNBtyFieldsSettings extends YogFieldsSettingsAbstract
  {
    public function __construct()
    {
      $this->fieldsSettings = array(
        'yog-nbty_Updated'                => array( 'title' => 'Laatste update via sync'),
        'yog-nbty_uuid'                   => array( 'title' => 'UUID'),
        'yog-nbty_scenario'               => array( 'title' => 'Scenario'),
        'yog-nbty_Naam'                   => array( 'title' => 'Titel van object',
                                                    'width' => 450),
        'yog-nbty_PrijsType'              => array( 'title' => 'Prijs soort'),
        'yog-nbty_KoopPrijsMin'           => array( 'title' => 'Min.',
                                                    'type'  => 'price'),
        'yog-nbty_KoopPrijsMax'           => array( 'title' => 'Max.',
                                                    'type'  => 'price'),
        'yog-nbty_HuurPrijsMin'           => array( 'title' => 'Min.',
                                                    'type'  => 'price'),
        'yog-nbty_HuurPrijsMax'           => array( 'title' => 'Max.',
                                                    'type'  => 'price'),
        'yog-nbty_HuurPrijsConditie'        => array( 'title' => 'Prijs conditie'),
        'yog-nbty_PerceelOppervlakteMinMax' => array( 'title' => 'Perceel oppervlakte',
                                                      'type'  => 'oppervlakte'),
        'yog-nbty_PerceelOppervlakteMin'    => array( 'title' => 'Min.',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100),
        'yog-nbty_PerceelOppervlakteMax'    => array( 'title' => 'Max.',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100),
        'yog-nbty_WoonOppervlakteMinMax'    => array( 'title' => 'Woon oppervlakte',
                                                      'type'  => 'oppervlakte'),
        'yog-nbty_WoonOppervlakteMin'       => array( 'title' => 'Min.',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100,
                                                      'search'=> 'minmax-range'),
        'yog-nbty_WoonOppervlakteMax'       => array( 'title' => 'Max.',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100),
        'yog-nbty_InhoudMinMax'             => array( 'title' => 'Inhoud',
                                                      'type'  => 'inhoud'),
        'yog-nbty_InhoudMin'                => array( 'title' => 'Min.',
                                                      'type'  => 'inhoud',
                                                      'width' => 100,
                                                      'search'=> 'minmax-range'),
        'yog-nbty_InhoudMax'                => array( 'title' => 'Max.',
                                                      'type'  => 'inhoud',
                                                      'width' => 100),
        'yog-nbty_Type'                     => array(),
        'yog-nbty_SoortWoning'              => array( 'title' => 'Soort woning',
                                                      'search'=> 'exact'),
        'yog-nbty_TypeWoning'             => array( 'title' => 'Type woning',
                                                    'search'=> 'exact'),
        'yog-nbty_KenmerkWoning'          => array( 'title' => 'Kenmerk woning'),
        'yog-nbty_PermanenteBewoning'     => array( 'title' => 'Permanente bewoning',
                                                    'type'  => 'bool'),
        'yog-nbty_Recreatiewoning'        => array( 'type'  => 'bool'),
        'yog-nbty_Aantalkamers'           => array( 'title' => 'Aantal kamers'),
        'yog-nbty_Verwarming'             => array(),
        'yog-nbty_WarmWater'              => array( 'title' => 'Warm water'),
        'yog-nbty_Dak'                    => array(),
        'yog-nbty_DakMaterialen'          => array( 'title' => 'Dak materialen'),
        'yog-nbty_Status'                 => array(),
        'yog-nbty_AantalEenheden'         => array( 'title' => 'Aantal eenheden'),
        'yog-nbty_AantalVrijeEenheden'    => array( 'title' => 'Aantal vrije eenheden'),
        'yog-nbty_StartBouw'              => array( 'title' => 'Start bouw'),
        'yog-nbty_DatumStartBouw'         => array( 'title' => 'Datum start bouw'),
        'yog-nbty_Oplevering'             => array(),
        'yog-nbty_DatumOplevering'        => array( 'title' => 'Datum oplevering'),
        'yog-nbty_GarageType'             => array( 'title' => 'Garage'),
        'yog-nbty_GarageCapaciteit'       => array( 'title' => 'Capaciteit garage'),
        'yog-nbty_GarageVoorzieningen'    => array( 'title' => 'Voorzieningen garage'),
        'yog-nbty_GarageIsolatievormen'   => array( 'title' => 'Isolatievormen garage'),
        'yog-nbty_TuinType'               => array( 'title' => 'Tuin'),
        'yog-nbty_TuinTotaleOppervlakte'  => array( 'title' => 'Totale oppervlakte tuin(en)'),
        'yog-nbty_HoofdTuinType'          => array( 'title' => 'Hoofd tuin'),
        'yog-nbty_HoofdTuinAchterom'      => array( 'title' => 'Achterom',
                                                    'type'  => 'bool'),
        'yog-nbty_HoofdTuinDiepte'        => array( 'title' => 'Diepte hoofd tuin',
                                                    'type'  => 'cm'),
        'yog-nbty_HoofdTuinBreedte'       => array( 'title' => 'Breedte hoofd tuin',
                                                    'type'  => 'cm'),
        'yog-nbty_HoofdTuinTotaleOppervlakte' => array('title' => 'Totale oppervlakte hoofd tuin',
                                                    'type'  => 'oppervlakte'),
        'yog-nbty_TuinLigging'            => array( 'title' => 'Ligging hoofd tuin'),
        'yog-nbty_BergingType'            => array( 'title' => 'Type berging'),
        'yog-nbty_BergingVoorzieningen'   => array( 'title' => 'Voorzieningen berging'),
        'yog-nbty_BergingIsolatievormen'  => array( 'title' => 'Isolatievormen berging'),
        'yog-nbty_CvKetel'                => array( 'title' => 'Type C.V.'),
        'yog-nbty_CvKetelBouwjaar'        => array( 'title' => 'Bouwjaar C.V.'),
        'yog-nbty_CvKetelBrandstof'       => array( 'title' => 'Brandstof C.V.'),
        'yog-nbty_CvKetelEigendom'        => array( 'title' => 'Eigendom C.V.'),
        'yog-nbty_CvCombiketel'           => array( 'title' => 'Combiketel',
                                                    'type'  => 'bool'),
        'yog-nbty_Plaats'                 => array( 'search'    => 'parent-exact',
                                                    'parentKey' => 'yog-nbpr_Plaats'),
        'yog-nbty_ParentLink'             => array('title'  => 'Project')

      );
    }
  }

  /**
  * @desc YogNBbnFieldsSettings
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogNBbnFieldsSettings extends YogFieldsSettingsAbstract
  {
    public function __construct()
    {
      $this->fieldsSettings = array(
        'yog-nbbn_Updated'                => array( 'title' => 'Laatste update via sync'),
        'yog-nbbn_uuid'                   => array( 'title' => 'UUID'),
        'yog-nbbn_scenario'               => array( 'title' => 'Scenario'),
        'yog-nbbn_Naam'                   => array( 'title' => 'Titel van object',
                                                    'width' => 450),
        'yog-nbbn_Straat'                 => array(),
        'yog-nbbn_Huisnummer'             => array( 'width' => 100),
        'yog-nbbn_Postcode'               => array( 'width' => 100),
        'yog-nbbn_Wijk'                   => array(),
        'yog-nbbn_Buurt'                  => array(),
        'yog-nbbn_Plaats'                 => array( 'search' => 'exact'),
        'yog-nbbn_Gemeente'               => array(),
        'yog-nbbn_Provincie'              => array(),
        'yog-nbbn_Land'                   => array(),
        'yog-nbbn_AantalKamers'           => array( 'title' => 'Aantal kamers',
                                                    'width' => 100),
        'yog-nbbn_GrondPrijs'             => array( 'title' => 'Grond prijs',
                                                    'type'  => 'price'),
        'yog-nbbn_AanneemSom'             => array( 'title' => 'Aanneemsom',
                                                    'type'  => 'price'),
        'yog-nbbn_KoopAanneemSom'         => array( 'title' => 'Koop aanneemsom',
                                                    'type'  => 'price'),
        'yog-nbbn_WoonOppervlakte'        => array( 'title' => 'Woon oppervlakte',
                                                    'type'  => 'oppervlakte'),
        'yog-nbbn_Inhoud'                 => array( 'type'  => 'inhoud'),
        'yog-nbbn_PerceelOppervlakte'     => array( 'title' => 'Perceel oppervlakte',
                                                    'type'  => 'oppervlakte')
      );
    }
  }

  /**
  * @desc YogBBprFieldsSettings
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogBBprFieldsSettings extends YogFieldsSettingsAbstract
  {
    public function __construct()
    {
      $this->fieldsSettings = array(
        'yog-bbpr_Updated'                  => array( 'title' => 'Laatste update via sync'),
        'yog-bbpr_uuid'                     => array( 'title' => 'UUID'),
        'yog-bbpr_scenario'                 => array( 'title' => 'Scenario'),
        'yog-bbpr_Naam'                     => array( 'title' => 'Titel van object',
                                                      'width' => 450),
        'yog-bbpr_Straat'                   => array(),
        'yog-bbpr_Huisnummer'               => array( 'width' => 100),
        'yog-bbpr_Postcode'                 => array( 'width' => 100),
        'yog-bbpr_Wijk'                     => array( 'search'					=> 'exact',
																											'replacementMeta' => 'yog-bbpr_WijkCustom'),
        'yog-bbpr_Buurt'                    => array( 'search'					=> 'exact',
																											'replacementMeta' => 'yog-bbpr_BuurtCustom'),
        'yog-bbpr_Plaats'                   => array( 'search' => 'exact'),
        'yog-bbpr_Gemeente'                 => array(),
        'yog-bbpr_Provincie'                => array(),
        'yog-bbpr_Land'                     => array(),
        'yog-bbpr_Longitude'                => array(),
        'yog-bbpr_Latitude'                 => array(),
        'yog-bbpr_PrijsType'                => array( 'title' => 'Prijs soort'),
        'yog-bbpr_KoopPrijsMin'             => array( 'title' => 'Min. koopprijs',
                                                      'type'  => 'price'),
        'yog-bbpr_KoopPrijsMax'             => array( 'title' => 'Max. koopprijs',
                                                      'type'  => 'price'),
        'yog-bbpr_HuurPrijsMin'             => array( 'title' => 'Min. huurprijs',
                                                      'type'  => 'price'),
        'yog-bbpr_HuurPrijsMax'             => array( 'title' => 'Max. huurprijs',
                                                      'type'  => 'price'),
        'yog-bbpr_HuurPrijsConditie'        => array( 'title' => 'Prijs conditie'),
        'yog-bbpr_PerceelOppervlakteMinMax' => array( 'title' => 'Perceel oppervlakte',
                                                      'type'  => 'oppervlakte'),
        'yog-bbpr_PerceelOppervlakteMin'    => array( 'title' => 'Min. perceeloppervlakte',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100),
        'yog-bbpr_PerceelOppervlakteMax'    => array( 'title' => 'Max. perceeloppervlakte',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100),
        'yog-bbpr_WoonOppervlakteMinMax'    => array( 'title' => 'Woon oppervlakte',
                                                      'type'  => 'oppervlakte'),
        'yog-bbpr_WoonOppervlakteMin'       => array( 'title' => 'Min. woonoppervlakte',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100,
                                                      'search'=> 'minmax-range'),
        'yog-bbpr_WoonOppervlakteMax'       => array( 'title' => 'Max. woonoppervlakte',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100),
        'yog-bbpr_InhoudMinMax'             => array( 'title' => 'Inhoud',
                                                      'type'  => 'inhoud'),
        'yog-bbpr_InhoudMin'                => array( 'title' => 'Min. inhoud',
                                                      'type'  => 'inhoud',
                                                      'width' => 100,
                                                      'search'=> 'minmax-range'),
        'yog-bbpr_InhoudMax'                => array( 'title' => 'Max. inhoud',
                                                      'type'  => 'inhoud',
                                                      'width' => 100)
      );
    }
  }

  /**
  * @desc YogBBtyFieldsSettings
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogBBtyFieldsSettings extends YogFieldsSettingsAbstract
  {
    public function __construct()
    {
      $this->fieldsSettings = array(
        'yog-bbty_Updated'                => array( 'title' => 'Laatste update via sync'),
        'yog-bbty_uuid'                   => array( 'title' => 'UUID'),
        'yog-bbty_scenario'               => array( 'title' => 'Scenario'),
        'yog-bbty_Naam'                   => array( 'title' => 'Titel van object',
                                                    'width' => 450),
        'yog-bbty_KoopPrijsMin'           => array( 'title' => 'Min. koopprijs',
                                                    'type'  => 'price'),
        'yog-bbty_KoopPrijsMax'           => array( 'title' => 'Max. koopprijs',
                                                    'type'  => 'price'),
        'yog-bbty_HuurPrijsMin'           => array( 'title' => 'Min. huurprijs',
                                                    'type'  => 'price'),
        'yog-bbty_HuurPrijsMax'           => array( 'title' => 'Max. huurprijs',
                                                    'type'  => 'price'),
        'yog-bbty_HuurPrijsConditie'        => array( 'title' => 'Prijs conditie'),
        'yog-bbty_PerceelOppervlakteMinMax' => array( 'title' => 'Perceel oppervlakte',
                                                      'type'  => 'oppervlakte'),
        'yog-bbty_PerceelOppervlakteMin'    => array( 'title' => 'Min. perceeloppervlakte',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100),
        'yog-bbty_PerceelOppervlakteMax'    => array( 'title' => 'Max. perceeloppervlakte',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100),
        'yog-bbty_WoonOppervlakteMinMax'    => array( 'title' => 'Woon oppervlakte',
                                                      'type'  => 'oppervlakte'),
        'yog-bbty_WoonOppervlakteMin'       => array( 'title' => 'Min. woonoppervlakte',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100,
                                                      'search'=> 'minmax-range'),
        'yog-bbty_WoonOppervlakteMax'       => array( 'title' => 'Max. woonoppervlakte',
                                                      'type'  => 'oppervlakte',
                                                      'width' => 100),
        'yog-bbty_InhoudMinMax'             => array( 'title' => 'Inhoud',
                                                      'type'  => 'inhoud'),
        'yog-bbty_InhoudMin'                => array( 'title' => 'Min. inhoud',
                                                      'type'  => 'inhoud',
                                                      'width' => 100,
                                                      'search'=> 'minmax-range'),
        'yog-bbty_InhoudMax'                => array( 'title' => 'Max. inhoud',
                                                      'type'  => 'inhoud',
                                                      'width' => 100),
        'yog-bbty_Type'                     => array(),
        'yog-bbty_SoortWoning'              => array( 'title' => 'Soort woning',
                                                      'search'=> 'exact'),
        'yog-bbty_TypeWoning'             => array( 'title' => 'Type woning',
                                                    'search'=> 'exact'),
        'yog-bbty_KenmerkWoning'          => array( 'title' => 'Kenmerk woning'),
        'yog-bbty_PermanenteBewoning'     => array( 'title' => 'Permanente bewoning',
                                                    'type'  => 'bool'),
        'yog-bbty_Recreatiewoning'        => array( 'type'  => 'bool'),
        'yog-bbty_Aantalkamers'           => array( 'title' => 'Aantal kamers'),
        'yog-bbty_Verwarming'             => array(),
        'yog-bbty_WarmWater'              => array( 'title' => 'Warm water'),
        'yog-bbty_Dak'                    => array(),
        'yog-bbty_DakMaterialen'          => array( 'title' => 'Dak materialen'),
        'yog-bbty_Status'                 => array(),
        'yog-bbty_GarageType'             => array( 'title' => 'Garage'),
        'yog-bbty_GarageCapaciteit'       => array( 'title' => 'Capaciteit garage'),
        'yog-bbty_GarageVoorzieningen'    => array( 'title' => 'Voorzieningen garage'),
        'yog-bbty_GarageIsolatievormen'   => array( 'title' => 'Isolatievormen garage'),
        'yog-bbty_TuinType'               => array( 'title' => 'Tuin'),
        'yog-bbty_TuinTotaleOppervlakte'  => array( 'title' => 'Totale oppervlakte tuin(en)'),
        'yog-bbty_HoofdTuinType'          => array( 'title' => 'Hoofd tuin'),
        'yog-bbty_HoofdTuinAchterom'      => array( 'title' => 'Achterom',
                                                    'type'  => 'bool'),
        'yog-bbty_HoofdTuinDiepte'        => array( 'title' => 'Diepte hoofd tuin',
                                                    'type'  => 'cm'),
        'yog-bbty_HoofdTuinBreedte'       => array( 'title' => 'Breedte hoofd tuin',
                                                    'type'  => 'cm'),
        'yog-bbty_HoofdTuinTotaleOppervlakte' => array('title' => 'Totale oppervlakte hoofd tuin',
                                                    'type'  => 'oppervlakte'),
        'yog-bbty_TuinLigging'            => array( 'title' => 'Ligging hoofd tuin'),
        'yog-bbty_BergingType'            => array( 'title' => 'Berging'),
        'yog-bbty_BergingVoorzieningen'   => array( 'title' => 'Voorzieningen berging'),
        'yog-bbty_BergingIsolatievormen'  => array( 'title' => 'Isolatievormen berging'),
        'yog-bbty_CvKetel'                => array( 'title' => 'Type C.V.'),
        'yog-bbty_CvKetelBouwjaar'        => array( 'title' => 'Bouwjaar C.V.'),
        'yog-bbty_CvKetelBrandstof'       => array( 'title' => 'Brandstof C.V.'),
        'yog-bbty_CvKetelEigendom'        => array( 'title' => 'Eigendom C.V.'),
        'yog-bbty_CvCombiketel'           => array( 'title' => 'Combiketel',
                                                    'type'  => 'bool'),
        'yog-bbty_Plaats'                 => array( 'search'    => 'parent-exact',
                                                    'parentKey' => 'yog-bbpr_Plaats'),
        'yog-bbty_ParentLink'             => array( 'title' => 'Complex')
      );
    }
  }

  /**
  * @desc YogRelationFieldsSettings
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogRelationFieldsSettings extends YogFieldsSettingsAbstract
  {
    /**
    * @desc Constructor
    *
    * @param void
    * @return YogRelationFieldsSettings
    */
    public function __construct()
    {
      $this->fieldsSettings = array(
				'relatie_type'									=> array(),
				'relatie_subtype'								=> array(),
        'relatie_Titel'                 => array(),
        'relatie_Initialen'             => array(),
        'relatie_Voornaam'              => array(),
        'relatie_Voornamen'             => array(),
        'relatie_Tussenvoegsel'         => array(),
        'relatie_Achternaam'            => array(),
				'relatie_Geslacht'							=> array(),
				'relatie_Functie'								=> array(),
        'relatie_Emailadres'            => array(),
        'relatie_Website'               => array(),
        'relatie_Telefoonnummer'        => array(),
        'relatie_Telefoonnummerwerk'    => array('title' => 'Telefoonnummer werk'),
        'relatie_Telefoonnummermobiel'  => array('title' => 'Telefoonnummer mobiel'),
        'relatie_Faxnummer'             => array(),
        'relatie_Hoofdadres_land'       => array('title' => 'Land'),
        'relatie_Hoofdadres_provincie'  => array('title' => 'Provincie'),
        'relatie_Hoofdadres_gemeente'   => array('title' => 'Gemeente'),
        'relatie_Hoofdadres_stad'       => array('title' => 'Stad'),
        'relatie_Hoofdadres_wijk'       => array('title' => 'Wijk'),
        'relatie_Hoofdadres_buurt'      => array('title' => 'Buurt'),
        'relatie_Hoofdadres_straat'     => array('title' => 'Straat'),
        'relatie_Hoofdadres_postcode'   => array('title' => 'Postcode'),
        'relatie_Hoofdadres_huisnummer' => array('title' => 'Huisnummer'),
        'relatie_Postadres_land'        => array('title' => 'Land'),
        'relatie_Postadres_provincie'   => array('title' => 'Provincie'),
        'relatie_Postadres_gemeente'    => array('title' => 'Gemeente'),
        'relatie_Postadres_stad'        => array('title' => 'Stad'),
        'relatie_Postadres_wijk'        => array('title' => 'Wijk'),
        'relatie_Postadres_buurt'       => array('title' => 'Buurt'),
        'relatie_Postadres_straat'      => array('title' => 'Straat'),
        'relatie_Postadres_postcode'    => array('title' => 'Postcode'),
        'relatie_Postadres_huisnummer'  => array('title' => 'Huisnummer'),
        'relatie_Longitude'          => array(),
        'relatie_Latitude'           => array()
      );
    }
  }