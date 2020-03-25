$(window).on('load', function () {

    $(".js-bg").each(function () {
        $(this).css('background-image', 'url(' + $(this).data("preload") + ')');
    });
    $(".js-img").each(function () {
        $(this).attr('src', $(this).data("src"));
    });

    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent)) {
        $('body').addClass('ios');

    } else {
        $('body').addClass('web');
        $(window).bind('resize', handler);
    };

    setTimeout(function () {
       $('body').removeClass('loaded');
    }, 200);

});

/* viewport width */
function viewport() {
    var e = window,
        a = 'inner';
    if (!('innerWidth' in window)) {
        a = 'client';
        e = document.documentElement || document.body;
    }
    return {
        width: e[a + 'Width'],
        height: e[a + 'Height']
    }
};
/* viewport width */
document.addEventListener("touchstart", function () {}, true);

$(function () {
    /* placeholder*/
    $('input, textarea').each(function () {
        var placeholder = $(this).attr('placeholder');
        $(this).focus(function () {
            $(this).attr('placeholder', '');
        });
        $(this).focusout(function () {
            $(this).attr('placeholder', placeholder);
        });
    });
    /* placeholder*/

    function showDiv() {
        if ($(window).scrollTop() > 0 && $('.header').data('positioned') == 'false') {
            $(".header").data('positioned', 'true');
            $(".header").addClass('fix');
        } else if ($(window).scrollTop() <= 0 && $('.header').data('positioned') == 'true') {
            $(".header").removeClass('fix').data('positioned', 'false');
        }
    }
    $(window).scroll(showDiv);
    //$(window).load(showDiv);
    $('.header').data('positioned', 'false');

});

//

document.addEventListener('DOMContentLoaded', function () {

});

var handler = function () {

    var height_footer = $('.footer').height();
    var height_header = $('.header').height();

    var viewport_wid = viewport().width;
    var viewport_height = viewport().height;

    //$('.js-vh').css({'height': viewport_height});
    $('.js-vh').css({'height': $(window).height()});


    if (viewport_wid <= 1023) {
        
    } 
    else if (viewport_wid > 1024) {
        
    }

}
$(window).bind('load', handler);

$(window).on('resize', function () {
    setTimeout(function () {
        handler();
    }, 10);
});

$(window).on('orientationchange', function () {
    setTimeout(function () {
        handler();
    }, 10);
});

/* */
