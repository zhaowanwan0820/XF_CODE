(function($) {
    $(function() {
        (function() {
            //海外手机号下拉框样式修改
            var mobileRegEx,
                mbsetpro = function() {
                    var mobileReg = Firstp2p.mobileReg;
                    mobileRegEx = mobileReg[$('#country_code').val()];
                };
            $(".select_box").select({
                onSelectChange: function($t, $input, index, $li) {
                    mbsetpro();
                    $t.find('.j_select').html('<span style="color:#555353;font-size: 14px;">+' + $li.data('value') + '</span>');
                    $('#editpwd_form').validator("setRule", {
                        mobile : [mobileRegEx, '注册手机号格式不正确']
                    });
                    $("#mobile").trigger("validate");
                }
            });
            mbsetpro();
            $(".j_select").css({
                'width': 65,
                'padding': '0px 0px 0px 13px',
                'background-position': '58px 13px'
            }).html('<span style="color:#555353;font-size: 14px;">+86</span>');
            $('.select_ul').css({
                'padding': '5px 3px 5px 3px',
                'width': '131'
            }).find('li').css({
                'padding': '0px 9px 0px 6px'
            }).html(function() {
                var curIconText = $(this).data('name');
                var areaName = "";
                var areaCode = $(this).data('value');
                switch (curIconText) {
                    case 'cn':
                        areaName = "中国";
                        break;
                    case 'hk':
                        areaName = "中国香港";
                        break;
                    case 'mo':
                        areaName = "中国澳门";
                        break;
                    case 'tw':
                        areaName = "中国台湾";
                        break;
                    case 'us':
                        areaName = "美国";
                        break;
                    case 'ca':
                        areaName = "加拿大";
                        break;
                    case 'uk':
                        areaName = "英国";
                        break;
                    default:
                        areaName = "中国";
                }
                return $('<span style="float:left;">' + areaName + '</span><span style="float:right;">+' + areaCode + '</span>')
            });
            $('#editpwd_form').validator({
                isNormalSubmit: false,
                messages: {},
                fields: {
                    'old_password': '旧密码:required;checkAjax;',
                    'mobile': '注册手机号:required;mobile;checkAjax;',
                    'captcha': '图形验证码:required;captcha;checkAjax;'
                },
                rules: {
                    mobile: [mobileRegEx, '注册手机号格式不正确'],
                    captcha: [/^\d{4,10}$/, '验证码不正确'],
                    checkAjax: function(element) {
                        var dataJson = {},
                            msgData = null,
                            msgObj = null,
                            reqType = '',
                            $el = $(element);
                        if ($el.attr("name") == 'old_password') {
                            dataJson.ajax = 1;
                            dataJson.old_password = $el.val();
                            reqType = "post"
                        } else if ($el.attr("name") == 'mobile') {
                            dataJson.ajax = 2;
                            dataJson.phone = $el.val();
                            dataJson.country_code = $('#country_code').val();
                            reqType = "post"
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
                        if ($el.attr("name") == 'old_password' || $el.attr("name") == 'mobile') {
                            msgObj = msgData.info;
                            if (msgObj.code == '-1') {
                                location.href = msgData.jump + '?error=error_jump';
                            }
                        } else if ($el.attr("name") == 'captcha') {
                            msgObj = msgData;
                        }
                        if (msgObj.code != 0) {
                            return msgObj.msg;
                        }
                    }
                },
                valid: function(form) {
                    $.ajax({
                        type: "post",
                        dataType: "json",
                        data: {
                            "old_password": $("#old_password").val(),
                            "phone": $("#mobile").val(),
                            "captcha": $("#captcha").val(),
                            "token": $("#token").val(),
                            "token_id": $("#token_id").val(),
                            "ajax": 0,
                            'country_code': $('#country_code').val()
                        },
                        url: "/user/DoModifyPwd",
                        success: function(data) {
                            if (data.info.code === '0') {
                                Firstp2p.getMsg({
                                    data: data,
                                    dataGetCode: {
                                        "type": 12,
                                        "mobile": $("#mobile").val(),
                                        "active": 1,
                                        'country_code': $('#country_code').val()
                                    },
                                    dataVerrifyCode: {
                                        "phone": $("#mobile").val(),
                                        "from": "modify_pwd"
                                    },
                                    onclose: function() {
                                        $("#img_captcha").trigger("click");
                                        //location.reload();
                                    }
                                });
                            } else if (data.info.code === '-1') {
                                location.href = data.jump + '?error=error_jump';
                            }
                        },
                        error: function() {

                        }
                    });
                }
            });

            // 图形验证码逻辑
            var img_captcha = $("#img_captcha");
            img_captcha.click(function() {
                img_captcha.attr('src', '/verify.php?w=93&h=34&rb=0&rand=' + new Date().valueOf());
            });
            img_captcha.attr('src', '/verify.php?w=93&h=34&rb=0&rand=' + new Date().valueOf());

        })();
    });
})(jQuery);