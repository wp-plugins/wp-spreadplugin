var scrollingDiv = jQuery('#spreadshirt-items #checkout');
var sep = '?';
var prod = getParameterByName('productCategory');
var sor = getParameterByName('articleSortBy');

if (pageLink.indexOf('?') > -1) {
	sep = '&';
}

/*
 * change article color and view
 */
function bindClick() {
	// avoid double firing events
	jQuery('#spreadshirt-items .colors li').unbind();
	jQuery('#spreadshirt-items .views li').unbind();
	jQuery('#spreadshirt-items .description-wrapper div.header').unbind();

	jQuery('#spreadshirt-items .colors li').click(
			function() {
				var id = '#'
						+ jQuery(this).closest('.spreadshirt-article').attr(
								'id');
				var appearance = jQuery(this).attr('value');
				var src = jQuery(id + ' img.preview').attr('src');
				var srccomp = jQuery(id + ' img.compositions').attr('src');

				jQuery(id + ' img.preview').attr(
						'src',
						src.replace(/\,appearanceId=(\d+)/g, '').replace(
								/\,viewId=(\d+)/g, '')
								+ ',appearanceId=' + appearance);
				jQuery(id + ' img.compositions').attr(
						'src',
						srccomp.replace(/\,appearanceId=(\d+)/g, '').replace(
								/\,viewId=(\d+)/g, '')
								+ ',appearanceId=' + appearance);

				jQuery(id + ' img.previewview').each(
						function() {
							var originalsrc = jQuery(this).attr('src');
							jQuery(this).attr(
									'src',
									originalsrc.replace(
											/\,appearanceId=(\d+)/g, '')
											+ ',appearanceId=' + appearance);
						});

				jQuery(id + ' #appearance').attr('value', appearance);
			});

	jQuery('#spreadshirt-items .views li').click(
			function() {
				var id = '#'
						+ jQuery(this).closest('.spreadshirt-article').attr(
								'id');
				var view = jQuery(this).attr('value');
				var src = jQuery(id + ' img.previewview').attr('src');
				var srccomp = jQuery(id + ' img.compositions').attr('src');

				jQuery(id + ' img.preview').attr(
						'src',
						src.replace(/\,viewId=(\d+)/g, '').replace(
								/\,width=(\d+)\,height=(\d+)/g, '')
								+ ',viewId=' + view);
				jQuery(id + ' img.compositions').attr(
						'src',
						srccomp.replace(/\,viewId=(\d+)/g, '') + ',viewId='
								+ view);
				jQuery(id + ' #view').attr('value', view);
			});

	jQuery('#spreadshirt-items .description-wrapper div.header').click(
			function() {
				var par = jQuery(this).parent().parent().parent();
				var field = jQuery(this).next();

				if (field.is(':hidden')) {
					par.addClass('activeDescription');
					field.show();
					jQuery(this).children('a').html(textHideDesc);
				} else {
					par.removeClass('activeDescription');
					jQuery('.description-wrapper div.description').hide();
					jQuery('.description-wrapper div.header a').html(
							textShowDesc);
				}
			});

	jQuery('.spreadshirt-article form').submit(function(event) {

		event.preventDefault();
		var data = jQuery(this).serialize() + '&action=myAjax';
		var form = this;
		var button = jQuery('#' + form.id + ' input[type=submit]');

		button.val(textButtonAdded);

		jQuery.post(ajaxLocation, data, function(json) {
			button.val(textButtonAdd);
			jQuery('#spreadshirt-items #checkout a').attr('href', json.c.u);
			jQuery('#spreadshirt-items #checkout a').removeAttr('title');
			jQuery('#spreadshirt-items #checkout span').text(json.c.q);
		}, 'json');

		return false;

	});

}

