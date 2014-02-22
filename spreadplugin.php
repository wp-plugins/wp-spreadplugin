<?php
/**
 * Plugin Name: WP-Spreadplugin
 * Plugin URI: http://wordpress.org/extend/plugins/wp-spreadplugin/
 * Description: This plugin uses the Spreadshirt API to list articles and let your customers order articles of your Spreadshirt shop using Spreadshirt order process.
 * Version: 3.5.3.2
 * Author: Thimo Grauerholz
 * Author URI: http://www.spreadplugin.de
 */


@set_time_limit(0);



/**
 * WP_Spreadplugin class
 */
if(!class_exists('WP_Spreadplugin')) {
	class WP_Spreadplugin {
		private $stringTextdomain = 'spreadplugin';
		public static $shopOptions;
		public static $shopArticleSortOptions = array(
				'name',
				'price',
				'recent',
				'weight'
		);
		public $defaultOptions = array(
				'shop_id' => '',
				'shop_locale' => '',
				'shop_api' => '',
				'shop_source' => '',
				'shop_secret' => '',
				'shop_limit' => '',
				'shop_category' => '',
				'shop_subcategory' => '',
				'shop_social' => '',
				'shop_enablelink' => '',
				'shop_productcategory' => '',
				'shop_sortby' => '',
				'shop_linktarget' => '',
				'shop_checkoutiframe' => '',
				'shop_designershop' => '',
				'shop_display' => '',
				'shop_designsbackground' => '',
				'shop_showdescription' => '',
				'shop_showproductdescription' => '',
				'shop_imagesize' => '',
				'shop_showextendprice' => '',
				'shop_zoomimagebackground' => '',
				'shop_infinitescroll' => '',
				'shop_customcss' => '',
				'shop_design' => '',
				'shop_view' => ''
		);
		private static $shopCache = 8760; // Shop article cache in hours 24*365 => 1 year

		public function WP_Spreadplugin() {
			WP_Spreadplugin::__construct();
		}

		public function __construct() {
			add_action('init', array(&$this,'plugin_init'));
			add_action('init', array(&$this,'startSession'), 1);
			add_action('wp_logout', array(&$this,'endSession'));
			add_action('wp_login', array(&$this,'endSession'));

			add_shortcode('spreadplugin', array($this,'Spreadplugin'));

			// Ajax actions
			add_action('wp_ajax_nopriv_mergeBasket',array(&$this,'mergeBaskets'));
			add_action('wp_ajax_mergeBasket',array(&$this,'mergeBaskets'));
			add_action('wp_ajax_nopriv_myAjax',array(&$this,'doAjax'));
			add_action('wp_ajax_myAjax',array(&$this,'doAjax'));
			add_action('wp_ajax_nopriv_myCart',array(&$this,'doCart'));
			add_action('wp_ajax_myCart',array(&$this,'doCart'));
			add_action('wp_ajax_nopriv_myDelete',array(&$this,'doCartItemDelete'));
			add_action('wp_ajax_myDelete',array(&$this,'doCartItemDelete'));
			add_action('wp_ajax_regenCache',array(&$this,'doRegenerateCache'));

			add_action('wp_enqueue_scripts', array(&$this,'enqueueJs'));
			add_action('wp_head', array(&$this,'loadHead'));


			// admin check
			if(is_admin()){
				// Regenerate cache after activation of the plugin
				register_activation_hook(__FILE__, array(&$this, 'setRegenerateCacheQuery'));

				// add Admin menu
				add_action('admin_menu', array(&$this, 'addPluginPage'));
				// add Plugin settings link
				add_filter('plugin_action_links', array(&$this, 'addPluginSettingsLink'),10,2);

				// add color picker
				wp_enqueue_style('wp-color-picker');
				wp_enqueue_script('wp-color-picker');
			}

		}




		/**
		 * Initialize Plugin
		 */
		public function plugin_init() {

			// get translation
			if(function_exists('load_plugin_textdomain')) {
				load_plugin_textdomain($this->stringTextdomain, false, dirname(plugin_basename( __FILE__ )) . '/translation');
			}

		}


		/**
		 * Function Spreadplugin
		 *
		 * @return string article display
		 *
		 */
		public function Spreadplugin($atts) {

			$articleCleanData = array(); // Array with article informations for sorting and filtering
			$articleData = array();
			$designsData = array();

			add_action('wp_footer', array(&$this,'loadFoot'));

			// get admin options (default option set on admin page)
			$conOp = $this->getAdminOptions();

			// shortcode overwrites admin options (default option set on admin page) if available
			$arrSc = shortcode_atts($this->defaultOptions, $atts);

			// replace options by shortcode if set
			if (!empty($arrSc)) {
				foreach ($arrSc as $key => $option) {
					if ($option != '') {
						$conOp[$key] = $option;
					}
				}
			}


			// setting defaults if needed
			self::$shopOptions = $conOp;
			self::$shopOptions['shop_source'] = (empty($conOp['shop_source'])?'net':$conOp['shop_source']);
			self::$shopOptions['shop_limit'] = (empty($conOp['shop_limit'])?10:intval($conOp['shop_limit']));
			self::$shopOptions['shop_locale'] = (($conOp['shop_locale']=='' || $conOp['shop_locale']=='de_DE') && $conOp['shop_source']=='com'?'us_US':$conOp['shop_locale']); // Workaround for older versions of this plugin
			self::$shopOptions['shop_imagesize'] = (intval($conOp['shop_imagesize'])==0?190:intval($conOp['shop_imagesize']));
			self::$shopOptions['shop_zoomimagebackground'] = (empty($conOp['shop_zoomimagebackground'])?'FFFFFF':str_replace("#", "", $conOp['shop_zoomimagebackground']));
			self::$shopOptions['shop_infinitescroll'] = ($conOp['shop_infinitescroll']==''?1:$conOp['shop_infinitescroll']);
				

			if (isset($_GET['productCategory'])) {
				$c = urldecode($_GET['productCategory']);
				self::$shopOptions['shop_productcategory'] = $c;
				self::$shopOptions['shop_productsubcategory'] = 'all';

				if (!empty($_GET['productSubCategory'])) {
					$c = urldecode($_GET['productSubCategory']);
					self::$shopOptions['shop_productsubcategory'] = $c;
				}
			}
			
			if (!empty(self::$shopOptions['shop_productcategory']) && empty(self::$shopOptions['shop_productsubcategory'])) {
				self::$shopOptions['shop_productsubcategory']="all";
			}
			
			if (isset($_GET['articleSortBy'])) {
				$c = urldecode($_GET['articleSortBy']);
				self::$shopOptions['shop_sortby'] = $c;
			}


			// At filtering articles don't use designs view
			if (self::$shopOptions['shop_display']==1 && self::$shopOptions['shop_productcategory']=='' && self::$shopOptions['shop_design']==0) {
			} else {
				self::$shopOptions['shop_display']=0;
			}


			// check
			if(!empty(self::$shopOptions['shop_id']) && !empty(self::$shopOptions['shop_api']) && !empty(self::$shopOptions['shop_secret'])) {

				$paged = ((int)$_GET['pagesp']>0)?(int)$_GET['pagesp']:1;	

				$offset=($paged-1)*self::$shopOptions['shop_limit'];
				
				
				// get article data
				$articleData=self::getArticleData();
				// get rid of types in array
				$typesData=$articleData['types'];
				unset($articleData['types']);

				// get designs data
				$designsData=self::getDesignsData();

				// built array with articles for sorting and filtering
				if (is_array($designsData)) {
					foreach ($designsData as $designId => $arrDesigns) {
						if (!empty($articleData[$designId])) {
							foreach ($articleData[$designId] as $articleId => $arrArticle) {
								$articleCleanData[$articleId] = $arrArticle;
							}
						}
					}
				}
				
				// Add those articles which have no own designs
				if (isset($articleData) && is_array($articleData[0])) {
					foreach ($articleData[0] as $articleId => $arrArticle) {
						$articleCleanData[$articleId] = $arrArticle;
					}
				}

				// filter
				if (is_array($articleCleanData)) {
					foreach ($articleCleanData as $id => $article) {
						
						// designs
						if (self::$shopOptions['shop_design']>0 && self::$shopOptions['shop_design']!=$articleCleanData[$id]['designid']) {
							unset($articleCleanData[$id]);
						}
						
						// product categories
						if (!empty(self::$shopOptions['shop_productcategory'])&&isset($typesData[self::$shopOptions['shop_productcategory']][self::$shopOptions['shop_productsubcategory']])) {
							if (!isset($typesData[self::$shopOptions['shop_productcategory']][self::$shopOptions['shop_productsubcategory']][$article['type']])) {
								unset($articleCleanData[$id]);
							}
						}
					}
				}


				// default sort
				@uasort($designsData,create_function('$a,$b',"return (\$a[place] > \$b[place])?-1:1;"));
				@uasort($articleCleanData,create_function('$a,$b',"return (\$a[place] < \$b[place])?-1:1;"));


				// sorting
				if (self::$shopOptions['shop_display']==1) {
					if (!empty(self::$shopOptions['shop_sortby']) && is_array($designsData) && in_array(self::$shopOptions['shop_sortby'],self::$shopArticleSortOptions)) {
						if (self::$shopOptions['shop_sortby']=="recent") {
							krsort($designsData);
						} else if (self::$shopOptions['shop_sortby']=="price") {
							uasort($designsData,create_function('$a,$b',"return (\$a[pricenet] < \$b[pricenet])?-1:1;"));
						} else if (self::$shopOptions['shop_sortby']=="weight") {
							uasort($designsData,create_function('$a,$b',"return (\$a[weight] > \$b[weight])?-1:1;"));
						} else {
							uasort($designsData,create_function('$a,$b',"return strnatcmp(\$a[".self::$shopOptions['shop_sortby']."],\$b[".self::$shopOptions['shop_sortby']."]);"));
						}
					}
				} else {
					if (!empty(self::$shopOptions['shop_sortby']) && is_array($articleCleanData) && in_array(self::$shopOptions['shop_sortby'],self::$shopArticleSortOptions)) {
						if (self::$shopOptions['shop_sortby']=="recent") {
							krsort($articleCleanData);
						} else if (self::$shopOptions['shop_sortby']=="price") {
							uasort($articleCleanData,create_function('$a,$b',"return (\$a[pricenet] < \$b[pricenet])?-1:1;"));
						} else if (self::$shopOptions['shop_sortby']=="weight") {
							uasort($articleCleanData,create_function('$a,$b',"return (\$a[weight] > \$b[weight])?-1:1;"));
						} else {
							uasort($articleCleanData,create_function('$a,$b',"return strnatcmp(\$a[".self::$shopOptions['shop_sortby']."],\$b[".self::$shopOptions['shop_sortby']."]);"));
						}
					}
				}


				// pagination
				if (self::$shopOptions['shop_display']==1) {
					if (!empty(self::$shopOptions['shop_limit']) && is_array($designsData)) {
						$cArticleNext = count(array_slice($designsData, $offset+self::$shopOptions['shop_limit'], self::$shopOptions['shop_limit'], true));
						$designsData = array_slice($designsData, $offset, self::$shopOptions['shop_limit'], true);
					}
				} else {
					if (!empty(self::$shopOptions['shop_limit']) && is_array($articleCleanData)) {
						$cArticleNext = count(array_slice($articleCleanData, $offset+self::$shopOptions['shop_limit'], self::$shopOptions['shop_limit'], true));
						$articleCleanData = array_slice($articleCleanData, $offset, self::$shopOptions['shop_limit'], true);
					}
				}
				
				
				
				
				// Start output
				$output = '<div id="spreadplugin-items" class="spreadplugin-items clearfix">';


				// display
				if (count($articleData) == 0 || $articleData==false) {

					$output .= '<br>No articles in Shop';

				} else {
					// Listing product
					if (!isset($_GET['product'])&&intval($_GET['product'])==0) {
	
						// add spreadplugin-menu
						$output .= '<div id="spreadplugin-menu" class="spreadplugin-menu">';
		
						// add product categories
						$output .= '<select name="productCategory" id="productCategory">';
						$output .= '<option value="">'.__('Product category', $this->stringTextdomain).'</option>';
						if (isset($typesData)) {
							foreach ($typesData as $t => $v) {
								$output .= '<option value="'.urlencode($t).'"'.($t==self::$shopOptions['shop_productcategory']?' selected':'').'>'.$t.'</option>';
							}
						}
						$output .= '</select> ';
		
						// simple sub categories
						// @TODO Javascript
						if (isset($_GET['productCategory'])) {
							$output .= '<select name="productSubCategory" id="productSubCategory">';
							$output .= '<option value="all"></option>';
							if (isset($typesData[self::$shopOptions['shop_productcategory']])) {
								@ksort($typesData[self::$shopOptions['shop_productcategory']]);
								unset($typesData[self::$shopOptions['shop_productcategory']]['all']);
								foreach ($typesData[self::$shopOptions['shop_productcategory']] as $t => $v) {
									$output .= '<option value="'.urlencode($t).'"'.($t==self::$shopOptions['shop_productsubcategory']?' selected':'').'>'.$t.'</option>';
								}
							}
							$output .= '</select> ';
						}
		
						// add sorting
						$output .= '<select name="articleSortBy" id="articleSortBy">';
						$output .= '<option value="">'.__('Sort by', $this->stringTextdomain).'</option>';
						$output .= '<option value="name"'.('name'==self::$shopOptions['shop_sortby']?' selected':'').'>'.__('name', $this->stringTextdomain).'</option>';
						$output .= '<option value="price"'.('price'==self::$shopOptions['shop_sortby']?' selected':'').'>'.__('price', $this->stringTextdomain).'</option>';
						$output .= '<option value="recent"'.('recent'==self::$shopOptions['shop_sortby']?' selected':'').'>'.__('recent', $this->stringTextdomain).'</option>';
						$output .= '<option value="weight"'.('weight'==self::$shopOptions['shop_sortby']?' selected':'').'>'.__('weight', $this->stringTextdomain).'</option>';
						$output .= '</select>';
		
						// url not needed here, but just in case if js won't work for some reason
						$output .= '<div id="checkout" class="spreadplugin-checkout"><span></span> <a href="'.$_SESSION['checkoutUrl'].'" target="'.self::$shopOptions['shop_linktarget'].'" id="basketLink" class="spreadplugin-checkout-link">'.__('Basket', $this->stringTextdomain).'</a></div>';
						$output .= '<div id="cart" class="spreadplugin-cart"></div>';
		
						$output .= '</div>';
	


					
						$output .= '<div id="spreadplugin-list">';
	
						// Designs view
						if (self::$shopOptions['shop_display']==1) {
							foreach ($designsData as $designId => $arrDesigns) {
								$bgc = false;
								$addStyle = '';
	
								// Display just Designs with products
								if (!empty($articleData[$designId])) {
	
									// check if designs background is enabled
									if (self::$shopOptions['shop_designsbackground']==1) {
										// fetch first article background color
										@reset($articleData[$designId]);
										$bgcV=$articleData[$designId][key($articleData[$designId])]['default_bgc'];
										$bgcV=str_replace("#", "", $bgcV);
										// calc to hex
										$bgc=$this->hex2rgb($bgcV);
										$addStyle="style=\"background-color:rgba(".$bgc[0].",".$bgc[1].",".$bgc[2].",0.4);\"";
									}
	
									$output .= "<div class=\"spreadplugin-designs\">";
									$output .= $this->displayDesigns($designId,$arrDesigns,$articleData[$designId],$bgc);
									$output .= "<div id=\"designContainer_".$designId."\" class=\"design-container clearfix\" ".$addStyle.">";
										
									if (!empty($articleData[$designId])) {
											
										// default sort
										@uasort($articleData[$designId],create_function('$a,$b',"return (\$a[place] < \$b[place])?-1:1;"));
											
										if (self::$shopOptions['shop_view']==1) {
											foreach ($articleData[$designId] as $articleId => $arrArticle) {
												$output .= $this->displayListArticles($articleId,$arrArticle,self::$shopOptions['shop_zoomimagebackground']);
											}
										} else {
											foreach ($articleData[$designId] as $articleId => $arrArticle) {
												$output .= $this->displayArticles($articleId,$arrArticle,self::$shopOptions['shop_zoomimagebackground']); 									
											}
										}
										
									}
	
									$output .= "</div>";
									$output .= "</div>";
								}
							}
						} else {
							// Article view
							if (!empty($articleCleanData)) {
								if (self::$shopOptions['shop_view']==1) {
									foreach ($articleCleanData as $articleId => $arrArticle) {
										$output .= $this->displayListArticles($articleId,$arrArticle,self::$shopOptions['shop_zoomimagebackground']);
									}
								} else {
									foreach ($articleCleanData as $articleId => $arrArticle) {
										$output .= $this->displayArticles($articleId,$arrArticle,self::$shopOptions['shop_zoomimagebackground']);
									}
								}
							}
						}
	
	
						$output .= '</div>';
						
						
						$output .= "<div id=\"pagination\">";
						if ($cArticleNext>0) {
							$output .= "<a href=\"".add_query_arg( 'pagesp', $paged+1)."\">".__('next', $this->stringTextdomain)."</a>";
						}
						$output .= "</div>";
				
					
					} else {
						
						// display product page
						$output .= '<div id="spreadplugin-list">';
						
						// checkout
						// add simple spreadplugin-menu
						$output .= '<div id="spreadplugin-menu" class="spreadplugin-menu">';
						$output .= '<a href="'.get_page_link().'">'.__('Back', $this->stringTextdomain);
						$output .= '<div id="checkout" class="spreadplugin-checkout"><span></span> <a href="'.$_SESSION['checkoutUrl'].'" target="'.self::$shopOptions['shop_linktarget'].'" id="basketLink" class="spreadplugin-checkout-link">'.__('Basket', $this->stringTextdomain).'</a></div>';
						$output .= '<div id="cart" class="spreadplugin-cart"></div>';
						$output .= '</div>';

						// product
						if (!empty($articleCleanData[intval($_GET['product'])])) {
							$output .= $this->displayDetailPage(intval($_GET['product']),$articleCleanData[intval($_GET['product'])],self::$shopOptions['shop_zoomimagebackground']);
						}
						
						$output .= '</div>';
					}
					
				}

				// footer
				$output .= "<!-- <div id=\"copyright\"><a href=\"http://lovetee.de\">lovetee - we love t-shirts</a></div> -->";

				$output .= '</div>';

				return $output;

			}
		}


		/**
		 * Function getArticleData
		 *
		 * Retrieves article data and save into cache
		 *
		 * @return array Article data
		 */
		private static function getArticleData() {
			$arrTypes=array();

			// retrieve id of post to save as different content, if shortcode is available in more than one post (more than one shop in the wordpress website)
			$articleData = get_transient('spreadplugin2-article-cache-'.get_the_ID());

			if($articleData === false) {

				$apiUrlBase = 'http://api.spreadshirt.'.self::$shopOptions['shop_source'].'/api/v1/shops/' . self::$shopOptions['shop_id'];
				$apiUrlBase .= (!empty(self::$shopOptions['shop_category'])?'/articleCategories/'.self::$shopOptions['shop_category']:'');
				$apiUrlBase .= '/articles?'.(!empty(self::$shopOptions['shop_locale'])?'locale=' . self::$shopOptions['shop_locale'] . '&':'').'fullData=true&noCache=true';

				// call first to get count of articles
				$apiUrl = $apiUrlBase . '&limit='.rand(2,999); // randomize to avoid spreadshirt caching issues

				$stringXmlShop = wp_remote_get($apiUrl);
				if (count($stringXmlShop->errors)>0) die('Error getting articles. Please check Shop-ID, API and secret.');
				if ($stringXmlShop['body'][0]!='<') die($stringXmlShop['body']);
				$stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
				$objArticles = new SimpleXmlElement($stringXmlShop);
				if (!is_object($objArticles)) die('Articles not loaded');

				// re-call to read articles with count
				// read max 1000 articles because of spreadshirt max. limit
				$apiUrl = $apiUrlBase . '&limit='.($objArticles['count']<=1?2:($objArticles['count']<1000?$objArticles['count']:1000));

				$stringXmlShop = wp_remote_get($apiUrl);
				if (count($stringXmlShop->errors)>0) die('Error getting articles. Please check your Shop-ID.');
				if ($stringXmlShop['body'][0]!='<') die($stringXmlShop['body']);
				$stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
				$objArticles = new SimpleXmlElement($stringXmlShop);
				if (!is_object($objArticles)) die('Articles not loaded');


				if ($objArticles['count']>0) {

					// ProductTypeDepartments
					$stringTypeApiUrl = 'http://api.spreadshirt.'.self::$shopOptions['shop_source'].'/api/v1/shops/' . self::$shopOptions['shop_id'].'/productTypeDepartments?'.(!empty(self::$shopOptions['shop_locale'])?'locale=' . self::$shopOptions['shop_locale'] . '&':'').'fullData=true&noCache=true';
					$stringTypeXml = wp_remote_get($stringTypeApiUrl);
					$stringTypeXml = wp_remote_retrieve_body($stringTypeXml);
					$objTypes = new SimpleXmlElement($stringTypeXml);

					if (is_object($objTypes)) {
						foreach ($objTypes->productTypeDepartment as $row) {
							foreach ($row->categories->category as $subrow) {
								foreach ($subrow->productTypes as $subrow2) {
									foreach ($subrow2->productType as $subrow3) {
										$arrTypes[(string)$row->name][(string)$subrow->name][(int)$subrow3['id']] = 1;
										$arrTypes[(string)$row->name]['all'][(int)$subrow3['id']] = 1;
									}
								}
							}
						}
					}

					$articleData['types'] = $arrTypes;


					// read articles
					$i=0;
					foreach ($objArticles->article as $article) {
						
						$stockstates_size=array();
						$stockstates_appearance=array();

						$stringXmlArticle = wp_remote_retrieve_body(wp_remote_get($article->product->productType->attributes('xlink', true).'?'.(!empty(self::$shopOptions['shop_locale'])?'locale=' . self::$shopOptions['shop_locale'].'&noCache=true':'?noCache=true')));
						if(substr($stringXmlArticle, 0, 5) !== "<?xml") continue;
						$objArticleData = new SimpleXmlElement($stringXmlArticle);
						$stringXmlCurreny = wp_remote_retrieve_body(wp_remote_get($article->price->currency->attributes('http://www.w3.org/1999/xlink')));
						if(substr($stringXmlArticle, 0, 5) !== "<?xml") continue;
						$objCurrencyData = new SimpleXmlElement($stringXmlCurreny);

						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['name']=(string)$article->name;
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['description']=(string)$article->description;
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['appearance']=(int)$article->product->appearance['id'];
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['view']=(int)$article->product->defaultValues->defaultView['id'];
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['type']=(int)$article->product->productType['id'];
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['productId']=(int)$article->product['id'];
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['pricenet']=(float)$article->price->vatExcluded;
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['pricebrut']=(float)$article->price->vatIncluded;
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['currencycode']=(string)$objCurrencyData->isoCode;
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['productname']=(string)$objArticleData->name;
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['productdescription']=(string)$objArticleData->description;
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['weight']=(float)$article['weight'];
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['id']=(int)$article['id'];
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['place']=$i;
						$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['designid']=(int)$article->product->defaultValues->defaultDesign['id'];


						/**
						* Stock States disabled for the moment - the informations provided by spreadshirt aren't such save as needed
						**
						
						// Assignment of stock availability and matching to articles
						// echo (string)$article->name."<br>";
						foreach($objArticleData->stockStates->stockState as $val) {
							$stockstates_size[(int)$val->size['id']]=(string)$val->available;
							$stockstates_appearance[(int)$val->appearance['id']]=(string)$val->available;
						}
						
						foreach($objArticleData->sizes->size as $val) {
							// echo (int)$val['id']." ".$stockstates_size[(int)$val['id']]." ". (string)$val->name."<br>";
							if ($stockstates_size[(int)$val['id']] == "true") {
								$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['sizes'][(int)$val['id']]=(string)$val->name;
							}
						}

						foreach($objArticleData->appearances->appearance as $appearance) {
							if ((int)$article->product->appearance['id'] == (int)$appearance['id']) {
								$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['default_bgc'] = (string)$appearance->colors->color;
							}

							// echo (int)$val['id']." ".$stockstates_appearance[(int)$val['id']]." ". (string)$appearance->resources->resource->attributes('xlink', true)."<br>";
							if (($article->product->restrictions->freeColorSelection == 'true' && $stockstates_appearance[(int)$appearance['id']] == "true") || (int)$article->product->appearance['id'] == (int)$appearance['id']) {
								$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['appearances'][(int)$appearance['id']]=(string)$appearance->resources->resource->attributes('xlink', true);
							}
						}
						*/
					
					
						// replace this lines with above, if stock states needed	
						foreach($objArticleData->sizes->size as $val) {
							$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['sizes'][(int)$val['id']]=(string)$val->name;
						}

						foreach($objArticleData->appearances->appearance as $appearance) {
							if ((int)$article->product->appearance['id'] == (int)$appearance['id']) {
								$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['default_bgc'] = (string)$appearance->colors->color;
							}

							if ($article->product->restrictions->freeColorSelection == 'true' || (int)$article->product->appearance['id'] == (int)$appearance['id']) {
								$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['appearances'][(int)$appearance['id']]=(string)$appearance->resources->resource->attributes('xlink', true);
							}
						}
						// replace end
					
					
					

						foreach($objArticleData->views->view as $view) {
							$articleData[(int)$article->product->defaultValues->defaultDesign['id']][(int)$article['id']]['views'][(int)$view['id']]=(string)$article->resources->resource->attributes('xlink', true);
						}

						$i++;
					}

					set_transient('spreadplugin2-article-cache-'.get_the_ID(), $articleData, self::$shopCache*3600);
				}
			}

			return $articleData;
		}





		/**
		 * Function getDesignsData
		 *
		 * Retrieves design data and save into cache
		 *
		 * @return array designs data
		 */
		private static function getDesignsData() {
			$arrTypes=array();

			// retrieve id of post to save as different content, if shortcode is available in more than one post (more than one shop in the wordpress website)
			$articleData = get_transient('spreadplugin2-designs-cache-'.get_the_ID());

			if($articleData === false) {

				$apiUrlBase = 'http://api.spreadshirt.'.self::$shopOptions['shop_source'].'/api/v1/shops/' . self::$shopOptions['shop_id'];
				//$apiUrlBase .= (!empty(self::$shopOptions['shop_category'])?'/articleCategories/'.self::$shopOptions['shop_category']:'');
				$apiUrlBase .= '/designs?'.(!empty(self::$shopOptions['shop_locale'])?'locale=' . self::$shopOptions['shop_locale'] . '&':'').'fullData=true&noCache=true';

				// call first to get count of articles
				$apiUrl = $apiUrlBase . '&limit='.rand(2,999); // randomize to avoid spreadshirt caching issues

				$stringXmlShop = wp_remote_get($apiUrl);
				if (count($stringXmlShop->errors)>0) die('Error getting articles. Please check Shop-ID, API and secret.');
				if ($stringXmlShop['body'][0]!='<') die($stringXmlShop['body']);
				$stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
				$objArticles = new SimpleXmlElement($stringXmlShop);
				if (!is_object($objArticles)) die('Articles not loaded');

				// re-call to read articles with count
				// read max 1000 articles because of spreadshirt max. limit
				$apiUrl = $apiUrlBase . '&limit='.($objArticles['count']<=1?2:($objArticles['count']<1000?$objArticles['count']:1000));

				$stringXmlShop = wp_remote_get($apiUrl);
				if (count($stringXmlShop->errors)>0) die('Error getting articles. Please check your Shop-ID.');
				if ($stringXmlShop['body'][0]!='<') die($stringXmlShop['body']);
				$stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
				$objArticles = new SimpleXmlElement($stringXmlShop);
				if (!is_object($objArticles)) die('Articles not loaded');


				if ($objArticles['count']>0) {

					// read articles
					$i=0;
					foreach ($objArticles->design as $article) {

						$articleData[(int)$article['id']]['name']=(string)$article->name;
						$articleData[(int)$article['id']]['description']=(string)$article->description;
						$articleData[(int)$article['id']]['appearance']=(int)$article->product->appearance['id'];
						$articleData[(int)$article['id']]['view']=(int)$article->product->defaultValues->defaultView['id'];
						$articleData[(int)$article['id']]['type']=(int)$article->product->productType['id'];
						$articleData[(int)$article['id']]['productId']=(int)$article->product['id'];
						$articleData[(int)$article['id']]['pricenet']=(float)$article->price->vatExcluded;
						$articleData[(int)$article['id']]['pricebrut']=(float)$article->price->vatIncluded;
						$articleData[(int)$article['id']]['currencycode']=(string)$objCurrencyData->isoCode;
						$articleData[(int)$article['id']]['resource0']=(string)$article->resources->resource[0]->attributes('xlink', true);
						$articleData[(int)$article['id']]['resource2']=(string)$article->resources->resource[1]->attributes('xlink', true);
						$articleData[(int)$article['id']]['productdescription']=(string)$objArticleData->description;
						$articleData[(int)$article['id']]['weight']=(float)$article['weight'];
						$articleData[(int)$article['id']]['place']=$i;
						$articleData[(int)$article['id']]['designid']=(int)$article['id'];

						$i++;
					}

					set_transient('spreadplugin2-designs-cache-'.get_the_ID(), $articleData, self::$shopCache*3600);
				}
			}

			return $articleData;
		}



		/**
		 * Function displayArticles
		 *
		 * Displays the articles
		 *
		 * @return html
		 */
		private function displayArticles($id,$article,$backgroundColor='') {

			$output = '<div class="spreadplugin-article clearfix" id="article_'.$id.'" style="width:'.(self::$shopOptions['shop_imagesize']+7).'px">';
			$output .= '<a name="'.$id.'"></a>';
			$output .= '<h3>'.htmlspecialchars($article['name'],ENT_QUOTES).'</h3>';
			$output .= '<form method="post" id="form_'.$id.'">';
				
			// edit article button
			if (self::$shopOptions['shop_designershop']>0) {
				$output .= ' <div class="edit-wrapper"><a href="//'.self::$shopOptions['shop_designershop'].'.spreadshirt.'.self::$shopOptions['shop_source'].'/-D1/customize/product/'.$article['productId'].'?noCache=true" target="'.self::$shopOptions['shop_linktarget'].'" title="'.__('Edit article', $this->stringTextdomain).'"><img src="'.plugins_url('/img/edit.png', __FILE__).'"></a></div>';
			}
				
			// display preview image
			$output .= '<div class="image-wrapper">';
			$output .= '<img src="'.plugins_url('/img/blank.gif', __FILE__).'" alt="' . htmlspecialchars($article['name'],ENT_QUOTES) . '" id="previewimg_'.$id.'" data-zoom-image="http://image.spreadshirt.'.self::$shopOptions['shop_source'].'/image-server/v1/products/'.$article['productId'].'/views/'.$article['view'].',width=800,height=800'.(!empty($backgroundColor)?',backgroundColor='.$backgroundColor:'').'" class="preview lazyimg" data-original="http://image.spreadshirt.'.self::$shopOptions['shop_source'].'/image-server/v1/products/'.$article['productId'].'/views/'.$article['view'].',width='.self::$shopOptions['shop_imagesize'].',height='.self::$shopOptions['shop_imagesize'].'" />';
			$output .= '</div>';

			// add a select with available sizes
			if (isset($article['sizes'])&&is_array($article['sizes'])) {
				$output .= '<select id="size-select" name="size">';

				foreach($article['sizes'] as $k => $v) {
					$output .= '<option value="'.$k.'">'.$v.'</option>';
				}

				$output .= '</select>';
			}


			if (self::$shopOptions['shop_enablelink']==1) {
				$output .= '<div class="details-wrapper2 clearfix"><a href="'.add_query_arg('product',$id,get_permalink()).'" target="'.self::$shopOptions['shop_linktarget'].'">'.__('Details', $this->stringTextdomain).'</a></div>';
			}

			$output .= '<div class="separator"></div>';

			// add a list with availabel product colors
			if (isset($article['appearances'])&&is_array($article['appearances'])) {
				$output .= '<ul class="colors" name="color">';

				foreach($article['appearances'] as $k=>$v) {
					$output .= '<li value="'.$k.'"><img src="'. $this->cleanURL($v) .'" alt="" /></li>';
				}

				$output .= '</ul>';
			}


			// add a list with available product views
			if (isset($article['views'])&&is_array($article['views'])) {
				$output .= '<ul class="views" name="views">';
				
				$_vc=0;
				foreach($article['views'] as $k=>$v) {
					$output .= '<li value="'.$k.'"><img src="'.plugins_url('/img/blank.gif', __FILE__).'" data-original="'. $this->cleanURL($v)  .',viewId='.$k.',width=42,height=42" class="previewview lazyimg" alt="" id="viewimg_'.$id.'" /></li>';
					if ($_vc==3) break;
					$_vc++;
				}

				$output .= '</ul>';
			}

			// Short product description
			$output .= '<div class="separator"></div>';
			$output .= '<div class="product-name">';
			$output .= htmlspecialchars($article['productname'],ENT_QUOTES);
			$output .= '</div>';

			// Show description link if not empty
			if (!empty($article['description'])) {
				$output .= '<div class="separator"></div>';

				if (self::$shopOptions['shop_showdescription']==0) {
					$output .= '<div class="description-wrapper"><div class="header"><a>'.__('Show article description', $this->stringTextdomain).'</a></div><div class="description">'.htmlspecialchars($article['description'],ENT_QUOTES).'</div></div>';
				} else {
					$output .= '<div class="description-wrapper">'.htmlspecialchars($article['description'],ENT_QUOTES).'</div>';
				}
			}
			
			// Show product description link if set
			if (self::$shopOptions['shop_showproductdescription']==1) {
				$output .= '<div class="separator"></div>';

				if (self::$shopOptions['shop_showdescription']==0) {
					$output .= '<div class="product-description-wrapper"><div class="header"><a>'.__('Show product description', $this->stringTextdomain).'</a></div><div class="description">'.$article['productdescription'].'</div></div>';
				} else {
					$output .= '<div class="product-description-wrapper">'.$article['productdescription'].'</div>';
				}
			}

			$output .= '<input type="hidden" value="'. $article['appearance'] .'" id="appearance" name="appearance" />';
			$output .= '<input type="hidden" value="'. $article['view'] .'" id="view" name="view" />';
			$output .= '<input type="hidden" value="'. $id .'" id="article" name="article" />';

			$output .= '<div class="separator"></div>';
			$output .= '<div class="price-wrapper">';
			if (self::$shopOptions['shop_showextendprice']==1) {
				$output .= '<span id="price-without-tax">'.__('Price (without tax):', $this->stringTextdomain)." ".(empty(self::$shopOptions['shop_locale']) || self::$shopOptions['shop_locale']=='en_US' || self::$shopOptions['shop_locale']=='en_GB' || self::$shopOptions['shop_locale']=='us_US' || self::$shopOptions['shop_locale']=='us_CA' || self::$shopOptions['shop_locale']=='fr_CA'?$article['currencycode']." ".number_format($article['pricenet'],2,'.',''):number_format($article['pricenet'],2,',','.')." ".$article['currencycode'])."<br /></span>";
				$output .= '<span id="price-with-tax">'.__('Price (with tax):', $this->stringTextdomain)." ".(empty(self::$shopOptions['shop_locale']) || self::$shopOptions['shop_locale']=='en_US' || self::$shopOptions['shop_locale']=='en_GB' || self::$shopOptions['shop_locale']=='us_US' || self::$shopOptions['shop_locale']=='us_CA' || self::$shopOptions['shop_locale']=='fr_CA'?$article['currencycode']." ".number_format($article['pricebrut'],2,'.',''):number_format($article['pricebrut'],2,',','.')." ".$article['currencycode'])."</span>";
				$output .= '<br><div class="additionalshippingcosts">';
				$output .= __('excl. Shipping', $this->stringTextdomain);
				$output .= '</div>';
			} else {
				$output .= '<span id="price">'.__('Price:', $this->stringTextdomain)." ".(empty(self::$shopOptions['shop_locale']) || self::$shopOptions['shop_locale']=='en_US' || self::$shopOptions['shop_locale']=='en_GB' || self::$shopOptions['shop_locale']=='us_US' || self::$shopOptions['shop_locale']=='us_CA' || self::$shopOptions['shop_locale']=='fr_CA'?$article['currencycode']." ".number_format($article['pricebrut'],2,'.',''):number_format($article['pricebrut'],2,',','.')." ".$article['currencycode'])."</span>";
			}
			$output .= '</div>';
			
			// order buttons
			$output .= '<input type="text" value="1" id="quantity" name="quantity" maxlength="4" />';
			$output .= '<input type="submit" name="submit" value="'.__('Add to basket', $this->stringTextdomain).'" /><br>';

			// Social buttons
			if (self::$shopOptions['shop_social']==true) {
				$output .= '
				<ul class="soc-icons">
				<li><a target="_blank" data-color="#5481de" class="fb" href="//www.facebook.com/sharer.php?u='.urlencode(add_query_arg( 'product', $id, get_permalink())).'&t='.rawurlencode(get_the_title()).'" title="Facebook"></a></li>
				<li><a target="_blank" data-color="#06ad18" class="goog" href="//plus.google.com/share?url='.urlencode(add_query_arg( 'product', $id, get_permalink())).'" title="Google"></a></li>
				<li><a target="_blank" data-color="#2cbbea" class="twt" href="//twitter.com/home?status='.rawurlencode(get_the_title()).' - '.urlencode(add_query_arg( 'product', $id, get_permalink())).'" title="Twitter"></a></li>
				<li><a target="_blank" data-color="#e84f61" class="pin" href="//pinterest.com/pin/create/button/?url='.rawurlencode(add_query_arg( 'product', $id, get_permalink())).'&media='.rawurlencode('http://image.spreadshirt.'.self::$shopOptions['shop_source'].'/image-server/v1/products/'.$article['productId'].'/views/'.$article['view'].',width='.self::$shopOptions['shop_imagesize'].',height='.self::$shopOptions['shop_imagesize'].'').',width='.self::$shopOptions['shop_imagesize'].',height='.self::$shopOptions['shop_imagesize'].'&description='.(!empty($article['description'])?htmlspecialchars($article['description'],ENT_QUOTES):'Product').'" title="Pinterest"></a></li>
				</ul>
				';

				/*
					<li><a target="_blank" data-color="#459ee9" class="in" href="#" title="LinkedIn"></a></li>
				<li><a target="_blank" data-color="#ee679b" class="drb" href="#" title="Dribbble"></a></li>
				<li><a target="_blank" data-color="#4887c2" class="tumb" href="#" title="Tumblr"></a></li>
				<li><a target="_blank" data-color="#f23a94" class="flick" href="#" title="Flickr"></a></li>
				<li><a target="_blank" data-color="#74c3dd" class="vim" href="#" title="Vimeo"></a></li>
				<li><a target="_blank" data-color="#4a79ff" class="delic" href="#" title="Delicious"></a></li>
				<li><a target="_blank" data-color="#6ea863" class="forr" href="#" title="Forrst"></a></li>
				<li><a target="_blank" data-color="#f6a502" class="hi5" href="#" title="Hi5"></a></li>
				<li><a target="_blank" data-color="#e3332a" class="last" href="#" title="Last.fm"></a></li>
				<li><a target="_blank" data-color="#3c6ccc" class="space" href="#" title="Myspace"></a></li>
				<li><a target="_blank" data-color="#229150" class="newsv" href="#" title="Newsvine"></a></li>
				<li><a href="#" class="pica" title="Picasa" data-color="#b163c8" target="_blank"></a></li>
				<li><a href="#" class="tech" title="Technorati" data-color="#3ac13a" target="_blank"></a></li>
				<li><a href="#" class="rss" title="RSS" data-color="#f18d3c" target="_blank"></a></li>
				<li><a href="#" class="rdio" title="Rdio" data-color="#2c7ec7" target="_blank"></a></li>
				<li><a href="#" class="share" title="ShareThis" data-color="#359949" target="_blank"></a></li>
				<li><a href="#" class="skyp" title="Skype" data-color="#00adf1" target="_blank"></a></li>
				<li><a href="#" class="slid" title="SlideShare" data-color="#ef8122" target="_blank"></a></li>
				<li><a href="#" class="squid" title="Squidoo" data-color="#f87f27" target="_blank"></a></li>
				<li><a href="#" class="stum" title="StumbleUpon" data-color="#f05c38" target="_blank"></a></li>
				<li><a href="#" class="what" title="WhatsApp" data-color="#3ebe2b" target="_blank"></a></li>
				<li><a href="#" class="wp" title="Wordpress" data-color="#3078a9" target="_blank"></a></li>
				<li><a href="#" class="ytb" title="Youtube" data-color="#df3434" target="_blank"></a></li>
				<li><a href="#" class="digg" title="Digg" data-color="#326ba0" target="_blank"></a></li>
				<li><a href="#" class="beh" title="Behance" data-color="#2d9ad2" target="_blank"></a></li>
				<li><a href="#" class="yah" title="Yahoo" data-color="#883890" target="_blank"></a></li>
				<li><a href="#" class="blogg" title="Blogger" data-color="#f67928" target="_blank"></a></li>
				<li><a href="#" class="hype" title="Hype Machine" data-color="#f13d3d" target="_blank"></a></li>
				<li><a href="#" class="groove" title="Grooveshark" data-color="#498eba" target="_blank"></a></li>
				<li><a href="#" class="sound" title="SoundCloud" data-color="#f0762c" target="_blank"></a></li>
				<li><a href="#" class="insta" title="Instagram" data-color="#c2784e" target="_blank"></a></li>
				<li><a href="#" class="vk" title="Vkontakte" data-color="#5f84ab" target="_blank"></a></li>
				*/
			}

			$output .= '
					</form>
					</div>';

			return $output;

		}



		/**
		 * Function displayListArticles
		 *
		 * Displays the articles
		 *
		 * @return html
		 */
		private function displayListArticles($id,$article,$backgroundColor='') {

			$output = '<div class="spreadplugin-article list" id="article_'.$id.'">';
			$output .= '<a name="'.$id.'"></a>';
			$output .= '<form method="post" id="form_'.$id.'"><div class="articleContentLeft">';
				
			// edit article button
			if (self::$shopOptions['shop_designershop']>0) {
				$output .= ' <div class="edit-wrapper"><a href="//'.self::$shopOptions['shop_designershop'].'.spreadshirt.'.self::$shopOptions['shop_source'].'/-D1/customize/product/'.$article['productId'].'?noCache=true" target="'.self::$shopOptions['shop_linktarget'].'" title="'.__('Edit article', $this->stringTextdomain).'"><img src="'.plugins_url('/img/edit.png', __FILE__).'"></a></div>';
			}
				
			// display preview image
			$output .= '<div class="image-wrapper">';
			$output .= '<img src="'.plugins_url('/img/blank.gif', __FILE__).'" alt="' . htmlspecialchars($article['name'],ENT_QUOTES) . '" id="previewimg_'.$id.'" data-zoom-image="http://image.spreadshirt.'.self::$shopOptions['shop_source'].'/image-server/v1/products/'.$article['productId'].'/views/'.$article['view'].',width=800,height=800'.(!empty($backgroundColor)?',backgroundColor='.$backgroundColor:'').'" class="preview lazyimg" data-original="http://image.spreadshirt.'.self::$shopOptions['shop_source'].'/image-server/v1/products/'.$article['productId'].'/views/'.$article['view'].',width='.self::$shopOptions['shop_imagesize'].',height='.self::$shopOptions['shop_imagesize'].'" />';
			$output .= '</div>';

			// Short product description
			$output .= '<div class="product-name">';
			$output .= htmlspecialchars($article['productname'],ENT_QUOTES);
			$output .= '</div>';
			
			if (self::$shopOptions['shop_enablelink']==1) {
				$output .= '<div class="details-wrapper2 clearfix"><a href="'.add_query_arg('product',$id,get_permalink()).'" target="'.self::$shopOptions['shop_linktarget'].'">'.__('Details', $this->stringTextdomain).'</a></div>';
			}

			$output .= '</div><div class="articleContentRight"><h3>'.htmlspecialchars($article['name'],ENT_QUOTES).'</h3>';


			// Show description link if not empty
			if (!empty($article['description'])) {
				if (self::$shopOptions['shop_showdescription']==0) {
					$output .= '<div class="description-wrapper"><div class="header"><a>'.__('Show article description', $this->stringTextdomain).'</a></div><div class="description">'.htmlspecialchars($article['description'],ENT_QUOTES).'</div></div>';
				} else {
					$output .= '<div class="description-wrapper">'.htmlspecialchars($article['description'],ENT_QUOTES).'</div>';
				}
			}
					

			// add a select with available sizes
			if (isset($article['sizes'])&&is_array($article['sizes'])) {
				$output .= '<div class="size-wrapper clearfix">'.__('Size', $this->stringTextdomain).': <select id="size-select" name="size">';

				foreach($article['sizes'] as $k => $v) {
					$output .= '<option value="'.$k.'">'.$v.'</option>';
				}

				$output .= '</select></div>';
			}


			// add a list with availabel product colors
			if (isset($article['appearances'])&&is_array($article['appearances'])) {
				$output .= '<div class="color-wrapper clearfix">'.__('Color', $this->stringTextdomain).': <ul class="colors" name="color">';

				foreach($article['appearances'] as $k=>$v) {
					$output .= '<li value="'.$k.'"><img src="'. $this->cleanURL($v) .'" alt="" /></li>';
				}

				$output .= '</ul></div>';
			}


			// add a list with available product views
			if (isset($article['views'])&&is_array($article['views'])) {
				$output .= '<div class="views-wrapper clearfix"><ul class="views" name="views">';
				
				$_vc=0;
				foreach($article['views'] as $k=>$v) {
					$output .= '<li value="'.$k.'"><img src="'.plugins_url('/img/blank.gif', __FILE__).'" data-original="'. $this->cleanURL($v)  .',viewId='.$k.',width=42,height=42" class="previewview lazyimg" alt="" id="viewimg_'.$id.'" /></li>';
					if ($_vc==3) break;
					$_vc++;
				}

				$output .= '</ul></div>';
			}



			$output .= '<input type="hidden" value="'. $article['appearance'] .'" id="appearance" name="appearance" />';
			$output .= '<input type="hidden" value="'. $article['view'] .'" id="view" name="view" />';
			$output .= '<input type="hidden" value="'. $id .'" id="article" name="article" />';

			$output .= '<div class="price-wrapper clearfix">';
			if (self::$shopOptions['shop_showextendprice']==1) {
				$output .= '<span id="price-without-tax">'.__('Price (without tax):', $this->stringTextdomain)." ".(empty(self::$shopOptions['shop_locale']) || self::$shopOptions['shop_locale']=='en_US' || self::$shopOptions['shop_locale']=='en_GB' || self::$shopOptions['shop_locale']=='us_US' || self::$shopOptions['shop_locale']=='us_CA' || self::$shopOptions['shop_locale']=='fr_CA'?$article['currencycode']." ".number_format($article['pricenet'],2,'.',''):number_format($article['pricenet'],2,',','.')." ".$article['currencycode'])."<br /></span>";
				$output .= '<span id="price-with-tax">'.__('Price (with tax):', $this->stringTextdomain)." ".(empty(self::$shopOptions['shop_locale']) || self::$shopOptions['shop_locale']=='en_US' || self::$shopOptions['shop_locale']=='en_GB' || self::$shopOptions['shop_locale']=='us_US' || self::$shopOptions['shop_locale']=='us_CA' || self::$shopOptions['shop_locale']=='fr_CA'?$article['currencycode']." ".number_format($article['pricebrut'],2,'.',''):number_format($article['pricebrut'],2,',','.')." ".$article['currencycode'])."</span>";
				$output .= '<br><div class="additionalshippingcosts">';
				$output .= __('excl. Shipping', $this->stringTextdomain);
				$output .= '</div>';
			} else {
				$output .= '<span id="price">'.__('Price:', $this->stringTextdomain)." ".(empty(self::$shopOptions['shop_locale']) || self::$shopOptions['shop_locale']=='en_US' || self::$shopOptions['shop_locale']=='en_GB' || self::$shopOptions['shop_locale']=='us_US' || self::$shopOptions['shop_locale']=='us_CA' || self::$shopOptions['shop_locale']=='fr_CA'?$article['currencycode']." ".number_format($article['pricebrut'],2,'.',''):number_format($article['pricebrut'],2,',','.')." ".$article['currencycode'])."</span>";
			}
			$output .= '</div>';
			
			// order buttons
			$output .= '<input type="text" value="1" id="quantity" name="quantity" maxlength="4" />';
			$output .= '<input type="submit" name="submit" value="'.__('Add to basket', $this->stringTextdomain).'" /><br>';

			// Social buttons
			if (self::$shopOptions['shop_social']==true) {
				$output .= '
				<ul class="soc-icons">
				<li><a target="_blank" data-color="#5481de" class="fb" href="//www.facebook.com/sharer.php?u='.urlencode(add_query_arg( 'product', $id, get_permalink())).'&t='.rawurlencode(get_the_title()).'" title="Facebook"></a></li>
				<li><a target="_blank" data-color="#06ad18" class="goog" href="//plus.google.com/share?url='.urlencode(add_query_arg( 'product', $id, get_permalink())).'" title="Google"></a></li>
				<li><a target="_blank" data-color="#2cbbea" class="twt" href="//twitter.com/home?status='.rawurlencode(get_the_title()).' - '.urlencode(add_query_arg( 'product', $id, get_permalink())).'" title="Twitter"></a></li>
				<li><a target="_blank" data-color="#e84f61" class="pin" href="//pinterest.com/pin/create/button/?url='.rawurlencode(add_query_arg( 'product', $id, get_permalink())).'&media='.rawurlencode('http://image.spreadshirt.'.self::$shopOptions['shop_source'].'/image-server/v1/products/'.$article['productId'].'/views/'.$article['view'].',width='.self::$shopOptions['shop_imagesize'].',height='.self::$shopOptions['shop_imagesize'].'').',width='.self::$shopOptions['shop_imagesize'].',height='.self::$shopOptions['shop_imagesize'].'&description='.(!empty($article['description'])?htmlspecialchars($article['description'],ENT_QUOTES):'Product').'" title="Pinterest"></a></li>
				</ul>
				';

			}

			$output .= '
			</div>
			</form>
			</div>';

			return $output;

		}



		/**
		 * Function displayDesigns
		 *
		 * Displays the designs
		 *
		 * @return html
		 */
		private function displayDesigns($id,$designData,$articleData,$bgc=false) {

			$addStyle = '';
			if ($bgc) $addStyle='style="background-color:rgba('.$bgc[0].','.$bgc[1].','.$bgc[2].',0.4);"';

			$output = '<div class="spreadplugin-design clearfix" id="design_'.$id.'" style="width:187px">';
			$output .= '<a name="'.$id.'"></a>';
			$output .= '<h3>'.htmlspecialchars($designData['name'],ENT_QUOTES).'</h3>';
			$output .= '<div class="image-wrapper" '.$addStyle.'>';
			$output .= '<img src="'.plugins_url('/img/blank.gif', __FILE__).'" class="lazyimg" data-original="' . $this->cleanURL($designData['resource2']) . ',width='.self::$shopOptions['shop_imagesize'].',height='.self::$shopOptions['shop_imagesize'].'" alt="' . htmlspecialchars($designData['name'],ENT_QUOTES) . '" id="compositedesignimg_'.$id.'" />'; // style="display:none;" // title="'.htmlspecialchars($designData['productdescription'],ENT_QUOTES).'"
			$output .= '<span class="img-caption">'.__('Click to view the articles', $this->stringTextdomain).'</em></span>';
			$output .= '</div>';

			// Show description link if not empty
			if (!empty($designData['description']) && $designData['description']!='null') {
				$output .= '<div class="separator"></div>';
				$output .= '<div class="description-wrapper">
				<div class="header"><a>'.__('Show description', $this->stringTextdomain).'</a></div>
				<div class="description">'.htmlspecialchars($designData['description'],ENT_QUOTES).'</div>
				</div>';
			}

			$output .= '
					</div>';

			return $output;

		}




		/**
		 * Function Add basket item
		 *
		 * @param $basketUrl
		 * @param $namespaces
		 * @param array $data
		 *
		 */
		private static function addBasketItem($basketUrl, $namespaces, $data) {

			$basketItemsUrl = $basketUrl . "/items";

			$basketItem = new SimpleXmlElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
					<basketItem xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://api.spreadshirt.net">
					<quantity>' . $data['quantity'] . '</quantity>
					<element id="' . $data['articleId'] . '" type="sprd:article" xlink:href="http://api.spreadshirt.'.self::$shopOptions['shop_source'].'/api/v1/shops/' . $data['shopId'] . '/articles/' . $data['articleId'] . '">
					<properties>
					<property key="appearance">' . $data['appearance'] . '</property>
					<property key="size">' . $data['size'] . '</property>
					</properties>
					</element>
					<links>
					<link type="edit" xlink:href="http://' . $data['shopId'] .'.spreadshirt.' .self::$shopOptions['shop_source'].'/-A' . $data['articleId'] . '"/>
					<link type="continueShopping" xlink:href="http://' . $data['shopId'].'.spreadshirt.'.self::$shopOptions['shop_source'].'"/>
					</links>
					</basketItem>');

			$header = array();
			$header[] = self::createAuthHeader("POST", $basketItemsUrl);
			$header[] = "Content-Type: application/xml";
			$result = self::oldHttpRequest($basketItemsUrl, $header, 'POST', $basketItem->asXML());

			if ($result) {
			} else {
				die('ERROR: Item not added.');
			}

		}


		/**
		 * Function delete basket item
		 *
		 * @param $basketUrl
		 * @param $namespaces
		 * @param array $data
		 *
		 */
		private static function deleteBasketItem($basketUrl, $itemId) {

			$basketItemsUrl = $basketUrl . "/items/".$itemId;

			$header = array();
			$header[] = self::createAuthHeader("DELETE", $basketItemsUrl);
			$result = self::oldHttpRequest($basketItemsUrl, $header, 'DELETE');

		}


		/**
		 * Function Create basket
		 *
		 * @param $platform
		 * @param $shop
		 * @param $namespaces
		 *
		 * @return string $basketUrl
		 *
		 */
		private static function createBasket($platform, $shop, $namespaces) {

			$basket = new SimpleXmlElement('<basket xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://api.spreadshirt.net">
					<shop id="' . $shop['id'] . '"/>
					</basket>');

			$attributes = $shop->baskets->attributes($namespaces['xlink']);
			$basketsUrl = $attributes->href;
			$header = array();
			$header[] = self::createAuthHeader("POST", $basketsUrl);
			$header[] = "Content-Type: application/xml";
			$result = self::oldHttpRequest($basketsUrl, $header, 'POST', $basket->asXML());

			if ($result) {
				$basketUrl = self::parseHttpHeaders($result, "Location");
			} else {
				die('ERROR: Basket not ready yet.');
			}

			return $basketUrl;

		}


		/**
		 * Function Checkout
		 *
		 * @param $basketUrl
		 * @param $namespaces
		 *
		 * @return string $checkoutUrl
		 *
		 */
		private static function checkout($basketUrl, $namespaces) {
			$checkoutUrl='';

			$basketCheckoutUrl = $basketUrl . "/checkout";
			$header = array();
			$header[] = self::createAuthHeader("GET", $basketCheckoutUrl);
			$header[] = "Content-Type: application/xml";
			$result = self::oldHttpRequest($basketCheckoutUrl, $header, 'GET');

			if ($result[0]=='<') {
				$checkoutRef = new SimpleXMLElement($result);
				$refAttributes = $checkoutRef->attributes($namespaces['xlink']);
				$checkoutUrl = (string)$refAttributes->href;
			} else {
				die('ERROR: Can\'t get checkout url.');
			}

			return $checkoutUrl;
		}


		/**
		 * Function createAuthHeader
		 *
		 * Creates authentification header
		 *
		 * @param string $method [POST,GET]
		 * @param string $url
		 *
		 * @return string
		 *
		 */
		private static function createAuthHeader($method, $url) {

			$time = microtime();

			$data = "$method $url $time";
			$sig = sha1("$data ".self::$shopOptions['shop_secret']);

			return "Authorization: SprdAuth apiKey=\"".self::$shopOptions['shop_api']."\", data=\"$data\", sig=\"$sig\"";

		}


		/**
		 * Function parseHttpHeaders
		 *
		 * @param string $header
		 * @param string $headername needle
		 * @return string $retval value
		 *
		 */
		private static function parseHttpHeaders($header, $headername) {

			$retVal = array();
			$fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));

			foreach($fields as $field) {
				if (preg_match('/(' . $headername . '): (.+)/m', $field, $match)) {
					return $match[2];
				}
			}

			return $retVal;
		}


		/**
		 * Function getBasket
		 *
		 * retrieves the basket
		 *
		 * @param string $basketUrl
		 * @return object $basket
		 *
		 */
		private static function getBasket($basketUrl) {

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


		/**
		 * Function getInBasketQuantity
		 *
		 * retrieves quantity of articles in basket
		 *
		 * @return int $intInBasket Quantity of articles
		 *
		 */
		private static function getInBasketQuantity() {
			$intInBasket = 0;
			
			if (isset($_SESSION['basketUrl'])) {
					
				$basketItems=self::getBasket($_SESSION['basketUrl']);

				if(!empty($basketItems)) {
					foreach($basketItems->basketItems->basketItem as $item) {
						$intInBasket += $item->quantity;
					}
				}
			}

			return $intInBasket;
		}


		/**
		 * Function oldHttpRequest
		 *
		 * creates the curl requests, until I get a fix for the wordpress request problems
		 *
		 * @param $url
		 * @param $header
		 * @param $method
		 * @param $data
		 * @param $len
		 *
		 * @return string|bool
		 *
		 */
		private static function oldHttpRequest($url, $header = null, $method = 'GET', $data = null, $len = null) {

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
					curl_setopt($ch, CURLOPT_POST, true); 
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

					break;

				case 'DELETE':
					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_HEADER, false);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST,'DELETE');

					break;

			}

			$result = curl_exec($ch);
			$info = curl_getinfo($ch);
			$status = isset($info['http_code'])?$info['http_code']:null;
			@curl_close($ch);

			if (in_array($status,array(200,201,204,403,406))) {
				return $result;
			}

			return false;
		}
		
		
		/**
		* call to merge the designer shop basket with the api basket
		* @TODO doesn't work yet - Spreadshirt's API is beta :(
		**/
		public function mergeBaskets() {
		
			$header = array();
			$basketId = "";
			$coolUrl = "";

			if (!empty($_SESSION['basketUrl'])) {
				
				if (preg_match("/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/",$_SESSION['basketUrl'],$found)) {
					$basketId = $found[0];
				}
				
				// cool widget url
				$coolUrl = "http://www.spreadshirt.de/de/DE/Widget/Www/synchronizeBasket/basket/".$basketId."/toApi/false";
				
				$result = self::oldHttpRequest($coolUrl, $header, 'GET');
			}

			echo $result;
			die();
		}



		/**
		 * Function loadHead
		 *
		 */
		public function loadHead() {
				
			$conOp = $this->getAdminOptions();
				
			if (!empty($conOp['shop_customcss'])) {
				echo '
				<style type="text/css">
				' . $conOp['shop_customcss'] . '
				</style>
				';
			}
		}


		/**
		 * Function loadFoot
		 *
		 */
		public function loadFoot() {
				
			echo "
					<script language='javascript' type='text/javascript'>
					/**
					* Spreadplugin vars
					*/

					var textHideDesc = '".__('Hide article description', $this->stringTextdomain)."';
					var textShowDesc = '".__('Show article description', $this->stringTextdomain)."';
					var textProdHideDesc = '".__('Hide product description', $this->stringTextdomain)."';
					var textProdShowDesc = '".__('Show product description', $this->stringTextdomain)."';
					var loadingImage = '".plugins_url('/img/loading.gif', __FILE__)."';
					var loadingMessage = '".__('Loading new articles...', $this->stringTextdomain)."';
					var loadingFinishedMessage = '".__('You have reached the end', $this->stringTextdomain)."';
					var pageLink = '".get_page_link()."';
					var pageCheckoutUseIframe = '".self::$shopOptions['shop_checkoutiframe']."';
					var textButtonAdd = '".__('Add to basket', $this->stringTextdomain)."';
					var textButtonAdded = '".__('Adding...', $this->stringTextdomain)."';
					var ajaxLocation = '".admin_url( 'admin-ajax.php' )."?pageid=".get_the_ID()."&nonce=".wp_create_nonce('spreadplugin')."';
					var display = '".self::$shopOptions['shop_display']."';
					var infiniteScroll = '".(self::$shopOptions['shop_infinitescroll']==1 || self::$shopOptions['shop_infinitescroll']==''?1:0)."';
					</script>";

			echo "
					<script language='javascript' type='text/javascript' src='".plugins_url('/js/spreadplugin.min.js', __FILE__)."'></script>";

		}


		public function enqueueJs() {
				
			$conOp = $this->getAdminOptions();

			// Scrolling
			if ($conOp['shop_infinitescroll']==1 || $conOp['shop_infinitescroll']=='') {
				wp_register_script('infinite_scroll', plugins_url('/js/jquery.infinitescroll.min.js', __FILE__),array('jquery'));
				wp_enqueue_script('infinite_scroll');
			}

			// Fancybox
			wp_register_script('magnific_popup', plugins_url('/js/jquery.magnific-popup.min.js', __FILE__),array('jquery'));
			wp_enqueue_script('magnific_popup');
				
			// Zoom
			wp_register_script('zoom', plugins_url('/js/jquery.elevateZoom-2.5.5.min.js', __FILE__),array('jquery'));
			wp_enqueue_script('zoom');
			
			// lazyload
			wp_register_script('lazyload', plugins_url('/js/jquery.lazyload.min.js', __FILE__),array('jquery'));
			wp_enqueue_script('lazyload');

			// Respects SSL, Style.css is relative to the current file
			wp_register_style('spreadplugin', plugins_url('/css/spreadplugin.css', __FILE__));
			wp_enqueue_style('spreadplugin');
			wp_register_style('magnific_popup_css', plugins_url('/css/magnific-popup.css', __FILE__));
			wp_enqueue_style('magnific_popup_css');
				
		}


		public function startSession() {
			if(!session_id()) {
				@session_start();
			}
		}

		public function endSession() {
			@session_destroy();
		}

		// prepare for https
		private function cleanURL($url) {
			return $url;
			//return str_replace('http:','',$url);
		}


		/**
		 * Function doAjax
		 *
		 * does all the ajax
		 *
		 * @return string json
		 *
		 */
		public function doAjax() {

			$_langCode = "";
			$_urlParts = array();

			if (!wp_verify_nonce($_GET['nonce'], 'spreadplugin')) die('Security check');

			$this->reparseShortcodeData();
			

			// create an new basket if not exist
			if (!isset($_SESSION['basketUrl'])) {

				// gets basket
				$apiUrl = 'http://api.spreadshirt.'.self::$shopOptions['shop_source'].'/api/v1/shops/' . self::$shopOptions['shop_id'];
				$stringXmlShop = wp_remote_get($apiUrl);
				if (count($stringXmlShop->errors)>0) die('Error getting basket.');
				if ($stringXmlShop['body'][0]!='<') die($stringXmlShop['body']);
				$stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
				$objShop = new SimpleXmlElement($stringXmlShop);
				if (!is_object($objShop)) die('Basket not loaded');

				// create the basket
				$namespaces = $objShop->getNamespaces(true);
				$basketUrl = self::createBasket('net', $objShop, $namespaces);
					
				if (empty($namespaces)) die('Namespaces empty');
				if (empty($basketUrl)) die('Basket url empty');
					
				// get the checkout url
				$checkoutUrl = self::checkout($basketUrl, $namespaces);
				
				
				// Workaround for checkout language
				$_langCode=@explode("_",self::$shopOptions['shop_locale']);
				$_langCode=$_langCode[0];

				if (!empty($_langCode)) {
					if ($_langCode=="us") {
						$_langCode="en";	
					} 
					
					$_urlParts = explode("/",$checkoutUrl);
					$_urlParts[3] = $_langCode;
					$checkoutUrl = implode("/",$_urlParts);
				}

				// saving to session
				$_SESSION['basketUrl'] = $basketUrl;
				$_SESSION['namespaces'] = $namespaces;
				$_SESSION['checkoutUrl'] = $checkoutUrl;

			}


			// add an article to the basket
			if (isset($_POST['size']) && isset($_POST['appearance']) && isset($_POST['quantity'])) {

				// article data to be sent to the basket resource
				$data = array(
						'articleId' => intval($_POST['article']),
						'size' => intval($_POST['size']),
						'appearance' => intval($_POST['appearance']),
						'quantity' => intval($_POST['quantity']),
						'shopId' => self::$shopOptions['shop_id']
				);

				// add to basket
				self::addBasketItem($_SESSION['basketUrl'] , $_SESSION['namespaces'] , $data);

			}


			$intInBasket=self::getInBasketQuantity();
			
			echo json_encode(array("c" => array("u" => $_SESSION['checkoutUrl'],"q" => intval($intInBasket))));
			die();
		}









		/**
		 * Function displayArticles
		 *
		 * Displays the articles
		 *
		 * @return html
		 */
		private function displayDetailPage($id,$article,$backgroundColor='') {

			$output = '<div class="spreadplugin-article-detail" id="article_'.$id.'">';
			$output .= '<a name="'.$id.'"></a>';
			$output .= '<form method="post" id="form_'.$id.'"><table><tr><td>';
				
			// edit article button
			if (self::$shopOptions['shop_designershop']>0) {
				$output .= ' <div class="edit-wrapper"><a href="//'.self::$shopOptions['shop_designershop'].'.spreadshirt.'.self::$shopOptions['shop_source'].'/-D1/customize/product/'.$article['productId'].'?noCache=true" target="'.self::$shopOptions['shop_linktarget'].'" title="'.__('Edit article', $this->stringTextdomain).'"><img src="'.plugins_url('/img/edit.png', __FILE__).'"></a></div>';
			}
				
			// display preview image
			$output .= '<div class="image-wrapper">';
			$output .= '<img src="http://image.spreadshirt.'.self::$shopOptions['shop_source'].'/image-server/v1/products/'.$article['productId'].'/views/'.$article['view'].',width=280,height=280" class="preview"  alt="' . htmlspecialchars($article['name'],ENT_QUOTES) . '" id="previewimg_'.$id.'" data-zoom-image="http://image.spreadshirt.'.self::$shopOptions['shop_source'].'/image-server/v1/products/'.$article['productId'].'/views/'.$article['view'].',width=600,height=600'.(!empty($backgroundColor)?',backgroundColor='.$backgroundColor:'').'" />';
			$output .= '</div>';


			// add a list with available product views
			if (isset($article['views'])&&is_array($article['views'])) {
				$output .= '<div class="views-wrapper"><ul class="views" name="views">';

				foreach($article['views'] as $k=>$v) {
					$output .= '<li value="'.$k.'"><img src="'. $this->cleanURL($v)  .',viewId='.$k.',width=42,height=42" class="previewview" alt="" id="viewimg_'.$id.'" /></li>';
				}

				$output .= '</ul></div>';
			}

			
			// Short product description
			$output .= '<div class="product-name">';
			$output .= htmlspecialchars($article['productname'],ENT_QUOTES);
			$output .= '</div>';
		

			if (self::$shopOptions['shop_enablelink']==1) {
				$output .= ' <div class="details-wrapper2"><a href="//'.self::$shopOptions['shop_id'].'.spreadshirt.'.self::$shopOptions['shop_source'].'/-A'.$id.'" target="_blank">'.__('Additional details', $this->stringTextdomain).'</a></div>';
			}


			$output .= '</td><td><h3>'.htmlspecialchars($article['name'],ENT_QUOTES).'</h3>';

			// Show description link if not empty
			if (!empty($article['description'])) {
				$output .= '<div class="description-wrapper clearfix">'.htmlspecialchars($article['description'],ENT_QUOTES).'</div>';
			}


			// Show product description
			$output .= '<div class="product-description-wrapper clearfix"><h4>'.__('Product details', $this->stringTextdomain).'</h4>'.$article['productdescription'].'</div>';


			// add a select with available sizes
			if (isset($article['sizes'])&&is_array($article['sizes'])) {
				$output .= '<div class="size-wrapper clearfix">'.__('Size', $this->stringTextdomain).': <select id="size-select" name="size">';

				foreach($article['sizes'] as $k => $v) {
					$output .= '<option value="'.$k.'">'.$v.'</option>';
				}

				$output .= '</select></div>';
			}

			// add a list with availabel product colors
			if (isset($article['appearances'])&&is_array($article['appearances'])) {
				$output .= '<div class="color-wrapper clearfix">'.__('Color', $this->stringTextdomain).': <ul class="colors" name="color">';

				foreach($article['appearances'] as $k=>$v) {
					$output .= '<li value="'.$k.'"><img src="'. $this->cleanURL($v) .'" alt="" /></li>';
				}

				$output .= '</ul></div>';
			}


			$output .= '<input type="hidden" value="'. $article['appearance'] .'" id="appearance" name="appearance" />';
			$output .= '<input type="hidden" value="'. $article['view'] .'" id="view" name="view" />';
			$output .= '<input type="hidden" value="'. $id .'" id="article" name="article" />';

			//$output .= '<div class="separator"></div>';
			$output .= '<div class="price-wrapper clearfix">';
			if (self::$shopOptions['shop_showextendprice']==1) {
				$output .= '<span id="price-without-tax">'.__('Price (without tax):', $this->stringTextdomain)." ".(empty(self::$shopOptions['shop_locale']) || self::$shopOptions['shop_locale']=='en_US' || self::$shopOptions['shop_locale']=='en_GB' || self::$shopOptions['shop_locale']=='us_US' || self::$shopOptions['shop_locale']=='us_CA' || self::$shopOptions['shop_locale']=='fr_CA'?$article['currencycode']." ".number_format($article['pricenet'],2,'.',''):number_format($article['pricenet'],2,',','.')." ".$article['currencycode'])."<br /></span>";
				$output .= '<span id="price-with-tax">'.__('Price (with tax):', $this->stringTextdomain)." ".(empty(self::$shopOptions['shop_locale']) || self::$shopOptions['shop_locale']=='en_US' || self::$shopOptions['shop_locale']=='en_GB' || self::$shopOptions['shop_locale']=='us_US' || self::$shopOptions['shop_locale']=='us_CA' || self::$shopOptions['shop_locale']=='fr_CA'?$article['currencycode']." ".number_format($article['pricebrut'],2,'.',''):number_format($article['pricebrut'],2,',','.')." ".$article['currencycode'])."</span>";
				$output .= '<br><div class="additionalshippingcosts">';
				$output .= __('excl. Shipping', $this->stringTextdomain);
				$output .= '</div>';
			} else {
				$output .= '<span id="price">'.__('Price:', $this->stringTextdomain)." ".(empty(self::$shopOptions['shop_locale']) || self::$shopOptions['shop_locale']=='en_US' || self::$shopOptions['shop_locale']=='en_GB' || self::$shopOptions['shop_locale']=='us_US' || self::$shopOptions['shop_locale']=='us_CA' || self::$shopOptions['shop_locale']=='fr_CA'?$article['currencycode']." ".number_format($article['pricebrut'],2,'.',''):number_format($article['pricebrut'],2,',','.')." ".$article['currencycode'])."</span>";
			}
			$output .= '</div>';

			// order buttons
			$output .= '<input type="text" value="1" id="quantity" name="quantity" maxlength="4" />';
			$output .= '<input type="submit" name="submit" value="'.__('Add to basket', $this->stringTextdomain).'" /><br>';

			// Social buttons
			if (self::$shopOptions['shop_social']==true) {
				$output .= '
				<ul class="soc-icons">
				<li><a target="_blank" data-color="#5481de" class="fb" href="//www.facebook.com/sharer.php?u='.urlencode(add_query_arg( 'product', $id, get_permalink())).'&t='.rawurlencode(get_the_title()).'" title="Facebook"></a></li>
				<li><a target="_blank" data-color="#06ad18" class="goog" href="//plus.google.com/share?url='.urlencode(add_query_arg( 'product', $id, get_permalink())).'" title="Google"></a></li>
				<li><a target="_blank" data-color="#2cbbea" class="twt" href="//twitter.com/home?status='.rawurlencode(get_the_title()).' - '.urlencode(add_query_arg( 'product', $id, get_permalink())).'" title="Twitter"></a></li>
				<li><a target="_blank" data-color="#e84f61" class="pin" href="//pinterest.com/pin/create/button/?url='.rawurlencode(add_query_arg( 'product', $id, get_permalink())).'&media='.rawurlencode('http://image.spreadshirt.'.self::$shopOptions['shop_source'].'/image-server/v1/products/'.$article['productId'].'/views/'.$article['view'].',width=280,height=280').',width='.self::$shopOptions['shop_imagesize'].',height='.self::$shopOptions['shop_imagesize'].'&description='.(!empty($article['description'])?htmlspecialchars($article['description'],ENT_QUOTES):'Product').'" title="Pinterest"></a></li>
				</ul>
				';

				/*
					<li><a target="_blank" data-color="#459ee9" class="in" href="#" title="LinkedIn"></a></li>
				<li><a target="_blank" data-color="#ee679b" class="drb" href="#" title="Dribbble"></a></li>
				<li><a target="_blank" data-color="#4887c2" class="tumb" href="#" title="Tumblr"></a></li>
				<li><a target="_blank" data-color="#f23a94" class="flick" href="#" title="Flickr"></a></li>
				<li><a target="_blank" data-color="#74c3dd" class="vim" href="#" title="Vimeo"></a></li>
				<li><a target="_blank" data-color="#4a79ff" class="delic" href="#" title="Delicious"></a></li>
				<li><a target="_blank" data-color="#6ea863" class="forr" href="#" title="Forrst"></a></li>
				<li><a target="_blank" data-color="#f6a502" class="hi5" href="#" title="Hi5"></a></li>
				<li><a target="_blank" data-color="#e3332a" class="last" href="#" title="Last.fm"></a></li>
				<li><a target="_blank" data-color="#3c6ccc" class="space" href="#" title="Myspace"></a></li>
				<li><a target="_blank" data-color="#229150" class="newsv" href="#" title="Newsvine"></a></li>
				<li><a href="#" class="pica" title="Picasa" data-color="#b163c8" target="_blank"></a></li>
				<li><a href="#" class="tech" title="Technorati" data-color="#3ac13a" target="_blank"></a></li>
				<li><a href="#" class="rss" title="RSS" data-color="#f18d3c" target="_blank"></a></li>
				<li><a href="#" class="rdio" title="Rdio" data-color="#2c7ec7" target="_blank"></a></li>
				<li><a href="#" class="share" title="ShareThis" data-color="#359949" target="_blank"></a></li>
				<li><a href="#" class="skyp" title="Skype" data-color="#00adf1" target="_blank"></a></li>
				<li><a href="#" class="slid" title="SlideShare" data-color="#ef8122" target="_blank"></a></li>
				<li><a href="#" class="squid" title="Squidoo" data-color="#f87f27" target="_blank"></a></li>
				<li><a href="#" class="stum" title="StumbleUpon" data-color="#f05c38" target="_blank"></a></li>
				<li><a href="#" class="what" title="WhatsApp" data-color="#3ebe2b" target="_blank"></a></li>
				<li><a href="#" class="wp" title="Wordpress" data-color="#3078a9" target="_blank"></a></li>
				<li><a href="#" class="ytb" title="Youtube" data-color="#df3434" target="_blank"></a></li>
				<li><a href="#" class="digg" title="Digg" data-color="#326ba0" target="_blank"></a></li>
				<li><a href="#" class="beh" title="Behance" data-color="#2d9ad2" target="_blank"></a></li>
				<li><a href="#" class="yah" title="Yahoo" data-color="#883890" target="_blank"></a></li>
				<li><a href="#" class="blogg" title="Blogger" data-color="#f67928" target="_blank"></a></li>
				<li><a href="#" class="hype" title="Hype Machine" data-color="#f13d3d" target="_blank"></a></li>
				<li><a href="#" class="groove" title="Grooveshark" data-color="#498eba" target="_blank"></a></li>
				<li><a href="#" class="sound" title="SoundCloud" data-color="#f0762c" target="_blank"></a></li>
				<li><a href="#" class="insta" title="Instagram" data-color="#c2784e" target="_blank"></a></li>
				<li><a href="#" class="vk" title="Vkontakte" data-color="#5f84ab" target="_blank"></a></li>
				*/
			}

			$output .= '
</td></tr></table>
					</form>
					</div>';


			return $output;

		}

























		/**
		 * Admin
		 */
		public function addPluginPage(){
			// Create menu tab
			add_options_page('Set Spreadplugin options', 'Spreadplugin Options', 'manage_options', 'splg_options', array($this, 'pageOptions'));
		}

		// call page options
		public function pageOptions(){
			if (!current_user_can('manage_options')){
				wp_die( __('You do not have sufficient permissions to access this page.') );
			}

			// display options page
			include(plugin_dir_path(__FILE__).'/options.php');
		}

		// Ajax delete the transient
		public function doRegenerateCache() {
			$this->setRegenerateCacheQuery();
			die();
		}
		// delete the transient
		public function setRegenerateCacheQuery() {
			global $wpdb;
			$wpdb->query("DELETE FROM `".$wpdb->options."` WHERE `option_name` LIKE '_transient_%spreadplugin%cache%'");
		}


		/**
		 * Add Settings link to plugin
		 */
		public function addPluginSettingsLink($links, $file) {
			static $this_plugin;
			if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);

			if ($file == $this_plugin){
				$settings_link = '<a href="options-general.php?page=splg_options">'.__("Settings", $this->stringTextdomain) .'</a>';
				array_unshift($links, $settings_link);
			}

			return $links;
		}


		// Convert hex to rgb values
		public function hex2rgb($hex) {
			if(strlen($hex) == 3) {
				$r = hexdec(substr($hex,0,1).substr($hex,0,1));
				$g = hexdec(substr($hex,1,1).substr($hex,1,1));
				$b = hexdec(substr($hex,2,1).substr($hex,2,1));
			} else {
				$r = hexdec(substr($hex,0,2));
				$g = hexdec(substr($hex,2,2));
				$b = hexdec(substr($hex,4,2));
			}
			$rgb = array($r, $g, $b);
			return $rgb; // returns an array with the rgb values
		}


		// read admin options
		public function getAdminOptions() {
			$scOptions = $this->defaultOptions;
			$splgOptions = get_option('splg_options');
			if (!empty($splgOptions)) {
				foreach($splgOptions as $key => $option) {
					$scOptions[$key] = $option;
				}
			}

			return $scOptions;
		}
		


		// read page config and admin options
		public function reparseShortcodeData() {
		
			/**
			 * re-parse the shortcode to get the authentication details
			 *
			 * @TODO find a different way
			 *
			*/
			$pageData = get_page(intval($_GET['pageid']));
			$pageContent = $pageData->post_content;

			// get admin options (default option set on admin page)
			$conOp = $this->getAdminOptions();

			// shortcode overwrites admin options (default option set on admin page) if available
			$arrSc = shortcode_parse_atts(str_replace("[spreadplugin",'',str_replace("]","",$pageContent)));

			// replace options by shortcode if set
			if (!empty($arrSc)) {
				foreach ($arrSc as $key => $option) {
					if ($option != '') {
						$conOp[$key] = $option;
					}
				}
			}

			
			self::$shopOptions = $conOp;
			self::$shopOptions['shop_locale'] = (($conOp['shop_locale']=='' || $conOp['shop_locale']=='de_DE') && $conOp['shop_source']=='com'?'us_US':$conOp['shop_locale']); // Workaround for older versions of this plugin
			self::$shopOptions['shop_source'] = (empty($conOp['shop_source'])?'net':$conOp['shop_source']);

		}


		
		// build cart
		public function doCart() {

			if (!wp_verify_nonce($_GET['nonce'], 'spreadplugin')) die('Security check');

			$this->reparseShortcodeData();

			// create an new basket if not exist
			if (isset($_SESSION['basketUrl'])) {
							
				$basketItems=self::getBasket($_SESSION['basketUrl']);

				$priceSum=0;
				$intSumQuantity=0;
				
				echo '<div class="spreadplugin-cart-contents">';
				
				if(!empty($basketItems)) {
					//echo "<pre>".print_r($basketItems)."</pre>";
					foreach($basketItems->basketItems->basketItem as $item){
						
						$apiUrl='http://api.spreadshirt.'.self::$shopOptions['shop_source'].'/api/v1/shops/'.(string)$item->shop['id'].'/articles/'.(string)$item->element['id'];						
						$stringXmlShop = wp_remote_get($apiUrl);
						if (count($stringXmlShop->errors)>0) die('Error getting articles. Please check Shop-ID, API and secret.');
						if ($stringXmlShop['body'][0]!='<') die($stringXmlShop['body']);
						$stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
						$objArticles = new SimpleXmlElement($stringXmlShop);
						if (!is_object($objArticles)) die('Articles not loaded');
						
						echo '<div class="cart-row" data-id="'.(string)$item['id'].'">
							<div class="cart-delete"><a href="javascript:;" class="deleteCartItem" title="'.__('Remove', $this->stringTextdomain).'"><img src="'.plugins_url('/img/delete.png', __FILE__).'"></a></div>
							<div class="cart-preview"><img src="http://image.spreadshirt.'.self::$shopOptions['shop_source'].'/image-server/v1/products/'.(string)$objArticles->product['id'].'/views/'.(string)$objArticles->product->defaultValues->defaultView['id'].',viewId='.(string)$objArticles->product->defaultValues->defaultView['id'].',width=60,height=60,appearanceId='.(string)$item->element->properties->property[1].'"></div>
							<div class="cart-description"><strong>'.htmlspecialchars((empty($objArticles->name)?$item->description:$objArticles->name),ENT_QUOTES).'</strong><br>'.__('Size', $this->stringTextdomain).': '.(string)$item->element->properties->property[0].'<br>'.__('Quantity', $this->stringTextdomain).': '.(int)$item->quantity.'</div>
							<div class="cart-price"><strong>'.(empty(self::$shopOptions['shop_locale']) || self::$shopOptions['shop_locale']=='en_US' || self::$shopOptions['shop_locale']=='en_GB' || self::$shopOptions['shop_locale']=='us_US' || self::$shopOptions['shop_locale']=='us_CA' || self::$shopOptions['shop_locale']=='fr_CA'?number_format((float)$item->price->vatIncluded*(int)$item->quantity,2,'.',''):number_format((float)$item->price->vatIncluded*(int)$item->quantity,2,',','.')).'</strong></div>
							</div>';
						
						$priceSum+=(float)$item->price->vatIncluded * (int)$item->quantity;
						$intSumQuantity+=(int)$item->quantity;
						
					}
				}
				
				echo '</div>';
				echo '<div class="spreadplugin-cart-total">'.__('Total (excl. Shipping)', $this->stringTextdomain).'<strong class="price">'.(empty(self::$shopOptions['shop_locale']) || self::$shopOptions['shop_locale']=='en_US' || self::$shopOptions['shop_locale']=='en_GB' || self::$shopOptions['shop_locale']=='us_US' || self::$shopOptions['shop_locale']=='us_CA' || self::$shopOptions['shop_locale']=='fr_CA'?number_format($priceSum,2,'.',''):number_format($priceSum,2,',','.')).'</strong></div>';
				
				if ($intSumQuantity>0) {
					echo '<div id="cart-checkout" class="spreadplugin-cart-checkout"><a href="'.$_SESSION['checkoutUrl'].'" target="'.self::$shopOptions['shop_linktarget'].'">'.__('Proceed checkout', $this->stringTextdomain).'</a></div>';
				} else {
					echo '<div id="cart-checkout" class="spreadplugin-cart-checkout"><a title="'.__('Basket is empty', $this->stringTextdomain).'">'.__('Proceed checkout', $this->stringTextdomain).'</a></div>';
				}
			}
			
			die();
		}
		
		
		// delete cart
		public function doCartItemDelete() {

			if (!wp_verify_nonce($_GET['nonce'], 'spreadplugin')) die('Security check');
			
			$this->reparseShortcodeData();


			// create an new basket if not exist
			if (isset($_SESSION['basketUrl'])) {
				// uuid test
				if(preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/',$_POST['id'])) {
					self::deleteBasketItem($_SESSION['basketUrl'],$_POST['id']);
				}
			}
			
			die();
		}
		


	} // END class WP_Spreadplugin

	new WP_Spreadplugin();
}



?>