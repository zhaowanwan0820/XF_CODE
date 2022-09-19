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
            if (json['errno'] != 0) {
                errorCallback.call(null, json['error']);
                return;
            }
            success_callback.call(null, json['data']);
        },
        error: function() {
            errorCallback.call(null, '您的网络貌似不给力,请稍后重试');
        }
    });
};

P2PWAP.util.dataFormat = function(timestamp,type){
    var data = new Date(timestamp * 1000);
    var year = data.getFullYear();
    var month = data.getMonth() + 1;
    var day = data.getDate();
    var hour = data.getHours();
    var minute = data.getMinutes();
    var second = data.getSeconds();
    //加0
    if(month < 10)  month = '0' + month;
    if(day < 10)    day = '0' + day;
    if(hour < 10)   hour = '0' + hour;
    if(minute < 10) minute = '0' + minute;
    if(second < 10) second = '0' + second;

    if (typeof type == 'undefined' || type == '') {
        return year + '-' + month + '-' + day + ' ' + hour + ':' + minute + ':' + second;
    } else {
        return type.replace('y',year).replace('m',month).replace('d',day).replace('h',hour).replace('i',minute).replace('s',second);
    }
};

P2PWAP.util.wxJudge = function(){
    var userAgentString = window.navigator ? window.navigator.userAgent : "";
    var weixinreg = /MicroMessenger/i;
    return weixinreg.test(userAgentString);
};

P2PWAP.util.checkMobile = function(val) {
    return /^0?(13[0-9]|15[0-9]|17[0-9]|18[0-9]|14[57])[0-9]{8}$/.test(val);
};

P2PWAP.ui = {};

P2PWAP.ui.confirm = function(title, content, opt_confirmCallback, opt_cancelCallback) {
    var popup = document.createElement("div");
    popup.className = "ui_confirm";
    var html = "";
    html += '<div class="opacity"></div>';
    html += '<div class="confirm_donate">';
    html += '    <p class="confirm_donate_title">' + title + '</p>';
    html += '    <p class="confirm_donate_text">' + content + '</p>';
    html += '    <div class="confirm_donate_but">';
    html += '        <input type="button" class="JS-cancel confirm_donate_but_del" value="取消">';
    html += '        <input type="button" class="JS-confirm confirm_donate_but_yes" value="确认">';
    html += '    </div>';
    html += '</div>';
    popup.innerHTML = html;
    $("body").append(popup);
    $(popup).find(".JS-cancel").bind("click", function() {
        $(popup).remove();
        if (typeof opt_cancelCallback == "function") {
            opt_cancelCallback.call(null);
        }
    });
    $(popup).find(".JS-confirm").bind("click", function() {
        $(popup).remove();
        if (typeof opt_confirmCallback == "function") {
            opt_confirmCallback.call(null);
        }
    });
};

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
    this.ele.setAttribute("style", "z-index:1002;position:fixed;width:100%;text-align:center;bottom:50px;-webkit-transition:opacity linear 0.5s;opacity:0;");
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

P2PWAP.ui.P2PLoadMore = function(container, loadmorepanel, urlbase, opt_page, opt_numperpage, opt_hasmore) {
    this.urlbase = urlbase;
    this.page = opt_page > 1 ? opt_page : 1;
    this.loading = false;
    this.xhr = null;
    this.hasNoMore = opt_hasmore == true;
    this.numPerPage = opt_numperpage > 1 ? opt_numperpage : 10;
    this.container = container;
    this.loadmorepanel = loadmorepanel;
    this.updateLoadMoreBtn();
};
P2PWAP.ui.P2PLoadMore.prototype.loadNextPage = function() {
    if (this.loading) {
        return;
    }
    this.setLoading(true);
    var pThis = this;
    this.xhr = P2PWAP.util.request(this.urlbase,
            function(rawData){
                pThis.setLoading(false);
                pThis.processData(rawData);
            },
            function(errorMsg){
                pThis.setLoading(false);
                P2PWAP.ui.toast(errorMsg);
            },
            'get', {'p': this.page});
};
P2PWAP.ui.P2PLoadMore.prototype.setLoading = function(loading) {
    this.loading = loading;
    if (this.loading == false && this.xhr) {
        delete this.xhr;
    }
    this.updateLoadMoreBtn();
};
P2PWAP.ui.P2PLoadMore.prototype.updateLoadMoreBtn = function(){
    if (this.loading) {
        this.loadmorepanel.innerHTML = '<div class="ui_loading"><div class="bar1"></div><div class="bar2"></div><div class="bar3"></div><div class="bar4"></div><div class="bar5"></div><div class="bar6"></div><div class="bar7"></div><div class="bar8"></div><div class="bar9"></div><div class="bar10"></div><div class="bar11"></div><div class="bar12"></div></div>&nbsp;&nbsp;正在加载';
    } else if(this.hasNoMore) {
        this.loadmorepanel.innerHTML = "没有更多了";
    } else {
        this.loadmorepanel.innerHTML = '<a href="javascript:void(0)">点击加载更多</a>';
        var pThis = this;
        $(this.loadmorepanel).find("a").unbind("click").bind("click", function(){
            pThis.loadNextPage();
        });
    }
};
P2PWAP.ui.P2PLoadMore.prototype.processData = function (rawData) {
    this.page++;
    var length = rawData ? rawData.length : 0;
    if (rawData && length > 0) {
        for (var i = 0; i < length; i++) {
            var addDom = this.createItem(rawData[i]);
            if($.isArray(addDom)){
                for(var j = 0; j < addDom.length; j++){
                    this.container.appendChild(addDom[j]);
                }
            } else {
                this.container.appendChild(addDom);
            }
        }
    }
    if (!(length >= this.numPerPage)) {
        this.hasNoMore = true;
    }
    this.updateLoadMoreBtn();
};
P2PWAP.ui.P2PLoadMore.prototype.preloadPage = function(rawData, page) {
    this.page = page;
    this.processData(rawData);
};
P2PWAP.ui.P2PLoadMore.prototype.refresh = function(){
    if (this.xhr) {
        this.xhr.abort();
        delete this.xhr;
    }
    this.page = 1;
    this.hasNoMore = false;
    this.loading = false;
    this.container.innerHTML = '';
    this.updateLoadMoreBtn();
    this.loadNextPage();
};
P2PWAP.ui.P2PLoadMore.prototype.createItem = function(dataItem) {
    var div = document.createElement("div");
    return div;
};

