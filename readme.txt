=== Spreadplugin ===
Contributors: Thimo Grauerholz
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=EZLKTKW8UR6PQ
Tags: spreadshirt,wordpress,plugin,shop
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: 1.0.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin uses the Spreadshirt API to display all articles. Add the articles to the Spreadshirt Basket with a single click.

== Description ==

A small plugin for Wordpress which uses the Spreadshirt API.
Just with a shortcode you can list all your products on one page and add them to the Spreadshirt basket. 
The pagination is done via infinity scroll.

Features:

* Infinity Scrolling
* uses Spreadshirt-Basket (A click on the basket opens the spreadshirt own basket)
* Price listing
* Choose color and sizes
* Language support (de_DE, en_GB based on Wordpress installation)

See it in action:
http://www.pr3ss-play.de/shop-en/ (in german)

What do you need:

* Wordpress most recent Version
* Spreadshirt shop
* Spreadshirt API key and secret (US/NA From: https://www.spreadshirt.com/-C6840, EU From: https://www.spreadshirt.de/-C7120)

== Installation ==

1. Upload the spreadplugin directory to the `/wp-content/plugins/spreadplugin` Directory (if not exists please create) or install using wordpress plugin installer
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Create a new site or edit an existing site
4. Insert shortcode

**US/NA**
`[spreadplugin shop_id="414192" shop_limit="20" shop_locale="" shop_source="com" shop_api="" shop_secret=""]`

**EU/DE**
`[spreadplugin shop_id="732552" shop_limit="20" shop_locale="de_DE" shop_source="net" shop_api="" shop_secret=""]`

5. Insert Shop ID, Shop API (Spreadshirt API Key) and Shop secret (Spreadshirt Secret)
6. Done (you may modify the layout using the separate css file in the spreadplugin Folder)

== Frequently asked questions ==

none

== Screenshots ==

1. The Output

