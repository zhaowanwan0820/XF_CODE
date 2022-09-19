;
(function($) {
    $(function() {
        (function() {
            // tooltip
            $(document).tooltip({
                track: true
            });
            var msglock = false;

            var popStr = "设置";

            // 短信验证弹出框
            $('#add_submit_btn').click(function() {
                getCode();


            });

            var popuoStr = '<div class="wee-send">\
            <div class="send-input">\
                <div class="error-box">\
                    <div class="error-wrap">\
                        <div class="e-text" style="width: 305px;">请填写6位数字验证码</div>\
                    </div>\
                </div>\
                <p>已向&nbsp;<span class="color_green">' + $(".mobile_num").html() + ' </span>&nbsp;发送验证短信</p>\
                <input type="text" class="ipt-txt w150" id="pop_code" placeholder="短信验证码" maxlength="10" value="">\
                <input type="button" id="action-send-code" class="reg-sprite btn-blue-h34 btn-gray-h34" value="发送">\
            </div>\
            </div>';
            //popup
            function popup() {

                if ($('.weedialog .dialog-content').length <= 0) {
                    $.weeboxs.open(popuoStr, {
                        boxid: null,
                        boxclass: 'ui_send_msg',
                        contentType: 'text',
                        showButton: true,
                        showOk: true,
                        okBtnName: '下一步',
                        showCancel: false,
                        title: popStr + '收货地址',
                        width: 463,
                        height: 125,
                        type: 'wee',
                        onclose: function() {

                        },
                        onok: function() {
                            var code = $(".ui_send_msg #pop_code").val();
                            var data = {
                                "code": code,
                                "sp": 1
                            };
                            var url = '/account/DeliveryPro';
                            $text = $(".ui_send_msg .error-box").find('.e-text'),
                                showError = function() {
                                    $(".ui_send_msg .error-box").css({
                                        'display': 'block',
                                        'visibility': 'visible'
                                    });
                                    $(".ui_send_msg .ipt-txt").addClass("err-shadow");
                                };

                            if (!/^\d{6}$/.test(code)) {
                                showError();
                                $text.html("请填写6位数字验证码");
                                return;
                            }
                            $.ajax({
                                url: url,
                                type: "post",
                                data: data,
                                dataType: "json",
                                beforeSend: function() {
                                    // $text.html("正在提交，请稍候...");
                                },
                                success: function(data) {
                                    // alert(JSON.stringify(data));
                                    if (data.errorCode == 1) {
                                        showError();
                                        $text.html(data.errorMsg);
                                    } else {
                                        $.weeboxs.close();
                                        location.href = "/address";
                                    }
                                },
                                error: function() {
                                    showError();
                                    $text.html("服务器错误，请重新再试！");
                                }
                            });
                            // $.weeboxs.close();
                        }
                    });
                } else {
                    $('.weedialog .dialog-content').html(popuoStr);
                }


            }

            // 点击“重新发送”按钮获取短信验证码
            $('body').on("click", "#action-send-code", function() {

                getCode();
            });

            function popup_1(str) {
                var word = (!!str ? str : '正在提交,请稍后...');
                var html = '';
                html += '<div class="wee-send">';
                html += '<div class="send-input">';
                html += '<div class="error-box">';
                html += '<div class="error-wrap">';
                html += '<div class="e-text" >' + word + '</div>';
                html += '</div>';
                html += '</div>';
                html += '<p></p>';
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
                        width: 250,
                        height: 120,
                        type: 'wee'
                    });
                } else {
                    $('.weedialog .dialog-content .e-text').html(word);
                }
            }

            var button = "";
            var errorSpan = "";
            var status = "";
            if ($("#add_submit_btn").text() == "修改") {
                status = 6;
                popStr = "修改";
            } else {
                status = 5;
            }

            function setProperty() {
                button = $(".ui_send_msg #action-send-code");
                bgGray();
                _reset();

            }

            var bgGray = function() {

                button.addClass("btn-gray-h34");
                button.val("正在获取中...");
                button.attr("disabled", "disabled");
            }

            var _set = function(msg) {
                var errorSpan = $(".ui_send_msg .error-box");
                errorSpan.css('visibility', 'visible');
                errorSpan.find('.e-text').html(msg);
                $(".ui_send_msg .ipt-txt").addClass("err-shadow");
            }

            var _reset = function() {
                var errorSpan = $(".ui_send_msg .error-box");
                errorSpan.css('visibility', 'hidden');
                errorSpan.find('.e-text').html('');
            }
            var timer = null;

            function updateTimeLabel(duration) {
                //var button = $(".ui_send_msg #action-send-code");
                var timeRemained = duration;
                //button.val(timeRemained + '秒后重新发送');
                timer = setInterval(function() {
                    button.val(timeRemained + '秒后重新发送');

                    timeRemained -= 1;
                    if (timeRemained == -1) {
                        clearInterval(timer);
                        msglock = false;
                        button.val('重新发送').removeAttr('disabled').removeClass("btn-gray-h34");
                    }
                }, 1000);
            }

            var callback = function(data) {
                //var button = $(".ui_send_msg #action-send-code");

                if (!msglock) {
                    updateTimeLabel(60, 'action-send-code');
                    msglock = true;
                }
                if (data.code != 1) {
                    _set(data.message);
                } else {
                    _reset();
                }


                //button.val('重新发送').removeAttr('disabled').removeClass("btn-gray-h34");
            }

            function getCode() {
                var data = {
                    "is_delivery": status
                };
                var getcodeUrl = '/user/EMCode';

                $.ajax({
                    url: getcodeUrl,
                    type: "post",
                    data: data,
                    dataType: "json",
                    beforeSend: function() {
                        //popup_1();
                    },
                    success: function(result) {
                        // alert(JSON.stringify(result));

                        //$(".dialog-mask").remove();
                        //$(".weebox_send_msg").remove();
                        if ($(".dialog-content").length <= 0) {
                            popup();
                        }

                        setProperty();
                        callback(result);
                    },
                    error: function() {

                    }
                });
            }



            // 取消授权的弹窗
            Firstp2p.cancle_shouquan = {
                //可以取消授权，两个按钮时
                twobutton : function(str,temp){
                    var settings = $.extend({
                            title: "取消授权提示",
                            ok: $.noop,
                            text: str,
                            close: $.noop
                        });
                        html = '',
                        instance = null;
                        html += '' + settings.text + '';
                        instance = $.weeboxs.open(html, {
                            boxid: null,
                            boxclass: 'setUpDialog setUpDialogTwo',
                            contentType: 'text',
                            showButton: true,
                            showOk: true,
                            showCancel:true,
                            okBtnName: '取消授权',
                            cancelBtnName: '放弃',
                            title: settings.title,
                            width: 300,
                            type: 'wee',
                            onok: function(settings) {
                                var data = {};
                                if(temp ==0){
                                    data["grant"] = "quickbid";
                                    $t = $(".JS_cancel_zdx");
                                }else{
                                    data["grant"] = "yxt";
                                    $t = $(".JS_cancel_yxt");
                                }
                                $.ajax({
                                    url: '/account/authCancel',
                                    type: "post",
                                    data: data,
                                    dataType: "json",
                                    success: function(json) {
                                        // location.reload();
                                        if(json.errno == 0&&json.data.status ==1){
                                            $t.hide();
                                        }else{
                                            Firstp2p.alert({
                                                text : '<div class="tc">'+  json.error +'</div>',
                                                ok : function(dialog){
                                                    dialog.close();
                                                }
                                            });
                                        }
                                    },
                                    error: function(){
                                        alert("网络错误");
                                    }
                                });
                                settings.close(settings);
                            }
                        });
                    },
                //不可取消授权，一个按钮
                onebutton : function(str){
                    var settings = $.extend({
                            title: "取消授权提示",
                            ok: $.noop,
                            text: str,
                            close: $.noop,
                            okBtnName: '知道了'
                        });
                        html = '',
                        instance = null;
                        html += '' + settings.text + '';
                        instance = $.weeboxs.open(html, {
                            boxid: null,
                            boxclass: 'setUpDialog setUpDialogOne',
                            contentType: 'text',
                            showButton: true,
                            showOk: true,
                            showCancel: false,
                            okBtnName: settings.okBtnName,
                            title: settings.title,
                            width: 300,
                            type: 'wee'
                        });
                    }
                }

            //个人设置-取消授权
            // 随鑫约取消授权
            $(".j_mcancel").click(function() {
                var str = null;
                if($(".isReserveValid").val() ==0){
                    str = "<p>取消授权后，投资P2P项目、网贷P2P账户划转至网信账户将需要验证密码，同时将无法使用随鑫约预约投资</p>";
                    Firstp2p.cancle_shouquan.twobutton(str,0);
                    $('.setUpDialogTwo').prop({id: 'sv_cancel_mmservice'});
                }else{
                    str = "<p style='text-align:center;'>随鑫约有未完成的投资，无法取消授权</p>";
                    Firstp2p.cancle_shouquan.onebutton(str);
                }

            });
            // 银信通取消授权
            $('.j_ycancel').on('click', function(obj) {
                var str = null;
                if($(".isYxtValid").val() ==0){
                    str = "<p style='text-align:center;'>取消授权后，将无法使用银信通借款</p>";
                    Firstp2p.cancle_shouquan.twobutton(str,1);
                    $('.setUpDialogTwo').prop({id: 'sv_cancel_creditloan'});
                }else{
                    str = "<p style='text-align:center;'>银信通有未完成的还款，无法取消授权</p>";
                    Firstp2p.cancle_shouquan.onebutton(str);
                }

            });
            $(".JS_kt_p2p").click(function() {
                Firstp2p.supervision.wancheng();
            });
        })();



        //设置密保问题JS
        (function() {


            var msglock = false;
            var button = null;
            function setProperty() {
                button = $(".ui_send_msg #ques-send-code");
                bgGray();
                _reset();

            };

            var bgGray = function() {

                button.addClass("btn-gray-h34");
                button.val("正在获取中...");
                button.attr("disabled", "disabled");
            };

            var _set = function(msg) {
                var errorSpan = $(".ui_send_msg .error-box");
                errorSpan.css('visibility', 'visible');
                errorSpan.find('.e-text').html(msg);
                $(".ui_send_msg .ipt-txt").addClass("err-shadow");
            };

            var _reset = function() {
                var errorSpan = $(".ui_send_msg .error-box");
                errorSpan.css('visibility', 'hidden');
                errorSpan.find('.e-text').html('');
            };

            var timer = null;

            function updateTimeLabel(duration) {
                //var button = $(".ui_send_msg #action-send-code");
                var timeRemained = duration;
                //button.val(timeRemained + '秒后重新发送');
                timer = setInterval(function() {
                    button.val(timeRemained + '秒后重新发送');

                    timeRemained -= 1;
                    if (timeRemained == -1) {
                        clearInterval(timer);
                        msglock = false;
                        button.val('重新发送').removeAttr('disabled').removeClass("btn-gray-h34");
                    }
                }, 1000);
            }

            var callback = function(data) {

                if (!msglock) {
                    updateTimeLabel(60);
                    msglock = true;
                }
                if (data.code != 1) {
                    _set(data.message);
                } else {
                    _reset();
                }
            }


            var id_html = '';
            id_html += '<div class="wee-pwp">';
            id_html += '<p class="pwp-decr font16">为了保证账户安全，设置密保问题前请先进行身份验证</p>';
            id_html += '<a href="javascript:;"  class="common-sprite btn-red-h46 j_valid_phone">验证手机号码</a>';
            id_html += '<a href="/account/ProtectAnswerPwd"  class="common-sprite btn-red-h46 mt20">回答密保问题</a>';
            id_html += '</div>';
            var popuoStr = '<div class="wee-send">\
             <div class="send-input">\
                 <div class="error-box">\
                     <div class="error-wrap">\
                        <div class="e-text" style="width: 305px;">请填写6位数字验证码</div>\
                        </div>\
                    </div>\
                    <p>已向&nbsp;<span class="color_green">' + $(".mobile_num").html() + ' </span>&nbsp;发送验证短信</p>\
                    <input type="text" class="ipt-txt w150" id="pop_code" placeholder="短信验证码" maxlength="10" value="">\
                    <input type="button" id="ques-send-code" class="reg-sprite btn-blue-h34 btn-gray-h34" value="发送">\
              </div>\
              </div>';
            var mibao_box = null,
                valid_box = null,
                getMsgCode = function(type) {
                    var getcodeUrl = '/account/protectionMobileCode',
                        data = {
                            type: type
                        };
                    $.ajax({
                        url: getcodeUrl,
                        data: data,
                        type: "post",
                        dataType: "json",
                        beforeSend: function() {},
                        success: function(result) {
                            showbox();
                            setProperty();
                            callback(result);
                        },
                        error: function() {

                        }
                    });
                },
                showbox = function() {
                    !!mibao_box && mibao_box.close();
                    !!valid_box && valid_box.close();
                    valid_box = $.weeboxs.open(popuoStr, {
                        boxid: null,
                        boxclass: 'ui_send_msg',
                        contentType: 'text',
                        showButton: true,
                        showOk: true,
                        okBtnName: '完成验证',
                        showCancel: false,
                        title: '验证手机号码',
                        width: 463,
                        height: 125,
                        type: 'wee',
                        onclose: function() {

                        },
                        onok: function() {
                            var code = $(".ui_send_msg #pop_code").val();
                            var data = {
                                "code": code,
                                "sp": 1
                            };
                            var url = '/account/DoCheckProtectionMobile';
                            $text = $(".ui_send_msg .error-box").find('.e-text'),
                                showError = function() {
                                    $(".ui_send_msg .error-box").css({
                                        'display': 'block',
                                        'visibility': 'visible'
                                    });
                                    $(".ui_send_msg .ipt-txt").addClass("err-shadow");
                                };

                            if (!/^\d{6}$/.test(code)) {
                                showError();
                                $text.html("请填写6位数字验证码");
                                return;
                            }
                            $.ajax({
                                url: url,
                                type: "post",
                                data: data,
                                dataType: "json",
                                beforeSend: function() {
                                    // $text.html("正在提交，请稍候...");
                                },
                                success: function(data) {
                                    // alert(JSON.stringify(data));
                                    if (data.errorCode === 0) {
                                        $.weeboxs.close();
                                        location.href = data.url;
                                    } else {
                                        showError();
                                        $text.html(data.errorMsg);
                                    }
                                },
                                error: function() {
                                    showError();
                                    $text.html("服务器错误，请重新再试！");
                                }
                            });
                            // $.weeboxs.close();
                        }
                    });

                },
                showwaybox = function() {
                    mibao_box = $.weeboxs.open(id_html, {
                        boxid: null,
                        boxclass: '',
                        contentType: 'text',
                        showButton: true,
                        showOk: false,
                        okBtnName: '',
                        showCancel: false,
                        title: '选择身份验证方式',
                        width: 455,
                        height: 192,
                        type: 'wee'
                    });
                };



            $('#pw_submit_button').click(function() {
                getMsgCode($(this).data("type"));
            });

            // 修改密保弹出框
            $('#pw_submit_button_01').click(function() {
                showwaybox();

            });

            //验证手机号码
            $("body").on("click", ".j_valid_phone", function() {
                getMsgCode(1);
            });

            $('body').on("click", "#ques-send-code", function() {
                getMsgCode(1);
            });
            //存管降级开关打开时个人设置页提示
            $(".j_isSvDown").click(function() {
                Firstp2p.alert({
                    text : '<div class="tc">'+  svMaintainMessage +'</div>',
                    ok : function(dialog){
                        dialog.close();
                    }
                });
            });



        })();
        // 设置密保短信验证弹出框
    });

})(jQuery);
(function($){
    $(function(){
        // 已绑卡未验证跳转
        $("#yanzheng").click(function(){
            $('#bindCardForm').submit();
        });
        // 已实名未绑卡设置跳转
        $("#shezhi").click(function(){
            $('#bindCardForm').submit();
        });
        // 问卷调查_是否愿意评估弹出框
        var id_html = '',
            pg_box = null;
        id_html += riskTs;
        id_html += '<div class="tc">';
        id_html += '<a href="javascript:;"  class="dialog_ok j_valid_pg">开始评估</a>';
        id_html += '<a href="javascript:;"  class="dialog_cancel mt15 j_cancel_pg">暂不参与</a>';
        id_html += '</div>';
        var showwqbox = function() {
            !!pg_box && pg_box.close();
            pg_box = $.weeboxs.open(id_html, {
                boxid: null,
                boxclass: 'wq_weebox',
                contentType: 'text',
                showButton: true,
                showOk: false,
                okBtnName: '',
                showCancel: false,
                title: '您是否愿意现在就进行评估？',
                width: 700,
                type: 'wee'
            });
        };
        $('#wj_submit_button').click(function() {
            showwqbox();
        });
        $("body").on("click",".j_valid_pg",function(){
            pg_box.close();
            location.href="/account/riskassessment";
        });
        $("body").on("click",".j_cancel_pg",function(){
            pg_box.close();
        });

    });
})(jQuery);

