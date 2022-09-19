$(function () {
    var token = $("#token").val();
    var bankType = $("#bankType").val();

    // 判断是否显示协议
    if (bankType == 'ICBC') {
        $(".statement").css("display","block");
    }

    // 验证码校验
    $(".codeInput").keyup(function (e) {
        e.stopPropagation();
        $(this).val($(this).val().replace(/\D/g, ''));
    })

    // 验证码倒计时
    var timer = null;
    function updateTimeLabel(duration) {
        var timeRemained = duration;
        timer = setInterval(function () {
            $(".getCode").html(timeRemained + 's').addClass("disabled");
            timeRemained -= 1;
            if (timeRemained == -1) {
                clearInterval(timer);
                $(".getCode").html('重新发送').removeClass('disabled');
            }
        }, 1000);
    }
    updateTimeLabel(60);

    // 完成按钮点亮逻辑
    $(".codeInput").bind("input",function () {
        var val = $(this).val();
        if (val.length > 0) {
            $(".sure").removeClass("disabled");
        } else {
            $(".sure").addClass("disabled");
        }
    })

    // 重新获取验证码
    $(".getCode").click(function () {
        if (!$(this).hasClass("disabled")) {
            $.ajax({
                url: '/banksign/Resend',
                data: {
                    token: token
                },
                type: "get",
                dataType: "json",
                beforeSend: function () { },
                success: function (result) {
                    $(".codeInput").val('');
                    if (result.status == 0) {
                        updateTimeLabel(60);
                    } else {
                        P2PWAP.ui.showErrorTip(result.err_msg);
                    }
                },
                error: function (msg) {
                    P2PWAP.ui.showErrorTip(msg);
                }
            });
        }
    })

    //点击完成按钮
    $(".sure").click(function () {
        if (!$(this).hasClass("disabled")) {
            var vcode = $(".codeInput").val();
            $.ajax({
                url: '/banksign/Confirm',
                data: {
                    token: token,
                    vcode: vcode
                },
                type: "get",
                dataType: "json",
                beforeSend: function () { },
                success: function (result) {
                    if (result.status == 0) {
                        var returnUrl = $("#returnUrl").val();
                        var outOrderId = $("#outOrderId").val();
                        if (returnUrl) {
                            if (returnUrl.indexOf('?')==-1){
                                returnUrl = returnUrl + '?out_order_id=' + outOrderId;
                            }else{
                                returnUrl = returnUrl + '&out_order_id=' + outOrderId;
                            }
                            location.href = returnUrl;
                        }
                    } else {
                        P2PWAP.ui.showErrorTip(result.err_msg);
                    }
                },
                error: function (msg) {
                    P2PWAP.ui.showErrorTip(msg);
                }
            });
        }
    })
})