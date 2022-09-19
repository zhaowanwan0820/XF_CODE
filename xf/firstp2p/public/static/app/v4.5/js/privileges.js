$(function(){
    function p2pBrowser(){
        var u = navigator.userAgent
        return {
            wx: /MicroMessenger/i.test(u),
            webkit: /AppleWebKit/i.test(u),
            gecko: /gecko/i.test(u),
            ios: /\(i[^;]+;( U;)? CPU.+Mac OS X/.test(u),
            android: /android/i.test(u),
            iPhone: /iPhone/i.test(u),
            iPad: /iPad/i.test(u),
            app: /wx/i.test(u),
            androidApp: /wxAndroid/i.test(u),
            iosApp: /wxiOS/i.test(u)
        }
    }
    if(p2pBrowser().ios){
        $(".ui_page_title").addClass('ui_ios_title');
    }
    $(".know_btn").click(function(){
        $(".JS_remind_no_cancle").hide();
    });
    $(".remind_cancle").click(function(){
        $(".JS_remind_can_cancle").hide();
    });
    //拼接开通授权url
    $(".JS_authorize_btn").click(function(){
        var _is_open_authorize_param = '{"srv":"authCreate" , "grant_list":"' + $(this).parent('.common_btn').data('grant') + '" ,"return_url":"storemanager://api?type=closecgpages"}';
        var _openauthorizeUrl = location.origin + "/payment/Transit?params=" + encodeURIComponent(_is_open_authorize_param);
        $(this).attr({ "href": 'storemanager://api?type=webview&gobackrefresh=true&url=' + encodeURIComponent(_openauthorizeUrl) });
    })
    
    //判断取消授权时弹哪个弹窗
    $(".cancle_authorize_btn").click(function(){
        if($(this).next(".auth_msg").html()){
            $(".JS_remind_no_cancle .remind_detail").html($(this).next(".auth_msg").html());
            $(".JS_remind_no_cancle").show();
        }else{
            $(".remind_confirm").data('accountid',$(this).data('accountid'));
            $(".remind_confirm").data('granttype',$(this).data('granttype'));
            if($(this).next(".auth_msg").next(".confirm_msg").text()){
                $(".JS_remind_can_cancle .remind_detail").html($(this).next(".auth_msg").next(".confirm_msg").html());
            }else{
                $(".JS_remind_can_cancle .remind_detail").html("是否确定取消授权");
            }
            $(".JS_remind_can_cancle").show();
        }
    });
    //点击确定取消授权
    $(".remind_confirm").click(function(){
        $.ajax({
            type: "post",
            dataType: "json",
            url: "/account/privilegesRemove?token=" + window['token'] + '&accountId=' + $(this).data('accountid') + '&privilege=' + $(this).data('granttype'),
            success: function(json){
                if(json.errno == 0){
                    //取消授权成功
                    $(".JS_remind_can_cancle").hide();
                    WXP2P.UI.showErrorTip('<span class="ui_privilege_suc"></span><p style="font-size:13px;margin-top:10px;">取消授权成功</p>');
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000)
                }else{
                    WXP2P.UI.showErrorTip(json.error);
                }
            },
            error:function() {
                WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
            }
        })
    })
})