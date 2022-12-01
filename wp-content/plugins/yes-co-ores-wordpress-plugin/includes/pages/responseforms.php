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
    echo '<form method="post" action="options-general.php?page=yesco_OG_responseforms" enctype="multipart/form-data">';
		wp_nonce_field('yog-response-forms');
    ?>
    <h3>Yes-co Response formulieren</h3>
    <div class="yog-setting">
      <label for="yog_response_forms_api_key">API Key</label>
      <input style="min-width:300px;" type="text" value="<?php echo (get_option('yog_response_forms_api_key') ? esc_attr(get_option('yog_response_forms_api_key')) :''); ?>" name="yog_response_forms_api_key" id="yog_response_forms_api_key" />
      <small>Te achterhalen in Yes-co App Market</small>
      <span class="msg"></span>
    </div>
    <br />
    <?php

    submit_button();
    ?>

    </form>

<?php
  }
  ?>
</div>