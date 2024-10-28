<?php

namespace eightb\sold_alerts\classes;

/**
	@brief		The Sold Alerts API for searches and other things.
	@since		2017-02-01 22:58:36
**/
class API
	extends \eightb\home_plugin_1\client\API
{
	/**
		@brief		Ask for a sales count for an address.
		@since		2017-04-02 14:11:51
	**/
	public function count_sales( $data )
	{
		$data = (object)$data;
		$r = $this->api_call( [
			'action' => 'count_sales',
			'prune' => false,		// All, not just 30 days.
			'street' => $data->street,
			'zip' => $data->zip,
		] );

		$this->update_plugin_status( $r );

		return $r;
	}

	/**
		@brief		Accept the validated API data from the server.
		@since		2017-03-09 22:48:22
	**/
	public function process_api_call( $data )
	{
		$this->plugin->debug( $data );

		switch( $data->call_type )
		{
			case 'new_api_data':
				$this->plugin->update_site_option( 'sold_alerts_api_key', $data->sold_alerts_api_key );
				$this->plugin->update_site_option( 'google_api_key', $data->google_api_key );
			break;
		}
	}

	/**
		@brief		Ask the server to generate a new (or retrieve our existing) key.
		@since		2017-02-03 19:49:34
	**/
	public function generate()
	{
		// The e-mail address of the admin user.
		$user = wp_get_current_user();
		$email = $user->data->user_email;

		$access_key = $this->generate_transient_access_key();

		$data = [
			'access_key' => $access_key,
			'action' => 'create_key',
			'email' => $email,
			'url' => wp_login_url(),		// This is where the new key will be sent.
		];

		return $this->api_call( $data );
	}

	/**
		@brief		Return the API url.
		@details	Based on the Premium server URL.
		@since		2017-02-05 22:33:00
	**/
	public static function get_url()
	{
		return Premium::$server_url . '/api';
	}

	/**
		@brief		Retrieve and send the sales to this lead.
		@since		2017-03-19 12:42:58
	**/
	public function send_sales( $lead, $options = [] )
	{
		$r = $this->api_call( [
			'action' => 'send_sales',
			'email' => $lead->meta->lead_email,
			'street' => $lead->meta->lead_street,
			'zip' => $lead->meta->lead_zip,
			'options' => $options,
		] );

		$email_text = $this->plugin->get_local_or_site_option( 'email_sales_text' );

		$replacements = $lead->get_shortcodes();

		$sales_text = '';
		foreach( (array)$r->sales as $sale )
		{
			$dir = dirname( __DIR__ );
			$dir = dirname( $dir );
			$sale_text = file_get_contents( $dir . '/views/email_sales_sale.html' );
			$replacements = array_merge( $replacements, $this->plugin->sale_to_shortcodes( $sale ) );
			$sale_text = $this->plugin->replace_shortcodes( $sale_text, $replacements );
			$sales_text .= $sale_text;
		}
		$replacements[ 'sales' ] = $sales_text;
		$replacements[ 'unsubscribe_url' ] = $lead->get_unsubscribe_url();
		$email_text = $this->plugin->replace_shortcodes( $email_text, $replacements );

		$subject = $this->plugin->get_local_or_site_option( 'email_sales_subject' );
		$subject = $this->plugin->replace_shortcodes( $subject, $replacements );

		$sale_count_text = _n( '1 sale', '%d sales', count( (array)$r->sales ) );
		$sale_count_text = sprintf( $sale_count_text, count( (array)$r->sales ) );

		// Generate an e-mail with this info.
		$entry = $this->plugin->email_entry()
			->set( 'post_title', sprintf( '%s near %s, %s', $sale_count_text, $lead->meta->lead_street, $lead->meta->lead_zip ) )
			->set( 'post_content', $email_text )
			->set_meta( 'subject', $subject )
			->save()
			->set_email( $lead->meta->lead_email );

		// Send it!
		$result = $this->plugin->send_email_log( $entry );
		$entry->set_meta( 'send_ok', intval( $result ) )
			->save();

		// Only update the last send if the mail was successful.
		if ( $result )
		{
			$lead->set_meta( 'lead_last_send', time() )
				->save();
		}

		$this->update_plugin_status( $r );
	}

	/**
		@brief		Ask for a status of the key.
		@since		2017-02-01 23:06:44
	**/
	public function status()
	{
		$r = $this->api_call( [
			'action' => 'status',
		] );
		$this->update_plugin_status( $r );
		return $r;
	}

	/**
		@brief		Update the license status.
		@since		2017-03-21 22:14:33
	**/
	public function update_plugin_status( $data )
	{
		$this->plugin->update_site_option( 'subscriber_count', $data->subscriber_count );
		$this->plugin->update_site_option( 'subscriber_max', $data->subscriber_max );
		$this->plugin->clear_site_option_cache( 'subscriber_count', 'subscriber_max' );
	}
}
