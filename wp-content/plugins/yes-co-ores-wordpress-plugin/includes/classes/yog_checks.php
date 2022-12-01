<?php
class YogChecks
{
	/**
	 * Check for errors
	 *
	 * @param void
	 * @return array
	 */
	public static function checkForErrors()
	{
		$errors   = array();

		// Upload folder writable
		$uploadDir = wp_upload_dir();

		if (!empty($uploadDir['error']))
		{
			$errors[] = $uploadDir['error'];
		}
		else if (!is_writable($uploadDir['basedir']))
		{
			$errors[] = 'De upload map van uw WordPress installatie is beveiligd tegen schrijven. Dat betekent dat er geen afbeelingen van de objecten gesynchroniseerd kunnen worden. Stel onderstaande locatie zo in, dat deze beschreven kan worden door de webserver. <br /><i><b>' . $uploadDir['basedir'] .'</b></i>';
		}
		else
		{
			$projectsDir	= $uploadDir['basedir'] . '/projecten';
			if (is_dir($projectsDir) && !is_writable($projectsDir))
				$errors[] = 'De "projecten" map in de upload map is beveiligd tegen schrijven. Stel onderstaande locatie zo in, dat deze beschreven kan worden door de webserver. <br /><i><b>' . $uploadDir['basedir'] .'</b></i>';
		}

		// PHP version check
		if (!version_compare(PHP_VERSION, '5.2.1', '>='))
			$errors[] = 'PHP versie ' . PHP_VERSION . ' is gedetecteerd, de plugin vereist minimaal PHP versie 5.2.1. Neem contact op met je hosting provider om de PHP versie te laten upgraden';

		// Lib XML check
		if (!extension_loaded('libxml'))
			$errors[] = 'De php Library <b>libxml</b> is niet geinstalleerd. Neem contact op met je hosting provider om libxml te laten installeren';

		// Wordpress version
		global $wp_version;
		if ((float) $wp_version < 3.1)
			$errors[] = 'Wordpress versie ' . $wp_version . ' is gedetecteerd, voor deze plugin is Wordpress versie 3.1 of hoger vereist. Upgrade wordpress naar een nieuwere versie';

		return $errors;
	}

	/**
	 * Check for warnings
	 *
	 * @param void
	 * @return array
	 */
	public static function checkForWarnings()
	{
		$warnings = array();

		if (defined('TEMPLATEPATH'))
		{
      $neededTemplates = [
        'single-huis.php'     => 'Wonen object details',
        'single-bedrijf.php'  => 'BOG object details',
        'single-yog-nbpr.php' => 'Nieuwbouw Project details',
        'single-yog-nbty.php' => 'Nieuwbouw type details',
        'single-yog-bbpr.php' => 'Bestaande bouw complexen',
        'single-yog-bbty.php' => 'Bestaande bouw complex types',
        'single-yog-bopr.php' => 'BOG project details'
      ];

      $missingTemplates = [];

      foreach ($neededTemplates as $template => $title)
      {
        if (locate_template($template) == '')
          $missingTemplates[$template] = $title;
      }

      if (count($missingTemplates) > 0)
      {
        $warning  = 'Het ingestelde thema heeft op dit moment geen \'' . implode('\', \'', array_keys($missingTemplates)) . '\' template' . (count($missingTemplates) > 1 ? 's' : '') . '.';
        $warning .= 'Er zal een alternatieve methode gebruikt worden voor het tonen van de \'' . implode('\', \'', $missingTemplates) . '\'.';
        
        $warnings[] = $warning;
      }
		}

		// PHP version check
		if (version_compare(PHP_VERSION, '5.2.1', '>=') && !version_compare(PHP_VERSION, '7.3', '>='))
			$warnings[] = 'PHP versie ' . PHP_VERSION . ' is gedetecteerd, voor deze php versie worden geen (beveiligings) updates meer uitgebracht. We raden je aan om contact op te nemen met je hosting provider om de PHP versie te laten upgraden. Er is ook geen garantie dat deze plugin blijft functioneren met deze php versie.';

    // Check for CURL
    if (function_exists('curl_version') === false)
      $warnings[] = 'De php Library <b>Curl</b> is niet geinstalleerd. Dit kan er mogelijk voor zorgen dat objecten niet gesynchroniseerd kunnen worden. Neem contact op met je hosting provider om curl te laten installeren.';

		return $warnings;
	}

	/**
	 * Get wordpress settings
	 *
	 * @Param void
	 * @return array
	 */
	public static function getSettings()
	{
		$noExtraTexts	= get_option('yog_noextratexts');
		$englishText	= get_option('yog_englishtext');
		$settings			= array();

		// Wordpress version
		global $wp_version;
		$settings['Wordpress version'] = $wp_version;

		// Plugin version
		$settings['Plugin version'] = YOG_PLUGIN_VERSION;

		// PHP version
		$settings['PHP version'] = PHP_VERSION;

		// allow_url_fopen
		$settings['allow_url_fopen'] = (ini_get('allow_url_fopen')) ? 'enabled' : 'disabled';

    // Server date/time
    $settings['current date/time']  = date('c');

    if (function_exists('mysql_get_client_info'))
      $settings['mysql_version'] = mysql_get_client_info();

    // Max execution time
    $settings['max_execution_time'] = ini_get('max_execution_time');

    // Wordpress settings
    $settings['Custom categories enabled']  = (get_option('yog_cat_custom') ? 'true' : 'false');
    $settings['3mcp version']               = get_option('yog_3mcp_version');
    $settings['Extra texts']								= !empty($noExtraTexts) ? ($noExtraTexts == 'seperate' ? 'seperate' : 'skip') : 'include';
		$settings['English text']								= !empty($englishText) ? $englishText : 'include';
    $settings['Synchronisation disabled']   = (get_option('yog_sync_disabled') ? 'true' : 'false');

    // Last sync
    $lastSync = get_option('yog-last-sync');
    if (!empty($lastSync))
      $settings['Last sync']  = date('c', $lastSync);

      $yogSystemLinkManager       = new YogSystemLinkManager();
      $systemLinks                = $yogSystemLinkManager->retrieveAll();

      $systemLinksInfo            = array();

      try
      {
          if (!empty($systemLinks))
          {
              $systemLinkInfo = array();

              foreach ($systemLinks as $systemLink)
              {
                  $systemLinkInfo['Name'] = $systemLink->getName();
                  $systemLinkInfo['State'] = $systemLink->getState();
                  $systemLinkInfo['Collection uuid'] = $systemLink->getCollectionUuid();
                  $systemLinkInfo['Credentials set'] = ($systemLink->hasCredentials() ? 'Yes' : 'No');

                  $lastSync                      = get_option('yog-' . $systemLink->getCollectionUuid() . '-last-sync', false);

                  $systemLinkInfo['Last sync'] =  date('c', $lastSync);

                  $systemLinksInfo[] = $systemLinkInfo;
              }
          }
      }
      catch (\Exception $e)
      {
          $settings['System links read error'] = $e->getMessage();
      }

      $settings['System links'] = $systemLinksInfo;

		return $settings;
	}
}