$(function () {
    (function () {
        // 选择城市
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
                "callBack": function (val) {
                    //回调函数（val为选择的值）
                    $cityDiv.html(val).data("value", val);
                    $("#address").val(val);
                    $("#addressLabelId span").hide();
                }
            });
        }

        $("#selectAddressBtn").click(function () {
            setTimeout(function () {
                picker3();
            }, 500);
        });

        // 添加好友
        $('#JS_add_btn').on('click', function () {
            var text = $('#JS_friends_info').html();
            $(text).appendTo('.JS_reg_info');
            var titNum = $('.JS_friend_tit').length;
            $('.JS_friend_tit').last().html('- 亲友' + titNum + ' -');
            $('.JS_err_tips').last().css('visibility','hidden');
            $('.JS_fri_info').last().find('.JS_select_friend').css({ 'color': '#D3D3D3', 'font-size': '12px' });
            $('.JS_select_friend').on('change', function () {
                $(this).css({ 'color': '#030303', 'font-size': '15px' });
            });
        });
        // select样式
        $('.JS_select_friend').on('change', function () {
            $(this).css({ 'color': '#030303', 'font-size': '15px' });
        })
        // 点击提交
        //验证手机号码正则
        var CheckMobile = function (val) {
            return /^1[3456789]\d{9}$/.test(val)
        }
        $('.JS_submit_btn').on('click', function () {
            var count = $('.error_tips').length;
            for(i=0;i < count; i++){
                if($('.error_tips').eq(i).css('visibility') == 'visible') return;
            }
            $('#JS_ui_pop_regi').show();
        });
        // 手机号输入框失去焦点验证手机号码格式
        $('.p_onlineorg').on('blur','.JS_fri_tel',function(){
            if (!CheckMobile($(this).val()) && $(this).val() != '' && $.trim($(this).val()) != '') {
                $(this).siblings('.error_tips').css('visibility', 'visible');
            } else {
                $(this).siblings('.error_tips').css('visibility', 'hidden');
            }
        });
        $('.JS_ok').on('click', function () {
            var friArr = [];
            $('.JS_fri_info').each(function (index, element) {
                friArr.push({
                    id: index + 1,
                    relation_type: $(this).find('.JS_select_type option:selected').val(),
                    relation_name: $(this).find('.JS_fri_name').val(),
                    relation_sex: $(this).find('.JS_select_sex option:selected').val(),
                    relation_age: $(this).find('.JS_fri_age').val(),
                    relation_phone: $(this).find('.JS_fri_tel').val()
                });
            });
            $.ajax({
                url: '/activity/SaveActivityUser',
                data: {
                    token:$('#token').val(),
                    from_login: $('#from_login').val(),
                    activity_id: $('#activity_id').val(),
                    address: $('#address').val(),
                    relations: JSON.stringify(friArr)
                },
                type: 'post',
                dataType: 'json',
                success: function (json) {
                    var isApp = $('#isApp').val();
                    $('#JS_reg_result').html(json.msg);
                    $('#JS_ui_pop_result').show();
                    // 报名成功或失败
                    if (isApp == '1') {
                        $('.JS_result_ok').attr('href', 'firstp2p://api?type=native&name=home');
                    } else {
                        $('.JS_result_ok').attr('href', 'https://m.ncfwx.com');
                    }
                },
                error: function () {
                }
            });
        })

        $('.JS_cancel,.JS_result_ok,.JS_ok').on('click', function () {
            $(this).closest('.ui_popup').hide();
        })

        //判断是否在微信打开
        function p2pBrowser() {
            var u = navigator.userAgent
            return {
                wx: /MicroMessenger/i.test(u),
                webkit: /AppleWebKit/i.test(u),
                gecko: /gecko/i.test(u),
                ios: /\(i[^;]+;( U;)? CPU.+Mac OS X/.test(u),
                android: /android/i.test(u),
                iPhone: /iPhone/i.test(u),
                iPad: /iPad/i.test(u),
                app: /wx/i.test(u),
                androidApp: /wxAndroid/i.test(u),
                iosApp: /wxiOS/i.test(u)
            }
        }
        if (p2pBrowser().wx) {
            $('#JS_shear_btn').show();
        }
        $('#JS_shear_btn').on('click', function () {
            $('#JS_ui_pop_share').show();
        });
        $('#JS_ui_pop_share').on('click', function () {
            $(this).hide();
        });
        var isApp = $('#isApp').val();
        if(isApp == 1){
            window.location.href="firstp2p://api?type=rightbtn&title="
        }
    })();
});