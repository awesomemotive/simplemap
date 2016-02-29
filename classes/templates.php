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
		function __construct() {

			// Set the template type
			$this->template_type = $this->set_template_type();

			// Set the specific template for this view
			$this->set_active_template();

			// Add the filter
			add_filter( 'the_content', array( &$this, 'apply_template' ), 1 );

		}

		/**
		 * Parses wp_query to determine the template type we are going to use
		 * As of 2.4, the only opiton is single-location
		 *
		 * @since 2.4
		*/
		function set_template_type() {

			global $post;

			// Exit if we're not on a single disply of the location post type
			if ( 'sm-location' != $post->post_type || ! is_single() )
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

			// Switch based on template type
			switch ( $this->template_type ) {
				case 'single-location' :
				default :
					// Grab the ID for the specific template for this post if it is present
					$template_id = ( get_post_meta( $post->ID, 'sm-location-template', false ) ) ? get_post_meta( $post->ID, 'sm-location-template', false ) : 0;
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
			
			// Grab the post that contains the template strucutre or the hard_coded structure
			if ( 0 != $this->template_id ) {
				// get post obejct via ID
				// return post_content
			} else {
                $return  = "<div class='sm-single-location-default-template'>";
                $return .= "<div class='sm-single-map'>[sm-location data='iframe-map' map_width='100px' map_height='100px']</div>";
				$return .= "<div class='sm-single-location-data'>[sm-location data='full-address']";
                $return .= "<br /><a href=\"[sm-location data='directions']\">Get Directions</a>";
                $return .= "<ul class='sm-single-location-data-ul'>[sm-location data='phone' before='<li>' after='</li>'] [sm-location data='email' before='<li><a href=\"mailto:%self%\">' after='</a></li>'] [sm-location data='sm_category' format='csv' before='<li>Categories: ' after='</li>'] [sm-location data='sm_tag' format='csv' before='<li>Tags: ' after='</li>']</ul>";
                $return .= "</div>";
                $return .= "<hr style='clear:both;' />";
                $return .= "</div>"; 
                $return .= "[sm-location data='description']";
                return apply_filters( 'sm-single-location-default-template', $return );
				
			}

			return $return;
		}

		/**
		 * This method applies the template to the content
		 *
		 * @since 2.4
		*/
		function apply_template( $content ) {
		
			// Return content untouched if not location data
			if ( ! $this->template_type || ! $this->template_structure )
				return $content;	

                        // Return if not in the loop
                        if ( ! in_the_loop() )
                                return $content;

			// Save the location 'description'
			$location_description = $content;

			// Send it through the shortcode parser
			$content = do_shortcode( $this->template_structure );
			
			return $content;

		}

	}

}
