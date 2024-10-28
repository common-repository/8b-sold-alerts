/**
	@brief		Initilialize all 8b SA js on the page.
	@since		2016-12-11 20:19:08
**/
eightb_sold_alerts = function()
{
	$( '.8b_sold_alerts .ask_for_address' ).eightb_sa_ask_for_address();
	$( '.8b_sold_alerts form' ).eightb_sa_ajaxify_form();
	$( '.8b_sold_alerts form' ).eightb_sa_noncify_form();
	$( 'form.plainview_form_auto_tabs' ).plainview_form_auto_tabs();
}

jQuery( document ).ready( function( jQuery )
{
	$ = jQuery;
	eightb_sold_alerts();
} );
