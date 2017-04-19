<?php
if ( ! class_exists( 'SM_XML_Search' ) ) {
	class SM_XML_Search {
		// Register hook to perform the search
		function __construct() {
			add_action( 'template_redirect', array( &$this, 'init_search' ) );
		}

		// Inits the search process. Collects default options, search options, and queries DB
		function init_search() {
			if ( isset( $_GET['sm-xml-search'] ) ) {
				global $wpdb, $simple_map;
				remove_filter( 'the_title', 'at_title_check' );

				$defaults = array(
					'lat'        => false,
					'lng'        => false,
					'radius'     => false,
					'namequery'  => false,
					'query_type' => 'distance',
					'locname'    => false,
					'address'    => false,
					'city'       => false,
					'state'      => false,
					'zip'        => false,
					'onlyzip'    => false,
					'country'    => false,
					'limit'      => false,
					'pid'        => 0,
				);
				$input    = array_filter( array_intersect_key( $_GET, $defaults ) ) + $defaults;

				$smtaxes = array();
				if ( $taxonomies = get_object_taxonomies( 'sm-location' ) ) {
					foreach ( $taxonomies as $key => $tax ) {
						$phpsafe = str_replace( '-', '_', $tax );
						$_GET += array( $phpsafe => '' );
						$smtaxes[ $tax ] = $_GET[ $phpsafe ];
					}
				}

				// Define my empty strings
				$additional_select = $having = $distance_order = '';

				// We're going to do a hard limit to 5000 for now.
				if ( ! $input['limit'] || $input['limit'] > 250 ) {
					$limit = "LIMIT 250";
				} else {
					$limit = 'LIMIT ' . absint( $input['limit'] );
				}

				$limit = apply_filters( 'sm-xml-search-limit', $limit );

				// Locations within specific distance or just get them all?
				$additional_select = $wpdb->prepare( "( 3959 * ACOS( COS( RADIANS(%s) ) * COS( RADIANS( lat_tbl.meta_value ) ) * COS( RADIANS( lng_tbl.meta_value ) - RADIANS(%s) ) + SIN( RADIANS(%s) ) * SIN( RADIANS( lat_tbl.meta_value ) ) ) ) AS distance", $input['lat'], $input['lng'], $input['lat'] ) . ', ';
				$distance_order  = 'distance, ';

				if ( $input['radius'] ) {
					$input['radius'] = ( $input['radius'] < 1 ) ? 1 : $input['radius'];
					$having = $wpdb->prepare( "HAVING distance < %d", $input['radius'] );
				}

				$i             = 1;
				$taxonomy_join = '';
				foreach ( array_filter( $smtaxes ) as $taxonomy => $tax_value ) {
					$term_ids = explode( ',', $tax_value );
					if ( $term_ids[0] == 'OR' ) {
						unset( $term_ids[0] );
						if ( empty( $term_ids ) ) {
							continue;
						}
						$search_values = array( "IN (" . vsprintf( '%d' . str_repeat( ',%d', count( $term_ids ) - 1 ), $term_ids ) . ")" );
					} else {
						$search_values = array();
						foreach ( $term_ids as $term_id ) {
							$search_values[] = sprintf( '= %d', $term_id );
						}
					}
					foreach ( $search_values as $search_value ) {
						$taxonomy_join .= "
							INNER JOIN
								$wpdb->term_relationships AS term_rel_$i ON posts.ID = term_rel_$i.object_id
							INNER JOIN
								$wpdb->term_taxonomy AS tax_$i ON
									term_rel_$i.term_taxonomy_id = tax_$i.term_taxonomy_id
									AND tax_$i.taxonomy = '$taxonomy'
									AND tax_$i.term_id $search_value
						";
						$i ++;
					}
				}

				// Locations by name or category name or tag name
				$input['locname'] = trim( $input['locname'] );
				if ( ! empty( $input['locname'] ) ) {
					$sql_locname_having = $wpdb->prepare( "LOWER( posts.post_title ) LIKE '%s'", '%' . strtolower( $input['locname'] ) . '%' );

					$sm_terms_by_name = array();
					foreach ( $smtaxes as $smtax => $ids ) {
						$sm_terms = get_terms( array(
							'taxonomy' => $smtax,
							'fields'   => 'id=>name',
							'name'     => $input['locname'],
						) );
						if ( ! empty( $sm_terms ) ) {
							$sm_terms_by_name[ $smtax ] = implode( ',', array_map( 'intval', array_keys( $sm_terms ) ) );
							$smtax_slug = str_replace( 'sm-', '', $smtax );
							$taxonomy_join .= "
								INNER JOIN
									$wpdb->term_relationships AS term_rel_$smtax_slug ON posts.ID = term_rel_$smtax_slug.object_id
								INNER JOIN
									$wpdb->term_taxonomy AS tax_$smtax_slug ON
										term_rel_$smtax_slug.term_taxonomy_id = tax_$smtax_slug.term_taxonomy_id
										AND tax_$smtax_slug.taxonomy = '$smtax'
										AND tax_$smtax_slug.term_id IN ( {$sm_terms_by_name[ $smtax ]} )
							";

							$additional_select .= "tax_$smtax_slug.term_id as {$smtax_slug}_term_id, ";
							$sql_locname_having .= $wpdb->prepare( " OR {$smtax_slug}_term_id IN ( %s ) ", $sm_terms_by_name[ $smtax ] );
						}
					}

					if ( ! empty( $having ) ) {
						$having .= " AND ( " . $sql_locname_having . " )";
					} else {
						$having = "HAVING " . $sql_locname_having;
					}
				}

				// Compatibility with WPML
				$wpml_join = "";
				if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
					global $sitepress;

					if ( isset( $_GET['wpml'] ) ) {
						$wpml_sm_lang = $_GET['wpml'];
					}

					if ( isset( $_GET['lang'] ) ) {
						$wpml_sm_lang = $_GET['lang'];
					}

					if ( isset( $wpml_sm_lang ) ) {
						$sitepress->switch_lang( $wpml_sm_lang );
					}
					$wpml_join = "INNER JOIN " . $wpdb->prefix . "icl_translations t ON posts.ID = t.element_id AND t.element_type = 'post_sm-location' AND t.language_code = '" . $sitepress->get_current_language() . "'";
				}

				$sql = "SELECT
						lat_tbl.meta_value AS lat,
						lng_tbl.meta_value AS lng,
						$additional_select
						posts.ID,
						posts.post_content,
						posts.post_title
					FROM
						$wpdb->posts AS posts
						$wpml_join
					INNER JOIN
						$wpdb->postmeta lat_tbl ON lat_tbl.post_id = posts.ID AND lat_tbl.meta_key = 'location_lat'
					INNER JOIN
						$wpdb->postmeta lng_tbl ON lng_tbl.post_id = posts.ID AND lng_tbl.meta_key = 'location_lng'
						$taxonomy_join
					WHERE
						posts.post_type = 'sm-location'
						AND posts.post_status = 'publish'
					GROUP BY
						posts.ID
						$having
					ORDER BY " . apply_filters( 'sm-location-sort-order', $distance_order . ' posts.post_name ASC', $input ) . " " . $limit;

				$sql = apply_filters( 'sm-xml-search-locations-sql', $sql );

				// TODO: Consider using this to generate the marker node attributes in print_xml().
				$location_fields = array(
					'location_address'  => 'address',
					'location_address2' => 'address2',
					'location_city'     => 'city',
					'location_state'    => 'state',
					'location_zip'      => 'zip',
					'location_country'  => 'country',
					'location_phone'    => 'phone',
					'location_fax'      => 'fax',
					'location_email'    => 'email',
					'location_url'      => 'url',
					'location_special'  => 'special',
				);

				$location_field_map = apply_filters( 'sm_location_fields', $location_fields );

				$options        = $simple_map->get_options();
				$show_permalink = ! empty( $options['enable_permalinks'] );

				if ( $locations = $wpdb->get_results( $sql ) ) {
					// Start looping through all locations i found in the radius
					foreach ( $locations as $key => $value ) {
						// Add postmeta data to location
						$custom_fields = get_post_custom( $value->ID );
						foreach ( $location_field_map as $key => $field ) {
							if ( isset( $custom_fields[ $key ][0] ) ) {
								$value->$field = $custom_fields[ $key ][0];
							} else {
								$value->$field = '';
							}
						}

						$value->postid = $value->ID;
						$value->name   = apply_filters( 'the_title', $value->post_title );

						$the_content = trim( $value->post_content );
						if ( ! empty( $the_content ) ) {
							$the_content = apply_filters( 'the_content', $the_content );
						}
						$value->description = $the_content;

						$value->permalink = '';
						if ( $show_permalink ) {
							$value->permalink = get_permalink( $value->ID );
							$value->permalink = apply_filters( 'the_permalink', $value->permalink );
						}

						// List all terms for all taxonomies for this post
						$value->taxes = array();
						foreach ( $smtaxes as $taxonomy => $tax_value ) {
							$phpsafe_tax     = str_replace( '-', '_', $taxonomy );
							$local_tax_names = '';

							// Get all taxes for this post
							if ( $local_taxes = wp_get_object_terms( $value->ID, $taxonomy, array( 'fields' => 'names' ) ) ) {
								$local_tax_names = implode( ', ', $local_taxes );
							}

							$value->taxes[ $phpsafe_tax ] = $local_tax_names;
						}
					}
				} else {
					// Print empty XML
					$locations = array();
				}

				$locations = apply_filters( 'sm-xml-search-locations', $locations );
				$this->print_json( $locations, $smtaxes );
			}
		}

		// Prints the JSON output
		function print_json( $dataset, $smtaxes ) {
			header( 'Status: 200 OK', false, 200 );
			header( 'Content-type: application/json' );
			do_action( 'sm-xml-search-headers' );

			do_action( 'sm-print-json', $dataset, $smtaxes );

			echo json_encode( $dataset );
			die();
		}
	}
}
