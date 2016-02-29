<?php
// Init Widgets
function simplemap_init_widgets() {
	register_widget( 'SM_Search_Widget' );
}
add_action( 'widgets_init', 'simplemap_init_widgets' );

// Location Search Widget
class SM_Search_Widget extends WP_Widget {

	// Define Widget
	function SM_Search_Widget() {
		$widget_ops = array( 'classname' => 'sm_search_widget', 'description' => __( "Adds a customizable search widget to your site" ) );
		$this->__Construct('sm_search_widget', __('SimpleMap Search'), $widget_ops);
	}

	function widget( $args, $instance ) {
		global $simple_map, $wp_rewrite;

		extract( $args );
			
		$options = $simple_map->get_options();

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'] );
		
		// Search Form Options
		$show_address 	= $instance['show_address'] ? 1 : 0;
		$show_city 	= $instance['show_city'] ? 1 : 0;
		$show_state 	= $instance['show_state'] ? 1 : 0;
		$show_zip 	= $instance['show_zip'] ? 1 : 0;
		$show_distance 	= $instance['show_distance'] ? 1 : 0;

		$default_lat	= $instance['default_lat'] ? $instance['default_lat'] : 0;
		$default_lng	= $instance['default_lng'] ? $instance['default_lng'] : 0;
		$simplemap_page	= $instance['simplemap_page'] ? $instance['simplemap_page'] : 2;

		// Set taxonomies to available equivalents 
		$show = array();
		$terms = array();
		foreach ( $options['taxonomies'] as $taxonomy => $tax_info ) {
			$key = strtolower( $tax_info['plural'] );
			$show[$taxonomy] = $instance['show_' . $key] ? 1 : 0;
			$terms[$taxonomy] = $instance[$key] ? $instance[$key] : '';
		}

		$available = $terms;

		echo $before_widget;
		if ( $title )
			echo '<span class="sm-search-widget-title">' . $before_title . $title . $after_title . '</span>';

		// Form Field Values
		$address_value 		= isset( $_REQUEST['location_search_address'] ) ? $_REQUEST['location_search_address'] : '';
		$city_value 		= isset( $_REQUEST['location_search_city'] ) ? $_REQUEST['location_search_city'] : '';
		$state_value 		= isset( $_REQUEST['location_search_state'] ) ? $_REQUEST['location_search_state'] : '';
		$zip_value 			= isset( $_REQUEST['location_search_zip'] ) ? $_REQUEST['location_search_zip'] : '';
		$radius_value	 	= isset( $_REQUEST['location_search_distance'] ) ? $_REQUEST['location_search_distance'] : $options['default_radius'];
		$limit_value		= isset( $_REQUEST['location_search_limit'] ) ? $_REQUEST['location_search_limit'] : $options['results_limit'];
		
		// Set action based on permalink structure
		if ( ! $wp_rewrite->permalink_structure ) {
			$method = 'get';
			$action = site_url();
		} else {
			$method = 'post';
			$action = get_permalink( absint( $simplemap_page ) );
		}
		
		$location_search  = '<div id="location_widget_search" >';
		$location_search .= '<form name="location_widget_search_form" id="location_widget_search_form" action="' . $action . '" method="' . $method . '">';
		$location_search .= '<table class="location_search_widget">';

		$location_search .= apply_filters( 'sm-location-search-widget-table-top', '' );

		if ( $show_address )
			$location_search .= '<tr><td class="location_search_widget_address_cell location_search_widget_cell">' . apply_filters( 'sm-search-label-street', __( 'Street', 'SimpleMap' ) ) . ':<br /><input type="text" id="location_search_widget_address_field" name="location_search_address" /></td></tr>';
		if ( $show_city )
			$location_search .= '<tr><td class="location_search_widget_city_cell location_search_widget_cell">' . apply_filters( 'sm-search-label-city', __( 'City', 'SimpleMap' ) ) . ':<br /><input type="text"  id="location_search_widget_city_field" name="location_search_city" /></td></tr>';
		if ( $show_state )
			$location_search .= '<tr><td class="location_search_widget_state_cell location_search_widget_cell">' . apply_filters( 'sm-search-label-state', __( 'State', 'SimpleMap' ) ) . ':<br /><input type="text" id="location_search_widget_state_field" name="location_search_state" /></td></tr>';
		if ( $show_zip )
			$location_search .= '<tr><td class="location_search_widget_zip_cell location_search_widget_cell">' . apply_filters( 'sm-search-label-zip', __( 'Zip', 'SimpleMap' ) ) . ':<br /><input type="text" id="location_search_widget_zip_field" name="location_search_zip" /></td></tr>';
		if ( $show_distance ) {
			$location_search .= '<tr><td class="location_search_widget_distance_cell location_search_widget_cell">' . apply_filters( 'sm-search-label-distance', __( 'Select a distance', 'SimpleMap' ) ) . ':<br /><select id="location_search_widget_distance_field" name="location_search_distance" >';

			foreach ( $simple_map->get_search_radii() as $value ) {
				$r = (int) $value;
				$location_search .= '<option value="' . $value . '"' . selected( $radius_value, $value, false ) . '>' . $value . ' ' . $options['units'] . "</option>\n";
			}

			$location_search .= '</select></td></tr>';
		}

		foreach ( $options['taxonomies'] as $taxonomy => $tax_info ) {
			// Place available values in array
			$available = explode( ',', $available[$taxonomy] );
			$valid = array();

			// Loop through all days and create array of available days
			if ( $all_terms = get_terms( $taxonomy ) ) {
				foreach ( $all_terms as $key => $value ) {
					if ( '' == $available[0] || in_array( $value->term_id, $available ) ) {
						$valid[] = $value->term_id;
					}
				}
			}

			// Show day filters if allowed
			if ( ! empty( $show[$taxonomy] ) && $all_terms ) {
				$php_taxonomy = str_replace( '-', '_', $taxonomy );
				$term_search = '<tr><td class="location_search_' . strtolower( $tax_info['singular'] ) . '_cell location_search_cell">' . apply_filters( $php_taxonomy . '-text',__( $tax_info['plural'], 'SimpleMap' ) ) . ':<br />';

				// Print checkbox for each available day
				foreach( $valid as $key => $termid ) {
					if( $term = get_term_by( 'id', $termid, $taxonomy ) ) {
						$term_search .= '<label for="location_search_widget_' . strtolower( $tax_info['plural'] ) . '_field_' . esc_attr( $term->term_id ) . '" class="no-linebreak"><input type="checkbox" name="location_search_' . $php_taxonomy . '_' . esc_attr( $term->term_id ) . 'field" id="location_search_widget_' . strtolower( $tax_info['plural'] ) . '_field_' . esc_attr( $term->term_id ) . '" value="' . esc_attr( $term->term_id ) . '" /> ' . esc_attr( $term->name ) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> ';
					}
				}

				$term_search .= '</td></tr>';
			} else {
				// Default day_selected is none
				$term_search = '<input type="hidden" name="location_search_' . strtolower( $tax_info['plural'] ) . '_field" value="" checked="checked" />';
			}

			// Hidden field for available days. We'll need this in the event that nothing is selected
			$term_search .= '<input type="hidden" id="avail_' . strtolower( $tax_info['plural'] ) . '" value="' . esc_attr( $terms[$taxonomy] ) . '" />';

			$term_search = apply_filters( 'sm-location-' . strtolower( $tax_info['singular'] ) . '-search-widget', $term_search );
			$location_search .= $term_search;
		}

		// Default lat / lng from shortcode?
		if ( ! $default_lat ) 
			$default_lat = $options['default_lat'];
		if ( ! $default_lng )
			$default_lng = $options['default_lng'];
		
		$location_search .= "<input type='hidden' id='location_search_widget_default_lat' value='" . $default_lat . "' />";
		$location_search .= "<input type='hidden' id='location_search_widget_default_lng' value='" . $default_lng . "' />";
		
		// Hidden value for limit
		$location_search .= "<input type='hidden' name='location_search_widget_limit' id='location_search_widget_limit' value='" . $limit_value . "' />";
		
		// Hidden value set to true if we got here via search
		$location_search .= "<input type='hidden' id='location_is_search_widget_results' name='location_is_search_results' value='1' />";
		
		// Hidden value referencing page_id
		$location_search .= "<input type='hidden' name='page_id' value='" . absint( $simplemap_page ) . "' />";
		
		$location_search .= apply_filters( 'sm-location-search-widget-before-submit', '' );
		
		$location_search .= '<tr><td class="location_search_widget_submit_cell location_search_widget_cell"> <input type="submit" value="' . apply_filters( 'sm-search-label-search', __( 'Search', 'SimpleMap' ) ) . '" id="location_search_widget_submit_field" class="submit" /></td></tr>';
		$location_search .= '</table>';
		$location_search .= '</form>';
		$location_search .= '</div>'; // close map_search div
	
		echo $location_search;
	
		echo $after_widget;
	}

