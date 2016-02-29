=== SimpleMap Store Locator ===

Contributors: hallsofmontezuma, fullthrottledevelopment
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=DTJBYXGQFSW64
Tags: map, maps, store locator, database, locations, stores, Google maps, locator
Requires at least: 2.8
Tested up to: 4.4
Stable tag: 2.4.8

SimpleMap is an easy-to-use international store locator plugin that uses Google Maps to display information directly on your WordPress site.

== Description ==

SimpleMap is a *powerful* and *easy-to-use* international store locator plugin. It has an intuitive interface and is completely customizable. Its search features make it easy for your users to find your locations quickly.

Please note: SimpleMap has some compatibility problems with WordPress MU.

Key features include:

* Manage locations from any country supported by Google Maps
* Manage an unlimited number of locations
* Put a Google Map on any page or post that gives instant results to users
* Users can enter a street address, city, state, or zip to search the database
* Customize the appearance of the map and results with your own themes
* Use a familiar interface that fits seamlessly into the WordPress admin area
* Import and export your database as a CSV file
* Quick Edit function allows for real-time updates to the location database
* Make certain locations stand out with a customizable tag (Most Popular, Ten-Year Member, etc.)
* Easy-to-use settings page means you don't need to know any code to customize your map

See the screenshots for examples of the plugin in action.

With SimpleMap, you can easily put a store locator on your WordPress site in seconds. Its intuitive interface makes it the easiest plugin of its kind to use, and the clean design means you'll have a classy store locator that fits in perfectly with any WordPress theme.

== Installation ==

1. Upload the entire `simplemap` folder to your `/wp-content/plugins/` folder.
2. Go to the 'Plugins' page in the menu and activate the plugin.
3. Type `[simplemap]` into any Post or Page you want SimpleMap to be displayed in.
4. Enter some locations in the database and start enjoying the plugin!

== Screenshots ==

1. Example of the map and results
2. Location with an image tag in the description
3. Location with HTML formatting in the description
4. General Options page
5. Managing the database

== Frequently Asked Questions ==

= What are the minimum requirements for SimpleMap? =

You must have:

* WordPress 2.8 or later
* PHP 5 (or PHP 4 with the SimpleXML extension loaded), DOMDocument class

= How do I put SimpleMap on my website? =

Simply insert the following shortcode into any page or post: `[simplemap]`

= I've put in the shortcode, but my map isn't showing up. Why? =

If the search form is showing up, but the map is blank, it's probably a Javascript error. Check to see if any other plugins are throwing Javascript errors before the SimpleMap Javascript gets loaded.

= What is the "Special Location Label"? =

This is meant to flag certain locations with a specific label. It shows up in the search results with a gold star next to it. Originally this was developed for an organization that wanted to highlight people that had been members for more than ten years. It could be used for something like that, or for "Favorite Spots," or "Free Wi-Fi," or anything you want. You can also leave it blank to disable it.

= Why can't my map load more than 100 search results at a time? =

On most browsers, loading more than 100 locations at once will really slow things down. In some cases, such as a slower internet connection, it can crash the browser completely. I put that limit on there to prevent that from happening.

= Can I suggest a feature for SimpleMap? =

