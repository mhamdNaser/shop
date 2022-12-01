<div class="wrap">
  <div class="icon32 icon32-config-yog"><br /></div>
  <h2>Yes-co Open Real Estate System instellingen</h2>
  <?php require_once(__DIR__ . '/parts/menu.php'); ?>
	<br />
	<?php
	if (!empty($locations))
	{
		?>
		<form method="POST" action="options-general.php?page=yesco_OG_areas">
			<?php
			wp_nonce_field('yog-areas');

			foreach ($locations as $city => $areas)
			{
				ksort($areas);

				?>
				<h3><?php echo esc_html($city);?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Wijk</th>
							<th>Buurt</th>
							<th>Aangepaste weergave</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($areas as $areaKey => $areaOptions)
						{
							echo '<tr>';
							 echo '<td>' . esc_html($areaOptions['label']) . '</td>';
								echo '<td></td>';
								echo '<td>';
								echo '<input type="hidden" name="custom_area[' . esc_attr($areaKey) . '][city]" value="' . esc_attr($city) . '" />';
								echo '<input type="hidden" name="custom_area[' . esc_attr($areaKey) . '][org]" value="' . esc_attr($areaOptions['label']) . '" />';
								echo '<input type="text" name="custom_area[' . esc_attr($areaKey) . '][value]" value="' . (empty($areaOptions['value']) ? '' : esc_attr($areaOptions['value'])) . '" style="width:250px;" />';
								echo '</td>';
							echo '</tr>';

							if (!empty($areaOptions['neighbourhoods']))
							{
								ksort($areaOptions['neighbourhoods']);

								foreach ($areaOptions['neighbourhoods'] as $neighbourhoodKey => $neighbourhoodOptions)
								{
									echo '<tr>';
										echo '<td>' . esc_html($areaOptions['label']) . '</td>';
										echo '<td>' . esc_html($neighbourhoodOptions['label']) . '</td>';
										echo '<td>';
										echo '<input type="hidden" name="custom_neighbourhood[' . esc_attr($neighbourhoodKey) . '][city]" value="' . esc_attr($city) . '" />';
										echo '<input type="hidden" name="custom_neighbourhood[' . esc_attr($neighbourhoodKey) . '][area]" value="' . esc_attr($areaKey) . '" />';
										echo '<input type="hidden" name="custom_neighbourhood[' . esc_attr($neighbourhoodKey) . '][org]" value="' . esc_attr($neighbourhoodOptions['label']) . '" />';
										echo '<input type="text" name="custom_neighbourhood[' . esc_attr($neighbourhoodKey) . '][value]" value="' . (empty($neighbourhoodOptions['value']) ? '' : esc_attr($neighbourhoodOptions['value'])) . '" style="width:250px;" />';
										echo '</td>';
									echo '</tr>';
								}
							}
						}
						?>
					</tbody>
				</table>
				<?php
			}
			?>
			<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Wijzigingen opslaan"></p>
		</form>
		<?php
	}
	?>

</div>