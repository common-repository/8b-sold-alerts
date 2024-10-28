<?php
/*
Author:			8blocks
Author Email:	support@8blocks.com
Author URI:		https://soldalertsplugin.com
Description:	Sold Alerts plugin.
Plugin Name:	8b Sold Alerts
Plugin URI:		https://plainviewplugins.com/
Version:		1.0
*/

DEFINE( 'EIGHTB_SOLD_ALERTS_PLUGIN_VERSION', 1.0 );

require_once( __DIR__ . '/vendor/autoload.php' );

/**
	@brief		Return the instance of the plugin.
	@since		2016-12-09 19:23:38
**/
function EightB_Sold_Alerts()
{
	return eightb\sold_alerts\Sold_Alerts::instance();
}

new eightb\sold_alerts\Sold_Alerts();
