<div class="wrap">
  <div class="icon32 icon32-config-yog"><br /></div>
  <h2>Yes-co Open Real Estate System instellingen</h2>
  <?php require_once(__DIR__ . '/parts/menu.php'); ?>
  <?php
  if (!empty($errors))
  {
    echo '<div id="message" class="error below-h2" style=" padding: 5px 10px;">';
      echo '<b>Er zijn fouten geconstateerd waardoor de Yes-co ORES plugin niet naar behoren kan functioneren</b>:';
      echo '<ul style="padding-left:15px;list-style-type:circle"><li>' . implode('</li><li>', $errors) . '</li></ul>';
    echo '</div>';
  }

  if (!empty($warnings))
  {
    echo '<div id="message" class="error below-h2" style="padding: 5px 10px; background-color:#feffd1;border-color:#d5d738;">';
      echo '<ul style="padding-left:15px;list-style-type:circle"><li>' . implode('</li><li>', $warnings) . '</li></ul>';
    echo '</div>';
  }

  if (empty($errors))
  {
		$noExtraText  = get_option('yog_noextratexts', false);
		$englishText	= get_option('yog_englishtext', false);
		$relationSync	= get_option('yog_relation_sync', false);
    ?>
		<form method="post" action="options-general.php?page=yesco_OG_synchronisation" >
			<?php
			wp_nonce_field('update-options');
			?>
			<h3>Synchronisatie</h3>
			<div class="yog-setting">
				<input type="checkbox"<?php echo (get_option('yog_cat_custom') ? ' checked':'');?> name="yog_cat_custom" id="yog-toggle-cat-custom" class="yog-toggle-setting" />
				<label for="yog-toggle-cat-custom">Objecten bij synchronisatie koppelen aan Yes-co ORES categorie&euml;n i.p.v. de standaard wordpress categorie&euml;n (bijv.: <?php echo site_url();?>/objecten/consument/ i.p.v. <?php echo site_url();?>/category/consument/).</label><span class="msg"></span>
			</div>
			<div class="yog-setting">
				<input type="checkbox"<?php echo (get_option('yog_sync_disabled') ? ' checked':'');?> name="yog_sync_disabled" id="yog-toggle-sync-disabled" class="yog-toggle-setting" />
				<label for="yog-toggle-sync-disabled">Normale synchronisatie uitschakelen. (Alleen gebruiken indien de synchronisatie op een andere manier gedaan wordt, zoals via WPCli)</label><span class="msg"></span>
			</div>

			<br />
			<div class="yog-setting">
				<input type="radio" value=""<?php echo (empty($noExtraText) ? ' checked':'');?> name="yog_noextratexts" id="yog-toggle-extratext-incl" class="yog-set-setting" />
				<label for="yog-toggle-extratext-incl">Extra teksten van objecten <u>wel</u> meenemen bij synchronisatie (als onderdeel van de totale tekst).</label><span class="msg"></span>
			</div>
			<div class="yog-setting">
				<input type="radio" value="skip"<?php echo (!empty($noExtraText) ? ' checked':'');?> name="yog_noextratexts" id="yog-toggle-extratext-skip" class="yog-set-setting" />
				<label for="yog-toggle-extratext-skip">Extra teksten van objecten <u>niet</u> meenemen bij synchronisatie.</label><span class="msg"></span>
			</div>
			<div class="yog-setting">
				<input type="radio" value="seperate"<?php echo ($noExtraText == 'seperate' ? ' checked':'');?> name="yog_noextratexts" id="yog-toggle-extratext-sepp" class="yog-set-setting" />
				<label for="yog-toggle-extratext-sepp">Extra teksten van objecten <u>apart</u> meenemen bij synchronisatie (Geen onderdeel van de tekst, wel als apart kenmerk op te vragen).</label><span class="msg"></span>
			</div>

			<br />
			<div class="yog-setting">
				<input type="radio" value=""<?php echo (empty($englishText) ? ' checked':'');?> name="yog_englishtext" id="yog-toggle-englishtext-incl" class="yog-set-setting" />
				<label for="yog-toggle-englishtext-incl">Engelse tekst van objecten <u>wel</u> meenemen bij synchronisatie (als onderdeel van de totale tekst).</label><span class="msg"></span>
			</div>
			<div class="yog-setting">
				<input type="radio" value="skip"<?php echo ($englishText == 'skip' ? ' checked':'');?> name="yog_englishtext" id="yog-toggle-englishtext-skip" class="yog-set-setting" />
				<label for="yog-toggle-englishtext-skip">Engelse tekst van objecten <u>niet</u> meenemen bij synchronisatie.</label><span class="msg"></span>
			</div>
			<div class="yog-setting">
				<input type="radio" value="seperate"<?php echo ($englishText == 'seperate' ? ' checked':'');?> name="yog_englishtext" id="yog-toggle-englishtext-sepp" class="yog-set-setting" />
				<label for="yog-toggle-englishtext-sepp">Engelse tekst van objecten <u>apart</u> meenemen bij synchronisatie (Geen onderdeel van de tekst, wel als apart kenmerk op te vragen).</label><span class="msg"></span>
			</div>

			<br />
			<div class="yog-setting">
				<input type="radio" value=""<?php echo (empty($relationSync) ? ' checked':'');?> name="yog_relation_sync" id="yog-toggle-relation-default" class="yog-set-setting" />
				<label for="yog-toggle-relation-default">Alleen kantoor/medewerkers meenemen bij synchronisatie.</label><span class="msg"></span>
			</div>
			<div class="yog-setting">
				<input type="radio" value="all"<?php echo ($relationSync == 'all' ? ' checked':'');?> name="yog_relation_sync" id="yog-toggle-relation-all" class="yog-set-setting" />
				<label for="yog-toggle-relation-all">Alle beschikbare relaties meenemen bij de synchronisatie.</label><span class="msg"></span>
			</div>

			<br />
			<table>
				<tr>
					<td>Voorkeur voor te synchroniseren media formaat:</td>
					<td>
						<div class="yog-setting">
							<select name="yog_media_size" id="yog_media_size" class="yog-set-setting">
								<?php
								foreach ($mediaSizeOptions as $key => $title)
								{
								  echo '<option value="' . esc_attr($key) . '"' . ($mediaSizeOption == $key ? ' selected="selected"' : '') . '>' . esc_html($title) . '</option>';
								}
								?>
							</select><span class="msg"></span>
						</div>
					</td>
				</tr>
				<tr>
					<td>Kwaliteit van afbeeldingen bij synchronisatie:</td>
					<td>
						<div class="yog-setting">
							<select name="yog_media_quality" id="yog_media_quality" class="yog-set-setting">
								<?php
								$quality					= 75;
								$selectedQuality	= (int) get_option('yog_media_quality', 82);

								while ($quality <= 90)
								{
								  echo '<option value="' . esc_attr($quality) . '"' . ($quality == $selectedQuality ? ' selected="selected"' : '') . '>' . esc_html($quality) . '</option>';
									$quality++;
								}
								?>
							</select><span class="msg"></span>
						</div>
					</td>
				</tr>
			</table>

			<h3>Gekoppelde yes-co open accounts<?php echo (!empty($systemLinks) ? ' (' . count($systemLinks) . ')' : '');?></h3>
			<span id="yog-add-system-link-holder">
				<b>Een koppeling toevoegen:</b><br>
				Activatiecode: <input id="yog-new-secret" name="yog-new-secret" type="text" style="width: 58px" maxlength="6" value="" />
        <input type="button" class="button-primary" id="yog-add-system-link" value="Koppeling toevoegen" style="margin-left: 10px;" />
			</span>
			<div id="yog-system-links">
				<?php

				if (!empty($systemLinks))
				{
          $numberOfSystemLinks = count($systemLinks);
          
					foreach ($systemLinks as $systemLink)
					{
						// create sync url
						$action     = 'sync_yesco_og';
						$signature  = md5('action=' . $action . 'uuid=' . $systemLink->getCollectionUuid() . $systemLink->getActivationCode());
						$syncUrl		= get_site_url() . '/?action=' . $action . '&uuid=' . $systemLink->getCollectionUuid() . '&signature=' . $signature;
						$systemLinkId	= 'yog-system-link-' . esc_attr($systemLink->getActivationCode());

						echo '<div class="system-link" id="' . $systemLinkId . '">';
							echo '<div data-sync-callback="' . $syncUrl . '">';
								echo '<b>Naam:</b> ' . esc_html($systemLink->getName()) .'<br />';
								echo '<b>Status:</b> ' . esc_html($systemLink->getState()) .'<br />';
								echo '<b>Activatiecode:</b> ' . esc_html($systemLink->getActivationCode()) .' <br />';

                                if (!$systemLink->isSyncEnabled()) {
                                    echo '<b>Normale synchronisatie uitgeschakeld!</b><br />';
                                }

								if (isset($systemObjectCounts[$systemLink->getCollectionUuid()]))
								  echo '<b>Aantal objecten:</b> <a href="' . esc_url(get_site_url()) . '?s=&object=all&amp;collection=' . esc_attr($systemLink->getCollectionUuid()) . '" target="_blank">' . esc_html($systemObjectCounts[$systemLink->getCollectionUuid()]) . '</a><br />';
                else if ($numberOfSystemLinks >= 10)
                  echo '<a href="' . esc_url(get_site_url()) . '?s=&object=all&amp;collection=' . esc_attr($systemLink->getCollectionUuid()) . '" target="_blank">Bekijk objecten</a><br />';

								echo '<a href="#TB_inline?width=600&height=250&inlineId=' . $systemLinkId . '-delete-screen" class="thickbox" title="Koppeling verwijderen">Koppeling verwijderen</a>';

								if ($systemLink->getState() === YogSystemLink::STATE_ACTIVE)
								{
									echo '&nbsp;|&nbsp;';
									echo '<a href="#TB_inline?width=600&height=470&inlineId=' . $systemLinkId . '-edit-screen" class="thickbox" title="Koppeling bewerken">Koppeling bewerken</a>';
								}
							echo '</div>';
						echo '</div>';
					}
				}
				?>
			</div>
		</form>

		<h3>Status</h3>
		<?php

		  $syncRunning = get_option('yog-sync-running', false);

		?>
		Draait synchronisatie op dit moment?: <b><?php echo ($syncRunning === false ? 'Nee' : 'Laatst vanaf: ' . esc_html(date('j F Y H:i:s', $syncRunning))); ?></b><br />
		Maximum execution time: <b><?php echo ini_get('max_execution_time'); ?></b> seconds

		<?php

			if (!empty($systemLinks))
			{
				foreach ($systemLinks as $systemLink)
				{
					$lastSync                      = get_option('yog-' . $systemLink->getCollectionUuid() . '-last-sync', false);
					$lastSyncReadMainCollection    = get_option('yog-' . $systemLink->getCollectionUuid() . '-last-sync-read-main-collection', false);
					$lastSyncResponse              = get_option('yog-' . $systemLink->getCollectionUuid() . '-last-sync-result', '');
					$lastSyncErrorMainFeed         = get_option('yog-' . $systemLink->getCollectionUuid() . '-last-sync-error-main-feed', '');
					$lastSyncedProject             = get_option('yog-' . $systemLink->getCollectionUuid() . '-last-sync-sync-project', '');

					if (empty($lastSync)) // @DEPRECATED
					    $lastSync    = get_option('yog-last-sync', false);

          // $html = '<input type="datetime-local" value="' . ($lastSync === false ? '' : esc_attr(date('Y-m-d\TH:i:s', $lastSync))) . '" name="yog_' . $systemLink->getCollectionUuid() . '_last_sync" id="yog-toggle-relation-all" class="yog-set-setting" />';

					echo '<div class="system-link" id="' . $systemLinkId . '">';
						echo '<div data-sync-callback="' . $syncUrl . '">';
							echo '<b>Naam:</b> ' . esc_html($systemLink->getName()) .'<br />';
							echo '<b>Laatste synchronisatie: </b>' . ($lastSync === false ? 'Nee' : esc_html(yog_format_timestamp('j F Y H:i:s', $lastSync))) . '<br />';
							echo '<b>Laatste synchronisatie feed uit kunnen lezen: </b>' . ($lastSyncReadMainCollection === false ? 'Nee' : esc_html(yog_format_timestamp('j F Y H:i:s', $lastSyncReadMainCollection))) . '<br />';

							if (!empty($lastSyncResponse))
							{
								$lastSyncResponse = json_decode($lastSyncResponse, true);
								if (is_array($lastSyncResponse) && isset($lastSyncResponse['debug']))
									unset($lastSyncResponse['debug']);

								echo '<b>Laatste synchronisatie response: </b> <pre style="overflow: auto;">' . esc_html(print_r($lastSyncResponse, true)) . '</pre><br />';
							}

							if (!empty($lastSyncErrorMainFeed))
							{
								$lastSyncErrorMainFeed = json_decode($lastSyncErrorMainFeed, true);

								echo '<b>Laatste synchronisatie error main feed: </b> <pre style="overflow: auto;">' . esc_html(print_r($lastSyncErrorMainFeed, true)) . '</pre><br />';
							}

							if (!empty($lastSyncedProject))
							{
								$lastSyncedProject = json_decode($lastSyncedProject, true);

								echo '<b>Laatste synchronisatie start voor project: </b> <pre style="overflow: auto;">' . esc_html(print_r($lastSyncedProject, true)) . '</pre><br />';
							}
							
						echo '</div>';
					echo '</div>';
				}
			}
			?>

		<?php
		// Generate system link lightboxes
		if (!empty($systemLinks))
		{
			foreach ($systemLinks as $systemLink)
			{
				$activationCode	= esc_attr($systemLink->getActivationCode());
				$systemLinkId		= 'yog-system-link-' . $activationCode;

				// Delete lightbox
				?>
				<div id="<?php echo $systemLinkId;?>-delete-screen" class="hide">
					<form method="post" action="options-general.php?page=yesco_OG_synchronisation" id="<?php echo $systemLinkId;?>-remove">
						<?php
						wp_nonce_field('yog-delete-system-link-' . $activationCode, '_wpnonce_delete' . $activationCode);
						?>
						<div class="notice-inline notice-warning"><p>Weet je zeker dat je deze koppeling wilt verwijderen? Alle objecten die via deze koppeling zijn gesynchroniseerd zullen ook verwijderd worden.</p></div>
						<br />
						<a class="button-primary" onclick="yogRemoveSystemLink('<?php echo esc_attr($systemLink->getActivationCode());?>');">Koppeling verwijderen</a>
					</form>
				</div>
				<?php
				// Edit lightbox
				if ($systemLink->getState() === YogSystemLink::STATE_ACTIVE)
				{
					// Edit lightbox
					?>
					<div id="<?php echo $systemLinkId;?>-edit-screen" class="hide">
						<form method="post" action="options-general.php?page=yesco_OG_synchronisation">
							<input type="hidden" name="activation_code" value="<?php echo $activationCode;?>"/>
							<input type="hidden" name="collection_uuid" value="<?php echo esc_attr($systemLink->getCollectionUuid());?>"/>
							<?php
							wp_nonce_field('yog-update-system-link-name-' . $activationCode, '_wpnonce_name_' . $activationCode);
							?>
							<p>
								<label for="name"><?php echo __('Naam', 'yog-plugin');?>: </label>
								<input class="widefat" id="name" name="name" type="text" value="<?php echo ($systemLink->getName() === \YogSystemLink::EMPTY_NAME ? '' : esc_attr($systemLink->getName()));?>" />
							</p>
							<?php
							submit_button(__('Naam aanpassen', 'yog-plugin'));
							?>
						</form>
                        <form method="post" action="options-general.php?page=yesco_OG_synchronisation">
                            <input type="hidden" name="activation_code" value="<?php echo $activationCode;?>"/>
                            <input type="hidden" name="collection_uuid" value="<?php echo esc_attr($systemLink->getCollectionUuid());?>"/>
                            <input type="hidden" name="sync_action" value="true"/>
                            <?php
                            wp_nonce_field('yog-update-system-link-sync-' . $activationCode, '_wpnonce_sync_' . $activationCode);
                            ?>
                            <p>
                                <input type="checkbox"<?php echo (!$systemLink->isSyncEnabled() ? ' checked="checked"' : ''); ?> value="Y" name="sync_disabled" id="sync_disabled_<?php echo $systemLink->getCollectionUuid();?>">
                                <label for="sync_disabled_<?php echo $systemLink->getCollectionUuid();?>">Normale synchronisatie uitschakelen voor deze account. (Alleen gebruiken indien de synchronisatie op een andere manier gedaan wordt, zoals via WPCli)</label>
                            </p>
                            <?php
                            submit_button(__('Synchronisatie instelling aanpassen', 'yog-plugin'));
                            ?>
                        </form>
						<form method="post" action="options-general.php?page=yesco_OG_synchronisation">
							<input type="hidden" name="activation_code" value="<?php echo $activationCode;?>"/>
							<input type="hidden" name="collection_uuid" value="<?php echo esc_attr($systemLink->getCollectionUuid());?>"/>
							<?php
							wp_nonce_field('yog-update-system-link-' . $activationCode, '_wpnonce_' . $activationCode);
							?>
							<div class="notice-inline notice-warning"><p>Hier kan je de Gebruikersnaam/wachtwoord voor het uitlezen van de Yes-co 3mcp feed aanpassen. Normaal gesproken wordt deze automatisch ingesteld.<br />Vul deze velden alleen in als je in overleg met Yes-co een andere gebruikersnaam/wachtwoord gekregen hebt.</p></div>
							<p>
								<label for="username"><?php echo __('Gebruikersnaam', 'yog-plugin');?>: </label>
								<input class="widefat" id="username" name="username" type="text" value="" />
							</p>
							<p>
								<label for="password"><?php echo __('Wachtwoord', 'yog-plugin');?>: </label>
								<input class="widefat" id="password" name="password" type="password" value="" />
							</p>
							<?php
							submit_button(__('Gebruikersnaam / wachtwoord aanpassen', 'yog-plugin'));
							?>
						</form>

					</div>
					<?php
				}
			}
		}
  }
  ?>
</div>