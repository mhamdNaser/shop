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
    ?>
		<form method="post" action="options-general.php?page=yesco_OG_mijnhuiszaken" enctype="multipart/form-data">
		<?php
		wp_nonce_field('yog-mijn-huiszaken');
    ?>
    <h3>Yes-co MijnHuiszaken</h3>
		<div class="notice-inline notice-info">
			<p>Meer informatie over MijnHuiszaken kun je vinden op <a target="_blank" href="https://aansluiten.mijnhuiszaken.nl">https://aansluiten.mijnhuiszaken.nl</a></p>
		</div>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="yog_mijnhuiszaken_api_key">Publieke API key:</label>
					</th>
					<td>
						<input style="min-width:340px;" type="text" value="<?php echo (get_option('yog_mijnhuiszaken_api_key') ? esc_attr(get_option('yog_mijnhuiszaken_api_key')) :''); ?>" name="yog_mijnhuiszaken_api_key" id="yog_mijnhuiszaken_api_key" />
						<small>Te achterhalen in je MijnHuiszaken beheer omgeving</small>
					</td>
				</tr>
			</tbody>
		</table>
    <?php
    submit_button();
    ?>
		</form>
		<p>Na het instellen van je Publieke API key zijn de volgende shortcodes te gebruiken:</p>
    <ul>
      <li>
				<b>[yog-mhz-get-url service="registreer"]</b><br />
				bijvoorbeeld door deze in een link te gebruiken: <?php echo esc_html('<a class="btn btn-primary" href="[yog-mhz-get-url service="registreer"]">Maak een MijnHuiszaken account aan</a>'); ?></li>
			<li>
				<b>[yog-mhz-get-url service="login"]</b><br />
				bijvoorbeeld door deze in een link te gebruiken: <?php echo esc_html('<a class="btn btn-primary" href="[yog-mhz-get-url service="login"]">Login in je MijnHuiszaken account</a>'); ?></li>
    </ul>
		<p>Ook kan je, indien je dit geactiveerd hebt in je MijnHuiszaken beheer omgeving, gebruik maken van de MijnHuiszaken widgets:</p>
		<ul>
			<li>
				<b>Favorieten</b><br />
				Deze widget dient door je gebruikte thema ondersteund te worden.<br />In je thema kan bij woningen het volgende stukje php code gebruikt worden voor de MijnHuiszaken favorieten widget:<br />
				&lt;?php echo yog_generateMhzFavoritsWidget(); ?&gt;
			</li>
		</ul>

<?php
  }
  ?>
</div>