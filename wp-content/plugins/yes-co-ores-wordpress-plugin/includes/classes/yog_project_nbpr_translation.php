<?php
  /**
  * @desc YogProjectNBprTranslation
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogProjectNBprTranslation extends YogProjectTranslationAbstract
  {
    /**
    * @desc Get post type
    *
    * @param void
    * @return string
    */
    public function getPostType()
    {
      return YOG_POST_TYPE_NBPR;
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
        'Land'                        => $this->mcp3Project->getStringByPath('//project:General/project:Location/project:Country'),
        'Provincie'                   => $this->mcp3Project->getStringByPath('//project:General/project:Location/project:State'),
        'Gemeente'                    => $this->mcp3Project->getStringByPath('//project:General/project:Location/project:Municipality'),
        'Plaats'                      => $this->mcp3Project->getStringByPath('//project:General/project:Location/project:City'),
        'Wijk'                        => $this->mcp3Project->getStringByPath('//project:General/project:Location/project:Area'),
        'Buurt'                       => $this->mcp3Project->getStringByPath('//project:General/project:Location/project:Neighbourhood'),
        'Straat'                      => $this->mcp3Project->getStringByPath('//project:General/project:Location/project:Street'),
        'Postcode'                    => $this->mcp3Project->getStringByPath('//project:General/project:Location/project:Zipcode'),
        'Longitude'                   => $this->mcp3Project->getStringByPath('//project:General/project:GeoCode/project:Longitude'),
        'Latitude'                    => $this->mcp3Project->getStringByPath('//project:General/project:GeoCode/project:Latitude'),
        'KoopAanneemSomMin'           => $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:KoopAanneemSom/project:Min'),
        'KoopAanneemSomMax'           => $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:KoopAanneemSom/project:Max'),
        'HuurPrijsMin'                => $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Min'),
        'HuurPrijsMax'                => $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Max'),
        'HuurPrijsConditie'           => $this->translatePriceCondition($this->mcp3Project->getStringByPath('//project:Details/project:Huur/project:PrijsConditie')),
        'PerceelOppervlakteMin'       => $this->mcp3Project->getIntByPath('//project:Details/project:PerceelOppervlakte/project:Min'),
        'PerceelOppervlakteMax'       => $this->mcp3Project->getIntByPath('//project:Details/project:PerceelOppervlakte/project:Max'),
        'WoonOppervlakteMin'          => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:WoonOppervlakte/project:Min'),
        'WoonOppervlakteMax'          => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:WoonOppervlakte/project:Max'),
        'InhoudMin'                   => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Inhoud/project:Min'),
        'InhoudMax'                   => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Inhoud/project:Max'),
        'Fase'                        => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:Fase'),
        'Status'                      => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:Status'),
        'ProjectSoort'                => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:Projectsoort'),
        'AantalEenheden'              => $this->mcp3Project->getIntByPath('//project:Details/project:Ontwikkeling/project:AantalEenheden'),
        'StartBouw'                   => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:StartBouw'),
        'DatumStartBouw'              => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:DatumStartBouw'),
        'Oplevering'                  => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:Oplevering'),
        'DatumOplevering'             => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:DatumOplevering'),
				'DatumStartVerkoop'           => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:DatumStartVerkoop')
      );

			// ApiKey
			if ($this->mcp3Project->hasNodeByXpath('/project:Project/@apiId'))
				$data['ApiKey']           = $this->mcp3Project->getStringByPath('/project:Project/@apiId'); // 3mcp 1.6
			else
				$data['ApiKey']           = $this->mcp3Project->getStringByPath('/project:Project/project:YProjectNumber'); // 3mcp 1.4

      // Housenumber
      if ($this->mcp3Project->hasLocation())
      {
        $address                  = $this->mcp3Project->getLocation();
        $data['Huisnummer']       = $address->getHouseNumber() . $address->getHouseNumberAddition();
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
      $price = $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:KoopAanneemSom/project:Min');
      if (!empty($price))
        return $price;

      $price = $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Min');
      if (!empty($price))
        return $price;

      $price = $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:KoopAanneemSom/project:Max');
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
			$sort = '';

      if ($this->mcp3Project->hasLocation())
      {
        $location	= $this->mcp3Project->getLocation();
				$city			= $location->getCity();
				$sort			= $city . ' ' . str_replace($city, '', $this->determineTitle());
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
	    $categories = array('nieuwbouw-projecten', 'nieuwbouw-project');

      // Verkoop
      $min = $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:KoopAanneemSom/project:Min');
      $max = $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:KoopAanneemSom/project:Max');
      if (!empty($min) || !empty($max))
        $categories[] = 'nieuwbouw-project-verkoop';

      // Verhuur
      $min = $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Min');
      $max = $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Max');
      if (!empty($min) || !empty($max))
        $categories[] = 'nieuwbouw-project-verhuur';

		  // State
      if (in_array($this->determineState(), array('verkocht', 'verhuurd', 'verkocht/verhuurd')))
			  $categories[] = 'nieuwbouw-project-verkochtverhuurd';

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
	    $state = $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:Status');

      return $state;
    }
  }