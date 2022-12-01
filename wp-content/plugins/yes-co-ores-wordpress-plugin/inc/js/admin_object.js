jQuery(document).ready( function($)
{
  /**
  * Add video
  */
  jQuery('#yog-add-video').click(function()
  {
		var nonceField	= jQuery('input[name=yog-video-nonce]');

		if (nonceField && nonceField.length === 1 && nonceField.val() !== '')
		{
			jQuery('#yog-video-form').addClass('loading');

			var postId      = jQuery('#post_ID').val();
			var videoType   = jQuery('#video_type').val();
			var videoTitle  = jQuery('#video_titel').val();
			var videoUrl    = jQuery('#video_url').val();

			jQuery.post(ajaxurl, {'action': 'addvideo', 'post': postId, 'titel': videoTitle, 'type': videoType, 'url': videoUrl, 'cookie': encodeURIComponent(document.cookie), '_wpnonce': nonceField.val()},
				function(videoUuid)
				{
					if (videoUuid !== '')
					{
						videoUrl = videoUrl.replace('http://', '');

						jQuery('#yog-video-tabel tbody').append('<tr id="video-' + videoUuid + '"><td><a href="http://' + videoUrl + '" target="_blank">' + videoTitle + '</a></td><td class="actions"><input type="button" class="button-primary" onclick="yogRemoveVideo(\'' + videoUuid + '\');" value="Verwijderen" /></td></tr>');
						jQuery('#video_titel').val('');
						jQuery('#video_url').val('');

						jQuery('#yog-videos-overview').removeClass('hide');
						jQuery('#yog-video-form').removeClass('loading');
					}
				});
		}
  });

  /**
  * Add Document
  */
  jQuery('#yog-add-document').click(function()
  {
		var nonceField	= jQuery('input[name=yog-document-nonce]');

		if (nonceField && nonceField.length === 1 && nonceField.val() !== '')
		{
			jQuery('#yog-document-form').addClass('loading');

			var postId        = jQuery('#post_ID').val();
			var documentType  = jQuery('#document_type').val();
			var documentTitel = jQuery('#document_titel').val();
			var documentUrl   = jQuery('#document_url').val();

			jQuery.post(ajaxurl, {'action': 'adddocument', 'post':postId, 'titel':documentTitel, 'type':documentType, 'url':documentUrl, 'cookie': encodeURIComponent(document.cookie), '_wpnonce': nonceField.val()},
				function(documentUuid)
				{
					if (documentUuid !== '')
					{
						jQuery('#yog-documents-table tbody').append('<tr id="document-' + documentUuid + '"><td><a href="http://' + documentUrl + '" class="' + documentType + '">' + documentTitel + '</a></td><td class="actions"><input type="button" class="button-primary" onclick="yogRemoveDocument(\'' + documentUuid + '\');" value="Verwijderen" /></td></tr>');
						jQuery('#document_titel').val('');
						jQuery('#document_type').val('');
						jQuery('#document_url').val('');

						jQuery('#yog-documents-overview').removeClass('hide');
						jQuery('#yog-document-form').removeClass('loading');
					}
				});
		}
  });

  /**
  * Add link
  */
  jQuery('#yog-add-link').click(function()
  {
		var nonceField	= jQuery('input[name=yog-link-nonce]');

		if (nonceField && nonceField.length === 1 && nonceField.val() !== '')
		{
			jQuery('#yog-link-form').addClass('loading');

			var postId    = jQuery('#post_ID').val();
			var linkType  = jQuery('#link_type').val();
			var linkTitle = jQuery('#link_titel').val();
			var linkUrl   = jQuery('#link_url').val();

			jQuery.post(ajaxurl, {'action':'addlink', 'post':postId, 'titel':linkTitle, 'type':linkType, 'url':linkUrl, 'cookie': encodeURIComponent(document.cookie), '_wpnonce': nonceField.val()},
				function(linkUuid)
				{
					if (linkUuid !== '')
					{
						jQuery('#yog-links-table tbody').append('<tr id="link-' + linkUuid + '"><td><a href="http://' + linkUrl + '" class="' + linkType + '">' + linkTitle + '</a></td><td class="actions"><input type="button" class="button-primary" onclick="yogRemoveLink(\'' + linkUuid + '\');" value="Verwijderen" /></td></tr>');
						jQuery('#link_type').val('');
						jQuery('#link_titel').val('');
						jQuery('#link_url').val('');

						jQuery('#yog-links-overview').removeClass('hide');
						jQuery('#yog-link-form').removeClass('loading');
					}
				});
		}
  });

  /**
  * Switch BOG type on change
  */
  jQuery('select#yog-bedrijf_Type').change(function(event)
  {
    var elem  = event.currentTarget;
    var value = elem.value;

    yogToggleMetaContainers(value);
  });

  /**
  * Switch BOG type on load
  */
  jQuery('select#yog-bedrijf_Type').ready(function(event)
  {
    yogToggleMetaContainers(jQuery('select#yog-bedrijf_Type').val());
  });

  // Fix input type date
  jQuery('input[type=date].yog-date').each(function()
  {
  	if (this.type != 'date')
  	{
  		var elem = jQuery(this);
  		elem.datepicker({'dateFormat': 'dd-mm-yy'});

  		if (this.min)
  			elem.datepicker('option', 'minDate', this.min);
  		if (this.max)
  			elem.datepicker('option', 'maxDate', this.max);

      var value = elem.val();
      if (value.indexOf('-') !== -1)
      {
        var parts = value.split('-');
        if (parts.length === 3 && parts[0].length === 4)
          elem.val(parts[2] + '-' + parts[1] + '-' + parts[0]);
      }
  	}
  });

  // Fix input type time
  jQuery('input[type=time].yog-time').each(function()
  {
    if (this.type != 'time')
    {
      jQuery(this).change(function(event) {
        var elem  = event.currentTarget;
        var value = elem.value;

        if (!value.match(/^(?:0?\d|1[0-9]|2[0123]):([0-5]{0,1}[0-9]{1})$/))
          jQuery(elem).addClass('error');
        else
          jQuery(elem).removeClass('error');
      });
    }
  });
});

