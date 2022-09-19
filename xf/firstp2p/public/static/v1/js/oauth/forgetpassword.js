/**
 * 验证密码合法性，true表示合法，false表示不合法
 * @returns {boolean}
 */
function validPasFn(){
    var pasInput=$('#input-password');
    var pasVal=pasInput.val(),
        errorTip=pasInput.next(),
        checkResult={},
        hasError=true,
        mobileInput = $('#input-mobile');
    if(pasVal==""){
        errorTip.html('密码不能为空');
    }else if(pasVal==mobileInput.val()){
        errorTip.html(validErrorArr[3]);
    }else{
        checkResult=checkPasStrenth(pasVal);
        if(!checkResult.isValid){
            errorTip.html(checkResult.textTip);
        }else{
            errorTip.html('');
            $.ajax({
                type: "post",
                data: {
                    pwd: pasVal,
                    mobile: mobileInput.val()
                },
                async:false,
                url: '/user/PasswordCheck',
                dataType: "json",
                success: function (data) {
                    if(data.errorCode==0){
                        errorTip.html('密码安全程度：'+data.errorMsg);
                        hasError=false;
                    }else{
                        errorTip.html(data.errorMsg);
                    }
                },
                error: function(e) {

                }
            });
        }
    }
    return !hasError;
}
;
(function($) {
    //function
    $(function() {

        var type = 1;
        $('.tab li a').click(function(e) {
            e.preventDefault();
            $('.tab li').removeClass('active');
            $(this).parent().addClass('active');
            $('.tab-content').hide();
            type = $(this).attr('rel');
            $('.tab-content-' + type).show();
            $('#input_type').val(type);
        });

        var mobileRegEx = /^1[3456789]\d{9}$/;

        $('#action-send-mobile-code').click(
                function(ev) {
                    var inputMobile = $("#input-mobile").val();
                    var button = $(this);
                    var token_id = $("#token_id").val();
                    var token = $("#token").val();

                    $(this).prev().html('');
                    if (inputMobile == '') {
                        $(this).prev().html('手机号不能为空');
                        return false;
                    } else if (!mobileRegEx.test(inputMobile)) {
                        $(this).prev().html('手机号格式不正确');
                        return false;
                    }

                    button.attr('disabled', 'disabled');

                    function updateTimeLabel(duration) {
                        var timeRemained = duration;
                        var timer = setInterval(function() {
                            button.val(timeRemained + '秒后重新发送').attr(
                                    'disabled', 'disabled');
                            timeRemained -= 1;
                            if (timeRemained == -1) {
                                clearInterval(timer);
                                button.val('获取手机验证码').removeAttr('disabled');
                            }
                        }, 1000);
                    }

                    $.ajax({
                        type : "post",
                        data : {
                            type : '2',
                            mobile : inputMobile,
                            token : token,
                            token_id : token_id
                        },
                        url : button.data("url"),
                        async : false,
                        dataType : "json",
                        success : function(data) {
                            if (data.code == 1) {
                                updateTimeLabel(180);
                                return;
                            } else {
                                button.val('获取手机验证码').removeAttr('disabled');
                                $.showErr(data.message, function() {
                                }, "提示");

                            }
                        }
                    });

                });
        var valid = function(ev) {
            var errors = [];

            var inputMobile = $.trim($('#input-mobile').val()), inputMobileVc = $
                    .trim($('#input-mobile-vc').val());
            if (inputMobile == '') {
                $('#input-mobile').next().html("手机号不能为空");
                return false;
            } else if (!mobileRegEx.test(inputMobile)) {
                $('#input-mobile').next().html("手机号格式不正确");
                return false;

            } else {
                $('#input-mobile').next().html("");
            }
            if (inputMobileVc == '') {
                $('#input-mobile-vc').next().html("手机验证码不能为空");
                return false;

            } else {
                $('#input-mobile-vc').next().html("");
            }

            var inputPassword = $('#input-password').val(), inputRetypePassword = $(
                    '#input-retype-password').val();
             /*if (inputPassword == '') {
                $('#input-password').next().html("密码不能为空");
                return false;

            } else if (inputPassword.length < 5 || inputPassword.length > 25) {
                $('#input-password').next().html("密码长度为5-25位");
                return false;

            } else {
                $('#input-password').next().html("");
            }*/
            if(!validPasFn()){
                return false;
            }

            if (inputRetypePassword == '') {
                $('#input-retype-password').next().html("确认密码不能为空");
                return false;

            } else if (inputRetypePassword != inputPassword) {
                $('#input-retype-password').next().html("两次填写的密码不一致");
                return false;

            } else {
                $('#input-retype-password').next().html("");
            }
            return true;
        };

        $('#password-form').submit(function(ev) {
            if (!valid(ev)) {
                return false;
            } else {
                return true;
            }
        });

        $('#password-form').on("blur focus", ".text:not(#input-password)", function(ev) {
            if (this.value) {
                valid(ev);
            }
        });
        $('#input-password').on('blur',function(){
            validPasFn();
        });

/*        $('#password-form').on("input propertychange", ".text", function(ev) {
            valid(ev);
        });
*/
    })
})(jQuery);

window.onload = function() {
	    $("#password-form .text").val("");
};