<?php

namespace eightb\home_plugin_1\client;

/**
	@brief		Handle all e-mailing
	@since		2017-03-05 14:20:12
**/
trait email_trait
{
	/**
		@brief		Send this lead to the specified e-mails.
		@since		2016-12-09 22:10:23
	**/
	public function send_lead( $lead, $shortcodes )
	{
		$mail = $this->mail();

		$sender_email = $this->get_local_or_site_option( 'email_new_lead_sender_email' );
		if ( $sender_email == '' )
			// Send from the admin
			$sender_email = get_option( 'admin_email', true );
		$sender_name = $this->get_local_or_site_option( 'email_new_lead_sender_name' );
		$mail->from( $sender_email, $sender_name );

		$recipients = $this->get_local_or_site_option( 'email_new_lead_recipients' );
		// Allow shortcodes in the recipients.
		$recipients = do_shortcode( $recipients );
		$recipients = $this->string_to_emails( $recipients );

		foreach( $recipients as $rec )
			$mail->to( $rec );

		$texts = [];
		$texts[ 'subject' ] = $this->get_local_or_site_option( 'email_new_lead_subject' );
		$texts[ 'text' ] = $this->get_local_or_site_option( 'email_new_lead_text' );


		// Replace lead shortcodes.
		foreach( $texts as $index => $string )
			$texts[ $index ] = $this->replace_shortcodes( $texts[ $index ], $shortcodes );

		/**
			@brief		Compatibility for version 2.1 that does not have properly prefixed lead shortcodes.
			@since		2017-02-28 19:04:00
		**/
		foreach( $shortcodes as $key => $value )
			foreach( $texts as $index => $string )
				$texts[ $index ] = str_replace( '[' . $key . ']', $value, $texts[ $index ] );

		$mail->subject( $texts[ 'subject' ] );
		$mail->html( $texts[ 'text' ] );

		$mail->send();
		return $mail->send_ok;
	}
}
