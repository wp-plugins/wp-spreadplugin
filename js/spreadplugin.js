/**
 * Plugin Name: WP-Spreadplugin
 * Plugin URI: http://wordpress.org/extend/plugins/wp-spreadplugin/
 * Description: This plugin uses the Spreadshirt API to list articles and let your customers order articles of your Spreadshirt shop using Spreadshirt order process.
 * Version: 3.5.5.5
 * Author: Thimo Grauerholz
 * Author URI: http://www.spreadplugin.de
 */

jQuery(function($) {

	var sep = '?';
	var prod = getParameterByName('productCategory');
	var prod2 = getParameterByName('productSubCategory');
	var sor = getParameterByName('articleSortBy');
	var infiniteItemSel = '.spreadplugin-article';
	var appearance = '';
	var view = '';
//	var sid = document.cookie.match(/PHPSESSID=[^;]+/);

	if (display == 1) {
		infiniteItemSel = '.spreadplugin-designs';
	}

	if (pageLink.indexOf('?') > -1) {
		sep = '&';
	}
	
	
	$('.spreadplugin-cart').hide();
	
	// hide cart when user clicks outside
	$(document).click(function(e) {
	    if (e.target.className != 'spreadplugin-checkout-link' && $('.spreadplugin-cart').is(':visible') && !$('.spreadplugin-cart').find(e.target).length) {
    	    $(".spreadplugin-cart").hide();
    	}
	});
	

	/*
	 * change article color and view
	 */
	function bindClick() {
		// avoid double firing events
		$('.spreadplugin-article .colors li,.spreadplugin-article-detail .colors li').unbind();
		$('.spreadplugin-article .views li,.spreadplugin-article-detail .views li').unbind();
		$('.spreadplugin-article .description-wrapper div.header,.spreadplugin-article-detail .description-wrapper div.header').unbind();
		$('.spreadplugin-article .product-description-wrapper div.header,.spreadplugin-article-detail .product-description-wrapper div.header').unbind();
		$('.spreadplugin-design .image-wrapper').unbind();
		$('.spreadplugin-article form,.spreadplugin-article-detail form').unbind();
		$('.spreadplugin-article .edit-wrapper a,.spreadplugin-article-detail .edit-wrapper a').unbind();
		$('.spreadplugin-article .details-wrapper a,.spreadplugin-article-detail .details-wrapper a').unbind();
		$('.spreadplugin-article .image-wrapper,.spreadplugin-article-detail .image-wrapper').unbind();

		$('.spreadplugin-article .colors li,.spreadplugin-article-detail .colors li').click(function() {	
	
			var id = '#' + $(this).closest('.spreadplugin-article,.spreadplugin-article-detail').attr('id');
			var image = $(id + ' img.preview');
			var src = image.attr('src');
			var srczoom = image.attr('data-zoom-image');
			var srczoomData = image.data('elevateZoom');
			
			appearance = $(this).attr('value');
			view = 	$(id + ' #view').val();						
			$(id + ' #appearance').val(appearance);

			image.attr('src',image.attr('src').replace(/\,appearanceId=(\d+)/g, '') 
			+ ',appearanceId=' + appearance);
			
			image.attr('data-zoom-image',	srczoom
			.replace(/\,appearanceId=(\d+)/g,'')
			.replace(/\,viewId=(\d+)/g,'')
			+ ',appearanceId=' + appearance + ',viewId=' + view);
			
			$(id + ' img.previewview').each(function() {
				var originalsrc = $(this).attr('src');
				$(this).attr('src',originalsrc.replace(/\,appearanceId=(\d+)/g,'') + ',appearanceId=' + appearance);
			});

			if (srczoomData) {
				var url = srczoomData.imageSrc.replace(	/\,appearanceId=(\d+)/g, '').replace(/\,viewId=(\d+)/g, '');
				url = url + ',appearanceId=' + appearance + ',viewId=' + view;
				srczoomData.imageSrc = url;
				srczoomData.zoomImage = url;
				srczoomData.currentImage = url;
				
				if (srczoomData.zoomWindow) {
					srczoomData.zoomWindow.css({
						backgroundImage : "url('" + url + "')"
					});
				}
				if (srczoomData.zoomLens) {
					srczoomData.zoomLens.css({
						backgroundImage : "url('" + url + "')"
					});
				}
			}		
		});

		$('.spreadplugin-article .views li,.spreadplugin-article-detail .views li').click(function() {
			var id = '#' + $(this).closest('.spreadplugin-article,.spreadplugin-article-detail').attr('id');						
			var image = $(id + ' img.preview');
			var src = image.attr('src');
			var srczoom = image.attr('data-zoom-image');
			var srczoomData = image.data('elevateZoom');
			
			view = $(this).attr('value');						
			appearance = $(id + ' #appearance').val();						
			$(id + ' #view').val(view);
				
			image.attr('src',src.replace(/\,viewId=(\d+)/g, '')
				.replace(/width=(\d+)/g, 'width=' + Math.round(image.width()))
				.replace(/height=(\d+)/g,'height=' + Math.round(image.height())) + ',viewId=' + view);

			image.attr('data-zoom-image',	srczoom
			.replace(/\,appearanceId=(\d+)/g,'')
			.replace(/\,viewId=(\d+)/g,'')
			+ ',appearanceId='	+ appearance + ',viewId=' + view);
			
			if (srczoomData) {
				var url = srczoomData.imageSrc.replace(	/\,appearanceId=(\d+)/g, '').replace(/\,viewId=(\d+)/g,'');
				url = url + ',appearanceId='	+ appearance + ',viewId=' + view;
				srczoomData.imageSrc = url;
				srczoomData.zoomImage = url;
				srczoomData.currentImage = url;
				if (srczoomData.zoomWindow) {
					srczoomData.zoomWindow.css({
						backgroundImage : "url('" + url + "')"
					});
				}
				if (srczoomData.zoomLens) {
					srczoomData.zoomLens.css({
						backgroundImage : "url('" + url + "')"
					});
				}
			}
		});

		$('.spreadplugin-article .description-wrapper div.header,.spreadplugin-article-detail .description-wrapper div.header').click(function() {
					var par = $(this).parent().parent().parent();
					var field = $(this).next();

					if (field.is(':hidden')) {
						par.addClass('activeDescription');
						field.show();
						$(this).children('a').html(textHideDesc);
					} else {
						par.removeClass('activeDescription');
						$('.description-wrapper div.description').hide();
						$('.description-wrapper div.header a').html(	textShowDesc);
					}
				});
		$('.spreadplugin-article .product-description-wrapper div.header,.spreadplugin-article-detail .description-wrapper div.header').click(function() {
					var par = $(this).parent().parent().parent();
					var field = $(this).next();

					if (field.is(':hidden')) {
						par.addClass('activeDescription');
						field.show();
						$(this).children('a').html(textProdHideDesc);
					} else {
						par.removeClass('activeDescription');
						$('.product-description-wrapper div.description').hide();
						$('.product-description-wrapper div.header a').html(textProdShowDesc);
					}
				});

		$('.spreadplugin-article form,.spreadplugin-article-detail form').submit(function(event) {

							event.preventDefault();
							var data = $(this).serialize() + '&action=myAjax'; //&'+sid
							var form = this;
							var button = $('#' + form.id + ' input[type=submit]');


							// to basket animation vars
							var productIdValSplitter = form.id.split("_");
							var productIdVal = productIdValSplitter[1];
							var newImageWidth   = $("#previewimg_" + productIdVal).width() / 3;	
							var newImageHeight  = $("#previewimg_" + productIdVal).height() / 3;
							var productX = $("#previewimg_" + productIdVal).offset().left;
							var productY = $("#previewimg_" + productIdVal).offset().top;
							var basketX = $(".spreadplugin-checkout").offset().left;
							var basketY = $(".spreadplugin-checkout").offset().top;
							var gotoX = basketX - productX;
							var gotoY = basketY - productY;
												
						
							button.val(textButtonAdded);
							$("#article_" + productIdVal + ' img.preview')
								.clone()
								.prependTo("#article_" + productIdVal)
								.css({'position' : 'absolute'})
								.css({'z-index' : '1008'})
								.animate({opacity: 0.9}, 100 )
								.animate({opacity: 0.1, marginLeft: gotoX, marginTop: gotoY, width: newImageWidth, height: newImageHeight}, 1200, function() { 
									$.post(ajaxLocation,data,function(json) {
															button.val(textButtonAdd);
															refreshCart(json);
														}, 'json');
									$(this).remove();	
								});
							
							

			return false;
		});




		if (pageCheckoutUseIframe == 2) {
			$('.spreadplugin-article .edit-wrapper a,.spreadplugin-article-detail .edit-wrapper a').magnificPopup({
			type: 'iframe',
			callbacks: {
				open: function() {
					$('.mfp-iframe-holder .mfp-content').css('height',$(window).height()-200);
				},
				resize: function () {
					$('.mfp-iframe-holder .mfp-content').css('height',$(window).height()-200);
				}
			}
			});


			$('.spreadplugin-article .details-wrapper a,.spreadplugin-article-detail .details-wrapper a').magnificPopup({
				type: 'iframe',
				preloader: true
			});
		}

		$('.spreadplugin-article .image-wrapper a,.spreadplugin-article-detail .image-wrapper a').magnificPopup({
			type: 'iframe',
			preloader: true
		});

		$('.spreadplugin-design .image-wrapper').click(function() {

					var id = $(this).parent().attr('id');
					id = '#' + id.replace('design', 'designContainer');

					if ($(id).is(':hidden')) {
						$(id).addClass('active');
						$(id).slideDown('slow');
					} else {
						$('#spreadplugin-list .design-container').slideUp('slow', function() {
									$(this).removeClass('active');
								});
					}

				});

	}

	function bindHover() {
		$(".spreadplugin-article img.preview,.spreadplugin-article-detail img.preview").unbind();

		// display image caption on top of image
		$(".spreadplugin-design div.image-wrapper").each(function() {

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
		$(".spreadplugin-article img.preview,.spreadplugin-article-detail img.preview").hover(function() {
			$(this).elevateZoom(zoomConfig);
		});

		// socials
		$('.spreadplugin-article ul.soc-icons a,.spreadplugin-article-detail ul.soc-icons a').hover(function() {
			$(this).parent().css('background-color',$(this).attr('data-color'));
		}, function() {
			$(this).parent().removeAttr('style');
		});

	}

	// Fixed menu bar
	var msie6 = $.browser == 'msie' && $.browser.version < 7;
	if (!msie6 && $('.spreadplugin-menu').length != 0) {
		var top = $('#spreadplugin-menu').offset().top - parseFloat($('#spreadplugin-menu').css('margin-top').replace(/auto/, 0));

		$(window).scroll(function(event) {
					// what the y position of the scroll is
					var y = $(this).scrollTop();
					// whether that's below the form
					if (y >= top - 0) {
						// if so, ad the fixed class
						$('#spreadplugin-menu').addClass('fixed');

						// using wp #main container width and pos for fixed
						$('#spreadplugin-menu').css('width',$('div.spreadplugin-items').width());
					} else {
						// otherwise remove it
						$('#spreadplugin-menu').css('width', '');
						$('#spreadplugin-menu').removeClass('fixed');
					}
				});
	}

	// reload caption
	$(window).resize(function() {
		$(".img-caption").hide();
	});

	if (infiniteScroll == 1) {
		// infinity scroll
		$('#spreadplugin-list').infinitescroll({
			nextSelector : '#spreadplugin-items #pagination a',
			navSelector : '#spreadplugin-items #pagination',
			itemSelector : '#spreadplugin-list ' + infiniteItemSel,
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
			
			if (lazyLoad == 1) {
				$("img.lazyimg").lazyload({effect : "fadeIn"});
			}
		});
	}

	$('#spreadplugin-items #productCategory').change(function() {
				prod = $(this).val();
				document.location = pageLink + sep + 'productCategory=' + prod + '&articleSortBy=' + sor;
			});
	$('#spreadplugin-items #productSubCategory').change(function() {
				prod2 = $(this).val();
				document.location = pageLink + sep + 'productCategory=' + prod + '&productSubCategory=' + prod2 + '&articleSortBy=' + sor;
			});

	$('#spreadplugin-items #articleSortBy').change(function() {
				sor = $(this).val();
				document.location = pageLink + sep + 'productCategory=' + prod + '&productSubCategory=' + prod2 + '&articleSortBy=' + sor;
			});


	$('.spreadplugin-checkout-link').click(function(event) {
		event.preventDefault();
		
		//mergeBasket();
		
		var cart = $(this).parent().next('.spreadplugin-cart');
		
		if (cart.is(':hidden')) {
			cart.show();
		} else {
			cart.hide();
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
		
		$('.spreadplugin-checkout a').attr('href', json.c.u);
		$('.spreadplugin-checkout a').removeAttr('title');
		$('.spreadplugin-checkout span').text(json.c.q);
		$('.spreadplugin-cart-checkout a').attr('href', json.c.u);
		
		// &'+sid
		$.get(ajaxLocation,'action=myCart',function (data) {
			$('.spreadplugin-cart').html(data);
			
			
			// checkout in an iframe in page
			if (pageCheckoutUseIframe == 1) {
						$('.spreadplugin-cart-checkout a').click(function(event) {
									event.preventDefault();
		
									var checkoutLink = $(this).attr('href');
		
									if (typeof checkoutLink !== "undefined" && checkoutLink.length > 0) {
		
										$('#spreadplugin-items #pagination').remove();
										$('#spreadplugin-items #spreadplugin-menu').remove();
										$(window).unbind('.infscr');
		
										$('#spreadplugin-list').html('<iframe style="z-index:10002" id="checkoutFrame" frameborder="0" width="900" height="2000" scroll="yes">');
										$('#spreadplugin-list #checkoutFrame').attr('src', checkoutLink);
		
										$('html, body').animate({
															scrollTop : $("#spreadplugin-items #checkoutFrame").offset().top
														}, 2000);
		
									}
								});
		
			}
		
			// checkout in an iframe with modal window (magnific)
			if (pageCheckoutUseIframe == 2) {
					var checkoutLink = $('.spreadplugin-cart-checkout a').attr('href');
		
					if (typeof checkoutLink !== "undefined" && checkoutLink.length > 0) {
					
							$('.spreadplugin-cart-checkout a').magnificPopup({
					type: 'iframe',
					callbacks: {
						close: function() {
							location.reload();
							return;
						}
					}
					});
				}
			}
			
			
			$('.cart-row a.deleteCartItem').click(function(e) {
				e.preventDefault;
				$(this).closest('.cart-row').show().fadeOut('slow');
				
				// &'+sid+'
				$.post(ajaxLocation,'action=myDelete&id='+$(this).closest('.cart-row').data('id'),function() {});

			});
			
			
		});
	}

	// call to merge the designer shop basket with the api basket
	function mergeBasket() {
		$.get(ajaxLocation,'action=mergeBasket',function(json) {
		//console.debug(json);
		}, 'json');	
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
	if (lazyLoad == 1) {
		$("img.lazyimg").lazyload({effect : "fadeIn"});
	}
	
	
	
	
	$("#spreadplugin-tabs li").click(function() {
		//	First remove class "active" from currently active tab
		$("#spreadplugin-tabs li").removeClass('active');

		//	Now add class "active" to the selected/clicked tab
		$(this).addClass("active");

		//	Hide all tab content
		$(".spreadplugin-tab_content").hide();

		//	Show the selected tab content
		$($(this).find("a").attr("href")).fadeIn();

		//	At the end, we add return false so that the click on the link is not executed
		return false;
	});
	
});
