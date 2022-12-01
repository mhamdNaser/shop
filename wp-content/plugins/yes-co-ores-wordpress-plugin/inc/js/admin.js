jQuery(document).ready( function($)
{
  /**
  * Toggle settings
  */
  jQuery('.yog-toggle-setting').click(function()
  {
		var element				= jQuery(this);
		var form					= element.closest('form');
		var nonceField		= jQuery('input[name=_wpnonce]', form);
		var refererField	= jQuery('input[name=_wp_http_referer]', form);

		if (nonceField && nonceField.length === 1 && nonceField.val() !== '' && refererField && refererField.length === 1 && refererField.val() !== '')
		{
			var msgHolder = jQuery('.msg', element.parent());
			msgHolder.addClass('hide');

			jQuery.post(ajaxurl, {'action': 'setsetting', 'cookie': encodeURIComponent(document.cookie), 'name' : this.name, '_wpnonce': nonceField.val(), '_wp_http_referer': refererField.val()},
				function(msg)
				{
					msgHolder.html('&nbsp;' + msg);
					msgHolder.removeClass('hide');

					if (msg.indexOf('Fout: ') !== -1)
					{
						msgHolder.addClass('error');
					}
					else
					{
						msgHolder.removeClass('error');
						setTimeout(function(){ msgHolder.addClass('hide') }, 5000);
					}
				}
			);
		}
  });

  /**
  * Set settings
  */
  jQuery('.yog-set-setting').change(function()
  {
		var element				= jQuery(this);
		var form					= element.closest('form');
		var nonceField		= jQuery('input[name=_wpnonce]', form);
		var refererField	= jQuery('input[name=_wp_http_referer]', form);

		if (nonceField && nonceField.length === 1 && nonceField.val() !== '' && refererField && refererField.length === 1 && refererField.val() !== '')
		{
			var msgHolder = jQuery('.msg', jQuery(this).parent());
			msgHolder.addClass('hide');

			jQuery.post(ajaxurl, {'action': 'setsetting', 'cookie': encodeURIComponent(document.cookie), 'name' : this.name, 'value': this.value, '_wpnonce': nonceField.val(), '_wp_http_referer': refererField.val()},
				function(msg)
				{
					msgHolder.html('&nbsp;' + msg);
					msgHolder.removeClass('hide');

					if (msg.indexOf('Fout: ') !== -1)
					{
						msgHolder.addClass('error');
					}
					else
					{
						msgHolder.removeClass('error');
						setTimeout(function(){ msgHolder.addClass('hide') }, 5000);
					}
				}
			);
		}
  });

  /**
   * Show / hide order when yog-toggle-cat-custom not checked
   */
  jQuery('#yog-toggle-cat-custom').change(function()
  {
    if (this.checked)
    {
      jQuery('#yog-sortoptions').show();
    }
    else
    {
      jQuery('#yog-sortoptions').hide();
      jQuery('#yog_order').val('');
    }
  });

  /**
  * Add system link
  */
  jQuery('#yog-add-system-link').click(function()
  {
		var element				= jQuery('#yog-add-system-link');
		var form					= element.closest('form');
		var nonceField		= jQuery('input[name=_wpnonce]', form);
		var refererField	= jQuery('input[name=_wp_http_referer]', form);

		if (nonceField && nonceField.length === 1 && nonceField.val() !== '' && refererField && refererField.length === 1 && refererField.val() !== '')
		{
			element.hide();
			jQuery('#yog-add-system-link-holder', form).addClass('loading');
			jQuery('#yog-add-system-link-holder', form).addClass('loading-padding');

			var secret  = jQuery('#yog-new-secret', form).val();

			jQuery.post(ajaxurl, {'action': 'addkoppeling', 'activatiecode':secret, 'cookie': encodeURIComponent(document.cookie), '_wpnonce': nonceField.val(), '_wp_http_referer': refererField.val()},
				function(html)
				{
					if (html.indexOf('Fout: ') !== -1)
					{
						jQuery('#yog-system-links').append('<p class="error">' + html + '</p>');
					}
					else
					{
						jQuery('#yog-system-links').append(html);
						jQuery('#yog-add-system-link-holder').removeClass('loading');
						jQuery('#yog-add-system-link-holder').removeClass('loading-padding');
						jQuery('#yog-new-secret').val('');
						jQuery('#yog-add-system-link').show();
					}
				}
			);
		}
  });

	function initWidgetFunctions()
	{
		// Toggle section on input checked
		jQuery('input[data-yog-toggle]').on('change', function() {
			if (this.dataset.yogToggle)
			{
				var toggleElem = jQuery('#' + this.dataset.yogToggle);
				if (this.checked)
					toggleElem.show();
				else
					toggleElem.hide();
			}
		});

		// Toggle section on input not checked
		jQuery('input[data-yog-toggle-reverse]').on('change', function() {
			if (this.dataset.yogToggleReverse)
			{
				var toggleElem = jQuery('#' + this.dataset.yogToggleReverse);
				if (this.checked)
					toggleElem.hide();
				else
					toggleElem.show();
			}
		});
	};

	// Init widget functions
	initWidgetFunctions();

	// Init widget functions after save of widget again (otherwise they can't be used anymore after save)
	jQuery(document).ajaxSuccess(function(e, xhr, settings) {
		if(settings.data && settings.data.search('action=save-widget') !== -1 && settings.data.search('id_base=yog') !== -1)
		{
			initWidgetFunctions();
		}
	});
});

