
var saheight = jQuery('.spreadshirt-article').css('height');
var par = '';
var scrollingDiv = jQuery('#checkout');


/*
* change article color
*/
function bindClick() {
	// avoid double firing events
	jQuery('.colors li').unbind();
	jQuery('.description-wrapper div.header').unbind();
	
	
	jQuery('.colors li').click(function(){
		var id = '#' + jQuery(this).closest('.spreadshirt-article').attr('id');
		var appearance = jQuery(this).attr('value');
		var src = jQuery(id + ' img.preview').attr('src');
		jQuery(id + ' img.preview').attr('src', src + ',appearanceId='+appearance);
		jQuery(id + ' #appearance').attr('value', appearance);
	});
	
	
	jQuery('.description-wrapper div.header').click(function(){
		var par = jQuery(this).parent().parent().parent();
		var field = jQuery(this).next();
		
		if (field.is(':hidden')) {
			par.css('height','');
			par.removeAttr('style');
			field.show();
			jQuery(this).children('a').html(textHideDesc);
		} else {
			jQuery('.spreadshirt-article').css('height',saheight);
			jQuery('.description-wrapper div.description').hide();
			jQuery('.description-wrapper div.header a').html(textShowDesc);
		}
	});

}



function bindHover() {
	jQuery('img.preview').mouseenter(function(){
	var id = jQuery(this).attr('id');
	id = '#' + id.replace('previewimg','compositeimg');
	
	if (jQuery(this).is(':visible')) {
		jQuery(this).hide();
		jQuery(id).show();
	}
	});
	
	jQuery('.spreadshirt-article').mouseleave(function(){
		var id = jQuery(this).attr('id');
		id = id.replace('article','');
		
		jQuery('#' + 'compositeimg' + id).hide();
		jQuery('#' + 'previewimg' + id).show();
	});

	
	jQuery('.fb-like').hover(function(){
		jQuery('meta[property=\"og:title\"]').attr('content',jQuery(this).parent().parent().find('h3').html());
		jQuery('meta[property=\"og:url\"]').attr('content',jQuery(this).attr('data-href'));
		jQuery('meta[property=\"og:image\"]').attr('content',jQuery(this).parent().parent().find('.preview').attr('src'));
	});
	
}



bindClick();
bindHover();


jQuery(window).scroll(function(){
	scrollingDiv.stop().animate({'marginTop': (jQuery(window).scrollTop() + 30) + 'px'}, 'slow');
});





jQuery('#spreadshirt-list').infinitescroll({
	nextSelector:'#navigation a',
	navSelector:'#navigation',
	itemSelector:'.spreadshirt-article',
	loading: {
	img: loadingImage,
	msgText: loadingMessage,
	finishedMsg: loadingFinishedMessage
	},
	animate: true,
	debug: false,
	bufferPx: 40
	}, function(arrayOfNewElems){
	bindClick();
	bindHover();
	
	FB.XFBML.parse();
	twttr.widgets.load();
});



