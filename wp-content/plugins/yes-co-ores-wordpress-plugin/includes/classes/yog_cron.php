<?php
class YogCron
{
  /**
  * @desc Update open house categories for open house dates in the past
  */
  public static function updateOpenHouses()
  {
    // Retrieve all objects with open house category
    $objecten = get_posts(array('post_type'     => YOG_POST_TYPE_WONEN,
                                'category_name' => 'open-huis',
                                'numberposts'   => -1));

    $taxName  = (get_option('yog_cat_custom') ? 'yog_category' : 'category');

    foreach ($objecten as $object)
    {
      $openHouseStart = get_post_meta($object->ID,'huis_OpenHuisTot', true);
      $openHouseEnd   = get_post_meta($object->ID,'huis_OpenHuisTot',true);

      $openHouseStartTimestamp	= null;
      $openHouseEndTimestamp		= null;
      $currentTimestamp					= current_time('timestamp');

      if (!empty($openHouseStart))
      {
        $dateTime									= new \DateTime($openHouseStart);
        $openHouseStartTimestamp	= $dateTime->format('U');
      }

      if (!empty($openHouseEnd))
      {
        $dateTime									= new \DateTime($openHouseEnd);
        $openHouseEndTimestamp		= $dateTime->format('U');
      }

      // Update categories if open house date is old
      if ((is_null($openHouseStartTimestamp) || $openHouseStartTimestamp < $currentTimestamp) && (is_null($openHouseEndTimestamp) || $openHouseEndTimestamp < $currentTimestamp))
      {
        $categories     = wp_get_object_terms( $object->ID, $taxName);
        $categorySlugs  = array();

        foreach ($categories as $category)
        {
          if ($category->slug != 'open-huis')
            $categorySlugs[] = $category->slug;
        }

        wp_set_object_terms( $object->ID, $categorySlugs, $taxName, false);
      }
    }
  }
}