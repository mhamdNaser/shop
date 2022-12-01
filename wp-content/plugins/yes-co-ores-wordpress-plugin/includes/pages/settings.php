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
		<form method="post" action="options-general.php?page=yesco_OG" >
			<?php
			wp_nonce_field('update-options');
			?>
			<h3>Objecten</h3>
			<div class="yog-setting">
				<input type="checkbox"<?php echo (get_option('yog_huizenophome')?' checked':'');?> name="yog_huizenophome" id="yog-toggle-home" class="yog-toggle-setting" />
				<label for="yog-toggle-home">Objecten plaatsen in blog (Objecten zullen tussen 'normale' blogposts verschijnen).</label><span class="msg"></span>
			</div>
			<div class="yog-setting">
				<input type="checkbox"<?php echo (get_option('yog_objectsinarchief')?' checked':'');?> name="yog_objectsinarchief" id="yog-toggle-archive" class="yog-toggle-setting" />
				<label for="yog-toggle-archive">Objecten plaatsen in archief (Objecten zullen tussen 'normale' blogposts verschijnen).</label><span class="msg"></span>
			</div>
			<div class="yog-setting">
				<input type="checkbox"<?php echo (get_option('yog_nochilds_searchresults')?' checked':'');?> name="yog_nochilds_searchresults" id="yog-toggle-nochilds-searchresults" class="yog-toggle-setting" />
				<label for="yog-toggle-nochilds-searchresults">Individuele objecten gekoppeld aan een NBty/BBty <u>niet</u> tonen in overzichten.</label><span class="msg"></span>
			</div>
			
			<h3>Zoeken</h3>
			<div class="yog-setting">
				BOG huurprijs zoeken a.d.h.v.
				<select name="yog_search_bog_rentalprice_type" id="yog_search_bog_rentalprice_type" class="yog-set-setting">
				<?php
				foreach ($bogRentalpriceSearchOptions as $key => $title)
				{
					echo '<option value="' . esc_attr($key) . '"' . ($bogRentalpriceSearchOption == $key ? ' selected="selected"' : '') . '>' . esc_html($title) . '</option>';
				}
				?>
				</select><span class="msg"></span>
			</div>

			<div id="yog-sortoptions" style="display:<?php echo(get_option('yog_cat_custom') ? 'block':'none');?>">
				<h3>Sortering</h3>
				<div class="yog-setting">
					Objecten in Yes-co ORES categorie&euml;n standaard sorteren op:
					<select name="yog_order" id="yog_order" class="yog-set-setting">
					<?php
					foreach ($sortOptions as $key => $title)
					{
					  echo '<option value="' . esc_attr($key) . '"' . ($sortOption == $key ? ' selected="selected"' : '') . '>' . esc_html($title) . '</option>';
					}
					?>
					</select><span class="msg"></span>
				</div>
			</div>
		</form>
<?php
  }
  ?>
</div>