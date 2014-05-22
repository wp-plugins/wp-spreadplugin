=== WP-Spreadplugin ===
Contributors: Thimo Grauerholz
Author: Thimo Grauerholz
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=EZLKTKW8UR6PQ
Tags: spreadshirt,wordpress,plugin,shop,store,shirt,t-shirt,integration,online store,online shop
Requires at least: 3.3
Tested up to: 3.9
Stable tag: 3.5.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin uses the Spreadshirt API to list articles and let your customers order articles of your Spreadshirt shop using Spreadshirt order process.

== Description ==

This is a small Wordpress plugin for displaying the articles of your Spreadshirt-Shop in Wordpress. The plugin uses the Spreadshirt API to display and don't uses iframes!
If you add an article to the cart and then click on the cart link, you will be redirected to the usual Spreadshirt basket. The whole payment and order process is handeled by Spreadshirt.

Using the plugin is quite easy!
You only need to add the shortcode to your new or existing page and you're done! 

The pagination is done via infinity scroll.

**Current features**

* Infinity Scrolling
* Uses Spreadshirt-Basket (A click on the basket opens the spreadshirt own basket)
* Price listing
* Choose color and sizes
* Language support (de_DE, en_GB, fr_FR, nl_NL, nn_NO, nb_NO, da_DK, it_IT based on Wordpress installation, more on request)
* Social buttons
* Enhanced article zoom
* Display article descriptions
* Cache
* Choose or direct link to the product category
* Sorting and filtering
* Adding products to the basket without page reload
* Choose between article or design view (click on a design shows the articles inside the website)
* Settings page/admin page for easier configuration
* No premium shop necessary except you want to link to and use the designer
* Basket
* Own product pages

**Premium Support**

The Spreadplugin team does not provide support for the plugin on the WordPress.org forums anymore. One on one email support is available to people who donated at least $10 or payed for the premium version.

**See it in action**

http://lovetee.de/#shop (in german)

**What do you need**

* Wordpress
* Spreadshirt shop
* Spreadshirt API key and secret (US/NA From: `https://www.spreadshirt.com/-C6840`, EU From: `https://www.spreadshirt.net/my-api-keys-C7120`)

If you have suggestions, feel free to email me at info@spreadplugin.de.
Want regular updates? Become a fan of my sites on Facebook!

http://www.facebook.com/lovetee.de

Or follow my sites on Twitter!

http://twitter.com/lovetee_de

Or you want to buy some of my shirts? 

http://welovetee.spreadshirt.net or http://welovetee.spreadshirt.com

== Installation ==

1. Upload the spreadplugin directory to the `/wp-content/plugins/wp-spreadplugin` Directory (if not exists please create) or install using wordpress plugin installer
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit default settings using `http://YOURBLOGURL/wp-admin/options-general.php?page=splg_options`.
4. Create a new site or edit an existing site
5. Insert shortcode

**Minimum**

[spreadplugin]

**Extended**

US/NA:
`[spreadplugin shop_id="414192" shop_limit="10" shop_locale="us_US" shop_source="com" shop_category="" shop_social="1" shop_enablelink="1" shop_productcategory="" shop_checkoutiframe="2" shop_sortby="" shop_designershop="0" shop_display="0" shop_api="" shop_secret=""]`

EU:
`[spreadplugin shop_id="732552" shop_limit="10" shop_locale="de_DE" shop_source="net" shop_category="" shop_social="1" shop_enablelink="1" shop_productcategory="" shop_checkoutiframe="2" shop_sortby="" shop_designershop="0" shop_display="0" shop_api="" shop_secret=""]`

6. These values are required: Shop Id, Shop locale, Shop source, Shop API and Shop secret!
7. When adding default settings, only the minimum shortcode is required. 
(If you extend the shortcode with additional values, you can overwrite the default settings and use the plugin with an additional shop (add the extended shortcode to a different article/site)).
8. Done

== Frequently asked questions ==

= It shows "No articles in Shop", but there are articles in the Spreadshirt-Shop =

Check if your Spreadshirt Shop has a language and country setting.

= I want to use a different currency. Is this possible? =

The currency bases on the currency you're using for your Spreadshirt Shop. So if you want to use DKK instead of EUR, change the currency in the Shop options of your Spreadshirt Shop. But be sure to not have any products in your shop, because Spreadshirt doesn't allow changing it, when you've already products in it. (The solution would be to create a new shop) Please clear the cache of the Spreadplugin plugin in the Spreadplugin settings page when done (you may wait some hours to the spreadshirt cache to refresh).

