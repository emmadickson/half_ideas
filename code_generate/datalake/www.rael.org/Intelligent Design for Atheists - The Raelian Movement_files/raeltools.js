/**
 * Outils developpe par Laurent Chaloupe pour le mouvement raelien
 */

var stateMenu = "block";
var versionMobile = false;
var mobileFirst = 1;

var idMenu = 'nav-wrapper';
var idCarousel = "fbox-scroller-slider";
var idCarouselBook = "gal-scroller-slider";

/**
 * resize the carousel by id
 * 
 * @param id
 * @param width new size
 */
function resize(id, width) {
	var fbox = $(id);
	
	if (fbox) {
		fbox.style.width = width + "px";
		var css = fbox.getElementsByClassName("slide");
		for (var i = 0; i < css.length; i++) {
			css[i].style.width = width + "px";
		}
	}
}

/**
 * Vérifie l'état du menu en mode mobile pour le restaurer si on change la taille d'écran ou si on bascule de l'état d'écran mobile ou PC
 * 
 * @param fullScreen
 * @returns void
 */
function checkStateMenu(fullScreen) {
    var content = $(idMenu);
    var stateDisplay = getComputedStyle(content, null).display;

    if (fullScreen) {
        if (versionMobile) {
            content.style.display = "block";
            stateMenu = stateDisplay;
            versionMobile = false;
        }
    } else {
		if (mobileFirst) {
            mobileFirst = 0;
            content.style.display = "none";
            stateMenu = 'none';
            versionMobile = true;
		} else if (!versionMobile) {
            content.style.display = stateMenu;

            versionMobile = true;
        }
    }
}

/**
 * Check the size of the screen and resize the carousel if the size screen change
 */
function checkSize() {
	if (document.body) {
		var width = (document.body.clientWidth);
	} else {
		var width = (window.innerWidth);
	}
	
	if (width >= 974) {
		checkStateMenu(true);
		
		resize(idCarousel, 900);
		resize(idCarouselBook, 582);
	} else {
		checkStateMenu(false);
		resize(idCarousel, (width - 2));
		resize(idCarouselBook, (width - 32));
	}
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');

    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') {
        	c = c.substring(1);
        }

        if (c.indexOf(name) == 0) {
        	return c.substring(name.length, c.length);
        }
    }

    return "";
}

function drawBoxMessage() {
    var box = $('drawBoxMessage');

    if(box) {
    	var IDCookie = box.getAttribute('data-id-cookie');

    	if (IDCookie && !getCookie(IDCookie)) {
			// console.log(e107Registry.Path.THEME+ 'images/ajax-loader.gif')
			var btClose = box.getElementsByClassName('closeBox')[0];
			var img = box.getElementsByTagName('img')[0];
			var url = img.getAttribute('data-url');

            box.setStyle({
            	display: 'block'
            });

			img.observe('click', function(e) {
				e.stop();
				setCookie(IDCookie, 1, 999);

                box.fxToggle({
                    effect: 'appear',
                    options: {
                        duration: 0.4,
                        afterFinish: function(o) { o.element.remove(); }
                    }
                });

				// window.location.href = url;
                window.open(url, '_blank');
			});

			btClose.observe('click', function(e) {
				e.stop();
				setCookie(IDCookie, 1, 999);

				box.fxToggle({
					effect: 'appear',
					options: {
						duration: 0.4,
						afterFinish: function(o) { o.element.remove(); }
					}
				});
			});
		}
    }
}

function closeAllBox(i) {
    if ((i & 8) == 8) {
        $('country-box').hide();
        $('country-link-wrapper').removeClassName('active')
    }

	if ((i & 2) == 2) {
        $('language-box').hide();
        $('language-link-wrapper').removeClassName('active')
	}

    if ((i & 1) == 1) {
        $('login-box').hide();
        $('login-link-wrapper').removeClassName('active')
    }
}

/**
 * Add event when the website load
 */
window.addEventListener("load", function(event) {
    if($('country-box')) {
        $('country-link').observe('click', function(event){
            closeAllBox(3)
        });
    }

    if($('language-box')) {
        $('language-link').observe('click', function(event){
            closeAllBox(9)
        });
    }

    if($('login-box')) {
        $('login-link').observe('click', function(event){
            closeAllBox(10)
        });
    }

    checkSize();
    drawBoxMessage();
});

/**
 * Add event when the website is resize
 */
window.addEventListener("resize", function(event) {
	checkSize();
});