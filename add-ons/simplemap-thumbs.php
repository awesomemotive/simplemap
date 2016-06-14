<?php
/*
Plugin Name: SFWD SimpleMap Add Thumbnails
Plugin URI: http://semperplugins.com/
Description: Adds support for thumbnails in locations and in the bubble.
Version: 0.2
Author: Semper Fi Web Design
Author URI: http://semperfiwebdesign.com/
*/

add_action( 'plugins_loaded', 'sfwd_example_sm_register_thumbs' );

function sfwd_example_sm_register_thumbs() {
	global $sm_locations;
	if ( class_exists( 'SM_Locations' ) && isset( $sm_locations ) && is_object( $sm_locations ) ) {
		remove_action( 'init', array( &$sm_locations, 'register_locations' ) );
		add_action( 'init', 'sfwd_example_sm_register_locations' );
		add_filter( 'sm-xml-search-locations', 'sfwd_example_sm_search_locations' );
	}
}

function sfwd_example_sm_search_locations( $locations ) {
	if ( !empty( $locations ) ) {
		foreach ( $locations as $key => $value ) {
			$value->thumbnail = '';
			if( has_post_thumbnail( $value->ID ) ) {
				$value->thumbnail = get_the_post_thumbnail( $value->ID, 'thumbnail' );
			}
		}
	}
	return $locations;
}

function sfwd_example_sm_register_locations() {
        global $simple_map, $sm_locations, $wp_rewrite;

        $args = array();
        $options = $simple_map->get_options();
        if ( !empty( $options['enable_permalinks'] ) ) {
			$args += array(
				'publicly_queryable' => true,
				'exclude_from_search' => false,
				'rewrite' => array( 'slug' => $options['permalink_slug'] )
			);
        }

        $args += array(
                'public' => true,
                'publicly_queryable' => false,
                'exclude_from_search' => true,
                'show_ui' => true,
                'capability_type' => 'post',
                'hierarchical' => false,
                'rewrite' => false,
                'query_var' => 'sm-location',
                'register_meta_box_cb' => array( &$sm_locations, 'location_meta_cb' ),
                'supports' => array( 'title', 'editor', 'thumbnail' ),
                'labels' => array(
                        'name' => 'Locations',
                        'singular_name' => 'Location',
                        'add_new_item' => 'Add New Location',
                        'edit_item' => 'Edit Location',
                        'new_item' => 'New Location',
                        'view_item' => 'View Locations',
                        'search_items' => 'Search Locations',
                        'not_found' => 'No Locations found',
                        'not_found_in_trash' => 'No Locations found in trash',
                )
        );

        // Register it
        register_post_type( 'sm-location', $args );
}

