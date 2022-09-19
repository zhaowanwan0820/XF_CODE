/** 依赖zepto **/
P2PWAP = {};
P2PWAP.app = {};
P2PWAP.common = {};
P2PWAP.util = {};
P2PWAP.Const = {
    AJAX_SIGN: window['_AJAXSIGN_']
};

P2PWAP.Const.ErrorCode = {
    UNLOGIN: 40002,
    NET_ERROR: 50001,
    SERVER_BUSY: 50002
};

P2PWAP.app.triggerScheme = function(scheme) {
    var iframe = document.createElement("iframe");
    iframe.src= scheme;
    iframe.style.display = "none";
    document.body.appendChild(iframe);
};

/************************ UI ****************************/
P2PWAP.ui = {};
P2PWAP.ui.showErrorInstance_ = null;
P2PWAP.ui.showErrorInstanceTimer_ = null;
// 错误提示
P2PWAP.ui.showErrorTip = function(msg) {
    if (P2PWAP.ui.showErrorInstance_) {
        clearTimeout(P2PWAP.ui.showErrorInstanceTimer_);
        P2PWAP.ui.showErrorInstance_.updateContent(msg);
    } else {
        P2PWAP.ui.showErrorInstance_ = new P2PWAP.ui.ErrorToaster_(msg);
        P2PWAP.ui.showErrorInstance_.show();
    }
    P2PWAP.ui.showErrorInstanceTimer_ = setTimeout(function() {
        P2PWAP.ui.showErrorInstance_.dispose();
        P2PWAP.ui.showErrorInstance_ = null;
        P2PWAP.ui.showErrorInstanceTimer_ = null;
    }, 2000);
};

P2PWAP.ui.ErrorToaster_ = function(msgHtml) {
    this.ele = null;
    this.msgHtml = msgHtml;
};

P2PWAP.ui.ErrorToaster_.prototype.createDom = function() {
    this.ele = document.createElement("div");
    this.ele.innerHTML = "<span style=\"display: inline-block;color:white;max-width:250px;min-width:100px;word-break:break-word;padding:10px;background:rgba(0,0,0,0.7);border-radius:5px;\">" + this.msgHtml + "</span>";
    this.ele.setAttribute("style", "z-index:999;position:fixed;width:100%;text-align:center;top:50%; margin-top:-30px;-webkit-transition:opacity linear 0.5s;opacity:0;");
    document.body.appendChild(this.ele);
};

P2PWAP.ui.ErrorToaster_.prototype.updateContent = function(msgHtml) {
    this.msgHtml = msgHtml;
    if (!this.ele) return;
    $(this.ele).find("span").html(this.msgHtml);
};

P2PWAP.ui.ErrorToaster_.prototype.show = function() {
    if (!this.ele) {
        this.createDom();
    }
    var pThis = this;
    setTimeout(function() {
        if (!pThis.ele) return;
        pThis.ele.style.opacity = "1";
    }, 1);
};

P2PWAP.ui.ErrorToaster_.prototype.hide = function() {
    if (!this.ele) return;
    this.ele.style.opacity = "0";
    var ele = this.ele;
    delete this.ele;
    setTimeout(function() {
        document.body.removeChild(ele);
    }, 500);
};

P2PWAP.ui.ErrorToaster_.prototype.dispose = function() {
    this.hide();
};

/*********************** UtIl ***************************/

P2PWAP.util.request = function(requrl, success_callback, errorCallback, opt_method, opt_data) {
    var senddata = (typeof opt_data == 'object') ? opt_data : {};
    senddata['asgn'] = P2PWAP.Const.AJAX_SIGN;
    return $.ajax({
        url: requrl,
        type: opt_method == "post" ? "post" : "get",
        dataType: 'json',
        data: senddata,
        success: function(json) {
            if (!json) {
                errorCallback.call(null, '服务器忙,请稍后重试', P2PWAP.Const.ErrorCode.SERVER_BUSY);
                return;
            }
            if (json['errno'] != 0) {
                errorCallback.call(null, json['error'], json['errno']);
                return;
            }
            success_callback.call(null, json['data']);
        },
        error: function() {
            errorCallback.call(null, '您的网络貌似不给力,请稍后重试', P2PWAP.Const.ErrorCode.NET_ERROR);
        }
    });
};

P2PWAP.util.wxJudge = function(){
    var userAgentString = window.navigator ? window.navigator.userAgent : "";
    var weixinreg = /MicroMessenger/i;
    return weixinreg.test(userAgentString);
};

P2PWAP.util.checkMobile = function(val) {
    return /^0?(1[3-9])[0-9]{9}$/.test(val);
};

P2PWAP.util.checkPassword = function(val) {
    return /^[^\s]{5,25}$/.test(val);
};

P2PWAP.util.checkCaptcha = function(val) {
    return /^\d{4,10}$/.test(val);
};

P2PWAP.util.checkMcode = function(val) {
    return /^\d{6}$/.test(val);
};
