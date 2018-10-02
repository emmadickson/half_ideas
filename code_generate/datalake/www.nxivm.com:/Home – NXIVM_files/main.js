(function($) {

	"use strict";



/* ==========================================================================
   ewfRowBehaviour - improves visual composer row resize
   ========================================================================== */

if ( typeof window[ 'ewf_rowBehaviour' ] !== 'function' ) {

	// console.log('- INIT - Ewf Row Behaviour -');
	window.ewf_rowBehaviour = function () {

		var $ = window.jQuery;
		var local_function = function () {

			var $elements = $( '[data-ewf-full-width="true"]' );
			var $el_wrap = $('#wrap');

			$.each( $elements, function ( key, item ) {
				var $el = $( this );
				var $el_full = $el.next( '.vc_row-full-width' );
				var el_margin_left = parseInt( $el.css( 'margin-left' ), 10 );
				var el_margin_right = parseInt( $el.css( 'margin-right' ), 10 );
				var offset = 0 - ($el_full.offset().left - $el_wrap.offset().left) - el_margin_left;
				var width = $el_wrap.width();

				if ( ! $el.data( 'ewfStretchContent' ) ) {
					var padding = (- 1 * offset);
					if ( padding < 0 ) {
						padding = 0;
					}

					var paddingRight = $el_wrap.width() - padding - $el_full.width() + el_margin_left + el_margin_right;
					if ( paddingRight < 0 ) {
						paddingRight = 0;
					}

					$el.css( { 'padding-left': padding + 'px', 'padding-right': paddingRight + 'px' } );
				}

				$el.css( {
					'position': 'relative',
					'left': offset,
					'box-sizing': 'border-box',
					'width': (width)
				} );

				$el.attr( "data-ewf-full-width-init", "true" );
			} );
		};


		$( window ).bind( 'resize.ewf_rowBehaviour', local_function );
		local_function();
	}

}



/* ==========================================================================
   ieViewportFix - fixes viewport problem in IE 10 SnapMode and IE Mobile 10
   ========================================================================== */

	function ieViewportFix() {

		var msViewportStyle = document.createElement("style");

		msViewportStyle.appendChild(
			document.createTextNode(
				"@-ms-viewport { width: device-width; }"
			)
		);

		if (navigator.userAgent.match(/IEMobile\/10\.0/)) {

			msViewportStyle.appendChild(
				document.createTextNode(
					"@-ms-viewport { width: auto !important; }"
				)
			);
		}

		document.getElementsByTagName("head")[0].
				appendChild(msViewportStyle);

	}

/* ==========================================================================
   exists - Check if an element exists
   ========================================================================== */

	function exists(e) {
		return $(e).length > 0;
	}

/* ==========================================================================
   isTouchDevice - return true if it is a touch device
   ========================================================================== */

	function isTouchDevice() {
		return !!('ontouchstart' in window) || ( !! ('onmsgesturechange' in window) && !! window.navigator.maxTouchPoints);
	}

/* ==========================================================================
   setDimensionsPieCharts
   ========================================================================== */

	function setDimensionsPieCharts() {

		$('.pie-chart').each(function() {

			var $t = $(this),
				n = $t.parent().width(),
				r = $t.attr("data-barSize");

			if (n < r) {
				r = n;
			}

			$t.css("height", r);
			$t.css("width", r);
			$t.css("line-height", r + "px");

			$t.find("i").css({
				"line-height": r + "px",
				"font-size": r / 3
			});

		});

	}

/* ==========================================================================
   animatePieCharts
   ========================================================================== */

	function animatePieCharts() {

		if(typeof $.fn.easyPieChart !== 'undefined'){

			$('.pie-chart:in-viewport').each(function() {

				var $t = $(this),
					n = $t.parent().width(),
					r = $t.attr("data-barSize"),
					l = "square";

				if ($t.attr("data-lineCap") !== undefined) {
					l = $t.attr("data-lineCap");
				}

				if (n < r) {
					r = n;
				}

				$t.easyPieChart({
					animate: 1300,
					lineCap: l,
					lineWidth: $t.attr("data-lineWidth"),
					size: r,
					barColor: $t.attr("data-barColor"),
					trackColor: $t.attr("data-trackColor"),
					scaleColor: "transparent",
					onStep: function(from, to, percent) {
						$(this.el).find('.pie-chart-percent span').text(Math.round(percent));
					}

				});

			});

		}

	}

/* ==========================================================================
   animateMilestones
   ========================================================================== */

	function animateMilestones() {

		$('.milestone:in-viewport').each(function() {

			var $t = $(this),
				n = $t.find(".milestone-value").attr("data-stop"),
				r = parseInt($t.find(".milestone-value").attr("data-speed"), 10);

			if (!$t.hasClass("already-animated")) {
				$t.addClass("already-animated");
				$({
					countNum: $t.find(".milestone-value").text()
				}).animate({
					countNum: n
				}, {
					duration: r,
					easing: "linear",
					step: function() {
						$t.find(".milestone-value").text(Math.floor(this.countNum));
					},
					complete: function() {
						$t.find(".milestone-value").text(this.countNum);
					}
				});
			}

		});

	}

/* ==========================================================================
   animateProgressBars
   ========================================================================== */

	function animateProgressBars() {

		$('.progress-bar .progress-bar-outer:in-viewport').each(function() {

			var $t = $(this);

			if ($t.attr("data-progress-bar-outer-color") !== undefined) {
				$t.css("background-color", $t.attr("data-progress-bar-outer-color"));
			}

			if (!$t.hasClass("already-animated")) {
				$t.addClass("already-animated");
				$t.animate({
					width: $t.attr("data-width") + "%"
				}, 2000);
			}

		});

	}

/* ==========================================================================
   handleMobileMenu
   ========================================================================== */

	var MOBILEBREAKPOINT = 991;

	function handleMobileMenu() {

		if ($(window).width() > MOBILEBREAKPOINT) {

			$("#mobile-menu").hide();
			$("#mobile-menu-trigger").removeClass("mobile-menu-opened").addClass("mobile-menu-closed");

		} else {

			if (!exists("#mobile-menu")) {

				$("#menu").clone().attr({
					id: "mobile-menu",
					"class": "fixed"
				}).insertAfter("#header");

				$("#mobile-menu > li > a, #mobile-menu > li > ul > li > a").each(function() {
					var $t = $(this);
					if ($t.next().hasClass('sub-menu') || $t.next().is('ul') || $t.next().is('.sf-mega')) {
						$t.append('<span class="fa fa-angle-down mobile-menu-submenu-arrow mobile-menu-submenu-closed"></span>');
					}
				});

				$(".mobile-menu-submenu-arrow").click(function(event) {
					var $t = $(this);
					if ($t.hasClass("mobile-menu-submenu-closed")) {
						$t.parent().siblings("ul").slideDown(300);
						$t.parent().siblings(".sf-mega").slideDown(300);
						$t.removeClass("mobile-menu-submenu-closed fa-angle-down").addClass("mobile-menu-submenu-opened fa-angle-up");
					} else {
						$t.parent().siblings("ul").slideUp(300);
						$t.parent().siblings(".sf-mega").slideUp(300);
						$t.removeClass("mobile-menu-submenu-opened fa-angle-up").addClass("mobile-menu-submenu-closed fa-angle-down");
					}
					event.preventDefault();
				});

				$("#mobile-menu li, #mobile-menu li a, #mobile-menu ul").attr("style", "");

			}

		}

	}

/* ==========================================================================
   showHideMobileMenu
   ========================================================================== */

	function showHideMobileMenu() {

		$("#mobile-menu-trigger").click(function(event) {

			var $t = $(this),
				$n = $("#mobile-menu");

			if ($t.hasClass("mobile-menu-opened")) {
				$t.removeClass("mobile-menu-opened").addClass("mobile-menu-closed");
				$n.slideUp(300);
			} else {
				$t.removeClass("mobile-menu-closed").addClass("mobile-menu-opened");
				$n.slideDown(300);
			}
			event.preventDefault();

		});

	}

/* ==========================================================================
   handleBackToTop
   ========================================================================== */

   function handleBackToTop() {

		$('#back-to-top').click(function(){
			$('html, body').animate({scrollTop:0}, 'slow');
			return false;
		});

   }

/* ==========================================================================
   showHidebackToTop
   ========================================================================== */

	function showHidebackToTop() {

		if ($(window).scrollTop() > $(window).height() / 2 ) {
			$("#back-to-top").removeClass('gone');
			$("#back-to-top").addClass('visible');
		} else {
			$("#back-to-top").removeClass('visible');
			$("#back-to-top").addClass('gone');
		}

	}

/* ==========================================================================
   handleSearch
   ========================================================================== */

	function handleSearch() {

		$("#custom-search-button").click(function(e) {

			e.preventDefault();

			if(!$("#custom-search-button").hasClass('open')) {

				$("#custom-search-form-container").fadeIn(200);

			} else {

				$("#custom-search-form-container").fadeOut(200);

			}

		});

		$('#custom-search-form').append('<a class="custom-search-form-close" href="#" title="Close Search Box">x</a>');

		$('#custom-search-form-container a.custom-search-form-close').click(function(event){

			event.preventDefault();
			$('#custom-search-form-container').fadeOut(200);

		});

	 }

/* ==========================================================================
   handleStickyHeader
   ========================================================================== */

	var stickyHeader = false;
	var stickypoint = 1;

	if ($('body').hasClass('ewf-sticky-header')){
		stickyHeader = true;
	}

	function handleStickyHeader() {

		var b = document.documentElement,
        	e = false;

		function f() {

			window.addEventListener("scroll", function (h) {

				if (!e) {
					e = true;
					setTimeout(d, 0);
				}
			}, false);

			window.addEventListener("load", function (h) {

				if (!e) {
					e = true;
					setTimeout(d, 0);
				}
			}, false);
		}

		function d() {

			var h = c();

			if (h >= stickypoint) {
				$('#header').addClass("stuck");
			} else {
				$('#header').removeClass("stuck");
			}

			e = false;
		}

		function c() {

			return window.pageYOffset || b.scrollTop;

		}

		f();

	}

/* ==========================================================================
   handleFullScreen
   ========================================================================== */

	function handleFullScreen() {

		var x = $(window).height();

		$('.fullscreen').css("height", x + "px");

	}

/* ==========================================================================
   When document is ready, do
   ========================================================================== */

	$(document).ready(function() {

		// twitterFetcher
		// http://jasonmayes.com/projects/twitterApi/

		if(typeof twitterFetcher !== 'undefined' && ($('.ewf_widget_latest_tweets').length > 0)) {

			$('.ewf_widget_latest_tweets').each(function(index){

				var account_id = $('.ewf-tweet-list', this).attr('data-account-id'),
					items = $('.ewf-tweet-list', this).attr('data-items'),
					newID = 'ewf-tweet-list-' + index;

				$('.ewf-tweet-list', this).attr('id', newID);

				var config = {
				  "id": account_id,
				  "domId": newID,
				  "maxTweets": items,
				  "showRetweet": false,
				  "showTime": false,
				  "showUser": false
				};

				twitterFetcher.fetch(config);
			});

		}

		// simplePlaceholder - polyfill for mimicking the HTML5 placeholder attribute using jQuery
		// https://github.com/marcgg/Simple-Placeholder/blob/master/README.md

		if(typeof $.fn.simplePlaceholder !== 'undefined'){

			$('input[placeholder], textarea[placeholder]').simplePlaceholder();

		}

		// Fitvids - fluid width video embeds
		// https://github.com/davatron5000/FitVids.js/blob/master/README.md

		if(typeof $.fn.fitVids !== 'undefined'){

			$('.fitvids,.responsive-embed').fitVids();

		}

		// Superfish - enhance pure CSS drop-down menus
		// http://users.tpg.com.au/j_birch/plugins/superfish/options/

		if(typeof $.fn.superfish !== 'undefined'){

			$('#menu').superfish({
				delay: 500,
				animation: {opacity:'show',height:'show'},
				speed: 100,
				cssArrows: true
			});

		}

		// bxSlider - responsive slider
		// http://bxslider.com/options

		if(typeof $.fn.bxSlider !== 'undefined'){

			$('.testimonial-slider .slides').bxSlider({
				 mode: 'fade',							// Type of transition between slides: 'horizontal', 'vertical', 'fade'
				 speed: 1000,							// Slide transition duration (in ms)
				 infiniteLoop: true,					// If true, clicking "Next" while on the last slide will transition to the first slide and vice-versa.
				 hideControlOnEnd: false,				// If true, "Next" control will be hidden on last slide and vice-versa. Only used when infiniteLoop: false
				 pager: true,							// If true, a pager will be added
				 pagerType: 'full',						// If 'full', a pager link will be generated for each slide. If 'short', a x / y pager will be used (ex. 1/5)
				 controls: true,						// If true, "Next" / "Prev" controls will be added
				 auto: true,							// If true, slides will automatically transition
				 pause: 7000,							// The amount of time (in ms) between each auto transition
				 autoHover: true,						// Auto show will pause when mouse hovers over slider
				 useCSS: false 							// If true, CSS transitions will be used for animations. False, jQuery animations. Setting to false fixes problem with jQuery 2.1.0 and mode:horizontal
			});

			$('.clients-slider .slides').bxSlider({
				 mode: 'horizontal',					// Type of transition between slides: 'horizontal', 'vertical', 'fade'
				 speed: 30000,							// Slide transition duration (in ms)
				 infiniteLoop: true,					// If true, clicking "Next" while on the last slide will transition to the first slide and vice-versa.
				 hideControlOnEnd: false,				// If true, "Next" control will be hidden on last slide and vice-versa. Only used when infiniteLoop: false
				 pager: true,							// If true, a pager will be added
				 pagerType: 'full',						// If 'full', a pager link will be generated for each slide. If 'short', a x / y pager will be used (ex. 1/5)
				 controls: true,						// If true, "Next" / "Prev" controls will be added
				 auto: true,							// If true, slides will automatically transition
				 pause: 4000,							// The amount of time (in ms) between each auto transition
				 autoHover: true,						// Auto show will pause when mouse hovers over slider
				 useCSS: false, 						// If true, CSS transitions will be used for animations. False, jQuery animations. Setting to false fixes problem with jQuery 2.1.0 and mode:horizontal
				 minSlides: 2,
				 maxSlides: 5,
				 slideWidth: 230,
				 slideMargin: 10,
				 ticker: true
			});

		}

		// Magnific PopUp - responsive lightbox
		// http://dimsemenov.com/plugins/magnific-popup/documentation.html

		if(typeof $.fn.magnificPopup !== 'undefined'){

			$('.magnificPopup').magnificPopup({
				disableOn: 400,
				closeOnContentClick: true,
				type: 'image'
			});

			$('.magnificPopup-gallery').magnificPopup({
				disableOn: 400,
				type: 'image',
				gallery: {
					enabled: true
				}
			});

		}

		// gMap -  embed Google Maps into your website; uses Google Maps v3
		// http://labs.mario.ec/jquery-gmap/

		if(typeof $.fn.gMap !== 'undefined'){

			//handleGoogleMapHeight();

			$('.google-map').each(function() {

				var $t = $(this),
					mapZoom = 15,
					mapAddress = $t.attr("data-address"),
					mapCaption = $t.attr("data-caption"),
					mapType = "ROADMAP",
					mapHeight = $t.attr("data-height"),
					popUp = false;

				if ($t.attr("data-zoom") !== undefined) {
					mapZoom = parseInt($t.attr("data-zoom"),10);
				}

				if ($t.attr("data-height") !== undefined) {
					$t.css( "height", mapHeight+'px');
				}

				if ($t.attr("data-maptype") !== undefined) {
					mapType = $t.attr("data-maptype");
				}

				if ($t.attr("data-popup") !== undefined) {
					popUp = $t.data("popup");
				}

				$t.gMap({
					maptype: mapType,
					scrollwheel: false,
					zoom: mapZoom,
					markers: [{
						address: mapAddress,
						html: mapCaption,
						popup: popUp
					}],
					controls: {
						panControl: true,
						zoomControl: true,
						mapTypeControl: true,
						scaleControl: false,
						streetViewControl: false,
						overviewMapControl: false
					}
				});

			});

		}

		// Isotope - portfolio filtering
		// http://isotope.metafizzy.co/beta/

		if ((typeof $.fn.isotope !== 'undefined') && (typeof $.fn.imagesLoaded !== 'undefined') && ($('.portfolio-isotope').length > 0)) {

			// initialize isotope after images are loaded

			$('.portfolio-isotope').imagesLoaded( function() {

				var container = $('.portfolio-isotope');

				container.isotope({
					itemSelector: '.item',
					layoutMode: 'masonry',
					transitionDuration: '0.5s'
				});

				$('.portfolio-filter li a').click(function () {
					$('.portfolio-filter li a').removeClass('active');
					$(this).addClass('active');

					var selector = $(this).attr('data-filter');
					container.isotope({
						filter: selector
					});

					return false;
				});

				$(window).resize(function () {

					container.isotope({ });

				});

			});

			//


		}

		//

		ieViewportFix();

		ewf_rowBehaviour();

		setDimensionsPieCharts();

		animatePieCharts();
		animateMilestones();
		animateProgressBars();

		handleMobileMenu();
		showHideMobileMenu();

		handleBackToTop();
		showHidebackToTop();

		handleSearch();

		if(stickyHeader && ($(window).width() > 1024)){
			handleStickyHeader();
		}

		handleFullScreen();

	});

/* ==========================================================================
   When the window is scrolled, do
   ========================================================================== */

	$(window).scroll(function() {

		animateMilestones();
		animatePieCharts();
		animateProgressBars();

		showHidebackToTop();

		if(stickyHeader && ($(window).width() > 1024)){
			handleStickyHeader();
		}


	});

/* ==========================================================================
   When the window is resized, do
   ========================================================================== */

	$(window).resize(function() {

		handleMobileMenu();

		if(stickyHeader && ($(window).width() > 1024)){
			handleStickyHeader();
		}

		handleFullScreen();

	});


})(window.jQuery);



