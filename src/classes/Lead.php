<?php

namespace eightb\sold_alerts\classes;

/**
	@brief		Lead custom post type.
	@since		2016-12-12 21:26:16
**/
class Lead
	extends \eightb\home_plugin_1\client\Lead
{
	// Use the post storage trait.
	use \plainview\sdk_eightb_sold_alerts\wordpress\object_stores\Post;

	/**
		@brief		Allow subclasses to construct.
		@since		2017-03-22 20:11:25
	**/
	public function _construct()
	{
		$this->meta->lead_status = 'active';
		$this->meta->lead_last_send = 0;
	}

	/**
		@brief		Can this lead be deleted or must we wait until the last_send timeout kicks in?
		@since		2017-03-21 14:43:54
	**/
	public function can_be_deleted()
	{
		return $this->meta->lead_last_send < ( time() - MONTH_IN_SECONDS );
	}

	/**
		@brief		Return the info column text for this lead.
		@since		2017-03-21 14:35:30
	**/
	public function get_info_column()
	{
		$r = '';

		// Status first.
		$r .= '<div class="status">';

		if ( $this->meta->lead_status == 'active' )
			$r .= 'Active';
		else
		{
			if ( $this->can_be_deleted() )
				$r .= 'Inactive. Can be deleted.';
			else
			{
				$seconds_until_delete = $this->meta->lead_last_send + MONTH_IN_SECONDS;
				$difference = $seconds_until_delete - time();
				$difference = $difference / DAY_IN_SECONDS;
				$difference = intval( $difference );
				$r .= sprintf( 'Inactive. Can be deleted in %s days.', $difference );
			}
		}

		$r .= '</div>';

		$r .= parent::get_info_column();

		$r .= '<div class="position">';
		$r .= $this->meta->lead_street . ' ' . $this->meta->lead_zip;
		$r .= '</div>';

		return $r;
	}

	/**
		@brief		Return the main plugin instance.
		@since		2017-03-07 22:04:13
	**/
	public function get_plugin()
	{
		return EightB_Sold_Alerts();
	}

	/**
		@brief		Return an array of all of the special meta keys we use.
		@since		2016-12-12 21:38:11
	**/
	public function get_meta_keys()
	{
		return array_merge( parent::get_meta_keys(), [ 'lead_street', 'lead_zip', 'lead_last_send', 'lead_status' ] );
	}

	/**
		@brief		Return the post type name.
		@since		2016-12-12 21:46:04
	**/
	public static function get_post_type()
	{
		return '8b_sa_lead';
	}

	/**
		@brief		Return the unsubscribe key.
		@since		2017-03-19 19:06:14
	**/
	public function get_unsubscribe_key()
	{
		// Those two values are never changed.
		$key = $this->meta->lead_street . $this->meta->lead_zip;
		$key = md5( $key );
		return $key;
	}

	/**
		@brief		Return the unsubscribe URL.
		@since		2017-03-27 13:45:16
	**/
	public function get_unsubscribe_url()
	{
		$url = home_url( add_query_arg( null, null ) );
		$url = add_query_arg( 'unsubscribe', $this->id, $url );
		$url = add_query_arg( 'key', $this->get_unsubscribe_key(), $url );
		return $url;
	}

	/**
		@brief		Convenience method to set the lead as active.
		@since		2017-03-21 14:53:10
	**/
	public function set_active()
	{
		$this->set_status( 'active' );
	}

	/**
		@brief		Convenience method to set the lead as inactive.
		@since		2017-03-21 14:53:10
	**/
	public function set_inactive()
	{
		$this->set_status( 'inactive' );
	}

	/**
		@brief		Set the status of this lead.
		@since		2017-03-21 14:52:40
	**/
	public function set_status( $new_status )
	{
		$this->meta->lead_status = $new_status;
		$this->save_meta();
	}
}
