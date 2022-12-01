<?php

$page = !empty($_GET['page']) ? sanitize_text_field($_GET['page']) : null;

?>
<h2 class="nav-tab-wrapper">
    <a href="options-general.php?page=yesco_OG" class="nav-tab<?php echo ($page == 'yesco_OG' ? ' nav-tab-active' : ''); ?>">Algemeen</a>
    <a href="options-general.php?page=yesco_OG_synchronisation" class="nav-tab<?php echo ($page == 'yesco_OG_synchronisation' ? ' nav-tab-active' : ''); ?>">Synchronisatie</a>
    <a href="options-general.php?page=yesco_OG_html" class="nav-tab<?php echo ($page == 'yesco_OG_html' ? ' nav-tab-active' : ''); ?>">HTML</a>
    <a href="options-general.php?page=yesco_OG_responseforms" class="nav-tab<?php echo ($page == 'yesco_OG_responseforms' ? ' nav-tab-active' : ''); ?>">Formulieren</a>
    <a href="options-general.php?page=yesco_OG_mijnhuiszaken" class="nav-tab<?php echo ($page == 'yesco_OG_mijnhuiszaken' ? ' nav-tab-active' : ''); ?>">MijnHuiszaken</a>
    <a href="options-general.php?page=yesco_OG_googlemaps" class="nav-tab<?php echo ($page == 'yesco_OG_googlemaps' ? ' nav-tab-active' : ''); ?>">Maps</a>
    <a href="options-general.php?page=yesco_OG_areas" class="nav-tab<?php echo ($page == 'yesco_OG_areas' ? ' nav-tab-active' : ''); ?>">Wijken / buurten</a>
    <a href="options-general.php?page=yesco_OG_shortcode_map" class="nav-tab<?php echo ($page == 'yesco_OG_shortcode_map' ? ' nav-tab-active' : ''); ?>">Map shortcode</a>
    <a href="options-general.php?page=yesco_OG_shortcode_objects" class="nav-tab<?php echo ($page == 'yesco_OG_shortcode_objects' ? ' nav-tab-active' : ''); ?>">Objecten shortcode</a>
    <a href="options-general.php?page=yesco_OG_advanced" class="nav-tab<?php echo ($page == 'yesco_OG_advanced' ? ' nav-tab-active' : ''); ?>">Geavanceerd</a>
</h2>