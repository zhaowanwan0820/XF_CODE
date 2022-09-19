$(function () {
    // 老版本直接回首页
    if (isLatestVersion == false) {
        WXP2P.UI.popup("教师节快乐~请先升级到最新版APP(4.9.5),再制作贺卡。", "提示", true, false, "确认", "", function () {
            WXP2P.APP.triggerScheme("firstp2p://api?type=native&name=home");
        });
    }
    WXP2P.APP.triggerScheme("firstp2p://api?type=rightbtn&title=");
    $("#pageContainer").css({
        "background-image": "url(" + backgroungArray[0] + ")",
        "background-repeat": "no-repeat",
        "background-position": "center",
        "background-size": "100% 100%",
    })
    var mySwiper = new Swiper('.swiper-container', {
        direction: 'horizontal',
        loop: true,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        }
    })
});


$(function () {
    function changeImage(num) {
        $("#pageContainer").css({
            "background-image": "url(" + backgroungArray[num] + ")",
            "background-repeat": "no-repeat",
            "background-position": "center",
            "background-size": "100% 100%",
        })
    }

    var num, length;
    num = 0;
    length = backgroungArray.length;
    $("#refresh").click(function () {
        num++
        if (num < length) {
            changeImage(num)
        } else {
            num = 0
            changeImage(num)
        }
    })
});

$(function () {
    var p2pBrowser = (function () {
        var u = navigator.userAgent
        return {
            app: /wx/i.test(u),
            androidApp: /wxAndroid/i.test(u),
            iosApp: /wxiOS/i.test(u)
        }
    })()
    $("#share").click(function () {
        var activeImg = $(".swiper-slide-active img")[0].src,
            transformDOM = $("#pageContainer");
        $(".arrow-left").hide();
        $(".arrow-right").hide();
        $("#share").hide();
        $("#refresh").hide();
        $("#qrCode").show();
        $("#swiperContainer").hide();
        $(".trimImg").show();
        $(".trimImg").attr('src', activeImg);
        var canvas = document.createElement('canvas'),
            ctx = canvas.getContext('2d');
        canvas.width = transformDOM.width() * 2;
        canvas.height = transformDOM.height() * 2;
        ctx.scale(2, 2);
        html2canvas(document.getElementById('pageContainer'), {
            canvas: canvas,
            allowTaint: false, //允许污染
            // taintTest: true, //在渲染前测试图片(没整明白有啥用)
            useCORS: true, //使用跨域(当allowTaint为true时这段代码没什么用)
            onrendered: function (canvas) {
                transformDOM.hide();
                $("#imageContainer").removeClass("hide");
                // canvas.style.width = transformDOM.width() + 'px';
                // canvas.style.height = transformDOM.height() + 'px';
                base64Img = canvas.toDataURL('image/jpeg'); //将图片转为base64
                $("canvas").hide();
                $("#html2canvas").attr("src", base64Img);
                base64Img = base64Img.toString().substring(base64Img.indexOf(",") + 1); //截取base64以便上传
                // 判断当前UA环境
                if (!p2pBrowser.app) {
                    $("#browserShare").show()
                } else {
                    $.ajax({
                        url: "/valentine/uploadImage",
                        type: "post",
                        dataType: "json",
                        data: {
                            'imgString': base64Img
                        },
                        beforeSend: function () {
                            $("#loadingContainer").show();
                        },
                        success: function (res) {
                            $("#loadingContainer").hide();
                            if (res.errno == 0) {
                                WXP2P.APP.triggerScheme('bonus://api?sharetype=image&face=' +
                                    encodeURIComponent(res.data.imgUrl));
                            } else {
                                P2PWAP.ui.toast("制作频繁，请稍后再试哦！")
                            }
                        },
                        error: function (error) {
                            P2PWAP.ui.toast("网络异常，请稍后再试哦！")
                        }
                    })
                }
            }
        });
    })
});

