// 默认模板
$(function() {
    var _wapHost = P2PWAP.util.getWapHost('redirect_uri', 'http:\/\/(.*)\/account');
    var $pageTitle = $('.JS-title');
    var $pageMain = $('.JS-main');
    var $submitBtnBox = $('.JS-submit_btn_box');
    if (P2PWAP.util.wxJudge()) {
        $pageTitle.hide();
    } else {
        //by ww  此处修改所有返回为 href="javascript:void(0)" onclick="window.history.back();"
        //$pageTitle.prepend('<a class="ui_back JS_back" href="http://' + _wapHost + '"><i class="ui_icon_back"></i>返回</a>');
        $pageTitle.prepend('<a class="ui_back JS_back" href="javascript:void(0)" onclick="window.history.back();"><i class="ui_icon_back"></i>返回</a>');
    }
    var bannerSrc = "/static/v1/images/oauth/mobile/upload/reg_banner.png";
    if (window['_eventRegisterAddParams']['from_platform'] == 'channel_JLY') {
        bannerSrc = "/static/v1/images/oauth/mobile/upload/reg_jly_banner2.jpg";
    } else if (window['_eventRegisterAddParams']['from_platform'] == 'wapshake_HLWDH') {
        bannerSrc = "/static/v1/images/oauth/mobile/upload/re_static.jpg";
    } else if (window['_eventRegisterAddParams']['from_platform'] == 'channel_JLYWPX') {
        bannerSrc = "/static/v1/images/oauth/mobile/upload/jlywpx_banner.jpg";
    } else if (window['_eventRegisterAddParams']['from_platform'] == 'channel_JLYWPX') {
        bannerSrc = "/static/v1/images/oauth/mobile/upload/jlywpx_banner.jpg";
    } else if (window['_eventRegisterAddParams']['from_platform'] == 'wapshake_HLWDH') {
        bannerSrc = "/static/v1/images/oauth/mobile/upload/re_static.jpg";
    } else if (window['_isDiscountBanner']) {
        bannerSrc = "/static/v1/images/oauth/mobile/upload/reg_banner_discount.jpg";
    }
    $pageTitle.after('<div class="login_banner JS-banner"><img src="' + bannerSrc + '" width="100%"></div>');
    $('.JS-banner').after('<div class="reg_top tr pt10 f14">已有账号？<a href="http://' + _wapHost + '/account/login">立即登录</a></div>');
});
