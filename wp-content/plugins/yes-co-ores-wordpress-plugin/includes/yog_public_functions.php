<?php
  require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_fields_settings.php');
  require_once(YOG_PLUGIN_DIR . '/includes/yog_private_functions.php');

  /**
  * @desc Check if post is an object
  *
  * @param int $postId (optional)
  * @return bool
  */
  function yog_isObject($postId = null)
  {
    if (is_null($postId))
      $postId = get_the_ID();

    $postType = get_post_type((int) $postId);

    return in_array($postType, array(YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG, YOG_POST_TYPE_NBPR, YOG_POST_TYPE_NBTY, YOG_POST_TYPE_NBBN, YOG_POST_TYPE_BBPR, YOG_POST_TYPE_BBTY, YOG_POST_TYPE_BOPR));
  }

  /**
  * @desc Get the address of an object
  *
  * @param int $postId (optional)
  * @return string
  */
  function yog_getAddress($postId = null)
  {
    $specs   = yog_retrieveSpecs(array('Straat', 'Huisnummer', 'Plaats'), $postId);

    return implode(' ', $specs);
  }

  /**
  * @desc Retrieve specs of an obect
  *
  * @param array specs
  * @param int $postId (optional)
  * @param bool $returnTitle (optional, return title as key instead of spec name, default true)
  * @return array
  */
  function yog_retrieveSpecs($specs, $postId = null, $returnTitle = true)
  {
    if (!is_array($specs))
      throw new Exception(__METHOD__ . '; Invalid specs provided, must be an array');

    if (is_null($postId))
      $postId = get_the_ID();
		else
			$postId = (int) $postId;

    $postType       = get_post_type($postId);
    $values         = array();

    if (!empty($postType) && in_array($postType, array(YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG, YOG_POST_TYPE_BOPR, YOG_POST_TYPE_NBPR, YOG_POST_TYPE_NBTY, YOG_POST_TYPE_NBBN, YOG_POST_TYPE_BBPR, YOG_POST_TYPE_BBTY, YOG_POST_TYPE_RELATION)))
    {
      $fieldsSettings = YogFieldsSettingsAbstract::create($postType);

      foreach ($specs as $spec)
      {
        $postMetaName = $postType . '_' . $spec;

        if (strpos($postMetaName, 'MinMax') !== false)
        {
          $minValue     = get_post_meta($postId, str_replace('Max', '', $postMetaName), true);
          $maxValue     = get_post_meta($postId, str_replace('Min', '', $postMetaName), true);
          $value        = '';

          if (!empty($minValue))
            $value .= number_format($minValue, 0, ',', '.');
          if (!empty($maxValue))
            $value .= ' t/m ' . number_format($maxValue, 0, ',', '.');

          if (!empty($value))
          {
            if ($fieldsSettings->containsField($postMetaName))
            {
              $settings = $fieldsSettings->getField($postMetaName);

              if (!empty($settings['type']))
              {
                switch ($settings['type'])
                {
                  case 'oppervlakte':
                    $value .= ' m&sup2;';
                    break;
                  case 'inhoud':
                    $value .= ' m&sup3;';
                    break;
                  case 'cm':
                    $value .= ' cm';
                    break;
                  case 'meter':
                    $value  .- ' m';
                    break;
                }
              }

              if (!empty($settings['title']) && $returnTitle !== false)
                $spec = $settings['title'];
            }

            $values[$spec] = $value;
          }
        }
        else if ($spec == 'ParentLink')
        {
          if (yog_hasParentObject($postId))
          {
            $parent = yog_retrieveParentObject($postId);
            $url    = get_permalink($parent->ID);
            $title  = get_the_title($parent->ID);

            if ($returnTitle !== false && $fieldsSettings->containsField($postMetaName))
            {
              $settings = $fieldsSettings->getField($postMetaName);
              if (!empty($settings['title']))
                $spec = $settings['title'];
            }

            $values[$spec] = '<a href="' . esc_url($url) . '" title="' . esc_attr($title) . '">' . esc_html($title) . '</a>';
          }
        }
				else if ($spec === 'Status')
				{
					$value = get_post_meta($postId, $postMetaName, true);

					if (!empty($value))
					{
						if (in_array(strtolower($value), ['verkocht onder voorbehoud', 'verhuurd onder voorbehoud']) && in_array($postType, [YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG]))
						{
							$voorbehoudDate = get_post_meta($postId, $postType . '_DatumVoorbehoudTot', true);
							if (!empty($voorbehoudDate) && strtotime($voorbehoudDate) < date('U'))
								$value = str_replace(' onder voorbehoud', '', $value);
						}
					}

					$values[$spec] = __($value, YOG_TRANSLATION_TEXT_DOMAIN);
				}
        else
        {
          $value = get_post_meta($postId, $postMetaName, true);

          if (!empty($value) && strlen(trim($value)) > 0)
          {
            $translatable = true;

            // Transform value
            if ($fieldsSettings->containsField($postMetaName))
            {
              $settings = $fieldsSettings->getField($postMetaName);

							// Check replacement meta value first, if not set use normal meta value
							if (!empty($settings['replacementMeta']))
							{
								$replacementValue = get_post_meta($postId, $settings['replacementMeta'], true);

								if (!empty($replacementValue))
									$value = $replacementValue;
							}

              if (!empty($settings['type']))
              {
                // Most setting types do not need to be translated
                $translatable = false;

                switch ($settings['type'])
                {
                  case 'oppervlakte':
                    $value = number_format($value, 0, ',', '.') . ' m&sup2;';
                    break;
                  case 'inhoud':
                    $value = number_format($value, 0, ',', '.') . ' m&sup3;';
                    break;
                  case 'cm':
                    $value = number_format($value, 0, ',', '.') . ' cm';
                    break;
									case 'cmToMeter':
										if ((int) $value > 100)
										{
											$value = ((int) $value) / 100;

											if ((int) $value == $value)
												$value = number_format($value, 0, ',', '.') . ' m';
											else
												$value = number_format($value, 2, ',', '.') . ' m';
										}
										else
										{
											$value = number_format($value, 0, ',', '.') . ' cm';
										}
										break;
                  case 'meter':
                    $value = number_format($value, 0, ',', '.') . ' m';
                    break;
                  case 'date':
                    $dateTime = new DateTime($value);
                    $value = $dateTime->format('d-m-Y');
                    break;
                  case 'price':
                  case 'priceBtw':

                    $value = '&euro; ' . number_format($value, 0, ',', '.') . ',-';

                    break;
                }
              }

              // Some fields can contain multiple values split by a comma
              if ($translatable === true && isset($settings['multipleSplitByComma']) && $settings['multipleSplitByComma'] === true && strpos($value, ',') !== false)
              {
                $translatedValues = [];

                foreach (explode(',', $value) as $currentValue)
                {
                  $translatedValues[] = __(trim($currentValue), YOG_TRANSLATION_TEXT_DOMAIN);
                }

                $value = implode(', ', $translatedValues);
              }

              // When an addition is set, the value also does not need to be translated
              if (!empty($settings['addition']))
              {
                $translatable = false;
                $value .= __($settings['addition'], YOG_TRANSLATION_TEXT_DOMAIN);
              }

              if (!empty($settings['title']) && $returnTitle !== false)
                $spec = $settings['title'];
            }

            if ($translatable === true)
             $values[$spec] = __($value, YOG_TRANSLATION_TEXT_DOMAIN);
            else
              $values[$spec] = $value;
          }
        }
      }
    }

    return $values;
  }

	function yog_retrieveDateTimeSpec($spec, $postId = null)
	{
		if (!in_array($spec, array('OpenHuisVan', 'OpenHuisTot')))
			throw new \Exception(__METHOD__ . '; Invalid date/time spec provided. Only OpenHuisVan and OpenHuisTot supported');

    if (is_null($postId))
      $postId = get_the_ID();
		else
			$postId = (int) $postId;

    $postType     = get_post_type($postId);
		$postMetaName	= $postType . '_' . $spec;
		$value				= get_post_meta($postId, $postMetaName, true);

		if (!empty($value))
			return new \DateTime($value);
	}

  /**
  * @desc Retrieve spec of an obect
  *
  * @param string $spec
  * @param int $postId (optional)
  * @return string
  */
  function yog_retrieveSpec($spec, $postId = null)
  {
    if (!is_string($spec) || strlen(trim($spec)) == 0)
      throw new Exception(__METHOD__ . '; Invalid spec, must be a non empty string');

    $values = yog_retrieveSpecs(array($spec), $postId);

    return array_shift($values);
  }

  /**
  * @desc Retrieve project prices
  *
  * @param string $priceTypeClass (default: priceType)
  * @param string $priceConditionClass (default: priceCondition)
  * @param int $postId (optional)
  * @param string $labelElem (optional, default span)
  * @param string $valueElem (optional, default none)
  * @return array
  */
  function yog_retrievePrices($priceTypeClass = 'priceType', $priceConditionClass = 'priceCondition', $postId = null, $labelElem = 'span', $valueElem = '')
  {
    $values         = array();
    $postType       = get_post_type(is_null($postId) ? null : (int) $postId);

    if (!empty($priceTypeClass) && empty($labelElem))
      $labelElem = 'span';

    $labelElemStart = empty($labelElem) ? '' : '<' . $labelElem . (empty($priceTypeClass) ? '' : ' class="' . $priceTypeClass . '"') . '>';
    $labelElemEnd   = empty($labelElem) ? '' : '</' . $labelElem . '>';
    $valueElemStart = empty($valueElem) ? ' ' : '<' . $valueElem . '>';
    $valueElemEnd   = empty($valueElem) ? '' : '</' . $valueElem . '>';

    switch ($postType)
    {
      case YOG_POST_TYPE_NBPR:
        $priceMinMaxTypes = array('KoopAanneemSom' => 'Aanneemsom', 'HuurPrijs' => 'Huurprijs');
        break;
      case YOG_POST_TYPE_NBTY:
      case YOG_POST_TYPE_BBPR:
      case YOG_POST_TYPE_BBTY:
      case YOG_POST_TYPE_BOPR:
        $priceMinMaxTypes = array('KoopPrijs' => 'Koopprijs', 'HuurPrijs' => 'Huurprijs');
        break;
			case YOG_POST_TYPE_BOG:
				$postId						= is_null($postId) ? get_the_ID() : $postId;
				$rentalValue			= get_post_meta($postId, 'bedrijf_HuurPrijs', true);

				if ($rentalValue == '')
				{
					$priceFields      = array('KoopPrijs');
					$priceMinMaxTypes = array('HuurPrijs' => 'Huurprijs');
				}
				else
				{
					$priceFields      = array('KoopPrijs', 'HuurPrijs');
				}
							//yog_retrieveSpecs(array('HuurPrijsVan', 'HuurPrijsTot'), $postId, false);
				break;
      default:
        $priceFields      = array('KoopPrijs', 'HuurPrijs');
        break;
    }

    if (!empty($priceFields))
    {
      foreach ($priceFields as $field)
      {
        $replace        = yog_retrieveSpec($field . 'Vervanging', $postId);
        $priceType      = ($field == 'HuurPrijs') ? 'Huurprijs' : yog_retrieveSpec($field . 'Soort', $postId);

        if (empty($priceType))
          $priceType = 'Vraagprijs';

        $price          = yog_retrieveSpec($field, $postId);

        // Some realtors synchronise a value of 1,- to Funda, replace it with prijs op aanvraag in case it occurs
        if (empty($replace) && (preg_match('/ 1,\-/', $price) || preg_match('/99\.999\.999,\-/', $price)))
          $replace = 'op aanvraag';

        if (empty($replace))
        {
          if (!empty($price))
          {
            $priceCondition = yog_retrieveSpec($field . 'Conditie', $postId);
            $value = $labelElemStart . esc_html(__($priceType, YOG_TRANSLATION_TEXT_DOMAIN)) . ': ' . $labelElemEnd . $valueElemStart . esc_html($price) . (empty($priceCondition) ? '' : ' <span class="' . esc_attr($priceConditionClass) . '">' . esc_html($priceCondition) . '</span>');

            if ($postType == YOG_POST_TYPE_BOG)
            {
              $btw = yog_retrieveSpec($field . 'BtwPercentage', $postId);
              if (!empty($btw))
                $value .= ' <span class="priceBtw">(' . esc_html($btw) . '% BTW)</span>';
            }

            $values[] = $value . $valueElemEnd;
          }
        }
        else
        {
          $values[] = $labelElemStart . esc_html(__($priceType, YOG_TRANSLATION_TEXT_DOMAIN)) . ': ' . $labelElemEnd . $valueElemStart . $replace . $valueElemEnd;
        }
      }
    }

    if (!empty($priceMinMaxTypes))
    {
      foreach ($priceMinMaxTypes as $priceType => $label)
      {
				$replace  = yog_retrieveSpec($priceType . 'Vervanging', $postId);

				if (empty($replace))
				{
					$minField = $priceType . 'Min';
					$maxField = $priceType . 'Max';

					$min      = yog_retrieveSpec($minField, $postId);
					$max      = yog_retrieveSpec($maxField, $postId);
					$value    = '';

					if (!empty($min) && !empty($max))
						$value = $min . ' t/m ' . $max;
					else if (!empty($min) && empty($max))
						$value = 'vanaf ' . $min;
					else if (!empty($max))
						$value = 't/m ' . $max;

					if (!empty($value))
					{
						$priceCondition = yog_retrieveSpec($priceType . 'Conditie', $postId);
						if (!empty($priceCondition))
							$value .= ' <span class="' . esc_attr($priceConditionClass) . '">' . esc_html($priceCondition) . '</span>';

						$values[] = $labelElemStart . __($label, 'yes-co-ores-wordpress-plugin') . ': ' . $labelElemEnd . $valueElemStart . $value . $valueElemEnd;
					}
				}
				else
				{
					$values[] = $labelElemStart . __($label, 'yes-co-ores-wordpress-plugin') . ': ' . $labelElemEnd . $valueElemStart . $replace . $valueElemEnd;
				}
      }
    }

    return $values;
  }

  /**
  * @desc Check if object has a parent object
  *
  * @param $postId (optional, default: ID of current post)
  * @return bool
  */
  function yog_hasParentObject($postId = null)
  {
    $ancestorIds = get_post_ancestors($postId);
    return (is_array($ancestorIds) && count($ancestorIds) > 0);
  }

  /**
  * @desc Get the parent object id
  *
  * @param $postId (optional, default: ID of current post)
  * @return mixed (integer parent object id or false)
  */
  function yog_getParentObjectId($postId = null)
  {
    $ancestorIds = get_post_ancestors($postId);

    if (is_array($ancestorIds) && count($ancestorIds) > 0)
    {
      $parentId = array_shift($ancestorIds);
      return (int) $parentId;
    }

    return false;
  }

  /**
  * @desc Retrieve the parent object
  *
  * @param $postId (optional, default: ID of current post)
  * @return mixed (integer parent object or false)
  */
  function yog_retrieveParentObject($postId = null)
  {
    $parentId = yog_getParentObjectId($postId);
    if ($parentId !== false)
      return get_post($parentId);

    return false;
  }

  /**
  * @desc Check if object has children
  *
  * @param $postId (optional, default: ID of current post)
  * @return bool
  */
  function yog_hasChildObjects($postId = null)
  {
	  if (is_null($postId))
		  $postId = get_the_ID();

    $childs = get_posts(array('numberposts'     => 1,
                              'offset'          => 0,
                              'post_parent'     => (int) $postId,
                              'post_type'       => array(YOG_POST_TYPE_NBTY, YOG_POST_TYPE_BBTY, YOG_POST_TYPE_NBBN, YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG),
                              'post_status'     => array('publish')));

    return (is_array($childs) && count($childs) > 0);
  }

  /**
  * @desc Get the child objects
  *
  * @param $postId (optional, default: ID of current post)
  * @return array
  */
  function yog_retrieveChildObjects($postId = null)
  {
	  if (is_null($postId))
		  $postId = get_the_ID();

    return get_posts(array( 'numberposts'     => -1,
                            'offset'          => 0,
                            'orderby'         => 'title',
                            'order'           => 'ASC',
                            'post_parent'     => (int) $postId,
                            'post_type'       => array(YOG_POST_TYPE_NBTY, YOG_POST_TYPE_BBTY, YOG_POST_TYPE_NBBN, YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG),
                            'post_status'     => array('publish')));
  }

  /**
  * @desc Get the child NBbn objects
  *
  * @param $postId (optional, default: ID of current post)
  * @return array
  */
  function yog_retrieveChildNBbnObjects($postId = null)
  {
	  if (is_null($postId))
		  $postId = get_the_ID();

    return get_posts(array( 'numberposts'     => -1,
                            'offset'          => 0,
                            'orderby'         => 'title',
                            'order'           => 'ASC',
                            'post_parent'     => (int) $postId,
                            'post_type'       => array(YOG_POST_TYPE_NBBN),
                            'post_status'     => array('publish')));
  }

  /**
  * @desc Get HTML for a table with all NBbn objects
  *
  * @param $postId (optional, default: ID of current post)
  * @return string
  */
  function yog_retrieveNbbnTable($postId = null)
  {
    $childs = yog_retrieveChildNBbnObjects();
    $html   = '';

    if (is_array($childs) && count($childs) > 0)
    {
      $html .= '<table class="yog-nbbn-table sorttable">';
        $html .= '<thead>';
          $html .= '<tr>';
            $html .= '<th class="yog-nbbn-bouwnr">Bouwnummer</th>';
            $html .= '<th class="yog-nbbn-woonopp">Woon opp.</th>';
            $html .= '<th class="yog-nbbn-perceelopp">Perceel opp.</th>';
            $html .= '<th class="yog-nbbn-grondprijs">Grond prijs</th>';
            $html .= '<th class="yog-nbbn-aanneemsom">Aanneemsom</th>';
            $html .= '<th class="yog-nbbn-koopaanneemsom">Koop aanneemsom</th>';
            $html .= '<th class="yog-nbbn-status">Status</th>';
          $html .= '</tr>';
        $html .= '<thead>';
        $html .= '<tbody>';

        foreach ($childs as $child)
        {
          $specs  = yog_retrieveSpecs(array('Naam', 'WoonOppervlakte', 'PerceelOppervlakte', 'GrondPrijs', 'AanneemSom', 'KoopAanneemSom', 'Status'), $child->ID);

          $name   = '';
          if (!empty($specs['Titel van object']) && strpos($specs['Titel van object'], '/') !== false)
          {
            $nameParts  = explode('/', $specs['Titel van object']);
            $name       = array_pop($nameParts);
          }

          $html .= '<tr>';
            $html .= '<td class="yog-nbbn-bouwnr">' . esc_html($name) . '</td>';
            $html .= '<td class="yog-nbbn-woonopp">' . (empty($specs['Woon oppervlakte']) ? '' : $specs['Woon oppervlakte']) . '</td>';
            $html .= '<td class="yog-nbbn-perceelopp">' . (empty($specs['Perceel oppervlakte']) ? '' : $specs['Perceel oppervlakte']) . '</td>';
            $html .= '<td class="yog-nbbn-grondprijs">' . (empty($specs['Grond prijs']) ? '' : $specs['Grond prijs']) . '</td>';
            $html .= '<td class="yog-nbbn-aanneemsom">' . (empty($specs['Aanneemsom']) ? '' : $specs['Aanneemsom']) . '</td>';
            $html .= '<td class="yog-nbbn-koopaanneemsom">' . (empty($specs['Koop aanneemsom']) ? '' : $specs['Koop aanneemsom']) . '</td>';
            $html .= '<td class="yog-nbbn-status">' . (empty($specs['Status']) ? '' : $specs['Status']) . '</td>';
          $html .= '</tr>';
        }

        $html .= '</tbody>';
      $html .= '</table>';
    }

    return $html;
  }

  /**
   * @desc Retrieve relations with an employee status
   *
   * @param void
   * @return array
   */
  function yog_retrieveEmployees()
  {
    $limit = null;

    $arguments = array('post_type'        => YOG_POST_TYPE_RELATION,
      'numberposts'     => (is_null($limit) ? -1 : $limit),
      'meta_key'        => 'relatie_subtype',
      'meta_value'      => 'employee',
      'orderby'         => 'menu_order',
      'order'           => 'ASC');

    $posts  = get_posts($arguments);

    return $posts;
  }

  /**
  * @desc Retrieve linked relations
  *
  * @param $postId (optional, default: ID of current post)
  * @return array
  */
  function yog_retrieveRelations($postId = null)
  {
	  if (is_null($postId))
		  $postId   = get_the_ID();
		else
			$postId		= (int) $postId;

    $postType   = get_post_type($postId);

    $relations      = get_post_meta($postId, $postType . '_Relaties',true);
    $relationPosts  = array();

    if (!empty($relations))
    {
	    foreach ($relations as $uuid => $relation)
	    {
	      $relationId = (int) $relation['postId'];
	      $role       = $relation['rol'];

	      $relationPosts[$role] = get_post($relationId);
	    }
    }

    return $relationPosts;
  }

  /**
  * @desc Retrieve linked relation with a specific role
  *
  * @param string $role
  * @param $postId (optional, default: ID of current post)
  * @return mixed (WP_Post or null)
  */
  function yog_retrieveRelationByRole($role, $postId = null)
  {
    if (!is_string($role) || strlen(trim($role)) == 0)
      throw new \Exception(__METHOD__ . '; Invalid role, must be a non empty string');

	  if (is_null($postId))
		  $postId   = get_the_ID();
		else
			$postId		= (int) $postId;

    $postType   = get_post_type($postId);

    $relations      = get_post_meta($postId, $postType . '_Relaties',true);

    if (!empty($relations))
    {
	    foreach ($relations as $uuid => $relation)
	    {
        if ($relation['rol'] == $role)
        {
          $relationId = (int) $relation['postId'];
          return get_post($relationId);
        }
	    }
    }
  }

  /**
  * @desc Retrieve links for a post
  *
  * @param $postId (optional, default: ID of current post)
  * @return array
  */
  function yog_retrieveLinks($postId = null)
  {
	  if (is_null($postId))
		  $postId = get_the_ID();
		else
			$postId = (int) $postId;

    $postType = get_post_type($postId);

	  $links    = get_post_meta($postId, $postType . '_Links',true);
	  return $links;
  }

  /**
  * @desc Retrieve documents for a post
  *
  * @param $postId (optional, default: ID of current post)
  * @return array
  */
  function yog_retrieveDocuments($postId = null)
  {
	  if (is_null($postId))
		  $postId   = get_the_ID();
		else
			$postId		= (int) $postId;

    $postType   = get_post_type($postId);
	  $documenten = get_post_meta($postId, $postType . '_Documenten',true);
	  return $documenten;
  }

  /**
  * @desc Retrieve movies for a post
  *
  * @param $postId (optional, default: ID of current post)
  * @return array
  */
  function yog_retrieveMovies($postId = null)
  {
    if (is_null($postId))
		  $postId = get_the_ID();
		else
			$postId = (int) $postId;

    $postType = get_post_type($postId);
	  $videos   = get_post_meta($postId, $postType . '_Videos',true);

    if (!empty($videos))
    {
      foreach ($videos as $uuid => $video)
      {
        $videos[$uuid]['type'] = 'other';

        if (!empty($video['videoereference_serviceuri']))
        {
          switch ($video['videoereference_serviceuri'])
          {
            case 'http://www.youtube.com/':
            case 'http://www.youtube.com':

              $videos[$uuid]['type']                        = 'youtube';
              $videos[$uuid]['videoereference_serviceuri']  = 'http://www.youtube.com';

              if (empty($videos[$uuid]['videoereference_id']) && !empty($videos[$uuid]['websiteurl']))
              {
                $chunks = @parse_url($videos[$uuid]['websiteurl'], PHP_URL_QUERY);
                if (!empty($chunks))
                {
                  parse_str($chunks, $params);
                  if (!empty($params['v']))
                    $videos[$uuid]['videoereference_id'] = $params['v'];
                }
              }

              if (!empty($videos[$uuid]['videoereference_id']))
              {
                $videos[$uuid]['websiteurl']      = 'https://www.youtube.com/watch?v=' . $videos[$uuid]['videoereference_id'];
                $videos[$uuid]['videostreamurl']  = 'https://www.youtube.com/embed/' . $videos[$uuid]['videoereference_id'];
              }

              break;
            case 'http://vimeo.com/':
            case 'http://vimeo.com':

              $videos[$uuid]['type']                        = 'vimeo';
              $videos[$uuid]['videoereference_serviceuri']  = 'http://vimeo.com';

              if (!empty($videos[$uuid]['videoereference_id']))
              {
                $videos[$uuid]['websiteurl']      = 'https://vimeo.com/' . $videos[$uuid]['videoereference_id'];
                $videos[$uuid]['videostreamurl']  = 'https://player.vimeo.com/video/' . $videos[$uuid]['videoereference_id'];
              }

              break;
            case 'http://www.flickr.com/':
            case 'http://www.flickr.com':

              $videos[$uuid]['type']                        = 'flickr';
              $videos[$uuid]['videoereference_serviceuri']  = 'http://www.flickr.com';
              break;
          }
        }
      }
    }

	  return $videos;
  }

  /**
  * @desc Retrieve embeded movies
  *
  * @param $postId (optional, default: ID of current post)
  * @return array
  */
  function yog_retrieveEmbedMovies($postId = null)
  {
	  $movies       = yog_retrieveMovies($postId);
    $embedMovies  = array();

    if (!empty($movies))
    {
      foreach ($movies as $uuid => $movie)
      {
        if (!empty($movie['videostreamurl']) && !empty($movie['videoereference_serviceuri']))
        {
          $embedMovies[$uuid] = $movie;
        }
      }
    }

	  return $embedMovies;
  }

  /**
  * @desc Retrieve non-embeded movies
  *
  * @param int $postId (optional, default: ID of current post)
  * @return array
  */
  function yog_retrieveExternalMovies($postId = null)
  {
	  $movies         = yog_retrieveMovies($postId);

    $externalMovies = array();

    if (!empty($movies))
    {
      foreach ($movies as $uuid => $movie)
      {
        if (empty($movie['videostreamurl']) || empty($movie['videoereference_serviceuri']))
        {
          $externalMovies[$uuid] = $movie;
        }
      }
    }

    return $externalMovies;
  }

  /**
  * Get embed code fot a specific movie
  *
  * @param array $movie
  * @param int $width
  * @param int $height
  * @return string
  */
  function yog_getMovieEmbedCode($movie, $width, $height, $class = null)
  {
    $code = '';

    // Determine embed code
    if (is_array($movie) && !empty($movie['videoereference_serviceuri']) && !empty($movie['videostreamurl']))
    {
      switch ($movie['videoereference_serviceuri'])
      {
        case 'http://www.youtube.com':
          $code = '<iframe class="youtube-player" type="text/html" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" src="' . esc_url($movie['videostreamurl']) . '" allowfullscreen frameborder="0"' . (empty($class) ? ' class="' . esc_attr($class) . '"' : '') . '></iframe>';
          break;
        case 'http://vimeo.com':
          $code = '<iframe src="' . esc_url($movie['videostreamurl']) . '" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen'  . (empty($class) ? ' class="' . esc_attr($class) . '"' : '') . '></iframe>';
          break;
        default:
          $code = '<iframe src="' . esc_url($movie['videostreamurl']) . '" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" frameborder="0"'  . (empty($class) ? ' class="' . esc_attr($class) . '"' : '') . '></iframe>';
          break;
      }
    }

    return $code;
  }

  /**
  * @desc Retrieve dossier items
  *
  * @param int $limit
  * @param int $postId (optional)
  * @return array
  */
  function yog_retrieveDossierItems($limit = null, $postId = null)
  {
    if (is_null($postId))
      $postId = get_the_ID();
		else
			$postId = (int) $postId;

    $items      = array();
    $mimeTypes  = array_filter(explode(';', get_option('yog_dossier_mimetypes', 'application/pdf')));

    if (is_array($mimeTypes) && count($mimeTypes) > 0)
    {
      $arguments = array('post_type'        => 'attachment',
                          'post_parent'     => $postId,
                          'post_mime_type'  => $mimeTypes,
                          'numberposts'     => (is_null($limit) ? -1 : $limit),
                          'orderby'         => 'title',
                          'order'           => 'ASC');

      $posts  = get_posts($arguments);

      foreach ($posts as $post)
      {
        $item = array('url'       => wp_get_attachment_url($post->ID),
                      'title'     => $post->post_title,
                      'mime_type' => $post->post_mime_type);

        $items[] = $item;
      }
    }

    return $items;
  }

  /**
  * @desc Check if an open house route is set (and in future)
  *
  * @param int $postId (optional)
  * @return bool
  */
  function yog_hasOpenHouse($postId = null)
  {
    $openHouseStart = yog_retrieveDateTimeSpec('OpenHuisVan', $postId);
    if (!empty($openHouseStart))
    {
      $openHouseEnd   = yog_retrieveDateTimeSpec('OpenHuisTot', $postId);

      if (empty($openHouseEnd))
        $openHouseEnd = $openHouseStart;

      return ($openHouseEnd->format('U') >= current_time('timestamp'));
    }

    return false;
  }

  /**
  * @desc Get the open house date
  *
  * @param string $label (default: Open huis)
  * @param int $postId (optional)
  * @return string
  */
  function yog_getOpenHouse($label = 'Open huis', $postId = null)
  {
    $openHouse = '';
    if (yog_hasOpenHouse($postId))
    {
      $openHouseStart = yog_retrieveDateTimeSpec('OpenHuisVan', $postId);
      return '<span class="label">' . esc_html($label) . ': </span>' . esc_html($openHouseStart->format('d-m-Y'));
    }

    return $openHouse;
  }

  /**
  * @desc Retrieve HTML for the main image
  *
  * @param string $size (thumbnail, medium, large)
  * @param int $postId (optional)
  * @return array
  */
  function yog_retrieveMainImage($size, $postId = null)
  {
    if (is_null($postId))
      $postId = get_the_ID();
		else
			$postId = (int) $postId;

    $html = get_the_post_thumbnail($postId, $size);

    // Fallback when no post thumbnail is set
    if (empty($html))
    {
      $images = yog_retrieveImages($size, 1, $postId);
      if (!empty($images) && is_array($images) && count($images) > 0)
      {
        $image = $images[0];
        $html = '<img width="' . esc_attr($image[1]) . '" height="' . esc_attr($image[2]) . '" src="' . esc_url($image[0]) . '" class="attachment-thumbnail wp-post-image" alt=""  />';
      }
    }

    return $html;
  }

  /**
  * @desc Retrieve images
  *
  * @param string $size (thumbnail, medium, large)
  * @param int $limit
  * @param int $postId (optional)
  * @return array
  */
  function yog_retrieveImages($size, $limit = null, $postId = null)
  {
    if (is_null($postId))
      $postId = get_the_ID();
		else
			$postId = (int) $postId;

    $arguments = array('post_type'        => 'attachment',
                        'post_parent'     => $postId,
                        'post_mime_type'  => 'image',
                        'numberposts'     => (is_null($limit) ? -1 : $limit),
                        'orderby'         => 'menu_order',
                        'order'           => 'ASC');

    $posts  = get_posts($arguments);
    $images = array();

    foreach ($posts as $post)
    {
      $image    = wp_get_attachment_image_src($post->ID, $size);
      if (empty($image[1]))
        $image[1] = get_option($size . '_size_w', 0);
      if (empty($image[2]))
        $image[2] = get_option($size . '_size_h', 0);

      $images[] = $image;
    }

    return $images;
  }

  /**
  * @desc Check if there are images without type 'Plattegrond'
  *
  * @param int $postId (optional)
  * @return bool
  */
  function yog_hasNormalImages($postId = null)
  {
  	if (is_null($postId))
  		$postId = get_the_ID();
		else
			$postId = (int) $postId;

    $found      = false;
    $arguments  = array('post_type'        => 'attachment',
                        'post_parent'     => $postId,
                        'post_mime_type'  => 'image');

    $images     = get_posts($arguments);

    while ($found === false && $image = array_pop($images))
    {
      $type = get_post_meta($images->ID, 'attachment_type', true);
      if ($type != 'Plattegrond')
        $found = true;
    }

    return $found;
  }

  /**
  * @desc Retrieve all images without type 'Plattegrond'
  *
  * @param string $size (thumbnail, medium, large)
  * @param int $limit
  * @param int $postId (optional)
  * @return array
  */
  function yog_retrieveNormalImages($size, $limit = null, $postId = null)
  {
    if (is_null($postId))
      $postId = get_the_ID();
		else
			$postId = (int) $postId;

    $arguments = array('post_type'        => 'attachment',
                        'post_parent'     => $postId,
                        'post_mime_type'  => 'image',
                        'numberposts'     => (is_null($limit) ? -1 : $limit),
                        'orderby'         => 'menu_order',
                        'order'           => 'ASC');

    $posts  = get_posts($arguments);
    $images = array();

    foreach ($posts as $post)
    {
      $type     = get_post_meta($post->ID, 'attachment_type', true);
      if ($type != 'Plattegrond' && (is_null($limit) || count($images) < $limit))
      {
        $image    = wp_get_attachment_image_src($post->ID, $size);
        if (empty($image[1]))
          $image[1] = get_option($size . '_size_w', 0);
        if (empty($image[2]))
          $image[2] = get_option($size . '_size_h', 0);

        $images[] = $image;
      }
    }

    return $images;
  }

  /**
  * @desc Check if there are images with type 'Plattegrond'
  *
  * @param int $postId (optional)
  * @return bool
  */
  function yog_hasImagePlans($postId = null)
  {
    if (is_null($postId))
      $postId = get_the_ID();
		else
			$postId = (int) $postId;

    $arguments = array('post_type'        => 'attachment',
                        'post_parent'     => $postId,
                        'post_mime_type'  => 'image',
                        'meta_key'        => 'attachment_type',
                        'meta_value'      => 'Plattegrond',
                        'numberposts'     => 1);

    $posts  = get_posts($arguments);
    return (is_array($posts) && count($posts) > 0);
  }

  /**
  * @desc Retrieve all images with type 'Plattegrond'
  *
  * @param string $size (thumbnail, medium, large)
  * @param int $limit
  * @param int $postId (optional)
  * @return array
  */
  function yog_retrieveImagePlans($size, $limit = null, $postId = null)
  {
    if (is_null($postId))
      $postId = get_the_ID();
		else
			$postId = (int) $postId;

    $arguments = array('post_type'        => 'attachment',
                        'post_parent'     => $postId,
                        'post_mime_type'  => 'image',
                        'meta_key'        => 'attachment_type',
                        'meta_value'      => 'Plattegrond',
                        'numberposts'     => (is_null($limit) ? -1 : $limit),
                        'orderby'         => 'menu_order',
                        'order'           => 'ASC');

    $posts  = get_posts($arguments);
    $images = array();

    foreach ($posts as $post)
    {
      $image    = wp_get_attachment_image_src($post->ID, $size);
      if (empty($image[1]))
        $image[1] = get_option($size . '_size_w', 0);
      if (empty($image[2]))
        $image[2] = get_option($size . '_size_h', 0);

      $images[] = $image;
    }

    return $images;
  }

  /**
  * @desc Check if geo location is set
  *
  * @param int $postId (optional)
  * @return bool
  */
  function yog_hasLocation($postId = null)
  {
    $specs = yog_retrieveSpecs(array('Latitude', 'Longitude'), $postId);
    return (!empty($specs['Latitude']) && !empty($specs['Longitude']));
  }

  /**
   * @desc function that generates a static map image tag
	 * The Google Maps API key should have access to the 'Maps Static API'
   *
   * @param string $mapType (optional, default hybrid)
   * @param integer $zoomLevel (optional, default 18)
   * @param integer width (optional, default 486)
   * @param integer height (optional, default 400)
   * @param int $postId (optional)
   * @return html
   */
  function yog_retrieveStaticMap($mapType = 'hybrid', $zoomLevel = 18, $width = 486, $height = 400, $postId = null)
  {
		if (!in_array($mapType, array('roadmap', 'satellite', 'terrain', 'hybrid')))
			throw new \InvalidArgumentException(__METHOD__ . '; Invalid map type');

		$specs      = yog_retrieveSpecs(array('Latitude', 'Longitude'), $postId);

    $latitude					= isset($specs['Latitude']) ? $specs['Latitude'] : false;
    $longitude				= isset($specs['Longitude']) ? $specs['Longitude'] : false;
		$googleMapsApiKey = get_option('yog_google_maps_api_key');

    $html       = '';

    if ($latitude !== false && $longitude !== false && !empty($googleMapsApiKey))
    {
			// Make sure the width/height is not above 640px
			if ($width > 640)
				$width = 640;

			if ($height > 640)
				$height = 640;

			// Determine params
			$params = array(
				'center'	=> $latitude . ',' . $longitude,
				'zoom'		=> $zoomLevel,
				'size'		=> (int) $width . 'x' . (int) $height,
				'maptype'	=> $mapType,
				'key'			=> $googleMapsApiKey,
				'markers'	=> $latitude . ',' . $longitude
			);

			// Generate image tag
			$html .= '<img alt="" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" src="' . esc_url('https://maps.google.com/maps/api/staticmap?' . http_build_query($params, '', '&amp;')) . '" />';
    }

    return $html;
  }

  /**
   * @desc function that generates a dynamic map
   *
   * @param string $mapType (optional, default hybrid)
   * @param integer $zoomLevel (optional, default 18)
   * @param integer width (optional, default 486)
   * @param integer height (optional, default 400)
   * @param string $extraAfterOnLoad (optional, default empty string)
   * @param bool $adminMode (optional, default false)
   * @param int|null $postId (optional)
   * @return string
   */
  function yog_retrieveDynamicMap($mapType = 'hybrid', $zoomLevel = 18, $width = 486, $height = 400, $extraAfterOnLoad = '', $adminMode = false, $postId = null)
  {
		if (is_null($postId))
			$postId   = get_the_ID();
		else
			$postId		= (int) $postId;

    // Retrieve lat/long
    $specs      = yog_retrieveSpecs(array('Latitude', 'Longitude'), $postId);
    $latitude   = isset($specs['Latitude']) ? $specs['Latitude'] : false;
    $longitude  = isset($specs['Longitude']) ? $specs['Longitude'] : false;

    if ($adminMode === true && $latitude === false && $longitude === false)
    {
      $latitude   = 52.06758749919184;
      $longitude  = 5.34619140625;
    }

    $mapTypeSetting = get_option('yog_map_type', 'google-maps');

    if ($mapTypeSetting === 'open-street-map')
      return yog_retrieveDynamicMap_openStreetMap($postId, $latitude, $longitude, $zoomLevel, $width, $height, $extraAfterOnLoad, $adminMode);
    else
      return yog_retrieveDynamicMap_googleMaps($postId, $latitude, $longitude, $mapType, $zoomLevel, $width, $height, $extraAfterOnLoad, $adminMode);
  }

  /**
   * @desc Method which generates a photo slider and main image
   *
   * @param string $largeImageSize (optional; thumbnail, medium, large. default: large)
   * @param string $thumbnailSize (optional; thumbnail, medium, large. default: thumbnail)
   * @param bool $scrollable (optional, default false)
   * @param string $type (optional; Plattegrond, Normaal or null)
   * @param int $postId (optional)
   * @return string
   */
  function yog_retrievePhotoSlider($largeImageSize = 'large', $thumbnailSize = 'thumbnail', $scrollable = false, $type = null, $postId = null)
  {
    if ($type == 'Plattegrond')
      $largeImages      = yog_retrieveImagePlans($largeImageSize);
    else if ($type == 'Normaal')
      $largeImages      = yog_retrieveNormalImages($largeImageSize);
    else
      $largeImages      = yog_retrieveImages($largeImageSize);

    if ($type == 'Plattegrond')
      $thumbnails       = yog_retrieveImagePlans($thumbnailSize);
    else if ($type == 'Normaal')
      $thumbnails       = yog_retrieveNormalImages($thumbnailSize);
    else
      $thumbnails       = yog_retrieveImages($thumbnailSize);

    $largeImageHeight   = get_option($largeImageSize . '_size_h');
    $largeImageWidth    = get_option($largeImageSize . '_size_w');

    $thumbs             = array();
    $html               = '';
    $scrollable         = true;

    if (!empty($largeImages) && count($largeImages) > 0 && !empty($largeImages[0][0]))
    {
			// Enqueue scripts / css
      $minifyExtension = (YOG_DEBUG_MODE === true) ? '' : '.min';
      wp_enqueue_script('yog-image-slider', YOG_PLUGIN_URL .'/inc/js/image_slider' . $minifyExtension . '.js', array('jquery'), YOG_PLUGIN_VERSION, true);
      wp_enqueue_style('yog-photo-slider',  YOG_PLUGIN_URL . '/inc/css/photo_slider.css', array(), YOG_PLUGIN_VERSION);

      $html = '<div class="yog-images-holder">
                <div id="imageactionsholder" class="clearfix yog-main-">
                   <div class="mainimage" style="height:' . esc_attr($largeImageHeight) .'px;">
                     <img class="yog-big-image" id="bigImage" alt="" src="' . esc_url($largeImages[0][0]) . '" style="max-height:' . esc_attr($largeImageHeight) . 'px;max-width:' . esc_attr($largeImageWidth) . 'px;" />
                   </div>
                 </div>';

      if (!empty($thumbnails) && count($thumbnails) > 1 && count($thumbnails) == count($largeImages))
      {
        $thumbnailsHtml = '';
        foreach ($thumbnails as $key => $thumbnail)
        {
          $largeImage      = $largeImages[$key];
          $thumbnailsHtml .= '<a href="' . esc_url($largeImage[0]) . '" class="yog-thumb"><img class="yog-image-' . esc_attr($key) . '" alt="" src="' . esc_url($thumbnail[0]) . '" /></a>';
        }

        $html .= '<div id="imgsliderholder" class="yog-image-slider-holder' .($scrollable === true ? ' yog-scrolling-enabled' : '') . '">';
        if ($scrollable === true)
          $html .= '<div class="left yog-scroll"><a title="Vorige foto" onclick="return false;" href="#">&nbsp;</a></div>';
        if ($scrollable === true)
          $html .= '<div class="right yog-scroll"><a title="Volgende foto" onclick="return false;" href="#">&nbsp;</a></div>';

        $html .= '<div id="imgslider" class="yog-image-slider">
                    <div id="slider-container">' . $thumbnailsHtml . '</div>
                  </div>';

        $html .= '</div>';
      }

      $html .= '</div>';
    }

    return $html;
  }

  /**
   * @desc Method yog_retrievePostTypes
   *
   * @param void
   * @return array
   */
  function yog_getAllPostTypes()
  {
    $postTypes  = array(
      YOG_POST_TYPE_WONEN,
      YOG_POST_TYPE_BOG,
      YOG_POST_TYPE_NBPR,
      YOG_POST_TYPE_NBTY,
      YOG_POST_TYPE_NBBN,
      YOG_POST_TYPE_BBPR,
      YOG_POST_TYPE_BBTY,
      YOG_POST_TYPE_BOPR,
      YOG_POST_TYPE_RELATION
    );

    return $postTypes;
  }

  /**
   * @desc Retrieve all the custom post types for objects
   *
   * @param void
   * @return array
   */
  function yog_getAllObjectPostTypes()
  {
    $postTypes  = array(
      YOG_POST_TYPE_WONEN,
      YOG_POST_TYPE_BOG,
      YOG_POST_TYPE_NBPR,
      YOG_POST_TYPE_NBTY,
      YOG_POST_TYPE_NBBN,
      YOG_POST_TYPE_BBPR,
      YOG_POST_TYPE_BBTY,
      YOG_POST_TYPE_BOPR
    );

    return $postTypes;
  }

	/**
	 * Retrieve all objects with specified city/area/neighbourhood
	 *
	 * @param string $city
	 * @param string|null $area
	 * @param string|null $neighbourhood
	 * @param boolean $includeCustom
	 * @return array
	 */
	function yog_retrieveObjectsByAddress($city, $area = null, $neighbourhood = null, $includeCustom = true)
	{
		$params = array(
			'post_type'				=> array(YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG, YOG_POST_TYPE_NBPR, YOG_POST_TYPE_BBPR),
			'posts_per_page'	=> -1,
			'meta_query'			=> array(
				'city_filter' => array(
					'relation' => 'OR',
					array(
						'key'			=> YOG_POST_TYPE_WONEN . '_Plaats',
						'value'		=> $city,
						'compare' => '=',
					),
					array(
						'key'			=> YOG_POST_TYPE_BOG . '_Plaats',
						'value'		=> $city,
						'compare' => '=',
					),
					array(
						'key'			=> YOG_POST_TYPE_NBPR . '_Plaats',
						'value'		=> $city,
						'compare' => '=',
					),
					array(
						'key'			=> YOG_POST_TYPE_BBPR . '_Plaats',
						'value'		=> $city,
						'compare' => '=',
					)
				)
			)
		);

		if (is_string($area) && strlen(trim($area)) > 0)
		{
			$params['meta_query']['area_filter'] = array(
				'relation' => 'OR',
				array(
					'key'			=> YOG_POST_TYPE_WONEN . '_Wijk',
					'value'		=> $area,
					'compare' => '=',
				),
				array(
					'key'			=> YOG_POST_TYPE_BOG . '_Wijk',
					'value'		=> $area,
					'compare' => '=',
				),
				array(
					'key'			=> YOG_POST_TYPE_NBPR . '_Wijk',
					'value'		=> $area,
					'compare' => '=',
				),
				array(
					'key'			=> YOG_POST_TYPE_BBPR . '_Wijk',
					'value'		=> $area,
					'compare' => '=',
				)
			);

			if ($includeCustom === true)
			{
				$params['meta_query']['area_filter'][] = array(
					'key'			=> YOG_POST_TYPE_WONEN . '_WijkCustom',
					'value'		=> $area,
					'compare' => '=',
				);
				$params['meta_query']['area_filter'][] = array(
					'key'			=> YOG_POST_TYPE_BOG . '_WijkCustom',
					'value'		=> $area,
					'compare' => '=',
				);
				$params['meta_query']['area_filter'][] = array(
					'key'			=> YOG_POST_TYPE_NBPR . '_WijkCustom',
					'value'		=> $area,
					'compare' => '=',
				);
				$params['meta_query']['area_filter'][] = array(
					'key'			=> YOG_POST_TYPE_BBPR . '_WijkCustom',
					'value'		=> $area,
					'compare' => '=',
				);
			}
		}

		if (is_string($neighbourhood) && strlen(trim($neighbourhood)) > 0)
		{
			$params['meta_query']['area_filter'] = array(
				'relation' => 'OR',
				array(
					'key'			=> YOG_POST_TYPE_WONEN . '_Buurt',
					'value'		=> $neighbourhood,
					'compare' => '=',
				),
				array(
					'key'			=> YOG_POST_TYPE_BOG . '_Buurt',
					'value'		=> $neighbourhood,
					'compare' => '=',
				),
				array(
					'key'			=> YOG_POST_TYPE_NBPR . '_Buurt',
					'value'		=> $neighbourhood,
					'compare' => '=',
				),
				array(
					'key'			=> YOG_POST_TYPE_BBPR . '_Buurt',
					'value'		=> $neighbourhood,
					'compare' => '=',
				)
			);

			if ($includeCustom === true)
			{
				$params['meta_query']['area_filter'][] = array(
					'key'			=> YOG_POST_TYPE_WONEN . '_BuurtCustom',
					'value'		=> $area,
					'compare' => '=',
				);
				$params['meta_query']['area_filter'][] = array(
					'key'			=> YOG_POST_TYPE_BOG . '_BuurtCustom',
					'value'		=> $area,
					'compare' => '=',
				);
				$params['meta_query']['area_filter'][] = array(
					'key'			=> YOG_POST_TYPE_NBPR . '_BuurtCustom',
					'value'		=> $area,
					'compare' => '=',
				);
				$params['meta_query']['area_filter'][] = array(
					'key'			=> YOG_POST_TYPE_BBPR . '_BuurtCustom',
					'value'		=> $area,
					'compare' => '=',
				);
			}
		}

		return get_posts($params);
	}

	/**
	 * Generate the MijnHuiszaken favorites widget HTML
	 * @param int|null $postId
	 * @return string
	 */
	function yog_generateMhzFavoritsWidget($postId = null)
	{
		$hmzApiKey	= get_option('yog_mijnhuiszaken_api_key');

		if (!empty($hmzApiKey))
		{
			if (is_null($postId))
				$postId   = get_the_ID();
			else
				$postId		= (int) $postId;

			$postType							= get_post_type($postId);
			if ($postType === YOG_POST_TYPE_WONEN)
			{
				$zipcode							= get_post_meta($postId, $postType . '_Postcode', true);
				$houseNumber					= get_post_meta($postId, $postType . '_HuisnummerNumeriek', true);
				$houseNumberAddition	= get_post_meta($postId, $postType . '_HuisnummerToevoeging', true);

				if (!empty($zipcode) && !empty($houseNumber) && $houseNumber !== '0')
					return '<div class="mhz-widget-favorite" data-mhz-zipcode="' . $zipcode . '" data-mhz-housenumber="' . $houseNumber . '"' . (!empty($houseNumberAddition) ? ' data-mhz-housenumber-addition="' . $houseNumberAddition . '"' : '') . '></div>';
			}
		}
	}