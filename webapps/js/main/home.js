$(window).unload(function() {});

var url = location.href,
    params = url.split("?"),
    paramms,
    hash = location.hash,
    error_status;

if (typeof params[1] !== "undefined") {
    var paramms = params[1].split("&"),
        paramArray = [];

    for (i = 0; i < paramms.length; i++) {
        neet = paramms[i].split("=");
        paramArray.push(neet[0]);
        paramArray[neet[0]] = neet[1];
    }

    if (paramArray["ERROR"] == "EMAIL_ALREADY_EXIST_SENT_AGAIN") {
        error_status = "account_return"
    } else if (paramArray["ERROR"] == "EMAIL_ALREADY_EXIST") {
        error_status = "account_false"
        if (paramArray["POS"] == "temp") {
            error_status = "magazine_false"
        }
    } else if (paramArray["ERROR"] == "NOT_AN_EMAIL") {
        error_status = "magazine_false"
    } else if (paramArray["temp_email"]) {
        error_status = "magazine_success"
    }
}

function modalOpen(url, callback) {
    if (url == "#modal-buy") {
        $("body").append('<div class="home-modal"><div class="home-modal-bg" onclick="ga(\'send\', \'event\', \'kit\', \'click\', \'close_modal_overlay\');"></div><div class="home-modal-close js-home-modal-close" onclick="ga(\'send\', \'event\', \'kit\', \'click\', \'close_modal_x\');"><img src="/images/main/index/icon_cross.png" width="36" alt=""></div><div class="inner"><div class="home-modal-content"></div></div></div>')
    } else if (url == "#modal-entry") {
        $("body").append('<div class="home-modal"><div class="home-modal-bg" onclick="ga(\'send\', \'event\', \'account\', \'click\', \'close_modal_overlay\');"></div><div class="home-modal-close js-home-modal-close" onclick="ga(\'send\', \'event\', \'account\', \'click\', \'close_modal_x\');"><img src="/images/main/index/icon_cross.png" width="36" alt=""></div><div class="inner"><div class="home-modal-content"></div></div></div>')
    } else {
        $("body").append('<div class="home-modal"><div class="home-modal-bg"></div><div class="home-modal-close js-home-modal-close"><img src="/images/main/index/icon_cross.png" width="36" alt=""></div><div class="inner"><div class="home-modal-content"></div></div></div>')
    }
    if ($(url).hasClass("type-youtube")) {
        $(".home-modal-content").addClass("mode-iframe").append('<iframe width="853" height="480" src="' + $(url).html() + '" frameborder="0" allowfullscreen></iframe>')
    } else {
        $(".home-modal-content").append($(url).html())
    }
    var target = $(".home-modal");
    $(target).velocity("fadeIn", {
        duration: 300,
        easing: "ease-out",
        complete: function() {
            // $(".wrp").hide()
            $("html,body").css({ "overflow": "hidden", "min-height": "100%" })
            if (callback == "error") {
                $(this).find(".js-form-email .error.response").show()
            }
        }
    })
}

function modalClose(target, callback) {

    $(".wrp").show()
    $(target).velocity("fadeOut", {
        duration: 300,
        easing: "ease-out",
        complete: function() {
            $(this).remove()
            $("html,body").removeAttr("style")
        }
    })
}

$(function() {
    //直モーダル
    if (hash && hash == "#modal-buy") {
        var url = "#modal-buy";
        modalOpen(url);
    } else if (hash && hash == "#modal-entry") {
        var url = "#modal-entry";
        modalOpen(url);
    }

    //エラー
    if (error_status == "account_return" || error_status == "account_false") {
        var url = "#modal-entry";
        modalOpen(url, "error");
    } else if (error_status == "magazine_false" || error_status == "magazine_success") {

        var href = "#mailmagazine",
            target = $(href === "#" || href === "" ? 'html' : href);
        if (target.length) {
            target.velocity("scroll", {
                duration: 0,
                complete: function() {
                    $("#submit_temp_1.js-form-email").find(".error.response").show()
                }
            });
        }
    }

    var pagetop = $('.pagetop')
        //scroll
    $(window).scroll(function() {
        if ($(this).scrollTop() > 600) {
            pagetop.addClass("is-show");
            $(".js_scrollheader").addClass("is-show")
        } else {
            pagetop.removeClass("is-show")
            $(".js_scrollheader").removeClass("is-show")
        }

        if ($(this).scrollTop() > 1200) {
            $(".human").addClass("is-scroll")
        } else {
            pagetop.removeClass("is-show")
            $(".human").removeClass("is-scroll")
        }

    });

    //モーダル
    $(document).on("click", ".js-home-modal", function(e) {
        e.preventDefault();
        var url = $(this).attr("href")
        modalOpen(url);
    })

    //tab
    $(document).on("click", ".js-home-tab a", function(e) {
        var target = $(this).attr("href");
        var list = $(".js-home-tab a")
        var wrp = $(".js-home-tab")
        //
        list.removeClass("is--active")
        $(this).addClass("is--active")

        wrp.find(".js-home-content").removeClass("is--active")
        wrp.find(target).addClass("is--active")
        e.preventDefault();
    })

    //メールバリデート
    $("#submit_temp_1").on("submit", function(e) {
        e.preventDefault();
        var t = $(this).find("input[type=email]");
        $(this).find(".error").hide();
        $(this).find(".error").removeClass("is-show");
        if (!util.isMail(t.val())) {
            $(this).find(".error.validate").show();
            $(this).find(".error.validate").addClass("is-show");
            return false;
        }
        var _self = $(this);
        var _req = {"email" : t.val()};
    	getData("check_is_user_by_email", _req, function(data){
            var is_user = "";
            if(data.length > 0 && data[0]["is_user"]){
                is_user = data[0]["is_user"];
            }
            //メルマガ登録処理 restサービスにて実装
            service.postAjax("/save/ins/t_temp_users", {"email" : t.val()},
                function(data, st, xhr) {
                    if(data["status"]=="success" && data["data"]["result"].length>0){
                        service.sendMail("t_temp_users_ins");
                        switch(is_user){
                            case "user":
                            case "tempuser":
                                _self.find(".error.already_"+is_user).show();
                                _self.find(".error.already_"+is_user).addClass("is-show")
                                break;
                            default:
                                _self.find(".error.success").show();
                                _self.find(".error.success").addClass("is-show")
                        }
                    }
                    else {
                        _self.find(".error.fatal").show();
                        _self.find(".error.fatal").addClass("is-show")
                    }
                },
                function(xhr, st, err) {
                    _self.find(".error.fatal").show();
                    _self.find(".error.fatal").addClass("is-show")
                }
            );
    	});
        return false;
    });

    //モーダル削除
    $(document).on("click", ".js-home-modal-close ,[href=#home-modal-cancel], .home-modal-bg", function(e) {
        e.preventDefault();
        var target = $(".home-modal");
        modalClose(target)
    })

})
