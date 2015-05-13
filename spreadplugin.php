<?php
/**
 * Plugin Name: WP-Spreadplugin
 * Plugin URI: http://wordpress.org/extend/plugins/wp-spreadplugin/
 * Description: This plugin uses the Spreadshirt API to list articles and let your customers order articles of your Spreadshirt shop using Spreadshirt order process.
 * Version: 3.8.6.6
 * Author: Thimo Grauerholz
 * Author URI: http://www.spreadplugin.de
 */
@set_time_limit(0);

/**
 * WP_Spreadplugin class
 */
if (!class_exists('WP_Spreadplugin')) {

    class WP_Spreadplugin {

        private $stringTextdomain = 'spreadplugin';

        public static $shopOptions;

        public static $shopArticleSortOptions = array(
            'name','price','recent','weight'
        );

        public $defaultOptions = array(
            'shop_id' => '','shop_locale' => '','shop_api' => '','shop_source' => '','shop_secret' => '','shop_limit' => '','shop_category' => '','shop_social' => '','shop_enablelink' => '','shop_productcategory' => '','shop_productsubcategory' => '','shop_sortby' => '','shop_linktarget' => '','shop_checkoutiframe' => '','shop_designershop' => '','shop_display' => '','shop_designsbackground' => '','shop_showdescription' => '','shop_showproductdescription' => '','shop_imagesize' => '','shop_showextendprice' => '','shop_zoomimagebackground' => '','shop_infinitescroll' => '','shop_customcss' => '','shop_design' => '','shop_view' => '','shop_zoomtype' => '','shop_lazyload' => '','shop_language' => '','shop_basket_text_icon' => '','shop_debug' => '','shop_sleep' => '','shop_designer' => ''
        );

        private static $shopCache = 0; // Shop article cache - never expires
        public function __construct() {
            add_action('init', array(
                &$this,'startSession'
            ), 1);
            add_action('wp_logout', array(
                &$this,'endSession'
            ));
            add_action('wp_login', array(
                &$this,'endSession'
            ));

            add_shortcode('spreadplugin', array(
                $this,'Spreadplugin'
            ));

            // Ajax actions
			/*
            add_action('wp_ajax_nopriv_mergeBasket', array(
                &$this,'mergeBaskets'
            ));
            add_action('wp_ajax_mergeBasket', array(
                &$this,'mergeBaskets'
            ));
			*/
            add_action('wp_ajax_nopriv_myAjax', array(
                &$this,'doAjax'
            ));
            add_action('wp_ajax_myAjax', array(
                &$this,'doAjax'
            ));
            add_action('wp_ajax_nopriv_myCart', array(
                &$this,'doCart'
            ));
            add_action('wp_ajax_myCart', array(
                &$this,'doCart'
            ));
            add_action('wp_ajax_nopriv_myDelete', array(
                &$this,'doCartItemDelete'
            ));
            add_action('wp_ajax_myDelete', array(
                &$this,'doCartItemDelete'
            ));
            add_action('wp_ajax_rebuildCache', array(
                &$this,'doRebuildCache'
            ));

            add_action('wp_enqueue_scripts', array(
                &$this,'enqueueJs'
            ));
            add_action('wp_head', array(
                &$this,'loadHead'
            ));

            // admin check
            if (is_admin()) {
                // Regenerate cache after activation of the plugin
                // register_activation_hook(__FILE__, array(&$this,'helperClearCacheQuery'));

                // add Admin menu
                add_action('admin_menu', array(
                    &$this,'addPluginPage'
                ));
                // add Plugin settings link
                add_filter('plugin_action_links', array(
                    &$this,'addPluginSettingsLink'
                ), 10, 2);

                add_action('admin_enqueue_scripts', array(
                    &$this,'enqueueAdminJs'
                ));
            }
        }

        /**
         * PHP 4 Compatible Constructor
         */
        function WP_Spreadplugin() {
            $this->__construct();
        }

        /**
         * Function Spreadplugin
         *
         * @return string article display
         *
         */
        public function Spreadplugin($atts) {
            $articleCleanData = array(); // Array with article informations for sorting and filtering
            $articleCleanDataComplete = array(); // Array with article informations for sorting and filtering
            $articleData = array();
            $designsData = array();

            add_action('wp_footer', array(
                &$this,'loadFoot'
            ));

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
            self::$shopOptions['shop_source'] = (empty($conOp['shop_source']) ? 'net' : $conOp['shop_source']);
            self::$shopOptions['shop_limit'] = (empty($conOp['shop_limit']) ? 10 : intval($conOp['shop_limit']));
            self::$shopOptions['shop_locale'] = (($conOp['shop_locale'] == '' || $conOp['shop_locale'] == 'de_DE') && $conOp['shop_source'] == 'com' ? 'us_US' : $conOp['shop_locale']); // Workaround for older versions of this plugin
            self::$shopOptions['shop_imagesize'] = (intval($conOp['shop_imagesize']) == 0 ? 190 : intval($conOp['shop_imagesize']));
            self::$shopOptions['shop_zoomimagebackground'] = (empty($conOp['shop_zoomimagebackground']) ? 'FFFFFF' : str_replace("#", "", $conOp['shop_zoomimagebackground']));
            self::$shopOptions['shop_infinitescroll'] = ($conOp['shop_infinitescroll'] == '' ? 1 : $conOp['shop_infinitescroll']);
            self::$shopOptions['shop_zoomtype'] = ($conOp['shop_zoomtype'] == '' ? 0 : $conOp['shop_zoomtype']);
            self::$shopOptions['shop_lazyload'] = ($conOp['shop_lazyload'] == '' ? 1 : $conOp['shop_lazyload']);
            self::$shopOptions['shop_debug'] = ($conOp['shop_debug'] == '' ? 0 : $conOp['shop_debug']);

            // Disable Zoom on min view, because of the new view - not on details page
            if (self::$shopOptions['shop_view'] == 2 && empty($_GET['splproduct'])) {
                self::$shopOptions['shop_zoomtype'] = 2;
            }

            // overwrite translation if language available and set
            if (!empty(self::$shopOptions['shop_language'])) {
                $_ol = dirname(__FILE__) . '/translation/' . $this->stringTextdomain . '-' . self::$shopOptions['shop_language'] . '.mo';
                if (file_exists($_ol)) {
                    load_textdomain($this->stringTextdomain, $_ol);
                }
            } else {
                load_plugin_textdomain($this->stringTextdomain, false, dirname(plugin_basename(__FILE__)) . '/translation');
            }

            if (isset($_GET['productCategory'])) {
                $c = $_GET['productCategory'];
                self::$shopOptions['shop_productcategory'] = $c;
                self::$shopOptions['shop_productsubcategory'] = 'all';

                if (!empty($_GET['productSubCategory'])) {
                    $c = $_GET['productSubCategory'];
                    self::$shopOptions['shop_productsubcategory'] = $c;
                }
            }

            if (!empty(self::$shopOptions['shop_productcategory'])) {
                self::$shopOptions['shop_productcategory'] = htmlspecialchars_decode(self::$shopOptions['shop_productcategory']);
            }
            if (!empty(self::$shopOptions['shop_productsubcategory'])) {
                self::$shopOptions['shop_productsubcategory'] = htmlspecialchars_decode(self::$shopOptions['shop_productsubcategory']);
            }

            if (!empty(self::$shopOptions['shop_productcategory']) && empty(self::$shopOptions['shop_productsubcategory'])) {
                self::$shopOptions['shop_productsubcategory'] = "all";
            }

            if (isset($_GET['articleSortBy'])) {
                $c = urldecode($_GET['articleSortBy']);
                self::$shopOptions['shop_sortby'] = $c;
            }

            // At filtering articles don't use designs view
            if (self::$shopOptions['shop_display'] == 1 && self::$shopOptions['shop_productcategory'] == '' && self::$shopOptions['shop_design'] == 0) {} else {
                self::$shopOptions['shop_display'] = 0;
            }

            // check
            if (!empty(self::$shopOptions['shop_id']) && !empty(self::$shopOptions['shop_api']) && !empty(self::$shopOptions['shop_secret'])) {

                $paged = (isset($_GET['pagesp']) && (int)$_GET['pagesp'] > 0) ? (int)$_GET['pagesp'] : 1;

                $offset = ($paged - 1) * self::$shopOptions['shop_limit'];

                // get article data
                $articleData = self::getCacheArticleData();
                // get rid of types in array
                $typesData = $articleData['types'];
                unset($articleData['types']);
                // get shipment data and delete
                $shipmentData = $articleData['shipment'];
                unset($articleData['shipment']);

                // get designs data
                $designsData = self::getCacheDesignsData();

                if (self::$shopOptions['shop_debug'] == 1) {
                    echo "Stored Article Data RAW (0):<br>";
                    print_r($articleData);
                }

                if (self::$shopOptions['shop_debug'] == 1) {
                    echo "Stored Design Data RAW (0):<br>";
                    print_r($designsData);
                }

                // built array with articles for sorting and filtering
                if (is_array($designsData)) {
                    foreach ($designsData as $designId => $arrDesigns) {
                        if (!empty($articleData[$designId])) {
                            foreach ($articleData[$designId] as $articleId => $arrArticle) {
                                $articleCleanData[$articleId] = $arrArticle;
                                $articleCleanDataComplete[$articleId] = $arrArticle;
                            }
                        }
                    }

                    if (self::$shopOptions['shop_debug'] == 1) {
                        echo "With Design (1):<br>";
                        print_r($articleCleanData);
                    }
                }

                // Add all those articles with no own designs and other cases - maybe overwrite them
                if (!empty($articleData)) {
                    foreach ($articleData as $arrDesigns) {
                        if (!empty($arrDesigns)) {
                            foreach ($arrDesigns as $articleId => $arrArticle) {
                                $articleCleanData[$articleId] = $arrArticle;
                                $articleCleanDataComplete[$articleId] = $arrArticle;
                            }
                        }
                    }

                    if (self::$shopOptions['shop_debug'] == 1) {
                        echo "With some cases (2):<br>";
                        print_r($articleCleanData);
                    }
                }

                // filter
                if (is_array($articleCleanData)) {
                    foreach ($articleCleanData as $id => $article) {

                        // designs
                        if (self::$shopOptions['shop_design'] > 0 && self::$shopOptions['shop_design'] != $articleCleanData[$id]['designid']) {
                            unset($articleCleanData[$id]);
                        }

                        // product categories
                        if (!empty(self::$shopOptions['shop_productcategory']) && isset($typesData[self::$shopOptions['shop_productcategory']][self::$shopOptions['shop_productsubcategory']])) {
                            if (!isset($typesData[self::$shopOptions['shop_productcategory']][self::$shopOptions['shop_productsubcategory']][$article['type']])) {
                                unset($articleCleanData[$id]);
                            }
                        }
                    }
                }

                // default sort
                @uasort($designsData, create_function('$a,$b', "return (\$a[place] > \$b[place])?-1:1;"));
                /*
                 * 2014-06-22 Changed from place to id, place is not set anymore (and sort direction to desc) 2014-07-20 Changed back to place and sort direction asc, because place added again
                 */
                @uasort($articleCleanData, create_function('$a,$b', "return (\$a[place] < \$b[place])?-1:1;"));

                // sorting
                if (self::$shopOptions['shop_display'] == 1) {
                    if (!empty(self::$shopOptions['shop_sortby']) && is_array($designsData) && in_array(self::$shopOptions['shop_sortby'], self::$shopArticleSortOptions)) {
                        if (self::$shopOptions['shop_sortby'] == "recent") {
                            krsort($designsData);
                        } elseif (self::$shopOptions['shop_sortby'] == "price") {
                            uasort($designsData, create_function('$a,$b', "return (\$a[pricenet] < \$b[pricenet])?-1:1;"));
                        } elseif (self::$shopOptions['shop_sortby'] == "weight") {
                            uasort($designsData, create_function('$a,$b', "return (\$a[weight] > \$b[weight])?-1:1;"));
                        } else {
                            uasort($designsData, create_function('$a,$b', "return strnatcmp(\$a[" . self::$shopOptions['shop_sortby'] . "],\$b[" . self::$shopOptions['shop_sortby'] . "]);"));
                        }
                    }
                } else {
                    if (!empty(self::$shopOptions['shop_sortby']) && is_array($articleCleanData) && in_array(self::$shopOptions['shop_sortby'], self::$shopArticleSortOptions)) {
                        if (self::$shopOptions['shop_sortby'] == "recent") {
                            krsort($articleCleanData);
                        } elseif (self::$shopOptions['shop_sortby'] == "price") {
                            uasort($articleCleanData, create_function('$a,$b', "return (\$a[pricenet] < \$b[pricenet])?-1:1;"));
                        } elseif (self::$shopOptions['shop_sortby'] == "weight") {
                            uasort($articleCleanData, create_function('$a,$b', "return (\$a[weight] > \$b[weight])?-1:1;"));
                        } else {
                            uasort($articleCleanData, create_function('$a,$b', "return strnatcmp(\$a[" . self::$shopOptions['shop_sortby'] . "],\$b[" . self::$shopOptions['shop_sortby'] . "]);"));
                        }
                    }
                }

                // pagination
                if (self::$shopOptions['shop_display'] == 1) {
                    if (!empty(self::$shopOptions['shop_limit']) && is_array($designsData)) {
                        $cArticleNext = count(array_slice($designsData, $offset + self::$shopOptions['shop_limit'], self::$shopOptions['shop_limit'], true));
                        $designsData = array_slice($designsData, $offset, self::$shopOptions['shop_limit'], true);
                    }
                } else {
                    if (!empty(self::$shopOptions['shop_limit']) && is_array($articleCleanData)) {
                        $cArticleNext = count(array_slice($articleCleanData, $offset + self::$shopOptions['shop_limit'], self::$shopOptions['shop_limit'], true));
                        $articleCleanData = array_slice($articleCleanData, $offset, self::$shopOptions['shop_limit'], true);
                    }
                }

                // Start output
                $output = '';

                // check if curl is enabled
                $output .= (function_exists('curl_version') ? '' : '<span class="error">Curl seems to be disabled. In order to use Shop functionality, it should be enabled</span>');
                // wrapper for integrated designer
                if (self::$shopOptions['shop_designer'] == 1) {
                    $output .= '
					<div id="spreadplugin-designer-wrapper"><div id="spreadplugin-designer" class="spreadplugin-designer spreadplugin-clearfix"></div></div>
					';
                }

                // Start div
                $output .= '
				<div id="spreadplugin-items" class="spreadplugin-items spreadplugin-clearfix">
				';

                // display
                if (count($articleData) == 0 || $articleData == false) {

                    $output .= '<br>No articles in Shop. Please rebuild cache.';
                } else {
                    // Listing product
                    if (empty($_GET['splproduct'])) {

                        // add spreadplugin-menu
                        $output .= '<div id="spreadplugin-menu" class="spreadplugin-menu">';

                        // add product categories
                        $output .= '<select name="productCategory" id="productCategory">';
                        $output .= '<option value="">' . __('Product category', $this->stringTextdomain) . '</option>';
                        if (isset($typesData)) {
                            foreach ($typesData as $t => $v) {
                                $output .= '<option value="' . urlencode($t) . '"' . ($t == self::$shopOptions['shop_productcategory'] ? ' selected' : '') . '>' . $t . '</option>';
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
                                    $output .= '<option value="' . urlencode($t) . '"' . ($t == self::$shopOptions['shop_productsubcategory'] ? ' selected' : '') . '>' . $t . '</option>';
                                }
                            }
                            $output .= '</select> ';
                        }

                        // add sorting
                        $output .= '<select name="articleSortBy" id="articleSortBy">';
                        $output .= '<option value="">' . __('Sort by', $this->stringTextdomain) . '</option>';
                        $output .= '<option value="name"' . ('name' == self::$shopOptions['shop_sortby'] ? ' selected' : '') . '>' . __('name', $this->stringTextdomain) . '</option>';
                        $output .= '<option value="price"' . ('price' == self::$shopOptions['shop_sortby'] ? ' selected' : '') . '>' . __('price', $this->stringTextdomain) . '</option>';
                        $output .= '<option value="recent"' . ('recent' == self::$shopOptions['shop_sortby'] ? ' selected' : '') . '>' . __('recent', $this->stringTextdomain) . '</option>';
                        $output .= '<option value="weight"' . ('weight' == self::$shopOptions['shop_sortby'] ? ' selected' : '') . '>' . __('weight', $this->stringTextdomain) . '</option>';
                        $output .= '</select>';

                        // url not needed here, but just in case if js won't work for some reason
                        $output .= '<div id="checkout" class="spreadplugin-checkout"><span></span> <a href="' . (!empty($_SESSION['checkoutUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']]) ? $_SESSION['checkoutUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']] : '') . '" target="' . self::$shopOptions['shop_linktarget'] . '" id="basketLink" class="spreadplugin-checkout-link' . (self::$shopOptions['shop_basket_text_icon'] == 1 ? ' button' : '') . '">' . (self::$shopOptions['shop_basket_text_icon'] == 0 ? __('Basket', $this->stringTextdomain) : '') . '</a></div>';
                        $output .= '<div id="cart" class="spreadplugin-cart"></div>';

                        $output .= '</div>';

                        $output .= '<div id="spreadplugin-list">';

                        // Designs view
                        if (self::$shopOptions['shop_display'] == 1) {
                            if (!empty($designsData)) {
                                foreach ($designsData as $designId => $arrDesigns) {
                                    $bgc = false;
                                    $addStyle = '';

                                    // Display just Designs with products
                                    if (!empty($articleData[$designId])) {

                                        // check if designs background is enabled
                                        if (self::$shopOptions['shop_designsbackground'] == 1) {
                                            // fetch first article background color
                                            @reset($articleData[$designId]);
                                            $bgcV = $articleData[$designId][key($articleData[$designId])]['default_bgc'];
                                            $bgcV = str_replace("#", "", $bgcV);
                                            // calc to hex
                                            $bgc = $this->hex2rgb($bgcV);
                                            $addStyle = "style=\"background-color:rgba(" . $bgc[0] . "," . $bgc[1] . "," . $bgc[2] . ",0.4);\"";
                                        }

                                        $output .= "<div class=\"spreadplugin-designs\">";
                                        $output .= $this->displayDesigns($designId, $arrDesigns, $articleData[$designId], $bgc);
                                        $output .= "<div id=\"designContainer_" . $designId . "\" class=\"design-container spreadplugin-clearfix\" " . $addStyle . ">";

                                        if (!empty($articleData[$designId])) {

                                            // default sort
                                            @uasort($articleData[$designId], create_function('$a,$b', "return (\$a[id] > \$b[id])?-1:1;")); // 2014-06-22 Changed from place to id, place is not set anymore (and sort direction to desc

                                            switch (self::$shopOptions['shop_view']) {
                                                case 1:
                                                    foreach ($articleData[$designId] as $articleId => $arrArticle) {
                                                        $output .= $this->displayListArticles($articleId, $arrArticle, self::$shopOptions['shop_zoomimagebackground']);
                                                    }
                                                    break;
                                                case 2:
                                                    foreach ($articleData[$designId] as $articleId => $arrArticle) {
                                                        $output .= $this->displayMinArticles($articleId, $arrArticle, self::$shopOptions['shop_zoomimagebackground']);
                                                    }
                                                    break;
                                                default:
                                                    foreach ($articleData[$designId] as $articleId => $arrArticle) {
                                                        $output .= $this->displayArticles($articleId, $arrArticle, self::$shopOptions['shop_zoomimagebackground']);
                                                    }
                                                    break;
                                            }
                                        }

                                        $output .= "</div>";
                                        $output .= "</div>";
                                    }
                                }
                            } else {
                                $output .= "No designs available?";
                            }
                        } else {
                            // Article view
                            if (!empty($articleCleanData)) {

                                switch (self::$shopOptions['shop_view']) {
                                    case 1:
                                        foreach ($articleCleanData as $articleId => $arrArticle) {
                                            $output .= $this->displayListArticles($articleId, $arrArticle, self::$shopOptions['shop_zoomimagebackground']);
                                        }
                                        break;
                                    case 2:
                                        foreach ($articleCleanData as $articleId => $arrArticle) {
                                            $output .= $this->displayMinArticles($articleId, $arrArticle, self::$shopOptions['shop_zoomimagebackground']);
                                        }
                                        break;
                                    default:
                                        foreach ($articleCleanData as $articleId => $arrArticle) {
                                            $output .= $this->displayArticles($articleId, $arrArticle, self::$shopOptions['shop_zoomimagebackground']);
                                        }
                                        break;
                                }
                            }
                        }

                        $output .= '</div>';

                        $output .= "<div id=\"pagination\">";
                        if ($cArticleNext > 0) {
                            $output .= "<a href=\"" . add_query_arg('pagesp', $paged + 1, get_permalink()) . "\">" . __('next', $this->stringTextdomain) . "</a>";
                        }
                        $output .= "</div>";
                    } else {

                        // display product page
                        $output .= '<div id="spreadplugin-list">';

                        // checkout
                        // add simple spreadplugin-menu
                        $output .= '<div id="spreadplugin-menu" class="spreadplugin-menu">';
                        $output .= '<a href="' . get_page_link() . '">' . __('Back', $this->stringTextdomain);
                        $output .= '<div id="checkout" class="spreadplugin-checkout"><span></span> <a href="' . (!empty($_SESSION['checkoutUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']]) ? $_SESSION['checkoutUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']] : '') . '" target="' . self::$shopOptions['shop_linktarget'] . '" id="basketLink" class="spreadplugin-checkout-link' . (self::$shopOptions['shop_basket_text_icon'] == 1 ? ' button' : '') . '">' . (self::$shopOptions['shop_basket_text_icon'] == 0 ? __('Basket', $this->stringTextdomain) : '') . '</a></div>';
                        $output .= '<div id="cart" class="spreadplugin-cart"></div>';
                        $output .= '</div>';

                        // product
                        if (!empty($articleCleanDataComplete[intval($_GET['splproduct'])])) {
                            $output .= $this->displayDetailPage(intval($_GET['splproduct']), $articleCleanDataComplete[intval($_GET['splproduct'])], self::$shopOptions['shop_zoomimagebackground']);
                        }

                        $output .= '</div>';
                    }
                }

                // End div
                $output .= '</div>';

                // Shipment Table
                if (!empty($shipmentData)) {
                    $output .= '<div id="spreadplugin-shipment-wrapper">
					<table class="shipment-table">';
                    foreach ($shipmentData as $c => $v) {
                        $output .= '<tr>';
                        $output .= '<th colspan="2">' . $c . '</th>';
                        $output .= '</tr>';
                        foreach ($v as $m => $d) {
                            $output .= '<tr>';
                            $output .= '<td>' . __('Order Value', $this->stringTextdomain) . '<br>';
                            if ($d['value-to'] == 0) {
                                $output .= __('over', $this->stringTextdomain) . ' ';
                            }
                            if ($d['value-from'] > 0) {
                                $output .= self::formatPrice($d['value-from'], '') . ' ';
                            }
                            if ($d['value-to'] > 0) {
                                $output .= __('up to', $this->stringTextdomain) . ' ' . self::formatPrice($d['value-to'], '');
                            }
                            $output .= ' </td>';
                            $output .= '<td>' . self::formatPrice($d['price'], '') . '</td>';
                            $output .= '</tr>';
                        }
                    }
                    $output .= '</table>
					</div>';
                }

                return $output;
            }
        }

        /**
         * Function getCacheArticleData
         *
         * @return array Article data
         */
        private static function getCacheArticleData() {
            return get_transient('spreadplugin2-article-cache-' . get_the_ID());
        }

        /**
         * function parseArticleData
         * Retrieves article data and collect
         */
        private function getRawArticleData($pageId) {

            // Limit to read max articles
            $_maxlimit = 1000;

            $articleData = array();
            $articleDataObj = array();

            $apiUrlBase = 'http://api.spreadshirt.' . self::$shopOptions['shop_source'] . '/api/v1/shops/' . self::$shopOptions['shop_id'];
            $apiUrlBase .= (!empty(self::$shopOptions['shop_category']) ? '/articleCategories/' . self::$shopOptions['shop_category'] : '');
            $apiUrlBase .= '/articles?' . (!empty(self::$shopOptions['shop_locale']) ? 'locale=' . self::$shopOptions['shop_locale'] . '&' : '') . 'fullData=true&noCache=true';

            // call first to get count of articles
            $apiUrl = $apiUrlBase . '&limit=' . $_maxlimit;

            $stringXmlShopBase = wp_remote_get($apiUrl, array(
                'timeout' => 120
            ));
            if (isset($stringXmlShopBase->errors) && count($stringXmlShopBase->errors) > 0) {
                if (self::$shopOptions['shop_debug'] == 1) {
                    echo '<br>Base read error: <br>';
                    print_r($stringXmlShopBase->errors);
                }
                die('Error getting articles. Please check Shop-ID, API and secret.');
            }
            if ($stringXmlShopBase['body'][0] != '<') die($stringXmlShopBase['body']);
            $stringXmlShopBase = wp_remote_retrieve_body($stringXmlShopBase);
			// Quickfix for Namespace changes of Spreadshirt API
			$stringXmlShopBase = str_replace('<ns3:','<',$stringXmlShopBase);
            $objArticlesBase = new SimpleXmlElement($stringXmlShopBase);
            if (!is_object($objArticlesBase)) {
                if (self::$shopOptions['shop_debug'] == 1) {
                    echo '<br>Base articles error: <br>';
                    print_r($objArticlesBase);
                }

                die('Articles not loaded');
            }

            return $objArticlesBase;
        }

        /**
         * function getTypesData
         * Retrieves types data
         */
        private function getTypesData() {
            $arrTypes = array();

            // Get ProductTypeDepartments
            $stringTypeApiUrl = 'http://api.spreadshirt.' . self::$shopOptions['shop_source'] . '/api/v1/shops/' . self::$shopOptions['shop_id'] . '/productTypeDepartments?' . (!empty(self::$shopOptions['shop_locale']) ? 'locale=' . self::$shopOptions['shop_locale'] . '&' : '') . 'fullData=true&noCache=true';
            $stringTypeXml = wp_remote_get($stringTypeApiUrl, array(
                'timeout' => 120
            ));
            $stringTypeXml = wp_remote_retrieve_body($stringTypeXml);
			// Quickfix for Namespace changes of Spreadshirt API
			$stringTypeXml = str_replace('<ns3:','<',$stringTypeXml);
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

            return $arrTypes;
        }

        /**
         * function getShipmentData
         * Retrieves types data
         */
        private function getShipmentData() {
            $arrTypes = array();
            $name = '';
            $region = '';

            // Get ProductTypeDepartments
            $stringTypeApiUrl = 'http://api.spreadshirt.' . self::$shopOptions['shop_source'] . '/api/v1/shops/' . self::$shopOptions['shop_id'] . '/shippingTypes?' . (!empty(self::$shopOptions['shop_locale']) ? 'locale=' . self::$shopOptions['shop_locale'] . '&' : '') . 'fullData=true&noCache=true';
            $stringTypeXml = wp_remote_get($stringTypeApiUrl, array(
                'timeout' => 120
            ));
            $stringTypeXml = wp_remote_retrieve_body($stringTypeXml);
			// Quickfix for Namespace changes of Spreadshirt API
			$stringTypeXml = str_replace('<ns3:','<',$stringTypeXml);
            $objTypes = new SimpleXmlElement($stringTypeXml);

            $countryCode = explode("_", self::$shopOptions['shop_locale']);

            if (is_object($objTypes)) {
                foreach ($objTypes->shippingType as $row) {
                    foreach ($row->shippingCountries as $subrow) {
                        foreach ($subrow->shippingCountry as $subrow2) {
                            if ((string)$subrow2->isoCode == $countryCode[1]) {
                                // $name = (string)$subrow2->name;
                                $region = (int)$subrow2->shippingRegion['id'];
                                break;
                            }
                        }
                    }
                }
            }

            if ($region !== '') {
                foreach ($objTypes->shippingType as $row) {
                    foreach ($row->shippingRegions as $subrow) {

                        foreach ($subrow->shippingRegion as $subrow2) {

                            if ((int)$subrow2['id'] == $region) {
                                foreach ($subrow2->shippingCosts as $subrow3) {
                                    foreach ($subrow3->shippingCost as $subrow4) {
                                        // [$name] Landname
                                        $arrTypes[(string)$row->name][] = array(
                                            'value-from' => (float)$subrow4->orderValueRange->from,'value-to' => (float)$subrow4->orderValueRange->to,'price' => (float)$subrow4->cost->vatIncluded
                                        );
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }

            return $arrTypes;
        }

        /**
         * function getSingleArticleData
         * Retrieves article data and save into cache
         */
        private function getSingleArticleData($pageId, $articleId, $place) {
            $articleData = array();
            $stockstates_size = array();
            $stockstates_appearance = array();
            $objProductData = array();
            $objPrintData = array();
            $objArticleData = array();
            $objCurrencyData = array();
            $objProductData = array();

            $apiUrlBase = 'http://api.spreadshirt.' . self::$shopOptions['shop_source'] . '/api/v1/shops/' . self::$shopOptions['shop_id'];
            $apiUrlBase .= '/articles/' . $articleId . '?' . (!empty(self::$shopOptions['shop_locale']) ? 'locale=' . self::$shopOptions['shop_locale'] . '&' : '') . 'fullData=true&noCache=true';

            $apiUrl = $apiUrlBase;

            $stringXmlShop = wp_remote_get($apiUrl, array(
                'timeout' => 120
            ));
            if ($stringXmlShop['body'][0] != '<') return 'Body error: ' . $stringXmlShop['body'];
            $stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
			// Quickfix for Namespace changes of Spreadshirt API
			$stringXmlShop = str_replace('<ns3:','<',$stringXmlShop);

            if (substr($stringXmlShop, 0, 5) != "<?xml" && substr($stringXmlShop, 0, 5) != "<arti") {
                return 'Error fetching URL: ' . $apiUrl;
            }

            $article = new SimpleXmlElement($stringXmlShop);
            if (!is_object($article)) return 'Article empty (object)';

            if ((int)$article['id'] > 0) {

                $url = wp_remote_get((string)$article->product->productType->attributes('http://www.w3.org/1999/xlink') . '?' . (!empty(self::$shopOptions['shop_locale']) ? 'locale=' . self::$shopOptions['shop_locale'] . '&noCache=true' : '&noCache=true'), array(
                    'timeout' => 120
                ));
                $stringXmlArticle = wp_remote_retrieve_body($url);
				// Quickfix for Namespace changes of Spreadshirt API
				$stringXmlArticle = str_replace('<ns3:','<',$stringXmlArticle);

                if (substr($stringXmlArticle, 0, 5) == "<?xml" && substr($stringXmlShop, 0, 5) != "<prod") {
                    $objArticleData = new SimpleXmlElement($stringXmlArticle);
                }

                $url = wp_remote_get((string)$article->price->currency->attributes('http://www.w3.org/1999/xlink'));
                $stringXmlCurreny = wp_remote_retrieve_body($url);
                if (substr($stringXmlCurreny, 0, 5) == "<?xml" || substr($stringXmlCurreny, 0, 5) == "<curr") {
					// Quickfix for Namespace changes of Spreadshirt API
					$stringXmlCurreny = str_replace('<ns3:','<',$stringXmlCurreny);
					$objCurrencyData = new SimpleXmlElement($stringXmlCurreny);
                }

                $url = wp_remote_get((string)$article->product->attributes('http://www.w3.org/1999/xlink') . '?' . (!empty(self::$shopOptions['shop_locale']) ? 'locale=' . self::$shopOptions['shop_locale'] . '&noCache=true' : '&noCache=true'), array(
                    'timeout' => 120
                ));
                $stringXmlProduct = wp_remote_retrieve_body($url);
                if (substr($stringXmlProduct, 0, 5) == "<?xml" || substr($stringXmlProduct, 0, 5) == "<prod") {
                    $objProductData = new SimpleXmlElement($stringXmlProduct);
                }

                if (is_object($objProductData)) {
                    if (!empty($objProductData->configurations->configuration->printType)) {
                        $url = wp_remote_get((string)$objProductData->configurations->configuration->printType->attributes('http://www.w3.org/1999/xlink') . '?' . (!empty(self::$shopOptions['shop_locale']) ? 'locale=' . self::$shopOptions['shop_locale'] . '&noCache=true' : '&noCache=true'), array(
                            'timeout' => 120
                        ));
                        $stringXmlPrint = wp_remote_retrieve_body($url);
                        if (substr($stringXmlPrint, 0, 5) == "<?xml" || substr($stringXmlPrint, 0, 5) == "<prin") {
							// Quickfix for Namespace changes of Spreadshirt API
							$stringXmlPrint = str_replace('<ns3:','<',$stringXmlPrint);
                            $objPrintData = new SimpleXmlElement($stringXmlPrint);
                        }
                    }
                }

                $articleData['name'] = (string)$article->name;
                $articleData['description'] = (string)$article->description;
                $articleData['appearance'] = (int)$article->product->appearance['id'];
                $articleData['view'] = (int)$article->product->defaultValues->defaultView['id'];
                $articleData['type'] = (int)$article->product->productType['id'];
                $articleData['productId'] = (int)$article->product['id'];
                $articleData['pricenet'] = (float)$article->price->vatExcluded;
                $articleData['pricebrut'] = (float)$article->price->vatIncluded;
                $articleData['currencycode'] = (string)$objCurrencyData->isoCode;
                $articleData['productname'] = (string)$objArticleData->name;
                $articleData['productshortdescription'] = (string)$objArticleData->shortDescription;
                $articleData['productdescription'] = (string)$objArticleData->description;

                $articleData['weight'] = (float)$article['weight'];
                $articleData['id'] = (int)$article['id'];
                $articleData['place'] = $place;
                $articleData['designid'] = (int)$article->product->defaultValues->defaultDesign['id'];

                $articleData['printtypename'] = '';
                $articleData['printtypedescription'] = '';

                if (is_object($objPrintData)) {
                    $articleData['printtypename'] = (string)$objPrintData->name;
                    $articleData['printtypedescription'] = (string)$objPrintData->description;
                }

                /**
                 * Stock States disabled at the moment - the informations provided by spreadshirt aren't such reliable as needed
                 * *
                 *
                 * // Assignment of stock availability and matching to articles
                 * // echo (string)$article->name."<br>";
                 * foreach($objArticleData->stockStates->stockState as $val) {
                 * $stockstates_size[(int)$val->size['id']]=(string)$val->available;
                 * $stockstates_appearance[(int)$val->appearance['id']]=(string)$val->available;
                 * }
                 *
                 * foreach($objArticleData->sizes->size as $val) {
                 * // echo (int)$val['id']." ".$stockstates_size[(int)$val['id']]." ". (string)$val->name."<br>";
                 * if ($stockstates_size[(int)$val['id']] == "true") {
                 * $articleData['sizes'][(int)$val['id']]=(string)$val->name;
                 * }
                 * }
                 *
                 * foreach($objArticleData->appearances->appearance as $appearance) {
                 * if ((int)$article->product->appearance['id'] == (int)$appearance['id']) {
                 * $articleData['default_bgc'] = (string)$appearance->colors->color;
                 * }
                 *
                 * // echo (int)$val['id']." ".$stockstates_appearance[(int)$val['id']]." ". (string)$appearance->resources->resource->attributes('xlink', true)."<br>";
                 * if (($article->product->restrictions->freeColorSelection == 'true' && $stockstates_appearance[(int)$appearance['id']] == "true") || (int)$article->product->appearance['id'] == (int)$appearance['id']) {
                 * $articleData['appearances'][(int)$appearance['id']]=(string)$appearance->resources->resource->attributes('xlink', true);
                 * }
                 * }
                 */

                // replace to use stock states || weiter unten ist neuer
                // sizes
                if (!empty($objArticleData->sizes->size)) {
                    foreach ($objArticleData->sizes->size as $val) {

                        $articleData['sizes'][(int)$val['id']]['name'] = (string)$val->name;

                        if (!empty($val->measures->measure[0]->name)) {
                            $articleData['sizes'][(int)$val['id']]['measures'][0]['name'] = (string)$val->measures->measure[0]->name;
                            $articleData['sizes'][(int)$val['id']]['measures'][0]['value'] = (string)$val->measures->measure[0]->value;
                        }
                        if (!empty($val->measures->measure[1]->name)) {
                            $articleData['sizes'][(int)$val['id']]['measures'][1]['name'] = (string)$val->measures->measure[1]->name;
                            $articleData['sizes'][(int)$val['id']]['measures'][1]['value'] = (string)$val->measures->measure[1]->value;
                        }
                    }
                }

                if (!empty($objArticleData->resources)) {
                    foreach ($objArticleData->resources as $val) {
                        foreach ($val->resource as $vr) {
                            if ($vr['type'] == 'size') {
                                $articleData['product-resource-size'] = (string)$vr->attributes('http://www.w3.org/1999/xlink');
                            }
                            if ($vr['type'] == 'detail') {
                                $articleData['product-resource-detail'] = (string)$vr->attributes('http://www.w3.org/1999/xlink');
                            }
                        }
                    }
                }

                if (!empty($objArticleData->appearances->appearance)) {
                    foreach ($objArticleData->appearances->appearance as $appearance) {
                        if ((int)$article->product->appearance['id'] == (int)$appearance['id']) {
                            $articleData['default_bgc'] = (string)$appearance->colors->color;
                        }

                        if ($article->product->restrictions->freeColorSelection == 'true' || (int)$article->product->appearance['id'] == (int)$appearance['id']) {
                            $articleData['appearances'][(int)$appearance['id']] = (string)$appearance->resources->resource->attributes('http://www.w3.org/1999/xlink');
                        }
                    }
                }
                // replace end

                if (!empty($objArticleData->views->view)) {
                    foreach ($objArticleData->views->view as $view) {
                        $articleData['views'][(int)$view['id']] = (string)$article->resources->resource->attributes('http://www.w3.org/1999/xlink');
                    }
                }

                return $articleData;
            }

            return 'Article empty';
        }

        /**
         * Function getCacheDesignsData
         *
         * @return array designs data
         */
        private static function getCacheDesignsData() {
            return get_transient('spreadplugin2-designs-cache-' . get_the_ID());
        }

        /**
         * Function getDesignsData
         *
         * Retrieves design data and saves directly into cache
         * Has a quick load time, so possible to save directly to cache
         */
        private function getDesignsData($pageId = 0) {

            // get page Id if not set in args
            $pageId = ($pageId == 0 ? get_the_ID() : $pageId);

            $arrTypes = array();
            $apiUrlBase = 'http://api.spreadshirt.' . self::$shopOptions['shop_source'] . '/api/v1/shops/' . self::$shopOptions['shop_id'];
            // $apiUrlBase .= (!empty(self::$shopOptions['shop_category'])?'/articleCategories/'.self::$shopOptions['shop_category']:'');
            $apiUrlBase .= '/designs?' . (!empty(self::$shopOptions['shop_locale']) ? 'locale=' . self::$shopOptions['shop_locale'] . '&' : '') . 'fullData=true&noCache=true';

            // call first to get count of articles
            $apiUrl = $apiUrlBase . '&limit=' . rand(2, 999); // randomize to avoid spreadshirt caching issues

            $stringXmlShop = wp_remote_get($apiUrl, array(
                'timeout' => 120
            ));
            if (isset($stringXmlShop->errors) && count($stringXmlShop->errors) > 0) die('Error getting articles. Please check Shop-ID, API and secret.');
            if ($stringXmlShop['body'][0] != '<') die($stringXmlShop['body']);
            $stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
			// Quickfix for Namespace changes of Spreadshirt API
			$stringXmlShop = str_replace('<ns3:','<',$stringXmlShop);
            $objArticles = new SimpleXmlElement($stringXmlShop);
            if (!isset($objArticles) || !is_object($objArticles)) die('Articles not loaded');

            // re-call to read articles with count
            // read max 1000 articles because of spreadshirt max. limit
            $apiUrl = $apiUrlBase . '&limit=' . ($objArticles['count'] <= 1 ? 2 : ($objArticles['count'] < 1000 ? $objArticles['count'] : 1000));

            $stringXmlShop = wp_remote_get($apiUrl, array(
                'timeout' => 120
            ));
            if (isset($stringXmlShop->errors) && count($stringXmlShop->errors) > 0) die('Error getting articles. Please check your Shop-ID.');
            if ($stringXmlShop['body'][0] != '<') die($stringXmlShop['body']);
            $stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
 			// Quickfix for Namespace changes of Spreadshirt API
			$stringXmlShop = str_replace('<ns3:','<',$stringXmlShop);
           $objArticles = new SimpleXmlElement($stringXmlShop);
            if (!is_object($objArticles)) die('Designs not loaded');

            if ($objArticles['count'] > 0) {

                // read articles
                $i = 0;
                foreach ($objArticles->design as $article) {

                    $articleData[(int)$article['id']]['name'] = (string)$article->name;
                    $articleData[(int)$article['id']]['description'] = (string)$article->description;
                    $articleData[(int)$article['id']]['appearance'] = (int)$article->product->appearance['id'];
                    // $articleData[(int)$article['id']]['view']=(int)$article->product->defaultValues->defaultView['id'];
                    $articleData[(int)$article['id']]['type'] = (int)$article->product->productType['id'];
                    $articleData[(int)$article['id']]['productId'] = (int)$article->product['id'];
                    $articleData[(int)$article['id']]['pricenet'] = (float)$article->price->vatExcluded;
                    $articleData[(int)$article['id']]['pricebrut'] = (float)$article->price->vatIncluded;
                    // $articleData[(int)$article['id']]['currencycode']=(string)$objCurrencyData->isoCode; // @TODO Check
                    $articleData[(int)$article['id']]['resource0'] = (string)$article->resources->resource[0]->attributes('http://www.w3.org/1999/xlink');
                    $articleData[(int)$article['id']]['resource2'] = (string)$article->resources->resource[1]->attributes('http://www.w3.org/1999/xlink');
                    // $articleData[(int)$article['id']]['productdescription']=(string)$objArticleData->description;
                    $articleData[(int)$article['id']]['weight'] = (float)$article['weight'];
                    $articleData[(int)$article['id']]['place'] = $i;
                    $articleData[(int)$article['id']]['designid'] = (int)$article['id'];

                    $i++;
                }

                set_transient('spreadplugin2-designs-cache-' . $pageId, $articleData, self::$shopCache);
            }
        }

        /**
         * Function displayArticles
         *
         * Displays the articles
         *
         * @return html
         */
        private function displayArticles($id, $article, $backgroundColor = '') {
            $imgSrc = '//image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . $article['productId'] . '/views/' . $article['view'] . ',width=' . self::$shopOptions['shop_imagesize'] . ',height=' . self::$shopOptions['shop_imagesize'];

            $output = '<div class="spreadplugin-article spreadplugin-clearfix grid-view" id="article_' . $id . '" style="width:' . (self::$shopOptions['shop_imagesize'] + 7) . 'px">';
            $output .= '<a name="' . $id . '"></a>';
            $output .= '<h3>' . (!empty($article['name']) ? htmlspecialchars($article['name'], ENT_QUOTES) : '') . '</h3>';
            $output .= '<form method="post" id="form_' . $id . '">';

            // edit article button
            if (self::$shopOptions['shop_designer'] == 1) {
                $output .= ' <div class="edit-wrapper-integrated" data-designid="' . $article['designid'] . '" data-productid="' . (!empty($article['productid'])?$article['productid']:'') . '" data-viewid="' . $article['view'] . '" data-appearanceid="' . $article['appearance'] . '" data-producttypeid="' . $article['type'] . '"><img src="' . plugins_url('/img/edit.png', __FILE__) . '"></div>';
            } else
                if (self::$shopOptions['shop_designer'] == 2 && self::$shopOptions['shop_designershop'] > 0) {
                    $output .= ' <div class="edit-wrapper"><a href="//' . self::$shopOptions['shop_designershop'] . '.spreadshirt.' . self::$shopOptions['shop_source'] . '/-D1/customize/product/' . $article['productId'] . '?noCache=true" target="' . self::$shopOptions['shop_linktarget'] . '" title="' . __('Edit article', $this->stringTextdomain) . '"><img src="' . plugins_url('/img/edit.png', __FILE__) . '"></a></div>';
                }

            // display preview image
            $output .= '<div class="image-wrapper">';
            $output .= '<img src="';

            if (self::$shopOptions['shop_lazyload'] == 0) {
                $output .= $imgSrc;
            } else {
                $output .= plugins_url('/img/blank.gif', __FILE__);
            }

            $output .= '" alt="' . (!empty($article['name']) ? htmlspecialchars($article['name'], ENT_QUOTES) : '') . '" id="previewimg_' . $id . '" data-zoom-image="//image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . $article['productId'] . '/views/' . $article['view'] . ',width=600,height=600' . (!empty($backgroundColor) ? ',backgroundColor=' . $backgroundColor : '') . '" class="preview lazyimg" data-original="' . $imgSrc . '" />';
            $output .= '</div>';

            // add a select with available sizes
            if (isset($article['sizes']) && is_array($article['sizes'])) {
                $output .= '<div class="size-wrapper spreadplugin-clearfix"><span>' . __('Size', $this->stringTextdomain) . ':</span> <select id="size-select" name="size">';

                foreach ($article['sizes'] as $k => $v) {
                    $output .= '<option value="' . $k . '">' . (!empty($v['name'])?$v['name']:$k) . '</option>';
                }

                $output .= '</select></div>';
            }

            if (self::$shopOptions['shop_enablelink'] == 1) {
                $output .= '<div class="details-wrapper2 spreadplugin-clearfix"><a href="' . add_query_arg('splproduct', $id, get_permalink()) . '" target="' . self::$shopOptions['shop_linktarget'] . '">' . __('Details', $this->stringTextdomain) . '</a></div>';
            }

            $output .= '<div class="separator"></div>';

            // add a list with availabel product colors
            if (isset($article['appearances']) && is_array($article['appearances'])) {
                $output .= '<div class="color-wrapper spreadplugin-clearfix"><span>' . __('Color', $this->stringTextdomain) . ':</span> <ul class="colors" name="color">';

                foreach ($article['appearances'] as $k => $v) {
                    $output .= '<li value="' . $k . '"><img src="' . $this->cleanURL($v) . '" alt="" /></li>';
                }

                $output .= '</ul></div>';
            }

            // add a list with available product views
            if (isset($article['views']) && is_array($article['views'])) {
                $output .= '<ul class="views" name="views">';

                $_vc = 0;
                foreach ($article['views'] as $k => $v) {

                    if (self::$shopOptions['shop_lazyload'] == 0) {
                        $liSrc = $this->cleanURL($v) . ',viewId=' . $k . ',width=42,height=42';
                    } else {
                        $liSrc = plugins_url('/img/blank.gif', __FILE__);
                    }

                    $output .= '<li value="' . $k . '"><img src="' . $liSrc . '" data-original="' . $this->cleanURL($v) . ',viewId=' . $k . ',width=42,height=42" class="previewview lazyimg" alt="" id="viewimg_' . $id . '" /></li>';
                    if ($_vc == 3) break;
                    $_vc++;
                }

                $output .= '</ul>';
            }

            // Short product description
            $output .= '<div class="separator"></div>';
            $output .= '<div class="product-name">';
            $output .= htmlspecialchars($article['productname'], ENT_QUOTES);
            $output .= '</div>';

            // Show description link if not empty
            if (!empty($article['description'])) {
                $output .= '<div class="separator"></div>';

                if (self::$shopOptions['shop_showdescription'] == 0) {
                    $output .= '<div class="description-wrapper"><div class="header"><a>' . __('Show article description', $this->stringTextdomain) . '</a></div><div class="description">' . htmlspecialchars($article['description'], ENT_QUOTES) . '</div></div>';
                } else {
                    $output .= '<div class="description-wrapper">' . htmlspecialchars($article['description'], ENT_QUOTES) . '</div>';
                }
            }

            // Show product description link if set
            if (self::$shopOptions['shop_showproductdescription'] == 1) {
                $output .= '<div class="separator"></div>';

                if (self::$shopOptions['shop_showdescription'] == 0) {
                    $output .= '<div class="product-description-wrapper"><div class="header"><a>' . __('Show product description', $this->stringTextdomain) . '</a></div><div class="description">' . $article['productdescription'] . '</div></div>';
                } else {
                    $output .= '<div class="product-description-wrapper">' . $article['productdescription'] . '</div>';
                }
            }

            $output .= '<input type="hidden" value="' . $article['appearance'] . '" id="appearance" name="appearance" />';
            $output .= '<input type="hidden" value="' . $article['view'] . '" id="view" name="view" />';
            $output .= '<input type="hidden" value="' . $id . '" id="article" name="article" />';

            $output .= '<div class="separator"></div>';
            $output .= '<div class="price-wrapper">';
            if (self::$shopOptions['shop_showextendprice'] == 1) {
                $output .= '<span id="price-without-tax">' . __('Price (without tax):', $this->stringTextdomain) . " " . self::formatPrice($article['pricenet'], $article['currencycode']) . "<br /></span>";
                $output .= '<span id="price-with-tax">' . __('Price (with tax):', $this->stringTextdomain) . " " . self::formatPrice($article['pricebrut'], $article['currencycode']) . "</span>";
                $output .= '<br><div class="additionalshippingcosts">';
                $output .= __('excl. <a class="shipping-window">Shipping</a>', $this->stringTextdomain);
                $output .= '</div>';
            } else {
                $output .= '<span id="price">' . __('Price:', $this->stringTextdomain) . " " . self::formatPrice($article['pricebrut'], $article['currencycode']) . "</span>";
            }
            $output .= '</div>';

            // order buttons
            $output .= '<input type="text" value="1" id="quantity" name="quantity" maxlength="4" />';
            $output .= '<input type="submit" name="submit" value="' . __('Add to basket', $this->stringTextdomain) . '" /><br>';

            // Social buttons
            if (self::$shopOptions['shop_social'] == true) {
                $output .= '
				<ul class="soc-icons">
				<li><a target="_blank" data-color="#5481de" class="fb" href="//www.facebook.com/sharer.php?u=' . urlencode(add_query_arg('splproduct', $id, get_permalink())) . '&t=' . rawurlencode(get_the_title()) . '" title="Facebook"></a></li>
				<li><a target="_blank" data-color="#06ad18" class="goog" href="//plus.google.com/share?url=' . urlencode(add_query_arg('splproduct', $id, get_permalink())) . '" title="Google"></a></li>
				<li><a target="_blank" data-color="#2cbbea" class="twt" href="//twitter.com/home?status=' . rawurlencode(get_the_title()) . ' - ' . urlencode(add_query_arg('splproduct', $id, get_permalink())) . '" title="Twitter"></a></li>
				<li><a target="_blank" data-color="#e84f61" class="pin" href="//pinterest.com/pin/create/button/?url=' . rawurlencode(add_query_arg('splproduct', $id, get_permalink())) . '&media=' . rawurlencode('http://image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . $article['productId'] . '/views/' . $article['view'] . ',width=' . self::$shopOptions['shop_imagesize'] . ',height=' . self::$shopOptions['shop_imagesize'] . '') . ',width=' . self::$shopOptions['shop_imagesize'] . ',height=' . self::$shopOptions['shop_imagesize'] . '&description=' . (!empty($article['description']) ? htmlspecialchars($article['description'], ENT_QUOTES) : 'Product') . '" title="Pinterest"></a></li>
				</ul>
				';

                /*
                 * <li><a target="_blank" data-color="#459ee9" class="in" href="#" title="LinkedIn"></a></li> <li><a target="_blank" data-color="#ee679b" class="drb" href="#" title="Dribbble"></a></li> <li><a target="_blank" data-color="#4887c2" class="tumb" href="#" title="Tumblr"></a></li> <li><a target="_blank" data-color="#f23a94" class="flick" href="#" title="Flickr"></a></li> <li><a target="_blank" data-color="#74c3dd" class="vim" href="#" title="Vimeo"></a></li> <li><a target="_blank" data-color="#4a79ff" class="delic" href="#" title="Delicious"></a></li> <li><a target="_blank" data-color="#6ea863" class="forr" href="#" title="Forrst"></a></li> <li><a target="_blank" data-color="#f6a502" class="hi5" href="#" title="Hi5"></a></li> <li><a target="_blank" data-color="#e3332a" class="last" href="#" title="Last.fm"></a></li> <li><a target="_blank" data-color="#3c6ccc" class="space" href="#" title="Myspace"></a></li> <li><a target="_blank" data-color="#229150" class="newsv" href="#" title="Newsvine"></a></li> <li><a href="#" class="pica" title="Picasa" data-color="#b163c8" target="_blank"></a></li> <li><a href="#" class="tech" title="Technorati" data-color="#3ac13a" target="_blank"></a></li> <li><a href="#" class="rss" title="RSS" data-color="#f18d3c" target="_blank"></a></li> <li><a href="#" class="rdio" title="Rdio" data-color="#2c7ec7" target="_blank"></a></li> <li><a href="#" class="share" title="ShareThis" data-color="#359949" target="_blank"></a></li> <li><a href="#" class="skyp" title="Skype" data-color="#00adf1" target="_blank"></a></li> <li><a href="#" class="slid" title="SlideShare" data-color="#ef8122" target="_blank"></a></li> <li><a href="#" class="squid" title="Squidoo" data-color="#f87f27" target="_blank"></a></li> <li><a href="#" class="stum" title="StumbleUpon" data-color="#f05c38" target="_blank"></a></li> <li><a href="#" class="what" title="WhatsApp" data-color="#3ebe2b" target="_blank"></a></li> <li><a href="#" class="wp" title="Wordpress" data-color="#3078a9" target="_blank"></a></li> <li><a href="#" class="ytb" title="Youtube" data-color="#df3434" target="_blank"></a></li> <li><a href="#" class="digg" title="Digg" data-color="#326ba0" target="_blank"></a></li> <li><a href="#" class="beh" title="Behance" data-color="#2d9ad2" target="_blank"></a></li> <li><a href="#" class="yah" title="Yahoo" data-color="#883890" target="_blank"></a></li> <li><a href="#" class="blogg" title="Blogger" data-color="#f67928" target="_blank"></a></li> <li><a href="#" class="hype" title="Hype Machine" data-color="#f13d3d" target="_blank"></a></li> <li><a href="#" class="groove" title="Grooveshark" data-color="#498eba" target="_blank"></a></li> <li><a href="#" class="sound" title="SoundCloud" data-color="#f0762c" target="_blank"></a></li> <li><a href="#" class="insta" title="Instagram" data-color="#c2784e" target="_blank"></a></li> <li><a href="#" class="vk" title="Vkontakte" data-color="#5f84ab" target="_blank"></a></li>
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
        private function displayListArticles($id, $article, $backgroundColor = '') {
            $imgSrc = '//image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . $article['productId'] . '/views/' . $article['view'] . ',width=' . self::$shopOptions['shop_imagesize'] . ',height=' . self::$shopOptions['shop_imagesize'];

            $output = '<div class="spreadplugin-article list-view" id="article_' . $id . '">';
            $output .= '<a name="' . $id . '"></a>';
            $output .= '<form method="post" id="form_' . $id . '"><div class="articleContentLeft">';

            // edit article button
            if (self::$shopOptions['shop_designer'] == 1) {
                $output .= ' <div class="edit-wrapper-integrated" data-designid="' . $article['designid'] . '" data-productid="' . (!empty($article['productid'])?$article['productid']:'') . '" data-viewid="' . $article['view'] . '" data-appearanceid="' . $article['appearance'] . '" data-producttypeid="' . $article['type'] . '"><img src="' . plugins_url('/img/edit.png', __FILE__) . '"></div>';
            } else
                if (self::$shopOptions['shop_designer'] == 2 && self::$shopOptions['shop_designershop'] > 0) {
                    $output .= ' <div class="edit-wrapper"><a href="//' . self::$shopOptions['shop_designershop'] . '.spreadshirt.' . self::$shopOptions['shop_source'] . '/-D1/customize/product/' . $article['productId'] . '?noCache=true" target="' . self::$shopOptions['shop_linktarget'] . '" title="' . __('Edit article', $this->stringTextdomain) . '"><img src="' . plugins_url('/img/edit.png', __FILE__) . '"></a></div>';
                }

            // display preview image
            $output .= '<div class="image-wrapper">';
            $output .= '<img src="';

            if (self::$shopOptions['shop_lazyload'] == 0) {
                $output .= $imgSrc;
            } else {
                $output .= plugins_url('/img/blank.gif', __FILE__);
            }

            $output .= '" alt="' . (!empty($article['name']) ? htmlspecialchars($article['name'], ENT_QUOTES) : '') . '" id="previewimg_' . $id . '" data-zoom-image="//image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . $article['productId'] . '/views/' . $article['view'] . ',width=600,height=600' . (!empty($backgroundColor) ? ',backgroundColor=' . $backgroundColor : '') . '" class="preview lazyimg" data-original="' . $imgSrc . '" />';
            $output .= '</div>';

            // Short product description
            $output .= '<div class="product-name">';
            $output .= htmlspecialchars($article['productname'], ENT_QUOTES);
            $output .= '</div>';

            if (self::$shopOptions['shop_enablelink'] == 1) {
                $output .= '<div class="details-wrapper2 spreadplugin-clearfix"><a href="' . add_query_arg('splproduct', $id, get_permalink()) . '" target="' . self::$shopOptions['shop_linktarget'] . '">' . __('Details', $this->stringTextdomain) . '</a></div>';
            }

            $output .= '</div><div class="articleContentRight"><h3>' . (!empty($article['name']) ? htmlspecialchars($article['name'], ENT_QUOTES) : '') . '</h3>';

            // Show description link if not empty
            if (!empty($article['description'])) {
                if (self::$shopOptions['shop_showdescription'] == 0) {
                    $output .= '<div class="description-wrapper"><div class="header"><a>' . __('Show article description', $this->stringTextdomain) . '</a></div><div class="description">' . htmlspecialchars($article['description'], ENT_QUOTES) . '</div></div>';
                } else {
                    $output .= '<div class="description-wrapper">' . htmlspecialchars($article['description'], ENT_QUOTES) . '</div>';
                }
            }

            // add a select with available sizes
            if (isset($article['sizes']) && is_array($article['sizes'])) {
                $output .= '<div class="size-wrapper spreadplugin-clearfix"><span>' . __('Size', $this->stringTextdomain) . ':</span> <select id="size-select" name="size">';

                foreach ($article['sizes'] as $k => $v) {
                    $output .= '<option value="' . $k . '">' . (!empty($v['name'])?$v['name']:$k) . '</option>';
                }

                $output .= '</select></div>';
            }

            // add a list with availabel product colors
            if (isset($article['appearances']) && is_array($article['appearances'])) {
                $output .= '<div class="color-wrapper spreadplugin-clearfix"><span>' . __('Color', $this->stringTextdomain) . ':</span> <ul class="colors" name="color">';

                foreach ($article['appearances'] as $k => $v) {
                    $output .= '<li value="' . $k . '"><img src="' . $this->cleanURL($v) . '" alt="" /></li>';
                }

                $output .= '</ul></div>';
            }

            // add a list with available product views
            if (isset($article['views']) && is_array($article['views'])) {
                $output .= '<div class="views-wrapper spreadplugin-clearfix"><ul class="views" name="views">';

                $_vc = 0;
                foreach ($article['views'] as $k => $v) {

                    if (self::$shopOptions['shop_lazyload'] == 0) {
                        $liSrc = $this->cleanURL($v) . ',viewId=' . $k . ',width=42,height=42';
                    } else {
                        $liSrc = plugins_url('/img/blank.gif', __FILE__);
                    }

                    $output .= '<li value="' . $k . '"><img src="' . $liSrc . '" data-original="' . $this->cleanURL($v) . ',viewId=' . $k . ',width=42,height=42" class="previewview lazyimg" alt="" id="viewimg_' . $id . '" /></li>';
                    if ($_vc == 3) break;
                    $_vc++;
                }

                $output .= '</ul></div>';
            }

            $output .= '<input type="hidden" value="' . $article['appearance'] . '" id="appearance" name="appearance" />';
            $output .= '<input type="hidden" value="' . $article['view'] . '" id="view" name="view" />';
            $output .= '<input type="hidden" value="' . $id . '" id="article" name="article" />';

            $output .= '<div class="price-wrapper spreadplugin-clearfix">';
            if (self::$shopOptions['shop_showextendprice'] == 1) {
                $output .= '<span id="price-without-tax">' . __('Price (without tax):', $this->stringTextdomain) . " " . self::formatPrice($article['pricenet'], $article['currencycode']) . "<br /></span>";
                $output .= '<span id="price-with-tax">' . __('Price (with tax):', $this->stringTextdomain) . " " . self::formatPrice($article['pricebrut'], $article['currencycode']) . "</span>";
                $output .= '<br><div class="additionalshippingcosts">';
                $output .= __('excl. <a class="shipping-window">Shipping</a>', $this->stringTextdomain);
                $output .= '</div>';
            } else {
                $output .= '<span id="price">' . __('Price:', $this->stringTextdomain) . " " . self::formatPrice($article['pricebrut'], $article['currencycode']) . "</span>";
            }
            $output .= '</div>';

            // order buttons
            $output .= '<input type="text" value="1" id="quantity" name="quantity" maxlength="4" />';
            $output .= '<input type="submit" name="submit" value="' . __('Add to basket', $this->stringTextdomain) . '" /><br>';

            // Social buttons
            if (self::$shopOptions['shop_social'] == true) {
                $output .= '
				<ul class="soc-icons">
				<li><a target="_blank" data-color="#5481de" class="fb" href="//www.facebook.com/sharer.php?u=' . urlencode(add_query_arg('splproduct', $id, get_permalink())) . '&t=' . rawurlencode(get_the_title()) . '" title="Facebook"></a></li>
				<li><a target="_blank" data-color="#06ad18" class="goog" href="//plus.google.com/share?url=' . urlencode(add_query_arg('splproduct', $id, get_permalink())) . '" title="Google"></a></li>
				<li><a target="_blank" data-color="#2cbbea" class="twt" href="//twitter.com/home?status=' . rawurlencode(get_the_title()) . ' - ' . urlencode(add_query_arg('splproduct', $id, get_permalink())) . '" title="Twitter"></a></li>
				<li><a target="_blank" data-color="#e84f61" class="pin" href="//pinterest.com/pin/create/button/?url=' . rawurlencode(add_query_arg('splproduct', $id, get_permalink())) . '&media=' . rawurlencode('http://image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . $article['productId'] . '/views/' . $article['view'] . ',width=' . self::$shopOptions['shop_imagesize'] . ',height=' . self::$shopOptions['shop_imagesize'] . '') . ',width=' . self::$shopOptions['shop_imagesize'] . ',height=' . self::$shopOptions['shop_imagesize'] . '&description=' . (!empty($article['description']) ? htmlspecialchars($article['description'], ENT_QUOTES) : 'Product') . '" title="Pinterest"></a></li>
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
         * Function displayMinArticles
         *
         * Displays the articles
         *
         * @return html
         */
        private function displayMinArticles($id, $article, $backgroundColor = '') {
            $imgSrc = '//image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . $article['productId'] . '/views/' . $article['view'] . ',width=' . self::$shopOptions['shop_imagesize'] . ',height=' . self::$shopOptions['shop_imagesize'];

            $output = '<div class="spreadplugin-article spreadplugin-clearfix min-view" id="article_' . $id . '" style="width:' . (self::$shopOptions['shop_imagesize'] + 7) . 'px">';
            $output .= '<a name="' . $id . '"></a>';
            $output .= '<form method="post" id="form_' . $id . '">';

            // edit article button
            if (self::$shopOptions['shop_designer'] == 1) {
                $output .= ' <div class="edit-wrapper-integrated" data-designid="' . $article['designid'] . '" data-productid="' . (!empty($article['productid'])?$article['productid']:'') . '" data-viewid="' . $article['view'] . '" data-appearanceid="' . $article['appearance'] . '" data-producttypeid="' . $article['type'] . '"><img src="' . plugins_url('/img/edit.png', __FILE__) . '"></div>';
            } else
                if (self::$shopOptions['shop_designer'] == 2 && self::$shopOptions['shop_designershop'] > 0) {
                    $output .= ' <div class="edit-wrapper"><a href="//' . self::$shopOptions['shop_designershop'] . '.spreadshirt.' . self::$shopOptions['shop_source'] . '/-D1/customize/product/' . $article['productId'] . '?noCache=true" target="' . self::$shopOptions['shop_linktarget'] . '" title="' . __('Edit article', $this->stringTextdomain) . '"><img src="' . plugins_url('/img/edit.png', __FILE__) . '"></a></div>';
                }

            // display preview image
            $output .= '<div class="image-wrapper">';
            $output .= '<a href="' . add_query_arg('splproduct', $id, get_permalink()) . '" target="' . self::$shopOptions['shop_linktarget'] . '"><img src="';

            if (self::$shopOptions['shop_lazyload'] == 0) {
                $output .= $imgSrc;
            } else {
                $output .= plugins_url('/img/blank.gif', __FILE__);
            }

            $output .= '" alt="' . (!empty($article['name']) ? htmlspecialchars($article['name'], ENT_QUOTES) : '') . '" id="previewimg_' . $id . '" data-zoom-image="//image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . $article['productId'] . '/views/' . $article['view'] . ',width=600,height=600' . (!empty($backgroundColor) ? ',backgroundColor=' . $backgroundColor : '') . '" class="preview lazyimg" data-original="' . $imgSrc . '" /></a>';
            $output .= '</div>';

            $output .= '<h3>' . (!empty($article['name']) ? htmlspecialchars($article['name'], ENT_QUOTES) : '') . '</h3>';

            $output .= '<div class="price-wrapper">' . self::formatPrice($article['pricebrut'], $article['currencycode']) . '</div>';

            $output .= '<div class="actions">';

            // add a select with available sizes
            if (isset($article['sizes']) && is_array($article['sizes'])) {
                $output .= '<div class="size-wrapper spreadplugin-clearfix"><span>' . __('Size', $this->stringTextdomain) . ':</span> <select id="size-select" name="size">';

                foreach ($article['sizes'] as $k => $v) {
                    $output .= '<option value="' . $k . '">' . (!empty($v['name'])?$v['name']:$k) . '</option>';
                }

                $output .= '</select></div>';
            }

            $output .= '<input type="hidden" value="' . $article['appearance'] . '" id="appearance" name="appearance" />';
            $output .= '<input type="hidden" value="' . $article['view'] . '" id="view" name="view" />';
            $output .= '<input type="hidden" value="' . $id . '" id="article" name="article" />';

            $output .= '<div class="add-basket-wrapper spreadplugin-clearfix"><input type="submit" name="submit" class="add-basket-button" value=""></div>';

            // order buttons
            $output .= '<input type="hidden" value="1" id="quantity" name="quantity" />';

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
        private function displayDesigns($id, $designData, $articleData, $bgc = false) {
            $addStyle = '';
            $dSrc = '';
            if ($bgc) $addStyle = 'style="background-color:rgba(' . $bgc[0] . ',' . $bgc[1] . ',' . $bgc[2] . ',0.4);"';

            if (self::$shopOptions['shop_lazyload'] == 0) {
                $dSrc = $this->cleanURL($designData['resource2']) . ',width=' . self::$shopOptions['shop_imagesize'] . ',height=' . self::$shopOptions['shop_imagesize'];
            } else {
                $dSrc = plugins_url('/img/blank.gif', __FILE__);
            }

            $output = '<div class="spreadplugin-design spreadplugin-clearfix" id="design_' . $id . '" style="width:187px">';
            $output .= '<a name="' . $id . '"></a>';
            $output .= '<h3>' . htmlspecialchars($designData['name'], ENT_QUOTES) . '</h3>';
            $output .= '<div class="image-wrapper" ' . $addStyle . '>';
            $output .= '<img src="' . $dSrc . '" class="lazyimg" data-original="' . $this->cleanURL($designData['resource2']) . ',width=' . self::$shopOptions['shop_imagesize'] . ',height=' . self::$shopOptions['shop_imagesize'] . '" alt="' . htmlspecialchars($designData['name'], ENT_QUOTES) . '" id="compositedesignimg_' . $id . '" />'; // style="display:none;" // title="'.htmlspecialchars($designData['productdescription'],ENT_QUOTES).'"
            $output .= '<span class="img-caption">' . __('Click to view the articles', $this->stringTextdomain) . '</em></span>';
            $output .= '</div>';

            // Show description link if not empty
            if (!empty($designData['description']) && $designData['description'] != 'null') {
                $output .= '<div class="separator"></div>';
                $output .= '<div class="description-wrapper">
				<div class="header"><a>' . __('Show description', $this->stringTextdomain) . '</a></div>
				<div class="description">' . htmlspecialchars($designData['description'], ENT_QUOTES) . '</div>
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
					<element id="' . $data['articleId'] . '" type="sprd:' . (array_key_exists('type', $data) && $data['type'] == 1 ? 'product' : 'article') . '" xlink:href="http://api.spreadshirt.' . self::$shopOptions['shop_source'] . '/api/v1/shops/' . $data['shopId'] . '/' . (array_key_exists('type', $data) && $data['type'] == 1 ? 'product' : 'article') . '/' . $data['articleId'] . '">
					<properties>
					<property key="appearance">' . $data['appearance'] . '</property>
					<property key="size">' . $data['size'] . '</property>
					</properties>
					</element>
					<links>
					<link type="edit" xlink:href="http://' . $data['shopId'] . '.spreadshirt.' . self::$shopOptions['shop_source'] . '/-A' . $data['articleId'] . '"/>
					<link type="continueShopping" xlink:href="http://' . $data['shopId'] . '.spreadshirt.' . self::$shopOptions['shop_source'] . '"/>
					</links>
					</basketItem>');

            $header = array();
            $header[] = self::createAuthHeader("POST", $basketItemsUrl);
            $header[] = "Content-Type: application/xml";
            $result = self::oldHttpRequest($basketItemsUrl, $header, 'POST', $basketItem->asXML());

            if ($result) {
                return '1';
            }

            return '0';
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
            $basketItemsUrl = $basketUrl . "/items/" . $itemId;

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
        private static function createBasket($shop, $namespaces) {
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
            $checkoutUrl = '';

            $basketCheckoutUrl = $basketUrl . "/checkout";
            $header = array();
            $header[] = self::createAuthHeader("GET", $basketCheckoutUrl);
            $header[] = "Content-Type: application/xml";
            $result = self::oldHttpRequest($basketCheckoutUrl, $header, 'GET');
			// Quickfix for Namespace changes of Spreadshirt API
			$result = str_replace('<ns3:','<',$result);

            if ($result[0] == '<') {
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
            $sig = sha1("$data " . self::$shopOptions['shop_secret']);

            return "Authorization: SprdAuth apiKey=\"" . self::$shopOptions['shop_api'] . "\", data=\"$data\", sig=\"$sig\"";
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

            foreach ($fields as $field) {
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
                if ($result[0] == '<') {
					// Quickfix for Namespace changes of Spreadshirt API
					$result = str_replace('<ns3:','<',$result);
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
        private static function getInBasketQuantity($source) {
            $intInBasket = 0;

            if (isset($_SESSION['basketUrl'][$source])) {

                $basketItems = self::getBasket($_SESSION['basketUrl'][$source]);

                if (!empty($basketItems)) {
                    foreach ($basketItems->basketItems->basketItem as $item) {
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
         * @return string bool
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
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

                    break;
            }

            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            $status = isset($info['http_code']) ? $info['http_code'] : null;
            @curl_close($ch);

            // debug output
            if (self::$shopOptions['shop_debug'] == 1) {
                print_r($result);
                $debug = explode("\r\n\r\n", $result);
                if (!empty($debug[1])) {
                    print_r(self::ownGzDecode($debug[1]));
                }
            }

            if (in_array($status, array(
                200,201,204,403,406
            ))) {
                return $result;
            }

            return false;
        }

        /**
         * call to merge the designer shop basket with the api basket
         * @TODO doesn't work yet - Spreadshirt's API is beta :(
         *
        public function mergeBaskets() {
            $header = array();
            $basketId = "";
            $coolUrl = "";

            if (!empty($_SESSION['basketUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']])) {

                if (preg_match("/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/", $_SESSION['basketUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']], $found)) {
                    $basketId = $found[0];
                }

                // cool widget url
                $coolUrl = "http://www.spreadshirt.de/de/DE/Widget/Www/synchronizeBasket/basket/" . $basketId . "/toApi/true";

                $result = self::oldHttpRequest($coolUrl, $header, 'GET');
            }

            echo $result;
            die();
        }
		*/

        /**
         * Function loadHead
         */
        public function loadHead() {
            $conOp = $this->getAdminOptions();

            if (!empty($conOp['shop_customcss'])) {
                echo '
				<style type="text/css">
				' . stripslashes($conOp['shop_customcss']) . '
				</style>
				';
            }
        }

        /**
         * Function loadFoot
         */
        public function loadFoot() {
            echo "
					<script language='javascript' type='text/javascript'>
					/**
					* Spreadplugin vars
					*/

					var textHideDesc = '" . esc_attr__('Hide article description', $this->stringTextdomain) . "';
					var textShowDesc = '" . esc_attr__('Show article description', $this->stringTextdomain) . "';
					var textProdHideDesc = '" . esc_attr__('Hide product description', $this->stringTextdomain) . "';
					var textProdShowDesc = '" . esc_attr__('Show product description', $this->stringTextdomain) . "';
					var loadingImage = '" . plugins_url('/img/loading.gif', __FILE__) . "';
					var loadingMessage = '" . esc_attr__('Loading new articles...', $this->stringTextdomain) . "';
					var loadingFinishedMessage = '" . esc_attr__('You have reached the end', $this->stringTextdomain) . "';
					var pageLink = '" . get_page_link() . "';
					var pageCheckoutUseIframe = '" . self::$shopOptions['shop_checkoutiframe'] . "';
					var textButtonAdd = '" . esc_attr__('Add to basket', $this->stringTextdomain) . "';
					var textButtonAdded = '" . esc_attr__('Adding...', $this->stringTextdomain) . "';
					var textButtonFailed = '" . esc_attr__('Add failed', $this->stringTextdomain) . "';
					var ajaxLocation = '" . admin_url('admin-ajax.php') . "?pageid=" . get_the_ID() . "&nonce=" . wp_create_nonce('spreadplugin') . "';
					var display = '" . self::$shopOptions['shop_display'] . "';
					var infiniteScroll = '" . (self::$shopOptions['shop_infinitescroll'] == 1 || self::$shopOptions['shop_infinitescroll'] == '' ? 1 : 0) . "';
					var lazyLoad = '" . (self::$shopOptions['shop_lazyload'] == 1 || self::$shopOptions['shop_lazyload'] == '' ? 1 : 0) . "';
					var zoomConfig = {
						";

            if (self::$shopOptions['shop_zoomtype'] == 0) {
                echo '
						zoomType : "inner",
						cursor: "crosshair",
						easing: true
						';
            } else {
                echo '
						zoomType: "lens",
						lensShape: "round",
						lensSize: 150
						';
            }

            echo "
					};
					";

            if (self::$shopOptions['shop_zoomtype'] == 2) {
                echo '
						var zoomActivated=0;
						';
            } else {
                echo '
						var zoomActivated=1;
						';
            }

            echo "

					var designerShopId = '" . self::$shopOptions['shop_id'] . "';
					var designerTargetId = 'spreadplugin-designer';
					var designerPlatform = '" . (self::$shopOptions['shop_source'] == 'net' ? 'EU' : 'NA') . "';
					var designerLocale = '" . self::$shopOptions['shop_locale'] . "';
					var designerWidth = '750';
					</script>

					<script language='javascript' type='text/javascript' src='http://cache.spreadshirt.net/Public/Confomat/Js/confomat-embed.js'></script>
					<script language='javascript' type='text/javascript' src='" . plugins_url('/js/spreadplugin.min.js', __FILE__) . "'></script>
					";
        }

        public function enqueueJs() {
            $conOp = $this->getAdminOptions();

            // Respects SSL, Style.css is relative to the current file
            wp_register_style('spreadplugin', plugins_url('/css/spreadplugin.css', __FILE__));
            wp_enqueue_style('spreadplugin');
            wp_register_style('magnific_popup_css', plugins_url('/css/magnific-popup.css', __FILE__));
            wp_enqueue_style('magnific_popup_css');

            // Scrolling
            wp_register_script('infinite_scroll', plugins_url('/js/jquery.infinitescroll.min.js', __FILE__), array(
                'jquery'
            ));
            wp_enqueue_script('infinite_scroll');

            // Fancybox
            wp_register_script('magnific_popup', plugins_url('/js/jquery.magnific-popup.min.js', __FILE__), array(
                'jquery'
            ));
            wp_enqueue_script('magnific_popup');

            // Zoom
            wp_register_script('zoom', plugins_url('/js/jquery.elevateZoom-2.5.5.min.js', __FILE__), array(
                'jquery'
            ));
            wp_enqueue_script('zoom');

            // lazyload
            wp_register_script('lazyload', plugins_url('/js/jquery.lazyload.min.js', __FILE__), array(
                'jquery'
            ));
            wp_enqueue_script('lazyload');
        }

        public function enqueueAdminJs() {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }

        public function startSession() {
            if (!session_id()) {
                @session_start();
            }
        }

        public function endSession() {
            @session_destroy();
        }

        // prepare for https
        private function cleanURL($url) {
            return $url;
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
            $_m = '';

            if (!wp_verify_nonce($_GET['nonce'], 'spreadplugin')) die('Security check');

            $this->reparseShortcodeData();

            // create an new basket if not exist
            if (!isset($_SESSION['basketUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']])) {

                // gets basket
                $apiUrl = 'http://api.spreadshirt.' . self::$shopOptions['shop_source'] . '/api/v1/shops/' . self::$shopOptions['shop_id'];
                $stringXmlShop = wp_remote_get($apiUrl, array(
                    'timeout' => 120
                ));
                if (count($stringXmlShop->errors) > 0) die('Error getting basket.');
                if ($stringXmlShop['body'][0] != '<') die($stringXmlShop['body']);
                $stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
				// Quickfix for Namespace changes of Spreadshirt API
				$stringXmlShop = str_replace('<ns3:','<',$stringXmlShop);
                $objShop = new SimpleXmlElement($stringXmlShop);
                if (!is_object($objShop)) die('Basket not loaded');

                // create the basket
                $namespaces = $objShop->getNamespaces(true);
                $basketUrl = self::createBasket($objShop, $namespaces);

                if (empty($namespaces)) die('Namespaces empty');
                if (empty($basketUrl)) die('Basket url empty');

                // get the checkout url
                $checkoutUrl = self::checkout($basketUrl, $namespaces);

                // Workaround for checkout language | the new checkout needs locale urgently
                $_langCode = @explode("_", (empty(self::$shopOptions['shop_language']) ? self::$shopOptions['shop_locale'] : self::$shopOptions['shop_language']));
                $_langCode = $_langCode[0];

                if (!empty($_langCode)) {
                    if ($_langCode == "us") {
                        $_langCode = "spreadshirt.com";
                    } elseif ($_langCode == "en" && $_langCode[1] == "GB") {
                        $_langCode = "spreadshirt.co.uk";
                    } else {
                        $_langCode = "spreadshirt.".$_langCode;
					}

                    $checkoutUrl = str_replace(array("spreadshirt.net","spreadshirt.com"),$_langCode,$checkoutUrl);
                }
				

                // saving to session
                $_SESSION['basketUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']] = $basketUrl;
                $_SESSION['namespaces'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']] = $namespaces;
                $_SESSION['checkoutUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']] = $checkoutUrl;
            }


            // add an article to the basket
            if (isset($_POST['size']) && isset($_POST['appearance']) && isset($_POST['quantity'])) {

                // article data to be sent to the basket resource
                $data = array(
                    'articleId' => intval($_POST['article']),'size' => intval($_POST['size']),'appearance' => intval($_POST['appearance']),'quantity' => intval($_POST['quantity']),'shopId' => self::$shopOptions['shop_id'],'type' => $_POST['type']
                );

                // add to basket
                $_m = self::addBasketItem($_SESSION['basketUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']], $_SESSION['namespaces'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']], $data);
            }

            $intInBasket = self::getInBasketQuantity(self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']);

            echo json_encode(array(

                "c" => array(

                    "u" => $_SESSION['checkoutUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']],"q" => intval($intInBasket),"m" => $_m
                )
            ));
            die();
        }

        /**
         * Function displayArticles
         *
         * Displays the articles
         *
         * @return html
         */
        private function displayDetailPage($id, $article, $backgroundColor = '') {
            $_toInches = false;
            if (self::$shopOptions['shop_locale'] == 'en_US' || self::$shopOptions['shop_locale'] == 'en_GB' || self::$shopOptions['shop_locale'] == 'us_US' || self::$shopOptions['shop_locale'] == 'us_CA' || self::$shopOptions['shop_locale'] == 'fr_CA') {
                $_toInches = true;
            }

            $output = '<div class="spreadplugin-article-detail" id="article_' . $id . '">';
            $output .= '<a name="' . $id . '"></a>';
            $output .= '<form method="post" id="form_' . $id . '"><div class="articleContentLeft">';

            // edit article button
            if (self::$shopOptions['shop_designershop'] > 0) {
                $output .= ' <div class="edit-wrapper"><a href="//' . self::$shopOptions['shop_designershop'] . '.spreadshirt.' . self::$shopOptions['shop_source'] . '/-D1/customize/product/' . $article['productId'] . '?noCache=true" target="' . self::$shopOptions['shop_linktarget'] . '" title="' . __('Edit article', $this->stringTextdomain) . '"><img src="' . plugins_url('/img/edit.png', __FILE__) . '"></a></div>';
            }

            // display preview image
            $output .= '<div class="image-wrapper">';
            $output .= '<img src="//image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . $article['productId'] . '/views/' . $article['view'] . ',width=280,height=280" class="preview"  alt="' . (!empty($article['name']) ? htmlspecialchars($article['name'], ENT_QUOTES) : '') . '" id="previewimg_' . $id . '" data-zoom-image="//image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . $article['productId'] . '/views/' . $article['view'] . ',width=600,height=600' . (!empty($backgroundColor) ? ',backgroundColor=' . $backgroundColor : '') . '" />';
            $output .= '</div>';

            // add a list with available product views
            if (isset($article['views']) && is_array($article['views'])) {
                $output .= '<div class="views-wrapper"><ul class="views" name="views">';

                foreach ($article['views'] as $k => $v) {
                    $output .= '<li value="' . $k . '"><img src="' . $this->cleanURL($v) . ',viewId=' . $k . ',width=42,height=42" class="previewview" alt="" id="viewimg_' . $id . '" /></li>';
                }

                $output .= '</ul></div>';
            }

            // Short product description
            $output .= '<div class="product-name">';
            $output .= htmlspecialchars($article['productname'], ENT_QUOTES);
            $output .= '</div>';

            if (self::$shopOptions['shop_enablelink'] == 1) {
                $output .= ' <div class="details-wrapper2"><a href="//' . self::$shopOptions['shop_id'] . '.spreadshirt.' . self::$shopOptions['shop_source'] . '/-A' . $id . '" target="_blank">' . __('Additional details', $this->stringTextdomain) . '</a></div>';
            }

            $output .= '</div><div class="articleContentRight"><h3>' . (!empty($article['name']) ? htmlspecialchars($article['name'], ENT_QUOTES) : '') . '</h3>';

            // Show description link if not empty
            if (!empty($article['description'])) {
                $output .= '<div class="description-wrapper spreadplugin-clearfix">' . htmlspecialchars($article['description'], ENT_QUOTES) . '</div>';
            }

            // Show product description
            $output .= '<div class="product-description-wrapper spreadplugin-clearfix"><h4>' . __('Product details', $this->stringTextdomain) . '</h4>' . $article['productshortdescription'] . '</div>';

            // add a select with available sizes
            if (isset($article['sizes']) && is_array($article['sizes'])) {
                $output .= '<div class="size-wrapper spreadplugin-clearfix"><span>' . __('Size', $this->stringTextdomain) . ':</span> <select id="size-select" name="size">';

                foreach ($article['sizes'] as $k => $v) {
                    $output .= '<option value="' . $k . '">' . (!empty($v['name'])?$v['name']:$k) . '</option>';
                }

                $output .= '</select></div>';
            }

            // add a list with availabel product colors
            if (isset($article['appearances']) && is_array($article['appearances'])) {
                $output .= '<div class="color-wrapper spreadplugin-clearfix"><span>' . __('Color', $this->stringTextdomain) . ':</span> <ul class="colors" name="color">';

                foreach ($article['appearances'] as $k => $v) {
                    $output .= '<li value="' . $k . '"><img src="' . $this->cleanURL($v) . '" alt="" /></li>';
                }

                $output .= '</ul></div>';
            }

            $output .= '<input type="hidden" value="' . $article['appearance'] . '" id="appearance" name="appearance" />';
            $output .= '<input type="hidden" value="' . $article['view'] . '" id="view" name="view" />';
            $output .= '<input type="hidden" value="' . $id . '" id="article" name="article" />';

            // $output .= '<div class="separator"></div>';
            $output .= '<div class="price-wrapper spreadplugin-clearfix">';
            if (self::$shopOptions['shop_showextendprice'] == 1) {
                $output .= '<span id="price-without-tax">' . __('Price (without tax):', $this->stringTextdomain) . " " . self::formatPrice($article['pricenet'], $article['currencycode']) . "<br /></span>";
                $output .= '<span id="price-with-tax">' . __('Price (with tax):', $this->stringTextdomain) . " " . self::formatPrice($article['pricebrut'], $article['currencycode']) . "</span>";
                $output .= '<br><div class="additionalshippingcosts">';
                $output .= __('excl. <a class="shipping-window">Shipping</a>', $this->stringTextdomain);
                $output .= '</div>';
            } else {
                $output .= '<span id="price">' . __('Price:', $this->stringTextdomain) . " " . self::formatPrice($article['pricebrut'], $article['currencycode']) . "</span>";
            }
            $output .= '</div>';

            // order buttons
            $output .= '<input type="text" value="1" id="quantity" name="quantity" maxlength="4" />';
            $output .= '<input type="submit" name="submit" value="' . __('Add to basket', $this->stringTextdomain) . '" /><br>';

            // Social buttons
            if (self::$shopOptions['shop_social'] == true) {
                $output .= '
				<ul class="soc-icons">
				<li><a target="_blank" data-color="#5481de" class="fb" href="//www.facebook.com/sharer.php?u=' . urlencode(add_query_arg('splproduct', $id, get_permalink())) . '&t=' . rawurlencode(get_the_title()) . '" title="Facebook"></a></li>
				<li><a target="_blank" data-color="#06ad18" class="goog" href="//plus.google.com/share?url=' . urlencode(add_query_arg('splproduct', $id, get_permalink())) . '" title="Google"></a></li>
				<li><a target="_blank" data-color="#2cbbea" class="twt" href="//twitter.com/home?status=' . rawurlencode(get_the_title()) . ' - ' . urlencode(add_query_arg('splproduct', $id, get_permalink())) . '" title="Twitter"></a></li>
				<li><a target="_blank" data-color="#e84f61" class="pin" href="//pinterest.com/pin/create/button/?url=' . rawurlencode(add_query_arg('splproduct', $id, get_permalink())) . '&media=' . rawurlencode('http://image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . $article['productId'] . '/views/' . $article['view'] . ',width=280,height=280') . ',width=' . self::$shopOptions['shop_imagesize'] . ',height=' . self::$shopOptions['shop_imagesize'] . '&description=' . (!empty($article['description']) ? htmlspecialchars($article['description'], ENT_QUOTES) : 'Product') . '" title="Pinterest"></a></li>
				</ul>
				';

                /*
                 * <li><a target="_blank" data-color="#459ee9" class="in" href="#" title="LinkedIn"></a></li> <li><a target="_blank" data-color="#ee679b" class="drb" href="#" title="Dribbble"></a></li> <li><a target="_blank" data-color="#4887c2" class="tumb" href="#" title="Tumblr"></a></li> <li><a target="_blank" data-color="#f23a94" class="flick" href="#" title="Flickr"></a></li> <li><a target="_blank" data-color="#74c3dd" class="vim" href="#" title="Vimeo"></a></li> <li><a target="_blank" data-color="#4a79ff" class="delic" href="#" title="Delicious"></a></li> <li><a target="_blank" data-color="#6ea863" class="forr" href="#" title="Forrst"></a></li> <li><a target="_blank" data-color="#f6a502" class="hi5" href="#" title="Hi5"></a></li> <li><a target="_blank" data-color="#e3332a" class="last" href="#" title="Last.fm"></a></li> <li><a target="_blank" data-color="#3c6ccc" class="space" href="#" title="Myspace"></a></li> <li><a target="_blank" data-color="#229150" class="newsv" href="#" title="Newsvine"></a></li> <li><a href="#" class="pica" title="Picasa" data-color="#b163c8" target="_blank"></a></li> <li><a href="#" class="tech" title="Technorati" data-color="#3ac13a" target="_blank"></a></li> <li><a href="#" class="rss" title="RSS" data-color="#f18d3c" target="_blank"></a></li> <li><a href="#" class="rdio" title="Rdio" data-color="#2c7ec7" target="_blank"></a></li> <li><a href="#" class="share" title="ShareThis" data-color="#359949" target="_blank"></a></li> <li><a href="#" class="skyp" title="Skype" data-color="#00adf1" target="_blank"></a></li> <li><a href="#" class="slid" title="SlideShare" data-color="#ef8122" target="_blank"></a></li> <li><a href="#" class="squid" title="Squidoo" data-color="#f87f27" target="_blank"></a></li> <li><a href="#" class="stum" title="StumbleUpon" data-color="#f05c38" target="_blank"></a></li> <li><a href="#" class="what" title="WhatsApp" data-color="#3ebe2b" target="_blank"></a></li> <li><a href="#" class="wp" title="Wordpress" data-color="#3078a9" target="_blank"></a></li> <li><a href="#" class="ytb" title="Youtube" data-color="#df3434" target="_blank"></a></li> <li><a href="#" class="digg" title="Digg" data-color="#326ba0" target="_blank"></a></li> <li><a href="#" class="beh" title="Behance" data-color="#2d9ad2" target="_blank"></a></li> <li><a href="#" class="yah" title="Yahoo" data-color="#883890" target="_blank"></a></li> <li><a href="#" class="blogg" title="Blogger" data-color="#f67928" target="_blank"></a></li> <li><a href="#" class="hype" title="Hype Machine" data-color="#f13d3d" target="_blank"></a></li> <li><a href="#" class="groove" title="Grooveshark" data-color="#498eba" target="_blank"></a></li> <li><a href="#" class="sound" title="SoundCloud" data-color="#f0762c" target="_blank"></a></li> <li><a href="#" class="insta" title="Instagram" data-color="#c2784e" target="_blank"></a></li> <li><a href="#" class="vk" title="Vkontakte" data-color="#5f84ab" target="_blank"></a></li>
                 */
            }
            $output .= '
			</div>
			</form>
			';

            $output .= '
<div id="spreadplugin-tabs_wrapper">
	<div id="spreadplugin-tabs_container">
		<ul id="spreadplugin-tabs">
			<li class="active"><a href="#tab1">' . __('Product images', $this->stringTextdomain) . '</a></li>
			<li><a href="#tab2">' . __('Sizes', $this->stringTextdomain) . '</a></li>
			<li><a href="#tab3">' . __('Description', $this->stringTextdomain) . '</a></li>';

            if (!empty($article['printtypename'])) {
                $output .= '
				<li><a href="#tab4">' . __('Print Technique', $this->stringTextdomain) . '</a></li>
				';
            }

            $output .= '
 		</ul>
	</div>
	<div id="spreadplugin-tabs_content_container">
		<div id="tab1" class="spreadplugin-tab_content" style="display: block;">
			<p><img alt="" src="' . $article['product-resource-detail'] . ',width=560,height=150.png"></p>
		</div>
		<div id="tab2" class="spreadplugin-tab_content">
			<p><img alt="" src="' . $article['product-resource-size'] . ',width=130,height=130.png"></p>

			<table class="assort_sizes">
			<thead>
			<tr>
			<th>' . __('Size', $this->stringTextdomain) . '</th>
			';

            if (isset($article['sizes']) && is_array($article['sizes'])) {
                foreach ($article['sizes'] as $k => $v) {
                    $output .= '<th>' . (!empty($v['name'])?$v['name']:$k) . '</th>';
                }
            }

            $output .= '
			</tr>
			</thead>
			<tbody>
			<tr>
			<td>' . __('Dimension', $this->stringTextdomain) . ' A (' . ($_toInches ? 'inch' : 'mm') . ')</td>
			';

            if (isset($article['sizes']) && is_array($article['sizes'])) {
                foreach ($article['sizes'] as $k => $v) {
                    $output .= '<td>' . (!empty($v['measures'][0]['value'])?($_toInches ? self::mmToIn($v['measures'][0]['value']) : $v['measures'][0]['value']):$k) . '</td>';
                }
            }

            $output .= '
			</tr>
			<tr class="even">
			<td>' . __('Dimension', $this->stringTextdomain) . ' B (' . ($_toInches ? 'inch' : 'mm') . ')</td>
			';

            if (isset($article['sizes']) && is_array($article['sizes'])) {
                foreach ($article['sizes'] as $k => $v) {
                    $output .= '<td>' . (!empty($v['measures'][1]['value'])?($_toInches ? self::mmToIn($v['measures'][1]['value']) : $v['measures'][1]['value']):$k) . '</td>';
                }
            }

            $output .= '
			</tr>
			</tbody>
			</table>
			';

            $output .= '
		</div>
		<div id="tab3" class="spreadplugin-tab_content">
			<p>' . $article['productdescription'] . '</p>
		</div>';

            if (!empty($article['printtypename'])) {
                $output .= '
			<div id="tab4" class="spreadplugin-tab_content">
				<p><strong>' . $article['printtypename'] . '</strong></p>
				<p>' . $article['printtypedescription'] . '</p>
			</div>
			';
            }
            $output .= '</div>
</div>
			';

            $output .= '</div>';

            return $output;
        }

        /**
         * Admin
         */
        public function addPluginPage() {
            // Create menu tab
            add_options_page('Set Spreadplugin options', 'Spreadplugin Options', 'manage_options', 'splg_options', array(

                $this,'pageOptions'
            ));
        }

        // call page options
        public function pageOptions() {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            // display options page
            include (plugin_dir_path(__FILE__) . '/options.php');
        }

        // Rebuild Cache Ajax Call
        public function doRebuildCache() {
            global $wpdb;

            $res = array();

            $action = $_POST["do"];

            if ($action == 'getlist') {

                // delete transient cache
                $wpdb->query("DELETE FROM `" . $wpdb->options . "` WHERE `option_name` LIKE '_transient_%spreadplugin%cache%'");

                // read posts/pages,... with shortcode
                $result = $wpdb->get_results("SELECT id,post_title FROM `" . $wpdb->posts . "` WHERE post_type <> 'revision' and post_content like '%[spreadplugin%'");

                if ($result) {
                    foreach ($result as $item) {

                        $items = array();
                        $_items = array();
                        $_types = array();
                        $this->reparseShortcodeData($item->id);
                        // get and store designs data directly to cache
                        $this->getDesignsData($item->id);
                        // get raw article data for later usage
                        $_items = $this->getRawArticleData($item->id);
                        // storing producttypedepartments for later use
                        $_types = $this->getTypesData();
                        $_shipping = $this->getShipmentData();

                        if (is_object($_items) && !empty($_items->article)) {
                            $i = 0;
                            foreach ($_items->article as $article) {

                                $items[] = array(
                                    'articleid' => (int)$article['id'],'previewimage' => '//image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . (int)$article->product['id'] . '/views/' . (int)$article->product->defaultValues->defaultView['id'] . ',width=100,height=100','articlename' => (string)$article->name,'place' => $i
                                );

                                $i++;
                            }
                        }

                        $res[] = array(
                            'id' => $item->id,'title' => $item->post_title,'items' => $items
                        );
                        // need to use session, because otherwise we can't transport the types and article data further down. (ajax/wordpress thingy)
                        $_SESSION['_tempArticleCache'][$item->id]['types'] = $_types;
                        $_SESSION['_tempArticleCache'][$item->id]['shipment'] = $_shipping;
                    }
                }

                die(json_encode($res));
            } else
                if ($action == 'rebuild') {
                    $_pageid = intval($_POST['_pageid']);
                    $_articleid = intval($_POST['_articleid']);
                    $_pos = intval($_POST['_pos']);
                    $this->reparseShortcodeData($_pageid);

                    $_articleData = $this->getSingleArticleData($_pageid, $_articleid, $_pos);

                    // sleep timer, for some users reaching their request limits - 20 sec will avoid it.
                    if (!empty(self::$shopOptions['shop_sleep']) && self::$shopOptions['shop_sleep'] > 0) {
                        sleep(self::$shopOptions['shop_sleep']);
                    }

                    if (array_key_exists('id', $_articleData) && $_articleData['id'] > 0) {
                        // store each article in a session for later use
                        $_SESSION['_tempArticleCache'][$_pageid][(int)$_articleData['designid']][(int)$_articleData['id']] = $_articleData;
                        die('Done');
                    } else {
                        die('Error: ' . $_articleData);
                    }
                } else
                    if ($action == 'save') {
                        $_pageid = intval($_POST['_pageid']);

                        if (!empty($_SESSION['_tempArticleCache'])) {

                            if (!empty($_SESSION['_tempArticleCache'][$_pageid])) {

                                // build cache from session data
                                set_transient('spreadplugin2-article-cache-' . $_pageid, $_SESSION['_tempArticleCache'][$_pageid], self::$shopCache);

                                die('Done');
                            }
                        } else {
                            die('Error');
                        }
                    }
        }

        /**
         * Add Settings link to plugin
         */
        public function addPluginSettingsLink($links, $file) {
            static $this_plugin;
            if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);

            if ($file == $this_plugin) {
                $settings_link = '<a href="options-general.php?page=splg_options">' . __("Settings", $this->stringTextdomain) . '</a>';
                array_unshift($links, $settings_link);
            }

            return $links;
        }

        // Convert hex to rgb values
        public function hex2rgb($hex) {
            if (strlen($hex) == 3) {
                $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
                $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
                $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
            } else {
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
            }
            $rgb = array(
                $r,$g,$b
            );
            return $rgb; // returns an array with the rgb values
        }

        private static function formatPrice($price, $currency) {
            return (empty(self::$shopOptions['shop_locale']) || self::$shopOptions['shop_locale'] == 'en_US' || self::$shopOptions['shop_locale'] == 'en_GB' || self::$shopOptions['shop_locale'] == 'us_US' || self::$shopOptions['shop_locale'] == 'us_CA' || self::$shopOptions['shop_locale'] == 'fr_CA' ? $currency . " " . number_format($price, 2, '.', '') : number_format($price, 2, ',', '.') . " " . $currency);
        }

        // read admin options
        public function getAdminOptions() {
            $scOptions = $this->defaultOptions;
            $splgOptions = get_option('splg_options');
            if (!empty($splgOptions)) {
                foreach ($splgOptions as $key => $option) {
                    $scOptions[$key] = $option;
                }
            }

            return $scOptions;
        }

        /**
         * re-parse the shortcode to get the authentication details
         * read page config and admin options
         * @TODO find a different way
         */
        public function reparseShortcodeData($pageId = 0) {
			$pageId = ($pageId == 0 ? intval($_GET['pageid']) : $pageId);
            $pageData = get_page($pageId);
            $pageContent = $pageData->post_content;

            // get admin options (default option set on admin page)
            $conOp = $this->getAdminOptions();

            // shortcode overwrites admin options (default option set on admin page) if available
            $arrSc = shortcode_parse_atts(str_replace("[spreadplugin", '', str_replace("]", "", $pageContent)));

            // replace options by shortcode if set
            if (!empty($arrSc)) {
                foreach ($arrSc as $key => $option) {
                    if ($option != '') {
                        $conOp[$key] = $option;
                    }
                }
            }

            self::$shopOptions = $conOp;
            self::$shopOptions['shop_locale'] = (($conOp['shop_locale'] == '' || $conOp['shop_locale'] == 'de_DE') && $conOp['shop_source'] == 'com' ? 'us_US' : $conOp['shop_locale']); // Workaround for older versions of this plugin
            self::$shopOptions['shop_source'] = (empty($conOp['shop_source']) ? 'net' : $conOp['shop_source']);
        }

        // build cart
        public function doCart() {
            if (!wp_verify_nonce($_GET['nonce'], 'spreadplugin')) die('Security check');

            $this->reparseShortcodeData();

            // overwrite translation if language available and set
            if (!empty(self::$shopOptions['shop_language'])) {
                $_ol = dirname(__FILE__) . '/translation/' . $this->stringTextdomain . '-' . self::$shopOptions['shop_language'] . '.mo';
                if (file_exists($_ol)) {
                    load_textdomain($this->stringTextdomain, $_ol);
                }
            } else {
                load_plugin_textdomain($this->stringTextdomain, false, dirname(plugin_basename(__FILE__)) . '/translation');
            }

            // create an new basket if not exist
            if (isset($_SESSION['basketUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']])) {

                $basketItems = self::getBasket($_SESSION['basketUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']]);

                $priceSum = 0;
                $intSumQuantity = 0;

                echo '<div class="spreadplugin-cart-contents">';

                if (!empty($basketItems)) {
                    // echo "<pre>".print_r($basketItems)."</pre>";
                    foreach ($basketItems->basketItems->basketItem as $item) {

                        if ((string)$item->element['type'] == 'sprd:product') {
                            // Product
                            $apiUrl = 'http://api.spreadshirt.' . self::$shopOptions['shop_source'] . '/api/v1/shops/' . (string)$item->shop['id'] . '/products/' . (string)$item->element['id'];
                            $stringXmlShop = wp_remote_get($apiUrl, array(
                                'timeout' => 120
                            ));
                            if (isset($stringXmlShop->errors) && count($stringXmlShop->errors) > 0) die('Error getting articles. Please check Shop-ID, API and secret.');
                            if ($stringXmlShop['body'][0] != '<') die($stringXmlShop['body']);
                            $stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
							// Quickfix for Namespace changes of Spreadshirt API
							$stringXmlShop = str_replace('<ns3:','<',$stringXmlShop);
                            $objArticles = new SimpleXmlElement($stringXmlShop);
                            if (!is_object($objArticles)) die('Articles not loaded');

                            echo '<div class="cart-row" data-id="' . (string)$item['id'] . '">
							<div class="cart-delete"><a href="javascript:;" class="deleteCartItem" title="' . __('Remove', $this->stringTextdomain) . '"><img src="' . plugins_url('/img/delete.png', __FILE__) . '"></a></div>
							<div class="cart-preview"><img src="//image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . (string)$item->element['id'] . '/views/1,width=60,height=60,appearanceId=' . (string)$item->element->properties->property[2] . '"></div>
							<div class="cart-description"><strong>' . htmlspecialchars((empty($objArticles->name) ? $item->description : $objArticles->name), ENT_QUOTES) . '</strong><br>' . __('Size', $this->stringTextdomain) . ': ' . (string)$item->element->properties->property[1] . '<br>' . __('Quantity', $this->stringTextdomain) . ': ' . (int)$item->quantity . '</div>
							<div class="cart-price"><strong>' . self::formatPrice((float)$item->price->vatIncluded * (int)$item->quantity, '') . '</strong></div>
							</div>';
                        } else {
                            // article

                            $apiUrl = 'http://api.spreadshirt.' . self::$shopOptions['shop_source'] . '/api/v1/shops/' . (string)$item->shop['id'] . '/articles/' . (string)$item->element['id'];
                            $stringXmlShop = wp_remote_get($apiUrl, array(
                                'timeout' => 120
                            ));
                            if (isset($stringXmlShop->errors) && count($stringXmlShop->errors) > 0) die('Error getting articles. Please check Shop-ID, API and secret.');
                            if ($stringXmlShop['body'][0] != '<') die($stringXmlShop['body']);
                            $stringXmlShop = wp_remote_retrieve_body($stringXmlShop);
							// Quickfix for Namespace changes of Spreadshirt API
							$stringXmlShop = str_replace('<ns3:','<',$stringXmlShop);
                            $objArticles = new SimpleXmlElement($stringXmlShop);
                            if (!is_object($objArticles)) die('Articles not loaded');

                            echo '<div class="cart-row" data-id="' . (string)$item['id'] . '">
							<div class="cart-delete"><a href="javascript:;" class="deleteCartItem" title="' . __('Remove', $this->stringTextdomain) . '"><img src="' . plugins_url('/img/delete.png', __FILE__) . '"></a></div>
							<div class="cart-preview"><img src="//image.spreadshirt.' . self::$shopOptions['shop_source'] . '/image-server/v1/products/' . (string)$objArticles->product['id'] . '/views/' . (string)$objArticles->product->defaultValues->defaultView['id'] . ',viewId=' . (string)$objArticles->product->defaultValues->defaultView['id'] . ',width=60,height=60,appearanceId=' . (string)$item->element->properties->property[2] . '"></div>
							<div class="cart-description"><strong>' . htmlspecialchars((empty($objArticles->name) ? $item->description : $objArticles->name), ENT_QUOTES) . '</strong><br>' . __('Size', $this->stringTextdomain) . ': ' . (string)$item->element->properties->property[1] . '<br>' . __('Quantity', $this->stringTextdomain) . ': ' . (int)$item->quantity . '</div>
							<div class="cart-price"><strong>' . self::formatPrice((float)$item->price->vatIncluded * (int)$item->quantity, '') . '</strong></div>
							</div>';
                        }

                        $priceSum += (float)$item->price->vatIncluded * (int)$item->quantity;
                        $intSumQuantity += (int)$item->quantity;
                    }
                }

                echo '</div>';
                echo '<div class="spreadplugin-cart-total">' . __('Total (excl. Shipping)', $this->stringTextdomain) . '<strong class="price">' . self::formatPrice($priceSum, '') . '</strong></div>';

                if ($intSumQuantity > 0) {
                    echo '<div id="cart-checkout" class="spreadplugin-cart-checkout"><a href="' . $_SESSION['checkoutUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']] . '" target="' . self::$shopOptions['shop_linktarget'] . '">' . __('Proceed to checkout', $this->stringTextdomain) . '</a></div>';
                } else {
                    echo '<div id="cart-checkout" class="spreadplugin-cart-checkout"><a title="' . __('Basket is empty', $this->stringTextdomain) . '">' . __('Proceed to checkout', $this->stringTextdomain) . '</a></div>';
                }
            }

            die();
        }

        // delete cart
        public function doCartItemDelete() {
            if (!wp_verify_nonce($_GET['nonce'], 'spreadplugin')) die('Security check');

            $this->reparseShortcodeData();

            // create an new basket if not exist
            if (isset($_SESSION['basketUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']])) {
                // uuid test
                if (preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $_POST['id'])) {
                    self::deleteBasketItem($_SESSION['basketUrl'][self::$shopOptions['shop_source'] . self::$shopOptions['shop_language']], $_POST['id']);
                }
            }

            die();
        }

        public static function mmToIn($val) {
            return number_format($val * 0.0393701, 1);
        }

        // alternative für gzdecode
        private function ownGzDecode($data, &$filename = '', &$error = '', $maxlength = null) {
            $len = strlen($data);
            if ($len < 18 || strcmp(substr($data, 0, 2), "\x1f\x8b")) {
                $error = "Not in GZIP format.";
                return null; // Not GZIP format (See RFC 1952)
            }
            $method = ord(substr($data, 2, 1)); // Compression method
            $flags = ord(substr($data, 3, 1)); // Flags
            if ($flags & 31 != $flags) {
                $error = "Reserved bits not allowed.";
                return null;
            }
            // NOTE: $mtime may be negative (PHP integer limitations)
            $mtime = unpack("V", substr($data, 4, 4));
            $mtime = $mtime[1];
            $xfl = substr($data, 8, 1);
            $os = substr($data, 8, 1);
            $headerlen = 10;
            $extralen = 0;
            $extra = "";
            if ($flags & 4) {
                // 2-byte length prefixed EXTRA data in header
                if ($len - $headerlen - 2 < 8) {
                    return false; // invalid
                }
                $extralen = unpack("v", substr($data, 8, 2));
                $extralen = $extralen[1];
                if ($len - $headerlen - 2 - $extralen < 8) {
                    return false; // invalid
                }
                $extra = substr($data, 10, $extralen);
                $headerlen += 2 + $extralen;
            }
            $filenamelen = 0;
            $filename = "";
            if ($flags & 8) {
                // C-style string
                if ($len - $headerlen - 1 < 8) {
                    return false; // invalid
                }
                $filenamelen = strpos(substr($data, $headerlen), chr(0));
                if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
                    return false; // invalid
                }
                $filename = substr($data, $headerlen, $filenamelen);
                $headerlen += $filenamelen + 1;
            }
            $commentlen = 0;
            $comment = "";
            if ($flags & 16) {
                // C-style string COMMENT data in header
                if ($len - $headerlen - 1 < 8) {
                    return false; // invalid
                }
                $commentlen = strpos(substr($data, $headerlen), chr(0));
                if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
                    return false; // Invalid header format
                }
                $comment = substr($data, $headerlen, $commentlen);
                $headerlen += $commentlen + 1;
            }
            $headercrc = "";
            if ($flags & 2) {
                // 2-bytes (lowest order) of CRC32 on header present
                if ($len - $headerlen - 2 < 8) {
                    return false; // invalid
                }
                $calccrc = crc32(substr($data, 0, $headerlen)) & 0xffff;
                $headercrc = unpack("v", substr($data, $headerlen, 2));
                $headercrc = $headercrc[1];
                if ($headercrc != $calccrc) {
                    $error = "Header checksum failed.";
                    return false; // Bad header CRC
                }
                $headerlen += 2;
            }
            // GZIP FOOTER
            $datacrc = unpack("V", substr($data, -8, 4));
            $datacrc = sprintf('%u', $datacrc[1] & 0xFFFFFFFF);
            $isize = unpack("V", substr($data, -4));
            $isize = $isize[1];
            // decompression:
            $bodylen = $len - $headerlen - 8;
            if ($bodylen < 1) {
                // IMPLEMENTATION BUG!
                return null;
            }
            $body = substr($data, $headerlen, $bodylen);
            $data = "";
            if ($bodylen > 0) {
                switch ($method) {
                    case 8:
                        // Currently the only supported compression method:
                        $data = gzinflate($body, $maxlength);
                        break;
                    default:
                        $error = "Unknown compression method.";
                        return false;
                }
            } // zero-byte body content is allowed
              // Verifiy CRC32
            $crc = sprintf("%u", crc32($data));
            $crcOK = $crc == $datacrc;
            $lenOK = $isize == strlen($data);
            if (!$lenOK || !$crcOK) {
                $error = ($lenOK ? '' : 'Length check FAILED. ') . ($crcOK ? '' : 'Checksum FAILED.');
                return false;
            }
            return $data;
        }
    } // END class WP_Spreadplugin

    new WP_Spreadplugin();
}

?>