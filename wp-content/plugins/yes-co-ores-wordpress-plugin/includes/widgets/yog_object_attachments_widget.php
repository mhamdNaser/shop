<?php
/**
* @desc YogObjectAttachmentsWidget
* @author Stefan van Zanden - Yes-co Nederland
*/
class YogObjectAttachmentsWidget extends WP_Widget
{
  const NAME                = 'Yes-co Object Koppelingen';
  const DESCRIPTION         = 'Toont o.a. de website en brochure links die meegegeven zijn bij een object.';
  const CLASSNAME           = 'yog-attachments-form';

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
    $title              = apply_filters('widget_title', $instance['title']);
    $showLinks          = !isset($instance['show_links']) ? true : ($instance['show_links'] == 1 ? true : false);
    $showDocuments      = !isset($instance['show_documents']) ? true : ($instance['show_documents'] == 1 ? true : false);
    $showEmbededMovies  = !isset($instance['show_embeded_movies']) ? false : ($instance['show_embeded_movies'] == 1 ? true : false);
    $showMovieLinks     = !isset($instance['show_movie_links']) ? true : ($instance['show_movie_links'] == 1 ? true : false);
    $showDossierItems   = !isset($instance['show_dossier_items']) ? false : ($instance['show_dossier_items'] == 1 ? true : false);

    $links              = array();
    $documents          = array();
    $movies             = array();

    if (!(is_single() && yog_isObject()))
      return;

    if ($showDossierItems === true)
      $dossierItems = yog_retrieveDossierItems();

    if ($showLinks === true)
      $links      = yog_retrieveLinks();

    if ($showDocuments === true)
      $documents  = yog_retrieveDocuments();

    if ($showEmbededMovies === true && $showMovieLinks === true)
      $movies     = yog_retrieveMovies();
    else if ($showEmbededMovies === true)
      $movies     = yog_retrieveEmbedMovies();
    else if ($showMovieLinks === true)
      $movies     = yog_retrieveExternalMovies();

    if (!empty($dossierItems) || !empty($links) || !empty($documents) || !empty($movies))
    {
      echo $args['before_widget'];
      echo '<div class="borderbox widget widget_yogobjectattachments colored">';

      if (!empty($title))
        echo $args['before_title'] . esc_html($title) . $args['after_title'];

      echo '<ul>';

      if (!empty($dossierItems) && is_array($dossierItems))
      {
        foreach ($dossierItems as $dossierItem)
        {
          if (!empty($dossierItem['url']) && !empty($dossierItem['title']))
          {
            echo '<li><div class="link"><a href="' . esc_url($dossierItem['url']) . '" class="link-default link-dossier" target="_blank">' . esc_html($dossierItem['title']) . '</a></div></li>';
          }
        }
      }

      // Links
      if (!empty($links) && is_array($links))
      {
        foreach ($links as $link)
        {
          if (!empty($link['url']) && !empty($link['title']))
          {
            switch ($link['type'])
            {
              case 'previsite tour':
                $url    = $link['url'] . ((strpos($link['url'], '?') !== false) ? '&amp;' : '?') . 'KeepThis=true&amp;TB_iframe=true&amp;height=470&amp;width=700';
                $class  = 'link-' . $link['type'] . ' thickbox';
                break;
              default:
                $url    = $link['url'];
                $class  = 'link-' . $link['type'];
                break;
            }

            echo '<li><div class="link"><a href="' . esc_url($url) . '" class="link-default ' . esc_attr($class) . '" target="_blank">' . esc_html($link['title']) . '</a></div></li>';
          }
        }
      }

      // Documents
      if (!empty($documents) && is_array($documents))
      {
        foreach ($documents as $document)
        {
          if (!empty($document['url']) && !empty($document['title']))
            echo '<li><div class="link"><a href="' . esc_url($document['url']) . '" class="link-default link-' . esc_attr($document['type']) . '" target="_blank">' . esc_html($document['title']) . '</a></div></li>';
        }
      }

      // External movies
      if (!empty($movies) && is_array($movies))
      {
        foreach ($movies as $movie)
        {
          if (!empty($movie['title']) && !empty($movie['websiteurl']))
            echo '<li><div class="link"><a href="' . esc_url($movie['websiteurl']) . '" class="link-default link-' . esc_attr($movie['type']) . '" target="_blank">' . esc_html($movie['title']) . '</a></div></li>';
        }
      }

      echo '</ul>';
			echo '</div>';

      echo $args['after_widget'];
    }


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
    $instance                         = $old_instance;
    $instance['title']                = empty($new_instance['title']) ? '' : $new_instance['title'];
    $instance['show_links']           = empty($new_instance['show_links']) ? 0 : 1;
    $instance['show_documents']       = empty($new_instance['show_documents']) ? 0 : 1;
    $instance['show_embeded_movies']  = empty($new_instance['show_embeded_movies']) ? 0 : 1;
    $instance['show_movie_links']     = empty($new_instance['show_movie_links']) ? 0 : 1;
    $instance['show_dossier_items']   = empty($new_instance['show_dossier_items']) ? 0 : 1;

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
    $showLinks          = !isset($instance['show_links']) ? true : ($instance['show_links'] == 1 ? true : false);
    $showDocuments      = !isset($instance['show_documents']) ? true : ($instance['show_documents'] == 1 ? true : false);
    $showEmbededMovies  = !isset($instance['show_embeded_movies']) ? false : ($instance['show_embeded_movies'] == 1 ? true : false);
    $showMovieLinks     = !isset($instance['show_movie_links']) ? true : ($instance['show_movie_links'] == 1 ? true : false);
    $showDossierItems   = !isset($instance['show_dossier_items']) ? true : ($instance['show_dossier_items'] == 1 ? true : false);

