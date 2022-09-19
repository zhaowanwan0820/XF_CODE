//初始化
;(function($) {
    $(function() {
        $("#password-form").valid();
        $("#validNum").blur(function() {
            if ($(this).val().length > 0) {
                $(".step1-tips").css("display", "none");
                  $("#step-one").removeAttr('disabled').removeClass("gray");
            }
        });
        //控制获取验证码
        var clearTime = false;
        $("#bt").click(function() {
            var button = $(this);
            var type = button.attr("data-type");
            var url = '/user/EMCode';
            var data = {is_edit:1};
            if(type == 2){//第一步
                url = '/user/MCode';
                var mobile = $("#mobile").val();
                var token = $("#token").val();
                var token_id = $("#token_id").val();
                var captcha = $("#input-captcha").val();
                var mobile_reg = /^1[3456789]\d{9}$/;
                if(!mobile_reg.test(mobile)){
                    $.showErr('手机号码格式不正确！');
                    return false;
                }
                data = {mobile:mobile,type:1,isrsms:0,sms_type:1,token:token,token_id:token_id,captcha:captcha};
            }

            button.attr('disabled', 'disabled');
            updateTimeLabel(180);
            function updateTimeLabel(duration) {
               var timeRemained = duration;
                    var timer = setInterval(function() {
                        button.val(timeRemained + '秒后重新发送').attr('disabled', 'disabled').addClass("gray");
                        ;
                        timeRemained -= 1;
                        if (timeRemained == -1 || clearTime == true) {
                            clearInterval(timer);
                            button.val('获取手机验证码').removeAttr('disabled').removeClass("gray");
                            clearTime = false;
                        }

                    }, 1000);
            }
            //获取验证码
            $.post(url,data,function(rs){
                var rs = $.parseJSON(rs);
                if(rs.code == 1){
                    updateTimeLabel(180, 'action-send-mobile-code');
                    return;
                }else {
                    $.showErr(rs.message, function(){}, "提示");
                    clearTime = true;
                }
            });//post
        });

    });

})(jQuery);
