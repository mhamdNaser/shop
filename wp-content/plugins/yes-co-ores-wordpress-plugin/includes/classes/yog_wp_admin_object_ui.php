<?php
  require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_fields_settings.php');

  /**
  * @desc YogWpAdminUiAbstract
  * @author Kees Brandenburg - Yes-co Nederland
  */
  abstract class YogWpAdminUiAbstract
  {
    /**
    * @desc Create a YogWpAdminUiAbstract based on the post type
    *
    * @param string $postType
    * @return mixed
    */
    static public function create($postType)
    {
      switch ($postType)
      {
        case YOG_POST_TYPE_WONEN:
          require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_wp_admin_object_ui_wonen.php');
          return new YogWpAdminObjectUiWonen();
          break;
        case YOG_POST_TYPE_BOG:
          require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_wp_admin_object_ui_bog.php');
          return new YogWpAdminObjectUiBog();
          break;
        case YOG_POST_TYPE_NBPR:
          require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_wp_admin_object_ui_nbpr.php');
          return new YogWpAdminObjectUiNBpr();
          break;
        case YOG_POST_TYPE_NBTY:
          require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_wp_admin_object_ui_nbty.php');
          return new YogWpAdminObjectUiNbty();
          break;
        case YOG_POST_TYPE_NBBN:
          require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_wp_admin_object_ui_nbbn.php');
          return new YogWpAdminObjectUiNbbn();
          break;
        case YOG_POST_TYPE_BBPR:
          require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_wp_admin_object_ui_bbpr.php');
          return new YogWpAdminObjectUiBbpr();
          break;
        case YOG_POST_TYPE_BBTY:
          require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_wp_admin_object_ui_bbty.php');
          return new YogWpAdminObjectUiBbty();
          break;
        case YOG_POST_TYPE_BOPR:
          require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_wp_admin_object_ui_bopr.php');
          return new YogWpAdminObjectUiBOpr();
          break;
        case YOG_POST_TYPE_RELATION:
          require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_wp_admin_ui_relation.php');
          return new YogWpAdminUiRelation();
          break;
      }
    }

    /**
    * @desc Initialize WP admin interface
    *
    * @param void
    * @return void
    */
    public function initialize()
    {
	    add_action('admin_init',                                        array($this, 'addMetaBoxes'));
	    add_action('save_post',                                         array($this, 'extendSave'), 1, 2);
	    add_action('manage_posts_custom_column',                        array($this, 'generateColumnContent'));
	    add_filter('manage_edit-' . $this->getPostType() . '_columns',  array($this, 'determineColumns'));
      add_action('init',                                              array($this, 'enqueueFiles'));

      $this->addAjaxActions();
    }

    /**
    * @desc Enqueue files
    *
    * @param void
    * @return void
    */
    public function enqueueFiles()
    {
      $minifyExtension = (YOG_DEBUG_MODE === true) ? '' : '.min';

      wp_enqueue_script('yog-admin-object-js',     YOG_PLUGIN_URL .'/inc/js/admin_object' . $minifyExtension . '.js', array('jquery', 'jquery-ui-datepicker'));
      wp_enqueue_style('yog-admin-object-css', YOG_PLUGIN_URL . '/inc/css/admin_objects.css');
    }

    /**
    * @desc Determine content of a single column in overview
    *
    * @param string $columnId
    * @return void
    */
    public function generateColumnContent($columnId)
    {
      switch ($columnId)
      {
        case 'thumbnail':
          $thumbnail = get_the_post_thumbnail(null, [200, 150]);
          if (!empty($thumbnail))
            echo $thumbnail;
          else
            echo '<div class="no-image" style="width: 200px;"></div>';

          break;
        case 'title':
          echo esc_html(yog_retrieveSpec('Naam'));
          break;
        case 'description':
          $content = get_the_excerpt();
          if (strlen($content) > 150)
            $content = substr($content, 0, 150) . '...';

          echo esc_html($content);
          break;
        case 'address':
          $specs   = yog_retrieveSpecs(array('Straat', 'Huisnummer', 'Plaats', 'Postcode'));

          echo esc_html($specs['Straat'] . ' ' . $specs['Huisnummer']) . '<br />';
          echo esc_html($specs['Postcode']) . '&nbsp;&nbsp;' . esc_html($specs['Plaats']);
          break;
        case 'location':
          $specs = yog_retrieveSpecs(array('Postcode', 'Plaats', 'Land'));

          echo esc_html(implode('<br />', $specs));
          break;
        case 'dlm':
          echo esc_html(get_the_modified_date() . ' ' . get_the_modified_time());
          break;
        case 'scenario':
          echo esc_html(yog_retrieveSpec('scenario'));
          break;
      }
    }

    /**
    * @desc Retrieve input fields for specific fields
    *
    * @param $postId
    * @param array $fields
    * @param $globalReadonly (default: false)
    * @return array
    */
    protected function retrieveInputs($postId, $fields, $globalReadonly = false)
    {
	    if (!is_array($fields))
	      throw new Exception(__METHOD__ . '; Invalid specs provided, must be an array');

      $postType           = get_post_type($postId);

	    foreach ($fields as $key => $field)
	    {
		    $fields[$key] = $postType . '_' . $field;
	    }

	    $html               = '';
	    $customFieldValues  = get_post_custom($postId);
      $fieldsSettings     = YogFieldsSettingsAbstract::create($postType);

	    foreach ($fields as $field)
	    {
        $settings = $fieldsSettings->getField($field);

		    $value    = array_key_exists($field, $customFieldValues) ? $customFieldValues[$field][0] : '';
		    $title    = empty($settings['title']) ? str_replace($postType . '_', '', $field) : $settings['title'];
		    $width    = empty($settings['width']) ? 300 : $settings['width'];

				// Check replacement meta value
				if (!empty($settings['replacementMeta']) && array_key_exists($settings['replacementMeta'], $customFieldValues))
					$value = $customFieldValues[$settings['replacementMeta']][0];

        // Overwrite that a field is readonly
        if (!empty($settings['readonly']) && $settings['readonly'] == true)
          $readOnly = true;
				else
					$readOnly = $globalReadonly;

		    $prefix   = '';
		    $addition = empty($settings['addition']) ? '' : ' ' . $settings['addition'];

		    if (!empty($settings['type']))
		    {
			    switch ($settings['type'])
			    {
            case 'meter':
              $addition = ' m';
              break;
            case 'cm':
              $addition = ' cm';
              break;
				    case 'oppervlakte':
					    $addition = ' m&sup2;';
					    break;
				    case 'inhoud';
				      $addition = ' m&sup3;';
				      break;
				    case 'price':
            case 'priceBtw':
					    $prefix = '&euro; ';
					    break;
			    }
		    }

		    $html .= '<tr>';
		    $html .= '<th scope="row">' . esc_html($title) . '</th>';
		    $html .= '<td>';
		    $html .= $prefix;

        if ($readOnly === true)
        {
          $html .= '<input type="hidden" name="' . $field . '" id="' . str_replace($postType, 'yog', $field) . '" value="' . esc_attr($value) . '" />';
          $html .= '<b>' . esc_html($value) . '</b>';
        }
        else if (!empty($settings['type']) && $settings['type'] == 'bool')
        {
          $html .= '<input type="checkbox" name="' . $field . '" value="ja"' . (($value == 'ja' || $value == 1) ? ' checked="checked"' : '') . ' />';
        }
        else if (!empty($settings['type']) && $settings['type'] == 'date')
        {
          $html .= '<input type="date" style="width: ' . esc_attr($width) . 'px;" name="' . $field . '" value="' . esc_attr($value) . '" />';
        }
        else if (!empty($settings['type']) && $settings['type'] == 'priceBtw')
        {
          $btwField = $field . 'BtwPercentage';
          $btwValue = array_key_exists($btwField, $customFieldValues) ? $customFieldValues[$btwField][0] : '';

          $html .= '<input type="text" style="width: 100px;" name="' . $field . '" value="' . esc_attr($value) . '" />';
          $html .= '&nbsp;&nbsp;btw <input type="text" style="width: 40px;" name="' . $btwField . '" value="' . esc_attr($btwValue) . '" /> %';
        }
        else if (!empty($settings['type']) && $settings['type'] == 'select' && !empty($settings['options']))
        {
          $html .= '<select name="' . $field . '" id="yog-' . $field . '">';
          foreach ($settings['options'] as $option)
          {
						if (is_array($option))
						{
							$key		= $option['key'];
							$label	= $option['label'];
						}
						else
						{
							$key		= $option;
							$label	= $option;
						}

						$html .= '<option value="' . esc_attr($key) . '"' . ($key == $value ? ' selected="selected"' : '') . '>' . esc_html($label) . '</option>';
          }
          $html .= '</select>';
        }
        else
        {
		      $html .= '<input type="text" style="width: ' . $width . 'px;" name="' . $field . '" value="' . esc_html($value) . '" />';
        }

		    $html .= $addition;
		    $html .= '</td>';
		    $html .= '</tr>';
	    }

	    return $html;
    }

    /**
    * @desc Render Google maps meta box
    *
    * @param object @post
    * @return void
    */
    public function renderMapsMetaBox($post)
    {
      $postId						= get_the_ID();
      $post							= get_post($postId);
      $postType					= $post->post_type;
			$googleMapsApiKey	= get_option('yog_google_maps_api_key');

      $specs      = yog_retrieveSpecs(array('Latitude', 'Longitude'));

      $latitude   = isset($specs['Latitude']) ? $specs['Latitude'] : false;
      $longitude  = isset($specs['Longitude']) ? $specs['Longitude'] : false;

      $html = '';

      $html .= '<div class="row">';
	      $html .= '<label for="' . $postType . '_Latitude">Latitude: </label><br />';
        $html .= '<input id="' . $postType . '_Latitude" name="' . $postType . '_Latitude" type="text" value="' . $latitude . '" />';
      $html .= '</div>';

      $html .= '<div class="row">';
	      $html .= '<label for="' . $postType . '_Longitude">Longitude: </label><br />';
        $html .= '<input id="' . $postType . '_Longitude" name="' . $postType . '_Longitude" type="text" value="' . $longitude . '" />';
      $html .= '</div>';

      $html .= '<br /><br />';

      echo $html;

			if (!empty($googleMapsApiKey))
			{
				$extraOnLoad = 'marker' . $postId . '.addListener(\'dragend\', function(mapsMouseEvent) {';
					$extraOnLoad .= 'document.getElementById(\'' . $postType . '_Latitude\').value = mapsMouseEvent.latLng.lat();';
					$extraOnLoad .= 'document.getElementById(\'' . $postType . '_Longitude\').value = mapsMouseEvent.latLng.lng();';
					$extraOnLoad .= 'map' . $postId . '.setCenter(mapsMouseEvent.latLng);';
				$extraOnLoad .= '});';

				$dynamicMap = yog_retrieveDynamicMap('hybrid', 18, 260, 260, $extraOnLoad, true);

				//$staticMap = yog_retrieveStaticMap('hybrid', 18, 260, 260);

				if (empty($dynamicMap))
				{
					echo '<p>Er is geen locatie bekend.</p>';
				}
				else
				{
					//echo $staticMap;
					echo $dynamicMap;
				}
			}
    }

    abstract public function getPostType();
    abstract public function determineColumns($columns);
    abstract public function addMetaBoxes();
    abstract public function extendSave($postId, $post);

    /**
    * @desc Add ajax actions
    * Can be overwritten by implementing class
    *
    * @param void
    * @return void
    */
    public function addAjaxActions()
    {

    }
  }

  /**
  * @desc YogWpAdminObjectUiAbstract
  * @author Kees Brandenburg - Yes-co Nederland
  */
  abstract class YogWpAdminObjectUiAbstract extends YogWpAdminUiAbstract
  {
    /**
    * @desc Add ajax actions
    *
    * @param void
    * @return void
    */
    public function addAjaxActions()
    {
	    add_action('wp_ajax_removelink',      array($this, 'ajaxRemoveLink'));
	    add_action('wp_ajax_removedocument',  array($this, 'ajaxRemoveDocument'));
	    add_action('wp_ajax_removevideo',     array($this, 'ajaxRemoveVideo'));
	    add_action('wp_ajax_addlink',         array($this, 'ajaxAddLink'));
	    add_action('wp_ajax_adddocument',     array($this, 'ajaxAddDocument'));
	    add_action('wp_ajax_addvideo',        array($this, 'ajaxAddVideo'));
    }

    /**
    * @desc Render synchronization meta box
    *
    * @param object $post
    * @return void
    */
    public function renderSyncMetaBox($post)
    {
	    echo '<input type="hidden" name="yog_nonce" id="myplugin_noncename" value="' .wp_create_nonce($this->getBaseName()) . '" />';
      echo '<input type="hidden" name="uuid" value="' . yog_retrieveSpec('uuid', $post->ID) . '" />';

	    echo '<table class="form-table">';
	    echo $this->retrieveInputs($post->ID, array('scenario', 'uuid'), true);
	    echo '</table>';
    }


    /**
    * @desc Render images meta box
    *
    * @param object $post
    * @return void
    */
    public function renderImagesMetaBox($post)
    {
      $images = yog_retrieveImages('thumbnail', null, $post->ID);

      $html = '<div class="images-holder">';
      foreach ($images as $image)
      {
        $html .= '<div class="image-holder">';
        $html .= '<img src="' . esc_url($image[0]) . '" />';
        $html .= '</div>';
      }
      $html .= '</div>';

      echo $html;
    }

    /**
    * @desc Render dossier meta box
    *
    * @param object $post
    * @return void
    */
    public function renderDossierMetaBox($post)
    {
      $dossierItems = yog_retrieveDossierItems(null, $post->ID);

      if (is_array($dossierItems) && count($dossierItems) > 0)
      {
        $html = '<ul>';
        foreach ($dossierItems as $dossierItem)
        {
          $html .= '<li><a href="' . esc_url($dossierItem['url']) . '" target="_blank">' . esc_html($dossierItem['title']) . '</a></li>';
        }
        $html .= '</ul>';
      }
      else
      {
        $html = '<p>Geen dossier items aanwezig.</p>';
      }

      echo $html;
    }

    /**
    * @desc Render movies meta box
    *
    * @param object $post
    * @return void
    */
    public function renderMoviesMetaBox($post)
    {
      $videos      = yog_retrieveMovies($post->ID);

	    $videoservices = array( 'Youtube' => 'http://www.youtube.com/',
	                            'Vimeo'   => 'http://vimeo.com/',
	                            'Flickr'  => 'http://www.flickr.com/');

	    $select = '<select id="video_type" name="video_type">';
	    foreach ($videoservices as $videoservice => $link)
      {
        $select.= '<option value="' .esc_attr($link) .'">' .esc_html($videoservice) .'</option>';
	    }
	    $select.= '</select>';

      $html  = '<div class="media-box" id="yog-video-form">';
        $html .= '<div class="row">';
	        $html .= '<label for="video_type">Videoprovider: </label>' . $select;
        $html .= '</div>';
        $html .= '<div class="row">';
	        $html .= '<label for="video_titel">Titel: </label>';
          $html .= '<input id="video_titel" name="video_titel" type="text" value="" />';
        $html .= '</div>';
        $html .= '<div class="row">';
	        $html .= '<label for="video_url">Link waarop de video te zien is, bijvoorbeeld <br /><i>www.youtube.com/watch?v=duqr82aYKRY</i>:  </label>';
          $html .= 'http://<input id="video_url" name="video_url" class="input-small" type="text" value="" />';
          $html .= '<input type="button" class="button-primary" id="yog-add-video" value="Toevoegen" />';
        $html .= '</div>';
      $html .= '</div>';

      $html .= '<div id="yog-videos-overview" class="media-overview' . ((is_array($videos) && count($videos) > 0) ? '' : ' hide') . '">';
	      $html .= '<b>Gekoppelde videos:</b>';
	      $html .= '<div class="media-box">';
          $html .= '<table class="form-table" id="yog-video-tabel">';
            $html .= '<tbody>';

	          if (is_array($videos) && count($videos))
            {
		          foreach ($videos as $videoUUID => $video)
		          {
                $url  = !empty($video['websiteurl']) ? $video['websiteurl'] : $video['videostreamurl'];

			          $html .= '<tr id="video-' . esc_attr($video['uuid']) . '">';
			          $html .= '<td><a href="' . esc_url($url) .'" class="' . esc_attr($video['type']) . '" target="_blank">' . esc_html($video['title']) . '</a></td>';
			          $html .= '<td class="actions"><input type="button" class="button-primary" onclick="yogRemoveVideo(\'' .esc_attr($video['uuid']) .'\');" value="Verwijderen" style="margin-left: 5px;"></td>';
			          $html .= '</tr>';
		          }
	          }

            $html .= '</tbody>';
	        $html .= '</table>';
        $html .= '</div>';
      $html .= '</div>';

			wp_nonce_field('yog-object-video', 'yog-video-nonce', false);
	    echo $html;
    }

    /**
    * @desc Render documents meta box
    *
    * @param object $post
    * @return void
    */
    public function renderDocumentsMetaBox($post)
    {
      $documents  = yog_retrieveDocuments($post->ID);

	    $html  = '<div class="media-box" id="yog-document-form">';
        $html .= '<div class="row">';
          $html .= '<label for="document_type">Type (bijvoorbeeld \'brochure\'): </label>';
          $html .= '<input id="document_type" name="document_type" type="text" value="" />';
        $html .= '</div>';
        $html .= '<div class="row">';
	        $html .= '<label for="document_titel">Titel: </label>';
          $html .= '<input id="document_titel" name="document_titel" type="text" value="" />';
        $html .= '</div>';
        $html .= '<div class="row">';
	        $html .= '<label for="document_url">Link: </label>';
          $html .= 'http://<input id="document_url" name="document_url" class="input-small" type="text" value="" />';
          $html .= '<input type="button" class="button-primary" id="yog-add-document" value="Toevoegen" />';
        $html .= '</div>';
      $html .= '</div>';

      $html .= '<div id="yog-documents-overview" class="media-overview' . ((is_array($documents) && count($documents) > 0) ? '' : ' hide') . '">';
	      $html .= '<b>Gekoppelde documenten:</b>';
	      $html .= '<div class="media-box">';
          $html .= '<table class="form-table" id="yog-documents-table">';
            $html .= '<tbody>';

	          if(is_array($documents) && count($documents))
            {
		          foreach ($documents as $documentsUUID => $document)
		          {
                $type = empty($document['type']) ? 'brochure' : $document['type'];

			          $html .= '<tr id="document-' . esc_attr($document['uuid']) . '">';
			          $html .= '<td><a href="' . esc_url($document['url']) . '" class="' . esc_attr($type) . '">' . esc_html($document['title']) . '</a></td>';
			            $html .= '<td class="actions"><input type="button" class="button-primary" onclick="yogRemoveDocument(\'' .esc_attr($document['uuid']) .'\');" value="Verwijderen" /></td>';
			          $html .= '</tr>';
		          }
	          }

            $html .=  '</tbody>';
	        $html .= '</table>';
        $html .= '</div>';
      $html .= '</div>';

			wp_nonce_field('yog-object-document', 'yog-document-nonce', false);
	    echo $html;
    }

    /**
    * @desc Render links meta box
    *
    * @param object $post
    * @return void
    */
    public function renderLinksMetaBox($post)
    {
      $links      = yog_retrieveLinks($post->ID);

	    $html  = '<div class="media-box" id="yog-link-form">';
        $html .= '<div class="row">';
          $html .= '<label for="link_type">Type: </label>';
          $html .= '<select id="link_type" name="link_type"><option value="website">Website</option></select>';
        $html .= '</div>';
        $html .= '<div class="row">';
	        $html .= '<label for="link_titel">Titel: </label>';
          $html .= '<input id="link_titel" name="link_titel" type="text" value="" />';
        $html .= '</div>';
        $html .= '<div class="row">';
	        $html .= '<label for="link_url">Link: </label>';
          $html .= 'http://<input id="link_url" name="link_url" class="input-small" type="text" value="" />';
          $html .= '<input type="button" id="yog-add-link" class="button-primary" value="Toevoegen" />';
        $html .= '</div>';
      $html .= '</div>';

      $html .= '<div id="yog-links-overview" class="media-overview' . ((is_array($links) && count($links) > 0) ? '' : ' hide') . '">';
	      $html .= '<b>Gekoppelde links: </b>';
	      $html .= '<div class="media-box">';
          $html .= '<table class="form-table" id="yog-links-table">';
	          $html .= '<tbody>';

	          if (is_array($links) && count($links) > 0)
            {
		          foreach ($links as $linkUUID => $link)
		          {
                $type = empty($link['type']) ? 'website' : $link['type'];

			          $html .= '<tr id="link-' . esc_attr($linkUUID) . '">';
			          $html .= '<td><a href="' . esc_url($link['url']) . '" class="' . esc_attr($type) . '" target="_blank">' . esc_html($link['title']) . '</a></td>';
			          $html .= '<td class="actions"><input type="button" class="button-primary" onclick="yogRemoveLink(\'' .esc_attr($link['uuid']) .'\');" value="Verwijderen" /></td>';
			          $html .= '</tr>';
		          }
	          }

            $html .= '</tbody>';
	        $html .= '</table>';
        $html .= '</div>';
      $html .= '</div>';

			wp_nonce_field('yog-object-link', 'yog-link-nonce', false);
	    echo $html;
    }

    /**
    * @desc Render parent meta box
    *
    * @param StdClass $post
    * @return void
    */
    public function renderParentMetaBox($post)
    {
      $parentId = yog_getParentObjectId($post->ID);
      if ($parentId !== false)
      {
        $parent   = get_post($parentId);

        $thumbnail = get_the_post_thumbnail($parent->ID, array(258,258));
        if (empty($thumbnail))
          $thumbnail = '<div class="no-image" style="width:100%;"></div>';

        echo '<div id="yog-parent-post">';
          echo $thumbnail;
          echo '<strong><a href="' . esc_url(get_edit_post_link($parent->ID)) . '">' . esc_html($parent->post_title) . '</a></strong>';
        echo '</div>';
      }
    }

    /**
    * @desc Render child projects meta box
    *
    * @param StdClass $post
    * @return void
    */
    public function renderChildProjectsMetaBox($post)
    {
      $childs = get_posts(array( 'numberposts'     => -1,
                                'offset'          => 0,
                                'orderby'         => 'title',
                                'order'           => 'ASC',
                                'post_parent'     => $post->ID,
                                'post_type'       => array(YOG_POST_TYPE_NBTY, YOG_POST_TYPE_BBTY, YOG_POST_TYPE_NBBN, YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG),
                                'post_status'     => array('publish', 'pending', 'trash', 'draft', 'auto-draft', 'future', 'private')));

      $thumbnailWidth   = get_option('thumbnail_size_w', 0);
      $noImageHtml      = '<div class="no-image" style="width:' . $thumbnailWidth . 'px;"></div>';

      echo '<table class="wp-list-table widefat fixed posts">';
        echo '<thead>';
          echo '<tr>';
            echo '<th scope="col" style="width: ' . ($thumbnailWidth + 10) . 'px;"></th>';
            echo '<th scope="col" class="manage-column column-title">Titel</th>';
            echo '<th scope="col" class="manage-column column-title" style="width:65px;">Scenario</th>';
            echo '<th scope="col" class="manage-column column-state" style="width:85px;">Status</th>';
            echo '<th scope="col" class="manage-column column-dlm" style="width:150px;">Laatste wijziging</th>';
          echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        if (is_array($childs) && count($childs) > 0)
        {
          foreach ($childs as $child)
          {
            // Determine thumbnail
            $thumbnail = get_the_post_thumbnail($child->ID, 'thumbnail');
            if (empty($thumbnail))
              $thumbnail = $noImageHtml;

            // Determine scenario
            $scenario = yog_retrieveSpec('scenario', $child->ID);

            // Determine state
            switch ($child->post_status)
            {
              case 'publish':
                $state = __('Published');
                break;
              default:
                $state = __(ucfirst($child->post_status));
                break;
            }

            // Determine admin links
            $links = array();

            if ($child->post_status != 'trash')
            {
              $links[] = '<a href="' . esc_url(get_edit_post_link($child->ID)) . '">' . __('Edit') . '</a>';
            }
            else
            {
              $links[] = '<a href="' . esc_url($this->getUntrashUrl($child)) . '">' . __('Restore') . '</a>';
              $links[] = '<a href="' . esc_url(get_delete_post_link($child->ID, '', true)) . '">' . __('Delete Permanently') . '</a>';
            }

            if ($scenario != 'NBbn' && $child->post_status != 'trash')
              $links[] = '<a href="' . esc_url(get_permalink($child->ID)) . '">' . __('View') . '</a>';

            // Determine title
            $title = esc_html($child->post_title);
            if ($child->post_status != 'trash')
              $title = '<a href="' . esc_url(get_edit_post_link($child->ID)) . '">' . $title . '</a>';

            echo '<tr>';
              echo '<td class="thumbnail column-thumbnail">' . $thumbnail . '</td>';
              echo '<td>';
                echo '<strong>' . $title . '</strong>';
                echo '<div class="row-actions"><span>' . implode(' | </span><span>', $links) . '</span></div>';
              echo '</td>';
              echo '<td>' . esc_html($scenario) . '</td>';
              echo '<td>' . esc_html($state) . '</td>';
              echo '<td>' . esc_html(get_the_modified_date() . ' ' . get_the_modified_time()) . '</td>';
            echo '</tr>';
          }
        }
        else
        {
          echo ' <tr><td colspan="3">Geen gelinkte objecten gevonden</td></tr>';
        }
        echo '</tbody>';
      echo '</table>';
    }

    /**
    * @desc Get the untrash url of a post
    *
    * @param StdClass $post
    * @return string
    */
    private function getUntrashUrl($post)
    {
      $post_type_object = get_post_type_object($post->post_type);
      return wp_nonce_url(admin_url(sprintf($post_type_object->_edit_link . '&amp;action=untrash', $post->ID)), 'untrash-' . $post->post_type . '_' . $post->ID);
    }

    /**
    * @desc Render address meta box
    *
    * @param StdClass $post
    * @return void
    */
    public function renderAddressMetaBox($post)
    {
	    echo '<table class="form-table">';
	    echo $this->retrieveInputs($post->ID, array('Straat', 'Huisnummer', 'Postcode', 'Wijk', 'Buurt', 'Plaats', 'Gemeente', 'Provincie', 'Land'));
	    echo '</table>';
    }

    /**
    * @desc Render linked relations meta box
    *
    * @param StdClass $post
    * @return void
    */
    public function renderRelationsMetaBox($post)
    {
      $relations = yog_retrieveRelations();

      $html  = '<table class="form-table" id="yog-links-table">';
        $html .= '<tbody>';

        foreach ($relations as $role => $relation)
        {
          $html .= '<tr id="relation-' . $relation->ID . '">';
            $html .= '<td>' . $role . ':</td>';
            $html .= '<td><a href="' . esc_url(get_edit_post_link($relation->ID)) . '">' . esc_html($relation->post_title) . '</a></td>';
          $html .= '</tr>';
        }

        $html .= '</tbody>';
      $html .= '</table>';

      echo $html;
    }

    /**
    * @desc Redirect to parent post
    *
    * @param int $postId
    * @return void
    */
    public function redirectToParent($postId)
    {
      $post         = get_post($postId);
      $ancestorIds  = get_post_ancestors($post);
      if (!empty($ancestorIds))
      {
        $parentId = array_shift($ancestorIds);
        $link     = 'post.php?post=' . $parentId . '&action=edit';

        wp_redirect($link);
        exit;
      }
    }

    /**
    * @desc Add video handler
    *
    * @param void
    * @return void
    */
	  public function ajaxAddVideo()
	  {
		  $uuid       = 'zelftoegevoegd-' .time();
		  $postId     = (int) $_POST['post'];
      $postType   = get_post_type($postId);
		  $titel      = sanitize_text_field($_POST['titel']);
		  $serviceUri = sanitize_text_field($_POST['type']);
		  $url        = sanitize_text_field($_POST['url']);

			// Check current user
			if (!current_user_can('edit_posts', $postId))
				wp_die();

			// Check nonce
			if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'yog-object-video'))
				wp_die();

      if (strpos($url, 'http://') === false && strpos($url, 'https://') === false && strpos($url, 'ftp://') === false)
        $url = 'http://' . $url;

		  $videos = get_post_meta($postId, $postType . '_Videos',true);
		  $order  = 10;

		  if (!is_array($videos))
      {
			  $videos = array();
      }
		  else
      {
			  foreach ($videos as $videouuid => $video)
        {
				  if ($video['order'] >= $order)
					  $order = $video['order'] + 1;
			  }
		  }

		  $videos[$uuid] = array('uuid'                       => $uuid,
                            'videoereference_serviceuri'  => $serviceUri,
                            'title'                       => $titel,
                            'websiteurl'                  => $url,
                            'order'                       => $order);

		  update_post_meta($postId, $postType . '_Videos', $videos);

		  echo esc_html($uuid);
		  exit();
	  }

    /**
    * @desc Remove video handle
    *
    * @param void
    * @return void
    */
	  public function ajaxRemoveVideo()
	  {
      $uuid     = sanitize_text_field($_POST['uuid']);
      $postId   = (int) $_POST['post'];
      $postType = get_post_type($postId);

			// Check current user
			if (!current_user_can('edit_posts', $postId))
				wp_die();

			// Check nonce
			if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'yog-object-video'))
				wp_die();

		  $videos = get_post_meta($postId, $postType . '_Videos', true);
		  if (is_array($videos) && count($videos))
      {
        if (isset($videos[$uuid]))
			    unset($videos[$uuid]);

			  update_post_meta($postId, $postType . '_Videos', $videos);
		  }

		  echo esc_html($uuid);
		  exit();
	  }

    /**
    * @desc Add link handler
    *
    * @param void
    * @return void
    */
	  public function ajaxAddLink()
	  {
		  $uuid     = 'zelftoegevoegd-' .time();
		  $postId   = (int) $_POST['post'];
      $postType = get_post_type($postId);
		  $titel    = sanitize_text_field($_POST['titel']);
		  $type     = sanitize_text_field($_POST['type']);
      $url      = sanitize_text_field($_POST['url']);

			// Check current user
			if (!current_user_can('edit_posts', $postId))
				wp_die();

			// Check nonce
			if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'yog-object-link'))
				wp_die();

      if (strpos($url, 'http://') === false && strpos($url, 'https://') === false && strpos($url, 'ftp://') === false)
        $url = 'http://' . $url;

		  $links = get_post_meta($postId, $postType . '_Links', true);
		  if (!is_array($links))
			  $links = array();

		  $links[$uuid] = array('uuid' => $uuid,'type' => $type,'title' => $titel,'url' => $url);
		  update_post_meta($postId, $postType . '_Links', $links);

		  echo esc_html($uuid);
		  exit();
	  }

    /**
    * @desc Remove link handler
    *
    * @param void
    * @return void
    */
	  public function ajaxRemoveLink()
	  {
      $postId   = (int) $_POST['post'];
      $postType = get_post_type($postId);
      $uuid     = sanitize_text_field($_POST['uuid']);
		  $links    = get_post_meta($postId, $postType . '_Links', true);

			// Check current user
			if (!current_user_can('edit_posts', $postId))
				wp_die();

			// Check nonce
			if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'yog-object-link'))
				wp_die();

		  if (is_array($links) && count($links))
      {
        if (isset($links[$uuid]))
			    unset($links[$uuid]);

			  update_post_meta($postId, $postType . '_Links',$links);
		  }

		  echo esc_html($uuid);
		  exit();
	  }

    /**
    * @desc Add document handler
    *
    * @param void
    * @return void
    */
	  public function ajaxAddDocument()
	  {
		  $uuid       = 'zelftoegevoegd-' .time();
		  $postId     = (int) $_POST['post'];
      $postType   = get_post_type($postId);
		  $titel      = sanitize_text_field($_POST['titel']);
		  $type       = sanitize_text_field($_POST['type']);
		  $url        = sanitize_text_field($_POST['url']);

			// Check current user
			if (!current_user_can('edit_posts', $postId))
				wp_die();

			// Check nonce
			if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'yog-object-document'))
				wp_die();

      if (strpos($url, 'http://') === false && strpos($url, 'https://') === false && strpos($url, 'ftp://') === false)
        $url = 'http://' . $url;

		  $documenten = get_post_meta($postId, $postType . '_Documenten', true);
		  $order      = 10;

		  if(!is_array($documenten))
      {
			  $documenten = array();
      }
		  else
      {
			  foreach ($documenten as $uuid => $document)
        {
				  if($document['order'] >= $order)
					  $order = $document['order']+1;
			  }
		  }

		  $documenten[$uuid] = array( 'uuid'  => $uuid,
                                  'type'  => $type,
                                  'title' => $titel,
                                  'url'   => $url,
                                  'order' => $order);

		  update_post_meta($postId, $postType . '_Documenten', $documenten);
		  echo esc_html($uuid);
		  exit();
	  }

    /**
    * @desc Remove document handler
    *
    * @param void
    * @return void
    */
	  public function ajaxRemoveDocument()
	  {
      $postId   = (int) $_POST['post'];
      $postType = get_post_type($postId);
      $uuid     = sanitize_text_field($_POST['uuid']);

			// Check current user
			if (!current_user_can('edit_posts', $postId))
				wp_die();

			// Check nonce
			if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'yog-object-document'))
				wp_die();

		  $links = get_post_meta($postId, $postType . '_Documenten', true);

		  if (is_array($links) && count($links))
      {
        if (isset($links[$uuid]))
			    unset($links[$uuid]);

			  update_post_meta($postId, $postType . '_Documenten', $links);
		  }

		  echo esc_html($uuid);
		  exit();
	  }
  }