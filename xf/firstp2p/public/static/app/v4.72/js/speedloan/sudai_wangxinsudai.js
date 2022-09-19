
$(function () {
    // var secondCount=1;
    // var toastPop=new ToastPop({
    //     "content":'<p class="text">正在为您测算额度</p><br><p class="text">'+secondCount+'S</p>',
    //     "clickHide":true,
    // });
    // setInterval(function () {
    //     secondCount++;
    //     toastPop.updateCont('<p class="text">正在为您测算额度</p><br><p class="text">'+secondCount+'S</p>')
    // },1000);
    var token=$('#tokenHidden').val();
    $('#mainBox').height($(window).height()).removeClass('posFixed');
    $('#errorToast').on('click',function () {
        new ToastPop({
            "content":'<p class="text">额度暂不可用</p>',
            "clickHide":true,
            "delayHideTime":2500
        })
    });
    triggerScheme('firstp2p://api?type=rightbtn&title=刷新&callback=refreshPage');

    //服务时间判断
    // $('#toLoan,#onlineRepay').on('click',function () {
    $('#onlineRepay').on('click',function () {
        var reqSource=$(this).data('reqSource');
        var _this=this;
        if ($(this).data('serveTimeLock')){
            return;
        }
        $(this).data('serveTimeLock',true);
        checkServeTime(reqSource,token,function (resultVal) {
            var href=$(_this).data('hrefText');
            triggerScheme(href)
        }).always(function () {
            $(_this).data('serveTimeLock',false);
        });
    });
    $('#toLoan').on('click',function () {
        new ToastPop({
            "content":'<p class="text">由于系统升级暂不能为您提供放款服务，如有疑问请咨询客服。</p>',
            "clickHide":true,
            "delayHideTime":2500
        })
    });
});