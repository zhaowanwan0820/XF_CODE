var body = document.body;
/*轮播*/
var imgLen = $(".JS_product_banner .img_item").length
if (imgLen > 1) {
    var mySwiper = new Swiper('.swiper-container', {
        direction: 'horizontal',
        loop: true,
        autoplay: {
            delay: 2000,
            stopOnLastSlide: false,
            /*如果设置为true，当切换到最后一个slide时停止自动切换.（loop模式下无效）。*/
            disableOnInteraction: false,
            /*用户操作swiper之后，是否禁止autoplay。默认为true：停止。*/
        }
    })
}
/*end*/

/*商品和详情的双向切换*/
var topDetails = document.getElementsByClassName('top_details')[0];
var topProduct = document.getElementsByClassName('top_product')[0];
var detailsPosition = document.getElementById('details').offsetTop - 90;
var detail = document.getElementsByClassName('detail')[0];
topProduct.onclick = function () { //点击商品
    window.scrollTo(document.documentElement.scrollTop, 0);
    setTimeout(function () {
        $(".top_switch").find("li").removeClass("top_anchor");
        $(".top_product").addClass("top_anchor");
    }, 100);
}
topDetails.onclick = function () { //点击详情
    window.scrollTo(document.documentElement.scrollTop, detailsPosition);
    setTimeout(function () {
        $(".top_switch").find("li").removeClass("top_anchor");
        $(".top_details").addClass("top_anchor");
    }, 100);
}


window.onscroll = function () {
    if ($(window).scrollTop() <= detailsPosition) {
        topProduct.className = "top_product top_anchor";
        topDetails.className = "top_details";
    } else {
        topProduct.className = "top_product";
        topDetails.className = "top_details top_anchor";
    }
}
/*商品和详情的双向切换*/
/*进度条*/
if (status == "1") {
    var progressBar = document.getElementsByClassName('JS_progressBar')[0];
    progressBar.style.width = productSchedule + "%";
}
/*进度条*/
/*夺宝码*/
var jsTicketView = document.getElementsByClassName("JS_ticket_view")[0];
var popupBabyNum = document.getElementsByClassName("popup_baby_num")[0];
if (jsTicketView) (
    jsTicketView.onclick = function () {
        popupBabyNum.style.display = 'block';
    }
)
/*夺宝码*/
/*参与记录*/
var timeStamp = document.getElementsByClassName("timeStamp");
var proTimeCode = document.getElementsByClassName('pro_time_code');
var timeNum = 0;
if (timeStamp[0]) {
    if (timeStamp.length < 5) {
        timeNum = timeStamp.length;
    } else {
        timeNum = 5;
    }
    for (var i = 0; i < timeStamp.length; i++) {
        time = parseInt(timeStamp[i].innerText);
        var date = new Date(time);
        Y = date.getFullYear();
        M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1);
        D = (date.getDate() < 10 ? '0' + (date.getDate()) : date.getDate());
        h = (date.getHours() < 10 ? '0' + (date.getHours()) : date.getHours());
        m = (date.getMinutes() < 10 ? '0' + (date.getMinutes()) : date.getMinutes());
        s = (date.getSeconds() < 10 ? '0' + (date.getSeconds()) : date.getSeconds());
        if(time % 1000 < 10){
            ms = '00' + time % 1000;
        }else if(time % 1000 < 100){
            ms = '0' + time % 1000;
        }else{
            ms = time % 1000;
        }
        var x = Y + '-' + M + "-" + D + " " + h + ":" + m + ":" + s + "." + ms;
        var timeCodeInn = " "+ h+m+s+ms;
        timeStamp[i].innerText = x;
        proTimeCode[i].innerText = timeCodeInn;

    }
}
/*参与记录*/
/*取消按钮*/
var proCancel = document.getElementsByClassName("pro_cancel");
var snatchPopWrap = document.getElementsByClassName("snatch_pop_wrap");
for (var m = 0; m < 6; m++) {
    (function (n) {
        if(proCancel[n]){
            proCancel[n].onclick = function () {
                snatchPopWrap[n].style.display = 'none';
                location.reload();
            }
        }
    })(m);
}

