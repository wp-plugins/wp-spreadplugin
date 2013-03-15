<?php
/**
 * Plugin Name: WP-Spreadplugin
 * Plugin URI: http://www.pr3ss-play.de/spreadshirt-wordpress-plugin-uber-api/
 * Description: Use a shortcut to display your Spreadshirt articles and add them to your Spreadshirt Basket using the API
 * Version: 1.2.6
 * Author: Thimo Grauerholz
 * Author URI: http://www.pr3ss-play.de
 */



/**
 * Api Key gibts hier: https://www.spreadshirt.de/-C7120 für EU/DE / for US/NA https://www.spreadshirt.com/-C6840
 *
 * Shortcode
 * [spreadplugin shop_id="732552" shop_limit="20" shop_locale="de_DE" shop_api="" shop_secret="" shop_source="net"]
 *
 * US/NA
 * [spreadplugin shop_id="414192" shop_limit="20" shop_locale="" shop_api="" shop_secret="" shop_source="com"]
 *
 * Put your API and secret in the fields above
 **/





/**
 * Avoid direct calls to this file
 */
if(!function_exists('add_action')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');

	exit();
}


/**
 * The class
 */
if(!class_exists('WP_Spreadplugin')) {
	class WP_Spreadplugin {
		private $stringTextdomain = 'spreadplugin';
		private static $intShopId = '';
		private static $stringApiUrl = '';
		private static $stringShopLocale = '';
		private static $stringShopLimit = '';
		private static $stringShopApi = '';
		private static $stringShopSecret = '';
		private static $stringShopImgSize = '190';

		function WP_Spreadplugin() {
			WP_Spreadplugin::__construct();
		}

		function __construct() {
			add_action('init', array($this,'plugin_init'));
			add_action('init', array($this,'myStartSession'), 1);
			add_action('wp_logout', array($this,'myEndSession'));
			add_action('wp_login', array($this,'myEndSession'));

			add_shortcode('spreadplugin', array($this,'ScSpreadplugin'));
			add_action('wp_head', array($this,'spreadpluginHead'));

			add_action('wp_enqueue_scripts', array($this,'myStyleMethod'));
			add_action('wp_enqueue_scripts', array($this,'myScriptMethod'));


			// These informations will be replaced on like button hovering
			add_action('wp_head', array($this,'socialHead'));
			add_action('wp_footer', array($this,'socialFooter'));

		}




		/**
		 * Initialize Plugin
		 */
		function plugin_init() {
			/**
			 * Language file not yet available
			 */
			if(function_exists('load_plugin_textdomain')) {
				load_plugin_textdomain($this->stringTextdomain, false, dirname(plugin_basename( __FILE__ )) . '/translation');
			}
				
		}




		/**
		 * Shortcode function
		 */
		function ScSpreadplugin($atts) {

			$shop_id = '';
			$shop_api = '';
			$shop_secret = '';
			$shop_limit = '';
			$shop_locale = '';
			$shop_source = '';

			extract(shortcode_atts(array(
			'shop_id' => '',
			'shop_locale' => 'de_DE',
			'shop_api' => '',
			'shop_source' => 'net',
			'shop_secret' => '',
			'shop_limit' => ''
			), $atts));

			self::$intShopId = $shop_id;
			self::$stringShopApi = $shop_api;
			self::$stringShopSecret = $shop_secret;
			self::$stringShopLimit = $shop_limit;
			self::$stringShopLocale = $shop_locale;
			self::$stringApiUrl = $shop_source;


			if(!empty(self::$intShopId) && !empty(self::$stringShopApi) && !empty(self::$stringShopSecret)) {

				if (empty(self::$stringShopLimit)) self::$stringShopLimit=20;


				/*
				 * add an article to the basket
				*/
				if (isset($_POST['size']) && isset($_POST['appearance']) && isset($_POST['quantity'])) {
					/*
					 * create an new basket if not exist
					*/
					if (!isset($_SESSION['basketUrl'])) {
						/*
						 * get shop xml
						*/
						$stringXmlShop = wp_remote_retrieve_body(wp_remote_get('http://api.spreadshirt.'.self::$stringApiUrl.'/api/v1/shops/' . self::$intShopId));
						$objShop = new SimpleXmlElement($stringXmlShop);

						/*
						 * create the basket
						*/
						$namespaces = $objShop->getNamespaces(true);

						$basketUrl = self::createBasket('net', $objShop, $namespaces);
						//print_r($basketUrl);

						$_SESSION['basketUrl'] = $basketUrl;

						$_SESSION['namespaces'] = $namespaces;
						/*
						 * get the checkout url
						*/
						$checkoutUrl = self::checkout($_SESSION['basketUrl'], $_SESSION['namespaces']);

						$_SESSION['checkoutUrl'] = $checkoutUrl;

					}
					/*
					 * article data to be sent to the basket resource
					*/
					$data = array(

							'articleId' => intval($_POST['article']),
							'size' => intval($_POST['size']),
							'appearance' => intval($_POST['appearance']),
							'quantity' => intval($_POST['quantity']),
							'shopId' => self::$intShopId

					);

					/*
					 * add to basket
					*/
					self::addBasketItem($_SESSION['basketUrl'] , $_SESSION['namespaces'] , $data);

				}




				/*
				 * print article list with size and color options
				*/

				// get pagination value from wordpress
				global $paged;
				if(empty($paged)) $paged = 1;

				$offset=($paged-1)*self::$stringShopLimit;

				$stringApiUrl='http://api.spreadshirt.'.self::$stringApiUrl.'/api/v1/shops/' . self::$intShopId . '/articles?'.(!empty(self::$stringShopLocale)?'locale=' . self::$stringShopLocale . '&':'').'fullData=true&limit='.self::$stringShopLimit.'&offset='.$offset;

				$stringXmlShop = wp_remote_get($stringApiUrl);
				if (count($stringXmlShop->errors)>0) die('Error getting articles. Please check Shop-ID, API and secret.');
				$stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
				$objArticles = new SimpleXmlElement($stringXmlShop);
				if (!is_object($objArticles)) die('Articles not loaded');
				

				if ($objArticles['count'] == 0) {
					
					echo 'No articles in Shop';
					
				} else {

					
					$output = '<div id="spreadshirt-items" class="spreadshirt-items clearfix">';
					$output .= '<div id="spreadshirt-list">';


					foreach ($objArticles->article as $article) {
						//print_r($article);
							
						$stringXmlArticle = wp_remote_retrieve_body(wp_remote_get($article->product->productType->attributes('xlink', true)));
						$objArticleData = new SimpleXmlElement($stringXmlArticle);
						$stringXmlCurreny = wp_remote_retrieve_body(wp_remote_get($article->price->currency->attributes('http://www.w3.org/1999/xlink')));
						$objCurrencyData = new SimpleXmlElement($stringXmlCurreny);

						/*
						 * get the productType resource
						*/
						$output .= '<div class="spreadshirt-article clearfix" id="article_'.$article['id'].'" style="height:550px">';
						$output .= '<a name="anchor_'.$article['id'].'"></a>';
						$output .= '<h3>'.$article->name.'</h3>';
						$output .= '<form method="post">';
						$output .= '<div class="image-wrapper">';
						$output .= '<img src="' . (string)$article->resources->resource->attributes('xlink', true) . ',width='.self::$stringShopImgSize.',height='.self::$stringShopImgSize.'" class="preview" alt="' . $article->name . '" id="previewimg_'.$article['id'].'" />';
						$output .= '<img src="' . (string)$article->resources->resource[2]->attributes('xlink', true) . ',width='.self::$stringShopImgSize.',height='.self::$stringShopImgSize.'" class="compositions" style="display:none;" alt="' . $article->name . '" id="compositeimg_'.$article['id'].'" />';
						$output .= '</div>';
							
						/*
						 * add a select with available sizes
						*/
						$output .= '<select id="size-select" name="size">';

						foreach($objArticleData->sizes->size as $val) {
							$output .= '<option value="'.$val['id'].'">'.$val->name.'</option>';
						}

						$output .= '</select>';
							
						$output .= '<div class="separator"></div>';
							
						/*
						 * add a list with availabel product colors
						*/
						$output .= '<ul class="colors" name="color">';

						foreach($objArticleData->appearances->appearance as $appearance) {
							if ($article->product->restrictions->freeColorSelection == 'true' || (int)$article->product->appearance['id'] == (int)$appearance['id']) {
								$output .= '<li value="'.$appearance['id'].'"><img src="'. $appearance->resources->resource->attributes('xlink', true) .'" alt="" /></li>';
							}
						}

						$output .= '</ul>';
							
						/**
						 * Show description link if not empty
						 */
						if (!empty($article->description)) {
							$output .= '<div class="separator"></div>';
							$output .= '<div class="description-wrapper"><div class="header"><a>'.__('Show description', $this->stringTextdomain).'</a></div><div class="description">'.$article->description.'</div></div>';
						}
							
						$output .= '<input type="hidden" value="'. $article->product->appearance['id'] .'" id="appearance" name="appearance" />';
						$output .= '<input type="hidden" value="'. $article['id'] .'" id="article" name="article" />';
						$output .= '<div class="separator"></div>';
						$output .= '<div class="price-wrapper">';
						$output .= '<span id="price-without-tax">'.__('Price (without tax):', $this->stringTextdomain)." ".$article->price->vatExcluded." ".$objCurrencyData->isoCode."<br /></span>";
						$output .= '<span id="price-with-tax">'.__('Price (with tax):', $this->stringTextdomain)." ".$article->price->vatIncluded." ".$objCurrencyData->isoCode."<br /></span>";
						$output .= '</div>';
						$output .= '<input type="text" value="1" id="quantity" name="quantity" maxlength="4" />';
						$output .= '<input type="submit" name="submit" value="'.__('Add to basket', $this->stringTextdomain).'" />';
						$output .= '<div class="fb-like" data-href="'.get_page_link().'#like='.$article['id'].'" data-send="false" data-layout="button_count" data-width="200" data-show-faces="false" style="width:200px; height:30px"></div>';
						$output .= '<a href="https://twitter.com/share" class="twitter-share-button" data-url="'.get_page_link().'#like='.$article['id'].'" data-text="'.(!empty($article->description)?$article->description:'Product').' @'.get_page_link().'#like='.$article['id'].'" data-lang="de">Tweet</a>';
						$output .= '</form></div>';

					}

					$output .= '</div>';


					$intInBasket=0;
					$basketItems=self::getBasket($_SESSION['basketUrl']);

					if(!empty($basketItems)) {
						foreach($basketItems->basketItems->basketItem as $item) {
							$intInBasket += $item->quantity;
						}
					}

					if (isset($_SESSION['checkoutUrl']) && $intInBasket>0) {
						$output .= '<div id="checkout">'.$intInBasket." <a href=".$_SESSION['checkoutUrl']." target=\"_blank\">".__('Basket', $this->stringTextdomain)."</a></div>";
					} else {
						$output .= '<div id="checkout">'.$intInBasket." <a title=\"".__('Basket is empty', $this->stringTextdomain)."\">".__('Basket', $this->stringTextdomain)."</a></div>";
					}

					echo $output;

					echo "
					<div id=\"navigation\"><a href=\"".get_pagenum_link($paged + 1)."\">".__('next', $this->stringTextdomain)."</a></div>
					<!-- <div id=\"copyright\">Copyright (c) Thimo Grauerholz - <a href=\"http://www.pr3ss-play.de\">pr3ss-play - Online Shop für deinen persönlichen Party-Style!</a></div> -->
					<div id=\"fb-root\"></div>
					</div>";

				}
			}
		}













		// Additional functions
		function addBasketItem($basketUrl, $namespaces, $data) {

			$basketItemsUrl = $basketUrl . "/items";

			$basketItem = new SimpleXmlElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
					<basketItem xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://api.spreadshirt.net">
					<quantity>' . $data['quantity'] . '</quantity>
					<element id="' . $data['articleId'] . '" type="sprd:article" xlink:href="http://api.spreadshirt.'.self::$stringApiUrl.'/api/v1/shops/' . $data['shopId'] . '/articles/' . $data['articleId'] . '">
					<properties>
					<property key="appearance">' . $data['appearance'] . '</property>
					<property key="size">' . $data['size'] . '</property>
					</properties>
					</element>
					<links>
					<link type="edit" xlink:href="http://' . $data['shopId'] .'.spreadshirt.' .self::$stringApiUrl.'/-A' . $data['articleId'] . '"/>
					<link type="continueShopping" xlink:href="http://' . $data['shopId'].'.spreadshirt.'.self::$stringApiUrl.'"/>
					</links>
					</basketItem>');

			$header = array();

			/*
			 $header['Authorization'] = self::createAuthHeader("GET", $basketItemsUrl);

			$header['Content-Type'] = "application/xml";

			$result = wp_remote_post( $basketItemsUrl, array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => $header,
					'body' => $basketItem->asXML(),
					'cookies' => array()
			)
			);
			*/

			$header[] = self::createAuthHeader("POST", $basketItemsUrl);

			$header[] = "Content-Type: application/xml";

			$result = self::oldHttpRequest($basketItemsUrl, $header, 'POST', $basketItem->asXML());

		}








		function createBasket($platform, $shop, $namespaces) {


			$basket = new SimpleXmlElement('<basket xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://api.spreadshirt.net">
					<shop id="' . $shop['id'] . '"/>
					</basket>');

			$attributes = $shop->baskets->attributes($namespaces['xlink']);

			$basketsUrl = $attributes->href;

			$header = array();

			/*
			 $header['Authorization'] = self::createAuthHeader("POST", $basketsUrl);

			$header['Content-Type'] = "application/xml";

			$result = wp_remote_post( $basketsUrl, array(
					'method' => 'POST',
					'httpversion' => '1.1',
					'headers' => $header,
					'body' => $basket->asXML()
			)
			);
			*/


			$header[] = self::createAuthHeader("POST", $basketsUrl);

			$header[] = "Content-Type: application/xml";

			$result = self::oldHttpRequest($basketsUrl, $header, 'POST', $basket->asXML());



			$basketUrl = self::parseHttpHeaders($result, "Location");

			return $basketUrl;

		}






		function checkout($basketUrl, $namespaces) {

			$basketCheckoutUrl = $basketUrl . "/checkout";

			$header = array();

			/*
			 $header['Authorization'] = self::createAuthHeader("GET", $basketCheckoutUrl);

			$header['Content-Type'] = "application/xml";

			$result = wp_remote_get( $basketCheckoutUrl, array(
					'method' => 'GET',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => $header
			)
			);
			*/

			$header[] = self::createAuthHeader("GET", $basketCheckoutUrl);

			$header[] = "Content-Type: application/xml";

			$result = self::oldHttpRequest($basketCheckoutUrl, $header, 'GET');


			$checkoutRef = new SimpleXMLElement($result);

			$refAttributes = $checkoutRef->attributes($namespaces['xlink']);

			$checkoutUrl = (string)$refAttributes->href;

			return $checkoutUrl;

		}

		/*
		 * functions to build headers
		*/

		/*function createAuthHeader($method, $url) {

		$time = time() *1000;

		$data = "$method $url $time";

		$sig = sha1("$data ".self::$stringShopSecret);

		return "SprdAuth apiKey=\"".self::$stringShopApi."\", data=\"$data\", sig=\"$sig\"";

		}
		*/
		function createAuthHeader($method, $url) {

			$time = time() *1000;

			$data = "$method $url $time";

			$sig = sha1("$data ".self::$stringShopSecret);

			return "Authorization: SprdAuth apiKey=\"".self::$stringShopApi."\", data=\"$data\", sig=\"$sig\"";

		}


		function parseHttpHeaders($header, $headername) {

			$retVal = array();

			$fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));

			foreach($fields as $field) {

				if (preg_match('/(' . $headername . '): (.+)/m', $field, $match)) {

					return $match[2];

				}

			}

			return $retVal;

		}

		function getBasket($basketUrl) {

			$header = array();

			if (!empty($basketUrl)) {
				$header[] = self::createAuthHeader("GET", $basketUrl);

				$header[] = "Content-Type: application/xml";

				$result = self::oldHttpRequest($basketUrl, $header, 'GET');

				$basket = new SimpleXMLElement($result);
			}
			return $basket;

		}



		function spreadpluginHead() {
			echo "<script>

jQuery(document).ready(function() {

var saheight = jQuery('.spreadshirt-article').css('height');
var par = '';



/*
* change article color
*/
function bindClick() {
	// avoid double firing events
	jQuery('.colors li').unbind();
	jQuery('.description-wrapper div.header').unbind();
	
	
	jQuery('.colors li').click(function(){
		var id = '#' + jQuery(this).closest('.spreadshirt-article').attr('id');
		var appearance = jQuery(this).attr('value');
		var src = jQuery(id + ' img.preview').attr('src');
		jQuery(id + ' img.preview').attr('src', src + ',appearanceId='+appearance);
		jQuery(id + ' #appearance').attr('value', appearance);
	});
	
	
	jQuery('.description-wrapper div.header').click(function(){
		var par = jQuery(this).parent().parent().parent();
		var field = jQuery(this).next();
		
		if (field.is(':hidden')) {
			par.css('height','');
			par.removeAttr('style');
			field.show();
			jQuery(this).children('a').html('".__('Hide description', $this->stringTextdomain)."');
		} else {
			jQuery('.spreadshirt-article').css('height',saheight);
			jQuery('.description-wrapper div.description').hide();
			jQuery('.description-wrapper div.header a').html('".__('Show description', $this->stringTextdomain)."');
		}
	});

}



function bindHover() {
	jQuery('img.preview').mouseenter(function(){
	var id = jQuery(this).attr('id');
	id = '#' + id.replace('previewimg','compositeimg');
	
	if (jQuery(this).is(':visible')) {
		jQuery(this).hide();
		jQuery(id).show();
	}
	});
	
	jQuery('.spreadshirt-article').mouseleave(function(){
		var id = jQuery(this).attr('id');
		id = id.replace('article','');
		
		jQuery('#' + 'compositeimg' + id).hide();
		jQuery('#' + 'previewimg' + id).show();
	});

	
	jQuery('.fb-like').hover(function(){
		jQuery('meta[property=\"og:title\"]').attr('content',jQuery(this).parent().parent().find('h3').html());
		jQuery('meta[property=\"og:url\"]').attr('content',jQuery(this).attr('data-href'));
		jQuery('meta[property=\"og:image\"]').attr('content',jQuery(this).parent().parent().find('.preview').attr('src'));
	});
	
}







jQuery('#spreadshirt-list').infinitescroll({
	nextSelector:'#navigation a',
	navSelector:'#navigation',
	itemSelector:'.spreadshirt-article',
	loading: {
	img: '".plugins_url('/img/loading.gif', __FILE__)."',
	msgText: 'Loading new articles...'
	},
	animate: true,
	debug: false,
	bufferPx: 40
	}, function(arrayOfNewElems){
	bindClick();
	bindHover();
	
	FB.XFBML.parse();
	twttr.widgets.load();
});



var scrollingDiv = jQuery('#checkout');

jQuery(window).scroll(function(){
	scrollingDiv.stop().animate({'marginTop': (jQuery(window).scrollTop() + 30) + 'px'}, 'slow');
});



bindClick();
bindHover();

});


</script>";
		}




		function oldHttpRequest($url, $header = null, $method = 'GET', $data = null, $len = null) {

			switch ($method) {

				case 'GET':

					$ch = curl_init($url);

					curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

					curl_setopt($ch, CURLOPT_HEADER, false);

					curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

					break;

				case 'POST':

					$ch = curl_init($url);

					curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

					curl_setopt($ch, CURLOPT_HEADER, true);

					curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

					curl_setopt($ch, CURLOPT_POST, true); //not createBasket but addBasketItem

					curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

					break;

			}

			$result = curl_exec($ch);

			curl_close($ch);

			return $result;

		}




		function myScriptMethod() {
			wp_enqueue_script(
			'infinite_scroll',
			plugins_url('/js/jquery.infinitescroll.min.js', __FILE__),
			array('jquery')
			);


			wp_enqueue_script('infinite_scroll');

		}
		function myStyleMethod() {
			// Respects SSL, Style.css is relative to the current file
			wp_register_style( 'spreadplugin', plugins_url('/css/spreadplugin.css', __FILE__) );
			wp_enqueue_style( 'spreadplugin' );

		}


		function myStartSession() {
			if(!session_id()) {
				session_start();
			}
		}

		function myEndSession() {
			session_destroy();
		}


		// gets replaced on facebook button hover
		function socialHead() {
				echo '
				
				<meta property="og:title" content="Online Shop" />
				<meta property="og:url" content="http://www.pr3ss-play.de/shop/" />
				<meta property="og:image" content="http://image.spreadshirt.net/image-server/v1/products/110098765/views/1,width=200,height=200" />
				
				';
		}
		
		function socialFooter() {
				echo '<script src="//connect.facebook.net/de_DE/all.js#xfbml=1"></script><script src="//platform.twitter.com/widgets.js"></script>';
		}


	} // END class WP_Spreadplugin

	new WP_Spreadplugin();
} // END if(!class_exists('WP_Spreadplugin'))



?>