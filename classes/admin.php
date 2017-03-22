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
