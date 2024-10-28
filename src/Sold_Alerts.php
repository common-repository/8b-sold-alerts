<?php

namespace eightb\sold_alerts;

use \Exception;

/**
	@brief		Sold Alerts plugin.
	@since		2016-12-09 20:28:13
**/
class Sold_Alerts
	extends \plainview\sdk_eightb_sold_alerts\wordpress\base
{
	use \eightb\home_plugin_1\client\base_trait;
	use admin_menu_trait;
	use shortcodes_trait;

	use \plainview\sdk_eightb_sold_alerts\wordpress\traits\debug;

	public $plugin_version = EIGHTB_SOLD_ALERTS_PLUGIN_VERSION;

	/**
		@brief		The options that we use. Combined local and site.
		@details	Note the "No text" values. Those help HV detect whether a lang file should be loaded from disk.
		@since		2016-12-25 17:12:10
	**/
	public static $options = [
		/**
			@brief		Displayt the e-mail log item in the admin menu.
			@since		2017-03-28
		**/
		'display_email_log' => false,

		/**
			@brief		List of e-mail recipients for new leads.
			@since		2016-12-25 14:47:38
		**/
		'email_new_lead_recipients' => '',
		/**
			@brief		The e-mail of the new lead e-mail sender.
			@since		2017-01-09 23:59:56
		**/
		'email_new_lead_sender_email' => '',
		/**
			@brief		The name of the new lead e-mail sender.
			@since		2017-01-09 23:59:56
		**/
		'email_new_lead_sender_name' => '',
		/**
			@brief		Subject line for new lead e-mails.
			@since		2016-12-25 14:50:41
		**/
		'email_new_lead_subject' => 'No text',
		/**
			@brief		Text for new lead e-mail.
			@since		2016-12-25 14:50:41
		**/
		'email_new_lead_text' => 'No text',

		/**
			@brief		The e-mail of the Sold Alerts e-mail sender.
			@since		2017-01-09 23:59:56
		**/
		'email_sales_sender_email' => '',
		/**
			@brief		The name of the Sold Alerts e-mail sender.
			@since		2017-01-09 23:59:56
		**/
		'email_sales_sender_name' => '',
		/**
			@brief		Text for an individual sale in the sales e-mail.
			@since		2017-03-24 14:58:34
		**/
		'email_sales_text' => 'No text',
		/**
			@brief		Text for the sales e-mail subject.
			@since		2017-03-24 14:58:34
		**/
		'email_sales_subject' => 'No text',

		/**
			@brief		The Google Places API key used to get the address from the form.
			@since		2016-12-11 19:59:14
		**/
		'google_api_key'	=> '',
		/**
			@brief		Which blog to send all of the leads.
			@since		2016-12-09 20:44:58
		**/
		'lead_pool_blog' => 0,
		/**
			@brief		Enqueue our css file?
			@since		2017-01-09 21:45:28
		**/
		'load_css' => true,
		/**
			@brief		Text shown to the user after subscription.
			@since		2017-04-04
		**/
		'thank_you_text' => 'No text',
		/**
			@brief		The SA API key, used to communicate with the server.
			@since		2017-02-01 22:14:16
		**/
		'sold_alerts_api_key' => '',
		/**
			@brief		How many subscribers the server thinks this user has.
			@since		2017-03-16 16:39:30
		**/
		'subscribers_count' => 0,
		/**
			@brief		How many subscribers the server thinks this user may have.
			@since		2017-03-16 16:39:30
		**/
		'subscribers_max' => 0,
	];

	/**
		@brief		Inserts our options into the database and handles replacements of text values.
		@since		2016-12-09 22:25:41
	**/
	public function activate_plugin()
	{
		parent::activate();

		wp_schedule_event( time(), 'hourly', 'sold_alerts_cron_hourly' );
	}

	/**
		@brief		Inserts our options into the database and handles replacements of text values.
		@since		2016-12-09 22:25:41
	**/
	public function deactivate()
	{
		parent::deactivate();

		wp_clear_scheduled_hook( 'sold_alerts_cron_hourly' );
	}

	/**
		@brief		Create a new email entry.
		@since		2017-03-23 21:31:25
	**/
	public function email_entry()
	{
		return new email\Entry();
	}

	/**
		@brief		Return the proper plugin name.
		@since		2017-03-05 14:25:05
	**/
	public function full_plugin_name()
	{
		return '8b Sold Alerts';
	}

	/**
		@brief		Return an instance of the API.
		@since		2017-03-07 22:35:13
	**/
	public function get_api()
	{
		if ( isset( $this->__sold_alerts_api ) )
			return $this->__sold_alerts_api;

		$this->__sold_alerts_api = new classes\API();
		$this->__sold_alerts_api->plugin = $this;
		$this->__sold_alerts_api->key = $this->get_api_key();
		return $this->__sold_alerts_api;
	}

	/**
		@brief		Convenience function to return the HV api key.
		@details	Used by the premium plugin to self-activate.
		@since		2017-02-09 22:29:53
	**/
	public function get_api_key()
	{
		return $this->get_site_option( 'sold_alerts_api_key' );
	}

	/**
		@brief		Return an instance of the Lead class.
		@since		2017-03-09 22:01:19
	**/
	public function get_lead()
	{
		return new classes\Lead();
	}

	/**
		@brief		Return the name of the JS variable where we keep our localization data.
		@since		2017-03-07 22:19:51
	**/
	public function get_localize_script_variable()
	{
		return 'eightb_sold_alerts_data';
	}

	/**
		@brief		Return the prefix used for shortcodes.
		@since		2017-03-05 11:33:05
	**/
	public function get_plugin_prefix()
	{
		return '8b_sold_alerts';
	}

	/**
		@brief		Init the plugin.
		@since		2017-03-19 17:01:16
	**/
	public function init_plugin()
	{
		$this->add_action( 'sold_alerts_cron_hourly' );
		$this->add_filter( 'pre_delete_post', 10, 3 );
		$this->add_action( 'untrash_post' );
		$this->add_action( 'wp_trash_post', 'trash_post' );		// Use trash_post for the sake of consistency.

		$this->add_action( 'wp_ajax_8b_sold_alerts', 'wp_ajax' );
		$this->add_action( 'wp_ajax_nopriv_8b_sold_alerts', 'wp_ajax' );

		$this->add_action( 'init', 'init_sa' );
	}

	/**
		@brief		Custom init hook.
		@since		2017-03-24 00:19:51
	**/
	public function init_sa()
	{
		$this->email_entry()->register_post_type();
	}

	/**
		@brief		Check whether the post is allowed to be deleted.
		@since		2017-03-21 14:20:25
	**/
	public function pre_delete_post( $check, $post, $force_delete )
	{
		// null = no decision.
		if ( $check !== null )
			return;

		// We only care about leads.
		if ( $post->post_type != $this->get_lead()->get_post_type() )
			return $check;

		// We are not allowed to delete active leads.
		$lead = classes\Lead::load_from_store( $post->ID );
		if ( $lead->meta->lead_status == 'active' )
			return false;

		// If inactive, is the lead old enough to be deleted?
		if ( ! $lead->can_be_deleted() )
			return false;

		// Otherwise we are good to go.
		return $check;
	}

	/**
		@brief		General function to replace any SA API key info shortcodes in a text.
		@since		2017-02-09 21:43:36
	**/
	public function replace_api_text( $text, $options = [] )
	{
		$form = $this->form();
		$options = array_merge( [
			'form' => true,		// Add the form tags?
		], $options );

		$api_key = $this->get_api_key();

		$renewal_url = classes\Premium::$server_url . '/renew';
		$renewal_url = add_query_arg( 'key', $api_key, $renewal_url );

		$refresh_button = $form->primary_button( 'refresh_status' )
			->value( 'Refresh API status' );

		// While we're here, we might as well check the form for action.
		if ( $form->is_posting() )
		{
			$form->post();
			if ( $refresh_button->pressed() )
			{
				try
				{
					$status = $this->get_api()->status();
				}
				catch ( \Exception $e )
				{
					$text .= $this->error_message_box()
						->_( $e->getMessage() );
				}
				// Only process this once per request.
				$_POST = [];
			}
		}

		foreach( [
			'api_key' => $api_key,
			'refresh_button' => $refresh_button->display_input(),
			'renewal_link' => $renewal_url,
			'subscriber_count' => intval( $this->get_site_option( 'subscriber_count' ) ),
			'subscriber_max' => intval( $this->get_site_option( 'subscriber_max' ) ),
		] as $key => $value )
			$text = str_replace( '[' . $key . ']', $value, $text );

		$text = sprintf( "%s%s%s",
			( $options[ 'form' ] ? $form->open_tag() : '' ),
			$text,
			( $options[ 'form' ] ? $form->close_tag() : '' )
		);

		return $text;
	}

	/**
		@brief		Convert a sale data object to an array of shortcodes.
		@since		2017-03-24 15:51:05
	**/
	public function sale_to_shortcodes( $sale )
	{
		$r = [];

		$r[ 'data_address_city' ] = $sale->address->city;
		$r[ 'data_address_state' ] = $sale->address->state;
		$r[ 'data_address_street' ] = $sale->address->deliveryLine;
		$r[ 'data_address_zipcode' ] = $sale->address->zip;

		if ( isset( $sale->attributes->baths ) )
			$value = $sale->attributes->baths;
		else
			$value = 'n/a';
		$r[ 'data_baths' ] = $value;

		if ( isset( $sale->attributes->beds ) )
			$value = $sale->attributes->beds;
		else
			$value = 'n/a';
		$r[ 'data_beds' ] = $value;

		$value = $sale->attributes->lotSize->sqft;
		$r[ 'data_lotSizeSqFt' ] = $this->number_format( $value );
		$value = $sale->attributes->size;
		$r[ 'data_size' ] = $this->number_format( $value );

		$r[ 'data_address' ] = sprintf( '%s, %s', $sale->address->street, $sale->address->city );

		$r[ 'data_sale_date' ] = date( 'F j, Y', $sale->attributes->saleDate );

		$r[ 'data_sale_price' ] = $this->number_format( $sale->attributes->salePrice );

		return $r;
	}

	/**
		@brief		Send this email log entry to the user.
		@since		2017-03-24 18:05:35
	**/
	public function send_email_log( $entry )
	{
		$mail = $this->mail();

		$sender_email = $this->get_local_or_site_option( 'email_sales_sender_email' );
		if ( $sender_email == '' )
			// Send from the admin
			$sender_email = get_option( 'admin_email', true );
		$sender_name = $this->get_local_or_site_option( 'email_sales_sender_name' );
		$mail->from( $sender_email, $sender_name );

		// The subscriber is always #1
		$email_address = $entry->get_email();
		$mail->to( $email_address );

		$recipients = $this->get_local_or_site_option( 'email_sales_recipients' );
		// Allow shortcodes in the recipients.
		$recipients = do_shortcode( $recipients );
		$recipients = $this->string_to_emails( $recipients );

		foreach( $recipients as $rec )
			$mail->bcc( $rec );

		$mail->subject( $entry->meta->subject );
		$mail->html( wpautop( $entry->post_content ) );

		$mail->send();
		return $mail->send_ok;
	}

	/**
		@brief		Find all leads / subscribers that should have their 30 days sales sent.
		@since		2017-03-19 17:04:07
	**/
	public function send_unsent_sales()
	{
		global $wpdb;

		$old = time() - MONTH_IN_SECONDS;

		$query = sprintf( "SELECT `post_id` FROM `%s` WHERE `meta_key` = 'lead_last_send' AND `meta_value` < %s",
			$wpdb->postmeta,
			$old );
		$results = $wpdb->get_results( $query );

		foreach( $results as $result )
		{
			try
			{
				$lead = classes\Lead::load_from_store( $result->post_id );
				$this->get_api()->send_sales( $lead );
			}
			catch ( Exception $e )
			{
			}
		}
	}

	/**
		@brief		Hourly cron.
		@since		2017-03-19 17:01:55
	**/
	public function sold_alerts_cron_hourly()
	{
		$this->send_unsent_sales();
	}

	/**
		@brief		Return the complete namespace and name of a subclass.
		@details	This is used by the plugin1 trait to create local classes from within the trait.
		@since		2017-03-10 00:14:12
	**/
	public function subclass( $extra )
	{
		return __NAMESPACE__ . $extra;
	}

	/**
		@brief		Trashing a lead? Mark as inactive.
		@since		2017-03-21 14:49:15
	**/
	public function trash_post( $post_id )
	{
		$lead = classes\Lead::load_from_store( $post_id );
		if ( ! $lead )
			return;
		$lead->set_inactive();
	}

	/**
		@brief		Untrashing a post marks it as active again.
		@since		2017-03-21 14:49:15
	**/
	public function untrash_post( $post_id )
	{
		$lead = classes\Lead::load_from_store( $post_id );
		if ( ! $lead )
			return;
		$lead->set_active();
	}

	/**
		@brief		wp_ajax
		@since		2017-04-02 14:03:16
	**/
	public function wp_ajax()
	{
		// Check the nonce.
		if ( ! isset( $_POST[ 'nonce' ] ) )
			return;

		// Check the nonce.
		$nonce_value = $_POST[ 'nonce' ];
		if ( ! wp_verify_nonce( $nonce_value, $this->get_nonce_key() ) )
			wp_die( 'Security check failed.' );

		switch( $_POST[ 'type' ] )
		{
			case 'count_sales':
				$address = sanitize_text_field( $_POST[ 'address' ] );
				$address = explode( ';', $address );
				if ( count( $address ) < 2 )	// Address, zip
					wp_die( 'Address incorrect.' );
				try
				{
					$r = $this->get_api()->count_sales( [
						'street' => $address[ 0 ],
						'prune' => false,
						'zip' => $address[ 1 ],
					] );
					echo json_encode( [
						'sales' => $r->sales,
					] );
					exit;
				}
				catch( Exception $e )
				{
					wp_die( 'Error: ' . $e->getMessage() );
				}
			break;
		}
	}
}
