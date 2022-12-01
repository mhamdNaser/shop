<?php
  /**
  * @desc YogProjectBOprTranslation
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogProjectBOprTranslation extends YogProjectTranslationAbstract
  {
    /**
    * @desc Get post type
    *
    * @param void
    * @return string
    */
    public function getPostType()
    {
      return YOG_POST_TYPE_BOPR;
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
        'BouwType'                    => $this->mcp3Project->getStringByPath('//project:General/project:BouwType'),
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
        'NummerreeksStart'            => $this->mcp3Project->getStringByPath('//project:General/project:Nummerreeks/project:Start'),
        'NummerreeksEind'             => $this->mcp3Project->getStringByPath('//project:General/project:Nummerreeks/project:End'),
        'KoopPrijsMin'                => $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:Prijs/project:Min'),
        'KoopPrijsMax'                => $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:Prijs/project:Max'),
        'HuurPrijsMin'                => $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Min'),
        'HuurPrijsMax'                => $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Max'),
        'DatumStartBouw'              => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:DatumStartBouw'),
        'DatumOplevering'             => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:DatumOplevering'),
				'DatumStartVerkoop'           => $this->mcp3Project->getStringByPath('//project:Details/project:Ontwikkeling/project:DatumStartVerkoop'),
        'GebouwNaam'                  => $this->mcp3Project->getStringByPath('//project:Gebouw/project:Naam'),
        'AantalVerdiepingen'          => $this->mcp3Project->getIntByPath('//project:Gebouw/project:Verdiepingen/project:Aantal')
      );

			// ApiKey
			if ($this->mcp3Project->hasNodeByXpath('/project:Project/@apiId'))
				$data['ApiKey']           = $this->mcp3Project->getStringByPath('/project:Project/@apiId');

      // Housenumber
      if ($this->mcp3Project->hasAddress())
      {
        $address										= $this->mcp3Project->getAddress();
				$addition										= $address->getHouseNumberAddition();

        $data['Huisnummer']					= $address->getHouseNumber() . $addition;
				$data['HuisnummerNumeriek']	= $address->getHouseNumber();

				if (!empty($addition))
					$data['HuisnummerToevoeging'] = $addition;
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
      $price = $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:Prijs/project:Min');
      if (!empty($price))
        return $price;

      $price = $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Min');
      if (!empty($price))
        return $price;

      $price = $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:Prijs/project:Max');
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
      $categories = ['bog-project'];

      // Verkoop
      $koopMin = $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:Prijs/project:Min');
      $koopMax = $this->mcp3Project->getIntByPath('//project:Details/project:Koop/project:Prijs/project:Max');
      if (!empty($koopMin) || !empty($koopMax))
        $categories[] = 'bog-project-verkoop';

      // Verhuur
      $huurMin = $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Min');
      $huurMax = $this->mcp3Project->getIntByPath('//project:Details/project:Huur/project:Prijs/project:Max');
      if (!empty($huurMin) || !empty($huurMax))
        $categories[] = 'bog-project-verhuur';

      // Sold?
      $state = strtolower($this->determineState());
      if (in_array($state, ['verkocht onder voorbehoud', 'verhuurd onder voorbehoud']))
        $categories[] = 'bog-project-verkochtverhuurd';

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
      if (!empty($state))
        return ucfirst($state);

      return 'Beschikbaar';
    }
  }