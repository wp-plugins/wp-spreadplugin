<script language="javascript">
	function setMessage(msg) {
		jQuery("#message").html(msg);
		jQuery("#message").show();
	}

	function rebuild() {
		jQuery.post("<?php echo admin_url('admin-ajax.php'); ?>","action=regenCache", function() {
			setMessage("<p><?php echo __('Successfull cleared cache'); ?></p>");
		});
	}
</script>

<div class="wrap">
  <?php screen_icon(); ?>
  <form action="options.php" method="post" id="spg_options_form" name="spg_options_form">
  <h2>Spreadplugin Plugin Options &raquo; Settings</h2>
  <div id="message" class="updated fade" style="display:none"></div>
  <p>&nbsp;</p>
  <p><a href="javascript:;" onclick="rebuild();"><strong><?php echo __('Clear cache'); ?></strong></a></p>
  <p>&nbsp;</p>
  <p>If you like this plugin, I'd be happy to read your comments on <a href="http://www.facebook.com/pr3ss.play" target="_blank">facebook</a>. 
    If you experience any problems or have suggestions, feel free to leave a message on <a href="http://wordpress.org/support/plugin/wp-spreadplugin" target="_blank">wordpress</a> or send an email to <a href="mailto:info@spreadplugin.de">info@spreadplugin.de</a>.<br />
  </p>
  <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
    <input type="hidden" name="cmd" value="_s-xclick">
    <input type="hidden" name="hosted_button_id" value="EZLKTKW8UR6PQ">
    <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="Jetzt einfach, schnell und sicher online bezahlen – mit PayPal.">
    <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
  </form>
  <p>All donations or backlinks to <a href="http://www.pr3ss-play.de/" target="_blank">http://www.pr3ss-play.de/</a> valued greatly</p>
</div>
