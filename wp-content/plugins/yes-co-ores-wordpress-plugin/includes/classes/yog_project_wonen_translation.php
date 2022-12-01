<?php
/**
* @desc YogProjectWonenTranslation
* @author Kees Brandenburg - Yes-co Nederland
*/
class YogProjectWonenTranslation extends YogProjectTranslationAbstract
{
    const POST_TYPE = 'huis';

    /**
    * @desc Get post type
    *
    * @param void
    * @return string
    */
    public function getPostType()
    {
        return YOG_POST_TYPE_WONEN;
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
    * @desc Check if a parent uuid is set
    *
    * @param void
    * @return bool
    */
    public function hasParentUuid()
    {
        return $this->mcp3Project->hasParentUuid();
    }

    /**
    * @desc Get the parent uuid
    *
    * @param void
    * @return string
    * @throws Exception
    */
    public function getParentUuid()
    {
        if (!$this->hasParentUuid())
            throw new Exception(__METHOD__ . '; Object does not have a parent object');

        return $this->mcp3Project->getParentUuid();
    }

    /**
    * @desc Get the title
    *
    * @param void
    * @return string
    */
    public function determineTitle()
    {
        if ($this->mcp3Project->hasAddress())
        {
            $address  = $this->mcp3Project->getAddress();
            $title    = $address->getStreet() . ' ' . $address->getHouseNumber() . $address->getHouseNumberAddition() . ' ' . $address->getCity();
        }
        else
        {
            $title    = $this->mcp3Project->getName();
        }

        return $title;
    }

    /**
    * @desc Get meta data
    *
    * @param void
    * @return array
    */
    public function getMetaData()
    {
        // General meta data
        $data = array_merge($this->getGenericMetaData(), $this->getGeneralMetaData());

		// Also add text meta data?
		$textData	= $this->getTextMetaData();
		if (!empty($textData))
			$data = array_merge($data, $textData);

        // Type specific meta data
        switch (strtolower($this->mcp3Project->getType()))
        {
            case 'woonruimte':
                $data = array_merge($data, $this->getWoonruimteMetaData());
                break;
            case 'bouwgrond':
                $data = array_merge($data, $this->getBouwgrondMetaData());
                break;
            case 'parkeergelegenheid':
                $data = array_merge($data, $this->getParkeergelegenheidMetaData());
                break;
            case 'onderstuk':
                $data = array_merge($data, $this->getOnderstukMetaData());
                break;
            case 'opslagruimte':
                $data = array_merge($data, $this->getOpslagruimteMetaData());
                break;
            case 'berging':
                $data = array_merge($data, $this->getBergingMetaData());
                break;
            case 'standplaats':
                $data = array_merge($data, $this->getStandplaatsMetaData());
                break;
            case 'ligplaats':
                $data = array_merge($data, $this->getLigplaatsMetaData());
                break;
        }

		// Determine a rental price to do calculations with (rental price calculated to a price per year)
		if (!empty($data['HuurPrijs']))
		{
            switch ($data['HuurPrijsConditie'])
            {
                case 'p.m.':
                    $data['HuurPrijsPerJaar'] = $data['HuurPrijs'] * 12;
                    break;
                default:
                    $data['HuurPrijsPerJaar'] = $data['HuurPrijs'];
                    break;
            }
		}

        return $data;
    }

    /**
     * Determine price to sort project by
     *
     * @param void
     * @return mixed
     */
    public function determineSortPrice()
    {
        $price = $this->mcp3Project->getStringByPath('//project:Details/project:Koop/project:Prijs');
        if (!empty($price))
            return $price;

        $price = $this->mcp3Project->getStringByPath('//project:Details/project:Huur/project:Prijs');
        if (!empty($price))
            return $price;

        return 0;
    }

    /**
     * Determine the city/street sort
     *
     * @param void
     * @return string
     */
    public function determineSortCityStreet()
    {
		$sort = '';

        if ($this->mcp3Project->hasAddress())
        {
            $address	= $this->mcp3Project->getAddress();
            $sort		= $address->getCity() . ' ' . $address->getStreet() . ' ' . $address->getHouseNumber() . $address->getHouseNumberAddition();
        }

		return $sort;
	}

    /**
    * @desc Get the categories to link project to
    *
    * @param void
    * @return array
    */
    public function getCategories()
    {
        $state      = strtolower($this->determineState());
        $sold       = in_array($state, array('verkocht', 'verhuurd'));
	    $categories = array('consument');

		if (in_array($this->mcp3Project->getScenario(), array('BBvk', 'BBvh', 'LIvk')))
			$categories[] = 'bestaand';
		elseif (in_array($this->mcp3Project->getScenario(), array('NBvk', 'NBvh')))
			$categories[] = 'nieuwbouw';

        switch (strtolower($this->mcp3Project->getType()))
        {
            // Woonruimte
            case 'woonruimte':
                $categories[] = 'woonruimte';
                $categories[] = strtolower($this->mcp3Project->getSubType());

                // Check for open house
                $openHouseStart = $this->mcp3Project->getStringByPath('//project:Details/project:OpenHuis/project:Van');
                $openHouseEnd   = $this->mcp3Project->getStringByPath('//project:Details/project:OpenHuis/project:Tot');

                if ((!empty($openHouseStart) && strtotime($openHouseStart) > time()) || (!empty($openHouseEnd) || strtotime($openHouseEnd) > time()))
                    $categories[] = 'open-huis';

                if ($sold === false)
                    $categories[] = 'woonruimte-actief';

                break;
            // Other
            default:
                $categories[] = strtolower($this->mcp3Project->getType());

                if ($sold === false)
                    $categories[] = strtolower($this->mcp3Project->getType()) . '-actief';
                break;
        }

        // Verkoop
        $koopPrijs = $this->mcp3Project->getStringByPath('//project:Details/project:Koop/project:Prijs');
        if (!empty($koopPrijs))
            $categories[] = 'verkoop';

        // Verhuur
        $huurPrijs = $this->mcp3Project->getStringByPath('//project:Details/project:Huur/project:Prijs');
        if (!empty($huurPrijs))
            $categories[] = 'verhuur';

        // State
        if ($sold === true)
        {
            $categories[] = 'verkochtverhuurd';

            if ($state === 'verhuurd')
                $categories[] = 'verhuurd';
            else
                $categories[] = 'verkocht';
        }

        // Allow the theme to add custom categories
        $this->getThemeCategories($this->mcp3Project, $categories);

        return $categories;
    }

    /**
    * @desc General meta data
    *
    * @param void
    * @return array
    */
    protected function getGeneralMetaData()
    {
        $scenario = $this->mcp3Project->getScenario();
        
        $data = array(
          'uuid'                  => $this->mcp3Project->getStringByPath('/project:Project/@uuid'),
          'dlm'                   => $this->translateDate($this->mcp3Link->getDlm()),
          'scenario'              => $scenario,
          'Source'                => $this->mcp3Project->getStringByPath('/project:Project/@source'),
          'Status'                => $this->determineState(),
          'Naam'                  => $this->mcp3Project->getStringByPath('//project:General/project:Name'),
          'Land'                  => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:Country'),
          'Provincie'             => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:State'),
          'Gemeente'              => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:Municipality'),
          'Plaats'                => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:City'),
          'Wijk'                  => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:Area'),
          'Buurt'                 => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:Neighbourhood'),
          'Straat'                => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:Street'),
          'Postcode'              => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:Zipcode'),
          'Longitude'             => $this->mcp3Project->getStringByPath('//project:General/project:GeoCode/project:Longitude'),
          'Latitude'              => $this->mcp3Project->getStringByPath('//project:General/project:GeoCode/project:Latitude'),
          'DatumVoorbehoudTot'    => $this->mcp3Project->getStringByPath('//project:General/project:Voorbehoud'),
          'KoopPrijsSoort'        => ucfirst($this->mcp3Project->getStringByPath('//project:Details/project:Koop/project:PrijsSoort')),
          'KoopPrijs'             => $this->mcp3Project->getStringByPath('//project:Details/project:Koop/project:Prijs'),
          'WozWaarde'             => $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:WOZ/project:Waarde'),
          'KoopPrijsConditie'     => $this->translatePriceCondition($this->mcp3Project->getStringByPath('//project:Details/project:Koop/project:PrijsConditie')),
          'KoopPrijsVervanging'   => $this->mcp3Project->getStringByPath('//project:Details/project:Koop/project:PrijsVervanging'),
          'Veilingdatum'          => $this->mcp3Project->getStringByPath('//project:Details/project:Koop/project:Veiling/project:Datum'),
          'HuurPrijs'             => $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs'),
          'HuurPrijsConditie'     => $this->translatePriceCondition($this->mcp3Project->getStringByPath('//project:Details/project:Huur/project:PrijsConditie')),
          'HuurPrijsVervanging'   => $this->mcp3Project->getStringByPath('//project:Details/project:Huur/project:PrijsVervanging'),
          'OpenHuisVan'           => $this->mcp3Project->getDateTimeByPath('//project:Details/project:OpenHuis/project:Van', $this->wpTimezone),
          'OpenHuisTot'           => $this->mcp3Project->getDateTimeByPath('//project:Details/project:OpenHuis/project:Tot', $this->wpTimezone),
          'OppervlaktePerceel'    => $this->mcp3Project->getIntByPath('//project:KadastraleInformatie/project:PerceelOppervlakte'),
          'Informatieplicht'      => $this->mcp3Project->getStringByPath('//project:Details/project:Informatie/project:Informatieplicht'),
          'OzbGebruikersDeel'     => $this->mcp3Project->getStringByPath('//project:Details/project:ZakelijkeLasten/project:OzbGebruikersDeel'),
          'OzbZakelijkeDeel'      => $this->mcp3Project->getStringByPath('//project:Details/project:ZakelijkeLasten/project:OzbZakelijkeDeel'),
          'Waterschapslasten'     => $this->mcp3Project->getStringByPath('//project:Details/project:ZakelijkeLasten/project:WaterschapsLasten'),
          'Stookkosten'           => $this->mcp3Project->getStringByPath('//project:Details/project:ZakelijkeLasten/project:Stookkosten'),
          'RuilverkavelingsRente' => $this->mcp3Project->getStringByPath('//project:Details/project:ZakelijkeLasten/project:RuilverkavelingsRente'),
          'Rioolrechten'          => $this->mcp3Project->getStringByPath('//project:Details/project:ZakelijkeLasten/project:Rioolrechten'),
          'Eigendomsoort'         => $this->mcp3Project->getStringByPath('//project:KadastraleInformatie/project:Eigendomsoort'),
          'ErfpachtPerJaar'       => $this->mcp3Project->getIntByPath('//project:KadastraleInformatie/project:Eigendom/project:ErfpachtPerJaar'),
          'BouwType'              => (in_array($scenario, ['NBvk', 'NBvh']) ? 'Nieuwbouw' : 'Bestaande bouw')
        );

        // ApiKey
        if ($this->mcp3Project->hasNodeByXpath('/project:Project/@apiId'))
            $data['ApiKey']           = $this->mcp3Project->getStringByPath('/project:Project/@apiId'); // 3mcp 1.6
        else
            $data['ApiKey']           = $this->mcp3Project->getStringByPath('/project:Project/project:YProjectNumber'); // 3mcp 1.4

        // Zakelijk recht
        if ($this->mcp3Project->hasNodeByXpath('//project:Details/project:ZakelijkeRechten/project:ZakelijkRecht/@naam'))
            $data['ZakelijkeRechten'] = $this->mcp3Project->getStringByPath('//project:Details/project:ZakelijkeRechten/project:ZakelijkRecht/@naam');	//3mcp 1.4
        else
            $data['ZakelijkeRechten'] = $this->mcp3Project->getStringByPath('//project:Details/project:ZakelijkeRechten/project:ZakelijkRecht');				//3mp 1.6

        // Erfpacht duur
	    $data['ErfpachtDuur']     = $this->mcp3Project->getStringByPath('//project:KadastraleInformatie[0]/project:Eigendom/project:ErfpachtDuur');
	    if ($data['ErfpachtDuur'] != 'eeuwig')
		    $data['ErfpachtDuur'] .= ' ' . $this->mcp3Project->getStringByPath('//project:KadastraleInformatie[0]/project:Eigendom/project:EindDatum');

        // Service costs
        $serviceKosten            = $this->mcp3Project->getStringByPath('//project:Details/project:Koop/project:Servicekosten');
        $bijdrageVve              = $this->mcp3Project->getStringByPath('//project:Details/project:ZakelijkeLasten/project:BijdrageVve');
	    $data['Servicekosten']    = empty($serviceKosten) ? $bijdrageVve : $serviceKosten;

        // Housenumber
        if ($this->mcp3Project->hasAddress())
        {
            $address					= $this->mcp3Project->getAddress();
			$addition					= $address->getHouseNumberAddition();

            $data['Huisnummer']			= $address->getHouseNumber() . $addition;
			$data['HuisnummerNumeriek']	= $address->getHouseNumber();

			if (!empty($addition))
				$data['HuisnummerToevoeging'] = $addition;
        }

        // Type
		$type                     = ($this->mcp3Project->hasSubType()) ? $this->mcp3Project->getSubType() : $this->mcp3Project->getType();
	    $data['Type']             = $type;

        // Aanvaarding
        $aanvaardingType          = $this->mcp3Project->getStringByPath('//project:Details/project:Aanvaarding/project:Type');
        if($aanvaardingType == 'per datum') {
            $datetime            = $this->mcp3Project->getDateTimeByPath('//project:Details/project:Aanvaarding/project:Datum', null, 'd-m-Y');
            if (!empty($datetime)) {
                $data['Aanvaarding'] = 'per ' . $datetime;
            }
        } else {
            $data['Aanvaarding'] = $this->mcp3Project->getStringByPath('//project:Details/project:Aanvaarding/project:Type');
        }
        
        $toelichting              = $this->mcp3Project->getStringByPath('//project:Details/project:Aanvaarding/project:Toelichting');
	    if (is_string($toelichting) && strlen(trim($toelichting)) > 0)
		    $data['Aanvaarding'] .= ', ' .$toelichting;

        return $data;
    }

    /**
    * @desc Determine project state
    *
    * @param void
    * @return string
    */
    private function determineState()
    {
	    $state          = $this->mcp3Project->getStringByPath('//project:General/project:ObjectStatus');
	    $voorbehoudDate = $this->mcp3Project->getStringByPath('//project:General/project:Voorbehoud');

	    if (in_array(strtolower($state), array('verkocht onder voorbehoud', 'verhuurd onder voorbehoud')) && !empty($voorbehoudDate) && strtotime($voorbehoudDate) < date('U')) {
            $koopPrijs = $this->mcp3Project->getStringByPath('//project:Details/project:Koop/project:Prijs');
            $huurPrijs = $this->mcp3Project->getStringByPath('//project:Details/project:Huur/project:Prijs');

            if (!empty($koopPrijs)) {
                $state = 'Verkocht';
            } else if (!empty($huurPrijs)) {
                $state = 'Verhuurd';
            } else {
                $state = ucfirst(str_replace(' onder voorbehoud', '', $state));
            }
	    } elseif ($state !== 'beschikbaar') {
            $state = ucfirst($state);
        }

        return $state;
    }

    /**
    * @desc Get woonruimte meta data
    *
    * @param void
    * @return array
    */
    protected function getWoonruimteMetaData()
    {
        $data = array(
          'PremieSubsidies'             => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:PremieSubsidie/project:Soort/@naam'),
          'Aantalkamers'                => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Verdieping/project:AantalKamers'),
          'AantalSlaapkamers'           => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Verdieping/project:AantalSlaapkamers'),
          'Oppervlakte'                 => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:WoonOppervlakte'),
          'Inhoud'                      => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Inhoud'),
          'Woonkamer'                   => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Verdieping/project:Kamers/project:Woonkamer/project:Type'),
          'Keuken'                      => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Verdieping/project:Kamers/project:Keuken/project:Type'),
          'KeukenVernieuwd'             => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Verdieping/project:Kamers/project:Keuken/project:JaarVernieuwd'),
          'Ligging'                     => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Ligging'),
          'GarageType'                  => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Garage/project:Type'),
          'GarageCapaciteit'            => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Garage/project:Capaciteit'),
          'TuinType'                    => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Tuin/project:Type'),
          'TuinTotaleOppervlakte'       => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Tuin/project:TotaleOppervlakte'),
          'HoofdTuinType'               => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Tuin/project:HoofdtuinType'),
          'HoofdTuinTotaleOppervlakte'  => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Tuin/project:Oppervlakte'),
          'TuinLigging'                 => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Tuin/project:Ligging'),
          'BergingType'                 => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Soort'),
          'PraktijkruimteType'          => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Praktijkruimte/project:Type'),
          'PraktijkruimteMogelijk'      => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:PraktijkruimteMogelijk/project:Type'),
          'EnergielabelKlasse'          => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Energielabel/project:Energieklasse'),
          'HuidigGebruik'               => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Bestemming/project:HuidigGebruik'),
          'HuidigeBestemming'           => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Bestemming/project:HuidigeBestemming'),
          'PermanenteBewoning'          => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Bestemming/project:PermanenteBewoning'),
          'Recreatiewoning'             => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Bestemming/project:Recreatiewoning'),
          'Verwarming'                  => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Installatie/project:Verwarming/project:Type'),
          'WarmWater'                   => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Installatie/project:WarmWater/project:Type'),
          'CvKetel'                     => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Installatie/project:CvKetel/project:Type'),
          'CvKetelBouwjaar'             => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Installatie/project:CvKetel/project:Bouwjaar'),
          'Dak'                         => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Diversen/project:Dak'),
          'OnderhoudBinnen'             => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Onderhoud/project:Binnen/project:Waardering'),
          'OnderhoudBuiten'             => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Onderhoud/project:Buiten/project:Waardering'),
          'OnderhoudSchilderwerkBinnen' => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Onderhoud/project:SchilderwerkBinnen'),
          'OnderhoudSchilderwerkBuiten' => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Onderhoud/project:SchilderwerkBuiten'),
          'OnderhoudPlafond'            => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Onderhoud/project:Plafond'),
          'OnderhoudMuren'              => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Onderhoud/project:Muren'),
          'OnderhoudVloer'              => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Onderhoud/project:Vloer'),
          'OnderhoudDak'                => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Onderhoud/project:Dak'),
          'Verdiepingen'				=> $this->mcp3Project->getNumFloors()
        );

        // Bouwjaar
        $bouwjaarPeriode  = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Bouwjaar/project:Periode');
        $bouwjaar         = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Bouwjaar/project:BouwjaarOmschrijving/project:Jaar');
        $data["Bouwjaar"] = empty($bouwjaarPeriode) ? $bouwjaar : $this->translateBouwjaarPeriode($bouwjaarPeriode);

        // Voorzieningen
        $voorzieningen    = array();

		if ($this->mcp3Project->hasNodeByXpath('//project:Details/project:Woonruimte/project:Voorzieningen/project:Voorziening/@naam'))	// 3mcp 1.4
		{
            $names            = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Voorzieningen/project:Voorziening/@naam');
            if (!empty($names))
                $voorzieningen[] = $names;
		}
        else
        {
            $names            = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Voorzieningen/project:Voorziening');
            if (!empty($names))
                $voorzieningen[] = $names;
        }

        if ($this->mcp3Project->hasNodeByXpath('//project:Details/project:Woonruimte/project:Verdieping/project:Indelingen/project:Indeling/@naam'))	// 3mcp 1.4
        {
            $names            = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Verdieping/project:Indelingen/project:Indeling/@naam');
            if (!empty($names))
                $voorzieningen[] = $names;
        }
        else	// 3mcp 1.6
        {
            $names            = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Verdieping/project:Indelingen/project:Indeling');
            if (!empty($names))
                $voorzieningen[] = $names;
        }

        if (count($voorzieningen) > 0)
            $data['Voorzieningen'] = implode(', ', $voorzieningen);

        // Bijzonderheden
        if ($this->mcp3Project->hasNodeByXpath('//project:Details/project:Woonruimte/project:Diversen/project:Bijzonderheden/project:Bijzonderheid/@naam'))
            $data['Bijzonderheden']	= $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Diversen/project:Bijzonderheden/project:Bijzonderheid/@naam'); // 3mcp 1.4
        else
            $data['Bijzonderheden']	= $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Diversen/project:Bijzonderheden/project:Bijzonderheid'); // 3mcp 1.6

        // Isolatie
        if ($this->mcp3Project->hasNodeByXpath('//project:Details/project:Woonruimte/project:Diversen/project:Isolatievormen/project:Isolatie/@naam'))
            $data['Isolatie']       = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Diversen/project:Isolatievormen/project:Isolatie/@naam'); // 3mcp 1.4
        else
            $data['Isolatie']       = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Diversen/project:Isolatievormen/project:Isolatie'); // 3mcp 1.6

        // BergingVoorzieningen
        if ($this->mcp3Project->hasNodeByXpath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Voorzieningen/project:Voorziening/@naam'))
            $data['BergingVoorzieningen']	= $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Voorzieningen/project:Voorziening/@naam'); 	// 3mcp 1.4
        else
            $data['BergingVoorzieningen']	= $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Voorzieningen/project:Voorziening');	// 3mcp 1.6

        // BergingIsolatie
        if ($this->mcp3Project->hasNodeByXpath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Isolatievormen/project:Isolatievorm/@naam'))
            $data['BergingIsolatie']      = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Isolatievormen/project:Isolatievorm/@naam'); // 3mcp 1.4
        else
            $data['BergingIsolatie']      = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Isolatievormen/project:Isolatievorm'); // 3mcp 1.6

        // Dak materiaal
        if ($this->mcp3Project->hasNodeByXpath('//project:Details/project:Woonruimte/project:Diversen/project:DakMaterialen/project:DakMateriaal/@naam'))
            $data['DakMaterialen']        = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Diversen/project:DakMaterialen/project:DakMateriaal/@naam'); // 3mcp 1.4
        else
            $data['DakMaterialen']        = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Diversen/project:DakMaterialen/project:DakMateriaal'); // 3mcp 1.6

        // Subtype specific
        switch (strtolower($this->mcp3Project->getSubType()))
        {
            case 'woonhuis':
                $data["SoortWoning"]    = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Woonhuis/project:SoortWoning');
                $data["TypeWoning"]     = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Woonhuis/project:TypeWoning');
                $data["KenmerkWoning"]  = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Woonhuis/project:Kenmerk');
                break;
            case 'appartement':
                $data["SoortWoning"]    = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Appartement/project:SoortAppartement');
                $data["KenmerkWoning"]  = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Appartement/project:Kenmerk');
                break;
        }

        return $data;
    }

    /**
    * @desc Get bouwgrond meta data
    *
    * @param void
    * @return array
    */
    protected function getBouwgrondMetaData()
    {
        return array(
            'Oppervlakte' => $this->mcp3Project->getIntByPath('//project:Details/project:Bouwgrond/project:Oppervlakte'),
            'Ligging'     => $this->mcp3Project->getStringByPath('//project:Details/project:Bouwgrond/project:Ligging')
        );
    }

    /**
    * @desc Get Parkeergelegenheid meta data
    *
    * @param void
    * @return array
    */
    protected function getParkeergelegenheidMetaData()
    {
        return array(
            'Oppervlakte'	=> $this->mcp3Project->getIntByPath('//project:Details/project:Parkeergelegenheid/project:Oppervlakte'),
            'Breedte'		=> $this->mcp3Project->getIntByPath('//project:Details/project:Parkeergelegenheid/project:Breedte'),
            'Lengte'		=> $this->mcp3Project->getIntByPath('//project:Details/project:Parkeergelegenheid/project:Lengte'),
            'Isolatie'		=> $this->mcp3Project->getStringByPath('//project:Details/project:Parkeergelegenheid/project:Isolatievormen/project:Isolatie'),
            'Voorzieningen' => $this->mcp3Project->getStringByPath('//project:Details/project:Parkeergelegenheid/project:Voorzieningen/project:Voorziening'),
        );
    }

    /**
     * Get onderstuk meta data
     *
     * @param void
     * @return array
     */
    protected function getOnderstukMetaData()
    {
        return array(
            'Oppervlakte'	=> $this->mcp3Project->getIntByPath('//project:Details/project:Onderstuk/project:Oppervlakte'),
            'Breedte'		=> $this->mcp3Project->getIntByPath('//project:Details/project:Onderstuk/project:Breedte'),
            'Lengte'		=> $this->mcp3Project->getIntByPath('//project:Details/project:Onderstuk/project:Lengte'),
            'Isolatie'		=> $this->mcp3Project->getStringByPath('//project:Details/project:Onderstuk/project:Isolatievormen/project:Isolatie'),
            'Voorzieningen' => $this->mcp3Project->getStringByPath('//project:Details/project:Onderstuk/project:Voorzieningen/project:Voorziening'),
        );
	}

    /**
     * Get opslagruimte meta data
     *
     * @param void
     * @return array
     */
    protected function getOpslagruimteMetaData()
    {
        return array(
            'Oppervlakte'	=> $this->mcp3Project->getIntByPath('//project:Details/project:Opslagruimte/project:Oppervlakte'),
			'Breedte'		=> $this->mcp3Project->getIntByPath('//project:Details/project:Opslagruimte/project:Breedte'),
			'Lengte'		=> $this->mcp3Project->getIntByPath('//project:Details/project:Opslagruimte/project:Lengte'),
			'Isolatie'		=> $this->mcp3Project->getStringByPath('//project:Details/project:Opslagruimte/project:Isolatievormen/project:Isolatie'),
			'Voorzieningen' => $this->mcp3Project->getStringByPath('//project:Details/project:Opslagruimte/project:Voorzieningen/project:Voorziening'),
        );
    }

    /**
    * @desc Get Berging meta data
    *
    * @param void
    * @return array
    */
    protected function getBergingMetaData()
    {
        return array(
            'Oppervlakte' => $this->mcp3Project->getIntByPath('//project:Details/project:Berging/project:Oppervlakte')
        );
    }

    /**
    * @desc Get Standplaats meta data
    *
    * @param void
    * @return array
    */
    protected function getStandplaatsMetaData()
    {
        return array(
            'Oppervlakte' => $this->mcp3Project->getIntByPath('//project:Details/project:Standplaats/project:Oppervlakte')
        );
    }

    /**
    * @desc Get Ligplaats meta data
    *
    * @param void
    * @return array
    */
    protected function getLigplaatsMetaData()
    {
        return array(
            'Oppervlakte' => $this->mcp3Project->getIntByPath('//project:Details/project:Ligplaats/project:Oppervlakte')
        );
    }
}