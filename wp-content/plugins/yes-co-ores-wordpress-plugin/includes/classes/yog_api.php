<?php
class YogApi
{
	const API_URL = 'https://api.yes-co.com/wp/';

	/**
	 * Enrich a system link through the API
	 * @param \YogSystemLink $systemLink
	 * @throws \YogException
	 */
	public function enrichSystemLink(\YogSystemLink $systemLink)
	{
		if ($systemLink->getState() === \YogSystemLink::STATE_ACTIVE)
		{
			$url			= self::API_URL . 'systemlink/';
			$apiKey		= $systemLink->getActivationCode() . '_' . $systemLink->getCollectionUuid();
			$headers        = array( "X-API-Key" => $apiKey );

			// Open the file using the HTTP headers set above
			$content = YogHttpManager::retrieveContent($url, 'GET', $headers);

			if ($content === false)
				throw new \YogException(__METHOD__ . '; Invalid response from API');

			$json = json_decode($content);
			if (!is_object($json))
				throw new \YogException(__METHOD__ . '; Invalid json returned by API');

			if (empty($json->u) || empty($json->p))
				throw new \YogException(__METHOD__ . '; Invalid response from API');

			$systemLink->setCredentials(new \YogSystemLinkCredentials(sanitize_text_field($json->u), sanitize_text_field($json->p)));
		}
	}
}
