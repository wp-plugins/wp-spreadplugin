<?php
/**
 * Plugin Name: WP-Spreadplugin
 * Plugin URI: http://www.pr3ss-play.de/spreadshirt-wordpress-plugin-uber-api/
 * Description: This plugin uses the Spreadshirt API to list articles and let your customers order articles of your Spreadshirt shop using Spreadshirt order process.
 * Version: 1.7.1
 * Author: Thimo Grauerholz
 * Author URI: http://www.pr3ss-play.de
 */



/**
 * Api Key gibts hier: https://www.spreadshirt.de/-C7120 für EU/DE / for US/NA https://www.spreadshirt.com/-C6840
 *
 * Shortcode
 * [spreadplugin shop_id="732552" shop_limit="20" shop_locale="de_DE" shop_api="" shop_secret="" shop_category="" shop_source="net" shop_social="1" shop_enablelink="1" shop_productcategory="" shop_linktarget=""]
 *
 * US/NA
 * [spreadplugin shop_id="414192" shop_limit="20" shop_locale="" shop_api="" shop_secret="" shop_category="" shop_source="com" shop_social="1"  shop_enablelink="1" shop_productcategory="" shop_linktarget=""]
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
		private static $stringShopCategoryId = '';
		private static $stringShopSocialEnabled = 1;
		private static $stringShopLinkEnabled = 1;
		private static $stringShopProductCategory = '';
		private static $stringShopLinkTarget = '_blank';

		function WP_Spreadplugin() {
			WP_Spreadplugin::__construct();
		}

		function __construct() {
			add_action('init', array($this,'plugin_init'));
			add_action('init', array($this,'startSession'), 1);
			add_action('wp_logout', array($this,'endSession'));
			add_action('wp_login', array($this,'endSession'));

			add_shortcode('spreadplugin', array($this,'ScSpreadplugin'));

			add_action('wp_enqueue_scripts', array($this,'styleMethod'));
			add_action('wp_enqueue_scripts', array($this,'scriptMethod'));

			add_action('wp_footer', array($this,'spreadpluginHead'));

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
			global $paged;

			$sc = shortcode_atts(array(
					'shop_id' => '',
					'shop_locale' => 'de_DE',
					'shop_api' => '',
					'shop_source' => 'net',
					'shop_secret' => '',
					'shop_limit' => '',
					'shop_category' => '',
					'shop_social' => 1,
					'shop_enablelink' => 1,
					'shop_productcategory' => '',
					'shop_linktarget' => '_blank',
			), $atts);

			self::$intShopId = intval($sc['shop_id']);
			self::$stringShopApi = $sc['shop_api'];
			self::$stringShopSecret = $sc['shop_secret'];
			self::$stringShopLimit = intval($sc['shop_limit']);
			self::$stringShopLocale = $sc['shop_locale'];
			self::$stringApiUrl = $sc['shop_source'];
			self::$stringShopCategoryId = intval($sc['shop_category']);
			self::$stringShopSocialEnabled = intval($sc['shop_social']);
			self::$stringShopLinkEnabled = intval($sc['shop_enablelink']);
			self::$stringShopProductCategory = $sc['shop_productcategory'];
			self::$stringShopLinkTarget = $sc['shop_linktarget'];

			if (isset($_GET['productCategory'])) {
				$c = urldecode($_GET['productCategory']);
				if (!empty($c)) self::$stringShopProductCategory = $c;
			}


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
						$stringApiUrl = 'http://api.spreadshirt.'.self::$stringApiUrl.'/api/v1/shops/' . self::$intShopId;
						$stringXmlShop = wp_remote_get($stringApiUrl);
						if (count($stringXmlShop->errors)>0) die('Error getting basket.');
						if ($stringXmlShop['body'][0]!='<') die($stringXmlShop['body']);
						$stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
						$objShop = new SimpleXmlElement($stringXmlShop);
						if (!is_object($objShop)) die('Basket not loaded');

						/*
						 * create the basket
						*/
						$namespaces = $objShop->getNamespaces(true);
						$basketUrl = self::createBasket('net', $objShop, $namespaces);
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
				// use pagination value from wordpress
				if(empty($paged)) $paged = 1;

				$offset=($paged-1)*self::$stringShopLimit;

				$articleData=self::getArticleData();
				$typesData=$articleData['types'];
				unset($articleData['types']);


				// filter
				foreach ($articleData as $id => $article) {
					if (!empty(self::$stringShopProductCategory)&&isset($typesData[self::$stringShopProductCategory])&&!in_array($article['type'],$typesData[self::$stringShopProductCategory])) {
						unset($articleData[$id]);
					}
				}



				// pagination
				if (!empty(self::$stringShopLimit)) {
					$articleData = array_slice($articleData, $offset, self::$stringShopLimit, true);
				}



				$output = '<div id="spreadshirt-items" class="spreadshirt-items clearfix">';


				$intInBasket=0;

				if (isset($_SESSION['basketUrl'])) {
					$basketItems=self::getBasket($_SESSION['basketUrl']);

					if(!empty($basketItems)) {
						foreach($basketItems->basketItems->basketItem as $item) {
							$intInBasket += $item->quantity;
						}
					}
				}

				if (isset($_SESSION['checkoutUrl']) && $intInBasket>0) {
					$output .= '<div id="checkout">'.$intInBasket." <a href=".$_SESSION['checkoutUrl']." target=\"".self::$stringShopLinkTarget."\">".__('Basket', $this->stringTextdomain)."</a></div>";
				} else {
					$output .= '<div id="checkout">'.$intInBasket." <a title=\"".__('Basket is empty', $this->stringTextdomain)."\">".__('Basket', $this->stringTextdomain)."</a></div>";
				}


				$output .= '<select name="productCategory" id="productCategory">';
				$output .= '<option value="">'.__('Product category', $this->stringTextdomain).'</option>';
				if (isset($typesData)) {
					foreach ($typesData as $t => $v) {
						$output .= '<option value="'.urlencode($t).'"'.($t==self::$stringShopProductCategory?' selected':'').'>'.$t.'</option>';
					}
				}
				$output .= '</select>';


				// anzeige
				if (count($articleData) == 0) {

					$output .= '<br>No articles in Shop';

				} else {

					$output .= '<div id="spreadshirt-list">';


					foreach ($articleData as $id => $article) {

						/*
						 * get the productType resource
						*/
						$output .= '<div class="spreadshirt-article clearfix" id="article_'.$id.'" style="height:600px">'; // fixing the height of each Article
						$output .= '<a name="'.$id.'"></a>';
						$output .= '<h3>'.$article['name'].'</h3>';
						$output .= '<form method="post">';
						$output .= '<div class="image-wrapper">';
						$output .= (self::$stringShopLinkEnabled==1?'<a href="http://'.self::$intShopId.'.spreadshirt.'.self::$stringApiUrl.'/-A'.$id.'" target="'.self::$stringShopLinkTarget.'">':'');
						$output .= '<img src="' . $article['resource0'] . ',width='.self::$stringShopImgSize.',height='.self::$stringShopImgSize.'" class="preview" alt="' . $article['name'] . '" id="previewimg_'.$id.'" />';
						$output .= '<img src="' . $article['resource2'] . ',width='.self::$stringShopImgSize.',height='.self::$stringShopImgSize.'" class="compositions" style="display:none;" alt="' . $article['name'] . '" id="compositeimg_'.$id.'" title="'.addslashes($article['productdescription']).'" />';
						$output .= (self::$stringShopLinkEnabled==1?'</a>':'');
						$output .= '</div>';
							
						/*
						 * add a select with available sizes
						*/
						$output .= '<select id="size-select" name="size">';

						foreach($article['sizes'] as $k => $v) {
							$output .= '<option value="'.$k.'">'.$v.'</option>';
						}

						$output .= '</select>';
							
						$output .= '<div class="separator"></div>';
							
						/*
						 * add a list with availabel product colors
						*/
						$output .= '<ul class="colors" name="color">';

						foreach($article['appearances'] as $k=>$v) {
							$output .= '<li value="'.$k.'"><img src="'. $v .'" alt="" /></li>';
						}

						$output .= '</ul>';



						/*
						 * add a list with availabel product views
						*/
						$output .= '<ul class="views" name="views">';

						foreach($article['views'] as $k=>$v) {
							$output .= '<li value="'.$k.'"><img src="'. $v  .',viewId='.$k.',width=42,height=42" class="previewview" alt="" id="viewimg_'.$id.'" /></li>';
						}

						$output .= '</ul>';


							
						/**
						 * Show description link if not empty
						 */
						if (!empty($article['description'])) {
							$output .= '<div class="separator"></div>';
							$output .= '<div class="description-wrapper"><div class="header"><a>'.__('Show description', $this->stringTextdomain).'</a></div><div class="description">'.$article['description'].'</div></div>';
						}
							
						$output .= '<input type="hidden" value="'. $article['appearance'] .'" id="appearance" name="appearance" />';
						$output .= '<input type="hidden" value="'. $article['view'] .'" id="view" name="view" />';
						$output .= '<input type="hidden" value="'. $id .'" id="article" name="article" />';
						$output .= '<div class="separator"></div>';
						$output .= '<div class="price-wrapper">';
						$output .= '<span id="price-without-tax">'.__('Price (without tax):', $this->stringTextdomain)." ".$article['pricenet']." ".$article['currencycode']."<br /></span>";
						$output .= '<span id="price-with-tax">'.__('Price (with tax):', $this->stringTextdomain)." ".$article['pricebrut']." ".$article['currencycode']."<br /></span>";
						$output .= '</div>';
						$output .= '<input type="text" value="1" id="quantity" name="quantity" maxlength="4" />';
						$output .= '<input type="submit" name="submit" value="'.__('Add to basket', $this->stringTextdomain).'" />';

						if (self::$stringShopSocialEnabled==true) {
							$output .= '<div class="fb-like" data-href="'.get_page_link().'#'.$id.'" data-send="false" data-layout="button_count" data-width="200" data-show-faces="false" style="width:200px; height:30px"></div>';
							$output .= '<a href="https://twitter.com/share" class="twitter-share-button" data-url="'.get_page_link().'#'.$id.'" data-count="none" data-text="'.(!empty($article['description'])?$article['description']:'Product').'" data-lang="'.(!empty(self::$stringShopLocale)?substr(self::$stringShopLocale,0,2):'en').'">Tweet</a>';
						}

						$output .= '</form></div>';

					}



					$output .= "
							<div id=\"navigation\"><a href=\"".get_pagenum_link($paged + 1)."\">".__('next', $this->stringTextdomain)."</a></div>
									<!-- <div id=\"copyright\">Copyright (c) Thimo Grauerholz - <a href=\"http://www.pr3ss-play.de\">pr3ss-play - Online Shop für deinen persönlichen Party-Style!</a></div> -->
									<div id=\"fb-root\"></div>
									</div>";
				}


				$output .= '</div>';

				echo $output;


			}
		}







		function getArticleData() {
			$arrTypes=array();

			// post id holen und mit zum cache namen speichern, falls code in mehrere seiten eingebunden und unterschiedliche shops benutzt werden
			$articleData = get_transient('spreadplugin-article-cache-'.get_the_ID());

			if($articleData === false) {

				$stringApiUrl = 'http://api.spreadshirt.'.self::$stringApiUrl.'/api/v1/shops/' . self::$intShopId;
				$stringApiUrl .= (!empty(self::$stringShopCategoryId)?'/articleCategories/'.self::$stringShopCategoryId:'');
				$stringApiUrl .= '/articles?'.(!empty(self::$stringShopLocale)?'locale=' . self::$stringShopLocale . '&':'').'fullData=true&limit=1'; # &limit='.self::$stringShopLimit.'&offset='.$offset

				$stringXmlShop = wp_remote_get($stringApiUrl);
				if (count($stringXmlShop->errors)>0) die('Error getting articles. Please check Shop-ID, API and secret.');
				if ($stringXmlShop['body'][0]!='<') die($stringXmlShop['body']);
				$stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
				$objArticles = new SimpleXmlElement($stringXmlShop);
				if (!is_object($objArticles)) die('Articles not loaded');


				// erneuter call, weil sonst limit bei 50
				$stringApiUrl .= '&limit='.$objArticles['count']; # &limit='.self::$stringShopLimit.'&offset='.$offset

				$stringXmlShop = wp_remote_get($stringApiUrl);
				if (count($stringXmlShop->errors)>0) die('Error getting articles. Please check Shop-ID, API and secret.');
				if ($stringXmlShop['body'][0]!='<') die($stringXmlShop['body']);
				$stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
				$objArticles = new SimpleXmlElement($stringXmlShop);
				if (!is_object($objArticles)) die('Articles not loaded');


				if ($objArticles['count']>0) {

					// ProductTypeDepartments
					$stringTypeApiUrl = 'http://api.spreadshirt.'.self::$stringApiUrl.'/api/v1/shops/' . self::$intShopId.'/productTypeDepartments?'.(!empty(self::$stringShopLocale)?'locale=' . self::$stringShopLocale . '&':'').'fullData=true';
					$stringTypeXml = wp_remote_get($stringTypeApiUrl);
					$stringTypeXml = wp_remote_retrieve_body($stringTypeXml);
					$objTypes = new SimpleXmlElement($stringTypeXml);

					if (is_object($objTypes)) {
						foreach ($objTypes->productTypeDepartment as $row) {
							foreach ($row->categories->category as $subrow) {
								foreach ($subrow->productTypes as $subrow2) {
									foreach ($subrow2->productType as $subrow3) {
										$arrTypes[(string)$row->name][] = (int)$subrow3['id'];
									}
								}
							}
						}
					}

					$articleData['types'] = $arrTypes;




					// artikel lesen
					foreach ($objArticles->article as $article) {

						$stringXmlArticle = wp_remote_retrieve_body(wp_remote_get($article->product->productType->attributes('xlink', true).'?'.(!empty(self::$stringShopLocale)?'locale=' . self::$stringShopLocale:'')));
						if(substr($stringXmlArticle, 0, 5) !== "<?xml") continue;
						$objArticleData = new SimpleXmlElement($stringXmlArticle);
						$stringXmlCurreny = wp_remote_retrieve_body(wp_remote_get($article->price->currency->attributes('http://www.w3.org/1999/xlink')));
						if(substr($stringXmlArticle, 0, 5) !== "<?xml") continue;
						$objCurrencyData = new SimpleXmlElement($stringXmlCurreny);

						$articleData[(int)$article['id']]['name']=(string)$article->name;
						$articleData[(int)$article['id']]['description']=(string)$article->description;
						$articleData[(int)$article['id']]['appearance']=(int)$article->product->appearance['id'];
						$articleData[(int)$article['id']]['view']=(int)$article->product->defaultValues->defaultView['id'];
						$articleData[(int)$article['id']]['type']=(int)$article->product->productType['id'];
						$articleData[(int)$article['id']]['pricenet']=(string)$article->price->vatExcluded;
						$articleData[(int)$article['id']]['pricebrut']=(string)$article->price->vatIncluded;
						$articleData[(int)$article['id']]['currencycode']=(string)$objCurrencyData->isoCode;
						$articleData[(int)$article['id']]['resource0']=(string)$article->resources->resource->attributes('xlink', true);
						$articleData[(int)$article['id']]['resource2']=(string)$article->resources->resource[2]->attributes('xlink', true);
						$articleData[(int)$article['id']]['productdescription']=(string)$objArticleData->description;

						foreach($objArticleData->sizes->size as $val) {
							$articleData[(int)$article['id']]['sizes'][(int)$val['id']]=(string)$val->name;
						}

						foreach($objArticleData->appearances->appearance as $appearance) {
							if ($article->product->restrictions->freeColorSelection == 'true' || (int)$article->product->appearance['id'] == (int)$appearance['id']) {
								$articleData[(int)$article['id']]['appearances'][(int)$appearance['id']]=(string)$appearance->resources->resource->attributes('xlink', true);
							}
						}

						foreach($objArticleData->views->view as $view) {
							$articleData[(int)$article['id']]['views'][(int)$view['id']]=(string)$article->resources->resource->attributes('xlink', true);
						}

					}

					set_transient('spreadplugin-article-cache-'.get_the_ID(), $articleData, 3600);
				}
			}

			return $articleData;
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
			$header[] = self::createAuthHeader("POST", $basketsUrl);
			$header[] = "Content-Type: application/xml";
			$result = self::oldHttpRequest($basketsUrl, $header, 'POST', $basket->asXML());
			$basketUrl = self::parseHttpHeaders($result, "Location");

			return $basketUrl;

		}






		function checkout($basketUrl, $namespaces) {

			$basketCheckoutUrl = $basketUrl . "/checkout";
			$header = array();
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
			$basket = "";

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
			/**
			* Spreadplugin vars
			*/

			var textHideDesc = '".__('Hide description', $this->stringTextdomain)."';
			var textShowDesc = '".__('Show description', $this->stringTextdomain)."';
			var loadingImage = '".plugins_url('/img/loading.gif', __FILE__)."';
			var loadingMessage = '".__('Loading new articles...', $this->stringTextdomain)."';
			var loadingFinishedMessage = '".__('You have reached the end', $this->stringTextdomain)."';
			var socialButtonsEnabled = ".self::$stringShopSocialEnabled.";
			var pageLink = '".get_page_link()."';
			
			</script>";

			echo "<script src='".plugins_url('/js/spreadplugin.js', __FILE__)."'></script>";
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




		function scriptMethod() {
			wp_enqueue_script(
			'infinite_scroll',
			plugins_url('/js/jquery.infinitescroll.min.js', __FILE__),
			array('jquery')
			);

			wp_enqueue_script('infinite_scroll');
		}

		function styleMethod() {
			// Respects SSL, Style.css is relative to the current file
			wp_register_style( 'spreadplugin', plugins_url('/css/spreadplugin.css', __FILE__) );
			wp_enqueue_style( 'spreadplugin' );
		}


		function startSession() {
			if(!session_id()) {
				@session_start();
			}
		}

		function endSession() {
			@session_destroy();
		}


		// gets replaced on facebook button hover
		function socialHead() {
			if (self::$stringShopSocialEnabled==true) echo '
					<meta property="og:title" content="" />
					<meta property="og:url" content="" />
					<meta property="og:image" content="" />
					';
		}

		function socialFooter() {
			if (self::$stringShopSocialEnabled==true) echo '<script src="//connect.facebook.net/'.(!empty(self::$stringShopLocale)?self::$stringShopLocale:'en_US').'/all.js#xfbml=1"></script><script src="//platform.twitter.com/widgets.js"></script>';
		}


	} // END class WP_Spreadplugin

	new WP_Spreadplugin();
} // END if(!class_exists('WP_Spreadplugin'))



?>