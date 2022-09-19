$(function() {
    //placehoder
    $(function() {
        $(".int_placeholder").each(function() {
            var p_text = $(this).attr("data-placeholder");
            new Firstp2p.placeholder(this, {
                placeholder_text: p_text == null ? "请输入" : p_text,
                isIE7Show: false
            });
        });
        $("#input-mobile,#input-mobile-mess").bind("focus",
            function() {
                var $p = $(this).parent();
                $p.removeClass('err-shadow');
                $p.addClass('ipt-focus');
            }).bind("blur",
            function() {
                $(this).parent().removeClass('ipt-focus');
            });
        $("#input-username").bind("focus",
            function() {
                var $p = $(this);
                $p.removeClass('err-shadow');
                $p.addClass('ipt-focus');
            }).bind("blur",
            function() {
                $(this).removeClass('ipt-focus');
            });
    })
    /***************** 获取cookie *********************/

    function _getCookie(c_name) {
        if (document.cookie.length > 0) {
            var c_start = document.cookie.indexOf(c_name + "=");
            if (c_start != -1) {
                c_start = c_start + c_name.length + 1;
                var c_end = document.cookie.indexOf(";", c_start);
                if (c_end == -1) c_end = document.cookie.length;
                return unescape(document.cookie.substring(c_start, c_end));
            }
        }
        return "";
    }
    /***************** 删除cookie *********************/
    function _delCookie(name) //删除cookie
    {
        var exp = new Date();
        exp.setMinutes(exp.getMinutes() - 30);
        var cval = _getCookie(name);
        if (cval != null) document.cookie = name + "=" + cval + ";path=/" + ";expires=" + exp.toGMTString();
    }

    //删除第二步绑卡时的Cookie
    //删除cookie
    _delCookie("c_realName");
    _delCookie("c_cardNo");
    $("#dateInput1").datepicker({
        onClose: function(selectedDate) {
            $("#dateInput2").datepicker("option", "minDate", selectedDate);
            if ($("#is_permanent").val() == "0") {
                if ($("#dateInput2").val() == "") {
                    fullnameVld({
                        el: $("input[name=credentials_expire_at]"),
                        status: false,
                        key: "credentials_expire_at",
                        msg: "请选择正确的有效期"
                    });
                }
            } else {
                if ($("#dateInput1").val() != "") {
                    $("#dateInput1").parent().find('.er-icon').css('display', 'block');
                    $("#dateInput1").parent().find('.error-wrap').hide().html("");
                    $("#dateInput1").removeClass('err-shadow');
                }
            }
            $("#dateInput1").removeClass('err-shadow');
        },
        yearRange: "c-117:c+100"
    });
    $("#dateInput2").datepicker({
        onClose: function(selectedDate) {
            $("#dateInput2").datepicker("option", "maxDate", selectedDate);
            if ($("#dateInput2").val() != "") {
                $("#dateInput2").removeClass('err-shadow');
                $("#dateInput2").parent().find('.error-wrap').hide().html("");
                $("#dateInput2").parent().find('.er-icon').css('display', 'block');
            }
            $("#dateInput2").removeClass('err-shadow');
        },
        yearRange: "c-117:c+100"
    });

    $(".JS_sms_country_code").select({
        onSelectChange: function($t, $input, index, $li) {
            __formpage__.setRule = {};
            __formpage__.setRule.sms_phone = Firstp2p.mobileReg[$('#sms_country_code').val()];
            __formpage__.setRule.consignee_phone = Firstp2p.mobileReg[$('#consignee_country_code').val()];
            __formpage__.setRule.idcard = Firstp2p.mobileReg[$('#idcard').val()];
            if ($.trim($("#input-mobile-mess").val()) != '') {
                __formpage__._blur($("#input-mobile-mess")[0], "blur");
            }
            if ($.trim($("#input-mobile").val()) != '') {
                __formpage__._blur($("#input-mobile")[0], "blur");
            }
            if ($.trim($("#idcard").val()) != '') {
                __formpage__._blur($("#idcard")[0], "blur");
            }
            $t.find('.j_select').html('<span style="color:#555353;font-size: 14px;" class="JS_code_span">+' + $li.data('value') + '</span>');
            $t.find('.j_select_type').html('<span style="color:#555353;font-size: 14px;">' + $li.data('value') + '</span>');
            // $t.parent().find(".er-icon").css('display', 'block');
        }
    });

    var test = $(".company_reg_select_box input");
    $('.JS_credentials_type ,.js_credentials_type ,.JS_legalbody_credentials_type').select({
        onSelectChange: function($t, $input, index, $li) {}
    });
    $('.JS_bank_name').select({
        onSelectChange: function($t, $input, index, $li) {
            nameBankzone();
        }
    });
    var test = $(".company_reg_select_box input");
    test.attr("data-con", "require");
    var code1 = $('#adress_code').val();
    $(".JS_sms_country_code .j_select").css({
        'width': 65,
        'padding': '0px 0px 0px 13px',
        'background-position': '58px 13px',
        'color': '#333'
    }).html('<span style="color:#555353;font-size: 14px;" class="code_pan">+86</span>');
    if (code1 !== "") {
        $('.j_select').find('.code_pan').html("+" + code1);
    }
    $(".j_select_type").css({
        'width': 295,
        'overflow': "hidden",
        'padding-right': 26
    });
    $(".select_box").next('div.ipt-wrap').css('width', 209);
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

    // })();

    // });
    var formId = document.getElementById("reg_v2_1");
    var __formpage__;

    function needSubmit(formId) {
        var util = new Util();
        __formpage__ = formpage({
            conf: {
                frm: formId,
                vld: validate_middleware.validate({
                    "password": ["密码", true, null, /^.{5,25}$/, '请输入5-25位数字、字母及常用符号'],
                    "credentials_expire_date": ["日期", true, null, null, null],
                    "credentials_expire_at": ["日期", true, null, null, null],
                    "sms_phone": ["接收短信通知手机号", true, null, /^0?(1[3456789]\d{9}$)/, '手机号格式不正确'],
                    "consignee_phone": ["企业联络人手机号", true, null, /^0?(1[3456789]\d{9}$)/, '手机号格式不正确'],
                    "company_name": ["企业全称", true, null, /^[\u4E00-\u9FA5\（\）]+$/, '企业全称只允许输入中文和中文括号'],
                    // "credentials_no": ["证件号码", true, null, /^[0-9]+[a-zA-Z]*.{5,8}$/, '证件号码格式正确'],
                    "credentials_type": ["证件类型", true, null, null, null],
                    "user_name": ["用户名", true, null, /^([A-Za-z])[\w-]{3,19}$/, '请输入4-20位字母、数字、下划线、横线，首位只能为字母'],
                    "registration_address": ["企业注册地址", null, null, null, null],
                    "contract_address": ["企业联系地址", null, null, null, null],
                    "legalbody_name": ["代表人信息", true, null, null, null],
                    // "legalbody_credentials_no": legalbody_credentials_no,
                    "legalbody_credentials_type": ["证件类型", true, null, null, null],
                    "card_name": ["开户行", true, null, null, null],
                    "bankcard": ["银行卡号", true, null, /^[0-9]*$/, "银行卡号格式不正确"],
                    "bank_id": ["开户行名称", true, null, null, null],
                    // "bankzone":["开户网点", null, null, null, null],
                    "legalbody_email": ["邮箱", true, [1, 100], /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i, '邮箱地址无效'],
                    "major_name": ["法定代表人姓名", true, null, null, null],
                    "major_condentials_type": ["法定代表人证件类别", true, null, null, null],
                    // "major_condentials_no":["账户管理人证件号码", true, null, /^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[A-Z])$/, "请输入正确的身份证号码"],
                    "major_mobile": ["代理人手机号码", true, null, /^0?(1[3456789]\d{9}$)/, '手机号格式不正确'],
                    "major_mobile_self": ["法定代表人手机号码", true, null, /^0?(1[3456789]\d{9}$)/, '手机号格式不正确'],
                    "major_email": ["代理人邮箱地址", true, [1, 100], /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i, '邮箱地址无效'],
                    "major_email1": ["法定代表人邮箱地址", true, [1, 100], /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i, '邮箱地址无效'],
                    "major_contract_region": ["账户管理人联系地址", true, null, null, null],
                    "company_register_amount": ["企业注册资金", true, null, /^\d+(\.\d+)?$/, '请输入小写数字'],
                    "company_apply_license_id": ["许可证核准号", true, null, /^J[\w]{13}/, '请输入以J开头编码'],
                    "company_phone_number": ["联系方式", true, null, /(^0?(1[3456789]\d{9}$))|(^0\d{2,3}-\d{7,8}(-\d{1,6})?$)/, '手机号格式不正确']
                }),
                custom_vld: {
                    company_phone_number: function(data) {
                        return fullnameVld(data);
                    },
                    company_apply_license_id: function(data) {
                        return fullnameVld(data);
                    },
                    company_register_amount: function(data) {
                        return fullnameVld(data);
                    },
                    company_name: function(data) {
                        return fullnameVld(data);
                    },
                    credentials_expire_date: function(data) {
                        return fullnameVld(data);
                    },

                    credentials_expire_at: function(data) {
                        if ($("#is_permanent").val() == "0") {
                            return fullnameVld(data);
                        } else {
                            return true;
                        }
                    },
                    credentials_no: function(data) {
                        return credentials_no(data);
                    },
                    credentials_type: function(data) {
                        return credentialsTypeVld(data);
                    },
                    registration_address: function(data) {
                        return fullnameVld(data);
                    },
                    contract_address: function(data) {
                        return fullnameVld(data);
                    },
                    legalbody_name: function(data) {
                        return fullnameVld(data);
                    },
                    legalbody_credentials_no: function(data) {
                        return idcard(data);
                    },
                    legalbody_credentials_type: function(data) {
                        return credentialsTypeVld(data);
                    },
                    legalbody_email: function(data) {
                        return fullnameVld(data);
                    },
                    card_name: function(data) {
                        return fullnameVld(data);
                    },
                    bankcard: function(data) {
                        return fullnameVld(data);
                    },
                    bank_id: function(data) {
                        return credentialsTypeVld(data);
                    },
                    bankzone: function(data) {
                        return credentialsTypeVld(data);
                    },
                    major_name: function(data) {
                        return fullnameVld(data);
                    },
                    major_condentials_type: function(data) {
                        return fullnameVld(data);
                    },
                    major_condentials_no: function(data) {
                        return idcard(data);
                    },
                    major_mobile: function(data) {
                        return commVld(data);
                    },
                    major_mobile_self: function(data) {
                        return commVld(data);
                    },
                    major_email: function(data) {
                        return fullnameVld(data);
                    },
                    major_email1: function(data) {
                        return fullnameVld(data);
                    },
                    major_contract_region: function(data) {
                        return fullnameVld(data);
                    }
                },
                callback: function(data, els) {
                    var form = this.frm.getAttribute("data-id")
                    submitVld(form);
                    return false;
                },
                focus: false,
                util: util
            }
        });
    }
    needSubmit(formId);
    /**
     * 判断密码合法性
     * @param data
     * @returns {*}
     */


    function commVld(data) {
        //debugger;
        var el = data.el;
        var status = data.status;
        var msg = data.msg;
        var ele = $(el).parent();
        if (data.key == 'password') {
            ele = ele.parent();
        } else if (data.key == 'consignee_phone' || data.key == 'bankzone' || data.key == 'major_mobile' || data.key == 'major_mobile_self') {
            ele = $(el).parent().parent();
        }
        var _reset = function(ele) {
            if (data.key == 'consignee_phone' || data.key == 'bankzone' || data.key == 'major_mobile' || data.key == 'major_mobile_self') {
                $(el).parent().removeClass('err-shadow');
            } else {
                $(el).removeClass('err-shadow');
            }

            ele.find(".er-icon").css('display', 'none');
            ele.find(".error-wrap").css('display', 'none');
        }

        var _error = function(ele, msg) {
            if (data.key == 'consignee_phone' || data.key == 'bankzone' || data.key == 'major_mobile' || data.key == 'major_mobile_self') {
                $(el).parent().addClass('err-shadow');
            } else {
                $(el).addClass('err-shadow');
            }
            _reset(ele);
            ele.find(".error-wrap").css('display', 'block');
            ele.find(".error-wrap .e-text").html(msg);
        }

        var _right = function(ele) {
            _reset(ele);
            ele.find(".er-icon").css('display', 'block');
        }

        if (msg === '') {
            _right(ele);
        } else {
            _error(ele, msg);
        }
        return data;
    }

    function fullnameVld(data) {
        var el = data.el;
        var status = data.status;
        var msg = data.msg;
        var ele = $(el).parent();
        var val = $(el).val();
        if (data.key == 'is_permanent') {
            ele = $(el).parent().parent();
        }
        var _reset = function(ele) {
            $(el).removeClass('err-shadow');
            ele.find(".er-icon").css('display', 'none');
            ele.find(".error-wrap").css({
                'display': 'none'
            });
        }
        var _error = function(ele, msg) {
            _reset(ele);
            $(el).addClass('err-shadow');
            ele.find(".error-wrap").css('display', 'block').html('<div class="form-sprite e-arrow"></div><div class="ew-icon"><i class="form-sprite icon-wrong"></i></div><div class="e-text"></div>');
            ele.find(".error-wrap .e-text").html(msg);
        }

        var _right = function(ele) {
            _reset(ele);
            ele.find(".er-icon").css('display', 'block');
        }

        if (msg === '') {
            _right(ele);
        } else {
            _error(ele, msg);
        }
        return data;
    }



    function credentialsTypeVld(data) {
        var el = data.el;
        var status = data.status;
        var msg = data.msg;
        var ele = $(el).parent();
        var val = $(el).val();
        if (data.key == 'credentials_type' || data.key == 'legalbody_credentials_type' || data.key == 'major_condentials_type' || data.key == 'bank_id' || data.key == 'bankzone') {
            ele = $(el).parent().parent();
        }
        var _reset = function(ele) {
            $(el).removeClass('err-shadow');
            ele.find(".er-icon").css('display', 'none');
            ele.find(".error-wrap").css({
                'display': 'none'
            });
        }
        var _error = function(ele, msg) {
            _reset(ele);
            $(el).addClass('err-shadow');
            ele.find(".error-wrap").css('display', 'block');
            ele.find(".error-wrap .e-text").html(msg);
        }

        var _right = function(ele) {
            _reset(ele);
            ele.find(".er-icon").css('display', 'block');
        }

        if (msg === '') {
            _right(ele);
        } else {
            _error(ele, msg);
        }
        return data;
    }

    //agree
    var elist = [
        //update
        function() {
            $(".p2p-ui-checkbox").p2pUiCheckbox();
            //agree
            var agree = $('#agree');
            agree.unbind('change').bind('change',
                function(e) {
                    agreeVld({
                        el: this
                    });
                })
            var is_permanent = $("#is_permanent");
            var status = false;
            var msg = "";
            is_permanent.unbind('change').bind('change',
                function(e) {
                    if (is_permanent.val() != "0") {
                        $(".j_date").attr('disabled', 'disabled').val("").removeClass('err-shadow');
                        if ($(".j_date_start").val() != "") {
                            status = true;
                            msg = "";
                        } else {
                            status = false;
                            msg = "请选择正确的有效期";
                            $(".j_date_start").addClass('err-shadow');
                        }
                        fullnameVld({
                            el: $('input[name="is_permanent"]'),
                            status: status,
                            key: "is_permanent",
                            msg: msg
                        });
                    } else {
                        $(".j_date").removeAttr('disabled');
                        if ($(".j_date").val() != "") {
                            status = true;
                            msg = "";
                        } else {
                            status = false;
                            msg = "请选择正确的有效期";
                            $(".j_date").addClass('err-shadow');
                        }
                        fullnameVld({
                            el: $('input[name="is_permanent"]'),
                            status: status,
                            key: "is_permanent",
                            msg: msg
                        });
                    }
                })


        }
        //p2p-ui-checkbox
    ]

    for (var i = 0,
            len = elist.length; i < len; i++) {
        elist[i]();
    }

    function idcard(data) {
        var el = data.el;
        var status = data.status;
        var msg = data.msg;
        var ele = $(el).parent();
        var val = $(el).val();
        if (data.key == 'idcard') {
            ele = $(el).parent().parent();
        }
        var _reset = function(ele) {
            $(el).removeClass('err-shadow');
            ele.find(".er-icon").css('display', 'none');
            ele.find(".error-wrap").css('display', 'none');
        }

        var _error = function(ele, msg) {
            _reset(ele);
            $(el).addClass('err-shadow');
            ele.find(".error-wrap").css('display', 'block');
            ele.find(".error-wrap .e-text").html(msg);
        }

        var _right = function(ele) {
            _reset(ele);
            if (val != "") {
                ele.find(".er-icon").css('display', 'block');
            }
        }

        if (!data.status) {
            _error(ele, data.msg);
            return data;
        }

        var hash = idcardajax(val, data);

        if (hash.status) {
            _right(ele);
        } else {
            _error(ele, hash.msg);
        }
        data.status = hash.status;
        return data;
    }

    function idcardajax(val, data) {
        var idcard = val;
        var stepNum;
        if (data.key == "major_condentials_no") {
            stepNum = 4
        } else if (data.key == "legalbody_credentials_no") {
            stepNum = 2
        }
        if (stepNum == 2) {
            var ele = $('#legalbody_credentials_no');
        } else if (stepNum == 4) {
            var ele = $('#major_condentials_no');
        }
        var hasError = false;
        var hash = {
            status: true,
            msg: ''
        };
        if (stepNum == 2) {
            var tel_code = $("#legalbody_credentials_type").val();
        } else if (stepNum == 4) {
            var tel_code = $("#major_condentials_type").val();
        }
        if (tel_code == 1) {
            var mobileRegEx = /^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[A-Z])$/;
        } else {
            var mobileRegEx = /\S*$/;
        }
        if (stepNum == 2) {
            var tel = $('#legalbody_credentials_no').val();
        } else if (stepNum == 4) {
            var tel = $('#major_condentials_no').val();
        }
        if (!tel || tel == null) {
            hash.status = false;
            hash.msg = "法人证件号码不能为空";
        } else if (!mobileRegEx.test(tel) && tel_code == 1) {
            hash.status = false;
            hash.msg = "身份证格式不正确";
        } else {
            hash.status = true;
            hash.msg = "";
        }
        idcardajax[val] = {
            status: hash.status,
            msg: hash.msg
        };
        return hash;
    }



    function credentials_no(data) {
        var el = data.el;
        var status = data.status;
        var msg = data.msg;
        var ele = $(el).parent();
        var val = $(el).val();
        if (data.key == 'idcard') {
            ele = $(el).parent().parent();
        }
        var _reset = function(ele) {
            $(el).removeClass('err-shadow');
            ele.find(".er-icon").css('display', 'none');
            ele.find(".error-wrap").css('display', 'none');
        }

        var _error = function(ele, msg) {
            _reset(ele);
            $(el).addClass('err-shadow');
            ele.find(".error-wrap").css('display', 'block');
            ele.find(".error-wrap .e-text").html(msg);
        }

        var _right = function(ele) {
            _reset(ele);
            if (val != "") {
                ele.find(".er-icon").css('display', 'block');
            }
        }

        if (!data.status) {
            _error(ele, data.msg);
            return data;
        }

        var hash = credentialsnoajax(val);

        if (hash.status) {
            _right(ele);
        } else {
            _error(ele, hash.msg);
        }
        data.status = hash.status;
        return data;
    }

    function credentialsnoajax(val) {
        var credentials_no = val;
        var ele = $('input[name="credentials_no"]');
        var hasError = false;
        var hash = {
            status: true,
            msg: ''
        };
        var legalbody_credentials_type = $("#credentials_type").val();
        if (legalbody_credentials_type == 3) {
            var mobileRegEx = /^[0-9]|[a-zA-Z]*$/;
        } else if (legalbody_credentials_type == 1) {
            var mobileRegEx = /^[0-9]*$/;
        } else {
            var mobileRegEx = /\S*$/;
        }

        var legalbody_credentials_no = $('input[name="credentials_no"]').val();
        if (!legalbody_credentials_no || legalbody_credentials_no == null) {
            hash.status = false;
            hash.msg = "证件号码不能为空";
        } else if ((!mobileRegEx.test(legalbody_credentials_no) || legalbody_credentials_no.length != 15) && legalbody_credentials_type == 1) {
            hash.status = false;
            hash.msg = "证件号码格式不正确";
        } else if ((!mobileRegEx.test(legalbody_credentials_no) || legalbody_credentials_no.length != 18) && legalbody_credentials_type == 3) {
            hash.status = false;
            hash.msg = "证件号码格式不正确";
        } else {
            hash.status = true;
            hash.msg = "";
        }
        credentialsnoajax[val] = {
            status: hash.status,
            msg: hash.msg
        };
        return hash;
    }



    function submitVld(form) {
        var step = form * 1;
        var data = $("#reg_v2_" + step).serialize();
        var major_type;
        if (step == 4) {
            major_type = 1;
        } else if (step == 5) {
            major_type = 2;
        }
        //     data = $(".ui-box form").serialize();
        //     $.ajax({
        //         url: "/enterprise/DoApply",
        //         type: "post",
        //         data:  data + "&isAjax=1&country_code=" + $("#sms_country_code").val() + "country_code1=" + $("#sms_country_code1").val() + "&major_type=" + major_type,
        //         dataType: "json",
        //         beforeSend: function() {
        //             //$text.html("正在提交，请稍候...");
        //             $(".JS_submit_button").attr('disabled', 'disabled');
        //         },
        //         success: function(json) {
        //             if (json.code == 0) {
        //                 window.location.replace("/enterprise/ConfirmApply");
        //             } else {
        //                 // $('.box' + (step + 1)).show();
        //                 // $('#submit_button' + step).hide();
        //                 // formId = document.getElementById("reg_v2_" + (step+1));
        //                 // needSubmit(formId);
        //                 if (json.data[0]["field"] != "") {
        //                     var errorname = json.data[0]["field"];
        //                     if (errorname == "idcard" || errorname == "bankzone" || errorname == "consignee_phone" ||errorname == "major_mobile" || errorname == "major_mobile_self") {
        //                         commVld({
        //                             el: $("input[name=" + errorname + "]"),
        //                             status: false,
        //                             key: errorname,
        //                             msg: json.data[0]["message"]
        //                         });
        //                     }else {
        //                         fullnameVld({
        //                             el: $("input[name=" + errorname + "]"),
        //                             status: false,
        //                             key: errorname,
        //                             msg: json.data[0]["message"]
        //                         });
        //                     }

        //                 }
        //             }
        //             $(".JS_submit_button").removeAttr('disabled');
        //         },
        //         error: function() {
        //             alert("服务器错误，请重新再试！");
        //             $(".JS_submit_button").removeAttr('disabled');
        //         }
        //     });
        $.ajax({
            url: "/enterprise/Validate",
            type: "post",
            data: data + "&isAjax=1" + "&major_type=" + major_type + "&company_phone_number=" + $(".phone_num").val(),
            dataType: "json",
            beforeSend: function() {
                //$text.html("正在提交，请稍候...");
                $(".JS_submit_button").attr('disabled', 'disabled');
            },
            success: function(json) {
                if (json.code == 0) {
                    $(".error-wrap").hide();
                    $('.box' + (step + 1)).show();
                    if (step < 4) {
                        $('#submit_button' + step).hide();
                    }
                    if (step == 3) {
                        if ($('#or_btn').val() == 2) {
                            formId = document.getElementById("reg_v2_" + (step + 2));
                        } else {
                            formId = document.getElementById("reg_v2_" + (step + 1));
                        }
                    } else if (step == 4 || step == 5) {

                        var phoneData = phoneVld(step);
                        $(".error-wrap").hide();
                        popup(step, major_type);
                        sendMsg(phoneData, step);
                        $("#action-send-code").unbind('click').bind('click',
                            function() {
                                sendMsg(phoneData, step);
                            });
                    } else {
                        formId = document.getElementById("reg_v2_" + (step + 1));
                    }
                    needSubmit(formId);
                } else {
                    // $('.box' + (step + 1)).show();
                    // $('#submit_button' + step).hide();
                    formId = document.getElementById("reg_v2_" + step);
                    needSubmit(formId);
                    if (json.data[0]["field"] != "") {
                        var errorname = json.data[0]["field"];
                        if (errorname == "idcard" || errorname == "bankzone" || errorname == "consignee_phone" || errorname == "major_mobile" || errorname == "major_mobile_self") {
                            commVld({
                                el: $("input[name=" + errorname + "]"),
                                status: false,
                                key: errorname,
                                msg: json.data[0]["message"]
                            });
                        } else {
                            fullnameVld({
                                el: $("input[name=" + errorname + "]"),
                                status: false,
                                key: errorname,
                                msg: json.data[0]["message"]
                            });
                        }

                    }
                }
                $(".JS_submit_button").removeAttr('disabled');
            },
            error: function() {
                alert("服务器错误，请重新再试！");
                $(".JS_submit_button").removeAttr('disabled');
            }
        });
    }
    //popup
    function sendMsg(_data, step) {
        //电话号码 正则去掉 必然正确才这里
        if (step == 4) {
            var phone = $("#input-mobile-mess1").val();
            var country_code = $("#sms_country_code1").val();
        } else if (step == 5) {
            var phone = $("#input-mobile-mess").val();
            var country_code = $("#sms_country_code").val();
        }
        var button = $("#action-send-code");
        // token_id  token 后台传递的    captcha 手输验证码
        var token_id = $("#token_id").val();
        var token = $("#token").val();
        var captcha = $("#input-captcha").val();
        var errorSpan = $(".error-box");
        var sendcodeUrl = '/user/MCode';
        sendMsg.sendNum = sendMsg.sendNum || 1;
        var btGray = function() {
            button.addClass("btn-gray-h34");
            button.val("正在获取中...");
            button.attr('disabled', 'disabled');
        };

        var _set = function(msg, status) {
            errorSpan.css('visibility', 'visible');
            if (status == 0) {
                errorSpan.find('.e-text').html(msg);
                errorSpan.css('visibility', 'visible');
                errorSpan.find('.e-text').html(msg);
                showNormal();
            }
            if (status == 1) {
                errorSpan.css('visibility', 'visible');
                errorSpan.find('.e-text').html(msg);
                $("#error-wrap").find('.e-text').css("width", "372px");
                $("#error-wrap").addClass("error-wrap2");
                $(".dialog-content").css("height", "148px");
            }
        }
        var _reset = function() {
            errorSpan.css('visibility', 'hidden');
            errorSpan.find('.e-text').html('');
        }
        _reset();
        if (phone == '') {
            _set('手机号不能为空', 0);
            return;
        }
        btGray();

        function updateTimeLabel(duration) {
            var timeRemained = duration;
            var timer = setInterval(function() {
                    button.val(timeRemained + '秒后重新发送');
                    timeRemained -= 1;
                    if (timeRemained == -1) {
                        clearInterval(timer);
                        button.val('重新发送').removeAttr('disabled').removeClass("btn-gray-h34");
                    }
                },
                1000);
        }
        var hash;
        var callback = function(data) {
            hash = data;
            if (data.code == 1) {
                updateTimeLabel(60, 'action-send-code');
                return;
            } else {
                _set(data.message, 0);
            }
            button.val('重新发送').removeAttr('disabled').removeClass("btn-gray-h34");
        }
        var _sendMsg = function(url, isrsms) {
            $.ajax({
                type: "post",
                data: {
                    type: '17',
                    isrsms: isrsms,
                    t: new Date().getTime(),
                    mobile: phone,
                    token: token,
                    token_id: token_id,
                    captcha: captcha,
                    country_code: $("#sms_country_code").val()
                },
                url: url,
                async: false,
                dataType: "json",
                success: function(data) {
                    callback(data);
                }
            });
        };

        if (_data && 'code' in _data) {
            callback(_data);
            return hash;
        }

        if (sendMsg.sendNum == 1) {
            _sendMsg(sendcodeUrl, 0);
        } else {
            _set(nogetCode, 1);
            _sendMsg(sendcodeUrl, 1);
        }
        sendMsg.sendNum = 2;
        return hash;
    }

    //sendMsg
    function phoneVld(step) {
        if (step == 4) {
            var phone = $("#input-mobile-mess1").val();
            var country_code = $("#sms_country_code1").val();
        } else if (step == 5) {
            var phone = $("#input-mobile-mess").val();
            var country_code = $("#sms_country_code").val();
        }
        var token_id = $("#token_id").val();
        var token = $("#token").val();
        var captcha = $("#input-captcha").val();
        var hash = {};
        var sendcodeUrl = '/user/MCode';
        var _sendMsg = function(url, isrsms) {
            $.ajax({
                type: "post",
                data: {
                    type: '17',
                    isrsms: isrsms,
                    t: new Date().getTime(),
                    mobile: phone,
                    token: token,
                    token_id: token_id,
                    captcha: captcha,
                    country_code: country_code
                },
                url: url,
                async: false,
                dataType: "json",
                success: function(data) {
                    hash = data;
                    // $("#captcha").attr("src","/verify.php?w=50&h=36&rb=0" + new Date().valueOf()).val("");
                }
            });
        };
        _sendMsg(sendcodeUrl, 0);
        return hash;
    }
    // 验证码逻辑
    function popup(step, major_type) {
        if (step == 4) {
            var phone = $("#input-mobile-mess1").val();
            var quhao = $('.j_mess_select1 span').html().replace(/\D*/g, '');
        } else if (step == 5) {
            var phone = $("#input-mobile-mess").val();
            var quhao = $('.j_mess_select span').html().replace(/\D*/g, '');
        }
        quhao = quhao.replace(/[^0-9]/, "");
        quhao = quhao == "86" ? "" : (quhao + "-");
        var phonelabel = quhao + phone;
        phonelabel = phonelabel.replace(/([0-9\-]*)([0-9]{4})([0-9]{4})/,
            function(_0, _1, _2, _3) {
                return _1 + "****" + _3
            });
        var html = '';
        html += '<div class="wee-send">';
        html += '<div class="send-input">';
        html += '<div class="error-box">';
        html += '<div class="error-wrap" id="error-wrap">';
        //                html += '<div class="e-arrow"></div>';
        html += '<div class="e-text" style="width: 267px;"></div>';
        html += '</div>';
        html += '</div>';
        html += '<p>已向&nbsp<span class="color_green">' + phonelabel + '</span>&nbsp发送验证短信</p>';
        html += '<input type="hidden" class="ipt-txt" id="pop_type" value="16">';
        html += '<input type="text" class="ipt-txt" id="pop_code" placeholder="短信验证码" maxlength="10">';
        html += '<input type="button" id="action-send-code" class="reg-sprite btn-blue-h34 btn-gray-h34" value="发送">';
        html += '</div>';
        html += '</div>';
        $.weeboxs.open(html, {
            boxid: null,
            boxclass: 'weebox_send_msg',
            contentType: 'text',
            showButton: true,
            showOk: true,
            okBtnName: '确定',
            showCancel: false,
            title: '填写短信验证码',
            width: 436,
            height: 125,
            type: 'wee',
            onclose: function() {
                //location.reload();
            },
            onok: function() {
                var code = $("#pop_code").val(),
                    type = $("#pop_type").val(),
                    $form = $(__formpage__.frm),
                    url = $form.attr("action"),
                    $text = $(".error-box").find('.e-text'),
                    showError = function() {
                        $(".error-box").css({
                            'display': 'block',
                            'visibility': 'visible'
                        });
                        showNormal();
                    };
                if (!/^\d{6}$/.test(code)) {
                    showError();
                    $text.html("请填写6位数字验证码");
                    return;
                }
                $('#input-code').val(code);

                //判断如果是第三方推广页，则进行表单同步提交；否则走异步提交
                if (showThirdPage($("#thirdPage")) == 1) {
                    showError();
                    $text.html("正在提交中，请稍候");
                    $form.unbind("submit").submit();
                    $("#submit_button").attr("disabled", "disabled");

                } else {
                    data = $(".ui-box form").serialize();
                    $.ajax({
                        url: "/enterprise/DoApply",
                        type: "post",
                        data: data + "&isAjax=1&country_code=" + $("#sms_country_code").val() + "country_code1=" + $("#sms_country_code1").val() + "&major_type=" + major_type + "&code=" + code,
                        dataType: "json",
                        beforeSend: function() {
                            //$text.html("正在提交，请稍候...");
                        },
                        success: function(data) {
                            if (data.code === 0) {
                                $.weeboxs.close();
                                window.location.replace("/enterprise/ConfirmApply");
                                // var sourceObj = $('#input-source');
                                // if (sourceObj.val()) {
                                //     location.href = sourceObj.attr('jump-url');
                                // } else {
                                //     location.href = "/" + data.redirect;
                                // }
                            } else {
                                showError();
                                $text.html(data.data);
                                // $("#captcha").attr("src","/verify.php?w=50&h=36&rb=0" + new Date().valueOf()).val("");
                            }
                        },
                        error: function() {
                            showError();
                            $text.html("服务器错误，请重新再试！");
                        }
                    });
                }
                //$.weeboxs.close();
            }
        });

        //判断第三方推广注册页则不允许关闭
        if (showThirdPage($("#thirdPage")) == 1) {
            $(".dialog-close").remove();
        }
    }

    //判断是否第三方注册推广页
    var showThirdPage = function($obj) {
        if ($obj[0] && $obj.val() == '1') {
            return 1;
        } else {
            return 0;
        }
    };

    function agreeVld(data) {
        var el = data.el;
        var val = el.value;
        var agreement_msg = $('#agreement_msg');
        if (val == '1') {
            data.status = true;
            agreement_msg.css('display', 'none');
        } else {
            data.status = false;
            agreement_msg.css('display', 'block');
        }

        return data;
    }

    function showNormal() {
        $("#error-wrap").find('.e-text').css("width", "267px");
        $("#error-wrap").removeClass("error-wrap2");
        $(".dialog-content").css("height", "125px");
    }

    function nameBankzone() {
        var bank = $(".JS_bank_id").text(),
            city = $("select[name='bankzone_region1'] option:selected").html(),
            bank = $.trim(bank.replace(/^\s+|\s+$/g, '')),
            province = $("select[name='bankzone_region0'] option:selected").html();
        $.ajax({
            url: "/enterprise/bank?c=" + encodeURIComponent(city) + "&p=" + encodeURIComponent(province) + "&b=" + encodeURIComponent(bank),
            type: "post",
            dataType: "json",
            async: false,
            success: function(json) {
                $(".JS_bankzone li").remove();
                for (var i = 0; i < json.length; i++) {
                    var bankHtml = "<li data-name=" + json[i]["id"] + " data-value=" + json[i]["name"] + ">" + json[i]["name"] + "</li>";
                    $('.bankwangdian').prepend(bankHtml);
                }
                if ($(".JS_bankzone").data('value') != "") {
                    $(".tit_bankwangdian").html($(".JS_bankzone").data('value'));
                    $('#bankzone').val($(".JS_bankzone").data('value'));
                } else {
                    $(".tit_bankwangdian").html($('.bankwangdian li').first().html());
                    $('#bankzone').val($('.bankwangdian li').first().html());
                }
                if (json.length == 0) {
                    $(".tit_bankwangdian").html("");
                    $('#bankzone').val("");
                };

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                alert(XMLHttpRequest.status);
                alert(XMLHttpRequest.readyState);
                alert(textStatus);
            }
        });
    }

    if ($('.box3').is(':visible')) {
        nameBankzone();
    }
    $(".JS_bankzone_region").on("change", "select[name='bankzone_region1']", function() {
        nameBankzone();
    });
    $(".JS_bankzone").on("click", ".tit_bankwangdian", function() {
        $('.JS_bankzone .bankwangdian').removeClass('none');
    });
    $(".JS_bankzone").on("click", "li", function() {
        $('.tit_bankwangdian').html($(this).html());
        $('#bankzone').val($(this).html());
        $('.JS_bankzone .bankwangdian').addClass('none');
    });

    $('#btn2').unbind().click(function() {
        $('#btn1').removeClass('btn_active').addClass('btn1')
        $('.step4_box1').hide();
        $('.step4_box2').show();
        $('#btn2').removeClass('btn2').addClass('btn_active')
        $('.box2_txt').show();
        $('#submit_button5').show();
        $('#submit_button4').hide();
        formId = document.getElementById("reg_v2_5");
        needSubmit(formId);
    });
    $('#btn1').unbind().click(function() {
        $('.box2_txt').hide();
        $('#btn2').removeClass('btn_active').addClass('btn2')
        $('.step4_box1').show();
        $('.step4_box2').hide();
        $('#btn1').removeClass('btn1').addClass('btn_active')
        $('#submit_button4').show();
        $('#submit_button5').hide();
        formId = document.getElementById("reg_v2_4");
        needSubmit(formId);
    });

    //进度条
    $('.submit_button1').unbind().click(function() {
        $('#bar').removeClass('bar_bg1').addClass('bar_bg2')
    });
    $('.submit_button2').unbind().click(function() {
        $('#bar').removeClass('bar_bg2').addClass('bar_bg3')
    });
    $('.submit_button3').unbind().click(function() {
        $('#bar').removeClass('bar_bg3').addClass('bar_bg4')
    });
    $('.btn-sub').unbind().click(function() {
        if ($('.box1').is(':visible')) {
            $('#bar .txt1').click(function() {
                $('#bar').removeClass().addClass('bar_bg1')
            });
        };
        if ($('.box2').is(':visible')) {
            $('#bar .txt2').click(function() {
                $('#bar').removeClass().addClass('bar_bg2')
            });
        }
        if ($('.box3').is(':visible')) {
            $('#bar .txt3').click(function() {
                $('#bar').removeClass().addClass('bar_bg3')
            });
        }
        if ($('.box4').is(':visible')) {
            $('#bar .txt4').click(function() {
                $('#bar').removeClass().addClass('bar_bg4')
            });
        }
    });
    $(document).ready(function() {
        $(document).scroll(function() {
            var top1 = $('#txt1').offset().top - $(document).scrollTop();
            var top2 = $('#txt2').offset().top - $(document).scrollTop();
            var top3 = $('#txt3').offset().top - $(document).scrollTop();
            var top4 = $('#txt4').offset().top - $(document).scrollTop();
            if (top1 > 2 && top1 < 20) {
                $('#bar').removeClass().addClass('bar_bg1')
            } else if (top2 > 2 && top2 < 200) {
                $('#bar').removeClass().addClass('bar_bg2')
            } else if (top3 > 2 && top3 < 200) {
                $('#bar').removeClass().addClass('bar_bg3')
            } else if (top4 > 2 && top4 < 300) {
                $('#bar').removeClass().addClass('bar_bg4')
            }
        });
    });
    //回显
    if ($('#or_btn').val() == 1) {
        $('.step4_box1').show();
        $('.step4_box2').hide();
        $('#submit_button5').hide();
        $('#submit_button4').show();
    } else if ($('#or_btn').val() == 2) {
        $('.step4_box1').hide();
        $('.step4_box2').show();
        $('#submit_button4').hide();
        $('#submit_button5').show();
    }
    $('.tit_bankwangdian').html($('#bankzone').val());
    nameBankzone();

});