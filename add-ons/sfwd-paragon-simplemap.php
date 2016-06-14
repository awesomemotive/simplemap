<?php
/*
Plugin Name: SFWD Paragon Vision to SimpleMap Search
Plugin URI: http://semperplugins.com
Description: Custom Plugin for Realty Direct Extra Multisite Functionality
Version: 0.1
Author: Semper Fi Web Design
Author URI: http://semperfiwebdesign.com
*/
add_action( 'plugins_loaded', 'sfwd_para_init' );
add_filter( 'sm-use-updating-image', 'sfwd_para_inject_js' );
//add_filter( 'sm-location-search-before-submit', 'sfwd_para_add_doctor_field' );
add_filter( 'sm_location_search_form', 'sfwd_para_add_doctor_field' );


function sfwd_para_add_doctor_field( $search ) {
	$search = preg_replace( "|<td class='location_search_empty_cell location_search_cell'></td>|", "<td class='location_search_cell'>Doctor Name:<br /><input name=location_search_doctor_field id=location_search_doctor_field type=text></td>", $search );
	return $search;
}

function sfwd_para_inject_js( $filter ) {
	static $start = 1;
	if ( $start ) {
		echo "if ( null != document.getElementById('location_search_doctor_field') ) {
	searchData.doctorName = document.getElementById('location_search_doctor_field').value;
	searchUrl = searchUrl + '&doctorName=' + searchData.doctorName;
}
";
	}
	$start = 0;
	return $filter;
}

function sfwd_para_permalink( $id ) {
	return "/doctor-information/?store=" . $id;
}

function sfwd_para_init() {
	global $sm_xml_search;
	if ( !empty( $sm_xml_search ) && is_object( $sm_xml_search ) ) {
		remove_action( 'template_redirect', array( $sm_xml_search, 'init_search' ) );
		add_action( 'template_redirect', 'sfwd_para_search' );
	}
}

