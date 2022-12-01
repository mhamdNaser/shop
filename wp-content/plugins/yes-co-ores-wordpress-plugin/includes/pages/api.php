<div class="wrap">
  <div class="icon32 icon32-config-yog"><br /></div>
  <h2>Yes-co Open Real Estate System instellingen</h2>
  <?php require_once(__DIR__ . '/parts/menu.php'); ?>
  <h3>Rest API sleutels</h3>
  <div class="notice-inline notice-info">
    <p>De Rest API kan gebruikt worden om bepaalde instellingen door een extern systeem te laten beheren. <u>In de meeste gevallen zal je deze instelling niet nodig hebben.</u></p>
  </div>
  <?php
  if (!empty($errors))
  {
    ?>
    <div class="notice-inline notice-error">
      <p><?php echo implode('<br />', $errors);?></p>
    </div>
    <?php
  }
  if (!empty($apiKeys))
  {
    ?>
    <br />
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th>Naam</th>
          <th>Sleutel</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php
        foreach ($apiKeys as $apiKey)
        {
          ?>
          <tr>
            <td><?php echo esc_html($apiKey->getName());?></td>
            <td><?php echo esc_html($apiKey->getKey());?></td>
            <td>
              <a href="#TB_inline?width=600&height=150&inlineId=rest-api-delete-key-<?php echo esc_html($apiKey->getKey());?>" class="thickbox" title="<?php echo __('API sleutel verwijderen', 'yog-plugin');?>"><?php echo __('Verwijderen', 'yog-plugin');?></a>
            </td>
          </tr>
          <?php
        }
        ?>
      </tbody>
    </table>
    <?php
  }
  ?>
  <br />
  <a href="#TB_inline?width=600&height=170&inlineId=rest-api-create-key" class="thickbox button button-primary" title="<?php echo __('API sleutel toevoegen', 'yog-plugin');?>"><?php echo __('API sleutel toevoegen', 'yog-plugin');?></a>
</div>

<div id="rest-api-create-key" class="hide">
  <form method="post" action="options-general.php?page=yesco_OG_api">
    <input type="hidden" name="mode" value="create" />
    <?php
    wp_nonce_field('yog-api-create', '_wpnonce-api-create');
    ?>
    <p>
      <label for="name"><?php echo __('Naam', 'yog-plugin');?>: </label>
      <input class="widefat" id="name" name="name" type="text" value="" required />
    </p>
    <?php
    submit_button(__('API sleutel toevoegen', 'yog-plugin'));
    ?>
  </form>
</div>
<?php
if (!empty($apiKeys))
{
  foreach ($apiKeys as $apiKey)
  {
    ?>
    <div id="rest-api-delete-key-<?php echo esc_html($apiKey->getKey());?>" class="hide">
      <form method="post" action="options-general.php?page=yesco_OG_api">
        <input type="hidden" name="mode" value="delete" />
        <input type="hidden" name="key" value="<?php echo esc_html($apiKey->getKey());?>" />
        <?php
        wp_nonce_field('yog-api-delete-' . esc_html($apiKey->getKey()), '_wpnonce-api-delete-' . esc_html($apiKey->getKey()));
        ?>
        <div class="notice-inline notice-warning"><p>Weet je zeker dat je deze API key wilt verwijderen?</p></div>
        <?php
        submit_button(__('Verwijderen', 'yog-plugin'));
        ?>
      </form>
    </div>
    <?php
  }
}