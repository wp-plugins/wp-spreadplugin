<?php
/*
 * Plugin Name: WP-Spreadplugin Basket Widget
 * Plugin URI: http://wordpress.org/extend/plugins/wp-spreadplugin/
 * Description: This plugin uses the Spreadshirt API to list articles and let your customers order articles of your Spreadshirt shop using Spreadshirt order process.
 * Version: 1.0
 * Author: Thimo Grauerholz
 * Author URI: http://www.spreadplugin.de
 */

class SpreadpluginBasketWidget extends WP_Widget {
	private $stringTextdomain = 'spreadplugin';
	function __construct() {
		// Instantiate the parent object
		parent::__construct ( false, 'Spreadplugin Basket' );
	}
	function widget($args, $instance) {
		load_plugin_textdomain ( $this->stringTextdomain, false, dirname ( plugin_basename ( __FILE__ ) ) . '/translation' );
		
		$output = '<div class="spreadplugin-checkout"><span></span> <a class="spreadplugin-checkout-link">' . __ ( "Basket", $this->stringTextdomain ) . '</a></div>
<div id="spreadplugin-widget-cart" class="spreadplugin-cart"></div>';
		
		echo $output;
	}
	function update($new_instance, $old_instance) {
		// Save widget options
	}
	function form($instance) {
		// Output admin widget options form
	}
}
function myplugin_register_widgets() {
	register_widget ( 'SpreadpluginBasketWidget' );
}

add_action ( 'widgets_init', 'myplugin_register_widgets' );

?>