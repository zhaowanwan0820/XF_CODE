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

// loadMore
WXP2P.UI.P2PLoadMore = function(container, loadmorepanel, urlbase, opt_page, opt_type, opt_numperpage) {
    var pThis = this;
    pThis.urlbase = urlbase;
    pThis.isEmpty = true;
    pThis.page = opt_page > 1 ? opt_page : 1;
    pThis.loading = false;
    pThis.xhr = null;
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
    this.xhr = $.ajax({
        url: pThis.urlbase + "&page=" + pThis.page,
        type: pThis.ajaxType,
        dataType: 'json',
        success: function(rawData) {
            pThis.xhr = null;
            pThis.setLoading(false);
            pThis.processData(rawData);
        },
        error: function() {
            pThis.xhr = null;
            pThis.setLoading(false);
            alert("网络错误");
        }
    });
};

WXP2P.UI.P2PLoadMore.prototype.setLoading = function(loading) {
    this.loading = loading;
    if (this.loading == false && this.xhr) {
        delete this.xhr;
    }
    this.updateLoadMoreBtn();
};

WXP2P.UI.P2PLoadMore.prototype.updateLoadMoreBtn = function(){
    if (this.loading) {
        this.loadmorepanel.innerHTML = '加载中...';
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

WXP2P.UI.P2PLoadMore.prototype.processData = function(ajaxData) {
    var pThis = this;
    if (!ajaxData.data) {
        //NOTE: 添加处理错误
        return;
    }
    pThis.page++;
    var listDataItem = ajaxData.data;
    if (listDataItem.length > 0) {
        pThis.isEmpty = false;
        for(var index = 0; index < listDataItem.length; index++) {
            pThis.container.appendChild(pThis.createItem(listDataItem[index]));
        }
    }
    if (!(listDataItem.length >= pThis.numPerPage)) {
        this.hasNoMore = true;
    }
    this.updateLoadMoreBtn();
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
    this.updateLoadMoreBtn();
    this.loadNextPage();
};

WXP2P.UI.P2PLoadMore.prototype.createItem = function(dataItem) {
    var div = document.createElement("div");
    return div;
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

WXP2P.UTIL.dataFormat = function(timestamp,type){
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


$(function(){
  // 进入页面拼写proto
    WXP2P.APP.batchWarpAnchorSchema('body a');
    var meta = "<meta name=\"format-detection\" content=\"telephone=no\" />";
    $("head").append(meta);
});
