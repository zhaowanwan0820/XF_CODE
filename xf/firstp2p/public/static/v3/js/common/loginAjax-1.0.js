(function($){
    $(function(){
        var is_wxlc = window.location.hostname.indexOf('wangxinlicai')>=0;
        var is_first = window.location.hostname.indexOf('firstp2p')>=0;
        if(is_wxlc){
            var host = "//www.wangxinlicai.com";
        }else if(is_first){
            var host = "//www.firstp2p.com";
        }
        var url = host + "/user/checkLogin",
        //我的账户
        myAccount = function() {
            var ele = $('.my_account'),
            ele_msg = $('.j_showMenu2');
            ele.hover(
                function(){
                    ele.addClass("select");
                },
                function(){
                    ele.removeClass("select");
                }
            );
            ele_msg.hover(
                function(){
                    ele_msg.addClass("select");
                },
                function(){
                    ele_msg.removeClass("select");
                }
            );
        };
        $.ajax({
            url : url ,
            dataType: "jsonp",
            //async: false,
            success: function(data){
                var arr = [];
                if(!!data.status){
                    arr.push('<ul class="fr nav">\
                    <li>您好，<a href="'+ host +'/account">'+ (!!data.realname ? data.realname : data.username) +'</a></li>\
                    <li><a href="'+ host +'/user/loginout">退出</a></li>\
                    <li class="j_showMenu2" ><a href="'+ host + '/message">消息</a>');
                    if(data.msg.msgCount > 0){
                        arr.push('<span class="message_num"><span class="m_lbg"></span><span class="m_rbg">'+ data.msg.msgCount +'</span></span>\
                            <div class="message_drop">\
                            <div class="drop_t"></div>\
                            <div class="drop_b">\
                            <ul class="clearfix">\
                        ');
                        if(!!data.msg.msgList && !!data.msg.msgList.length){
                            $.each(data.msg.msgList , function(i , v){
                                arr.push('<li><a href="'+ host + v.url +'">'+ v.total +'条 '+ (!!data.msg.msgTitle[v.is_notice] ? data.msg.msgTitle[v.is_notice] : "系统消息") +'</a></li>');
                            });
                        }
                        arr.push('</ul>\
                            </div>\
                        </div>');
                    }
                    arr.push('</li>\
                    <li><a href="'+ host +'/app" target="_blank" class="border_l pl20">手机客户端</a></li>\
                    </ul>');
                    $(".m_header .nav").html(arr.join(""));
                    arr = null;
                    USER_INFO = 1;
                }
                if (USER_INFO == 1) {
                    myAccount();
                }
            }
        });


    });
    var is_wxlc = window.location.hostname.indexOf('wangxinlicai')>=0;
    var is_first = window.location.hostname.indexOf('firstp2p')>=0;
    $('.m_header .logo').addClass('new_logo');
    $('.m_footer .logo').addClass("bottom_logo");
    if(is_wxlc){
            $('.new_logo').css({
            "background-image":"url(//www.wangxinlicai.com/static/v3/images/common/icon_bg.png?v=20170112)",
            "background-position":"0px -234px"
        });
        $('.bottom_logo').css({
            "background-image":"url(//www.wangxinlicai.com/static/v3/images/common/icon_bg.png?v=20170112)",
            "background-position":"0px -273px"
        });
    }
    if(is_first){
        $('.new_logo').css({
            "background-image":"url(//www.firstp2p.com/static/v3/images/common/icon_bg.png?v=20170112)",
            "background-position":"0px -234px"
        });
        $('.bottom_logo').css({
            "background-image":"url(//www.firstp2p.com/static/v3/images/common/icon_bg.png?v=20170112)",
            "background-position":"0px -273px"
        });
    }
})(jQuery);