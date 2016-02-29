<?php
if ( !class_exists( 'SM_Help' ) ) {
	class SM_Help {
		// Prints the options page
		function print_page(){
			global $simple_map;
			$options = $simple_map->get_options();

			extract( $options );

			?>
			<div class="wrap">
					
				<?php
				// Title
				$sm_page_title = 'SimpleMap: Premium Support';
				
				// Toolbar
				$simple_map->show_toolbar( $sm_page_title );
				?>
				
				<div><p><?php _e( 'Jump to a section:', 'SimpleMap' ); ?> <a href="#displaying_your_map"><?php _e( 'Displaying Your Map', 'SimpleMap' ); ?></a> | <a href="#general_options"><?php _e( 'General Options', 'SimpleMap' ); ?></a> | <a href="#adding_a_location"><?php _e( 'Adding a Location', 'SimpleMap' ); ?></a> | <a href="#everything_else"><?php _e( 'Everything Else', 'SimpleMap' ); ?></a></p></div>
			
				<div id="dashboard-widgets-wrap" class="clear">
			
					<div id='dashboard-widgets' class='metabox-holder'>
					
						<div class='postbox-container' style='width:49%;'>
						
							<div id='normal-sortables' class='meta-box-sortables ui-sortable'>
							
								<a name="premium_features"></a>
								<div class="postbox">
					
									<h3><?php _e( 'Premium Features', 'SimpleMap' ); ?></h3>
									
									<div class="inside" style="padding: 0 10px 10px 10px;">
										
										<div class="table">
											<table class="form-table">
																
												<tr><td><?php _e( 'Custom category markers can now be used. Login to Premium support and look for the KB article for instructions.', 'SimpleMap' ); ?></td></tr>
												<tr><td><?php _e( 'We now have a search widget. Login to Premium support and look for the KB article for instructions.', 'SimpleMap' ); ?></td></tr>
												
											</table>
										</div>
										
										<div class="clear"></div>
										
									</div> <!-- inside -->
								</div> <!-- postbox -->
								
								<!-- =========================================
								==============================================
								========================================== -->
							
								<a name="displaying_your_map"></a>
								<div class="postbox">
					
									<h3><?php _e( 'Displaying Your Map', 'SimpleMap' ); ?></h3>
									
									<div class="inside" style="padding: 0 10px 10px 10px;">
										
										<div class="table">
											<table class="form-table">
										
												<tr><td><?php _e( 'To show your map on any post or page, insert the shortcode in the body:', 'SimpleMap' ); ?> <code style="font-size: 1.2em; background: #ffffe0;">[simplemap]</code></td></tr>
										
												<tr><td><?php _e( 'If you want only certain categories or tags to show on a map, insert shortcode like this, where the numbers are replaced with the ID numbers of your desired categories and tags:', 'SimpleMap' ); ?> <code style="font-size: 1.2em; background: #ffffe0;">[simplemap categories=2,5,14 tags=3,6,15]</code></td></tr>

												<tr><td><?php _e( 'If you want to hide the category or tag filters on the search form, insert shortcode like this:', 'SimpleMap' ); ?> <code style="font-size: 1.2em; background: #ffffe0;">[simplemap show_categories_filter=false show_tags_filter=false]</code></td></tr>

												<tr><td><?php _e( 'If you want to hide the map, insert shortcode like this:', 'SimpleMap' ); ?> <code style="font-size: 1.2em; background: #ffffe0;">[simplemap hide_map=true]</code></td></tr>

												<tr><td><?php _e( 'If you want to hide the list of results, insert shortcode like this:', 'SimpleMap' ); ?> <code style="font-size: 1.2em; background: #ffffe0;">[simplemap hide_list=true]</code></td></tr>

												<tr><td><?php _e( 'If you want to override the default lat / lng for a specific map, insert shortcode like this:', 'SimpleMap' ); ?> <code style="font-size: 1.2em; background: #ffffe0;">[simplemap default_lat='34.1346702' default_lng='-118.4389877']</code></td></tr>

												<tr><td><?php _e( 'You can combine tag attributes as needed:', 'SimpleMap' ); ?> <code style="font-size: 1.2em; background: #ffffe0;">[simplemap categories=2,5,14 show_tags_filter=false]</code></td></tr>
												
												<tr><td><?php _e( 'You can place content above or below your map, just like in any other post. Note that any content placed below the map will be pushed down by the list of search results (unless you have them displaying differently with a custom theme).', 'SimpleMap' ); ?></td></tr>
												
												<tr><td><?php printf( __( 'Configure the appearance of your map on the %s General Options page.%s', 'SimpleMap'), '<a href="' . esc_url( admin_url( 'admin.php?page=simplemap' ) ) . '">', '</a>' ); ?></td></tr>
												
											</table>
										</div>
										
										<div class="clear"></div>
										
									</div> <!-- inside -->
								</div> <!-- postbox -->
								
								<!-- =========================================
								==============================================
								========================================== -->
							
								<a name="general_options"></a>
								<div class="postbox">
					
									<h3><?php _e( 'General Options', 'SimpleMap' ); ?></h3>
									
									<div class="inside" style="padding: 0 10px 10px 10px;">
										
										<div class="table">
											<table class="form-table">
												
												<tr valign="top">
													<td width="150"><strong><?php _e( 'Starting Location', 'SimpleMap' ); ?></strong></td>
													<td><?php _e( 'Enter the location the map should open to by default, when no location has been searched for. If you do not know the latitude and longitude of your starting location, enter the address in the provided text field and press "Geocode Address."', 'SimpleMap' ); ?></td>
												</tr>
												
												<tr valign="top">
													<td width="150"><strong><?php _e('Auto-Load Database', 'SimpleMap'); ?></strong></td>
													<td>
														<?php printf( __( '%s No auto-load:%s Locations will not load automatically.', 'SimpleMap' ), '<strong>', '</strong>'); ?><br />
														<?php printf( __( '%s Auto-load search results:%s The locations will load based on the default location, default search radius and zoom level you have set.', 'SimpleMap' ), '<strong>', '</strong>' ); ?><br />
														<?php printf( __( '%s Auto-load all locations:%s All of the locations in your database will load at the default zoom level you have set, disregarding your default search radius. %s This option is not enabled if you have more than 100 locations in your database.%s', 'SimpleMap' ), '<strong>', '</strong>', '<em>', '</em>' ); ?><br /><br />
														
														<?php _e( 'If you leave the checkbox unchecked, then the auto-load feature will automatically move the map to the center of all the loaded locations. If you check the box, your default location will be respected regardless of the locations the map is loading.', 'SimpleMap' ); ?>
													</td>
												</tr>
												
												<tr valign="top">
													<td width="150"><strong><?php _e( 'Special Location Label', 'SimpleMap' ); ?></strong></td>
													<td><?php _e( 'This is meant to flag certain locations with a specific label. It shows up in the search results with a gold star next to it. Originally this was developed for an organization that wanted to highlight people that had been members for more than ten years. It could be used for something like that, or for "Favorite Spots," or "Free Wi-Fi," or anything you want. You can also leave it blank to disable it.', 'SimpleMap' ); ?></td>
												</tr>
												
											</table>
										</div>
										
										<div class="clear"></div>
										
									</div> <!-- inside -->
								</div> <!-- postbox -->
								
								<!-- =========================================
								==============================================
								========================================== -->
								
								<a name="adding_a_location"></a>
								<div class="postbox">
					
									<h3><?php _e( 'Adding a Location', 'SimpleMap' ); ?></h3>
									
									<div class="inside" style="padding: 0 10px 10px 10px;">
										
										<div class="table">
											<table class="form-table">
										
												<tr><td>
													<?php _e( 'To properly add a new location, you must enter one or both of the following:', 'SimpleMap' ); ?><br />
													<span style="padding-left: 20px;"><?php _e( '1. A full address', 'SimpleMap'); ?></span><br />
													<span style="padding-left: 20px;"><?php _e( '2. A latitude and longitude', 'SimpleMap' ); ?></span><br />
													<?php _e( 'If you enter a latitude and longitude, then the address will not be geocoded, and your custom values will be left in place. Entering an address without latitude or longitude will result in the address being geocoded before it is submitted to the database.', 'SimpleMap' ); ?>
												</td></tr>
												
												<tr><td>
													<?php _e( 'You must also enter a name for every location.', 'SimpleMap' ); ?>
												</td></tr>
												
											</table>
										</div>
										
										<div class="clear"></div>
										
									</div> <!-- inside -->
								</div> <!-- postbox -->
								
								
								<!-- =========================================
								==============================================
								========================================== -->
							
							</div> <!-- meta-box-sortables -->

						</div> <!-- postbox-container -->

						<div class='postbox-container' style='width:49%;'>
								
								<div id='side-sortables' class='meta-box-sortables ui-sortable'>
								
								<?php do_action( 'sm-help-side-sortables-top' ); ?>
								
								<!-- #### PREMIUM SUPPORT #### -->
								
								<div class="postbox" >
									
									<h3 style='color:#fff;text-shadow:0 1px 0 #000;background: #fff url( <?php echo SIMPLEMAP_URL; ?>/inc/images/blue-grad.png ) top left repeat-x;'><?php _e( 'Premium Support and Features', 'SimpleMap' ); ?></h3>
									
									<div class="inside" style='padding: 0pt 10px 10px;' >
										
										<?php
										// Check for premium support status
										global $simplemap_ps;

										if ( ! url_has_ftps_for_item( $simplemap_ps ) ) : ?>
										
											<h4><?php printf( __( 'SimpleMap Premium Support Benefits', 'SimpleMap' ), esc_attr( site_url() ) ); ?></h4>
											<p>
												<?php printf( __( 'SimpleMap now offers a premium support package for the low cost of %s per year per domain.', 'SimpleMap' ), '$30.00 USD' ); ?>
											</p>
											<p>
												<?php _e( 'By signing up for SimpleMap premium support, you help to ensure future enhancements to this excellent project as well as the following benefits:', 'SimpleMap' ); ?>
											</p>
										
											<ul style='margin-left:25px;list-style-type:disc'>
												<li><?php _e( 'Around the clock access to our extensive knowledge base and support forum from within your WordPress dashboard', 'SimpleMap' ); ?></li>
												<li><?php _e( 'Professional and timely response times to all your questions from the SimpleMap team', 'SimpleMap' ); ?></li>
												<li><?php _e( 'A 10% discount for any custom functionality you request from the SimpleMap developers', 'SimpleMap' ); ?></li>
												<li><?php _e( 'A 6-12 month advance access to new features integrated into the auto upgrade functionality of WordPress', 'SimpleMap' ); ?></li>
											</ul>
											
											<ul style='margin-left:25px;list-style-type:none'>
												<li><a href='<?php echo get_ftps_paypal_button( $simplemap_ps ); ?>'><?php _e( 'Signup Now', 'SimpleMap' ); ?></a></li>
												<li><a target='_blank' href='<?php echo get_ftps_learn_more_link( $simplemap_ps ); ?>'><?php _e( 'Learn More', 'SimpleMap' ); ?></a></li>
											</ul>
										<?php else : ?>

											<p class='howto'><?php printf( "Your premium support for <code>%s</code> was purchased on <code>%s</code> by <code>%s</code> (%s). It will remain valid for this URL until <code>%s</code>.", get_ftps_site( $simplemap_ps ), date( "F d, Y", get_ftps_purchase_date( $simplemap_ps ) ), get_ftps_name( $simplemap_ps ), get_ftps_email( $simplemap_ps ), date( "F d, Y", get_ftps_exp_date( $simplemap_ps ) ) ); ?></p>
											<p><a href='#' id='premium_help'><?php _e( 'Launch Premium Support widget', 'SimpleMap' ); ?></a> | <a target="blank" href="http://support.simplemap-plugin.com?sso=<?php echo get_ftps_sso_key( $simplemap_ps ); ?>"><?php _e( 'Visit Premium Support web site', 'SimpleMap' );?></a></p>
											<script type="text/javascript" charset="utf-8">
											  Tender = {
											    hideToggle: true,
											    sso: "<?php echo get_ftps_sso_key( $simplemap_ps ); ?>",
											    widgetToggles: [document.getElementById('premium_help')]
											  }
											</script>
											<script src="https://simplemap.tenderapp.com/tender_widget.js" type="text/javascript"></script>
										
										<?php endif; ?>
										
									</div> <!-- inside -->
								</div> <!-- postbox -->
								
								<?php do_action( 'sm-help-side-sortables-bottom' ); ?>
							
							</div> <!-- meta-box-sortables -->
						</div> <!-- postbox-container -->
						
					</div> <!-- dashboard-widgets -->
					
					<div class="clear">
					</div>
				</div><!-- dashboard-widgets-wrap -->
			</div> <!-- wrap -->			
			<?php		
		}
	}
}
?>
