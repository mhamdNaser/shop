jQuery(document).ready(function()
{
  var yFormatThousands = function(n, dp){
    var s = ''+(Math.floor(n)), d = n % 1, i = s.length, r = '';
    while ( (i -= 3) > 0 ) { r = '.' + s.substr(i, 3) + r; }
    return s.substr(0, i + 3) + r + 
      (d ? '.' + Math.round(d * Math.pow(10, dp || 2)) : '');
  };
  
  var yogFormSliders    = jQuery('.yog-form-slider-holder');
  var yogFormSliderIds  = new Array();
  
  // Set correct display styling
  jQuery('input', yogFormSliders).css('display', 'none');
  jQuery('.yog-form-slider-labels', yogFormSliders).css('display', 'block');
  
  // Collect slider ids
  for (var i=0; i < yogFormSliders.length; i++)
  {
    yogFormSliderIds[yogFormSliderIds.length] = yogFormSliders[i].id;
  }
  
  // Create sliders
  for (var i=0; i < yogFormSliderIds.length; i++)
  {
    var yogFormSliderId       = yogFormSliderIds[i];
    
    var settings  = jQuery.parseJSON(jQuery('#' + yogFormSliderId + ' .yog-form-slider-settings').html());
    var minValue  = parseInt(jQuery('#' + yogFormSliderId + ' input.yog-form-slider-min').val());
    var maxValue  = parseInt(jQuery('#' + yogFormSliderId + ' input.yog-form-slider-max').val());
    
    // Set min value
    if (!minValue || minValue == 0)
    {
      minValue = settings.min;
      jQuery('#' + yogFormSliderId + ' input.yog-form-slider-min').val(minValue);
    }

    // Set max value
    if (!maxValue || maxValue == 0)
    {
      maxValue = settings.max;
      jQuery('#' + yogFormSliderId + ' input.yog-form-slider-max').val(maxValue);
    }
    
    // Assign min / max values to labels
    jQuery('#' + yogFormSliderId + ' .yog-form-slider-min-label').html(yFormatThousands(parseInt(minValue)));
    jQuery('#' + yogFormSliderId + ' .yog-form-slider-max-label').html(yFormatThousands(parseInt(maxValue)));
    
    // Determine steps
    var difference = (settings.max - settings.min);
    
    if (difference <= 20)
      var steps = 1;
    else if (difference <= 50)
      var steps = 5;
    else if (difference <= 500)
      var steps = 10;
    else if (difference <= 1000)
      var steps = 25;
    else if (difference <= 10000)
      var steps = 100;
    else
      var steps = 1000;
    
    jQuery('#' + yogFormSliderId + ' .yog-form-slider').slider(
      { 'range': true,
        'min': parseInt(settings.min),
        'max': parseInt(settings.max),
        'step': steps,
        'values': [minValue, maxValue],
        'slide': function(e,ui)
        {
          var values    = ui.values;
          var minValue  = Math.floor(values[0]);
          var maxValue  = Math.ceil(values[1]);
          var step      = jQuery(this).slider('option', 'step');
          var min       = jQuery(this).slider('option', 'min');
          var max       = jQuery(this).slider('option', 'max');
          
          if (minValue == step || minValue < (min + step))
            minValue = min;
            
          if (maxValue > (max - step))
            maxValue = max;
          
          jQuery(this).slider('option', 'values', [minValue, maxValue]);
          jQuery('.yog-form-slider-min-label', this.parentNode).html(yFormatThousands(minValue));
          jQuery('.yog-form-slider-max-label', this.parentNode).html(yFormatThousands(maxValue));
        },
        'stop': function(e, ui)
        {
          var minElement      = jQuery('input.yog-form-slider-min', this.parentNode);
          var maxElement      = jQuery('input.yog-form-slider-max', this.parentNode);
          var currentMinValue = minElement.val();
          var currentMaxValue = maxElement.val();
          var newMinValue     = jQuery('.yog-form-slider-min-label', this.parentNode).html();
          var newMaxValue     = jQuery('.yog-form-slider-max-label', this.parentNode).html();
          
          if (currentMinValue != newMinValue)
          {
            minElement.val(newMinValue);
            minElement.change();
          }
          
          if (currentMaxValue != newMaxValue)
          {
            maxElement.val(newMaxValue);
            minElement.change();
          }
        }
      }
    );
  }
});
