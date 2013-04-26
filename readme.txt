=== WP-Spreadplugin ===
Contributors: Thimo Grauerholz
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=EZLKTKW8UR6PQ
Tags: spreadshirt,wordpress,plugin,shop
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: 1.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin uses the Spreadshirt API to list articles and let your customers order articles of your Spreadshirt shop using Spreadshirt order process.

== Description ==

This is a small Wordpress plugin for displaying the articles of your Spreadshirt-Shop in Wordpress. The plugin uses the Spreadshirt API to display and don't uses iframes!
If you add an article to the cart and then click on the cart link, you will be redirected to the usual Spreadshirt basket. The whole payment and order process is handeled by Spreadshirt.

Using the plugin is quite easy!
You only need to add the shortcode to your new or existing page and you're done! 

The pagination is done via infinity scroll.

Features:

* Infinity Scrolling
* Uses Spreadshirt-Basket (A click on the basket opens the spreadshirt own basket)
* Price listing
* Choose color and sizes
* Language support (de_DE, en_GB, fr_FR, nl_NL based on Wordpress installation)
* Social buttons
* Zoomed article preview
* Display article descriptions
* Cache
* Choose or direct link to the product category
* Sorting and filtering
* Ajax driven shop (Add products to the basket without reloading the whole content)

See it in action:
http://www.pr3ss-play.de/shop/ (in german)

What do you need:

* Wordpress most recent Version
* Spreadshirt shop
* Spreadshirt API key and secret ( US/NA From: https://www.spreadshirt.com/-C6840 , EU From: https://www.spreadshirt.de/-C7120 )

Feel free to contact me.

== Installation ==

1. Upload the spreadplugin directory to the `/wp-content/plugins/wp-spreadplugin` Directory (if not exists please create) or install using wordpress plugin installer
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Create a new site or edit an existing site
4. Insert shortcode

**US/NA**
`[spreadplugin shop_id="414192" shop_limit="20" shop_locale="" shop_source="com" shop_category="" shop_social="1" shop_enablelink="1" shop_productcategory="" shop_checkoutiframe="0" shop_sortby="" shop_api="" shop_secret=""]`

**EU/DE/FR**
`[spreadplugin shop_id="732552" shop_limit="20" shop_locale="de_DE" shop_source="net" shop_category="" shop_social="1" shop_enablelink="1" shop_productcategory="" shop_checkoutiframe="0" shop_sortby="" shop_api="" shop_secret=""]`

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

= How do I get the category Id? =

1. You must have already created a category in your spreadshirt shop, if not, please do so, now. Please refer to spreadshirt, if you don't know how.
2. Please open your normal spreadshirt shop (shopid.spreadshirt.net). In my case: http://pr3ss-play.spreadshirt.de/
3. Choose a category from the category selection.
4. The url of your shop has changed to `http://pr3ss-play.spreadshirt.de/winter-C269237` in my case
5. The category Id in this case is `269237` (the numbers after the "-C" part)
6. Paste this number in the `shop_category=""` variable.
7. Save and done.

= How to disable the social buttons? =

Add or change in the [spreadplugin] code the value from `shop_social="1"` to `shop_social="0"`

= How to disable the product links? =

Add or change in the [spreadplugin] code the value from `shop_enablelink="1"` to `shop_enablelink="0"`

= How to change the link targets? =

Add or change in the [spreadplugin] code the value from `shop_linktarget=""` to shop_linktarget="YOUR_IFRAME_NAME"` or whatever it is

= I want my checkout page display in an iframe, what to do? =

Add or change in the [spreadplugin] code the value from `shop_checkoutiframe="0"` to `shop_checkoutiframe="1"`. Note: shop_linktarget will be ignored.

= How to default sort? =

Add or change in the [spreadplugin] code the value from `shop_sortby=""` to `shop_sortby="name"`. Available sort options are name, price, recent

= I get following error, when I add an article to the basket: "ERROR: Basket not ready yet." =

Please update to the most recent version

== Screenshots ==

1. The Output 1
2. Screenshot 2
3. Screenshot 3

== Changelog ==

= 1.9 =
* Ajax driven shop (Add products to the basket without reloading the whole content)
* Internal article cache extended to 2 hours. If you want to change, have a look at row 46 in spreadplugin.php and change the value.

= 1.8.4 =
Price formatting added

= 1.8.3b =
Finally fixed "ERROR: Basket not ready".

= 1.8.3a =
Some CSS things

= 1.8.3 =
Compatibility update for using with 'Simple Facebook Connect'-Plugin

= 1.8.2a =
Dutch language added (nl_NL)

= 1.8.2 =
Compatibility update for < PHP 5

= 1.8.1 =
Translation for sorting added

= 1.8 =
* Added a new shortcode variable to sort the articles by default. To enable change shop_sortby=""` to shop_sortby="[name, price, recent]"`.
* Sorting select box added

= 1.7.4b =
Bugfix

= 1.7.4a =
Bugfix

= 1.7.4 =
* Custom url structures now possible

= 1.7.3 =
* Added a new shortcode variable to open the checkout window in an iframe. To enable change shop_checkoutiframe="0"` to shop_checkoutiframe="1"`.
* Bugfix

= 1.7.2 =
Bugfix

= 1.7.1 =
* Added a new shortcode variable to change the link targets. To enable change `shop_linktarget=""` to shop_linktarget="YOUR_IFRAME_NAME"`.
* You may hide the product category field by adding a style in the plugin css. E.g. .spreadshirt-items #productCategory {display:none}

= 1.7 =
* Own cache added (updates every 8 hours) - speed improvements.
* Product category now accessable
* Shortcode added for direct calls of a category. Add ` shop_productcategory=""` and fill with field value e.g. Women => ` shop_productcategory="Women"`

= 1.6.5 =
* Debugging things, no need to update

= 1.6.4 =
* Each article image has now a link to it's spreadshirt product details website. Use the shortcode to enable ` shop_enablelink="1"` or disable `shop_enablelink="0"` this behaviour (default is enabled).

= 1.6.3 =
* Version push

= 1.6.2 =
* Enabled some error messages

= 1.6.1 =
* Added a new shortcode variable to disable social media buttons. Enable ` shop_social="1"` / Disable `shop_social="0"`

= 1.6 =
* Define a category to display with `shop_category=""`. Please have a look at the faq for getting the category id. In v2 I hope to have an admin interface which will help you with the configuration.

= 1.5 =
* Shows detailed product description when hovering the article image (mouseover)

= 1.4.2 =
* Zoom image now shows right color of views

= 1.4.1 =
* Size of views increased

= 1.4 =
* Different views of the article available (front, back, left, right)

= 1.3 =
* Language improvements (Thanks to Steve for helping me with french :))

= 1.2.9 =
* Skipping some errors when spreadshirt articles are no more readable (link dead?)

= 1.2.8 =
* Removed some error messages when in Wordpress debug mode

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
