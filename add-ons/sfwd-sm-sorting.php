<?php
/*
Plugin Name: SFWD SimpleMap Custom Sorting
Plugin URI: http://semperplugins.com/
Description: Allows custom ordering of search results.
Version: 0.1
Author: Semper Fi Web Design
Author URI: http://semperfiwebdesign.com/
*/

add_action( 'plugins_loaded', 'sfwd_123_sm_custom_sorting' );

function sfwd_123_sm_custom_sorting() {
	global $sm_locations;
	if ( class_exists( 'SM_Locations' ) && isset( $sm_locations ) && is_object( $sm_locations ) ) {
		remove_action( 'init', array( &$sm_locations, 'register_locations' ) );
		add_action( 'init', 'sfwd_123_sm_register_locations' );
		add_filter( 'sm-location-sort-order', 'sfwd_123_sm_sort_order' );
	}
}

function sfwd_123_sm_sort_order( $order ) {
	if ( !empty( $order ) ) $order = ", " . $order;
	return 'posts.menu_order DESC, RAND() ' . $order;
}

function sfwd_123_sm_register_locations() {
        global $simple_map, $sm_locations;

        $args = array();
        $options = $simple_map->get_options();
        if ( !empty( $options['enable_permalinks'] ) ) {
                $args += array(
                        'publicly_queryable' => true,
                        'exclude_from_search' => false,
                        'rewrite' => array( 'slug' => $options['permalink_slug'] ),
                );
        }

        $args += array(
                'public' => true,
                'publicly_queryable' => false,
                'exclude_from_search' => true,
                'show_ui' => true,
                'capability_type' => 'post',
                'hierarchical' => false,
                'rewrite' => false,
                'query_var' => 'sm-location',
                'register_meta_box_cb' => array( &$sm_locations, 'location_meta_cb' ),
                'supports' => array( 'title', 'editor', 'page-attributes' ),
                'labels' => array(
                        'name' => 'Locations',
                        'singular_name' => 'Location',
                        'add_new_item' => 'Add New Location',
                        'edit_item' => 'Edit Location',
                        'new_item' => 'New Location',
                        'view_item' => 'View Locations',
                        'search_items' => 'Search Locations',
                        'not_found' => 'No Locations found',
                        'not_found_in_trash' => 'No Locations found in trash',
                )
        );

        // Register it
        register_post_type( 'sm-location', $args );
}