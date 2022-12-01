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

  if (empty($errors))
  {
    ?>
		<form method="post" action="options-general.php?page=yesco_OG_html" >
			<?php
			wp_nonce_field('update-options');
			?>
			<h3>Open Graph</h3>
			<div class="yog-setting">
				<input type="checkbox"<?php echo (get_option('yog_show_og')?' checked':'');?> name="yog_show_og" id="yog-toggle-show-og" class="yog-toggle-setting" />
				<label for="yog-toggle-show-og">Open Graph informatie in head van pagina plaatsen bij objecten. (Als er een plugin/thema gebruikt wordt die dit voor je regelt dan dient deze instelling uit te staan!)</label><span class="msg"></span>
			</div>

			<h3>Javascript loading</h3>
			<div class="yog-setting">
				<input type="checkbox"<?php echo (get_option('yog_javascript_dojo_dont_enqueue')?' checked':'');?> name="yog_javascript_dojo_dont_enqueue" id="yog-toggle-javascript-dojo-dont-enqueue" class="yog-toggle-setting" />
				<label for="yog-toggle-javascript-dojo-dont-enqueue">Echo + defer load de Dojo Javascript library in plaats van gebruik te maken van de wp_enqueue (gebruik in het geval dat de jquery libraries conflicteren met deze plugin)</label><span class="msg"></span>
			</div>

			<br />

			<h3>HTML Style</h3>
			<div class="yog-setting">
				<label for="yog_html_style">Html Style</label>

				<?php

					$options = array();
					$options['basic'] = 'Basic (Standaard)';
					$options['bootstrap4'] = 'Bootstrap 4';

				?>
				<select name="yog_html_style" class="yog-set-setting">
				<?php

				$selectedHtmlStyle = (get_option('yog_html_style') ? get_option('yog_html_style') :'');

				foreach ($options as $value => $label)
				{
					$selected = '';

					if ($value == $selectedHtmlStyle)
						$selected = ' selected';

						?><option value="<?php echo esc_attr($value); ?>"<?php echo $selected; ?>><?php echo esc_html($label); ?></option><?php
				}

				?>
				</select>
				<span class="msg"></span>
			</div>
		</form>
		<?php
  }
  ?>
</div>