    echo '<p>';
    echo '<label for="' . esc_attr($this->get_field_id('title')) . '">' . __('Titel') . ': </label>';
    echo '<input class="widefat" id="' . esc_attr($this->get_field_id('title')) . '" name="' . esc_attr($this->get_field_name('title')) . '" type="text" value="' . $title . '" />';
    echo '</p>';

    $id = $this->get_field_id('show_dossier_items');
		echo '<p>';
		echo '<input type="checkbox" name="' . esc_attr($this->get_field_name('show_dossier_items')) . '" value="1" id="' . esc_attr($id) . '"' . ($showDossierItems == true ? ' checked="checked"' : '') . ' />';
		echo '<label for="' . esc_attr($id) . '">' . __('Toon dossier items') . ': </label>';
    echo '</p>';

    $id = $this->get_field_id('show_links');
		echo '<p>';
		echo '<input type="checkbox" name="' . esc_attr($this->get_field_name('show_links')) . '" value="1" id="' . esc_attr($id) . '"' . ($showLinks == true ? ' checked="checked"' : '') . ' />';
		echo '<label for="' . esc_attr($id) . '">' . __('Toon links') . ': </label>';
    echo '</p>';

    $id = $this->get_field_id('show_documents');
		echo '<p>';
		echo '<input type="checkbox" name="' . esc_attr($this->get_field_name('show_documents')) . '" value="1" id="' . esc_attr($id) . '"' . ($showDocuments == true ? ' checked="checked"' : '') . ' />';
		echo '<label for="' . esc_attr($id) . '">' . __('Toon documenten') . ': </label>';
    echo '</p>';

    $id = $this->get_field_id('show_embeded_movies');
		echo '<p>';
		echo '<input type="checkbox" name="' . esc_attr($this->get_field_name('show_embeded_movies')) . '" value="1" id="' . esc_attr($id) . '"' . ($showEmbededMovies == true ? ' checked="checked"' : '') . ' />';
		echo '<label for="' . esc_attr($id) . '">' . __('Toon youtube / vimeo videos') . ': </label>';
    echo '</p>';

    $id = $this->get_field_id('show_movie_links');
		echo '<p>';
		echo '<input type="checkbox" name="' . esc_attr($this->get_field_name('show_movie_links')) . '" value="1" id="' . esc_attr($id) . '"' . ($showMovieLinks == true ? ' checked="checked"' : '') . ' />';
		echo '<label for="' . esc_attr($id) . '">' . __('Toon overige videos') . ': </label>';
    echo '</p>';
  }
}