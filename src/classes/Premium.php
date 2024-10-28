<?php

namespace eightb\sold_alerts\classes;

/**
	@brief		Base class for the premium plugin.
	@since		2017-02-01 14:51:51
**/
class Premium
	extends \plainview\sdk_eightb_sold_alerts\wordpress\base
{
	/**
		@brief		The URL of the purchase and update server.
		@since		2017-02-05 22:25:05
	**/
	public static $server_url = 'https://soldalertsplugin.com';
}
