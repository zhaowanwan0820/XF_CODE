$(function(){
    function weixinFacility() {
        var userAgentString = window.navigator ? window.navigator.userAgent : "";
        var weixinreg = /MicroMessenger/i;
        return weixinreg.test(userAgentString);
    };
    var wHeight = $(window).height();
    $('body').append('<div class="share_wrap"><div class="share_cover"></div><div class="share_box"></div></div>');
        if(weixinFacility()){
          $('.share_wrap').append('<div class="wx_tip"></div>');
          $('.share_box').append('<div class="line"><div class="part"><a class="wx_hy">微信好友</a></div><div class="part"><a class="wx_pyq">朋友圈</a></div><div class="part"><a class="jiathis_button_tsina">新浪微博</a></div><div class="part"><a class="jiathis_button_tqq">腾讯微博</a></div></div><div class="line"><div class="part"><a class="jiathis_button_qzone">QQ空间</a></div><div class="part"><a class="jiathis_button_douban">豆瓣</a></div><div class="part"></div><div class="part"></div></div>');
          $('.wx_hy,.wx_pyq').click(function(event) {
            $('.share_icon ,.ui_mask').show();
            $('.share_wrap').removeClass('show');
          });
          $('.share_icon').click(function(event) {
            $('.ui_mask , .share_icon').hide();
          });
        } else {
          $('.share_box').append('<div class="line"><div class="part"><a class="jiathis_button_tsina">新浪微博</a></div><div class="part"><a class="jiathis_button_tqq">腾讯微博</a></div><div class="part"><a class="jiathis_button_qzone">QQ空间</a></div><div class="part"><a class="jiathis_button_douban">豆瓣</a></div></div>');
        }
        $('.JS-share_btn').click(function(event) {
          $('.share_wrap').addClass('show');
        });
        $('.share_wrap .share_cover').click(function(event) {
          $('.share_wrap').removeClass('show');
    });
    _weixinshareLink = 'http://m.firstp2p.com/';
    _weixinshareTitle = 'title';
    _weixinshareImg = 'img';
    _weixinshareContent = 'content';
    var title = "title";
        var url = "url";
        url = "";
        var img = "img";
    var sinalink = "http://service.weibo.com/share/share.php?title=" + title + "&url=" + url + "&pic=" + img;
    var qqlink = "http://share.v.t.qq.com/index.php?c=share&a=index&title=" + title + "&url=" + url + "&pic=" + img;
    var qzonelink = "http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=" + url + "&title=" + title + "&pics=" + img;
    var doubanlink = "http://www.douban.com/share/service?href=" + url + "&text=" + title + "&image=" + img;
    $(".jiathis_button_tsina").attr("href", sinalink).attr("target", "_blank");
    $(".jiathis_button_tqq").attr("href", qqlink).attr("target", "_blank");
    $(".jiathis_button_qzone").attr("href", qzonelink).attr("target", "_blank");
    $(".jiathis_button_douban").attr("href", doubanlink).attr("target", "_blank");
})