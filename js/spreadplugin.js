/**
 * Plugin Name: WP-Spreadplugin
 * Plugin URI: http://wordpress.org/extend/plugins/wp-spreadplugin/
 * Description: This plugin uses the Spreadshirt API to list articles and let your customers order articles of your Spreadshirt shop using Spreadshirt order process.
 * Version: 3.2
 * Author: Thimo Grauerholz
 * Author URI: http://lovetee.de/
 */

jQuery(function($) {

	var sep = '?';
	var prod = getParameterByName('productCategory');
	var prod2 = getParameterByName('productSubCategory');
	var sor = getParameterByName('articleSortBy');
	var infiniteItemSel = '.spreadshirt-article';
	var fancyBoxWidth = 840;
	var appearance = '';
	var view = '';
//	var sid = document.cookie.match(/PHPSESSID=[^;]+/);

	if (display == 1) {
		infiniteItemSel = '.spreadshirt-designs';
	}

	if (pageLink.indexOf('?') > -1) {
		sep = '&';
	}
	
	
	$('#cart').hide();
	

	/*
	 * change article color and view
	 */
	function bindClick() {
		// avoid double firing events
		$('.spreadshirt-article .colors li,.spreadshirt-article-detail .colors li').unbind();
		$('.spreadshirt-article .views li,.spreadshirt-article-detail .views li').unbind();
		$('.spreadshirt-article .description-wrapper div.header,.spreadshirt-article-detail .description-wrapper div.header').unbind();
		$('.spreadshirt-design .image-wrapper').unbind();
		$('.spreadshirt-article form,.spreadshirt-article-detail form').unbind();
		$('.spreadshirt-article .edit-wrapper a,.spreadshirt-article-detail .edit-wrapper a').unbind();
		$('.spreadshirt-article .details-wrapper a,.spreadshirt-article-detail .details-wrapper a').unbind();
		$('.spreadshirt-article .image-wrapper,.spreadshirt-article-detail .image-wrapper').unbind();

		$('.spreadshirt-article .colors li,.spreadshirt-article-detail .colors li')
				.click(
						function() {
							var id = '#'
									+ $(this).closest(
											'.spreadshirt-article,.spreadshirt-article-detail').attr('id');
							var src = $(id + ' img.preview').attr('src');
							var srczoom = $(id + ' img.preview').attr(
									'data-zoom-image');
							var srczoomData = $(id + ' img.preview').data(
									'elevateZoom');
							appearance = $(this).attr('value');

							$(id + ' img.preview').attr(
									'src',
									src.replace(/\,appearanceId=(\d+)/g, '')
											+ ',appearanceId=' + appearance);

							$(id + ' img.previewview')
									.each(
											function() {
												var originalsrc = $(this)
														.attr('src');
												$(this)
														.attr(
																'src',
																originalsrc
																		.replace(
																				/\,appearanceId=(\d+)/g,
																				'')
																		+ ',appearanceId='
																		+ appearance);
											});

							$(id + ' img.preview').attr(
									'data-zoom-image',
									srczoom
											.replace(/\,appearanceId=(\d+)/g,
													'')
											+ ',appearanceId=' + appearance);

							$(id + ' #appearance').val(appearance);

							var url = srczoomData.imageSrc.replace(
									/\,viewId=(\d+)/g, '');
							url = url + ',appearanceId=' + appearance;
							url = url + ',viewId='
									+ $(id + ' #view').val();

							srczoomData.zoomWindow.css({
								backgroundImage : "url('" + url + "')"
							});

						});

		$('.spreadshirt-article .views li,.spreadshirt-article-detail .views li').click(
				function() {
					var id = '#'
							+ $(this).closest('.spreadshirt-article,.spreadshirt-article-detail')
									.attr('id');
					var src = $(id + ' img.previewview').attr('src');
					var srczoomData = $(id + ' img.preview').data(
							'elevateZoom');
					view = $(this).attr('value');

					$(id + ' img.preview').attr(
							'src',
							src.replace(/\,viewId=(\d+)/g, '').replace(
									/width=(\d+)/g, 'width=' + imageSize)
									.replace(/height=(\d+)/g,
											'height=' + imageSize)
									+ ',viewId=' + view);

					$(id + ' #view').val(view);

					var url = srczoomData.imageSrc.replace(/\,viewId=(\d+)/g,
							'');
					url = url + ',appearanceId='
							+ $(id + ' #appearance').val();
					url = url + ',viewId=' + view;

					srczoomData.zoomWindow.css({
						backgroundImage : "url('" + url + "')"
					});

				});

		$('.spreadshirt-article .description-wrapper div.header,.spreadshirt-article-detail .description-wrapper div.header').click(
				function() {
					var par = $(this).parent().parent().parent();
					var field = $(this).next();

					if (field.is(':hidden')) {
						par.addClass('activeDescription');
						field.show();
						$(this).children('a').html(textHideDesc);
					} else {
						par.removeClass('activeDescription');
						$('.description-wrapper div.description').hide();
						$('.description-wrapper div.header a').html(
								textShowDesc);
					}
				});

		$('.spreadshirt-article form,.spreadshirt-article-detail form')
				.submit(
						function(event) {

							event.preventDefault();
							var data = $(this).serialize()
									+ '&action=myAjax'; //&'+sid
							var form = this;
							var button = $('#' + form.id
									+ ' input[type=submit]');

							button.val(textButtonAdded);

							$
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
			$('.spreadshirt-article .edit-wrapper a,.spreadshirt-article-detail .edit-wrapper a').fancybox({
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

			$('.spreadshirt-article .details-wrapper a,.spreadshirt-article-detail .details-wrapper a').fancybox({
				type : 'iframe',
				fitToView : false,
				autoSize : false,
				height : 1000,
				width : fancyBoxWidth,
				preload : true
			});

		}

		$('.spreadshirt-article .image-wrapper a,.spreadshirt-article-detail .image-wrapper a').fancybox({
			type : 'iframe',
			fitToView : false,
			autoSize : false,
			height : 1000,
			width : fancyBoxWidth,
			preload : true
		});

		$('.spreadshirt-design .image-wrapper').click(
				function() {

					var id = $(this).parent().attr('id');
					id = '#' + id.replace('design', 'designContainer');

					if ($(id).is(':hidden')) {
						$(id).addClass('active');
						$(id).slideDown('slow');
					} else {
						$('#spreadshirt-list .design-container').slideUp(
								'slow', function() {
									$(this).removeClass('active');
								});
					}

				});

	}

	function bindHover() {
		$(".spreadshirt-article img.preview,.spreadshirt-article-detail img.preview").unbind();

		// display image caption on top of image
		$(".spreadshirt-design div.image-wrapper").each(
				function() {

					$(this).hover(
							function() {
								$(this).find(".img-caption").stop(true)
										.css('display', 'inline-block')
										.animate({
											'top' : -50
										}, {
											queue : false,
											duration : 400
										});
							},
							function() {
								$(this).find(".img-caption").stop(true)
										.hide().animate({
											'top' : 0
										});
							});
				});

		// Articles zoom image
		$(".spreadshirt-article img.preview,.spreadshirt-article-detail img.preview").hover(function() {
				$(this).elevateZoom({
				zoomType : "inner",
				cursor : "crosshair",
				easing : true
			});
		});

		// socials
		$('.spreadshirt-article ul.soc-icons a,.spreadshirt-article-detail ul.soc-icons a').hover(
				function() {
					$(this).parent().css('background-color',
							$(this).attr('data-color'));
				}, function() {
					$(this).parent().removeAttr('style');
				});

	}

	// Fixed menu bar
	var msie6 = $.browser == 'msie' && $.browser.version < 7;
	if (!msie6 && $('.spreadshirt-menu').length != 0) {
		var top = $('#spreadshirt-menu').offset().top
				- parseFloat($('#spreadshirt-menu').css('margin-top')
						.replace(/auto/, 0));

		$(window).scroll(
				function(event) {
					// what the y position of the scroll is
					var y = $(this).scrollTop();
					// whether that's below the form
					if (y >= top - 0) {
						// if so, ad the fixed class
						$('#spreadshirt-menu').addClass('fixed');

						// using wp #main container width and pos for fixed
						$('#spreadshirt-menu').css('width',
								$('div.spreadshirt-items').width());
						// $('#spreadshirt-menu').css('left',$('div.spreadshirt-items').position().left);
					} else {
						// otherwise remove it
						$('#spreadshirt-menu').css('width', '');
						// $('#spreadshirt-menu').css('left','');
						$('#spreadshirt-menu').removeClass('fixed');
					}
				});
	}

	// reload caption
	$(window).resize(function() {
		$(".img-caption").hide();
	});

	if (infiniteScroll == 1) {
		// infinity scroll
		$('#spreadshirt-list').infinitescroll({
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
			$("img.lazy").lazyload({effect : "fadeIn"});
		});
	}

	$('#spreadshirt-items #productCategory').change(
			function() {
				prod = $(this).val();
				document.location = pageLink + sep + 'productCategory=' + prod
						+ '&articleSortBy=' + sor;
			});
	$('#spreadshirt-items #productSubCategory').change(
			function() {
				prod2 = $(this).val();
				document.location = pageLink + sep + 'productCategory=' + prod
						+ '&productSubCategory=' + prod2 + '&articleSortBy='
						+ sor;
			});

	$('#spreadshirt-items #articleSortBy').change(
			function() {
				sor = $(this).val();
				document.location = pageLink + sep + 'productCategory=' + prod
						+ '&productSubCategory=' + prod2 + '&articleSortBy='
						+ sor;
			});


		$('#basketLink').click(
						function(event) {
							event.preventDefault();
							
							if ($('#cart').is(':hidden')) {
								$('#cart').show();
							} else {
								$('#cart').hide();
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
		
		$('#spreadshirt-items #spreadshirt-menu #checkout a').attr('href', json.c.u);
		$('#spreadshirt-items #spreadshirt-menu #checkout a').removeAttr('title');
		$('#spreadshirt-items #spreadshirt-menu #checkout span').text(json.c.q);
		$('#cart-checkout a').attr('href', json.c.u);
		
		// &'+sid
		$.get(ajaxLocation,'action=myCart',function (data) {
			$('#spreadshirt-items #cart').html(data);
			
			
			// checkout in an iframe in page
			if (pageCheckoutUseIframe == 1) {
						$('#cart-checkout a').click(
								function(event) {
									event.preventDefault();
		
									var checkoutLink = $(
											'#cart-checkout a')
											.attr('href');
		
									if (typeof checkoutLink !== "undefined"
											&& checkoutLink.length > 0) {
		
										$('#spreadshirt-items #pagination')
												.remove();
										$('#spreadshirt-items #spreadshirt-menu')
												.remove();
										$(window).unbind('.infscr');
		
										$('#spreadshirt-list')
												.html(
														'<iframe style="z-index:10002" id="checkoutFrame" frameborder="0" width="900" height="2000" scroll="yes">');
										$('#spreadshirt-list #checkoutFrame')
												.attr('src', checkoutLink);
		
										$('html, body')
												.animate(
														{
															scrollTop : $(
																	"#spreadshirt-items #checkoutFrame")
																	.offset().top
														}, 2000);
		
									}
								});
		
			}
		
			// checkout in an iframe with modal window (fancybox)
			if (pageCheckoutUseIframe == 2) {
					var checkoutLink = $(
							'#cart-checkout a')
							.attr('href');
		
					if (typeof checkoutLink !== "undefined" && checkoutLink.length > 0) {
					
							$('#cart-checkout a').fancybox({
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
			
			
			$('.cart-row a.deleteCartItem').click(function(e) {
				e.preventDefault;
				$(this).closest('.cart-row').show().fadeOut('slow');
				
				// &'+sid+'
				$.post(ajaxLocation,	'action=myDelete&id='+$(this).closest('.cart-row').data('id'),function() {	
					// &'+sid
					$.post(ajaxLocation,	'action=myAjax',function(json) {
						refreshCart(json);
						}, 'json');	
					});	

			});
			
			
		});
	}


	
	// &'+sid
	$.post(ajaxLocation,	'action=myAjax',function(json) {
		refreshCart(json);
	}, 'json');	
			
	setInterval(function() {
		$.post(ajaxLocation,	'action=myAjax',function(json) {
			refreshCart(json);
		}, 'json');	
	}, 10000);
	
	

	bindClick();
	bindHover();
	$("img.lazy").lazyload({effect : "fadeIn"});
	
});



