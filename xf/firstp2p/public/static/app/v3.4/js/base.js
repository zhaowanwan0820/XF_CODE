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

//前端分页
P2PWAP.ui.frontPage = function(options){
    var defaultSettings = {
        length : 10 ,
        index : 0 ,
        list : $(".loan_detail"),
        tips : $("#tips"),
        noMsg : "没有更多了"
    },
    settings = $.extend(true, defaultSettings, options),
    length = settings.length,
    index = settings.index,
    $list = settings.list,
    $tips = settings.tips,
    noMsg =settings.noMsg,
    lock = false;


    if($list.length <= length){
        $tips.html(noMsg);
        return;
    }
    $list.each(function(i , v){
        if(i >= length){
            $(v).hide();
        }
    });

    $tips.on("tap touchend" ,function(event){
        if(!!lock){
            return false;
        }
        $list.each(function(i , v){
            if(i >= index && i < (length+index)){
                setTimeout(function(){
                    $(v).css("display" , "block") ;
                }, 400);
            }
        });
        index = length + index;
        if(index >= $list.length){
             setTimeout(function(){
                    $tips.html(noMsg);
                }, 500);

            lock = true;
        }
        event.preventDefault();
    });
}

P2PWAP.cache = {};
P2PWAP.cache._StoreKey_ = "_P2PBANKCACHEKEY_";
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
    if (!during) during = 600000;
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


P2PWAP.P2PLoadMore = function(container, loadmorepanel, urlbase, opt_page, opt_type, opt_numperpage) {
    var pThis = this;
    pThis.urlbase = urlbase;
    pThis.page = opt_page >= 1 ? opt_page : 0;
    pThis.loading = false;
    pThis.ajaxType = opt_type == "post" ? "post" : "get";
    pThis.numPerPage = opt_numperpage >= 1 ? (opt_numperpage): 10;
    pThis.container = container;
    pThis.loadmorepanel = loadmorepanel;
    // $(pThis.loadmorepanel).find("a").bind("click", function(event){
    //   if($(event.target).parent().hasClass('load_more')){
    //     pThis.loadNextPage(0);
    //   }else{
    //     pThis.loadNextPage();
    //   }

    // });
};


P2PWAP.P2PLoadMore.prototype.loadNextPage = function(type) {
    var pThis = this;
    if (pThis.loading) {
        return;
    }
    pThis.setLoading(true);
    $.ajax({
        url: pThis.urlbase + "&offset=" + pThis.page + "&count=" + pThis.numPerPage,
        type: pThis.ajaxType,
        dataType: 'json',
        success: function(rawData) {
            //console.log(rawData);
            pThis.setLoading(false);
            pThis.processData(rawData , type);
        },
        error: function() {
            pThis.setLoading(false);
            alert("网络错误");
        }
    });
};

P2PWAP.P2PLoadMore.prototype.setLoading = function(loading) {
    this.loading = loading;
    this.loadmorepanel.innerHTML = "加载中...";
};

P2PWAP.P2PLoadMore.prototype.preProcessData = function(ajaxData) {
    return ajaxData;
};

P2PWAP.P2PLoadMore.prototype.processData = function(ajaxData) {
    var pThis = this;
    ajaxData = this.preProcessData(ajaxData);
    if (!ajaxData.data) {
        //NOTE: 添加处理错误
        return;
    }else if(ajaxData.data.length <= 0  && pThis.page <= 0){
        $(pThis.container).html('<div class="no_coupon"><p>这里空空如也</p></div>');
        pThis.loadmorepanel.innerHTML = '';
    }else{
        pThis.page += pThis.numPerPage;
        //console.log(ajaxData.data);
        var listDataItem = ajaxData.data;

        if (listDataItem.length > 0) {
            for(var index = 0; index < listDataItem.length; index++) {
                pThis.container.appendChild(pThis.createItem(listDataItem[index]));
            }
           !!pThis.callback && pThis.callback();
        }
        if (listDataItem.length < (pThis.numPerPage) ) {
            pThis.loadmorepanel.innerHTML = "没有更多了";
        }else{
            pThis.loadmorepanel.innerHTML = '<a href="javascript:void(0);">点击加载更多</a>';
            $(pThis.loadmorepanel).find("a").unbind('click').bind("click", function(){
              pThis.loadNextPage();
            });
        }

    }

};

P2PWAP.P2PLoadMore.prototype.createItem = function(dataItem) {
    var div = document.createElement("div");
    return div;
};

P2PWAP.P2PLoadMore.prototype.refresh = function(){
    if (this.xhr) {
        this.xhr.abort();
        delete this.xhr;
    }
    this.page = 0;
    this.hasNoMore = false;
    this.loading = false;
    this.container.innerHTML = '';
    this.isEmpty = true;
    //this.updateLoadMoreBtn();
    this.loadNextPage();
};