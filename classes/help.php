<?php

if ( ! class_exists( 'SM_Help' ) ) {

	/**
	 * Class SM_Help
	 */
	class SM_Help {

		function print_page() {
			// Prints the options page.
			global $simple_map;
			$options = $simple_map->get_options();

			extract( $options );

			?>
			<div class="wrap">

				<?php
				// Title.
				$sm_page_title = 'SimpleMap: Premium Support';

				// Toolbar.
				$simple_map->show_toolbar( $sm_page_title );
				?>

				<div><p><?php _e( 'Jump to a section:', 'simplemap' ); // XSS ok. ?> <a href="#displaying_your_map"><?php _e( 'Displaying Your Map', 'simplemap' ); // XSS ok. ?></a> | <a href="#general_options"><?php _e( 'General Options', 'simplemap' ); // XSS ok. ?></a> | <a href="#adding_a_location"><?php _e( 'Adding a Location', 'simplemap' ); // XSS ok. ?></a></p></div>

				<div id="dashboard-widgets-wrap" class="clear">

					<div id='dashboard-widgets' class='metabox-holder'>

						<div class='postbox-container'>

							<div id='normal-sortables' class='meta-box-sortables ui-sortable'>

								<!-- =========================================
								==============================================
								========================================== -->

								<a name="displaying_your_map"></a>
								
								<div class="postbox">

									<h3><?php _e( 'Displaying Your Map', 'simplemap' ); // XSS ok. ?></h3>

									<div class="inside">

										<div class="table">
											
											<table class="form-table">

												<tr>
													<td><?php _e( 'To show your map on any post or page, insert the shortcode in the body:', 'simplemap' ); // XSS ok. ?>
														<code>[simplemap]</code>
													</td>
												</tr>

												<tr>
													<td><?php _e( 'If you want only certain categories or tags to show on a map, insert shortcode like this, where the numbers are replaced with the ID numbers of your desired categories and tags:', 'simplemap' ); // XSS ok. ?>
														<code>[simplemap categories=2,5,14 tags=3,6,15]</code>
													</td>
												</tr>

												<tr>
													<td><?php _e( 'If you want to hide the category or tag filters on the search form, insert shortcode like this:', 'simplemap' ); // XSS ok. ?>
														<code>[simplemap show_categories_filter=false show_tags_filter=false]</code>
													</td>
												</tr>

												<tr>
													<td><?php _e( 'If you want to hide the map, insert shortcode like this:', 'simplemap' ); // XSS ok. ?>
														<code>[simplemap hide_map=true]</code></td>
												</tr>

												<tr>
													<td><?php _e( 'If you want to hide the list of results, insert shortcode like this:', 'simplemap' ); // XSS ok. ?>
														<code>[simplemap hide_list=true]</code></td>
												</tr>

												<tr>
													<td><?php _e( 'If you want to override the default lat / lng for a specific map, insert shortcode like this:', 'simplemap' ); // XSS ok. ?>
														<code>[simplemap default_lat='34.1346702' default_lng='-118.4389877']</code>
													</td>
												</tr>

												<tr>
													<td><?php _e( 'You can combine tag attributes as needed:', 'simplemap' ); // XSS ok. ?>
														<code>[simplemap categories=2,5,14 show_tags_filter=false]</code></td>
												</tr>

												<tr>
													<td><?php _e( 'You can place content above or below your map, just like in any other post. Note that any content placed below the map will be pushed down by the list of search results (unless you have them displaying differently with a custom theme).', 'simplemap' ); // XSS ok. ?></td>
												</tr>

												<tr>
													<td><?php printf( __( 'Configure the appearance of your map on the %s General Options page.%s', 'simplemap' ), '<a href="' . esc_url( admin_url( 'admin.php?page=simplemap' ) ) . '">', '</a>' ); ?></td>
												</tr>

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

									<h3><?php _e( 'General Options', 'simplemap' ); // XSS ok. ?></h3>

									<div class="inside">

										<div class="table">
											
											<table class="form-table">

												<tr valign="top">
													<td width="150">
														<strong><?php _e( 'Starting Location', 'simplemap' ); // XSS ok. ?></strong>
													</td>
													<td><?php _e( 'Enter the location the map should open to by default, when no location has been searched for. If you do not know the latitude and longitude of your starting location, enter the address in the provided text field and press "Geocode Address."', 'simplemap' ); // XSS ok. ?></td>
												</tr>

												<tr valign="top">
													<td width="150">
														<strong><?php _e( 'Auto-Load Database', 'simplemap' ); // XSS ok. ?></strong>
													</td>
													<td>
														<?php printf( __( '%s No auto-load:%s Locations will not load automatically.', 'simplemap' ), '<strong>', '</strong>' ); ?>
														<br/>
														<?php printf( __( '%s Auto-load search results:%s The locations will load based on the default location, default search radius and zoom level you have set.', 'simplemap' ), '<strong>', '</strong>' ); ?>
														<br/>
														<?php printf( __( '%s Auto-load all locations:%s All of the locations in your database will load at the default zoom level you have set, disregarding your default search radius. %s This option is not enabled if you have more than 100 locations in your database.%s', 'simplemap' ), '<strong>', '</strong>', '<em>', '</em>' ); ?>
														<br/><br/>

														<?php _e( 'If you leave the checkbox unchecked, then the auto-load feature will automatically move the map to the center of all the loaded locations. If you check the box, your default location will be respected regardless of the locations the map is loading.', 'simplemap' ); // XSS ok. ?>
													</td>
												</tr>

												<tr valign="top">
													<td width="150">
														<strong><?php _e( 'Special Location Label', 'simplemap' ); // XSS ok. ?></strong>
													</td>
													<td><?php _e( 'This is meant to flag certain locations with a specific label. It shows up in the search results with a gold star next to it. Originally this was developed for an organization that wanted to highlight people that had been members for more than ten years. It could be used for something like that, or for "Favorite Spots," or "Free Wi-Fi," or anything you want. You can also leave it blank to disable it.', 'simplemap' ); // XSS ok. ?></td>
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

									<h3><?php _e( 'Adding a Location', 'simplemap' ); // XSS ok. ?></h3>

									<div class="inside">

										<div class="table">
											
											<table class="form-table">

												<tr>
													<td>
														<?php _e( 'To properly add a new location, you must enter one or both of the following:', 'simplemap' ); // XSS ok. ?>
														<br/>
														<ol>
															<li><?php _e( 'A full address', 'simplemap' ); // XSS ok. ?></li>
															<li><?php _e( 'A latitude and longitude', 'simplemap' ); // XSS ok. ?></li>
														</ol>
														<?php _e( 'If you enter a latitude and longitude, then the address will not be geocoded, and your custom values will be left in place. Entering an address without latitude or longitude will result in the address being geocoded before it is submitted to the database.', 'simplemap' ); // XSS ok. ?>
													</td>
												</tr>

												<tr>
													<td>
														<?php _e( 'You must also enter a name for every location.', 'simplemap' ); // XSS ok. ?>
													</td>
												</tr>

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

						<div class='postbox-container'>

							<div id='side-sortables' class='meta-box-sortables ui-sortable'>

								<?php do_action( 'sm-help-side-sortables-top' ); ?>

								<!-- #### PREMIUM SUPPORT #### -->

								<div class="postbox">

									<h3 class='blue-bg'><?php _e( 'Premium Support and Features', 'simplemap' ); // XSS ok. ?></h3>

									<div class="inside">

										<?php
										// Check for premium support status
										global $simplemap_ps;

										if ( ! url_has_ftps_for_item( $simplemap_ps ) ) : ?>

											<p>Premium support is no longer available for SimpleMap.</p>
										
										<?php else : ?>

											<p class='howto'><?php printf( "Premium support is no longer available for SimpleMap. You will continue to receive support until your current subscription expires on <code>%s</code>.", date( "F d, Y", get_ftps_exp_date( $simplemap_ps ) ) ); ?></p>
											
											<p><a target="blank" href="https://simplemap-plugin.com/contact/"><?php _e( 'Visit Premium Support web site', 'simplemap' ); ?></a></p>

										<?php endif; ?>

									</div> <!-- inside -->
									
								</div> <!-- postbox -->

								<?php do_action( 'sm-help-side-sortables-bottom' ); ?>

							</div> <!-- meta-box-sortables -->
							
						</div> <!-- postbox-container -->

					</div> <!-- dashboard-widgets -->

					<div class="clear"></div>
					
				</div><!-- dashboard-widgets-wrap -->
				
			</div> <!-- wrap -->
			
			<?php
		}
	}
}
?>
