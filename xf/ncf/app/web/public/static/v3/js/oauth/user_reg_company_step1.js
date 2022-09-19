$(function() {
    //placehoder
    $(function(){
        $(".int_placeholder").each(function() {
            var p_text = $(this).attr("data-placeholder");
            new Firstp2p.placeholder(this, {
                placeholder_text: p_text == null ? "请输入": p_text,
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


        //海外手机号下拉框样式修改
        // (function() {
            $("#dateInput1").datepicker({
                onClose: function(selectedDate) {
                    $("#dateInput2").datepicker("option", "minDate", selectedDate);
                    if($("#is_permanent").val()=="0"){
                        if ($("#dateInput2").val() == "") {
                            fullnameVld({
                                el: $("input[name=credentials_expire_at]"),
                                status: false,
                                key: "credentials_expire_at",
                                msg: "请选择正确的有效期"
                            });
                        }
                    }else{
                        if ($("#dateInput1").val() != "") {
                            $("#dateInput1").parent().find('.er-icon').css('display', 'block');
                            $("#dateInput1").parent().find('.error-wrap').hide().html("");
                            $("#dateInput1").removeClass('err-shadow');
                        }
                    }
                    $("#dateInput1").removeClass('err-shadow');
                },
                yearRange : "c-117:c+100"
            });
            $("#dateInput2").datepicker({
                onClose: function(selectedDate) {
                    $("#dateInput1").datepicker("option", "maxDate", selectedDate);
                        if ($("#dateInput1").val() != "" && $("#dateInput2").val() != "") {
                        $("#dateInput1").parent().find('.er-icon').css('display', 'block');
                        $("#dateInput1").parent().find('.error-wrap').hide().html("");
                        $("#dateInput2 , #dateInput1").removeClass('err-shadow');
                    }
                    $("#dateInput2").removeClass('err-shadow');
                },
                yearRange : "c-117:c+100"
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
                    $t.find('.j_select').html('<span style="color:#555353;font-size: 14px;">+' + $li.data('value') + '</span>');
                    $t.find('.j_select_type').html('<span style="color:#555353;font-size: 14px;">' + $li.data('value') + '</span>');
                    // $t.parent().find(".er-icon").css('display', 'block');
                }
            });

            var test = $(".company_reg_select_box input");
            $('.JS_credentials_type ,.js_credentials_type ,.JS_legalbody_credentials_type').select({
                onSelectChange: function($t, $input, index, $li) {
                    console.log($t.context);
                }
            });
            $('.JS_bank_name').select({
                onSelectChange: function($t, $input, index, $li) {
                    nameBankzone();
                }
            });
            var test = $(".company_reg_select_box input");
            test.attr("data-con", "require");
            $(".JS_sms_country_code .j_select").css({
                'width': 65,
                'padding': '0px 0px 0px 13px',
                'background-position': '58px 13px',
                'color': '#333'
            }).html('<span style="color:#555353;font-size: 14px;">+86</span>');
            $(".j_select_type").css({
                'width': 295,
                'overflow':"hidden",
                'padding-right':26
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

    var util = new Util();
    var __formpage__ = formpage({
        conf: {
            frm: document.getElementById("reg_v2"),
            vld: validate_middleware.validate({
                "password": ["密码", true, null, /^.{5,25}$/, '请输入5-25位数字、字母及常用符号'],
                "credentials_expire_date": ["日期", true, null, null, null],
                "credentials_expire_at": ["日期", true, null, null, null],
                "sms_phone": ["接收短信通知手机号", true, null, /^1[3456789]\d{9}$/, '手机号格式不正确'],
                "consignee_phone": ["企业联络人手机号", true, null, /^1[3456789]\d{9}$/, '手机号格式不正确'],
                "company_name": ["企业全称", true, null, /^[\u4E00-\u9FA5\（\）]+$/,'企业全称只允许输入中文和中文括号'],
                // "credentials_no": ["证件号码", true, null, /^[0-9]+[a-zA-Z]*.{5,8}$/, '证件号码格式不正确'],
                "credentials_type": ["证件类型", true, null, null, null],
                "user_name": ["用户名", true, null, /^([A-Za-z])[\w-]{3,19}$/, '请输入4-20位字母、数字、下划线、横线，首位只能为字母'],
                "registration_address":["企业注册地址",null, null, null, null],
                "contract_address":["企业联系地址",null, null, null, null],
                "legalbody_name": ["企业全称",true, null, null, null],
                // "legalbody_credentials_no": legalbody_credentials_no,
                "legalbody_credentials_type": ["证件类型", true, null, null, null],
                "card_name": ["开户行",true, null, null, null],
                "bankcard": ["银行卡号",true, null, /^[0-9]*$/, "银行卡号格式不正确"],
                "bank_id": ["开户行名称", true, null, null, null],
                // "bankzone":["开户网点", null, null, null, null],
                "legalbody_email":["邮箱", true, [1, 100], /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i, '邮箱地址无效'],
                "major_name":["账户管理人姓名",true, null, null, null],
                "major_condentials_type":["账户管理人证件类别",true, null, null, null],
                // "major_condentials_no":["账户管理人证件号码", true, null, /^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[A-Z])$/, "请输入正确的身份证号码"],
                "major_mobile":["账户管理人手机号码", true, null, /^1[3456789]\d{9}$/, '手机号格式不正确'],
                "major_email":["账户管理人邮箱地址",true, [1, 100], /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i, '邮箱地址无效'],
                "major_contract_region":["账户管理人联系地址",true, null, null, null],
            }),
            custom_vld: {
                company_name: function(data) {
                    return fullnameVld(data);
                },
                credentials_expire_date: function(data) {
                    return fullnameVld(data);
                },

                credentials_expire_at: function(data){
                    if($("#is_permanent").val() == "0" ){
                        return fullnameVld(data);
                    }else{
                        return true;
                    }
                },
                credentials_no: function(data) {
                    return credentials_no(data);
                },
                credentials_type: function(data) {
                    return credentialsTypeVld(data);
                },
                registration_address: function(data){
                    return fullnameVld(data);
                },
                contract_address: function(data){
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
                legalbody_email:function(data){
                    return fullnameVld(data);
                },
                card_name:function(data){
                    return fullnameVld(data);
                },
                bankcard:function(data){
                    return fullnameVld(data);
                },
                bank_id:function(data){
                    return credentialsTypeVld(data);
                },
                bankzone:function(data){
                    return credentialsTypeVld(data);
                },
                major_name:function(data){
                    return fullnameVld(data);
                },
                major_condentials_type:function(data){
                    return fullnameVld(data);
                },
                major_condentials_no:function(data){
                    return idcard(data);
                },
                major_mobile:function(data) {
                    return commVld(data);
                },
                major_email:function(data){
                    return fullnameVld(data);
                },
                major_contract_region:function(data){
                    return fullnameVld(data);
                }
            },
            callback: function(data, els) {
                submitVld();
                return false;
            },
            focus: false,
            util: util
        }
    });
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
        } else if (data.key == 'consignee_phone' || data.key == 'bankzone' || data.key =='major_mobile') {
            ele = $(el).parent().parent();
        }
        var _reset = function(ele) {
            if (data.key == 'consignee_phone' || data.key == 'bankzone' || data.key =='major_mobile') {
                $(el).parent().removeClass('err-shadow');
            } else {
                $(el).removeClass('err-shadow');
            }

            ele.find(".er-icon").css('display', 'none');
            ele.find(".error-wrap").css('display', 'none');
        }

        var _error = function(ele, msg) {
            if (data.key == 'consignee_phone' || data.key == 'bankzone' || data.key =='major_mobile') {
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
        if(data.key == 'is_permanent'){
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
            if(is_permanent.val() != "0"){
                $(".j_date").attr('disabled', 'disabled').val("").removeClass('err-shadow');
                if($(".j_date_start").val() != ""){
                    status = true;
                    msg = "";
                }else{
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
            }else{
                $(".j_date").removeAttr('disabled');
                if($(".j_date").val() != ""){
                    status = true;
                    msg = "";
                }else{
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

        var hash = idcardajax(val);

        if (hash.status) {
            _right(ele);
        } else {
            _error(ele, hash.msg);
        }
        data.status = hash.status;
        return data;
    }

    function idcardajax(val) {
        var idcard = val;
        if($("input[name='step']").val()==2){
            var ele = $('#legalbody_credentials_no');
        }else if($("input[name='step']").val()==4){
            var ele = $('#major_condentials_no');
        }
        var hasError = false;
        var hash = {
            status: true,
            msg: ''
        };
        if($("input[name='step']").val()==2){
            var tel_code = $("#legalbody_credentials_type").val();
        }else if($("input[name='step']").val()==4){
            var tel_code = $("#major_condentials_type").val();
        }
        if(tel_code == 1){
            var mobileRegEx = /^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[A-Z])$/;
        }else{
            var mobileRegEx = /\S*$/;
        }
        if($("input[name='step']").val()==2){
            var tel = $('#legalbody_credentials_no').val();
        }else if($("input[name='step']").val()==4){
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
        if(legalbody_credentials_type ==3){
            var mobileRegEx = /^[0-9]|[a-zA-Z]*$/;
        }else if(legalbody_credentials_type == 1){
            var mobileRegEx = /^[0-9]*$/;
        }else{
            var mobileRegEx = /\S*$/;
        }

        var legalbody_credentials_no = $('input[name="credentials_no"]').val();
        if (!legalbody_credentials_no || legalbody_credentials_no == null) {
            hash.status = false;
            hash.msg = "证件号码不能为空";
        } else if ((!mobileRegEx.test(legalbody_credentials_no) || legalbody_credentials_no.length != 15) && legalbody_credentials_type ==1) {
            hash.status = false;
            hash.msg = "证件号码格式不正确";
        } else if((!mobileRegEx.test(legalbody_credentials_no) || legalbody_credentials_no.length != 18) && legalbody_credentials_type ==3){
            hash.status = false;
            hash.msg = "证件号码格式不正确";
        }else{
            hash.status = true;
            hash.msg = "";
        }
        credentialsnoajax[val] = {
            status: hash.status,
            msg: hash.msg
        };
        return hash;
    }




    function submitVld() {
        $.ajax({
            url: "/enterprise/Validate",
            type: "post",
            data: $("form").serialize() + "&isAjax=1&country_code=" + $("#sms_country_code").val(),
            dataType: "json",
            beforeSend: function() {
                //$text.html("正在提交，请稍候...");
                $("#submit_button").attr('disabled', 'disabled');
            },
            success: function(json) {
                console.log(json);
                if (json.code == 0) {
                    $(".error-wrap").hide();
                    location.href=$("form").attr("action");
                } else {
                    if (json.data[0]["field"] != "") {
                        var errorname = json.data[0]["field"];
                        if (errorname == "idcard" || errorname == "bankzone" || errorname == "consignee_phone" ||errorname == "major_mobile") {
                            commVld({
                                el: $("input[name=" + errorname + "]"),
                                status: false,
                                key: errorname,
                                msg: json.data[0]["message"]
                            });
                        }else {
                            fullnameVld({
                                el: $("input[name=" + errorname + "]"),
                                status: false,
                                key: errorname,
                                msg: json.data[0]["message"]
                            });
                        }

                    }
                }
                $("#submit_button").removeAttr('disabled');
            },
            error: function() {
                alert("服务器错误，请重新再试！");
                $("#submit_button").removeAttr('disabled');
            }
        });
    }


    function showNormal() {
        $("#error-wrap").find('.e-text').css("width", "267px");
        $("#error-wrap").removeClass("error-wrap2");
        $(".dialog-content").css("height", "125px");
    }
    function nameBankzone(){
        var bank = $(".JS_bank_id").html(),
            city = $("select[name='bankzone_region1'] option:selected").html();
            bank = $.trim(bank.replace(/^\s+|\s+$/g, ''));
            province = $("select[name='bankzone_region0'] option:selected").html();
        $.ajax({
            url: "/enterprise/bank?c="+ city +"&p=" + province + "&b=" + bank,
            type: "post",
            dataType: "json",
            async:false,
            success: function(json) {
                $(".JS_bankzone li").remove();
                for(var i=0;i<json.length;i++){
                    var bankHtml = "<li data-name=" + json[i]["id"] + " data-value=" + json[i]["name"] + ">"+ json[i]["name"] +"</li>";
                    $('.bankwangdian').prepend(bankHtml);
                }
                if($(".JS_bankzone").data('value') != ""){
                    $(".tit_bankwangdian").html($(".JS_bankzone").data('value'));
                    $('#bankzone').val($(".JS_bankzone").data('value'));
                }else{
                    $(".tit_bankwangdian").html($('.bankwangdian li').first().html());
                    $('#bankzone').val($('.bankwangdian li').first().html());
                }
                console.log(json.length);
                if(json.length==0) {
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
    if($("input[name='step']").val() == 3){
        nameBankzone();
    }
    $(".JS_bankzone_region").on("change","select[name='bankzone_region1']",function(){
        nameBankzone();
    });
    $(".JS_bankzone").on("click",".tit_bankwangdian",function(){
        $('.JS_bankzone .bankwangdian').removeClass('none');
    });
    $(".JS_bankzone").on("click","li",function(){
        $('.tit_bankwangdian').html($(this).html());
        $('#bankzone').val($(this).html());
        $('.JS_bankzone .bankwangdian').addClass('none');
    });
});