	// Save settings in backend
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		$instance['title'] 				= strip_tags( $new_instance['title'] );
		$instance['show_address'] 		= $new_instance['show_address'] ? 1 : 0;
		$instance['show_city'] 			= $new_instance['show_city'] ? 1 : 0;
		$instance['show_state'] 		= $new_instance['show_state'] ? 1 : 0;
		$instance['show_zip'] 			= $new_instance['show_zip'] ? 1 : 0;
		$instance['show_distance'] 		= $new_instance['show_distance'] ? 1 : 0;
		$instance['default_lat']		= $new_instance['default_lat'] ? $new_instance['default_lat'] : 0;
		$instance['default_lng']		= $new_instance['default_lng'] ? $new_instance['default_lng'] : 0;
		$instance['simplemap_page']		= $new_instance['simplemap_page'] ? $new_instance['simplemap_page'] : 2;

		global $simple_map;
		$options = $simple_map->get_options();
		foreach ( $options['taxonomies'] as $taxonomy => $tax_info ) {
			$key = strtolower( $tax_info['plural'] );
			$instance['show_' . $key] = $new_instance['show_' . $key] ? 1 : 0;
			$instance[$key] = $new_instance[$key] ? $new_instance[$key] : '';
		}

		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		
		$title 				= esc_attr( $instance['title'] );
		$show_address 		= isset( $instance['show_address'] ) ? (bool) $instance['show_address'] : false;
		$show_city 			= isset( $instance['show_city'] ) ? (bool) $instance['show_city'] : false;
		$show_state 		= isset( $instance['show_state'] ) ? (bool) $instance['show_state'] : false;
		$show_zip 			= isset( $instance['show_zip'] ) ? (bool) $instance['show_zip'] : false;
		$show_distance 		= isset( $instance['show_distance'] ) ? (bool) $instance['show_distance'] : false;
		$default_lat 		= isset( $instance['default_lat'] ) ? esc_attr( $instance['default_lat'] ) : 0;
		$default_lng 		= isset( $instance['default_lng'] ) ? esc_attr( $instance['default_lng'] ) : 0;
		$simplemap_page		= isset( $instance['simplemap_page'] ) ? esc_attr( $instance['simplemap_page'] ) : '';
		?>
		
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_address' ); ?>" name="<?php echo $this->get_field_name( 'show_address' ); ?>"<?php checked( $show_address ); ?> />
		<label for="<?php echo $this->get_field_id( 'show_address' ); ?>"><?php _e( 'Show Address', 'SimpleMap' ); ?></label><br />