Of course! Visit [the SimpleMap home page](http://simplemap-plugin.com/) to do so.

= What if I have a problem with SimpleMap, or find a bug? =

Please visit [the SimpleMap forums at WordPress.org](http://wordpress.org/tags/simplemap?forum_id=10) if you have a bug to report. Otherwise, you may access premium support inside the plugin dashboard.

== Changelog ==

= 2.4.6 =
* Bugfix release for Google API changes
* Fixed display of marker bubble

= 2.4.5 =
* Compatibility fixes for WordPress 3.5
= 2.4.4 =
* Added filters to menu item permissions
* Don't display map updating image if map is hidden
* Show error if over Google API limit
* Added ability to to show description in search results with a filter
* Fixed typo with call to wp_get_current_user
* Fixed bug that prevented locations less than 1KM from being returned
* Passing current WordPress post/page ID to search script
* Fixed bug that created memory errors when large amount of locations were deleted at once
* Fixed bug that created memory errors on medium size DBs during export. Still need to refactor for large exports
* Change locations from hierarchical to non-hierarchical to avoid poor WP query
* Fixed error that prevented permalink map from rendering in IE

= 2.4.3 =
* Added filter to allow devs to remove loading image
* Added filter to allow devs to change sort order
* Added language options for Google Maps API call to allow for additional localization
* Changed the name of the CSV class to prevent conflicts with other plugins
* Removed some random characters appearing in location pages
* Change HTTP/1.1 200 OK header to Status: 200 OK', false, 200
* Added ability to hide bubble description with filter
* Switch paramater order in call to maps.googleapis.com that was causing errors
* Fixed permalink error
* Added auto-locate options to experimental features

= 2.4.2 =
* Changed XML in search class to json
* Fix broken taxonomy filters
* Fix error caused when deleting locations where no categories exist
* Changed query params to get directions links to reflect Googles changes
* Fixed display bug associated with "Now Loading" image when small maps are used.
* Forced 200 status in header of xml-search.php script

= 2.4.1 =
* Revamped the way we pass locaitonData and searchData back and forth between JS functions (placed in objects)
* Made custom JS function for Custom Markers future proof (this is not backwards compatible but will be from here out)
* Fixed errors in options-general.php
* Fixed bad tabbing that snuck into the code
* Fixed autoload bug introduced in 2.4
* Fixed taxonomy shortcodes.


= 2.4 =
* Added ability to turn on location permalinks
* Google Maps API version 3
* Streetview
* Options to activate permalinks for locations
* Foundation for templating system
* Added ability to delete all locations but to preserve settings
* Added cancel link to second stage of import process
* Added code to remove a hook added by the AddThis plugin that breaks results
* Added map_type and zoom_level to shortcode options
* Relax permissions on SM menu items
* Applied label filters for categories in widget (to match search form)
* Allow custom taxonomies to be integrated into the SimpleMap API and attached to the sm-location custom post type
* Allow for custom taxonomies to be imported and created on the fly
* Added ability to span table cells via shortcode
* Added markers to titles
* Added autoload shortcode
* Fixed error where not all categories are imported in some instances
* Don’t autozoom search results on initial page load
* Changed location of temp XML upload file and added the blog id to file handle for ** * MultiSite configurations
* Added missing filters for search form text
* Only pass non-empty post_content through 'the_content' filter in case submit button is not included, attached an invisible submit button so that hitting Enter in the address fields still works
* Reorganized column counting function and added in safety limitations to avoid all rows from being combined when the column count doesn't exactly match
* W3C and performance enhancements
* Only save options when they have changed
* Fixed the lat / lng and zoom settings on a permalink location map
* Fixed error causing 404 for location permalinks after initial activation
* Added a pin to indicate search lat/lng location as well as additional pin colors that can be set with a filter
* Fixed error message appearing in some instances of deleting locations from General Options screen
* Import / Export modification to make sure we convert old CSV values to new ones.
* Added region to Google Maps API call to fix default google domain
* Added some additional CSS classes to map bubbles and search results divs
* Fixed some JS errors that broke search results in some cases
* Fixed issue that prevented adsense from showing in some instances
* Template class refining
* Enabled custom markers for permalink pages
* Made taxonomy information available on permalink page
* Fix autoload shortcode argument
* Added titles to markers
* Additional actions, filters, whitespace formatting, and notifications

= 2.3.4 =
* Refactored XML Search query. Very large speed improvments to search
* Fixed bug returning false negatives with low limit + taxonomy combo on search
* Added South Africa Google domain
* Made Google Domains and Country names array filterable
* Revamped functions that identify plugin path and plugin URL to work with SSL and non-standard setups
* Minor CSS changes
* Replace calls to get_option( 'site_url' ) with site_url()
* Removed debugging code at top of XML-Search.php that was lowering PHP memory_limit
* Fixed limit shortcode so that it works again

= 2.3.3 =
* Add a fallback that geocodes new locations via JS when PHP doesn't work because of 620 status from Google
* Added actions to the load_simplemap JS function
* Added ability to use a select box for taxonomies in the search form (rather than checkboxes)
* Fixed bug that allowed results to show up in search
* Fixed bug preventing drag and drop location from updating address in some locations

= 2.3.2 =
* Add a fallback XML generator for servers without DOMDocument Installed
* Added ids to search form tr and td elements to allow manipulation via CSS / JS
* Refactored parts of XML search script for optimization
* More hooks
* Forced JS header as 200. Some hosts were reporting status for PHP as JS to be 400.
* Added WP_IMPORTING flag while importing locations.
* Fixed some HTML Validation issues in the Search form (have one more to root out)

= 2.3.1 =
* Added additional filters to allow modifying search form labels without use of .pot file.
* Removed stray testing code in simplemap.php

= 2.3 =
* Revamped the search form making it extremly flexible via shortcode arguments (see KB article on premium support site)
* Reformulated the main search query to trim search results to 1/10 the time of that introduced in 2.0 (more work still to be done here)
* Added multiple filters that allow for customization of text labels / tabs / visual elements in the map and search results
* Added more than a dozen new shortcode arguments for overriding General Settings on a per map instance (see KB article on premium support site)
* Added 2 new optional taxonomies (Days and Times)
* Added optional ability to add Google Adwords for maps overlay to map
* Removed caching system introduced in 2.2.1 as it was failing for large DBs
* Fixed country code problem in General Settings
* Fixed bug that prevented default zoom level from working
* Fixed bug that prevented auto load all locations in some setups
* Limited total number of auto load locations. It proved impractical to load them all if you had a large DB
* Added instructional descriptions to General Settings that proved confusing to users
* Fixed bug preventing radius from working in some setups

= 2.2.1 =
* Added 'Loading' message to map when doing search
* Added caching system to speed up repeated searches on systems with large amounts of locations
* Added filters to compensate for WP Bug when more than 10k locations exist
* Fixed bug that prevented locations from appearing when searched from widget and search form is hidden
* Fixed bug that prevented categories and tags from being imported correctly on some systems
* Fixed bug that prevented 'No Limit' option for number of results shown to be set.
* Fixed autoload bug introduced in 2.2
* Fixed bug that prevented changing of Default Country in options

= 2.2 =
* Added ability to customize map markers without hacking plugin files
* Added ability to filter locations by meeting day and time
* Added search widget
* Added shortcode argument for custom search form title
* Fixed errors that broke distance reporting
* Fixed errors that locked up the edit screen with large number of locations
* Fixed error that created bad XML link when no search results were found
* Added several warning / error messages to UI
* Fixed error that prevented custom style sheets from loading properly

= 2.1 =
* Added shortcode args to hide map
* Added shortcode args to hide list of results
* Added shortcode args to hide entire search box
* Added shortcode args to override default lat / lng for individual maps

= 2.0 =
* First major overhaul since FullThrottle took over development
* Custom Post Types and Taxonomies for Locations, Location Categories, and Location Tags.
* All scripts and styles are now enqueued to prevent conflicts
* Custom MySQL queries have been reduced to 1. The rest have been replaced by WordPress API functions.
* Overhauled search form allows more control
* Additional shortag attributes allows more search form flexibility
* Drag and Drop map available on New / Edit Location screen to fine tune placement of location
* Autoload of locations on by default.
* Multiple bug fixes covered in switch to custom post types and WordPress DB API (functions and $wpdb)
* Several hooks and filters added to code (more to come in the future)
* Option added to General Options screen that allows complete deletion of all SimpleMap data
* Revamped Import / Export process. Import process now allows fine tuning of column data
* Ability to export legacy ( prior to version 2.0 ) data into CSV
* Ability to completely remove legacy database tables and data
* Ability to create categories / tags on the fly during CSV import

= 1.2.2 =
* Modified URL references to admin pages to fix 'Do not have permissions' errors that occurred in WP 3.0
* Modified the way existing form values are populated in the admin pages
* Plugin development was taken over by [FullThrottle Development](http://fullthrottledevelopment.com/)

= 1.2.1 =
* Moved SimpleMap out of beta status and into a stable release

= 1.2b4 =
* Cleaned up the scripts loaded in the head section of the page.
* Added ability to specify page IDs on which to load the map scripts.
* Separated CSS from Javascript in the head section to hopefully make it more compatible with the "Javascript to the Bottom" plugin.

= 1.2b3 =
* Fixed error where database table was not being created on install/activation of plugin
* Text search works on name, description, and tags (but not on categories yet - still in progress)
* Changing a location's address in Quick Edit forces you to re-geocode before saving the changes
* Added ability to specify an address format (order of fields in the City line)
* Page now scrolls to map when you click on a location in the results list

= 1.2b2 =
* "Get Directions" link now uses custom Google Maps country domain (as set in General Options)
* Added translation support for a few strings that were missing it (including "Fax" and "Tags")
* Fixed the bubble-height problem (hopefully for good this time)
* Added ability to re-geocode address when changing the database with Quick Edit
* Info bubble correctly displays category name instead of ID number
* Removed "get computed style" function, which should eliminate that error in any browsers

= 1.2b1 =
* Added tags
* Multiple maps (on different posts/pages only)
* Maps with specific categories
* Searching by name or keyword now searches the Name, Description, Category, and Tags fields
* Improved CSV support
* Added ability to choose Google Maps domain (.com, .co.uk, etc.)

= 1.1.4 =
* Added Help page to de-clutter the other admin pages, and allow for more thorough explanations of features
* Improved CSV import: Now includes an option to quickly import large files if the locations are already geocoded
* Improved latitude/longitude handling: The values are now directly editable on the Manage Database page, and any new location (added on the Add Location page OR via CSV import) that has latitude/longitude already set will keep those values intact
* Improved Auto-Load: There is now an option to auto-load all locations in the database, and it automatically disables itself if there are more than 100 locations (to prevent crashing browsers)
* Improved Auto-Load: There is now an option to lock the auto-load to your default location, instead of the map centering itself on the loaded locations
* Improved map display: Numerous CSS fixes added to ensure that map overlays display properly
* Improved state/province: Is now a text field instead of a drop-down list, so any value can be entered
* Added ability to sort the database by column on the Manage Database page
* Added ability to change the number of search results shown (was previously limited to 20)
* Fixed bug: Info bubble should now expand properly when a location's name/category takes up more than one line
* Added questions to FAQ in Readme file

= 1.1.3 =
* Fixed bug: "Get Directions" link in location's info bubble no longer inserts "null" in the destination address
* Fixed bug: Single quotes in a location's name and address are no longer preceded by a backslash in the results display

= 1.1.2 =
* Fixed bug: Javascript code was breaking map functionality and the Geocode Address button in the General Options (Internet Explorer/Firefox only)

= 1.1.1 =
* Support for Cyrillic characters
* Autoloading locations now respects the default zoom level set in General Options
* Link to sign up for Google Maps API key will link to a translated signup page (English, Spanish, German, Japanese, Korean, Portugese, Russian, and Chinese)
* Fixed bug causing error when adding a location before any categories had been created
* Fixed bug causing the default country to reset to "United States"
* Fixed bug regarding duplicate function names; all function names are now unique to the plugin

= 1.1 =
* New Feature: Support for international locations
* New Feature: HTML descriptions for locations
* New Feature: Custom categories for locations
* New Feature: Choose to show or hide the search box with the map
* New Feature: Geocode your default location right in the Admin section
* Simplified Autoload feature: automatically loads at the default location
* Improved Admin interface
* Slightly improved CSV compatibility (in regards to quotation marks)
* Fixed bug causing map overlays to display strangely when a background color was applied to images in that WordPress theme

= 1.0.6 =
* Fixed bug that was causing map to always appear at the top of a page or post
* Modified CSV importing function to be more flexible about quotation marks
* Modified paging in the Manage Database screen to better accomodate a large number of pages
* Added button to Manage Database screen to clear entire database
* Added German & Spanish translations
* Fixed some tiny cosmetic errors
* Removed some redundant code in the Manage Database screen

= 1.0.5 =
* Changed required WordPress version to 2.8

= 1.0.4 =
* Added support for localization
* Fixed bug causing fatal error when trying to activate the plugin

= 1.0.3 =
* Added optional "Powered by SimpleMap" link to map display

= 1.0.2 =
* Fixed bug that was showing ".svn" in the drop-down list of themes
* Added the ability to automatically load the map results from a given location
* Added the ability to change the default search radius
* Added support for both miles and kilometers
* Fixed invalid markup in search form
* Fixed invalid markup in Google Maps script call
* Fixed bug appearing on certain servers when trying to access remote file created by Google Maps

= 1.0.1 =
* Fixed a folder structure problem where an auto-install from within WordPress would give a missing header error.

= 1.0 =
* Initial release

== Making Your Own Theme ==

To upload your own SimpleMap theme, make a directory in your `plugins` folder called `simplemap-styles`. Upload your theme's CSS file here.

To give it a name that shows up in the **Theme** drop-down menu (instead of the filename), use the following markup at the beginning of the CSS file:

`/*
Theme Name: YOUR THEME NAME HERE
*/`

== Other Notes ==

Planned for future releases:

* UI for custom markers
* Show map of single location
* Search by Day / Time (great for groups)
* Search by Date (great for traveling gigs / performances)

To suggest any new features, please visit [the SimpleMap home page](http://simplemap-plugin.com/) and leave a comment.

== Credits ==

= Code and Inspiration =

* [Alison Barrett](http://alisothegeek.com/) Original developer and maintainer until June, 2010.

= Translations =

* German: Thorsten at [.numinose](http://www.numinose.com)
* Spanish: Fernando at [Dixit](http://www.dixit.es)
* Portugese (Brazil): Rodolfo Rodrigues at [ChEngineer Place](http://chengineer.com/)
* Dutch: Jan-Albert Droppers at [Droppers.NL](http://droppers.nl)

If you want to help with any translation for this plugin, please don't hesitate to contact us. Any help translating is greatly appreciated! The updated `.POT` file is always included in every release, in the `lang` folder.

== License ==

SimpleMap - the easy store locator for WordPress.
Copyright (C) 2010 FullThrottle Development, LLC.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
