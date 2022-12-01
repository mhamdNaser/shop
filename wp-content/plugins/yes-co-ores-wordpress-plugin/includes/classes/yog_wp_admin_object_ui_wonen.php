<?php

/**
 * @desc YogWpAdminObjectUiWonen
 * @author Kees Brandenburg - Yes-co Nederland
 */
class YogWpAdminObjectUiWonen extends YogWpAdminObjectUiAbstract
{
    /**
     * @desc Get the post type
     *
     * @param void
     * @return string
     */
    public function getPostType()
    {
        return YOG_POST_TYPE_WONEN;
    }

    /**
     * @desc Get base name
     *
     * @param void
     * @return string
     */
    public function getBaseName()
    {
        return plugin_basename(__FILE__);
    }

    /**
     * @desc Determine columns used in overview
     *
     * @param array $columns
     * @return array
     */
    public function determineColumns($columns)
    {
        return array(
            'cb' => '<input type="checkbox" />',
            'thumbnail' => '',
            'title' => 'Object',
            'description' => 'Omschrijving',
            'address' => 'Adres',
            'dlm' => 'Laatste wijziging',
            'date' => 'Aangemaakt'
        );
    }

    /**
     * @desc Add containers to project screen
     *
     * @param void
     * @return void
     */
    public function addMetaBoxes()
    {
        add_meta_box('yog-standard-meta', 'Basis gegevens', array($this, 'renderBasicMetaBox'), YOG_POST_TYPE_WONEN, 'normal', 'low');
        add_meta_box('yog-price-meta', 'Prijs', array($this, 'renderPriceMetaBox'), YOG_POST_TYPE_WONEN, 'normal', 'low');
        add_meta_box('yog-extended-meta', 'Gegevens object', array($this, 'renderObjectDetailsMetaBox'), YOG_POST_TYPE_WONEN, 'normal', 'low');
        add_meta_box('yog-openhuis', 'Open huis', array($this, 'renderOpenHouseMetaBox'), YOG_POST_TYPE_WONEN, 'normal', 'low');
        add_meta_box('yog-movies', 'Video', array($this, 'renderMoviesMetaBox'), YOG_POST_TYPE_WONEN, 'normal', 'low');
        add_meta_box('yog-documents', 'Documenten', array($this, 'renderDocumentsMetaBox'), YOG_POST_TYPE_WONEN, 'normal', 'low');
        add_meta_box('yog-links', 'Externe koppelingen', array($this, 'renderLinksMetaBox'), YOG_POST_TYPE_WONEN, 'normal', 'low');

        // Only show parent object container when there is a parent object
        if (!empty($_REQUEST['post']) && yog_hasParentObject((int)$_REQUEST['post']))
            add_meta_box('yog-parent-meta', 'Nieuwbouw type', array($this, 'renderParentMetaBox'), $this->getPostType(), 'side', 'high');

        add_meta_box('yog-meta-sync', 'Synchronisatie', array($this, 'renderSyncMetaBox'), YOG_POST_TYPE_WONEN, 'side', 'low');
        add_meta_box('yog-location', 'Locatie', array($this, 'renderMapsMetaBox'), YOG_POST_TYPE_WONEN, 'side', 'low');
        add_meta_box('yog-relations', 'Relaties', array($this, 'renderRelationsMetaBox'), YOG_POST_TYPE_WONEN, 'side', 'low');
        add_meta_box('yog-images', 'Afbeeldingen', array($this, 'renderImagesMetaBox'), YOG_POST_TYPE_WONEN, 'side', 'low');
        add_meta_box('yog-dossier', 'Dossier items', array($this, 'renderDossierMetaBox'), YOG_POST_TYPE_WONEN, 'side', 'low');
    }

    /**
     * @desc Render basic meta box
     *
     * @param object $post
     * @return void
     */
    public function renderBasicMetaBox($post)
    {
        echo '<table class="form-table">';
        echo $this->retrieveInputs($post->ID, array('Naam', 'Straat', 'Huisnummer', 'Postcode', 'Wijk', 'Buurt', 'Plaats', 'Gemeente', 'Provincie', 'Land', 'Status', 'DatumVoorbehoudTot'));
        echo '</table>';
    }

    /**
     * @desc Render price meta box
     *
     * @param object $post
     * @return void
     */
    public function renderPriceMetaBox($post)
    {
        echo '<table class="form-table">';

        // Koop
        echo '<tr>';
        echo '<th colspan="2"><b>Koop</b></th>';
        echo '</tr>';
        echo $this->retrieveInputs($post->ID, array('KoopPrijsSoort', 'KoopPrijs', 'KoopPrijsConditie', 'KoopPrijsVervanging'));

        // Huur
        echo '<tr>';
        echo '<th colspan="2"><b>Huur</b></th>';
        echo '</tr>';
        echo $this->retrieveInputs($post->ID, array('HuurPrijs', 'HuurPrijsConditie'));

        echo '</table>';
    }

    /**
     * @desc Render object details meta box
     *
     * @param object $post
     * @return void
     */
    public function renderObjectDetailsMetaBox($post)
    {
        echo '<table class="form-table">';
        echo $this->retrieveInputs($post->ID, array('Type', 'SoortWoning', 'TypeWoning', 'KenmerkWoning', 'Bouwjaar', 'Aantalkamers', 'AantalSlaapkamers', 'Oppervlakte',
            'OppervlaktePerceel', 'Inhoud', 'Woonkamer', 'Keuken', 'KeukenVernieuwd', 'Ligging', 'GarageType', 'GarageCapaciteit',
            'TuinType', 'TuinTotaleOppervlakte', 'HoofdTuinType', 'HoofdTuinTotaleOppervlakte', 'TuinLigging', 'BergingType', 'PraktijkruimteType',
            'EnergielabelKlasse', 'Bijzonderheden'));
        echo '</table>';
    }

