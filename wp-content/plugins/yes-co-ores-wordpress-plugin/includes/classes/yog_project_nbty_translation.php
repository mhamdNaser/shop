<?php
  /**
  * @desc YogProjectNBtyTranslation
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogProjectNBtyTranslation extends YogProjectTranslationAbstract
  {
    /**
    * @desc Get post type
    *
    * @param void
    * @return string
    */
    public function getPostType()
    {
      return YOG_POST_TYPE_NBTY;
    }

    /**
    * @desc Get the title
    *
    * @param void
    * @return string
    */
    public function determineTitle()
    {
      $title    = $this->mcp3Project->getName();

      if (empty($title))
        $title = $this->mcp3Project->getStringByPath('//project:General/project:Name');

      return $title;
    }

    /**
    * @desc Check if a parent uuid is set
    *
    * @param void
    * @return bool
    */
    public function hasParentUuid()
    {
      return true;
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
      return $this->mcp3Project->getNBprUuid();
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
      $data = array(
        'uuid'                        => $this->mcp3Project->getStringByPath('/project:Project/@uuid'),
        'dlm'                         => $this->translateDate($this->mcp3Link->getDlm()),
        'scenario'                    => $this->mcp3Project->getScenario(),
				'Source'											=> $this->mcp3Project->getStringByPath('/project:Project/@source'),
        'Status'                      => $this->determineState(),
        'Naam'                        => $this->mcp3Project->getStringByPath('//project:General/project:Name'),
				'KoopPrijsMin'                => $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:PrijsMin'),
				'KoopPrijsMax'                => $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:PrijsMax'),
				'HuurPrijsMin'                => $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Min'),
				'HuurPrijsMax'                => $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Max'),
				'HuurPrijsConditie'           => $this->translatePriceCondition($this->mcp3Project->getStringByPath('//project:Details/project:Huur/project:PrijsConditie')),
				'PerceelOppervlakteMin'       => $this->mcp3Project->getIntByPath('//project:Details/project:PerceelOppervlakte/project:Min'),
				'PerceelOppervlakteMax'       => $this->mcp3Project->getIntByPath('//project:Details/project:PerceelOppervlakte/project:Max'),
				'AantalEenheden'              => $this->mcp3Project->getIntByPath('//project:Details/project:Ontwikkeling/project:AantalEenheden'),
				'AantalVrijeEenheden'         => $this->mcp3Project->getIntByPath('//project:Details/project:Ontwikkeling/project:AantalVrijeEenheden'),
				'StartBouw'                   => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:StartBouw'),
				'DatumStartBouw'              => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:DatumStartBouw'),
				'Oplevering'                  => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:Oplevering'),
				'DatumOplevering'             => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:DatumOplevering')
			);

      // Type
		  $type                     = ($this->mcp3Project->hasSubType()) ? $this->mcp3Project->getSubType() : $this->mcp3Project->getType();
	    $data['Type']             = $type;

			// ApiKey
			if ($this->mcp3Project->hasNodeByXpath('/project:Project/@apiId'))
				$data['ApiKey']           = $this->mcp3Project->getStringByPath('/project:Project/@apiId'); // 3mcp 1.6
			else
				$data['ApiKey']           = $this->mcp3Project->getStringByPath('/project:Project/project:YProjectNumber'); // 3mcp 1.4

      switch (strtolower($this->mcp3Project->getType()))
      {
				// Bouwgrond
				case 'bouwgrond':

					$data = array_merge($data, array(
						'HuidigGebruik'               => $this->mcp3Project->getStringByPath('//project:Details/project:Bouwgrond/project:Bestemming/project:HuidigGebruik'),
						'HuidigeBestemming'           => $this->mcp3Project->getStringByPath('//project:Details/project:Bouwgrond/project:Bestemming/project:HuidigeBestemming'),
						'PermanenteBewoning'          => $this->translateBool($this->mcp3Project->getBoolByPath('//project:Details/project:Bouwgrond/project:Bestemming/project:PermanenteBewoning')),
						'Recreatiewoning'             => $this->translateBool($this->mcp3Project->getBoolByPath('//project:Details/project:Bouwgrond/project:Bestemming/project:Recreatiewoning')),
						'InPark'											=> $this->translateBool($this->mcp3Project->getBoolByPath('//project:Details/project:Bouwgrond/project:Bestemming/project:InPark')),
						'Ligging'											=> $this->mcp3Project->getStringByPath('//project:Details/project:Bouwgrond/project:Ligging')
					));

					break;
				// Woonruimte
				default:

					$data = array_merge($data, array(
						'WoonOppervlakteMin'          => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:WoonOppervlakte/project:Min'),
						'WoonOppervlakteMax'          => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:WoonOppervlakte/project:Max'),
						'WoonkamerOppervlakteMin'     => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:WoonkamerOppervlakte/project:Min'),
						'WoonkamerOppervlakteMax'     => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:WoonkamerOppervlakte/project:Max'),
						'InhoudMin'                   => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Inhoud/project:Min'),
						'InhoudMax'                   => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Inhoud/project:Max'),
						'PermanenteBewoning'          => $this->translateBool($this->mcp3Project->getBoolByPath('//project:Details/project:Woonruimte/project:Bestemming/project:PermanenteBewoning')),
						'Recreatiewoning'             => $this->translateBool($this->mcp3Project->getBoolByPath('//project:Details/project:Woonruimte/project:Bestemming/project:Recreatiewoning')),
						'Aantalkamers'                => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Verdieping/project:AantalKamers'),
						'GarageType'                  => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Garage/project:Type'),
						'GarageCapaciteit'            => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Garage/project:Capaciteit'),
						'TuinType'                    => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Tuin/project:Type'),
						'TuinTotaleOppervlakte'       => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Tuin/project:TotaleOppervlakte'),
						'HoofdTuinType'               => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Tuin/project:HoofdtuinType'),
						'HoofdTuinDiepte'             => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Tuin/project:Diepte'),
						'HoofdTuinBreedte'            => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Tuin/project:Breedte'),
						'HoofdTuinTotaleOppervlakte'  => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Tuin/project:Oppervlakte'),
						'TuinLigging'                 => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Tuin/project:Ligging'),
						'HoofdTuinAchterom'           => $this->translateBool($this->mcp3Project->getBoolByPath('//project:Details/project:Woonruimte/project:Tuin/project:Achterom')),
						'BergingType'                 => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Soort'),
						'Verwarming'                  => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Installatie/project:Verwarming/project:Type'),
						'WarmWater'                   => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Installatie/project:WarmWater/project:Type'),
						'CvKetel'                     => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Installatie/project:CvKetel/project:Type'),
						'CvKetelBouwjaar'             => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Installatie/project:CvKetel/project:Bouwjaar'),
						'CvKetelBrandstof'            => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Installatie/project:CvKetel/project:GasOlie'),
						'CvKetelEigendom'             => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Installatie/project:CvKetel/project:Eigendom'),
						'CvCombiketel'                => $this->translateBool($this->mcp3Project->getBoolByPath('//project:Details/project:Woonruimte/project:Installatie/project:CvKetel/project:Combiketel')),
						'Dak'                         => $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Diversen/project:Dak')
					));

					// Garage voorzieningen
					if ($this->mcp3Project->hasNodeByXpath('//project:Details/project:Woonruimte/project:Garage/project:Voorzieningen/project:Voorziening/@naam'))
						$data['GarageVoorzieningen'] = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Garage/project:Voorzieningen/project:Voorziening/@naam'); // 3mcp 1.4
					else
						$data['GarageVoorzieningen'] = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Garage/project:Voorzieningen/project:Voorziening'); // 3mcp 1.6

					// Garage Isolatievormen
					if ($this->mcp3Project->hasNodeByXpath('//project:Details/project:Woonruimte/project:Garage/project:Isolatievormen/project:Isolatievorm/@naam'))
						$data['GarageIsolatievormen'] = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Garage/project:Isolatievormen/project:Isolatievorm/@naam'); // 3mcp 1.4
					else
						$data['GarageIsolatievormen'] = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Garage/project:Isolatievormen/project:Isolatievorm'); // 3mcp 1.6

					// Schuur/berging voorzieningen
					if ($this->mcp3Project->hasNodeByXpath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Voorzieningen/project:Voorziening/@naam'))
						$data['BergingVoorzieningen'] = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Voorzieningen/project:Voorziening/@naam'); // 3mcp 1.4
					else
						$data['BergingVoorzieningen'] = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Voorzieningen/project:Voorziening'); // 3mcp 1.6

					// Schuur/berging Isolatievormen
					if ($this->mcp3Project->hasNodeByXpath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Isolatievormen/project:Isolatievorm/@naam'))
						$data['BergingIsolatievormen'] = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Isolatievormen/project:Isolatievorm/@naam'); // 3mcp 1.4
					else
						$data['BergingIsolatievormen'] = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:SchuurBerging/project:Isolatievormen/project:Isolatievorm'); // 3mcp 1.6

					// Dak materiaal
					if ($this->mcp3Project->hasNodeByXpath('//project:Details/project:Woonruimte/project:Diversen/project:DakMaterialen/project:DakMateriaal/@naam'))
						$data['DakMaterialen'] = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Diversen/project:DakMaterialen/project:DakMateriaal/@naam'); // 3mcp 1.4
					else
						$data['DakMaterialen'] = $this->mcp3Project->getStringByPath('//project:Details/project:Woonruimte/project:Diversen/project:DakMaterialen/project:DakMateriaal'); // 3mcp 1.6

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

					break;
			}

			// Also add text meta data?
			$textData	= $this->getTextMetaData();
			if (!empty($textData))
				$data = array_merge($data, $textData);

      // Also add generic meta data
      $genericMetaData = $this->getGenericMetaData();
      if (!empty($genericMetaData))
        $data = array_merge($data, $genericMetaData);

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
      $price = $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:PrijsMin');
      if (!empty($price))
        return $price;

      $price = $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Min');
      if (!empty($price))
        return $price;

      $price = $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:PrijsMax');
      if (!empty($price))
        return $price;

      $price = $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Max');
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
			return 'PARENT ' . $this->determineTitle();
		}

    /**
    * @desc Get the categories to link project to
    *
    * @param void
    * @return array
    */
    public function getCategories()
    {
	    $categories = array('nieuwbouw-projecten', 'nieuwbouw-type');

      // Verkoop
      $min = $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:PrijsMin');
      $max = $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:PrijsMax');
      if (!empty($min) || !empty($max))
        $categories[] = 'nieuwbouw-type-verkoop';

      // Verhuur
      $min = $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Min');
      $max = $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Max');
      if (!empty($min) || !empty($max))
        $categories[] = 'nieuwbouw-type-verhuur';

      // Allow the theme to add custom categories
      $this->getThemeCategories($this->mcp3Project, $categories);

      return $categories;
    }

    /**
    * @desc Determine project state
    *
    * @param void
    * @return string
    */
    private function determineState()
    {
	    $state = $this->mcp3Project->getStringByPath('//project:General/project:ObjectStatus');

      return $state;
    }
  }