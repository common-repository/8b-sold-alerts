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
;
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
;
/**
	@brief		Handle the ask for address page.
	@since		2016-12-11 20:18:44
**/
jQuery(function($)
{
	$.fn.extend(
	{
		eightb_sa_ask_for_address : function()
		{
			return this.each(function ()
			{
				var $$ = $(this);

				var $address = $( 'input.address', $$ );
				var $found_address = $( '.found_address', $$ );

				$$.message = $( ".message", $$ );
				$$.message.hide();

				autocomplete = new google.maps.places.Autocomplete
				(
					$address[ 0 ],
					{
						types: ['geocode'],
						componentRestrictions: { country: 'us' }
					}
				);

				google.maps.event.addListener(autocomplete, 'place_changed', function()
				{
					var found_parts = {};
					var needed_parts = {
						0 : 'street_number',
						1 : 'route',
						2 : 'postal_code',
					};

					var place = this.getPlace();
					var components = place.address_components;

					for ( var part_number in needed_parts )
					{
						var part_key = needed_parts[ part_number ];

						for ( var counter = 0; counter < components.length ; counter++ )
							if ( components[ counter ].types[ 0 ] == part_key )
								found_parts[ part_key ] = components[ counter ].long_name;
					}

					var found_address = found_parts[ 'street_number' ] + ' ' + found_parts[ 'route' ] + ';' + found_parts[ 'postal_code' ];
					found_address += ';' + place.geometry.location.lat() + ';' + place.geometry.location.lng();
					$found_address.val( found_address );

					$$.message.hide();

					// Search for this address.
					$.ajax( {
						'data' : {
							'address' : found_address,
							'action' : eightb_sold_alerts_data.action,
							'nonce' : eightb_sold_alerts_data.nonce,
							'type' : 'count_sales'
						},
						'dataType' : 'json',
						'type' : 'post',
						'url' : eightb_sold_alerts_data.ajaxurl
					} )
					.done( function( data )
					{
						if ( data.sales < 1 )
							return;
						// Set the count.
						$( '.sales_count', $$.message ).html( data.sales );
						$$.message.show();
					} )
					.fail( function( data )
					{
					} );
				} );

			});
		}
	});
}(jQuery));
;
/**
	@brief		Convert the form fieldsets in a form2 table to ajaxy tabs.
	@since		2015-07-11 19:47:46
**/
;(function( $ )
{
    $.fn.extend(
    {
        plainview_form_auto_tabs : function()
        {
            return this.each( function()
            {
                var $this = $(this);

                if ( $this.hasClass( 'auto_tabbed' ) )
                	return;

                $this.addClass( 'auto_tabbed' );

				var $fieldsets = $( 'div.fieldset', $this );
				if ( $fieldsets.length < 1 )
					return;

				$this.prepend( '<div style="clear: both"></div>' );
				// Create the "tabs", which are normal Wordpress tabs.
				var $subsubsub = $( '<ul class="subsubsub">' )
					.prependTo( $this );

				$.each( $fieldsets, function( index, item )
				{
					var $item = $(item);
					var $h3 = $( 'h3.title', $item );
					var $a = $( '<a href="#">' ).html( $h3.html() );
					$h3.remove();
					var $li = $( '<li>' );
					$a.appendTo( $li );
					$li.appendTo( $subsubsub );

					// We add a separator if we are not the last li.
					if ( index < $fieldsets.length - 1 )
						$li.append( '<span class="sep">&emsp;|&emsp;</span>' );

					// When clicking on a tab, show it
					$a.click( function()
					{
						$( 'li a', $subsubsub ).removeClass( 'current' );
						$(this).addClass( 'current' );
						$fieldsets.hide();
						$item.show();
					} );

				} );

				$( 'li a', $subsubsub ).first().click();
            } ); // return this.each( function()
        } // plugin: function()
    } ); // $.fn.extend({
} )( jQuery );
;
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
;
