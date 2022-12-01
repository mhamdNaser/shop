<?php
require_once(__DIR__ . '/yog_rest_api_key.php');

/**
* @desc YogRestApiKeyManager
* @author Kees Brandenburg - Yes-co Nederland
*/
class YogRestApiKeyManager
{
  const API_KEYS_OPTION = 'yog_rest_api_keys';

  /**
   * Store an API key
   * @param \YogRestApiKey $apiKey
   * @return void
   */
  public function store(\YogRestApiKey $apiKey)
  {
    $storedKeys   = get_option(self::API_KEYS_OPTION, []);
    $updated      = false;

    // Check if API key already exists, if so update it
    foreach ($storedKeys as $i => $storedKey)
    {
      if (is_array($storedKey) && isset($storedKey['key']) && $storedKey['key'] === $apiKey->getKey())
      {
        $storedKeys[$i] = $apiKey->toArray();
        $updated        = true;
      }
    }

    // If API key wasn't used to update an existing, add it as new
    if ($updated === false)
      $storedKeys[] = $apiKey->toArray();

		update_option(self::API_KEYS_OPTION, $storedKeys, false);
  }

  /**
   * Delete an API key
   * @param \YogRestApiKey $apiKey
   * @return void
   */
  public function delete(\YogRestApiKey $apiKey)
  {
    $storedKeys = get_option(self::API_KEYS_OPTION, []);
    $apiKeys    = [];

    foreach ($storedKeys as $storedKey)
    {
      if (is_array($storedKey) && isset($storedKey['key']) && $storedKey['key'] !== $apiKey->getKey())
        $apiKeys[] = $storedKey->toArray();
    }

    update_option(self::API_KEYS_OPTION, $apiKeys, false);
  }

  /**
   * Check if a specific key exists
   * @param string $key
   * @return bool
   */
  public function checkKeyExists($key)
  {
    $storedKeys = get_option(self::API_KEYS_OPTION, []);

    foreach ($storedKeys as $storedKey)
    {
      if (is_array($storedKey) && isset($storedKey['key']) && $storedKey['key'] === $key)
        return true;
    }

    return false;
  }

  /**
   * Retrieve API key by key
   * @param string $key
   * @return \YogRestApiKey
   * @throws \Exception
   */
  public function retrieveByKey($key)
  {
    $storedKeys = get_option(self::API_KEYS_OPTION, []);

    foreach ($storedKeys as $storedKey)
    {
      if (is_array($storedKey) && isset($storedKey['key']) && $storedKey['key'] === $key)
        return YogRestApiKey::createFromArray($storedKey);
    }

    throw new \Exception(__METHOD__ . '; API key not found');
  }

  /**
   * Retrieve all API keys
   * @return array
   */
  public function retrieveAll()
  {
    $storedKeys = get_option(self::API_KEYS_OPTION, []);
    $apiKeys    = [];

    foreach ($storedKeys as $storedKey)
    {
      $apiKeys[] = YogRestApiKey::createFromArray($storedKey);
    }

    return $apiKeys;
  }
}