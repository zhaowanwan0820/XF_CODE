;(function($) {
    var config = null;
    $(function() {
            //启动我的账户逻辑
            try {
                if (!!USER_INFO && USER_INFO == 1) {
                    myAccount();
                }
            } catch (e) {

            }
        })
        //我的账户
    function myAccount() {
        var ele = $('.my_account');
        var ele_msg = $('.j_showMenu2');
        ele.hover(
            function() {
                ele.addClass("select");
            },
            function() {
                ele.removeClass("select");
            }
        );
        ele_msg.hover(
            function() {
                ele_msg.addClass("select");
            },
            function() {
                ele_msg.removeClass("select");
            }
        )

    }
})(jQuery, "firstp2p common.js");

var Firstp2p = {
    mobileReg: {
        'cn': /^1[3456789]\d{9}$/,
        'hk': /^[968]\d{7}$/,
        'mo': /^[68]\d{7}$/,
        'tw': /^09\d{8}$/,
        'us': /^\d{10}$/,
        'ca': /^\d{10}$/,
        'uk': /^7\d{9}$/
    },
    isIE7 : function(){
       return navigator.appVersion.search(/MSIE 7/i) != -1;
    },
    // 3秒后自动跳转公用js
    goPay: function(options){
        var defaultSettings = {
            number: 3,
            $obj: $("#second"),
            callback : function(){
                $("#bindCardForm").submit();
            }
        },
        settings = $.extend(true, defaultSettings, options),
        num = settings.number;
        $(settings.$obj).html(num);
        var goes = setInterval(function(){
                num--;
                $(settings.$obj).html(num);
                if(num === 0 ){
                    clearInterval(goes);
                    settings.callback();
                }
        },1000);
    }
};

//登录后强制修改密码提示
(function($) {
    $(function() {
        if (typeof forceChangePwd !== 'undefined' && !!forceChangePwd) {
            //弹出层
            var promptStr = '';
            promptStr = '<div class="pop-tit"><i></i>为了您的账户安全，首次登陆系统时请修改您的登陆密码！</div>' +
                '<div class="tc">点击<a href="javascript:void(0)" class="blue" id="edit-btn">修改登陆密码</a></div>';
            Firstp2p.alert({
                text: '<div class="f16">' + promptStr + '</div>',
                ok: function(dialog) {
                    dialog.close();
                },
                width: 560,
                showButton: false,
                boxclass: "commpany-popbox"
            });
            $("body").on("click", ".dialog-close , #edit-btn" ,function() {
                $.weeboxs.close();
                location.href = '/user/editpwd';
            });
        }
    });
})(jQuery);

