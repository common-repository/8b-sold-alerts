<?php

namespace eightb\home_plugin_1\client;

use \Exception;

/**
	@brief		Handles lots of admin menu functions.
	@since		2017-03-05 11:13:49
**/
trait admin_menu_trait
{
	/**
		@brief		Add the menu.
		@since		2016-12-22 20:30:21
	**/
	public function add_menu()
	{
		$menu = $this->menu_page( 'settings.php' )
			->callback_this( '' )
			->menu_slug( 'settings.php' )
			->menu_title( '' );

		$prefix = $this->get_plugin_prefix();
		$menu->submenu( $prefix )
			->callback_this( 'admin_menu_tabs' )
			->capability( 'manage_network_plugins' )
			->menu_slug( $prefix )
			->menu_title( $this->full_plugin_name() )
			->page_title( $this->full_plugin_name() . ' Settings' );

		$class = $this->subclass( '\actions\prepare_menu' );
		$prepare_menu_action = new $class();
		$prepare_menu_action->menu = $menu;
		$prepare_menu_action->execute();

		$menu->add_submenus();
	}

	/**
		@brief		Admin menu.
		@since		2016-12-22 20:29:45
	**/
	public function admin_menu()
	{
		if ( ! current_user_can( 'manage_options' ) )
			return;

		// Clear the menu page.
		unset( $this->__menu_page );

		$key = 'edit.php?post_type=' . $this->get_lead()->get_post_type();
		$menu = $this->menu_page( $key )
			->callback_this( '' )
			->menu_slug( $key )
			->menu_title( '' );

		// Add Lead export.
		$submenu_key = $this->get_plugin_prefix() . '_export_leads';
		$menu->submenu( $submenu_key )
			->callback_this( 'export_leads' )
			// ->menu_slug( $key )
			->menu_title( 'Export' )
			->page_title( 'Export leads' );

		$submenu_key = $this->get_plugin_prefix() . '_settings';
		$menu->submenu( $submenu_key )
			->callback_this( 'admin_menu_tabs' )
			// ->menu_slug( '8b_home_value' )
			->menu_title( 'Settings' )
			->page_title( $this->full_plugin_name() . ' Settings' );

		$class = $this->subclass( '\actions\prepare_menu' );
		$prepare_menu_action = new $class();
		$prepare_menu_action->menu = $menu;
		$prepare_menu_action->execute();

		$menu->add_submenus();
	}

	/**
		@brief		admin_menu_tabs
		@since		2016-12-09 20:31:16
	**/
	public function admin_menu_tabs()
	{
		$tabs = $this->tabs();

		$tabs->valid_get_keys []= 'post_type';

		$tabs->tab( 'settings' )
			->callback_this( 'network_admin_menu_settings' )
			->heading_( '%s Settings', $this->full_plugin_name() )
			->name_( 'Settings' )
			->sort_order( 25 );

		$tabs->tab( 'premium' )
			->callback_this( 'show_premium_info' )
			->heading_( 'Premium plugin information' )
			->name_( 'Premium' );

		if ( ! $this->is_network OR ! is_network_admin() )
			$tabs->tab( 'tools' )
				->callback_this( 'network_admin_menu_tools' )
				->heading_( '%s Tools', $this->full_plugin_name() )
				->name_( 'Tools' );

		if ( $this->show_network_settings() )
		{
			$tabs->tab( 'system_info' )
				->callback_this( 'system_info' )
				->name( 'System info' );

			$tabs->tab( 'uninstall' )
				->callback_this( 'admin_uninstall' )
				->name_( 'Uninstall' )
				->sort_order( 90 );		// Always last.
		}

		$class = $this->subclass( '\actions\prepare_settings_tabs' );

		$prepare_settings_tabs_action = new $class();
		$prepare_settings_tabs_action->tabs = $tabs;
		$prepare_settings_tabs_action->execute();

		echo $tabs;
	}

	/**
		@brief		Allow subclasses to do things after the admin menu tabs.
		@since		2017-03-10 00:10:42
	**/
	public function admin_menu_tabs_action()
	{
	}

	/**
		@brief		Function to export leads.
		@since		2016-12-23 20:43:05
	**/
	public function export_leads()
	{
		$form = $this->form();
		$r = '';

		$dir = $this->paths( '__FILE__' );

		// Create a temporary directory into which to store the export.
		$temp_dir = dirname( $dir ) . '/temp';
		if ( ! is_dir( $temp_dir ) )
			mkdir( $temp_dir );

		// Find all old files and delete them.
		$files = glob( $temp_dir . '/*' );
		foreach( $files as $file )
			if ( filemtime( $file ) > DAY_IN_SECONDS )
				unlink( $file );

		$hash = date('Ymd' ) . $_SERVER[ 'REMOTE_ADDR' ] . NONCE_SALT;
		$hash = md5( $hash );
		$file = sprintf( '%s/leads.%s.%s.csv',
			$temp_dir,
			date( 'Ymd' ),
			substr( $hash, 0, 8 )
		);

		$export_csv = $form->primary_button( 'export_csv' )
			->value_( 'Export to CSV' );

		$delete_csv_export = $form->secondary_button( 'delete_csv_export' )
			->value_( 'Delete the export file' );

		if ( $form->is_posting() )
		{
			$form->post();

			if ( $export_csv->pressed() )
				$this->export_csv_to_file( $file );

			if ( $delete_csv_export->pressed() )
			{
				$r .= $this->info_message_box()->_( 'The export file has been deleted!' );
				if ( file_exists( $file ) )
					unlink( $file );
			}
		}

		$url = $this->paths( 'url' ) . '/temp/' . basename( $file );
		if ( file_exists( $file ) )
		{
			$r .= $this->p_( 'Download the leads export file: %s',
				'<a href="' . $url . '">' . $url . '</a>'
			);
		}

		$r .= $this->p_( 'Press the button below to generate a new leads export file. ' );
		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $this->wrap( $r, $this->_( 'Export leads' ) );
	}

