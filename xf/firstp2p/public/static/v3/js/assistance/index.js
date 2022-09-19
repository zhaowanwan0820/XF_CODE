$(function () {
    var winW = document.documentElement.clientWidth;
    var fontSize = winW / 375 * 100;
    document.documentElement.style.fontSize = fontSize + "px";

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

    if (p2pBrowser.app) {
        /*控制原生title右侧隐藏title传空按钮4.7.3*/
        window.location.href = "firstp2p://api?type=rightbtn&title=";
        $(".openCon").addClass("isApp");
        //分享按钮链接
        var shareIcon = $("#shareIcon").val();
        var shareUrl = $("#shareUrl").val();
        var shareContent = $("#shareContent").val();
        var shareTitle = $("#shareTitle").val();
        $(".share").attr("href", "bonus://api?title=" + encodeURIComponent(shareTitle) + "&content=" + encodeURIComponent(shareContent) + "&face=" + encodeURIComponent(shareIcon) + "&url=" + encodeURIComponent(shareUrl));
    } else {
        // 微信里点击给好友发红包分享
        $(".share").click(function () {
            $(".wxShareMask").show();
            $("body").css({ position: "fixed", width: "100%" });
        })
        $(".wxShareMask").click(function () {
            $(this).hide();
            $("body").css({ position: "relative" });
        })
    }

    var role = $("#role").val();
    var pageType = $("#pageType").val();
    var acquireType = $("#acquireType").val();
    var newerBonus = $("#newerBonus").val();

    if ($(".zhuliMask").length > 0) {
        $('body').css({ 'position': 'fixed', 'width': '100%' });
    } else {
        $('body').css({ 'position': 'relative' });
    }

    if (location.href.indexOf('isOpen')!=-1){
        if (pageType == 4) {//获得奖励
            if (newerBonus > 0) {//新用户奖励
                zhuge.track("新春助力红包_助力人点开启", {
                    "获得奖励_新用户奖励": "获得奖励_新用户奖励"
                });
            }
            if (acquireType > 0) {//有发起资格，在白名单
                zhuge.track("新春助力红包_助力人点开启", {
                    "获得奖励_白名单": "获得奖励_白名单"
                });
            } else {
                zhuge.track("新春助力红包_助力人点开启", {
                    "获得奖励_非白名单": "获得奖励_非白名单"
                });
            }
        } else if (pageType == 5 || pageType == 6 || pageType == 8) {//未获得奖励
            if (acquireType > 0) { //有发起资格，在白名单
                zhuge.track("新春助力红包_助力人点开启", {
                    "未获得奖励_白名单": "未获得奖励_白名单"
                });
            } else {
                zhuge.track("新春助力红包_助力人点开启", {
                    "未获得奖励_非白名单": "未获得奖励_非白名单"
                });
            }
        }
    }

    // 点击open按钮
    var openUrl = $("#openUrl").val();
    var sn = $("#sn").val();
    var eventId = $("#eventId").val();
    $(".openBtn").click(function () {
        if (role < 1) { //发起人
            zhuge.track("新春助力红包_发起人点开启");
        }
        $.ajax({
            "url": openUrl,
            "dataType": "json",
            "type": "post",
            "data": {"sn":sn,"eventId":eventId},
            "success": function (response) {
                if (response.code == 0) {
                    var href = location.href;
                    if (href.indexOf('?') == -1){
                        href = href +'?isOpen=1';
                    }else{
                        href = href + '&isOpen=1';
                    }
                    location.replace(href);
                } else if (response.code == -1) {//未登录
                    location.replace(response.data);
                }  else {
                    showToast(response.msg);
                }
            },
            "fail": function (msg) {
                showToast(msg);
            }
        })
    })

    //toast提示
    function showToast(tip) {
        var toastTip = $('#site_toastTip');
        if (toastTip.size() == 0) {
            toastTip = $('<div class="site_toastTip" id="site_toastTip"><div class="textTip"></div></div>').appendTo(
                document.body);
        }
        var textTip = toastTip.find('.textTip');
        textTip.text(tip);
        toastTip.show();
        setTimeout(function () {
            toastTip.hide();
        }, 5000);
    }

    // 诸葛统计
    if (pageType == 1) { //待发起
        zhuge.track("新春助力红包_发起人进入开启红包页");
    } else if (pageType == 2) { //发起人红包详情页
        zhuge.track("新春助力红包_进入红包详情页面");
    }
    if(role==1){//助力页
        zhuge.track("新春助力红包_进入助力红包待开页");
    }
    // 点击给好友发红包
    $(".share").click(function () {
        zhuge.track("新春助力红包_点击“给好友发红包”");
    })
    // 点击看看我的专属红包
    $(".lookSelfBtn").click(function () {
        zhuge.track("新春助力红包_点击“看看我的专属红包”");
    });
    // 点击进入APP查看
    $(".lookBtn").click(function () {
        zhuge.track("新春助力红包_点击“进入APP查看”");
    });
    // 点击活动规则
    $(".ruleBtn").click(function () {
        zhuge.track("新春助力红包_查看活动规则");
    });
})