add_action( 'sm-load-simplemap-js-bottom', 'sfwd_sm_override_createmarker' );
function sfwd_sm_override_createmarker() { ?>
	createMarker = function ( locationData ) {	

		// Init tax heights
		locationData.taxonomyheights = [];

		// Allow plugin users to define Maker Options (including custom images)
		var markerOptions = {};
		if ( 'function' == typeof window.simplemapCustomMarkers ) {
			markerOptions = simplemapCustomMarkers( locationData );
		}

		// Allow developers to turn of description in bubble. (Return true to hide)
		<?php if ( true === apply_filters( 'sm-hide-bubble-description', false ) ) : ?>
		locationData.description = '';
		<?php endif; ?>

		markerOptions.map = map;
		markerOptions.position = locationData.point;
		var marker = new google.maps.Marker( markerOptions );
		marker.title = locationData.name;
		markersArray.push(marker);

		var mapwidth;
		var mapheight;
		var maxbubblewidth;
		var maxbubbleheight;
		
		mapwidth = document.getElementById("simplemap");
		if ( typeof mapwidth != 'undefined' ) {
			mapwidth = mapwidth.offsetWidth;
		} else {
			if ( typeof map_width != 'undefined' ) {
				mapwidth = Number(stringFilter(map_width));						
			} else {
				mapwidth = 400;
			}
		}
		
		mapheight = document.getElementById("simplemap");
		if ( typeof mapheight != 'undefined' ) {
			mapheight = mapheight.offsetHeight;
		} else {
			if ( typeof map_height != 'undefined' ) {
				mapheight = Number(stringFilter(map_height));						
			} else {
				mapheight = 200;
			}
		}
		maxbubblewidth = Math.round(mapwidth / 1.5);
		maxbubbleheight = Math.round(mapheight / 2.2);
		var fontsize = 12;
		var lineheight = 12;

		if (locationData.taxes.sm_category && locationData.taxes.sm_category != '' ) {
			var titleheight = 3 + Math.floor((locationData.name.length + locationData.taxes.sm_category.length) * fontsize / (maxbubblewidth * 1.5));
		} else {
			var titleheight = 3 + Math.floor((locationData.name.length) * fontsize / (maxbubblewidth * 1.5));
		}

		var addressheight = 2;
		if (locationData.address2 != '') {
			addressheight += 1;
		}
		if (locationData.phone != '' || locationData.fax != '') {
			addressheight += 1;
			if (locationData.phone != '') {
				addressheight += 1;
			}
			if (locationData.fax != '') {
				addressheight += 1;
			}
		}

		for (jstax in locationData.taxes) {
			if ( locationData.taxes[jstax] !== '' ) {
				locationData.taxonomyheights[jstax] = 3 + Math.floor((locationData.taxes[jstax][length]) * fontsize / (maxbubblewidth * 1.5));
			}
		}
		var linksheight = 2;

		var totalheight = titleheight + addressheight;
		for (jstax in locationData.taxes) {
			if ( 'sm_category' != jstax ) {
				totalheight += locationData.taxonomyheights[jstax];
			}
		}
		totalheight = (totalheight + 1) * fontsize;

		if (totalheight > maxbubbleheight) {
			totalheight = maxbubbleheight;
		}

		var html = '<div class="markertext" style="height: ' + totalheight + 'px; overflow-y: auto; overflow-x: hidden;">';
		if ( locationData.thumbnail != null && locationData.thumbnail != '' ) {
			html += '<div class="icon" style="float:left;padding-right:5px;">' + locationData.thumbnail + '</div>';
		}
		html += '<h3 style="margin-top: 0; padding-top: 0; border-top: none;">';

		if ( '' != locationData.permalink ) {
			html += '<a href="' + locationData.permalink + '">';
		}
		html += locationData.name;

		if ( '' != locationData.permalink ) {
			html += '</a>';
		}

		if (locationData.taxes.sm_category && locationData.taxes.sm_category != null && locationData.taxes.sm_category != '' ) {
			html += '<br /><span class="bubble_category">' + locationData.taxes.sm_category + '</span>';
		}

		html += '</h3>';

		html += '<p class="buble_address">' + locationData.address;
		if (locationData.address2 != '') {
			html += '<br />' + locationData.address2;
		}
		
		// Address Data
		if (address_format == 'town, province postalcode') {
			html += '<br />' + locationData.city + ', ' + locationData.state + ' ' + locationData.zip + '</p>';
		} else if (address_format == 'town province postalcode') {
			html += '<br />' + locationData.city + ' ' + locationData.state + ' ' + locationData.zip + '</p>';
		} else if (address_format == 'town-province postalcode') {
			html += '<br />' + locationData.city + '-' + locationData.state + ' ' + locationData.zip + '</p>';
		} else if (address_format == 'postalcode town-province') {
			html += '<br />' + locationData.zip + ' ' + locationData.city + '-' + locationData.state + '</p>';
		} else if (address_format == 'postalcode town, province') {
			html += '<br />' + locationData.zip + ' ' + locationData.city + ', ' + locationData.state + '</p>';
		} else if (address_format == 'postalcode town') {
			html += '<br />' + locationData.zip + ' ' + locationData.city + '</p>';
		} else if (address_format == 'town postalcode') {
			html += '<br />' + locationData.city + ' ' + locationData.zip + '</p>';
		}

		// Phone and Fax Data
		if (locationData.phone != null && locationData.phone != '') {
			html += '<p class="bubble_contact"><span class="bubble_phone">' + phone_text + ': ' + locationData.phone + '</span>';
			if (locationData.email != null && locationData.email != '') {
				html += '<br />' + email_text + ': <a class="bubble_email" href="mailto:' + locationData.email + '">' + locationData.email + '</a>';
			}
			if (locationData.fax != null && locationData.fax != '') {
				html += '<br /><span class="bubble_fax">' + fax_text + ': ' + locationData.fax + '</span>';
			}
			html += '</p>';
		} else if (locationData.fax != null && locationData.fax != '') {
			html += '<p>' + fax_text + ': ' + locationData.fax + '</p>';
		}
						
		html += '<p class="bubble_tags">';
		
		for (jstax in locationData.taxes) {
			if ( 'sm_category' == jstax ) {
				continue;
			}
			if ( locationData.taxes[jstax] != null && locationData.taxes[jstax] != '' ) {
				html += taxonomy_text[jstax] + ': ' + locationData.taxes[jstax] + '<br />';
			}
		}
		html += '</p>';

			var dir_address = locationData.point.toUrlValue(10);
			var dir_address2 = '';
			if (locationData.address) { dir_address2 += locationData.address; }
			if (locationData.city) { if ( '' != dir_address2 ) { dir_address2 += ' '; } dir_address2 += locationData.city; };
			if (locationData.state) { if ( '' != dir_address2 ) { dir_address2 += ' '; } dir_address2 += locationData.state; };
			if (locationData.zip) { if ( '' != dir_address2 ) { dir_address2 += ' '; } dir_address2 += locationData.zip; };
			if (locationData.country) { if ( '' != dir_address2 ) { dir_address2 += ' '; } dir_address2 += locationData.country; };

			if ( '' != dir_address2 ) { dir_address = locationData.point.toUrlValue(10) + '(' + escape( dir_address2 ) + ')'; };
						
		html += '		<p class="bubble_links"><a class="bubble_directions" href="http://google' + default_domain + '/maps?saddr=' + locationData.homeAddress + '&daddr=' + dir_address + '" target="_blank">' + get_directions_text + '</a>';
						if (locationData.url != '') {
		html += '			<span class="bubble_website">&nbsp;|&nbsp;<a href="' + locationData.url + '" title="' + locationData.name + '" target="_blank">' + visit_website_text + '</a></span>';
						}
		html += '		</p>';

		if (locationData.description != '' && locationData.description != null) {
			var numlines = Math.ceil(locationData.description.length / 40);
			var newlines = locationData.description.split('<br />').length - 1;
			var totalheight2 = 0;

			if ( locationData.description.indexOf('<img') == -1) {
				totalheight2 = (numlines + newlines + 1) * fontsize;
			}
			else {
				var numberindex = locationData.description.indexOf('height=') + 8;
				var numberend = locationData.description.indexOf('"', numberindex);
				var imageheight = Number(locationData.description.substring(numberindex, numberend));

				totalheight2 = ((numlines + newlines - 2) * fontsize) + imageheight;
			}

			if (totalheight2 > maxbubbleheight) {
				totalheight2 = maxbubbleheight;
			}

			//marker.openInfoWindowTabsHtml([new GInfoWindowTab(location_tab_text, html), new GInfoWindowTab(description_tab_text, html2)], {maxWidth: maxbubblewidth});
			// tabs aren't possible with the Google Maps api v3
			html += '<hr /><p>' + locationData.description + '</p>';
		}

		html += '	</div>';

		google.maps.event.addListener(marker, 'click', function() {
			clearInfoWindows();
			var infowidth = 0;
			if ( maxbubblewidth <= 100 ) {
				infowidth = document.getElementById("simplemap");
				if ( typeof infowidth != 'undefined' ) {
					infowidth = infowidth.offsetWidth;
				} else {
					infowidth = 400;
				}
			    infowidth = infowidth * (maxbubblewidth / 100.0);
			}
			if ( infowidth < maxbubblewidth ) infowidth = maxbubblewidth;
			infowidth = parseInt(infowidth) + 'px';
			var infowindow = new google.maps.InfoWindow({
				maxWidth: infowidth,
				content: html
			});				
			infowindow.open(map, marker);
			infowindowsArray.push(infowindow);
			window.location = '#map_top';
		});

		return marker;
	}
<?php
}
