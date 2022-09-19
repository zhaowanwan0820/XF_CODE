
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
                    if($navli.length <= 1) return;
                    $(this).addClass("select").siblings().removeClass("select");
                    var index = $navli.index(this);
                    $(".invf_txt>div").eq(index).show()
                        .siblings().hide();
                });
            })();
            (function () {
                var body=$('body');
                var newIframe=null;
                if (body.data('umStatistic')){
                    body.on('click',function (event) {
                        var tarObj=$(event.target);
                        var uKey=tarObj.data('uKey');
                        if (uKey){
                            location.href="firstp2p://api?type=umeng&ukey="+uKey;
                        }
                    });
                }
            })();
        });

    })(Zepto);
