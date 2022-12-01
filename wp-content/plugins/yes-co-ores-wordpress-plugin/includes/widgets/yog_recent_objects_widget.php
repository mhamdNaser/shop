<?php
/**
* @desc YogRecentObjectsWidget
* @author Kees Brandenburg - Yes-co Nederland
*/
class YogRecentObjectsWidget extends WP_Widget
{
  const NAME									= 'Yes-co Recente objecten';
  const DESCRIPTION						= 'De laatst gepubliceerde objecten';
  const CLASSNAME							= 'yog-recent-list';
  const DEFAULT_LIMIT					= 5;
  const DEFAULT_IMG_SIZE			= 'thumbnail';
	const MAX_CATEGORY_SETTINGS	= 3;

  /**
  * @desc Constructor
  *
  * @param void
  * @return YogRecentObjectsWidget
  */
  public function __construct()
  {
    $options = array( 'classsname'  => self::CLASSNAME,
                      'description' => self::DESCRIPTION);

    parent::__construct(false, $name = self::NAME, $options);
  }

  /**
  * @desc Display widget
  *
  * @param array $args
  * @param array $instance
  * @return void
  */
  public function widget($args, $instance)
  {
		global $wpdb;

    $title									= apply_filters('widget_title', $instance['title']);
    $limit									= empty($instance['limit']) ? self::DEFAULT_LIMIT : (int) $instance['limit'];
    $imgSize								= empty($instance['img_size']) ? self::DEFAULT_IMG_SIZE : $instance['img_size'];
    $postTypes							= $this->determinePostTypes($instance);
		$nochildsSearchresults	= get_option('yog_nochilds_searchresults');
		$onlyAvailable					= (isset($instance['only_available']) && $instance['only_available'] === true);
		$sqls										= array();
		$groupedPosts						= array();
		$htmlStyle							= (get_option('yog_html_style') ? get_option('yog_html_style') : 'basic');
		$isBootstrap4						= ($htmlStyle === 'bootstrap4');
		$groupTitleUsed					= false;

		// Determine base SQL
		$baseSql = 'SELECT * FROM ' . $wpdb->posts  . ' WHERE post_type IN (\'' . implode('\',\'', $postTypes) . '\') ';

		if (!empty($nochildsSearchresults))
		{
			$baseSql .= 'AND (post_type != \'' . YOG_POST_TYPE_WONEN . '\' OR post_parent = 0) ';
		}

		if ($onlyAvailable === true)
		{
			$onlyAvailableWhere = array();

			foreach ($postTypes as $postType)
			{
				$onlyAvailableWhere[] = 'EXISTS (SELECT true FROM ' . $wpdb->postmeta . ' WHERE ' . $wpdb->postmeta . '.post_id=' . $wpdb->posts . '.ID AND meta_key="' . $wpdb->_real_escape($postType . '_Status') . '" AND meta_value="Beschikbaar")';
			}

			if (count($onlyAvailableWhere) > 0)
				$baseSql .= 'AND (' . implode(' OR ', $onlyAvailableWhere) . ') ';
		}

		$baseSql .= 'AND post_status = \'publish\' ';

		// Create SQL to retrieve posts (for group by category)
		if (!empty($instance['group_by']) && $instance['group_by'] === 'category')
		{
			if (!empty($instance['group_by_category_options']))
			{
				foreach ($instance['group_by_category_options'] as $categoryOptions)
				{
					if (!empty($categoryOptions['num']) && !empty($categoryOptions['cat']))
					{
						$sql = $baseSql;
						// Add category SQL
						$sql .= 'AND EXISTS (SELECT true FROM ' . $wpdb->term_relationships . ' AS termLink WHERE termLink.object_id=' . $wpdb->posts . '.ID AND termLink.term_taxonomy_id = ' . (int) $categoryOptions['cat'] . ')';
						// Add order/limit
						$sql .= 'ORDER BY post_date DESC LIMIT ' . $categoryOptions['num'];

						if (!empty($categoryOptions['title']))
						{
							$sqls[] = array('sql' => $sql, 'title' => $categoryOptions['title']);
							$groupTitleUsed = true;
						}
						else
						{
							$sqls[] = array('sql' => $sql);
						}
					}
				}
			}
		}
		// Create SQL to retrieve posts (when no group by is specified)
		else
		{
      // Add filter on category
      if (!empty($instance['only_cat']))
        $baseSql .= 'AND EXISTS (SELECT true FROM ' . $wpdb->term_relationships . ' AS termLink WHERE termLink.object_id=' . $wpdb->posts . '.ID AND termLink.term_taxonomy_id = ' . (int) $instance['only_cat'] . ')';

			$sqls[] = array('sql' => $baseSql . 'ORDER BY post_date DESC LIMIT ' . $limit);
		}

		// Retrieve posts
		foreach ($sqls as $sqlOptions)
		{
			$posts = $wpdb->get_results($sqlOptions['sql'], OBJECT);

			if (!empty($posts))
			{
				if (!empty($sqlOptions['title']))
					$groupedPosts[] = array('posts' => $posts, 'title' => $sqlOptions['title']);
				else
					$groupedPosts[] = array('posts' => $posts);
			}
		}

		if (!empty($groupedPosts))
		{
			// Check for custom theme template
			$customTemplate = locate_template('yesco/parts/recent-object.php');
			// Fallback to old naming of custom theme template
			if ($customTemplate === '')
				$customTemplate = locate_template('object-recent.php');

			// Enqueue styles
			if (!(empty($customTemplate) || !file_exists(get_template_directory() . '/recent_objects.css')))
				wp_enqueue_style('yog-recent-object', YOG_PLUGIN_URL . '/inc/css/recent_objects.css');

			echo $args['before_widget'];
			if (!empty($title))
				echo $args['before_title'] . esc_html($title) . $args['after_title'];

			// Call action so theme can show html before the widget content
			do_action('yog_recent_objects_widget_before_content', $instance);

			// Handle posts with default styling
			if ($customTemplate == '')
			{
				$holderClass	= '';
				$imgClass			= '';

				// Show start of HTML
				switch ($htmlStyle)
				{
					case 'bootstrap4':
						echo '<div class="container recent-objects"><div class="row">';

						// Calculate size of container
						if ($limit %4 === 0)
							$bootstrapColSize = 6;
						else if ($limit %3 === 0)
							$bootstrapColSize = 4;
						else if ($limit > 2)
							$bootstrapColSize = 4;
						else
							$bootstrapColSize = ceil(12 / $limit);

						// Set classes for bootstrap 4
						$holderClass	= 'col-sm-' . $bootstrapColSize . '';
						$imgClass			= 'img-fluid';

						break;
					default:
						echo '<div class="recent-objects">';
						break;
				}

				// Loop through posts
				foreach ($groupedPosts as $postOptions)
				{
					foreach ($postOptions['posts'] as $post)
					{
						$images     = yog_retrieveImages($imgSize, 1, $post->ID);
						$title      = yog_retrieveSpec('Naam', $post->ID);
						$link       = get_permalink($post->ID);
						$prices     = yog_retrievePrices('recent-price-label', 'recent-price-specification', $post->ID);
						$openHouse  = yog_getOpenHouse('Open huis', $post->ID);
						$city       = yog_retrieveSpec('Plaats', $post->ID);

						// Show object
						echo '<div class="recent-object' . (empty($holderClass) ? '' : ' ' . $holderClass) . '">';
							// Image
							if (!empty($images))
							{
								echo '<div class="recent-img">';
									echo '<a href="' . esc_url($link) . '" rel="bookmark" title="' . esc_attr($title) . '">';
										echo '<img src="' . esc_url($images[0][0]) . '" width="' . esc_attr($images[0][1]) . '" height="' . esc_attr($images[0][2]) . '" alt="' . esc_attr($title) . '"' . (empty($imgClass) ? '' : ' class="' . $imgClass . '"') . ' />';
									echo '</a>';
								echo '</div>';
							}

							echo '<h2><a href="' . esc_url($link) . '" rel="bookmark" title="' . esc_attr($title) . '">' . esc_html($title) . '</a></h2>';
							echo '<h3><a href="' . esc_url($link) . '" rel="bookmark" title="' . esc_attr($title) . '">' . esc_html($city) . '</a></h3>';

							// Prices
							if (!empty($prices))
							{
								echo '<div class="recent-prices">';
								foreach ($prices as $price)
								{
									echo '<div class="recent-price">' . $price . '</div>';
								}
								echo '</div>';
							}
							// Open house
							if (!empty($openHouse))
								echo '<div class="recent-open-house">' . $openHouse . '</div>';

						echo '</div>';
					}
				}

				// Show end of HTML
				switch ($htmlStyle)
				{
					case 'bootstrap4':
						echo '</div></div>';
						break;
					default:
						echo '</div>';
						break;
				}
			}
			// Use template file from theme for posts
			else
			{
			  if ($isBootstrap4)
			    echo '<div class="container"><div class="row">';

				// Backup original post
				global $post;
        if (!empty($post))
          $orgPost	= clone $post;

				// Call template for each recent post
				foreach ($groupedPosts as $postOptions)
				{
					// Set group title, so theme can use this
					$groupTitle	= !empty($postOptions['title']) ? $postOptions['title'] : null;

					foreach ($postOptions['posts'] as $post)
					{
						setup_postdata($post);
						include($customTemplate);	// Include the template, instead of using get_template_part so variables are also useable

						// Unset group title, so it won't be shown twice
						$groupTitle = null;
					}
				}

				// Restore original post
        if (!empty($orgPost))
        {
          $post = $orgPost;
          setup_postdata($orgPost);
        }

        if ($isBootstrap4)
          echo '</div></div>';

			}

			// Call action so theme can show html before the widget content
      do_action('yog_recent_objects_widget_after', $instance);

			echo $args['after_widget'];
		}
  }