(function($) {

    var $allVideos = $(".post-content iframe[src^='//player.vimeo.com'], .post-content  iframe[src^='//www.youtube.com'], .post-content  iframe, .post-content  object, .post-content  embed"),
    $fluidEl = $(".post-content");

	$allVideos.each(function() {

	  $(this)
	    // jQuery .data does not work on object/embed elements
	    .attr('data-aspectRatio', this.height / this.width)
	    .removeAttr('height')
	    .removeAttr('width');

	});

	$(window).resize(function() {

	  var newWidth = $fluidEl.width();
	 $allVideos.each(function() {

	    var $el = $(this);
	    $el
	        .width(newWidth)
	        .height(newWidth * $el.attr('data-aspectRatio'));

	  });

	}).resize();

})(window.jQuery);



jQuery(document).ready(function() {

		jQuery(".terms").fancybox({
			maxWidth	: 800,
			maxHeight	: 600,
			fitToView	: false,
			width		: '70%',
			height		: '70%',
			autoSize	: false,
			closeClick	: false,
			openEffect	: 'none',
			closeEffect	: 'none'
		});

});


var jumpLinks = function() {
      $('a[href*=\\#]:not([href=\\#]):not(.accordion dd>a)').click(function() {
    if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
      var target = $(this.hash);
      target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
      if (target.length) {
        $('html,body').animate({
          scrollTop: target.offset().top
        }, 1000);
        return false;
      }
    }
  });
}

// non jQuery scripts below