= How do I get the category Id? =

1. You must have already created a category in your spreadshirt shop, if not, please do so, now. Please refer to spreadshirt, if you don't know how.
2. Please open your normal spreadshirt shop (shopid.spreadshirt.net). In my case: http://welovetee.spreadshirt.de/
3. Choose a category from the category selection.
4. The url of your shop has changed to `http://welovetee.spreadshirt.de/alsterwasser-C300890` in my case
5. The category Id in this case is `300890` (the numbers after the "-C" part)
6. Paste this number in the `shop_category=""` variable. In my case `shop_cagegory="300890"`.
7. Save and done.

= How to display one category per page? (Custom categories) =

1. Please see `How do I get the category Id?` 
2. If you have got your category id, create a page in wordpress
3. Go to text mode
4. Paste extended shortcode (you don't need all values, just the one you want to overwrite) `[spreadplugin shop_cagegory="CATEGORYID"]`
5. Save and repeat by each category you want to display.

= How to display one pre-defined category per page? =

1. Please see `How to display one category per page? (Custom categories)`
2. Instead of using shop_category, use `shop_productcategory` and one of your category names as values. Possible values are Men, Women, Kids & Babies,... But in your language.

= How to disable the social buttons? =

Add or change in the [spreadplugin] code the value from `shop_social="1"` to `shop_social="0"` or use the settings page.

= How to disable the product links? =

Add or change in the [spreadplugin] code the value from `shop_enablelink="1"` to `shop_enablelink="0"` or use the settings page.

= How to change the link targets? =

Add or change in the [spreadplugin] code the value from `shop_linktarget=""` to shop_linktarget="YOUR_IFRAME_NAME"` or whatever it is. Or use the settings page.

= I want my checkout page display in an iframe, what is to do? =

Add or change in the [spreadplugin] code the value from `shop_checkoutiframe="0"` to `shop_checkoutiframe="1"` or `shop_checkoutiframe="2"`. Note: shop_linktarget will be ignored. Or use the settings page.

* `shop_checkoutiframe="0"` opens basket in separate window
* `shop_checkoutiframe="1"` opens basket in an iframe in the page content 
* `shop_checkoutiframe="2"` opens basket in an iframe in a modal window 

= How to default sort? =

Add or change in the `[spreadplugin]` code the value from `shop_sortby=""` to `shop_sortby="name"`. Available sort options are name, price, recent. Or use the settings page.

= I get following error, when I add an article to the basket: "ERROR: Basket not ready yet." =

Please update to the most recent version

= How to active edit articles to use the designer shop? =

Activate by changing shop_designershop="0" to shop_designershop="DESIGNERSHOPID" where DESIGNERSHOPID is your designer shop ID. Or use the settings page.

= How to active designs view? =

Activate by changing shop_display="0" to shop_display="1" or change it in the admin panel. Or use the settings page.

= It shows old articles =

Please go to the settings page in the admin panel of the plugin and click "Clear cache". 

= I want to use more than one shop on the same website =

Please use the extended shortcode. 
This will overwrite the default plugin settings just for the page, where you have added this shortcode.

= The infinity scroll always repeats all of my articles on and on and on and.. =

This might be a problem resulting of a special URL structure (permalinks). In this case, please have a look at your wordpress settings -> permalinks. 
If you don't want to change this setting to another one, please let me know the structure to check it.

= The article description should be displayed without clicking "Show description". How to do it? =

Add or edit the shortcode or set in shortcode plugin settings to enable. Shortcode would be `shop_showdescription="1"` for enabling.

= I want to display one design per page =

Add or edit the shortcode you are using and add/change following variable `shop_design="DESIGNID"`.
You get the `DESIGNID` from your spreadshirt admin panel.

= If I checkout, the language differs from the language of the shop?! =

Please check your `Locale` setting in your user-data of spreadshirt.
You can find it here: http://www.spreadshirt.net/user-data-C162 or http://www.spreadshirt.com/user-data-C162

= How can I change the language of different shop instances? =

If you change the language in your wordpress installation, the language of the plugin changes, too. Well, but you can change the language only for the plugin by selecting your language in the spreadplugin options, now. If you have multiple pages with different shops on it and want to use a different language on each page, please use the shortcode and extend your already used shortcode by - for example `shop_language="de_DE"` - possible values are: de_DE, en_GB, fr_FR, nl_NL, nn_NO, nb_NO, da_DK, it_IT. Your new shortcode could look like this: `[spreadplugin shop_language="de_DE"]`

= Where do I find great website templates for wordpress? =

Please have a look here: http://themeforest.net/category/wordpress?ref=thimo

== Screenshots ==

1. Design view
2. Article view
3. Settings page

== Changelog ==

= 3.5.7 =
* Language of the plugin is now selectable / changeable through shortcode. On questions please see faq
* Small error message when adding a product failes.

= 3.5.6.3 =
* Some minor fixes
* Added span tags for size and color label to disable them via css

= 3.5.6.1 =
* Inches on detail pages for US/CA
* Bugfixes

= 3.5.6 =
* Get rid of some error messages

= 3.5.5.6 =
* Https fixes

= 3.5.5.5 =
* Minor bug fix to get rid of php notices

= 3.5.5.3 =
* Print technique for detail page added

= 3.5.5.2 =
* Language fix french

= 3.5.5.1 =
* Added option to enable or disable lazy load

= 3.5.5 =
* New option to change the zoom behaviour. Please see spreadplugin options at Zoom type.

= 3.5.4 =
* New detail page. Added product details, size table...

= 3.5.3.4 =
* Changed URL style of detail page, so woocommerce installations are not harmed :)

= 3.5.3.3 =
* Bugfix: In some cases the detail pages of a product is empty

= 3.5.3.2 =
* Bugfix in Product detail pages

= 3.5.3.1 =
* Bugfix in shop URL

= 3.5.3 =
* Checkout-Language workaround added and set to shop country

= 3.5.2 =
* Replaced fancyBox 2 with magnific-popup

= 3.5 =
* Bugfixes

= 3.4.2 =
* Bugfixes

= 3.4.1 =
* Minor improvements

= 3.4 =
* Added close basket, when click outside
* Added new option to display product description under article
* Minor enhancements

= 3.3.1 = 
* Italian translation added

= 3.3 = 
* Beautiful flyover to basket animation

= 3.2 =
* Depending on stock state the size and color of a product will be hidden (beta) / removed

= 3.1.5 =
* Speed improvements by adding lazy image loading (only loads images when in viewport)

= 3.1.4 =
* Solved session basket problem: On some server configurations, the session couldn't be reused, so there was created a new session and so the basket contents were lost.
* Minor Bugfixes

= 3.1.3 =
* Bugfix: Translation problem in detail page fixed

= 3.1.2 =
* Bugfix: Pagination did not work in some conditions

= 3.1.1 =
* Bugfix: Basket has shown wrong prices, when quantity is higher than one

= 3.1 =
* Added own product pages (detail pages)

= 3.0.1 =
* Added new shortcode for displaying only specific designs in specific pages. Please refer faq.

= 3.0 =
* Basket added
* Minor bugfixes

= 2.9.6 =
* Minor bugfixes
* Added Text for german locale 'zzgl. Versandkosten' if extended price is choosen

= 2.9.5.1 =
Norwegian language added

= 2.9.5 =
* JS now loaded at the shortcode call.
* Timeout limit removed

= 2.9.4 =
Pagination bugfix: In some cases, the pagination doesn't work and always shows the first page.

= 2.9.3 =
Price format changed for USD

= 2.9.2 =
Bugfix: InfiniteScroll was not disabled correctly - it did show Javascript errors.

= 2.9.1 =
Sometimes no articles were displayed, when no designs are available. This has now been fixed.

= 2.9 =
* Add your own custom css in admin interface. This won't be overwritten by any spreadplugin update.
* Disable infinite scrolling in admin interface.
* Added edit article button, if designer shop id is added.

= 2.8.2 =
Bugfix: Wrong view was used as default

= 2.8.1 =
* Color picker for zoom image background / choose background color
* Default sorting changed

= 2.8 =
* Enhanced article zoom
* Price with tax now displayed only. Can be changed in options/settings page.
* Two images sizes now available (190 & 280 pixel)

= 2.7.6 =
Enhanced cache call method to get always newest API file, when deleting spreadplugin cache.

= 2.7.5 =
Bugfix: Script tried to display descriptions of the designs, which is currently not available from spreadshirt

= 2.7.4 =
Article description can now be displayed always. Use `shop_showdescription="1"` to enable or use settings.

= 2.7.3 =
Edit article now opens in fancybox when set `shop_checkoutiframe="2"`. It opens in a separate window by default, now. Thanks to grillwear-shop.de

= 2.7.2 =
* Extended admin page
* An must have update!

= 2.7.1 =
* Fix for `Better WP Security` users, which were unable to display the options page in some circumstances.

= 2.7 =
* New kick-ass retina social buttons
* Clean-up release
* Maybe the last release

= 2.6.2 =
* Bugfixes (Sorting reverted, can't solve it yet)

= 2.6.1 =
* Bugfixes (incl. sorting bug - hopefully ;-))
* Added short product description e.g. Women�s Classic T-Shirt, Men�s Classic T-Shirt,...

= 2.6 =
* New social buttons, if you don't like, just replace in the image directory with your own. See FAQ
* Speed improvements (got rid of facebook and twitter implementations)

= 2.5 =
* Settings page will now be used for all default settings. If you configure default settings, you'll just need the minimum shortcode `[spreadplugin]`. 
If you extend your shortcode with additional settings, they will be used! All existing shortcodes may stay untouched at least your shop_locale is not empty.
If you receive a locale error, please add shop_locale="us_US" to your shortcode. Please refer `http://wordpress.org/extend/plugins/wp-spreadplugin/installation/`.
* Bugfix
* Please save your old css file. If it's from Version 2.2.x you can reuse it in 2.5.

= 2.2.1 =
* Sorting added 'weight'

= 2.2 =
* New sticky toolbar
* CSS & JS fixes

= 2.1.3 =
* Updated the design view
* SSL improvements - thanks goes to Marcus from sozenshirts.de for that note
* Minor bugfixes

= 2.1.2 =
Bugfix: Infinity scrolling doesn't work sometimes when in designs view

= 2.1 =
* Settings page added to regenerate cache because **Cache doesn't regenerate itself anymore** due to performance

= 2.0 =
* Added a new shortcode variable to by display designs by default. To enable change shop_display="0"` to shop_display="1"`. 
Sample (active): http://lovetee.de/shop/
Sample (disabled/article view): http://lovetee.de/shop-articles/
* Added Pinterest / Thanks to shirtarrest.com
* Article category sub-filter
* CSS fixes

= 1.9.4 =
Click on zoom image doesn't open a separate window anymore. The article description is now displayed in a modal window on the website.

= 1.9.3 =
Edit for articles added, if you have a designer shop. Activate by changing shop_designershop="0" to shop_designershop="[DESIGNERSHOPID]".

= 1.9.2 =
* Bugfix: JS script took each form to submit an article to the basket :)
* Style: Changed some styles to fit most environments

