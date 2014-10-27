/*
 *	jQuery for ads displaying at frontend
 *
 *
 */


(function($) { 

var viewed = [];

$(document).ready(function($) {
    // Using default configuration
    $('.aas_zone').each(function(){
		var w = jQuery(this).data('w');
		var h = jQuery(this).data('h');
		var t = jQuery(this).data('t') * 1000;
	jQuery(this).carouFredSel({
	width: w,
	height:h ,
	scroll : {
            easing          : "linear",
            duration        : 800,                         
            pauseOnHover    : true,
			fx : 'fade',
			onAfter : logging_ads
        } ,
	auto : {
			timeoutDuration : t
		}
	});
	$(this).css({visibility:'visible'});

	});
	logging_ads();
    // Using custom configuration
  
});
$(window).scroll(logging_ads);

function logging_ads(){

	$('.aas_wrapper').each(function(){
		if( isVisible( jQuery(this).children() ) && jQuery(this).offset().left == jQuery(this).closest('.caroufredsel_wrapper').offset().left && viewed[jQuery(this).data('ads')] != 1 ){
		viewed[jQuery(this).data('ads')] = 1;
		var data = {
		action : 'aas_view_log',
		nonce : jQuery(this).data('nonce'),
		data : jQuery(this).data('ads')
		};
		jQuery.post(ajax.url,data,function(response){});
		}
	});
}
function isVisible(elem)
{
    var docViewTop = $(window).scrollTop();
    var docViewBottom = docViewTop + $(window).height();
	var docViewLeft = $(window).scrollLeft();
    var docViewRight = docViewLeft + $(window).width();

    var elemTop = elem.offset().top;
    var elemBottom = elemTop + elem.height();
	var elemLeft = elem.offset().left;
    var elemRight = elemLeft + elem.width();

    return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop) && (elemLeft >= docViewLeft) && (elemRight <= docViewRight));
}


})(jQuery)