P2PWAP.cache = {};
P2PWAP.cache._StoreKey_ = "_P2PCACHEKEY_";
P2PWAP.cache._CacheData_ = null;

P2PWAP.cache._init_ = function() {
    var json = localStorage.getItem(P2PWAP.cache._StoreKey_);
    if (!json) json = JSON.stringify({});
    var cacheObj = JSON.parse(json);
    var nowDate = (new Date()).getTime();
    for (var i in cacheObj) {
        if (cacheObj[i]['timeStamp'] < nowDate) delete cacheObj[i];
    }
    P2PWAP.cache._CacheData_ = cacheObj;
    P2PWAP.cache._store_();
};
P2PWAP.cache._store_ = function() {
    try {
        localStorage.setItem(P2PWAP.cache._StoreKey_, JSON.stringify(P2PWAP.cache._CacheData_));
    } catch (e) {
        
    }
};
P2PWAP.cache.get = function(key) {
    P2PWAP.cache._init_();
    if (P2PWAP.cache._CacheData_[key]) {
        return P2PWAP.cache._CacheData_[key]['value'];  
    }
    return undefined;
};

P2PWAP.cache.set = function(key, value, during) {
    if (!during) during = 60;
    P2PWAP.cache._init_();
    var timeStamp = (new Date()).getTime() + during * 1000;
    P2PWAP.cache._CacheData_[key] = {"value": value, "timeStamp": timeStamp};
    P2PWAP.cache._store_();
};
P2PWAP.cache.del = function(key) {
    P2PWAP.cache._init_();
    if (!P2PWAP.cache._CacheData_[key]) return;
    delete P2PWAP.cache._CacheData_[key];
    P2PWAP.cache._store_();
};

P2PWAP.ui.instanceTextClip = function(lineDom, selfFn, showFn, hideFn, option) {
    if ($(lineDom).attr("data-textclip") == "true") return;
    if (typeof selfFn != 'function') selfFn = function() {};
    if (typeof showFn != 'function') showFn = function() {};
    if (typeof hideFn != 'function') hideFn = function() {};
    var _instance = new P2PWAP.ui.textClip(lineDom, showFn, hideFn, option);
    selfFn.call(null, _instance);
    _instance.init();
    return _instance;
};
P2PWAP.ui.textClip = function(lineDom, showFn, hideFn, option) {
    var option = (typeof option == 'object') ? option : {};
    this.showFn = showFn;
    this.hideFn = hideFn;
    this.lineDom = $(lineDom);
    this.opt = $.extend({}, option);
};
P2PWAP.ui.textClip.prototype.init = function() {
    $(this.lineDom).attr("data-textclip", "true");
    this.judgeHeight();
};
P2PWAP.ui.textClip.prototype.judgeHeight = function() {
    var _this = this;
    _this.lineDom.css({
        "white-space": "nowrap",
        "overflow": "hidden"
    });
    _this.lineHeight = _this.lineDom[0].clientHeight;
    _this.lineDom.css({
        "white-space": "normal",
        "overflow": "visible"
    });
    if (_this.lineDom[0].clientHeight > _this.lineHeight) {
        _this.neddClip = true;
        _this.setDom();
    }
};
P2PWAP.ui.textClip.prototype.setDom = function() {
    var _this = this;
    _this.lineDom.addClass('__textClip__');
    _this.lineDom.css({
        "white-space": "nowrap",
        "overflow": "hidden",
        "text-overflow": "ellipsis"
    });
    _this.createArrow();
    _this.arrowIsDown = true;
    _this.arrowEvent();
};
P2PWAP.ui.textClip.prototype.createArrow = function() {
    this.lineDom.append('<span class="__textClipArrow__ __textClipArrowDown__"></span>');
    this.arrowEle = this.lineDom.find('.__textClipArrow__');
};
P2PWAP.ui.textClip.prototype.arrowEvent = function() {
    var _this = this;
    $(_this.arrowEle).click(function(event) {
        // event.preventDefault();
        if (_this.arrowIsDown) {
            _this.arrowIsDown = false;
            $(this).removeClass('__textClipArrowDown__').addClass('__textClipArrowUp__');
            _this.lineDom.css({
                "white-space": "normal",
                "overflow": "visible",
                "text-overflow": "clip"
            });
            _this.showFn.call(null, _this);
        } else {
            _this.arrowIsDown = true;
            $(this).removeClass('__textClipArrowUp__').addClass('__textClipArrowDown__');
            _this.lineDom.css({
                "white-space": "nowrap",
                "overflow": "hidden",
                "text-overflow": "ellipsis"
            });
            _this.hideFn.call(null, _this);
        }
    });
}

$(function(){
    //初始化header
    $("#JS-downloadPanelClose").bind("click", function(){
      P2PWAP.util.setCookie(P2PWAP.Const.COOKIE_HIDE_APPDOWNLOAD, 'true', 3 * 24 * 3600);
      $("#JS-headPanel").addClass("down_app_none");
    });
  });