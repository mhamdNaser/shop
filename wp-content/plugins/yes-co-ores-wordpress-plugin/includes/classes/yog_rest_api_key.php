<?php
/**
* @desc YogRestApiKey
* @author Kees Brandenburg - Yes-co Nederland
*/
class YogRestApiKey
{
  /**
   * Constructor
   * @param string $key
   * @param string $name
   * @throws \InvalidArgumentException
   * @return \YogRestApiKey
   */
  public function __construct($key, $name)
  {
    $this->setKey($key);
    $this->setName($name);
  }

  /**
   * Set the API key
   * @param string $key
   * @throws \InvalidArgumentException
   */
  public function setKey($key)
  {
    if (!is_string($key) || strlen(trim($key)) === 0)
      throw new \InvalidArgumentException(__METHOD__ . '; Invalid key');

    $this->key = $key;
  }

  /**
   * Get the API key
   * @return string
   */
  public function getKey()
  {
    return $this->key;
  }

  /**
   * Set the name
   * @param string $name
   * @throws \InvalidArgumentException
   */
  public function setName($name)
  {
    if (!is_string($name) || strlen(trim($name)) === 0)
      throw new \InvalidArgumentException(__METHOD__ . '; Invalid name$name');

    $this->name = $name;
  }

  /**
   * Get the name
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Convert API key object to array
   * @param void
   * @return array
   */
  public function toArray()
  {
    return ['key' => $this->getKey(), 'name' => $this->getName()];
  }

  /**
   * Create API key object from array
   * @param array $array
   * @return \YogRestApiKey
   * @throws \InvalidArgumentException
   */
  static public function createFromArray($array)
  {
    if (!is_array($array) || !isset($array['key']) || !isset($array['name']))
      throw new \InvalidArgumentException(__METHOD__ . '; No key/name item in array');

    return new self($array['key'], $array['name']);
  }
}