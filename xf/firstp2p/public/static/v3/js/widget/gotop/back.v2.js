//初始化
;(function($) {
    $(function() {
        if( !!window.ActiveXObject && !window.XMLHttpRequest){
              return;
        }
        var bool=true;
        $('<div class="layAppTopnew">\
        <div class="app">\
            <a class="app_btn" href="javascript:void(0)"></a>\
            <div class="app_box">\
                <i class="icon_a"></i>\
                <div class="app_tab_con"></div>\
                <h2>下载网信APP</h2>\
            </div>\
        </div>\
        <div class="serve_box">\
            <a class="serve" href="javascript:void(0)"></a>\
            <div class="serve_con">\
                <i class="icon_a"></i>\
                <h2>95782</h2>\
                <p>工作时间 8:00-20:00</p>\
            </div>\
        </div>\
        </div>').appendTo($("body"));
        $appDownload=$('<a class="backToTop" href="javascript:void(0)"></a>').appendTo($(".layAppTopnew")).hide();
            //判断是不是要显示新手专区
        if ($('#isNewUser_hidden').val()==1){
            if ($('.JS_is_wxlc').val()==1){
                $('<a href="/activity/NewUserPage" class="isNewUser_11626 abs newbie"></a>').appendTo($(".layAppTopnew"));
            }else if ($('#JS_is_firstp2p').val()==1){
                $('<a href="/activity/NewUserP2p" class="isNewUser_11626 p2p"></a>').appendTo('.layAppTopnew');
            } else {
                $('<a href="/activity/NewUserPage" class="isNewUser_11626 fixed"></a>').appendTo('body');
            }
        }
        
            $('.app_btn').hover(function(){
                // $('.app_box').stop().show();
                //$(".app_box").stop().animate({left:"-137px","opacity":"1"});              
                var $img = $("<img />");
                if(bool){
                    bool=false;
                    $img.attr("src","../static/v2/images/common/download.png");
                    $(".app_tab_con").append($img);
                    }               
            },function(){
            })
            
        $backToTopEle = $('.backToTop').click(function() {
            $("html,body").animate({
                    scrollTop: 0
                },
                300);
        });
        $(window).bind("scroll",
            function() {  
                var st = $(document).scrollTop();
                var winh = $(window).height();
                var ban= $(".banner_slide").length;
                if(st > 300){
                        $('.layAppTopnew').css({"height":"212px"});
                        $appDownload.show();
                    }else{
                        $appDownload.hide();
                        $('.layAppTopnew').css({"height":"141px"});
                    }
        });
    })  
})(jQuery);
