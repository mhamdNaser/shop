<div class="wrap">
  <div class="icon32 icon32-config-yog"><br /></div>
  <h2>Yes-co Open Real Estate System instellingen</h2>
  <?php require_once(__DIR__ . '/parts/menu.php'); ?>
	<form method="post" action="options-general.php?page=yesco_OG_advanced">
		<?php
		wp_nonce_field('yog-advanced');
		?>
		<h3>Niet verwijderen bij synchronisatie</h3>
		<div class="notice-inline notice-info"> 
			<p>Bij de synchronisatie van een object wordt standaard alle meta data die niet meer voorkomt verwijderd. Hieronder kan je opgeven welke meta data je wilt behouden bij het bijwerken van een project.<br />
				Dit kan nodig zijn als je een extra plugin gebruikt die ook meta data bij de gesychroniseerde objecten opslaat. <u>In de meeste gevallen zal je deze instelling niet nodig hebben.</u><br />
				<br />
				Vul hieronder de meta keys die niet verwijderd moeten worden bij de synchronisatie in, 1 meta key per regel.
			</p>
		</div>
		<br />
		<textarea name="advanced[no_delete_meta_keys]" style="width:100%;height:300px;"><?php echo !empty($noDeleteMetaKeys) ? implode("\n", $noDeleteMetaKeys) : '';?></textarea>
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Wijzigingen opslaan"></p>
	</form>
</div>