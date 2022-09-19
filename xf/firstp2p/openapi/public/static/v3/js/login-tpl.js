// 默认模板
$(function(){
    var _wapHost = P2PWAP.util.getWapHost('redirect_uri', 'http%3A%2F%2F(.*)%2Foauth');
    var $pageTitle = $('.JS-title');
    var $pageMain = $('.JS-main');
    var $submitBtnBox = $('.JS-submit_btn_box');
    //by ww  此处修改所有返回为 href="javascript:void(0)" onclick="window.history.back();"
    //$pageTitle.prepend('<a class="ui_back JS_back" href="http://' + _wapHost + '"><i class="ui_icon_back"></i>返回</a>');
    $pageTitle.prepend('<a class="ui_back JS_back" href="javascript:void(0)" onclick="window.history.back();"><i class="ui_icon_back"></i>返回</a>');
    // if (P2PWAP.util.wxJudge()) {
    //     $pageTitle.hide();
    // } else {
    //     $pageTitle.prepend('<a class="ui_back JS_back" href="http://' + _wapHost + '"><i class="ui_icon_back"></i>返回</a>');
    // }

    var loginbanner = '';
    if ("1" != window['_IS_WAP_FENZHAN_']) {
        loginbanner = window["_LOGIN_BANNER_"] ? window["_LOGIN_BANNER_"] : "/static/v3/images/upload/login_banner_1.png"; 
        $pageTitle.after('<div class="login_banner"><img src="' + loginbanner + '" width="100%"></div>');
    }

    $pageMain.prepend('<div class="tr pt10 f14">没有账号？<a href="http://' + _wapHost + '/account/register">立即注册</a></div>');
    $submitBtnBox.append('<p class="tc pt10 f14"><a href="http://' + _wapHost + '/account/pwd_find">忘记密码</a></p>');
    var platformTemplate = window['_PLATFORM_TEMPLATE_'];
    if (platformTemplate && platformTemplate['sign_in_footer']) {
        $(".p_login_new").append('<div class="login_banner" style="font-size:0px;"><img src="' + platformTemplate['sign_in_footer'] + '" width="100%"></div>');
    }
});
