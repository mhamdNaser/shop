<?php
/**
* @desc YogLinkedObjectsWidget
* @author Kees Brandenburg - Yes-co Nederland
*/
class YogLinkedObjectsWidget extends WP_Widget
{
  const NAME              = 'Yes-co gelinkte objecten';
  const DESCRIPTION       = 'Toont de gelinkte objecten. Bijvoorbeeld om nieuwbouw types bij een project te tonen.';
  const CLASSNAME         = 'yog-linked-objects';
  const DEFAULT_IMG_SIZE  = 'thumbnail';

  /**
  * @desc Constructor
  *
  * @param void
  * @return YogObjectAttachmentsWidget
  */
  public function __construct()
  {
    $options = array( 'classsname'  => self::CLASSNAME,
                      'description' => self::DESCRIPTION);

    parent::__construct(false, $name = self::NAME, $options);
  }

  /**
  * @desc Display widget
  *
  * @param array $args
  * @param array $instance
  * @return void
  */
  public function widget($args, $instance)
  {
    if (!(is_single() && yog_isObject()))
      return;

    // Retrieve parent object
    if (yog_hasParentObject())
      $parentObject = yog_retrieveParentObject();

    // Retrieve child objects
    $childObjects = yog_retrieveChildObjects();

    if (!empty($parentObject) || !empty($childObjects))
    {
      // Retrieve widget settings
      $title            = apply_filters('widget_title', $instance['title']);
      $imgSize          = empty($instance['img_size']) ? self::DEFAULT_IMG_SIZE : $instance['img_size'];
      $beforeWidget     = isset($args['before_widget']) ? $args['before_widget'] : '';
      $afterWidget      = isset($args['after_widget']) ? $args['after_widget'] : '';
      $beforeTitle      = isset($args['before_title']) ? $args['before_title'] : '';
      $afterTitle       = isset($args['after_title']) ? $args['after_title'] : '';

			// Enqueue styles
      wp_enqueue_style('yog-widgets-css', YOG_PLUGIN_URL . '/inc/css/widgets.css', array(), YOG_PLUGIN_VERSION);

      // Show widget start
      echo $beforeWidget;
      echo $beforeTitle . esc_html($title) . $afterTitle;

      // Show parent object
      if (!empty($parentObject))
      {
        echo '<div class="yog-parent-object-holder">';
        echo $this->renderObject($parentObject, $imgSize);
        echo '</div>';
      }

      // Show child objects
      if (!empty($childObjects))
      {
        echo '<div class="yog-child-objects-holder">';
        foreach ($childObjects as $childObject)
        {
          if (in_array($childObject->post_type, array(YOG_POST_TYPE_NBTY, YOG_POST_TYPE_WONEN)))
            echo $this->renderObject($childObject, $imgSize);
        }

        echo '</div>';
      }

      // Show widget end
      echo $afterWidget;
    }
  }

  /**
  * @desc Render object
  *
  * @param WP_Post $object
  * @param string $imgSize
  * @return void
  */
  private function renderObject($object, $imgSize)
  {
    if (!is_object($object))
      throw new Exception(__METHOD__ . '; No object provided');

    $title      = $object->post_title;
    $url        = get_permalink($object->ID);
    $thumbnail  = get_the_post_thumbnail($object->ID, $imgSize);
    $prices     = yog_retrievePrices('priceType', 'priceCondition', $object->ID);
    $scenario   = yog_retrieveSpec('scenario', $object->ID);

    echo '<div class="yog-linked-object yog-object-' . esc_attr(strtolower($scenario)) . '">';
      if (!empty($thumbnail))
        echo '<a href="' . esc_url($url) . '" title="' . esc_attr($title) . '">' . $thumbnail . '</a>';
      echo '<a href="' . esc_url($url) . '" title="' . esc_attr($title) . '">' . esc_html($title) . '</a>';
      if (!empty($prices))
        echo '<div class="yog-linked-object-price">' . implode('<br />', $prices) . '</div>';
    echo '</div>';
  }

  /**
  * @desc Update widget settings
  *
  * @param array $new_instance
  * @param array $old_instance
  * @return array
  */
  public function update($new_instance, $old_instance)
  {
    $instance               = $old_instance;
    $instance['title']      = empty($new_instance['title']) ? '' : $new_instance['title'];
    $instance['img_size']   = empty($new_instance['img_size']) ? self::DEFAULT_IMG_SIZE : $new_instance['img_size'];

    return $instance;
  }

  /**
  * @desc Display widget form
  *
  * @param array $instance
  * @return void
  */
  public function form($instance)
  {
    $title              = empty($instance['title']) ? '' : esc_attr($instance['title']);
    $imgSize            = empty($instance['img_size']) ? self::DEFAULT_IMG_SIZE : $instance['img_size'];

    echo '<p>';
      echo '<label for="' . esc_attr($this->get_field_id('title')) . '">' . __('Titel') . ': </label>';
      echo '<input class="widefat" id="' . esc_attr($this->get_field_id('title')) . '" name="' . esc_attr($this->get_field_name('title')) . '" type="text" value="' . esc_attr($title) . '" />';
    echo '</p>';

    echo '<p>';
    echo '<label for="' . esc_attr($this->get_field_id('img_size')) . '">' . __('Formaat afbeeldingen') . ': </label>';
    echo '<select id="' . esc_attr($this->get_field_id('img_size')) . '" name="' . esc_attr($this->get_field_name('img_size')) . '">';
      foreach (get_intermediate_image_sizes() as $size)
      {
        echo '<option value="' . esc_attr($size) . '"' . (($size == $imgSize) ? ' selected="selected"' : '') . '>' . esc_attr(__(ucfirst($size))) . '</option>';
      }
      echo '</select>';
    echo '</p>';
  }
}