<?php

namespace eightb\sold_alerts\actions;

class action
	extends \plainview\sdk_eightb_sold_alerts\wordpress\actions\action
{
	public function get_prefix()
	{
		return 'eightb_sold_alerts_';
	}
}
