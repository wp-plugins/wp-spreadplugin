=== WP-Spreadplugin ===
Contributors: Thimo Grauerholz
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=EZLKTKW8UR6PQ
Tags: spreadshirt,wordpress,plugin,shop
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: 1.2.7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin uses the Spreadshirt API to display all articles. Add the articles to the Spreadshirt Basket with a single click.

== Description ==

A small plugin for Wordpress which uses the Spreadshirt API.
Just with a shortcode you can list all your products on one page and add them to the Spreadshirt basket. 
The pagination is done via infinity scroll.

Features:

* Infinity Scrolling
* Uses Spreadshirt-Basket (A click on the basket opens the spreadshirt own basket)
* Price listing
* Choose color and sizes
* Language support (de_DE, en_GB, fr_FR based on Wordpress installation)
* Social buttons
* Zoomed article preview

See it in action:
http://www.pr3ss-play.de/shop/ (in german)

What do you need:

* Wordpress most recent Version
* Spreadshirt shop
* Spreadshirt API key and secret (US/NA From: https://www.spreadshirt.com/-C6840, EU From: https://www.spreadshirt.de/-C7120)

Feel free to contact me.

== Installation ==

1. Upload the spreadplugin directory to the `/wp-content/plugins/wp-spreadplugin` Directory (if not exists please create) or install using wordpress plugin installer
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Create a new site or edit an existing site
4. Insert shortcode

**US/NA**
`[spreadplugin shop_id="414192" shop_limit="20" shop_locale="" shop_source="com" shop_api="" shop_secret=""]`

**EU/DE/FR**
`[spreadplugin shop_id="732552" shop_limit="20" shop_locale="de_DE" shop_source="net" shop_api="" shop_secret=""]`

5. Insert Shop ID, Shop API (Spreadshirt API Key) and Shop secret (Spreadshirt Secret)
6. Done (you may modify the layout using the separate css file in the spreadplugin Folder)

== Frequently asked questions ==

= It shows "No articles in Shop", but there are articles in the Spreadshirt-Shop =

Check if your Spreadshirt Shop has a language and country setting. If you haven't set one, please remove `xx_XX` from the `shop_locale="xx_XX"` value.

= How can I reduce the space between color selection and price? =

I added a spacer div to give you the ability to change the space/appearance.
You can reduce the space by editing the style css in following folder: `/wp-content/plugins/wp-spreadplugin/css/spreadplugin.css` on line 82. 
Change padding-top and padding-bottom to a value you like.

= I want to use a different currency. Is this possible? =

The currency bases on the currency you're using for your Spreadshirt Shop. So if you want to use DKK instead of EUR, change the currency in the Shop options of your Spreadshirt Shop. But be sure to not have any products in your shop, because Spreadshirt doesn't allow changing it, when you've already products in it. (The solution would be to create a new shop)

= I just want to display one price. What to do? =

Please have look at the file `/wp-content/plugins/wp-spreadplugin/css/spreadplugin.css` and search for `.spreadshirt-article #price-`.
Depending on what kind of price you want to hide uncomment the line.

== Screenshots ==

1. The Output

== Changelog ==

= 1.2.7.3 =
* Compatibility update

= 1.2.7.2 =
* Facebook drives me crazy!

= 1.2.7.1 =
* CSS fixes

= 1.2.7 =
* I missed the multi language features of Twitter and Facebook, so sorry!

= 1.2.6 =
* Added Twitter share button. It pushes description text if available, else it just says 'Product'. Additionally, it says @URL to product. 

= 1.2.5.1 =
* BUGFIX

= 1.2.5 =
* Added Facebook like button

= 1.2.4 =
* HTML Bugfixes

= 1.2.2 =
* Enabled some error messages

= 1.2 =
* Spreadshirts "Free Color Selection"/Color limitation is now processed

= 1.1.3 =
* Show hide prices using stylesheet
* French language improvements

= 1.1 =
* jQuery compatibility improvements
* Currency display fix

= 1.0 =
