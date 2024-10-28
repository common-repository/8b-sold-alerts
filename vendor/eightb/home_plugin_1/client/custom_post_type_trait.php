<?php

namespace eightb\home_plugin_1\client;

/**
	@brief		Handle the custom post type.
	@since		2017-03-05 12:10:55
**/
trait custom_post_type_trait
{
	/**
		@brief		Init the custom post type.
		@since		2016-12-09 22:16:52
	**/
	public function init_custom_post_types()
	{
		$this->add_action( 'threewp_broadcast_get_post_types' );

		// Hide the broadcasted column from non super-admins.
		if ( function_exists( 'ThreeWP_Broadcast' ) )
			if ( ! is_super_admin() )
				if ( isset( $_GET[ 'post_type' ] ) AND $_GET[ 'post_type' ] == $this->get_lead()->get_post_type() )
					ThreeWP_Broadcast()->display_broadcast_columns = false;

		$this->add_filter( 'manage_posts_columns', 10, 2 );
		$this->add_action( 'manage_posts_custom_column', 10, 2 );

		$this->get_lead()->register();
	}

	/**
		@brief		Add our columns to the leads type.
		@since		2016-12-12 21:55:46
	**/
	public function manage_posts_columns( $columns, $post_type )
	{
		if ( $post_type != $this->get_lead()->get_post_type() )
			return $columns;

		// Title isn't interesting.
		unset( $columns[ 'title' ] );

		$columns = array_merge(
			array_slice( $columns, 0, 1 ),
			[
				$this->get_plugin_prefix() . '_lead_name' => 'Name',
				$this->get_plugin_prefix() . '_lead_info' => 'Info',
			],
			array_slice( $columns, 1 )
		);

		return $columns;
	}

	/**
		@brief		manage_posts_custom_column
		@since		2016-12-12 21:52:59
	**/
	public function manage_posts_custom_column( $column, $post_id )
	{
		$lead = $this->get_lead()->load_from_store( $post_id );
		switch( $column )
		{
			case $this->get_plugin_prefix() . '_lead_name':
				echo sprintf( '%s %s',
					$lead->meta->lead_first_name,
					$lead->meta->lead_last_name
				);
			break;
			case $this->get_plugin_prefix() . '_lead_info':
				echo $lead->get_info_column();
			break;
		}
	}

	/**
		@brief		Allow leads to be broadcasted.
		@since		2016-12-12 22:10:33
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( $this->get_lead()->get_post_type() );
	}
}
