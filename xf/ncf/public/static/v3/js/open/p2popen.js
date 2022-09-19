p2popen = {};
p2popen.util = {};
p2popen.util.getNextUniqueIdCount_ = 0;
p2popen.util.getNextUniqueId = function() {
    p2popen.util.getNextUniqueIdCount_++;
    return "_p2popen_idp_" + p2popen.util.getNextUniqueIdCount_;
};
p2popen.advtpl = {};
p2popen.advtpl.tpls = {
    banner: '<style>\
            .ui_banner_slide { height: 100%; width:100%; position: relative;  overflow: hidden; }\
            .ui_banner_slide ul.ui_banner_view { position: relative; height: 100%; }\
            .ui_banner_slide ul.ui_banner_view li { width: 100%; height: 100%; background-repeat: no-repeat; background-position: center top; position: absolute; left: 0; top: 0; z-index: 0; display: none; }\
            .ui_banner_slide ul.ui_banner_view li a { display: block; height: 100%; width: 100%; }\
            .ui_slide_pager { position: absolute; bottom: 15px; left: 50%; z-index: 10; }\
            .ui_slide_pager_l { background: url(/static/v2/images/common/banner_bg.png) no-repeat; width: 12px; float: left; height: 21px; }\
            .ui_slide_pager_r { background: url(/static/v2/images/common/banner_bg.png) no-repeat right top; padding-right: 12px; float: left; height: 21px; }\
            .ui_slide_pager_r li { width: 22px; height: 4px; background: #fff; filter: Alpha(Opacity=36); opacity: 0.36; float: left; cursor: pointer; margin: 8px 3px; border-radius: 2px; font-size: 0px; line-height: 0px; }\
            .ui_slide_pager_r li.active { background: #fff; filter: Alpha(Opacity=100); opacity: 1; }\
            </style>\
            <div class="ui_banner_slide">\
                <ul class="ui_banner_view"></ul>\
                <div class="ui_slide_pager"><div class="ui_slide_pager_l"></div><div class="ui_slide_pager_r"><ul></ul></div>\
            </div>'
};
p2popen.advtpl.warpBanner = function(data, opt_container) {
    if (!opt_container) {
        var conid = p2popen.util.getNextUniqueId();
        document.write("<div id='" + conid + "' style='display:inline-block;'></div>");
        opt_container = document.getElementById(conid);
    }
    var container = opt_container;
    $(container).append(p2popen.advtpl.tpls['banner']);
    var imgLen = data.length;
    if (!(imgLen > 0)) {
        $(container).find('.ui_slide_pager').hide();
        return;
    }
    function _warpBanner_() {
          var curTab = 0;
            var autoFlag;
            // 最多16个
            if(imgLen > 16) imgLen = 16;
            // 添加元素
            for (var i = 0; i < imgLen; i++) {
                $(container).find('.ui_banner_view').append('<li style="background-image:url(' + data[i]['imageurl'] + ')"><a target="_blank" href="' + data[i]['link'] + '"></a></li>');
                if (i == 0) {
                    $(container).find('.ui_banner_view li').eq(0).show();
                    $(container).find('.ui_slide_pager ul').append('<li class="active"></li>')
                } else {
                    $(container).find('.ui_slide_pager ul').append('<li></li>')
                }
            }
            // 计算导航位置与图片容器宽度
            $(container).find('.ui_slide_pager').css('margin-left', $(container).find('.ui_slide_pager').width() / 2 * (-1) + 'px');
            // 移动函数
            function switchFoucs(idx) {
                $(container).find('.ui_slide_pager ul li').removeClass('active').eq(idx).addClass('active');
                $(container).find('.ui_banner_view li').stop().fadeOut(500).css('z-index','0').eq(idx).stop().fadeIn(500).css('z-index','1');
            }
            // 自动函数
            function autoAni() {
                curTab++;
                if (curTab >= imgLen) curTab = 0;
                switchFoucs(curTab);
            }
            function setAutoAni() {
                autoFlag = setInterval(function() {
                    autoAni(curTab);
                }, 5000);
            }
            // 默认初始滚动
            setAutoAni();
            // 绑定事件
            $(container).find('.ui_slide_pager ul li').mouseover(function(event) {
                curTab = $(this).index();
                switchFoucs(curTab);
            });
            $(container).find('.ui_banner_slide').mouseover(function() {
                clearInterval(autoFlag);
            });
            $(container).find('.ui_banner_slide').mouseout(function() {
                setAutoAni();
            });
    }
    //根据第一张图片设置container的宽度来适配
    //var img = document.createElement("img");
    //img.onload = function() {
    //    var width = img.width;
    //    var height = img.height;
    //    $(container).css({height: img.height, width: img.width});
    //    $(function(){_warpBanner_()});
    //}
    //img.src = data[0]['imageurl'];

    $(container).css({height: data[0]['height'], width: data[0]['width']});
    $(function(){_warpBanner_()});
};

//登录后判断用户密码是否是弱密码
$(function(){
    //lz_isWeakPwd为全局检测用户密码是否为弱密码 弱密码情况值为1
    if (typeof lz_isWeakPwd !== 'undefined' && !!lz_isWeakPwd) {
        if( window.location.pathname!='/user/editpwd'){
            var WeakPwdStr = '<div class="wee-send">\
                     <div class="send-input">\
                         <div class="error-box">\
                            <p></p>\
                            <p>您的密码存在安全隐患 , 建议您立即修改密码</p>\
                        </div>\
                      </div>';

            var WeakPwdBox = function() {
                    $.weeboxs.open(WeakPwdStr, {
                        boxid: "lz_weakPwdBox",
                        boxclass: 'lz_weakPwdBox',
                        contentType: 'text',
                        showButton: true,
                        showOk: true,
                        okBtnName: '去设置',
                        showCancel: false,
                        title: '提示',
                        width: 463,
                        height: 60,
                        type: 'wee',
                        draggable:true,
                        clickClose:false,
                        onclose: function() {
                            location.href = "/user/editpwd";
                        },
                        onok: function() {
                            $.weeboxs.close();
                            location.href = "/user/editpwd";
                        }
                    });
                    $(".weedialog").css({
                        top: '214px'
                    });
                    $(".dialog-close").hide()

                };
            //用户未操作的情况下 10s后自动跳转到修改密码页面
            setTimeout(function () {
                location.href = "/user/editpwd";
            }, 10000);
            WeakPwdBox();
        }
    }
});