/**
 * Plugin Name: WP-Spreadplugin
 * Plugin URI: http://wordpress.org/extend/plugins/wp-spreadplugin/
 * Description: This plugin uses the Spreadshirt API to list articles and let your customers order articles of your Spreadshirt shop using Spreadshirt order process.
 * Version: 3.1.4
 * Author: Thimo Grauerholz
 * Author URI: http://lovetee.de/
 */

jQuery(function() {

	var sep = '?';
	var prod = getParameterByName('productCategory');
	var prod2 = getParameterByName('productSubCategory');
	var sor = getParameterByName('articleSortBy');
	var infiniteItemSel = '.spreadshirt-article';
	var fancyBoxWidth = 840;
	var appearance = '';
	var view = '';
	var sid = document.cookie.match(/PHPSESSID=[^;]+/);

	if (display == 1) {
		infiniteItemSel = '.spreadshirt-designs';
	}

	if (pageLink.indexOf('?') > -1) {
		sep = '&';
	}
	
	
	jQuery('#cart').hide();
	

	/*
	 * change article color and view
	 */
	function bindClick() {
		// avoid double firing events
		jQuery('.spreadshirt-article .colors li,.spreadshirt-article-detail .colors li').unbind();
		jQuery('.spreadshirt-article .views li,.spreadshirt-article-detail .views li').unbind();
		jQuery('.spreadshirt-article .description-wrapper div.header,.spreadshirt-article-detail .description-wrapper div.header').unbind();
		jQuery('.spreadshirt-design .image-wrapper').unbind();
		jQuery('.spreadshirt-article form,.spreadshirt-article-detail form').unbind();
		jQuery('.spreadshirt-article .edit-wrapper a,.spreadshirt-article-detail .edit-wrapper a').unbind();
		jQuery('.spreadshirt-article .details-wrapper a,.spreadshirt-article-detail .details-wrapper a').unbind();
		jQuery('.spreadshirt-article .image-wrapper,.spreadshirt-article-detail .image-wrapper').unbind();

		jQuery('.spreadshirt-article .colors li,.spreadshirt-article-detail .colors li')
				.click(
						function() {
							var id = '#'
									+ jQuery(this).closest(
											'.spreadshirt-article,.spreadshirt-article-detail').attr('id');
							var src = jQuery(id + ' img.preview').attr('src');
							var srczoom = jQuery(id + ' img.preview').attr(
									'data-zoom-image');
							var srczoomData = jQuery(id + ' img.preview').data(
									'elevateZoom');
							appearance = jQuery(this).attr('value');

							jQuery(id + ' img.preview').attr(
									'src',
									src.replace(/\,appearanceId=(\d+)/g, '')
											+ ',appearanceId=' + appearance);

							jQuery(id + ' img.previewview')
									.each(
											function() {
												var originalsrc = jQuery(this)
														.attr('src');
												jQuery(this)
														.attr(
																'src',
																originalsrc
																		.replace(
																				/\,appearanceId=(\d+)/g,
																				'')
																		+ ',appearanceId='
																		+ appearance);
											});

							jQuery(id + ' img.preview').attr(
									'data-zoom-image',
									srczoom
											.replace(/\,appearanceId=(\d+)/g,
													'')
											+ ',appearanceId=' + appearance);

							jQuery(id + ' #appearance').val(appearance);

							var url = srczoomData.imageSrc.replace(
									/\,viewId=(\d+)/g, '');
							url = url + ',appearanceId=' + appearance;
							url = url + ',viewId='
									+ jQuery(id + ' #view').val();

							srczoomData.zoomWindow.css({
								backgroundImage : "url('" + url + "')"
							});

						});

		jQuery('.spreadshirt-article .views li,.spreadshirt-article-detail .views li').click(
				function() {
					var id = '#'
							+ jQuery(this).closest('.spreadshirt-article,.spreadshirt-article-detail')
									.attr('id');
					var src = jQuery(id + ' img.previewview').attr('src');
					var srczoomData = jQuery(id + ' img.preview').data(
							'elevateZoom');
					view = jQuery(this).attr('value');

					jQuery(id + ' img.preview').attr(
							'src',
							src.replace(/\,viewId=(\d+)/g, '').replace(
									/width=(\d+)/g, 'width=' + imageSize)
									.replace(/height=(\d+)/g,
											'height=' + imageSize)
									+ ',viewId=' + view);

					jQuery(id + ' #view').val(view);

					var url = srczoomData.imageSrc.replace(/\,viewId=(\d+)/g,
							'');
					url = url + ',appearanceId='
							+ jQuery(id + ' #appearance').val();
					url = url + ',viewId=' + view;

					srczoomData.zoomWindow.css({
						backgroundImage : "url('" + url + "')"
					});

				});

		jQuery('.spreadshirt-article .description-wrapper div.header,.spreadshirt-article-detail .description-wrapper div.header').click(
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

		jQuery('.spreadshirt-article form,.spreadshirt-article-detail form')
				.submit(
						function(event) {

							event.preventDefault();
							var data = jQuery(this).serialize()
									+ '&action=myAjax&'+sid;
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
												refreshCart(json);
											}, 'json');

							return false;

						});


		if (pageCheckoutUseIframe == 2) {
			jQuery('.spreadshirt-article .edit-wrapper a,.spreadshirt-article-detail .edit-wrapper a').fancybox({
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

			jQuery('.spreadshirt-article .details-wrapper a,.spreadshirt-article-detail .details-wrapper a').fancybox({
				type : 'iframe',
				fitToView : false,
				autoSize : false,
				height : 1000,
				width : fancyBoxWidth,
				preload : true
			});

		}

		jQuery('.spreadshirt-article .image-wrapper a,.spreadshirt-article-detail .image-wrapper a').fancybox({
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
		jQuery(".spreadshirt-article img.preview,.spreadshirt-article-detail img.preview").unbind();

		// display image caption on top of image
		jQuery(".spreadshirt-design div.image-wrapper").each(
				function() {

					jQuery(this).hover(
							function() {
								jQuery(this).find(".img-caption").stop(true)
										.css('display', 'inline-block')
										.animate({
											'top' : -50
										}, {
											queue : false,
											duration : 400
										});
							},
							function() {
								jQuery(this).find(".img-caption").stop(true)
										.hide().animate({
											'top' : 0
										});
							});
				});

		// Articles zoom image
		jQuery(".spreadshirt-article img.preview,.spreadshirt-article-detail img.preview").elevateZoom({
			zoomType : "inner",
			cursor : "crosshair",
			easing : true
		});

		// socials
		jQuery('.spreadshirt-article ul.soc-icons a,.spreadshirt-article-detail ul.soc-icons a').hover(
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

	// reload caption
	jQuery(window).resize(function() {
		jQuery(".img-caption").hide();
	});

	if (infiniteScroll == 1) {
		// infinity scroll
		jQuery('#spreadshirt-list').infinitescroll({
			nextSelector : '#spreadshirt-items #pagination a',
			navSelector : '#spreadshirt-items #pagination',
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
	}

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
						+ '&productSubCategory=' + prod2 + '&articleSortBy='
						+ sor;
			});

	jQuery('#spreadshirt-items #articleSortBy').change(
			function() {
				sor = jQuery(this).val();
				document.location = pageLink + sep + 'productCategory=' + prod
						+ '&productSubCategory=' + prod2 + '&articleSortBy='
						+ sor;
			});


		jQuery('#basketLink').click(
						function(event) {
							event.preventDefault();
							
							if (jQuery('#cart').is(':hidden')) {
								jQuery('#cart').show();
							} else {
								jQuery('#cart').hide();
							}
						});



	function getParameterByName(name) {
		name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
		var regexS = "[\\?&]" + name + "=([^&#]*)";
		var regex = new RegExp(regexS);
		var results = regex.exec(window.location.search);
		if (results == null) {
			return "";
		} else {
			return encodeURIComponent(decodeURIComponent(results[1].replace(/\+/g, " ")));
		}
	}
	
	
	function refreshCart(json) {
		
		jQuery('#spreadshirt-items #spreadshirt-menu #checkout a').attr('href', json.c.u);
		jQuery('#spreadshirt-items #spreadshirt-menu #checkout a').removeAttr('title');
		jQuery('#spreadshirt-items #spreadshirt-menu #checkout span').text(json.c.q);
		jQuery('#cart-checkout a').attr('href', json.c.u);
		
		jQuery.get(ajaxLocation,'action=myCart&'+sid,function (data) {
			jQuery('#spreadshirt-items #cart').html(data);
			
			
			// checkout in an iframe in page
			if (pageCheckoutUseIframe == 1) {
						jQuery('#cart-checkout a').click(
								function(event) {
									event.preventDefault();
		
									var checkoutLink = jQuery(
											'#cart-checkout a')
											.attr('href');
		
									if (typeof checkoutLink !== "undefined"
											&& checkoutLink.length > 0) {
		
										jQuery('#spreadshirt-items #pagination')
												.remove();
										jQuery('#spreadshirt-items #spreadshirt-menu')
												.remove();
										jQuery(window).unbind('.infscr');
		
										jQuery('#spreadshirt-list')
												.html(
														'<iframe style="z-index:10002" id="checkoutFrame" frameborder="0" width="900" height="2000" scroll="yes">');
										jQuery('#spreadshirt-list #checkoutFrame')
												.attr('src', checkoutLink);
		
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
					var checkoutLink = jQuery(
							'#cart-checkout a')
							.attr('href');
		
					if (typeof checkoutLink !== "undefined" && checkoutLink.length > 0) {
					
							jQuery('#cart-checkout a').fancybox({
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
			}
			
			
			jQuery('.cart-row a.deleteCartItem').click(function(e) {
				e.preventDefault;
				jQuery(this).closest('.cart-row').show().fadeOut('slow');
				
				jQuery.post(ajaxLocation,	'action=myDelete&'+sid+'&id='+jQuery(this).closest('.cart-row').data('id'),function() {	
					jQuery.post(ajaxLocation,	'action=myAjax&'+sid,function(json) {
						refreshCart(json);
						}, 'json');	
					});	

			});
			
			
		});
	}
	
	
	jQuery.post(ajaxLocation,	'action=myAjax&'+sid,function(json) {
		refreshCart(json);
		}, 'json');	
	
});
