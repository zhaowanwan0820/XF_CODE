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
    this.ele.innerHTML = "<span style=\"display: inline-block;font-size:14px;color:white;max-width:250px;min-width:100px;word-break:break-word;padding:10px;background:rgba(0,0,0,0.7);border-radius:5px;\">" + this.msgHtml + "</span>";
    this.ele.setAttribute("style", "z-index:999;position:fixed;width:100%;text-align:center;bottom:50%;-webkit-transition:opacity linear 0.5s;opacity:0;");
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

P2PWAP.ui.showNoticeDialog = function(title,content,ui_mask_height) {
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


    // 关闭
    $(div).find('.dialog_btn').bind("click" , function(event) {
        P2PWAP.ui.removeModalView(div);
        $(div).remove();
    });

    P2PWAP.ui.addModalView(div);
    if(ui_mask_height){
        $(".ui_mask").height(ui_mask_height);
    }else{
        $(".ui_mask").height($(window).height());
    }
    function dialogTopFix(){
        var $dialog = $(div).find('.ui_dialog');
        $dialog.css('margin-top',$dialog.height() * (-1/2) + 'px');
    }
    dialogTopFix();
    $(window).resize(function(){
        dialogTopFix();
    });
    var myScroll_id = new IScroll($(div).find('.dialog_text')[0],{
        scrollbars: true,
        interactiveScrollbars: true,
        shrinkScrollbars: 'scale',
        fadeScrollbars: true
    });
};

P2PWAP.ui.showShareView = function(title, content, url, img, opt_wxshowonly){
    var shareWrapDiv = $('<div class="ui-share-view" style="z-index:300;"></div>');
    shareWrapDiv.append('<div class="share_cover"></div><div class="share_box"></div>');

    var sinalink = "http://service.weibo.com/share/share.php?title=" + encodeURIComponent(content) + "&url=" + encodeURIComponent(url) + "&pic=" + encodeURIComponent(img);
    var qqlink = "http://share.v.t.qq.com/index.php?c=share&a=index&title=" + encodeURIComponent(content) + "&url=" + encodeURIComponent(url) + "&pic=" + encodeURIComponent(img);
    var qzonelink = "http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=" + encodeURIComponent(url) + "&title=" + encodeURIComponent(content) + "&pics=" + encodeURIComponent(img);
    var doubanlink = "http://www.douban.com/share/service?href=" + encodeURIComponent(url) + "&text=" + encodeURIComponent(content) + "&image=" + encodeURIComponent(img);
    var wxshowonly = !!opt_wxshowonly;

    if (P2PWAP.util.wxJudge()) {
        if (wxshowonly) {
            shareWrapDiv.append('<div class="wx_tip"></div>');
            shareWrapDiv.find('.wx_tip').show();
            if (window['wx'] && (typeof wx.onMenuShareAppMessage == 'function') && (typeof wx.onMenuShareTimeline == 'function')){
                wx.onMenuShareAppMessage({
                    title: title,
                    desc: content,
                    link: url, // 分享链接
                    imgUrl: img
                });
                wx.onMenuShareTimeline({
                    title: title,
                    link: url,
                    imgUrl: img
                });
            }
            shareWrapDiv.find('.wx_tip').click(function(event) {
                shareWrapDiv.remove();
            });
        } else {
            shareWrapDiv.append('<div class="wx_tip"></div>');
            shareWrapDiv.find('.share_box').append('<div class="line"><div class="part"><a class="wx_hy">微信好友</a></div><div class="part"><a class="wx_pyq">朋友圈</a></div><div class="part"><a class="button_tsina" target="_blank" href="' + sinalink +'">新浪微博</a></div><div class="part"><a class="button_tqq" target="_blank" href="' + qqlink + '">腾讯微博</a></div></div><div class="line"><div class="part"><a class="button_qzone" target="_blank" href="' + qzonelink + '">QQ空间</a></div><div class="part"><a class="button_douban" target="_blank" href="' + doubanlink +'">豆瓣</a></div><div class="part"></div><div class="part"></div></div>');
            shareWrapDiv.find('.wx_hy,.wx_pyq').click(function(event) {
                shareWrapDiv.find('.wx_tip').show();
                if (window['wx'] && (typeof wx.onMenuShareAppMessage == 'function') && (typeof wx.onMenuShareTimeline == 'function')){
                    wx.onMenuShareAppMessage({
                        title: title,
                        desc: content,
                        link: url, // 分享链接
                        imgUrl: img
                    });
                    wx.onMenuShareTimeline({
                        title: title,
                        link: url,
                        imgUrl: img
                    });
                }
            });
            shareWrapDiv.find('.wx_tip').click(function(event) {
                $(this).hide();
            });
        }
    } else {
        shareWrapDiv.find('.share_box').append('<div class="line"><div class="part"><a class="button_tsina" target="_blank" href="' + sinalink +'">新浪微博</a></div><div class="part"><a class="button_tqq" target="_blank" href="' + qqlink + '">腾讯微博</a></div><div class="part"><a class="button_qzone" target="_blank" href="' + qzonelink + '">QQ空间</a></div><div class="part"><a class="button_douban" target="_blank" href="' + doubanlink +'">豆瓣</a></div></div>');
    }
    shareWrapDiv.find('.share_cover').click(function(event) {
        P2PWAP.ui.removeModalView(shareWrapDiv[0]);
        shareWrapDiv.find('.wx_hy,.wx_pyq,.wx_tip,.share_cover').unbind('click');
        shareWrapDiv.remove();
    });
    P2PWAP.ui.addModalView(shareWrapDiv[0]);
    $('body').append(shareWrapDiv);
};

