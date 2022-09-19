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
  var stringArr = ['title','backtype', 'backid', 'type','identity'];
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

WXP2P.UI.P2PLoadMore.prototype.processData = function(ajaxData) {
    var pThis = this;
    if (!ajaxData.data) {
        //NOTE: 添加处理错误
        return;
    }
    pThis.page++;
    var listDataItem = ajaxData.data;
    if (listDataItem.length > 0) {
        for(var index = 0; index < listDataItem.length; index++) {
            pThis.container.appendChild(pThis.createItem(listDataItem[index]));
        }
    }
    if (!(listDataItem.length >= pThis.numPerPage)) {
        pThis.loadmorepanel.innerHTML = "没有更多了";
    }else{
        pThis.loadmorepanel.innerHTML = '<a href="javascript:void(0);">点击加载更多</a>';
        $(pThis.loadmorepanel).find("a").unbind('click').bind("click", function(){
          pThis.loadNextPage();
        });
    }
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


$(function(){
  // 进入页面拼写proto
  WXP2P.APP.batchWarpAnchorSchema('body a');
});