function bindHover() {
	jQuery('#spreadshirt-items img.preview').mouseenter(function() {
		var id = jQuery(this).attr('id');
		id = '#' + id.replace('previewimg', 'compositeimg');

		if (jQuery(this).is(':visible')) {
			jQuery(this).hide();
			jQuery(id).show();
		}
	});

	jQuery('#spreadshirt-items .spreadshirt-article').mouseleave(function() {
		var id = jQuery(this).attr('id');
		id = id.replace('article', '');

		jQuery('#' + 'compositeimg' + id).hide();
		jQuery('#' + 'previewimg' + id).show();
	});

	if (socialButtonsEnabled == true) {
		jQuery('#spreadshirt-items .fb-like').hover(
				function() {
					jQuery('meta[property=\"og:title\"]').attr('content',
							jQuery(this).parent().parent().find('h3').html());
					jQuery('meta[property=\"og:url\"]').attr('content',
							jQuery(this).attr('data-href'));
					jQuery('meta[property=\"og:image\"]').attr(
							'content',
							jQuery(this).parent().parent().find('.preview')
									.attr('src'));
				});
	}
}

bindClick();
bindHover();

jQuery(window).scroll(function() {
	scrollingDiv.stop().animate({
		'marginTop' : (jQuery(window).scrollTop() + 30) + 'px'
	}, 'slow');
});

jQuery('#spreadshirt-list').infinitescroll({
	nextSelector : '#spreadshirt-list #navigation a',
	navSelector : '#spreadshirt-list #navigation',
	itemSelector : '#spreadshirt-list .spreadshirt-article',
	loading : {
		img : loadingImage,
		msgText : loadingMessage,
		finishedMsg : loadingFinishedMessage
	},
	animate : true,
	debug : false,
	bufferPx : 40
}, function(arrayOfNewElems) {
	bindClick();
	bindHover();

	if (socialButtonsEnabled == true) {
		FB.XFBML.parse();
		twttr.widgets.load();
	}
});

jQuery('#spreadshirt-items #productCategory').change(
		function() {
			prod = jQuery(this).val();
			document.location = pageLink + sep + 'productCategory=' + prod
					+ '&articleSortBy=' + sor;
		});

jQuery('#spreadshirt-items #articleSortBy').change(
		function() {
			sor = jQuery(this).val();
			document.location = pageLink + sep + 'productCategory=' + prod
					+ '&articleSortBy=' + sor;
		});

// checkout in an iframe in page
if (pageCheckoutUseIframe == 1) {
	jQuery('#spreadshirt-items #checkout a')
			.click(
					function(event) {
						event.preventDefault();

						var checkoutLink = jQuery(
								'#spreadshirt-items #checkout a').attr('href');

						if (typeof checkoutLink !== "undefined"
								&& checkoutLink.length > 0) {

							jQuery('#spreadshirt-items #navigation').remove();
							jQuery('#spreadshirt-items #checkout').remove();
							jQuery(window).unbind('.infscr');

							jQuery('#spreadshirt-list')
									.html(
											'<iframe style="z-index:10002" id="checkoutFrame" frameborder="0" width="900" height="2000" scroll="yes">');
							jQuery('#spreadshirt-list #checkoutFrame').attr(
									'src', checkoutLink);

							jQuery('html, body')
									.animate(
											{
												scrollTop : jQuery(
														"#spreadshirt-items #checkoutFrame")
														.offset().top
											}, 2000);

						}
					});

}

// checkout in an iframe with modal window (fancybox)
if (pageCheckoutUseIframe == 2) {

	jQuery('#spreadshirt-items #checkout a').fancybox({
		type: 'iframe',
		fitToView: false,
		autoSize: false,
		height: 1000,
		width: 800,
		preload: true,
		afterClose: function() {
			location.reload();
			return;
		}
	});

}

function getParameterByName(name) {
	name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
	var regexS = "[\\?&]" + name + "=([^&#]*)";
	var regex = new RegExp(regexS);
	var results = regex.exec(window.location.search);
	if (results == null) {
		return "";
	} else {
		return encodeURIComponent(decodeURIComponent(results[1].replace(/\+/g,
				" ")));
	}
}
