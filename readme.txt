=== SimpleMap Store Locator ===

Contributors: hallsofmontezuma, fullthrottledevelopment
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=mrtorbert%40gmail%2ecom&item_name=SimpleMap&item_number=Support%20Open%20Source&no_shipping=0&no_note=1&tax=0&currency_code=USD&lc=US&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: map, maps, store locator, database, locations, stores, Google maps, locator
Requires at least: 3.0
Tested up to: 4.6
Stable tag: 2.4.10

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

* A free Google Maps API key
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

Please open a ticket on [the SimpleMap project's repo on Github](https://github.com/semperfiwebdesign/simplemap/issues) if you have a bug to report. Otherwise, you may access premium support inside the plugin dashboard.

== Changelog ==

SimpleMap [Changelog](http://simplemap-plugin.com/changelog/)

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
