<?php
  require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_translation.php');

  /**
  * @desc YogRelationTranslationAbstract
  * @author Kees Brandenburg - Yes-co Nederland
  */
  abstract class YogRelationTranslationAbstract extends YogTranslationAbstract
  {
    const POST_TYPE = 'relatie';

    protected $mcp3Relation;
    protected $mcp3Link;

    /**
    * @desc Constructor
    *
    * @param Yog3McpXmlRelationAbstract $mcp3Relation
    * @param Yog3McpRelationLink $mcp3Link
    * @return YogRelationTranslationAbstract
    */
    private function __construct(Yog3McpXmlRelationAbstract $mcp3Relation, Yog3McpRelationLink $mcp3Link)
    {
      $this->mcp3Relation = $mcp3Relation;
      $this->mcp3Link     = $mcp3Link;
    }

    /**
    * @desc Create from Yog3McpProjectAbstract
    *
    * @param Yog3McpXmlRelationAbstract $mcp3Relation
    * @param Yog3McpRelationLink $mcp3Link
    * @return YogRelationTranslationAbstract
    */
    static public function create(Yog3McpXmlRelationAbstract $mcp3Relation, Yog3McpRelationLink $mcp3Link)
    {
      if ($mcp3Relation instanceOf Yog3McpXmlRelationPerson)
        return new YogRelationTranslationPerson($mcp3Relation, $mcp3Link);
      else if ($mcp3Relation instanceOf Yog3McpXmlRelationBusiness)
         return new YogRelationTranslationBusiness($mcp3Relation, $mcp3Link);
      else
        throw new Exception(__METHOD__ . '; Unsupported 3mcp relation');
    }

    /**
    * @desc Get post type
    *
    * @param void
    * @return string
    */
    public function getPostType()
    {
      return self::POST_TYPE;
    }

    /**
    * @desc Get the data for the post
    *
    * @param void
    * @return array
    */
    public function getPostData()
    {
	    $data = array();
	    $data['post_title']         = $this->determineTitle();
	    $data['post_status']        = 'publish';
	    $data['post_author']        = 1;
	    //$data['menu_order']         = 0;
	    $data['comment_status']     = 'closed';
	    $data['ping_status']        = 'closed';
	    $data['post_date']          = $this->translateDate($this->mcp3Link->getDoc());
	    $data['post_parent']        = 0;
	    $data['post_type']          = $this->getPostType();

      return $data;
    }

    /**
    * @desc Get the address meta data
    *
    * @param Yog3McpXmlAddress $address
    * @param string $prefix
    * @return array
    */
    protected function getAddressMetaData(Yog3McpXmlAddress $address, $prefix)
    {
      return array(
        $prefix . 'land'        => $address->getCountry(),
        $prefix . 'provincie'   => $address->getState(),
        $prefix . 'gemeente'    => $address->getMunicipality(),
        $prefix . 'stad'        => $address->getCity(),
        $prefix . 'wijk'        => $address->getArea(),
        $prefix . 'buurt'       => $address->getNeighbourhood(),
        $prefix . 'straat'      => $address->getStreet(),
        $prefix . 'postcode'    => $address->getZipcode(),
        $prefix . 'huisnummer'  => $address->getHouseNumber() . $address->getHouseNumberAddition()
      );
    }

    /**
    * @desc Check if a parent uuid is set, can be overwriten by extending class
    *
    * @param void
    * @return bool
    */
    public function hasParentUuid()
    {
      return false;
    }

    /**
    * @desc Get the parent uuid, can be overwritten by extending class
    *
    * @param void
    * @return string
    * @throws Exception
    */
    public function getParentUuid()
    {
      throw new Exception(__METHOD__ . '; Object does not have a parent object');
    }
  }

  /**
  * @desc YogRelationTranslationPerson
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogRelationTranslationPerson extends YogRelationTranslationAbstract
  {
    /**
    * @desc Determine relation title
    *
    * @param void
    * @return string
    */
    public function determineTitle()
    {
      $firstname  = $this->mcp3Relation->getStringByPath('//relation:Person/relation:Name/relation:Firstname');
      $initals    = $this->mcp3Relation->getStringByPath('//relation:Person/relation:Name/relation:Initials');
      $prefix     = $this->mcp3Relation->getStringByPath('//relation:Person/relation:Name/relation:LastnamePrefix');
      $lastname   = $this->mcp3Relation->getStringByPath('//relation:Person/relation:Name/relation:Lastname');

      $title      = (empty($firstname) ? $initals : $firstname);
      if (!empty($prefix))
        $title .= ' ' . $prefix;
      if (!empty($lastname))
        $title .= ' ' . $lastname;

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
      $data = array(
        'type'                  => 'Person',
				'subtype'								=> $this->mcp3Relation->getStringByPath('/relation:Relation/relation:Type'),
        'uuid'                  => $this->mcp3Relation->getStringByPath('/relation:Relation/@uuid'),
        'dlm'                   => $this->translateDate($this->mcp3Link->getDlm()),
				'ApiKey'                => $this->mcp3Relation->getStringByPath('/relation:Relation/@apiId'),
				'Source'                => $this->mcp3Relation->getStringByPath('/relation:Relation/@source'),
        'Titel'                 => $this->mcp3Relation->getStringByPath('//relation:Person/relation:Name/relation:Title'),
        'Initialen'             => $this->mcp3Relation->getStringByPath('//relation:Person/relation:Name/relation:Initials'),
        'Voornaam'              => $this->mcp3Relation->getStringByPath('//relation:Person/relation:Name/relation:Firstname'),
        'Voornamen'             => $this->mcp3Relation->getStringByPath('//relation:Person/relation:Name/relation:Firstnames'),
        'Tussenvoegsel'         => $this->mcp3Relation->getStringByPath('//relation:Person/relation:Name/relation:LastnamePrefix'),
        'Achternaam'            => $this->mcp3Relation->getStringByPath('//relation:Person/relation:Name/relation:Lastname'),
        'Emailadres'            => $this->mcp3Relation->getStringByPath('//relation:Person/relation:EmailAddress'),
        'Telefoonnummer'        => $this->mcp3Relation->getStringByPath('//relation:Person/relation:PhoneNR'),
        'Telefoonnummerwerk'    => $this->mcp3Relation->getStringByPath('//relation:Person/relation:WorkPhoneNR'),
        'Telefoonnummermobiel'  => $this->mcp3Relation->getStringByPath('//relation:Person/relation:MobilePhoneNR'),
        'Faxnummer'             => $this->mcp3Relation->getStringByPath('//relation:Person/relation:FaxNR'),
        'Functie'               => $this->mcp3Relation->getStringByPath('//relation:Person/relation:Position'),
        'Geslacht'              => str_replace(array('male', 'female'), array('man', 'vrouw'), $this->mcp3Relation->getStringByPath('//relation:Person/relation:Sex'))
      );

      if ($this->mcp3Relation->hasMainAddress())
        $data = array_merge($data, $this->getAddressMetaData($this->mcp3Relation->getMainAddress(), 'Hoofdadres_'));

      if ($this->mcp3Relation->hasPostalAddress())
        $data = array_merge($data, $this->getAddressMetaData($this->mcp3Relation->getPostalAddress(), 'Postadres_'));

      return $data;
    }

    /**
    * @desc Check if a parent uuid is set
    *
    * @param void
    * @return bool
    */
    public function hasParentUuid()
    {
      return $this->mcp3Relation->hasCompanyUuid();
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
        throw new Exception(__METHOD__ . '; Object does not have a parent company');

      return $this->mcp3Relation->getCompanyUuid();
    }
  }

  /**
  * @desc YogRelationTranslationBusiness
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogRelationTranslationBusiness extends YogRelationTranslationAbstract
  {
    /**
    * @desc Determine relation title
    *
    * @param void
    * @return string
    */
    public function determineTitle()
    {
      return $this->mcp3Relation->getStringByPath('//relation:Business/relation:Name');
    }

    /**
    * @desc Get meta data
    *
    * @param void
    * @return array
    */
    public function getMetaData()
    {
      $data = array(
        'type'            => 'Business',
				'subtype'					=> $this->mcp3Relation->getStringByPath('/relation:Relation/relation:Type'),
        'uuid'            => $this->mcp3Relation->getStringByPath('/relation:Relation/@uuid'),
        'dlm'             => $this->translateDate($this->mcp3Link->getDlm()),
				'ApiKey'          => $this->mcp3Relation->getStringByPath('/relation:Relation/@apiId'),
				'Source'          => $this->mcp3Relation->getStringByPath('/relation:Relation/@source'),
        'Emailadres'      => $this->mcp3Relation->getStringByPath('//relation:Business/relation:EmailAddress'),
        'Website'         => $this->mcp3Relation->getStringByPath('//relation:Business/relation:WebsiteURL'),
        'Telefoonnummer'  => $this->mcp3Relation->getStringByPath('//relation:Business/relation:PhoneNR'),
        'Faxnummer'       => $this->mcp3Relation->getStringByPath('//relation:Business/relation:FaxNR')
      );

      if ($this->mcp3Relation->hasMainAddress())
        $data = array_merge($data, $this->getAddressMetaData($this->mcp3Relation->getMainAddress(), 'Hoofdadres_'));

      if ($this->mcp3Relation->hasPostalAddress())
        $data = array_merge($data, $this->getAddressMetaData($this->mcp3Relation->getPostalAddress(), 'Postadres_'));

      return $data;
    }
  }