<?php
class YogRestApiController
{
  const API_NAMESPACE = 'yesco-og/v1';

  /**
   * Constructor
   */
  public function __construct()
  {
    require_once(__DIR__ . '/classes/yog_rest_api_key.php');
    require_once(__DIR__ . '/classes/yog_rest_api_key_manager.php');
    require_once(__DIR__ . '/classes/yog_system_link_manager.php');
  }
  
  /**
   * Init the REST API routes
   * @param void
   * @return void
   */
  public function init()
  {
    // Register routes
    register_rest_route(self::API_NAMESPACE, '//systemlink/add', [
      'methods'             => 'POST',
      'callback'            => [$this, 'addSystemLink'],
      'permission_callback' => [$this, 'checkApiWritePermission']
    ]);

    register_rest_route(self::API_NAMESPACE, '//systemlink/(?P<system>[a-zA-Z0-9\-]+)/update', [
      'methods'             => 'POST',
      'callback'            => [$this, 'updateSystemLink'],
      'permission_callback' => [$this, 'checkApiWritePermission']
    ]);
  }


  /**
   * Check API write permission
   *
   * @param \WP_REST_Request $request Current request.
   * @return bool|\WP_Error
   */
  public function checkApiWritePermission(\WP_REST_Request $request)
  {
    // Get API key from header
    $providedApiKey = $request->get_header('X-API-Key');

    // Validate API key
    if (!empty($providedApiKey))
    {
      $apiKeyManager  = new YogRestApiKeyManager();
      $apiKeyExists   = $apiKeyManager->checkKeyExists($providedApiKey);

      if ($apiKeyExists === true)
        return true;
    }

    return new \WP_Error('rest_forbidden', esc_html__('You do not have sufficient rights'), ['status' => 401]);
  }

  /**
   * Add a system link
   * @param \WP_REST_Request $request
   * @return array|\WP_Error
   */
  public function addSystemLink(\WP_REST_Request $request)
  {
    $systemLinkManager  = new \YogSystemLinkManager();

    // Collect params
    $activationCode = $request->get_param('activation_code');
    $collectionUuid = $request->has_param('collection_uuid') ? sanitize_text_field($request->get_param('collection_uuid')) : \YogSystemLink::EMPTY_UUID;
    $name           = $request->has_param('name') ? sanitize_text_field($request->get_param('name')) : \YogSystemLink::EMPTY_NAME;
    $username       = $request->get_param('3mcp_username');
    $password       = $request->get_param('3mcp_password');

    // Validate activation code
    if (empty($activationCode))
      return new \WP_Error('invalid_params', esc_html__('No activation code provided'), ['status' => 400]);
   
    // Determine state
    if (empty($collectionUuid) || $collectionUuid === \YogSystemLink::EMPTY_UUID)
      $state  = \YogSystemLink::STATE_NOT_ACTIVATED;
    else
      $state  = \YogSystemLink::STATE_ACTIVE;

    // Create system link
    $systemLink = new \YogSystemLink($name, $state, $activationCode, $collectionUuid);

    // Add username / password
    if (!empty($username) && !empty($password))
      $systemLink->setCredentials(new \YogSystemLinkCredentials(sanitize_text_field($username), sanitize_text_field($password)));

    // Store system link
    $systemLinkManager->store($systemLink);

    // Return response
    return $this->createSystemLinkResponse($systemLink);
  }

  /**
   * Update the name of a system link
   * @param \WP_REST_Request $request
   * @return array|\WP_Error
   */
  public function updateSystemLink(\WP_REST_Request $request)
  {
    // Get activation code / collection uuid
    $system = $request->get_param('system');

    // Validate activation code
    if (empty($system))
      return new \WP_Error('invalid_params', esc_html__('No activation code/collection uuid provided'), ['status' => 400]);

    // Validate name
    if (!$request->has_param('name'))
      return new \WP_Error('invalid_params', esc_html__('No name provided'), ['status' => 400]);

    // Retrieve system link
    $systemLinkManager  = new \YogSystemLinkManager();
    $systemLinks        = $systemLinkManager->retrieveAll();
    $systemLink         = null;

    foreach ($systemLinks as $curSystemLink)
    {
      if ($curSystemLink->getActivationCode() === $system)
        $systemLink = $curSystemLink;
      else if ($curSystemLink->getCollectionUuid() === $system)
        $systemLink = $curSystemLink;
    }

    if (is_null($systemLink))
      return new \WP_Error('invalid_params', esc_html__('No system link with provided activation code / collection uuid found'), ['status' => 400]);

    // Update name
    $systemLink->setName(sanitize_text_field($request->get_param('name')));

    // Store system link
    $systemLinkManager->store($systemLink);

    // Return response
    return $this->createSystemLinkResponse($systemLink);
  }

  /**
   * Create a system link response
   * @param \YogSystemLink $systemLink
   * @return array
   */
  private function createSystemLinkResponse(\YogSystemLink $systemLink)
  {
    $response = ['activation_code' => $systemLink->getActivationCode()];

    // Add callback url to response (if system link is already active)
    if ($systemLink->getState() === \YogSystemLink::STATE_ACTIVE)
    {
      $action     = 'sync_yesco_og';
      $signature  = md5('action=' . $action . 'uuid=' . $systemLink->getCollectionUuid() . $systemLink->getActivationCode());
      $syncUrl		= get_site_url() . '/?action=' . $action . '&uuid=' . $systemLink->getCollectionUuid() . '&signature=' . $signature;

      $response['callback_url'] = $syncUrl;
    }

    return $response;
  }
}

// Add Rest API controller
add_action('rest_api_init', function () {
  $controller = new YogRestApiController();
  $controller->init();
});