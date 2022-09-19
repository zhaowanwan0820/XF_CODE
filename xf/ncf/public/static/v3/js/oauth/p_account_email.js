(function () {
    var emailRegEx = /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i;
    $(function () {
        $("#reset").click();
        $('.error_tip').html('');

        // $("#old_mail").on("blur", function () {
        //  var result = validOldEmail();
        //  var tip = $(this).next();
        //     if (!result.status) {
        //         toNO(tip, result.info);
        //     } else {
        //         toYES(tip);
        //     }
        // });
        $("#password").on("blur", function () {
            var result = validPassword();
            var tip = $("#password").next();
            if (!result.status) {
                toNO(tip, result.info);
            } else {
                toYES(tip);
            }
        });
        
        $("#new_mail").on("blur", function () {
            var result = validNewEmail($(this));
            var tip = $(this).next();
            if (!result.status) {
                toNO(tip, result.info);
            } else {
                toYES(tip);
            }
        });

        $("#captcha").on("blur", function () {
            captcha = $("#captcha").val();
            var result = verifyAjax(captcha);
            var tip = $("#code-err");
            if (!result.status) {
                toNO(tip, result.msg);
            } else {
                toYES(tip);
            }
        });
        
        // $("#re_new_mail").on("blur", function () {
        //     var result = validReNewEmail();
        //     tip = $("#re_new_mail").next();
        //     if (!result.status) {
        //         toNO(tip, result.info);
        //     } else {
        //         toYES(tip);
        //     }
        // });

        $("#submit_button").click(function () {
                var result, tip;
                // var $oldemail = $("#old_mail");
                // var oldEmail = $.trim($oldemail.val());
                
                // result = validOldEmail();
                // tip = $oldemail.next();
                // if (!result.status) {
                //     toNO(tip, result.info);
                //     return false;
                // } else {
                //     toYES(tip);
                // }
                
                var $password = $("#password");
                var password = $password.val();
                result = validPassword();
                tip = $password.next();
                if (!result.status) {
                    toNO(tip, result.info);
                    return false;
                } else {
                    toYES(tip);
                }

                var $newmail = $("#new_mail");
                var newEmail = $.trim($newmail.val());

                result = validNewEmail($newmail);
                tip = $newmail.next();
                if (!result.status) {
                    toNO(tip, result.info);
                    return false;
                } else {
                    toYES(tip);
                    // var $reNewmail = $("#re_new_mail");
                    // tip = $reNewmail.next();
                    // var result = validReNewEmail();
                    // if (!result.status) {
                    //     toNO(tip, result.info);
                    //     return false;
                    // } else {
                    //     toYES($("span.new_mail"));
                    // }
                }
                captcha = $("#captcha").val();
                var result = verifyAjax(captcha);
                var tip = $("#code-err");
                if (!result.status) {
                    toNO(tip, result.msg);
                    return false;
                } else {
                    toYES(tip);
                }
            }
        );
        // 图形验证码逻辑
        var img_captcha = $("#img_captcha");
        img_captcha.click(function() {
            img_captcha.attr('src', '/verify.php?w=93&h=34&rb=0&rand=' + new Date().valueOf());
        });
        img_captcha.attr('src', '/verify.php?w=93&h=34&rb=0&rand=' + new Date().valueOf());

    });
    
    // function validOldEmail() {
        
    //     var mail = $.trim($("#old_mail").val());
        
    //     if (!emailRegEx.test(mail)) {
    //      return {status:0,info:"邮箱输入错误"}
    //     }
        
    //     var result = getAjaxData('email', mail);
    //     return result;
    // }
    
    function getAjaxData(type_info, value_info){
        $.ajax({
            type: "POST",
            cache: false,
            async: false,
            url: "/user/validator",
            dataType: "json",
            data: {type:type_info, value:value_info}
        }).done(function (data) {
            result = data;
        });
        return result;
    }

    function validPassword() {
        var password = $.trim($("#password").val());
        
        if (password.length < 5 || password.length > 25) {
            return {status:0,info:"密码输入错误"};
        }
        var result = getAjaxData('password', password);
        return result;
    }

    function validNewEmail(e) {
        var email = $.trim(e.val());
        var emailRest = email.split('@')[1];
        
        // var realOldEmail = $('#real_old_email').val();
        // if (realOldEmail == email) {
        //     return {status:0,info:"新邮箱和旧邮箱不能一致"};
        // }
        
        if (!emailRegEx.test(email)) {
            return {status:0,info:"请输入正确的邮箱"};
        }

        if (emailRest.toLocaleLowerCase() == 'ncfwx.com' || emailRest.toLocaleLowerCase() == 'ncfwx.cn' || emailRest.toLocaleLowerCase() == 'firstp2p.cn' || emailRest.toLocaleLowerCase() == 'firstp2p.com'){
            return {status:0,info:"请填写有效的邮箱地址"};
        }

        return {status:1};
        //var result = getAjaxData('new_email', email);
        //return result;
    }

    function validReNewEmail() {
        var newEmail = $.trim($("#new_mail").val());
        var reNewEmail = $.trim($("#re_new_mail").val());

        var result = {status:1,info:''};
        if (newEmail !== reNewEmail) {
            result = {status:0,info:"新邮箱和确认邮箱不一致"};
        }
        return result;
    }

    function toNO(element, msg) {
        element.addClass("no_tip").removeClass("yes_tip").html(msg)
    }

    function toYES(element) {
        element.addClass("yes_tip").removeClass("no_tip").html("")
    }

    function verifyAjax(val) {

        var url = '/user/captchaCheck';
        var captcha = val;
        var hash = {status: true, msg:''};

        $.ajax({
            type: "get",
            data: {captcha: captcha},
            dataType: "json",
            url: url,
            async: false,
            success: function(data) {

                if (data && data.code != '0') {
                    if(data.code == '-1')
                    {
                        //本地存储
                        hash.status = false;
                        hash.msg = data.msg;
                    }
                    else
                    {
                        hash.status = false;
                        hash.msg = data.msg;
                    }
                } else if (data == null) {
                    hash.status = false;
                    hash.msg = "系统发生错误";

                } else {
                    hash.status = true;
                    hash.msg = "";
                }

            }
        });

        //console.log(hash);
        return hash;
    }
})();