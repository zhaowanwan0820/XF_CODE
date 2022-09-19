$(function() {
    //表单验证
    var _validateReqest = false;
    $('#validateMcodeForm').validator({
        rules: {
            mobile: [/^1[3456789]\d{9}$/, '手机号格式不正确'],
            vcode: [/^\d{6}$/, '请输入6位数字'],
        },
        fields: {
            mobile: "手机号: required;mobile;",
            vcode: "验证码: required;vcode;"
        },
        valid: function() {
            if (_validateReqest) return false;
            _validateReqest = true;
            $.ajax({
                url: '/payment/yeepayConfirmBind',
                type: "post",
                data: {
                    'vCode': $('#vcode').val()
                },
                dataType: "json",
                success: function(res) {
                    console.log(res);
                    if (res.status == 0) {
                        window.location.href = '/payment/yeepayConfirmPay';
                    } else {
                        paymentAlert('提示', res.msg);
                        _validateReqest = false;
                    }
                },
                error: function() {
                    paymentAlert('提示', '网络错误！');
                    _validateReqest = false;
                }
            });
            return false;
        }
    });

    var $vCode = $('#vcode_btn');
    var _vcodeTimer = null;

    function _updateMcodeBtn() {
        var timeRemained = 60;
        $vCode.addClass('btn-gray-h34').attr('disabled', 'disabled').val(timeRemained + '秒后重新发送');
        _vcodeTimer = setInterval(function() {
            timeRemained--;
            if (timeRemained < 1) {
                _clearCodeBtn();
            } else {
                $vCode.val(timeRemained + '秒后重新发送');
            }
        }, 1000);
    }

    function _clearCodeBtn() {
        if (_vcodeTimer == null) return;
        clearInterval(_vcodeTimer);
        _vcodeTimer = null;
        $vCode.removeClass('btn-gray-h34').removeAttr('disabled').val('重新发送');
    }

    function paymentAlert(title, txt, cls, okfn) {
        return Firstp2p.alert({
            title: title,
            text: '<div class="f16">' + txt + '</div>',
            ok: function(dialog) {
                dialog.close();
                okfn && okfn.call(this);
            },
            width: 435,
            showButton: true,
            boxclass: cls || ''
        });
    }

    $vCode.click(function() {
        var $t = $(this);
        var $mobile = $('#mobile');
        if (!$mobile.isValid()) return;
        $vCode.addClass('btn-gray-h34').attr('disabled', 'disabled').val('正在发送');
        $.ajax({
            url: '/payment/yeepayValidateCode',
            type: "post",
            data: {
                'mobile': $mobile.val()
            },
            dataType: "json",
            success: function(res) {
                console.log(res);
                if (res.status == 0) {
                    _updateMcodeBtn();
                } else if (res.status == 1) {
                        window.location.href = '/payment/yeepayConfirmPay';
                } else {
                    paymentAlert('提示', res.msg, '', function() {
                        $vCode.removeClass('btn-gray-h34').removeAttr('disabled').val('重新发送');
                    });
                }
            },
            error: function() {
                paymentAlert('提示', '网络错误！', '', function() {
                    $vCode.removeClass('btn-gray-h34').removeAttr('disabled').val('重新发送');
                });

            }
        });
    });
});
