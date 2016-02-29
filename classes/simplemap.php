<?php
if ( !class_exists( 'Simple_Map' ) ) {

	class Simple_Map {
	
		var $plugin_url;
		var $plugin_domain = 'SimpleMap';
		
		// Initialize the plugin
		function Simple_Map() {
			
			$plugin_dir = basename( SIMPLEMAP_PATH );
			load_plugin_textdomain( $this->plugin_domain, 'wp-content/plugins/' . $plugin_dir . '/lang', $plugin_dir . '/lang' );
			
			$this->plugin_url = SIMPLEMAP_URL;
						
			// Add shortcode handler
			add_shortcode( 'simplemap', array( &$this, 'display_map' ) );
						
			// Enqueue frontend scripts & styles into <head>
			add_action( 'template_redirect', array( &$this, 'enqueue_frontend_scripts_styles' ) );
			
			// Enqueue backend scripts
			add_action( 'init', array( &$this, 'enqueue_backend_scripts_styles' ) );

			// Add hook for master js file
			add_action( 'template_redirect', array( &$this, 'google_map_js_script' ) );

			// Add hook for general options js file
			add_action( 'init', array( &$this, 'general_options_js_script' ) );
			
			// Query vars
			add_filter( 'query_vars', array( &$this, 'register_query_vars' ) );

			// Backwards compat for core sm taxonomies
			add_filter( 'sm_category-text', array( &$this, 'backwards_compat_categories_text' ) );
			add_filter( 'sm_tag-text', array( &$this, 'backwards_compat_tags_text' ) );
			add_filter( 'sm_day-text', array( &$this, 'backwards_compat_days_text' ) );
			add_filter( 'sm_time-text', array( &$this, 'backwards_compat_times_text' ) );
            
		}

		// This function generates the code to display the map
		function display_map( $atts ) {

			$options = $this->get_options();

			$atts = $this->parse_shortcode_atts( $atts );

			extract( $atts );

			$to_display = '';

			$to_display .= $this->location_search_form( $atts );

			if ( $powered_by )
				$to_display .= '<div id="powered_by_simplemap">' . sprintf( __( 'Powered by %s SimpleMap', 'SimpleMap' ), '<a href="http://simplemap-plugin.com/" target="_blank">' ) . '</a></div>';

			// Hide map?
			$hidemap = ( $hide_map ) ? "display:none; " : '';

			// Hide list?
			$hidelist = $hide_list ? "display:none; " : '';
			
			// Map Width and height
			$map_width = ( '' == $map_width ) ? $options['map_width'] : $map_width;
			$map_height = ( '' == $map_height ) ? $options['map_height'] : $map_height;

			// Updating Div
			$sm_updating_img_src = apply_filters( 'sm_updating_img_src', SIMPLEMAP_URL . '/inc/images/loading.gif' );
			$sm_updating_div_size = apply_filters( 'sm_updating_img_size', 'height:' . $map_height . ';width:' . $map_width . ';' );
			$to_display .= '<div id="simplemap-updating" style="'. $sm_updating_div_size. '"><img src="' . $sm_updating_img_src . '" alt="' . __( 'Loading new locations', 'SimpleMap' ) . '" /></div>';

			$to_display .= '<div id="simplemap" style="' . $hidemap . 'width: ' . $map_width . '; height: ' . $map_height . ';"></div>';
			$to_display .= '<div id="results" style="' . $hidelist . 'width: ' . $map_width . ';"></div>';
			$to_display .= '<script type="text/javascript">';
			$to_display .= '(function($) { ';

			// Load Locations
			$is_sm_search = isset( $_REQUEST['location_is_search_results'] ) ? 1 : 0;

						
			$do_search_function = '
				load_simplemap( lat, lng, aspid, ascid, asma, shortcode_zoom_level, map_type, shortcode_autoload );
				//searchLocations( ' . absint( $is_sm_search ) . ' );
			';

			$to_display .= '$(document).ready(function() {
				var lat = "' . esc_js( $default_lat ) . '";
				var lng = "' . esc_js( $default_lng ) . '";
				var aspid = "' . esc_js( $adsense_publisher_id ) . '";
				var ascid = "' . esc_js( $adsense_channel_id ) . '";
				var asma = "' . esc_js( $adsense_max_ads ) . '";
				var shortcode_zoom_level = "' . esc_js( $zoom_level ) . '";
				var map_type = "' . esc_js( $map_type ) . '";
				var shortcode_autoload = "' . esc_js( $autoload ) . '";
				var auto_locate = "' . esc_js( $options['auto_locate'] ) . '";
				var sm_autolocate_complete = false;
				geocoder = new google.maps.Geocoder();

				if ( !' . absint( $is_sm_search ) . ' && auto_locate == "ip" ) {
					// alternatively, freegeoip.net/json/
					jQuery.getJSON( "http://www.telize.com/geoip/?callback=?", function(location) {
						lat = location.latitude;
						lng = location.longitude;

                    	if ( document.getElementById("location_search_city_field") ) {
							document.getElementById("location_search_city_field").value = location.city;
						}
	                    if ( document.getElementById("location_search_country_field") ) {
							document.getElementById("location_search_country_field").value = location.country_code;
						}
        	            if ( document.getElementById("location_search_state_field") ) {
							document.getElementById("location_search_state_field").value = location.region_code;
						}
			    if ( document.getElementById("location_search_zip_field") && typeof location.zipcode != "undefined" && location.zipcode != "undefined" ) {
							document.getElementById("location_search_zip_field").value = location.zipcode;
						}
						if ( document.getElementById("location_search_default_lat" ) ) {
							document.getElementById("location_search_default_lat").value = lat;
						}
						if ( document.getElementById("location_search_default_lng" ) ) {
							document.getElementById("location_search_default_lng").value = lng;
						}
						' . $do_search_function . '
						searchLocations( 1 );
					}).error(function() {
					 	' . $do_search_function . '
						searchLocations( ' . absint( $is_sm_search ) . ' );
					});
				}
				else if ( !' . absint( $is_sm_search ) . ' && auto_locate == "html5" ) {
					// Ugly hack for FireFox "Not Now" option
					setTimeout(function () { 
						if ( sm_autolocate_complete == false ) {
							' . $do_search_function . ' searchLocations( 0 );
						}
					}, 10000);

					navigator.geolocation.getCurrentPosition(
						function(position) {
							lat = position.coords.latitude;
							lng = position.coords.longitude;
							if ( document.getElementById("location_search_default_lat" ) ) {
								document.getElementById("location_search_default_lat").value = lat;
							}
							if ( document.getElementById("location_search_default_lng" ) ) {
								document.getElementById("location_search_default_lng").value = lng;
							}
							sm_autolocate_complete = true;
							' . $do_search_function . '
							searchLocations( 1 );
						},
						function(error) {
							sm_autolocate_complete = true;
							' . $do_search_function . '
							searchLocations( ' . absint( $is_sm_search ) . ' );
						},
						{
							maximumAge:300000,
							timeout:5000
						}
					);
				}
				else {
					sm_autolocate_complete = true;
					' . $do_search_function . '
					searchLocations( ' . absint( $is_sm_search ) . ' );
				}
			';

			$to_display .= '});';
			$to_display .= '})(jQuery);';
			$to_display .= '</script>';

			return apply_filters( 'sm-display-map', $to_display, $atts );
		}

		// This function returns the location search form
		function location_search_form( $atts ) {
			global $post;

			// Grab default SimpleMap options
			$options = $this->get_options();

			// Merge default simplemap options with default shortcode options and provided shortcode options
			$atts = $this->parse_shortcode_atts( $atts );

			// Create individual vars for each att
			extract( $atts );

			// Array of the names for all taxonomies registered with sm-location post type
			$sm_tax_names = get_object_taxonomies( 'sm-location' );

			// Array of field names for this form (with label syntax stripped
			$form_field_names = $this->get_form_field_names_from_shortcode_atts( $search_fields );

			// Form onsubmit, action, and method values
			$on_submit = apply_filters( 'sm-location-search-onsubmit', ' onsubmit="searchLocations( 1 ); return false; "', $post->ID );
			$action = apply_filters( 'sm-locaiton-search-action', get_permalink(), $post->ID );
			$method = apply_filters( 'sm-location-search-method', 'post', $post->ID );			

			// Form Field Values
			$address_value		= get_query_var( 'location_search_address' );
			$city_value 		= isset( $_REQUEST['location_search_city'] ) ? $_REQUEST['location_search_city'] : '';
			$state_value 		= isset( $_REQUEST['location_search_state'] ) ? $_REQUEST['location_search_state'] : '';
			$zip_value 			= get_query_var( 'location_search_zip' );
			$country_value 		= get_query_var( 'location_search_country' );
			$radius_value	 	= isset( $_REQUEST['location_search_distance'] ) ? $_REQUEST['location_search_distance'] : $radius;
			$limit_value		= isset( $_REQUEST['location_search_limit'] ) ? $_REQUEST['location_search_limit'] : $limit;
			$is_sm_search		= isset( $_REQUEST['location_is_search_results'] ) ? 1 : 0;

			// Normal Field inputs
			$ffi['street']		= array( 'label' => apply_filters( 'sm-search-label-street', __( 'Street: ', 'SimpleMap' ), $post ), 'input' => '<input type="text" id="location_search_address_field" name="location_search_address" value="' . esc_attr( $address_value ) . '" />' );
			$ffi['city']		= array( 'label' => apply_filters( 'sm-search-label-city', __( 'City: ', 'SimpleMap' ), $post ), 'input' => '<input type="text"  id="location_search_city_field" name="location_search_city" value="' . esc_attr( $city_value ) . '" />' );
			$ffi['state']		= array( 'label' => apply_filters( 'sm-search-label-state', __( 'State: ', 'SimpleMap' ), $post ), 'input' => '<input type="text" id="location_search_state_field" name="location_search_state" value="' . esc_attr( $state_value ) . '" />' );
			$ffi['zip']			= array( 'label' => apply_filters( 'sm-search-label-zip', __( 'Zip: ', 'SimpleMap' ), $post ), 'input' => '<input type="text" id="location_search_zip_field" name="location_search_zip" value="' . esc_attr( $zip_value ) . '" />' );
			$ffi['country']		= array( 'label' => apply_filters( 'sm-search-label-country', __( 'Country: ', 'SimpleMap' ), $post ), 'input' => '<input type="text" id="location_search_country_field" name="location_search_country" value="' . esc_attr( $country_value ) . '" />' );
			$ffi['empty']		= array( 'label' => '', 'input' => '' );
			$ffi['submit']		= array( 'label' => '', 'input' => '<input type="submit" value="' . apply_filters( 'sm-search-label-search', __('Search', 'SimpleMap'), $post ) . '" id="location_search_submit_field" class="submit" />' );
			$ffi['distance']	= $this->add_distance_field( $radius_value, $units );

			$hidden_fields = array();

			// Visible Taxonomy Field Inputs
			foreach ( $sm_tax_names as $tax_name ) {
				if ( in_array( $tax_name, $form_field_names ) && $this->show_taxonomy_filter( $atts, $tax_name ) )
					$ffi[$tax_name] = $this->add_taxonomy_fields( $atts, $tax_name );
				else
					$hidden_fields[] = '<input type="hidden" name="location_search_' . str_replace( '-', '_', $tax_name ) . '_field" value="1" />';
			}

			// More Taxonomy Fields
			foreach ( $sm_tax_names as $tax_name ) {
				$hidden_fields[] = '<input type="hidden" id="avail_' . str_replace( '-', '_', $tax_name ) . '" value="' . $atts[str_replace( '-', '_', $tax_name )] . '" />';
			}

			// Hide search?
			$hidesearch = $hide_search ? " style='display:none;' " : '';

			$location_search  = '<div id="map_search" >';
			$location_search .= '<a id="map_top"></a>';
			$location_search .= '<form ' . $on_submit . ' name="location_search_form" id="location_search_form" action="' . $action . '" method="' . $method . '">';

			$location_search .= '<table class="location_search"' . $hidesearch . '>';

			$location_search .= apply_filters( 'sm-location-search-table-top', '', $post );

			$location_search .= '<tr><td colspan="' . $search_form_cols . '" class="location_search_title">' . apply_filters( 'sm-location-search-title', $search_title, $post->ID ) . '</td></tr>';

			// Loop through field inputs and print table
			$search_form_tr = 0;
			$search_form_trs = array();
			$search_field_td = 1;
			$search_fields = explode( '||', $search_fields);

			if ( ! in_array( 'submit', $search_fields ) ) {
				$hidden_fields[] = '<input type="submit" style="display: none;" />';
			}

			foreach ( $search_fields as $field_key => $field_labelvalue ) {
				switch( substr( $field_labelvalue, 0, 8 ) ) {
					case 'labelbr_' :
						$field_label	= true;
						$field_br 		= '<br />';
						$field_value 	= substr( $field_labelvalue, 8 );
						break;

					case 'labelsp_' :
						$field_label	= true;
						$field_br		= '&nbsp';
						$field_value	= substr( $field_labelvalue, 8 );
						break;

					case 'labeltd_' :
						$field_label	= true;
						$field_br		= "</td>\n\t\t<td>";
						$field_value	= substr( $field_labelvalue, 8 );
						break;

					default :
						$field_label	= false;
						$field_br		= '';
						$field_value	= $field_labelvalue;
				}

				// Back compat for class names
				switch ( $field_value ) {
					case 'sm-category' :
						$class_value = 'cat';
						break;
					case 'sm-tag' :
						$class_value = 'tag';
						break;
					case 'sm-day' :
						$class_value = 'day';
						break;
					case 'sm-time' :
						$class_value = 'time';
						break;
					case 'address' :
						$class_value = 'street';
						break;
					default :
						$class_value = $field_value;
				}

				// Print open TR if on column 1
				if ( 1 === $search_field_td ) {
					$search_form_tr_data = "\n\t<tr id='location_search_" . esc_attr( $search_form_tr ) . "_tr' class='location_search_row'>";
					$search_form_tr++;
				}

				// Print field for this position
				if ( 'span' == $field_value ) {
					if ( $tr_data_array = explode( '<td ', $search_form_tr_data ) ) {
						$target_td = end( $tr_data_array );

						end( $tr_data_array );
						$key = key( $tr_data_array );

						if ( 'colspan="' != substr( $target_td, 0, 9 ) ) {
							$tr_data_array[$key] = 'colspan="2" ' . $target_td;
							//echo "<pre>";print_r($tr_data_array);die();
						} else {
							$numcells = substr( $target_td, 9, 1);
							$tr_data_array[$key] = substr_replace( $target_td, $numcells + 1, 9, 1 );
						}

						$search_form_tr_data = implode( '<td ', $tr_data_array );
					}
				} else {
					// The extra column needs to be counted independent of whether the field_value isset so that we don't lose count.
					if ( "</td>\n\t\t<td>" == $field_br ) {
						$search_field_td++;
						$field_br = "</td>\n\t\t<td id='location_search_" . esc_attr( substr( $field_labelvalue, 8 ) ) . "_fields'>";
					}

					if ( isset( $ffi[$field_value] ) && 'empty' != $field_value && 'span' != $field_value ) {
						// If the field_br is a space, we need to wrap the label in a div so that it floats left too.
						if ( '&nbsp' == $field_br ) {
							$ffi[$field_value]['label'] = '<div class="float_text_left">' . $ffi[$field_value]['label'] . '</div>';
						}

						$taxonomy_class = ( isset( $options['taxonomies'][$field_value] ) ? 'location_search_taxonomy_cell' : '' );
						$search_form_tr_data .= "\n\t\t<td class='location_search_" . esc_attr( $class_value ) . "_cell $taxonomy_class location_search_cell'>";

						if ( $field_label ) {
							if ( isset( $ffi[$field_value]['label'] ) ) {
								$search_form_tr_data .= $ffi[$field_value]['label'];
							}

							$search_form_tr_data .= $field_br;
						}

						$search_form_tr_data .= isset( $ffi[$field_value]['input'] ) ? $ffi[$field_value]['input'] . '</td>' : '</td>';
					} else {
						$search_form_tr_data .= "\n\t\t<td class='location_search_empty_cell location_search_cell'></td>";
					}
				}

				// Print close TR if on column 3 or higher (for safety)
				if ( $search_form_cols <= $search_field_td ) {
					$search_form_tr_data .= "\n\t</tr>";
					$search_field_td = 0;

					// Only keep the rows that contain an actionable item.
					if ( strpos( $search_form_tr_data, 'input' ) || strpos( $search_form_tr_data, 'select' ) ) {
						$search_form_trs[] = $search_form_tr_data;
					}
				}

				// Bump search field count
				$search_field_td++;
			}

			// Add table fields
			if ( ! empty( $search_form_trs ) ) {
				$location_search .= implode( ' ', $search_form_trs );
			}

			$location_search .= apply_filters( 'sm-location-search-before-submit', '', $post );

			$location_search .= '</table>';

			// Add hidden fields
			if ( ! empty( $hidden_fields ) ) {
				$location_search .= implode( ' ', $hidden_fields );
			}

			// Lat / Lng
			$location_search .= "<input type='hidden' id='location_search_default_lat' value='" . $default_lat . "' />";
			$location_search .= "<input type='hidden' id='location_search_default_lng' value='" . $default_lng . "' />";

			// Hidden value for limit
			$location_search .= "<input type='hidden' id='location_search_limit' value='" . $limit_value . "' />";

			// Hidden value set to true if we got here via search
			$location_search .= "<input type='hidden' id='location_is_search_results' name='sm-location-search' value='" . $is_sm_search . "' />";

			$location_search .= '</form>';
			$location_search .= '</div>'; // close map_search div

			return apply_filters( 'sm_location_search_form', $location_search, $atts );
		}

		// Separates form field names from label syntax attached to them when submitted via shortcode
		function get_form_field_names_from_shortcode_atts( $fields ) {

			// String to array
			$fields = explode( '||', $fields);

			foreach( $fields as $key => $field ) {

				switch( substr( $field, 0, 8 ) ) {

					case 'labelbr_' :
						$field_names[] 	= substr( $field, 8 );
						break;
					case 'labelsp_' :
						$field_names[]	= substr( $field, 8 );
						break;
					case 'labeltd_' :
						$field_names[]	= substr( $field, 8 );
						break;
					default :
						$field_names[]	= $field;
				}

			}

			return (array) $field_names;

		}

		// Determines if we're supposed to show this taxonomy's filter options in the form
		function show_taxonomy_filter( $atts, $tax_name ) {

			// Convert tax_name to PHP safe equiv
			$php_tax_name = str_replace( '-', '_', $tax_name );

			// Convert Given Taxonomy's 'show filter' into a generic one
			$key = 'show_' . $php_tax_name . '_filter';
			$show_taxes_filter = $atts[$key];

			if ( false != $show_taxes_filter && 'false' != $show_taxes_filter )
				return true;

			return false;

		}

		// Adds Distance field to form
		function add_distance_field( $radius_value, $units ) {
			global $post;

			// Distance
			$distance_input		= '<select id="location_search_distance_field" name="location_search_distance" >';
			foreach ( $this->get_search_radii() as $value ) {
				$r = (int) $value;
				$distance_input .= '<option value="' . $value . '"' . selected( $radius_value, $value, false ) . '>' . $value . ' ' . $units . "</option>\n";
			}
			$distance_input .= '</select>';

			return array( 'label' => apply_filters( 'sm-search-label-distance', __( 'Select a distance: ', 'SimpleMap' ), $post ), 'input' => $distance_input );
		
		}

		// Adds taxonomy fields to search form
		function add_taxonomy_fields( $atts, $taxonomy ) {
			global $post;

			// Get taxonomy object or return empty;
			if ( ! $tax_object = get_taxonomy( $taxonomy ) )
				return '';

			$options = $this->get_options();

			$atts = $this->parse_shortcode_atts( $atts );

			extract( $atts );

			$php_taxonomy = str_replace( '-', '_', $taxonomy );

			// Convert Specific Taxonomy var names and var values to Generic var names and var values
			$taxonomies 		= $atts[$php_taxonomy];
			$tax_hidden_name	= 'avail_' . $php_taxonomy;	
			$show_taxes_filter	= $atts['show_' . $php_taxonomy . '_filter'];
			$tax_field_name		= $php_taxonomy;

			// This originates at the comma separated list of taxonomy ids in the shortcode. ie: sm_category='1,3,5'
			$taxes_avail = $atts[$tax_hidden_name];

			// Place available taxes in array
			$taxes_avail = explode( ',', $taxes_avail );
			$taxes_array = array();

			// Loop through all cats and create array of available cats
			if ( $all_taxes = get_terms( $taxonomy ) ) {

				foreach ( $all_taxes as $key => $value ){
					if ( '' == $taxes_avail[0] || in_array( $value->term_id, $taxes_avail ) ) {
						$taxes_array[] = $value->term_id;
					}
				}

			}

			$taxes_avail = $taxes_array;

			// Show taxes filters if allowed
			$tax_search = '';
			$tax_label  = apply_filters( $php_taxonomy . '-text', __( $tax_object->labels->singular_name . ': ' ), 'SimpleMap' );

			$taxes_array = apply_filters( 'sm-search-from-taxonomies', $taxes_array, $taxonomy );

			if ( 'checkboxes' == $taxonomy_field_type ) {
				// Print checkbox for each available cat
				foreach( $taxes_array as $key => $taxid ) {
					if( $term = get_term_by( 'id', $taxid, $taxonomy ) ) {
						$tax_checked = isset( $_REQUEST['location_search_' . $tax_field_name . '_' . esc_attr( $term->term_id ) . 'field'] ) ? ' checked="checked" ' : '';
						$tax_search .= '<label for="location_search_' . $tax_field_name . '_field_' . esc_attr( $term->term_id ) . '" class="no-linebreak"><input rel="location_search_' . $tax_field_name . '_field" type="checkbox" name="location_search_' . $tax_field_name . '_' . esc_attr( $term->term_id ) . 'field" id="location_search_' . $tax_field_name . '_field_' . esc_attr( $term->term_id ) . '" value="' . esc_attr( $term->term_id ) . '" ' . $tax_checked . '/> ' . esc_attr( $term->name ) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> ';
					}
				}
			} elseif( 'select' == $taxonomy_field_type ) {
				// Print selectbox if that's what we're doing
				$tax_select  = "<select id='location_search_" . esc_attr( $tax_field_name ) . "_select' name='location_search_" . esc_attr( $tax_field_name ) . "_select' >";
				$tax_select .= "<option value=''>" . apply_filters( 'sm-search-tax-select-default', __( 'Select a value', 'SimpleMap' ), $taxonomy ) . "</option>";
				foreach( $taxes_array as $key => $taxid ) {
					if( $term = get_term_by( 'id', $taxid, $taxonomy ) ) {
						$tax_checked = isset( $_REQUEST['location_search_' . esc_attr( $tax_field_name ) . '_select' ] ) ? ' selected="selected" ' : '';
						$tax_select .= '<option rel="location_search_' . esc_attr( $tax_field_name ) . '_select_val"' . ' value="' . esc_attr( $term->term_id ) . '" ' . $tax_checked . '>' . esc_attr( $term->name ) . '</option>';
					}
				}
				$tax_select .= "</select>";

				if ( ! empty( $taxid ) )
					$tax_search .= $tax_select;
			}

			return array( 'label' => $tax_label, 'input' => $tax_search );
		}

		// This function enqueues all the javascript and stylesheets
		function enqueue_frontend_scripts_styles() {
			global $post;
			$options = $this->get_options();

			// Rewrite rules if we've changed the location permalink slug
			if ( get_option( 'sm-rewrite-rules' ) ) {
				global $wp_rewrite;
				$wp_rewrite->flush_rules();
				delete_option( 'sm-rewrite-rules' );
			}

			// Frontend only
			if ( ! is_admin() && is_object( $post ) || apply_filters( 'sm-force-frontend-js', '__return_false' ) ) {
				// Bail if we're not showing on all pages and this isn't a map page
				if ( ! in_array( $post->ID, explode( ',', $options['map_pages'] ) ) && ! in_array( 0, explode( ',', $options['map_pages'] ) ) )
					return false;

				// Check for use of custom stylesheet and load styles
				if ( strstr( $options['map_stylesheet'], 'simplemap-styles' ) )
					$style_url = plugins_url() . '/' . $options['map_stylesheet'];
				else
					$style_url = SIMPLEMAP_URL . '/' . $options['map_stylesheet'];

				// Load styles
				wp_enqueue_style( 'simplemap-map-style', $style_url );

				// Scripts
				wp_enqueue_script( 'simplemap-master-js', get_home_url() . '?simplemap-master-js=1&smpid=' . $post->ID, array( 'jquery' ) );

				// Google API v3 does not need a key
				$url_params = array(
					'sensor' => 'false',
					'v' => '3',
					'language' => $options['default_language'],
					'region' => $options['default_country'],
				);
				if ( $options['adsense_for_maps'] ) {
					$url_params['libraries'] = 'adsense';
				}
				wp_enqueue_script( 'simplemap-google-api', SIMPLEMAP_MAPS_JS_API . http_build_query( $url_params, '', '&amp;' ) );
			}
		}

		// This function enqueues all the javascript and stylesheets
		function enqueue_backend_scripts_styles() {
			// Admin only
			if ( is_admin() ) {
				$options = $this->get_options();
				wp_enqueue_style( 'simplemap-admin', SIMPLEMAP_URL . '/inc/styles/admin.css' );

				// SimpleMap General options
				if ( isset( $_GET['page'] ) && 'simplemap' == $_GET['page'] )
					wp_enqueue_script( 'simplemap-general-options-js', get_home_url() . '/?simplemap-general-options-js', array( 'jquery' ) );

				// Google API v3 does not need a key
				$url_params = array(
					'v' => '3',
					'sensor' => 'false',
					'language' => $options['default_language'],
					'region' => $options['default_country'],
				);
				wp_enqueue_script( 'simplemap-google-api', SIMPLEMAP_MAPS_JS_API . http_build_query( $url_params, '', '&amp;' ) );
			}
		}

		// JS Script for general options page
		function general_options_js_script() {
			if ( ! isset( $_GET['simplemap-general-options-js'] ) )
				return;

			header( 'Status: 200 OK', false, 200 );
			header( 'Content-type: application/x-javascript' );
			do_action( 'sm-master-js-headers' );

			$options = $this->get_options();

			do_action( 'sm-general-options-js' );
			?>
			function codeAddress() {
				// if this is modified, modify mirror function in master-js php function
				var d_address = document.getElementById("default_address").value;

				geocoder = new google.maps.Geocoder();
				geocoder.geocode( { 'address': d_address }, function( results, status ) {
					if ( status == google.maps.GeocoderStatus.OK ) {
						var latlng = results[0].geometry.location;
						document.getElementById("default_lat").value = latlng.lat();
						document.getElementById("default_lng").value = latlng.lng();
					} else {
						alert("Geocode was not successful for the following reason: " + status);
					}
				});
			}
			<?php
			die();
		}

		// This function prints the JS
		function google_map_js_script() {
			if ( ! isset( $_GET['simplemap-master-js'] ) )
				return;

			header( "HTTP/1.1 200 OK" );
			header( "Content-type: application/x-javascript" );
			$options = $this->get_options();

			?>
			var default_lat 			= <?php echo esc_js( $options['default_lat'] ); ?>;
			var default_lng 			= <?php echo esc_js( $options['default_lng'] ); ?>;
			var default_radius 			= <?php echo esc_js( $options['default_radius'] ); ?>;
			var zoom_level 				= '<?php echo esc_js( $options['zoom_level'] ); ?>';
			var map_width 				= '<?php echo esc_js( $options['map_width'] ); ?>';
			var map_height 				= '<?php echo esc_js( $options['map_height'] ); ?>';
			var special_text 			= '<?php echo esc_js( $options['special_text'] ); ?>';
			var units 					= '<?php echo esc_js( $options['units'] ); ?>';
			var limit 					= '<?php echo esc_js( $options['results_limit'] ); ?>';
			var plugin_url 				= '<?php echo esc_js( SIMPLEMAP_URL ); ?>';
			var visit_website_text 		= '<?php echo apply_filters( 'sm-visit-website-text', __( 'Visit Website', 'SimpleMap' ) ); ?>';
			var get_directions_text		= '<?php echo apply_filters( 'sm-get-directions-text', __( 'Get Directions', 'SimpleMap' ) ); ?>';
			var location_tab_text		= '<?php echo apply_filters( 'sm-location-text', __( 'Location', 'SimpleMap' ) ); ?>';
			var description_tab_text	= '<?php echo apply_filters( 'sm-description-text', __( 'Description', 'SimpleMap' ) ); ?>';
			var phone_text				= '<?php echo apply_filters( 'sm-phone-text', __( 'Phone', 'SimpleMap' ) ); ?>';
			var fax_text				= '<?php echo apply_filters( 'sm-fax-text', __( 'Fax', 'SimpleMap' ) ); ?>';
			var email_text				= '<?php echo apply_filters( 'sm-email-text', __( 'Email', 'SimpleMap' ) ); ?>';

			var taxonomy_text = {};
			<?php
			if ( $taxonomies = $this->get_sm_taxonomies( 'array', '', true, 'object' ) ) {
				foreach( $taxonomies as $taxonomy ) {
					?>
					taxonomy_text.<?php echo $taxonomy->name; ?> = '<?php echo apply_filters( $taxonomy->name . '-text', __( $taxonomy->labels->name, 'SimpleMap' ) ); ?>';
					<?php
				}
			}
			?>
			var noresults_text			= '<?php echo apply_filters( 'sm-no-results-found-text', __( 'No results found.', 'SimpleMap' ) ); ?>';
			var default_domain 			= '<?php echo esc_js( $options['default_domain'] ); ?>';
			var address_format 			= '<?php echo esc_js( $options['address_format'] ); ?>';
			var siteurl					= '<?php echo esc_js( get_home_url() ); ?>';
			var map;
			var geocoder;
			var autoload				= '<?php echo esc_js( $options['autoload'] ); ?>';
			var auto_locate				= '<?php echo esc_js( $options['auto_locate'] ); ?>';
			var markersArray = [];
			var infowindowsArray = [];

			function clearInfoWindows() {
				if (infowindowsArray) {
					for (var i=0;i<infowindowsArray.length;i++) {
						infowindowsArray[i].close();
					}
				}
			}

			function clearOverlays() {
				if (markersArray) {
					for (var i=0;i<markersArray.length;i++) {
						markersArray[i].setMap(null);
					}
				}
			}

			//function load_simplemap( lat, lng, aspid, ascid, asma ) {
			function load_simplemap( lat, lng, aspid, ascid, asma, shortcode_zoom_level, map_type, shortcode_autoload ) {

				zoom_level = shortcode_zoom_level;
                autoload = shortcode_autoload;
				<?php 
				/*
				if ( '' == $options['api_key'] ) {
					?>
					jQuery( "#simplemap" ).html( "<p style='padding:10px;'><?php printf( __( 'You must enter an API Key in <a href=\"%s\">General Settings</a> before your maps will work.', 'SimpleMap' ), admin_url( 'admin.php?page=simplemap' ) ); ?></p>" );
				<?php
				}
				*/

				do_action( 'sm-load-simplemap-js-top' );
				?>
  
				if ( lat == 0 ) {
					lat = '<?php echo esc_js( $options['default_lat'] ); ?>';
				}

				if ( lng == 0 ) {
					lng = '<?php echo esc_js( $options['default_lng'] ); ?>';
				}

				var latlng = new google.maps.LatLng( lat, lng );
				var myOptions = {
					zoom: parseInt(zoom_level),
					center: latlng,
					mapTypeId: google.maps.MapTypeId[map_type] 
				};
				map = new google.maps.Map( document.getElementById( "simplemap" ), myOptions );

				// Adsense for Google Maps
				<?php 
				if ( '' != $options['adsense_pub_id'] && $options['adsense_for_maps'] ) {

					$default_adsense_publisher_id = isset( $options['adsense_pub_id'] ) ? $options['adsense_pub_id'] : '';
					$default_adsense_channel_id = isset( $options['adsense_channel_id'] ) ? $options['adsense_channel_id'] : '';
					$default_adsense_max_ads = isset( $options['adsense_max_ads'] ) ? $options['adsense_max_ads'] : 2;

					?>

					// Adsense ID. If no shortcode, check for options. If not options, use blank string.
					if ( aspid == 0 )
						aspid = '<?php echo esc_js( $default_adsense_publisher_id ); ?>';

					// Channel ID. If no shortcode, check for options. If not options, use blank string.
					if ( ascid == 0 )
						ascid = '<?php echo esc_js( $default_adsense_channel_id ); ?>';

					// Max ads per map. If no shortcode, check for options. If no options, use 2.
					if ( asma == 0 )
						asma = '<?php echo esc_js( $default_adsense_max_ads ); ?>';

					var publisher_id = aspid;

					var adUnitDiv = document.createElement('div');
					var adUnitOptions = {
						channelNumber: ascid,
						format: google.maps.adsense.AdFormat.HALF_BANNER,
						position: google.maps.ControlPosition.TOP,
						map: map,
						visible: true,
						publisherId: publisher_id
					};
					adUnit = new google.maps.adsense.AdUnit(adUnitDiv, adUnitOptions);
				<?php
				}
				
				do_action( 'sm-load-simplemap-js-bottom' );				
				?>
			}

			function codeAddress() {
				// if this is modified, modify mirror function in general-options-js php function 
				var d_address = document.getElementById("default_address").value;

				geocoder = new google.maps.Geocoder();
				geocoder.geocode( { 'address': d_address }, function( results, status ) {
					if ( status == google.maps.GeocoderStatus.OK ) {
						var latlng = results[0].geometry.location;
						document.getElementById("default_lat").value = latlng.lat();
						document.getElementById("default_lng").value = latlng.lng();
					} else {
						alert("Geocode was not successful for the following reason: " + status);
					}
				});
			}

			function codeNewAddress() {
				if (document.getElementById("store_lat").value != '' && document.getElementById("store_lng").value != '') {
					document.new_location_form.submit();
				}
				else {
					var address = '';
					var street = document.getElementById("store_address").value;
					var city = document.getElementById("store_city").value;
					var state = document.getElementById("store_state").value;
					var country = document.getElementById("store_country").value;

					if (street) { address += street + ', '; }
					if (city) { address += city + ', '; }
					if (state) { address += state + ', '; }
					address += country;

					geocoder = new google.maps.Geocoder();
					geocoder.geocode( { 'address': address }, function( results, status ) {
						if ( status == google.maps.GeocoderStatus.OK ) {
							var latlng = results[0].geometry.location;
							document.getElementById("store_lat").value = latlng.lat();
							document.getElementById("store_lng").value = latlng.lng();
							document.new_location_form.submit();
						} else {
							alert("Geocode was not successful for the following reason: " + status);
						}
					});
				}
			}

			function codeChangedAddress() {
				var address = '';
				var street = document.getElementById("store_address").value;
				var city = document.getElementById("store_city").value;
				var state = document.getElementById("store_state").value;
				var country = document.getElementById("store_country").value;

				if (street) { address += street + ', '; }
				if (city) { address += city + ', '; }
				if (state) { address += state + ', '; }
				address += country;

				geocoder = new google.maps.Geocoder();
				geocoder.geocode( { 'address': address }, function( results, status ) {
					if ( status == google.maps.GeocoderStatus.OK ) {
						var latlng = results[0].geometry.location;
						document.getElementById("store_lat").value = latlng.lat();
						document.getElementById("store_lng").value = latlng.lng();
					} else {
						alert("Geocode was not successful for the following reason: " + status);
					}
				});
			}

			function searchLocations( is_search ) {
				// Init searchData
				var searchData = {};
				searchData.taxes = {};

				// Set defaults for search form fields
				searchData.address	= '';
				searchData.city 	= '';
				searchData.state	= '';
				searchData.zip		= '';
				searchData.country	= '';

				if ( null != document.getElementById('location_search_address_field') ) {
					searchData.address = document.getElementById('location_search_address_field').value;
				}

				if ( null != document.getElementById('location_search_city_field') ) {
					searchData.city = document.getElementById('location_search_city_field').value;
				}

				if ( null != document.getElementById('location_search_country_field') ) {
					searchData.country = document.getElementById('location_search_country_field').value;;
				}

				if ( null != document.getElementById('location_search_state_field') ) {
					searchData.state = document.getElementById('location_search_state_field').value;
				}

				if ( null != document.getElementById('location_search_zip_field') ) {
					searchData.zip = document.getElementById('location_search_zip_field').value;
				}

				if ( null != document.getElementById('location_search_distance_field') ) {
					searchData.radius = document.getElementById('location_search_distance_field').value;
				}

				searchData.lat			= document.getElementById('location_search_default_lat').value;
				searchData.lng			= document.getElementById('location_search_default_lng').value;
				searchData.limit		= document.getElementById('location_search_limit').value; 
				searchData.searching	= document.getElementById('location_is_search_results').value;

				// Do SimpleMap Taxonomies
				<?php 
				if ( $taxnames = get_object_taxonomies( 'sm-location' ) ) {

					foreach ( $taxnames as $name ) {
						$php_name = str_replace( '-', '_', $name );
						?>

						// Do taxnonomy for checkboxes
						searchData.taxes.<?php echo $php_name; ?> = '';
						var checks_found = false;
						jQuery( 'input[rel=location_search_<?php echo $php_name; ?>_field]' ).each( function() {
							checks_found = true;
							if ( jQuery( this ).attr( 'checked' ) && jQuery( this ).attr( 'value' ) != null ) {
								<?php echo 'searchData.taxes.' . $php_name; ?> += jQuery( this ).attr( 'value' ) + ',';
							}
						});

						// Do taxnonomy for select box if checks weren't found
						if ( false == checks_found ) {	
							jQuery( 'option[rel=location_search_<?php echo $php_name; ?>_select_val]' ).each( function() {
								if ( jQuery( this ).attr( 'selected' ) && jQuery( this ).attr( 'value' ) != null ) {
									<?php echo 'searchData.taxes.' . $php_name; ?> += jQuery( this ).attr( 'value' ) + ',';
								}
							});
						}

						<?php
					}
				}
				?>

				var query = '';
				var start = 0;
 
				if ( searchData.address && searchData.address != '' ) {
					query += searchData.address + ', ';
				}

				if ( searchData.city && searchData.city != '' ) {
					query += searchData.city + ', ';
				}

				if ( searchData.state && searchData.state != '' ) {
					query += searchData.state + ', ';
				}

				if ( searchData.zip && searchData.zip != '' ) {
					query += searchData.zip + ', ';
				}

				if ( searchData.country && searchData.country != '' ) {
					query += searchData.country + ', ';
				}

				// Query
				if ( query != null ) {
					query = query.slice(0, -2);
				}

				if ( searchData.limit == '' || searchData.limit == null ) {
					searchData.limit = 0;
				}

				if ( searchData.radius == '' || searchData.radius == null ) {
					searchData.radius = 0;
				}

				// Taxonomies
				<?php 
				if ( $taxnames = get_object_taxonomies( 'sm-location' ) ) {

					foreach ( $taxnames as $name ) {
						$php_name = str_replace( '-', '_', $name );
						?>

						if ( <?php echo 'searchData.taxes.' . $php_name; ?> != null ) {
							var _<?php echo $php_name; ?> = <?php echo 'searchData.taxes.' . $php_name; ?>.slice(0, -1);
						} else {
							var _<?php echo $php_name; ?> = '';
						}

						// Append available taxes logic if no taxes are selected but limited taxes were passed through shortcode as available
						if ( '' != document.getElementById('avail_<?php echo $php_name; ?>').value && '' == _<?php echo $php_name; ?> ) {
							_<?php echo $php_name; ?> = 'OR,' + document.getElementById('avail_<?php echo $php_name; ?>').value;
						}

						searchData.taxes.<?php echo $php_name; ?> = _<?php echo $php_name; ?>;

						<?php
					}
				}
				?>

				// Load default location if query is empty
				if ( query == '' || query == null ) {

					if ( searchData.lat != 0 && searchData.lng != 0 )
						query = searchData.lat + ', ' + searchData.lng;
					else
						query = '<?php echo esc_js( $options['default_lat'] ); ?>, <?php echo esc_js( $options['default_lng'] ); ?>';

				}

				// Searching
				if ( 1 == searchData.searching || 1 == is_search ) {
					is_search = 1;
					searchData.source = 'search';
				} else {
					is_search = 0;
					searchData.source = 'initial_load';
				}

				geocoder.geocode( { 'address': query }, function( results, status ) {
					if ( status == google.maps.GeocoderStatus.OK ) {
						searchData.center = results[0].geometry.location;
						if ( 'none' != autoload || is_search ) {
							if ( 'all' == autoload && is_search != 1 ) {
								searchData.radius = 0;
								searchData.limit = 0;
							}

							if (! searchData.center) {
								searchData.center = new GLatLng( 44.9799654, -93.2638361 );
							}
							searchData.query_type = 'all';
							searchData.mapLock = 'unlock';
							searchData.homeAddress = query;

							searchLocationsNear(searchData); 
						}
					}
				});
			}

			function searchLocationsNear(searchData) {
				// Radius
				if ( searchData.radius != null && searchData.radius != '' ) {
					searchData.radius = parseInt( searchData.radius );

					if ( units == 'km' ) {
						searchData.radius = parseInt( searchData.radius ) / 1.609344;
					}
				} else if ( autoload == 'all' ) {
					searchData.radius = 0;
				} else {
					if ( units == 'mi' ) {
						searchData.radius = parseInt( default_radius );
					} else if ( units == 'km' ) {
						searchData.radius = parseInt( default_radius ) / 1.609344;
					}
				}

				// Build search URL
				<?php 
				if ( $taxonomies = $this->get_sm_taxonomies( 'array', '', true ) ) {
					$js_tax_string = '';
					foreach( $taxonomies as $taxonomy ) {
						$js_tax_string .= "'&$taxonomy=' + searchData.taxes.$taxonomy + ";
					}
				}
				?>

				var searchUrl = siteurl + '/?sm-xml-search=1&lat=' + searchData.center.lat() + '&lng=' + searchData.center.lng() + '&radius=' + searchData.radius + '&namequery=' + searchData.homeAddress + '&query_type=' + searchData.query_type  + '&limit=' + searchData.limit + <?php echo $js_tax_string; ?>'&address=' + searchData.address + '&city=' + searchData.city + '&state=' + searchData.state + '&zip=' + searchData.zip + '&pid=<?php echo esc_js( absint( $_GET['smpid'] ) ); ?>';

				<?php if ( apply_filters( 'sm-use-updating-image', true ) ) : ?>
				// Display Updating Message and hide search results
				if ( jQuery( "#simplemap" ).is(":visible") ) {
					jQuery( "#simplemap" ).hide();
					jQuery( "#simplemap-updating" ).show();
				}
				<?php endif; ?>
				jQuery( "#results" ).html( '' );

				jQuery.get( searchUrl, {}, function(data) {
					<?php if ( apply_filters( 'sm-use-updating-image', true ) ) : ?>
					// Hide Updating Message
					if ( jQuery( "#simplemap-updating" ).is(":visible") ) {
						jQuery( "#simplemap-updating" ).hide();
						jQuery( "#simplemap" ).show();
					}
					<?php endif; ?>

					clearOverlays();

					var results = document.getElementById('results');
					results.innerHTML = '';

					var markers = jQuery( eval( data ) );
					if (markers.length == 0) {
						results.innerHTML = '<h3>' + noresults_text + '</h3>';
						map.setCenter( searchData.center );
						return;
					}

					var bounds = new google.maps.LatLngBounds();
					markers.each( function () {
						var locationData = this;
						locationData.distance = parseFloat(locationData.distance);
						locationData.point = new google.maps.LatLng(parseFloat(locationData.lat), parseFloat(locationData.lng));
						locationData.homeAddress = searchData.homeAddress;

						var marker = createMarker(locationData);
						var sidebarEntry = createSidebarEntry(marker, locationData, searchData);
						results.appendChild(sidebarEntry);
						bounds.extend(locationData.point);
					});

					// Make centeral marker on search
					if ( 'search' == searchData.source && <?php echo apply_filters( 'sm-show-search-marker-image', 'true' ); ?>) {
						var searchMarkerOptions = {};
						searchMarkerOptions.map = map;
						searchMarkerOptions.position = searchData.center;

						searchMarkerOptions.icon = new google.maps.MarkerImage( 
							'<?php echo esc_js( apply_filters( 'sm-search-marker-image-url', SIMPLEMAP_URL . "/inc/images/blue-dot.png" ) );?>',
							new google.maps.Size(20, 32),
							new google.maps.Point(0,0),
							new google.maps.Point(0,32)
						);

						var searchMarkerTitle = '';
						if ( '' != searchData.address ) {
								searchMarkerTitle += searchData.address + ' ';
						}
						if ( '' != searchData.city ) {
								searchMarkerTitle += searchData.city + ' ';
						}
						if ( '' != searchData.state ) {
								searchMarkerTitle += searchData.state + ' ';
						}
						if ( '' != searchData.zip ) {
								searchMarkerTitle += searchData.zip + ' ';
						}

						var searchMarker = new google.maps.Marker( searchMarkerOptions );
						searchMarker.title = searchMarkerTitle;
						markersArray.push(searchMarker);
						bounds.extend(searchMarkerOptions.position);
					}

					// If the search button was clicked, limit to a 15px zoom
					if ( 'search' == searchData.source ) {
						map.fitBounds( bounds );
						if ( map.getZoom() > 15 ) {
							map.setZoom( 15 );
						}
					} else {
						// If initial load of map, zoom to default settings
						map.setZoom(parseInt(zoom_level));
					}
                    
					// Paranoia - fix container sizing bug -- pdb
					google.maps.event.addListener(map, "idle", function(){
						google.maps.event.trigger(map, 'resize');
					});
				});
			}

			function stringFilter(s) {
				filteredValues = "emnpxt%";     // Characters stripped out
				var i;
				var returnString = "";
				for (i = 0; i < s.length; i++) {  // Search through string and append to unfiltered values to returnString.
					var c = s.charAt(i);
					if (filteredValues.indexOf(c) == -1) returnString += c;
				}
				return returnString;
			}

			function createMarker( locationData ) {

				// Init tax heights
				locationData.taxonomyheights = [];

				// Allow plugin users to define Maker Options (including custom images)
				var markerOptions = {};
				if ( 'function' == typeof window.simplemapCustomMarkers ) {
					markerOptions = simplemapCustomMarkers( locationData );
				}

				// Allow developers to turn of description in bubble. (Return true to hide)
				<?php if ( true === apply_filters( 'sm-hide-bubble-description', false ) ) : ?>
				locationData.description = '';
				<?php endif; ?>

				markerOptions.map = map;
				markerOptions.position = locationData.point;
				var marker = new google.maps.Marker( markerOptions );
				marker.title = locationData.name;
				markersArray.push(marker);

				var mapwidth;
				var mapheight;
				var maxbubblewidth;
				var maxbubbleheight;
				
				mapwidth = document.getElementById("simplemap");
				if ( typeof mapwidth != 'undefined' ) {
					mapwidth = mapwidth.offsetWidth;
				} else {
					if ( typeof map_width != 'undefined' ) {
						mapwidth = Number(stringFilter(map_width));						
					} else {
						mapwidth = 400;
					}
				}
				
				mapheight = document.getElementById("simplemap");
				if ( typeof mapheight != 'undefined' ) {
					mapheight = mapheight.offsetHeight;
				} else {
					if ( typeof map_height != 'undefined' ) {
						mapheight = Number(stringFilter(map_height));						
					} else {
						mapheight = 200;
					}
				}
				maxbubblewidth = Math.round(mapwidth / 1.5);
				maxbubbleheight = Math.round(mapheight / 2.2);

				var fontsize = 12;
				var lineheight = 12;

				if (locationData.taxes.sm_category && locationData.taxes.sm_category != '' ) {
					var titleheight = 3 + Math.floor((locationData.name.length + locationData.taxes.sm_category.length) * fontsize / (maxbubblewidth * 1.5));
				} else {
					var titleheight = 3 + Math.floor((locationData.name.length) * fontsize / (maxbubblewidth * 1.5));
				}

				var addressheight = 2;
				if (locationData.address2 != '') {
					addressheight += 1;
				}
				if (locationData.phone != '' || locationData.fax != '') {
					addressheight += 1;
					if (locationData.phone != '') {
						addressheight += 1;
					}
					if (locationData.fax != '') {
						addressheight += 1;
					}
				}

				for (jstax in locationData.taxes) {
					if ( locationData.taxes[jstax] !== '' ) {
						locationData.taxonomyheights[jstax] = 3 + Math.floor((locationData.taxes[jstax][length]) * fontsize / (maxbubblewidth * 1.5));
					}
				}
				var linksheight = 2;

				var totalheight = titleheight + addressheight;
				for (jstax in locationData.taxes) {
					if ( 'sm_category' != jstax ) {
						totalheight += locationData.taxonomyheights[jstax];
					}
				}
				totalheight = (totalheight + 1) * fontsize;

				if (totalheight > maxbubbleheight) {
					totalheight = maxbubbleheight;
				}
				
				if ( isNaN( totalheight ) || totalheight > maxbubbleheight ) {
					totalheight = maxbubbleheight;
				}

				var html = '<div class="markertext" style="background-color:#fff;padding:10px;max-width: ' + maxbubblewidth + 'px; height: ' + totalheight + 'px; overflow-y: auto; overflow-x: hidden;">';
				html += '<h3 style="margin-top: 0; padding-top: 0; border-top: none;">';

				if ( '' != locationData.permalink ) {
					html += '<a href="' + locationData.permalink + '">';
				}
				html += locationData.name;

				if ( '' != locationData.permalink ) {
					html += '</a>';
				}

				if (locationData.taxes.sm_category && locationData.taxes.sm_category != null && locationData.taxes.sm_category != '' ) {
					html += '<br /><span class="bubble_category">' + locationData.taxes.sm_category + '</span>';
				}

				html += '</h3>';

				html += '<p class="buble_address">' + locationData.address;
				if (locationData.address2 != '') {
					html += '<br />' + locationData.address2;
				}
				
				// Address Data
				if (address_format == 'town, province postalcode') {
					html += '<br />' + locationData.city + ', ' + locationData.state + ' ' + locationData.zip + '</p>';
				} else if (address_format == 'town province postalcode') {
					html += '<br />' + locationData.city + ' ' + locationData.state + ' ' + locationData.zip + '</p>';
				} else if (address_format == 'town-province postalcode') {
					html += '<br />' + locationData.city + '-' + locationData.state + ' ' + locationData.zip + '</p>';
				} else if (address_format == 'postalcode town-province') {
					html += '<br />' + locationData.zip + ' ' + locationData.city + '-' + locationData.state + '</p>';
				} else if (address_format == 'postalcode town, province') {
					html += '<br />' + locationData.zip + ' ' + locationData.city + ', ' + locationData.state + '</p>';
				} else if (address_format == 'postalcode town') {
					html += '<br />' + locationData.zip + ' ' + locationData.city + '</p>';
				} else if (address_format == 'town postalcode') {
					html += '<br />' + locationData.city + ' ' + locationData.zip + '</p>';
				}

				// Phone and Fax Data
				if (locationData.phone != null && locationData.phone != '') {
					html += '<p class="bubble_contact"><span class="bubble_phone">' + phone_text + ': ' + locationData.phone + '</span>';
					if (locationData.email != null && locationData.email != '') {
						html += '<br />' + email_text + ': <a class="bubble_email" href="mailto:' + locationData.email + '">' + locationData.email + '</a>';
					}
					if (locationData.fax != null && locationData.fax != '') {
						html += '<br /><span class="bubble_fax">' + fax_text + ': ' + locationData.fax + '</span>';
					}
					html += '</p>';
				} else if (locationData.fax != null && locationData.fax != '') {
					html += '<p>' + fax_text + ': ' + locationData.fax + '</p>';
				}
								
				html += '<p class="bubble_tags">';
				
				for (jstax in locationData.taxes) {
					if ( 'sm_category' == jstax ) {
						continue;
					}
					if ( locationData.taxes[jstax] != null && locationData.taxes[jstax] != '' ) {
						html += taxonomy_text[jstax] + ': ' + locationData.taxes[jstax] + '<br />';
					}
				}
				html += '</p>';

					var dir_address = locationData.point.toUrlValue(10);
					var dir_address2 = '';
					if (locationData.address) { dir_address2 += locationData.address; }
					if (locationData.city) { if ( '' != dir_address2 ) { dir_address2 += ' '; } dir_address2 += locationData.city; };
					if (locationData.state) { if ( '' != dir_address2 ) { dir_address2 += ' '; } dir_address2 += locationData.state; };
					if (locationData.zip) { if ( '' != dir_address2 ) { dir_address2 += ' '; } dir_address2 += locationData.zip; };
					if (locationData.country) { if ( '' != dir_address2 ) { dir_address2 += ' '; } dir_address2 += locationData.country; };

					if ( '' != dir_address2 ) { dir_address = locationData.point.toUrlValue(10) + '(' + escape( dir_address2 ) + ')'; };
								
				html += '		<p class="bubble_links"><a class="bubble_directions" href="http://google' + default_domain + '/maps?saddr=' + locationData.homeAddress + '&daddr=' + dir_address + '" target="_blank">' + get_directions_text + '</a>';
								if (locationData.url != '') {
				html += '			<span class="bubble_website">&nbsp;|&nbsp;<a href="' + locationData.url + '" title="' + locationData.name + '" target="_blank">' + visit_website_text + '</a></span>';
								}
				html += '		</p>';

				if (locationData.description != '' && locationData.description != null) {
					var numlines = Math.ceil(locationData.description.length / 40);
					var newlines = locationData.description.split('<br />').length - 1;
					var totalheight2 = 0;

					if ( locationData.description.indexOf('<img') == -1) {
						totalheight2 = (numlines + newlines + 1) * fontsize;
					}
					else {
						var numberindex = locationData.description.indexOf('height=') + 8;
						var numberend = locationData.description.indexOf('"', numberindex);
						var imageheight = Number(locationData.description.substring(numberindex, numberend));

						totalheight2 = ((numlines + newlines - 2) * fontsize) + imageheight;
					}

					if (totalheight2 > maxbubbleheight) {
						totalheight2 = maxbubbleheight;
					}

					//marker.openInfoWindowTabsHtml([new GInfoWindowTab(location_tab_text, html), new GInfoWindowTab(description_tab_text, html2)], {maxWidth: maxbubblewidth});
					// tabs aren't possible with the Google Maps api v3
					html += '<hr /><p>' + locationData.description + '</p>';
				}

				html += '	</div>';
/**
 * @name InfoBox
 * @version 1.1.13 [March 19, 2014]
 * @author Gary Little (inspired by proof-of-concept code from Pamela Fox of Google)
 * @copyright Copyright 2010 Gary Little [gary at luxcentral.com]
 */
eval(function(p,a,c,k,e,r){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('7 8(a){a=a||{};r.s.1R.2k(2,3d);2.Q=a.1v||"";2.1H=a.1B||J;2.S=a.1G||0;2.H=a.1z||1h r.s.1Y(0,0);2.B=a.U||1h r.s.2E(0,0);2.15=a.13||t;2.1p=a.1t||"2h";2.1m=a.F||{};2.1E=a.1C||"3g";2.P=a.1j||"3b://38.r.33/2Y/2T/2N/1r.2K";3(a.1j===""){2.P=""}2.1f=a.1x||1h r.s.1Y(1,1);3(q a.A==="p"){3(q a.18==="p"){a.A=L}v{a.A=!a.18}}2.w=!a.A;2.17=a.1n||J;2.1I=a.2g||"2e";2.16=a.1l||J;2.4=t;2.z=t;2.14=t;2.V=t;2.E=t;2.R=t}8.9=1h r.s.1R();8.9.25=7(){5 i;5 f;5 a;5 d=2;5 c=7(e){e.20=L;3(e.1i){e.1i()}};5 b=7(e){e.30=J;3(e.1Z){e.1Z()}3(!d.16){c(e)}};3(!2.4){2.4=1e.2S("2Q");2.1d();3(q 2.Q.1u==="p"){2.4.O=2.G()+2.Q}v{2.4.O=2.G();2.4.1a(2.Q)}2.2J()[2.1I].1a(2.4);2.1w();3(2.4.6.D){2.R=L}v{3(2.S!==0&&2.4.Z>2.S){2.4.6.D=2.S;2.4.6.2D="2A";2.R=L}v{a=2.1P();2.4.6.D=(2.4.Z-a.W-a.11)+"12";2.R=J}}2.1F(2.1H);3(!2.16){2.E=[];f=["2t","1O","2q","2p","1M","2o","2n","2m","2l"];1o(i=0;i<f.1L;i++){2.E.1K(r.s.u.19(2.4,f[i],c))}2.E.1K(r.s.u.19(2.4,"1O",7(e){2.6.1J="2j"}))}2.V=r.s.u.19(2.4,"2i",b);r.s.u.T(2,"2f")}};8.9.G=7(){5 a="";3(2.P!==""){a="<2d";a+=" 2c=\'"+2.P+"\'";a+=" 2b=11";a+=" 6=\'";a+=" U: 2a;";a+=" 1J: 29;";a+=" 28: "+2.1E+";";a+="\'>"}K a};8.9.1w=7(){5 a;3(2.P!==""){a=2.4.3n;2.z=r.s.u.19(a,"1M",2.27())}v{2.z=t}};8.9.27=7(){5 a=2;K 7(e){e.20=L;3(e.1i){e.1i()}r.s.u.T(a,"3m");a.1r()}};8.9.1F=7(d){5 m;5 n;5 e=0,I=0;3(!d){m=2.1D();3(m 3l r.s.3k){3(!m.26().3h(2.B)){m.3f(2.B)}n=m.26();5 a=m.3e();5 h=a.Z;5 f=a.24;5 k=2.H.D;5 l=2.H.1k;5 g=2.4.Z;5 b=2.4.24;5 i=2.1f.D;5 j=2.1f.1k;5 o=2.23().3c(2.B);3(o.x<(-k+i)){e=o.x+k-i}v 3((o.x+g+k+i)>h){e=o.x+g+k+i-h}3(2.17){3(o.y<(-l+j+b)){I=o.y+l-j-b}v 3((o.y+l+j)>f){I=o.y+l+j-f}}v{3(o.y<(-l+j)){I=o.y+l-j}v 3((o.y+b+l+j)>f){I=o.y+b+l+j-f}}3(!(e===0&&I===0)){5 c=m.3a();m.39(e,I)}}}};8.9.1d=7(){5 i,F;3(2.4){2.4.37=2.1p;2.4.6.36="";F=2.1m;1o(i 35 F){3(F.34(i)){2.4.6[i]=F[i]}}2.4.6.32="31(0)";3(q 2.4.6.X!=="p"&&2.4.6.X!==""){2.4.6.2Z="\\"2X:2W.2V.2U(2R="+(2.4.6.X*1X)+")\\"";2.4.6.2P="2O(X="+(2.4.6.X*1X)+")"}2.4.6.U="2M";2.4.6.M=\'1c\';3(2.15!==t){2.4.6.13=2.15}}};8.9.1P=7(){5 c;5 a={1b:0,1g:0,W:0,11:0};5 b=2.4;3(1e.1s&&1e.1s.1W){c=b.2L.1s.1W(b,"");3(c){a.1b=C(c.1V,10)||0;a.1g=C(c.1U,10)||0;a.W=C(c.1T,10)||0;a.11=C(c.1S,10)||0}}v 3(1e.2I.N){3(b.N){a.1b=C(b.N.1V,10)||0;a.1g=C(b.N.1U,10)||0;a.W=C(b.N.1T,10)||0;a.11=C(b.N.1S,10)||0}}K a};8.9.2H=7(){3(2.4){2.4.2G.2F(2.4);2.4=t}};8.9.1y=7(){2.25();5 a=2.23().2C(2.B);2.4.6.W=(a.x+2.H.D)+"12";3(2.17){2.4.6.1g=-(a.y+2.H.1k)+"12"}v{2.4.6.1b=(a.y+2.H.1k)+"12"}3(2.w){2.4.6.M="1c"}v{2.4.6.M="A"}};8.9.2B=7(a){3(q a.1t!=="p"){2.1p=a.1t;2.1d()}3(q a.F!=="p"){2.1m=a.F;2.1d()}3(q a.1v!=="p"){2.1Q(a.1v)}3(q a.1B!=="p"){2.1H=a.1B}3(q a.1G!=="p"){2.S=a.1G}3(q a.1z!=="p"){2.H=a.1z}3(q a.1n!=="p"){2.17=a.1n}3(q a.U!=="p"){2.1q(a.U)}3(q a.13!=="p"){2.22(a.13)}3(q a.1C!=="p"){2.1E=a.1C}3(q a.1j!=="p"){2.P=a.1j}3(q a.1x!=="p"){2.1f=a.1x}3(q a.18!=="p"){2.w=a.18}3(q a.A!=="p"){2.w=!a.A}3(q a.1l!=="p"){2.16=a.1l}3(2.4){2.1y()}};8.9.1Q=7(a){2.Q=a;3(2.4){3(2.z){r.s.u.Y(2.z);2.z=t}3(!2.R){2.4.6.D=""}3(q a.1u==="p"){2.4.O=2.G()+a}v{2.4.O=2.G();2.4.1a(a)}3(!2.R){2.4.6.D=2.4.Z+"12";3(q a.1u==="p"){2.4.O=2.G()+a}v{2.4.O=2.G();2.4.1a(a)}}2.1w()}r.s.u.T(2,"2z")};8.9.1q=7(a){2.B=a;3(2.4){2.1y()}r.s.u.T(2,"21")};8.9.22=7(a){2.15=a;3(2.4){2.4.6.13=a}r.s.u.T(2,"2y")};8.9.2x=7(a){2.w=!a;3(2.4){2.4.6.M=(2.w?"1c":"A")}};8.9.2w=7(){K 2.Q};8.9.1A=7(){K 2.B};8.9.2v=7(){K 2.15};8.9.2u=7(){5 a;3((q 2.1D()==="p")||(2.1D()===t)){a=J}v{a=!2.w}K a};8.9.3i=7(){2.w=J;3(2.4){2.4.6.M="A"}};8.9.3j=7(){2.w=L;3(2.4){2.4.6.M="1c"}};8.9.2s=7(c,b){5 a=2;3(b){2.B=b.1A();2.14=r.s.u.2r(b,"21",7(){a.1q(2.1A())})}2.1N(c);3(2.4){2.1F()}};8.9.1r=7(){5 i;3(2.z){r.s.u.Y(2.z);2.z=t}3(2.E){1o(i=0;i<2.E.1L;i++){r.s.u.Y(2.E[i])}2.E=t}3(2.14){r.s.u.Y(2.14);2.14=t}3(2.V){r.s.u.Y(2.V);2.V=t}2.1N(t)};',62,210,'||this|if|div_|var|style|function|InfoBox|prototype||||||||||||||||undefined|typeof|google|maps|null|event|else|isHidden_|||closeListener_|visible|position_|parseInt|width|eventListeners_|boxStyle|getCloseBoxImg_|pixelOffset_|yOffset|false|return|true|visibility|currentStyle|innerHTML|closeBoxURL_|content_|fixedWidthSet_|maxWidth_|trigger|position|contextListener_|left|opacity|removeListener|offsetWidth||right|px|zIndex|moveListener_|zIndex_|enableEventPropagation_|alignBottom_|isHidden|addDomListener|appendChild|top|hidden|setBoxStyle_|document|infoBoxClearance_|bottom|new|stopPropagation|closeBoxURL|height|enableEventPropagation|boxStyle_|alignBottom|for|boxClass_|setPosition|close|defaultView|boxClass|nodeType|content|addClickHandler_|infoBoxClearance|draw|pixelOffset|getPosition|disableAutoPan|closeBoxMargin|getMap|closeBoxMargin_|panBox_|maxWidth|disableAutoPan_|pane_|cursor|push|length|click|setMap|mouseover|getBoxWidths_|setContent|OverlayView|borderRightWidth|borderLeftWidth|borderBottomWidth|borderTopWidth|getComputedStyle|100|Size|preventDefault|cancelBubble|position_changed|setZIndex|getProjection|offsetHeight|createInfoBoxDiv_|getBounds|getCloseClickHandler_|margin|pointer|relative|align|src|img|floatPane|domready|pane|infoBox|contextmenu|default|apply|touchmove|touchend|touchstart|dblclick|mouseup|mouseout|addListener|open|mousedown|getVisible|getZIndex|getContent|setVisible|zindex_changed|content_changed|auto|setOptions|fromLatLngToDivPixel|overflow|LatLng|removeChild|parentNode|onRemove|documentElement|getPanes|gif|ownerDocument|absolute|mapfiles|alpha|filter|div|Opacity|createElement|en_us|Alpha|Microsoft|DXImageTransform|progid|intl|MsFilter|returnValue|translateZ|WebkitTransform|com|hasOwnProperty|in|cssText|className|www|panBy|getCenter|http|fromLatLngToContainerPixel|arguments|getDiv|setCenter|2px|contains|show|hide|Map|instanceof|closeclick|firstChild'.split('|'),0,{}));

				google.maps.event.addListener(marker, 'click', function() {
					clearInfoWindows();
					var infowidth = 0;
					if ( maxbubblewidth <= 100 ) {
						infowidth = document.getElementById("simplemap");
						if ( typeof infowidth != 'undefined' ) {
							infowidth = infowidth.offsetWidth;
						} else {
							infowidth = 400;
						}
					    infowidth = infowidth * (maxbubblewidth / 100.0);
					}
					if ( infowidth < maxbubblewidth ) infowidth = maxbubblewidth;
					infowidth = parseInt(infowidth) + 'px';
/*
					var infowindow = new google.maps.InfoWindow({
						maxWidth: infowidth,
						content: html
					});				
*/

					var infowindow = new InfoBox({
						maxWidth: infowidth
						,content: html
		,disableAutoPan: false
		,pixelOffset: new google.maps.Size(-80,-80)
		,alignBottom: true
		,zIndex: null
		,boxStyle: { 
		  opacity: 1.0
		 ,fontSize:'11pt'
		 }
		,closeBoxURL: "http://www.google.com/intl/en_us/mapfiles/close.gif"
		,infoBoxClearance: new google.maps.Size(1, 1)
		,isHidden: false
		,pane: "floatPane"
		,enableEventPropagation: true

					});				
					infowindow.open(map, marker);
					infowindowsArray.push(infowindow);
					window.location = '#map_top';
				});

				return marker;
			}

			function createSidebarEntry(marker, locationData, searchData) {
				var div = document.createElement('div');

				// Beginning of result
				var html = '<div id="location_' + locationData.postid + '" class="result">';

				// Flagged special
				if (locationData.special == 1 && special_text != '') {
					html += '<div class="special">' + special_text + '</div>';
				}

				// Name & distance
				html += '<div class="result_name">';
				html += '<h3 style="margin-top: 0; padding-top: 0; border-top: none;">';
				if (locationData.permalink != null && locationData.permalink != '') {
					html += '<a href="' + locationData.permalink + '">';
				}
				html += locationData.name;
				if (locationData.permalink != null && locationData.permalink != '') {
					html += '</a>';
				}

				if (locationData.distance.toFixed(1) != 'NaN') {
					if (units == 'mi') {
						html+= ' <small class="result_distance">' + locationData.distance.toFixed(1) + ' miles</small>';
					}
					else if (units == 'km') {
						html+= ' <small class="result_distance">' + (locationData.distance * 1.609344).toFixed(1) + ' km</small>';
					}
				}
				html += '</h3></div>';

				// Address
				html += '<div class="result_address"><address>' + locationData.address;
				if (locationData.address2 != '') {
					html += '<br />' + locationData.address2;
				}

				if (address_format == 'town, province postalcode') {
					html += '<br />' + locationData.city + ', ' + locationData.state + ' ' + locationData.zip + '</address></div>';
				}
				else if (address_format == 'town province postalcode') {
					html += '<br />' + locationData.city + ' ' + locationData.state + ' ' + locationData.zip + '</address></div>';
				}
				else if (address_format == 'town-province postalcode') {
					html += '<br />' + locationData.city + '-' + locationData.state + ' ' + locationData.zip + '</address></div>';
				}
				else if (address_format == 'postalcode town-province') {
					html += '<br />' + locationData.zip + ' ' + locationData.city + '-' + locationData.state + '</address></div>';
				}
				else if (address_format == 'postalcode town, province') {
					html += '<br />' + locationData.zip + ' ' + locationData.city + ', ' + locationData.state + '</address></div>';
				}
				else if (address_format == 'postalcode town') {
					html += '<br />' + locationData.zip + ' ' + locationData.city + '</address></div>';
				}
				else if (address_format == 'town postalcode') {
					html += '<br />' + locationData.city + ' ' + locationData.zip + '</address></div>';
				}

				// Phone, email, and fax numbers
				html += '<div class="result_phone">';
				if (locationData.phone != null && locationData.phone != '') {
					html += phone_text + ': ' + locationData.phone;
				}
				if (locationData.email != null && locationData.email != '') {
					html += '<span class="result_email"><br />' + email_text + ': <a href="mailto:' + locationData.email + '">' + locationData.email + '</a></span>';
				}
				if (locationData.fax != null && locationData.fax != '') {
					html += '<span class="result_fax"><br />' + fax_text + ': ' + locationData.fax + '</span>';
				}
				html += '</div>';

				// Links section
				html += '<div class="result_links">';

				// Visit Website link
				html += '<div class="result_website">';
				if (locationData.url != null && locationData.url != 'http://' && locationData.url != '') {
					html += '<a href="' + locationData.url + '" title="' + locationData.name + '" target="_blank">' + visit_website_text + '</a>';
				}
				html += '</div>';

				// Get Directions link
				if (locationData.distance.toFixed(1) != 'NaN') {
					var dir_address = locationData.point.toUrlValue(10);
					var dir_address2 = '';
					if (locationData.address) { dir_address2 += locationData.address; }
					if (locationData.city) { if ( '' != dir_address2 ) { dir_address2 += ' '; } dir_address2 += locationData.city };
					if (locationData.state) { if ( '' != dir_address2 ) { dir_address2 += ' '; } dir_address2 += locationData.state };
					if (locationData.zip) { if ( '' != dir_address2 ) { dir_address2 += ' '; } dir_address2 += locationData.zip };
					if (locationData.country) { if ( '' != dir_address2 ) { dir_address2 += ' '; } dir_address2 += locationData.country };
					if ( '' != dir_address2 ) { dir_address += '(' + escape( dir_address2 ) + ')' };

					html += '<a class="result_directions" href="http://google' + default_domain + '/maps?saddr=' + searchData.homeAddress + '&daddr=' + dir_address + '" target="_blank">' + get_directions_text + '</a>';
				}
				html += '</div>';
				html += '<div style="clear: both;"></div>';

				<?php if ( apply_filters( 'sm-show-results-description', false ) ) : ?>
				html += '<div class="sm-results-description">';
				html += locationData.description;
				html += '</div>';
				html += '<div style="clear:both;"></div>';
				<?php endif; ?>

				// Taxonomy lists
				for (jstax in locationData.taxes) {
					if ( locationData.taxes[jstax] != null && locationData.taxes[jstax] != '' ) {
						html += '<div class="' + jstax + '_list"><small><strong>' + taxonomy_text[jstax] + ':</strong> ' + locationData.taxes[jstax] + '</small></div>';
					}
				}

				// End of result
				html += '</div>';

				div.innerHTML = html;
				div.style.cursor = 'pointer'; 
				div.style.margin = 0;
				google.maps.event.addDomListener(div, 'click', function() {
					google.maps.event.trigger(marker, 'click');
				});
				return div;
			}
			<?php
			die();
		}

		// This function geocodes a location
		function geocode_location( $address='', $city='', $state='', $zip='', $country='', $key='' ) {
			$options = $this->get_options();
			// Create URL encoded comma separated list of address elements that != ''
			$to_geocode = urlencode( implode( ', ', array_filter( compact( 'address', 'city', 'state', 'zip', 'country' ) ) ) );

			// Base URL
			$base_url = SIMPLEMAP_MAPS_WS_API . 'geocode/json?sensor=false&region=' . substr( $options['default_domain'], strrpos( $options['default_domain'], '.' ) + 1 );

			// Add query
			$request_url = $base_url . "&address=" . $to_geocode;

			$response = wp_remote_get( $request_url );

			// TODO: Handle this situation better
			if ( ! is_wp_error( $response ) ) {
				$body = json_decode( $response['body'] );
				$status = $body->status;

				if ( $status == 'OK' ) {
					// Successful geocode
					//echo "<pre>";print_r( $body );die();
					$location = $body->results[0]->geometry->location;

					// Format: Longitude, Latitude, Altitude
					$lat = $location->lat;
					$lng = $location->lng;
				}

				return compact( 'body', 'status', 'lat', 'lng' );
			} else {
				return false;
			}
		}

		// Returns list of SimpleMap Taxonomies
		function get_sm_taxonomies( $format='array', $prefix='', $php_safe=false, $output='names' ) {

			$taxes = array();

			if ( $taxes = get_object_taxonomies( 'sm-location', $output ) ) {

				foreach( $taxes as $key => $tax ) {

					// Convert to PHP safe and add prefix
					if ( $php_safe && 'names' == $output )
						$taxes[$key] = str_replace( '-', '_', $prefix.$tax );
					elseif ( $php_safe )
						$taxes[$key]->name = str_replace( '-', '_', $prefix.$tax->name );

				}

			}

			// Convert to string if needed
			if ( 'string' == $format )
				$taxes = implode( ', ', $taxes );

			return $taxes;

		}

		function get_taxonomy_settings( $taxonomy = null ) {
			$standard_taxonomies = array(
				'sm-category' => array( 'singular' => 'Category', 'plural' => 'Categories', 'hierarchical' => true, 'field' => 'category' ),
				'sm-tag' => array( 'singular' => 'Tag', 'plural' => 'Tags', 'field' => 'tags' ),
				'sm-day' => array( 'singular' => 'Day', 'plural' => 'Days', 'field' => 'days', 'description' => 'day of week' ),
				'sm-time' => array( 'singular' => 'Time', 'plural' => 'Times', 'field' => 'times', 'description' => 'time of day' ),
			);

			if ( empty( $taxonomy ) ) {
				return $standard_taxonomies;
			}

			if ( isset( $standard_taxonomies[$taxonomy] ) ) {
				return $standard_taxonomies[$taxonomy];
			}
			else {
				$singular = ucwords( substr( $taxonomy, strpos( $taxonomy, '-' ) + 1 ) );
				return array( 'singular' => $singular, 'plural' => $singular . 's' );
			}
		}

		// This function returns the default SimpleMap options		
		function get_options() {
			$options = array();
			$saved = get_option( 'SimpleMap_options' );

			if ( !empty( $saved ) ) {
				$options = $saved;
			}

			static $default = null;
			if ( empty( $default ) ) {
				$default = array(
					'map_width' => '100%',
					'map_height' => '350px',
					'default_lat' => '44.968684',
					'default_lng' => '-93.215561',
					'zoom_level' => '10',
					'default_radius' => '10',
					'map_type' => 'ROADMAP',
					'special_text' => '',
					'default_state' => '',
					'default_country' => 'US',
					'default_language' => 'en',
					'default_domain' => '.com',
					'map_stylesheet' => 'inc/styles/light.css',
					'units' => 'mi',
					'autoload' => 'all',
					'lock_default_location' => false,
					'results_limit' => '20',
					'address_format' => 'town, province postalcode',
					'powered_by' => 0,
					'enable_permalinks' => 0,
					'permalink_slug' => 'location',
					'display_search' => 'show',
					'map_pages' => '0',
					'adsense_for_maps' => 0,
					'adsense_pub_id' => '',
					'adsense_channel_id' => '',
					'adsense_max_ads' => 2,
					//'api_key' => '',
					'auto_locate' => '',
					'taxonomies' => array(
						'sm-category' => $this->get_taxonomy_settings( 'sm-category' ),
						'sm-tag' => $this->get_taxonomy_settings( 'sm-tag' ),
					),
				);

				$valid_map_type_map = array(
					'ROADMAP' => 'ROADMAP',
					'SATELLITE' => 'SATELLITE',
					'HYBRID' => 'HYBRID',
					'TERRAIN' => 'TERRAIN',
					'G_NORMAL_MAP' => 'ROADMAP',
					'G_SATELLITE_MAP' => 'SATELLITE',
					'G_HYBRID_MAP' => 'HYBRID',
					'G_PHYSICAL_MAP' => 'TERRAIN',
				);

				if ( empty( $valid_map_type_map[$options['map_type']] ) ) {
					$options['map_type'] = $default['map_type'];
				}
				else {
					$options['map_type'] = $valid_map_type_map[$options['map_type']];
				}
			}

			$options += $default;

			if ( isset( $options['days_taxonomy'] ) ) {
				if ( ! empty( $options['days_taxonomy'] ) ) {
					$options['taxonomies']['sm-day'] = $this->get_taxonomy_settings( 'sm-day' );
				}
				unset( $options['days_taxonomy'] );
			}

			if ( isset( $options['time_taxonomy'] ) ) {
				if ( ! empty( $options['time_taxonomy'] ) ) {
					$options['taxonomies']['sm-time'] = $this->get_taxonomy_settings( 'sm-time' );
				}
				unset( $options['time_taxonomy'] );
			}

			if ( $saved != $options ) {
				update_option( 'SimpleMap_options', $options );
			}
			return $options;
		}
		
		// Google Domains
		function get_domain_options() {
			$domains_list = array(
				'United States' => '.com',
				'Austria' => '.at',
				'Australia' => '.com.au',
				'Bosnia and Herzegovina' => '.com.ba',
				'Belgium' => '.be',
				'Brazil' => '.com.br',
				'Canada' => '.ca',
				'Switzerland' => '.ch',
				'Czech Republic' => '.cz',
				'Germany' => '.de',
				'Denmark' => '.dk',
				'Spain' => '.es',
				'Finland' => '.fi',
				'France' => '.fr',
				'Italy' => '.it',
				'Japan' => '.jp',
				'Netherlands' => '.nl',
				'Norway' => '.no',
				'New Zealand' => '.co.nz',
				'Poland' => '.pl',
				'Russia' => '.ru',
				'Sweden' => '.se',
				'Taiwan' => '.tw',
				'United Kingdom' => '.co.uk',
				'South Africa' => '.co.za'
			);
			
			return apply_filters( 'sm-domain-list', $domains_list );
		}

		// Region list from http://code.google.com/apis/adwords/docs/appendix/provincecodes.html
		// Used for Maps v3 localization: http://code.google.com/apis/maps/documentation/javascript/basics.html#Localization
		function get_region_options() {
			$region_list = array(
				'US' => 'United States',
				'AR' => 'Argentina',
				'AU' => 'Australia',
				'AT' => 'Austria',
				'BE' => 'Belgium',
				'BR' => 'Brazil',
				'CA' => 'Canada',
				'CL' => 'Chile',
				'CN' => 'China',
				'CO' => 'Colombia',
				'HR' => 'Croatia',
				'CZ' => 'Czech Republic',
				'DK' => 'Denmark',
				'EG' => 'Egypt',
				'FI' => 'Finland',
				'FR' => 'France',
				'DE' => 'Germany',
				'HU' => 'Hungary',
				'IN' => 'India',
				'IE' => 'Ireland',
				'IL' => 'Israel',
				'IT' => 'Italy',
				'JP' => 'Japan',
				'MY' => 'Malaysia',
				'MX' => 'Mexico',
				'MA' => 'Morocco',
				'NL' => 'Netherlands',
				'NZ' => 'New Zealand',
				'NG' => 'Nigeria',
				'NO' => 'Norway',
				'PL' => 'Poland',
				'PT' => 'Portugal',
				'RU' => 'Russian Federation',
				'SA' => 'Saudi Arabia',
				'ZA' => 'South Africa',
				'KR' => 'South Korea',
				'ES' => 'Spain',
				'SE' => 'Sweden',
				'CH' => 'Switzerland',
				'TH' => 'Thailand',
				'TR' => 'Turkey',
				'UA' => 'Ukraine',
				'GB' => 'United Kingdom',
			);

			return apply_filters( 'sm-region-list', $region_list );
		}

		// Country list
		function get_country_options() {
			$country_list = array(
				'US' => 'United States',
				'AF' => 'Afghanistan',
				'AL' => 'Albania',
				'DZ' => 'Algeria',
				'AS' => 'American Samoa',
				'AD' => 'Andorra',
				'AO' => 'Angola',
				'AI' => 'Anguilla',
				'AQ' => 'Antarctica',
				'AG' => 'Antigua and Barbuda',
				'AR' => 'Argentina',
				'AM' => 'Armenia',
				'AW' => 'Aruba',
				'AU' => 'Australia',
				'AT' => 'Austria',
				'AZ' => 'Azerbaijan',
				'BS' => 'Bahamas',
				'BH' => 'Bahrain',
				'BD' => 'Bangladesh',
				'BB' => 'Barbados',
				'BY' => 'Belarus',
				'BE' => 'Belgium',
				'BZ' => 'Belize',
				'BJ' => 'Benin',
				'BM' => 'Bermuda',
				'BT' => 'Bhutan',
				'BO' => 'Bolivia',
				'BA' => 'Bosnia and Herzegowina',
				'BW' => 'Botswana',
				'BV' => 'Bouvet Island',
				'BR' => 'Brazil',
				'IO' => 'British Indian Ocean Territory',
				'BN' => 'Brunei Darussalam',
				'BG' => 'Bulgaria',
				'BF' => 'Burkina Faso',
				'BI' => 'Burundi',
				'KH' => 'Cambodia',
				'CM' => 'Cameroon',
				'CA' => 'Canada',
				'CV' => 'Cape Verde',
				'KY' => 'Cayman Islands',
				'CF' => 'Central African Republic',
				'TD' => 'Chad',
				'CL' => 'Chile',
				'CN' => 'China',
				'CX' => 'Christmas Island',
				'CC' => 'Cocos (Keeling) Islands',
				'CO' => 'Colombia',
				'KM' => 'Comoros',
				'CG' => 'Congo',
				'CD' => 'Congo, The Democratic Republic of the',
				'CK' => 'Cook Islands',
				'CR' => 'Costa Rica',
				'CI' => 'Cote D\'Ivoire',
				'HR' => 'Croatia (Local Name: Hrvatska)',
				'CU' => 'Cuba',
				'CY' => 'Cyprus',
				'CZ' => 'Czech Republic',
				'DK' => 'Denmark',
				'DJ' => 'Djibouti',
				'DM' => 'Dominica',
				'DO' => 'Dominican Republic',
				'TP' => 'East Timor',
				'EC' => 'Ecuador',
				'EG' => 'Egypt',
				'SV' => 'El Salvador',
				'GQ' => 'Equatorial Guinea',
				'ER' => 'Eritrea',
				'EE' => 'Estonia',
				'ET' => 'Ethiopia',
				'FK' => 'Falkland Islands (Malvinas)',
				'FO' => 'Faroe Islands',
				'FJ' => 'Fiji',
				'FI' => 'Finland',
				'FR' => 'France',
				'FX' => 'France, Metropolitan',
				'GF' => 'French Guiana',
				'PF' => 'French Polynesia',
				'TF' => 'French Southern Territories',
				'GA' => 'Gabon',
				'GM' => 'Gambia',
				'GE' => 'Georgia',
				'DE' => 'Germany',
				'GH' => 'Ghana',
				'GI' => 'Gibraltar',
				'GR' => 'Greece',
				'GL' => 'Greenland',
				'GD' => 'Grenada',
				'GP' => 'Guadeloupe',
				'GU' => 'Guam',
				'GT' => 'Guatemala',
				'GN' => 'Guinea',
				'GW' => 'Guinea-Bissau',
				'GY' => 'Guyana',
				'HT' => 'Haiti',
				'HM' => 'Heard and Mc Donald Islands',
				'VA' => 'Holy See (Vatican City State)',
				'HN' => 'Honduras',
				'HK' => 'Hong Kong',
				'HU' => 'Hungary',
				'IS' => 'Iceland',
				'IN' => 'India',
				'ID' => 'Indonesia',
				'IR' => 'Iran (Islamic Republic of)',
				'IQ' => 'Iraq',
				'IE' => 'Ireland',
				'IL' => 'Israel',
				'IT' => 'Italy',
				'JM' => 'Jamaica',
				'JP' => 'Japan',
				'JO' => 'Jordan',
				'KZ' => 'Kazakhstan',
				'KE' => 'Kenya',
				'KI' => 'Kiribati',
				'KP' => 'Korea, Democratic People\'s Republic of',
				'KR' => 'Korea, Republic of',
				'KW' => 'Kuwait',
				'KG' => 'Kyrgyzstan',
				'LA' => 'Lao People\'s Democratic Republic',
				'LV' => 'Latvia',
				'LB' => 'Lebanon',
				'LS' => 'Lesotho',
				'LR' => 'Liberia',
				'LY' => 'Libyan Arab Jamahiriya',
				'LI' => 'Liechtenstein',
				'LT' => 'Lithuania',
				'LU' => 'Luxembourg',
				'MO' => 'Macau',
				'MK' => 'Macedonia, Former Yugoslav Republic of',
				'MG' => 'Madagascar',
				'MW' => 'Malawi',
				'MY' => 'Malaysia',
				'MV' => 'Maldives',
				'ML' => 'Mali',
				'MT' => 'Malta',
				'MH' => 'Marshall Islands',
				'MQ' => 'Martinique',
				'MR' => 'Mauritania',
				'MU' => 'Mauritius',
				'YT' => 'Mayotte',
				'MX' => 'Mexico',
				'FM' => 'Micronesia, Federated States of',
				'MD' => 'Moldova, Republic of',
				'MC' => 'Monaco',
				'MN' => 'Mongolia',
				'MS' => 'Montserrat',
				'MA' => 'Morocco',
				'MZ' => 'Mozambique',
				'MM' => 'Myanmar',
				'NA' => 'Namibia',
				'NR' => 'Nauru',
				'NP' => 'Nepal',
				'NL' => 'Netherlands',
				'AN' => 'Netherlands Antilles',
				'NC' => 'New Caledonia',
				'NZ' => 'New Zealand',
				'NI' => 'Nicaragua',
				'NE' => 'Niger',
				'NG' => 'Nigeria',
				'NU' => 'Niue',
				'NF' => 'Norfolk Island',
				'MP' => 'Northern Mariana Islands',
				'NO' => 'Norway',
				'OM' => 'Oman',
				'PK' => 'Pakistan',
				'PW' => 'Palau',
				'PA' => 'Panama',
				'PG' => 'Papua New Guinea',
				'PY' => 'Paraguay',
				'PE' => 'Peru',
				'PH' => 'Philippines',
				'PN' => 'Pitcairn',
				'PL' => 'Poland',
				'PT' => 'Portugal',
				'PR' => 'Puerto Rico',
				'QA' => 'Qatar',
				'RE' => 'Reunion',
				'RO' => 'Romania',
				'RU' => 'Russian Federation',
				'RW' => 'Rwanda',
				'KN' => 'Saint Kitts and Nevis',
				'LC' => 'Saint Lucia',
				'VC' => 'Saint Vincent and The Grenadines',
				'WS' => 'Samoa',
				'SM' => 'San Marino',
				'ST' => 'Sao Tome And Principe',
				'SA' => 'Saudi Arabia',
				'SN' => 'Senegal',
				'SC' => 'Seychelles',
				'SL' => 'Sierra Leone',
				'SG' => 'Singapore',
				'SK' => 'Slovakia (Slovak Republic)',
				'SI' => 'Slovenia',
				'SB' => 'Solomon Islands',
				'SO' => 'Somalia',
				'ZA' => 'South Africa',
				'GS' => 'South Georgia, South Sandwich Islands',
				'ES' => 'Spain',
				'LK' => 'Sri Lanka',
				'SH' => 'St. Helena',
				'PM' => 'St. Pierre and Miquelon',
				'SD' => 'Sudan',
				'SR' => 'Suriname',
				'SJ' => 'Svalbard and Jan Mayen Islands',
				'SZ' => 'Swaziland',
				'SE' => 'Sweden',
				'CH' => 'Switzerland',
				'SY' => 'Syrian Arab Republic',
				'TW' => 'Taiwan',
				'TJ' => 'Tajikistan',
				'TZ' => 'Tanzania, United Republic of',
				'TH' => 'Thailand',
				'TG' => 'Togo',
				'TK' => 'Tokelau',
				'TO' => 'Tonga',
				'TT' => 'Trinidad and Tobago',
				'TN' => 'Tunisia',
				'TR' => 'Turkey',
				'TM' => 'Turkmenistan',
				'TC' => 'Turks and Caicos Islands',
				'TV' => 'Tuvalu',
				'UG' => 'Uganda',
				'UA' => 'Ukraine',
				'AE' => 'United Arab Emirates',
				'GB' => 'United Kingdom',
				'UM' => 'United States Minor Outlying Islands',
				'UY' => 'Uruguay',
				'UZ' => 'Uzbekistan',
				'VU' => 'Vanuatu',
				'VE' => 'Venezuela',
				'VN' => 'Vietnam',
				'VG' => 'Virgin Islands (British)',
				'VI' => 'Virgin Islands (U.S.)',
				'WF' => 'Wallis and Futuna Islands',
				'EH' => 'Western Sahara',
				'YE' => 'Yemen',
				'YU' => 'Yugoslavia',
				'ZM' => 'Zambia',
				'ZW' => 'Zimbabwe'
			);

			return apply_filters( 'sm-country-list', $country_list );
		}

		// Region list from http://code.google.com/apis/maps/faq.html#languagesupport
		// Used for Maps v3 localization: http://code.google.com/apis/maps/documentation/javascript/basics.html#Localization
		function get_language_options() {
			$language_list = array(
				'ar' => 'Arabic',
				'eu' => 'Basque',
				'bg' => 'Bulgarian',
				'bn' => 'Bengali',
				'ca' => 'Catalan',
				'cs' => 'Czech',
				'da' => 'Danish',
				'de' => 'German',
				'el' => 'Greek',
				'en' => 'English',
				'en-AU' => 'English (Australian)',
				'en-GB' => 'English (Great Britain)',
				'es' => 'Spanish',
				'eu' => 'Basque',
				'fa' => 'Farsi',
				'fi' => 'Finnish',
				'fil' => 'Filipino',
				'fr' => 'French',
				'gl' => 'Galician',
				'gu' => 'Gujarati',
				'hi' => 'Hindi',
				'hr' => 'Croatian',
				'hu' => 'Hungarian',
				'id' => 'Indonesian',
				'it' => 'Italian',
				'iw' => 'Hebrew',
				'ja' => 'Japanese',
				'kn' => 'Kannada',
				'ko' => 'Korean',
				'lt' => 'Lithuanian',
				'lv' => 'Latvian',
				'ml' => 'Malayalam',
				'mr' => 'Marathi',
				'nl' => 'Dutch',
				'no' => 'Norwegian',
				'pl' => 'Polish',
				'pt' => 'Portuguese',
				'pt-BR' => 'Portuguese (Brazil)',
				'pt-PT' => 'Portuguese (Portugal)',
				'ro' => 'Romanian',
				'ru' => 'Russian',
				'sk' => 'Slovak',
				'sl' => 'Slovenian',
				'sr' => 'Serbian',
				'sv' => 'Swedish',
				'tl' => 'Tagalog',
				'ta' => 'Tamil',
				'te' => 'Telugu',
				'th' => 'Thai',
				'tr' => 'Turkish',
				'uk' => 'Ukrainian',
				'vi' => 'Vietnamese',
				'zh-CN' => 'Chinese (Simplified)',
				'zh-TW' => 'Chinese (Traditional)',
			);

			return apply_filters( 'sm-language-list', $language_list );
		}

		function get_auto_locate_options() {
			$auto_locate_list = array(
				'' => 'No Automatic Location',
				'ip' => 'Use IP Address',
				'html5' => 'Use HTML5',
			);

			return apply_filters( 'sm-auto-locte-list', $auto_locate_list );
		}

		// Echo the toolbar
		function show_toolbar( $title = '' ) {
			global $simple_map;
			$options = $simple_map->get_options();
			if ( '' == $title )
				$title = 'SimpleMap';
			?>
			<table class="sm-toolbar" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td class="sm-page-title">
						<h2><?php _e( $title, 'SimpleMap' ); ?></h2>
					</td>
					<td class="sm-toolbar-item">
						<a href="http://simplemap-plugin.com" target="_blank" title="<?php _e( 'Go to the SimpleMap Home Page', 'SimpleMap' ); ?>"><?php _e( 'SimpleMap Home Page', 'SimpleMap' ); ?></a>
					</td>
					<td class="sm-toolbar-item">
						<a href="<?php echo admin_url( 'admin.php?page=simplemap-help' ); ?>" title="<?php _e( 'Premium Support', 'SimpleMap' ); ?>"><?php _e( 'Premium Support', 'SimpleMap' ); ?></a>
					</td>
					<td class="sm-toolbar-item">
						<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<input type="hidden" name="cmd" value="_s-xclick" />
						<input type="hidden" name="hosted_button_id" value="DTJBYXGQFSW64" />
						<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" style="border: none;" name="submit" alt="PayPal - The safer, easier way to pay online!" />
						<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
						</form>
					</td>
				</tr>
			</table>

			<?php
			/*
			if ( !isset( $options['api_key'] ) || $options['api_key'] == '' )
				echo '<div class="error"><p>' . __( 'You must enter an API key for your domain.', 'SimpleMap' ).' <a href="' . admin_url( 'admin.php?page=simplemap' ) . '">' . __( 'Enter a key on the General Options page.', 'SimpleMap' ) . '</a></p></div>';
			*/
		}

		// Return the available search_radii
		function get_search_radii(){
			$search_radii = array( 1, 5, 10, 25, 50, 100, 500, 1000 );
			return apply_filters( 'sm-search-radii', $search_radii );
		}

		// What link are we using for google's API
		function get_api_link() {
			$lo = str_replace('_', '-', get_locale());
			$l = substr($lo, 0, 2);
			switch($l) {
				case 'es':
				case 'de':
				case 'ja':
				case 'ko':
				case 'ru':
					$api_link = "http://code.google.com/intl/$l/apis/maps/signup.html";
					break;
				case 'pt':
				case 'zh':
					$api_link = "http://code.google.com/intl/$lo/apis/maps/signup.html";
					break;
				case 'en':
				default:
					$api_link = "http://code.google.com/apis/maps/signup.html";
					break;
			}
			return $api_link;
		}

		// Returns true if legacy tables exist in the DB
		function legacy_tables_exist() {
			global $wpdb;

			$sql = "SHOW TABLES LIKE '" . $wpdb->prefix . "simple_map'";
			if ( $tables = $wpdb->get_results( $sql ) ) {
				return true;
			}

			return false;
		}

		// Search form / widget query vars
		function register_query_vars( $vars ) {

			$vars[] = 'location_search_address';
			$vars[] = 'location_search_city';
			$vars[]	= 'location_search_state';
			$vars[] = 'location_search_zip';
			$vars[] = 'location_search_distance';
			$vars[] = 'location_search_limit';
			$vars[] = 'location_is_search_results';

			return $vars;
		}

		/**
		 * Parses the shortcode attributes with the default options and returns array
		 *
		 * @since 2.3
		 */
		function parse_shortcode_atts( $shortcode_atts ) {
			$options			= $this->get_options();
			$default_atts		= $this->get_default_shortcode_atts();
			$atts			 	= shortcode_atts( $default_atts, $shortcode_atts );

			// If deprecated shortcodes were used, replace with current ones
			if ( isset( $atts['show_categories_filter'] ) )
				$atts['show_sm_category_filter'] = $atts['show_categories_filter'];
			if ( isset( $atts['show_tags_filter'] ) )
				$atts['show_sm_tag_filter'] = $atts['show_tags_filter'];
			if ( isset( $atts['show_days_filter'] ) )
				$atts['show_sm_day_filter'] = $atts['show_days_filter'];
			if ( isset( $atts['show_times_filter'] ) )
				$atts['show_sm_time_filter'] = $atts['show_times_filter'];
			if ( isset( $atts['categories'] ) )
				$atts['sm_category'] = $atts['categories'];			
			if ( isset( $atts['tags'] ) )
				$atts['sm_tag'] = $atts['tags'];			
			if ( isset( $atts['days'] ) )
				$atts['sm_day'] = $atts['days'];			
			if ( isset( $atts['times'] ) )
				$atts['sm_time'] = $atts['times'];			

			// Determine if we need to hide the search form or not
			if ( '' == $atts['hide_search'] ) {
				// Use default value
				if ( 'show' == $options['display_search'] )
					$atts['hide_search'] = 0;
				else
					$atts['hide_search'] = 1;
			} 

			// Set categories and tags to available equivelants 
			$atts['avail_sm_category'] 	= $atts['sm_category'];
			$atts['avail_sm_tag'] 		= $atts['sm_tag'];
			$atts['avail_sm_day'] 		= $atts['sm_day'];
			$atts['avail_sm_time'] 		= $atts['sm_time'];

			// Default lat / lng from shortcode?
			if ( ! $atts['default_lat'] ) 
				$atts['default_lat'] = $options['default_lat'];
			if ( ! $atts['default_lng'] )
				$atts['default_lng'] = $options['default_lng'];

			// Doing powered by?
			if ( '' == $atts['powered_by'] ) {

				// Use default value
				$atts['powered_by'] = $options['powered_by'];

			} else {

				// Use shortcode
				if ( 0 == $atts['powered_by'] )
					$atts['powered_by'] = 0;
				else
					$atts['powered_by'] = 1;

			}

			// Default units or shortcode units?
			if ( 'km' != $atts['units'] && 'mi' != $atts['units'] )
				$atts['units'] = $options['units'];

			// Default radius or shortcode radius?
			if ( '' != $atts['radius'] && in_array( $atts['radius'], $this->get_search_radii() ) )
				$atts['radius'] = absint( $atts['radius'] );
			else
				$atts['radius'] = $options['default_radius'];

			//Make sure we have limit
			if ( '' == $atts['limit'] )
				$atts['limit'] = $options['results_limit'];

			// Clean search_field_cols
			if ( 0 === absint( $atts['search_form_cols'] ) )
				$atts['search_form_cols'] = $default_atts['search_form_cols'];

			// Which type of map are we using?
			if ( '' == $atts['map_type'] )
				$atts['map_type'] = $options['map_type'];

			// Height of the map?
			if ( '' == $atts['map_height'] )
				$atts['map_height'] = $options['map_height'];

			// Width of the map?
			if ( '' == $atts['map_width'] )
				$atts['map_width'] = $options['map_width'];

			// Which zoom level are we using?
			if ( '' == $atts['zoom_level'] )
				$atts['zoom_level'] = $options['zoom_level'];

			// Which autoload option are we using?
			if ( '' == $atts['autoload'] )
				$atts['autoload'] = $options['autoload'];

			// Return final array
			return $atts;
		}
		
		/**
		 * Returns default shortcode attributes
		 *
		 * @since 2.3
		 */
		function get_default_shortcode_atts() {
			$options = $this->get_options();

			$tax_atts = array();
			$tax_search_fields = array();
			foreach ( $options['taxonomies'] as $taxonomy => $taxonomy_info ) {
				$tax_search_fields[] = "||labeltd_$taxonomy||empty";

				$safe_tax = str_replace('-', '_', $taxonomy);
				$tax_atts[$safe_tax] = '';
				$tax_atts['show_' . $safe_tax . '_filter'] = 1;

				// The following are deprecated. Don't use them.
				$tax_atts[strtolower($taxonomy_info['plural'])] = null;
				$tax_atts['show_' . strtolower($taxonomy_info['plural']) . '_filter'] = null;
			}

			$atts = $tax_atts + array(
				'search_title'				=> __( 'Find Locations Near:', 'SimpleMap' ), 
				'search_form_type'			=> 'table', 
				'search_form_cols'			=> 3, 
				'search_fields'				=> 'labelbr_street||labelbr_city||labelbr_state||labelbr_zip||empty||empty||labeltd_distance||empty' . implode('', $tax_search_fields) . '||submit||empty||empty', 
				'taxonomy_field_type'		=> 'checkboxes',
				'hide_search'				=> '', 
				'hide_map'					=> 0, 
				'hide_list'					=> 0, 
				'default_lat'				=> 0, 
				'default_lng'				=> 0, 
				'adsense_publisher_id'		=> 0, 
				'adsense_channel_id'		=> 0, 
				'adsense_max_ads'			=> 0,
				'map_width'					=> '', 
				'map_height'				=> '', 
				'units'						=> '',
				'radius'					=> '',
				'limit'						=> '',
				'autoload'					=> '',
				'zoom_level'				=> '',
				'map_type'					=> '',
				'powered_by'				=> '', 
				'sm_day'					=> '',
				'sm_time'					=> ''
			);

			return apply_filters( 'sm-default-shortcode-atts', $atts );
		}

		// This function filters category text labels
		function backwards_compat_categories_text( $text ) {
			return __( 'Categories', 'SimpleMap' );
		}

		// This function filters category text labels
		function backwards_compat_tags_text( $text ) {
			return __( 'Tags', 'SimpleMap' );
		}

		// This function filters category text labels
		function backwards_compat_days_text( $text ) {
			return __( 'Days', 'SimpleMap' );
		}

		// This function filters category text labels
		function backwards_compat_times_text( $text ) {
			return __( 'Times', 'SimpleMap' );
		}

	}	
}
