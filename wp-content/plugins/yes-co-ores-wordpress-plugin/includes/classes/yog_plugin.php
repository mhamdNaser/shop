<?php
  require_once(YOG_PLUGIN_DIR . '/includes/config/config.php');
  require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_object_search_manager.php');
  require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_system_link_manager.php');
  require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_cron.php');

  /**
  * @desc YogPlugin
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogPlugin
  {
    static private $instance;

    protected $wpVersion;

    /**
    * @desc Constructor
    *
    * @param void
    * @return YogPlugin
    */
    private function __construct()
    {
			// Define old style post type constants for backwards compatibility
			foreach (array('WONEN', 'BOG', 'NBPR', 'NBTY', 'NBBN', 'BBPR', 'BBTY', 'RELATION', 'ATTACHMENT') as $type)
			{
				if (!defined('POST_TYPE_' . $type))
					define('POST_TYPE_' . $type,	constant('YOG_POST_TYPE_' . $type));
			}

      // Include widgets
      require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_address_search_form_widget.php');
      require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_recent_objects_widget.php');
      require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_linked_objects_widget.php');
      require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_linked_relations_widget.php');
      require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_contact_form_widget.php');
      require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_map_widget.php');

			if (!defined('YOG_PLUGIN_DISABLE_ATTACHMENT_WIDGET') || YOG_PLUGIN_DISABLE_ATTACHMENT_WIDGET === false)
				require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_object_attachments_widget.php');

			if (!defined('YOG_PLUGIN_DISABLE_SEARCH_WONEN_WIDGET') || YOG_PLUGIN_DISABLE_SEARCH_WONEN_WIDGET === false)
				require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_search_form_widget.php');
			if (!defined('YOG_PLUGIN_DISABLE_SEARCH_BOG_WIDGET') || YOG_PLUGIN_DISABLE_SEARCH_BOG_WIDGET === false)
				require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_search_form_bog_widget.php');
			if (!defined('YOG_PLUGIN_DISABLE_SEARCH_NBPR_WIDGET') || YOG_PLUGIN_DISABLE_SEARCH_NBPR_WIDGET === false)
				require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_search_form_nbpr_widget.php');
			if (!defined('YOG_PLUGIN_DISABLE_SEARCH_NBTY_WIDGET') || YOG_PLUGIN_DISABLE_SEARCH_NBTY_WIDGET === false)
				require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_search_form_nbty_widget.php');
			if (!defined('YOG_PLUGIN_DISABLE_SEARCH_BBPR_WIDGET') || YOG_PLUGIN_DISABLE_SEARCH_BBPR_WIDGET === false)
				require_once(YOG_PLUGIN_DIR . '/includes/widgets/yog_search_form_bbpr_widget.php');

      global $wp_version;
      $this->wpVersion = (float) $wp_version;
    }

    /**
    * @desc Get an instance of the YogPlugin
    *
    * @param void
    * @return YogPlugin
    */
    static public function getInstance()
    {
      if (is_null(self::$instance))
      {
      	// Check script name, because using is_admin() is causing fatal on wp 3.7
        if (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/wp-admin/') !== false)
          self::$instance = new YogPluginAdmin();
        else
          self::$instance = new YogPluginPublic();
      }

      return self::$instance;
    }

    /**
    * @desc Initialize Wordpress plugin
    *
    * @param void
    * @return void
    */
    public function init()
    {
      add_theme_support('post-thumbnails');
      add_action('init', array($this, 'registerPostTypes'));
			add_action('init', array($this, 'registerBlocks'));

			add_action( 'init', array($this, 'loadTranslations'));

      add_action('widgets_init', array($this, 'registerWidgets'));
      add_filter('post_rewrite_rules', array($this, 'insertCustomRewriteRules'));
      add_filter('post_type_link', array($this, 'fixPermalinks'), 1, 3);

      add_action('yog_cron_open_houses',        array('YogCron', 'updateOpenHouses'));

      register_deactivation_hook(YOG_PLUGIN_DIR . '/yesco-og.php', array($this, 'onDeactivation'));
    }

    /**
    * @desc Fix NBty/BBty permalinks
    *
    * @param string $permalink
    * @param StdClass $post
    * @param bool $leavename
    * @return string
    */
    public function fixPermalinks($permalink, $post, $leavename)
    {
      switch ($post->post_type)
      {
        case YOG_POST_TYPE_NBTY:

          if (!empty($post->post_parent))
          {
            $parent = get_post($post->post_parent);

            $permalink = str_replace('/nieuwbouw-type/', '/nieuwbouw/' . $parent->post_name . '/type/', $permalink);

            if (strpos($permalink, '%' . YOG_POST_TYPE_NBTY . '%') !== false && !empty($post->post_parent))
              $permalink = str_replace('%' . YOG_POST_TYPE_NBTY . '%', '%pagename%', $permalink);
          }

          break;
        case YOG_POST_TYPE_BBTY:

          if (!empty($post->post_parent))
          {
            $parent = get_post($post->post_parent);

            $permalink = str_replace('/yog-bbty/', '/complex/' . $parent->post_name . '/type/', $permalink);

            if (strpos($permalink, '%' . YOG_POST_TYPE_BBTY . '%') !== false && !empty($post->post_parent))
              $permalink = str_replace('%' . YOG_POST_TYPE_BBTY . '%', '%pagename%', $permalink);
          }

          break;
      }

	    return $permalink;
    }

    /**
    * @desc Add custom rewrite rules for NBty
    *
    * @param array $rules
    * @return array
    */
    public function insertCustomRewriteRules($rules)
    {
	    $newrules = array();
	    $newrules['nieuwbouw/(.+?)/type/(.+?)$']  = 'index.php?' . YOG_POST_TYPE_NBTY . '=$matches[2]';
      $newrules['complex/(.+?)/type/(.+?)$']    = 'index.php?' . YOG_POST_TYPE_BBTY . '=$matches[2]';

	    return $newrules + $rules;
    }

    public static function enqueueDojo()
    {
      add_action('wp_head', array(YogPlugin, 'loadDojo'));
      add_action('admin_head', array(YogPlugin, 'loadDojo'));
    }

    private static $dojoLoaded = false;

    public static function isDojoLoaded()
    {
      return self::$dojoLoaded;
    }

    public static function loadDojo($enqueue = true)
    {
      self::$dojoLoaded = true;

      echo '<script type="text/javascript">
            // <![CDATA[
              var djConfig = {
              cacheBust: "' . YOG_PLUGIN_VERSION . '",
              async: true,
              packages: [
                {
                  name: "yog",
                  location: "' . YOG_PLUGIN_URL . '/inc/js"
                }
              ]
              };

              delete define;

            // ]]>
            </script>';

      $dojoUrl = YOG_PLUGIN_URL . '/inc/js/dojo.min.js';

      // Fix for jquery being loaded crashing whole interface
      if (get_option('yog_javascript_dojo_dont_enqueue') || $enqueue === false)
        echo '<script defer type="text/javascript" src="' . esc_url($dojoUrl) . '"></script>';
      else
        wp_enqueue_script('dojo', $dojoUrl, false, YOG_PLUGIN_DOJO_VERSION);

    }

    /**
    * @desc Enqueue files
    *
    * @param void
    * @return void
    */
    public function enqueueFiles()
    {

    }

    /**
    * @desc Register post types
    *
    * @param void
    * @return void
    */
    public function registerPostTypes()
    {
      if (get_option('yog_cat_custom'))
      {
        register_taxonomy('yog_category',
                          array(YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG, YOG_POST_TYPE_NBPR, YOG_POST_TYPE_NBTY,
                                YOG_POST_TYPE_NBBN, YOG_POST_TYPE_BBPR, YOG_POST_TYPE_BBTY, YOG_POST_TYPE_BOPR),
                          array('hierarchical'      => true,
                                'show_ui'           => true,
                                'rewrite'           => array('slug' => 'objecten'),
                                'labels'            => array('name' => 'Object categori&euml;n'),
                                'capabilities'      => array('manage_terms', 'edit_terms', 'delete_terms', 'assign_terms'),
                                'show_in_menu'      => 'yog_posts_menu',
                                'query_var'         => 'objecten'
                                ));

        $taxonomies = array('yog_category', 'post_tag');
      }
      else
      {
        $taxonomies = array('category', 'post_tag');
      }

	    register_post_type(YOG_POST_TYPE_WONEN,
	                  array('labels'    => array('name'               => 'Wonen',
	                                            'singular_name'       => 'Woon object',
                                              'add_new'             => 'Toevoegen',
                                              'add_new_item'        => 'Object toevoegen',
                                              'search_items'        => 'Objecten zoeken',
                                              'not_found'           => 'Geen objecten gevonden',
                                              'not_found_in_trash'  => 'Geen objecten gevonden in de prullenbak',
                                              'edit_item'           => 'Object bewerken',
                                              'view_item'           => __('View')
                                              ),
                          'public'            => true,
	                        'show_ui'           => true, // UI in admin panel
                          'show_in_menu'      => 'yog_posts_menu',
	                        'show_in_nav_menus' => true,
                          'has_archive'       => true,
	                        'capability_type'   => 'post',
                          'menu_icon'         => YOG_PLUGIN_URL . '/img/icon_yes-co.gif',
	                        'hierarchical'      => false,
	                        'rewrite'           => array('slug' => YOG_POST_TYPE_WONEN), // Permalinks format
	                        'supports'          => array('title','editor', 'thumbnail'),
	                        'taxonomies'        => $taxonomies
	                        )
	    );

	    register_post_type(YOG_POST_TYPE_BOG,
	                  array('labels'    => array('name'               => 'BOG',
	                                            'singular_name'       => 'BOG object',
                                              'add_new'             => 'BOG object toevoegen',
                                              'add_new_item'        => 'Object toevoegen',
                                              'search_items'        => 'Objecten zoeken',
                                              'not_found'           => 'Geen objecten gevonden',
                                              'not_found_in_trash'  => 'Geen objecten gevonden in de prullenbak',
                                              'edit_item'           => 'Object bewerken',
                                              'view_item'           => __('View')
                                              ),
                          'public'            => true,
	                        'show_ui'           => true, // UI in admin panel
                          'show_in_menu'      => 'yog_posts_menu',
	                        'show_in_nav_menus' => true,
                          'has_archive'       => true,
	                        'capability_type'   => 'post',
                          'menu_icon'         => YOG_PLUGIN_URL . '/img/icon_yes-co.gif',
	                        'hierarchical'      => false,
	                        'rewrite'           => array('slug' => YOG_POST_TYPE_BOG), // Permalinks format
	                        'supports'          => array('title','editor', 'thumbnail'),
	                        'taxonomies'        => $taxonomies
	                        )
	    );

	    register_post_type(YOG_POST_TYPE_BOPR,
	                  array('labels'    => array('name'               => 'BOG projecten',
	                                            'singular_name'       => 'BOG project',
                                              'add_new'             => 'BOG project toevoegen',
                                              'add_new_item'        => 'Project toevoegen',
                                              'search_items'        => 'Projecten zoeken',
                                              'not_found'           => 'Geen BOG projecten gevonden',
                                              'not_found_in_trash'  => 'Geen BOG projecten gevonden in de prullenbak',
                                              'edit_item'           => 'Project bewerken',
                                              'view_item'           => __('View')
                                              ),
                          'public'            => true,
	                        'show_ui'           => true, // UI in admin panel
                          'show_in_menu'      => 'yog_posts_menu',
	                        'show_in_nav_menus' => true,
                          'has_archive'       => true,
	                        'capability_type'   => 'post',
                          'menu_icon'         => YOG_PLUGIN_URL . '/img/icon_yes-co.gif',
	                        'hierarchical'      => false,
	                        'rewrite'           => array('slug' => 'bog-project'), // Permalinks format
	                        'supports'          => array('title','editor', 'thumbnail'),
	                        'taxonomies'        => $taxonomies
	                        )
	    );

	    register_post_type(YOG_POST_TYPE_NBPR,
	                  array('labels'    => array('name'               => 'Nieuwbouw',
	                                            'singular_name'       => 'Nieuwbouw project',
                                              'add_new'             => 'Nieuwbouw project toevoegen',
                                              'add_new_item'        => 'Project toevoegen',
                                              'search_items'        => 'Projecten zoeken',
                                              'not_found'           => 'Geen nieuwbouw projecten gevonden',
                                              'not_found_in_trash'  => 'Geen nieuwbouw projecten gevonden in de prullenbak',
                                              'edit_item'           => 'Project bewerken',
                                              'view_item'           => __('View')
                                              ),
                          'public'            => true,
	                        'show_ui'           => true, // UI in admin panel
                          'show_in_menu'      => 'yog_posts_menu',
	                        'show_in_nav_menus' => true,
	                        'capability_type'   => 'post',
                          'menu_icon'         => YOG_PLUGIN_URL . '/img/icon_yes-co.gif',
	                        'hierarchical'      => false,
	                        'rewrite'           => array('slug' => 'nieuwbouw'), // Permalinks format
	                        'supports'          => array('title','editor', 'thumbnail'),
	                        'taxonomies'        => $taxonomies
	                        )
	    );

	    register_post_type(YOG_POST_TYPE_NBTY,
	                  array('labels'    => array('name'               => 'Nieuwbouw types',
	                                            'singular_name'       => 'Nieuwbouw type',
                                              'add_new'             => 'Nieuwbouw type toevoegen',
                                              'add_new_item'        => 'Type toevoegen',
                                              'search_items'        => 'Types zoeken',
                                              'not_found'           => 'Geen nieuwbouw types gevonden',
                                              'not_found_in_trash'  => 'Geen nieuwbouw types gevonden in de prullenbak',
                                              'edit_item'           => 'Type bewerken',
                                              'view_item'           => __('View')
                                              ),
                          'public'            => true,
	                        'show_ui'           => true, // UI in admin panel
                          'show_in_menu'      => 'yog_posts_menu',
	                        'show_in_nav_menus' => true,
	                        'capability_type'   => 'post',
                          'menu_icon'         => YOG_PLUGIN_URL . '/img/icon_yes-co.gif',
	                        'hierarchical'      => false,
	                        'rewrite'           => array('slug' => 'nieuwbouw-type', 'with_front' => false), // Permalinks format
	                        'supports'          => array('title','editor', 'thumbnail'),
	                        'taxonomies'        => $taxonomies
	                        )
	    );

	    register_post_type(YOG_POST_TYPE_NBBN,
	                  array('labels'    => array('name'               => 'Nieuwbouw bouwnummers',
	                                            'singular_name'       => 'Nieuwbouw bouwnummer',
                                              'add_new'             => 'Nieuwbouw bouwnummer toevoegen',
                                              'add_new_item'        => 'Bouwnummer toevoegen',
                                              'search_items'        => 'Bouwnummers zoeken',
                                              'not_found'           => 'Geen nieuwbouw bouwnummers gevonden',
                                              'not_found_in_trash'  => 'Geen nieuwbouw bouwnummers gevonden in de prullenbak',
                                              'edit_item'           => 'Bouwnummer bewerken'
                                              ),
                          'public'            => false,
	                        'show_ui'           => true, // UI in admin panel
                          'show_in_menu'      => 'yog_posts_menu',
	                        'show_in_nav_menus' => true,
	                        'capability_type'   => 'post',
                          'menu_icon'         => YOG_PLUGIN_URL . '/img/icon_yes-co.gif',
	                        'hierarchical'      => true,
	                        'rewrite'           => array('slug' => 'nieuwbouw-bouwnummer'), // Permalinks format
	                        'supports'          => array('title')
	                        )
	    );

	    register_post_type(YOG_POST_TYPE_BBPR,
	                  array('labels'    => array('name'               => 'Complex',
	                                            'singular_name'       => 'Complex (bestaande bouw project)',
                                              'add_new'             => 'Complex toevoegen',
                                              'add_new_item'        => 'Complex toevoegen',
                                              'search_items'        => 'Complexen zoeken',
                                              'not_found'           => 'Geen complexen (bestaande bouw projecten) gevonden',
                                              'not_found_in_trash'  => 'Geen complexen (bestaande bouw projecten) gevonden in de prullenbak',
                                              'edit_item'           => 'Project bewerken',
                                              'view_item'           => __('View')
                                              ),
                          'public'            => true,
	                        'show_ui'           => true, // UI in admin panel
                          'show_in_menu'      => 'yog_posts_menu',
	                        'show_in_nav_menus' => true,
	                        'capability_type'   => 'post',
                          'menu_icon'         => YOG_PLUGIN_URL . '/img/icon_yes-co.gif',
	                        'hierarchical'      => false,
	                        'rewrite'           => array('slug' => 'complex'), // Permalinks format
	                        'supports'          => array('title','editor', 'thumbnail'),
	                        'taxonomies'        => $taxonomies
	                        )
	    );

	    register_post_type(YOG_POST_TYPE_BBTY,
	                  array('labels'    => array('name'               => 'Complex types',
	                                            'singular_name'       => 'Complex type',
                                              'add_new'             => 'Complex type toevoegen',
                                              'add_new_item'        => 'Type toevoegen',
                                              'search_items'        => 'Types zoeken',
                                              'not_found'           => 'Geen complex (bestaande bouw) types gevonden',
                                              'not_found_in_trash'  => 'Geen complex (bestaande bouw) types gevonden in de prullenbak',
                                              'edit_item'           => 'Type bewerken',
                                              'view_item'           => __('View')
                                              ),
                          'public'            => true,
	                        'show_ui'           => true, // UI in admin panel
                          'show_in_menu'      => 'yog_posts_menu',
	                        'show_in_nav_menus' => true,
	                        'capability_type'   => 'post',
                          'menu_icon'         => YOG_PLUGIN_URL . '/img/icon_yes-co.gif',
	                        'hierarchical'      => false,
	                        //'rewrite'           => array('slug' => 'complex-type', 'with_front' => false), // Permalinks format
	                        'supports'          => array('title','editor', 'thumbnail'),
	                        'taxonomies'        => $taxonomies
	                        )
	    );

	    register_post_type(YOG_POST_TYPE_RELATION,
	                  array('labels'    => array( 'name'                => 'Relaties',
	                                              'singular_name'       => 'Relatie',
                                                'add_new'             => 'Toevoegen',
                                                'add_new_item'        => 'Relatie toevoegen',
                                                'search_items'        => 'Relaties zoeken',
                                                'not_found'           => 'Geen relaties gevonden',
                                                'not_found_in_trash'  => 'Geen relaties gevonden in de prullenbak'
                                                ),
	                        'public'            => false,
	                        'show_ui'           => true, // UI in admin panel
                          'show_in_menu'      => 'yog_posts_menu',
	                        'show_in_nav_menus' => true,
	                        'capability_type'   => 'post',
                          'menu_icon'         => YOG_PLUGIN_URL . '/img/icon_yes-co.gif',
	                        'hierarchical'      => false,
	                        'rewrite'           => array('slug' => YOG_POST_TYPE_RELATION), // Permalinks format
	                        'supports'          => array('title', 'thumbnail', 'page-attributes')
	                        )
	    );
    }


    /**
     * Load plugin textdomain.
     */
    public function loadTranslations()
    {
      load_plugin_textdomain( YOG_TRANSLATION_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/../../languages' );
    }

		/**
		 * Register the wordpress gutenburg blocks
		 */
		public function registerBlocks()
		{
			require_once(YOG_PLUGIN_DIR . '/includes/blocks/yog_block_abstract.php');

			// Plugin blocks
			$blocks = array();

			// Extend the blocks with blocks of theme / other plugins
			$blocks = apply_filters('yog_init_blocks', $blocks);

			// Register all the blocks
			if (!empty($blocks))
			{
				add_filter('block_categories', array($this, 'registerBlockCategories'), 10, 2 );

				foreach ($blocks as $block)
				{
					$block->register();
				}
			}
		}

		public function registerBlockCategories($categories, $post)
		{
			if (!in_array($post->post_type, array('page', 'post')))
				return $categories;

			return array_merge(
				$categories,
				array(
					array(
						'slug'	=> 'yesco',
						'title' => __('Yes-co', 'yog')
						//'icon'  => 'wordpress',
					)
				)
			);
		}

    /**
    * @desc Register widgets
    *
    * @param void
    * @return void
    */
    public function registerWidgets()
    {
      register_widget('YogRecentObjectsWidget');
			if (!defined('YOG_PLUGIN_DISABLE_SEARCH_WONEN_WIDGET') || YOG_PLUGIN_DISABLE_SEARCH_WONEN_WIDGET === false)
				register_widget('YogSearchFormWonenWidget');
			if (!defined('YOG_PLUGIN_DISABLE_SEARCH_BOG_WIDGET') || YOG_PLUGIN_DISABLE_SEARCH_BOG_WIDGET === false)
				register_widget('YogSearchFormBogWidget');
			if (!defined('YOG_PLUGIN_DISABLE_SEARCH_NBPR_WIDGET') || YOG_PLUGIN_DISABLE_SEARCH_NBPR_WIDGET === false)
				register_widget('YogSearchFormNBprWidget');
			if (!defined('YOG_PLUGIN_DISABLE_SEARCH_NBTY_WIDGET') || YOG_PLUGIN_DISABLE_SEARCH_NBTY_WIDGET === false)
				register_widget('YogSearchFormNBtyWidget');
			if (!defined('YOG_PLUGIN_DISABLE_SEARCH_BBPR_WIDGET') || YOG_PLUGIN_DISABLE_SEARCH_BBPR_WIDGET === false)
				register_widget('YogSearchFormBBprWidget');
      register_widget('YogAddressSearchFormWidget');
      register_widget('YogContactFormWidget');
      register_widget('YogMapWidget');
			if (!defined('YOG_PLUGIN_DISABLE_ATTACHMENT_WIDGET') || YOG_PLUGIN_DISABLE_ATTACHMENT_WIDGET === false)
				register_widget('YogObjectAttachmentsWidget');
      register_widget('YogLinkedObjectsWidget');
      register_widget('YogLinkedRelationsWidget');
    }

    /**
    * Cleanup some things on deactivation
    */
    public function onDeactivation()
    {
      if (wp_next_scheduled('yog_cron_open_houses'))
        wp_clear_scheduled_hook('yog_cron_open_houses');
    }

    /**
     * List all theme php files (parent + child theme files)
     *
     * @param void
     * @return array
     */
    protected function listThemeFiles()
    {
      $parentFiles  = glob(get_template_directory() . '/*.php');
      $childFiles   = glob(get_stylesheet_directory() . '/*.php');
      $files        = array();

      foreach ($parentFiles as $parentFile)
      {
        $files[basename($parentFile)] = $parentFile;
      }

      foreach ($childFiles as $childFile)
      {
        $files[basename($childFile)] = $childFile;
      }

      return $files;
    }
  }

  /**
  * @desc YogPluginAdmin
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogPluginPublic extends YogPlugin
  {
    /**
    * @desc Initialize Wordpress public
    *
    * @param void
    * @return void
    */
    public function init()
    {
      parent::init();

			// Define SVZ_GOOGLE_MAPS_API_KEY for backwards compatibility
			if (!defined('SVZ_GOOGLE_MAPS_API_KEY'))
			{
				$googleMapsApiKey = get_option('yog_google_maps_api_key');
				if (!empty($googleMapsApiKey))
					define('SVZ_GOOGLE_MAPS_API_KEY', $googleMapsApiKey);
			}

      add_filter('pre_get_posts',           array($this, 'extendPostQuery'));
      add_filter('the_content',             array($this, 'extendTheContent'));

      if (get_option('yog_javascript_dojo_dont_enqueue'))
        add_filter( 'wp_enqueue_scripts', array($this, 'enqueueFiles') , 0 );
      else
        add_action('init',                    array($this, 'enqueueFiles'));

      // Add shortcodes
      add_shortcode('yog-widget',         array($this, 'handleWidgetShortcode'));
      add_shortcode('yog-contact-widget', array($this, 'handleContactWidgetShortcode'));
      add_shortcode('yog-map',            array($this, 'handleMapShortcode'));
      add_shortcode('yog-objects',        array($this, 'handleObjectsShortcode'));
      add_shortcode('yog-mhz-get-url',    array($this, 'handleMhzRetrieveUrlShortcode'));
      add_shortcode('yog-employees-bar',  array($this, 'handleEmployeesBarShortcode'));

			// Set stuff to head
			add_action('wp_head',								array($this, 'addStuffToHead'));

      $searchManager = YogObjectSearchManager::getInstance();
     	$searchManager->extendSearch();
    }

    /**
    * @desc Enqueue files
    *
    * @param void
    * @return void
    */
    public function enqueueFiles()
    {
      parent::enqueueFiles();

			// Enqueue MHZ script
			$hmzApiKey	= get_option('yog_mijnhuiszaken_api_key');
			if (!empty($hmzApiKey))
			{
			  $mhzUrl = (defined('YOG_MHZ_URL') ? YOG_MHZ_URL : 'https://mijnhuiszaken.nl');

			  wp_enqueue_script('yog-mhz-api',   $mhzUrl . '/connect/api.js?key=' . $hmzApiKey, array(), YOG_PLUGIN_VERSION, true);
			}
    }

		/**
		 * Print inline scripts in head section
		 */
		public function addStuffToHead()
		{
			$yogConfig = array('baseUrl' => home_url());

			// Add yog_config
			echo '<script type=\'text/javascript\'>';
			echo '/* <![CDATA[ */' . "\n";
			echo 'var YogConfig = ' . json_encode($yogConfig) . ';' . "\n";
			echo '/* ]]> */';
			echo '</script>' . "\n";

			if (is_singular() && get_option('yog_show_og', false))
			{
				global $post;

				if (in_array($post->post_type, yog_getAllObjectPostTypes()))
				{
				  echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . '"/>' . "\n";
					echo '<meta property="og:url" content="' . get_permalink() . '"/>' . "\n";
					echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '"/>' . "\n";

					$locale = get_locale();
					if (!empty($locale))
					  echo '<meta property="og:locale" content="' . esc_attr($locale) . '"/>' . "\n";

					if (!empty($post->post_content))
					{
						$morePos		= strpos($post->post_content, '<!--more-->');
						if ($morePos !== false)
							$description = substr($post->post_content, 0, $morePos);
						else
							$description = $post->post_content;

						echo '<meta property="og:description" content="' . esc_attr(str_replace('"', "'", strip_tags($description))) . '"/>' . "\n";
					}

					if (has_post_thumbnail($post->ID))
					{
						$thumbnail_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'medium' );
						echo '<meta property="og:image" content="' . esc_attr($thumbnail_src[0]) . '"/>' . "\n";
						echo '<meta property="og:image:width" content="' . esc_attr($thumbnail_src[1]) . '"/>' . "\n";
						echo '<meta property="og:image:height" content="' . esc_attr($thumbnail_src[2]) . '"/>' . "\n";
					}
				}
			}
		}

    /**
    * @desc Extend the content, if theme contains no single-*.php template
    *
    * @param string $content
    * @return string
    */
    public function extendTheContent($content)
    {
      $postType = get_post_type();
      $prefix   = '';
      $suffix   = '';

      if (is_single() && in_array($postType, yog_getAllPostTypes()) && locate_template('single-' . $postType . '.php') == '')
      {
        // Add photo slider
        $prefix .= yog_retrievePhotoSlider();

        // Add prices
        $prices = yog_retrievePrices();
        if (count($prices) > 0)
          $prefix .= '<div class="yog-prices">' . implode('<br />', $prices) . '</div>';

        switch ($postType)
        {
          case YOG_POST_TYPE_WONEN:
            // Add open house
            if (yog_hasOpenHouse())
              $prefix .= '<div class="yog-open-house">' . yog_getOpenHouse() . '</div>';

            // Add location
            $suffix = yog_retrieveDynamicMap();
            break;
          case YOG_POST_TYPE_BOG:
            // Add location
            $suffix .= yog_retrieveDynamicMap();
            break;
          case YOG_POST_TYPE_NBPR:
          case YOG_POST_TYPE_BBPR:
            // Add location
            $suffix .= yog_retrieveDynamicMap();

            // Add types
            $childs = yog_retrieveChildObjects();
            if (is_array($childs) && count($childs) > 0)
            {
              $suffix .= '<h2>Types</h2>';

              foreach ($childs as $child)
              {
                $name   = $child->post_title;
                $image  = get_the_post_thumbnail($child->ID, 'thumbnail', array('alt' => $name, 'title' => $name));
                $url    = get_permalink($child->ID);

                $suffix .= '<div class="yog-post-child">';
                if (!empty($image))
                  $suffix .= '<a href="' . esc_url($url) . '" title="' . esc_attr($name) . '">' . $image . '</a> ';

                  $suffix .= '<a href="' . esc_url($url) . '" title="' . esc_attr($name) . '">' . esc_html($name) . '</a>';
                $suffix .= '</div>';
              }
            }
            break;
          case YOG_POST_TYPE_NBTY:
            // Add NBbn
            $table = yog_retrieveNbbnTable();
            if (!empty($table))
            {
              $suffix .= '<h2>Bouwnummers</h2>';
              $suffix .= $table;
            }
            break;
          case YOG_POST_TYPE_BBTY:

            // Add child objects
            $childs = yog_retrieveChildObjects();

            if (is_array($childs) && count($childs) > 0)
            {
              $suffix .= '<h2>Objecten</h2>';

              foreach ($childs as $child)
              {
                $name   = $child->post_title;
                $image  = get_the_post_thumbnail($child->ID, 'thumbnail', array('alt' => $name, 'title' => $name));
                $url    = get_permalink($child->ID);

                $suffix .= '<div class="yog-post-child">';
                if (!empty($image))
                  $suffix .= '<a href="' . esc_url($url) . '" title="' . esc_attr($name) . '">' . $image . '</a> ';

                  $suffix .= '<a href="' . esc_url($url) . '" title="' . esc_attr($name) . '">' . esc_html($name) . '</a>';
                $suffix .= '</div>';
              }
            }
            break;

          case YOG_POST_TYPE_BOPR:

            // Add location
            $suffix .= yog_retrieveDynamicMap();

            // Add types
            $childs = yog_retrieveChildObjects();
            if (is_array($childs) && count($childs) > 0)
            {
              $suffix .= '<h2>Objecten</h2>';

              foreach ($childs as $child)
              {
                $name   = $child->post_title;
                $image  = get_the_post_thumbnail($child->ID, 'thumbnail', array('alt' => $name, 'title' => $name));
                $url    = get_permalink($child->ID);

                $suffix .= '<div class="yog-post-child">';
                if (!empty($image))
                  $suffix .= '<a href="' . esc_url($url) . '" title="' . esc_attr($name) . '">' . $image . '</a> ';

                  $suffix .= '<a href="' . esc_url($url) . '" title="' . esc_attr($name) . '">' . esc_html($name) . '</a>';
                $suffix .= '</div>';
              }
            }

            break;
        }
      }

      return $prefix . $content . $suffix;
    }

    /**
    * @desc Register the post types to use on several pages
    *
    * @param WP_Query $query
    * @return WP_Query
    */
    public function extendPostQuery($query)
    {
      if ($query->is_main_query())
      {
        $extendQuery    = true;
        $isYogCategory  = is_tax('yog_category');

        if ($isYogCategory)
          $extendQuery = false;
        else if (!(!isset($query->query_vars['suppress_filters']) || $query->query_vars['suppress_filters'] == false))
          $extendQuery = false;
        else if (!($query->is_archive || $query->is_category || $query->is_feed || $query->is_home))
          $extendQuery = false;
        else if ($query->is_archive && !$query->is_category && !$query->is_tag && !get_option('yog_objectsinarchief'))
          $extendQuery = false;
        else if ($query->is_home && !get_option('yog_huizenophome'))
          $extendQuery = false;

        // Make post types available
        if ($extendQuery === true)
        {
          $postTypes  = $query->get('post_type');
          if (empty($postTypes))
            $postTypes = array('post');
          else if (!is_array($postTypes))
            $postTypes = array($postTypes);

          if (!in_array(YOG_POST_TYPE_WONEN, $postTypes))
            $postTypes[] = YOG_POST_TYPE_WONEN;

          if (!in_array(YOG_POST_TYPE_BOG, $postTypes))
            $postTypes[] = YOG_POST_TYPE_BOG;

          if (!in_array(YOG_POST_TYPE_BOPR, $postTypes))
            $postTypes[] = YOG_POST_TYPE_BOPR;

          if (!in_array(YOG_POST_TYPE_NBPR, $postTypes))
            $postTypes[] = YOG_POST_TYPE_NBPR;

          if (!in_array(YOG_POST_TYPE_NBTY, $postTypes))
            $postTypes[] = YOG_POST_TYPE_NBTY;

          if (!in_array(YOG_POST_TYPE_BBPR, $postTypes))
            $postTypes[] = YOG_POST_TYPE_BBPR;

          if (!in_array(YOG_POST_TYPE_BBTY, $postTypes))
            $postTypes[] = YOG_POST_TYPE_BBTY;

          $query->set('post_type', $postTypes);
        }

        // Set custom order
        if ($isYogCategory)
        {
          $defaultOrder = get_option('yog_order');

          if (!empty($defaultOrder))
          {
            switch ($defaultOrder)
            {
              case 'date_asc':
              case 'title_asc':
              case 'price_asc':
							case 'city_asc':
                $query->set('order', 'ASC');
                break;
              case 'title_desc':
              case 'price_desc';
							case 'city_desc':
                $query->set('order', 'DESC');
                break;
            }

            switch ($defaultOrder)
            {
              case 'date_asc':
                $query->set('orderby', 'date');
                break;
              case 'title_asc':
              case 'title_desc':
                $query->set('orderby', 'title');
                break;
              case 'price_asc':
              case 'price_desc';
                $query->set('orderby',  'meta_value_num');
                $query->set('meta_key', 'yog_price_order');
                break;
							case 'city_asc':
							case 'city_desc':
                $query->set('orderby',  'meta_value');
                $query->set('meta_key', 'yog_city_street_order');
								break;
            }
          }
        }
      }
    }

    /**
     * Handle widget shortcodes like [yog-widget type=".." id=".."]
     *
     * @param array $attr
     * @return string
     */
    public function handleWidgetShortcode($attr)
    {
      if (!empty($attr['type']) && !empty($attr['id']))
      {
        global $wp_registered_widgets;

        // Check type
        switch ($attr['type'])
        {
          case 'contact':
            $widgetType = $attr['type'] . 'form';
            break;
          case 'searchwonen':
          case 'searchbog':
          case 'searchnbpr':
          case 'searchnbty':
          case 'searchbbpr':
            $widgetType = str_replace('search', 'searchform', $attr['type']);
            break;
          default:
            return '';
            break;
        }

        ///YogSearchFormNBtyWidget

        $widgetNr     = $attr['id'];
        $widgetClass  = 'widget_yog' . $widgetType . 'widget';
        $widgetId     = 'yog' . $widgetType . 'widget-' .  $widgetNr;

        // Widget not found, so return empty string
        if (empty($wp_registered_widgets[$widgetId]))
          return '';

        // Widget object not found
        if (empty($wp_registered_widgets[$widgetId]['callback']) || empty($wp_registered_widgets[$widgetId]['callback'][0]))
          return '';

        // Get widget object
        $widgetObject = $wp_registered_widgets[$widgetId]['callback'][0];

        // Determine args / settings
        $args         = array(
                          'before_widget' => '<div class="widget ' . $widgetClass . '" id="' . $widgetId . 'shortcode">',
                          'before_title'  => '<h2 class="widgettitle">',
                          'after_title'   => '</h2>',
                          'after_widget'  => '</div>'
                        );
        $settings     = $widgetObject->get_settings();

        // Catch widget output through output buffering
        ob_start();
        $widgetObject->widget($args, $settings[$widgetNr]);
        $html = ob_get_contents();
        ob_end_clean();

        // Return widget html
        return $html;
      }
    }

    /**
     * Handle depricated contact widget shortcode like [yog-contact-widget id=".."]
     *
     * @param array $attr
     * @return string
     */
    public function handleContactWidgetShortcode($attr)
    {
      $attr['type'] = 'contact';
      return $this->handleWidgetShortcode($attr);
    }

    /**
     * Handle map shortcode like [yog-map center_latitude=".." center_longitude=".." zoomlevel="9" map_type="terrain" width="100" width_unit="%" height="100" height_unit="%" control_map_type_position=".." control_pan_position=".." control_zoom_position=".."]
     * @param type $attr
     * @return type
     */
    public function handleMapShortcode($attr)
    {
      $mapWidget = new YogMapWidget();
      $settings  = $mapWidget->shortcodeAttributesToSettings($attr);

      return $mapWidget->generate($settings);
    }

    /**
     * Handle objects shortcode like [yob-objects type=".." num=".." cat=".." order=".."]
     * @param type $attr
     */
    public function handleObjectsShortcode($attr)
    {
      $output = '';
      // Determine provided params
      $type           = !empty($attr['type']) ? explode(',', $attr['type']) : array(YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG, YOG_POST_TYPE_BOPR, YOG_POST_TYPE_NBPR, YOG_POST_TYPE_NBTY, YOG_POST_TYPE_BBPR, YOG_POST_TYPE_BBTY);
      $showPagination = (!empty($attr['pagination']));
      $currentPage    = (int) get_query_var('page', 1);

      // Determine query attributes
      $query = array(
        'post_type'       => $type,
        'posts_per_page'  => (!empty($attr['num']) ? (int) $attr['num'] : 5000),
        'nopaging'        => (!empty($attr['num']) ? false : true),
        'paged'           => $currentPage
      );

      // Add category to the query?
      if (!empty($attr['cat']))
      {
        $query['tax_query'] = array(
          array(
            'taxonomy'  => (get_option('yog_cat_custom') ? 'yog_category' : 'category'),
            'field'     => 'slug',
            'terms'     => $attr['cat']
          )
        );
      }

      // Add order to the query?
      if (!empty($attr['order']) && in_array($attr['order'], array('date_asc', 'date_desc', 'title_asc', 'title_desc', 'price_asc', 'price_desc')))
      {
        list($orderBy, $order) = explode('_', $attr['order']);

        if ($orderBy == 'price')
        {
          $query['orderby']   = 'meta_value_num';
          $query['meta_key']  = 'yog_price_order';
        }
        else
        {
          $query['orderby'] = $orderBy;
        }

        $query['order'] = strtoupper($order);
      }

      // Retrieve posts
      $wpQuery  = new WP_Query($query);

      if ($wpQuery->have_posts())
      {
        while ($wpQuery->have_posts())
        {
          $wpQuery->the_post();

          // Use template to show object
          if (!empty($attr['template']))
          {
            ob_start();
            get_template_part('object', $attr['template']);
            $output .= ob_get_contents();
            ob_end_clean();
          }
          // Show default output
          else
          {
            $title      = get_the_title();
            $permalink  = get_permalink();

            $output .= '<div class="yog-post post-' . get_post_type() . '">';
            $output .= '<h2><a href="' . esc_url($permalink) . '" rel="bookmark" title="' . esc_attr($title) . '">' . yog_retrieveSpec('Naam') . '</a></h2>';

              if (has_post_thumbnail())
                $output .= '<a href="' . esc_url($permalink) . '" rel="bookmark" title="' . esc_attr($title) . '">' . get_the_post_thumbnail() . '</a>';

            $output .= '</div>';
          }
        }
      }

      if ($showPagination === true && $wpQuery->max_num_pages > 1)
      {
        $paginationType     = !empty($attr['pagination']) ? $attr['pagination'] : '';
        $paginationClass    = !empty($attr['pagination_class']) ? ' ' . esc_attr($attr['pagination_class']) . '' : '';
        $paginationUlClass  = !empty($attr['pagination_ul_class']) ? ' class="' . esc_attr($attr['pagination_ul_class']) . '"' : '';
        $supportedPrevNext  = ['glyphicon', 'fa', 'text'];
        $paginationPrevNext = (!empty($attr['pagination_prev_next']) && in_array($attr['pagination_prev_next'], $supportedPrevNext)) ? $attr['pagination_prev_next'] : '';

        // Determine link options
        $linkOptions        = ['type' => 'array', 'prev_next' => (!empty($paginationPrevNext)), 'total' => $wpQuery->max_num_pages, 'current' => $currentPage];

        // Add prev / next text to link options
        switch ($paginationPrevNext)
        {
          case 'glyphicon':
            $linkOptions['prev_text'] = '<span aria-hidden="true" class="glyphicon glyphicon-triangle-left"></span>';
            $linkOptions['next_text'] = '<span aria-hidden="true" class="glyphicon glyphicon-triangle-right"></span>';
            break;
          case 'fa':
            $linkOptions['prev_text'] = '<i class="fas fa-caret-left"></i>';
            $linkOptions['next_text'] = '<i class="fas fa-caret-right"></i>';
            break;
        }

        // Generate links
        $pageLinks          = paginate_links($linkOptions);

        // Generate navigation output
        $output .= '<nav class="yog-objects-shortcode-navigation' . $paginationClass . '">';

        switch ($paginationType)
        {
          case 'ul':
            $output .= '<ul' . $paginationUlClass . '><li>' . implode('</li><li>', $pageLinks) . '</li></ul>';
            break;
          default:
            $output .= implode('', $pageLinks);
            break;
        }

        $output .= '</nav>';
      }

      return $output;
    }

    /**
     * Method handleMhzRetrieveUrlShortcode
     *
     * @param unknown $attr
     * @return string
     */
    public function handleMhzRetrieveUrlShortcode($attr)
    {
      if (!empty($attr['service']))
      {
				$mhzApiKey = get_option('yog_mijnhuiszaken_api_key');

				if (!empty($mhzApiKey))
				{
					$mhzUrl = (defined('YOG_MHZ_URL') ? YOG_MHZ_URL : 'https://mijnhuiszaken.nl');

					switch ($attr['service'])
					{
						// Register account
						case 'account-register':
						case 'registreer':
							$mhzUrl .= '/account/inschrijven';
							break;
						// Login
						case 'login':
							break;
						// No supported service
						default:
							return;
					}

					return $mhzUrl . '?api_key=' . $mhzApiKey;
				}
      }
    }

    /**
     * Method handleEmployeesBarShortcode
     *
     * @param unknown $attr
     * @return void
     */
    public function handleEmployeesBarShortcode($attr)
    {
      $customTemplate = locate_template('yesco/parts/employees.php');

      $style = (!empty($attr['style']) ? $attr['style'] : '');

      $output         = '';

      // Enqueue styles
      if (!(empty($customTemplate) && file_exists($customTemplate)))
      {
        ob_start();

        set_query_var( 'yogShortcode', 'shortcode-bar' );
        set_query_var( 'yogStyle', $style );

        load_template($customTemplate);
        $output .= ob_get_contents();
        ob_end_clean();
      }

      return $output;
    }

  }

  /**
  * @desc YogPluginAdmin
  * @author Kees Brandenburg - Yes-co Nederland
  */
  class YogPluginAdmin extends YogPlugin
  {
    private $optionGroup = 'yesco_OG';

    /**
    * @desc Initialize Wordpress admin
    *
    * @param void
    * @return void
    */
    public function init()
    {
      parent::init();

      add_action('admin_menu',              array($this, 'createAdminMenu'));
      add_action('init',                    array($this, 'enqueueFiles'));
      add_action('init',                    array($this, 'checkPluginVersion'));
      add_filter('editable_slug',           array($this, 'fixEditableparmalinkSlug'));
      add_action('wp_dashboard_setup',      array($this, 'initDashboardWidgets'));

      // Ajax callbacks
      add_action('wp_ajax_setsetting',      array($this, 'ajaxSetSetting'));
      add_action('wp_ajax_addkoppeling',    array($this, 'addSystemLink'));
      add_action('wp_ajax_removekoppeling', array($this, 'ajaxRemoveSystemLink'));
      //

      // Init custom post type admin pages
      if (!empty($_REQUEST['post_type']) || !empty($_REQUEST['post']))
      {
        require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_wp_admin_object_ui.php');

        $postType  = empty($_REQUEST['post_type']) ? get_post_type((int) $_REQUEST['post']) : sanitize_text_field($_REQUEST['post_type']);
        $wpAdminUi = YogWpAdminUiAbstract::create($postType);
        if (!is_null($wpAdminUi))
          $wpAdminUi->initialize();
      }
    }

    /**
    * @desc Check the current plugin version
    *
    * @param void
    * @return void
    */
    public function checkPluginVersion()
    {
      // Check plugin version
      $currentVersion = get_option('yog_plugin_version');
      if (empty($currentVersion))
        $currentVersion = '0';

      if ($currentVersion != YOG_PLUGIN_VERSION)
      {
        // Make sure rewrite rules are up-to-date
        $this->registerPostTypes();
        flush_rewrite_rules();

        // Remove unused project images when updated from version 1.2.5 or smaller
        if (version_compare($currentVersion, '1.2.5', '<='))
          $this->removeUnusedProjectImages();

        // Register update open houses cron when not already active
        if (!wp_next_scheduled('yog_cron_open_houses'))
          wp_schedule_event(time(), 'hourly', 'yog_cron_open_houses');

        // Update projects order price when updated from version 1.3.9 or smaller
        if (version_compare($currentVersion, '1.3.9', '<='))
          $this->updateProjectsWithPriceOrder();

        // Update projects order city/street when updated from version 1.3.37 or smaller
        if (version_compare($currentVersion, '1.3.37', '<='))
          $this->updateProjectsWithCityStreetOrder();

        // Update plugin version
        update_option('yog_plugin_version', YOG_PLUGIN_VERSION);
      }
    }

    /**
    * @desc Fix editable permalink slug for NBty/BBty
    *
    * @param string $slug
    * @return string
    */
    public function fixEditableparmalinkSlug($slug)
    {
      if (!empty($GLOBALS['post']))
      {
        $post = $GLOBALS['post'];
      }
      else if (!empty($_POST['post_id']))
      {
        $postId   = (int) $_POST['post_id'];
        $post     = get_post($postId);
      }

			$newSlug = empty($_POST['new_slug']) ? null : sanitize_text_field($_POST['new_slug']);

      if (isset($post) && in_array($post->post_type, array(YOG_POST_TYPE_NBTY, YOG_POST_TYPE_BBTY)) && $slug != $post->post_name && (empty($newSlug) || $newSlug != $slug))
        $slug = $slug . '/type';

      return $slug;
    }

    /**
    * @desc Init the dashboard widgets
    *
    * @param void
    * @return void
    */
    public function initDashboardWidgets()
    {
      wp_add_dashboard_widget('yog-last-updated-objects', 'Laatst gewijzigde objecten', array($this, 'lastUpdatedProjectsDashboardWidget'));
    }

    public function lastUpdatedProjectsDashboardWidget()
    {
      $objects = get_posts(array( 'numberposts' => 5,
                                  'post_type'   => array(YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG, YOG_POST_TYPE_NBPR, YOG_POST_TYPE_NBTY, YOG_POST_TYPE_BBPR, YOG_POST_TYPE_BBTY, YOG_POST_TYPE_BOPR),
                                  'orderby'     => 'modified'));

	    // Display whatever it is you want to show
      if (is_array($objects) && count($objects) > 0)
      {
        $thumbnailWidth   = get_option('thumbnail_size_w', 0);

        if ($thumbnailWidth > 300)
          $thumbnailWidth = 300;

        $noImageHtml      = '<div class="no-image" style="width:' . esc_attr($thumbnailWidth) . 'px;"></div>';

        echo '<table class="wp-list-table widefat fixed posts">';
          echo '<tbody>';

          foreach ($objects as $object)
          {
            $thumbnail = get_the_post_thumbnail($object->ID, 'thumbnail');

            if (empty($thumbnail))
              $thumbnail = $noImageHtml;

            $scenario = yog_retrieveSpec('scenario', $object->ID);

            // Determine admin links
            $links = array();

            if ($object->post_status != 'trash')
              $links[] = '<a href="' . esc_url(get_edit_post_link($object->ID)) . '">' . __('Edit') . '</a>';
            if ($scenario != 'NBbn' && $object->post_status != 'trash')
              $links[] = '<a href="' . esc_url(get_permalink($object->ID)) . '">' . __('View') . '</a>';

            // Determine title
            $title = esc_html($object->post_title);

            if ($object->post_status != 'trash')
              $title = '<a href="' . esc_url(get_edit_post_link($object->ID)) . '">' . $title . '</a>';

            echo '<tr>';
            echo '<td style="width:' . esc_attr(($thumbnailWidth + 10)) . 'px;">' . $thumbnail . '</td>';
            echo '<td>';
              echo '<strong>' . $title . '</strong>';
              echo '<div class="row-actions"><span>' . implode(' | </span><span>', $links) . '</span></div>';
            echo '</td>';
            echo '</tr>';
          }

          echo '</tbody>';
        echo '</table>';
      }
      else
      {
        echo '<p>Er zijn nog geen objecten gepubliceerd</p>';
      }
    }

    /**
    * @desc Enqueue files
    *
    * @param void
    * @return void
    */
    public function enqueueFiles()
    {
      parent::enqueueFiles();

      $minifyExtension = (YOG_DEBUG_MODE === true) ? '' : '.min';

      wp_enqueue_script('yog-admin-js',   YOG_PLUGIN_URL .'/inc/js/admin' . $minifyExtension . '.js', array('jquery'), YOG_PLUGIN_VERSION);
      wp_enqueue_style('yog-admin-css',   YOG_PLUGIN_URL . '/inc/css/admin.css', array(), YOG_PLUGIN_VERSION);
			add_thickbox();
    }

    /**
    * @desc Create admin menu
    *
    * @param void
    * @return void
    */
    public function createAdminMenu()
    {
      add_menu_page('Yes-co ORES', 'Yes-co ORES', 'edit_posts', 'yog_posts_menu', '', YOG_PLUGIN_URL . '/img/icon_yes-co.gif', 21);
      remove_submenu_page('yog_posts_menu', 'edit.php?post_type=' . YOG_POST_TYPE_NBTY);
      remove_submenu_page('yog_posts_menu', 'edit.php?post_type=' . YOG_POST_TYPE_NBBN);
      remove_submenu_page('yog_posts_menu', 'edit.php?post_type=' . YOG_POST_TYPE_BBTY);

      if (get_option('yog_cat_custom'))
        add_submenu_page('yog_posts_menu', __('Categories'), __('Categories'), 'manage_options', 'edit-tags.php?taxonomy=yog_category');

      add_options_page('Yes-co ORES opties', 'Yes-co ORES', 'manage_options', 'yesco_OG', array($this, 'renderSettingsPage'));
      add_options_page('Yes-co ORES Synchronisatie', 'Synchronisatie', 'manage_options', 'yesco_OG_synchronisation', array($this, 'renderSynchronisationPage'));
      add_options_page('Yes-co ORES HTML', 'HTML', 'manage_options', 'yesco_OG_html', array($this, 'renderHtmlPage'));
      add_options_page('Yes-co ORES Wijken / buurten', 'Wijken / buurten', 'manage_options', 'yesco_OG_areas', array($this, 'renderAreasPage'));
      add_options_page('Yes-co ORES Response formulieren', 'Response formulieren', 'manage_options', 'yesco_OG_responseforms', array($this, 'renderResponseFormsPage'));
      add_options_page('Yes-co ORES MijnHuiszaken', 'MijnHuiszaken', 'manage_options', 'yesco_OG_mijnhuiszaken', array($this, 'renderMijnHuiszakenPage'));
      add_options_page('Yes-co ORES Google Maps', 'Google Maps', 'manage_options', 'yesco_OG_googlemaps', array($this, 'renderGoogleMapsPage'));
      add_options_page('Map shortcode generator', 'Map shortcode generator', 'manage_options', 'yesco_OG_shortcode_map', array($this, 'renderShortcodeMapPage'));
      add_options_page('Objecten shortcode generator', 'Objecten shortcode generator', 'manage_options', 'yesco_OG_shortcode_objects', array($this, 'renderShortcodeObjectsPage'));
      add_options_page('Yes-co ORES Geavanceerd', 'Geavanceerd', 'manage_options', 'yesco_OG_advanced', array($this, 'renderAdvancedPage'));
      add_options_page('Yes-co ORES Rest API', 'Rest API', 'manage_options', 'yesco_OG_api', array($this, 'renderApiSettingsPage'));

	    remove_submenu_page('options-general.php', 'yesco_OG_synchronisation');
			remove_submenu_page('options-general.php', 'yesco_OG_areas');
			remove_submenu_page('options-general.php', 'yesco_OG_html');
			remove_submenu_page('options-general.php', 'yesco_OG_responseforms');
			remove_submenu_page('options-general.php', 'yesco_OG_mijnhuiszaken');
			remove_submenu_page('options-general.php', 'yesco_OG_googlemaps');
      remove_submenu_page('options-general.php', 'yesco_OG_shortcode_map');
      remove_submenu_page('options-general.php', 'yesco_OG_shortcode_objects');
			remove_submenu_page('options-general.php', 'yesco_OG_advanced');
      remove_submenu_page('options-general.php', 'yesco_OG_api');
    }

    /**
    * @desc Render plugin settings page
    *
    * @param void
    * @return void
    */
    public function renderSettingsPage()
    {
      require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_system_link_manager.php');
      require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_checks.php');

      // Checks
      $errors 	= YogChecks::checkForErrors();
      $warnings = YogChecks::checkForWarnings();

      if (empty($errors))
      {
        // Sort options
        $sortOptions  = array('date_asc' => 'datum oplopend', '' => 'datum aflopend',
                              'title_asc' => 'titel oplopend', 'title_desc' => 'titel aflopend',
															'city_asc'	=> 'plaats oplopend', 'city_desc'	=> 'plaats aflopend',
                              'price_asc' => 'prijs oplopend', 'price_desc' => 'prijs aflopend');
        $sortOption   = get_option('yog_order');

				// BOG rental price search options
				$bogRentalpriceSearchOptions	= array('pm' => 'Prijs per maand', 'pj' => 'Prijs per jaar', '' => 'Zoals opgegeven (p.m. / p.j. / p.m2.j door elkaar)');
				$bogRentalpriceSearchOption		= get_option('yog_search_bog_rentalprice_type', '');
      }

      // Render html
      include(YOG_PLUGIN_DIR . '/includes/pages/settings.php');
    }

    /**
     * @desc Render plugin synchronisation page
     *
     * @param void
     * @return void
     */
    public function renderSynchronisationPage()
    {
        require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_system_link_manager.php');
        require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_checks.php');
				require_once(YOG_PLUGIN_DIR . '/includes/yog_private_functions.php');

        // Checks
        $errors 	= YogChecks::checkForErrors();
        $warnings = YogChecks::checkForWarnings();

        if (empty($errors))
        {
            // Init system link manager
            $systemLinkManager  = new YogSystemLinkManager();

            // Store username/password
            if (!empty($_POST) && current_user_can('manage_options') && !empty($_POST['activation_code']) && !empty($_POST['collection_uuid']))
            {
                try
                {
                    $systemLink = $systemLinkManager->retrieveByActivationCode(sanitize_text_field($_POST['activation_code']));

                    if (!empty($_POST['name']) && !empty($_POST['_wpnonce_name_' . $_POST['activation_code']]) && wp_verify_nonce($_POST['_wpnonce_name_' . $_POST['activation_code']], 'yog-update-system-link-name-' . sanitize_text_field($_POST['activation_code']))) {
                        $systemLink->setName(sanitize_text_field($_POST['name']));

                        $systemLinkManager->store($systemLink);

                        echo '<div class="notice notice-success"><p>Koppeling naam is aangepast.</p></div>';
                    } elseif (!empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['_wpnonce_' . $_POST['activation_code']]) && wp_verify_nonce($_POST['_wpnonce_' . $_POST['activation_code']], 'yog-update-system-link-' . sanitize_text_field($_POST['activation_code']))) {
                        $systemLink->setCredentials(new \YogSystemLinkCredentials(sanitize_text_field($_POST['username']), sanitize_text_field($_POST['password'])));

                        $systemLinkManager->store($systemLink);

                        echo '<div class="notice notice-success"><p>Koppeling gebruikersnaam/wachtwoord is aangepast.</p></div>';
                    } elseif (!empty($_POST['sync_action']) && !empty($_POST['_wpnonce_sync_' . $_POST['activation_code']]) && wp_verify_nonce($_POST['_wpnonce_sync_' . $_POST['activation_code']], 'yog-update-system-link-sync-' . sanitize_text_field($_POST['activation_code']))) {
                        $systemLink->setSyncEnabled(empty($_POST['sync_disabled']));

                        $systemLinkManager->store($systemLink);

                        echo '<div class="notice notice-success"><p>Synchronisatie is ' . ($systemLink->isSyncEnabled() ? 'aan' : 'uit') . ' gezet.</p></div>';
                    }
                }
                catch (\Exception $e)
                {
                    echo '<div class="notice notice-error"><p>Koppeling kon niet bijgewerkt worden.</p></div>';
                }
            }

            global $wpdb;

            // Retrieve system links
            $systemLinks        = $systemLinkManager->retrieveAll();
            $systemObjectCounts	= array();

            // Retrieve number of objects per system (not if a lot of systems are linked)
            if (!empty($systemLinks) && count($systemLinks) < 10)
            {
              foreach ($systemLinks as $systemLink)
              {
                if ($systemLink->getState() === YogSystemLink::STATE_ACTIVE)
                {
                  $metaKeys = array();
                  foreach (yog_getAllObjectPostTypes() as $postType)
                  {
                      $metaKeys[] = $postType . '_' . $systemLink->getCollectionUuid() . '_uuid';
                  }

                  $sql  = 'SELECT COUNT(ID) AS count FROM ' . $wpdb->posts;
                  $sql .= ' WHERE post_type IN (\'' . implode('\',\'', yog_getAllObjectPostTypes()) . '\')';
                  $sql .= ' AND EXISTS (';
                  $sql .= 'SELECT true FROM ' . $wpdb->postmeta;
                  $sql .= ' WHERE ' . $wpdb->postmeta . '.post_id=' . $wpdb->posts . '.ID';
                  $sql .= ' AND meta_key IN (\'' . implode('\',\'', $metaKeys) . '\')';
                  $sql .= ')';

                  $results  = $wpdb->get_results($sql);

                  $systemObjectCounts[$systemLink->getCollectionUuid()] = (int) $results[0]->count;
                }
              }
            }

            // Media size options
            $mediaSizeOptions = array('medium' => 'Medium (1280x1280)', 'large' => 'Large (1600x1600)');
            $mediaSizeOption  = get_option('yog_media_size', 'medium');
        }

        // Render html
        include(YOG_PLUGIN_DIR . '/includes/pages/synchronisation.php');
    }

    /**
     * Render html settings
     */
    public function renderHtmlPage()
    {
      // Render html
      include(YOG_PLUGIN_DIR . '/includes/pages/html.php');
    }

    /**
     * Render response formulieren settings
     */
    public function renderResponseFormsPage()
    {
			if (!empty($_POST) && current_user_can('manage_options') && !empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'yog-response-forms'))
			{
				$responseFormsApiKey = empty($_POST['yog_response_forms_api_key']) ? null : sanitize_text_field($_POST['yog_response_forms_api_key']);

				if (!is_null($responseFormsApiKey))
				{
				  update_option('yog_response_forms_api_key', $responseFormsApiKey);
				}
				else
				{
					delete_option('yog_response_forms_api_key');
				}
			}

      // Render html
      include(YOG_PLUGIN_DIR . '/includes/pages/responseforms.php');
    }

    /**
     * Render response mijnhuiszaken settings
     */
    public function renderMijnHuiszakenPage()
    {
			if (!empty($_POST) && current_user_can('manage_options') && !empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'yog-mijn-huiszaken'))
			{
				$apiKey									= empty($_POST['yog_mijnhuiszaken_api_key']) ? null : sanitize_text_field($_POST['yog_mijnhuiszaken_api_key']);

				if (!empty($apiKey))
					update_option('yog_mijnhuiszaken_api_key', $apiKey);
				else
					delete_option('yog_mijnhuiszaken_api_key');
			}

      // Render html
      include(YOG_PLUGIN_DIR . '/includes/pages/mijnhuiszaken.php');
    }

    /**
     * Render google maps settings
     */
    public function renderGoogleMapsPage()
    {
			// Store settings
			if (!empty($_POST) && current_user_can('manage_options') && !empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'yog-settings'))
			{
        $mapType  = empty($_POST['yog_map_type']) ? 'google-maps' : sanitize_text_field($_POST['yog_map_type']);
				$apiKey   = empty($_POST['yog_google_maps_api_key']) ? null : sanitize_text_field($_POST['yog_google_maps_api_key']);

        // Update map type
        if (!empty($mapType))
          update_option('yog_map_type', $mapType);
        else
          delete_option('yog_map_type');

        // Update Google Maps API key
				if (!empty($apiKey))
				{
					// Update API Key
					update_option('yog_google_maps_api_key', $apiKey);
				}
				else
				{
					delete_option('yog_google_maps_api_key');
				}
			}

      // Render html
      include(YOG_PLUGIN_DIR . '/includes/pages/googlemaps.php');
    }

    /**
     * Render maps shortcode generator
     */
    public function renderShortcodeMapPage()
    {
      $shortcode = (!empty($_GET['shortcode']) ? sanitize_text_field($_GET['shortcode']) : '');

      $yogMapWidget = new YogMapWidget();
      $settings     = $yogMapWidget->shortcodeToSettings($shortcode);
      $postTypes    = yog_getAllPostTypes();

      include(YOG_PLUGIN_DIR . '/includes/pages/shortcode_map.php');
    }

    /**
     * Render objects shortcode generator
     */
    public function renderShortcodeObjectsPage()
    {
      $minifyExtension = (YOG_DEBUG_MODE === true) ? '' : '.min';

      wp_enqueue_script('yog-admin-objects-shortcode-js',   YOG_PLUGIN_URL .'/inc/js/admin_objects_shortcode' . $minifyExtension . '.js', array('jquery'), YOG_PLUGIN_VERSION);

      $postTypes      = array(YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG, YOG_POST_TYPE_NBPR, YOG_POST_TYPE_NBTY, YOG_POST_TYPE_BBPR, YOG_POST_TYPE_BBTY, YOG_POST_TYPE_BOPR);
      $sortOptions    = array('date_asc' => 'datum oplopend', '' => 'datum aflopend',
                              'title_asc' => 'titel oplopend', 'title_desc' => 'titel aflopend',
                              'price_asc' => 'prijs oplopend', 'price_desc' => 'prijs aflopend');
      $sortOption     = get_option('yog_order');
      $categories     = $this->retrieveHierarchinalCategories((get_option('yog_cat_custom') ? 'yog_category' : 'category'));

      $files          = $this->listThemeFiles();
      $templateFiles  = array();

      foreach ($files as $file)
      {
        $file = basename($file);
        if (strpos($file, 'object-') !== false)
          $templateFiles[] = str_replace(array('object-', '.php'), '', $file);
      }

      include(YOG_PLUGIN_DIR . '/includes/pages/shortcode_objects.php');
    }

		private function retrieveHierarchinalCategories($taxonomy, $catId = 0)
		{
			$categories     = get_categories(array('taxonomy' => $taxonomy, 'orderby' => 'name', 'parent' => $catId));

			if (!empty($categories))
			{
				foreach ($categories as $category)
				{
					$childs = $this->retrieveHierarchinalCategories($taxonomy, $category->term_id);
					if (!empty($childs))
					{
						$category->childs = $childs;
					}
				}
			}

			return $categories;
		}

		/**
		 * Render area/neighbourhood settings
		 */
		public function renderAreasPage()
		{
			// Handle post
			if (!empty($_POST) && isset($_POST['custom_area']))
			{
				// Check if current user has enough rights to edit plugin settings (should already be done by wordpress, but just to be sure)
				if (!current_user_can('manage_options'))
					wp_die();

				// Check nonce
				if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'yog-areas'))
				{
					echo 'Fout: Je hebt de pagina te lang open staan, herlaad de pagina en probeer opnieuw.';
					wp_die();
				}

				// Collect set custom areas
				$prevCustomAreas	= get_option('yog_custom_areas', array());
				$customAreas			= array();
				if (is_array($_POST['custom_area']))
				{
					foreach ($_POST['custom_area'] as $key => $values)
					{
						if (is_array($values) && !empty($values['city']) && !empty($values['org']))
						{
							if (isset($values['value']) && strlen(trim($values['value'])) > 0)
							{
								// Sanitze input
								$values['city']			= sanitize_text_field($values['city']);
								$values['value']		= sanitize_text_field($values['value']);
								$values['org']			= sanitize_text_field($values['org']);

								// Store in custom areas
								$customAreas[$key]	= $values;
							}

							// Determine the prev/new value
							$prevValue	= (isset($prevCustomAreas[$key]) ? $prevCustomAreas[$key]['value'] : null);
							$newValue		= (isset($customAreas[$key]) ? $customAreas[$key]['value'] : null);

							// Update meta values for projects
							if ($newValue !== $prevValue)
							{
								// Retrieve projects with specified area
								$objects = yog_retrieveObjectsByAddress($values['city'], $values['org'], null, false);

								// Update/insert meta value
								if (!is_null($newValue))
								{
									foreach ($objects as $object)
									{
										update_post_meta($object->ID, $object->post_type . '_WijkCustom', $newValue);
									}
								}
								// Delete meta value
								else if (!is_null($prevValue))
								{
									foreach ($objects as $object)
									{
										delete_post_meta($object->ID, $object->post_type . '_WijkCustom');
									}
								}
							}
						}
					}
				}

				// Collect set custom neighbourhoods
				$prevCustomNeighbourhoods	= get_option('yog_custom_neighbourhoods', array());
				$customNeighbourhoods			= array();

				if (isset($_POST['custom_neighbourhood']) && is_array($_POST['custom_neighbourhood']))
				{
					foreach ($_POST['custom_neighbourhood'] as $key => $values)
					{
						if (is_array($values) && !empty($values['city']) && !empty($values['area']) && !empty($values['org']))
						{
							if (isset($values['value']) && strlen(trim($values['value'])) > 0)
							{
								// Sanitize input
								$values['city']			= sanitize_text_field($values['city']);
								$values['area']			= sanitize_text_field($values['area']);
								$values['org']			= sanitize_text_field($values['org']);
								$values['value']		= sanitize_text_field($values['value']);

								// Store in custom neighbourhoods variable
								$customNeighbourhoods[$key] = $values;
							}

							// Determine the prev/new value
							$prevValue	= (isset($prevCustomNeighbourhoods[$key]) ? $prevCustomNeighbourhoods[$key]['value'] : null);
							$newValue		= (isset($customNeighbourhoods[$key]) ? $customNeighbourhoods[$key]['value'] : null);

							// Update meta values for projects
							if ($newValue !== $prevValue)
							{
								// Retrieve projects with specified neighbourhood
								$objects = yog_retrieveObjectsByAddress($values['city'], $values['area'], $values['org'], false);

								// Update/insert meta value
								if (!is_null($newValue))
								{
									foreach ($objects as $object)
									{
										update_post_meta($object->ID, $object->post_type . '_BuurtCustom', $newValue);
									}
								}
								// Delete meta value
								else if (!is_null($prevValue))
								{
									foreach ($objects as $object)
									{
										delete_post_meta($object->ID, $object->post_type . '_BuurtCustom');
									}
								}
							}
						}
					}
				}

				// Store custom areas
				if (count($customAreas) > 0)
					update_option('yog_custom_areas', $customAreas, false);
				else
					delete_option('yog_custom_areas');

				// Store custom neighbourhoods
				if (count($customNeighbourhoods) > 0)
					update_option('yog_custom_neighbourhoods', $customNeighbourhoods, false);
				else
					delete_option('yog_custom_neighbourhoods');
			}

			// Retrieve all areas/neighbourhoods from database
			global $wpdb;

			$sql  = 'SELECT DISTINCT ';
			$sql .= ' (SELECT UPPER(meta_value) FROM ' . $wpdb->postmeta . ' WHERE post_id=' . $wpdb->posts . '.ID AND meta_key IN (\'' . YOG_POST_TYPE_WONEN . '_Plaats\',\'' . YOG_POST_TYPE_BOG . '_Plaats\',\'' . YOG_POST_TYPE_NBPR . '_Plaats\',\'' . YOG_POST_TYPE_BBPR . '_Plaats\',\'' . YOG_POST_TYPE_BOPR . '_Plaats\')) AS city, ';
			$sql .= ' (SELECT meta_value FROM ' . $wpdb->postmeta . ' WHERE post_id=' . $wpdb->posts . '.ID AND meta_key IN (\'' . YOG_POST_TYPE_WONEN . '_Wijk\',\'' . YOG_POST_TYPE_BOG . '_Wijk\',\'' . YOG_POST_TYPE_NBPR . '_Wijk\',\'' . YOG_POST_TYPE_BBPR . '_Wijk\',\'' . YOG_POST_TYPE_BOPR . '_Wijk\')) AS area, ';
			$sql .= ' (SELECT meta_value FROM ' . $wpdb->postmeta . ' WHERE post_id=' . $wpdb->posts . '.ID AND meta_key IN (\'' . YOG_POST_TYPE_WONEN . '_Buurt\',\'' . YOG_POST_TYPE_BOG . '_Buurt\',\'' . YOG_POST_TYPE_NBPR . '_Buurt\',\'' . YOG_POST_TYPE_BBPR . '_Buurt\',\'' . YOG_POST_TYPE_BOPR . '_Buurt\')) AS neighbourhood ';
			$sql .= ' FROM ' . $wpdb->posts;
			$sql .= ' WHERE post_type IN (\'' . YOG_POST_TYPE_WONEN . '\',\'' . YOG_POST_TYPE_BOG . '\',\'' . YOG_POST_TYPE_NBPR . '\',\'' . YOG_POST_TYPE_BBPR . '\',\'' . YOG_POST_TYPE_BOPR . '\') AND post_status = \'publish\'';

			$results		= $wpdb->get_results($sql);
			$locations	= array();

			if (is_array($results) && count($results) > 0)
			{
				foreach ($results as $result)
				{
					if (!empty($result->city) && !empty($result->area))
					{
						$areaKey = $result->city . '/' . strtoupper($result->area);

						if (!array_key_exists($result->city, $locations))
							$locations[$result->city] = array();

						if (!array_key_exists($areaKey, $locations[$result->city]))
							$locations[$result->city][$areaKey] = array('label' => $result->area, 'neighbourhoods' => array());

						if (!empty($result->neighbourhood))
						{
							$neighbourhoodKey	= $areaKey . '/' . strtoupper($result->neighbourhood);

							if (!array_key_exists($neighbourhoodKey, $locations[$result->city][$areaKey]))
								$locations[$result->city][$areaKey]['neighbourhoods'][$neighbourhoodKey] = array('label' => $result->neighbourhood);
						}
					}
				}

				ksort($locations);
			}

			// Retrieve stored areas
			$customAreas = get_option('yog_custom_areas', array());

			if (!empty($customAreas))
			{
				foreach ($customAreas as $areaKey => $values)
				{
					if (!array_key_exists($values['city'], $locations))
						$locations[$values['city']] = array();

					if (!array_key_exists($areaKey, $locations[$values['city']]))
						$locations[$values['city']][$areaKey] = array('label' => $values['org'], 'value' => $values['value'], 'neighbourhoods' => array());
					else
						$locations[$values['city']][$areaKey]['value'] = $values['value'];
				}
			}

			// Retrieve stored neighbourhoods
			$customNeighbourhoods = get_option('yog_custom_neighbourhoods', array());

			if (!empty($customNeighbourhoods))
			{
				foreach ($customNeighbourhoods as $neighbourhoodKey => $values)
				{
					$areaKey = strtoupper($values['area']);

					if (!array_key_exists($values['city'], $locations))
						$locations[$values['city']] = array();

					if (!array_key_exists($areaKey, $locations[$values['city']]))
						$locations[$values['city']][$areaKey] = array('label' => $values['area'], 'neighbourhoods' => array());

					if (!array_key_exists($neighbourhoodKey, $locations[$values['city']][$areaKey]['neighbourhoods']))
						$locations[$values['city']][$areaKey]['neighbourhoods'][$neighbourhoodKey] = array('label' => $values['org'], 'value' => $values['value']);
					else
						$locations[$values['city']][$areaKey]['neighbourhoods'][$neighbourhoodKey]['value'] = $values['value'];
				}
			}

			if (!empty($locations))
				ksort($locations);

      // Render html
      include(YOG_PLUGIN_DIR . '/includes/pages/areas.php');
		}

		/**
		 * Render advanced settings page
		 */
		public function renderAdvancedPage()
		{
			// Handle post
			if (!empty($_POST) && isset($_POST['advanced']))
			{
				// Check if current user has enough rights to edit plugin settings (should already be done by wordpress, but just to be sure)
				if (!current_user_can('manage_options'))
					wp_die();

				// Check nonce
				if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'yog-advanced'))
				{
					echo 'Fout: Je hebt de pagina te lang open staan, herlaad de pagina en probeer opnieuw.';
					wp_die();
				}

				// Store no delete meta keys
				if (isset($_POST['advanced']['no_delete_meta_keys']))
				{
					$providedNoDeleteMetaKeys =	!empty($_POST['advanced']['no_delete_meta_keys']) ? explode("\n", $_POST['advanced']['no_delete_meta_keys']) : array();
					$storeNoDeleteMetaKeys		= array();

					foreach ($providedNoDeleteMetaKeys as $providedNoDeleteMetaKey)
					{
						$providedNoDeleteMetaKey = str_replace(array(';', "\t", "\r"), '', trim($providedNoDeleteMetaKey));

						if (!empty($providedNoDeleteMetaKey))
							$storeNoDeleteMetaKeys[] = $providedNoDeleteMetaKey;
					}

					if (count($storeNoDeleteMetaKeys) > 0)
						update_option('yog_no_delete_meta_keys', $storeNoDeleteMetaKeys, false);
					else
						delete_option('yog_no_delete_meta_keys');
				}
			}

			// Retrieve no delete meta keys
			$noDeleteMetaKeys = get_option('yog_no_delete_meta_keys', array());

      // Render html
      include(YOG_PLUGIN_DIR . '/includes/pages/advanced.php');
		}

		/**
		 * Render API settings page
		 */
		public function renderAPISettingsPage()
		{
      require_once(__DIR__ . '/yog_rest_api_key.php');
      require_once(__DIR__ . '/yog_rest_api_key_manager.php');
      
      $apiKeyManager  = new YogRestApiKeyManager();
      $errors         = [];

			// Handle post
			if (!empty($_POST) && isset($_POST['mode']) && in_array($_POST['mode'], ['create', 'delete']))
			{
        try
        {
          // Check if current user has enough rights to edit plugin settings (should already be done by wordpress, but just to be sure)
          if (!current_user_can('manage_options'))
            throw new \InvalidArgumentException('Je hebt niet de juiste rechten om deze pagina te mogen zien');

          // Determine nonce name (based on mode / key)
          $mode         = sanitize_text_field($_POST['mode']);
          $key          = (empty($_POST['key']) ? null : sanitize_text_field($_POST['key']));
          $nonceName    = '_wpnonce-api-' . $mode . (empty($key) ? '' : '-' . $key);
          $nonceAction  = 'yog-api-' . $mode . (empty($key) ? '' : '-' . $key);

          // Check nonce
          if (empty($_POST[$nonceName]) || !wp_verify_nonce($_POST[$nonceName], $nonceAction))
            throw new \InvalidArgumentException('Je hebt de pagina te lang open staan, herlaad de pagina en probeer opnieuw.');

          switch ($mode)
          {
            // Handle creattion of API key
            case 'create':

              if (!empty($_POST['name']))
              {
                // Create new API key
                $apiKey = new YogRestApiKey(wp_generate_uuid4(), sanitize_text_field($_POST['name']));

                // Store the API key
                $apiKeyManager->store($apiKey);
              }

              break;

            // Handle deltion of API key
            case 'delete':

              if (!empty($key))
              {
                // Retrieve API key
                $apiKey = $apiKeyManager->retrieveByKey($key);

                // Delete API key
                $apiKeyManager->delete($apiKey);
              }

              break;
          }
        }
        catch (\InvalidArgumentException $e)
        {
          $errors[] = $e->getMessage();
        }
        catch (\Exception $e)
        {
          $errors[] = 'Er is een fout opgetreden';
        }
			}

      // Retrieve all API keys
      $apiKeys  = $apiKeyManager->retrieveAll();

      // Render html
      include(YOG_PLUGIN_DIR . '/includes/pages/api.php');
		}

    /**
     * @desc Method renderRow
     *
     * @param {String} $label
     * @param {String} $value
     * @return {String}
     */
    public function renderRow($label, $value)
    {
      $html = '';

      $html .= '<tr valign="top">';
	      $html .= '<th scope="row">' . $label . '</th>';
        $html .= '<td><div style="margin-bottom: 10px;">' . $value . '</div></td>';
      $html .= '</tr>';

      return $html;
    }

    /**
     * @desc Method section
     *
     * @param {Void}
     * @return {String}
     */
    public function section()
    {
      echo '<p>Stel hier je eigen gewenste plaatjes in voor de markers op de map:</p>';
    }

    /**
     * @desc Method inputFile
     *
     * @param {Array}
     * @return {Void}
     */
    public function inputFile($args)
    {
      $logoOptions = $args[0];
      $postType    = $args[1];
      $optionName  = $args[2];
      $filesKey    = 'marker_type_' . $postType;

      if (!empty($_FILES) && !empty($_FILES[$filesKey]) && !empty($_FILES[$filesKey]['tmp_name']) && current_user_can('manage_options') && !empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'yog-settings'))
      {
        $response = wp_handle_upload($_FILES[$filesKey], array('test_form' => false));

        if (!empty($response))
        {
          $imageSize          = getimagesize($response['file']);
          $response['width']  = $imageSize[0];
          $response['height'] = $imageSize[1];

          // Remove old logo
          $options  = get_option($optionName);

          if (!($options === false || empty($options['file'])))
            @unlink($options['file']);

          // Update logo settings
					update_option($optionName, $response);

					$logoOptions = $response;
        }
      }
			else if (!empty($_POST[$filesKey . '_delete']) && $logoOptions !== false && !empty($logoOptions['file']))
			{
				@unlink($logoOptions['file']);
				delete_option($optionName);

				$logoOptions = false;
			}

      $html = '';

      if ($logoOptions === false || empty($logoOptions['url']))
        $logoUrl = YOG_PLUGIN_URL . '/img/maps_marker.png';
      else
        $logoUrl = $logoOptions['url'];

      $html .= '<div style="margin-bottom:10px;max-width:400px;">';
				$html .= '<img style="float: right;" src="' . esc_url($logoUrl) . '" alt="" />';
				$html .= '<input type="file" name="marker_type_' . esc_attr($postType) . '" accept="image/png,image/gif" />';

				if ($logoOptions !== false && !empty($logoOptions['url']))
					$html .= '<br /><label><input type="checkbox" name="marker_type_' . esc_attr($postType) . '_delete" value="Y" /> Verwijder ingestelde marker afbeelding</label>';

      $html .= '</div>';

      echo $html;
    }

    /**
    * @desc Ajax toggle disable link objects to normal wordpress categories
    *
    * @param void
    * @return void
    */
    public function ajaxSetSetting()
    {
			// Check if current user has enough rights to edit plugin settings
			if (!current_user_can('manage_options'))
				wp_die();

			// Check nonce
			if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-options'))
			{
				echo 'Fout: Je hebt de pagina te lang open staan, herlaad de pagina en probeer opnieuw.';
				wp_die();
			}

			$boolSettings	= array('yog_cat_custom', 'yog_objectsinarchief', 'yog_huizenophome', 'yog_nochilds_searchresults', 'yog_sync_disabled', 'yog_show_og', 'yog_javascript_dojo_dont_enqueue');
			$textSettings	= array('yog_noextratexts', 'yog_englishtext', 'yog_order', 'yog_media_size', 'yog_relation_sync', 'yog_google_maps_api_key', 'yog_html_style', 'yog_search_bog_rentalprice_type');
			$intSettings	= array('yog_media_quality');

      if (!empty($_POST['name']))
      {
				// Handle boolean settings (toogle setting)
				if (in_array($_POST['name'], $boolSettings))
					$value = !((bool) get_option($_POST['name'], false));
				// Handle text settings
				else if (in_array($_POST['name'], $textSettings))
					$value = empty($_POST['value']) ? '' : sanitize_text_field($_POST['value']);
				// Handle integer settings
				else if (in_array($_POST['name'], $intSettings))
					$value = empty($_POST['value']) ? '' : (int) $_POST['value'];
        // Otherwise, just exit
        else
					wp_die();

        update_option($_POST['name'], $value);

				switch ($_POST['name'])
				{
					// Custom stuff for yog_cat_custom
					case 'yog_cat_custom':

						// Flush rewrite rules
						$this->registerPostTypes();
						flush_rewrite_rules();

						// Clear yog_order if needed
						if ($value === false)
							delete_option('yog_order');

						break;

					// Custom stuff for yog_relation_sync
					case 'yog_relation_sync':

						// Clear yog_skipped_relation_uuids
						delete_option('yog_skipped_relation_uuids');

						break;

					// Custom stuff for yog_search_bog_rentalprice_type
					case 'yog_search_bog_rentalprice_type':

						$this->updateProjectsWithBogRentalPricePerYear();

						break;
        }

        echo 'instelling opgeslagen.';
      }

		  wp_die();
    }

    /**
    * @desc Add a system link
    *
    * @param void
    * @return void
    */
	  public function addSystemLink()
	  {
		  // No activation code in post, just exit
		  if (empty($_POST['activatiecode']))
			  wp_die();

			// Check if current user has enough rights to edit plugin settings
			if (!current_user_can('manage_options'))
				wp_die();

			// Check nonce
			if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-options'))
			{
				echo 'Fout: Je hebt de pagina te lang open staan, herlaad de pagina en probeer opnieuw.';
				wp_die();
			}

      $systemLink         = new YogSystemLink(YogSystemLink::EMPTY_NAME, 'Nog niet geactiveerd', sanitize_text_field($_POST['activatiecode']), '-');

      $systemLinkManager  = new YogSystemLinkManager();
      $systemLinkManager->store($systemLink);

		  echo '<div class="system-link" id="yog-system-link-' . $systemLink->getActivationCode() . '">';
        echo '<div>';
          echo '<b>Naam:</b> ' . esc_html($systemLink->getName()) .'<br />';
          echo '<b>Status:</b> ' . esc_html($systemLink->getState()) .'<br />';
          echo '<b>Activatiecode:</b> ' . esc_html($systemLink->getActivationCode()) .' <br />';
          echo '<a onclick="jQuery(this).next().show(); jQuery(this).hide();">Koppeling verwijderen</a>';
          echo '<span class="hide" id="yog-system-link-' . esc_attr($systemLink->getActivationCode()) . '-remove">Wilt u deze koppeling verbreken? <span><a onclick="jQuery(this).parent().hide();jQuery(this).parent().prev().show();">annuleren</a> | <a onclick="yogRemoveSystemLink(\'' . esc_attr($systemLink->getActivationCode()) .'\');">doorgaan</a></span></span>';
        echo '</div>';
		  echo '</div>';

		  wp_die();
	  }

    /**
    * @desc Remove a system link
    *
    * @param void
    * @return void
    */
	  public function ajaxRemoveSystemLink()
	  {
		  // No activation code in post, just exit
		  if (empty($_POST['activatiecode']))
			  wp_die();

			// Check if current user has enough rights to edit plugin settings
			if (!current_user_can('manage_options'))
				wp_die();

			// Check nonce
			if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'yog-delete-system-link-' . $_POST['activatiecode']))
			{
				echo 'Fout: Je hebt de pagina te lang open staan, herlaad de pagina en probeer opnieuw.';
				wp_die();
			}

      $systemLinkManager  = new YogSystemLinkManager();
      $systemLink         = $systemLinkManager->retrieveByActivationCode(sanitize_text_field($_POST['activatiecode']));

			if ($systemLink->getState() === YogSystemLink::STATE_ACTIVE)
			{
				global $wpdb;

				// Collect all collection uuid meta keys
				$metaKeys = array();
				foreach (yog_getAllObjectPostTypes() as $postType)
				{
					$metaKeys[] = $postType . '_' . $systemLink->getCollectionUuid() . '_uuid';
				}

				// Retrieve post id's linked to collection
				$sql  = 'SELECT ID FROM ' . $wpdb->posts;
					$sql .= ' WHERE post_type IN (\'' . implode('\',\'', yog_getAllObjectPostTypes()) . '\')';
					$sql .= ' AND EXISTS (';
						$sql .= 'SELECT true FROM ' . $wpdb->postmeta;
						$sql .= ' WHERE ' . $wpdb->postmeta . '.post_id=' . $wpdb->posts . '.ID';
						$sql .= ' AND meta_key IN (\'' . implode('\',\'', $metaKeys) . '\')';
					$sql .= ')';

				$results  = $wpdb->get_results($sql);

				// Delete all posts linked to the deleted system
				if (!empty($results))
				{
					// Determine upload directory
					$wpUploadDir	= wp_upload_dir();
					$uploadDir		= null;
					if (!empty($wpUploadDir['basedir']) && is_writeable($wpUploadDir['basedir']))
						$uploadDir = $wpUploadDir['basedir'] . '/';

					// Loop though objects
					foreach ($results as $result)
					{
						// Delete post attachments
						$attachments = get_attached_media( '', $result->ID);

						foreach ($attachments as $attachment)
						{
							wp_delete_attachment((int) $attachment->ID, true);
						}

						// Delete post
						wp_delete_post((int) $result->ID, true);

						// Remove upload directory of objects
						if (!is_null($uploadDir) && is_dir($uploadDir .'projecten/' .$result->ID))
						{
							// Remove remaining files from projects/$postId folder
							$files = glob($uploadDir .'projecten/' .$result->ID . '/*');
							if (is_array($files))
							{
								foreach ($files as $file)
								{
									if (is_file($file))
										@unlink($file);
								}
							}

							// Unlink post directory
							!@rmdir($uploadDir .'projecten/' . $result->ID);
						}
					}
				}
			}

      $systemLinkManager->remove($systemLink);

      echo sanitize_text_field($_POST['activatiecode']);
			wp_die();
	  }

	  /**
	   * Try to remove images of deleted projects
	   *
	   * @param void
	   * @return void
	   */
	  private function removeUnusedProjectImages()
	  {
	  	$uploadDir 			= wp_upload_dir();

	  	// If wp_upload_dir returns errors, skip everything else
	  	if (!empty($uploadDir['error']))
				return;

	  	// Skip everything if projects upload dir does not exist
	  	if (!is_dir($uploadDir['basedir'] . '/projecten/'))
	  		return;

	  	// Skip everything if projects upload dir is not writeable
	  	if (!is_writeable($uploadDir['basedir'] . '/projecten/'))
	  		return;

	  	// Set variables
	  	$activePostIds 			= array();
	  	$projectsUploadDir	= $uploadDir['basedir'] . '/projecten/';

	  	// Retrieve existing YOG posts
	  	$posts = get_posts(array(
	  													'post_type' 			=> yog_getAllObjectPostTypes(),
	  													'post_status'			=> 'any',
	  													'posts_per_page'	=> -1
	  												));

	  	// Determine id's of extisting YOG posts
	  	foreach ($posts as $post)
	  	{
	  		$activePostIds[] = (int) $post->ID;
	  	}

	  	// Determine all project folders
	  	$projectFolders = glob($projectsUploadDir . '*');

	  	if (is_array($projectFolders))
	  	{
	  		foreach ($projectFolders as $projectFolder)
	  		{
	  			$postId = (int) basename($projectFolder);
	  			if (!in_array($postId, $activePostIds))
	  			{
	  				@array_map( "unlink", glob($projectFolder . '/*') );
	  				@rmdir($projectFolder);
	  			}
	  		}
	  	}
	  }

	  /**
	   * Set yog_price_order of projects that doesnt have it yet
	   *
	   * @param void
	   * @return void
	   */
	  private function updateProjectsWithPriceOrder()
	  {
	  	// Retrieve existing YOG posts
	  	$posts = get_posts(array(
	  													'post_type' 			=> array(YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG, YOG_POST_TYPE_NBPR, YOG_POST_TYPE_NBTY, YOG_POST_TYPE_BBPR, YOG_POST_TYPE_BBTY, YOG_POST_TYPE_BOPR),
	  													'post_status'			=> 'any',
	  													'posts_per_page'	=> -1
	  												));

	  	// Loop through posts
	  	foreach ($posts as $post)
	  	{
        $postId         = (int) $post->ID;
        $priceOrder     = get_post_meta($postId, 'yog_price_order', true);
        $priceMetaKeys  = null;
        $postType       = $post->post_type;

	  		if (empty($priceOrder) && $priceOrder != '0')
        {
          switch ($postType)
          {
            case YOG_POST_TYPE_WONEN:
            case YOG_POST_TYPE_BOG:
              $priceMetaKeys = array($postType . '_KoopPrijs', $postType . '_HuurPrijs');
              break;
            case YOG_POST_TYPE_NBPR:
              $priceMetaKeys = array($postType . '_KoopAanneemSomMin', $postType . '_HuurPrijsMin', $postType . '_KoopAanneemSomMax', $postType . '_HuurPrijsMax');
              break;
            case YOG_POST_TYPE_NBTY:
            case YOG_POST_TYPE_BBPR:
            case YOG_POST_TYPE_BBTY:
            case YOG_POST_TYPE_BOPR:
              $priceMetaKeys = array($postType . '_KoopPrijsMin', $postType . '_HuurPrijsMin', $postType . '_KoopPrijsMax', $postType . '_HuurPrijsMax');
              break;
          }

          if (!empty($priceMetaKeys))
          {
            // Determine price based on meta keys
            foreach ($priceMetaKeys as $priceMetaKey)
            {
              $price = get_post_meta($postId, $priceMetaKey, true);
              if (!empty($price))
                break;
            }

            // Set yog_price_order
            update_post_meta($postId, 'yog_price_order', empty($price) ? 0 : $price);
          }
        }
	  	}
	  }

	  /**
	   * Set yog_city_street_order of projects that doesnt have it yet
	   *
	   * @param void
	   * @return void
	   */
	  private function updateProjectsWithCityStreetOrder()
	  {
	  	// Retrieve existing YOG posts
	  	$posts = get_posts(array(
	  													'post_type' 			=> array(YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG, YOG_POST_TYPE_NBPR, YOG_POST_TYPE_NBTY, YOG_POST_TYPE_BBPR, YOG_POST_TYPE_BBTY, YOG_POST_TYPE_BOPR),
	  													'post_status'			=> 'any',
	  													'posts_per_page'	=> -1
	  												));

	  	// Loop through posts
	  	foreach ($posts as $post)
	  	{
        $postId						= (int) $post->ID;
        $cityStreetOrder	= get_post_meta($postId, 'yog_city_street_order', true);
        $metaKeys					= null;
        $postType					= $post->post_type;

	  		if (empty($cityStreetOrder))
        {
          switch ($postType)
          {
            case YOG_POST_TYPE_WONEN:
            case YOG_POST_TYPE_BOG:
            case YOG_POST_TYPE_BOPR:
              $metaKeys = array($postType . '_Plaats', $postType . '_Straat', $postType . '_Huisnummer');
              break;
            case YOG_POST_TYPE_NBPR:
						case YOG_POST_TYPE_BBPR:
              $metaKeys = array($postType . '_Plaats', $postType . '_Naam');
              break;
            case YOG_POST_TYPE_NBTY:
							$metaKeys = array('PARENT_' . YOG_POST_TYPE_NBPR . '_Plaats', $postType . '_Naam');
							break;
            case YOG_POST_TYPE_BBTY:
							$metaKeys = array('PARENT_' . YOG_POST_TYPE_BBPR . '_Plaats', $postType . '_Naam');
              break;
          }

          if (!empty($metaKeys))
          {
						$values = [];


            // Determine price based on meta keys
            foreach ($metaKeys as $metaKey)
            {
							$value = '';

							if (strpos($metaKey, 'PARENT_') !== false)
							{
								if (!empty($post->post_parent))
									$value = get_post_meta($post->post_parent, str_replace('PARENT_', '', $metaKey), true);
							}
							else
							{
								$value = get_post_meta($postId, $metaKey, true);
							}

              if (!empty($value))
                $values[] = $value;
            }

            // Set yog_price_order
            update_post_meta($postId, 'yog_city_street_order', !empty($values) ? strtolower(implode(' ', $values)) : '');
          }
        }
	  	}
	  }

	  /**
	   * Set HuurPrijsPerJaar of projects that doesnt have it yet
	   *
	   * @param void
	   * @return void
	   */
	  private function updateProjectsWithBogRentalPricePerYear()
	  {
	  	// Retrieve existing YOG posts
	  	$posts = get_posts(array(
	  													'post_type' 			=> array(YOG_POST_TYPE_BOG),
	  													'post_status'			=> 'any',
	  													'posts_per_page'	=> -1
	  												));

	  	// Loop through posts
	  	foreach ($posts as $post)
	  	{
        $postId						= (int) $post->ID;

				// Determine rental price condition
				$condition				= get_post_meta($postId, YOG_POST_TYPE_BOG . '_HuurPrijsConditie', true);

				if (!empty($condition))
				{
					// Check if price per year is not yet set
					$pricePerYear			= get_post_meta($postId, YOG_POST_TYPE_BOG . '_HuurPrijsPerJaar', true);
					$pricePerYearMin	= get_post_meta($postId, YOG_POST_TYPE_BOG . '_HuurPrijsMinPerJaar', true);
					$pricePerYearMax	= get_post_meta($postId, YOG_POST_TYPE_BOG . '_HuurPrijsMaxPerJaar', true);

					if (empty($pricePerYear) && empty($pricePerYearMin) && empty($pricePerYearMax))
					{
						// Retrieve prices
						$rentalPrice		= get_post_meta($postId, YOG_POST_TYPE_BOG . '_HuurPrijs', true);
						$rentalPriceMin	= get_post_meta($postId, YOG_POST_TYPE_BOG . '_HuurPrijsMin', true);
						$rentalPriceMax	= get_post_meta($postId, YOG_POST_TYPE_BOG . '_HuurPrijsMax', true);

						// Handle normal rental price
						if (!empty($rentalPrice))
						{
							switch ($condition)
							{
								case 'p.m.':
									$pricePerYear = (int) $rentalPrice * 12;
									break;
								case 'per m2/jaar':
								case 'per vierkante meter p.j.':
									$surface	= get_post_meta($postId, YOG_POST_TYPE_BOG . '_Oppervlakte', true);

									if (!empty($surface))
										$pricePerYear	= (int) $rentalPrice * (int) $surface;
									else
										$pricePerYear = (int) $rentalPrice;
									break;
								default:
									$pricePerYear = (int) $rentalPrice;
									break;
							}

							update_post_meta($postId, YOG_POST_TYPE_BOG . '_HuurPrijsPerJaar', $pricePerYear);
						}

						// Handle min/max rental price
						if (!empty($rentalPriceMin) || !empty($rentalPriceMax))
						{
							$pricePerYearMin	= null;
							$pricePerYearMax	= null;

							switch ($condition)
							{
								case 'p.m.':
									if (!empty($rentalPriceMin))
										$pricePerYearMin = (int) $rentalPriceMin * 12;

									if (!empty($rentalPriceMax))
										$pricePerYearMax = (int) $rentalPriceMax * 12;
									break;
								case 'per m2/jaar':
								case 'per vierkante meter p.j.':
									$surface	= get_post_meta($postId, YOG_POST_TYPE_BOG . '_Oppervlakte', true);

									if (!empty($surface))
									{
										if (!empty($rentalPriceMin))
											$pricePerYearMin = (int) $rentalPriceMin * (int) $surface;

										if (!empty($rentalPriceMax))
											$pricePerYearMax = (int) $rentalPriceMax * (int) $surface;
									}
									else
									{
										if (!empty($rentalPriceMin))
											$pricePerYearMin = (int) $rentalPriceMin;

										if (!empty($rentalPriceMax))
											$pricePerYearMax = (int) $rentalPriceMax;
									}
									break;
								default:
									if (!empty($rentalPriceMin))
										$pricePerYearMin = (int) $rentalPriceMin;

									if (!empty($rentalPriceMax))
										$pricePerYearMax = (int) $rentalPriceMax;
									break;
							}

							if (!empty($pricePerYearMin))
								update_post_meta($postId, YOG_POST_TYPE_BOG . '_HuurPrijsMinPerJaar', $pricePerYearMin);

							if (!empty($pricePerYearMax))
								update_post_meta($postId, YOG_POST_TYPE_BOG . '_HuurPrijsMaxPerJaar', $pricePerYearMax);
						}
					}
				}
	  	}
	  }

  }