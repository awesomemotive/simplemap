<?php
if ( !class_exists( 'SM_Admin' ) ){
	class SM_Admin{
		
		// Init the admin menu and pages
		function sm_admin(){
			add_action( 'admin_menu', array( &$this, 'menu_shuffle' ), 20 );
			add_action( 'admin_head', array( &$this, 'load_admin_scripts' ) );
		}
		
		// Remove Menu added by WordPress UI and add my own as a subpage
		function menu_shuffle(){
			global $menu,$simple_map,$sm_options,$sm_help,$sm_import_export;

			// Get options
			$options = $simple_map->get_options();

			// loop through menu and find the one I need to disable
			foreach( $menu as $key => $value ) {
				if ( in_array( 'edit.php?post_type=sm-location' , $value ) ) {
					unset( $menu[$key] );
				}
			}
			add_menu_page(__('SimpleMap Options', 'SimpleMap'), 'SimpleMap', apply_filters( 'sm-admin-permissions-sm-options', 'publish_posts' ), 'simplemap', array( &$sm_options, 'print_page' ), SIMPLEMAP_URL.'/inc/images/icon.png' );
			add_submenu_page( 'simplemap', __('SimpleMap: General Options', 'SimpleMap'), __( 'General Options', 'SimpleMap'), apply_filters( 'sm-admin-permissions-sm-options', 'manage_options' ), 'simplemap', array( &$sm_options, 'print_page' ) );
			add_submenu_page( 'simplemap', __('SimpleMap: Add Location', 'SimpleMap'), __( 'Add Location', 'SimpleMap' ), apply_filters( 'sm-admin-permissions-sm-add-locations', 'publish_posts' ), 'post-new.php?post_type=sm-location' );
			add_submenu_page( 'simplemap', __('SimpleMap: Edit Locations', 'SimpleMap'), __( 'Edit Locations', 'SimpleMap' ), apply_filters( 'sm-admin-permissions-sm-edit-locations', 'publish_posts' ), 'edit.php?post_type=sm-location' );

			foreach ( $options['taxonomies'] as $taxonomy => $tax_info ) {
				add_submenu_page( 'simplemap', __('SimpleMap: Location ' . $tax_info['plural'], 'SimpleMap'), __( 'Location ' . $tax_info['plural'], 'SimpleMap' ), 'publish_posts', 'edit-tags.php?taxonomy=' . $taxonomy . '&amp;post_type=sm-location' );
			}

			add_submenu_page( 'simplemap', __('SimpleMap: Import / Export CSV', 'SimpleMap'), __('Import / Export CSV', 'SimpleMap'), 'publish_posts', 'simplemap-import-export', array( &$sm_import_export, 'print_page' ) );
			add_submenu_page( 'simplemap', __('SimpleMap: Premium Support', 'SimpleMap'), __('Premium Support', 'SimpleMap'), 'publish_posts', 'simplemap-help', array( &$sm_help, 'print_page' ) );
		}

		// Print admin scripts
		function load_admin_scripts(){
			global $current_screen;
			
			#### GENERAL OPTIONS PAGE ####
			if ( 'toplevel_page_simplemap' == $current_screen->id ) :
				?>
				<script type="text/javascript">
				jQuery(document).ready(function($) {
					if ($(document).width() < 1300) {
						$('.postbox-container').css({'width': '99%'});
					}
					else {
						$('.postbox-container').css({'width': '49%'});
					}
					
					if ($('#autoload').val() == 'none') {
						$('#lock_default_location').attr('checked', false);
						$('#lock_default_location').attr('disabled', true);
						$('#lock_default_location_label').addClass('disabled');
					}
					
					$('#autoload').change(function() {
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
					
					$('#address_format').change(function() {
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

		function on_activate() {
			//$current = get_site_transient( 'update_plugins' );
			//if ( !isset( $current->checked[SIMPLEMAP_FILE] ) ) {
			return; // <--- Remove to enable
			$options = get_option( 'SimpleMap_options' );
			if ( empty( $options ) ) {
				$options = array( 'auto_locate' => 'html5' );
				update_option( 'SimpleMap_options', $options );
			}
		}
	}
}
?>