function sfwd_para_search() {
	if ( isset( $_GET['sm-xml-search'] ) ) {
		global $wpdb, $simple_map;
		remove_filter( 'the_title', 'at_title_check' );

		$defaults = array(
			'lat' => false,
			'lng' => false,
			'radius' => false,
			'units' => false,
			'namequery' => false,
			'query_type' => 'distance',
			'address' => false,
			'city' => false,
			'state' => false,
			'zip' => false,
			'onlyzip' => false,
			'country' => false,
			'limit' => false,
			'pid'	=> 0,
			'sort'	=> '',
			'location_name' => '',
		);
		$input = array_filter( array_intersect_key( $_GET, $defaults ) ) + $defaults;
		
		// We're going to do a hard limit to 5000 for now.
		if ( !$input['limit'] || $input['limit'] > 5000 )
			$limit = 5000;
		else
			$limit = $input['limit'];

		$limit = apply_filters( 'sm-xml-search-limit', $limit );
		
//		http://drsearch.paragonvision.com/beta/drSearch.php?lat=36.001827&lng=-78.884911&page=1&perPage=20&boxWidth=1&secretKey=db9ce134b01c10cb15921cdc6042f328
		if ( !empty( $_REQUEST['doctorName'] ) ) 
			$url = "http://drsearch.paragonvision.com/beta/drSearch.php?doctorName=" . $_REQUEST['doctorName'] . "&page=1&perPage=20&boxWidth=1&secretKey=db9ce134b01c10cb15921cdc6042f328";
		else
			$url = "http://drsearch.paragonvision.com/beta/drSearch.php?lat={$input['lat']}&lng={$input['lng']}&page=1&perPage=20&boxWidth=1&secretKey=db9ce134b01c10cb15921cdc6042f328";
		$locations = wp_remote_get( $url );
		if ( !empty( $locations['body'] ) ) {
			$locations = json_decode( $locations['body'] );
			if ( !empty( $locations ) )
				$locations = $locations->doctors;
		}
		else
			$locations = Array();
		
		$location_field_map = array(
			'id' => 'ID',
			'Latitude' => 'lat',
			'Longitude' => 'lng',
			'businessName' => 'name',
			'pageHTML' => 'post_content',
			'businessName' => 'post_title',
			'Promo' => 'special',
			'shownWebsite' => 'url',
			'postal' => 'zip'
		);

		$options = $simple_map->get_options();
		$show_permalink = !empty( $options['enable_permalinks'] );
//		$show_permalink = false;
		
		$locations = apply_filters( 'sm-xml-search-locations', $locations );
		
		if ( $locations ) {
			$location_num = 1;
			$standard_flagged = false;
			// Start looping through all locations i found in the radius
			foreach ( $locations as $key => $value ) {
				// Add postmeta data to location
			//	$custom_fields = get_post_custom( $value->ID );
				
				// Some filters for separating special and standard locations
				
				if ( $location_num == 1 && $value->Promo ) {
					$value->first_of_type = $value->location_custom_fields->first_of_type = 'special';
					$value->first_of_type_id = $value->location_custom_fields->first_of_type_id = $value->id;
				} elseif ( ! $value->Promo && ! $standard_flagged ) {
					$value->first_of_type = $value->location_custom_fields->first_of_type = 'standard';
					$value->first_of_type_id = $value->location_custom_fields->first_of_type_id = $value->ID;
					$standard_flagged = true;
				}

				foreach ( $location_field_map as $key => $field ) {
					$tmp = $value->$key;
					unset($value->$key);
					$value->$field = $tmp;
/*
					if ( isset( $custom_fields[$key][0] ) ) {
						$value->$field = $value->location_custom_fields->$field = $custom_fields[$key][0];
					}
					else {
						$value->$field = $value->location_custom_fields->$field = '';
					}
*/
				}
				$fields = array_keys ( (Array)$value );
				foreach( $fields as $f ) {
					if ( $value->$f == null ) $value->$f = '';
				}
				if ( !empty( $value->url ) )
					if ( substr( $value->url, 3 ) != 'http' ) $value->url = "http://" . $value->url;
				$value->postid = $value->ID;
				$value->name = apply_filters( 'the_title', $value->post_title );
				$value->is_results_location = true;

				$the_content = trim( $value->post_content );
				if ( !empty( $the_content ) ) {
					$the_content = apply_filters( 'the_content', $the_content );
				}
				$value->description = $the_content;

				$value->permalink = '';
				if ( $show_permalink ) {
					$value->permalink = sfwd_para_permalink( $value->ID );
					$value->permalink = apply_filters( 'the_permalink', $value->permalink );
				}

				// List all terms for all taxonomies for this post
				$value->taxes = array();
				$value->taxes->sm_category = '';
				/*
				foreach ( $smtaxes as $taxonomy => $tax_value ) {
					$phpsafe_tax = str_replace( '-', '_', $taxonomy );
					$local_tax_names = '';

					// Get all taxes for this post
					if ( $local_taxes = wp_get_object_terms( $value->ID, $taxonomy, array( 'fields' => 'names' ) ) ) {
						$local_tax_names = implode( ', ', $local_taxes );
					}

					$value->taxes[$phpsafe_tax] = $local_tax_names;
				}
				*/

				// Post thumbnail if it exists
				/*
				if ( has_post_thumbnail( $value->ID ) ) {
					$value->thumbnail = get_the_post_thumbnail( $value->ID, 'thumbnail' );
				}
				*/
				$value->units = $input['units'];

				// Grab the template object
				$template = new SM_Template_Factory( array( 'template-type' => 'results-location' ) );
				
				// Set the result div
				$value->resultDiv = $template->apply_template( $value );
				$location_num++;
			}
		} else {
			// Print empty XML
			$locations = array();
		}
		
		$dataset = $locations;
		
		header( 'Status: 200 OK', false, 200 );
		header( 'Content-type: application/json' );
		do_action( 'sm-xml-search-headers' );

		do_action( 'sm-print-json', $dataset, $smtaxes );

		echo @json_encode( $dataset );
		die();
	}
}