<?php
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
  exit();

$pluginOptions = array( 'yog_plugin_version', 'yog_3mcp_version', 'yog_koppelingen',
                        'yog_huizenophome', 'yog_objectsinarchief', 'yog_javascript_dojo_dont_enqueue',
                        'yog_cat_custom', 'yog_noextratexts', 'yog_order', 'yog_dossier_mimetypes',
                        'yog-last-sync', 'yog-sync-running', 'yog_sync_disabled', 'yog_nochilds_searchresults',
                        'yog_media_size', 'yog_google_maps_api_key', 'yog_custom_areas', 'yog_custom_neighbourhoods',
												'yog_media_quality', 'yog_relation_sync', 'yog_skipped_relation_uuids', 'yog_no_delete_meta_keys',
												'yog_mijnhuiszaken_aankoopmakelaar_uuid', 'yog_mijnhuiszaken_verkoopmakelaar_uuid', 'yog_mijnhuiszaken_hypotheekadviseur_uuid', 'yog_mijnhuiszaken_uitgenodigd_door_partij',	// No longer used, but remove anyway (for systems that still have it set)
												'yog_mijnhuiszaken_api_key'
		);

foreach ($pluginOptions as $pluginOption)
{
  delete_option($pluginOption);
  delete_site_option($pluginOption);
}