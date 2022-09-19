if (typeof WXP2P == "undefined") {
  WXP2P = {};
}
if (typeof WXP2P.APP == "undefined") {
  WXP2P.APP = {};
}
// proto
// WXP2P.APP.browser_model = false;
WXP2P.APP.browser_model = false;
WXP2P.APP.warpAnchorSchema = function(anchor) {
  if (this.browser_model == true) return;
  if(anchor.href == 'javascript:void(0);') return;
  var proto = anchor.getAttribute("data-proto");
  if (proto == null || proto == "") {
    return;
  }
  var url = proto + "?url=" + encodeURIComponent(anchor.href);
  var stringArr = ['title','backtype', 'backid', 'type','identity', 'needcloseall'];
  var booleanArr = ['needback','needrefresh'];
  for(var i = 0; i < stringArr.length; i++){
    var str = anchor.getAttribute('data-' + stringArr[i]);
    if (str != null && str != "") {
      url += "&" + stringArr[i] + "=" + encodeURIComponent(str);
    }
  }
  for(var i = 0; i < booleanArr.length; i++){
    var boolean = anchor.getAttribute('data-' + booleanArr[i]);
    if (boolean == "true") {
      url += "&" + booleanArr[i] + "=true";
    }else{
      url += "&" + booleanArr[i] + "=false";
    }
  }
  anchor.href = url;
};
WXP2P.APP.batchWarpAnchorSchema = function(el){
  $(el).each(function(k,v){
    WXP2P.APP.warpAnchorSchema($(v)[0]);
  });
}

if (typeof WXP2P.UI == "undefined") {
  WXP2P.UI = {};
}