	/**
		@brief		Init the admin menu trait.
		@since		2016-12-22 20:51:35
	**/
	public function init_admin_menu()
	{
		$this->add_action( 'admin_menu' );
		$this->add_action( 'network_admin_menu' );
	}

	/**
		@brief		network_admin_menu
		@since		2016-12-09 20:29:55
	**/
	public function network_admin_menu()
	{
		$this->add_menu();
	}

	/**
		@brief		Misc tools for fixing things.
		@since		2017-01-10 23:10:34
	**/
	public function network_admin_menu_tools()
	{
		$form = $this->form();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';


		$form->markup( 'm_clear_local_settings' )
			->p_( 'Use the button below to clear all local settings, in order to use the settings specified by your network administrator.' );

		$clear_local_settings = $form->primary_button( 'clear_local_settings' )
			->value_( 'Clear local settings' );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			if ( $clear_local_settings->pressed() )
			{
				foreach( $this->local_options() as $option=>$value )
				{
					$option = $this->fix_local_option_name( $option );
					delete_option( $option );
				}
				$r .= $this->info_message_box()
					->_( 'Your local settings have been cleared, so you should now be using the defaults from the network administrator.' );
			}
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Show the premium text file.
		@since		2017-02-05 21:39:50
	**/
	public function show_premium_info()
	{
		$text = $this->get_text_file( 'premium_info' );
		$text = $this->replace_api_text( $text );
		echo wpautop( $text );
	}

	/**
		@brief		Return the system info.
		@since		2017-03-10 23:27:26
	**/
	public function system_info()
	{
		$r = '';
		$table = $this->table();

		// Caption for the blog / PHP information table
		$table->caption()->text( 'System information' );

		$row = $table->head()->row();
		$row->th()->text_( 'Key' );
		$row->th()->text_( 'Value' );

		if ( $this->debugging() )
		{
			$row = $table->body()->row();
			$row->td()->text_( 'Debugging' );
			$row->td()->text_( 'Enabled' );
		}

		$row = $table->body()->row();
		$row->td()->text_( '%s version', $this->full_plugin_name() );
		$row->td()->text( $this->plugin_version );

		global $wp_version;
		$row = $table->body()->row();
		$row->td()->text_( 'Wordpress version' );
		$row->td()->text( $wp_version );

		$row = $table->body()->row();
		$row->td()->text_( 'PHP version' );
		$row->td()->text( phpversion() );

		$row = $table->body()->row();
		$row->td()->text_( 'Wordpress upload directory array' );
		$row->td()->text( '<pre>' . var_export( wp_upload_dir(), true ) . '</pre>' );

		$this->paths[ 'ABSPATH' ] = ABSPATH;
		$this->paths[ 'WP_PLUGIN_DIR' ] = WP_PLUGIN_DIR;
		$row = $table->body()->row();
		$row->td()->text_( 'Plugin paths' );
		$row->td()->text( '<pre>' . var_export( $this->paths(), true ) . '</pre>' );

		$row = $table->body()->row();
		$row->td()->text_( 'PHP maximum execution time' );
		$text = $this->p_( '%s seconds', ini_get ( 'max_execution_time' ) );
		$row->td()->text( $text );

		$row = $table->body()->row();
		$row->td()->text_( 'PHP memory limit' );
		$text = ini_get( 'memory_limit' );
		$row->td()->text( $text );

		$row = $table->body()->row();
		$row->td()->text_( 'Wordpress memory limit' );
		$text = wpautop( sprintf( WP_MEMORY_LIMIT . "

%s

<code>define('WP_MEMORY_LIMIT', '512M');</code>
",		$this->_( 'This can be increased by adding the following to your wp-config.php:' ) ) );
		$row->td()->text( $text );

		$row = $table->body()->row();
		$row->td()->text_( 'Debug code' );
		$text = WP_MEMORY_LIMIT;
		$text = wpautop( sprintf( "%s

<code>ini_set('display_errors','On');</code>
<code>define('WP_DEBUG', true);</code>
",		$this->p_( 'Add the following lines to your wp-config.php to help find out why errors or blank screens are occurring:' ) ) );
		$row->td()->text( $text );

		$value = intval( ini_get( 'allow_url_fopen' ) );
		$row = $table->body()->row();
		$row->td()->text_( 'allow_url_fopen' );
		$row->td()->text( $value );

		$r .= $table;

		echo $r;
	}
}
