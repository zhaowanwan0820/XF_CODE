// 默认模板
$(function(){
    var _wapHost = P2PWAP.util.getWapHost('return_url', 'http:\/\/(.*)\/account');
    var $pageTitle = $('.p_addbank_new .JS-title');
    var $pageMain = $('.JS-main');
    var $submitBtnBox = $('.JS-submit_btn_box');
    //by ww  此处修改所有返回为 href="javascript:void(0)" onclick="window.history.back();"
    //$pageTitle.prepend('<a class="ui_back JS_back" href="http://' + _wapHost + '"><i class="ui_icon_back"></i>返回</a>');
    $pageTitle.prepend('<a class="ui_back JS_back" href="javascript:void(0)" onclick="window.history.back();"><i class="ui_icon_back"></i>返回</a>');
    // if (P2PWAP.util.wxJudge()) {
    //     $pageTitle.hide();
    //     
    // } else {
    //     $pageTitle.prepend('<a class="ui_back JS_back" href="http://' + _wapHost + '"><i class="ui_icon_back"></i>返回</a>');
    // }
    // $pageMain.prepend('<div class="tc pt10 f15">资金同卡进出，安全无忧</div>');
    // $pageTitle.after('<div class="login_banner"><img src="/static/v3/images/upload/bank_banner.png" width="100%"></div>');
    // $submitBtnBox.after('<p class="tc pt10 f14 gray"><i class="icon_yes"></i>同意<a style="color:#ee4634;" href="https://m.ucfpay.com/mobilepay-p2p/zjtgProtocol.html">《先锋支付托管账户服务协议》</a></p>');
    if (!isWapApp) {
        $('.p_account_addbank_panel .JS-title').after('<div class="bank_tip">您可以通过<a href="http://www.wangxinlicai.com/app">下载网信客户端</a>或登录电脑选择其他银行。</div>');
    }    // iscroll初始化后改变银行列表dom，记得更新iscroll
    // if (window['scroll_bankid']) window['scroll_bankid'].refresh();
});