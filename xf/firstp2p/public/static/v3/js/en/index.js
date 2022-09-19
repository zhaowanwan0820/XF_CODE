$(function() {
    function IsPC() {
      var flag = true;
      var winW = document.documentElement.clientWidth
      if(winW < 1200) flag = false;
      return flag;
    }
    var flag = IsPC(); 
    if(flag){//pc
        //   第一屏自适应屏幕大小
        var windowHeight=$(window).height()
        var headerHeight = $(".top_head .header").height()
        var slideHeight = windowHeight - headerHeight
        $(".slide1").height(slideHeight)
        // window.onbeforeunload = function () {
        //     $(window).scrollTop(0)
        // }
        window.onresize = function () {
            $(".slide1").height(slideHeight)
        };

        var e = 0,
        n = !1,
        t = 0,
        o = function (e) {//滚动的方向，e为1时向下滚动一屏，-1时向上滚动一屏
            var o = e ? e.wheelDelta || -e.deltaY || -e.detail : -3,//-3
            a = Math.max(-1, Math.min(1, o)),//-1
            r = $(window).scrollTop();
            if (n) return void(e && e.preventDefault());
            if (t - 80 > r) {
            if (e && e.preventDefault(), 1 !== a) {
                n = !0, scrollDirection(1, function () {
                n = !1
                });
                return $(".slide_title_pc").addClass("an-end"),$(".float_ul").addClass("an-end"), void $(".intro").addClass("an-end")
            }
            return 1 === a ? (n = !0, scrollDirection(-1, function () {
                n = !1
            }), $(".slide_title_pc").removeClass("an-end"),$(".float_ul").removeClass("an-end"), void $(".intro").removeClass("an-end")) : !1
            }
        },
        a = function () {
            document.addEventListener ? (document.addEventListener("mousewheel", o, !1), document.addEventListener("wheel", o, !1), document.addEventListener("DOMMouseScroll", o, !1)) : document.attachEvent("onmousewheel", o)
        },
        scrollDirection = function (e, n) {//滚动的方向，e为1时向下滚动一屏，-1时向上滚动一屏
            if (1 === e) {
                var a = t;
                $("html, body").stop().animate({
                    scrollTop: a
                }, 1e3, function () {
                    $(".about_line").addClass("an-end"),$(".about").addClass("an-end"), $(".slide_title_pc").addClass("an-end"), n && n()
                })
            } else -1 === e && $("html, body").stop().animate({
                scrollTop: 0
            }, 1e3, function () {
                $(".about_line").removeClass("an-end"),$(".about").removeClass("an-end"), $(".slide_title_pc").removeClass("an-end"), n && n()
            });
            o()
        },
        r = function () {//每次滚动时，添加或移除an-end
            if (!n) {
                var o = $(window).scrollTop(),
                    a = (e - o) / Math.abs(o - e),
                    i = $(window).scrollTop();
                e = o, t - 80 > i ? $(".header-section .header").removeClass("colorful") : $(".header-section .header").addClass("colorful"), $(".common-an-start").each(function () {
                    var e = $(this);
                    e.offset().top - i < t + 140 && -1 === a ? e.addClass("an-end") : e.offset().top - i > t + 40 && 1 === a && e.removeClass("an-end")
                });
                // var r = $(".footer").offset().top;
                // t >= r - i && -1 === a ? $(".home-footer").addClass("an-end") : r - i > t - 100 && 1 === a && $(".home-footer").removeClass("an-end")
            }
        },
        s = !1

        $(document).ready(function () {
        t = $(".slide1").height(), a(), $(window).on("scroll", r), $(".header-arrow").click(function () {
            i(1)
        });
        })

    }
    
})