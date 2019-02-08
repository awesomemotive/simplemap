<?php

function simplemap_init_widgets() {
	// Init Widgets.
	register_widget( 'SM_Search_Widget' );
}

add_action( 'widgets_init', 'simplemap_init_widgets' );

/**
 * Class SM_Search_Widget.
 *
 * Location Search Widget.
 */
class SM_Search_Widget extends WP_Widget {

	/**
	 * SM_Search_Widget constructor.
	 *
	 * Define Widget.
	 *
	 */
	public function __construct() {

		$widget_ops = array(
							'classname'   => 'sm_search_widget',
							'description' => __( 'Adds a customizable search widget to your site' ),
		);
		parent::__construct( 'sm_search_widget', 'SimpleMap ' . __( 'Search' ), $widget_ops );
	}

	/**
	 * Build the widget.
	 *
	 * @param array $args widget comments
	 * @param array $instance actual instance of the widget
	 */
	public function widget( $args, $instance ) {
		global $simple_map, $wp_rewrite;

		extract( $args );

		$options = $simple_map->get_options();

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'] );

		// Ensures Variables are always Defined
		$instance = $this->set_instance( $instance );
		/*
		 * Most of these variables could be reduced, and use the instance
		 * array instead.
		 *
		 * Same with $this->form method.
		 */
		// Search Form Options.
		$show_name      = $instance['show_name'];
		$show_address   = $instance['show_address'];
		$show_city      = $instance['show_city'];
		$show_state     = $instance['show_state'];
		$show_zip       = $instance['show_zip'];
		$show_distance  = $instance['show_distance'];

		$default_lat    = $instance['default_lat'];
		$default_lng    = $instance['default_lng'];
		$simplemap_page = $instance['simplemap_page'];

		// Set taxonomies to available equivalents.
		$show  = array();
		$terms = array();
		foreach ( $options['taxonomies'] as $taxonomy => $tax_info ) {
			$key                = strtolower( $tax_info['plural'] );
			$show[ $taxonomy ]  = ! empty( $instance[ 'show_' . $key ] ) ? 1 : 0;
			$terms[ $taxonomy ] = ! empty( $instance[ $key ] ) ? $instance[ $key ] : '';
		}

		$available = $terms;

		//TODO update this to modern Widget API code.
		echo $before_widget;
		if ( $title ) {
			echo '<span class="sm-search-widget-title">' . $before_title . $title . $after_title . '</span>'; // XSS ok.
		}

		// Form Field Values.
		$name_value    = isset( $_REQUEST['location_search_name'] ) ? $_REQUEST['location_search_name'] : '';
		$address_value = isset( $_REQUEST['location_search_address'] ) ? $_REQUEST['location_search_address'] : '';
		$city_value    = isset( $_REQUEST['location_search_city'] ) ? $_REQUEST['location_search_city'] : '';
		$state_value   = isset( $_REQUEST['location_search_state'] ) ? $_REQUEST['location_search_state'] : '';
		$zip_value     = isset( $_REQUEST['location_search_zip'] ) ? $_REQUEST['location_search_zip'] : '';
		$radius_value  = isset( $_REQUEST['location_search_distance'] ) ? $_REQUEST['location_search_distance'] : $options['default_radius'];
		$limit_value   = isset( $_REQUEST['location_search_limit'] ) ? $_REQUEST['location_search_limit'] : $options['results_limit'];

		// Set action based on permalink structure.
		if ( ! $wp_rewrite->permalink_structure ) {
			$method = 'get';
			$action = site_url();
		} else {
			$method = 'post';
			$action = get_permalink( absint( $simplemap_page ) );
		}

		$location_search = '<div id="location_widget_search" >';
		$location_search .= '<form name="location_widget_search_form" id="location_widget_search_form" action="' . $action . '" method="' . $method . '">';
		$location_search .= '<table class="location_search_widget">';

		$location_search .= apply_filters( 'sm-location-search-widget-table-top', '' );

		if ( $show_name ) {
			$location_search .= '<tr><td class="location_search_widget_name_cell location_search_widget_cell">' . apply_filters( 'sm-search-label-name', __( 'Place', 'simplemap' ) ) . ':<br /><input type="text" id="location_search_widget_name_field" name="location_search_name" /></td></tr>';
		}
		if ( $show_address ) {
			$location_search .= '<tr><td class="location_search_widget_address_cell location_search_widget_cell">' . apply_filters( 'sm-search-label-street', __( 'Street', 'simplemap' ) ) . ':<br /><input type="text" id="location_search_widget_address_field" name="location_search_address" /></td></tr>';
		}
		if ( $show_city ) {
			$location_search .= '<tr><td class="location_search_widget_city_cell location_search_widget_cell">' . apply_filters( 'sm-search-label-city', __( 'City', 'simplemap' ) ) . ':<br /><input type="text"  id="location_search_widget_city_field" name="location_search_city" /></td></tr>';
		}
		if ( $show_state ) {
			$location_search .= '<tr><td class="location_search_widget_state_cell location_search_widget_cell">' . apply_filters( 'sm-search-label-state', __( 'State', 'simplemap' ) ) . ':<br /><input type="text" id="location_search_widget_state_field" name="location_search_state" /></td></tr>';
		}
		if ( $show_zip ) {
			$location_search .= '<tr><td class="location_search_widget_zip_cell location_search_widget_cell">' . apply_filters( 'sm-search-label-zip', __( 'Zip', 'simplemap' ) ) . ':<br /><input type="text" id="location_search_widget_zip_field" name="location_search_zip" /></td></tr>';
		}
		if ( $show_distance ) {
			$location_search .= '<tr><td class="location_search_widget_distance_cell location_search_widget_cell">' . apply_filters( 'sm-search-label-distance', __( 'Select a distance', 'simplemap' ) ) . ':<br /><select id="location_search_widget_distance_field" name="location_search_distance" >';

			foreach ( $simple_map->get_search_radii() as $value ) {
				$r = (int) $value;
				$location_search .= '<option value="' . $value . '"' . selected( $radius_value, $value, false ) . '>' . $value . ' ' . $options['units'] . "</option>\n";
			}

			$location_search .= '</select></td></tr>';
		}

		foreach ( $options['taxonomies'] as $taxonomy => $tax_info ) {
			// Place available values in array.
			$available_terms = explode( ',', $available[ $taxonomy ] );
			$valid     = array();

			// Loop through all days and create array of available days.
			if ( $all_terms = get_terms( $taxonomy ) ) {
				foreach ( $all_terms as $key => $value ) {
					if ( '' === $available_terms[0] || in_array( $value->term_id, $available_terms ) ) {
						$valid[] = $value->term_id;
					}
				}
			}

			// Show day filters if allowed.
			if ( $all_terms && ! empty( $show[ $taxonomy ] ) ) {
				$php_taxonomy = str_replace( '-', '_', $taxonomy );
				$term_search  = '<tr><td class="location_search_' . strtolower( $tax_info['singular'] ) . '_cell location_search_cell">' . apply_filters( $php_taxonomy . '-text', __( $tax_info['plural'], 'simplemap' ) ) . ':<br />';

				// Print checkbox for each available day.
				foreach ( $valid as $key => $termid ) {
					if ( $term = get_term_by( 'id', $termid, $taxonomy ) ) {
						$term_search .= '<label for="location_search_widget_' . strtolower( $tax_info['plural'] ) . '_field_' . esc_attr( $term->term_id ) . '" class="no-linebreak"><input type="checkbox" name="location_search_' . $php_taxonomy . '_' . esc_attr( $term->term_id ) . 'field" id="location_search_widget_' . strtolower( $tax_info['plural'] ) . '_field_' . esc_attr( $term->term_id ) . '" value="' . esc_attr( $term->term_id ) . '" /> ' . esc_attr( $term->name ) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> ';
					}
				}

				$term_search .= '</td></tr>';
			} else {
				// Default day_selected is none.
				$term_search = '<input type="hidden" name="location_search_' . strtolower( $tax_info['plural'] ) . '_field" value="" checked="checked" />';
			}

			// Hidden field for available days; we'll need this in the event that nothing is selected.
			$term_search .= '<input type="hidden" id="avail_' . strtolower( $tax_info['plural'] ) . '" value="' . esc_attr( $terms[ $taxonomy ] ) . '" />';

			$term_search = apply_filters( 'sm-location-' . strtolower( $tax_info['singular'] ) . '-search-widget', $term_search );
			$location_search .= $term_search;
		}

		// Default lat / lng from shortcode?
		if ( ! $default_lat ) {
			$default_lat = $options['default_lat'];
		}
		if ( ! $default_lng ) {
			$default_lng = $options['default_lng'];
		}

		$location_search .= "<input type='hidden' id='location_search_widget_default_lat' value='" . $default_lat . "' />";
		$location_search .= "<input type='hidden' id='location_search_widget_default_lng' value='" . $default_lng . "' />";

		// Hidden value for limit.
		$location_search .= "<input type='hidden' name='location_search_widget_limit' id='location_search_widget_limit' value='" . $limit_value . "' />";

		// Hidden value set to true if we got here via search.
		$location_search .= "<input type='hidden' id='location_is_search_widget_results' name='location_is_search_results' value='1' />";

		// Hidden value referencing page_id.
		$location_search .= "<input type='hidden' name='page_id' value='" . absint( $simplemap_page ) . "' />";

		$location_search .= apply_filters( 'sm-location-search-widget-before-submit', '' );

		$location_search .= '<tr><td class="location_search_widget_submit_cell location_search_widget_cell"> <input type="submit" value="' . apply_filters( 'sm-search-label-search', __( 'Search', 'simplemap' ) ) . '" id="location_search_widget_submit_field" class="submit" /></td></tr>';
		$location_search .= '</table>';
		$location_search .= '</form>';
		$location_search .= '</div>'; // Close map_search div.

		echo $location_search;

		echo $after_widget;
	}

