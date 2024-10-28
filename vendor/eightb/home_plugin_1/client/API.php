<?php

namespace eightb\home_plugin_1\client;

use \Exception;

class API
{
	/**
		@brief		The main plugin instance.
		@details	This key is filled in when the class is created.
		@since		2017-03-09 22:45:19
	**/
	public $plugin;

	/**
		@brief		The key to be used in the API calls.
		@details	This key is filled in when the class is created.
		@since		2017-02-01 23:07:07
	**/
	public $key = '';

	/**
		@brief		Execute an API call and return the json decoded result as an array.
		@since		2017-02-01 23:08:10
	**/
	public function api_call( $data )
	{
		$defaults = [
			'action' => 'status',
			'key' => $this->key,
		];

		$data = array_merge( $defaults, $data );

		$hv = json_encode( $data );
		$hv = base64_encode( $hv );
		$url = add_query_arg( $this->get_api_query_key(), $hv, static::get_url() );

		// Make the request wait a long time.
		add_filter( 'http_request_timeout', function()
		{
			return 30;	// seconds
		} );

		$r = wp_remote_get( $url );

		if ( ! $r )
			throw new Exception( 'Unable to communicate with the API server.' );

		if ( is_wp_error( $r ) )
		{
			$e = reset( $r->errors );
			throw new Exception( reset( $e ) );
		}

		$data = $r[ 'body' ];

		$json = json_decode( $data );
		if ( ! is_object( $json ) )
			throw new Exception( 'Did not receive a correct reply from the API server.' );

		if ( $json->error != '' )
			throw new Exception( $json->error );

		return $json;
	}

	/**
		@brief		Store an access key, allowing only the server that knows our key to contact us.
		@since		2017-03-19 21:31:01
	**/
	public function generate_transient_access_key()
	{
		$transient_key = $this->plugin->get_api_init_key();

		$old_access_key = get_transient( $transient_key );

		if ( ! $old_access_key )
			$access_key = md5( microtime() . AUTH_KEY );
		else
			$access_key = $old_access_key;

		set_transient( $transient_key, $access_key, 30 );		// If we don't get an answer in 30 seconds, then something is terribly wrong.

		return $access_key;
	}

	/**
		@brief		Return the API query key.
		@details	The default is the plugin prefix.
		@todo		Remove this sometime in the future after all of the old plugin versions are gone.
		@since		2017-03-09 22:49:28
	**/
	public function get_api_query_key()
	{
		return $this->plugin->get_plugin_prefix();
	}

	/**
		@brief		Attempt to retrieve, and set, the API data from the request.
		@since		2017-02-03 20:05:01
	**/
	public function maybe_process_api_call( $data )
	{
		if ( ! is_string( $data ) )
			return;

		$data = base64_decode( $data );
		$data = json_decode( $data );
		if ( $data === null )
			return $this->plugin->debug( 'Invalid API call data.' );

		$this->plugin->debug( 'API call data: %s', $data );

		// Check the access key.
		if ( ! isset( $data->access_key ) )
			return $this->plugin->debug( 'No access key!' );

		// Does the data contain our secret key?
		$transient_key = $this->plugin->get_api_init_key();
		$access_key = get_transient( $transient_key );
		if ( $data->access_key != $access_key )
			return $this->plugin->debug( 'Invalid access key!' );

		$this->process_api_call( $data );
	}

	/**
		@brief		Handle the API call.
		@details	This is called after the call is validated.
		@since		2017-03-09 23:21:47
	**/
	public function process_api_call( $data )
	{
		// Override me!
	}
}
