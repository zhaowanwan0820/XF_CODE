;
(function($) {
    $(function() {
        var area_select = null;
        var cityJson = json;
        // 多重下拉列表

        var justify = function() {
            var $pro = $("select[name='province']"),
                $city = $("select[name='city']"),
                $area = $("select[name='areaA']"),
                $p = $pro.closest('li'),
                $wrong = $p.find(".n-error"),
                $right = $p.find(".n-ok"),
                len = $(".select").length,
                str = "所在地区不能为空",
                show = function($obj, str) {
                    $obj.find(".n-msg").html(str);
                    $obj.show().siblings().hide();
                };
            if ($pro.find("option:selected").val() == 0) {
                show($wrong, str);
                return false;
            }
            if ($city.find("option:selected").val() == 0) {
                show($wrong, str);
                return false;
            }

            if ($area.find("option:selected").val() == 0) {
                show($wrong, str);
                return false;
            }
            show($right, "");
            return true;

        };



        //placehoder
        $(".int_placeholder").each(function() {
            var p_text = $(this).attr("data-placeholder");
            new Firstp2p.placeholder(this, {
                placeholder_text: p_text == null ? "请输入" : p_text
            });
        });

        //弹窗调用
        function popup(str) {
            var word = (!!str ? str : '正在提交,请稍后...');
            var html = '';
            html += '<div class="wee-send">';
            html += '<div class="send-input">';
            html += '<div class="error-box">';
            html += '<div class="error-wrap">';
            html += '<div class="e-text" >' + word + '</div>';
            html += '</div>';
            html += '</div>';
            html += '<p style="text-align:center;color:red;"></p>';
            html += '</div>';
            html += '</div>';
            if ($('.weedialog .dialog-content').length <= 0) {
                $.weeboxs.open(html, {
                    boxid: null,
                    boxclass: 'weebox_send_msg',
                    showTitle: true,
                    contentType: 'text',
                    showButton: false,
                    showOk: true,
                    okBtnName: '完成注册',
                    showCancel: false,
                    title: '提交表单',
                    width: 600,
                    height: 460,
                    type: 'wee'
                });
            } else {
                $('.weedialog .error-box>p').html(word);
            }
        }

        //设为默认地址
        $(".add_list_con").on("click", ".j_radio,.j_label_default", function() {
            var $t = $(this);
            if ($t.hasClass('select')) {
                return false;
            }
            $(".is_default").addClass('none');
            $t.closest('dl').find(".is_default").removeClass('none');
            $.ajax({
                url: $t.data("url"),
                type: 'post',
                data: {
                    id: $t.data("id"),
                    token_id: $t.data("tokenid"),
                    token: $t.data("token"),
                    isDefault: 1
                },
                dataType: "json",
                success: function(json) {
                    if (json.errno == 0) {
                        alert('设置成功!');
                    } else {
                        alert(json.error);
                    }
                },
                error: function() {
                    alert("网络异常，稍后重试");
                }
            });
        });

        //删除收货地址
        $(".j_addr_del").on("click", function() {
            var $t = $(this);
            if (confirm("确定要删除收货地址吗？")) {
                $.ajax({
                    url: $t.data("url"),
                    type: 'post',
                    data: {
                        id: $t.data("id"),
                        token_id: $t.data("tokenid"),
                        token: $t.data("token"),
                    },
                    dataType: "json",
                    success: function(json) {
                        if (json.errno == 0) {
                            $t.closest('dl').remove();
                        } else {
                            alert(json.error);
                        }

                    },
                    error: function() {
                        alert("网络异常，稍后重试");
                    }
                });
            }
            return false;
        });


        //新增收货地址
        $(".j_addr_btn , .j_addr_edit").on("click", function() {
            var $t = $(this) ,
            url = !$("#delivery").attr("action") ? "/address/NewAddr" : $("#delivery").attr("action");

            if($t.hasClass('j_addr_btn')){
                if($("dl").length >= 5){
                    alert("收货地址最多添加5条");
                    return false;
                }
            }else{
                url = '/address/UpdateAddr'
            }
            popup(add_addr_str);
            area_select = new Firstp2p.mulselect(".cityDom", {
                mulDom: ".cityDom",
                defaultdata: !!$("#cityDom").data("defaultdata") ? $("#cityDom").data("defaultdata").split(":") : ["请选择省", "请选择市", "请选择县"],
                selectsClass: "select",
                url: cityJson,
                jsonsingle: "n",
                jsonmany: "s"
            });

            $('#delivery').validator({
                rules: {
                    consignee: [/^[A-Za-z\u0391-\uFFE5]{2,25}$/, '请输入2-25个字符，限汉字或字母'],
                    mobile: [/^1[3456789]\d{9}$/, '手机号格式不正确'],
                    address: [/^[,，。：\.\-\"\“\”\(\)（）A-Za-z\u0391-\uFFE5\d\u0020]{5,80}$/, '请输入5-80个常用字符'],
                    postalcode: [/^\d{6}$/, '请输入6位数字'],
                    phonecode: [/^\d{6}$/, '请输入6位数字'],
                },
                fields: {
                    consignee: "收货人姓名:required;consignee",
                    mobile: "手机号: required;mobile;",
                    address: "详细地址: required;address;",
                    postalcode: "postalcode;",
                    phonecode: "手机验证码 : required;phonecode;",
                },
                isNormalSubmit: false,
                valid: function(form) {
                    var $f = $(form),
                        areaStr = "",
                        len = $("#cityDom .select").length,
                        me = this;
                    if (!justify()) {
                        return false;
                    }
                    $("#cityDom .select").each(function(i, v) {
                        if (i != len - 1) {
                            areaStr += v.value + ":";
                        } else {
                            areaStr += v.value;
                        }

                    });

                    $("#area").val(areaStr);

                    var dataObj = {
                        "consignee": $("#consignee").val(),
                        "mobile": $("#mobile").val(),
                        "area": $("#area").val(),
                        "address": $("#address").val(),
                        "token_id": $("#token_id").val(),
                        "token": $("#token").val(),
                        "id" : $t.data("id")
                    };

                    //this.holdSubmit();
                    $.ajax({
                        url: url,
                        data: dataObj,
                        dataType: "json",
                        type: "post",
                        success: function(data) {
                            if (data.errno === 0) {
                                //alert("表单提交成功");
                                location.reload();
                            } else {
                                alert(data.error);
                            }
                            me.holdSubmit(false);
                        }
                    });
                },
                invalid: function(form) {
                    var $f = $(form);
                    if (!justify()) {
                        return false;
                    }
                    //$f.action = $f.attr("action");
                }
            });

            $("#cityDom").on("change", "select", function() {
                $("#area_msg").css("display" , "block");
                justify();
            });

            if ($t.hasClass('j_addr_edit')) {
                $.ajax({
                    url: $t.data("url"),
                    type: 'post',
                    data: {
                        id: $t.data("id")
                    },
                    dataType: "json",
                    success: function(json) {
                        if (json.errno == 0) {
                            var data = json.data;
                            $("#consignee").val(data.consignee);
                            $("#mobile").val(data.mobile);
                            $("#cityDom").data("defaultdata", data.area);
                            area_select.destroysel();
                            area_select = new Firstp2p.mulselect(".cityDom", {
                                mulDom: ".cityDom",
                                defaultdata: !!$("#cityDom").data("defaultdata") ? $("#cityDom").data("defaultdata").split(":") : ["请选择省", "请选择市", "请选择县"],
                                selectsClass: "select",
                                url: cityJson,
                                jsonsingle: "n",
                                jsonmany: "s"
                            });
                            $("#area_msg").hide();
                            $("#address").val(data.address);
                            $("#token_id").val(data.tokenId);
                            $("#token").val(data.token);
                        } else {
                            alert(json.error);
                        }

                    },
                    error: function() {
                        alert("网络异常，稍后重试");
                    }
                });
            }

            return false;
        });
        $(".j_radio").each(function(){
            var $t = $(this);
            if(!$t.hasClass('is_checked')){
                $t.removeAttr('checked');
            }
        });
    });
})(jQuery);
