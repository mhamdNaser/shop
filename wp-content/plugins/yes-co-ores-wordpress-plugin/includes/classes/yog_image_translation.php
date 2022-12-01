<?php
  require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_translation.php');

  /**
  * @desc YogImageTranslation
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogImageTranslation extends YogTranslationAbstract
  {
    protected $mcp3Image;
    protected $mcp3Link;

    /**
    * @desc Constructor
    *
    * @param Yog3McpXmlMediaImage $mcp3Image
    * @param Yog3McpMediaLink $mcp3Link
    * @return YogRelationTranslationAbstract
    */
    private function __construct(Yog3McpXmlMediaImage $mcp3Image, Yog3McpMediaLink $mcp3Link)
    {
      $this->mcp3Image  = $mcp3Image;
      $this->mcp3Link   = $mcp3Link;
    }

    /**
    * @desc Create from Yog3McpProjectAbstract
    *
    * @param Yog3McpXmlMediaImage $mcp3Image
    * @param Yog3McpMediaLink $mcp3Link
    * @return YogRelationTranslationAbstract
    */
    static public function create(Yog3McpXmlMediaImage $mcp3Image, Yog3McpMediaLink $mcp3Link)
    {
      return new self($mcp3Image, $mcp3Link);
    }

    /**
    * @desc Get post type
    *
    * @param void
    * @return string
    */
    public function getPostType()
    {
      return YOG_POST_TYPE_ATTACHMENT;
    }

    /**
    * @desc Get the data for the post
    *
    * @param void
    * @return array
    */
    public function getPostData()
    {
	    return array(
        'post_mime_type'    => $this->mcp3Link->getMimeType(),
        'post_date'         => $this->translateDate($this->mcp3Link->getDoc()),
        'post_date_gmt'     => $this->translateDate($this->mcp3Link->getDoc()),
        'post_modified'     => $this->translateDate($this->mcp3Link->getDlm()),
        'post_modified_gmt' => $this->translateDate($this->mcp3Link->getDlm()),
        'post_title'        => $this->determineTitle(),
        'post_content'      => $this->mcp3Link->getUuid(),
        'post_status'       => 'inherit',
        'menu_order'        => $this->mcp3Image->getOrder()
      );
    }

    /**
    * @desc Determine image title
    *
    * @param void
    * @return string
    */
    public function determineTitle()
    {
      return $this->mcp3Image->getTitle();
    }

    /**
    * @desc Get image meta data
    *
    * @param void
    * @return array
    */
    public function getMetaData()
    {
      return array(
        'type'                  => $this->mcp3Image->getType(),
        'dlm'                   => $this->translateDate($this->mcp3Link->getDlm())
      );
    }
  }