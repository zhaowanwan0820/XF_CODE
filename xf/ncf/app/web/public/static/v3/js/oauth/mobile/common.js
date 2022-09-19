/** 依赖zepto **/
P2PWAP = {};
P2PWAP.common = {};
P2PWAP.util = {};

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

// 弹框
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
    $(div).find('.dialog_btn').click(function(event) {
        $(div).remove();
    });
    var myScroll_id = new IScroll($(div).find('.dialog_text')[0],{
        scrollbars: true,
        interactiveScrollbars: true,
        shrinkScrollbars: 'scale',
        fadeScrollbars: true
    });
};

// 倒计时
P2PWAP.ui.updateTimeLabel = function(domBtn, during) {
    var timeRemained = during;
    var timer = setInterval(function() {
        domBtn.value = timeRemained + '秒后可重发';
        timeRemained -= 1;
        if (timeRemained == -1) {
            clearInterval(timer);
            domBtn.value = '重新发送';
            domBtn.classList.remove('sending');
            domBtn.removeAttribute('disabled');
        }
    }, 1000);
    return timer;
}

//提交按钮状态
P2PWAP.ui.btnDisableType = function(arr, elBtn) {
    function checkClickAble() {
        var disable = false;
        for (var i = 0; i < arr.length; i++) {
            if (arr[i].value == '') {
                disable = true;
                break;
            }
        }
        if (disable) {
            elBtn.setAttribute('disabled', 'disabled');
        } else {
            elBtn.removeAttribute('disabled');
        }
    }
    for (var i = 0; i < arr.length; i++) {
        arr[i].oninput = function() {
            checkClickAble();
        }
    }
    checkClickAble();
    setInterval(function(){checkClickAble()}, 300);
};

// 按钮上锁
P2PWAP.ui.btnLock = function(btn) {
    btn.setAttribute('disabled', 'disabled');
}

// 按钮解锁
P2PWAP.ui.btnUnLock = function(btn) {
    btn.removeAttribute('disabled');
}


/*********************** UtIl ***************************/

P2PWAP.util.ajax = function(url, method, suc_back, error_back, opt_postdata, anys) {
    return $.ajax({
        url: url,
        type: method,
        async: (!anys) ? true : false,
        data: opt_postdata ? opt_postdata : null,
        success: function(data) {
            var object = $.parseJSON(data);
            suc_back.call(null, object);
            // if (!object.data) {
            //   error_back.call(null, "服务器错误，稍后重试");
            // }
            // if (object.errorCode == 0) {
            //   suc_back.call(null, object.data);
            // } else {
            //   error_back.call(null, object.errorMsg);
            // }
        },
        error: function() {
            error_back.call(null, "网络异常，稍后重试");
        }
    });
};


/********************** COMMON **************************/

P2PWAP.common.checkMobile = function(mobile) {
    var reg = /^1[3456789]\d{9}$/;
    if (!reg.test(mobile.value)) {
        P2PWAP.ui.showErrorTip('手机号格式不正确');
        return false;
    }
    return true;
};

P2PWAP.common.checkPassword = function(password) {
    var reg = /^[^\s]{6,20}$/m;
    var spaceReg = / /;
    if (!reg.test(password.value)) {
        P2PWAP.ui.showErrorTip('登录密码\<br\/\>（6-20位数字/字母/标点）');
        return false;
    }
    return true;
};

P2PWAP.common.checkCaptcha = function(captcha) {
    var reg = /^\d{4,10}$/;
    if (!reg.test(captcha.value)) {
        P2PWAP.ui.showErrorTip('图形验证码不正确');
        return false;
    }
    return true;
};


P2PWAP.common.checkInvite = function(invite, domBtn, callBack, btnFn) {
    var val = invite.value;
    if (!callBack) callBack = function() {};
    if (!btnFn) btnFn = function() {};
    if (val != '') {
        P2PWAP.util.ajax('./CheckInvitecode', 'get', function(obj) {
            if (obj.errno == '0') {
                callBack.call(null);
            } else {
                P2PWAP.ui.showErrorTip(obj.error);
                btnFn.call(null);
            }
        }, function(msg) {
            P2PWAP.ui.showErrorTip(msg);
            btnFn.call(null);
        }, {
            code: val
        });
    } else {
        callBack.call(null);
    }
};
