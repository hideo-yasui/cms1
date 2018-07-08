function modalOpen(url, callback) {
    var target = $(".modal");
    $(".modal__content").append("<img src='" + url + "' alt=''>");
    $("body,html").css({
        "overflow-y": "hidden",
        "min-height": "100%"
    })
    $(target).velocity("fadeIn", {
        duration: 300,
        easing: "ease-out",
        complete: function() {}
    })
}

function modalClose(target, callback) {

    $(target).velocity("fadeOut", {
        duration: 300,
        easing: "ease-out",
        complete: function() {
            $(".modal__content").html("")
            $("body,html").css({
                "overflow-y": "visible",
                "min-height": "initial",
                "min-height": "auto"
            })
        }
    })
}
$(function() {
    if ($('.js_inview').length) {
        var $inview = $('.js_inview');
        $inview.on('inview', function(event, isInView, visiblePartX, visiblePartY) {
            $(this).addClass('on');
        });
    }


    var h = $(".js_scroll");
    $(window).on('load scroll',function() {
        if ($(this).scrollTop() > 100) {
            h.addClass("is--scroll");
        } else {
            h.removeClass("is--scroll");
        }
    });

    $(".js_scroll").on("click", function() {
        var href = $(this).attr("href"),
            target = $(href === "#" || href === "" ? 'html' : href);
        if (target.length) {
            target.velocity("scroll", {
                duration: 1000,
                easing: "easeInOutQuint"
            });
        }
        return false;
    });

    var sitem = $(".js_selection_item").children();
    var si = $(".js_selection > li.js_selection_li");
    si.on("click", function() {
        var index = si.index(this);
        si.removeClass("is--active")
        $(this).addClass("is--active")
        sitem.removeClass("is--active").eq(index).addClass("is--active")
    });

    $(document).on("click", ".js_modal", function(e) {
        e.preventDefault();
        var url = $(this).attr("href")
        modalOpen(url);
    })

    $(document).on("click", "[href='#close'],.js_modal_close , .modal__bg", function(e) {
        e.preventDefault();
        var target = $(".modal");
        modalClose(target)
    })

})
