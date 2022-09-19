/** 依赖zepto **/
P2PWAP = {};
P2PWAP.common = {};
P2PWAP.util = {};
P2PWAP.Const = {
    AJAX_SIGN: window['_AJAXSIGN_']
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
    this.ele.setAttribute("style", "z-index:999;position:fixed;width:100%;text-align:center;bottom:50px;-webkit-transition:opacity linear 0.5s;opacity:0;");
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

P2PWAP.ui.showNoticeDialog = function(title,content) {
    var div = document.createElement('div');
    var html = '';
    html += '<div class="ui_max_width">';
    html += '    <div class="ui_dialog hd_dialog">';
    html += '        <div class="dialog_con">';
    html += '            <div class="title">' + title + '</div>';
    html += '            <div class="dialog_text">';
    html += '                <div class="iscroll_inner">';
    html +=                      content;
    html += '                </div>';
    html += '            </div>';
    html += '            <div class="dialog_btn">我知道了</div>';
    html += '        </div>';
    html += '    </div>';
    html += '</div>';
    div.innerHTML = html;
    div.className = 'ui_mask';
    document.body.appendChild(div);

    function dialogTopFix(){
        var $dialog = $(div).find('.ui_dialog');
        $dialog.css('margin-top',$dialog.height() * (-1/2) + 'px');
    }
    dialogTopFix();
    $(window).resize(function(){
        dialogTopFix();
    });
    // 关闭
    $(div).find('.dialog_btn').bind("click" , function(event) {
        P2PWAP.ui.removeModalView(div);
        $(div).remove();
    });

    P2PWAP.ui.addModalView(div);
    $(".ui_mask").height($(window).height());
    var myScroll_id = new IScroll($(div).find('.dialog_text')[0],{
        scrollbars: true,
        interactiveScrollbars: true,
        shrinkScrollbars: 'scale',
        fadeScrollbars: true
    });
};

P2PWAP.ui.addModalView = function(instance) {
    if (!window.navigator || !/iphone.*OS 7.*/i.test(window.navigator.userAgent)) return;
    window.scrollTo(0, 0);
    if (P2PWAP.ui.ModalViewEls_.length == 0) {
        document.body.style.height = P2PWAP.ui.ModalHeight_ + "px";
        document.body.style.overflow = "hidden";
    }
    P2PWAP.ui.ModalViewEls_.push(instance);
    instance.style.height = P2PWAP.ui.ModalHeight_ + "px";
};

P2PWAP.ui.removeModalView = function(instance) {
    if (!window.navigator || !/iphone.*OS 7.*/i.test(window.navigator.userAgent)) return;
    var index = P2PWAP.ui.ModalViewEls_.indexOf(instance);
    if (index >= 0) {
        P2PWAP.ui.ModalViewEls_.splice(index, 1);
    }
    if (P2PWAP.ui.ModalViewEls_.length == 0) {
        document.body.style.height = "auto";
        document.body.style.overflow = "auto";
    }
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
                errorCallback.call(null, '服务器忙,请稍后重试');
                return;
            }
            if (json['errorCode'] != 0) {
                errorCallback.call(null, json['errorMsg']);
                return;
            }
            success_callback.call(null, json['data']);
        },
        error: function() {
            errorCallback.call(null, '您的网络貌似不给力,请稍后重试');
        }
    });
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
P2PWAP.util.wxJudge = function(){
    var userAgentString = window.navigator ? window.navigator.userAgent : "";
    var weixinreg = /MicroMessenger/i;
    return weixinreg.test(userAgentString);
};

P2PWAP.util.getUrlParam = function(name){
    var regObj = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
    var matchResult = window.location.search.substr(1).match(regObj);
    if (matchResult != null) {
        return decodeURIComponent(matchResult[2]);
    }
    return null;
};

P2PWAP.util.getWapHost = function(param, reg, idx){
    var _redirectUri, _matchResult, _wapHost = 'm.firstp2p.com';
    var _redirectUri = P2PWAP.util.getUrlParam(param);
    idx = idx || 1;
    if (_redirectUri) {
        _matchResult = _redirectUri.match(new RegExp(reg));
    }
    if (_matchResult) {
        _wapHost = _matchResult[idx] ? _matchResult[idx] : 'm.firstp2p.com';
    }
    return _wapHost;
};

P2PWAP.util.checkMobile = function(val) {
    return /^0?(13[0-9]|15[0-9]|17[0-9]|18[0-9]|14[57])[0-9]{8}$/.test(val);
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
P2PWAP.util.checkEmail = function(val) {
    return /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i.test(val);
};