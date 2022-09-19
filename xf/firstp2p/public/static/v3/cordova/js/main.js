

(function (window, jQuery, undefind) {

    //初始化网络状态
    var appline = true;
    //设备正常联网
    function onOnline() {
        appline = true;
        console.log("网络连接重新");
        window.plugins.toast.show('网络断开，请重新连接!', 'long', 'center');
    }
    //网络未连接
    function onOffline() {
        appline = false;
        //showLoading(1200);
        console.log("网络断开");
        window.plugins.toast.show('网络断开，请重新连接!', 'long', 'center');
        //location.href = "../error.html";
    }
    //获取当前设备连接的网络
    function checkConnection() {
        var networkState = navigator.connection.type;
        var states = {};
        states[Connection.UNKNOWN] = 'Unknown connection';
        states[Connection.ETHERNET] = 'Ethernet connection';
        states[Connection.WIFI] = 'WiFi connection';
        states[Connection.CELL_2G] = 'Cell 2G connection';
        states[Connection.CELL_3G] = 'Cell 3G connection';
        states[Connection.CELL_4G] = 'Cell 4G connection';
        states[Connection.CELL] = 'Cell generic connection';
        states[Connection.NONE] = 'No network connection';
        console.log('Connection type: ' + states[networkState]);
    }

    function onDeviceReady() {
        //alert("sss");
        console.log("begin");
        //强制不能横屏landscape
        screen.lockOrientation('portrait');
        //网络事件
        document.addEventListener("offline", onOffline, false);
        document.addEventListener("online", onOnline, false);
        checkConnection();
        //设置本地提醒标签
        //cordova.plugins.notification.badge.set(1);

        //调用锁屏
        //ongesture();
        //本地open重定向
        //cordova.InAppBrowser.open = window.open();
        if (appline) {
            console.log("网络通畅");
            //window.location = "http://m.firstp2p.com";
            //setTimeout(function () {
            //    //闪屏动画
            //    navigator.splashscreen.hide();
            //}, 2000);
            navigator.splashscreen.hide();
        } else {
            showLoading(1200);
            location.href = "error.html";
        }
        //onMessage();
        //解锁
        console.log("end");
    }
    document.addEventListener("deviceready", onDeviceReady, false);

    //加载中动画
    function showLoading(time) {
        var _time = 1200;
        if (time) {
            _time = time;
        }
        //随机一个数
        //var _mNum = parseInt(Math.random()*(7-1)+1);
        //加载中动画
        jQuery(".fakeLoader").fakeLoader({
            timeToHide: _time,
            zIndex: 999,
            spinner: "spinner8",
            bgColor: "rgba(0,0,0,0)"
        });
        centerLoader();
        console.log("active beigin");
    }

    //动画效果居中
    function centerLoader() {

        var winW = jQuery(window).width();
        var winH = jQuery(window).height();

        var spinnerW = jQuery('.fl').outerWidth();
        var spinnerH = jQuery('.fl').outerHeight();

        jQuery('.fl').css({
            'position': 'absolute',
            'left': (winW / 2) - (spinnerW / 2),
            'top': (winH / 2) - (spinnerH / 2)
        });

    }

    function onMessage() {
        // Schedule notification for tomorrow to remember about the meeting
        cordova.plugins.notification.local.schedule({
            id: 10,
            title: "Meeting in 15 minutes!",
            text: "Jour fixe Produktionsbesprechung",
            at: tomorrow_at_8_45_am,
            data: { meetingId: "#123FG8" }
        });

        // Join BBM Meeting when user has clicked on the notification
        cordova.plugins.notification.local.on("click", function (notification) {
            if (notification.id == 10) {
                joinMeeting(notification.data.meetingId);
            }
        });

        // Notification has reached its trigger time (Tomorrow at 8:45 AM)
        cordova.plugins.notification.local.on("trigger", function (notification) {
            if (notification.id != 10)
                return;

            // After 10 minutes update notification's title
            setTimeout(function () {
                cordova.plugins.notification.local.update({
                    id: 10,
                    title: "Meeting in 5 minutes!"
                });
            }, 600000);
        });
    }

    var ref;

    //模态窗口打开外链
    function openUrl(url) {
        //url = "http://www.baidu.com";
        if (!cordova.InAppBrowser) {
            return;
        }
        // toolbar=yes 仅iOS有效,提供关闭、返回、前进三个按钮
        // toolbarposition=top/bottom 仅iOS有效,决定toolbar的位置
       // closebuttoncaption=关闭 仅iOS有效
       ref= cordova.InAppBrowser.open(url, '_blank', 'location=no,toolbar=yes,toolbarposition=top,closebuttoncaption=关闭');
        //cordova.InAppBrowser.open(url, '_blank', 'toolbar=yes');
    }


    function openlock() {
        //手势密码解锁
        var opt = {
            chooseType: 3,
            width: 400,
            height: 400,
            container: "lockdom",
        }
        var lock = new H5lock(opt, function () {
            jQuery("#lockdom").fadeOut();
            jQuery(".p_index").removeClass("hidden_ov");
        });
        lock.init();
        jQuery("#lockdom").fadeIn();
        jQuery(".p_index").addClass("hidden_ov");
    }


    /***************** 获取cookie *********************/
    function _getCookie(c_name) {
        if (document.cookie.length > 0) {
            var c_start = document.cookie.indexOf(c_name + "=");
            if (c_start != -1) {
                c_start = c_start + c_name.length + 1;
                var c_end = document.cookie.indexOf(";", c_start);
                if (c_end == -1) c_end = document.cookie.length;
                return unescape(document.cookie.substring(c_start, c_end));
            }
        }
        return "";
    }
    /***************** 设置cookie *********************/

    function _setCookie(name, value, time, opt_domain) {
        var str1 = time.substring(1, time.length) * 1;
        var str2 = time.substring(0, 1);
        var str_time;
        if (str2 == "s") {
            str_time= str1 * 1000;
        }
        else if (str2 == "h") {
            str_time = str1 * 60 * 60 * 1000;
        }
        else if (str2 == "d") {
            str_time = str1 * 24 * 60 * 60 * 1000;
        }

        var exp = new Date();
        var domainStr = opt_domain ? ';domain=' + opt_domain : '';
        exp.setTime(exp.getTime() + str_time * 1);
        document.cookie = name + "=" + escape(value) + domainStr + ";path=/" + ";expires=" + exp.toGMTString();
    }
    /***************** 删除cookie *********************/
    function _delCookie(name) {
        var exp = new Date();
        exp.setTime(exp.getTime() - 1);
        var cval = _getCookie(name);
        if (cval != null)
            document.cookie = name + "=" + cval + ";expires=" + exp.toGMTString();
    }

    //客服电话点击回调
    function onConfirm(buttonIndex) {
        if (buttonIndex == 2) {
            window.location.href = 'tel://400-890-9888';
        }
    };




    //页面加载完全
    jQuery(function () {
        //增加快速点击事件
        //FastClick.attach(document.body);
        //所有页面的body中追加元素
        if (jQuery(".fakeLoader").length <= 0) {
            jQuery("body").append("<div class=\"fakeLoader\"></div>");
        }
        showLoading(10);
        jQuery(".fakeLoader").fadeOut();
        //页面中所有点击事件拦截
        jQuery(document).on("click", "button,a,img", function () {
            //判断是否有href
            var thisHref = $(this).attr("href");
            if (!thisHref) return true;
            if (thisHref.indexOf("http") != -1&&!$(this).hasClass("noOpenUrl")) {
                openUrl(thisHref);
                return false;
            } else if (thisHref.indexOf("javascript")!=-1) {
                console.log("URL====" + thisHref);
                return true;
            } else if (thisHref.indexOf("400")!=-1) {
                navigator.notification.confirm(
                    '如遇问题，请联系客服',
                    onConfirm, // callback to invoke with index of button pressed
                    '400-890-9888', // title
                    ['取消', '呼叫'] // buttonLabels
                );
                return false;
            }else if (thisHref.indexOf("/account/logout") != -1 || thisHref.indexOf("/account/register") != -1 || thisHref.indexOf("/account/login")!=-1) {
                _delCookie("wapapp_login_succ");
                if (thisHref.indexOf("/account/register") != -1) {
                    window.localStorage.removeItem('passwordxx');
                    window.localStorage.removeItem('chooseType');
                }
                return true;
            } else if (thisHref.indexOf("chunyuyisheng") != -1) {
                 openUrl("http://www.chunyuyisheng.com/");
                return false;
            } else {
                //ref.close();
                jQuery(".fakeLoader").fadeIn();
                console.log("URL====" + thisHref);
                return true;
            }
        });

        //页面点击分享
        jQuery(document).on("click", "#app_inviteBtn", function () {
            var options;
            if (_getCookie("WAPAPP") == "android") {
                options = {
                    message: _weixinshareTitle,
                    subject: 'Message and link',
                    url: _weixinshareLink,
                    chooserTitle: '邀请好友'
                };
            } else {
                options = {
                    message: _weixinshareTitle,
                    subject: 'the subject',
                    files: [_weixinshareImg, ''],
                    url: _weixinshareLink,
                    chooserTitle: '邀请好友'
                };
            }
            var onSuccess = function (result) {
                console.log("Share completed? " + result.completed);
                console.log("Shared to app: " + result.app);
            }

            var onError = function (msg) {
                console.log("Sharing failed with message: " + msg);
            }
            window.plugins.socialsharing.shareWithOptions(options, onSuccess, onError);
            //window.plugins.socialsharing.share(_weixinshareTitle, _weixinshareImg, 'http://www.baidu.com/img/bdlogo.gif', _weixinshareLink);
        });

        jQuery(".fakeLoader").bind("click", function () {
            jQuery(".fakeLoader").fadeOut();
        });

        //是否已经登陆回调
        function islogin() {
            var _bool = false;
            $.ajax({
                url: '/status/isLogin',
                type: 'post',
                async: false,
                dataType: 'json',
                success: function (data) {
                    if (data == 1) {
                        _bool = true;
                    } else {
                        _bool = false;
                    }
                },
                error: function () {
                    _bool = false;
                }

            });
            return _bool;

        }

        //islogin();
        //判断是否调用手势密码
        if (_getCookie("wapapp_login_succ") == "") {
            //ajax请求是否已登陆接口
            if (islogin()) {
                _setCookie("wapapp_login_succ", "0", "h2");
                // openlock();
            } else {
                _delCookie("wapapp_login_succ");
            }

        }
        //重置密码
        jQuery("#updatePassword").bind("click", function() {
            //_delCookie("wapapp_login_succ");
            _delCookie("wapapp_login_succ");
            window.localStorage.removeItem('passwordxx');
            window.localStorage.removeItem('chooseType');
            location.href = "/account/logout";
        });

        //所以页面返回
        var app_lock = false;
        jQuery("body").on("click tap", ".JS_back", function () {
            jQuery(".fakeLoader").fadeIn();
            if (app_lock) {
                setTimeout(function() {
                    app_lock = false;
                }, 1000);
                return false;
            } else {
                app_lock = true;
                window.history.back();
            }
        });

        //设置登陆成功后cookie
        //判断session是否有值
        var cookie_PHPSESSID = _getCookie("PHPSESSID");
        window.localStorage.setItem('PHPSESSID', cookie_PHPSESSID);

        var sto_PHPSESSID = window.localStorage.getItem('PHPSESSID');
        if (sto_PHPSESSID!="") {
            _setCookie("PHPSESSID", sto_PHPSESSID, "d30");
        }




    });


})(window, jQuery);
