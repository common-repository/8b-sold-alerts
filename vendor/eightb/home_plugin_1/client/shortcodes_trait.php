<?php

namespace eightb\home_plugin_1\client;

/**
	@brief		Handles shortcode functions.
	@since		2017-03-05 11:32:31
**/
trait shortcodes_trait
{
	/**
		@brief		Return the prefix used for shortcodes.
		@details	For example: 8b_home_value_
		@since		2017-03-05 11:33:05
	**/
	public function get_plugin_prefix()
	{
		$this->wp_die( 'Please override this function: %s\%s', __CLASS__, __FUNCTION__ );
	}

	/**
		@brief		Return the name of the shortcode.
		@details	The default is the plugin prefix.
		@since		2017-03-07 22:27:42
	**/
	public function get_shortcode_name()
	{
		return $this->get_plugin_prefix();
	}

	/**
		@brief		Return the shortcode function name.
		@details	The default is shortcode_PLUGINPREFIX
		@since		2017-03-07 22:27:51
	**/
	public function get_shortcode_function()
	{
		$name = $this->get_plugin_prefix();
		$name = 'shortcode_' . $name;
		return $name;
	}

	/**
		@brief		Replace the shortcodes in this text.
		@since		2017-02-24 23:54:27
	**/
	public function replace_shortcodes( $text, $replacements )
	{
		$prefix = $this->get_plugin_prefix();
		foreach( $replacements as $find => $replacement )
			$text = str_replace( '[' . $prefix . '_' . $find . ']', $replacement, $text );
		return $text;
	}
}
