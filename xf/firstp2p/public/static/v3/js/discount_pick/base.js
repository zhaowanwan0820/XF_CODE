/**
 * 
 */
var P2PWAP = {};

P2PWAP.Const = {
    COOKIE_HIDE_APPDOWNLOAD: 'mp2p_hide_appdownload',
    AJAX_SIGN: window['_AJAXSIGN_']
};
P2PWAP.Common = {};

P2PWAP.util = {};

P2PWAP.util.setCookie = function(name, value, expiredays) {
    var exdate = new Date();
    exdate.setDate(exdate.getDate()+expiredays)
    document.cookie=name+ "=" +escape(value)+ ";path=/" +
    ((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
};

P2PWAP.util.getCookie = function(name) {
    var cookiestr = document.cookie;
    if (cookiestr == null || cookiestr == "") return null;
    var cookieArrs = cookiestr.split(";");
    for (var i = cookieArrs.length - 1; i >= 0; i--) {
        var cookiekvstr = $.trim(cookieArrs[i]);
        var kv = cookiekvstr.split("=");
        var key = kv[0];
        var value = decodeURIComponent(kv[1]);
        if (key == name) {
            return value;
        }
    }
    return null;
};

P2PWAP.util.ajax = function(url, method, suc_back, error_back, opt_postdata, anys) {
    return $.ajax({
        url: url,
        type: method,
        async: (!anys) ? true : false,
        data: opt_postdata ? opt_postdata : null,
        dataType: "json",
        success: function(json) {
            suc_back.call(null, json);
        },
        error: function() {
            error_back.call(null, "网络异常，稍后重试");
        }
    });
};


P2PWAP.util.checkMobile = function(val) {
    return /^0?(13[0-9]|15[0-9]|17[0-9]|18[0-9]|14[57])[0-9]{8}$/.test(val);
};

P2PWAP.ui = {};
P2PWAP.ui.showErrorInstance_ = null;
P2PWAP.ui.showErrorInstanceTimer_ = null;
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
P2PWAP.ui.toast = P2PWAP.ui.showErrorTip;

P2PWAP.ui.ErrorToaster_ = function(msgHtml) {
    this.ele = null;
    this.msgHtml = msgHtml;
};

P2PWAP.ui.ErrorToaster_.prototype.createDom = function() {
    this.ele = document.createElement("div");
    this.ele.innerHTML = "<span style=\"display: inline-block;color:white;max-width:250px;min-width:100px;word-break:break-word;padding:10px;background:rgba(0,0,0,0.7);border-radius:5px;\">" + this.msgHtml + "</span>";
    this.ele.setAttribute("style", "z-index:1002;position:fixed;width:100%;text-align:center;top:50%;-webkit-transition:opacity linear 0.5s;opacity:0;");
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