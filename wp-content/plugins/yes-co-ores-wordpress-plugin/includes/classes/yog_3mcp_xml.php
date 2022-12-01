<?php
  /**
  * @desc Yog3McpXmlAbstract
  * @author Kees Brandenburg - Yes-co Nederland
  */
  abstract class Yog3McpXmlAbstract
  {
    protected $xml;
    protected $ns;

    /**
    * @desc Constructor
    *
    * @param YogSimpleXMLElement $xml
    * @param string $namespace
    * @return Yog3McpXmlAbstract
    */
    public function __construct(YogSimpleXMLElement $xml, $namespace)
    {
      $this->xml  = $xml;
      $this->ns   = $namespace;
    }

    /**
    * @desc Get the xml
    *
    * @param void
    * @return YogSimpleXMLElement
    */
    public function getXml()
    {
      return $this->xml;
    }

    /**
    * Get array with nodes by xpath query
    *
    * @param string $xpath
    * @@return array
    */
    public function getNodesByXpath($xpath)
    {
      $nodes  = $this->xml->xpath($xpath);
      if ($nodes === false)
        $nodes = array();

      return $nodes;
    }

		/**
		 * Check if node with specified xpath exists
		 * @param string $xpath
		 * @return bool
		 */
		public function hasNodeByXpath($xpath)
		{
      $nodes  = $this->xml->xpath($xpath);

      return ($nodes !== false && count($nodes) > 0);
		}

    /**
    * @desc Get string by xpath
    *
    * @param string $xpath
    * @return string
    */
    public function getStringByPath($xpath)
    {
      $nodes  = $this->xml->xpath($xpath);
      $values = array();

      if ($nodes !== false && count($nodes) > 0)
      {
        foreach ($nodes as $node)
        {
          $values[] = (string) $node;
        }
      }

      if (empty($values))
        return '';
      else
        return implode(', ', $values);
    }

    /**
    * @desc Get date/time by xpath
    *
    * @param string $xpath
		* @param string|null $targetTimeZone
    * @return string
    */
    public function getDateTimeByPath($xpath, $targetTimeZone = null, $format = 'c')
    {
      $nodes  = $this->xml->xpath($xpath);

      if ($nodes !== false && count($nodes) > 0)
      {
				try
				{
					$value = new \DateTime((string) array_shift($nodes));

					if (!is_null($targetTimeZone))
						$value->setTimezone(new \DateTimeZone($targetTimeZone));

					return $value->format($format);
				}
				catch (\Exception $e) {}
      }

      return '';
    }

    /**
    * @desc Get int by xpath
    *
    * @param string $xpath
    * @return mixed
    */
    public function getIntByPath($xpath)
    {
      $nodes  = $this->xml->xpath($xpath);
      $value  = 0;

      if ($nodes !== false && count($nodes) > 0)
      {
        foreach ($nodes as $node)
        {
          $value += (int) $node;
        }
      }

      if (empty($value))
        return '';
      else
        return $value;
    }

    /**
    * @desc Get bool by path
    *
    * @param string $xpath
    * @return bool
    */
    public function getBoolByPath($xpath)
    {
      $nodes  = $this->xml->xpath($xpath);

      if ($nodes !== false && count($nodes) > 0)
      {
        $value  = (string) array_shift($nodes);
        return ($value == 'true');
      }

      return false;
    }
  }

  /**
  * @desc Yog3McpXmlProjectAbstract
  * @author Kees Brandenburg - Yes-co Nederland
  */
  abstract class Yog3McpXmlProjectAbstract extends Yog3McpXmlAbstract
  {
    /**
    * @desc Create Yog3McpXmlProjectAbstract
    *
    * @param YogSimpleXMLElement $xml
    * @param string $namespace
    * @return Yog3McpXmlProjectAbstract
    */
    static public function create(YogSimpleXMLElement $xml, $namespace)
    {
      $xml->registerXPathNamespace('project', $namespace);

      switch ((string) $xml->Scenario)
      {
        case 'BBvk':
        case 'BBvh':
        case 'NBvk':
        case 'NBvh':
        case 'LIvk':
          return new Yog3McpXmlProjectWonen($xml, $namespace);
          break;
        case 'BOvk':
        case 'BOvh':
          return new Yog3McpXmlProjectBog($xml, $namespace);
          break;
        case 'NBpr':
          return new Yog3McpXmlProjectNBpr($xml, $namespace);
          break;
        case 'NBty':
          return new Yog3McpXmlProjectNBty($xml, $namespace);
          break;
        case 'NBbn':
          return new Yog3McpXmlProjectNBbn($xml, $namespace);
          break;
        case 'BBpr':
          return new Yog3McpXmlProjectBBpr($xml, $namespace);
          break;
        case 'BBty':
          return new Yog3McpXmlProjectBBty($xml, $namespace);
          break;
        case 'BOpr':
          return new Yog3McpXmlProjectBOpr($xml, $namespace);
          break;
        default:
          throw new YogException(__METHOD__ . '; Unsupported scenario', YogException::GLOBAL_ERROR);
          break;
      }
    }

    /**
    * @desc Get the type
    *
    * @param void
    * @return string
    */
    public function getType()
    {
      return (string) $this->xml->Type;
    }

    /**
    * @desc Get the subtype
    *
    * @param void
    * @return string
    */
    public function getSubType()
    {
      return (string) $this->xml->SubType;
    }

    /**
    * @desc Check if subtype is set
    *
    * @param void
    * @return bool
    */
    public function hasSubType()
    {
      return isset($this->xml->SubType);
    }

    /**
    * @desc Get the name
    *
    * @param void
    * @return string
    */
    public function getName()
    {
      return (string) $this->xml->Name;
    }

    /**
    * @desc Get general node
    *
    * @param void
    * @return YogSimpleXMLElement
    */
    public function getGeneralNode()
    {
      $scenario = $this->getScenario();
      return $this->xml->$scenario->General;
    }

    /**
    * @desc Get scenario
    *
    * @param void
    * @return string
    */
    public function getScenario()
    {
      return (string) $this->xml->Scenario;
    }

    /**
    * @desc Get the texts
    *
    * @param void
    * @return array
    */
    public function getTexts()
    {
      $texts    = array();
      $scenario = $this->getScenario();
      foreach ($this->xml->$scenario->Text as $text)
      {
        $texts[(string) $text->Type] = (string) $text->Content;
      }

      return $texts;
    }

    /**
    * @desc Get relation references
    *
    * @param void
    * @return array
    */
    public function getRelationReferences()
    {
      $references = array();
      foreach ($this->xml->Relation as $reference)
      {
        $references[] = new Yog3McpXmlRelationReference($reference, $this->ns);
      }

      return $references;
    }

    /**
    * @desc Get the images
    *
    * @param void
    * @return array
    */
    public function getMediaImages()
    {
      $images = array();
      $nodes  = $this->xml->xpath('//project:Media[project:Image]');

      foreach ($nodes as $node)
      {
        $node->registerXPathNamespace('project', $this->ns);
        $images[] = new Yog3McpXmlMediaImage($node, $this->ns);
      }

      return $images;
    }

    /**
    * @desc Get the videos
    *
    * @param void
    * @return array
    */
    public function getMediaVideos()
    {
      $videos = array();
      $nodes  = $this->xml->xpath('//project:Media[project:Video]');

      foreach ($nodes as $node)
      {
        $node->registerXPathNamespace('project', $this->ns);
        $videos[] = new Yog3McpXmlMediaVideo($node, $this->ns);
      }

      return $videos;
    }

    /**
    * @desc Get the documents
    *
    * @param void
    * @return array
    */
    public function getMediaDocuments()
    {
      $documents  = array();
      $nodes      = $this->xml->xpath('//project:Media[project:Document]');

      foreach ($nodes as $node)
      {
        $node->registerXPathNamespace('project', $this->ns);
        $documents[] = new Yog3McpXmlMediaDocument($node, $this->ns);
      }

      return $documents;
    }

    /**
    * @desc Get the links
    *
    * @param void
    * @return array
    */
    public function getLinks()
    {
      $links  = array();
      $nodes  = $this->xml->xpath('//project:Link[project:Url]');

      foreach ($nodes as $node)
      {
        $node->registerXPathNamespace('project', $this->ns);
        $links[] = new Yog3McpXmlLink($node, $this->ns);
      }

      return $links;
    }

    /**
     * Get documents from dossier
     *
     * @param void
     * @return array
     */
    public function getDossierItems()
    {
      $scenario   = (string) $this->xml->Scenario;
      $documents  = array();
      $nodes      = $this->xml->xpath('//project:' . $scenario . '/project:Document');

      foreach ($nodes as $node)
      {
        $node->registerXPathNamespace('project', $this->ns);
        $documents[] = new Yog3McpXmlDossier($node, $this->ns);
      }

      return $documents;
    }

    /**
    * @desc Get project tags
    *
    * @param void
    * @return array
    */
    public function getTags()
    {
      $references = array();
      if (isset($this->xml->Tags))
      {
        foreach ($this->xml->Tags->Tag as $tag)
        {
          $references[] = (string) $tag;
        }
      }

      return $references;
    }
  }

  /**
  * @desc Yog3McpProjectWonen
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlProjectWonen extends Yog3McpXmlProjectAbstract
  {
    /**
    * @desc Get the address
    *
    * @param void
    * @return Yog3McpXmlAddress
    */
    public function getAddress()
    {
      $nodes = $this->xml->xpath('//project:General/project:Address');
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No address found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      $node->registerXPathNamespace('project', $this->ns);

      return new Yog3McpXmlAddress($node, $this->ns);
    }

    /**
    * @desc Check if address is set
    *
    * @param void
    * @return bool
    */
    public function hasAddress()
    {
      $nodes = $this->xml->xpath('//project:General/project:Address');
      return ($nodes !== false && count($nodes) > 0);
    }

    /**
    * @desc Check if project has a link to a parent object (NBty or BBty)
    *
    * @param void
    * @return bool
    */
    public function hasParentUuid()
    {
      $parentScenario = in_array($this->getScenario(), array('BBvk', 'BBvh', 'LIvk')) ? 'BBty' : 'NBty';

      $nodes = $this->xml->xpath("//project:Project[project:Scenario = '" . $parentScenario . "']/@uuid");
      return !($nodes === false || count($nodes) == 0);
    }

    /**
    * @desc Get the parent object uuid (NBty or BBty)
    *
    * @param void
    * @return string
    */
    public function getParentUuid()
    {
      $parentScenario = in_array($this->getScenario(), array('BBvk', 'BBvh', 'LIvk')) ? 'BBty' : 'NBty';

      $nodes = $this->xml->xpath("//project:Project[project:Scenario = '" . $parentScenario . "']/@uuid");
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No parent link found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      return (string) $node;
    }

    /**
    * @desc Get the number of floors
    *
    * @param void
    * @return int
    */
    public function getNumFloors()
    {
      $documents  = array();
      $nodes      = $this->xml->xpath('//project:Details/project:Woonruimte/project:Verdieping');

			if (is_array($nodes))
				return count($nodes);
			else
				return 1;
    }
  }

  /**
  * @desc Yog3McpXmlProjectBOpr
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlProjectBOpr extends Yog3McpXmlProjectAbstract
  {
    /**
    * @desc Get the address
    *
    * @param void
    * @return Yog3McpXmlAddress
    */
    public function getAddress()
    {
      $nodes = $this->xml->xpath('//project:General/project:Address');
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No address found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      $node->registerXPathNamespace('project', $this->ns);

      return new Yog3McpXmlAddress($node, $this->ns);
    }

    /**
    * @desc Check if address is set
    *
    * @param void
    * @return bool
    */
    public function hasAddress()
    {
      $nodes = $this->xml->xpath('//project:General/project:Address');
      return ($nodes !== false && count($nodes) > 0);
    }
  }

  /**
  * @desc Yog3McpXmlProjectBog
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlProjectBog extends Yog3McpXmlProjectAbstract
  {
    /**
    * @desc Get the address
    *
    * @param void
    * @return Yog3McpXmlAddress
    */
    public function getAddress()
    {
      $nodes = $this->xml->xpath('//project:General/project:Address');
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No address found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      $node->registerXPathNamespace('project', $this->ns);

      return new Yog3McpXmlAddress($node, $this->ns);
    }

    /**
    * @desc Check if address is set
    *
    * @param void
    * @return bool
    */
    public function hasAddress()
    {
      $nodes = $this->xml->xpath('//project:General/project:Address');
      return ($nodes !== false && count($nodes) > 0);
    }

		/**
		 * Check if belegging is set
		 *
		 * @param void
		 * @return bool
		 */
		public function hasBelegging()
		{
      $nodes = $this->xml->xpath('//project:Details/project:Koop/project:Belegging');
      return ($nodes !== false && count($nodes) > 0);
		}

		/**
		 * Check if veiling is set
		 *
		 * @param void
		 * @return bool
		 */
		public function hasVeiling()
		{
      $nodes = $this->xml->xpath('//project:Details/project:Koop/project:Veiling');
      return ($nodes !== false && count($nodes) > 0);
		}

    /**
    * @desc Check if project has a link to a parent object (NBty or BBty)
    *
    * @param void
    * @return bool
    */
    public function hasParentUuid()
    {
      $nodes = $this->xml->xpath("//project:Project[project:Scenario = 'BOpr']/@uuid");
      return !($nodes === false || count($nodes) == 0);
    }

    /**
    * @desc Get the parent object uuid (NBty or BBty)
    *
    * @param void
    * @return string
    */
    public function getParentUuid()
    {
      $nodes = $this->xml->xpath("//project:Project[project:Scenario = 'BOpr']/@uuid");
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No parent link found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      return (string) $node;
    }
  }

  /**
  * @desc Yog3McpXmlProjectNBpr
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlProjectNBpr extends Yog3McpXmlProjectAbstract
  {
    /**
    * @desc Get the location
    *
    * @param void
    * @return Yog3McpXmlLocation
    */
    public function getLocation()
    {
      $nodes = $this->xml->xpath('//project:General/project:Location');
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No location found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      $node->registerXPathNamespace('project', $this->ns);

      return new Yog3McpXmlLocation($node, $this->ns);
    }

    /**
    * @desc Check if location is set
    *
    * @param void
    * @return bool
    */
    public function hasLocation()
    {
      $nodes = $this->xml->xpath('//project:General/project:Location');
      return ($nodes !== false && count($nodes) > 0);
    }
  }

  /**
  * @desc Yog3McpXmlProjectNBty
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlProjectNBty extends Yog3McpXmlProjectAbstract
  {
    /**
    * @desc Get the parent NBpr uuid
    *
    * @param void
    * @return string
    */
    public function getNBprUuid()
    {
      $nodes = $this->xml->xpath("//project:Project[project:Scenario = 'NBpr']/@uuid");
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No NBpr link found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      return (string) $node;
    }
  }

  /**
  * @desc Yog3McpXmlProjectNBbn
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlProjectNBbn extends Yog3McpXmlProjectAbstract
  {
    /**
    * @desc Get the parent NBty uuid
    *
    * @param void
    * @return string
    */
    public function getNBtyUuid()
    {
      $nodes = $this->xml->xpath("//project:Project[project:Scenario = 'NBty']/@uuid");
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No NBpr link found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      return (string) $node;
    }

    /**
    * @desc Get the address
    *
    * @param void
    * @return Yog3McpXmlAddress
    */
    public function getAddress()
    {
      $nodes = $this->xml->xpath('//project:General/project:Address');
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No address found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      $node->registerXPathNamespace('project', $this->ns);

      return new Yog3McpXmlAddress($node);
    }

    /**
    * @desc Check if address is set
    *
    * @param void
    * @return bool
    */
    public function hasAddress()
    {
      $nodes = $this->xml->xpath('//project:General/project:Address');
      return ($nodes !== false && count($nodes) > 0);
    }
  }

  /**
  * @desc Yog3McpXmlProjectBBpr
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlProjectBBpr extends Yog3McpXmlProjectAbstract
  {
    /**
    * @desc Get the location
    *
    * @param void
    * @return Yog3McpXmlLocation
    */
    public function getLocation()
    {
      $nodes = $this->xml->xpath('//project:General/project:Location');
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No location found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      $node->registerXPathNamespace('project', $this->ns);

      return new Yog3McpXmlLocation($node, $this->ns);
    }

    /**
    * @desc Check if location is set
    *
    * @param void
    * @return bool
    */
    public function hasLocation()
    {
      $nodes = $this->xml->xpath('//project:General/project:Location');
      return ($nodes !== false && count($nodes) > 0);
    }
  }

  /**
  * @desc Yog3McpXmlProjectBBty
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlProjectBBty extends Yog3McpXmlProjectAbstract
  {
    /**
    * @desc Get the parent BBpr uuid
    *
    * @param void
    * @return string
    */
    public function getBBprUuid()
    {
      $nodes = $this->xml->xpath("//project:Project[project:Scenario = 'BBpr']/@uuid");
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No NBpr link found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      return (string) $node;
    }
  }

  /**
  * @desc Yog3McpXmlAddress
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlAddress extends Yog3McpXmlAbstract
  {
    /**
    * @desc Get the country
    *
    * @param void
    * @return string
    */
    public function getCountry()
    {
      return (string) $this->xml->Country;
    }

    /**
    * @desc Check if the state is set
    *
    * @param void
    * @return bool
    */
    public function hasState()
    {
      return isset($this->xml->State);
    }

    /**
    * @desc Get the state
    *
    * @param void
    * @return string
    */
    public function getState()
    {
      if ($this->hasState())
        return (string) $this->xml->State;
    }

    /**
    * @desc Check if the municipality is set
    *
    * @param void
    * @return bool
    */
    public function hasMunicipality()
    {
      return isset($this->xml->Municipality);
    }

    /**
    * @desc Get the municipality
    *
    * @param void
    * @return string
    */
    public function getMunicipality()
    {
      if ($this->hasState())
        return (string) $this->xml->Municipality;
    }

    /**
    * @desc Get the city
    *
    * @param void
    * @return string
    */
    public function getCity()
    {
      return (string) $this->xml->City;
    }

    /**
    * @desc Check if the area is set
    *
    * @param void
    * @return bool
    */
    public function hasArea()
    {
      return isset($this->xml->Area);
    }

    /**
    * @desc Get the area
    *
    * @param void
    * @return string
    */
    public function getArea()
    {
      if ($this->hasArea())
        return (string) $this->xml->Area;
    }

    /**
    * @desc Check if the neighbourhood is set
    *
    * @param void
    * @return bool
    */
    public function hasNeighbourhood()
    {
      return isset($this->xml->Neighbourhood);
    }

    /**
    * @desc Get the neighbourhood
    *
    * @param void
    * @return string
    */
    public function getNeighbourhood()
    {
      if ($this->hasNeighbourhood())
        return (string) $this->xml->Neighbourhood;
    }

    /**
    * @desc Get the street
    *
    * @param void
    * @return string
    */
    public function getStreet()
    {
      return (string) $this->xml->Street;
    }

    /**
    * @desc Get the housenumner
    *
    * @param void
    * @return string
    */
    public function getHouseNumber()
    {
      return (int) $this->xml->Housenumber;
    }

    /**
    * @desc Get the housenumber addition
    *
    * @param void
    * @return string
    */
    public function getHouseNumberAddition()
    {
      if ($this->hasHouseNumberAddition())
        return (string) $this->xml->HousenumberAddition;
    }

    /**
    * @desc Check if housenumber addition is set
    *
    * @param void
    * @return bool
    */
    public function hasHouseNumberAddition()
    {
      return isset($this->xml->HousenumberAddition);
    }

    /**
    * @desc Get the zipcode addition
    *
    * @param void
    * @return string
    */
    public function getZipcode()
    {
      if ($this->hasZipcode())
        return (string) $this->xml->Zipcode;
    }

    /**
    * @desc Check if zipcode addition is set
    *
    * @param void
    * @return bool
    */
    public function hasZipcode()
    {
      return isset($this->xml->Zipcode);
    }
  }

  /**
  * @desc Yog3McpXmlLocation
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlLocation extends Yog3McpXmlAddress
  {

  }

  /**
  * @desc Yog3McpXmlMediaAbstract
  * @author Kees Brandenburg - Yes-co Nederland
  */
  abstract class Yog3McpXmlMediaAbstract extends Yog3McpXmlAbstract
  {
    /**
    * @desc Get the uuid
    *
    * @param void
    * @return string
    */
    public function getUuid()
    {
      return (string) $this->xml['uuid'];
    }
  }

  /**
  * @desc Yog3McpXmlMediaImage
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlMediaImage extends Yog3McpXmlMediaAbstract
  {
    /**
    * @desc Get the order
    *
    * @param void
    * @return int
    */
    public function getOrder()
    {
      return (int) $this->xml->Image['order'];
    }

    /**
    * @desc Get the title
    *
    * @param void
    * @return string
    */
    public function getTitle()
    {
       return (string) $this->xml->Image->Title;
    }

    /**
    * @desc Get the type
    *
    * @param void
    * @return string
    */
    public function getType()
    {
      if (isset($this->xml->Image->Type))
        return (string) $this->xml->Image->Type;

      return '';
    }
  }

  /**
  * @desc Yog3McpXmlMediaVideo
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlMediaVideo extends Yog3McpXmlMediaAbstract
  {

  }

  /**
  * @desc Yog3McpXmlMediaDocument
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlMediaDocument extends Yog3McpXmlMediaAbstract
  {

  }

  /**
  * @desc Yog3McpXmlDossier
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlDossier extends Yog3McpXmlMediaAbstract
  {
    /**
    * @desc Get the title
    *
    * @param void
    * @return string
    */
    public function getTitle()
    {
       return (string) $this->xml->Title;
    }
  }

  /**
  * @desc Yog3McpXmlLink
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlLink extends Yog3McpXmlAbstract
  {

  }

  /**
  * @desc Yog3McpXmlRelationReference
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlRelationReference extends Yog3McpXmlAbstract
  {
    /**
    * @desc Get the role
    *
    * @param void
    * @return string
    */
    public function getRole()
    {
      return (string) $this->xml->Role;
    }

    /**
    * @desc Get the uuid
    *
    * @param void
    * @return string
    */
    public function getUuid()
    {
       return (string) $this->xml['uuid'];
    }
  }

  /**
  * @desc Yog3McpXmlRelationAbstract
  * @author Kees Brandenburg - Yes-co Nederland
  */
  abstract class Yog3McpXmlRelationAbstract extends Yog3McpXmlAbstract
  {
    /**
    * @desc Create Yog3McpXmlRelationAbstract
    *
    * @param YogSimpleXMLElement $xml
    * @param string $namespace
    * @return Yog3McpXmlRelationAbstract
    */
    static public function create(YogSimpleXMLElement $xml, $namespace)
    {
      $xml->registerXPathNamespace('relation', $namespace);

      switch ((string) $xml->Type)
      {
        case 'company':
        case 'office':
          return new Yog3McpXmlRelationBusiness($xml, $namespace);
          break;
        case 'contact':
        case 'employee':
        case 'individual':
          return new Yog3McpXmlRelationPerson($xml, $namespace);
          break;
        default:
          throw new YogException(__METHOD__ . '; Unsupported relation type', YogException::GLOBAL_ERROR);
          break;
      }
    }

    /**
    * @desc Get the relation type
    *
    * @param void
    * @return string
    */
    public function getType()
    {
      return (string) $this->xml->Type;
    }

    /**
    * @desc Get the main address
    *
    * @param void
    * @return Yog3McpXmlAddress
    */
    public function getMainAddress()
    {
      $nodes = $this->xml->xpath('//relation:MainAddress');
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No main address found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      $node->registerXPathNamespace('relation', $this->ns);

      return new Yog3McpXmlAddress($node, $this->ns);
    }

    /**
    * @desc Check if main address is set
    *
    * @param void
    * @return bool
    */
    public function hasMainAddress()
    {
      $nodes = $this->xml->xpath('//relation:MainAddress');
      return ($nodes !== false && count($nodes) > 0);
    }

    /**
    * @desc Get the postal address
    *
    * @param void
    * @return Yog3McpXmlAddress
    */
    public function getPostalAddress()
    {
      $nodes = $this->xml->xpath('//relation:PostalAddress');
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No postal address found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      $node->registerXPathNamespace('relation', $this->ns);

      return new Yog3McpXmlAddress($node, $this->ns);
    }

    /**
    * @desc Check if postal address is set
    *
    * @param void
    * @return bool
    */
    public function hasPostalAddress()
    {
      $nodes = $this->xml->xpath('//relation:PostalAddress');
      return ($nodes !== false && count($nodes) > 0);
    }
  }

  /**
  * @desc Yog3McpXmlRelationPerson
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlRelationPerson extends Yog3McpXmlRelationAbstract
  {
    /**
    * @desc Check if relation has a link to a parent company
    *
    * @param void
    * @return bool
    */
    public function hasCompanyUuid()
    {
      $nodes = $this->xml->xpath("//relation:Person/relation:CompanyLink/@uuid");

      return !($nodes === false || count($nodes) == 0);
    }

    /**
    * @desc Get the parent company uuid
    *
    * @param void
    * @return string
		* @throws \YogException
    */
    public function getCompanyUuid()
    {
      $nodes = $this->xml->xpath("//relation:Person/relation:CompanyLink/@uuid");
      if ($nodes === false || count($nodes) == 0)
        throw new YogException(__METHOD__ . '; No parent company link found', YogException::GLOBAL_ERROR);

      $node = array_shift($nodes);
      return (string) $node;
    }
  }

  /**
  * @desc Yog3McpXmlRelationBusiness
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class Yog3McpXmlRelationBusiness extends Yog3McpXmlRelationAbstract
  {

  }