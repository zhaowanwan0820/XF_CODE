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
                <h2>400-890-9888</h2>\
                <p>工作时间 7:00-23:00</p>\
            </div>\
        </div>\
        </div>').appendTo($("body"));
        $appDownload=$('<a class="backToTop" href="javascript:void(0)"></a>').appendTo($(".layAppTopnew")).hide();

        
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
