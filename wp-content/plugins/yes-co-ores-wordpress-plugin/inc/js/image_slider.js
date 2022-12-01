var YogScrollTimer;
var YogImageSlider;

jQuery(document).ready(function()
{
  /**
  * Image slider thumbnail click
  */
  jQuery('.yog-thumb').click(function(event)
  {
    event.preventDefault();
    
    var elem              = jQuery(event.currentTarget);
    var imagesHolder      = elem.closest('.yog-images-holder');
    var imageSliderHolder = jQuery('.yog-image-slider-holder', imagesHolder);
    var enableScrolling   = imageSliderHolder.hasClass('yog-scrolling-enabled');
    
    if (enableScrolling)
    {
      var child       = elem.children();
      var imageClass  = child.attr('class');
      var imageSlider = jQuery('.yog-image-slider', imageSliderHolder);
      
      var firstImage  = jQuery('.yog-image-0', imageSliderHolder);
      var image       = jQuery('.' + imageClass, imageSliderHolder);
      
      if (firstImage.length > 0 && image.length > 0)
      {
        var pos = (image.offset().left - firstImage.offset().left) - (imageSlider.width()  / 2) + (image.width() / 2);
        if (pos < 0)
          pos = 0;
        
        imageSlider.animate({scrollLeft: pos}, 'slow');
      }
    }

    jQuery('.yog-big-image', imagesHolder).attr('src', elem.attr('href'));
  });
  
  /**
  * Stop scrolling on mouse out
  */
  jQuery('.yog-image-slider-holder.yog-scrolling-enabled .yog-scroll').mouseout(function()
  {
    clearInterval(YogScrollTimer);
  });
  
  /**
  * Scroll left
  */
  jQuery('.yog-image-slider-holder.yog-scrolling-enabled .yog-scroll.left').mouseover(function(event)
  {
    var elem          = jQuery(event.currentTarget);
    var imagesHolder  = elem.closest('.yog-images-holder');
    YogImageSlider    = jQuery('.yog-image-slider', imagesHolder);
    
    YogScrollTimer    = setInterval(function() {
                        YogImageSlider.scrollLeft(YogImageSlider.scrollLeft() - 2);
                      }, 15);
  });

  /**
  * Scroll right
  */
  jQuery('.yog-image-slider-holder.yog-scrolling-enabled .yog-scroll.right').mouseover(function(event)
  {
    var elem          = jQuery(event.currentTarget);
    var imagesHolder  = elem.closest('.yog-images-holder');
    YogImageSlider    = jQuery('.yog-image-slider', imagesHolder);

    YogScrollTimer    = setInterval(function() {
                        YogImageSlider.scrollLeft(YogImageSlider.scrollLeft() + 2);
                      }, 15);
  });

  // Adjust .left / .right height
  var yogSliderHeight = jQuery('.yog-image-slider-holder.yog-scrolling-enabled').height();
  if (yogSliderHeight)
  {
    jQuery('.yog-image-slider-holder .left').height(yogSliderHeight);
    jQuery('.yog-image-slider-holder .right').height(yogSliderHeight);
  }
});
