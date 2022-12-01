jQuery(document).ready(function()
{
  var searchFormWidgets = jQuery('.yog-search-form-widget');

  for (var i=0; i < searchFormWidgets.length; i++)
  {
    yogSearchFormUpdateNum(searchFormWidgets[i].id);

    var yogObjectType = jQuery('input[name=object_type]', searchFormWidgets[i]).val();

    jQuery('.price-type-holder', searchFormWidgets[i]).change(function()
    {
      var koopChecked     = jQuery('#PrijsType0', this).prop('checked');
      var huurChecked     = jQuery('#PrijsType1', this).prop('checked');
      var koopPrijsHolder = jQuery('#Koopprijs-holder', searchFormWidgets[i]);
      var huurPrijsHolder = jQuery('#Huurprijs-holder', searchFormWidgets[i]);

      if (koopPrijsHolder.length === 1)
      {
        if (koopChecked || (!koopChecked && !huurChecked))
          koopPrijsHolder.css('display', 'block');
        else
          koopPrijsHolder.css('display', 'none');
      }

      if (huurPrijsHolder.length === 1)
      {
        if (huurChecked || (!koopChecked && !huurChecked))
          huurPrijsHolder.css('display', 'block');
        else
          huurPrijsHolder.css('display', 'none');
      }

      if (yogObjectType === 'bedrijf')
      {
        jQuery('.price-condition-holder input', searchFormWidgets[i]).each(function()
        {
          var elem        = jQuery(this);
          var elemHolder  = elem.closest('.yog-object-search-row');

          if (this.value === 'k.k.' || this.value === 'v.o.n.')
          {
            if (koopChecked || (!koopChecked && !huurChecked))
            {
              elemHolder.css('display', 'block');
            }
            else
            {
              elemHolder.css('display', 'none');
              elem.attr('checked', false);
            }
          }
          else
          {
            if (huurChecked || (!koopChecked && !huurChecked))
            {
              elemHolder.css('display', 'block');
            }
            else
            {
              elemHolder.css('display', 'none');
              elem.attr('checked', false);
            }
          }
        });
      }
    });

    if (yogObjectType === 'bedrijf')
    {
      jQuery('.price-condition-holder', searchFormWidgets[i]).change(function()
      {
        var koopChecked     = false;
        var huurChecked     = false;
        var koopPrijsHolder = jQuery('#Koopprijs-holder', searchFormWidgets[i]);
        var huurPrijsHolder = jQuery('#Huurprijs-holder', searchFormWidgets[i]);
        var conditieValues  = new Array();

        jQuery('input:checked', this).each(function()
        {
          if (this.value == 'k.k.' || this.value == 'v.o.n.')
          {
            koopChecked = true;
          }
          else
          {
            conditieValues.push(this.value);
            huurChecked = true;
          }
        });

        if (huurPrijsHolder.length === 1)
        {
          jQuery.post(YogConfig.baseUrl + '/wp-admin/admin-ajax.php?action=bogminmaxrentalprices', {'HuurPrijsConditie[]': conditieValues, 'object_type': yogObjectType},
            function(data, status)
            {
              if (status === 'success')
              {
                var response          = jQuery.parseJSON(data);
                var huurPrijsMinElem  = jQuery('select#HuurPrijs_min', huurPrijsHolder);
                var huurPrijsMaxElem  = jQuery('select#HuurPrijs_max', huurPrijsHolder);

                if (response.from)
                {
                  huurPrijsMinElem.empty();
                  jQuery.each(response.from, function(value,key) {
                    huurPrijsMinElem.append(jQuery('<option value="' + value + '">' + key + '</option>'));
                  });
                }

                if (response.till)
                {
                  huurPrijsMaxElem.empty();
                  jQuery.each(response.till, function(value,key) {
                    huurPrijsMaxElem.append(jQuery('<option value="' + value + '">' + key + '</option>'));
                  });
                }
              }
            }
          );
        }

        if (koopPrijsHolder.length === 1)
        {
          if (koopChecked || (!koopChecked && !huurChecked))
            koopPrijsHolder.css('display', 'block');
          else
            koopPrijsHolder.css('display', 'none');
        }

        if (huurPrijsHolder.length === 1)
        {
          if (huurChecked || (!koopChecked && !huurChecked))
            huurPrijsHolder.css('display', 'block');
          else
            huurPrijsHolder.css('display', 'none');
        }
      });
    }

    jQuery('.minmax-holder', searchFormWidgets[i]).each(function()
    {
      var minElem = jQuery('.value-min', this);
      var maxElem = jQuery('.value-max', this);

      if (minElem.length === 1 && maxElem.length === 1)
      {
        minElem.change(function()
        {
          var minElemValue  = parseInt(this.value, 10);
          var maxElemValue  = parseInt(maxElem.val(), 10);
          var numOptions    = jQuery('option', maxElem).length;

          if (maxElemValue > 0 && maxElemValue <= minElemValue)
          {
            var index = maxElem[0].selectedIndex;
            if ((index + 1) < numOptions)
            {
              while (maxElemValue > 0 && maxElemValue <= minElemValue)
              {
                jQuery('option:nth-child(' + index + ')', maxElem).prop('selected', true);

                maxElemValue = parseInt(maxElem.val(), 10);
                index++;
              }
            }
          }
        });

        maxElem.change(function()
        {
          var minElemValue  = parseInt(minElem.val(), 10);
          var maxElemValue  = parseInt(this.value, 10);

          if (minElemValue > 0 && maxElemValue > 0 && minElemValue >= maxElemValue)
          {
            var index = minElem[0].selectedIndex;

            while (minElemValue > 0 && minElemValue >= maxElemValue)
            {
              jQuery('option:nth-child(' + index + ')', minElem).prop('selected', true);

              minElemValue  = parseInt(minElem.val(), 10);
              index--;
            }
          }
        });
      }
    });
  }

  jQuery('.yog-search-form-widget .yog-object-form-elem').change(function(event)
  {
    yogSearchFormUpdateNum(this.form.id);
  }
  );
});

yogSearchFormUpdateNum = function(formId)
{
  // Detemine base url
  var baseUrl     = YogConfig.baseUrl;
  var formElem    = jQuery('#' + formId);

  formElem.addClass('loading');

  jQuery.getJSON(baseUrl, formElem.serialize() + '&yog-search-form-widget-ajax-search=true&form_id=' + formId,
    function(data, status)
    {
      if (status == 'success')
      {
        jQuery('#' + formId).removeClass('loading');

        var resultMsg = jQuery('#' + data.formId + ' .yog-object-search-result');
        var searchBtn = jQuery('#' + data.formId + ' .yog-object-search-button');

        resultMsg.html(data.msg);
        resultMsg.css('display', 'block');

        if (data.posts > 0)
          searchBtn.css('display', 'inline');
        else
          searchBtn.css('display', 'none');
      }
    }
  );
}