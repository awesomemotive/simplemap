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
                        $options = $simple_map->get_options();

			// Default args for map itself, not for locations on map
			$map_defaults = array(
                                'data'                  => '',
                                'before'                => '',
                                'after'                 => '',
				'map_width'             => '75px',
				'map_height'	        => '75px',
                                'default_lat'           => get_post_meta( $post->ID, 'location_lat', true ),
                                'default_lng'           => get_post_meta( $post->ID, 'location_lng', true ),
                                'panControl'            => false,
                                'zoomContorl'           => false,
                                'scaleControl'          => false,
                                'streetViewControl'     => false,
                                'mapTypeContorl'        => false,
                                'mapTypeId'             => 'google.maps.MapTypeId.ROADMAP',
                                'format'                => 'csv',
			);

			$atts = shortcode_atts( $map_defaults, $args );

			// If the requested data is description, return the post content
			if ( 'description' == $atts['data'] )
				return $post->post_content;

                        // If we're looking for the full address, compose that from the options
                        if ( 'full-address' == $atts['data'] ) {
                                $postmeta = get_metadata( 'post', $post->ID );
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

                                return $full_address;
                        }

			// if the requested data is 'directions' return the link to google maps
			if ( 'directions' == $atts['data'] )
				return $this->get_directions_link( $post->ID );

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
                                $map = new SM_Map_Factory();
                                $map->set_map_atts( $r );

                                // Add Locations
                                foreach( $location_ids as $location_id ) {
                                        $map->add_location( $location_id );
                                }

                                return $map->get_iframe_embed();

                        }

                        // If the requested data is a taxonomy
                        $sm_taxes = $simple_map->get_sm_taxonomies( 'array', '', true, 'objects' ) ? $simple_map->get_sm_taxonomies( 'array', '', true, 'objects' ) : array();

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

                        // Look for postmeta with location_ prepended to it
                        if ( $value = get_post_meta( absint( $post->ID ), 'location_' . $atts['data'], true ) )
                                return str_replace( '%self%', $value, $atts['before'] . $value . $atts['after'] );

                        // Look for postmeta of another type
                        if ( $value = get_post_meta( absint( $post->ID ), $atts['data'], true ) )
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
