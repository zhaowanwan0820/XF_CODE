
        ;
(function($) {

    $(function() {

       //初始错误提示
        $(".msg").each(function() {
            if ($(this).text().length > 0) {
                $(this).parent().addClass("msg-show");
            }
        });

        $('#register-btn').click(function(ev) {
            //防止重复点击
            $('.register-btn').addClass("gray").attr('disabled', true);
            $(".reg_load").css("display", "block");
            $('#register-form').submit();
            // //ajax数据提交
            // $.ajax({
            //     type: 'POST',
            //     url: '...',
            //     data: $('#register_form').serialize(),
            //     dataType: 'json',
            //     beforeSend: function() {
            //         $load.css("display", "block");
            //     },
            //     success: function(data) {
            //         $load.css("display", "none");
            //         alert("注册成功");
            //     },
            //     error: function(xhr, type) {
            //         $load.css("display", "none");
            //         alert('注册失败!');
            //     }
            // });
        });

        //发送短信请求ajax

        var sendNum = 1;  //判断是否第一次点击发送

        $('#action-send-code').removeAttr('disabled').click(function(ev) {
            //判断电话号码是否正确
            if ($(".yes-mobile").css("display") == 'block') {
                var button = $(this);
                var token_id = $("#token_id").val();
                var token = $("#token").val();
                var captcha = $(".img-yanzheng").val();
                var mobile = $("#mobile").val();
                var btGray = function() {
                    button.addClass("phone_gray");
                    button.val("正在获取中...");
                    button.attr('disabled', 'disabled');
                };
                btGray();

                function updateTimeLabel(duration) {
                    var timeRemained = duration;
                    var timer = setInterval(function() {
                        button.val(timeRemained + '秒后重新发送');
                        timeRemained -= 1;
                        if (timeRemained == -1) {
                            clearInterval(timer);
                            button.val('重新发送').removeAttr('disabled').removeClass("gray");
                        }
                    }, 1000);
                }

                var sendMsg = function(url, isrsms) {
                    sendNum = 2;
                   // lock(btn_r, 'gray');
                    $.ajax({
                        type: "post",
                        data: {
                            type: '1',
                            isrsms: isrsms,
                            t: new Date().getTime(),
                            mobile: mobile,
                            token: token,
                            token_id: token_id,
                            captcha: captcha
                        },
                        url: url,
                        //async: false,
                        dataType: "json",
                        success: function(data) {
                            //unlock(btn_r, 'gray');
                            if (data.code == 0) {
                                updateTimeLabel(60);
                                return;
                            } else {
                                //$.showErr(data.message, function() {}, "提示");
                                  $(".no-code").addClass("msg-show").html(data.message);
                            }
                            button.val('重新发送').removeAttr('disabled').removeClass("phone_gray");
                        }
                    });
                };
                if (sendNum == 1) {
                    sendMsg(button.data("url"), 0);
                } else {
                    sendMsg(button.data("url"), 1);
                }

                //updateTimeLabel(60, 'action-send-code');
            } else {
                $(".no-mobile").addClass("msg-show");
            }
        });
    })
})(Zepto);