  /**
  * @desc Update widget settings
  *
  * @param array $new_instance
  * @param array $old_instance
  * @return array
  */
  public function update($new_instance, $old_instance)
  {
		$postTypes											= array();

    $instance												= $old_instance;
    $instance['title']							= empty($new_instance['title']) ? '' : $new_instance['title'];
    $instance['img_size']						= empty($new_instance['img_size']) ? self::DEFAULT_IMG_SIZE : $new_instance['img_size'];

		$instance['only_available']			= (!empty($new_instance['only_available']) && $new_instance['only_available'] === 'true');
		$instance['group_by']						= (!empty($new_instance['group_by']) && in_array($new_instance['group_by'], array('category'))) ? $new_instance['group_by'] : 'none';

		// Make sure previously stored group_by_category_options are removed
		if (isset($instance['group_by_category_options']))
			unset($instance['group_by_category_options']);

		// Store group_by_category_options settings
		if ($instance['group_by'] === 'category')
		{
			$counter				= 0;
			$storedCounter	= 0;
			$limit					= 0;
      $onlyCategory   = null;

			while ($counter < self::MAX_CATEGORY_SETTINGS)
			{
				if (!empty($new_instance['group_by_category_options'][$counter]['num']) && !empty($new_instance['group_by_category_options'][$counter]['cat']))
				{
					if (!isset($instance['group_by_category_options']))
						$instance['group_by_category_options'] = array();

					$instance['group_by_category_options'][$storedCounter] = array(
						'num'	=> (int) $new_instance['group_by_category_options'][$counter]['num'],
						'cat'	=> (int) $new_instance['group_by_category_options'][$counter]['cat']
					);

					if (!empty($new_instance['group_by_category_options'][$counter]['title']))
						$instance['group_by_category_options'][$storedCounter]['title'] = $new_instance['group_by_category_options'][$counter]['title'];

					// Update limit
					$limit += $new_instance['group_by_category_options'][$counter]['num'];

					$storedCounter++;
				}

				$counter++;
			}
		}
		// Store no group by options
		else
		{
			// Determine post types
			if (!empty($new_instance['post_type_' . YOG_POST_TYPE_WONEN]))
				$postTypes[]          = YOG_POST_TYPE_WONEN;
			if (!empty($new_instance['post_type_' . YOG_POST_TYPE_BOG]))
				$postTypes[]          = YOG_POST_TYPE_BOG;
			if (!empty($new_instance['post_type_' . YOG_POST_TYPE_NBPR]))
				$postTypes[]          = YOG_POST_TYPE_NBPR;
			if (!empty($new_instance['post_type_' . YOG_POST_TYPE_NBTY]))
				$postTypes[]          = YOG_POST_TYPE_NBTY;
			if (!empty($new_instance['post_type_' . YOG_POST_TYPE_BOPR]))
				$postTypes[]          = YOG_POST_TYPE_BOPR;

			// Set limit
			$limit	= (!empty($new_instance['limit']) && ctype_digit($new_instance['limit'])) ? (int) $new_instance['limit'] : self::DEFAULT_LIMIT;

      // Set only category
      $onlyCategory = !empty($new_instance['only_cat']) ? $new_instance['only_cat'] : null;
		}

		$instance['limit']			= $limit;
		$instance['post_types']	= implode(',', $postTypes);
    $instance['only_cat']   = $onlyCategory;

    // Widget settings storage is extendible by a theme or other plugin
    $instance = apply_filters('yog_recent_objects_widget_update', $instance, $new_instance);

    return $instance;
  }

