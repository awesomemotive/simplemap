<?php
// Init Widgets
function simplemap_init_widgets() {
	register_widget( 'SM_Search_Widget' );
}
add_action( 'widgets_init', 'simplemap_init_widgets' );

/* abstract Widget base class */
if ( !class_exists( 'SFWD_Generic_Widget' ) ) {
	abstract class SFWD_Generic_Widget extends WP_Widget {
		protected $default_options;
		public function __construct( $name = 'sfwd_generic_widget', $title = '', $args = array() ) {
			
			if ( empty( $title ) ) $title = __( 'SFWD Generic Widget', 'SimpleMap' );
			
			if ( empty( $args['default_options'] ) ) {
				$this->default_options = Array(
						'title'				=> Array( 'name' => __( 'Title:', 'SimpleMap' ),	  'type' => 'text', 'default' => $title ),
					);
			} else {
				$this->default_options = $args['default_options'];
			}
			
			parent::__construct( $name, $title, $args );
		}
		
		abstract protected function widget_content( $args, $instance );
		
		public function widget( $args, $instance ) {

			extract( $args, EXTR_SKIP );
			
			/* Before Widget content */
			echo $before_widget;

			/* Get user defined widget title */
			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
						
			if ( !empty( $title ) ) echo $before_title . $title . $after_title;

			/* Display Widget Data */
			$this->widget_content( $args, $instance );
			
			/* After Widget content */
			echo $after_widget;
		}
		
		// Save settings in backend
		public function update( $new_instance, $old_instance ) {
			/* Updates widget title value */
			$instance = $old_instance;
			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['template'] = wp_kses_post( $new_instance['template'] );
			if ( !empty( $this->default_options ) )
				foreach( $this->default_options as $k => $v )
					switch ( $v['type'] ) {
						case 'multiselect':
						case 'multicheckbox': $instance[$k] = urlencode_deep( $new_instance[$k] );
											  break;
						case 'textarea':	  $instance[$k] = wp_kses_post( $new_instance[$k] );
											  break;
						case 'filename':	  $instance[$k] = sanitize_file_name( $new_instance[$k] );
											  break;
						case 'text':		  $instance[$k] = wp_kses_post( $new_instance[$k] );
						case 'checkbox':
						case 'radio':
						case 'select':
						default:			  $instance[$k] = esc_attr( $new_instance[$k] );
					}
			return $instance;
		}

		public function form( $instance ) {
			if ( !empty( $this->default_options ) )
				foreach ( $this->default_options as $name => $args ) {
					if ( $args['type'] != 'hidden' ) {						
					?><p>
					<label for="<?php echo $this->get_field_id( $name ); ?>"><?php echo $args['name']; ?>
					<?php
					}
					$value = '';
					if ( !empty( $instance[$name] ) )
						$value = $instance[$name];
					elseif ( !empty( $args['default'] ) )
						$value = $args['default'];
					$attr = " id='" . $this->get_field_id( $name ) . "'";
					if ( !empty( $args['class'] ) )
						$attr .= " class='{$args['class']}'";
					else {						
						if ( $args['type'] != 'checkbox' )
							$attr .= " class='widefat sm_{$args['type']}'";
						else
							$attr .= " class='sm_{$args['type']}'";
					}
					if ( !empty( $args['rows'] ) ) $attr .= " rows='{$args['rows']}'";
					if ( !empty( $args['cols'] ) ) $attr .= " cols='{$args['cols']}'";
					echo $this->get_option_html( Array( 'name' => $this->get_field_name( $name ), 'options' => $args, 'attr' => $attr, 'value' => $value ) );
					if ( $args['type'] != 'hidden' ) {	
					?></label></p><?php
					}
				}
		}

		/**
		 * Outputs radio buttons, checkboxes, selects, multiselects, handles groups.
		 */	
		function do_multi_input( $args ) {
			extract( $args );
			$buf1 = '';
			$type = $options['type'];
			if ( ( $type == 'radio' ) || ( $type == 'checkbox' ) ) {
				$strings = Array(
					'block'		=> "%s\n",
					'group'		=> "\t<b>%s</b><br>\n%s\n",
					'item'		=> "\t<label class='sfwd_option_setting_label'><input type='$type' %s name='%s' value='%s' %s> %s</label>\n",
					'item_args' => Array( 'sel', 'name', 'v', 'attr', 'subopt' ),
					'selected'	=> 'checked '
					);
			} else {
				$strings = Array(
						'block'		=> "<select name='$name' $attr>%s\n</select>\n",
						'group'		=> "\t<optgroup label='%s'>\n%s\t</optgroup>\n",
						'item'		=> "\t<option %s value='%s'>%s</option>\n",
						'item_args' => Array( 'sel', 'v', 'subopt' ),
						'selected'	=> 'selected '
					);
			}
			$setsel = $strings['selected'];
			if ( isset($options['initial_options'] ) && is_array($options['initial_options']) ) {
				foreach ( $options['initial_options'] as $l => $option ) {
					$is_group = is_array( $option );
					if ( !$is_group ) $option = Array( $l => $option );
					$buf2 = '';
					foreach ( $option as $v => $subopt ) {
						$sel = '';
						$is_arr = is_array( $value );
						if ( is_string( $v ) || is_string( $value ) )
							$cmp = !strcmp( (string)$v, (string)$value );
						else
							$cmp = ( $value == $v );
						if ( ( !$is_arr && $cmp ) || ( $is_arr && in_array( $v, $value ) ) )
							$sel = $setsel;
						$item_arr = Array();
						foreach( $strings['item_args'] as $arg ) $item_arr[] = $$arg;
						$buf2 .= vsprintf( $strings['item'], $item_arr );
					}
					if ( $is_group )
						$buf1 .= sprintf( $strings['group'], $l, $buf2);
					else
						$buf1 .= $buf2;
				}				
				$buf1 = sprintf( $strings['block'], $buf1 );
			}
			return $buf1;
		}
		/**
		 * Outputs a setting item for settings pages and metaboxes.
		 */
		function get_option_html( $args ) {
			extract( $args );
			if ( empty( $options['type'] ) ) $options['type'] = 'checkbox';
			if ( in_array( $options['type'], Array( 'multiselect', 'select', 'multicheckbox', 'radio', 'checkbox', 'textarea', 'text', 'submit', 'hidden' ) ) )
				$value = esc_attr( $value );
			$buf = '';

			switch ( $options['type'] ) {
				case 'multiselect':   $attr .= ' MULTIPLE';
									  $args['attr'] = $attr;
									  $args['name'] = $name = "{$name}[]";
				case 'select':		  $buf .= $this->do_multi_input( $args ); break;
				case 'multicheckbox': $args['name'] = $name = "{$name}[]";
									  $args['options']['type'] = $options['type'] = 'checkbox';
				case 'radio':		  $buf .= $this->do_multi_input( $args ); break;
				case 'checkbox':	  if ( $value ) $attr .= ' CHECKED';
									  $buf .= "<input name='$name' type='{$options['type']}' $attr>\n"; break;
				case 'textarea':	  $buf .= "<textarea name='$name' $attr>$value</textarea>"; break;
				case 'image':		  $buf .= "<input class='sfwd_upload_image_button' type='button' value='Upload Image' style='float:left;' />" .
											  "<input class='sfwd_upload_image_label' name='$name' type='text' readonly $attr value='$value' size=57 style='float:left;clear:left;'>\n";
									  break;
				case 'html':		  $buf .= $value; break;
				default:			  $buf .= "<input name='$name' type='{$options['type']}' $attr value='$value'>\n";
			}
			return $buf;
		}	
	}
}

