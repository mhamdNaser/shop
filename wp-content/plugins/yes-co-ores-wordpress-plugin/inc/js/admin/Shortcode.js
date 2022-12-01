define("yog/admin/Shortcode", ['dojo'], function(dojo) {

/**
 * YOG Admin Object class
 */
dojo.declare('yog.admin.Shortcode', null,
/**
 * @lends yog.admin.Object.prototype
 */
{
  /**
   * Constructor
   *
   * @constructs
   * @param {Void}
   * @return {yog.admin.Object}
   */
  constructor: function(yogMap, yogMarker)
  {
    this._mapInstance     = yogMap;
		this._markerInstance	= yogMarker;

    this._postTypesCheckboxes = dojo.query('input[name="shortcode_PostTypes[]"]');

    for (var i = 0; i < this._postTypesCheckboxes.length; i++)
    {
      dojo.connect(this._postTypesCheckboxes[i], 'onclick', this, '_onInputFieldsChange');
    }

    this._shortcodeElem   = dojo.byId('yogShortcode');
    this._inputLatitude   = dojo.byId('shortcode_Latitude');
    this._inputLongitude  = dojo.byId('shortcode_Longitude');
    this._inputWidth      = dojo.byId('shortcode_Width');
    this._inputWidthUnit  = dojo.byId('shortcode_WidthUnit');
    this._inputHeight     = dojo.byId('shortcode_Height');
    this._inputHeightUnit = dojo.byId('shortcode_HeightUnit');

    if (this._inputLatitude && this._inputLongitude)
    {
      dojo.connect(this._inputLatitude, 'onchange', this, '_onMarkerGeocodeInputFieldsChange');
      dojo.connect(this._inputLongitude, 'onchange', this, '_onMarkerGeocodeInputFieldsChange');
      dojo.connect(this._inputWidth, 'onchange', this, '_onInputFieldsChange');
      dojo.connect(this._inputWidthUnit, 'onchange', this, '_onInputFieldsChange');
      dojo.connect(this._inputHeight, 'onchange', this, '_onInputFieldsChange');
      dojo.connect(this._inputHeightUnit, 'onchange', this, '_onInputFieldsChange');
    }

    // Connecting to the marker dragend event
		this._markerInstance.addListener('dragend', function(marker) {
			this._onMarkerDragEnd(marker);
		}.bind(this));

    this._mapInstance.addListener('zoom_changed', function() {
			this._onZoomLevelChanged();
		}.bind(this));

		this._mapInstance.addListener('tilesloaded', function() {
			this._onMapLoaded();
		}.bind(this));
  },

  /**
   * Method _onMapLoaded
   *
   * @param {Void}
   * @return {Void}
   */
  _onMapLoaded: function()
  {
    this.generateShortcode();
  },

  /**
   * Method which is called when the width or height field is changed
   *
   * @param {Void}
   * @return {Void}
   */
  _onInputFieldsChange: function()
  {
    this.generateShortcode();
  },

  /**
   * Method which is called when the zoomlevel has been changed
   *
   * @param {Void}
   * @return {void}
   */
  _onZoomLevelChanged: function()
  {
		this.generateShortcode();
  },

  /**
   * Method which is called when a draggable marker is stopped being moved on the screen
   *
   * @param {object} marker
   * @return {void}
   */
	_onMarkerDragEnd: function(marker)
	{
		this._mapInstance.setCenter(marker.latLng);

		this._onMarkerPositionChanged();
	},

    /**
     * Method which takes the current map settings and generates a shortcode
     *
     * @param {Void}
     * @return {Void}
     */
    generateShortcode: function()
    {
      var newShortcode = '[yog-map ';

      var valuePostTypes = [];

      // Post types
      for (var i = 0; i < this._postTypesCheckboxes.length; i++)
      {
        var checked = dojo.attr(this._postTypesCheckboxes[i], 'checked');
        var value   = dojo.attr(this._postTypesCheckboxes[i], 'value');

        if (checked === true)
        {
          valuePostTypes.push(value);
        }
      }

      // Only render the post type tag in case 1 or more are checked and in case not all are checked
      if (valuePostTypes.length > 0 && valuePostTypes.length < this._postTypesCheckboxes.length)
      {
        newShortcode += ' post_types="' + valuePostTypes.join(',') + '"';
      }

      // Center latitude / longitude
      if (this._markerInstance)
      {
				var geocode = this._markerInstance.position;

        newShortcode += ' center_latitude="' + geocode.lat() + '"';
        newShortcode += ' center_longitude="' + geocode.lng() + '"';
      }

      // Zoomlevel
      newShortcode += ' zoomlevel="' + this._mapInstance.zoom + '"';

      // Map type
      newShortcode += ' map_type="' + this._mapInstance.mapTypeId + '"';

      // Width
      var width  = dojo.attr(this._inputWidth, 'value');

      // Height
      var height = dojo.attr(this._inputHeight, 'value');

      // Width and WidthUnit
      if (width > 0)
      {
        newShortcode += ' width="' + width + '"';

        var widthUnit = this._inputWidthUnit.options[this._inputWidthUnit.selectedIndex].value;

        // WidthUnit
        newShortcode += ' width_unit="' + widthUnit + '"';
      }

      // Height and HeightUnit
      if (height > 0)
      {
        newShortcode += ' height="' + height + '"';

        var heightUnit = this._inputHeightUnit.options[this._inputHeightUnit.selectedIndex].value;

        // HeightUnit
        newShortcode += ' height_unit="' + heightUnit + '"';
      }

      newShortcode += ']';

      this._shortcodeElem.innerHTML = newShortcode;
    },

  /**
   * Method which is called when either the latitude or longitude field is changed
   *
   * @param object event
   * @return void
   */
    _onMarkerGeocodeInputFieldsChange: function(event)
    {
			if (this._markerInstance)
			{
				var inputLatitude   = parseFloat(dojo.attr(this._inputLatitude, 'value'));
				var inputLongitude	= parseFloat(dojo.attr(this._inputLongitude, 'value'));

				if (!isNaN(inputLatitude) && !isNaN(inputLongitude))
				{
					var geocodeValid	= true;

					if (inputLatitude < -90 || inputLatitude > 90)
					{
						dojo.addClass(this._inputLatitude, this.ERROR_CLASSNAME);
						dojo.attr(this._inputLatitude, 'title', 'This value needs to be between -90 and 90 degree');

						geocodeValid = false;
					}

					if (inputLongitude < -180 || inputLongitude > 180)
					{
						dojo.addClass(this._inputLongitude, this.ERROR_CLASSNAME);
						dojo.attr(this._inputLongitude, 'title', 'This value needs to be between -180 and 180 degree');

						geocodeValid = false;
					}

					if (geocodeValid === true)
					{
						var geocode = {lat: inputLatitude, lng: inputLongitude};

						this._mapInstance.setCenter(geocode);
						this._markerInstance.setPosition(geocode);

						dojo.removeClass(this._inputLatitude, this.ERROR_CLASSNAME);
						dojo.removeClass(this._inputLongitude, this.ERROR_CLASSNAME);

						dojo.attr(this._inputLatitude, 'title', '');
						dojo.attr(this._inputLongitude, 'title', '');

						this._onMarkerPositionChanged();
					}
				}
			}
    },

    /**
   * Method which is called whenever the position of the marker is changed to update data
   *
   * @param {void}
   * @return {void}
   */
    _onMarkerPositionChanged: function()
    {
			if (this._markerInstance) // Assuming we only have 1 marker which will return
			{
				var geocode = this._markerInstance.position;

				dojo.attr(this._inputLatitude, 'value', geocode.lat());
				dojo.attr(this._inputLongitude, 'value', geocode.lng());

				// Assuming it will be correct now because we get the info from mister Google himself
				dojo.removeClass(this._inputLatitude, this.ERROR_CLASSNAME);
				dojo.removeClass(this._inputLongitude, this.ERROR_CLASSNAME);

				dojo.attr(this._inputLatitude, 'title', '');
				dojo.attr(this._inputLongitude, 'title', '');
			}

			this.generateShortcode();
    }

});

});
