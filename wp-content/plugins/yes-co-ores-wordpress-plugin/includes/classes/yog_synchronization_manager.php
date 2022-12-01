<?php
require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_3mcp.php');
require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_project_translation.php');
require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_project_wonen_translation.php');
require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_relation_translation.php');
require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_image_translation.php');
require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_dossier_translation.php');
require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_api.php');
require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_cron.php');
require_once(YOG_PLUGIN_DIR . '/includes/yog_private_functions.php');

/**
 * @desc YogSynchronizationManager
 * @author Kees Brandenburg - Yes-co Nederland
 */
class YogSynchronizationManager
{
    const LOCK_SYNC_SECONDS = 900; // Prevent multiple sync actions to be allowed to run at the same time... set to 15 minutes or higher

    private $systemLink;
    private $feedReader;
    private $db;
    private $uploadDir;
    private $newLastSyncTimestamp;
    private $warnings = array();
    private $errors = array();
    private $handledProjects = array();
    private $handledRelations = array();
    private $debugMode = false;
    private $debugMessages = array();
    private $alternativeCities = array();
    private $cliMode = false;

    /**
     * @desc Constructor
     *
     * @param YogSystemLink $systemLink
     * @return YogSynchronizationManager
     */
    public function __construct(YogSystemLink $systemLink)
    {
        /*
        * Require needed wordpress files
        */
        // image.php is needed to use wp_generate_attachment_metadata(), according to: https://codex.wordpress.org/Function_Reference/wp_insert_attachment#Example
        if (file_exists(ABSPATH . 'wp-admin/includes/image.php'))
            require_once(ABSPATH . 'wp-admin/includes/image.php');

        if (!$systemLink->hasCredentials()) {
            $yogApi = new \YogApi();
            $yogApi->enrichSystemLink($systemLink);

            $systemLinkManager = new \YogSystemLinkManager();
            $systemLinkManager->store($systemLink);
        }

        $this->systemLink = $systemLink;

        $this->feedReader = Yog3McpFeedReader::getInstance();
        $this->feedReader->read($systemLink->getCollectionUuid(), $this->systemLink->getCredentials());

        global $wpdb;
        $this->db = $wpdb;

        // Determine upload directory
        $wpUploadDir = wp_upload_dir();
        if (!empty($wpUploadDir['basedir']) && is_writeable($wpUploadDir['basedir']))
            $this->uploadDir = $wpUploadDir['basedir'] . '/';

        // Check if debug mode is enabled
        if (!empty($_GET['debug']))
            $this->debugMode = true;
    }

    public function enableCliMode()
    {
        $this->cliMode = true;
    }

    public function init()
    {
        add_action('init', array($this, 'doSync'));
    }