// Location Search Widget
if ( !class_exists( 'SM_Search_Widget' ) ) {
	class SM_Search_Widget extends SFWD_Generic_Widget {
		public function __construct( $name = 'sm_search_widget', $title = '', $args = array( 'classname' => 'sm_search_widget', 'description' => '' ) ) {
			
			if ( empty( $title ) ) $title = __( 'SimpleMap Search', 'SimpleMap' );
			
			if ( empty( $args['description'] ) ) $args['description'] = __( "Adds a customizable search widget to your site", 'SimpleMap' );
			
			if ( empty( $args['default_options'] ) ) {
				$args['default_options'] = Array(
						'title'				=> Array( 'name' => __( 'Title:', 'SimpleMap' ), 'type' => 'text', 'default' => $title ),
						'show_address'		=> Array( 'name' => __( 'Show Address', 'SimpleMap' ), 'type' => 'checkbox' ),
						'show_city'			=> Array( 'name' => __( 'Show City', 'SimpleMap' ), 'type' => 'checkbox' ),
						'show_state'		=> Array( 'name' => __( 'Show State', 'SimpleMap' ), 'type' => 'checkbox' ),
						'show_zip'			=> Array( 'name' => __( 'Show Zip', 'SimpleMap' ), 'type' => 'checkbox' ),
						'show_country'		=> Array( 'name' => __( 'Show Country', 'SimpleMap' ), 'type' => 'checkbox' ),
						'show_distance'		=> Array( 'name' => __( 'Show Distance', 'SimpleMap' ), 'type' => 'checkbox' ),
						'default_lat'		=> Array( 'type' => 'hidden' ),
						'default_lng'		=> Array( 'type' => 'hidden' ),
						'distance'			=> Array( 'type' => 'hidden' ),
						'limit'				=> Array( 'type' => 'hidden' ),
						'simplemap_page'	=> Array( 'name' => __( 'SimpleMap Page or Post ID:', 'SimpleMap' ), 'type' => 'text', 'default' => 2 )
					);
			}
			
			parent::__construct( $name, $title, $args );
		}
		
		public function widget_content( $args, $instance ) {
			global $simple_map, $wp_rewrite;
			$options = $simple_map->get_options();

			extract( $args, EXTR_SKIP );
			extract( $instance, EXTR_SKIP );
			
			// Set taxonomies to available equivalents 
			$show = array();
			$terms = array();
			foreach ( $options['taxonomies'] as $taxonomy => $tax_info ) {
				$key = strtolower( $tax_info['plural'] );
				$show[$taxonomy] = $instance['show_' . $key] ? 1 : 0;
				$terms[$taxonomy] = $instance[$key] ? $instance[$key] : '';
			}

			$available = $terms;
			
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
			
			/* Display Widget Data */
			
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
			if ( $show_country )
				$location_search .= '<tr><td class="location_search_widget_country_cell location_search_widget_cell">' . apply_filters( 'sm-search-label-country', __( 'Country', 'SimpleMap' ) ) . ':<br /><input type="text" id="location_search_widget_country_field" name="location_search_country" /></td></tr>';
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
		}
	}
}


