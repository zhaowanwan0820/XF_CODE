if (typeof WXP2P == "undefined") {
  WXP2P = {};
}
if (typeof WXP2P.APP == "undefined") {
  WXP2P.APP = {};
}
WXP2P.Const = {
    AJAX_SIGN: window['_AJAXSIGN_']
};
WXP2P.Const.ErrorCode = {
    UNLOGIN: 40002,
    NET_ERROR: 50001,
    SERVER_BUSY: 50002
};
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


WXP2P.APP.request = function(requrl, success_callback, errorCallback, opt_method, opt_data) {
    var senddata = (typeof opt_data == 'object') ? opt_data : {};
    return $.ajax({
        url: requrl,
        type: opt_method == "post" ? "post" : "get",
        dataType: 'json',
        data: senddata,
        success: function(json) {
            if (!json) {
                errorCallback.call(null, '服务器忙,请稍后重试', WXP2P.Const.ErrorCode.SERVER_BUSY);
                return;
            }
            if (json['errno'] != 0) {
                errorCallback.call(null, json['error'], json['errno']);
                return;
            }
            success_callback.call(null, json['data']);
        },
        error: function() {
            errorCallback.call(null, '您的网络貌似不给力,请稍后重试', WXP2P.Const.ErrorCode.NET_ERROR);
        }
    });
};

// loadMore
WXP2P.UI.P2PLoadMore = function(container, loadmorepanel, urlbase, opt_page, opt_type, opt_numperpage) {
    var pThis = this;
    console.log(this);
    pThis.urlbase = urlbase;
    pThis.page = opt_page > 1 ? opt_page : 1;
    pThis.loading = false;
    pThis.ajaxType = opt_type == "post" ? "post" : "get";
    pThis.numPerPage = opt_numperpage > 1 ? opt_numperpage : 10;
    pThis.container = container;
    pThis.loadmorepanel = loadmorepanel;
    $(pThis.loadmorepanel).find("a").bind("click", function(event){
      if($(event.target).parent().hasClass('load_more')){
        pThis.loadNextPage(1);
      }else{
        pThis.loadNextPage();
      }

    });
};

