<?php

if ( ! class_exists( 'SM_Import_Export' ) ) {

	/**
	 * Class SM_Import_Export
	 */
	class SM_Import_Export {

		/**
		 * SM_Import_Export constructor.
		 *
		 * Update options of form submission.
		 *
		 */
		public function __construct() {
			add_action( 'admin_init', array( &$this, 'export_csv' ) );
			add_action( 'admin_init', array( &$this, 'export_legacy_csv' ) );
			add_action( 'admin_init', array( &$this, 'delete_legacy_tables' ) );

		}

		/**
		 * CSV Exported.
		 *
		 * Handles the exporting of data to CSV.
		 *
		 */
		public function export_csv() {

			if ( isset( $_POST['sm_export_csv_nonce'] ) && wp_verify_nonce( $_POST['sm_export_csv_nonce'], 'sm_export_csv' ) ) {
				// Grab locations.
				$content = array();
				set_time_limit( 0 );

				$query_args = array(
					'post_type'      => 'sm-location',
					'post_status'    => 'publish',
					'posts_per_page' => '-1',
				);

				$locations_results = new WP_Query( $query_args );
				$locations         = $locations_results->posts;

				// Include CSV library.
				require_once( SIMPLEMAP_PATH . '/classes/parsecsv.lib.php' );
				$taxonomies = get_object_taxonomies( 'sm-location' );

				foreach ( $locations as $key => $location ) {
					$location_data = array(
						'name'        => esc_attr( $location->post_title ),
						'address'     => esc_attr( get_post_meta( $location->ID, 'location_address', true ) ),
						'address2'    => esc_attr( get_post_meta( $location->ID, 'location_address2', true ) ),
						'city'        => esc_attr( get_post_meta( $location->ID, 'location_city', true ) ),
						'state'       => esc_attr( get_post_meta( $location->ID, 'location_state', true ) ),
						'zip'         => esc_attr( get_post_meta( $location->ID, 'location_zip', true ) ),
						'country'     => esc_attr( get_post_meta( $location->ID, 'location_country', true ) ),
						'phone'       => esc_attr( get_post_meta( $location->ID, 'location_phone', true ) ),
						'email'       => esc_attr( get_post_meta( $location->ID, 'location_email', true ) ),
						'fax'         => esc_attr( get_post_meta( $location->ID, 'location_fax', true ) ),
						'url'         => esc_attr( get_post_meta( $location->ID, 'location_url', true ) ),
						'description' => esc_attr( $location->post_content ),
						'special'     => esc_attr( get_post_meta( $location->ID, 'location_special', true ) ),
						'lat'         => esc_attr( get_post_meta( $location->ID, 'location_lat', true ) ),
						'lng'         => esc_attr( get_post_meta( $location->ID, 'location_lng', true ) ),
						'dateUpdated' => esc_attr( $location->post_modified ),
					);

					foreach ( $taxonomies as $tax ) {
						$term_value = '';
						if ( $terms = wp_get_object_terms( $location->ID, $tax ) ) {
                            $term_array = array();
                            foreach ( $terms as $term ) {
                                $parent_tree    = array();
                                $child          = $term;
                                while ( ! empty( $child->parent ) ) {
                                    $parent     = get_term_by( 'id', $child->parent, $tax );
                                    if ( $parent ) {
                                        $parent_tree[]  = $parent->name;
                                        $child      = $parent;
                                    }
                                }
                                $prepend            = '';
                                if ( $parent_tree ) {
                                    $parent_tree    = array_reverse( $parent_tree );
                                    $prepend        = implode( ' | ', $parent_tree ) . ' | ';
                                }
                                $term_array[]   = $prepend . $term->name;
                            }
							$term_value = implode( ',', $term_array );
						}
						$location_data[ "tax_$tax" ] = esc_attr( $term_value );
					}

					$content[] = $location_data;
				}

				if ( ! empty( $content ) ) {
					$csv = new smParseCSV();
					$csv->output( 'simplemap.csv', $content, array_keys( reset( $content ) ) );
					die();
				}
			}
		}

		/**
		 * CSV Exporter.
		 *
		 * Handles the exporting of legacy data to CSV.
		 *
		 */
		public function export_legacy_csv() {

			if ( isset( $_GET['sm-action'] ) && 'export-legacy-csv' === $_GET['sm-action'] ) {

				// Include CSV library.
				include_once( SIMPLEMAP_PATH . '/classes/parsecsv.lib.php' );

				// Grab Categories.
				if ( $categories = $wpdb->get_results( 'SELECT * FROM `' . $wpdb->prefix . 'simple_map_cats`' ) ) {
					foreach ( $categories as $key => $value ) {
						$cats[ $value->id ] = $value;
					}
				}
				// Grab locations.
				if ( $locations = $wpdb->get_results( 'SELECT * FROM `' . $wpdb->prefix . 'simple_map`' ) ) {

					foreach ( $locations as $key => $location ) {

						$catnames = '';

						// Do Cats.
						if ( isset( $location->category, $cats[ $location->category ] ) && 0 != $location->category ) {
							$catnames = $cats[ $location->category ]->name;
						}
						$content[] = array(
							'name'        => esc_attr( $location->name ),
							'address'     => esc_attr( $location->address ),
							'address2'    => esc_attr( $location->address2 ),
							'city'        => esc_attr( $location->city ),
							'state'       => esc_attr( $location->state ),
							'zip'         => esc_attr( $location->zip ),
							'country'     => esc_attr( $location->country ),
							'phone'       => esc_attr( $location->phone ),
							'fax'         => esc_attr( $location->fax ),
							'url'         => esc_attr( $location->url ),
							'description' => esc_attr( $location->description ),
							'category'    => esc_attr( $catnames ),
							'tags'        => esc_attr( $location->tags ),
							'special'     => esc_attr( $location->special ),
							'lat'         => esc_attr( $location->lat ),
							'lng'         => esc_attr( $location->lng ),
							'dateUpdated' => esc_attr( $location->dateUpdated ),
						);

					}

					$csv = new smParseCSV();
					$csv->output( 'simplemap.csv', $content, array(
						'name',
						'address',
						'address2',
						'city',
						'state',
						'zip',
						'country',
						'phone',
						'fax',
						'url',
						'description',
						'category',
						'tags',
						'special',
						'lat',
						'lng',
						'dateUpdated',
					) );
					die();

				} else {
					$csv = new smParseCSV();
					$csv->output( 'simplemap.csv', array( array( 'You have no locations in your legacy database' ) ) );
					die();
				}
			}

		}

		/**
		 * Delete legacy tables.
		 *
		 * Deletes old tables.
		 *
		 */
		public function delete_legacy_tables() {
			// Deletes legacy tables.
			global $wpdb, $simple_map;

			// Confirm we have both permisssion to do this and we have intent to do this.
			if ( isset( $_GET['sm-action'] ) && 'delete-legacy-simplemap' === $_GET['sm-action'] && current_user_can( 'manage_options' ) && check_admin_referer( 'delete-legacy-simplemap' ) ) {

				$drop_sm   = 'DROP TABLE `' . $wpdb->prefix . 'simple_map`;';
				$drop_cats = 'DROP TABLE `' . $wpdb->prefix . 'simple_map_cats`;';

				$wpdb->query( $wpdb->prepare( $drop_sm ) );
				$wpdb->query( $wpdb->prepare( $drop_cats ) );

				if ( $simple_map->legacy_tables_exist() ) {
					wp_redirect( admin_url( 'admin.php?page=simplemap-import-export&sm-msg=3' ) );
					die();
				} else {
					wp_redirect( admin_url( 'admin.php?page=simplemap-import-export&sm-msg=2' ) );
					die();
				}
			}
		}

		/**
		 *
		 * All location data.
		 *
		 * @param array $init
		 *
		 * @return array|mixed|null|void
		 */
		public function get_location_data_types( array $init = array() ) {
			static $types = null;

			if ( empty( $types ) ) {
				$types = array(
					'name'        => __( 'Name', 'SimpleMap' ),
					'address'     => __( 'Address', 'SimpleMap' ),
					'address2'    => __( 'Address 2', 'SimpleMap' ),
					'city'        => __( 'City', 'SimpleMap' ),
					'state'       => __( 'State', 'SimpleMap' ),
					'zip'         => __( 'Zip', 'SimpleMap' ),
					'country'     => __( 'Country', 'SimpleMap' ),
					'phone'       => __( 'Phone', 'SimpleMap' ),
					'fax'         => __( 'Fax', 'SimpleMap' ),
					'email'       => __( 'Email', 'SimpleMap' ),
					'url'         => __( 'URL', 'SimpleMap' ),
					'description' => __( 'Description', 'SimpleMap' ),
					'special'     => __( 'Special', 'SimpleMap' ),
					'lat'         => __( 'Lat', 'SimpleMap' ),
					'lng'         => __( 'Lng', 'SimpleMap' ),
				);

				$legacy_types = array(
					'category' => __( 'Legacy Taxonomy: Categories', 'SimpleMap' ),
					'tags'     => __( 'Legacy Taxonomy: Tags', 'SimpleMap' ),
					'days'     => __( 'Legacy Taxonomy: Days', 'SimpleMap' ),
					'times'    => __( 'Legacy Taxonomy: Times', 'SimpleMap' ),
				);

				foreach ( get_object_taxonomies( 'sm-location', 'objects' ) as $taxonomy => $tax_info ) {
					$types[ 'tax_' . $taxonomy ] = __( 'Taxonomy: ' . $tax_info->label, 'SimpleMap' );
				}

				foreach ( $init as $key => $value ) {
					if ( substr( $key, 0, 4 ) === 'tax_' ) {
						$types[ $key ] = __( 'Taxonomy: ' . ucwords( substr( $key, strpos( $key, '-' ) + 1 ) ), 'SimpleMap' );
					} elseif ( isset( $legacy_types[ $key ] ) ) {
						$types[ $key ] = $legacy_types[ $key ];
					}
				}

				$types = apply_filters( 'sm-data-types', $types );
			}

			return $types;
		}

		/**
		 * CSV Importer.
		 *
		 * Handles the importing of data from CSV.
		 *
		 */
		public function import_csv() {
			global $simple_map, $sm_locations, $current_user, $blog_id;

			// Define Importing Constant.
			define( 'WP_IMPORTING', true );

			if ( isset( $_POST['sm-action'], $_POST['step'] ) && 'import-csv' === $_POST['sm-action'] && 2 == $_POST['step'] ) {

				echo '<div class="wrap">';

				// Title.
				$sm_page_title = apply_filters( 'sm-import-export-page-title', 'SimpleMap: Import. Step One' );

				// Toolbar.
				$simple_map->show_toolbar( $sm_page_title );

				echo '
	<div id="dashboard-widgets-wrap" class="clear">

		<div id="dashboard-widgets" class="metabox-holder">

			<div class="postbox-container half-width">

				<div id="normal-sortables" class="meta-box-sortables ui-sortable">

					<div class="postbox">

						<h3>';


				_e( 'CSV Import: Step Two: Importing CSV', 'SimpleMap' );

				echo '</h3>

						<div class="inside">';


				// Include CSV library.
				require_once( SIMPLEMAP_PATH . '/classes/parsecsv.lib.php' );

				$file_location = $this->get_import_path( $blog_id . '.csv' );

				if ( file_exists( $file_location ) && $csv = new smParseCSV() ) {
					$csv->auto( $file_location );

					if ( isset( $csv->data ) ) {
						echo '<ol>';


						/**
						 *We're going to do some pre-processing to prevent WP's wp_get_unique_slug from pinging the database
						 * a ridiculous amount of times in the event that this file is importing several thousand locations with
						 * same name (like a franchise).
						 */
						$post_names = array();

						$taxes = $simple_map->get_taxonomy_settings();
						foreach ( $taxes as $taxonomy => $tax_info ) {
							$taxes[ $taxonomy ] = $tax_info['field'];
						}

						$types   = $this->get_location_data_types( current( $csv->data ) );
						$columns = array();
						foreach ( $types as $key => $value ) {
							$taxonomy = null;

							// If custom taxonomies don't exist, register them temporarily in the event that we need to import them.
							if ( substr( $key, 0, 7 ) === 'tax_sm-' ) {
								$taxonomy           = substr( $key, 4 );
								$taxes[ $taxonomy ] = $key;
							} // Allow legacy taxonomy column names.
							elseif ( array_search( $key, $taxes ) ) {
								$taxonomy = $key;
							}

							if ( isset( $taxonomy ) ) {
								$sm_locations->register_location_taxonomy( $taxonomy, $simple_map->get_taxonomy_settings( $taxonomy ) );
							}

							$columns[ $key ] = str_replace( 'col_', '', array_search( $key, $_POST ) );
						}

						$active_taxes = get_object_taxonomies( 'sm-location' );
						$taxes        = array_intersect_key( $taxes, array_flip( $active_taxes ) );

						$legacy_taxes = $simple_map->get_taxonomy_settings();

						foreach ( $csv->data as $row => $location ) {
							// Give me 20 seconds for each location. That should be more than enough time.
							set_time_limit( 20 );

							// Convert assoc to int array since I can't trust the headings from the user.
							$location = array_values( $location );

							// Use the information the user gave me via select boxes to map columns to correct attributes.
							foreach ( $columns as $key => $column ) {
								if ( isset( $location[ $column ] ) ) {
									if ( 'description' === $key ) {
										$to_insert[ $key ] = trim( html_entity_decode( $location[ $column ] ) );
									} else {
										$to_insert[ $key ] = trim( $location[ $column ] );
									}
								} else {
									$to_insert[ $key ] = '';
								}
							}

							// Combine legacy taxonomy fields into new taxonomy fields.
							foreach ( $legacy_taxes as $taxonomy => $legacy_taxonomy ) {
								$old_key = $legacy_taxonomy['field'];
								$key     = 'tax_' . $taxonomy;
								if ( isset( $to_insert[ $old_key ] ) ) {
									$to_insert += array( $key => '' );
									$to_insert[ $key ] = implode( ',', array_filter( array(
										$to_insert[ $key ],
										$to_insert[ $old_key ],
									) ) );
									unset( $to_insert[ $old_key ] );
								}
							}

							// Prep and insert.
							if ( isset( $to_insert ) ) {
								$options  = $simple_map->get_options();
								$geocoded = '';

								// Maybe geo encode.
								if ( empty( $to_insert['lat'] ) || empty( $to_insert['lng'] ) ) {
									if ( $geo = $simple_map->geocode_location( $to_insert['address'], $to_insert['city'], $to_insert['state'], $to_insert['zip'], $to_insert['country'], $options['api_key'] ) ) {
										$geocoded = __( 'geocoded and ', 'SimpleMap' );

										if ( isset( $geo['lat'] ) ) {
											$to_insert['lat'] = $geo['lat'];
										}

										if ( isset( $geo['lng'] ) ) {
											$to_insert['lng'] = $geo['lng'];
										}
									}
								}

								// Prevent dup names before getting to wp_unique_slug function.
								$clean_name = sanitize_title( $to_insert['name'] );

								// Start by check to see if this post's name has been used before.
								if ( isset( $post_names[ $clean_name ] ) ) {

									// Set the int to the value of the post_name key (this is set the first time we import a post with this title).
									$post_names_int = $post_names[ $clean_name ];

									// Just to be safe, lets make sure the incremented name doesn't exist. Loop till we find one available.
									while ( isset( $post_names[ $clean_name . '-' . $post_names_int ] ) ) {
										$post_names_int ++;
									}

									// Set the new unique slug.
									$unique_title = $clean_name . '-' . $post_names_int;

									// Add the post_name to the post attributes array we're about to insert.
									$vars['post_name'] = $unique_title;

									// Update the original slug for this title with the new int.
									$post_names[ $clean_name ] = $post_names[ $unique_title ] = $post_names_int;
								} else {
									// If we made it here, its the first time we're inserting a post with this title (this import anyway).
									// Add it to the post attributes array we're about to send to wp_insert_post.
									$vars['post_name'] = $clean_name;

									// Log this title as used in the post_names array with an int of 1.
									$post_names[ $clean_name ] = 1;
								}

								// Prep for WordPress function.
								wp_get_current_user();
								$vars['post_title']   = $to_insert['name'];
								$vars['post_author']  = $current_user->ID;
								$vars['post_type']    = 'sm-location';
								$vars['post_status']  = 'publish';
								$vars['post_content'] = $to_insert['description'];

								// Insert into WordPress post table.
								if ( $id = wp_insert_post( $vars ) ) {
									update_post_meta( $id, 'location_address', $to_insert['address'] );
									update_post_meta( $id, 'location_address2', $to_insert['address2'] );
									update_post_meta( $id, 'location_city', $to_insert['city'] );
									update_post_meta( $id, 'location_state', $to_insert['state'] );
									update_post_meta( $id, 'location_zip', $to_insert['zip'] );
									update_post_meta( $id, 'location_country', $to_insert['country'] );
									update_post_meta( $id, 'location_phone', $to_insert['phone'] );
									update_post_meta( $id, 'location_fax', $to_insert['fax'] );
									update_post_meta( $id, 'location_email', $to_insert['email'] );
									update_post_meta( $id, 'location_url', $to_insert['url'] );
									update_post_meta( $id, 'location_special', $to_insert['special'] );
									update_post_meta( $id, 'location_lat', $to_insert['lat'] );
									update_post_meta( $id, 'location_lng', $to_insert['lng'] );

									foreach ( $taxes as $taxonomy => $tax_field ) {
										if ( isset( $to_insert[ $tax_field ] ) ) {
											// Place comma separated values into array.
											$terms = explode( ',', $to_insert[ $tax_field ] );

											// Loop through array. If term does not exist, create it.
											// Then associate the term with the location.
											foreach ( (array) $terms as $key => $name ) {
												$name = trim( $name );

												// Skip it if we have bad data.
												if ( empty( $name ) ) {
													continue;
												}

                                                // check if it is parent | child type of entry
                                                $args           = array();
                                                $parent_tree    = array_map( 'trim', explode( '|', $name ) );
                                                if ( count( $parent_tree ) > 1 ) {
                                                    // pop the last element as this will be handled outside the if
                                                    $name       = array_pop( $parent_tree );
                                                    $parent_id  = 0;
                                                    foreach ( $parent_tree as $parent ) {
                                                        $args       = array(
                                                            'parent'    => $parent_id,
                                                        );
                                                        // create the parent if not already created and store it for future use
                                                        if ( $term_obj = get_term_by( 'name', $parent, $taxonomy ) ) {
                                                            $parent_id = $term_obj->term_id;
                                                        } else {
                                                            $term_array = wp_insert_term( $parent, $taxonomy, $args );
                                                            if ( is_wp_error( $term_array ) ) {
                                                                continue;
                                                            }
                                                            $parent_id  = $term_array['term_id'];
                                                        }
                                                    }
                                                    $args       = array(
                                                        'parent'    => $parent_id,
                                                    );
                                                }

												// Grab or create and grab the category ID.
												if ( $term_obj = get_term_by( 'name', $name, $taxonomy ) ) {
													$term_id = $term_obj->term_id;
												} else {
													$term_array = wp_insert_term( $name, $taxonomy, $args );
													if ( is_wp_error( $term_array ) ) {
														continue;
													}
													$term_id = $term_array['term_id'];
												}

												// This is just a failsafe. It also gives us access to vars the WP API created rather than from the CSV
												if ( ! is_wp_error( $term_id ) && ( $term = get_term( (int) $term_id, $taxonomy ) ) && ! is_wp_error( $term ) ) {
														// Associate (last var appends term to rather than replaces existing terms)
														wp_set_object_terms( $id, $term->name, $taxonomy, true );
														unset( $term );

												}
											}
										}
									}

									echo '<li>' . sprintf( esc_attr( $to_insert['name'] ) . __( ' was successfully %simported', 'SimpleMap' ), $geocoded ) . '</li>';
								} else {
									echo '<li>' . esc_attr( $to_insert['name'] ) . __( ' failed to import properly', 'SimpleMap' ) . '</li>';
								}

								unset( $to_insert );
								unset( $geocoded );
							}
						}

						echo '</ul>';
						echo '<h2>' . sprintf( __( 'View them <a href="%s">here</a>', 'SimpleMap' ), admin_url( 'edit.php?post_type=sm-location' ) ) . '</h2>';
					}

					// Import is finished, delete csv and redirect to edit locations page.
					unlink( $file_location );
				}
				echo '
		</div>
		</div>
		</div>
		</div>
		</div>';

			}
		}

		/**
		 * Gets import path.
		 *
		 * @param $file
		 *
		 * @return string
		 */
		function get_import_path( $file ) {
			$upload_dir = wp_upload_dir();

			// fallback to original path...?
			if ( isset( $upload_dir['error'] ) && ! empty( $upload_dir['error'] ) ) {
				return WP_PLUGIN_DIR . '/sm-temp-csv-' . $file;
			}

			return $upload_dir['path'] . '/sm-temp-csv-' . $file;
		}

		/**
		 * CSV Preview.
		 *
		 * Generates the CSV preview for the imported data.
		 *
		 */
		public function do_csv_preview() {

			global $simple_map, $blog_id;
			$options = $simple_map->get_options();

			if ( !isset( $options['api_key'] ) )
				$options['api_key'] = '';

			extract( $options );

			echo '<div class="wrap">';

			// Title.
			$sm_page_title = apply_filters( 'sm-import-export-page-title', 'SimpleMap: Import. Step One' );

			// Toolbar.
			$simple_map->show_toolbar( $sm_page_title );
			echo '

	<div id="dashboard-widgets-wrap" class="clear">

		<div id="dashboard-widgets" class="metabox-holder">

			<div class="postbox-container full-width">

				<div id="normal-sortables" class="meta-box-sortables ui-sortable">

					<div class="postbox">

						<h3>';

			_e( 'CSV Import: Step One', 'SimpleMap' );


			echo '</h3>

						<div class="inside sm-scroll">

							<p class="howto">';


			printf( __( 'The first step is to confirm that we importing the data correctly. %sPlease match the following sample data%s from your CSV to the correct data type by selecting an attributes form the downdown boxes.', 'SimpleMap' ), '<strong>', '</strong>' );

			echo '</p>';


			// Include CSV library.
			include_once( SIMPLEMAP_PATH . '/classes/parsecsv.lib.php' );

			$file_location = $this->get_import_path( $blog_id . '.csv' );
			if ( move_uploaded_file( $_FILES['simplemap-csv-upload']['tmp_name'], $file_location ) ) {
				if ( $csv = new smParseCSV( $file_location ) ) {
					echo '
									<form action="" method="post">
										<input type="hidden" name="sm-action" value="import-csv"/>
										<input type="hidden" name="step" value="2"/>
										<p><input type="submit" class="button-primary"
										          value="';

					_e( 'Import CSV', 'SimpleMap' );

					echo '"/> | <a href="">';

					_e( 'Cancel', 'SimpleMap' );

					echo '</a></p>
										<table>
											<tr>';

					$this->get_location_data_types( current( $csv->data ) );
					foreach ( $csv->titles as $col => $title ) {
						echo '<td>' . $this->column_select( $col, $title ) . '</td>';
					}

					echo '</form>
									</tr>';

					// Grab some random rows to display as a sample.
					$row_count = count( $csv->data );
					if ( $row_count < 50 ) {
						foreach ( $csv->data as $csv_row => $csv_row_data ) {
							echo '<tr>';
							foreach ( $csv_row_data as $td => $tdv ) {
								echo '<td>';
								echo esc_attr( $tdv );
								echo '</td>';

							}
							echo '</tr>';
						}
					} else {
						for ( $i = 0; $i <= 50; $i ++ ) {
							$numb = mt_rand( 1, $row_count - 1 );
							echo '<tr>';

							foreach ( $csv->data[ $numb ] as $td => $tdv ) {

								echo '<td>';
								echo esc_attr( $tdv );
								echo '</td>';

							}
							echo '</tr>';
						}
					}
					echo '</table>';

				}
			} else {
				_e( sprintf( 'Please make the following directory <a href="%s">writable by WordPress</a>: %s', 'http://codex.wordpress.org/Changing_File_Permissions#Permission_Scheme_for_WordPress', '<code>' . WP_PLUGIN_DIR . '</code>' ), 'SimpleMap' );
			}
			echo '

						</div>

					</div>

				</div>

			</div>

		</div>

	</div>';
		}

		/**
		 * Select box for columns.
		 *
		 * This function creates a select box of all my data types to assign to this column
		 *
		 * @param $col
		 * @param $title
		 *
		 * @return string
		 */
		public function column_select( $col, $title ) {
			$select = "<select name='col_" . esc_attr( $col ) . "'>";

			$select .= "<option value='-1' >Don't Import</option>";


			foreach ( $this->get_location_data_types() as $type => $label ) {
				$select .= "<option value='" . $type . "' " . selected( trim( $type ), trim( $title ), false ) . ' >' . $label . '</option>';
			}

			$select .= '</select>';

			return $select;
		}

		/**
		 * Prints the options page.
		 *
		 */
		public function print_page() {

			if ( isset( $_POST['sm-action'] ) && 'import-csv' === $_POST['sm-action'] ) {
				$step = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 1;

				// Check for uploaded file with no errors.
				if ( 2 == $step || ( 1 == $step && isset( $_FILES['simplemap-csv-upload'] ) && ! $_FILES['simplemap-csv-upload']['error'] > 0 ) ) {

					switch ( $step ) {
						case 2:
							$this->import_csv();
							break;
						case 1:
						default :
							$this->do_csv_preview();
					}
				}
			} else {
				global $simple_map;
				$options = $simple_map->get_options();

				if ( !isset( $options['api_key'] ) )
					$options['api_key'] = '';

				extract( $options );


				echo '<div class="wrap">';

				// Title.
				$sm_page_title = apply_filters( 'sm-import-export-page-title', 'SimpleMap: Import/Export CSV' );

				// Toolbar.
				$simple_map->show_toolbar( $sm_page_title );

				// Messages.
				if ( isset( $_GET['sm-msg'] ) && '2' == $_GET['sm-msg'] ) {
					echo '<div class="updated fade"><p>' . __( 'Legacy SimpleMap settings deleted.', 'SimpleMap' ) . '</p></div>';
				}

				if ( isset( $_GET['sm-msg'] ) && '3' == $_GET['sm-msg'] ) {
					echo '<div class="error fade"><p>' . __( 'Legacy SimpleMap NOT settings deleted.', 'SimpleMap' ) . '</p></div>';
				}

				echo '
		<div id="dashboard-widgets-wrap" class="clear">

			<div id="dashboard-widgets" class="metabox-holder">

				<div class="postbox-container half-width">

					<div id="normal-sortables" class="meta-box-sortables ui-sortable">

						<div class="postbox">

							<h3>';

				_e( 'Import From File', 'SimpleMap' );


				echo '</h3>

							<div class="inside">

								<h4>';

				_e( 'If your file has fewer than 100 records and does not have latitude/longitude data:', 'SimpleMap' );

				echo '</h4><p>';
				_e( 'Make sure your CSV has a header row that gives the field names (in English). A good example of a header row would be as follows:', 'SimpleMap' );

				echo '</p>

<p>
	<em>';

				_e( 'Name, Address, Address Line 2, City, State/Province, ZIP/Postal Code, Country, Phone, Fax, URL, Category, Tags, Days, Times, Description, Special (1 or 0), Latitude, Longitude', 'SimpleMap' );
				echo '</em></p><p>';
				_e( 'You can import your file with or without quotation marks around each field. However, if any of your fields contain commas, you should enclose your fields in quotation marks. Single ( \' ) or double ( " ) quotation marks will work.', 'SimpleMap' );

				echo '</p>

<h4>';
				_e( 'If your file has more than 100 records:', 'SimpleMap' );
				echo '</h4><p>';
				_e( 'If you have more than 100 records to import, it is best to do one of the following:', 'SimpleMap' );
				echo '</p>
<ul>
	<li>';
				_e( 'Geocode your own data before importing it' );
				echo '</li><li>';
				_e( 'Split your file into multiple files with no more than 100 lines each' );
				echo '</li></ul><p>';

				printf( __( 'Geocoding your own data will allow you to import thousands of records very quickly. If your locations need to be geocoded by SimpleMap, any file with more than 100 records might stall your server. %s Resources for geocoding your own locations can be found here.%s', 'SimpleMap' ), '<a href="http://groups.google.com/group/Google-Maps-API/web/resources-non-google-geocoders" target="_blank">', '</a>' );

				echo '</p>
<form name="import_form" method="post"
      action="';
				echo admin_url( 'admin.php?page=simplemap-import-export' );
				echo '"
      enctype="multipart/form-data" class="inabox">
	<input type="hidden" name="MAX_FILE_SIZE"
	       value="<?php echo( 2 * 1024 * 1024 );';
				echo '"/>
	<input type="hidden" name="sm-action" value="import-csv"/>

	<p><label for="simplemap-csv-upload">';
				_e( 'File to import (maximum size 2MB):', 'SimpleMap' );
				echo '</label><input
			type="file"
			id="simplemap-csv-upload" name="simplemap-csv-upload"/>
		<br/>';


				// Warn them if the simplemap path is not writable.
				if ( ! is_writable( WP_PLUGIN_DIR ) ) {
					echo '<br />' . __( sprintf( 'Please make the following directory <a href="%s">writable by WordPress</a>: %s', 'http://codex.wordpress.org/Changing_File_Permissions#Permission_Scheme_for_WordPress', '<code>' . WP_PLUGIN_DIR . '</code>' ), 'SimpleMap' );
				}


				echo '</p>
	<input type="submit" class="button-primary"
	       value="';
				_e( 'Import CSV File', 'SimpleMap' );
				echo '"/>';
				if ( '' == $options['api_key'] ) : ?>
															   <?php printf( __( "Warning: You still need to enter an <a href='%s'>API key</a> if you need your locations geocoded.", 'SimpleMap' ), admin_url( "admin.php?page=simplemap" ) ); ?>
														   <?php endif;

				echo '</form>

<p>';
				_e( 'Importing a file may take several seconds; please be patient.', 'SimpleMap' );
				echo '</p><div class="clear"></div></div></div>';

				echo '<div class="postbox"><h3>';
				_e( 'Export To File', 'SimpleMap' );
				echo '</h3><div class="inside">

		<form name="export_form" method="post" action="">';
				wp_nonce_field( 'sm_export_csv', 'sm_export_csv_nonce' );
				echo '<input type="submit" class="button-primary" value="';

				_e( 'Export Database to CSV File', 'SimpleMap' );
				echo '"/>

		</form>
		<div class="clear"></div>

	</div> 
</div> 
</div> 
</div> ';

				?>
				<div class='postbox-container half-width'>

				<div id='side-sortables' class='meta-box-sortables ui-sortable'>

				<?php do_action( 'sm-import-export-side-sortables-top' ); ?>

				<!-- #### PREMIUM SUPPORT #### -->

				<div class="postbox">

					<h3 class='blue-bg'><?php _e( 'Premium Support and Features', 'SimpleMap' ); ?></h3>

					<div class="inside">

						<?php
						// Check for premium support status.
						global $simplemap_ps;

						if ( ! url_has_ftps_for_item( $simplemap_ps ) ) : ?>

							<p>Premium support is no longer available for SimpleMap.</p>
							
						<?php else : ?>

							<p class='howto'><?php printf( "Premium support is no longer available for SimpleMap. You will continue to receive support until your current subscription expires on <code>%s</code>.", date( "F d, Y", get_ftps_exp_date( $simplemap_ps ) ) ); ?></p>

							<p><a target="blank" href="https://simplemap-plugin.com/contact/"><?php _e( 'Visit Premium Support web site', 'simplemap' ); ?></a></p>

						<?php endif; ?>

					</div> <!-- inside -->
				</div> <!-- postbox -->

				<?php if ( $simple_map->legacy_tables_exist() ) : ?>

					<div class="postbox">

						<h3><?php _e( 'Legacy Data', 'SimpleMap' ); ?></h3>

						<div class="inside">
							<p class='howto'><?php _e( 'It appears that you have location data stored in legacy SimpleMap tables that existed prior to version 2.0. What would you like to do with that data?', 'SimpleMap' ); ?></p>

							<ul>
								<li>
									<a href='<?php echo admin_url( 'admin.php?page=simplemap-import-export&amp;sm-action=export-legacy-csv' ); ?>'><?php _e( 'Export legacy data as a CSV file', 'SimpleMap' ); ?></a>
								</li>
								<!--<li><a href=''><?php _e( 'Port all legacy data over to custom post types', 'SimpleMap' ); ?></a></li>-->
								<li>
									<a onClick="javascript:return confirm('<?php _e( 'Last chance! Pressing OK will delete all Legacy SimpleMap data.' ); ?>')"
									   href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=simplemap-import-export&sm-action=delete-legacy-simplemap' ), 'delete-legacy-simplemap' ); ?>"><?php _e( 'Permanently delete the legacy data and tables', 'SimpleMap' ); ?></a>
								</li>
							</ul>
						</div>
					</div>

				<?php endif; ?>

				<?php do_action( 'sm-import-export-side-sortables-bottom' );

				echo '

	</div>

</div> <!-- dashboard-widgets -->

<div class="clear">
</div>
</div><!-- dashboard-widgets-wrap -->
</div> <!-- wrap -->';


			}
		}
	}
}

