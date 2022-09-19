(function($) {
    $(function() {
        (function() {
            var editForm = $('#forgetpwdmobile_form').validator({
                isNormalSubmit: false,
                messages: {},
                fields: {
                    'mobile': '注册手机号:required;mobile;checkAjax',
                    'captcha': '图形验证码:required;captcha;checkAjax'
                },
                rules: {
                    mobile: [/^1[3456789]\d{9}$/, '注册手机号格式不正确'],
                    captcha: [/^\d{4,10}$/, '验证码不正确'],
                    checkAjax: function(element) {
                        var dataJson = {},
                            msgData = null,
                            msgObj = null,
                            reqType = '',
                            $el = $(element);
                        if($el.attr("name") == 'mobile'){
                           dataJson.ajax = 1;
                           dataJson.phone = $el.val();
                           reqType = "post"
                        } else if($el.attr("name") == 'captcha') {
                            dataJson.captcha = $el.val();
                            reqType = "get"
                        }
                        $.ajax({
                            type: reqType,
                            data: dataJson,
                            url: $el.data("url"),
                            dataType: "json",
                            async: false,
                            success: function(data) {
                                msgData = data;
                            }
                        });
                        if($el.attr("name") == 'mobile'){
                            msgObj = msgData.info;
                            if (msgObj.code != "1") {
                                if(msgObj.code == '-1'){
                                    location.href = msgData.jump + '?error=error_jump';
                                } else {
                                    return msgObj.msg;
                                }
                            }
                        } else if($el.attr("name") == 'captcha'){
                            msgObj = msgData;
                            if (msgObj.code != 0) {
                                return msgObj.msg;
                            }
                        }

                    }
                },
                valid: function(form) {
                    $.ajax({
                        type: "post",
                        dataType: "json",
                        data: {
                            "phone": $("#mobile").val(),
                            "captcha": $("#captcha").val(),
                            "token" : $("#token").val(),
                            "token_id" : $("#token_id").val(),
                        },
                        url: $(form).attr("action"),
                        success: function(data) {
                            if (data.info.code == '0'||data.info.code == '3') {
                                $("#jump_url").val(data.jump);
                                Firstp2p.getMsg({
                                    dataVerrifyCode:{
                                        "code": $(".ui_send_msg #pop_code").val(),
                                        "phone" : $("#mobile").val(),
                                        "from":"forget_pwd"
                                    },
                                    callbackpost: function(){
                                        window.location.href = $("#jump_url").val();
                                    }
                                });
                            } else if (data.info.code == '-1'){
                                location.href = data.jump + '?error=error_jump';
                            }
                        },
                        error: function(){
                        }
                    });


                }

            }).data('validator');

            // 图形验证码逻辑
            var img_captcha = $("#img_captcha");
            img_captcha.click(function() {
                img_captcha.attr('src', '/verify.php?w=93&h=34&rb=0&rand=' + new Date().valueOf());
            });
            img_captcha.attr('src', '/verify.php?w=93&h=34&rb=0&rand=' + new Date().valueOf());

        })();
    });
})(jQuery);