$(function(){
    var winW = document.documentElement.clientWidth;
    var fontSize = winW / 750 * 100;
    document.documentElement.style.fontSize = fontSize + "px";

    /*控制原生title右侧隐藏title传空按钮4.7.3*/
    window.location.href = "firstp2p://api?type=rightbtn&title=";

    // 链接配置
    var p2pBrowser = (function () {
        var u = navigator.userAgent
        return {
            wx: /MicroMessenger/i.test(u),
            webkit: /AppleWebKit/i.test(u),
            gecko: /gecko/i.test(u),
            ios: /\(i[^;]+;( U;)? CPU.+Mac OS X/.test(u),
            android: /android/i.test(u),
            iPhone: /iPhone/i.test(u),
            iPad: /iPad/i.test(u),
            app: /wx/i.test(u),
            androidApp: /wxAndroid/i.test(u),
            iosApp: /wxiOS/i.test(u)
        }
    })()

    //分享按钮
    if (p2pBrowser.app) {
        $(".share").attr("href", "bonus://api?title=" + encodeURIComponent("叮咚，您有一条新消息未点击！挑战排行榜，得惊喜好礼，速来围观！") + "&content=" + encodeURIComponent("参与排行榜活动，有机会获得惊喜好礼！精彩不容错过呦，快来参与吧！") + "&face=" + encodeURIComponent("http://event.ncfwx.com/upload/image/20181025/14-16-_20181025141617.png") + "&url=" +
            encodeURIComponent(location.href));
    }
    // 微信分享遮罩
    if (p2pBrowser.wx) {
        $(".share").click(function () {
            $(".ui_mask").show();
        })
    }
    $(".ui_mask").click(function () {
        $(this).hide();
    })










})