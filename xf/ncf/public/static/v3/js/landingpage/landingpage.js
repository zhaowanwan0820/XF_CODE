;
(function($) {
    $(function() {
        var tmpData = {};
        //密码明暗文切换
        $("#register").on("click", ".pwd-show", function() {
            $("#password").attr("type", "text");
            $(this).toggleClass('pwd-show').toggleClass('pwd-hide');
        });

        $("#register").on("click", ".pwd-hide", function() {
            $("#password").attr("type", "password");
            $(this).toggleClass('pwd-show').toggleClass('pwd-hide');
        });

        if (typeof Firstp2p == 'undefined') {
            Firstp2p = {};
        };


        Firstp2p.getMsg = function(obj) {
            var quhao = obj.data ? obj.data.info.mobile_code : "86";
            quhao = quhao.replace(/[^0-9]/, "");
            quhao = quhao == "86" ? "" : (quhao + "-");
            var phone = $("#mobile").val();
            var phonelabel = quhao + phone;
            phonelabel = phonelabel.replace(/([0-9\-]*)([0-9]{4})([0-9]{4})/, function(_0, _1, _2, _3) {
                return _1 + "****" + _3
            });
            var settings = $.extend(true, {
                    data: {},
                    title: '填写短信验证码',
                    html: '<div class="wee-send">\
                        <div class="send-input">\
                            <div class="error-box" style="visibility:hidden;">\
                                <div class="error-wrap">\
                                    <div class="e-text" id="j_e_text" style="width: 305px;">请填写6位数字验证码</div>\
                                </div>\
                            </div>\
                            <p>已向&nbsp;<span class="color_green">' + phonelabel + ' </span>&nbsp;发送验证短信</p>\
                            <input type="text" class="ipt-txt w150" id="pop_code" placeholder="短信验证码" maxlength="10" value="">\
                            <input type="button"  class="reg-sprite btn-blue-h34 btn-gray-h34 j_sendMessage" value="发送">\
                        </div>\
                        </div>',
                    msgUrl: '/user/MCode',
                    draggable: true,
                    postUrl: '/user/CheckPwdCode',
                    callback: function(data) {
                        if (!msglock) {
                            updateTimeLabel(60);
                            msglock = true;
                        }
                        if (!!data && data.code != 1) {
                            _set(data.message);
                        } else {
                            _reset();
                        }
                    },
                    callbackpost: function(data, settings) {
                        if (data.info.code === "1") {
                            $.weeboxs.close();
                            window.location.href = data.jump;
                        } else {
                            settings.showError();
                            $("#j_e_text").html(data.info.msg);
                        }
                    }
                }, obj),
                errorSpan = "",
                status = "",
                timer = null,
                msglock = false,
                setProperty = function() {
                    var button = $(".ui_send_msg .j_sendMessage");
                    bgGray();
                    _reset();
                },
                bgGray = function() {
                    var button = $(".ui_send_msg .j_sendMessage");
                    button.addClass("btn-gray-h34");
                    button.val("正在获取中...");
                    button.attr("disabled", "disabled");
                },
                _set = function(msg) {
                    var errorSpan = $(".ui_send_msg .error-box");
                    errorSpan.css('visibility', 'visible');
                    errorSpan.find('.e-text').html(msg);
                    $(".ui_send_msg .ipt-txt").addClass("err-shadow");
                },
                _reset = function() {
                    var errorSpan = $(".ui_send_msg .error-box");
                    errorSpan.css('visibility', 'hidden');
                    errorSpan.find('.e-text').html('');
                },
                updateTimeLabel = function(duration) {
                    var timeRemained = duration;
                    var button = $(".ui_send_msg .j_sendMessage");
                    timer = setInterval(function() {
                        button.val(timeRemained + '秒后重新发送');
                        timeRemained -= 1;
                        if (timeRemained == -1) {
                            clearInterval(timer);
                            msglock = false;
                            button.val('重新发送').removeAttr('disabled').removeClass("btn-gray-h34");
                        }
                    }, 1000);
                },
                getCode = function() {
                    var data = settings.dataGetCode;
                    var getcodeUrl = settings.msgUrl;
                    $.ajax({
                        url: getcodeUrl,
                        type: "post",
                        data: data,
                        dataType: "json",
                        beforeSend: function() {},
                        success: function(result) {
                            settings.instanceForm.holdSubmit(false);
                            if (result.code == -5) {
                                $("#register").validator("showMsg", "#mobile", {
                                    type: "error",
                                    msg: result.message
                                });
                                $("#captchaImg").trigger('click');
                                $("#captcha").val("");
                            } else if (result.code == -9 || result.code == -10 || result.code == -15) {
                                $("#register").validator("showMsg", "#captcha", {
                                    type: "error",
                                    msg: result.message
                                });
                                $("#captchaImg").trigger('click');
                                $("#captcha").val("");
                            } else {
                                $.weeboxs.open(settings.html, {
                                    boxid: null,
                                    boxclass: 'ui_send_msg',
                                    contentType: 'text',
                                    showButton: true,
                                    showOk: true,
                                    okBtnName: '确定',
                                    draggable: settings.draggable,
                                    showCancel: false,
                                    title: settings.title,
                                    width: 463,
                                    height: 125,
                                    type: 'wee',
                                    onopen: function() {

                                    },
                                    onclose: function() {
                                        !!settings.onclose && settings.onclose();
                                    },
                                    onok: function() {
                                        var $text = $(".ui_send_msg .error-box").find('.e-text'),
                                            cn_code = null;
                                        if (!settings.showError) {
                                            settings.showError = function() {
                                                $(".ui_send_msg .error-box").css({
                                                    'display': 'block',
                                                    'visibility': 'visible'
                                                });
                                                $(".ui_send_msg .ipt-txt").addClass("err-shadow");
                                            };
                                        }


                                        if (!/^\d{6}$/.test($(".ui_send_msg #pop_code").val())) {
                                            settings.showError();
                                            $text.html("请填写6位数字验证码");
                                            return;
                                        }
                                        settings.dataVerrifyCode.code = $(".ui_send_msg #pop_code").val();
                                        cn_code = window.parent.location.href.match(/cn=([^&]+)/i);
                                        if (!!cn_code) {
                                            settings.dataVerrifyCode.invite = cn_code[1];
                                        }
                                        //console.log(settings.dataVerrifyCode);
                                        $.ajax({
                                            url: settings.postUrl,
                                            type: "post",
                                            data: settings.dataVerrifyCode,
                                            dataType: "json",
                                            beforeSend: function() {
                                                // $text.html("正在提交，请稍候...");
                                            },
                                            success: function(data) {
                                                settings.callbackpost(data, settings);
                                            },
                                            error: function() {
                                                settings.showError();
                                                $text.html("服务器错误，请重新再试！");
                                            }
                                        });
                                        // $.weeboxs.close();
                                    }
                                });

                                setProperty();
                                settings.callback(result);
                                // 点击“重新发送”按钮获取短信验证码
                                $('.j_sendMessage').bind("click", function() {
                                    var data = settings.dataGetCode;
                                    var getcodeUrl = settings.msgUrl;
                                    if (!msglock) {
                                        bgGray();
                                        updateTimeLabel(60);
                                        msglock = true;
                                        $.ajax({
                                            url: getcodeUrl,
                                            type: "post",
                                            data: data,
                                            dataType: "json",
                                            beforeSend: function() {},
                                            success: function(data) {
                                                if (!!data && data.code != 1) {
                                                    _set(data.message);
                                                } else {
                                                    _reset();
                                                }
                                            },
                                            error: function() {

                                            }
                                        });

                                    }
                                    
                                });
                            }


                        },
                        error: function() {

                        }
                    });
                };
            getCode();



        };

        Firstp2p.regValidate = function(options) {
            var that = this,
                defaultSettings = {},
                settings = $.extend(true, defaultSettings, options),
                data = settings.data,
                $obj = !!settings.dom ? settings.dom : $(".j-form");

            $obj.validator({
                fields: settings.fields,
                rules: settings.rules,
                messages: settings.messages,
                valid: function(form) {
                    var $f = $(form),
                        me = this;
                    me.holdSubmit();
                    var mobile = $("#mobile").val();
                    var password = $("#password").val();
                    var captcha = $("#captcha").val();
                    var token_id = $("#token_id").val();
                    var token = $("#token").val();
                    tmpData = {
                        "mobile": mobile,
                        "password": password,
                        "captcha": captcha,
                        "token_id": token_id,
                        "token": token
                    };
                    Firstp2p.getMsg({
                        draggable: false,
                        dataGetCode: {
                            "mobile": tmpData.mobile,
                            "type": "1",
                            "captcha": tmpData.captcha,
                            "token": tmpData.token,
                            "token_id": tmpData.token_id
                        },
                        dataVerrifyCode: {
                            "mobile": tmpData.mobile,
                            "password": tmpData.password,
                            "isAjax": 1
                        },
                        instanceForm: me,
                        msgUrl: '/user/MCode',
                        postUrl: '/user/DoRegister?type=h5',
                        callbackpost: function(data, settings) {
                            me.holdSubmit(false);
                            if (data.errorCode === 0) {
                                $.weeboxs.close();
                                window.parent.location.href = "http://www.firstp2p.com/" + data.redirect;

                            } else {
                                settings.showError();
                                $("#j_e_text").html(data.errorMsg);
                            }
                        },
                        onclose: function() {
                            me.holdSubmit(false);
                            $("#captchaImg").trigger('click');
                            $(".changeCaptcha").next().find(".n-msg").text("");
                            $("#captcha").val("");
                        }
                    });
                }
            });
        }


        Firstp2p.regValidate({
            dom: $("#register"),
            messages: {
                'checkAgree': '请同意注册协议'
            },
            fields: {
                'captcha': '验证码:required;checkCaptcha;',
                'agree': 'checkAgree'
            },
            rules: {
                checkCaptcha: function(element) {
                    var dataJson = {
                            "captcha": element.value
                        },
                        $ele = $(element),
                        type = !!$ele.data("method") ? $ele.data("method") : "get";
                    return $.ajax({
                        type: type,
                        data: dataJson,
                        url: $ele.data("url"),
                        dataType: "json",
                        success: function(data) {
                            if (data.code != 0) {
                                $("#captchaImg").trigger('click');
                            }
                        }
                    });
                },
                checkpass: function(element) {
                    var dataJson = {
                            "pwd": element.value
                        },
                        $ele = $(element);
                    return $.ajax({
                        type: "post",
                        data: dataJson,
                        url: "/user/PasswordCheck",
                        dataType: "json",
                        success: function(data) {
                            if (data.errorCode == 0) {
                                $("#anquan").show();
                            } else {
                                $("#anquan").hide();
                            }
                        }
                    });
                },
                checkAgree: function(element) {
                    var $ele = $(element);
                    if ($ele[0].checked != true) {
                        return false;
                    }
                }
            }
        });
        $("#register").on("click", "#captchaImg", function() {
            this.src = $(this).data("src") + new Date().valueOf();
        });

        $(".changeCaptcha").click(function() {
            $("#captchaImg").trigger('click');
        });

        $("#password").on("blur", function() {
            if (!$.trim(this.value)) {
                $("#anquan").hide();
            }
        });
    });
})(jQuery);