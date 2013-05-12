var sep = '?';
var prod = getParameterByName('productCategory');
var prod2 = getParameterByName('productSubCategory');
var sor = getParameterByName('articleSortBy');
var infiniteItemSel = '.spreadshirt-article';
var fancyBoxWidth = 840;

if (display == 1) {
	infiniteItemSel = '.spreadshirt-designs';
}

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
	jQuery('.spreadshirt-design .image-wrapper').unbind();
	jQuery('.spreadshirt-article form').unbind();

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

	jQuery('.spreadshirt-article form')
			.submit(
					function(event) {

						event.preventDefault();
						var data = jQuery(this).serialize() + '&action=myAjax';
						var form = this;
						var button = jQuery('#' + form.id
								+ ' input[type=submit]');

						button.val(textButtonAdded);

						jQuery
								.post(
										ajaxLocation,
										data,
										function(json) {
											button.val(textButtonAdd);
											jQuery(
													'#spreadshirt-items #spreadshirt-menu #checkout a')
													.attr('href', json.c.u);
											jQuery(
													'#spreadshirt-items #spreadshirt-menu #checkout a')
													.removeAttr('title');
											jQuery(
													'#spreadshirt-items #spreadshirt-menu #checkout span')
													.text(json.c.q);
										}, 'json');

						return false;

					});

	if (pageCheckoutUseIframe == 2) {
		jQuery('.spreadshirt-article #editArticle').fancybox({
			type : 'iframe',
			fitToView : false,
			autoSize : false,
			height : 1000,
			width : fancyBoxWidth,
			preload : true,
			afterClose : function() {
				location.reload();
				return;
			}
		});
	}

	jQuery('.spreadshirt-article .image-wrapper a').fancybox({
		type : 'iframe',
		fitToView : false,
		autoSize : false,
		height : 1000,
		width : fancyBoxWidth,
		preload : true
	});

	jQuery('.spreadshirt-design .image-wrapper').click(
			function() {

				var id = jQuery(this).parent().attr('id');
				id = '#' + id.replace('design', 'designContainer');

				if (jQuery(id).is(':hidden')) {
					jQuery(id).addClass('active');
					jQuery(id).slideDown('slow');
				} else {
					jQuery('#spreadshirt-list .design-container').slideUp(
							'slow', function() {
								jQuery(this).removeClass('active');
							});
				}

			});

}

function bindHover() {

	// display image caption on top of image
	jQuery(".spreadshirt-design div.image-wrapper").each(
			function() {

				jQuery(this).hover(
						function() {
							jQuery(this).find(".img-caption").stop(true).css(
									'display', 'inline-block').animate({
								'top' : -50
							}, {
								queue : false,
								duration : 400
							});
						},
						function() {
							jQuery(this).find(".img-caption").stop(true).hide()
									.animate({
										'top' : 0
									});
						});
			});

	// Articles
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

	jQuery('.spreadshirt-items ul.soc-icons a').hover(
			function() {
				jQuery(this).parent().css('background-color',
						jQuery(this).attr('data-color'));
			}, function() {
				jQuery(this).parent().removeAttr('style');
			});

}

bindClick();
bindHover();

// Fixed menu bar
jQuery(function() {
	var msie6 = jQuery.browser == 'msie' && jQuery.browser.version < 7;
	if (!msie6 && jQuery('.spreadshirt-menu').length != 0) {
		var top = jQuery('#spreadshirt-menu').offset().top
				- parseFloat(jQuery('#spreadshirt-menu').css('margin-top')
						.replace(/auto/, 0));

		jQuery(window).scroll(
				function(event) {
					// what the y position of the scroll is
					var y = jQuery(this).scrollTop();
					// whether that's below the form
					if (y >= top - 0) {
						// if so, ad the fixed class
						jQuery('#spreadshirt-menu').addClass('fixed');

						// using wp #main container width and pos for fixed
						jQuery('#spreadshirt-menu').css('width',
								jQuery('div.spreadshirt-items').width());
						// jQuery('#spreadshirt-menu').css('left',jQuery('div.spreadshirt-items').position().left);
					} else {
						// otherwise remove it
						jQuery('#spreadshirt-menu').css('width', '');
						// jQuery('#spreadshirt-menu').css('left','');
						jQuery('#spreadshirt-menu').removeClass('fixed');
					}
				});
	}
});

// reload caption
jQuery(window).resize(function() {
	jQuery(".img-caption").hide();
});

// infinity scroll
jQuery('#spreadshirt-list').infinitescroll({
	nextSelector : '#spreadshirt-list #pagination a',
	navSelector : '#spreadshirt-list #pagination',
	itemSelector : '#spreadshirt-list ' + infiniteItemSel,
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
});

jQuery('#spreadshirt-items #productCategory').change(
		function() {
			prod = jQuery(this).val();
			document.location = pageLink + sep + 'productCategory=' + prod
					+ '&articleSortBy=' + sor;
		});
jQuery('#spreadshirt-items #productSubCategory').change(
		function() {
			prod2 = jQuery(this).val();
			document.location = pageLink + sep + 'productCategory=' + prod
					+ '&productSubCategory=' + prod2 + '&articleSortBy=' + sor;
		});

jQuery('#spreadshirt-items #articleSortBy').change(
		function() {
			sor = jQuery(this).val();
			document.location = pageLink + sep + 'productCategory=' + prod
					+ '&productSubCategory=' + prod2 + '&articleSortBy=' + sor;
		});

// checkout in an iframe in page
if (pageCheckoutUseIframe == 1) {
	jQuery('#spreadshirt-items #spreadshirt-menu a')
			.click(
					function(event) {
						event.preventDefault();

						var checkoutLink = jQuery(
								'#spreadshirt-items #spreadshirt-menu a').attr(
								'href');

						if (typeof checkoutLink !== "undefined"
								&& checkoutLink.length > 0) {

							jQuery('#spreadshirt-items #pagination').remove();
							jQuery('#spreadshirt-items #spreadshirt-menu')
									.remove();
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

	jQuery('#spreadshirt-items #spreadshirt-menu a').fancybox({
		type : 'iframe',
		fitToView : false,
		autoSize : false,
		height : 1000,
		width : fancyBoxWidth,
		preload : true,
		afterClose : function() {
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