/**
* Remove system link
*
* @param {string} secret
*/
function yogRemoveSystemLink(secret)
{
	var element				= jQuery('#yog-system-link-' + secret + '-remove');
	var form					= element.closest('form');
	var nonceField		= jQuery('input[name=_wpnonce_delete' + secret + ']', form);
	var refererField	= jQuery('input[name=_wp_http_referer]', form);

	if (nonceField && nonceField.length === 1 && nonceField.val() !== '' && refererField && refererField.length === 1 && refererField.val() !== '')
	{
		jQuery('span', element).hide();
		element.addClass('loading');
		element.addClass('loading-padding');

		jQuery.post(ajaxurl, {action:"removekoppeling", 'activatiecode':secret, 'cookie': encodeURIComponent(document.cookie), '_wpnonce': nonceField.val(), '_wp_http_referer': refererField.val()},
			function(secret)
			{
				if (secret.indexOf('Fout: ') !== -1)
				{
					jQuery('#TB_ajaxContent').prepend('<div class="notice-inline notice-error"><p>' + secret + '</p></div>');
				}
				else
				{
					tb_remove();
					jQuery('#yog-system-link-' + secret).fadeOut();
					jQuery('#yog-system-link-' + secret).remove();
				}
			}
		);
	}
}

/**
* Activate NB admin menu
*/
var yogActivateNbAdminMenu = function ()
{
  var mainMenuItem  = jQuery('#toplevel_page_yog_posts_menu');
  var wpBodyContent = jQuery('#wpbody-content');

  if (mainMenuItem.length > 0)
  {
	var nbMenuLink    = jQuery('li a[href="edit.php?post_type=yog-nbpr"]', mainMenuItem);
    var nbMenuItem    = nbMenuLink.parent();

    if (nbMenuItem.length > 0 && nbMenuLink.length > 0)
    {
      nbMenuItem.addClass('current');
      nbMenuLink.addClass('current');
    }
  }

  if (wpBodyContent.length > 0)
  {
    var scenario = jQuery('#yog_scenario');
    if (scenario.length > 0)
      wpBodyContent.addClass('yog-' + scenario.attr('value'));
  }
}

/**
* Activate BBpr admin menu
*/
var yogActivateComplexAdminMenu = function ()
{
  var mainMenuItem  = jQuery('#toplevel_page_yog_posts_menu');
  var wpBodyContent = jQuery('#wpbody-content');

  if (mainMenuItem.length > 0)
  {
	var nbMenuLink    = jQuery('li a[href="edit.php?post_type=yog-bbpr"]', mainMenuItem);
    var nbMenuItem    = nbMenuLink.parent();

    if (nbMenuItem.length > 0 && nbMenuLink.length > 0)
    {
      nbMenuItem.addClass('current');
      nbMenuLink.addClass('current');
    }
  }

  if (wpBodyContent.length > 0)
  {
    var scenario = jQuery('#yog_scenario');
    if (scenario.length > 0)
      wpBodyContent.addClass('yog-' + scenario.attr('value'));
  }
}