= 1.9.1 =
Fancybox added to display checkout in a modal window. Activate by adding or changing `shop_checkoutiframe="0"` to `shop_checkoutiframe="2"`

= 1.9 =
* Ajax driven shop (Add products to the basket without reloading the whole content)
* Internal article cache extended to 2 hours. If you want to change, have a look at row 46 in spreadplugin.php and change the value.

= 1.8.4 =
Price formatting added

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

= 1.7.4 =
* Custom url structures now possible

= 1.7.3 =
* Added a new shortcode variable to open the checkout window in an iframe. To enable change shop_checkoutiframe="0"` to shop_checkoutiframe="1"`.
* Bugfix

= 1.7.1 =
* Added a new shortcode variable to change the link targets. To enable change `shop_linktarget=""` to shop_linktarget="YOUR_IFRAME_NAME"`.
* You may hide the product category field by adding a style in the plugin css. E.g. .spreadplugin-items #productCategory {display:none}

= 1.7 =
* Own cache added (updates every 8 hours) - speed improvements.
* Product category now accessable
* Shortcode added for direct calls of a category. Add ` shop_productcategory=""` and fill with field value e.g. Women => ` shop_productcategory="Women"`

= 1.6.5 =
* Debugging things, no need to update

= 1.6.4 =
* Each article image has now a link to it's spreadshirt product details website. Use the shortcode to enable ` shop_enablelink="1"` or disable `shop_enablelink="0"` this behaviour (default is enabled).

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

= 1.2.7 =
* I missed the multi language features of Twitter and Facebook, so sorry!

= 1.2.6 =
* Added Twitter share button. It pushes description text if available, else it just says 'Product'. Additionally, it says @URL to product. 

= 1.2.5 =
* Added Facebook like button

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
