<?php
/**
 * CLI commands for the Yes-co ORES Plugin to use with WP CLI
 * @author Kees Brandenburg - Yes-co Nederland
 */
class YogCliCommands extends \WP_CLI_Command
{
	/**
	 * Synchronize objects
	 *
	 * ## OPTIONS
	 *
	 * [--post-id=<post-id>]
	 * : Force the synchronisation of a specific object
     *
     * [--collection_uuid=<collection_uuid>]
     * : Only synchronize the system with specified collection uuid
	 *
	 * @when after_wp_load
	 */
	public function synchronize($args, $assoc_args)
	{
		set_time_limit(0);

		require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_checks.php');
		require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_system_link_manager.php');
		require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_http_manager.php');
		require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_synchronization_manager.php');

		// Force sync of a specific object?
		if (isset($assoc_args['post-id']) && ctype_digit($assoc_args['post-id'])) {
            $_GET['force'] = (int)$assoc_args['post-id'];
        }

        // Collection uuid provided?
        $collectionUuid = (!empty($assoc_args['collection_uuid']) ? trim($assoc_args['collection_uuid']) : null);

		// Check for global errors
		$errors = \YogChecks::checkForErrors();
		if (count($errors) > 0) {
            \WP_CLI::error(str_replace(array('<br />', '<i>', '</i>', '<b>', '</b>'), array("\n", '', '', '', ''), implode(', ', $errors)), true);
        }

		// Retrieve system links
		$yogSystemLinkManager   = new \YogSystemLinkManager();
        $yogSystemLinks         = [];

        if (is_null($collectionUuid)) {
            $yogSystemLinks = $yogSystemLinkManager->retrieveAll();

            if (count($yogSystemLinks) === 0) {
                \WP_CLI::error('No system links found', true);
            }
        } else {
            try {
                $yogSystemLink  = $yogSystemLinkManager->retrieveByCollectionUuid($collectionUuid);
                $yogSystemLinks = [$yogSystemLink];
            } catch (YogException $e) {
                \WP_CLI::error('No system link with collection uuid [' . $collectionUuid . '] found', true);
            }
        }

		// Synchronize each system link
		foreach ($yogSystemLinks as $yogSystemLink) {
			// Only handle active system links
			if ($yogSystemLink->getState() === \YogSystemLink::STATE_ACTIVE) {
				$yogSynchronizationManager  = new \YogSynchronizationManager($yogSystemLink);
				$yogSynchronizationManager->enableCliMode();
				$response                   = $yogSynchronizationManager->doSync(true);

				if (isset($response['errors']) && count($response['errors']) > 0) {
                    \WP_CLI::error(implode(', ', $response['errors']), false);
                }

				if (isset($response['warnings']) && count($response['warnings']) > 0) {
                    \WP_CLI::warning(implode(', ', $response['warnings']));
                }

                if (isset($response['debug']) && count($response['debug']) > 0) {
                    \WP_CLI::debug(implode("\n", $response['debug']));
                }

				if (isset($response['handledProjects']) && count($response['handledProjects']) > 0) {
                    \WP_CLI::success('Synchronization for system link with activation code "' . $yogSystemLink->getActivationCode() . '" complete, handled ' . count($response['handledProjects']) . ' projects.');
                } else {
                    \WP_CLI::success('Synchronization for system link with activation code "' . $yogSystemLink->getActivationCode() . '" complete, all projects are up-to-date.');
                }
			}
			// Show message for non-active system links
			else {
				\WP_CLI::warning('Skipping non-active system link with activation code "' . $yogSystemLink->getActivationCode() . '"');
			}
		}
	}
}

// Register cli commands with WP CLI
WP_CLI::add_command('yog', 'YogCliCommands');