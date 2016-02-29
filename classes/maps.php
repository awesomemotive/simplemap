<?php
/**
 * This file creates maps for us. We're all about generating some maps.
 */

/**
 * SM_Map_Factory Class
 *
 * Handles all our map making duties (well it will be taking over shortly anyway).
 *
 * @since 2.4
*/
if ( ! class_exists( 'SM_Map_Factory' ) ) {

	class SM_Map_Factory {

		/**
		 * Map attributes
		*/
		var $map_atts;

		/**
		 * Locations - An array of location data that we are adding to the map.
		*/
		var $locations;

		/**
		 * Inits the templating system. Don't init class prior to template_redirect hook
		 *
		 * @since 2.4
		*/
		function __construct() {

			// Lets load the map with some defaults. This should be overwritten by calling the method directly
			$this->set_map_atts();

            // Prints the iframe
            if ( isset( $_GET['sm_map_iframe'] ) ) {
                add_action( 'template_redirect', array( &$this, 'add_iframe_locations' ), 2 );
                add_action( 'template_redirect', array( &$this, 'generate_iframe' ) );
            }
		}

		/**
		 * This loads all the attributes for the map itself
		 *
		 * 'map_width'
                 * 'map_height'
                 * 'default_lat'
                 * 'default_lng'
                 * 'zoom_level'
                 * 'default_radius'
                 * 'map_type'
                 * 'special_text'
                 * 'default_state'
                 * 'default_country'
                 * 'default_language'
                 * 'default_domain'
                 * 'map_stylesheet'
                 * 'units'
                 * 'autoload'
                 * 'lock_default_location'
                 * 'results_limit'
                 * 'address_format'
                 * 'powered_by'
                 * 'enable_permalinks'
                 * 'permalink_slug'
                 * 'display_search'
                 * 'map_pages'
                 * 'adsense_for_maps'
                 * 'adsense_pub_id'
                 * 'adsense_channel_id'
                 * 'adsense_max_ads'
		 *
		 * @since 2.4
		*/
		function set_map_atts( $atts=array() ) {

			global $simple_map;
            $locations = ! empty ( $_GET['location_ids'] ) ? explode( ',', $_GET['location_ids'] ) : array();

            // Do atts for iframes
            if ( isset( $_GET['sm_map_iframe'] ) ) {
                // Set atts from GET vars
                if ( ! empty( $_GET['map_width'] ) )
                    $atts['map_width'] = $_GET['map_width'];
                if ( ! empty( $_GET['map_height'] ) )
                    $atts['map_height'] = $_GET['map_height'];
                if ( ! empty( $_GET['pan_control'] ) )
                    $atts['panControl'] = $_GET['pan_control'];
                if ( ! empty( $_GET['zoom_control'] ) )
                    $atts['zoomControl'] = $_GET['zoom_control'];
                if ( ! empty( $_GET['scale_control'] ) )
                    $atts['scaleControl'] = $_GET['scale_control'];
                if ( ! empty( $_GET['street_view_control'] ) )
                    $atts['streetViewControl'] = $_GET['street_view_control'];
                if ( ! empty( $_GET['map_type_control'] ) )
                    $atts['mapTypeControl'] = $_GET['map_type_control'];
                if ( ! empty( $_GET['map_type'] ) )
                    $atts['mapType'] = $_GET['map_type'];
                if ( empty( $_GET['default_lat'] ) )
                    $atts['default_lat'] = get_post_meta( $locations[0], 'location_lat', true );
                if ( empty( $_GET['default_lng'] ) )
                    $atts['default_lng'] = get_post_meta( $locations[0], 'location_lng', true );
                if ( empty( $_GET['zoom_level'] ) )
                    $atts['zoom_level'] = 15;
            }

			// Default Options
			$defaults = $simple_map->get_options();

			// Overwrite defaults with any vars passed in
			$merged_atts = wp_parse_args( $atts, $defaults );

			// Kick back to property
			$this->map_atts = $merged_atts;	

		}

		/**
		 * Adds a location to the map
		 * 
		 * @since 2.4
		*/
		function add_location( $location ) {

            // $location can be a post object or a post ID. If its an object, grab the id
            if ( is_object( $location ) )
                $location = $location->ID;

            // Build array of important post data
			if ( $location_data = get_metadata( 'post', $location ) ) {
				$location_array = array(
					'id' 	=> $location,
					'lat'	=> ! empty( $location_data['location_lat'][0] ) ? $location_data['location_lat'][0] : false,
					'lng'	=> ! empty( $location_data['location_lng'][0] ) ? $location_data['location_lng'][0] : false
				);

				if ( $location_array['lat'] && $location_array['lng'] )
					$this->locations[$location] = $location_array;	
			}

		}

        /**
         * A wrapper for add_location for use when loading an iframe
         * 
         * @since 2.4
        */
        function add_iframe_locations() {

                // Add locations from GET string in iframe embed
                $locations = ! empty ( $_GET['location_ids'] ) ? explode( ',', $_GET['location_ids'] ) : array();
                foreach( $locations as $location ) {
                        $this->add_location( $location );
                }

        }

        /**
        * Returns the map code
        *
        * @since 2.4
        */
        function get_map() {

        }

        /**
        * Returns the link for the iframe embed
        *
        * @since 2.4
        */
        function get_iframe_embed() {

                $atts = $this->map_atts;
                $locations = array_keys( $this->locations );

                $iframe = '<iframe width="' . $atts['map_width'] . '" height="' . $atts['map_height'] . '" frameborder=0 scrolling="no" src="' . esc_url( site_url() ) . '?sm_map_iframe=1&map_width=' . esc_attr( $atts['map_width'] ) . '&map_height=' . esc_attr( $atts['map_height'] ) . '&location_ids=' . esc_attr( implode( ',', $locations ) ) . '"></iframe>';

                return $iframe;

        }

        /**
         * Generates the actual iframe
         *
         * @since 1.0
        */
        function generate_iframe() {

            if ( ! empty( $_GET['sm_map_iframe'] ) ) {
                global $simple_map;
                $this->set_map_atts();
                $atts = $this->map_atts;

                wp_enqueue_script('jquery');
                ?>
                <html style='margin-top:0 !important;padding-top:0 !important;'>
                    <head>
                        <?php wp_head(); ?>
                        <style type='text/css'>* { margin:0; padding:0; }</style>
                        <script src="<?php echo esc_url( SIMPLEMAP_MAPS_JS_API . '?v=3&amp;sensor=false&amp;language=' . $atts['default_language'] . '&amp;region=' . $atts['default_country'] ); ?>" type="text/javascript"></script> 
                    </head>
                    <body>

                        <script type="text/javascript"> 

                        var map = null;
                        var geocoder = null;
                        var latlng = new google.maps.LatLng( '<?php echo esc_js( $atts['default_lat'] ) ;?>', '<?php echo esc_js( $atts['default_lng'] ); ?>' );

                        function initialize() {
                            var myOptions = {
                                zoom: parseInt(<?php echo esc_js( $atts['zoom_level'] ); ?>),
                                center: latlng,
                                panControl: <?php echo ($atts['panControl']) ? 'true' : 'false'; ?>,
                                zoomControl: <?php echo ($atts['zoomControl']) ? 'true' : 'false'; ?>,
                                scaleControl: <?php echo ($atts['scaleControl']) ? 'true' : 'false'; ?>,
                                streetViewControl: <?php echo ($atts['streetViewControl']) ? 'true' : 'false'; ?>,
                                mapTypeControl: <?php echo ($atts['mapTypeControl']) ? 'true' : 'false'; ?>,
                                mapTypeId: google.maps.MapTypeId.ROADMAP,
                                draggable: false
                            };

                            map = new google.maps.Map( document.getElementById( "map_canvas" ), myOptions );
                            geocoder = new google.maps.Geocoder();
                        }

                        function addMapMarkers() {

                            var markersArray = [];
                            <?php foreach( $this->locations as $location ) { ?>
                                
                                <?php $customvals = get_metadata( 'post', $location['id'] ); ?>
                                
                                var name = '<?php echo esc_js( get_the_title( $location['id'] ) ); ?>';
                                var address = '<?php echo esc_js( $customvals['location_address'][0] ); ?>';
                                var address2 = '<?php echo esc_js( $customvals['location_address2'][0] ); ?>';
                                var city = '<?php echo esc_js( $customvals['location_city'][0] ); ?>';
                                var state = '<?php echo esc_js( $customvals['location_state'][0] ); ?>';
                                var zip = '<?php echo esc_js( $customvals['location_zip'][0] ); ?>';
                                var country = '<?php echo esc_js( $customvals['location_country'][0] ); ?>';
                                var email = '<?php echo esc_js( $customvals['location_email'][0] ); ?>';
                                var url = '<?php echo esc_js( $customvals['location_url'][0] ); ?>';
                                var phone = '<?php echo esc_js( $customvals['location_phone'][0] ); ?>';
                                var fax = '<?php echo esc_js( $customvals['location_fax'][0] ); ?>';
                                var special = '<?php echo esc_js( $customvals['location_special'][0] ); ?>';
                                
                                map.setCenter(latlng, 13);
                                var markerOptions = {};
                                if ( 'function' == typeof window.simplemapCustomMarkers ) {
                                        markerOptions = simplemapCustomMarkers( name, address, address2, city, state, zip, country, '', url, phone, fax, email, special, '', '', '');
                                }
                                markerOptions.map = map;

                                markerOptions.position = new google.maps.LatLng( '<?php echo esc_js( $location['lat'] ) ;?>', '<?php echo esc_js( $location['lng'] ); ?>' );
                                var marker = new google.maps.Marker( markerOptions );
                                markersArray.push(marker);
                            <?php } ?>

                        }
                        jQuery(document).ready( function() { initialize(); addMapMarkers(); } );

                        </script> 

                        <div id="map_canvas" style="height: <?php echo esc_attr( $_GET['map_height'] ); ?>; width: <?php echo esc_attr( $_GET['map_width'] ); ?>; border: 1px solid #eee; overflow: hidden"></div> 

                    </body>
                </html>
                <?php
                die();
            }

        }

	}

}
