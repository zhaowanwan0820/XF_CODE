$(function () {

    var token=$('#tokenHidden').val();
    var loanBtn=$('#loanBtn');

    loanBtn.on('click',function () {
        if ($(this).hasClass('noValid') || $(this).hasClass('pending')){
            return;
        }else{
            lockBtn(true);
            $.ajax({
                "url":'/speedloan/repayConfirm',
                "method":'post',
                "data":{
                    "token":token,
                    "id":$('#idHidden').val(),
                    "repay_amount":$('#repayAmountHidden').val()
                },
                "success":function (resultVal) {
                    var data=resultVal.data;
                    if (resultVal.errno!=0){
                        new ToastPop({
                            "content":resultVal.error,
                            "clickHide":true,
                            "delayHideTime":2500
                        })
                    }else{
                        location.assign('/speedloan/repayResult?token='+data.token+'&orderId='+data.orderId)
                    }
                    lockBtn(false);
                },
                "error":function () {
                    new ToastPop({
                        "content":'服务器端异常，请稍后重试',
                        "clickHide":true,
                        "delayHideTime":2500
                    });
                    lockBtn(false);
                }
            });
        }
    });

    function lockBtn(flag) {
        if(flag){
            loanBtn.addClass('pending').text('请求发送中……');
        }else{
            loanBtn.removeClass('pending').text('立即还款');
        }
    }

});
