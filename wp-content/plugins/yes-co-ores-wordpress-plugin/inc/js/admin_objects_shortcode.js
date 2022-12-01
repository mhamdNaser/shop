jQuery(document).ready( function($)
{
  var shortcodeGeneratorHolder  = jQuery('#yog-shortcode-generator');
  
  if (shortcodeGeneratorHolder && shortcodeGeneratorHolder.length > 0)
  {
    var shortcodeGenerate = function()
    {
      var shortcode = '';

      jQuery('.shortcode-elem', shortcodeGeneratorHolder).each(function()
      {
        var elem = jQuery(this);
        if (elem.val() != '')
        {
          shortcode += ' ' + elem.attr('name') + '="' + elem.val() + '"';
        }
      });

      jQuery('#yog-shortcode-result', shortcodeGeneratorHolder).html(shortcode);
    };
  
    jQuery('.shortcode-elem', shortcodeGeneratorHolder).change(shortcodeGenerate);
    
    shortcodeGenerate();
  }
});