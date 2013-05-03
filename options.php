<div class="wrap">
  <?php screen_icon(); ?>
  <form action="options.php" method="post" id="spg_options_form" name="spg_options_form">
    <h2>Spreadplugin Plugin Options &raquo; Settings</h2>
    <div id="message" class="updated fade" style="display:none"></div>
    <br />
    <br />
    <br />
    <a href="javascript:;" onclick="regenerate();"><?php echo __('Regenerate Cache'); ?></a>
  </form>
  <script language="javascript">
	function setMessage(msg) {
		jQuery("#message").html(msg);
		jQuery("#message").show();
	}

	function regenerate() {
		jQuery.post("<?php echo admin_url('admin-ajax.php'); ?>","action=regenCache", function() {
			setMessage("<p><?php echo __('Successfull cleared cache'); ?></p>");
		});
	}
</script> 
</div>
