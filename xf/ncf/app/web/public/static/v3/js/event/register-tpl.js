// 默认模板
$(function() {
    var _wapHost = P2PWAP.util.getWapHost('redirect_uri', 'http:\/\/(.*)\/account');
    $(".JS-topbackbtn").attr("href", "http://" + _wapHost);
    $(".JS-loginbtn a").attr("href", "http://" + _wapHost + "/account/login");
    var platformTemplate = window['_PLATFORM_TEMPLATE_'];
    if (platformTemplate && platformTemplate['sign_up_footer']) {
        $(".p_login_new").append('<div class="login_banner" style="font-size:0px;"><img src="' + platformTemplate['sign_up_footer'] + '" width="100%"></div>');
    }
});
