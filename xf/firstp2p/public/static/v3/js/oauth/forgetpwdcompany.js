(function($) {
    $(function() {
        (function() {
            $('#forgetpwdmobile_form').validator({
                isNormalSubmit: false,
                messages: {},
                fields: {
                    'captcha': '图形验证码:required;captcha;checkAjax',
                    'username': '用户名:required;username;checkAjax'
                },
                rules: {
                    captcha: [/^\d{4,10}$/, '验证码不正确'],
                    username: [/^([A-Za-z])[\w-]{3,19}$/, '用户名格式不正确'],
                    checkAjax: function(element) {
                        var dataJson = {},
                            msgData = null,
                            msgObj = null,
                            reqType = '',
                            $el = $(element);
                        if ($el.attr("name") == 'username') {
                            dataJson.ajax = 1;
                            dataJson.username = $('#username').val();
                            reqType = "post";
                        } else if ($el.attr("name") == 'captcha') {
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
                        if ($el.attr("name") == 'username') {
                            msgObj = msgData.info;
                            if (msgObj.code == 2) {
                                return msgObj.msg;
                            } else if (msgObj.code == -1) {
                                $.weeboxs.open(msgObj.msg, {
                                    boxid: null,
                                    contentType: 'text',
                                    showButton: false,
                                    showOk: false,
                                    title: '提示',
                                    width: 441,
                                    type: 'wee',
                                });
                            }
                        } else if ($el.attr("name") == 'captcha') {
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
                            "captcha": $("#captcha").val(),
                            "username": $("#username").val(),
                            "token": $("#token").val(),
                            "token_id": $("#token_id").val(),
                        },
                        url: '/enterprise/ForgetPwd',
                        success: function(data) {
                            if (data.info.code == '-1') {

                            }

                            if (data.info.code == '0') {
                                location.href = data.jump;
                            }
                        },
                        error: function() {}
                    });


                }

            });

            // 图形验证码逻辑
            var img_captcha = $("#img_captcha");
            img_captcha.bind("click", function() {
                img_captcha.attr('src', '/verify.php?w=93&h=34&rb=0&rand=' + new Date().valueOf());
            });
            // img_captcha.attr('src', '/verify.php?w=93&h=34&rb=0&rand=' + new Date().valueOf());

        })();
    });
})(jQuery);