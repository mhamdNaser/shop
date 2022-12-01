<?php
require_once(YOG_PLUGIN_DIR . '/includes/config/config.php');
require_once(YOG_PLUGIN_DIR . '/includes/classes/yog_fields_settings.php');

/**
 * @desc YogObjectSearchManager
 * @author Kees Brandenburg - Yes-co Nederland
 */
class YogObjectSearchManager
{
    static public $instance;
    private $db;
    private $searchExtended = false;

    /**
     * @desc Constructor
     *
     * @param void
     * @return YogObjectWonenManager
     */
    private function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
    }

    /**
     * @desc Get the instance of the YogObjectSearch
     *
     * @param void
     * @return YogObjectSearch
     */
    static public function getInstance()
    {
        if (is_null(self::$instance))
            self::$instance = new self();

        return self::$instance;
    }

    /**
     * @desc Extend the wordpress search with object functionality
     *
     * @param void
     * @return void
     */
    public function extendSearch()
    {
        // Make sure the search is only extended once
        if ($this->searchExtended === false) {
            add_filter('pre_get_posts', array($this, 'limitDefaultSearchPostTypes'));
            add_action('posts_where_request', array($this, 'extendSearchWhere'));
            add_action('posts_orderby_request', array($this, 'changePostSortOrder'));

            $this->searchExtended = true;
        }
    }

    public function limitDefaultSearchPostTypes($query)
    {
        if (is_search() && !empty($_REQUEST['object']) && in_array($_REQUEST['object'], yog_getAllObjectPostTypes()) && isset($query->query) && isset($query->query['s'])) {
            $query->set('post_type', [sanitize_text_field($_REQUEST['object'])]);
        }
    }

    /**
     * Adjust sort order for search widget
     *
     * @param string $order
     * @return string
     */
    public function changePostSortOrder($order)
    {
        if (is_search() && !empty($_REQUEST['order'])) {
            $requestOrder = sanitize_text_field($_REQUEST['order']);

            switch ($requestOrder) {
                case 'date_asc':
                    $order = 'post_date ASC';
                    break;
                case 'date_desc':
                    $order = 'post_date DESC';
                    break;
                case 'title_asc':
                    $order = 'post_title ASC';
                    break;
                case 'title_desc':
                    $order = 'post_title DESC';
                    break;
                case 'city_asc':
                    $order = 'CAST((SELECT meta_value FROM ' . $this->db->postmeta . ' WHERE post_id=' . $this->db->posts . '.ID AND meta_key=\'yog_city_street_order\') AS CHAR(100)) ASC';
                    break;
                case 'city_desc';
                    $order = 'CAST((SELECT meta_value FROM ' . $this->db->postmeta . ' WHERE post_id=' . $this->db->posts . '.ID AND meta_key=\'yog_city_street_order\') AS CHAR(100)) DESC';
                    break;
                case 'price_asc':
                    $order = 'CAST((SELECT meta_value FROM ' . $this->db->postmeta . ' WHERE post_id=' . $this->db->posts . '.ID AND meta_key=\'yog_price_order\') AS SIGNED) ASC';
                    break;
                case 'price_desc';
                    $order = 'CAST((SELECT meta_value FROM ' . $this->db->postmeta . ' WHERE post_id=' . $this->db->posts . '.ID AND meta_key=\'yog_price_order\') AS SIGNED) DESC';
                    break;
                case 'bog_surface_asc':
                    $order = 'CAST((SELECT meta_value FROM ' . $this->db->postmeta . ' WHERE post_id=' . $this->db->posts . '.ID AND meta_key=\'bedrijf_Oppervlakte\') AS SIGNED) ASC';
                    break;
                case 'bog_surface_desc':
                    $order = 'CAST((SELECT meta_value FROM ' . $this->db->postmeta . ' WHERE post_id=' . $this->db->posts . '.ID AND meta_key=\'bedrijf_Oppervlakte\') AS SIGNED) DESC';
                    break;
            }
        }

        return $order;
    }

    /**
     * @desc Extend the where to also search on the object custom fields, should not be called manually
     *
     * @param string $where
     * @return string
     */
    public function extendSearchWhere($where)
    {
        if (is_search()) {
            if (!empty($_REQUEST['object_type']) && in_array($_REQUEST['object_type'], array(YOG_POST_TYPE_WONEN, YOG_POST_TYPE_BOG, YOG_POST_TYPE_NBPR, YOG_POST_TYPE_NBTY, YOG_POST_TYPE_BBPR, YOG_POST_TYPE_BBTY, YOG_POST_TYPE_BOPR)))
                $where = $this->extendSearchWhereSearchWidget($where);
            else
                $where = $this->extendSearchWhereDefault($where);
        } else if (is_category() || is_archive()) {
            $where = $this->extendCategoryWhere($where);
        }

        return $where;
    }

    /**
     * Extend where query for showing category
     *
     * @param string $where
     * @return string
     */
    private function extendCategoryWhere($where)
    {
        $yogNochildsSearchresults = get_option('yog_nochilds_searchresults');

        if (!empty($yogNochildsSearchresults))
            $where .= ' AND (post_type != \'' . YOG_POST_TYPE_WONEN . '\' OR post_parent = 0)';

        return $where;
    }

    /**
     * @desc Extend normal search queries
     *
     * @param string $where
     * @return string
     */
    private function extendSearchWhereDefault($where)
    {
        global $wp;

        $objectType = empty($_REQUEST['object']) ? 'all' : sanitize_text_field($_REQUEST['object']);
        $searchTerm = $wp->query_vars['s'];
        $postTbl = $this->db->posts;
        $addressMode = false;

        // Use address as search term if set and search term not
        if (empty($searchTerm) && !empty($_REQUEST['adres'])) {
            $searchTerm = sanitize_text_field($_REQUEST['adres']);
            $addressMode = true;
        }

        // Check if search field is filled
        if (empty($searchTerm) || $searchTerm == '%25' || $searchTerm == '%') {
            if (!empty($_REQUEST['collection'])) {
                $metaKeys = array();
                $collectionUuid = sanitize_text_field($_REQUEST['collection']);

                foreach (yog_getAllObjectPostTypes() as $postType) {
                    $metaKeys[] = $this->escape($postType . '_' . $collectionUuid . '_uuid');
                }

                $where .= ' AND EXISTS (';
                $where .= 'SELECT true FROM ' . $this->db->postmeta . ' ';
                $where .= 'WHERE ' . $this->db->postmeta . '.post_id=' . $this->db->posts . '.ID ';
                $where .= 'AND meta_key IN (\'' . implode('\',\'', $metaKeys) . '\')';
                $where .= ')';
            }

            return $where;
        }

        // Escape search terms
        $searchTerm = $this->escape($searchTerm);
        $objectType = $this->escape($objectType);

        // Determine supported fields
        $supportedMetaFields = array();
        if (in_array($objectType, array(YOG_POST_TYPE_WONEN, 'all')))
            $supportedMetaFields = array_merge($supportedMetaFields, array('huis_Wijk', 'huis_WijkCustom', 'huis_Buurt', 'huis_BuurtCustom', 'huis_Land', 'huis_Provincie', 'huis_Gemeente', 'huis_Plaats', 'huis_PlaatsCustom', 'huis_Straat', 'huis_Huisnummer', 'huis_Postcode', 'huis_SoortWoning', 'huis_TypeWoning', 'huis_KenmerkWoning', 'huis_tags'));
        if (in_array($objectType, array(YOG_POST_TYPE_BOG, 'all')))
            $supportedMetaFields = array_merge($supportedMetaFields, array('bedrijf_Wijk', 'bedrijf_WijkCustom', 'bedrijf_Buurt', 'bedrijf_BuurtCustom', 'bedrijf_Land', 'bedrijf_Provincie', 'bedrijf_Gemeente', 'bedrijf_Plaats', 'bedrijf_PlaatsCustom', 'bedrijf_Straat', 'bedrijf_Huisnummer', 'bedrijf_Postcode', 'bedrijf_Type', 'bedrijf_tags'));
        if (in_array($objectType, array(YOG_POST_TYPE_NBPR, 'all')))
            $supportedMetaFields = array_merge($supportedMetaFields, array('yog-nbpr_Wijk', 'yog-nbpr_WijkCustom', 'yog-nbpr_Buurt', 'yog-nbpr_BuurtCustom', 'yog-nbpr_Land', 'yog-nbpr_Provincie', 'yog-nbpr_Gemeente', 'yog-nbpr_Plaats', 'yog-nbpr_PlaatsCustom', 'yog-nbpr_Straat', 'yog-nbpr_Huisnummer', 'yog-nbpr_Postcode', 'yog-nbpr_ProjectSoort', 'yog-nbpr_tags'));
        if (in_array($objectType, array(YOG_POST_TYPE_BBPR, 'all')))
            $supportedMetaFields = array_merge($supportedMetaFields, array('yog-bbpr_Wijk', 'yog-bbpr_WijkCustom', 'yog-bbpr_Buurt', 'yog-bbpr_BuurtCustom', 'yog-bbpr_Land', 'yog-bbpr_Provincie', 'yog-bbpr_Gemeente', 'yog-bbpr_Plaats', 'yog-bbpr_PlaatsCustom', 'yog-bbpr_Postcode', 'yog-bbpr_tags'));

        $metaTbl = $this->db->postmeta;

        $whereQuery = array();

        foreach ($supportedMetaFields as $metaField) {
            $whereQuery[] = "meta_key = '" . $metaField . "' AND meta_value LIKE '%" . $searchTerm . "%'";
        }

        // Extent with tags
        $tagsQuery = '';

        $query = "SELECT DISTINCT post_id FROM " . $metaTbl . " WHERE (" . implode(') OR (', $whereQuery) . ')';

        // Retrieve post ids
        $postIds = $this->db->get_col($query, 0);

        if (is_array($postIds) && count($postIds)) {
            $matches = array();
            $idPart = $postTbl . ".ID IN (" . implode(',', $postIds) . ")";

            // If address mode is set, no search on title is done so just add to query
            if ($addressMode === true) {
                $where .= ' AND ' . $idPart;
            } // Otherwise, add to original where (part of title like)
            else if (preg_match_all("/\(\s*" . $this->db->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\) OR /", $where, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $where = str_replace($match[0], $idPart . ' OR ' . $match[0], $where);
                }
            }
        }

        return $where;
    }

    /**
     * @desc Extend search for widgets
     *
     * @param string $where
     * @return string
     */
    private function extendSearchWhereSearchWidget($where, $returnArray = false)
    {
        $request = array_map('stripslashes_deep', $_REQUEST);
        $objectType = sanitize_text_field($request['object_type']);
        $fieldsSettings = YogFieldsSettingsAbstract::create($objectType);
        $tbl = $this->db->postmeta;

        $query = array();
        $query[] = $this->db->posts . ".post_type = '" . $this->escape($objectType) . "'";
        $priceQuery = array();
        $priceAndOr = (!empty($request['PriceAndOr']) && $request['PriceAndOr'] === 'OR') ? 'OR' : 'AND';

        // Determine parts of query for custom fields
        foreach ($fieldsSettings->getFields() as $metaKey => $options) {
            $requestKey = str_replace($objectType . '_', '', $metaKey);

            if (!empty($options['search'])) {
                $selectSql = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($metaKey) . "' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";
                $searchSql = null;

                switch ($options['search']) {
                    // Exact search
                    case 'exact':
                        if (!empty($request[$requestKey])) {
                            // Sanitize input
                            $values = $this->sanitizeInputToArray($request[$requestKey]);

                            // Create queries
                            if (!empty($options['replacementMeta']))
                                $searchSql = "((" . $selectSql . ") IN ('" . implode("', '", $values) . "') OR (" . str_replace($metaKey, $options['replacementMeta'], $selectSql) . ") IN ('" . implode("', '", $values) . "'))";
                            else
                                $searchSql = "(" . $selectSql . ") IN ('" . implode("', '", $values) . "')";
                        }
                        break;
                    // Exact search on parent
                    case 'parent-exact':
                        if (!empty($options['parentKey'])) {
                            // Sanitize input
                            $values = array();
                            if (!is_array($request[$requestKey])) {
                                $values = array($this->escape(sanitize_text_field($request[$requestKey])));
                            } else {
                                foreach ($request[$requestKey] as $key => $value) {
                                    $values[$key] = $this->escape(sanitize_text_field($value));
                                }
                            }

                            // Create queries
                            $metaKey = $options['parentKey'];
                            $selectSql = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($metaKey) . "' AND " . $tbl . ".post_id = " . $this->db->posts . ".post_parent";

                            $sql = $this->db->posts . '.post_parent IS NOT NULL AND ';
                            $sql .= $this->db->posts . '.post_parent > 0 AND ';
                            $sql .= "(" . $selectSql . ") IN ('" . implode("', '", $values) . "')";

                            $searchSql = '(' . $sql . ')';
                        }
                        break;
                    // Range search
                    case 'range':
                        $min = empty($request[$requestKey . '_min']) ? 0 : (int)str_replace('.', '', $request[$requestKey . '_min']);
                        $max = empty($request[$requestKey . '_max']) ? 0 : (int)str_replace('.', '', $request[$requestKey . '_max']);

                        if ($min > 0 && $max > 0) {
                            $searchSql = "((" . $selectSql . ") BETWEEN " . $min . " AND " . $max . ")";
                        } else if ($min > 0 && $max == 0) {
                            $searchSql = "((" . $selectSql . ") >= " . $min . ")";
                        } else if ($min == 0 && $max > 0) {
                            $searchSql = "((" . $selectSql . ") <= " . $max;

                            if ($priceAndOr !== 'OR')
                                $searchSql .= ' OR (' . $selectSql . ') IS NULL';

                            $searchSql .= ')';
                        }

                        break;
                    // Search on BOG rental price range (normal value, but also min/max rental price)
                    case 'bog-rental-price-range':

                        $priceSearchType = get_option('yog_search_bog_rentalprice_type', '');
                        $multiplyPrice = 1;

                        switch ($priceSearchType) {
                            case 'pm':
                            case 'pj':
                                $selectSql = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = 'bedrijf_HuurPrijsPerJaar' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";
                                $selectMinSql = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = 'bedrijf_HuurPrijsMinPerJaar' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";
                                $selectMaxSql = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = 'bedrijf_HuurPrijsMaxPerJaar' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";

                                if ($priceSearchType === 'pm')
                                    $multiplyPrice = 12;
                                break;
                            default:
                                $selectMinSql = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($metaKey) . "Min' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";
                                $selectMaxSql = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($metaKey) . "Max' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";
                                break;
                        }

                        $min = empty($request[$requestKey . '_min']) ? 0 : (int)str_replace('.', '', $request[$requestKey . '_min']) * $multiplyPrice;
                        $max = empty($request[$requestKey . '_max']) ? 0 : (int)str_replace('.', '', $request[$requestKey . '_max']) * $multiplyPrice;

                        if ($min > 0 && $max > 0)
                            $searchSql = "(((" . $selectSql . ") BETWEEN " . $min . " AND " . $max . ") OR ((" . $selectMinSql . ") BETWEEN " . $min . " AND " . $max . ") OR ((" . $selectMaxSql . ") BETWEEN " . $min . " AND " . $max . "))";
                        else if ($min > 0 && $max == 0)
                            $searchSql = "((" . $selectSql . ") >= " . $min . " OR (" . $min . " BETWEEN (" . $selectMinSql . ") AND (" . $selectMaxSql . ")) OR ((" . $selectMinSql . ") <= " . $min . " AND (" . $selectMaxSql . ") IS NULL))";
                        else if ($min == 0 && $max > 0)
                            $searchSql = "((" . $selectSql . ") <= " . $max . " OR (" . $max . " BETWEEN (" . $selectMinSql . ") AND (" . $selectMaxSql . ")) OR ((" . $selectMaxSql . ") >= " . $max . " AND (" . $selectMinSql . ") IS NULL))";

                        break;
                    // Range search on Min / Max fields
                    case 'minmax-range':
                        $requestKey = str_replace(array('Min', 'Max'), '', $requestKey);

                        $min = empty($request[$requestKey . '_min']) ? 0 : (int)str_replace('.', '', $request[$requestKey . '_min']);
                        $max = empty($request[$requestKey . '_max']) ? 0 : (int)str_replace('.', '', $request[$requestKey . '_max']);

                        $metaKey = str_replace(array('Min', 'Max'), '', $metaKey);
                        $minField = $metaKey . 'Min';
                        $maxField = $metaKey . 'Max';

                        $sqlMin = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($minField) . "' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";
                        $sqlMax = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($maxField) . "' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";

                        if ($min > 0 && $max > 0)
                            $searchSql = "(((" . $sqlMin . ") BETWEEN " . $min . " AND " . $max . ") OR ((" . $sqlMax . ") BETWEEN " . $min . " AND " . $max . "))";
                        else if ($min > 0)
                            $searchSql = "(" . $min . " <= (" . $sqlMax . "))";
                        else if ($max > 0)
                            $searchSql = "(" . $max . " >= (" . $sqlMax . "))";

                        break;
                    case 'energielabel':
                        if (!empty($request[$requestKey])) {
                            $values = array_map('strtoupper', $this->sanitizeInputToArray($request[$requestKey]));

                            // If searched on A, also search on A+ / A++ (etc)
                            if (in_array('A', $values)) {
                                $values = array_merge($values, ['A+', 'A++', 'A+++', 'A++++', 'A+++++']);
                            }

                            $values = array_filter(array_unique($values));

                            $searchSql = "(SELECT UPPER(" . $tbl . ".meta_value) FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($metaKey) . "' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID) IN ('" . implode("', '", $values) . "')";
                        }
                        break;
                }

                // Add search sql to query
                if (!empty($searchSql)) {
                    if (!empty($options['type']) && in_array($options['type'], array('priceBtw', 'price')))
                        $priceQuery[] = $searchSql;
                    else
                        $query[] = $searchSql;
                }
            }
        }

        // Handle price type search
        if (!empty($request['PrijsType'])) {
            if (!is_array($request['PrijsType']))
                $request['PrijsType'] = array($request['PrijsType']);

            $metaKeys = array();

            if (in_array($objectType, array(YOG_POST_TYPE_NBPR, YOG_POST_TYPE_NBTY, YOG_POST_TYPE_BBPR))) {
                if (in_array('Koop', $request['PrijsType'])) {
                    $metaKeys[] = $objectType . '_' . ($objectType == YOG_POST_TYPE_NBPR ? 'KoopAanneemSomMin' : 'KoopPrijsMin');
                    $metaKeys[] = $objectType . '_' . ($objectType == YOG_POST_TYPE_NBPR ? 'KoopAanneemSomMax' : 'KoopPrijsMax');
                }

                if (in_array('Huur', $request['PrijsType'])) {
                    $metaKeys[] = $objectType . '_HuurPrijsMin';
                    $metaKeys[] = $objectType . '_HuurPrijsMax';
                }
            } else if (in_array($objectType, array(YOG_POST_TYPE_BOG))) {
                if (in_array('Koop', $request['PrijsType']))
                    $metaKeys[] = $objectType . '_KoopPrijs';

                if (in_array('Huur', $request['PrijsType'])) {
                    $metaKeys[] = $objectType . '_HuurPrijs';
                    $metaKeys[] = $objectType . '_HuurPrijsMin';
                    $metaKeys[] = $objectType . '_HuurPrijsMax';
                }
            } else {
                if (in_array('Koop', $request['PrijsType']))
                    $metaKeys[] = $objectType . '_KoopPrijs';

                if (in_array('Huur', $request['PrijsType']))
                    $metaKeys[] = $objectType . '_HuurPrijs';
            }

            if (count($metaKeys) > 0) {
                $queryParts = array();

                foreach ($metaKeys as $metaKey) {
                    $queryParts[] = 'EXISTS (SELECT true FROM ' . $tbl . ' WHERE ' . $tbl . '.meta_key = \'' . $this->escape($metaKey) . '\' AND ' . $tbl . '.meta_value IS NOT NULL AND ' . $tbl . '.meta_value != \'\' AND ' . $tbl . '.post_id = ' . $this->db->posts . '.ID)';
                }

                $query[] = '(' . implode(' OR ', $queryParts) . ')';
            }
        }

        // Handle price condition search (for BOG)
        if (!empty($request['PrijsConditie']) && is_array($request['PrijsConditie']) && $objectType == YOG_POST_TYPE_BOG) {
            $queryParts = array();
            $buyConditions = array_intersect($request['PrijsConditie'], array('k.k.', 'v.o.n.'));
            $rentConditions = array_intersect($request['PrijsConditie'], array('p.m.', 'p.j.', 'per vierkante meter p.j.'));

            if (count($buyConditions) > 0)
                $queryParts[] = "(SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($objectType . '_KoopPrijsConditie') . "' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID) IN ('" . $this->db->_real_escape(implode("', '", $buyConditions)) . "')";

            if (count($rentConditions) > 0)
                $queryParts[] = "(SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($objectType . '_HuurPrijsConditie') . "' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID) IN ('" . $this->db->_real_escape(implode("', '", $rentConditions)) . "')";

            if (count($queryParts) > 0)
                $query[] = '(' . implode(' OR ', $queryParts) . ')';
        }

        // Handle price search (koop + huur)
        if (!empty($request['Prijs_min']) || !empty($request['Prijs_max'])) {
            $min = empty($request['Prijs_min']) ? 0 : (int)str_replace('.', '', $request['Prijs_min']);
            $max = empty($request['Prijs_max']) ? 0 : (int)str_replace('.', '', $request['Prijs_max']);

            if (in_array($objectType, array(YOG_POST_TYPE_NBPR, YOG_POST_TYPE_NBTY, YOG_POST_TYPE_BBPR))) {
                $koopMinField = ($objectType == YOG_POST_TYPE_NBPR) ? 'KoopAanneemSomMin' : 'KoopPrijsMin';
                $koopMaxField = ($objectType == YOG_POST_TYPE_NBPR) ? 'KoopAanneemSomMax' : 'KoopPrijsMax';
                $huurMinField = 'HuurPrijsMin';
                $huurMaxField = 'HuurPrijsMax';

                $koopSqlMin = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($objectType . "_" . $koopMinField) . "' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";
                $koopSqlMax = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($objectType . "_" . $koopMaxField) . "' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";
                $huurSqlMin = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($objectType . "_" . $huurMinField) . "' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";
                $huurSqlMax = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($objectType . "_" . $huurMaxField) . "' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";

                $sql = '(';
                $sql .= '((' . $koopSqlMin . ') BETWEEN ' . $min . ' AND ' . $max . ')';
                $sql .= ' OR ';
                $sql .= '((' . $koopSqlMax . ') BETWEEN ' . $min . ' AND ' . $max . ')';
                $sql .= ')';
                $sql .= ' OR ';
                $sql .= '(';
                $sql .= '((' . $huurSqlMin . ') BETWEEN ' . $min . ' AND ' . $max . ')';
                $sql .= ' OR ';
                $sql .= '((' . $huurSqlMax . ') BETWEEN ' . $min . ' AND ' . $max . ')';
                $sql .= ')';

                if (!empty($sql))
                    $query[] = '(' . $sql . ')';
            } else {
                $koopSql = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($objectType . '_KoopPrijs') . "' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";
                $huurSql = "SELECT " . $tbl . ".meta_value FROM " . $tbl . " WHERE " . $tbl . ".meta_key = '" . $this->escape($objectType . '_HuurPrijs') . "' AND " . $tbl . ".post_id = " . $this->db->posts . ".ID";

                if ($min > 0 && $max > 0)
                    $query[] = "(((" . $koopSql . ") BETWEEN " . $min . " AND " . $max . ") OR ((" . $huurSql . ") BETWEEN " . $min . " AND " . $max . "))";
                else if ($min > 0 && $max == 0)
                    $query[] = "((" . $koopSql . ") >= " . $min . " OR (" . $huurSql . ") >= " . $min . ")";
                else if ($min == 0 && $max > 0)
                    $query[] = "((" . $koopSql . ") <= " . $max . " OR (" . $huurSql . ") <= " . $max . " OR ((" . $koopSql . ") IS NULL AND (" . $huurSql . ") IS NULL))";
            }
        }

        // Handle address search (free text)
        if (!empty($request['Adres'])) {
            $metaKeys = array('Wijk', 'WijkCustom', 'Buurt', 'BuurtCustom', 'Provincie', 'Gemeente', 'Plaats', 'PlaatsCustom', 'Straat', 'Huisnummer', 'Postcode');
            $queryParts = array();
            $addressValue = str_replace(array('%'), '', sanitize_text_field($request['Adres']));

            $queryParts[] = '(' . $this->db->posts . '.post_title LIKE \'%' . $this->escape($addressValue) . '%\')';

            foreach ($metaKeys as $metaKey) {
                $queryParts[] = 'EXISTS (SELECT true FROM ' . $tbl . ' WHERE ' . $tbl . '.meta_key = \'' . $this->escape($objectType . '_' . $metaKey) . '\' AND ' . $tbl . '.post_id = ' . $this->db->posts . '.ID AND ' . $tbl . '.meta_value LIKE \'%' . $this->escape($addressValue) . '%\')';
            }

            $query[] = '(' . implode(' OR ', $queryParts) . ')';
        }

        // Filter NBvk/NBvh/BBvk/BBvh objects with a parent
        $yogNochildsSearchresults = get_option('yog_nochilds_searchresults');
        if ($objectType === YOG_POST_TYPE_WONEN && !empty($yogNochildsSearchresults))
            $query[] = 'post_parent = 0';

        if ($returnArray === true)
            return $query;

        // Update where query
        if (!empty($query))
            $where .= ' AND ' . implode(' AND ', $query);

        if (!empty($priceQuery)) {
            if ($priceAndOr === 'OR' && count($priceQuery) > 1)
                $where .= ' AND (' . implode(' OR ', $priceQuery) . ')';
            else
                $where .= ' AND ' . implode(' AND ', $priceQuery);
        }

        return $where;
    }

    /**
     * Sanitize input and return it as an array
     *
     * @param $input
     * @return array|array[]
     */
    private function sanitizeInputToArray($input)
    {
        $values = [];

        if (!is_array($input)) {
            $values = array($this->escape(sanitize_text_field($input)));
        } else {
            foreach ($input as $key => $value) {
                $values[$key] = $this->escape(sanitize_text_field($value));
            }
        }

        return $values;
    }

    /**
     * @desc Retrieve the lowest available price for a specific meta field
     *
     * @param mixed $metaKeys (string or array)
     * @param $params (optional, default array)
     * @return mixed
     */
    public function retrieveMinMetaValue($metaKeys, $params = array(), $extendWithRequest = false)
    {
        if (!is_array($metaKeys))
            $metaKeys = array($metaKeys);

        $postType = substr($metaKeys[0], 0, strpos($metaKeys[0], '_'));

        // Determine where parts
        $where = array();
        $where[] = $this->db->posts . ".post_type = '" . $this->escape($postType) . "'";
        $where = array_merge($where, $this->determineGlobalMetaWhere($params, false, $extendWithRequest));

        $sql = "SELECT DISTINCT (";
        $sql .= "SELECT MIN(CAST(meta_value  AS UNSIGNED INTEGER)) FROM " . $this->db->postmeta . " WHERE ";
        $sql .= "meta_key IN ('" . implode("', '", $this->escape($metaKeys)) . "') AND ";
        $sql .= $this->db->postmeta . ".post_id = " . $this->db->posts . ".ID";
        $sql .= ") AS value FROM " . $this->db->posts;
        $sql .= " WHERE " . implode(' AND ', $where);

        $results = $this->db->get_results($sql);

        $min = null;
        foreach ($results as $result) {
            if (empty($result->value)) {
                $min = 0;
                break;
            } else if (is_null($min) || (int)$result->value < $min) {
                $min = (int)$result->value;
            }
        }

        return $min;
    }

    /**
     * @desc Retrieve the highest available value for a specific meta field
     *
     * @param mixed $metaKeys (string or array)
     * @param $params (optional, default array)
     * @return mixed
     */
    public function retrieveMaxMetaValue($metaKeys, $params = array())
    {
        if (!is_array($metaKeys))
            $metaKeys = array($metaKeys);

        // Determine where parts
        $where = array();
        $where[] = $this->db->postmeta . ".meta_key IN ('" . implode("', '", $this->escape($metaKeys)) . "')";
        $where[] = $this->db->postmeta . ".meta_value != ''";
        $where = array_merge($where, $this->determineGlobalMetaWhere($params));

        $sql = "SELECT " . $this->db->postmeta . ".meta_value FROM " . $this->db->postmeta . " WHERE ";
        $sql .= implode(' AND ', $where) . ' ';
        $sql .= "ORDER BY CAST(meta_value AS UNSIGNED INTEGER) DESC LIMIT 1";

        return (int)$this->db->get_var($sql);
    }

    /**
     * @desc Retrieve all available values for a specfic meta field
     *
     * @param string $metaKey
     * @param $params (optional, default array)
     * @return array
     */
    public function retrieveMetaList($metaKey, $params = array())
    {
        $values = array();

        if (strpos($metaKey, '_Wijk') !== false || strpos($metaKey, '_Buurt') !== false) {
            // Determine where parts
            $where = array();
            $where[] = $this->db->postmeta . ".meta_key = '" . $this->escape($metaKey) . "'";
            $where[] = $this->db->postmeta . ".meta_value != ''";
            $where = array_merge($where, $this->determineGlobalMetaWhere($params));

            // Create query to retrieve values
            $sql = 'SELECT DISTINCT ';
            $sql .= '(SELECT meta_value FROM ' . $this->db->postmeta . ' AS customMeta WHERE customMeta.post_id = ' . $this->db->postmeta . '.post_id AND meta_key = \'' . $this->escape($metaKey) . 'Custom\') AS custom,';
            $sql .= $this->db->postmeta . ".meta_value FROM " . $this->db->postmeta . " WHERE ";
            $sql .= implode(' AND ', $where) . ' ';
            $sql .= "ORDER BY meta_value";

            $results = $this->db->get_results($sql);

            foreach ($results as $result) {
                if (!empty($result->custom))
                    $values[] = $result->custom;
                else
                    $values[] = $result->meta_value;
            }

            $values = array_unique($values);

            sort($values);
        } else {
            // Determine where parts
            $where = array();
            $where[] = $this->db->postmeta . ".meta_key = '" . $this->escape($metaKey) . "'";
            $where[] = $this->db->postmeta . ".meta_value != ''";
            $where = array_merge($where, $this->determineGlobalMetaWhere($params));

            $sql = "SELECT DISTINCT " . $this->db->postmeta . ".meta_value FROM " . $this->db->postmeta . " WHERE ";
            $sql .= implode(' AND ', $where) . ' ';
            $sql .= "ORDER BY meta_value";

            $results = $this->db->get_results($sql);

            foreach ($results as $result) {
                $values[] = $result->meta_value;
            }
        }

        return $values;
    }

    /**
     * @desc Determine global where for meta selection
     *
     * @param array $params
     * @param bool $relativeToMeta (optional, default true)
     * @return array
     */
    private function determineGlobalMetaWhere($params, $relativeToMeta = true)
    {
        $where = array();
        $postIdField = $relativeToMeta ? $this->db->postmeta . '.post_id' : $this->db->posts . '.ID';

        // Category based
        if (!empty($params['cat']))
            $where[] = $postIdField . " IN (SELECT " . $this->db->term_relationships . ".object_id FROM " . $this->db->term_relationships . " WHERE " . $this->db->term_relationships . ".term_taxonomy_id = " . (int)$params['cat'] . ")";

        // Yog objects category based
        if (!empty($params['objecten'])) {
            $taxSql = 'SELECT ' . $this->db->term_relationships . '.object_id FROM ' . $this->db->term_relationships;
            $taxSql .= ' WHERE ' . $this->db->term_relationships . '.term_taxonomy_id = (';
            $taxSql .= 'SELECT term_taxonomy_id FROM ' . $this->db->term_taxonomy;
            $taxSql .= ' WHERE taxonomy=\'yog_category\' AND EXISTS (';
            $taxSql .= 'SELECT true FROM ' . $this->db->terms . ' WHERE ' . $this->db->terms . '.slug=\'' . $this->escape($params['objecten']) . '\' AND ' . $this->db->terms . '.term_id=' . $this->db->term_taxonomy . '.term_id';
            $taxSql .= ')';
            $taxSql .= ')';

            $where[] = $postIdField . ' IN (' . $taxSql . ')';
        }

        // Extend with object type
        if (!empty($params['object_type']))
            $where[] = 'EXISTS (SELECT true FROM ' . $this->db->posts . ' WHERE ID=' . $postIdField . ' AND post_type=\'' . $this->escape($params['object_type']) . '\')';

        // Extend with price condition
        if (!empty($params['HuurPrijsConditie']) && !empty($params['object_type'])) {
            if (!is_array($params['HuurPrijsConditie']))
                $params['HuurPrijsConditie'] = array($params['HuurPrijsConditie']);

            $where[] = 'EXISTS (SELECT true FROM ' . $this->db->postmeta . ' AS meta2 WHERE meta2.post_id=' . $postIdField . ' AND meta2.meta_key=\'' . $this->escape($params['object_type']) . '_HuurPrijsConditie\' AND meta2.meta_value IN (\'' . implode('\',\'', $this->escape($params['HuurPrijsConditie'])) . '\'))';
        }

        return $where;
    }

    private function escape($value)
    {
        if (is_array($value)) {
            $values = array();
            foreach ($value as $currentValue) {
                $values[] = $this->escape($currentValue);
            }

            return $values;
        }

        return $this->db->_real_escape($value);
    }
}