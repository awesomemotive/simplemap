<?php
/**
 * This file contains all my classes for the different shortcodes
 */

/**
 * SM_Shortcodes Class
 *
 * Registers and handles shortcodes for SimpleMap Locations. The only exception right now is the
 * main [simplemap] shortcode which is currently being handled by /classes/simplemap.php. 
 * It will eventually be moved here
 *
 * @since 2.4
*/
if ( ! class_exists( 'SM_Location_Shortcodes' ) ) {

	class SM_Location_Shortcodes {

		/**
		 * An array of all shortcodes related to locations
		 * 
		 * @since 2.4
		*/
		var $shortcode_tags = array();

		/**
		 * Class constructor
		 *
		 * @since 2.4
		*/
		function __construct() {

			// Build shortcode tag list
			$this->shortcode_tags = $this->get_shortcode_tags();

			// Register shortcodes with WordPress
			add_action( 'init', array( &$this, 'register_shortcodes' ) );

		}

		/**
		 * This method loops through all my location shortcodes and registers them.
		 *
		 * All shortcode tags actually use the same callback method
		 *
		 * @since 2.4
		*/
		function register_shortcodes() {
		
			// Register
			add_shortcode( 'sm-location', array( &$this, 'do_shortcode' ) );

		}

		/**
		 * Builds the array of tags used for location shortcodes
		 *
		 * @since 2.4
		*/
		function get_shortcode_tags() {

			$shortcode_tags = array(
				'address',
				'city',
				'state',
				'zip',
				'country',
				'phone',
				'fax',
				'email',
				'url'
			);

			return apply_filters( 'sm-location-shortcode-tag-array', $shortcode_tags );
		}

		/**
		 * Responsible for returning a location address
		 *
		 * @since 2.4
		*/
		function do_shortcode( $args ) {
			global $post, $simple_map;
			static $options = null;
			static $sm_taxes = null;
			if ( $options === null )
				$options = $simple_map->get_options();

			$cl = false;
			// Overwrite global post with current results location post if printing search results
			if ( ! empty( $simple_map->current_results_location ) ) {
				$loc_id = $simple_map->current_results_location->ID;
				if ( empty( $post ) || ( $post->ID != $loc_id ) )
					$post = get_post( $loc_id );
				$cl = $simple_map->current_results_location;
			}
			if ( empty( $cl->lat ) || empty( $cl->lng ) )
				if ( !empty( $post->postmeta ) )
					$postmeta = $post->postmeta;
				else
					$postmeta = $post->postmeta = get_metadata( 'post', $post->ID );
			// Don't query post meta if we already have it
			$the_default_lat = empty( $cl->lat ) ? $postmeta['location_lat'][0] : $cl->lat;
			$the_default_lng = empty( $cl->lng ) ? $postmeta['location_lng'][0] : $cl->lng;

			// Default args for map itself, not for locations on map
			$map_defaults = array(
				'data'                  => '',
				'before'                => '',
				'after'                 => '',
				'map_width'             => '120px',
				'map_height'	        => '120px',
				'default_lat'           => $the_default_lat,
				'default_lng'           => $the_default_lng,
				'panControl'            => false,
				'zoomControl'           => false,
				'scaleControl'          => false,
				'streetViewControl'     => false,
				'mapTypeControl'        => false,
				'mapTypeId'             => 'google.maps.MapTypeId.ROADMAP',
				'format'                => 'csv',
				'thumbnail_map_marker_image' => '',
				'thumbnail_map_marker_color' => 'blue',
				'thumbnail_map_zoom'	=> '11',
			);

			$atts = shortcode_atts( $map_defaults, $args );
			
			$atts['pan_control'] = $atts['panControl'];
			$atts['zoom_control'] = $atts['zoomControl'];
			$atts['scale_control'] = $atts['scaleControl'];
			$atts['street_view_control'] = $atts['streetViewControl'];
			$atts['map_type_control'] = $atts['mapTypeControl'];
			$atts['map_type'] = $atts['mapTypeId'];

			if ( 'search' == $atts['data'] ) {
				$buf = '';
				ob_start();
				$search_form = get_search_form( false );
				$buf = ob_get_clean();
				if ( empty( $search_form ) && !empty( $buf ) ) $search_form = $buf;
				$search_form = str_ireplace( '</form>', '<input type="hidden" name="post_type" value="sm-location" /></form>', $search_form );
				return $search_form;
			}

			// If the requested data is description, return the post content
			if ( 'description' == $atts['data'] )
				return $post->post_content;

                        // If we're looking for the full address, compose that from the options
                        if ( 'full-address' == $atts['data'] ) {
								if ( !empty( $post->postmeta ) )
									$postmeta = $post->postmeta;
								else
									$postmeta = $post->postmeta = get_metadata( 'post', $post->ID );
                                $address_format = $options['address_format'];

                                switch ( $address_format ) {
                                    case 'town province postalcode' :
                                        $full_address = $postmeta['location_address'][0] . ' ' . $postmeta['location_address2'][0] . ' ' .$postmeta['location_city'][0] . $postmeta['location_state'][0] . ' ' . $postmeta['location_zip'][0];
                                        break;

                                    case 'town-province postalcode' :
                                        $full_address = $postmeta['location_address'][0] . ' ' . $postmeta['location_address2'][0] . ' ' .$postmeta['location_city'][0] . '-' . $postmeta['location_state'][0] . ' ' . $postmeta['location_zip'][0];
                                        break;

                                    case 'postalcode town-province' :
                                        $full_address = $postmeta['location_address'][0] . ' ' . $postmeta['location_address2'][0] . ' ' .$postmeta['location_zip'][0] . ' ' . $postmeta['location_city'][0] . '-' . $postmeta['location_state'][0];
                                        break;

                                    case 'postalcode town, province' :
                                        $full_address = $postmeta['location_address'][0] . ' ' . $postmeta['location_address2'][0] . ' ' .$postmeta['location_zip'][0] . ' ' . $postmeta['location_city'][0] . ', ' . $postmeta['location_state'][0];
                                        break;

                                    case 'postalcode town' :
                                        $full_address = $postmeta['location_address'][0] . ' ' . $postmeta['location_address2'][0] . ' ' .$postmeta['location_zip'][0] . ' ' . $postmeta['location_city'][0];
                                        break;

                                    case 'town postalcode' :
                                        $full_address = $postmeta['location_address'][0] . ' ' . $postmeta['location_address2'][0] . ' ' .$postmeta['location_city'][0] . ' ' . $postmeta['location_zip'][0];
                                        break;

                                    case 'town, province postalcode' :
                                    default :
                                        $full_address = $postmeta['location_address'][0] . ' ' . $postmeta['location_address2'][0] . ' ' .$postmeta['location_city'][0] . ', ' . $postmeta['location_state'][0] . ' ' . $postmeta['location_zip'][0];
                                        break;

                                }

                                return $atts['before'] . $full_address . $atts['after'];
                        }

			// if the requested data is 'directions' return the link to google maps
			if ( 'directions' == $atts['data'] )
				return $atts['before'] . $this->get_directions_link( $post->ID ) . $atts['after'];

			// if the requested data is a map, return the map
			if ( 'iframe-map' == $atts['data'] ) {
				
				// Args we need to make the map itself - not the locations on the map
				$r = array( 
                                        'map_width'	        => $atts['map_width'],
                                        'map_height'	    => $atts['map_height'],
                                        'panControl'        => $atts['pan_control'],
                                        'default_lat'       => $atts['default_lat'],
                                        'default_lng'       => $atts['default_lng'],
                                        'zoomControl'       => $atts['zoom_control'],
                                        'scaleControl'      => $atts['scale_control'],
                                        'streetViewControl' => $atts['street_view_control'],
                                        'mapTypeControl'    => $atts['map_type_control'],
                                        'mapType'           => $atts['map_type']
                                );

                                // Determine location IDs
                                $location_ids = empty( $args['location_ids'] ) ? array( $post->ID ) : explode( ',', $args['location_ids'] );

                                // Init the object and return the iframe source
                                $map = new SM_Map_Factory( $r );

                                // Add Locations
                                foreach( $location_ids as $location_id ) {
                                        $map->add_location( $location_id );
                                }

                                return $map->get_iframe_embed();

                        }

                        // If the requested data is a taxonomy
						if ( $sm_taxes === null ) {
							$sm_taxes = $simple_map->get_sm_taxonomies( 'array', '', true, 'objects' );
							if ( !$sm_taxes ) $sm_taxes = Array();
						}

                        foreach( $sm_taxes as $taxonomy ) {
							if ( $taxonomy->name == $atts['data'] ) {
								// Forward compatible format types
								$tax_format = ( in_array( $atts['format'], array( 'csv' ) ) ) ? $atts['format'] : 'csv';
								if ( $terms = wp_get_object_terms( $post->ID, $taxonomy->query_var ) ) {
									foreach( $terms as $termk => $termv ) {
										$term_names[] = $termv->name;
									}

								} else {
									$term_names = array();
								}

								// Forward compatible format types
								switch( $tax_format ) {
									case 'csv':
									default:
										return ! empty( $term_names ) ? $args['before'] . implode( ', ', $term_names ) . $args['after'] : '' ;
										break;
								}
							}
                        }

						// Look for permalink
						if ( 'permalink' == $atts['data'] )
							return str_replace( '%%sm-location-name%%', apply_filters( 'the_title', $post->post_title ), str_replace( '%self%', get_permalink( $post->ID ), $atts['before'] . get_permalink( $post->ID ) . $atts['after'] ) );

						// Look for post name
						if ( 'name' == $atts['data'] )
							return $atts['before'] . apply_filters( 'the_title', $post->post_title ) . $atts['after'];

						// Look for first of type CSS class
						if ( 'first-of-type-class' == $atts['data'] ) {
							if ( ! empty( $simple_map->current_results_location->first_of_type ) )
								return 'first-' . $simple_map->current_results_location->first_of_type;
							else
								return false;
						}

						// Look for Distnace
						if ( 'distance' == $atts['data'] ) {
							$the_distance = $cl->distance;
							if ( 'km' == $cl->units )
								$the_distance *= 1.609344;
							return $atts['before'] . round( $the_distance, 2 ) . ' ' . $cl->units . $atts['after'];
						}

						// Look for Location (post) ID
						if ( 'ID' == $atts['data'] )
							return $atts['before'] . $cl->ID . $atts['after'];

						// Look for Location Thumbnail Image
						if ( 'thumbnail-image' == $atts['data'] )
							return $atts['before'] . $cl->thumbnail . $atts['after'];

						// Look for Location map thumbnail image
						if ( 'thumbnail-map' == $atts['data'] ) {
							//$atts['thumbnail_map_marker_image'] = apply_filter( 'sm_thumbnail_map_marker_image', $atts['thumbnail_map_marker_image'], $cl->ID );
							$markers = empty( $atts['thumbnail_map_marker_image'] ) ? 'markers=color:' . $atts['thumbnail_map_marker_color'] : 'markers=icon:' . $atts['thumbnail_map_marker_image'];
							return $atts['before'] . '<img src="http://maps.googleapis.com/maps/api/staticmap?center=' . $the_default_lat . ',' . $the_default_lng . '&' . $markers . '%7Clabel:%7C' . $the_default_lat . ',' . $the_default_lng . '&zoom=' . $atts['thumbnail_map_zoom'] . '&size=75x75&sensor=false">' . $atts['after'];
						}

						// Look for 'Special'
						if ( 'special' == $atts['data'] && $cl->special )
							return $atts['before'] . esc_attr( $options['special_text'] ) . $atts['after'];

                        // Look for postmeta with location_ prepended to it
						if ( ! empty( $cl->location_custom_fields->$atts['data'] ) ) {
							return str_replace( '%self%', $cl->location_custom_fields->$atts['data'], $atts['before'] . $cl->location_custom_fields->$atts['data'] . $atts['after'] );
						} else {
							if ( !empty( $post->postmeta ) )
								$postmeta = $post->postmeta;
							else
								$postmeta = $post->postmeta = get_metadata( 'post', $post->ID );
							if ( !empty( $postmeta[ 'location_' . $atts['data'] ] ) && ( !empty( $postmeta[ 'location_' . $atts['data'] ][0] ) ) && ( $value = $postmeta[ 'location_' . $atts['data'] ][0] ) )
								return str_replace( '%self%', $value, $atts['before'] . $value . $atts['after'] );
						}

                        // Look for postmeta of another type. This is expensive. Run as last option.
						if ( !empty( $post->postmeta ) )
							$postmeta = $post->postmeta;
						else
							$postmeta = $post->postmeta = get_metadata( 'post', $post->ID );
                        if ( isset( $postmeta[$atts['data']] ) && $value = $postmeta[$atts['data']][0] )
                                return str_replace( '%self%', $value, $atts['before'] . $value . $atts['after'] );

                        return false;
		}

		/**
		 * Returns a directions link
		 * 
		 * @since 2.4
		*/
		function get_directions_link( $post ) {
			global $simple_map;

			$options = $simple_map->get_options(); 
			$address = $directions_url  = '';

			if ( $pm = get_metadata( 'post', $post ) ) {

				$address	.= empty( $pm['location_address'][0] ) ? '' : $pm['location_address'][0];
				$address	.= empty( $pm['location_city'][0] ) ? '' : ' '.$pm['location_city'][0];
				$address	.= empty( $pm['location_state'][0] ) ? '' : ' '.$pm['location_state'][0];
				$address	.= empty( $pm['location_zip'][0] ) ? '' : ' '.$pm['location_zip'][0];
				$address	.= empty( $pm['location_country'][0] ) ? '' : ' '.$pm['location_country'][0];

			 	$directions_url = 'http://google' . $options['default_domain'] . '/maps?saddr=&daddr=' . urlencode( $address );

			}
			
			return $directions_url;

		}

	}

}
