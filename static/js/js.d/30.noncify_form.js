/**
	@brief		Use JS to add the nonce to the form.
	@since		2016-12-23 19:29:36
**/
jQuery(function($)
{
	$.fn.extend(
	{
		eightb_sa_noncify_form : function()
		{
			return this.each(function ()
			{
				var $$ = $(this);

				// Put the nonce into the form.
				$( '<input>' )
					.prop( 'type', 'hidden' )
					.prop( 'name', '8b_sold_alerts[nonce]' )
					.val( eightb_sold_alerts_data.nonce )
					.appendTo( $$ );
			});
		}
	});
}(jQuery));