P2PWAP.ui.ModalHeight_ = window.innerHeight;
P2PWAP.ui.ModalViewEls_ = [];
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
P2PWAP.ui.alert =function (parObj) {
    function noop() {return true;}
    var defaultPar={
        'type':'alert',
        'title':'提示',
        'text':'这是默认提示信息',
        'okFn':noop,
        'cancelFn':noop,
        'hook':noop
    };
    var option=$.extend({},defaultPar,parObj);
    var html='<div class="ui-alertPop">'+
        '    <div class="inner">'+
        '	<div class="title"></div>'+
        '	<div class="content">'+
        '	    <p class="text"></p>'+
        '	</div>'+
        '	<div class="btnBox">'+
        '	    <input type="button" value="确认" class="okBtn" />'+
        '	    <input type="button" value="取消" class="cancelBtn" />'+
        '	</div>'+
        '    </div>'+
        '</div>';
    var dom=$(html).appendTo('body');
    $('.title',dom).text(option.title);
    $('.content .text',dom).text(option.text);
    $('.btnBox .okBtn',dom).on('click',function () {
        if (option.okFn(dom)!==false){
            dom.remove();
        }
    });
    if (option.type=="alert"){//如果是确认弹层
        $('.btnBox .cancelBtn',dom).hide();
    }
    $('.btnBox .cancelBtn',dom).on('click',function () {
        if (option.cancelFn(dom)!==false){
            dom.remove();
        }
    });
    option.hook(dom);
    dom.show();
};

/*********************** UtIl ***************************/

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

P2PWAP.util.checkMobile = function(val) {
    return /^0?(13[0-9]|15[0-9]|17[0-9]|18[0-9]|14[57])[0-9]{8}$/.test(val);
};

P2PWAP.util.checkPassword = function(val) {
    return /^[^\s]{6,20}$/.test(val);
   // return /^.{6,20}$/.test(val);
};

P2PWAP.util.checkCaptcha = function(val) {
    return /^\d{4,10}$/.test(val);
};

P2PWAP.util.checkMcode = function(val) {
    return /^\d{6}$/.test(val);
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
p2popen = {};
p2popen.util = {};
p2popen.util.getNextUniqueIdCount_ = 0;
p2popen.util.getNextUniqueId = function () {
    p2popen.util.getNextUniqueIdCount_++;
    return "_p2popen_idp_" + p2popen.util.getNextUniqueIdCount_;
};
p2popen.advtpl = {};
p2popen.advtpl.tpls = {
    banner: ''
};

p2popen.advtpl.wap_Banner = function (data, opt_container) {
    if (!opt_container) {
        var conid = p2popen.util.getNextUniqueId();
        document.write("<div id='" + conid + "'></div>");
        opt_container = document.getElementById(conid);
    }
    var container = opt_container;
    $(container).append(p2popen.advtpl.tpls['banner']);
    var imgLen = data.length;
    if (!(imgLen > 0)) {
        $(container).hide();
        return;
    }
    var html = '<div class="banner"><div class="JS-wapadvbanner focus">';
    for (var i = 0; i < data.length; i++) {
        html += '<div class="slideDiv">';
        html += '<a class="noOpenUrl" target="_blank" href="' + data[i].link + '">';
        html += '<img lazyload="' + data[i].imageurl + '">';
        html += '</a></div>';
    }
    html += '</div></div>';
    $(container).append(html);
    $(function(){
        $(container).find('.JS-wapadvbanner').slider({
            autoPlay: true,
            imgZoom: false,
            arrow: false
        });
    });
};
