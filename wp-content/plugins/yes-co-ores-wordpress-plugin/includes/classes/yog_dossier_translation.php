<?php
  require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_translation.php');

  /**
  * @desc YogDossierTranslation
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogDossierTranslation extends YogTranslationAbstract
  {
    protected $mcp3Dossier;
    protected $mcp3Link;

    /**
    * @desc Constructor
    *
    * @param Yog3McpXmlDossier $mcp3DossierItem
    * @param Yog3McpMediaLink $mcp3Link
    * @return YogDossierTranslation
    */
    private function __construct(Yog3McpXmlDossier $mcp3DossierItem, Yog3McpDossierLink $mcp3Link)
    {
      $this->mcp3Dossier  = $mcp3DossierItem;
      $this->mcp3Link     = $mcp3Link;
    }

    /**
    * @desc Create from Yog3McpProjectAbstract
    *
    * @param Yog3McpXmlDossier $mcp3DossierItem
    * @param Yog3McpDossierLink $mcp3Link
    * @return YogDossierTranslation
    */
    static public function create(Yog3McpXmlDossier $mcp3DossierItem, Yog3McpDossierLink $mcp3Link)
    {
      return new self($mcp3DossierItem, $mcp3Link);
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
    * @desc Get the data for the dossier item
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
        'post_status'       => 'inherit'
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
      return $this->mcp3Dossier->getTitle();
    }

    /**
    * @desc Get dossier meta data
    *
    * @param void
    * @return array
    */
    public function getMetaData()
    {
      return array(
        'dlm'                   => $this->translateDate($this->mcp3Link->getDlm()),
        'filename'              => basename($this->mcp3Link->getUrl())
      );
    }
  }