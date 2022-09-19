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
WXP2P.UI.P2PLoadMore = function(container, loadmorepanel, urlbase, opt_page, opt_type, opt_numperpage ,opt_cirle) {
    var pThis = this;
    pThis.urlbase = urlbase;
    pThis.page = opt_page > 1 ? opt_page : 1;
    pThis.loading = false;
    pThis.ajaxType = opt_type == "post" ? "post" : "get";
    pThis.numPerPage = opt_numperpage > 1 ? opt_numperpage : 10;
    pThis.container = container;
    pThis.loadmorepanel = loadmorepanel;
    pThis.opt_cirle = opt_cirle;
    $(pThis.loadmorepanel).find("a").bind("click", function(){
      pThis.loadNextPage();
    });
    return pThis;
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
    }else if(!!pThis.opt_cirle){
        pThis.page++;
        var listDataItem = ajaxData.data;
        
        if (listDataItem.length > 0) {
            //pThis.container.appendChild(pThis.createItem(ajaxData));
            pThis.createItem(ajaxData);   
        }
        !!pThis.callback && pThis.callback();
        if (!(listDataItem.length >= pThis.numPerPage)) {
            pThis.loadmorepanel.innerHTML = "没有更多了";
        }else{
            pThis.loadmorepanel.innerHTML = '<a href="javascript:void(0);">点击加载更多</a>';
            $(pThis.loadmorepanel).find("a").unbind('click').bind("click", function(){
              pThis.loadNextPage();
            });
        }
    }else{
        pThis.page++;
        var listDataItem = ajaxData.data;
        
        if (listDataItem.length > 0) {
            for(var index = 0; index < listDataItem.length; index++) {
                pThis.container.appendChild(pThis.createItem(listDataItem[index]));
            }
            !!pThis.callback && pThis.callback();
        }
        if (!(listDataItem.length >= pThis.numPerPage)) {
            pThis.loadmorepanel.innerHTML = "没有更多了";
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
      }
      else if(format==2){
        return year + '-' + month + '-' + day;
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

$(function(){
  // 进入页面拼写proto
    WXP2P.APP.batchWarpAnchorSchema('body a');
    var meta = "<meta name=\"format-detection\" content=\"telephone=no\" />",
        $j_errorMsgBtn = $(".j-errorMsgBtn"),  // 获取要禁用掉的按钮
        errorData = $j_errorMsgBtn.data("errmsg"); //获取错误信息
    
    $("head").append(meta);

  
    if(!!errorData){
      //如果错误信息存在，显示错误弹框提示，以及禁用掉按钮
        P2PWAP.ui.showErrorTip(errorData); 
        $(".j-errorMsgBtn").css("background" ,"#cccccc").attr("href" ,"#")
    }
});