/*点击开始夺宝*/
if (status == "1") {
    var treasureBtn = document.getElementsByClassName("JS_treasure")[0];
    var proPopup = document.getElementsByClassName("pro_popup")[0];
    var popupLayer = document.getElementsByClassName("popup_layer")[0];
    var jsSub = document.getElementsByClassName('JS_sub')[0];
    var jsClose = document.getElementsByClassName('JS_close')[0];
    var popupChance = document.getElementsByClassName('popup_chance')[0];
    var popupCandy = document.getElementsByClassName('popup_candy')[0];
    var popupSuccess = document.getElementsByClassName('popup_success')[0];
    var popupFail = document.getElementsByClassName('popup_fail')[0];
    treasureBtn.onclick = function () {
        if((invite == 2 && presentCount<=0) || todayAvailableCount < 1){
            popupChance.style.display = 'block';
        } else if (candy < 1) {
            popupCandy.style.display = 'block';
        } else {
            proPopup.style.display = 'block';
        }
        zhuge.track("信宝夺宝-商品-夺宝按钮")
    }
    popupLayer.onclick = function () {
        proPopup.style.display = 'none';
    }
    jsClose.onclick = function () {
        proPopup.style.display = 'none';
        jsNum.value = 1;
    }

    /*点击开始夺宝-投信宝数*/
    var jsNum = document.getElementsByClassName("JS_num")[0];
    $('.sub').tap(function () {
        if (jsNum.value > 1) {
                jsNum.value--;
            }
    })
    $('.add').tap(function () {
        if (jsNum.value < +availableCount) {
            jsNum.value++;
        }
    })
    /*点击开始夺宝-投信宝数*/
    /*点击夺宝确定按钮*/
    jsSub.onclick = function () {
        if (+periodCodesCount >= +maxCount) {
            WXP2P.UI.showErrorTip("您已超出您对此商品的最大可投数！");
            return;
        } else if (parseInt(jsNum.value, 10) != jsNum.value) {
            WXP2P.UI.showErrorTip("所投信宝数必须为整数");
            return;
        } else if (jsNum.value < 1) {
            WXP2P.UI.showErrorTip("所投信宝数不能小于1");
            return;
        } else if (jsNum.value > +availableCount) {
            WXP2P.UI.showErrorTip("超出可投限制");
            return;
        } else {
            $.ajax({
                url: "/candysnatch/SnatchAction",
                type: "post",
                data: {
                    "token": token,
                    "periodId": periodId,
                    "amount": jsNum.value
                },
                dataType: "json",
                beforeSend: function () {
                    $(jsSub).attr("disabled", true)
                },
                success: function (data) {
                    proPopup.style.display = 'none';
                    if (data.errno == 0) {
                        popupSuccess.style.display = 'block';
                        var jsDataCode = document.getElementsByClassName("JS_data_code");
                        var const_num = document.getElementsByClassName("const_num")[0];
                        const_num.innerText = jsNum.value;
                        jsDataCode[0].innerText = data.data.codes[0];
                        jsDataCode[1].innerText = data.data.codes[data.data.codes.length - 1];
                        $(jsSub).attr("disabled", false);
                    } else {
                        var failMsg = document.getElementsByClassName("fail_msg")[0];
                        failMsg.innerText = data.error;
                        popupFail.style.display = 'block';
                        $(jsSub).attr("disabled", false)
                    }
                }
            });
        }
    }
    }
//跳转
$('.pro_rule').attr('href', function () {
    return $(this).attr('href') + encodeURIComponent($(this).data('pram'));
})
$('.orders').attr('href', function () {
    return $(this).attr('href') + encodeURIComponent(location.origin + $(this).data('pram'));
})
if(navigator.userAgent.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/)) {
    $(".hei_fixed").attr("style", "padding-top:0.3rem;");
    $(".pro_details_top").attr("style", "padding-top:0.3rem;");
}
$(".pro_popup , .snatch_pop_wrap").bind("touchmove", function (event) {
    event.preventDefault();
});
$(".popup_scroll").bind("touchmove", function () {
    event.stopPropagation();
})
