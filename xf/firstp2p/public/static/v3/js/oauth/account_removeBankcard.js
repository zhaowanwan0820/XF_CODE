$(function() {
    // 表单验证
    $('#resetBank_form').validator({
        rules: {
            validN: [/^\d{6}$/, '请填写6位数字验证码']
        },
        fields: {
            code: "验证码: required;validN;"
        },
        valid: function(form) {
            //验证短信验证码
            var resetBankPop=$.weeboxs.open('<div class="rmBankInner"><i></i>确定解除绑定银行卡？</div>', {
                boxclass:'rmBankConf',
                contentType: 'html',
                showButton: true,
                showCancel: true,
                showOk: true,
                title: '解除绑定银行卡',
                width: 435,
                type: 'wee',
                onclose: null,
                onok:function(){
                    $.ajax({
                        url: '/account/doResetBankcard',
                        data: {
                            'code':validNum.val()
                        },
                        type: "post",
                        dataType: "json",
                        beforeSend: function() {},
                        success: function(result) {
                            if(result.status==1){
                                location.assign('/account');
                            }else{
                                resetBankPop.close();
                                var alertObj=Firstp2p.alert({
                                    'title':'错误提示',
                                    'text':result.info,
                                    'ok':function(){
                                        location.assign('/account');
                                    },
                                    'close':function(){
                                        location.assign('/account');
                                    }
                                })
                            }
                        },
                        error: function(data) {

                        }
                    });

                }
            });
        }
    });
    //控制获取验证码
    var msglock = false;
    var button = $("#bt");
    var errorSpan = $("#code-err");
    var validNum=$('#validNum');

    function setProperty() {
        bgGray();
        _reset();
    };
    //正在获取状态
    var bgGray = function() {
        button.addClass("btn-send-gray");
        button.val("正在获取中...");
        button.attr("disabled", "disabled");
    };
    //显示错误提示，和错误状态
    var _set = function(msg) {
        html = '<span class="msg-wrap n-error" role="alert"><span class="n-icon"></span><span class="n-msg">' + msg + '</span></span>';
        errorSpan.css('display', 'block');
        errorSpan.find('.n-msg').html(html);
        $('#validNum').addClass("n-invalid");
    };
    //去掉错误提示
    var _reset = function() {
        errorSpan.css('display', 'none');
        errorSpan.find('.n-msg').html('');
        $('#validNum').removeClass("n-invalid");
    };
    var timer = null;

    function updateTimeLabel(duration) {
        var timeRemained = duration;
        timer = setInterval(function() {
            button.val('重新发送(' + timeRemained + ')');
            timeRemained -= 1;
            if (timeRemained == -1) {
                clearInterval(timer);
                msglock = false;
                button.val('重新发送').removeAttr('disabled').removeClass("btn-send-gray");
            }
        }, 1000);
    }
    var callback = function(data) {
        if (!msglock) {
            updateTimeLabel(60);
            msglock = true;
        }
        if (data.code != 1) {
            _set(data.message);
        } else {
            _reset();
        }
    }

    // 获取并验证手机验证码
    var getMsgCode = function() {
        var getcodeUrl = '/user/EMCode',
            data = {
                "is_edit": 1,
                "req":'resetbankcard'
            };
        setProperty();
        $.ajax({
            url: getcodeUrl,
            data: data,
            type: "post",
            dataType: "json",
            beforeSend: function() {},
            success: function(result) {
                callback(result);
            },
            error: function() {

            }
        });
    }
    $("#bt").bind('click', function() {
        getMsgCode();
    });
});