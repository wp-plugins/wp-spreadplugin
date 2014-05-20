<?php 

if (is_user_logged_in() && is_admin()) {
	
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

<div class="wrap">
  <?php 
  screen_icon(); 
  ?>
  <h2>Spreadplugin Plugin Options &raquo; Settings</h2>
  <div id="message" class="updated fade" style="display:none"></div>
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
                <td><select name="shop_locale" id="shop_locale" class="required">
                    <option value="de_DE"<?php echo ($adminOptions['shop_locale']=='de_DE' || empty($adminOptions['shop_locale'])?" selected":"") ?>>Deutschland</option>
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
                  </select></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Shop source:','spreadplugin'); ?></td>
                <td><select name="shop_source" id="shop_source" class="required">
                    <option value="net"<?php echo ($adminOptions['shop_source']=='net'?" selected":"") ?>>Europe</option>
                    <option value="com"<?php echo ($adminOptions['shop_source']=='com'?" selected":"") ?>>US/Canada</option>
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
                    <?php _e('All products'); ?>
                    </option>
                    <option value="<?php _e('Men'); ?>"<?php echo ($adminOptions['shop_productcategory']==__('Men')?" selected":""); ?>>
                    <?php _e('Men'); ?>
                    </option>
                    <option value="<?php _e('Women'); ?>"<?php echo ($adminOptions['shop_productcategory']==__('Women')?" selected":""); ?>>
                    <?php _e('Women'); ?>
                    </option>
                    <option value="<?php _e('Kids & Babies'); ?>"<?php echo ($adminOptions['shop_productcategory']==__('Kids & Babies')?" selected":""); ?>>
                    <?php _e('Kids & Babies'); ?>
                    </option>
                    <option value="<?php _e('Accessories'); ?>"<?php echo ($adminOptions['shop_productcategory']==__('Accessories')?" selected":""); ?>>
                    <?php _e('Accessories'); ?>
                    </option>
                    <option value="<?php _e('New Products'); ?>"<?php echo ($adminOptions['shop_productcategory']==__('New Products')?" selected":""); ?>>
                    <?php _e('New Products'); ?>
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
                    <option></option>
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
                  <input type="text" name="shop_linktarget" value="<?php echo (empty($adminOptions['shop_linktarget'])?'_blank':$adminOptions['shop_linktarget']); ?>" /></td>
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
                <td valign="top"><?php _e('Designer Shop ID:','spreadplugin'); ?></td>
                <td><?php _e('If you have a designer Shop (Spreadshirt premium account), enter the ID here. A new link will appear where the customer can change the design.','spreadplugin'); ?>
                  <br />
                  <br />
                  <input type="text" name="shop_designershop" value="<?php echo $adminOptions['shop_designershop']; ?>" class="only-digit" /></td>
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
                  <?php _e('List view','spreadplugin'); ?></td>
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
                  <?php _e('Lens','spreadplugin'); ?></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Shop language:','spreadplugin'); ?></td>
                <td><select name="shop_language" id="shop_language">
                     <option value=""<?php echo (empty($adminOptions['shop_language'])?" selected":"") ?>><?php _e('Wordpress installation language (default)','spreadplugin'); ?></option>
                    <option value="da_DK"<?php echo ($adminOptions['shop_language']=='da_DK'?" selected":"") ?>>Dansk</option>
                   <option value="de_DE"<?php echo ($adminOptions['shop_language']=='de_DE'?" selected":"") ?>>Deutsch</option>
                    <option value="nl_NL"<?php echo ($adminOptions['shop_language']=='nl_NL'?" selected":"") ?>>Dutch (Nederlands)</option>
                    <option value="fr_FR"<?php echo ($adminOptions['shop_language']=='fr_FR'?" selected":"") ?>>Fran�ais</option>
                    <option value="it_IT"<?php echo ($adminOptions['shop_language']=='it_IT'?" selected":"") ?>>Italiano</option>
                   <option value="nb_NO"<?php echo ($adminOptions['shop_language']=='nb_NO'?" selected":"") ?>>Norsk (Bokm�l)</option>
                   <option value="nb_NO"<?php echo ($adminOptions['shop_language']=='nn_NO'?" selected":"") ?>>Nynorsk</option>
                  </select></td>
              </tr>
              <tr>
                <td valign="top"><?php _e('Custom CSS'); ?></td>
                <td><textarea style="width: 300px; height: 215px; background: #EEE;" name="shop_customcss" class="custom-css"><?php echo stripslashes(htmlspecialchars($adminOptions['shop_customcss'], ENT_QUOTES)); ?></textarea></td>
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
            <?php _e('Extended sample shortcode','spreadplugin'); ?>
          </h4>
          <p>
            <?php _e('The extended shortcodes will overwrite the default settings. You may use it to create a different shop with the same plugin.'); ?>
          </p>
          <p> [spreadplugin
            <?php
  
  $_plgop = '';
  foreach ($adminOptions as $k => $v) {
	  if ($k != 'shop_infinitescroll' && $k != 'shop_customcss') {	
		$_plgop .= $k.'="'.$v.'" ';
	  }
  }
  
  echo trim($_plgop);
  
  ?>
            ] </p>
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
            <?php _e('Clear cache','spreadplugin'); ?>
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
<script language="javascript">
function setMessage(msg) {
	jQuery("#message").append(msg); //.html(msg)
	jQuery("#message").show();
	jQuery('html, body').animate({scrollTop: 0}, 800);
}

function rebuild() {
	jQuery.post("<?php echo admin_url('admin-ajax.php'); ?>","action=regenCache", function() {
		setMessage("<p><?php _e('Successfully cleared the cache','spreadplugin'); ?></p>");
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
	
	
	// Formularpr�fung
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
	echo '<script language="javascript">rebuild();</script>';
	echo '<script language="javascript">setMessage("<p>'.__('Successfully saved settings','spreadplugin').'</p>");</script>';
}


} ?>
