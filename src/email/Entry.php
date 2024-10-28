<?php

namespace eightb\sold_alerts\email;

/**
	@brief		An e-mail entry.
	@since		2017-03-23 21:02:34
**/
class Entry
{
	// Use the post storage trait.
	use \plainview\sdk_eightb_sold_alerts\wordpress\object_stores\Post;

	/**
		@brief		The tags used by this post type.
		@since		2017-03-23 21:10:58
	**/
	public static $tags = [
		'email' => 'sa_email_entry_email',
	];

	/**
		@brief		Return the tagged e-mail address.
		@since		2017-03-24 18:12:02
	**/
	public function get_email()
	{
		$r = $this->get_tags( 'email' );
		$r = reset( $r );
		return $r->name;
	}

	/**
		@brief		Get meta keys.
		@since		2017-03-24 21:33:14
	**/
	public function get_meta_keys()
	{
		return [
			'send_ok',
		];
	}

	/**
		@brief		Return the custom post type name.
		@since		2017-03-17 17:19:52
	**/
	public static function get_post_type()
	{
		return 'sa_email_entry';
	}

	/**
		@brief		Return the objects of a specific taxonomy.
		@since		2017-03-24 18:12:34
	**/
	public function get_tags( $tag_type )
	{
		return wp_get_object_terms( $this->id, static::$tags[ $tag_type ] );
	}

	/**
		@brief		manage_posts_custom_column
		@since		2017-03-24 20:48:30
	**/
	public function manage_posts_columns( $columns, $post_type )
	{
		if ( $post_type != static::get_post_type() )
			return $columns;

		$columns[ 'info' ] = 'Info';
		return $columns;

	}

	/**
		@brief		manage_posts_custom_column
		@since		2017-03-24 20:48:30
	**/
	public function manage_posts_custom_column( $column, $post_id )
	{
		$entry = static::load_from_store( $post_id );

		switch( $column )
		{
			case 'info':
				if ( ! $entry->meta->send_ok )
					echo 'Unable to send.';
			break;
		}
	}

	/**
		@brief		Register the subscriber post type.
		@since		2017-03-17 17:09:30
	**/
	public function register_post_type()
	{
		$args = [
			'label'                 => 'E-mail log',
			'description'           => 'E-mail logs',
			'labels'                => [
				'name'                  => 'Sold Alerts e-mail log',
				'menu_name'             => 'SA E-mail Log',
				'singular_name'         => 'Log item',
			],
			// Prevent creation of new posts.
			'map_meta_cap'			=> true,
			'capabilities' => [
				'create_posts'		=> 'do_not_allow',
			],
			'supports'              => [],
			'taxonomies'            => [],
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => ( EightB_Sold_Alerts()->get_local_or_site_option( 'display_email_log' ) != false ),
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'page',
		];
		register_post_type( $this->get_post_type(), $args );

		// EMAIL
		$args = [
			'hierarchical'      => false,
			'labels'            => [
				'name'              => 'Email',
				'menu_name'         => 'Emails',
				'singular_name'     => 'Email',
			],
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
		];
		register_taxonomy( static::$tags[ 'email' ], [ $this->get_post_type() ], $args );

		add_filter( 'manage_posts_columns', [ $this, 'manage_posts_columns' ], 10, 2 );
		add_action( 'manage_posts_custom_column', [ $this, 'manage_posts_custom_column' ], 10, 2 );
	}

	/**
		@brief		Set the email of the item.
		@since		2017-03-23 21:17:11
	**/
	public function set_email( $email )
	{
		return $this->set_tag( 'email', $email );
	}

	/**
		@brief		Sets a tag to this post.
		@since		2017-03-23 21:15:02
	**/
	public function set_tag( $tag_type, $tag_value )
	{
		wp_set_object_terms( $this->id, $tag_value, static::$tags[ $tag_type ], true );
		return $this;
	}
}