	/**
	 *
	 * Save settings in backend.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $this->set_instance( $old_instance );
		$instance = $this->set_instance( $new_instance, $instance );

		return $instance;
	}

	/**
	 * Defaults
	 *
	 * @param array $instance
	 *
	 * @return string|void
	 */
	public function form( $instance ) {
		$instance = $this->set_instance( $instance );

		$title          = esc_attr( $instance['title'] );
		$show_name      = $instance['show_name'];
		$show_address   = $instance['show_address'];
		$show_city      = $instance['show_city'];
		$show_state     = $instance['show_state'];
		$show_zip       = $instance['show_zip'];
		$show_distance  = $instance['show_distance'];
		$default_lat    = ! empty( $instance['default_lat'] ) ? esc_attr( $instance['default_lat'] ) : 0;
		$default_lng    = ! empty( $instance['default_lng'] ) ? esc_attr( $instance['default_lng'] ) : 0;
		$simplemap_page = $instance['simplemap_page'];
		?>

		<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"/>
		</p>

		<p><input type="checkbox" class="checkbox" id="<?php echo esc_attr(  $this->get_field_id( 'show_name' ) ); ?>"
		          name="<?php echo esc_attr( $this->get_field_name( 'show_name' ) ); ?>"<?php checked( $show_name ); ?> />
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'show_name' ) ); ?>"><?php _e( 'Show Place', 'simplemap' ); ?></label><br/>

			<input type="checkbox" class="checkbox" id="<?php echo esc_attr(  $this->get_field_id( 'show_address' ) ); ?>"
		          name="<?php echo esc_attr( $this->get_field_name( 'show_address' ) ); ?>"<?php checked( $show_address ); ?> />
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'show_address' ) ); ?>"><?php _e( 'Show Address', 'simplemap' ); ?></label><br/>

			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_city' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'show_city' ) ); ?>"<?php checked( $show_city ); ?> />
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'show_city' ) ); ?>"><?php _e( 'Show City', 'simplemap' ); ?></label><br/>

			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_state' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'show_state' ) ); ?>"<?php checked( $show_state ); ?> />
			<label
				for="<?php echo $this->get_field_id( 'show_state' ); ?>"><?php _e( 'Show State', 'simplemap' ); ?></label><br/>

			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_zip' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'show_zip' ) ); ?>"<?php checked( $show_zip ); ?> />
			<label
				for="<?php echo $this->get_field_id( 'show_zip' ); ?>"><?php _e( 'Show Zip', 'simplemap' ); ?></label><br/>

			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_distance' ) ); ?>"
			       name="<?php echo $this->get_field_name( 'show_distance' ); ?>"<?php checked( $show_distance ); ?> />
			<label
				for="<?php echo $this->get_field_id( 'show_distance' ); ?>"><?php _e( 'Show Distance', 'simplemap' ); ?></label><br/>

			<?php
			global $simple_map;
			$options = $simple_map->get_options();
			foreach ( $options['taxonomies'] as $taxonomy => $tax_info ) {
				$key        = strtolower( $tax_info['plural'] );
				$show_field = 'show_' . $key;
				$show       = isset( $instance[ $show_field ] ) ? (bool) $instance[ $show_field ] : false;
				?>
				<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( $show_field ); ?>"
				       name="<?php echo $this->get_field_name( $show_field ); ?>"<?php checked( $show ); ?> />
				<label
					for="<?php echo $this->get_field_id( $show_field ); ?>"><?php _e( 'Show ' . $tax_info['plural'], 'simplemap' ); ?></label>
				<br/>

				<?php
				/** TODO: The commented out code below isn't working yet. Implement it.
				 * $values = isset( $instance[$key] ) ? esc_attr( $instance[$key] ) : '';
				 * ?>
				 * <p><label for="<?php echo $this->get_field_id( $key ); ?>"><?php _e( $tax_info['plural'] . ':', 'simplemap' ); ?></label>
				 * <input class="widefat" id="<?php echo $this->get_field_id( $key ); ?>" name="<?php echo $this->get_field_name( $key ); ?>" type="text" value="<?php echo $values; ?>" /></p>
				 * <?php
				 */
			}

			/** TODO: The commented out code below isn't working yet. Implement it.
			 * <p><label for="<?php echo $this->get_field_id( 'default_lat' ); ?>"><?php _e( 'Default Lat:', 'simplemap' ); ?></label>
			 * <input class="widefat" id="<?php echo $this->get_field_id( 'default_lat' ); ?>" name="<?php echo $this->get_field_name( 'default_lat' ); ?>" type="text" value="<?php echo $default_lat; ?>" /></p>
			 *
			 * <p><label for="<?php echo $this->get_field_id( 'default_lng' ); ?>"><?php _e( 'Default Lng:', 'simplemap' ); ?></label>
			 * <input class="widefat" id="<?php echo $this->get_field_id( 'default_lng' ); ?>" name="<?php echo $this->get_field_name( 'default_lng' ); ?>" type="text" value="<?php echo $default_lng; ?>" /></p>
			 */
			?>
		<p><label
				for="<?php echo $this->get_field_id( 'simplemap_page' ); ?>">SimpleMap <?php _e( 'Page or Post ID:', 'simplemap' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'simplemap_page' ); ?>"
			       name="<?php echo $this->get_field_name( 'simplemap_page' ); ?>" type="text"
			       value="<?php echo $simplemap_page; ?>"/></p>

		<?php
	}

	/**
	 * Set Widget Instance
	 *
	 * Defines the current instance array with either default values or old
	 * values to overwrite with.
	 *
	 * @since 2.6
	 * @access private
	 *
	 * @global object $simple_map
	 *
	 * @param array $instance Current instance array; which can be empty.
	 * @param array $old_instance Optional. Old instance settings to overwrite. Default: array().
	 *
	 * @return array Defined instance.
	 */
	private function set_instance( $instance, $old_instance = array() ) {
		$default = array(
			'title'             => '',
			'show_name'         => false,
			'show_address'      => false,
			'show_city'         => false,
			'show_state'        => false,
			'show_zip'          => false,
			'show_distance'     => false,
			'default_lat'       => 0,
			'default_lng'       => 0,
			'simplemap_page'    => 2,
		);

		$new_instance = array();
		if ( ! empty( $old_instance ) ) {
			$new_instance = $old_instance;
		}

		foreach ( $default as $key => $default_value ) {
			if ( ! empty( $instance[ $key ] ) ) {
				switch ( $key ) {
					case 'title':
						$new_instance[ $key ] = strip_tags( $instance[ $key ] );
						break;
					case 'default_lat':
						$new_instance[ $key ] = (string) $instance[ $key ];
						break;
					case 'default_lng':
						$new_instance[ $key ] = (string) $instance[ $key ];
						break;
					case 'simplemap_page':
						$new_instance[ $key ] = absint( $instance[ $key ] );
						break;
					default:
						$new_instance[ $key ] = true;
				}
			} else {
				$new_instance[ $key ] = $default_value;
			}
		}

		global $simple_map;
		$options = $simple_map->get_options();
		// Taxonomies
		foreach ( $options['taxonomies'] as $taxonomy => $tax_info ) {
			$key                                = strtolower( $tax_info['plural'] );
			$new_instance[ 'show_' . $key ]     = 0;
			$new_instance[ $key ]               = '';

			if ( ! empty( $instance[ 'show_' . $key ] ) ) {
				$new_instance[ 'show_' . $key ] = 1;
				if ( ! empty( $instance[ $key ] ) ) {
					$new_instance[ $key ] = $instance[ $key ];
				}
			}
		}

		return $new_instance;
	}
}

?>