WXP2P.UI.P2PLoadMore.prototype.loadNextPage = function(type) {
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
            pThis.processData(rawData , type);
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
        $(pThis.container).html('<div class="no_coupon"><p>这里空空如也</p></div>');
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
        var discountType = $("#load_txt").data("discounttype")
        if(pThis.loadmorepanel.id == "load_txt"){
            pThis.loadmorepanel.innerHTML = "仅显示最近30天内的优惠券";
        }else{
            if (listDataItem.length < pThis.numPerPage) {
                pThis.loadmorepanel.innerHTML= '没有更多了';  
            } else {
                pThis.loadmorepanel.innerHTML = '<a href="javascript:void(0);">点击加载更多</a>';
                $(pThis.loadmorepanel).find("a").unbind('click').bind("click", function(){
                  pThis.loadNextPage();
                });
            }
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
    console.log(this);
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

WXP2P.APP.triggerScheme = function(scheme) {
    var iframe = document.createElement("iframe");
    iframe.src= scheme;
    iframe.style.display = "none";
    document.body.appendChild(iframe);
};


WXP2P.UI.popup = function(content,title,isConfirmBtn,isCancelBtn,confirmBtnTxt,cancelBtnTxt,confirmCallback,cancelCallback,popupClass){
    var div = document.createElement("div");
    var html = ""
    html += '<div class="ui_mask_box ' + popupClass + '">';
    html += '<div class="ui_popup">';
    if(title){
        html += '<div class="title">' + title + '</div>';
    }
    html += '<div class="content">' + content + '</div>';
    html += '<div class="btn_box">';
    if(isConfirmBtn){
        html += '<div class="confirm_btn">' + confirmBtnTxt + '</div>';
    }
    if(isCancelBtn){
        html += '<div class="cancel_btn">' + cancelBtnTxt + '</div>';
    }
    html += '</div>';
    html += '</div>';
    html += '</div>';
    div.innerHTML = html;
    document.body.appendChild(div);
    $(".confirm_btn").click(function(){
        if(confirmCallback){
            confirmCallback.call(null,"",$(".ui_mask_box").hide())
        } else {
            $(".ui_mask_box").hide();
        }
    })
    $(".cancel_btn").click(function(){
        if(cancelCallback){
            cancelCallback.call(null,"", $(".ui_mask_box").hide())
        } else {
            $(".ui_mask_box").hide();
        }
    })
}

WXP2P.UI.pullDownRefresh = function(refreshContainer,refreshText,refreshTextBox,callbackFun){
    var _element = refreshContainer,
      _refreshText = refreshText
      _startPos = 0,
      _transitionHeight = 0;

    _element.addEventListener('touchstart', function(e) {
        _startPos = e.touches[0].pageY;
        _element.style.position = 'relative';
        _element.style.transition = 'transform 0s';
    }, false);

    _element.addEventListener('touchmove', function(e) {
        refreshTextBox.style.display="block";
        _transitionHeight = e.touches[0].pageY - _startPos;
        if (_transitionHeight > 100) {
            _refreshText.innerText = '正在加载';
            _element.style.transform = 'translateY('+_transitionHeight+'px)';
            callbackFun.call(null)
        }
    }, false);

    _element.addEventListener('touchend', function(e) {
        _element.style.transition = 'transform 0.5s ease 1s';
        _element.style.transform = 'translateY(0px)';
        refreshTextBox.style.display="none";
    }, false);
}

var _text = document.querySelector('.refreshText'),
    _container = document.getElementById('refreshContainer');
WXP2P.APP.pullUpRefresh = function(scrolEle,_text,_container) {
    alert("a");
    var pThis = this;
    console.log(this)
    pThis.scrolEle = scrolEle;
    console.log(pThis);
    // this.getScrollTop();
    pThis.scrolEle.onscroll = function() {
        if (pThis.getScrollTop() + pThis.getClientHeight() == pThis.getScrollHeight()) {
            // _text.innerText = '加载中...';
            pThis.throttle(pThis.fetchData);
        }
    };
}

WXP2P.APP.pullUpRefresh.prototype.getScrollTop = function(){
    var pThis = this;
    var scrollTop = 0; 
    if (document.documentElement && document.documentElement.scrollTop) { 
        scrollTop = document.documentElement.scrollTop; 
    } else if (document.body) { 
        scrollTop = document.body.scrollTop; 
    } 
    return scrollTop;
}

WXP2P.APP.pullUpRefresh.prototype.getClientHeight = function(){
    var clientHeight = 0; 
    if (document.body.clientHeight && document.documentElement.clientHeight) { 
        clientHeight = Math.min(document.body.clientHeight, document.documentElement.clientHeight); 
    } 
    else { 
        clientHeight = Math.max(document.body.clientHeight, document.documentElement.clientHeight); 
    } 
    return clientHeight;
}

WXP2P.APP.pullUpRefresh.prototype.getScrollHeight = function(){
    return Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
}

WXP2P.APP.pullUpRefresh.prototype.throttle = function(method, context){
    clearTimeout(method.tId);
    method.tId = setTimeout(function(){
    method.call(context);
    }, 300);
}

WXP2P.APP.pullUpRefresh.prototype.fetchData = function() {
    var pThis =this;
    setTimeout(function() {
        pThis._container.insertAdjacentHTML('beforeend', '<li>new add...</li>');
    }, 1000);
}

$(function(){
  // 进入页面拼写proto
    WXP2P.APP.batchWarpAnchorSchema('body a');
    var meta = "<meta name=\"format-detection\" content=\"telephone=no\" />";
    $("head").append(meta);
});
