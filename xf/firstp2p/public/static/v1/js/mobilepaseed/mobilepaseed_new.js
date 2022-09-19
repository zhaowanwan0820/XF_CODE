seajs.use(['jquery', 'validator', 'formsubmit', 'dialog', 'upload'], function ($, undefined, formsubmit, dialog, upload) {


    $.validator.config({
        //stopOnError: false,
        //theme: 'yellow_right',
        defaultMsg: "{0}格式不正确",
        loadingMsg: "正在验证...",
        rules: {
            digits: [/^\d+$/, "请输入数字"]
            , letters: [/^[a-z]+$/i, "{0}只能输入字母"]
            , tel: [/^(?:(?:0\d{2,3}[\- ]?[1-9]\d{6,7})|(?:[48]00[\- ]?[1-9]\d{6}))$/, "电话格式不正确"]
            , mobile: [/^1[3456789]\d{9}$/, "手机号格式不正确"]
            , email: [/^[\w\+\-]+(\.[\w\+\-]+)*@[a-z\d\-]+(\.[a-z\d\-]+)*\.([a-z]{2,4})$/i, "邮箱格式不正确"]
            , qq: [/^[1-9]\d{4,}$/, "QQ号格式不正确"]
            //,date: [/^\d{4}-\d{1,2}-\d{1,2}$/, "请输入正确的日期,例:yyyy-mm-dd"]
            , time: [/^([01]\d|2[0-3])(:[0-5]\d){1,2}$/, "请输入正确的时间,例:14:30或14:30:00"]
            , ID_card: [/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[A-Z])$/, "请输入正确的身份证号码"]
            , url: [/^(https?|ftp):\/\/[^\s]+$/i, "网址格式不正确"]
            , postcode: [/^[1-9]\d{5}$/, "邮政编码格式不正确"]
            , chinese: [/^[\u0391-\uFFE5]+$/, "请输入中文"]
            , chineseName: [/^[\u0391-\uFFE5]{2,6}$/, "请输入2-6个汉字中文"]
            , username2: [/^\w{4,16}$/, "请输入4-16位数字、字母、下划线"]
            , password: [/^[0-9a-zA-Z]{6,16}$/, "密码由6-16位数字、字母组成"]
            , fileImage: [/\.jpg$|\.png$/, "图片格式仅限JPG,PNG"]
            , address: [/^[\u0391-\uFFE5][\u0391-\uFFE5\d]+$/, "填写正确的地址"]
            , name: [/^.{2,20}$/, "请正确输入{0}"]
            , judegRepeat: function (el) {
                var val = $.trim($(el).val());
                return $.ajax({
                    url: '/deal/dobidstepone',
                    type: 'post',
                    data: { "username": val },
                    dataType: 'json',
                    success: function (data) {
                        data.error = "该用户名已被使用";
                    }
                });
            }
            , ID_card_more: function (el) {
                var val = $.trim($(el).val());
                var regs = ['', /^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[a-zA-Z])$/, /^.{1,50}$/, /^.{1,50}$/, /^.{1,50}$/];
                var id_type = document.getElementById('id_type');
                var _val = id_type.value;
                //正则验证通过后做唯一性验证
                var index;
                var hash = { "1": 1, "4": 2, "6": 3, "2": 4 };
                if (hash[id_type.value] == undefined) {
                    return;
                }
                index = hash[id_type.value];

                if (regs[index] && regs[index].test(val)) {
                    if (storages[val]) {
                        return;
                    }

                    return $.ajax({
                        url: './IdcardExist',
                        type: 'post',
                        //idno idType id_type
                        data: { "idno": val, "idType": document.getElementById('id_type').value },
                        dataType: 'json',
                        success: function (data) {
                            if (data.code == "0") {
                                storages[val] = true;
                            } else {
                                data.error = data.msg;
                            }
                        }
                    })

                } else {
                    return {
                        "error": "证件格式不正确，请重新输入"
                    }
                }

            }
        }
    });

    $(function () {
        control_checkbox($);
        var hasError;
        var vhash = {
            "agreement": function (el) {

                if (!$('#agree').is(':checked')) {
                    var $td = $('#agree').parent();
                    $td.find(".errorDiv").html('不同意支付协议无法完成注册');
                    hasError = true;
                } else {
                    var $td = $('#agree').parent();
                    $td.find(".errorDiv").html('');
                    hasError = false;
                }
                return hasError;
            }
        };
        $('.pp_checkbox').click(function (ev) {
            vhash["agreement"]();
        });
        var valid = function (ev) {
            var hasError = false;
            $('#register-form').find(".errorDiv").html("");
            $('#register-form').find("i").removeClass();
            for (var k in vhash) {
                if (typeof vhash[k] == 'function' && vhash[k](false) && !hasError) {
                    hasError = true;
                }
            }
            //有错误 true
            if (hasError) {
                ev.preventDefault();
            }
            return hasError;
        };
        $('#licaiuser').click(function (ev) {
            if (valid(ev)) {
                return false;
            }
            else {
                $('#mobilepaseed').submit();
            }
        });


    });

    //多选控件
    function control_checkbox($) {
        var ele = $('a[name=control_checkbox]');
        ele.bind("click", function (e) {
            var _this = $(this), id = _this.attr("data-for");
            if (!id) {
                return;
            }
            var el = document.getElementById(id);
            _this.toggleClass('current_checkbox');
            _this.hasClass('current_checkbox') ? (el.checked = true) : (el.checked = false);
        });
    }
});