		<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_city' ); ?>" name="<?php echo $this->get_field_name( 'show_city' ); ?>"<?php checked( $show_city ); ?> />
		<label for="<?php echo $this->get_field_id( 'show_city' ); ?>"><?php _e( 'Show City', 'SimpleMap' ); ?></label><br />

		<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_state' ); ?>" name="<?php echo $this->get_field_name( 'show_state' ); ?>"<?php checked( $show_state ); ?> />
		<label for="<?php echo $this->get_field_id( 'show_state' ); ?>"><?php _e( 'Show State', 'SimpleMap' ); ?></label><br />

		<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_zip' ); ?>" name="<?php echo $this->get_field_name( 'show_zip' ); ?>"<?php checked( $show_zip ); ?> />
		<label for="<?php echo $this->get_field_id( 'show_zip' ); ?>"><?php _e( 'Show Zip', 'SimpleMap' ); ?></label><br />

		<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_distance' ); ?>" name="<?php echo $this->get_field_name( 'show_distance' ); ?>"<?php checked( $show_distance ); ?> />
		<label for="<?php echo $this->get_field_id( 'show_distance' ); ?>"><?php _e( 'Show Distance', 'SimpleMap' ); ?></label><br />

		<?php
		global $simple_map;
		$options = $simple_map->get_options();
		foreach ( $options['taxonomies'] as $taxonomy => $tax_info ) {
			$key = strtolower( $tax_info['plural'] );
			$show_field = 'show_' . $key;
			$show = isset( $instance[$show_field] ) ? (bool) $instance[$show_field] : false;
		?>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( $show_field ); ?>" name="<?php echo $this->get_field_name( $show_field ); ?>"<?php checked( $show ); ?> />
			<label for="<?php echo $this->get_field_id( $show_field ); ?>"><?php _e( 'Show ' . $tax_info['plural'], 'SimpleMap' ); ?></label><br />

		<?php
			/** TODO: The commented out code below isn't working yet. Implement it.
			$values = isset( $instance[$key] ) ? esc_attr( $instance[$key] ) : '';
		?>
			<p><label for="<?php echo $this->get_field_id( $key ); ?>"><?php _e( $tax_info['plural'] . ':', 'SimpleMap' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( $key ); ?>" name="<?php echo $this->get_field_name( $key ); ?>" type="text" value="<?php echo $values; ?>" /></p>
		<?php
			*/
		}
		
		/** TODO: The commented out code below isn't working yet. Implement it.
		<p><label for="<?php echo $this->get_field_id( 'default_lat' ); ?>"><?php _e( 'Default Lat:', 'SimpleMap' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'default_lat' ); ?>" name="<?php echo $this->get_field_name( 'default_lat' ); ?>" type="text" value="<?php echo $default_lat; ?>" /></p>
		
		<p><label for="<?php echo $this->get_field_id( 'default_lng' ); ?>"><?php _e( 'Default Lng:', 'SimpleMap' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'default_lng' ); ?>" name="<?php echo $this->get_field_name( 'default_lng' ); ?>" type="text" value="<?php echo $default_lng; ?>" /></p>
		*/
		?>
		<p><label for="<?php echo $this->get_field_id( 'simplemap_page' ); ?>"><?php _e( 'SimpleMap Page or Post ID:', 'SimpleMap' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'simplemap_page' ); ?>" name="<?php echo $this->get_field_name( 'simplemap_page' ); ?>" type="text" value="<?php echo $simplemap_page; ?>" /></p>
		
		<?php
	}
}
?>
