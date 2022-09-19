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

P2PWAP.ui = {};
P2PWAP.ui.toast = function(msg) {
    var ins = new P2PWAP.ui.Toaster_(msg);
    ins.show();
    setTimeout(function() {
        ins.dispose();
    }, 2000);
};
P2PWAP.ui.Toaster_ = function(msgHtml) {
    this.ele = null;
    this.msgHtml = msgHtml;
};
P2PWAP.ui.Toaster_.prototype.createDom = function() {
    this.ele = document.createElement("div");
    this.ele.className = "ui-input-error";
    this.ele.innerHTML = "<span>" + this.msgHtml + "</span>";
    document.body.appendChild(this.ele);
};
P2PWAP.ui.Toaster_.prototype.show = function() {
    if (!this.ele) {
        this.createDom();
    };
    var pThis = this;
    setTimeout(function() {
        if (!pThis.ele) return;
        pThis.ele.className = "ui-input-error show";
    }, 1);
};
P2PWAP.ui.Toaster_.prototype.dispose = function() {
    if (!this.ele) return;
    this.ele.className = "ui-input-error";
    var el = this.ele;
    delete this.ele;
    setTimeout(function(){
        document.body.removeChild(el);
    }, 300);
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
        this.loadmorepanel.innerHTML = "加载中...";
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
    var length = rawData.length;
    if (rawData && length > 0) {
        for (var i = 0; i < length; i++) {
            this.container.appendChild(this.createItem(rawData[i]));
        }
    }
    if (!(length >= this.numPerPage)) {
        this.hasNoMore = true;
    }
    this.updateLoadMoreBtn();
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

$(function(){
  //初始化header
  $("#JS-downloadPanelClose").bind("click", function(){
    P2PWAP.util.setCookie(P2PWAP.Const.COOKIE_HIDE_APPDOWNLOAD, 'true', 3 * 24 * 3600);
    $("#JS-headPanel").addClass("down_app_none");
  });
});