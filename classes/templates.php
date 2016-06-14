<?php
/**
 * This file contains all our classes for the different location templates
 */

/**
 * SM_Template_Factory Class
 *
 * A templating system for SimpleMap
 *
 * @since 2.4
*/
if ( ! class_exists( 'SM_Template_Factory' ) ) {

	class SM_Template_Factory {

		/**
		 * Template Type - single-location, map, results
		*/
		var $template_type = false;

		/**
		 * The post_id for the template used by the current instance of this class
		 * Won't be implemented in 2.4
		*/
		var $template_id;

		/**
		 * The content of the active template (the template itself)
		 * @since 2.4
		*/
		var $template_structure = false;

		/**
		 * Inits the templating system. Don't init class prior to template_redirect hook
		 *
		 * @since 2.4
		*/
		function __construct( $args=array() ) {

			$template_type = empty( $args['template-type'] ) ? false : $args['template-type'];

			// Set the template type
			$this->template_type = $this->set_template_type( $template_type );

			// Set the specific template for this view
			$this->set_active_template();

			// Add the filter
			add_filter( 'the_content', array( &$this, 'apply_template' ), 1 );

		}

		/**
		 * Parses wp_query to determine the template type we are going to use
		 * As of 2.4, the only option is single-location
		 *
		 * @since 2.4
		*/
		function set_template_type( $template_type ) {

			global $post;

			if ( ! empty( $template_type ) )
				return $template_type;

			// Exit if we're not on a single disply of the location post type
			if ( empty( $post ) || 'sm-location' != $post->post_type || ! is_single() )
				return false;

			return 'single-location';
		}

		/**
		 * Sets the template we will use for the current view based on a cascading set of rules
		 * If this is a single location view, do the following:
		 * <ul>
		 * <li>Check for specific template via post-meta
		 * <li>Check for default template</li>
		 * </ul>
		 * 
		 * @since 2.4
		*/
		function set_active_template() {

			global $post;

			if ( empty( $post ) ) return false;

			// Switch based on template type
			switch ( $this->template_type ) {
				case 'results-location' :
					// Grab the ID for the specific template for this post if it is present
					$template_id = ( get_post_meta( $post->ID, 'sm-results-location-template', false ) ) ? get_post_meta( $post->ID, 'sm-results-location-template', false ) : 0;
					break;
				case 'single-location' :
				default :
					// Grab the ID for the specific template for this post if it is present
					$template_id = ( get_post_meta( $post->ID, 'sm-single-location-template', false ) ) ? get_post_meta( $post->ID, 'sm-single-location-template', false ) : 0;
					break;
			}

			$this->template_id = $template_id;
			$this->template_structure = $this->get_template_structure();
		}

		/**
		 * Returns the actual template structure we're going to use for this object
		 *
		 * @since 2.4
		*/
		function get_template_structure() {

			$return = '';

			// Grab the post that contains the template structure or the hard_coded structure
			if ( 0 != $this->template_id ) {
				// get post object via ID
				$template_post = get_post( $this->template_id );
				return $template_post->post_content;
			} else {
				if ( 'results-location' == $this->template_type ) {
					$return .= $this->get_default_results_location_template();
				} elseif ( 'single-location' == $this->template_type ) {
					$return .= $this->get_default_single_location_template();
				}
			}

			return $return;
		}

		/**
		 * This method returns the default template for a location in the search results list
		 * 
		 * @since 2.5
		 */
		function get_default_results_location_template() {
//						<div class="name">[sm-location before=\'<a href="\' data="permalink" after=\'">%%sm-location-name%%</a>\'] [sm-location before="(" data="distance" after=")"]</div>

			$return = <<< EOF
				<div id="location_[sm-location data="ID"]" class="result result-template-2">
					[sm-location before='<div class="icon">' data="thumbnail-image" thumbnail_map_marker_color="" after="</div>"]
					<div class="info">
						<div class="name">[sm-location data="name"] [sm-location before="<small class='result_distance'>(" data="distance" after=")</small>"]</div>
						<div class="address">
							<span class="adr"><span class="street-address">[sm-location data="address"]</span><br />[sm-location data="address2" after="<br />"]<span class="locality">[sm-location data="city"]</span>, <span class="region">[sm-location data="state"]</span> 
							<span class="postal-code">[sm-location data="zip"]</span>,
							<span class="country">[sm-location data="country"]</span><br /></span>
							[sm-location before='<span class="sm_category_list"><small><strong>Categories:</strong> ' data="sm_category" format="csv" after="</small></span>"]
							[sm-location before='<span class="sm_tag_list"><small><strong>Tags:</strong> ' data="sm_tag" format="csv" after="</small></span>"]
						</div>
					</div>

						<div class="extra">
							[sm-location before='<div class="special">' data="special" after="</div>"]
							[sm-location before='<div class="phone">' data="phone" after="</div>"]
							[sm-location before='<div class="email"><a href="mailto:%self%">' data="email" after="</a></div>"]
							[sm-location before='<div class="directions"><a href="' data="directions" after='">Get Directions</a></div>']
							<div class="website">[sm-location data='url' before='<a href="' data="url" after='">Visit Website</a>']</div>
						</div>
					<div style="clear:both;"></div>
				</div>
EOF;
			return apply_filters( 'sm-results-location-default-template', $return );	
		}

		/**
		 * This method returns the default template for a location on the single location page
		 *
		 * @since 2.5
		 */
		function get_default_single_location_template() {
			$return  = <<< EOF
<div class='sm-single-location-default-template'>
<div class='sm-single-map'>[sm-location data='iframe-map' map_width='150px' map_height='150px']</div>
<div class='sm-single-location-data'>[sm-location data='full-address']
<br /><a href="[sm-location data='directions']">Get Directions</a>
<ul class='sm-single-location-data-ul'>[sm-location data='phone' before='<li>' after='</li>'] [sm-location data='email' before='<li><a href=\"mailto:%self%\">' after='</a></li>'] [sm-location data='sm_category' format='csv' before='<li>Categories: ' after='</li>'] [sm-location data='sm_tag' format='csv' before='<li>Tags: ' after='</li>']</ul>
</div>
<hr style='clear:both;' />
</div>
[sm-location data='description']
EOF;
			return apply_filters( 'sm-single-location-default-template', $return );	
		}

		/**
		 * This method applies the template to the content
		 *
		 * @since 2.4
		*/
		function apply_template( $content ) {

			global $simple_map;
			$before_content = $after_content = '';

			if ( is_object( $content ) && ! empty( $content->is_results_location ) ) {
				$simple_map->current_results_location = $content;
				$before_content = apply_filters( 'sm_before_results_location_template', '' );
				$after_content = apply_filters( 'sm_after_results_location_template', '' );
			}

			// Return content untouched if not location data
			if ( ! $this->template_type || ! $this->template_structure )
				return $content;	

			// Return if not in the loop
			if ( ! in_the_loop() && 'results-location' != $this->template_type )
					return $content;

			// Save the location 'description'
			$location_description = $content;

			// Send it through the shortcode parser
			$content = $before_content . do_shortcode( $this->template_structure ) . $after_content;
			
			return $content;

		}

	}

}
