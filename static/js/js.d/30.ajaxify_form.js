/**
	@brief		Ajaxify a form in the content div.
	@details	When the form is sent, the 8b he div is given the css class "busy".
	@since		2016-12-23 18:44:35
**/
jQuery(function($)
{
	$.fn.extend(
	{
		eightb_sa_ajaxify_form : function()
		{
			return this.each(function ()
			{
				var $$ = $(this);

				var $div = $( '.8b_sold_alerts' );

				/**
					@brief		Send the form content via ajax, and then replace the page contents with the new contents.
					@since		2016-12-23 19:21:17
				**/
				$$.send_via_ajax = function()
				{
					$.post( {
						'data': $$.serialize(),
						'type': $$.attr('method'),
						'url': $$.attr('action')
					} )
					.done( function( data )
					{
						// Replace the content with the new content.
						var $data = $( data );
						var $new_div = $( '.8b_sold_alerts', $data );
						$div.html( $new_div.html() );
						$div.removeClass( 'busy' );
						// And restart all javascript.
						eightb_sold_alerts();
					} );
				}

				$$.submit( function( e )
				{
					e.preventDefault();
					$div.addClass( 'busy' );
					$$.send_via_ajax();
				} );
			});
		}
	});
}(jQuery));
