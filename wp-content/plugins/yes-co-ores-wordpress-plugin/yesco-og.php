<?php
  /*
  Plugin Name: Yes-co ORES
  Plugin URI: https://www.yes-co.nl/aansluitmogelijkheden/wordpress-plugin/
  Text Domain: yes-co-ores-wordpress-plugin
  Description: Publiceert uw onroerend goed op uw Wordpress Blog
  Version: 1.3.82
  Author: Yes-co
  Author URI: https://www.yes-co.nl
  License: GPL2
  */

	// Determine plugin directory
	if (!defined('YOG_PLUGIN_DIR'))
		define('YOG_PLUGIN_DIR', dirname(__FILE__));

	if (!defined('YOG_PLUGIN_URL'))
		define('YOG_PLUGIN_URL', plugins_url(null, __FILE__));

	// https://codex.wordpress.org/I18n_for_WordPress_Developers
	if (!defined('YOG_TRANSLATION_TEXT_DOMAIN'))
	  define('YOG_TRANSLATION_TEXT_DOMAIN', 'yes-co-ores-wordpress-plugin');

  // Include files
  require_once(YOG_PLUGIN_DIR . '/includes/config/config.php');
  require_once(YOG_PLUGIN_DIR . '/includes/yog_rest_api.php');

  // Determine action
  $action     = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

  try
  {
    switch ($action)
    {
      // Activate plugin (called with URL from Yes-co)
      case 'activate_yesco_og':
        require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_system_link_manager.php');

        $yogSystemLinkManager       = new YogSystemLinkManager();
        $yogSystemLink              = $yogSystemLinkManager->retrieveByRequest($_REQUEST);
        $yogSystemLinkManager->activate($yogSystemLink);

        header('X-Robots-Tag: noindex');
        echo json_encode(array( 'status'  => 'ok',
	                              'message' => 'Plug-in activated')
                        );

        exit;

        break;
      // Synchronize objects / relations
      case 'sync_yesco_og':

        // Check if synchronisation is disabled
        $syncDisabled   = get_option('yog_sync_disabled', false);
        if ($syncDisabled !== false && !empty($syncDisabled) && empty($_GET['force'])) {
            echo json_encode(['status'   => 'ok', 'message' => 'Synchronisatie uitgeschakeld']);
            exit;
        }

        require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_system_link_manager.php');
        require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_http_manager.php');
        require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_synchronization_manager.php');
        require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_plugin.php');

        set_time_limit(900);

        $yogSystemLinkManager       = new YogSystemLinkManager();
        $yogSystemLink              = $yogSystemLinkManager->retrieveByRequest($_REQUEST);

        if (!$yogSystemLink->isSyncEnabled()) {
            echo json_encode(['status'   => 'ok', 'message' => 'Synchronisatie voor dit account is uitgeschakeld']);
            exit;
        }

        $yogPlugin = YogPlugin::getInstance();
        $yogPlugin->init();

        $yogSynchronizationManager  = new YogSynchronizationManager($yogSystemLink);
        $yogSynchronizationManager->init();

        break;
      // Remote checks
      case 'check':

				require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_system_link_manager.php');
				require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_checks.php');

				$yogSystemLinkManager       = new YogSystemLinkManager();
				$yogSystemLink              = $yogSystemLinkManager->retrieveByRequest($_REQUEST);

				$response = array('settings' 	=> YogChecks::getSettings(),
													'errors'		=> YogChecks::checkForErrors(),
													'warnings'	=> YogChecks::checkForWarnings());

        header('X-Robots-Tag: noindex');
				echo json_encode($response);

				exit;

        break;
      // Initialize plugin
      default:
        require_once(YOG_PLUGIN_DIR . '/includes/yog_public_functions.php');
        require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_plugin.php');

        $yogPlugin = YogPlugin::getInstance();
        $yogPlugin->init();

        break;
    }
  }
  catch (YogException $e)
  {
    echo $e->toJson();
    exit;
  }

	// Include CLI commands for WP_CLI (if WP CLI is running)
	if (defined('WP_CLI') && WP_CLI === true)
		require_once(YOG_PLUGIN_DIR . '/includes/yog_cli.php');