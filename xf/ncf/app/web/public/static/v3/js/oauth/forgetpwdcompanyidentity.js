(function($) {
    $(function() {
        (function() {
            //海外手机号下拉框样式修改
            $(".j_select").css({
                'width': 65,
                'padding': '0px 0px 0px 13px',
                'background-position': '58px 13px'
            }).html('<span style="color:#7e7575;font-size: 14px;">+86</span>');

            $('#forgetpwdmobile_form').validator({
                isNormalSubmit: false,
                messages: {},
                fields: {
                    'mobile': '手机号码:required;mobile;checkAjax',
                    'legalbody_credentials_no': '法定代表人证件号:required;legalbody_credentials_no;checkAjax',
                    'major_condentials_no': '代理人证件号码:required;major_condentials_no;checkAjax'
                },
                rules: {
                    mobile: [/^1[3456789]\d{9}$/, '手机号码格式不正确'],
                    major_condentials_no: [/^[0-9A-Z()]{6,20}$/, '代理人证件号码格式不正确'],
                    legalbody_credentials_no: [/^[0-9A-Z()]{6,20}$/, '法定代表人证件号格式不正确'],
                    checkAjax: function(element) {
                        var dataJson = {},
                            msgData = null,
                            msgObj = null,
                            reqType = 'post',
                            $el = $(element);
                        dataJson.viewContent = $('#view_content').val();
                        var fieldName = $el.attr("name");
                        dataJson[fieldName] = $el.val();
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

                        msgObj = msgData.info;
                        if (msgObj.code != "0") {
                            return msgObj.msg;
                        }
                    }
                },
                valid: function(form) {
                    $.ajax({
                        type: "post",
                        dataType: "json",
                        data: {
                            "mobile": $("#mobile").val(),
                            "major_condentials_no": $("#major_condentials_no").val(),
                            "legalbody_credentials_no": $("#legalbody_credentials_no").val(),
                            "token": $("#token").val(),
                            "token_id": $("#token_id").val(),
                            "viewContent": $('#view_content').val()
                        },
                        url: $(form).attr("action"),
                        success: function(data) {
                            if (data.info.code == '0') {
                                // 短信验证
                                Firstp2p.getMsg({
                                    dataGetCode: {
                                        "type": 20,
                                        "active": 1,
                                        'idno': $("#idno").val(),
                                        "mobile": $("#mobile").val(),
                                        'country_code': $('#country_code').data('name')
                                    },
                                    dataVerrifyCode: {
                                        "phone": $("#mobile").val(),
                                        "from": "forget_pwd"
                                    },
                                    callbackpost: function(mdata, settings) {
                                        if (mdata.info.code === "1") {
                                            $.weeboxs.close();
                                            location.href = data.jump;
                                        } else {
                                            settings.showError();
                                            $("#j_e_text").html(mdata.info.msg);
                                        }
                                    }
                                });
                            } else {
                                $.weeboxs.open(data.info.msg, {
                                    boxid: null,
                                    contentType: 'text',
                                    showButton: false,
                                    showOk: false,
                                    title: '提示',
                                    width: 441,
                                    type: 'wee',
                                });
                            }
                        },
                        error: function() {}
                    });
                }
            });
        })();
    });
})(jQuery);