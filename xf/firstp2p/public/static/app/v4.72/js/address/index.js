
function _addCookie(name, value, second) {
    var exdate = new Date((new Date()).getTime() + second * 1000);
    document.cookie = name + "=" + escape(value) + ";path=/" +
        ((second == null) ? "" : ";expires=" + exdate.toGMTString());
}

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

var triggerScheme = function(scheme) {
    var iframe = document.createElement("iframe");
    iframe.src = scheme;
    iframe.style.display = "none";
    document.body.appendChild(iframe);
};

var validNull = function($obj, msg) {
    if (!$.trim($obj.val())) {
        P2PWAP.ui.showErrorTip(msg);
        return 0;
    } else {
        return 1;
    }
};

var validMobile = function($obj, msg) {
    if (!/^1[3456789]\d{9}$/.test($obj.val())) {
        P2PWAP.ui.showErrorTip(msg);
        return 0;
    } else {
        return 1;
    }
};



var validLength = function($obj, length, msg) {
    if ($.trim($obj.val()).length < length) {
        P2PWAP.ui.showErrorTip(msg);
        return 0;
    } else {
        return 1;
    }
};
var is_native = P2PWAP.util.getUrlParam("fromtype");

var getStore = function(){
    console.log("删除成功");
}
//获取默认地址id
if($(".item-addr").length == 1){
    var del_address_id = _getCookie("del_address_id");
    var default_address_id = $('.item-addr').data("id");
    if(!del_address_id){
        _addCookie("del_address_id",default_address_id,100000000)
    }
}

