<?php
/**
 * Abstract for defining blocks
 * @author Kees Brandenburg - Yes-co
 */
abstract class YogBlockAbstract
{
	/**
	 * Unique identifier of the block, must be overwritten by extending class
	 * @param void
	 * @return string
	 */
	abstract public function getIdentifier();

	/**
	 * Get the editor script url, must be overwritten by extending class
	 * @param void
	 * @return string
	 */
	abstract public function getEditorScript();

	/**
	 * Get the dependencies for the editor script, can be overwritten by extending class
	 * @return array
	 */
	public function getEditorScriptDependencies()
	{
		return array( 'wp-blocks', 'wp-editor', 'wp-components', 'wp-element');
	}

	/**
	 * Get the editor stylesheet url, can be overwritten by extending class
	 * @return boolean|string
	 */
	public function getEditorStyle()
	{
		return false;
	}

	/**
	 * Get the stylesheet url for displaying the block, can be overwritten by extending class
	 * @return boolean|string
	 */
	public function getDisplayStyle()
	{
		return false;
	}

	/**
	 * Get the version of the block, can be overwritten by the extending class
	 * @return mixed
	 */
	public function getVersion()
	{
		return YOG_PLUGIN_VERSION;
	}

	/**
	 * Generate the asset key
	 * @return string
	 */
	final public function generateAssetKey()
	{
		return str_replace(array('/', ' '), '-', $this->getIdentifier());
	}

	/**
	 * Register the block
	 * @return void
	 */
	final public function register()
	{
		$editorStyle	= $this->getEditorStyle();
		$displayStyle	= $this->getDisplayStyle();
		$blockKey			= $this->generateAssetKey();
		$blockVersion	= $this->getVersion();

		$options = array(
			'editor_script'		=> $this->generateAssetKey() . '-editor-script',
		);

		if (method_exists($this, 'render'))
			$options['render_callback'] = array(get_class($this), 'render');

		// Register editor script
		wp_register_script($blockKey . '-editor-script', $this->getEditorScript(), $this->getEditorScriptDependencies(), $blockVersion);

		// Handle editor style
		if (!empty($editorStyle))
		{
			wp_register_style($blockKey . '-editor-style',$editorStyle, array(), $blockVersion);

			$options['editor_style'] = $blockKey . '-editor-style';
		}

		// Handle display style
		if (!empty($displayStyle))
		{
			wp_register_style($blockKey . '-display-style', $displayStyle, array(), $blockVersion);

			$options['style'] = $blockKey . '-display-style';
		}

		register_block_type($this->getIdentifier(), $options);
	}
}