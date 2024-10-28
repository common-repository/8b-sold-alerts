<?php

namespace eightb\sold_alerts;

use \Exception;

/**
	@brief		All admin menu functions.
	@since		2016-12-09 20:29:44
**/
trait admin_menu_trait
{
	/**
		@brief		network_admin_menu_settings
		@since		2016-12-09 20:32:03
	**/
	public function network_admin_menu_settings()
	{
		$get = 'get_local_option';
		$set = 'update_local_option';

		if ( is_network_admin() )
		{
			$get = 'get_site_option';
			$set = 'update_site_option';
		}

		$form = $this->form();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$fs = $form->fieldset( 'fs_api_settings' );
		$fs->legend->label_( 'API settings' );

		if ( $this->show_network_settings() )
		{
			$sold_alerts_api_key = $fs->text( 'sold_alerts_api_key' )
				->description_( "This key is used to retrieve the listing from the Sold Alerts server. Use the checkbox below to generate or retrieve a previously generated key for this Wordpress installation. The key is attached to the domain name of this server." )
				->label_( 'Sold Alerts API key' )
				->size( 64 )
				->value( $this->get_api_key() );

			$generate_sold_alerts_api_key = $fs->checkbox( 'generate_sold_alerts_api_key' )
				->checked( $this->$get( 'sold_alerts_api_key' ) == '' )
				->description_( 'Check & save to generate a new or retrieve your existing Sold Alerts API key.' )
				->label_( 'Generate or Retrieve key' );

			// Only show the renew info if there is a key.
			if ( $this->get_api_key() != '' )
			{
				$text = $this->get_text_file( 'api_key_info' );
				$text = $this->replace_api_text( $text, [ 'form' => false ] );
			}
			else
			{
				$text = $this->get_text_file( 'api_key_info_no_key' );
			}

			$fs->markup( 'm_sa_api_key_info' )
				->p( $text );

			$test_sold_alerts = $fs->secondary_button( 'test_sold_alerts' )
				->value_( 'Use after saving: test the Sold Alerts API key' );

		}
		else
		{
			$url = network_admin_url( 'settings.php?page=8b_sold_alerts');
			$fs->markup( 'm_api_for_network' )
				->p_( 'Please visit the <a href="%s">Sold Alerts network settings</a> page to configure your API keys.', $url );
		}

		$fs = $form->fieldset( 'fs_general_settings' );
		$fs->legend->label_( 'General settings' );

		// Only network admins are allowed to lead pool.
		if ( is_network_admin() )
		{
			$lead_pool_blog = $fs->select( 'lead_pool_blog' )
				->value( $this->$get( 'lead_pool_blog' ) )
				->label_( 'Lead pool blog' )
				->option( 'Lead pooling disabled', 0 )
				->required();

			// Because the desc contains html, we need to handle it the long way.
			$description = $this->_( 'To which blog will all leads automatically be pooled. This function requires the %sfree Broadcast plugin%s.',
				'<a href="https://wordpress.org/plugins/threewp-broadcast/">',
				'</a>'
				);
			$lead_pool_blog->description->label->content = $description;

			if ( function_exists( 'ThreeWP_Broadcast' ) )
			{
				$blogs = get_sites( [
					'number' => PHP_INT_MAX,
				] );
				foreach( $blogs as $blog )
				{
					$details = get_blog_details( $blog->blog_id );
					$label = sprintf( '%s (%s)', $details->blogname, $blog->blog_id );
					$lead_pool_blog->option( $label, $blog->blog_id );
				}
			}
		}

		$create_shortcode = $fs->checkbox( 'create_shortcode' )
			->description_( 'Use this checkbox to create a new page with the [%s] shortcode on it.', $this->get_plugin_prefix() )
			->label( 'Create shortcode on new page' );

		$display_email_log = $fs->checkbox( 'display_email_log' )
			->checked( $this->$get( 'display_email_log' ) )
			->description_( 'Display the Sold Alerts email log menu item.' )
			->label( 'Display email log' );

		$load_css = $fs->checkbox( 'load_css' )
			->checked( $this->$get( 'load_css' ) )
			->description_( "Load the plugin's own CSS for the front-end, or disable to style the form yourself." )
			->label_( 'Load plugin CSS' );

		$fs = $form->fieldset( 'fs_new_lead_email' );
		$fs->legend->label_( 'Lead Email' );

		$fs->markup( 'm_new_lead_email_text' )
			->p_( 'These are the settings for the email sent when a new lead is created.' );

		$email_new_lead_sender_email = $fs->email( 'email_new_lead_sender_email' )
			->description_( 'Send the email from this email address. Note that this value may be restricted by your webhost.' )
			->label_( 'Sender Email' )
			->size( 64 )
			->value( $this->$get( 'email_new_lead_sender_email' ) );

		$email_new_lead_sender_name = $fs->text( 'email_new_lead_sender_name' )
			->description_( 'Send the email with this sender name.' )
			->label_( 'Sender name' )
			->size( 64 )
			->value( $this->$get( 'email_new_lead_sender_name' ) );

		$email_new_lead_recipients = $fs->textarea( 'email_new_lead_recipients' )
			->description_( 'To which email addresses shall new leads be sent? One email address per line. Shortcodes allowed.' )
			->label_( 'New lead recipients' )
			->placeholder( "email@address.com" )
			->rows( 5, 40 )
			->value( $this->$get( 'email_new_lead_recipients' ) );

		$email_new_lead_subject = $fs->text( 'email_new_lead_subject' )
			->description_( 'Subject of the new lead email. Valid shortcodes are [8b_sold_alerts_first_name], [8b_sold_alerts_last_name] & [8b_sold_alerts_email].' )
			->label_( 'New lead subject' )
			->size( 64 )
			->value( $this->$get( 'email_new_lead_subject' ) );

		$email_new_lead_text = $fs->wp_editor( 'email_new_lead_text' )
			->description_( 'This is the text of the email for new leads that is sent to the new lead email recipients. Valid shortcodes are [8b_sold_alerts_first_name], [8b_sold_alerts_last_name], [8b_sold_alerts_email] & [8b_home_value_searched_address].' )
			->label_( 'New lead Email' )
			->rows( 10 )
			->set_unfiltered_value( $this->$get( 'email_new_lead_text' ) );

		$fs = $form->fieldset( 'fs_sales_email' );
		$fs->legend->label_( 'Sold Alerts Email' );

		$fs->markup( 'm_sales_email_text' )
			->p_( 'These are the settings for the email sent showing the subscriber their sold alerts.' );

		$email_sales_sender_email = $fs->email( 'email_sales_sender_email' )
			->description_( 'Send the email from this email address. Note that this value may be restricted by your webhost.' )
			->label_( 'Sender Email' )
			->size( 64 )
			->value( $this->$get( 'email_sales_sender_email' ) );

		$email_sales_sender_name = $fs->text( 'email_sales_sender_name' )
			->description_( 'Send the email with this sender name.' )
			->label_( 'Sender name' )
			->size( 64 )
			->value( $this->$get( 'email_sales_sender_name' ) );

		$email_sales_recipients = $fs->textarea( 'email_sales_recipients' )
			->description_( 'To which email addresses shall sold alerts also be sent, in addition to the subscriber? One email address per line. Shortcodes allowed.' )
			->label_( 'Sold Alerts copies' )
			->placeholder( "email@address.com" )
			->rows( 5, 40 )
			->value( $this->$get( 'email_sales_recipients' ) );

		$email_sales_subject = $fs->text( 'email_sales_subject' )
			->description_( 'Subject of the Sold Alerts email. Valid shortcodes are [8b_sold_alerts_first_name], [8b_sold_alerts_last_name] &[8b_sold_alerts_email].' )
			->label_( 'Sold Alerts subject' )
			->size( 64 )
			->value( $this->$get( 'email_sales_subject' ) );

		$email_sales_text = $fs->wp_editor( 'email_sales_text' )
			->description_( 'This is the text of the email for sold alerts that is sent to the subscriber. Valid shortcodes are [8b_sold_alerts_first_name], [8b_sold_alerts_last_name], [8b_sold_alerts_email], [8b_sold_alerts_data_size], [8b_sold_alerts_data_beds] and [8b_sold_alerts_data_baths].' )
			->label_( 'Sold Alerts Email' )
			->rows( 10 )
			->set_unfiltered_value( $this->$get( 'email_sales_text' ) );

		$fs = $form->fieldset( 'fs_texts' );
		$fs->legend->label_( 'Texts' );

		$thank_you_text= $fs->wp_editor( 'thank_you_text' )
			->description_( 'This text is shown to the user after subscription.' )
			->label_( 'Thank you text' )
			->rows( 10 )
			->set_unfiltered_value( $this->$get( 'thank_you_text' ) );

		// Remove the "No text" and replace them with empty values.
		foreach( $form->inputs() as $input )
			if ( $input->get_value() == 'No text' )
				$input->value( '' );

		// --DEBUG--------------------------------------------------------------------------------------------------

		$this->add_debug_settings_to_form( $form );

		$save = $form->primary_button( 'save' )
			->value_( 'Save settings' );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			if ( $save->pressed() )
			{
				if ( is_network_admin() )
				{
					foreach( [
						'lead_pool_blog',
					] as $key )
						$this->update_site_option( $key, $$key->get_post_value() );
				}

				if ( $this->show_network_settings() )
				{
					$old_api_key = $this->get_api_key();
					foreach( [
						'sold_alerts_api_key',
					] as $key )
						$this->update_site_option( $key, $$key->get_post_value() );
				}

				// The checkbox should override the manual api key.
				if ( isset( $generate_sold_alerts_api_key ) )
				{
					if ( $generate_sold_alerts_api_key->is_checked() )
					{
						try
						{
							$data = $this->get_api()->generate();

							$r .= $this->info_message_box()
								->_( $data->message );

							// WP caches site options per request. The new API key is received in a different request.
							// We must clear our cache in order to get the new API key.
							$this->clear_site_option_cache( [ 'sold_alerts_api_key', 'google_api_key', 'subscriber_count', 'subscriber_max' ] );
						}
						catch ( Exception $e )
						{
							$r .= $this->error_message_box()
								->_( 'Unable to generate or retrieve your Sold Alerts API key: %s', $e->getMessage() );
						}
					}
					else
					{
						// New API key inputted? Refresh the status.
						try
						{
							$new_api_key = $this->get_api_key();
							if ( $new_api_key != $old_api_key )
								if ( $new_api_key != '' )
									$this->get_api()->status();
						}
						catch( Exception $e )
						{
						}
					}
				}

				if ( $create_shortcode->is_checked() )
				{
					$page_id = wp_insert_post( [
						'post_title' => 'Sold Alerts',
						'post_content' => '[8b_sold_alerts]',
						'post_type' => 'page',
						'post_status' => 'publish',
					] );
					$r .= $this->info_message_box()
						->_( '<a href="%s">A page containing the shortcode</a> has been created.', get_permalink( $page_id ) );
				}

				foreach( [
					'display_email_log',
					'email_new_lead_recipients',
					'email_new_lead_sender_email',
					'email_new_lead_sender_name',
					'email_new_lead_subject',
					'email_new_lead_text',
					'email_sales_sender_email',
					'email_sales_sender_name',
					'email_sales_recipients',
					'email_sales_text',
					'email_sales_subject',
					'load_css',
					'thank_you_text',
				] as $key )
				{
					$this->$set( $key, $$key->get_post_value() );
				}

				$this->save_debug_settings_from_form( $form );

				$r .= $this->info_message_box()
					->_( 'Saved!' );
			}

			if ( $this->show_network_settings() )
			{
				if ( $test_sold_alerts->pressed() )
				{
					try
					{
						$data = $this->get_api()->status();
						$r .= $this->info_message_box()
							->_( 'Your key seems valid and you have %s of a maximum %s subscribers available.',
								intval( $data->subscriber_count ),
								intval( $data->subscriber_max )
							);
					}
					catch ( Exception $e )
					{
						$r .= $this->error_message_box()
							->_( 'Sold Alerts API key test failure: %s', $e->getMessage() );
					}
				}
			}

			$_POST = [];
			echo $r .= $this->network_admin_menu_settings();
			return;
		}

		if ( is_network_admin() )
			$r .= $this->p_( 'These are the global settings. Each blog has the possibility of specifying their own settings, but if a setting or a text is not found locally, it will be taken from the global settings.' );

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}
}