WXP2P.APP.setCookie = function(name, value, expiredays) {
    var exdate = new Date();
    exdate.setDate(exdate.getDate()+expiredays)
    document.cookie=name+ "=" +escape(value)+ ";path=/" +
    ((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
};

WXP2P.APP.getCookie = function(name) {
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


// loadMore
WXP2P.UI.P2PLoadMore = function(container, loadmorepanel, urlbase, opt_page, opt_type, opt_numperpage) {
    var pThis = this;
    pThis.urlbase = urlbase;
    pThis.page = opt_page > 1 ? opt_page : 1;
    pThis.loading = false;
    pThis.ajaxType = opt_type == "post" ? "post" : "get";
    pThis.numPerPage = opt_numperpage > 1 ? opt_numperpage : 10;
    pThis.container = container;
    pThis.loadmorepanel = loadmorepanel;
    $(pThis.loadmorepanel).find("a").bind("click", function(){
      pThis.loadNextPage();
    });
};

WXP2P.UI.P2PLoadMore.prototype.loadNextPage = function() {
    var pThis = this;
    if (pThis.loading) {
        return;
    }
    pThis.setLoading(true);
    $.ajax({
        url: pThis.urlbase + "&page=" + pThis.page,
        type: pThis.ajaxType,
        dataType: 'json',
        success: function(rawData) {
            //console.log(rawData);
            pThis.setLoading(false);
            pThis.processData(rawData);
        },
        error: function() {
            pThis.setLoading(false);
            alert("网络错误");
        }
    });
};

WXP2P.UI.P2PLoadMore.prototype.setLoading = function(loading) {
    this.loading = loading;
    this.loadmorepanel.innerHTML = "加载中...";
};

WXP2P.UI.P2PLoadMore.prototype.preProcessData = function(ajaxData) {
    return ajaxData;
};

WXP2P.UI.P2PLoadMore.prototype.processData = function(ajaxData) {
    var pThis = this;
    ajaxData = this.preProcessData(ajaxData);
    if (!ajaxData.data) {
        //NOTE: 添加处理错误
        return;
    }else if(ajaxData.data.total <= 0 ){
        $(pThis.container).html('<div class="no_coupon"><p>暂无投资券</p></div>');
        pThis.loadmorepanel.innerHTML = '';
    }else{
        pThis.page++;
        //console.log(ajaxData.data);
        var listDataItem = ajaxData.data.list;

        if (listDataItem.length > 0) {
            for(var index = 0; index < listDataItem.length; index++) {
                pThis.container.appendChild(pThis.createItem(listDataItem[index]));
            }
            !!pThis.callback && pThis.callback();
        }
        if (!(listDataItem.length >= pThis.numPerPage)) {
            if(pThis.loadmorepanel.id == "load_txt"){
              pThis.loadmorepanel.innerHTML = "仅显示最近30天内的投资券";
            }else{
                // pThis.loadmorepanel.innerHTML = "没有更多了";
                pThis.loadmorepanel.innerHTML = "";
            }
        }else{
            pThis.loadmorepanel.innerHTML = '<a href="javascript:void(0);">点击加载更多</a>';
            $(pThis.loadmorepanel).find("a").unbind('click').bind("click", function(){
              pThis.loadNextPage();
            });
        }
    }

};

WXP2P.UI.P2PLoadMore.prototype.createItem = function(dataItem) {
    var div = document.createElement("div");
    return div;
};

WXP2P.UI.P2PLoadMore.prototype.refresh = function(){
    if (this.xhr) {
        this.xhr.abort();
        delete this.xhr;
    }
    this.page = 1;
    this.hasNoMore = false;
    this.loading = false;
    this.container.innerHTML = '';
    this.isEmpty = true;
    //this.updateLoadMoreBtn();
    this.loadNextPage();
};

// 轮询
if (typeof WXP2P.UTIL == "undefined") {
  WXP2P.UTIL = {};
}
WXP2P.UTIL._notificationScriptTagMap = {};
WXP2P.UTIL.longLoopLink = function (link, onError, key){
    if (WXP2P.UTIL._notificationScriptTagMap[key] != null) {
        document.body.removeChild(WXP2P.UTIL._notificationScriptTagMap[key]);
        delete WXP2P.UTIL._notificationScriptTagMap[key];
    }
    WXP2P.UTIL._notificationScriptTagMap[key] = document.createElement("script");
    WXP2P.UTIL._notificationScriptTagMap[key].src = link;
    WXP2P.UTIL._notificationScriptTagMap[key].onerror = function(err){
        document.body.removeChild(WXP2P.UTIL._notificationScriptTagMap[key]);
        delete WXP2P.UTIL._notificationScriptTagMap[key];
        WXP2P.UTIL._notificationScriptTagMap[key] = null;
        onError.call(null);
    }
    WXP2P.UTIL._notificationScriptTagMap[key].onload = function() {
        document.body.removeChild(WXP2P.UTIL._notificationScriptTagMap[key]);
        delete WXP2P.UTIL._notificationScriptTagMap[key];
        WXP2P.UTIL._notificationScriptTagMap[key] = null;
    }
    document.body.appendChild(WXP2P.UTIL._notificationScriptTagMap[key]);
}

WXP2P.UTIL.dataFormat = function(timestamp,type,format){
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
      if(format==1){
        return month + '-' + day + ' ' + hour + ':' + minute;
      }else{
        return year + '-' + month + '-' + day + ' ' + hour + ':' + minute + ':' + second;
      }
    } else {
        if(format==1){
          return type.replace('m',month).replace('d',day).replace('h',hour).replace('i',minute);
        }else{
            return type.replace('y',year).replace('m',month).replace('d',day).replace('h',hour).replace('i',minute).replace('1s',second);
          }
        }

};

WXP2P.UI.showErrorInstance_ = null;
WXP2P.UI.showErrorInstanceTimer_ = null;
WXP2P.UI.showErrorTip = function(msg) {
    if (WXP2P.UI.showErrorInstance_) {
        clearTimeout(WXP2P.UI.showErrorInstanceTimer_);
        WXP2P.UI.showErrorInstance_.updateContent(msg);
    } else {
        WXP2P.UI.showErrorInstance_ = new WXP2P.UI.ErrorToaster_(msg);
        WXP2P.UI.showErrorInstance_.show();
    }
    WXP2P.UI.showErrorInstanceTimer_ = setTimeout(function() {
        WXP2P.UI.showErrorInstance_.dispose();
        WXP2P.UI.showErrorInstance_ = null;
        WXP2P.UI.showErrorInstanceTimer_ = null;
    }, 2000);
};
WXP2P.UI.toast = WXP2P.UI.showErrorTip;

WXP2P.UI.ErrorToaster_ = function(msgHtml) {
    this.ele = null;
    this.msgHtml = msgHtml;
};

WXP2P.UI.ErrorToaster_.prototype.createDom = function() {
    this.ele = document.createElement("div");
    this.ele.innerHTML = "<span style=\"display: inline-block;color:white;max-width:250px;min-width:100px;word-break:break-word;padding:10px;background:rgba(0,0,0,0.7);border-radius:5px;\">" + this.msgHtml + "</span>";
    this.ele.setAttribute("style", "z-index:1002;position:fixed;width:100%;text-align:center;top:45%;-webkit-transition:opacity linear 0.5s;opacity:0;");
    document.body.appendChild(this.ele);
};

WXP2P.UI.ErrorToaster_.prototype.updateContent = function(msgHtml) {
    this.msgHtml = msgHtml;
    if (!this.ele) return;
    $(this.ele).find("span").html(this.msgHtml);
};

WXP2P.UI.ErrorToaster_.prototype.show = function() {
    if (!this.ele) {
        this.createDom();
    }
    var pThis = this;
    setTimeout(function() {
        if (!pThis.ele) return;
        pThis.ele.style.opacity = "1";
    }, 1);
};

WXP2P.UI.ErrorToaster_.prototype.hide = function() {
    if (!this.ele) return;
    this.ele.style.opacity = "0";
    var ele = this.ele;
    delete this.ele;
    setTimeout(function() {
        document.body.removeChild(ele);
    }, 500);
};

WXP2P.UI.ErrorToaster_.prototype.dispose = function() {
    this.hide();
};

$(function(){
  // 进入页面拼写proto
    WXP2P.APP.batchWarpAnchorSchema('body a');
    var meta = "<meta name=\"format-detection\" content=\"telephone=no\" />";
    $("head").append(meta);
});
