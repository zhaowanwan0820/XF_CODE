
    (function($) {
        $(function() {
            (function () {
                var tabletd = $(".invf_txt").width() * 0.75;
                $(".invf_txt img").each(function () {
                    var bili = $(this).width() / tabletd;
                    var imgheight = $(this).height() / bili;
                    $(this).css("width", tabletd).css("height", imgheight);
                });
                var $navli = $(".menu li");
                $navli.bind("click", function() {
                    $(this).addClass("select").siblings().removeClass("select");
                    var index = $navli.index(this);
                    $(".invf_txt>div").eq(index).show()
                        .siblings().hide();
                });
            })();
        });

    })(Zepto);
