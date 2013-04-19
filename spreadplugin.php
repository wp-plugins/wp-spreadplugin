<?php
/**
 * Plugin Name: WP-Spreadplugin
 * Plugin URI: http://wordpress.org/extend/plugins/wp-spreadplugin/
 * Description: This plugin uses the Spreadshirt API to list articles and let your customers order articles of your Spreadshirt shop using Spreadshirt order process.
 * Version: 1.8.2a
 * Author: Thimo Grauerholz
 * Author URI: http://www.pr3ss-play.de
 */
 

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
		private static $shopId = '';
		private static $apiUrl = '';
		private static $shopLocale = '';
		private static $shopLimit = '';
		private static $shopApi = '';
		private static $shopSecret = '';
		private static $shopImgSize = '190';
		private static $shopCategoryId = '';
		private static $shopSocialEnabled = 1;
		private static $shopLinkEnabled = 1;
		private static $shopProductCategory = '';
		private static $shopArticleSort = '';
		private static $shopLinkTarget = '_blank';
		private static $shopCheckoutIframe = 0;
		private static $shopArticleSortOptions = array("name","price","recent");

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
					'shop_sortby' => '',
					'shop_linktarget' => '_blank',
					'shop_checkoutiframe' => 0,
			), $atts);

			self::$shopId = intval($sc['shop_id']);
			self::$shopApi = $sc['shop_api'];
			self::$shopSecret = $sc['shop_secret'];
			self::$shopLimit = intval($sc['shop_limit']);
			self::$shopLocale = $sc['shop_locale'];
			self::$apiUrl = $sc['shop_source'];
			self::$shopCategoryId = intval($sc['shop_category']);
			self::$shopSocialEnabled = intval($sc['shop_social']);
			self::$shopLinkEnabled = intval($sc['shop_enablelink']);
			self::$shopProductCategory = $sc['shop_productcategory'];
			self::$shopArticleSort = $sc['shop_sortby'];
			self::$shopLinkTarget = $sc['shop_linktarget'];
			self::$shopCheckoutIframe = $sc['shop_checkoutiframe'];

			if (isset($_GET['productCategory'])) {
				$c = urldecode($_GET['productCategory']);
				self::$shopProductCategory = $c;
			}
			if (isset($_GET['articleSortBy'])) {
				$c = urldecode($_GET['articleSortBy']);
				self::$shopArticleSort = $c;
			}


			if(!empty(self::$shopId) && !empty(self::$shopApi) && !empty(self::$shopSecret)) {

				if (empty(self::$shopLimit)) self::$shopLimit=20;

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
						$apiUrl = 'http://api.spreadshirt.'.self::$apiUrl.'/api/v1/shops/' . self::$shopId;
						$stringXmlShop = wp_remote_get($apiUrl);
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
							'shopId' => self::$shopId

					);

					/*
					 * add to basket
					*/
					self::addBasketItem($_SESSION['basketUrl'] , $_SESSION['namespaces'] , $data);

				}




				// use pagination value from wordpress
				if(empty($paged)) $paged = 1;

				$offset=($paged-1)*self::$shopLimit;

				$articleData=self::getArticleData();
				$typesData=$articleData['types'];
				unset($articleData['types']);


				// filter
				if (is_array($articleData)) {
					foreach ($articleData as $id => $article) {
						if (!empty(self::$shopProductCategory)&&isset($typesData[self::$shopProductCategory])&&!in_array($article['type'],$typesData[self::$shopProductCategory])) {
							unset($articleData[$id]);
						}
					}
				}
				
				
				
				// sorting
				if (!empty(self::$shopArticleSort) && is_array($articleData) && in_array(self::$shopArticleSort,self::$shopArticleSortOptions)) {
					if (self::$shopArticleSort==="recent") {
						krsort($articleData);
					} else if (self::$shopArticleSort==="price") {
						uasort($articleData,create_function('$a,$b',"return (\$a[pricenet] < \$b[pricenet])?-1:1;"));
					} else {
						uasort($articleData,create_function('$a,$b',"return strnatcmp(\$a[".self::$shopArticleSort."],\$b[".self::$shopArticleSort."]);"));
					}
				}


				// pagination
				if (!empty(self::$shopLimit) && is_array($articleData)) {
					$articleData = array_slice($articleData, $offset, self::$shopLimit, true);
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
					$output .= '<div id="checkout">'.$intInBasket." <a href=".$_SESSION['checkoutUrl']." target=\"".self::$shopLinkTarget."\">".__('Basket', $this->stringTextdomain)."</a></div>";
				} else {
					$output .= '<div id="checkout">'.$intInBasket." <a title=\"".__('Basket is empty', $this->stringTextdomain)."\">".__('Basket', $this->stringTextdomain)."</a></div>";
				}


				$output .= '<select name="productCategory" id="productCategory">';
				$output .= '<option value="">'.__('Product category', $this->stringTextdomain).'</option>';
				if (isset($typesData)) {
					foreach ($typesData as $t => $v) {
						$output .= '<option value="'.urlencode($t).'"'.($t==self::$shopProductCategory?' selected':'').'>'.$t.'</option>';
					}
				}
				$output .= '</select>';

				$output .= '<select name="articleSortBy" id="articleSortBy">';
				$output .= '<option value="">'.__('Sort by', $this->stringTextdomain).'</option>';
				$output .= '<option value="name"'.('name'==self::$shopArticleSort?' selected':'').'>'.__('name', $this->stringTextdomain).'</option>';
				$output .= '<option value="price"'.('price'==self::$shopArticleSort?' selected':'').'>'.__('price', $this->stringTextdomain).'</option>';
				$output .= '<option value="recent"'.('recent'==self::$shopArticleSort?' selected':'').'>'.__('recent', $this->stringTextdomain).'</option>';
				$output .= '</select>';


				// anzeige
				if (count($articleData) == 0 || $articleData==false) {

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
						$output .= (self::$shopLinkEnabled==1?'<a href="http://'.self::$shopId.'.spreadshirt.'.self::$apiUrl.'/-A'.$id.'" target="'.self::$shopLinkTarget.'">':'');
						$output .= '<img src="' . $article['resource0'] . ',width='.self::$shopImgSize.',height='.self::$shopImgSize.'" class="preview" alt="' . $article['name'] . '" id="previewimg_'.$id.'" />';
						$output .= '<img src="' . $article['resource2'] . ',width='.self::$shopImgSize.',height='.self::$shopImgSize.'" class="compositions" style="display:none;" alt="' . $article['name'] . '" id="compositeimg_'.$id.'" title="'.addslashes($article['productdescription']).'" />';
						$output .= (self::$shopLinkEnabled==1?'</a>':'');
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

						if (self::$shopSocialEnabled==true) {
							$output .= '<div class="fb-like" data-href="'.get_page_link().'#'.$id.'" data-send="false" data-layout="button_count" data-width="200" data-show-faces="false" style="width:200px; height:30px"></div>';
							$output .= '<a href="https://twitter.com/share" class="twitter-share-button" data-url="'.get_page_link().'#'.$id.'" data-count="none" data-text="'.(!empty($article['description'])?$article['description']:'Product').'" data-lang="'.(!empty(self::$shopLocale)?substr(self::$shopLocale,0,2):'en').'">Tweet</a>';
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

				$apiUrlBase = 'http://api.spreadshirt.'.self::$apiUrl.'/api/v1/shops/' . self::$shopId;
				$apiUrlBase .= (!empty(self::$shopCategoryId)?'/articleCategories/'.self::$shopCategoryId:'');
				$apiUrlBase .= '/articles?'.(!empty(self::$shopLocale)?'locale=' . self::$shopLocale . '&':'').'fullData=true';

				$apiUrl = $apiUrlBase . '&limit=2'; # &limit='.self::$shopLimit.'&offset='.$offset

				$stringXmlShop = wp_remote_get($apiUrl);
				if (count($stringXmlShop->errors)>0) die('Error getting articles. Please check Shop-ID, API and secret.');
				if ($stringXmlShop['body'][0]!='<') die($stringXmlShop['body']);
				$stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
				$objArticles = new SimpleXmlElement($stringXmlShop);
				if (!is_object($objArticles)) die('Articles not loaded');


				// erneuter call, weil sonst limit bei 50
				// limit bei max 1000 durch Spreadshirt-Limitierung
				$apiUrl = $apiUrlBase . '&limit='.($objArticles['count']==1?2:($objArticles['count']<1000?$objArticles['count']:1000)); # &limit='.self::$shopLimit.'&offset='.$offset

				$stringXmlShop = wp_remote_get($apiUrl);
				if (count($stringXmlShop->errors)>0) die('Error getting articles. Please check Shop-ID, API and secret.');
				if ($stringXmlShop['body'][0]!='<') die($stringXmlShop['body']);
				$stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
				$objArticles = new SimpleXmlElement($stringXmlShop);
				if (!is_object($objArticles)) die('Articles not loaded');


				if ($objArticles['count']>0) {

					// ProductTypeDepartments
					$stringTypeApiUrl = 'http://api.spreadshirt.'.self::$apiUrl.'/api/v1/shops/' . self::$shopId.'/productTypeDepartments?'.(!empty(self::$shopLocale)?'locale=' . self::$shopLocale . '&':'').'fullData=true';
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

						$stringXmlArticle = wp_remote_retrieve_body(wp_remote_get($article->product->productType->attributes('xlink', true).'?'.(!empty(self::$shopLocale)?'locale=' . self::$shopLocale:'')));
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
					<element id="' . $data['articleId'] . '" type="sprd:article" xlink:href="http://api.spreadshirt.'.self::$apiUrl.'/api/v1/shops/' . $data['shopId'] . '/articles/' . $data['articleId'] . '">
					<properties>
					<property key="appearance">' . $data['appearance'] . '</property>
					<property key="size">' . $data['size'] . '</property>
					</properties>
					</element>
					<links>
					<link type="edit" xlink:href="http://' . $data['shopId'] .'.spreadshirt.' .self::$apiUrl.'/-A' . $data['articleId'] . '"/>
					<link type="continueShopping" xlink:href="http://' . $data['shopId'].'.spreadshirt.'.self::$apiUrl.'"/>
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
			$sig = sha1("$data ".self::$shopSecret);

			return "Authorization: SprdAuth apiKey=\"".self::$shopApi."\", data=\"$data\", sig=\"$sig\"";

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
				if ($result[0]=='<') {
					$basket = new SimpleXMLElement($result);
				}
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
			var socialButtonsEnabled = ".self::$shopSocialEnabled.";
			var pageLink = '".get_page_link()."';
			var pageCheckoutUseIframe = ".self::$shopCheckoutIframe.";
			
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
			if (self::$shopSocialEnabled==true) echo '
					<meta property="og:title" content="" />
					<meta property="og:url" content="" />
					<meta property="og:image" content="" />
					';
		}

		function socialFooter() {
			if (self::$shopSocialEnabled==true) echo '
			<script src="//connect.facebook.net/'.(!empty(self::$shopLocale)?self::$shopLocale:'en_US').'/all.js#xfbml=1"></script>
			<script src="//platform.twitter.com/widgets.js"></script>
			';
		}
		
	


	} // END class WP_Spreadplugin

	new WP_Spreadplugin();
} // END if(!class_exists('WP_Spreadplugin'))



?>