//新建收货地址 表单提交
function add_submit() {
    setTimeout(function() {
      triggerScheme('firstp2p://api?type=rightbtn&title=' + encodeURIComponent('保存') + '&callback=add_submit');
    }, 1);
    var $f = $("#form"),
        lock = $f.data("lock");
    if (!validNull($("#consignee"), "收货人不能为空")) {
        return;
    }

    if (!(validNull($("#mobile"), "联系电话不能为空") && validMobile($("#mobile"),  "请输入正确的手机号"))) {
        return;
    }

    if (!validNull($("#area"), "请填写所在区域")) {
        return;
    }

    if (!(validNull($("#address"), "详细地址不能为空") && validLength($("#address"), 5, "详细地址不能少于5个字"))) {
        return;
    }
    if (lock == '0') {
        $f.data("lock", 1);
        $.ajax({
            url: $f.attr("action"),
            type: 'post',
            data: $f.serialize(),
            dataType: "json",
            success: function(json) {
                if (json.errno == 0) {
                    P2PWAP.ui.showErrorTip('添加成功!');
                    if(is_native == "native"){
                        _addCookie("del_address_id" , json.data.id ,100000000);
                        var addressStr = JSON.stringify(json.data);
                        triggerScheme("firstp2p://api?type=storeset&name=takeaddress&value=" + addressStr);
                        location.href = 'firstp2p://api?type=closeall';
                    } else {
                        location.href = 'firstp2p://api?type=local&action=closeself';
                    }
                    // if($("#entryType").val() == 'o2o'){
                    //     location.href = '/address/index?token=' + $("#token").val() + "&returnUrl="+ $("#returnUrl").val() + "&entryType="+  $("#entryType").val();
                    // }else{
                    //     location.href = 'firstp2p://api?type=local&action=closeself';
                    // }
                } else {
                    P2PWAP.ui.showErrorTip(json.error);
                }
                $f.data("lock", 0);
            },
            error: function() {
                $f.data("lock", 0);
                P2PWAP.ui.showErrorTip("网络异常，稍后重试");
            }
        });
    }

}
;
(function($) {
    //收货地址首页
    $(function() {
        //判断是否从o2o过来的
        var is_o2o = $("#entryType").val();
        $(".item-addr").on("touchstart" , function(){
            var $t = $(this),
            id = $t.data("id");
            if(is_o2o == 'o2o'){
                _addCookie("address_id" , id ,100000000);
                location.href = 'firstp2p://api?type=local&action=closeself&needrefresh=true';
            } else if(is_o2o == "candy_snatch"){
                $.ajax({
                    type: "post",
                    dataType:"json",
                    url: "/candysnatch/SnatchPrizeAddress",
                    data: {
                        periodId: $("#returnUrl").val(),
                        token: $("#token").val(),
                        addressId: id
                    },
                    success: function (json) {
                        triggerScheme("firstp2p://api?type=local&action=closeself&needrefresh=true");
                    }
                })
            } else if(is_native == "native"){
                _addCookie("del_address_id" , id ,100000000);
                var addressStr = '{"area":"' + $t.data("area") + '","address":"'+ $t.data("address") + '","consignee":"' + $t.data("name") + '","mobile":"' + $t.data("mobile") +'","id":"'+ id +'"}';
                triggerScheme("firstp2p://api?type=storeset&name=takeaddress&value=" + addressStr);
                location.href = 'firstp2p://api?type=closeall';
            }
        });

        //设为默认
        $(".j_addr_list").on("touchstart" ,function(event) {
            var $t = $(this);
            //阻止事件冒泡
            event.stopPropagation();
            if ($t.hasClass('select')) {
                return false;
            }
            $t.closest('.address').find(".select").html('<em></em><span>设为默认</span>').removeClass('select');
            $t.addClass('select').html('默认地址');
            $.ajax({
                url: $t.data("url"),
                type: 'post',
                data: {
                    token: $("#token").val(),
                    id: $t.data("id"),
                    isDefault: 1
                },
                dataType: "json",
                success: function(json) {
                    if (json.errno == 0) {
                        P2PWAP.ui.showErrorTip('设置成功!');
                    } else {
                        P2PWAP.ui.showErrorTip(json.error);
                    }
                },
                error: function() {
                    P2PWAP.ui.showErrorTip("网络异常，稍后重试");
                }
            });
        });
    });
    //新建地址页面
    $(function() {
        function picker3() {
            var $cityDiv = $("#svalue-add"),
                nowValue = $cityDiv.data("value");
            new Picker({
                "title": '请选择', //标题(可选)
                "defaultValue": nowValue, //默认值-多个以空格分开（可选）
                "data": cityData, //数据(必传)
                "keys": {
                    "id": "Code",
                    "value": "Name",
                    "childData": "level" //最多3级联动
                }, //数组内的键名称(必传，id、text、data)
                "callBack": function(val) {
                    //回调函数（val为选择的值）
                    $cityDiv.html(val).data("value", val);
                    $("#area").val(val);
                    $("#addressLabelId").html("");
                }
            });
        }

        $("#selectAddressBtn").click(function() {
            setTimeout(function() {
                picker3();
            }, 500);
        });

        $("#switch").bind("click", function() {
            var $t = $(this);
            $t.toggleClass('is_default');
            if ($t.hasClass('is_default')) {
                $("#isDefault").val(1);
            } else {
                $("#isDefault").val(0);
            }
        });

        var do_addr_del_ajax = function($t) {
            $.ajax({
                url: $t.data("url"),
                type: 'post',
                data: {
                    token: $("#token").val(),
                    id: $t.data("id")
                },
                dataType: "json",
                success: function(json) {
                    if (json.errno == 0) {
                        location.href = 'firstp2p://api?type=local&action=closeself';
                    } else {
                        P2PWAP.ui.showErrorTip(json.error);
                    }
                },
                error: function() {
                    P2PWAP.ui.showErrorTip("网络异常，稍后重试");
                }
            });
        }

        var do_del_ajax = function($t) {
            $.ajax({
                url: $t.data("url"),
                type: 'post',
                data: {
                    token: $("#token").val(),
                    id: $t.data("id")
                },
                dataType: "json",
                success: function(json) {
                    if (json.errno == 0) {
                        $t.closest('.item-addr').remove();
                        var addressId = $t.closest('.item-addr').data("id");
                        var takeaddressId = _getCookie("del_address_id") || null;
                        if(addressId == takeaddressId){
                            triggerScheme("firstp2p://api?type=storeset&name=takeaddress&value=0");
                            _addCookie("del_address_id" , "" ,100000000);
                        }
                        if ($('.item-addr').length <= 0) {
                            $(".p_error").css("display", "block");
                        } else {
                            $(".p_error").css("display", "none");
                        }
                    } else {
                        P2PWAP.ui.showErrorTip(json.error);
                    }

                },
                error: function() {
                    P2PWAP.ui.showErrorTip("网络异常，稍后重试");
                }
            });
        };

        $('#dialog1').dialog({
            autoOpen: false,
            closeBtn: false,
            width: 250,
            buttons: {
                '取消': function() {
                    this.close();
                },
                '确定': function(event) {
                    do_del_ajax($(".j_index_del").eq($('#dialog1').data("click_index")));
                    this.close();
                }
            }
        });

        $('#dialog2').dialog({
            autoOpen: false,
            closeBtn: false,
            width: 250,
            buttons: {
                '取消': function() {
                    this.close();
                },
                '确定': function(event) {
                    do_addr_del_ajax($(".j_addr_del"));
                    this.close();
                }
            }
        });

        $(".j_addr_del").on("click touchstart", function() {
            var $t = $(this);
            $('#dialog2').dialog("open");
        });

        $(".j_index_del").on("touchstart", function(event) {
            event.stopPropagation();
            var $t = $(this);
            $('#dialog1').data("click_index", $('.item-addr').index($t.closest('.item-addr')));
            $('#dialog1').dialog("open");
        });

        $(".j_href").on("touchstart", function(event) {
            event.stopPropagation();
            var href = $(this).data("url"),
                url = "firstp2p://api?type=webview&url=" + encodeURIComponent(location.protocol + "//" + location.host + href);
            $(this).attr("href", url);

        });

        $(".j_list_limit").on("click", function() {
            var $t = $(this);
            if ($(".item-addr").length >= 5) {
                P2PWAP.ui.showErrorTip("收货地址最多添加5条");
                return false;
            }
        })

        //点击回填收货地址
        $('.JS_choose_address').on('click', function() {
            var name = $(this).data('name')
            var mobile = $(this).data('mobile')
            var area = $(this).data('area')
            var address = $(this).data('address')
            var id = $(this).data('id')
            if(is_native == 'candy') {
                var CaddressStr = '{"consignee":"' + name + '","mobile":"'+ mobile + '","area":"' + area + '","address":"' + address +'","id":"'+ id +'"}';
                triggerScheme("firstp2p://api?type=storeset&name=candytakeaddress&value=" + CaddressStr);
                location.href = 'firstp2p://api?type=local&action=closeself';
            }
        })

    });

})(Zepto);