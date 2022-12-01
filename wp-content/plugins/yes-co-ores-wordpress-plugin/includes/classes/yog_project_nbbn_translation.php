<?php
  /**
  * @desc YogProjectNBbnTranslation
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogProjectNBBNTranslation extends YogProjectTranslationAbstract
  {
    /**
    * @desc Get post type
    *
    * @param void
    * @return string
    */
    public function getPostType()
    {
      return YOG_POST_TYPE_NBBN;
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
      return $this->mcp3Project->getNBtyUuid();
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
        'Land'                        => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:Country'),
        'Provincie'                   => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:State'),
        'Gemeente'                    => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:Municipality'),
        'Plaats'                      => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:City'),
        'Wijk'                        => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:Area'),
        'Buurt'                       => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:Neighbourhood'),
        'Straat'                      => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:Street'),
        'Postcode'                    => $this->mcp3Project->getStringByPath('//project:General/project:Address/project:Zipcode'),
        'Longitude'                   => $this->mcp3Project->getStringByPath('//project:General/project:GeoCode/project:Longitude'),
        'Latitude'                    => $this->mcp3Project->getStringByPath('//project:General/project:GeoCode/project:Latitude'),
        'Bouwnummer'                  => $this->mcp3Project->getIntByPath('//project:General/project:Bouwnummer'),
        'WoonOppervlakte'             => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:WoonOppervlakte'),
        'Inhoud'                      => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:Inhoud'),
        'PerceelOppervlakte'          => $this->mcp3Project->getIntByPath('//project:Details/project:PerceelOppervlakte'),
        'AantalKamers'                => $this->mcp3Project->getIntByPath('//project:Details/project:Woonruimte/project:AantalKamers'),
        'GrondPrijs'                  => $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:GrondPrijs'),
        'AanneemSom'                  => $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:AanneemSom'),
        'KoopAanneemSom'              => $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:KoopaanneemSom'),
      );

			// ApiKey
			if ($this->mcp3Project->hasNodeByXpath('/project:Project/@apiId'))
				$data['ApiKey']           = $this->mcp3Project->getStringByPath('/project:Project/@apiId'); // 3mcp 1.6
			else
				$data['ApiKey']           = $this->mcp3Project->getStringByPath('/project:Project/project:YProjectNumber'); // 3mcp 1.4

      // Housenumber
      if ($this->mcp3Project->hasAddress())
      {
        $address                  = $this->mcp3Project->getAddress();
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
				$sort			= $address->getCity() . ' ' . $address->getStreet() . ' ' . $address->getHouseNumber() . $address->getHouseNumberAddition();
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
	    $categories = array('nieuwbouw-projecten', 'nieuwbouw-bouwnummer');

		  // State
      if (in_array($this->determineState(), array('verkocht', 'verhuurd')))
			  $categories[] = 'nieuwbouw-bouwnummer-verkochtverhuurd';

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