    /**
     * @desc Render open house meta box
     *
     * @param object $post
     * @return void
     */
    public function renderOpenHouseMetaBox($post)
    {
        $openhuisVan = yog_retrieveDateTimeSpec('OpenHuisVan', $post->ID);
        $openhuisTot = yog_retrieveDateTimeSpec('OpenHuisTot', $post->ID);

        $aanwezig = (!empty($openhuisTot) && !empty($openhuisVan));

        echo '<table class="form-table">';
            echo '<tr>';
                echo '<th scope="row">Open huis actief:</th>';
                echo '<td>';
                    echo '<input type="checkbox" ' . ($aanwezig ? 'checked' : '') . ' name="yog_openhuis_actief" id="openhuischeck" onchange="if(jQuery(\'#openhuischeck:checked\').val() !== undefined) { jQuery(\'#datumselectie\').show(); }else{ jQuery(\'#datumselectie\').hide(); }">';
                echo '</td>';
            echo '</tr>';
        echo '</table>';
        echo '<table class="form-table" id="datumselectie"' . ($aanwezig ? '' : ' style="display: none;"') . '>';
            echo '<tr>';
                echo '<th scope="row">Datum:</th>';
                echo '<td><input type="date" name="yog_openhuis_date" value="' . (empty($openhuisVan) ? '' : $openhuisVan->format('Y-m-d')) . '" class="yog-date" /></td>';
            echo '</tr>';
            echo '<tr>';
                echo '<th scope="row">Van:</th>';
                echo '<td><input type="time" name="yog_openhuis_van" value="' . (empty($openhuisVan) ? '' : $openhuisVan->format('H:i')) . '" class="yog-time" placeholder="00:00" /></td>';
            echo '</tr>';
            echo '<tr>';
                echo '<th scope="row">Tot:</th>';
                echo '<td><input type="time" name="yog_openhuis_tot" value="' . (empty($openhuisTot) ? '' : $openhuisTot->format('H:i')) . '" class="yog-time" placeholder="00:00" /></td>';
            echo '</tr>';
        echo '</table>';
    }

    /**
     * @desc Extend saving of huis post type with storing of custom fields
     *
     * @param int $postId
     * @param StdClass $post
     * @return void
     */
    public function extendSave($postId, $post)
    {
        // Check if post is of type wonen
        if ($post->post_type != YOG_POST_TYPE_WONEN)
            return $postId;

        // Verify nonce
        if (!isset($_POST['yog_nonce']) || !wp_verify_nonce($_POST['yog_nonce'], plugin_basename(__FILE__)))
            return $postId;

        // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $postId;

        // Check permissions
        if (!current_user_can('edit_page', $postId))
            return $postId;

        // Handle meta data
        $fieldsSettings = YogFieldsSettingsAbstract::create($post->post_type);

        // Handle normal fields
        foreach ($fieldsSettings->getFieldNames() as $fieldName) {
            if (empty($_POST[$fieldName]))
                delete_post_meta($postId, $fieldName);
            else
                update_post_meta($postId, $fieldName, sanitize_textarea_field($_POST[$fieldName]));
        }

        // Handle open huis
        $date = null;
        $timeFrom = null;
        $timeTill = null;
        $matches = array();

        if (!empty($_POST['yog_openhuis_actief']) && $_POST['yog_openhuis_actief'] == 'on' && !empty($_POST['yog_openhuis_date']) && !empty($_POST['yog_openhuis_van']) && !empty($_POST['yog_openhuis_tot'])) {
            $openHouseDate = sanitize_text_field($_POST['yog_openhuis_date']);
            $openHouseTimeFrom = sanitize_text_field($_POST['yog_openhuis_van']);
            $openHouseTimeTill = sanitize_text_field($_POST['yog_openhuis_tot']);

            if (preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/', $openHouseDate, $matches))
                $date = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
            else if (preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/', $openHouseDate, $matches))
                $date = sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);

            if (preg_match('/^([0-9]{1,2}):([0-9]{1,2})$/', $openHouseTimeFrom, $matches))
                $timeFrom = sprintf('%02d:%02d', $matches[1], $matches[2]);

            if (preg_match('/^([0-9]{1,2}):([0-9]{1,2})$/', $openHouseTimeTill, $matches))
                $timeTill = sprintf('%02d:%02d', $matches[1], $matches[2]);

            if (!is_null($date) && !is_null($timeFrom) && !is_null($timeTill)) {
                $timeZone = wp_timezone_string();
                if (empty($timeZone)) {
                    $timeZone = 'Europe/Amsterdam';
                }

                $from               = new DateTime($date . ' ' . $timeFrom . ':00', new DateTimeZone($timeZone));
                $till               = new DateTIme($date . ' ' . $timeTill . ':00', new DateTimeZone($timeZone));
                $currentDateTime    = new DateTime('now', new DateTimeZone($timeZone));

                update_post_meta($postId, YOG_POST_TYPE_WONEN . '_OpenHuisVan', $from->format('c'));
                update_post_meta($postId, YOG_POST_TYPE_WONEN . '_OpenHuisTot', $till->format('c'));

                if ($till > $currentDateTime) {
                    wp_set_object_terms($postId, 'open-huis', 'category', true);
                }
            }
        }

        if (is_null($date) || is_null($timeFrom) || is_null($timeTill)) {
            delete_post_meta($postId, YOG_POST_TYPE_WONEN . '_OpenHuisVan');
            delete_post_meta($postId, YOG_POST_TYPE_WONEN . '_OpenHuisTot');
        }
    }
}