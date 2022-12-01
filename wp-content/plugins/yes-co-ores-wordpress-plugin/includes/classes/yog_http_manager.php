<?php

  /**
  * @desc YogHttpManager
  * @author Stefan van Zanden - Yes-co Nederland
  */
  class YogHttpManager
  {
    /**
     * @desc Method url
     *
     * @param string $url
     * @param string $method
     * @param array $headers
     * @return string
     */
    public static function retrieveContent($url, $method = 'GET', $headers = array())
    {
      $args = array(
        'timeout'   => 30
      );

      if (!empty($method))
        $args['method'] = $method;

      if (!empty($headers))
        $args['headers'] = $headers;

      $response = wp_remote_get($url, $args);
	  $httpCode	= wp_remote_retrieve_response_code( $response );

	  if (!empty($httpCode) && !in_array($httpCode, array(200, 304)))
		return false;

      $body		= wp_remote_retrieve_body($response);
      if (empty($body))
		return false;

	  return $body;
    }
  }