  /**
  * @desc Display widget form
  *
  * @param array $instance
  * @return void
  */
  public function form($instance)
  {
    $title									= empty($instance['title']) ? '' : esc_attr($instance['title']);
    $limit									= empty($instance['limit']) ? self::DEFAULT_LIMIT : (int) $instance['limit'];
    $imgSize								= empty($instance['img_size']) ? self::DEFAULT_IMG_SIZE : $instance['img_size'];
    $postTypes							= $this->determinePostTypes($instance);
		$onlyAvailable					= (!empty($instance['only_available']) && $instance['only_available'] === true);
		$groupByCategory				= (!empty($instance['group_by']) && $instance['group_by'] === 'category');
		$groupByNone						= !$groupByCategory;
    $onlyCategory           = ($groupByNone === true && !empty($instance['only_cat'])) ? $instance['only_cat'] : null;
		$groupByCategoryOptions	= empty($instance['group_by_category_options']) ? array() : $instance['group_by_category_options'];

    $supportedPostTypes = array(YOG_POST_TYPE_WONEN => 'Wonen', YOG_POST_TYPE_BOG => 'BOG', YOG_POST_TYPE_NBPR => 'Nieuwbouw projecten', YOG_POST_TYPE_NBTY => 'Nieuwbouw types', YOG_POST_TYPE_BOPR => 'BOG projecten');

		echo '<div class="yog-recent-objects-widget-admin">';
			echo '<p>';
			echo '<label for="' . esc_attr($this->get_field_id('title')) . '">' . __('Titel') . ': </label>';
			echo '<input class="widefat" id="' . esc_attr($this->get_field_id('title')) . '" name="' . esc_attr($this->get_field_name('title')) . '" type="text" value="' . esc_attr($title) . '" />';
			echo '</p>';

			$groupByNoneOptionsId			= $this->get_field_id('group_by_none_options');
			$groupByCategoryOptionsId = $this->get_field_id('group_by_category_options');
			$counter									= 0;
			$categoryTaxanomy					= (get_option('yog_cat_custom') ? 'yog_category' : 'category');

			// No group by
			echo '<div class="yog-group-holder">';
				echo '<input type="radio" name="' . $this->get_field_name('group_by') . '" value="" id="' . $this->get_field_id('group_by_none') . '" data-yog-toggle="' . $groupByNoneOptionsId . '" data-yog-toggle-reverse="' . $groupByCategoryOptionsId . '"' . ($groupByNone ? ' checked="checked"' : '') . ' /> <label for="' . $this->get_field_id('group_by_none') . '">Niet groeperen</label>';
				echo '<div id="' . $groupByNoneOptionsId . '" class="yog-group-child' . ($groupByNone ? '' : ' hide') . '">';
					echo '<p>';
						echo '<label><i>' . __('Ondersteunde objecten') . '</i>: </label><br />';
						foreach ($supportedPostTypes as $postType => $label)
						{
							$id   = $this->get_field_id('post_type_' . $postType);
							$name = $this->get_field_name('post_type_' . $postType);
							echo '<input type="checkbox" name="' . esc_attr($name) . '" value="' . esc_attr($postType) . '" id="' . esc_attr($id) . '"' . (in_array($postType, $postTypes) ? ' checked="checked"' : '') . ' /> <label for="' . esc_attr($id) . '">' . esc_attr($label) . '</label><br />';
						}
					echo '</p>';
          echo '<p>';
            echo '<label for="' . esc_attr($this->get_field_id('limit')) . '"><i>Aantal te tonen objecten</i>: </label>';
            echo '<input id="' . esc_attr($this->get_field_id('limit')) . '" name="' . esc_attr($this->get_field_name('limit')) . '" type="number" min="0" max="9" step="1" value="' . esc_attr($limit) . '" size="3" />';
          echo '</p>';
          echo '<p>';
            echo '<label for="' . esc_attr($this->get_field_id('only_cat')) . '"><i>Alleen objecten uit bepaalde category tonen</i>: </label>';
            wp_dropdown_categories(array('taxonomy' => $categoryTaxanomy, 'hierarchical' => true, 'orderby' => 'name', 'hide_empty' => false, 'show_option_none' => ' ', 'option_none_value' => '', 'name' => $this->get_field_name('only_cat'), 'selected' => $onlyCategory));
          echo '</p>';
				echo '</div>';
			echo '</div>';
			// Group by category
			echo '<div class="yog-group-holder">';
				echo '<input type="radio" name="' . $this->get_field_name('group_by') . '" value="category" id="' . $this->get_field_id('group_by_category') . '" data-yog-toggle="' . $groupByCategoryOptionsId . '" data-yog-toggle-reverse="' . $groupByNoneOptionsId . '"' . ($groupByCategory ? ' checked="checked"' : '') . ' /> <label for="' . $this->get_field_id('group_by_category') . '">Groeperen a.d.h.v. category</label>';
				echo '<div id="' . $groupByCategoryOptionsId . '" class="yog-group-child' . ($groupByCategory ? '' : ' hide') . '">';
					echo '<table>';
						echo '<thead>';
							echo '<tr>';
								echo '<td><i>Aantal</i></td>';
								echo '<td><i>Category</i></td>';
								echo '<td><i>Titel</i></td>';
							echo '</tr>';
						echo '</thead>';
						echo '<tbody>';
							while ($counter < self::MAX_CATEGORY_SETTINGS)
							{
								$options	= !empty($groupByCategoryOptions[$counter]) ? $groupByCategoryOptions[$counter] : array();
								$num			= !empty($options['num']) ? (int) $options['num'] : '';
								$cat			= !empty($options['cat']) ? (int) $options['cat'] : '';
								$catTitle	= !empty($options['title']) ? $options['title'] : '';

								echo '<tr>';
									echo '<td>';
										echo '<input type="number" min="0" max="9" step="1" size="2" value="' . $num . '" name="' . esc_attr($this->get_field_name('group_by_category_options[' . $counter . '][num]')) . '" />';
									echo '</td>';
									echo '<td>';
										wp_dropdown_categories(array('taxonomy' => $categoryTaxanomy, 'hierarchical' => true, 'orderby' => 'name', 'hide_empty' => false, 'show_option_none' => ' ', 'option_none_value' => '', 'name' => $this->get_field_name('group_by_category_options[' . $counter . '][cat]'), 'selected' => $cat));
									echo '</td>';
									echo '<td>';
										echo '<input class="widefat" name="' . esc_attr($this->get_field_name('group_by_category_options[' . $counter . '][title]')) . '" type="text" value="' . esc_attr($catTitle) . '" />';
									echo '</td>';
								echo '</tr>';

								$counter++;
							}
						echo '</tbody>';
					echo '</table>';
				echo '</div>';
			echo '</div>';

			echo '<p>';
				echo '<input type="checkbox" name="' . $this->get_field_name('only_available') . '" value="true" id="' . $this->get_field_id('only_available') . '"' . ($onlyAvailable ? ' checked="checked"' : '') . ' /> <label for="' . $this->get_field_id('only_available') . '">Alleen beschikbare objecten tonen</label>';
			echo '</p>';

			echo '<p>';
			echo '<label for="' . esc_attr($this->get_field_id('img_size')) . '">' . __('Formaat afbeeldingen') . ': </label>';
			echo '<select id="' . esc_attr($this->get_field_id('img_size')) . '" name="' . esc_attr($this->get_field_name('img_size')) . '">';
				foreach (get_intermediate_image_sizes() as $size)
				{
					echo '<option value="' . esc_attr($size) . '"' . (($size == $imgSize) ? ' selected="selected"' : '') . '>' . esc_attr(__(ucfirst($size))) . '</option>';
				}
				echo '</select>';
			echo '</p>';

		echo '</div>';

		// Widget settings are extendible by a theme or other plugin
		do_action('yog_recent_objects_widget_after_settings', $this, $instance);
  }

  /**
  * @desc Determine configured post types
  *
  * @param array $instance
  * @return array
  */
  private function determinePostTypes($instance)
  {
		if (!empty($instance['group_by']) && $instance['group_by'] === 'category')
		{
			$postTypes = yog_getAllObjectPostTypes();
		}
		else
		{
			$postTypes          = array(YOG_POST_TYPE_WONEN);
			if (isset($instance['post_types']))
				$postTypes        = empty($instance['post_types']) ? array() : explode(',', $instance['post_types']);
		}

    return $postTypes;
  }
}