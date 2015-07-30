<?php 

if (is_user_logged_in() && is_admin()) {
	
	load_plugin_textdomain($this->stringTextdomain, false, dirname(plugin_basename(__FILE__)) . '/translation');
	
	$adminSettings = $this->defaultOptions;

	if (isset($_POST['update-splg_options'])) {//save option changes
		foreach ($adminSettings as $key => $val){
			if (isset($_POST[$key])) {
				$adminSettings[$key] = trim($_POST[$key]);
			}
		}
	
		update_option('splg_options', $adminSettings);
	}
	
	$adminOptions = $this->getAdminOptions();
	

?>
<style>
.form-table td {
	vertical-align: top;
}
</style>
<div class="wrap">
  <?php 
  screen_icon(); 
  ?>
  <h2>Spreadplugin Plugin Options &raquo; Settings</h2>
  <div id="sprdplg-message" class="updated fade" style="display:none"></div>
  <div class="metabox-holder">
    <div class="meta-box-sortables ui-sortable">
      <div class="postbox">
        <div class="handlediv" title="Click to toggle"><br />
        </div>
        <h3 class="hndle">Spreadplugin
          <?php _e('Settings','spreadplugin'); ?>
        </h3>
        <div class="inside">
          <p>
            <?php _e('These settings will be used as default and can be overwritten by the extended shortcode.','spreadplugin'); ?>
          </p>
          <form action="options-general.php?page=splg_options&saved" method="post" id="splg_options_form" name="splg_options_form">
            <?php wp_nonce_field('splg_options'); ?>
            <table border="0" cellpadding="3" cellspacing="0" class="form-table">
              <tr>
                <td valign="top"><?php _e('Shop id:','spreadplugin'); ?></td>
                <td><input type="text" name="shop_id" value="<?php echo (empty($adminOptions['shop_id'])?0:$adminOptions['shop_id']); ?>" class="only-digit required" /></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Shop country:','spreadplugin'); ?></td>
                <td><select name="shop_locale" id="shop_locale">
                    <option value=""<?php echo (empty($adminOptions['shop_locale'])?" selected":"") ?>>None/Unknown</option>
                    <option value="de_DE"<?php echo ($adminOptions['shop_locale']=='de_DE'?" selected":"") ?>>Deutschland</option>
                    <option value="fr_FR"<?php echo ($adminOptions['shop_locale']=='fr_FR'?" selected":"") ?>>France</option>
                    <option value="en_GB"<?php echo ($adminOptions['shop_locale']=='en_GB'?" selected":"") ?>>United Kingdom</option>
                    <option value="nl_BE"<?php echo ($adminOptions['shop_locale']=='nl_BE'?" selected":"") ?>>Belgie (Nederlands)</option>
                    <option value="fr_BE"<?php echo ($adminOptions['shop_locale']=='fr_BE'?" selected":"") ?>>Belgique (Fran&ccedil;ais)</option>
                    <option value="dk_DK"<?php echo ($adminOptions['shop_locale']=='dk_DK'?" selected":"") ?>>Danmark</option>
                    <option value="es_ES"<?php echo ($adminOptions['shop_locale']=='es_ES'?" selected":"") ?>>Espa&ntilde;a</option>
                    <option value="en_IE"<?php echo ($adminOptions['shop_locale']=='en_IE'?" selected":"") ?>>Ireland</option>
                    <option value="it_IT"<?php echo ($adminOptions['shop_locale']=='it_IT'?" selected":"") ?>>Italia</option>
                    <option value="nl_NL"<?php echo ($adminOptions['shop_locale']=='nl_NL'?" selected":"") ?>>Nederland</option>
                    <option value="no_NO"<?php echo ($adminOptions['shop_locale']=='no_NO'?" selected":"") ?>>Norge</option>
                    <option value="pl_PL"<?php echo ($adminOptions['shop_locale']=='pl_PL'?" selected":"") ?>>Polska</option>
                    <option value="fi_FI"<?php echo ($adminOptions['shop_locale']=='fi_FI'?" selected":"") ?>>Suomi</option>
                    <option value="se_SE"<?php echo ($adminOptions['shop_locale']=='se_SE'?" selected":"") ?>>Sverige</option>
                    <option value="de_AT"<?php echo ($adminOptions['shop_locale']=='de_AT'?" selected":"") ?>>&Ouml;sterreich</option>
                    <option value="us_US"<?php echo ($adminOptions['shop_locale']=='us_US'?" selected":"") ?>>United States</option>
                    <option value="us_CA"<?php echo ($adminOptions['shop_locale']=='us_CA'?" selected":"") ?>>Canada (English)</option>
                    <option value="fr_CA"<?php echo ($adminOptions['shop_locale']=='fr_CA'?" selected":"") ?>>Canada (Fran&ccedil;ais)</option>
                    <option value="us_AU"<?php echo ($adminOptions['shop_locale']=='us_AU'?" selected":"") ?>>Australia</option>
                    <option value="us_BR"<?php echo ($adminOptions['shop_locale']=='us_BR'?" selected":"") ?>>Brazil</option>
                  </select></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Shop source:','spreadplugin'); ?></td>
                <td><select name="shop_source" id="shop_source" class="required">
                    <option value="net"<?php echo ($adminOptions['shop_source']=='net'?" selected":"") ?>>Europe</option>
                    <option value="com"<?php echo ($adminOptions['shop_source']=='com'?" selected":"") ?>>US/Canada/Australia/Brazil</option>
                  </select></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Spreadshirt API Key:','spreadplugin'); ?></td>
                <td><input type="text" name="shop_api" value="<?php echo $adminOptions['shop_api']; ?>" class="required" /></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Spreadshirt API Secret:','spreadplugin'); ?></td>
                <td><input type="text" name="shop_secret" value="<?php echo $adminOptions['shop_secret']; ?>" class="required" /></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Limit articles per page:','spreadplugin'); ?></td>
                <td><input type="text" name="shop_limit" value="<?php echo (empty($adminOptions['shop_limit'])?10:$adminOptions['shop_limit']); ?>" class="only-digit" /></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Image size:','spreadplugin'); ?></td>
                <td><select name="shop_imagesize" id="shop_imagesize">
                    <option value="190"<?php echo ($adminOptions['shop_imagesize']==190?" selected":"") ?>>190</option>
                    <option value="280"<?php echo ($adminOptions['shop_imagesize']==280?" selected":"") ?>>280</option>
                  </select>
                  px</td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Product category:','spreadplugin'); ?></td>
                <td><select name="shop_productcategory" id="shop_productcategory">
                    <option value="">
                    <?php _e('All products','spreadplugin'); ?>
                    </option>
                    <option value="<?php _e('Men','spreadplugin'); ?>"<?php echo ($adminOptions['shop_productcategory']==__('Men','spreadplugin')?" selected":""); ?>>
                    <?php _e('Men','spreadplugin'); ?>
                    </option>
                    <option value="<?php _e('Women'); ?>"<?php echo ($adminOptions['shop_productcategory']==__('Women','spreadplugin')?" selected":""); ?>>
                    <?php _e('Women','spreadplugin'); ?>
                    </option>
                    <option value="<?php _e('Kids & Babies'); ?>"<?php echo ($adminOptions['shop_productcategory']==__('Kids & Babies','spreadplugin')?" selected":""); ?>>
                    <?php _e('Kids & Babies','spreadplugin'); ?>
                    </option>
                    <option value="<?php _e('Accessories'); ?>"<?php echo ($adminOptions['shop_productcategory']==__('Accessories','spreadplugin')?" selected":""); ?>>
                    <?php _e('Accessories','spreadplugin'); ?>
                    </option>
                    <option value="<?php _e('New Products'); ?>"<?php echo ($adminOptions['shop_productcategory']==__('New Products','spreadplugin')?" selected":""); ?>>
                    <?php _e('New Products','spreadplugin'); ?>
                    </option>
                  </select></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Article category:','spreadplugin'); ?></td>
                <td>Please see <strong>How do I get the category Id?</strong> in FAQ<br />
                  <br />
                  <input type="text" name="shop_category" value="<?php echo $adminOptions['shop_category']; ?>" class="only-digit" /></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Social buttons:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_social" value="0"<?php echo ($adminOptions['shop_social']==0?" checked":"") ?> />
                  <?php _e('Disabled','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_social" value="1"<?php echo ($adminOptions['shop_social']==1?" checked":"") ?> />
                  <?php _e('Enabled','spreadplugin'); ?></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Product linking:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_enablelink" value="0"<?php echo ($adminOptions['shop_enablelink']==0?" checked":"") ?> />
                  <?php _e('Disabled','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_enablelink" value="1"<?php echo ($adminOptions['shop_enablelink']==1?" checked":"") ?> />
                  <?php _e('Enabled','spreadplugin'); ?></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Sort articles by:','spreadplugin'); ?></td>
                <td><select name="shop_sortby" id="shop_sortby">
                    <option>place</option>
                    <?php if (!empty(self::$shopArticleSortOptions)) {
		  foreach (self::$shopArticleSortOptions as $val) {
			  ?>
                    <option value="<?php echo $val; ?>"<?php echo ($adminOptions['shop_sortby']==$val?" selected":"") ?>><?php echo $val; ?></option>
                    <?php }
	  }
	  ?>
                  </select></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Target of links:','spreadplugin'); ?></td>
                <td><?php _e('Enter the name of your target iframe or frame, if available. Default is _blank (new window).','spreadplugin'); ?>
                  <br />
                  <br />
                  <input type="text" name="shop_linktarget" value="<?php echo (empty($adminOptions['shop_linktarget'])?'_self':$adminOptions['shop_linktarget']); ?>" /></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Use iframe for checkout:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_checkoutiframe" value="0"<?php echo ($adminOptions['shop_checkoutiframe']==0?" checked":"") ?> />
                  <?php _e('Opens in separate window','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_checkoutiframe" value="1"<?php echo ($adminOptions['shop_checkoutiframe']==1?" checked":"") ?> />
                  <?php _e('Opens an iframe in the page content','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_checkoutiframe" value="2"<?php echo ($adminOptions['shop_checkoutiframe']==2?" checked":"") ?> />
                  <?php _e('Opens an iframe in a modal window','spreadplugin'); ?></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Use designer:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_designer" value="0"<?php echo ($adminOptions['shop_designer']==0?" checked":"") ?> />
                  <?php _e('None','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_designer" value="1"<?php echo ($adminOptions['shop_designer']==1?" checked":"") ?> />
                  <?php _e('Integrated [BETA] (All marketplace designs are shown on design tab)','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_designer" value="2"<?php echo ($adminOptions['shop_designer']==2?" checked":"") ?> />
                  <?php _e('Premium (Contents of your designer shop are shown - Tablomat)','spreadplugin'); ?>
                  <div id="premium-shop-span"> <br />
                    <br />
                    <?php _e('Premium Designer Shop Id','spreadplugin'); ?>
                    <input type="text" name="shop_designershop" value="<?php echo $adminOptions['shop_designershop']; ?>" class="only-digit" />
                    <br />
                    <?php _e('If you have a designer Shop activated, enter the ID here. A new link will appear where the customer can change the design.','spreadplugin'); ?>
                  </div></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Default display:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_display" value="0"<?php echo ($adminOptions['shop_display']==0?" checked":"") ?> />
                  <?php _e('Articles','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_display" value="1"<?php echo ($adminOptions['shop_display']==1?" checked":"") ?> />
                  <?php _e('Designs','spreadplugin'); ?></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Designs with background:','spreadplugin'); ?></td>
                <td><?php _e('Displays designs with background color of each first given article/shirt','spreadplugin'); ?>
                  <br />
                  <br />
                  <input type="radio" name="shop_designsbackground" value="0"<?php echo ($adminOptions['shop_designsbackground']==0?" checked":"") ?> />
                  <?php _e('Disabled','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_designsbackground" value="1"<?php echo ($adminOptions['shop_designsbackground']==1?" checked":"") ?> />
                  <?php _e('Enabled','spreadplugin'); ?></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Always show article description:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_showdescription" value="0"<?php echo ($adminOptions['shop_showdescription']==0?" checked":"") ?> />
                  <?php _e('Disabled','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_showdescription" value="1"<?php echo ($adminOptions['shop_showdescription']==1?" checked":"") ?> />
                  <?php _e('Enabled','spreadplugin'); ?></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Show product description under article description:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_showproductdescription" value="0"<?php echo ($adminOptions['shop_showproductdescription']==0?" checked":"") ?> />
                  <?php _e('Disabled','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_showproductdescription" value="1"<?php echo ($adminOptions['shop_showproductdescription']==1?" checked":"") ?> />
                  <?php _e('Enabled','spreadplugin'); ?></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Display price without and with tax:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_showextendprice" value="0"<?php echo ($adminOptions['shop_showextendprice']==0?" checked":"") ?> />
                  <?php _e('Disabled','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_showextendprice" value="1"<?php echo ($adminOptions['shop_showextendprice']==1?" checked":"") ?> />
                  <?php _e('Enabled','spreadplugin'); ?></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Zoom image background color:','spreadplugin'); ?></td>
                <td><input type="text" name="shop_zoomimagebackground" class="colorpicker" value="<?php echo (empty($adminOptions['shop_zoomimagebackground'])?'#FFFFFF':$adminOptions['shop_zoomimagebackground']); ?>" data-default-color="#FFFFFF" maxlength="7" /></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('View:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_view" value="0"<?php echo ($adminOptions['shop_view']==0 || $adminOptions['shop_view']==''?" checked":"") ?> />
                  <?php _e('Grid view','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_view" value="1"<?php echo ($adminOptions['shop_view']==1?" checked":"") ?> />
                  <?php _e('List view','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_view" value="2"<?php echo ($adminOptions['shop_view']==2?" checked":"") ?> />
                  <?php _e('Min view','spreadplugin'); ?>
                  (Disables Zoom, too)</td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Basket text or icon:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_basket_text_icon" value="0"<?php echo ($adminOptions['shop_basket_text_icon']==0 || $adminOptions['shop_basket_text_icon']==''?" checked":"") ?> />
                  <?php _e('Text','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_basket_text_icon" value="1"<?php echo ($adminOptions['shop_basket_text_icon']==1?" checked":"") ?> />
                  <?php _e('Icon','spreadplugin'); ?></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Infinity scrolling:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_infinitescroll" value="0"<?php echo ($adminOptions['shop_infinitescroll']==0?" checked":"") ?> />
                  <?php _e('Disabled','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_infinitescroll" value="1"<?php echo ($adminOptions['shop_infinitescroll']==1 || $adminOptions['shop_infinitescroll']==''?" checked":"") ?> />
                  <?php _e('Enabled','spreadplugin'); ?></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Lazy load:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_lazyload" value="0"<?php echo ($adminOptions['shop_lazyload']==0?" checked":"") ?> />
                  <?php _e('Disabled','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_lazyload" value="1"<?php echo ($adminOptions['shop_lazyload']==1 || $adminOptions['shop_lazyload']==''?" checked":"") ?> />
                  <?php _e('Enabled','spreadplugin'); ?>
                  <br />
                  <br />
                  <?php _e('If active, load images on view (speed up page load).','spreadplugin'); ?></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Zoom type:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_zoomtype" value="0"<?php echo ($adminOptions['shop_zoomtype']==0 || $adminOptions['shop_zoomtype']==''?" checked":"") ?> />
                  <?php _e('Inner','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_zoomtype" value="1"<?php echo ($adminOptions['shop_zoomtype']==1?" checked":"") ?> />
                  <?php _e('Lens','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_zoomtype" value="2"<?php echo ($adminOptions['shop_zoomtype']==2?" checked":"") ?> />
                  <?php _e('Disabled','spreadplugin'); ?></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Shop language:','spreadplugin'); ?></td>
                <td><select name="shop_language" id="shop_language">
                    <option value=""<?php echo (empty($adminOptions['shop_language'])?" selected":"") ?>>
                    <?php _e('Wordpress installation language (default)','spreadplugin'); ?>
                    </option>
                    <option value="da_DK"<?php echo ($adminOptions['shop_language']=='da_DK'?" selected":"") ?>>Dansk</option>
                    <option value="de_DE"<?php echo ($adminOptions['shop_language']=='de_DE'?" selected":"") ?>>Deutsch</option>
                    <option value="nl_NL"<?php echo ($adminOptions['shop_language']=='nl_NL'?" selected":"") ?>>Dutch (Nederlands)</option>
                    <option value="fi_FI"<?php echo ($adminOptions['shop_language']=='fi_FI'?" selected":"") ?>>Suomi</option>
                    <option value="es_ES"<?php echo ($adminOptions['shop_language']=='es_ES'?" selected":"") ?>>Español)</option>
                    <option value="fr_FR"<?php echo ($adminOptions['shop_language']=='fr_FR'?" selected":"") ?>>French</option>
                    <option value="it_IT"<?php echo ($adminOptions['shop_language']=='it_IT'?" selected":"") ?>>Italiano</option>
                    <option value="nb_NO"<?php echo ($adminOptions['shop_language']=='nb_NO'?" selected":"") ?>>Norsk</option>
                    <option value="nn_NO"<?php echo ($adminOptions['shop_language']=='nn_NO'?" selected":"") ?>>Nynorsk</option>
                    <option value="pl_PL"<?php echo ($adminOptions['shop_language']=='pl_PL'?" selected":"") ?>>Jezyk polski</option>
                    <option value="pt_PT"<?php echo ($adminOptions['shop_language']=='pt_PT'?" selected":"") ?>>Português</option>
                    <option value="jp_JP"<?php echo ($adminOptions['shop_language']=='jp_JP'?" selected":"") ?>>Japanese</option>
                  </select></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Custom CSS'); ?></td>
                <td><textarea style="width: 300px; height: 215px; background: #EEE;" name="shop_customcss" class="custom-css"><?php echo stripslashes(htmlspecialchars($adminOptions['shop_customcss'], ENT_QUOTES)); ?></textarea></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Debug mode:','spreadplugin'); ?></td>
                <td><input type="radio" name="shop_debug" value="0"<?php echo ($adminOptions['shop_debug']==0 || $adminOptions['shop_debug']==''?" checked":"") ?> />
                  <?php _e('Off','spreadplugin'); ?>
                  <br />
                  <input type="radio" name="shop_debug" value="1"<?php echo ($adminOptions['shop_debug']==1?" checked":"") ?> />
                  <?php _e('On','spreadplugin'); ?>
                  <br />
                  <br />
                  If active, all your spreadshirt/spreadplugin data could be exposed, so please be carefull with this option!</td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Sleep timer:','spreadplugin'); ?></td>
                <td><input type="text" name="shop_sleep" value="<?php echo (empty($adminOptions['shop_sleep'])?0:intval($adminOptions['shop_sleep'])); ?>" class="only-digit" />
                  <br />
                  <br />
                  <strong>Don't change this value, if you have no problems rebuilding your article cache otherwise it would take very long!</strong> Changing this value is only neccessary if you are experiencing problems when rebuilding cache. Some webspaces (e.g. godaddy.com) have request limits, which you can avoid by setting this value to for example 10.</td>
              </tr>
            </table>
            <input type="submit" name="update-splg_options" id="update-splg_options" class="button-primary" value="<?php _e('Update settings','spreadplugin'); ?>" />
          </form>
        </div>
      </div>
      <div class="postbox">
        <div class="handlediv" title="Click to toggle"><br />
        </div>
        <h3 class="hndle">Shortcode Samples</h3>
        <div class="inside">
          <h4>
            <?php _e('Minimum required shortcode','spreadplugin'); ?>
          </h4>
          <p>[spreadplugin]</p>
          <h4>
            <?php _e('Sample shortcode with category','spreadplugin'); ?>
          </h4>
          <p>[spreadplugin shop_category="CATEGORYID"]</p>
          <h4>
            <?php _e('Sample shortcode with only Men products','spreadplugin'); ?>
          </h4>
          <p>[spreadplugin shop_productcategory="Men"]</p>
          <h4>
            <?php _e('Extended sample shortcode','spreadplugin'); ?>
            (only for experienced users) </h4>
          <p>
            <?php _e('The extended shortcodes will overwrite the default settings. You may use it to create a different shop with the same plugin.'); ?>
          </p>
          <p>
            <?php
  
  $_plgop = '[spreadplugin ';
  foreach ($adminOptions as $k => $v) {
	  if ($k != 'shop_infinitescroll' && $k != 'shop_customcss' && $k != 'shop_debug' && $k != 'shop_sleep') {	
		$_plgop .= $k.'="'.$v.'" ';
	  }
  }
  
  echo trim($_plgop).']';
  
  ?>
          </p>
        </div>
      </div>
      <div class="postbox">
        <div class="handlediv" title="Click to toggle"><br />
        </div>
        <h3 class="hndle">
          <?php _e('Options','spreadplugin'); ?>
        </h3>
        <div class="inside">
          <p><a href="javascript:;" onclick="rebuild();"><strong>
            <?php _e('Rebuild cache','spreadplugin'); ?>
            </strong></a></p>
        </div>
      </div>
    </div>
  </div>
  <p>If you like this plugin, I'd be happy to read your comments on <a href="http://www.facebook.com/lovetee.de" target="_blank">facebook</a>. 
    If you experience any problems or have suggestions, feel free to leave a message on <a href="http://wordpress.org/support/plugin/wp-spreadplugin" target="_blank">wordpress</a> or send an email to <a href="mailto:info@spreadplugin.de">info@spreadplugin.de</a>.<br />
  </p>
  <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=EZLKTKW8UR6PQ" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" alt="Jetzt einfach, schnell und sicher online bezahlen � mit PayPal." /></a>
  <p>All donations or backlinks to <a href="http://lovetee.de/" target="_blank">http://lovetee.de/</a> valued greatly</p>
</div>
<script language='javascript' type='text/javascript'>
function setMessage(msg) {
	jQuery("#sprdplg-message").append(msg); //.html(msg)
	jQuery("#sprdplg-message").show();
}

function rebuildItem(listcontent,cur1,cur2) {
	
	if (cur2==0) {
		if (typeof listcontent[cur1].title !== 'undefined') {
			setMessage("Rebuilding Page " + (cur1+1) + " of " + listcontent.length + "...<br>");
		} else {
			setMessage("Rebuilding Page " + (cur1+1) + " of " + listcontent.length + " (" + listcontent[cur1].title + ")...<br>");
		}
	}
	
	
	if (cur2 >= listcontent[cur1].items.length) {
		setMessage("Done<br>");
		
		// storing items
		jQuery.ajax({
			url: "<?php echo admin_url('admin-ajax.php'); ?>",
			type: "POST",
			data: "action=rebuildCache&do=save&_pageid=" + listcontent[cur1].id + "&_ts=" + (new Date()).getTime(),
			timeout: 360000,
			cache: false,
			success: function(result) {
				//console.debug(result);
				setMessage("Successfully stored page " + cur1 + "<br>");
			},
			error: function(request, status, error) {
				setMessage("Error " + request.status + " storing page " + cur1 + "<br>");
			}
			
		});
		
		// next page
		cur1 = cur1 + 1;
		
		if (listcontent[cur1]) {
			rebuildItem(listcontent,cur1,0);
		}

		return;
	}
	
	
	setMessage("Rebuilding Item " + (cur2+1) + " of " + listcontent[cur1].items.length + " (" + listcontent[cur1].items[cur2].articlename + ") <img src='" + listcontent[cur1].items[cur2].previewimage + "' width='32' height='32'>... ");

	jQuery.ajax({
		url: "<?php echo admin_url('admin-ajax.php'); ?>",
		type: "POST",
		data: "action=rebuildCache&do=rebuild&_pageid=" + listcontent[cur1].id + "&_articleid=" + listcontent[cur1].items[cur2].articleid + "&_pos=" + listcontent[cur1].items[cur2].place + "&_ts=" + (new Date()).getTime(),
		success: function(result) {
			setMessage(result + ' <br>');
			
			// next item
			cur2 = cur2 + 1;
			rebuildItem(listcontent,cur1,cur2);
		},
		error: function(request, status, error) {
			setMessage("Request not performed error " + request.status + '. Try next<br>');
			
			// skip to next item
			cur2 = cur2 + 1;
			rebuildItem(listcontent,cur1,cur2);
		}
		
	});
}
				
function rebuild() {
	
	jQuery('html, body').animate({scrollTop: 0}, 800);
	setMessage("Reading pages. Please wait.<br>");
	
	jQuery.ajax({
		url: "<?php echo admin_url('admin-ajax.php'); ?>",
		type: "POST",
		data: "action=rebuildCache&do=getlist" + "&_ts=" + (new Date()).getTime(),
		timeout: 360000,
		cache: false,
		dataType: 'json',
		success: function(result) {
			var list = result;

			if (!list) {
				setMessage("No pages found.<br>");
				return;
			}
	
			var curr1 = 0;				
			var curr2 = 0;

			rebuildItem(list,curr1,curr2);
		},
		error: function(request, status, error) {
			setMessage("Getlist not performed error '" + error + " (" + request.status + ")'. Please check the browser console for more informations." + '<br>');
			console.log("Got following error message: " + request.responseText);
		}
	});
}
			

jQuery('.only-digit').keyup(function() {
	if (/\D/g.test(this.value)) {
		// Filter non-digits from input value.
		this.value = this.value.replace(/\D/g, '');
	}
});

// select different locale if north america is set
jQuery('#shop_locale').change(function() {
	var sel = jQuery(this).val();

	if (sel == 'us_US' || sel == 'us_CA' || sel == 'fr_CA') {
		jQuery('#shop_source').val('com');
	} else {
		jQuery('#shop_source').val('net');
	}
});
jQuery('#premium-shop-span').hide();	
jQuery('input[type=radio][name=shop_designer]').click(function() {
	jQuery('#premium-shop-span').hide();
});
jQuery('input[type=radio][name=shop_designer][value=2]').not(':selected').click(function() {
	jQuery('#premium-shop-span').show();
});

if (jQuery('#premium-shop-span input').val().length >0) {
	jQuery('#premium-shop-span').show();
}


// bind to the form's submit event
jQuery('#splg_options_form').submit(function() {

	var isFormValid = true;
		
	jQuery("#splg_options_form .required").each(function() { 
		if (jQuery.trim(jQuery(this).val()).length == 0) {
			jQuery(this).parent().addClass("highlight");
			isFormValid = false;
		} else {
			jQuery(this).parent().removeClass("highlight");
		}
	});
	
	
	// Formularprüfung
	if (!isFormValid) { 	
		setMessage("<p><?php _e('Please fill in the highlighted fields!','spreadplugin'); ?></p>");
	} else {
		return true;
	}

	return false;
});

// add color picker
jQuery(document).ready(function() {  
	jQuery('.colorpicker').wpColorPicker();  
});
</script>
<?php 
if (isset($_GET['saved'])) {
	/*echo '<script language="javascript">rebuild();</script>';*/
	echo '<script language="javascript">setMessage("<p>'.__('Successfully saved settings. Please click rebuild cache if necessary.','spreadplugin').'</p>");</script>';
}


} ?>
