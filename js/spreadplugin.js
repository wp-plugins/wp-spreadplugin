var saheight = jQuery('.spreadshirt-article').css('height');
var par = '';
var scrollingDiv = jQuery('.spreadshirt-items #checkout');

/*
 * change article color and view
 */
function bindClick() {
	// avoid double firing events
	jQuery('.colors li').unbind();
	jQuery('.views li').unbind();
	jQuery('.description-wrapper div.header').unbind();

	jQuery('.colors li').click(
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

	jQuery('.views li').click(
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

	jQuery('.description-wrapper div.header').click(function() {
		var par = jQuery(this).parent().parent().parent();
		var field = jQuery(this).next();

		if (field.is(':hidden')) {
			par.css('height', '');
			par.removeAttr('style');
			field.show();
			jQuery(this).children('a').html(textHideDesc);
		} else {
			jQuery('.spreadshirt-article').css('height', saheight);
			jQuery('.description-wrapper div.description').hide();
			jQuery('.description-wrapper div.header a').html(textShowDesc);
		}
	});
}

function bindHover() {
	jQuery('img.preview').mouseenter(function() {
		var id = jQuery(this).attr('id');
		id = '#' + id.replace('previewimg', 'compositeimg');

		if (jQuery(this).is(':visible')) {
			jQuery(this).hide();
			jQuery(id).show();
		}
	});

	jQuery('.spreadshirt-article').mouseleave(function() {
		var id = jQuery(this).attr('id');
		id = id.replace('article', '');

		jQuery('#' + 'compositeimg' + id).hide();
		jQuery('#' + 'previewimg' + id).show();
	});

	if (socialButtonsEnabled == true) {
		jQuery('.fb-like').hover(
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
	nextSelector : '#navigation a',
	navSelector : '#navigation',
	itemSelector : '.spreadshirt-article',
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

jQuery('#productCategory').change(function() {
	var sep;
	if (pageLink[pageLink.length-1]=='/') { sep='?'; } else { sep='&'; }
	document.location = pageLink + sep + 'productCategory='+jQuery(this).val();
});