/**
* Remove video
*/
function yogRemoveVideo(uuid)
{
	var nonceField	= jQuery('input[name=yog-video-nonce]');

	if (nonceField && nonceField.length === 1 && nonceField.val() !== '')
	{
		jQuery('#video-' + uuid + ' td:last-child').addClass('loading');
		jQuery('#video-' + uuid + ' td .button-primary').remove();

		var postId      = jQuery('#post_ID').val();

		jQuery.post(ajaxurl, {action:"removevideo", 'uuid':uuid, 'post':postId, 'cookie': encodeURIComponent(document.cookie), '_wpnonce': nonceField.val()},
			function(uuid)
			{
				if (uuid !== '')
				{
					jQuery('#video-' + uuid).fadeOut();
					jQuery('#video-' + uuid).remove();

					if (jQuery('#yog-video-tabel tbody tr').length == 0)
						jQuery('#yog-videos-overview').addClass('hide');
				}
			});
	}
}

/**
* Remove document
*/
function yogRemoveDocument(uuid)
{
	var nonceField	= jQuery('input[name=yog-document-nonce]');

	if (nonceField && nonceField.length === 1 && nonceField.val() !== '')
	{
		jQuery('#document-' + uuid + ' td:last-child').addClass('loading');
		jQuery('#document-' + uuid + ' td .button-primary').remove();

		var postId      = jQuery('#post_ID').val();

		jQuery.post(ajaxurl, {action:"removedocument", 'uuid':uuid, 'post':postId, 'cookie': encodeURIComponent(document.cookie), '_wpnonce': nonceField.val()},
			function(uuid)
			{
				if (uuid !== '')
				{
					jQuery('#document-' + uuid).fadeOut();
					jQuery('#document-' + uuid).remove();

					if (jQuery('#yog-documents-table tbody tr').length == 0)
						jQuery('#yog-documents-overview').addClass('hide');
				}
			});
	}
}

/**
* Remove link
*/
function yogRemoveLink(uuid)
{
	var nonceField	= jQuery('input[name=yog-link-nonce]');

	if (nonceField && nonceField.length === 1 && nonceField.val() !== '')
	{
		jQuery('#link-' + uuid + ' td:last-child').addClass('loading');
		jQuery('#link-' + uuid + ' td .button-primary').remove();

		var postId      = jQuery('#post_ID').val();

		jQuery.post(ajaxurl, {action:"removelink", 'uuid':uuid, 'post':postId, 'cookie': encodeURIComponent(document.cookie), '_wpnonce': nonceField.val()},
			function(uuid)
			{
				if (uuid !== '')
				{
					jQuery('#link-' + uuid).fadeOut();
					jQuery('#link-' + uuid).remove();

					if (jQuery('#yog-links-table tbody tr').length == 0)
						jQuery('#yog-links-overview').addClass('hide');
				}
			});
	}
}

/**
* Toggle meta containers
*
* @param string objectType
* @return void
*/
function yogToggleMetaContainers(objectType)
{
  var bouwgrondVisible      = false;
  var gebouwVisible         = false;
  var bedrijfsruimteVisible = false;
  var kantoorruimteVisible  = false;
  var winkelruimteVisible   = false;
  var horecaVisible         = false;

  switch (objectType)
  {
    case 'Bouwgrond':
      bouwgrondVisible      = true;
      break;
    case 'Bedrijfsruimte':
      gebouwVisible         = true;
      bedrijfsruimteVisible = true;
      break;
    case 'Kantoorruimte':
      gebouwVisible         = true;
      kantoorruimteVisible  = true;
      break;
    case 'Winkelruimte':
      gebouwVisible         = true;
      winkelruimteVisible   = true;
      break;
    case 'Horeca':
      gebouwVisible         = true;
      horecaVisible         = true;
      break;
		case 'Garagebox':
			gebouwVisible         = true;
			break;
		case 'Leisure':
			gebouwVisible         = true;
			break;
		case 'Maatschappelijk vastgoed':
			gebouwVisible         = true;
			break;
		case 'Praktijkruimte':
			gebouwVisible         = true;
			break;
  }

  if (bouwgrondVisible)
    jQuery('#yog-bouwgrond-meta').show();
  else
    jQuery('#yog-bouwgrond-meta').hide();

  if (gebouwVisible)
    jQuery('#yog-gebouw-meta').show();
  else
    jQuery('#yog-gebouw-meta').hide();

  if (bedrijfsruimteVisible)
    jQuery('#yog-bedrijfsruimte-meta').show();
  else
    jQuery('#yog-bedrijfsruimte-meta').hide();

  if (kantoorruimteVisible)
    jQuery('#yog-kantoorruimte-meta').show();
  else
    jQuery('#yog-kantoorruimte-meta').hide();

  if (winkelruimteVisible)
    jQuery('#yog-winkelruimte-meta').show();
  else
    jQuery('#yog-winkelruimte-meta').hide();

  if (horecaVisible)
    jQuery('#yog-horeca-meta').show();
  else
    jQuery('#yog-horeca-meta').hide();
}
