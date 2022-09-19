(function () {
    var emailRegEx = /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i;
    $(function () {
        $("#reset").click();
        $('.error_tip').html('');
        
        $("#new_mail").on("blur", function () {
            var result = validNewEmail($(this));
            var tip = $(this).next();
            if (!result.status) {
                toNO(tip, result.info);
            } else {
                toYES(tip);
            }
        });
        
        $("#re_new_mail").on("blur", function () {
            var result = validReNewEmail();
            tip = $("#re_new_mail").next();
            if (!result.status) {
                toNO(tip, result.info);
            } else {
                toYES(tip);
            }
        });

        $("#modify").submit(function () {
                var result, tip;

                var $newmail = $("#new_mail");
                var newEmail = $.trim($newmail.val());

                result = validNewEmail($newmail);
                tip = $newmail.next();
                if (!result.status) {
                    toNO(tip, result.info);
                    return false;
                } else {
                    toYES(tip);
                    var $reNewmail = $("#re_new_mail");
                    tip = $reNewmail.next();
                    var result = validReNewEmail($reNewmail);
                    if (!result.status) {
                        toNO(tip, result.info);
                        return false;
                    } else {
                        toYES($("span.new_mail"));
                    }
                }
            }
        )

    });
   
    function validNewEmail(e) {
        var email = $.trim(e.val());
        
        var realOldEmail = $('#real_old_email').val();
        if (realOldEmail == email) {
            return {status:0,info:"新邮箱和旧邮箱不能一致"};
        }
        
        if (!emailRegEx.test(email)) {
        	return {status:0,info:"请填写有效的邮箱地址"};
        }

        return {status:1,info:""};
    }

    function validReNewEmail() {
        var newEmail = $.trim($("#new_mail").val());
        var reNewEmail = $.trim($("#re_new_mail").val());

        var result = {status:1,info:''};
        if (newEmail !== reNewEmail) {
        	result = {status:0,info:"邮箱和确认邮箱不一致"};
        }
        return result;
    }

    function toNO(element, msg) {
        element.addClass("no_tip").removeClass("yes_tip").html(msg)
    }

    function toYES(element) {
        element.addClass("yes_tip").removeClass("no_tip").html("")
    }
})();