    public function doSync($return = false)
    {
        $syncRunning = get_option('yog-sync-running', false);

        $lockSyncSeconds = self::LOCK_SYNC_SECONDS;

        $timestampNow = date('U');
        $timestampNowMinusSyncLocked = $timestampNow - $lockSyncSeconds;

        if ($syncRunning === false || $timestampNowMinusSyncLocked > $syncRunning) {
            $syncStartedTimestamp = date('U');

            if (!defined('WP_IMPORTING')) {
                define( 'WP_IMPORTING', true);
            }

            wp_defer_term_counting( true );
            wp_defer_comment_counting( true );

            try {
                update_option('yog-sync-running', $syncStartedTimestamp);

                // Set jpg quality
                add_filter('jpeg_quality', function ($arg) {
                    return (int)get_option('yog_media_quality', 82);
                });

                // Sync relations
                $this->syncRelations();

                // Sync projects
                $this->syncProjects();

                $response = array('status' => 'ok',
                    'message' => 'Synchronisatie voltooid');

                if (count($this->handledProjects) > 0)
                    $response['handledProjects'] = $this->handledProjects;

                if (count($this->handledRelations))
                    $response['handledRelations'] = $this->handledRelations;

                if ($this->hasWarnings())
                    $response['warnings'] = $this->getWarnings();

                if ($this->hasErrors())
                    $response['errors'] = $this->getErrors();

                if (!empty($this->debugMessages))
                    $response['debug'] = $this->debugMessages;
            } catch (Exception $e) {
                header("HTTP/1.0 500 Internal Server Error");

                $response = array('status' => 'error',
                    'message' => $e->getMessage());
            }

            delete_option('yog-sync-running');

            update_option('yog-' . $this->systemLink->getCollectionUuid() . '-last-sync-result', @json_encode($response));

            if (!$this->hasErrors() && !empty($this->newLastSyncTimestamp))
                update_option('yog-' . $this->systemLink->getCollectionUuid() . '-last-sync', $this->newLastSyncTimestamp);

            wp_defer_term_counting( false );
            wp_defer_comment_counting( false );

            // Remove debug messages from response if debug mode is not set
            if ($this->debugMode !== true && isset($response['debug']))
                unset($response['debug']);
        } else {
            header("HTTP/1.0 429 Too Many Requests");

            $response = array('status' => 'warning',
                'message' => 'synchronization already running, started at ' . date('Y-m-d H:i:s', $syncRunning) . ' this script ran at the following time ' . date('Y-m-d H:i:s', $timestampNow));
        }

        if ($return === true) {
            return $response;
        } else {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }

    /**
     * @desc Synchronize relations
     *
     * @param void
     * @return void
     */
    public function syncRelations()
    {
        $existingRelationUuids = $this->retrieveRelationUuidMapping();
        $relationEntityLinks = $this->feedReader->getRelationEntityLinks();
        $processedRelationUuids = array();
        $relationSyncMode = get_option('yog_relation_sync', false);
        $skippedRelationUuids = get_option('yog_skipped_relation_uuids', array());
        $forceRelationSync = !empty($_GET['force_relation']);

        foreach ($relationEntityLinks as $relationEntityLink) {
            $uuid = $relationEntityLink->getUuid();
            $publicationDlm = strtotime($relationEntityLink->getDlm());
            $processedRelationUuids[] = $uuid;

            // Check if relation allready exists
            $postType = YogRelationTranslationAbstract::POST_TYPE;
            $existingRelation = array_key_exists($uuid, $existingRelationUuids);
            $postId = ($existingRelation) ? $existingRelationUuids[$uuid] : null;
            $postDlm = $this->retrievePostDlm($postId, $postType);

            // If relation was previous skipped, skip it again
            if (in_array($uuid, $skippedRelationUuids))
                continue;

            if ($publicationDlm > $postDlm || $forceRelationSync) {
                $mcp3Relation = $this->feedReader->retrieveRelationByLink($relationEntityLink);

                if ($relationSyncMode === 'all' || in_array($mcp3Relation->getType(), array('office', 'employee'))) {
                    // Recheck relation id / if is already existing (mapping is made at the start of the sync, perhaps it exists now)
                    if (is_null($postId)) {
                        $postId = $this->determineRelationPostId($uuid, $existingRelationUuids);
                        $existingRelation = !is_null($postId);
                    }

                    $translationRelation = YogRelationTranslationAbstract::create($mcp3Relation, $relationEntityLink);
                    $relationMetaData = $translationRelation->getMetaData();

                    // Check if there already is a relation with the same API id (3mcp >= 1.6 needed)
                    if (is_null($postId) && !empty($relationMetaData['ApiKey'])) {
                        $postId = $this->retrievePostIdByApiKey($relationMetaData['ApiKey']);
                        $existingRelation = !is_null($postId);
                    }

                    // Translate 3mcp relation to post data
                    $postData = $translationRelation->getPostData();

                    // Add parent post id to post data if needed
                    if ($translationRelation->hasParentUuid()) {
                        $parentUuid = $translationRelation->getParentUuid();

                        if (array_key_exists($parentUuid, $existingRelationUuids))
                            $postData['post_parent'] = $existingRelationUuids[$parentUuid];

                        // TODO: if parent relation uuid does not exist yet, handle linking of post_parent after all relations are processed (parent relation might be processed later on)
                    }

                    // Insert / Update post
                    if ($existingRelation) {
                        @wp_update_post(array_merge(array('ID' => $postId), $postData));
                    } else {
                        $postId = @wp_insert_post($postData);

                        // Add to extisting relations array
                        $existingRelationUuids[$uuid] = $postId;
                    }

                    // Store meta data
                    $this->handlePostMetaData($postId, $postType, $relationMetaData);

                    // Update system link name (if needed)
                    if ($mcp3Relation->getType() == 'office' && $this->systemLink->getName() == YogSystemLink::EMPTY_NAME) {
                        $this->systemLink->setName($translationRelation->determineTitle());

                        $systemLinkManager = new YogSystemLinkManager();
                        $systemLinkManager->store($this->systemLink);
                    }

                    // Add relation post id to handled relations
                    $this->handledRelations[] = $postId;
                } else {
                    // Register skipped relation, so it won't be checked at the next sync
                    $skippedRelationUuids[] = $uuid;
                }
            }
        }

        /* Cleanup old relations */
        $deleteRelationUuids = array_diff(array_flip($existingRelationUuids), $processedRelationUuids);

        foreach ($deleteRelationUuids as $uuid) {
            $postId = $existingRelationUuids[$uuid];
            $noDelete = (bool)get_post_meta($postId, YOG_POST_TYPE_RELATION . '_nodelete', true);

            if ($noDelete !== true)
                wp_delete_post($postId);
        }

        // Also delete skipped relations (if needed)
        foreach (array_intersect(array_flip($existingRelationUuids), $skippedRelationUuids) as $uuid) {
            $postId = $existingRelationUuids[$uuid];
            $noDelete = (bool)get_post_meta($postId, YOG_POST_TYPE_RELATION . '_nodelete', true);

            if ($noDelete !== true)
                wp_delete_post($postId);
        }

        // Register skipped relations
        update_option('yog_skipped_relation_uuids', array_intersect($skippedRelationUuids, $processedRelationUuids), false);
    }

    /**
     * @desc Synchronize projects
     *
     * @param void
     * @return void
     */
    public function syncProjects()
    {
        require_once(YOG_PLUGIN_DIR . '/includes/config/alternative_cities.php');

        // Register categories if needed
        $this->registerCategories();

        // Load the alternative cities
        if (defined('YOG_ALTERNATIVE_CITIES'))
            $this->alternativeCities = unserialize(YOG_ALTERNATIVE_CITIES);

        $existingProjectUuids = $this->retrieveProjectUuidsMapping();
        $existingRelationUuids = $this->retrieveRelationUuidMapping();
        $groupedProjectEntityLinks = $this->feedReader->getProjectEntityLinks();
        $processedProjectUuids = array();
        $forcePostId = !empty($_GET['force']) ? (int)$_GET['force'] : null;
        $forceAll = (!empty($_GET['force_all']) && $_GET['force_all'] === 'true' && current_user_can('manage_options'));

        $yogLastSync = get_option('yog-' . $this->systemLink->getCollectionUuid() . '-last-sync', 0);

        if (empty($yogLastSync)) // @DEPRECATED.. now possible to keep a last synced for each feed
            $yogLastSync = get_option('yog-last-sync', 0);

        $syncFromTimestamp = (!is_null($forcePostId) || $forceAll === true) ? 0 : $yogLastSync;

        // Also check objects from 2 hours before last sync, to make sure small time differences do not affect the synchronisation
        if ($syncFromTimestamp > 0) {
            $syncFromTimestamp -= (3600 * 2);
            $this->addDebugMessage('Settings sync from timestamp to ' . date('c', $syncFromTimestamp) . ' (2 hours before last sync, to make sure small time differences do not affect the synchronisation)');
        }

        // Determine sync from date (for debug messages)
        $syncFromDate = yog_format_timestamp('c', $syncFromTimestamp);

        // Determine future timestamp (to check if there are projects with a date in the future)
        $futureDayTimestamp = strtotime('+1 day');

        // If force all is set, set time limit to 0
        if ($forceAll === true)
            set_time_limit(0);

        foreach ($groupedProjectEntityLinks as $scenario => $projectEntityLinks) {
            foreach ($projectEntityLinks as $uuid => $projectEntityLink) {
                $postId = null;
                $processedProjectUuids[] = $uuid;

                try {
                    $publicationDlmDate = $projectEntityLink->getDlm();
                    $publicationDlm = $projectEntityLink->getDlmTimestamp();

                    // Determine post type
                    $postType = $this->determinePostTypeByScenario($scenario);

                    // Skip unsupported scenario's
                    if (is_null($postType)) {
                        $this->warnings[] = 'Unsupported scenario ' . $scenario;
                    } // Only process supported scenario's with publication dlm after sync from timestamp
                    else if ($publicationDlm >= $syncFromTimestamp || !array_key_exists($uuid, $existingProjectUuids)) {
                        // Check if project already exists
                        $existingProject = array_key_exists($uuid, $existingProjectUuids);
                        $postId = ($existingProject) ? $existingProjectUuids[$uuid] : null;
                        $postDlm = $this->retrievePostDlm($postId, $postType);
                        $forceSync = false;

                        // Check if there is a post
                        if (empty($postId)) {
                            $this->addDebugMessage($uuid . ': no post yet for this uuid');
                            $forceSync = true;
                        } // Check if publication is newer then post in database
                        else if ($publicationDlm > $postDlm) {
                            $this->addDebugMessage($uuid . ': Publication dlm ' . yog_format_timestamp('c', $publicationDlm) . ' newer then post dlm ' . yog_format_timestamp('c', $postDlm) . ', so going to sync');
                            $forceSync = true;
                        } // In case the post dlm is in the future, something might be wrong, do a sync
                        else if ($postDlm > $futureDayTimestamp) {
                            $this->errors[] = 'Project has a date ' . yog_format_timestamp('c', $postDlm) . ' in the future, sync is forced ' . $uuid . (is_null($postId) ? '' : ' (post ID: ' . $postId . ')') . '.';
                            $forceSync = true;
                        } // Check if sync is forced for this project
                        else if ((!is_null($forcePostId) && $postId == $forcePostId) || $forceAll === true) {
                            $this->addDebugMessage($uuid . ': Sync is forced for this project, so going to sync');
                            $forceSync = true;
                        } else {
                            $this->addDebugMessage($uuid . ': Publication dlm <= post dlm, so skipping sync (' . $publicationDlmDate . ' <= ' . yog_format_timestamp('c', $postDlm) . ')');
                        }

                        if ($forceSync === true) {
                            // Update sync running to prevent script from running multiple times... even when a cron is running for hours to do a full project sync
                            $syncStartedTimestamp = date('U');
                            update_option('yog-sync-running', $syncStartedTimestamp);

                            // Register the last project before syncing.. to debug problems if nesescary
                            $projectEntityLinkJson = new \StdClass();
                            $projectEntityLinkJson->scenario = $projectEntityLink->getScenario();
                            $projectEntityLinkJson->uuid = $projectEntityLink->getUuid();
                            $projectEntityLinkJson->url = $projectEntityLink->getUrl();
                            $projectEntityLinkJson->doc = $projectEntityLink->getDoc();
                            $projectEntityLinkJson->dlm = $projectEntityLink->getDlm();

                            // This option should be deleted after sync has finished
                            update_option('yog-' . $this->systemLink->getCollectionUuid() . '-last-sync-sync-project', @json_encode($projectEntityLinkJson));

                            // Prevent timeouts by flushing content
                            if (defined('YOG_PREVENT_TIMEOUTS') && YOG_PREVENT_TIMEOUTS == true) {
                                echo ' ';
                                flush();
                            }

                            $mcp3Project = $this->feedReader->retrieveProjectByLink($projectEntityLink);
                            $translationProject = YogProjectTranslationAbstract::create($mcp3Project, $projectEntityLink);

                            // Determine post data
                            $postData = $translationProject->getPostData();

                            // Add parent post id to post data if needed
                            if ($translationProject->hasParentUuid()) {
                                $parentUuid = $translationProject->getParentUuid();
                                if (!array_key_exists($parentUuid, $existingProjectUuids))
                                    throw new YogException(__METHOD__ . '; Parent project with uuid ' . $parentUuid . ' not found', YogException::GLOBAL_ERROR);

                                $postData['post_parent'] = $existingProjectUuids[$parentUuid];
                            }

                            // Recheck project id / if it's already existing (mapping is made at the start of the sync, perhaps it exists now)
                            if (is_null($postId)) {
                                $postId             = $this->determineProjectPostId($uuid, $postType, $existingProjectUuids);
                                $existingProject    = !is_null($postId);

                                if ($existingProject) {
                                    $existingProjectUuids[$uuid] = $postId;
                                }
                            }

                            // Insert / Update post
                            if ($existingProject) {
                                @wp_update_post(array_merge(array('ID' => $postId), $postData));

                                // Update version
                                $version = (int)get_post_meta($postId, $postType . '_versie', true) + 1;
                            } else {
                                $postId = @wp_insert_post($postData);

                                // Add to existing projects array
                                $existingProjectUuids[$uuid] = $postId;

                                // Set version to 1
                                $version = 1;
                            }

                            // Store meta data
                            $this->handlePostMetaData($postId, $postType, $translationProject->getMetaData());

                            // Store price to order by
                            update_post_meta($postId, 'yog_price_order', $translationProject->determineSortPrice());

                            // Store version of object
                            update_post_meta($postId, $postType . '_versie', $version);

                            // Store city/street/number to order by
                            $sortCityStreet = $translationProject->determineSortCityStreet();
                            if (strpos($sortCityStreet, 'PARENT') !== false) {
                                if (isset($postData['post_parent']))
                                    $parentSortCityStreet = get_post_meta($postData['post_parent'], 'yog_city_street_order', true);
                                else
                                    $parentSortCityStreet = '';

                                $sortCityStreet = trim(str_replace('PARENT', $parentSortCityStreet, $sortCityStreet));
                            }
                            update_post_meta($postId, 'yog_city_street_order', strtolower($sortCityStreet));

                            // Handle linked relations
                            $existingLinkedRelations = array_intersect_key($translationProject->getRelationLinks(), $existingRelationUuids);
                            $relations = array();
                            foreach ($existingLinkedRelations as $relationUuid => $role) {
                                $relations[$relationUuid] = array('rol' => $role, 'postId' => $existingRelationUuids[$relationUuid]);
                            }
                            update_post_meta($postId, $postType . '_Relaties', $relations);

                            // Handle video
                            $this->handleMediaLink($postId, $postType, 'Videos', $translationProject->getVideos());

                            // Handle external documents
                            $this->handleMediaLink($postId, $postType, 'Documenten', $translationProject->getExternalDocuments());

                            // Handle links
                            $this->handleMediaLink($postId, $postType, 'Links', $translationProject->getLinks());

                            // Handle categories
                            if (get_option('yog_cat_custom')) {
                                wp_set_object_terms($postId, $translationProject->getCategories(), 'yog_category', false);
                                wp_set_object_terms($postId, array(), 'category', false);
                            } else {
                                wp_set_object_terms($postId, $translationProject->getCategories(), 'category', false);
                            }

                            // Handle tags
                            wp_set_post_tags($postId, $translationProject->getTags(), false);

                            // Handle images
                            $this->handlePostImages($postId, $mcp3Project->getMediaImages());

                            // Handle dossier items
                            $this->handlePostDossier($postId, $mcp3Project->getDossierItems());

                            // Store Yes-co ORES post dlm (use the lowest of 3mcp entity link dlm, or current timestamp)
                            $currentTimestamp = date('U');
                            $newPostDlm = ($publicationDlm > $currentTimestamp) ? $currentTimestamp : $publicationDlm;
                            update_post_meta($postId, 'yog_post_dlm', $newPostDlm);

                            // Update the new last sync timestamp (if needed)
                            if (is_null($this->newLastSyncTimestamp) || $newPostDlm > $this->newLastSyncTimestamp)
                                $this->newLastSyncTimestamp = $newPostDlm;

                            // Add post id to handled projects
                            $this->handledProjects[] = $postId;

                            // Update yog-sync-running timestamp, to make sure sync isn't run twice if a sync runs for more then 15 minutes
                            update_option('yog-sync-running', date('U'));

                            // Delete the last sync projct information, everything seems to be fine
                            delete_option('yog-' . $this->systemLink->getCollectionUuid() . '-last-sync-sync-project');

                            if ($this->cliMode === true) {
                                if (class_exists('WP_CLI')) {
                                    \WP_CLI::log('Handled project with 3mcp uuid [' . $uuid . '] and wordpress post id [' . $postId . ']');
                                }

                                // Sleep for a second to decrease load a bit
                                sleep(1);
                            }
                        }
                    } else {
                        $this->addDebugMessage($uuid . ': Publication dlm before last sync date, so no need to synchronize (' . yog_format_timestamp('c', $publicationDlm) . ' < ' . $syncFromDate . ')');
                    }
                } catch (Exception $e) {
                    $this->errors[] = 'Exception on project ' . $uuid . (is_null($postId) ? '' : ' (post ID: ' . $postId . ')') . ': ' . $e->getMessage();
                }
            }
        }

        /* Cleanup old projects */
        $deleteProjectUuids = array_diff(array_flip($existingProjectUuids), $processedProjectUuids);

        foreach ($deleteProjectUuids as $uuid) {
            $postId = $existingProjectUuids[$uuid];

            $this->deletePostFiles($postId);
            wp_delete_post($postId);
        }

        // Force the deletion of a project?
        if (!empty($_GET['force_delete']) && ctype_digit($_GET['force_delete']) && current_user_can('manage_options')) {
            $this->deletePostFiles((int)$_GET['force_delete']);
            wp_delete_post((int)$_GET['force_delete']);
        }

        // Check if there are project's with open house category that shouldn't have it anymore
        YogCron::updateOpenHouses();

        // Clear cache of external wordpress plugins (currently only Cache Enabler is supported)
        if (!empty($this->handledProjects) || !empty($deleteProjectUuids)) {
            if (has_action('ce_clear_cache'))
                do_action('ce_clear_cache');
        }

        // Delete the last sync projct information, everything seems to be fine
        delete_option('yog-' . $this->systemLink->getCollectionUuid() . '-last-sync-sync-project');
    }

    /**
     * @desc Check if there are warnings
     *
     * @param void
     * @return bool
     */
    public function hasWarnings()
    {
        return count($this->warnings) > 0;
    }

    /**
     * @desc Get the warnings
     *
     * @param void
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * @desc Check if there are errors
     *
     * @param void
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * @desc Get the errors
     *
     * @param void
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @desc Determine post type based on the scenario
     *
     * @param string $scenario
     * @return void (string or null)
     */
    private function determinePostTypeByScenario($scenario)
    {
        $postType = null;
        switch ($scenario) {
            case 'BBvk':
            case 'BBvh':
            case 'NBvk':
            case 'NBvh':
            case 'LIvk':
                $postType = YOG_POST_TYPE_WONEN;
                break;
            case 'BOvk':
            case 'BOvh':
                $postType = YOG_POST_TYPE_BOG;
                break;
            case 'NBpr':
                $postType = YOG_POST_TYPE_NBPR;
                break;
            case 'NBty':
                $postType = YOG_POST_TYPE_NBTY;
                break;
            case 'NBbn':
                $postType = YOG_POST_TYPE_NBBN;
                break;
            case 'BBpr':
                $postType = YOG_POST_TYPE_BBPR;
                break;
            case 'BBty':
                $postType = YOG_POST_TYPE_BBTY;
                break;
            case 'BOpr':
                $postType = YOG_POST_TYPE_BOPR;
                break;
        }

        return $postType;
    }

    /**
     * @desc Store images for a post
     *
     * @param int $parentPostId
     * @param array $mcp3Images
     * @return void
     */
    private function handlePostImages($parentPostId, $mcp3Images)
    {
        if (!is_null($this->uploadDir)) {
            // Create projects directory (if needed)
            if (!is_dir($this->uploadDir . 'projecten/' . $parentPostId)) {
                if (!is_dir($this->uploadDir . 'projecten'))
                    mkdir($this->uploadDir . 'projecten');

                mkdir($this->uploadDir . 'projecten/' . $parentPostId);
            }

            // Determine prefered media size
            $mediaSize = get_option('yog_media_size', 'medium');

            // Determine existing media
            $results = $this->db->get_results("SELECT ID, post_content AS uuid FROM " . $this->db->prefix . "posts WHERE post_parent = " . $parentPostId . " AND post_type = '" . YOG_POST_TYPE_ATTACHMENT . "' AND post_content != '' AND post_mime_type IN ('image/jpeg', 'image/jpg', 'image/pjpeg')");
            $existingMediaMapping = array();

            if (is_array($results)) {
                foreach ($results as $result) {
                    $existingMediaMapping[$result->uuid] = $result->ID;
                }
            }

            $mainPhotoId = get_post_meta($parentPostId, '_thumbnail_id', true);
            if (empty($mainPhotoId))
                $mainPhotoId = null;

            $processedMediaUuids = array();
            $forcePostId = !empty($_GET['force']) ? (int)$_GET['force'] : null;
            $forceAll = (!empty($_GET['force_all']) && $_GET['force_all'] === 'true' && current_user_can('manage_options'));

            // Handle images
            foreach ($mcp3Images as $mcp3Image) {
                try {
                    $uuid = $mcp3Image->getUuid();
                    $processedMediaUuids[] = $uuid;
                    $imageLink = $this->feedReader->getMediaLinkByUuid($uuid, $mediaSize);
                    $publicationDlm = strtotime($imageLink->getDlm());
                    $existingMedia = array_key_exists($uuid, $existingMediaMapping);
                    $attachmentId = ($existingMedia === true) ? $existingMediaMapping[$uuid] : null;
                    $attachmenDlm = $this->retrievePostDlm($attachmentId, YOG_POST_TYPE_ATTACHMENT);

                    if (!$existingMedia || ($publicationDlm > $attachmenDlm) || (!is_null($forcePostId) && $forcePostId == $parentPostId) || $forceAll === true) {
                        $translationImage = YogImageTranslation::create($mcp3Image, $imageLink);

                        $imageData = YogHttpManager::retrieveContent($imageLink->getUrl());

                        if ($imageData !== false) {
                            // Detect file extension
                            $extension = 'jpg';

                            switch ($imageLink->getMimeType()) {
                                case 'image/png':
                                    $extension = 'png';
                                    break;
                                case 'image/gif':
                                    $extension = 'gif';
                                    break;
                                case 'image/bmp':
                                    $extension = 'bmp';
                                    break;
                                case 'image/jpeg':
                                case 'image/pjpeg':
                                    $extension = 'jpg';
                                    break;
                                default:
                                    $this->warnings[] = 'Image ' . $uuid . ' of post ' . $parentPostId . ' has an unsupported mime type (' . $imageLink->getMimeType() . '), using jpg extension as fallback.';
                                    break;
                            }

                            // Copy image
                            $destination = $this->uploadDir . 'projecten/' . $parentPostId . '/' . $uuid . '.' . $extension;
                            if (file_put_contents($destination, $imageData) === false)
                                throw new \Exception(__METHOD__ . '; Failed to write image ' . $uuid . ' to disk (' . $destination . ')');

                            // Determine image data
                            $imagePostData = $translationImage->getPostData();
                            if (!is_null($attachmentId))
                                $imagePostData['ID'] = $attachmentId;

                            // Update / insert attachment
                            $attachmentId = wp_insert_attachment($imagePostData, $destination, $parentPostId);
                            $attachmentMeta = wp_generate_attachment_metadata($attachmentId, $destination);
                            wp_update_attachment_metadata($attachmentId, $attachmentMeta);

                            // Set meta data
                            foreach ($translationImage->getMetaData() as $key => $value) {
                                if (!empty($value))
                                    update_post_meta($attachmentId, YOG_POST_TYPE_ATTACHMENT . '_' . $key, $value);
                                else
                                    delete_post_meta($attachmentId, YOG_POST_TYPE_ATTACHMENT . '_' . $key);
                            }

                            // Prevent timeouts by flushing content
                            if (defined('YOG_PREVENT_TIMEOUTS') && YOG_PREVENT_TIMEOUTS == true) {
                                echo ' ';
                                flush();
                            }
                        } else {
                            $this->warnings[] = 'Failed to retrieve image data for image ' . $uuid . ' (post ID: ' . $parentPostId . ')';
                        }
                    } // Make sure the image order is up-to-date
                    else if (!is_null($attachmentId)) {
                        wp_update_post(array('ID' => $attachmentId, 'menu_order' => $mcp3Image->getOrder()));
                    }

                    // Is image the main image?
                    if ($mcp3Image->getOrder() == 1)
                        $mainPhotoId = $attachmentId;
                } catch (Exception $e) {
                    $this->errors[] = $e->getMessage();
                }
            }

            // Set main photo
            if (!is_null($mainPhotoId)) {
                if (function_exists('set_post_thumbnail'))
                    set_post_thumbnail($parentPostId, $mainPhotoId);
                else
                    update_post_meta($parentPostId, '_thumbnail_id', $mainPhotoId);
            } else {
                if (function_exists('delete_post_thumbnail'))
                    delete_post_thumbnail($parentPostId);
                else
                    delete_post_meta($parentPostId, '_thumbnail_id');
            }

            /* Cleanup old media */
            $deleteMediaUuids = array_diff(array_flip($existingMediaMapping), $processedMediaUuids);

            foreach ($deleteMediaUuids as $uuid) {
                $attachmentId = $existingMediaMapping[$uuid];
                wp_delete_attachment($attachmentId, true);

                // Remove files
                if (!is_null($this->uploadDir) && is_dir($this->uploadDir . 'projecten/' . $parentPostId)) {
                    $files = glob($this->uploadDir . 'projecten/' . $parentPostId . '/' . $uuid . '*');
                    if (is_array($files)) {
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                if (!@unlink($file))
                                    $this->warning[] = 'Unable to unlink ' . $file;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @desc Store dossier items for a post
     *
     * @param int $parentPostId
     * @param array $mcp3DossierItems
     * @return void
     */
    private function handlePostDossier($parentPostId, $mcp3DossierItems)
    {
        if (!is_null($this->uploadDir)) {
            // Create projects directory (if needed)
            if (!is_dir($this->uploadDir . 'projecten/' . $parentPostId)) {
                if (!is_dir($this->uploadDir . 'projecten'))
                    mkdir($this->uploadDir . 'projecten');

                mkdir($this->uploadDir . 'projecten/' . $parentPostId);
            }

            $results = $this->db->get_results("SELECT ID, post_content AS uuid FROM " . $this->db->prefix . "posts WHERE post_parent = " . $parentPostId . " AND post_type = '" . YOG_POST_TYPE_ATTACHMENT . "' AND post_content != '' AND post_mime_type NOT IN ('image/jpeg', 'image/jpg', 'image/pjpeg')");
            $existingDossierMapping = array();

            if (is_array($results)) {
                foreach ($results as $result) {
                    $existingDossierMapping[$result->uuid] = $result->ID;
                }
            }

            $processedDossierUuids = array();
            $possibleMimeTypes = explode(';', get_option('yog_dossier_mimetypes', 'application/pdf'));
            $forcePostId = !empty($_GET['force']) ? (int)$_GET['force'] : null;
            $forceAll = (!empty($_GET['force_all']) && $_GET['force_all'] === 'true' && current_user_can('manage_options'));

            // Handle dossier items
            foreach ($mcp3DossierItems as $mcp3DossierItem) {
                try {
                    $uuid = $mcp3DossierItem->getUuid();
                    $dossierLink = $this->feedReader->getDossierLinkByUuid($uuid);

                    // Only handle dossier items (category document is kadastrale kaart)
                    if ($dossierLink->getCategory() == 'dossier' && !in_array($dossierLink->getMimeType(), array('image/jpeg', 'image/jpg', 'image/pjpeg'))) {
                        $processedDossierUuids[] = $uuid;
                        $publicationDlm = strtotime($dossierLink->getDlm());
                        $existingDocument = array_key_exists($uuid, $existingDossierMapping);
                        $attachmentId = ($existingDocument === true) ? $existingDossierMapping[$uuid] : null;
                        $attachmenDlm = $this->retrievePostDlm($attachmentId, YOG_POST_TYPE_ATTACHMENT);

                        if (!$existingDocument || ($publicationDlm > $attachmenDlm) || (!is_null($forcePostId) && $forcePostId == $parentPostId) || $forceAll === true) {
                            $translationDossier = YogDossierTranslation::create($mcp3DossierItem, $dossierLink);
                            $dossierData = YogHttpManager::retrieveContent($dossierLink->getUrl());

                            if ($dossierData !== false) {
                                // Copy dossier item
                                $extension = pathinfo($dossierLink->getUrl(), PATHINFO_EXTENSION);
                                $destination = $this->uploadDir . 'projecten/' . $parentPostId . '/' . $uuid . (empty($extension) ? '' : '.' . $extension);
                                file_put_contents($destination, $dossierData);

                                // Determine image data
                                $dossierPostData = $translationDossier->getPostData();
                                if (!is_null($attachmentId))
                                    $dossierPostData['ID'] = $attachmentId;

                                // Update / insert attachment
                                $attachmentId = wp_insert_attachment($dossierPostData, $destination, $parentPostId);

                                // Set meta data
                                foreach ($translationDossier->getMetaData() as $key => $value) {
                                    if (!empty($value))
                                        update_post_meta($attachmentId, YOG_POST_TYPE_ATTACHMENT . '_' . $key, $value);
                                    else
                                        delete_post_meta($attachmentId, YOG_POST_TYPE_ATTACHMENT . '_' . $key);
                                }

                                // Add mime type to possible mime types
                                $possibleMimeTypes[] = $dossierLink->getMimeType();
                            } else {
                                $this->warnings[] = 'Failed to retrieve dossier item data';
                            }
                        }
                    }
                } catch (Exception $e) {
                    $this->warnings[] = $e->getMessage();
                }
            }

            // Update possible mime types
            update_option('yog_dossier_mimetypes', implode(';', array_unique($possibleMimeTypes)));

            /* Cleanup old dossier items */
            $deleteDossierUuids = array_diff(array_flip($existingDossierMapping), $processedDossierUuids);

            foreach ($deleteDossierUuids as $uuid) {
                $attachmentId = $existingDossierMapping[$uuid];
                wp_delete_attachment($attachmentId, true);

                // Remove files
                if (!is_null($this->uploadDir) && is_dir($this->uploadDir . 'projecten/' . $parentPostId)) {
                    $files = glob($this->uploadDir . 'projecten/' . $parentPostId . '/' . $uuid . '*');
                    if (is_array($files)) {
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                if (!@unlink($file))
                                    $this->warning[] = 'Unable to unlink ' . $file;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @desc Delete post images
     *
     * @param int $postId
     * @return void
     */
    private function deletePostFiles($postId)
    {
        $postType = YOG_POST_TYPE_ATTACHMENT;

        // Remove attachment links
        $attachmentPostIds = $this->db->get_col("SELECT ID FROM " . $this->db->prefix . "posts WHERE post_parent = " . $postId . " AND post_type = '" . $postType . "' AND post_content != ''");
        foreach ($attachmentPostIds as $attachmentPostId) {
            wp_delete_attachment($attachmentPostId, true);
        }

        if (!is_null($this->uploadDir) && is_dir($this->uploadDir . 'projecten/' . $postId)) {
            // Remove remaining files from projects/$postId folder
            $files = glob($this->uploadDir . 'projecten/' . $postId . '/*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        if (!@unlink($file))
                            $this->warning[] = 'Unable to unlink ' . $file;
                    }
                }
            }

            // Unlink post directory
            if (!@rmdir($this->uploadDir . 'projecten/' . $postId))
                $this->warning[] = 'Unable to rmdir ' . $this->uploadDir . 'projecten/' . $postId;
        }
    }

    /**
     * @desc Handle media links
     *
     * @param int $postId
     * @param string $postType
     * @param string $type
     * @param array $mediaLinks
     */
    private function handleMediaLink($postId, $postType, $type, $newMediaLinks)
    {
        $metaKey = $postType . '_' . $type;

        // Retrieve already set media links
        $mediaLinks = get_post_meta($postId, $metaKey, true);
        if (!is_array($mediaLinks))
            $mediaLinks = array();

        // Remove all media links not added through WP admin
        foreach ($mediaLinks as $uuid => $mediaLink) {
            if (strpos($mediaLink['uuid'], 'zelftoegevoegd') === false)
                unset($mediaLinks[$mediaLink['uuid']]);
        }

        // Add new media links to array
        $mediaLinks = array_merge($mediaLinks, $newMediaLinks);

        // Store media links
        update_post_meta($postId, $metaKey, $mediaLinks);
    }

    /**
     * @desc Retrieve post dlm
     *
     * @param mixed $postId
     * @return int
     */
    private function retrievePostDlm($postId, $postType)
    {
        $dlm = 0;

        if (is_numeric($postId)) {
            // First try Yes-co ORES post dlm
            $dlm = get_post_meta($postId, 'yog_post_dlm', true);

            if (!empty($dlm) && is_numeric($dlm)) // Returned is already a timestamp in a string type
                return (int)$dlm;

            // Fallback to wordpress post dlm
            if (empty($dlm))
                $dlm = get_post_meta($postId, $postType . '_dlm', true); // Format returned is for exmaple 16-jan-2016

            if (!empty($dlm))
                return strtotime($dlm);
        }

        return $dlm;
    }

    /**
     * @desc Retrieve relation uuid mapping
     *
     * @param void
     * @return array
     */
    private function retrieveRelationUuidMapping()
    {
        $postType = YogRelationTranslationAbstract::POST_TYPE;
        $metaKey = $postType . '_' . $this->systemLink->getCollectionUuid() . '_uuid';
        $tableName = $this->db->prefix . 'postmeta';
        $results = $this->db->get_results("SELECT post_id, meta_value AS uuid FROM " . $tableName . " WHERE meta_key = '" . $metaKey . "'");
        $uuids = array();

        if (is_array($results)) {
            foreach ($results as $result) {
                $uuids[$result->uuid] = (int)$result->post_id;
            }
        }

        return $uuids;
    }

    /**
     * @desc Retrieve relation post id by API key
     *
     * @param string $apiKey
     * @return int|null
     */
    private function retrievePostIdByApiKey($apiKey)
    {
        $postType = YogRelationTranslationAbstract::POST_TYPE;
        $metaKey = $postType . '_ApiKey';
        $tableName = $this->db->prefix . 'postmeta';
        $results = $this->db->get_results("SELECT post_id FROM " . $tableName . " WHERE meta_key = '" . $metaKey . "' AND meta_value = '" . $this->db->_real_escape($apiKey) . "'");

        if (is_array($results) && !empty($results)) {
            $result = array_shift($results);

            return (int)$result->post_id;
        }

        return null;
    }

    /**
     * Determine the post id for a relation
     * @param string $uuid
     * @param array|null $uuidMapping
     * @return int|null
     */
    private function determineRelationPostId($uuid, $uuidMapping = null)
    {
        if (!is_string($uuid) || strlen(trim($uuid)) === 0)
            throw new \Exception(__METHOD__ . '; Invalid uuid provided');

        // First check mapping
        if (!is_array($uuidMapping) && array_key_exists($uuid, $uuidMapping))
            return $uuidMapping[$uuid];

        // Otherwise check database
        $postType = YogRelationTranslationAbstract::POST_TYPE;
        $metaKey = $postType . '_' . $this->systemLink->getCollectionUuid() . '_uuid';
        $tableName = $this->db->prefix . 'postmeta';
        $query = "SELECT post_id FROM " . $tableName . " WHERE meta_key = '" . $metaKey . "' AND meta_value = '" . $this->db->_real_escape($uuid) . "'";
        $results = $this->db->get_results($query);

        if (is_array($results) && !empty($results)) {
            $result = array_shift($results);

            return (int)$result->post_id;
        }

        return null;
    }

    /**
     * @desc Retrieve project uuid mapping
     *
     * @param void
     * @return array
     */
    private function retrieveProjectUuidsMapping()
    {
        $collectionUuid = $this->systemLink->getCollectionUuid();
        $metaKeyWonen = $this->db->_real_escape(YOG_POST_TYPE_WONEN . '_' . $collectionUuid . '_uuid');
        $metaKeyBog = $this->db->_real_escape(YOG_POST_TYPE_BOG . '_' . $collectionUuid . '_uuid');
        $metaKeyNBpr = $this->db->_real_escape(YOG_POST_TYPE_NBPR . '_' . $collectionUuid . '_uuid');
        $metaKeyNBty = $this->db->_real_escape(YOG_POST_TYPE_NBTY . '_' . $collectionUuid . '_uuid');
        $metaKeyNBbn = $this->db->_real_escape(YOG_POST_TYPE_NBBN . '_' . $collectionUuid . '_uuid');
        $metaKeyBBpr = $this->db->_real_escape(YOG_POST_TYPE_BBPR . '_' . $collectionUuid . '_uuid');
        $metaKeyBBty = $this->db->_real_escape(YOG_POST_TYPE_BBTY . '_' . $collectionUuid . '_uuid');
        $metaKeyBOpr = $this->db->_real_escape(YOG_POST_TYPE_BOPR . '_' . $collectionUuid . '_uuid');

        $tableName = $this->db->prefix . 'postmeta';
        $results = $this->db->get_results("SELECT post_id, meta_key, meta_value AS uuid FROM " . $tableName . " WHERE meta_key IN ('" . $metaKeyWonen . "', '" . $metaKeyBog . "', '" . $metaKeyNBpr . "', '" . $metaKeyNBty . "', '" . $metaKeyNBbn . "', '" . $metaKeyBBpr . "', '" . $metaKeyBBty . "', '" . $metaKeyBOpr . "') ORDER BY post_id DESC");
        $uuids = array();

        foreach ($results as $result) {
            if (array_key_exists($result->uuid, $uuids)) {
                $this->warnings[] = 'Duplicate ' . str_replace('_' . $collectionUuid . '_uuid', '', $result->meta_key) . ' UUID: ' . $result->uuid . '(Post ID: ' . $result->post_id . ', org post ID: ' . $uuids[$result->uuid] . ')';
            }

            $uuids[$result->uuid] = (int)$result->post_id;
        }

        return $uuids;
    }

    /**
     * Determine the post id for a project
     * @param string $uuid
     * @param string $postType
     * @param array|null $uuidMapping
     * @return int|null
     */
    private function determineProjectPostId($uuid, $postType, $uuidMapping = null)
    {
        if (!is_string($uuid) || strlen(trim($uuid)) === 0)
            throw new \Exception(__METHOD__ . '; Invalid uuid provided');

        if (!is_string($postType) || strlen(trim($postType)) === 0)
            throw new \Exception(__METHOD__ . '; Invalid post type');

        // First check mapping
        if (!is_array($uuidMapping) && array_key_exists($uuid, $uuidMapping))
            return $uuidMapping[$uuid];

        // Otherwise check database
        $metaKey = $postType . '_' . $this->systemLink->getCollectionUuid() . '_uuid';
        $tableName = $this->db->prefix . 'postmeta';
        $query = "SELECT post_id FROM " . $tableName . " WHERE meta_key = '" . $metaKey . "' AND meta_value = '" . $this->db->_real_escape($uuid) . "'";
        $results = $this->db->get_results($query);

        if (is_array($results) && !empty($results)) {
            $result = array_shift($results);
            return (int)$result->post_id;
        }

        return null;
    }

    /**
     * Store meta data for a specific post
     *
     * @param int $postId
     * @param string $postType
     * @param array $metaData
     * @return void
     */
    private function handlePostMetaData($postId, $postType, $metaData)
    {
        // Add uuid / collection uuid mapping to meta data
        if (isset($metaData['uuid']))
            $metaData[$this->systemLink->getCollectionUuid() . '_uuid'] = $metaData['uuid'];

        // Add collection uuid / system link name to meta data
        $metaData['collectionUuid'] = $this->systemLink->getCollectionUuid();
        $metaData['systemLinkName'] = $this->systemLink->getName();

        // Retrieve current meta data
        $oldFields = get_post_custom_keys((int)$postId);
        if (empty($oldFields))
            $oldFields = array();

        // Insert new meta data
        $updatedFields = array();
        if (count($metaData) > 0) {
            // Determine custom plaats meta data
            if (!empty($metaData['Plaats'])) {
                $city = strtoupper($metaData['Plaats']);
                if (array_key_exists($city, $this->alternativeCities))
                    $metaData['PlaatsCustom'] = $this->alternativeCities[$city];
            }

            // Determine custom wijk/buurt meta data
            if (!empty($metaData['Plaats']) && !empty($metaData['Wijk'])) {
                // Determine custom area
                $customAreas = get_option('yog_custom_areas', array());
                $areaKey = strtoupper($metaData['Plaats'] . '/' . $metaData['Wijk']);

                if (array_key_exists($areaKey, $customAreas))
                    $metaData['WijkCustom'] = $customAreas[$areaKey]['value'];

                // Determine custom neighbourhood
                if (!empty($metaData['Buurt'])) {
                    $customNeighbourhoods = get_option('yog_custom_neighbourhoods', array());
                    $neighbourhoodKey = $areaKey . '/' . strtoupper($metaData['Buurt']);

                    if (array_key_exists($neighbourhoodKey, $customNeighbourhoods))
                        $metaData['BuurtCustom'] = $customNeighbourhoods[$neighbourhoodKey]['value'];
                }
            }

            // Set meta data
            foreach ($metaData as $key => $val) {
                if (!empty($val)) {
                    update_post_meta($postId, $postType . '_' . $key, $val);
                    $updatedFields[] = $postType . '_' . $key;
                }
            }
        }

        /* Cleanup old meta data */
        // Do not delete media link / relation fields
        $deleteFields = array_diff($oldFields, array($postType . '_Relaties', $postType . '_Links', $postType . '_Documenten', $postType . '_Videos'));
        // Do not delete updated fields
        $deleteFields = array_diff($deleteFields, $updatedFields);

        // Check for configured yog_no_delete_meta_keys
        $noDeleteMetaKeys = get_option('yog_no_delete_meta_keys', array());
        if (!empty($noDeleteMetaKeys))
            $deleteFields = array_diff($deleteFields, $noDeleteMetaKeys);

        // Do not delete the relation latitude / longitude fields upon syncing (they are not available in
        // Yes-co yet so we need to prevent them from being deleted when edited manually in WordPress)
        $deleteFields = array_diff($deleteFields, array('relatie_Longitude', 'relatie_Latitude'));

        // Do not delete the relation _thumbnail_id / _nodelete upon syncing (no image available in 3mcp)
        if ($postType === YOG_POST_TYPE_RELATION)
            $deleteFields = array_diff($deleteFields, array('_thumbnail_id', $postType . '_nodelete'));

        if (is_array($deleteFields) && count($deleteFields) > 0) {
            foreach ($deleteFields as $deleteField) {
                delete_post_meta((int)$postId, $deleteField);
            }
        }
    }

    /**
     * @desc Register project categories if needed
     *
     * @param void
     * @return void
     */
    private function registerCategories()
    {
        $consumentId = $this->createCategory('Consument', 'consument');
        $woonruimteId = $this->createCategory('Woonruimte', 'woonruimte', $consumentId);
        $bogId = $this->createCategory('Bedrijf', 'bedrijf');
        $boprId = $this->createCategory('BOG project', 'bog-project');
        $nbId = $this->createCategory('Nieuwbouw projecten', 'nieuwbouw-projecten');
        $nbprId = $this->createCategory('Nieuwbouw project', 'nieuwbouw-project', $nbId);
        $nbtyId = $this->createCategory('Nieuwbouw type', 'nieuwbouw-type', $nbId);
        $nbbnId = $this->createCategory('Nieuwbouw bouwnummer', 'nieuwbouw-bouwnummer', $nbId);
        $complexId = $this->createCategory('Complexen', 'complexen');
        $bbprId = $this->createCategory('Complex', 'complex', $complexId);
        $bbtyId = $this->createCategory('Complex type', 'complex-type', $complexId);

        $categoryIdMapping = array(
            'consument' => $consumentId,
            'woonruimte' => $woonruimteId,
            'bedrijf' => $bogId,
            'bog-project' => $boprId,
            'nieuwbouw-projecten' => $nbprId,
            'nieuwbouw-type' => $nbtyId,
            'nieuwbouw-bouwnummer' => $nbbnId,
        );

        // Subcategories
        $subcategories = array($consumentId => array('bestaand' => 'Bestaand',
            'nieuwbouw' => 'Nieuwbouw',
            'open-huis' => 'Open huis',
            'bouwgrond' => 'Bouwgrond',
            'parkeergelegenheid' => 'Parkeergelegenheid',
            'berging' => 'Berging',
            'onderstuk' => 'Onderstuk',
            'opslagruimte' => 'Opslagruimte',
            'standplaats' => 'Standplaats',
            'ligplaats' => 'Ligplaats',
            'verhuur' => 'Verhuur',
            'verkoop' => 'Verkoop',
            'verkochtverhuurd' => 'Verkocht/verhuurd',
            'verkocht' => 'Verkocht',
            'verhuurd' => 'Verhuurd',
            'woonruimte-actief' => 'Actieve woningen',
            'bouwgrond-actief' => 'Actieve bouwgronden',
            'parkeergelegenheid-actief' => 'Actieve parkeergelegenheden',
            'berging-actief' => 'Actieve bergingen',
            'onderstuk-actief' => 'Actieve onderstukken',
            'opslagruimte-actief' => 'Actieve opslagruimtes',
            'standplaats-actief' => 'Actieve standplaatsen',
            'ligplaats-actief' => 'Actieve ligplaatsen'),
            $woonruimteId => array('appartement' => 'Appartement',
                'woonhuis' => 'Woonhuis'),
            $bogId => array('bog-bestaand' => 'Bestaand',
                'bog-nieuwbouw' => 'Nieuwbouw',
                'bog-verkoop' => 'Verkoop',
                'bog-verhuur' => 'Verhuur',
                'bog-verkochtverhuurd' => 'Verkocht/verhuurd',
                'bog-verkocht' => 'Verkocht',
                'bog-verhuurd' => 'Verhuurd',
                'bog-actief' => 'Actieve BOG objecten',
                'bedrijfsruimte' => 'Bedrijfsruimte',
                'bog-bouwgrond' => 'Bouwgrond',
                'bog-garagebox' => 'Garagebox',
                'horeca' => 'Horeca',
                'kantoorruimte' => 'Kantoorruimte',
                'winkelruimte' => 'Winkelruimte',
                'leisure' => 'Leisure',
                'maatschappelijk-vastgoed' => 'Maatschappelijk vastgoed',
                'praktijkruimte' => 'Praktijkruimte',
                'verhard-buitenterrein' => 'Verhard buitenterrein',
                'bog-belegging' => 'Belegging'),
            $boprId => array('bog-project-verkoop' => 'Verkoop',
                'bog-project-verhuur' => 'Verhuur',
                'bog-project-verkochtverhuurd' => 'Verkocht/verhuurd'),
            $nbprId => array('nieuwbouw-project-verkoop' => 'Verkoop',
                'nieuwbouw-project-verhuur' => 'Verhuur',
                'nieuwbouw-project-verkochtverhuurd' => 'Verkocht/verhuurd'),
            $nbtyId => array('nieuwbouw-type-verkoop' => 'Verkoop',
                'nieuwbouw-type-verhuur' => 'Verhuur'),
            $nbbnId => array('nieuwbouw-bouwnummer-verkochtverhuurd' => 'Verkocht/verhuurd'),
            $bbprId => array('complex-verkoop' => 'Verkoop',
                'complex-verhuur' => 'Verhuur'),
            $bbtyId => array('complex-type-verkoop' => 'Verkoop',
                'complex-type-verhuur' => 'Verhuur')
        );

        $this->registerNewThemeCategories($categoryIdMapping, $subcategories);

        // Create subcategories
        foreach ($subcategories as $parentId => $values) {
            foreach ($values as $slug => $name) {
                $this->createCategory($name, $slug, $parentId);
            }
        }
    }

    /**
     * @desc Method registerNewThemeCategories Allow the theme to influence creation of extra categories
     *
     * @param {Array} $categoryIdMapping
     * @param {Array} $subcategories
     * @return {Void}
     */
    private function registerNewThemeCategories($categoryIdMapping, &$subcategories)
    {
        $templateDir = get_template_directory();

        // Include the Theme's function directory
        if (file_exists($templateDir . '/functions.php'))
            require_once($templateDir . '/functions.php');

        // Execute the hook if provided in the functions.php
        if (function_exists('yog_plugin_register_new_categories')) {
            $extendCategories = yog_plugin_register_new_categories($categoryIdMapping);

            if (is_array($extendCategories)) {
                foreach ($extendCategories as $categoryId => $values) {
                    if (is_numeric($categoryId) && isset($subcategories[$categoryId])) {
                        $currentValues = $subcategories[$categoryId];

                        if (is_array($values)) {
                            $currentValues = array_merge($currentValues, $values);

                            // Overwrite the current categories
                            $subcategories[$categoryId] = $currentValues;
                        }
                    }
                }
            }
        }
    }

    /**
     * @desc Create a term (if not existing
     *
     * @param string $name
     * @param int $parentTermId (optional)
     * @return int
     */
    private function createCategory($name, $slug, $parentTermId = 0)
    {
        $categoryTaxonomy = (get_option('yog_cat_custom') ? 'yog_category' : 'category');
        $term = get_term_by('slug', $slug, $categoryTaxonomy, ARRAY_A);

        if (!$term)
            $term = wp_insert_term($name, $categoryTaxonomy, array('description' => $name, 'parent' => $parentTermId, 'slug' => $slug));

        if ($term instanceof WP_Error) {
            return (int)$term->error_data['term_exists'];
        } else {
            return (int)$term['term_id'];
        }
    }

    private function addDebugMessage($msg)
    {
        $this->debugMessages[] = $msg;
    }
}