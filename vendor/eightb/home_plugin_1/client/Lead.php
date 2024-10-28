<?php

namespace eightb\home_plugin_1\client;

use \Exception;

/**
	@brief		Lead custom post type.
	@since		2016-12-12 21:26:16
**/
class Lead
{
	/**
		@brief		Broadcast this lead to the lead pool.
		@since		2016-12-16 22:19:25
	**/
	public function broadcast()
	{
		if ( ! function_exists( 'ThreeWP_Broadcast' ) )
			return;

		// Get the lead pool blog id.
		$blog_id = $this->get_plugin()->get_site_option( 'lead_pool_blog' );

		if ( $blog_id < 1 )
			return;

		ThreeWP_Broadcast()->api()->broadcast_children( $this->id, [ $blog_id ] );
	}

	/**
		@brief		Return the info column text for this lead.
		@since		2017-03-21 14:35:30
	**/
	public function get_info_column()
	{
		$r = '';

		$r .= '<div class="email">';
		$r .= sprintf( '<a href="mailto:%s" title="%s">%s</a>',
			$this->meta->lead_email,
			'Send e-mail to this lead',
			$this->meta->lead_email
		);
		$r .= '</div>';

		// Display the phone number in a nice way?
		$r .= '<div class="phone">';
		$r .= $this->meta->lead_phone;
		$r .= '</div>';

		return $r;
	}

	/**
		@brief		Return an array of meta keys we use.
		@since		2017-03-22 13:26:38
	**/
	public function get_meta_keys()
	{
		return [
			'lead_email',
			'lead_first_name',
			'lead_last_name',
			'lead_phone'
		];
	}

	/**
		@brief		Generate a post title.
		@since		2016-12-12 21:36:30
	**/
	public function generate_post_title()
	{
		$r = sprintf( '%s %s %s %s', $this->meta->lead_first_name, $this->meta->lead_last_name, $this->meta->lead_phone, $this->meta->lead_email );
		$r = str_replace( '  ', ' ', $r );
		$r = trim( $r );
		return $r;
	}

	/**
		@brief		Retrieve an array of shortcodes based on this lead.
		@since		2017-02-25 19:02:21
	**/
	public function get_shortcodes()
	{
		$r = [
			'email' => $this->meta->lead_email,
			'first_name' => $this->meta->lead_first_name,
			'last_name' => $this->meta->lead_last_name,
			'phone' => $this->meta->lead_phone,
		];
		return $r;
	}

	/**
		@brief		Return the post type name.
		@since		2016-12-12 21:46:04
	**/
	public static function get_post_type()
	{
		return '8b_hv_lead';
	}

	/**
		@brief		Register ourself with Wordpress.
		@since		2017-03-09 22:11:03
	**/
	public function register()
	{
		$this->register_cpt();
		$this->register_taxonomies();
	}

	/**
		@brief		Register the CPT.
		@since		2017-03-09 22:09:51
	**/
	public function register_cpt()
	{
		$plugin = static::get_plugin();
		$full_plugin_name = $plugin->full_plugin_name();

		$labels = [
			'name'                  => $full_plugin_name . ' Leads',
			'singular_name'         => 'Lead',
			'menu_name'             => $full_plugin_name,
			'not_found'             => 'No leads',
			'name_admin_bar'        => 'Lead',
			'archives'              => 'Lead Archives',
			'parent_item_colon'     => 'Parent Lead:',
			'all_items'             => 'All Leads',
			'add_new_item'          => 'Add New Lead',
			'add_new'               => 'Add New',
			'new_item'              => 'New Lead',
			'edit_item'             => 'Edit Lead',
			'update_item'           => 'Update Lead',
			'view_item'             => 'View Lead',
			'search_items'          => 'Find Lead',
			'not_found_in_trash'    => 'Not found in Trash',
			'featured_image'        => 'Featured Image',
			'set_featured_image'    => 'Set featured image',
			'remove_featured_image' => 'Remove featured image',
			'use_featured_image'    => 'Use as featured image',
			'insert_into_item'      => 'Insert into lead',
			'uploaded_to_this_item' => 'Uploaded to this lead',
			'items_list'            => 'Leads list',
			'items_list_navigation' => 'Leads list navigation',
			'filter_items_list'     => 'Filter leads list',
		];

		$args = [
			'label'                 => $full_plugin_name,
			'description'           => $full_plugin_name . ' Leads',
			'labels'                => $labels,
			// Prevent creation of new posts.
			'map_meta_cap'			=> true,
			'capabilities' => [
				//'edit_posts'		=> 'do_not_allow',
				'create_posts'		=> 'do_not_allow',
				'read_post'				=> 'read_post',
			],
			'supports'              => [],
			'taxonomies'            => [],
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => false,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
		];
		register_post_type( $this->get_post_type(), $args );
	}

	/**
		@brief		Register the taxonomy. If any.
		@since		2017-03-09 22:10:11
	**/
	public function register_taxonomies()
	{
	}
}
