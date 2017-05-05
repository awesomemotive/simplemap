<?php
if ( ! class_exists( 'SM_Admin' ) ) {
	/**
	 * Class SM_Admin
	 */
	class SM_Admin {

		/**
		 * SM_Admin constructor.
		 *
		 * Init the admin menu and pages.
		 */
		public function __construct() {
			add_action( 'admin_head', array( &$this, 'load_admin_scripts' ) );
			add_action( 'admin_menu', array( &$this, 'add_addl_menus' ), 20 );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			// Add a button to the TinyMCE console
			add_action( 'edit_form_after_title', array( &$this, 'register_shortcode_button' ) );
		}

		/**
		 * This method adds a button that helps the user insert a shortcode instead of having to memorize it
		 *
		 * @since 2.5.1
		 */
        function register_shortcode_button() {
            global $current_screen;
            $screen = $current_screen->base;
            
            if ( ! ( is_admin() && ( $screen == 'post' || $screen == 'page' ) ) ) {
                return;
            }

            if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) && get_user_option( 'rich_editing' ) == 'true') {
                return;
            }

            wp_enqueue_script( 'jquery-chosen', SIMPLEMAP_URL . '/inc/js/chosen.jquery.min.js', array( 'jquery' ) );
            wp_enqueue_style( 'jquery-chosen', SIMPLEMAP_URL . '/inc/styles/chosen.min.css' );
            wp_enqueue_style( 'simplemap-admin-shortcode', SIMPLEMAP_URL . '/inc/styles/shortcode.css', array( 'jquery-chosen' ) );

			wp_enqueue_style( 'simplemap-awesome-font', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
            add_filter( 'mce_external_plugins', array( &$this, 'mce_external_plugins' ) );
            add_filter( 'mce_buttons', array( &$this, 'mce_buttons' ) );

            // Let's get the html content of the modal
            ob_start();
            include_once SIMPLEMAP_PATH . '/inc/templates/shortcode.php';
            $html   = ob_get_clean();

            $params = array(
                'i10n'  => array(
                    'title_button'          => __( 'Add a SimpleMap', 'SimpleMap' ),
                    'title_window'          => __( 'Add a SimpleMap', 'SimpleMap' ),
                    'title_insert_button'   => __( 'Insert SimpleMap', 'SimpleMap' ),
                    'title_cancel_button'   => __( 'Cancel', 'SimpleMap' ),
                ),
                'html'  => esc_js($html),
            );
?>
    <script type='text/javascript'>
        var simple_map_js_array = JSON.parse( '<?php echo json_encode( $params );?>' );

        function sm_initChosen($) {
            $( '.simplemap-chosen' ).chosen({
                width           : '60%'
            });
        }

        function sm_getAttributes($) {
            var atts        = '';
            var categories  = $( '#simplemap_category' ).val();
            var tags        = $( '#simplemap_location' ).val();
            var lat         = $( '#default_lat' ).val().trim();
            var lon         = $( '#default_lon' ).val().trim();

            if (categories) {
                atts        += ' categories=' + categories.join( ',' );
            }
            if (tags) {
                atts        += ' tags=' + tags.join( ',' );
            }
            if (lat != '') {
                atts        += ' default_lat="' + lat + '"';
            }
            if (lon != '') {
                atts        += ' default_lng="' + lon + '"';
            }

            // define all radio buttons here along with their default value
            // will include the value in the shortcode only when its a non-default value
            var radios      = {'show_categories_filter':'true', 'show_tags_filter':'true', 'hide_map':'false', 'hide_list':'false'};
            for ( var prop in radios ) {
                var val     = $( 'input[name="' + prop + '"]:checked' ).val();
                var def     = radios[prop];
                if ( val !== def) {
                    atts        += ' ' + prop + '=' + val;
                }
            }
            return '[simplemap' + atts + ']';
        }
    </script>
<?php
        }

		/**
		 * This method adds a callback to register our tinymce plugin 
		 *
		 * @since 2.5.1
		 */
        function mce_external_plugins( $plugin_array ) {
            $plugin_array['simplemap_button'] = SIMPLEMAP_URL . '/inc/js/shortcode.js';
            return $plugin_array;
        }

		/**
		 * This method add a callback to add our button to the TinyMCE toolbar
		 *
		 * @since 2.5.1
		 */
        function mce_buttons($buttons) {
            $buttons[] = 'simplemap_button';
            return $buttons;
        }

		/**
		 * Adds admin notices.
		 *
		 * @since 2.5.1
		 */
		function admin_notices() {

			global $simple_map;
			$options = $simple_map->get_options();

			if ( empty( $options['api_key'] ) ) {
				?>
                <div class="error">
                    <p>
						<?php echo __( 'You must enter an API key for your domain.',
								'simplemap' ) .
						           ' <a href="' .
						           admin_url( 'edit.php?post_type=sm-location&page=simplemap' ) .
						           '">'
						           . __( 'Enter a key on the General Options page.',
								'simplemap' )
						           . '</a>'; ?></p>
                </div>
				<?php
			}

		}

		/**
		 * Add's our submenus to the CPT top level menu.
		 */
		public function add_addl_menus(){
			global $simple_map, $sm_options, $sm_help, $sm_import_export;

			// Get options.
			$options = $simple_map->get_options();

			add_submenu_page( 'edit.php?post_type=sm-location',
				__( 'SimpleMap: General Options', 'simplemap' ),
				__( 'General Options', 'simplemap' ),
				apply_filters( 'sm-admin-permissions-sm-options',
					'manage_options' ), 'simplemap', array(
					&$sm_options,
					'print_page',
				) );
			add_submenu_page( 'edit.php?post_type=sm-location',
				__( 'SimpleMap: Import / Export CSV', 'simplemap' ),
				__( 'Import / Export CSV', 'simplemap' ), 'publish_posts',
				'simplemap-import-export', array(
					&$sm_import_export,
					'print_page',
				) );
			add_submenu_page( 'edit.php?post_type=sm-location',
				__( 'SimpleMap: Premium Support', 'simplemap' ),
				__( 'Premium Support', 'simplemap' ), 'publish_posts',
				'simplemap-help', array(
					&$sm_help,
					'print_page',
				) );
        }

		public function load_admin_scripts() {
			// Print admin scripts.
			global $current_screen;

			// General options page.
			if ( 'toplevel_page_simplemap' === $current_screen->id ) :
				/**
				 * TODO: Currently this loads on toplevel_page_simplemap... but that could change once we redo the menus.
				 */
				?>
				<script type="text/javascript">
					jQuery(document).ready(function ($) {
						if ($(document).width() < 1300) {
							$('.postbox-container').css({'width': '99%'});
						}
						else {
							$('.postbox-container').css({'width': '49%'});
						}

						//I'm not sure this is even being used -Michael
						if ($('#autoload').val() == 'none') {
							$('#lock_default_location').attr('checked', false);
							$('#lock_default_location').attr('disabled', true);
							$('#lock_default_location_label').addClass('disabled');
						}

						$('#autoload').change(function () {
							if ($(this).val() != 'none') {
								$('#lock_default_location').attr('disabled', false);
								$('#lock_default_location_label').removeClass('disabled');
							}
							else {
								$('#lock_default_location').attr('checked', false);
								$('#lock_default_location').attr('disabled', true);
								$('#lock_default_location_label').addClass('disabled');
							}
						});

						$('#address_format').siblings().addClass('hidden');
						if ($('#address_format').val() == 'town, province postalcode')
							$('#order_1').removeClass('hidden');
						else if ($('#address_format').val() == 'town province postalcode')
							$('#order_2').removeClass('hidden');
						else if ($('#address_format').val() == 'town-province postalcode')
							$('#order_3').removeClass('hidden');
						else if ($('#address_format').val() == 'postalcode town-province')
							$('#order_4').removeClass('hidden');
						else if ($('#address_format').val() == 'postalcode town, province')
							$('#order_5').removeClass('hidden');
						else if ($('#address_format').val() == 'postalcode town')
							$('#order_6').removeClass('hidden');
						else if ($('#address_format').val() == 'town postalcode')
							$('#order_7').removeClass('hidden');

						$('#address_format').change(function () {
							$(this).siblings().addClass('hidden');
							if ($(this).val() == 'town, province postalcode')
								$('#order_1').removeClass('hidden');
							else if ($(this).val() == 'town province postalcode')
								$('#order_2').removeClass('hidden');
							else if ($(this).val() == 'town-province postalcode')
								$('#order_3').removeClass('hidden');
							else if ($(this).val() == 'postalcode town-province')
								$('#order_4').removeClass('hidden');
							else if ($(this).val() == 'postalcode town, province')
								$('#order_5').removeClass('hidden');
							else if ($(this).val() == 'postalcode town')
								$('#order_6').removeClass('hidden');
							else if ($(this).val() == 'town postalcode')
								$('#order_7').removeClass('hidden');
						});

						// #autoload, #lock_default_location
					});
				</script>
				<?php
			endif;
		}
	}
}
