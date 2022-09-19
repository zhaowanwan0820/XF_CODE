$(function(){
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
    
    var hostName = (location.hostname.split(".")[0] == "www" ? "" : (location.hostname.split(".")[0]+'.')) + "api." + location.hostname.split(".")[1] + "." + location.hostname.split(".")[2];

    var lineHostName = location.hostname.split(".")[0] == "www" ? "m.ncfwx.com" : location.hostname.split(".")[0] + ".m.ncfwxlocal.com";
    var lineHostName2 = location.hostname.split(".")[0] == "www" ? "m.firstp2p.cn" : location.hostname.split(".")[0] + ".m.firstp2plocal.cn";

    var sxyUrl = location.protocol + "//" + hostName + "/deal/reserveEntry";
    var sxyUrlLine = location.protocol + "//" + lineHostName + "/deal/reserve_entry";
    var inviteUrlLine = location.protocol + "//" + lineHostName + "/account/fcode";
    var targetUrlLine = location.protocol + "//" + lineHostName2 + "/deal/zxlist";
    var xfdUrlLine = location.protocol + "//" + lineHostName2 + "/deal/zxlist?&isp2pindex=1&product_class_type=5";
    var gylUrlLine = location.protocol + "//" + lineHostName2 + "/deal/zxlist?&isp2pindex=1&product_class_type=223";

    $(".JS_xfd_btn").attr("href", xfdUrlLine);
    $(".JS_gyl_btn").attr("href", gylUrlLine);

    if (p2pBrowser.app) {
        $(".JS_sxy_btn").attr("href", "firstp2p://api?type=webview&url=" + encodeURIComponent(sxyUrl));
        $(".JS_invite_btn").attr("href", "firstp2p://api?type=native&name=invite");
        $(".JS_target_btn").attr("href", "firstp2p://api?type=native&name=other&pageno=2");
        $(".share").attr("href", "bonus://api?title=" + encodeURIComponent("5动未来，有信有你！网信五周年过生日喽！") + "&content=" + encodeURIComponent("全民理财日+用户特权日，high翻全场！") + "&face=" + encodeURIComponent("http://event.ncfwx.com/upload/image/20180706/18-49-face.jpg") + "&url=" +
            encodeURIComponent(location.href));
    } else {
        $(".JS_sxy_btn").attr("href", sxyUrlLine);
        $(".JS_invite_btn").attr("href", inviteUrlLine);
        $(".JS_target_btn").attr("href", targetUrlLine);
    }

    // 微信分享遮罩
    if (p2pBrowser.wx) {
        $(".shareBtn").click(function(){
            $(".ui_mask").show();
        })
    }
    $(".ui_mask").click(function(){
        $(this).hide();
    })
})