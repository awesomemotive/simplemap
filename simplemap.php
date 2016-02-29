<?php
/*
Plugin Name: SimpleMap
Version: 2.4.8
Plugin URI: http://simplemap-plugin.com/
Author: Michael Torbert
Author URI: http://fullthrottledevelopment.com/
Description: An easy-to-use international store locator plugin that uses Google Maps to display information directly on your WordPress site.

This plugin was originally created by Alison Barrett (http://alisothegeek.com/). FullThrottle took over development at v 1.2.3
*/

global $wp_version, $wpdb;

$exit_msg = __( 'SimpleMap requires WordPress 2.8 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update!</a>', 'SimpleMap' );
if ( version_compare( $wp_version, "2.8", "<" ) )
	exit( $exit_msg );

#### CONSTANTS ####

	// Plugin Version Number
	define( 'SIMPLEMAP_VERSION', '2.4.8' );

	if ( !defined( 'WP_PLUGIN_DIR' ) ) {
		define( 'WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins' );
	}

	// If this file is in the plugin directory, proceed as normal.
	if ( strpos( __FILE__, WP_PLUGIN_DIR ) === 0 ) {
		$simplemap_file = plugin_basename( __FILE__ );
	} else {
		// This file is most likely marked as an active plugin, so let's find it that way.
		$simplemap_plugins = preg_grep( '#/' . basename( __FILE__ ) . '$#', get_option( 'active_plugins', array() ) );
		if ( !empty( $simplemap_plugins ) ) {
			$simplemap_file = current( $simplemap_plugins );
		} else {
			// Last ditch effort to find the 'good' filename.
			$simplemap_file = plugin_basename( $plugin ? $plugin : ( $mu_plugin ? $mu_plugin : ( $network_plugin ? $network_plugin : __FILE__ ) ) );
		}
	}
	$simplemap_dir = dirname( $simplemap_file );

	// Define plugin file
	define( 'SIMPLEMAP_FILE', $simplemap_file );

	// Define plugin path
	define( 'SIMPLEMAP_PATH', WP_PLUGIN_DIR . '/' . $simplemap_dir );

	// Define plugin URL
	define( 'SIMPLEMAP_URL', plugins_url() . '/' . $simplemap_dir );

	// Table Names
	if ( !defined( 'SIMPLEMAP_TABLE' ) )
		define( 'SIMPLEMAP_TABLE', $wpdb->prefix . 'simple_map' );

	if ( !defined( 'SIMPLEMAP_CAT_TABLE' ) )
		define( 'SIMPLEMAP_CAT_TABLE', $wpdb->prefix . 'simple_map_cats' );

	// Map HOST
	if ( !defined( 'SIMPLEMAP_MAPS_JS_API' ) ) {
		// TODO: Evaluate removing scheme check as scheme-less urls can be enqueued in 3.3 (WP #16560)
		$scheme = 'http:';
		if ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) {
			$scheme = 'https:';
		}

		define( 'SIMPLEMAP_MAPS_WS_API', $scheme . '//maps.googleapis.com/maps/api/' );
		define( 'SIMPLEMAP_MAPS_JS_API', SIMPLEMAP_MAPS_WS_API . 'js?' );
	}

#### INCLUDES ####

	include_once( 'classes/simplemap.php' );
	include_once( 'classes/widgets.php' );
	include_once( 'classes/locations.php' );

	// Check to make sure another plugin hasn't already loaded the client before including
	if ( ! class_exists( 'FT_Premium_Support_Client' ) )
		include_once( 'classes/ft-ps-client.php' );

#### FIRE IN THE HOLE! ####

	// Init SimpleMap class
	if ( class_exists( 'Simple_Map' ) && ( ! isset( $simple_map ) ) )
		$simple_map = $SimpleMap = new Simple_Map();

	// Register Location post types and custom taxonomies
	if ( class_exists( 'SM_Locations' ) && ( ! isset( $sm_locations ) || ! is_object( $sm_locations ) ) )
		$sm_locations = new SM_Locations();

	if ( is_admin() ) {
		register_activation_hook( SIMPLEMAP_FILE, array( 'SM_Admin', 'on_activate' ) );

		include_once( 'classes/admin.php' );
		include_once( 'classes/options-general.php' );
		include_once( 'classes/import-export.php' );
		include_once( 'classes/help.php' );

		// Register General Options adminpages
		if ( class_exists( 'SM_Options' ) && ( ! isset( $sm_options ) || ! is_object( $sm_options ) ) )
			$sm_options = new SM_Options();

		// Register Import / Export adminpages
		if ( class_exists( 'SM_Import_Export' ) && ( ! isset( $sm_import_export ) || ! is_object( $sm_import_export ) ) )
			$sm_import_export = new SM_Import_Export();

		// Register Help adminpages
		if ( class_exists( 'SM_Help' ) && ( ! isset( $sm_help ) || ! is_object( $sm_help ) ) )
			$sm_help = new SM_Help();
	} else {
		include_once( 'classes/xml-search.php' );
		include_once( 'classes/shortcodes.php' );
		include_once( 'classes/maps.php' );
		include_once( 'classes/templates.php' );

		// Init XML Search class
		if ( class_exists( 'SM_XML_Search' ) && ( ! isset( $sm_xml_search ) ) )
			$sm_xml_search = new SM_XML_Search();

		// Init the shortcode class
		if ( class_exists( 'SM_Location_Shortcodes' ) && ! isset( $sm_location_shortcodes ) )
			$sm_location_shortcodes = new SM_Location_Shortcodes();

		// Init the maps class
		if ( class_exists( 'SM_Map_Factory' ) && ! isset( $sm_map_factory ) )
			$sm_map_factory = new SM_Map_Factory();

		// Init the templating system for single locations
		if ( class_exists( 'SM_Template_Factory' ) && ! isset( $sm_template_factory ) )
			add_action( 'template_redirect', 'sm_init_templating' );
	}

	function sm_init_templating() {
		$sm_location_master = new SM_Template_Factory();
	}

	// Build admin pages and shuffle menu to merge WP UI for custom posts with our custom pages
	if ( class_exists( 'SM_Admin' ) && ( ! isset( $sm_admin ) || ! is_object( $sm_admin ) ) )
		$sm_admin = new SM_Admin();

	// Premium Support Client for SimpleMap
	$config = array( 
		'server_url' => 'http://simplemap-plugin.com', 
		'product_id' => 1, 
		'product-slug' => 'sm-premium', 
		'plugin_support_page_ids' => array( 'simplemap_page_simplemap-help', 'toplevel_page_simplemap', 'simplemap_page_simplemap-import-export',  'sm-location' ), 
		'plugin_basename' => plugin_basename( SIMPLEMAP_PATH . '/simplemap.php' ), 
		'plugin_slug' => 'simplemap',
		'learn_more_link' => 'http://simplemap-plugin.com/premium-support/' 
	);
	if ( class_exists( 'FT_Premium_Support_Client' ) && ( ! isset( $simplemap_ps ) || ! is_object( $simplemap_ps ) ) )
		$simplemap_ps = new FT_Premium_Support_Client( $config );
?>
