
 ;(function($) {

     $(function() {
         var emailRegEx = /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i,
             captchaRegEx = /^\d{4,10}$/,
             mobileRegEx = /^1[3456789]\d{9}$/,
             userNameRegEx = /^[a-zA-Z][\w\-]{3,15}$/,
             passRegEx = /^.{6,26}$/,
             numRegEx = /^[_\d]+$/;
         var errorSpan = $('#error-row');
         var username_hash = {}, email_hash = {}, captcha_hash = {}, invite_hash = {}, mobile_hash = {};
         var store = {};
         var btn_r = $('#register-btn');
         var hasError;
         //解决firefox下刷新不了用户名的问题
        if ( window.location.href.toLowerCase().indexOf('/user/register') != -1 ) {
            $('#input-username').val('');
        }
        function html2code(s) {
            return s
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");
        }
        var actionSendCodeSwt = function(swt) {
            if (swt) {
                $('#action-send-code').removeAttr('disabled').removeClass("gray");
            } else {
                $('#action-send-code').attr('disabled', 'disabled').addClass("gray");
            }
        }

         var showError = function($obj, txt) {
             var $td = $obj.closest("td");
             $td.find(".errorDiv").html(txt);
             $td.find("i").css("display", "inline-block").removeClass("icon_yes");
         };
         var showTrue = function($obj) {
             var $td = $obj.closest("td");
             $td.find(".errorDiv").html("");
             $td.find("i").css("display", "inline-block").removeClass("icon_no").addClass("icon_yes")
         };

         var showTrueMsg = function($obj, txt) {
             var $td = $obj.closest("td");
             $td.find(".errorDiv").html(txt);
             $td.find("i").css("display", "inline-block").removeClass("icon_no").addClass("icon_yes")
         };

        var lock = function(ele, cls) {
            ele.addClass(cls);
            ele.attr('disabled',true);
        }
        var unlock = function(ele, cls) {
            ele.removeClass(cls);
            ele.attr('disabled',false);
        }

         var username_valid = function(tag) {
            var $user = $('#input-username');
            var userName = $.trim($user.val());

            var sep = ',', isErr = 0, msg = '';
            if (!userName) {
                 msg = '请填写用户名';
                 showError($user, msg);
                 isErr = 1;
            } else if ( userName.length < 6 ) {
                 msg = '用户名至少需要 6 个字符';
                 showError($user, msg);
                 isErr = 1;
            } else if ( userName.length > 16 ) {
                 msg = '用户名最多 16 个字符';
                 showError($user, msg);
                 isErr = 1;
            } else if ( !userNameRegEx.test(userName) ) {
                 msg = '用户名请输入4-16位字母、数字、下划线、横线，首位只能为字母';
                 showError($user, msg);
                 isErr = 1;
            } else if(numRegEx.test(userName)){
                 msg = '不能只有数字或下划线';
                 showError($user, msg);
                 isErr = 1;
            } else if ( userName in username_hash ) {
                 msg = '该用户名已被使用';
                 showError($user, msg);
                 isErr = 1;
            } else {
                showTrue($user);
            }

            //console.log('eee', firstp2p_username_hash.indexOf( (sep + userName + sep) ));
            if (isErr) {
                $user.get(0).setAttribute("iserr", isErr);
                $user.get(0).setAttribute("msg", msg);
                return !!isErr;
            }

            if (typeof tag != 'undefined') {
                return tag;
            }

            lock(btn_r, 'gray');
            showError($user, '');
            var url = "/user/userExist";
            $.ajax({
                type: "get",
                data: {"username": userName},
                url: url,
                success: function(data) {
                    try {
                        data = $.parseJSON(data);
                    } catch(e) {
                        data = null;
                    }
                    if (data && data.code != '0') {
                        if(data.code == '-1')
                        {
                                //本地存储
                                username_hash[userName] = true;
                                msg = '该用户名已被使用';
                                showError($user, msg);
                                isErr = 1;
                        }
                        else
                        {
                                msg = data.msg;
                                showError($user, msg);
                                isErr = 1;
                        }
                    } else if (data == null) {
                            msg = '系统发生错误';
                            showError($user, msg);
                            isErr = 1;
                    } else {
                            showTrue($user);
                    }
                    unlock(btn_r, 'gray');
                },
                error: function(e) {
                    unlock(btn_r, 'gray');
                    console.log('error', e);
                }
            });
            $user.attr("iserr", isErr);
            $user.attr("msg", msg);
            return !!isErr;
         };

         var vhash = {
                "username" : function(tag){
                    return username_valid(tag);
                },
                "password" : function(el){
                    var $ps = $('#input-password');
                     if (!$ps.val() ) {
                         showError($ps, '请填写登录密码');
                         hasError = true;
                     } else if (!passRegEx.test($ps.val())) {
                         showError($ps, '密码只能为 6 - 26 位数字，字母及常用符号组成');
                         hasError = true;
                     } else {
                         showTrue($ps);
                         hasError = false;
                     }
                     return hasError;
                },
                "email" : function(tag){
                    var $em = $('#input-email');
                    var val = $.trim($em.val());
                    var isErr = 0, msg = '';

                    if (!val) {
                        showError($em, '请填写邮箱');
                        hasError = true;
                    }else if (!emailRegEx.test(val)) {
                        showError($em, '邮箱地址无效');
                        hasError = true;
                    } else if ( val in email_hash ) {
                        msg = '邮箱地址已存在';
                        showError($em, msg);
                        hasError = true;
                    } else {
                        showTrue($em);
                        hasError = false;
                    }

                    if (hasError) {
                        return hasError;
                    }

                    if (typeof tag != 'undefined') {
                        return tag;
                    }

                    lock(btn_r, 'gray');
                    showError($em, '');

                    var url = "/user/mailExist";
                    $.ajax({
                        type: "get",
                        data: {"email": val},
                        url: url,
                        success: function(data) {
                            try {
                                data = $.parseJSON(data);
                            } catch(e) {
                                data = null;
                            }
                            if (data && data.code != '0') {
                                if(data.code == '-1')
                                {
                                        //本地存储
                                        email_hash[val] = true;
                                        msg = '邮箱地址已存在';
                                        showError($em, msg);
                                        isErr = 1;
                                }
                                else
                                {
                                        msg = data.msg;
                                        showError($em, msg);
                                        isErr = 1;
                                }
                            } else if (data == null) {
                                    msg = '系统发生错误';
                                    showError($em, msg);
                                    isErr = 1;
                            } else {
                                    showTrue($em);
                            }
                            unlock(btn_r, 'gray');
                        },
                        error: function(e) {
                            unlock(btn_r, 'gray');
                            console.log('error', e);
                        }
                    });
                    return !!isErr;
                    //email end
                },
                "captcha" : function(tag) {

                    var ele = $('#input-captcha');
                    var val = $.trim(ele.val());
                    var isErr = 0, msg = '';

                    if (!val) {
                        showError(ele, '请填写验证码');
                        hasError = true;
                    }else if (!captchaRegEx.test(val)) {
                        showError(ele, '验证码不正确');
                        hasError = true;
                    } else {
                        showTrue(ele);
                        hasError = false;
                    }

                    if (hasError) {
                        return hasError;
                    }

                    if (val in captcha_hash) {
                        return false;
                    }

                    if (typeof tag != 'undefined') {
                        return tag;
                    }

                    lock(btn_r, 'gray');
                    showError(ele, '');
                    var url = "/user/captchaCheck";
                    $.ajax({
                        type: "get",
                        data: {"captcha": val},
                        url: url,
                        success: function(data) {
                            try {
                                data = $.parseJSON(data);
                            } catch(e) {
                                data = null;
                            }
                            if (data && data.code != '0') {
                                if(data.code == '-1')
                                {
                                        //本地存储
                                        email_hash[val] = true;
                                        msg = '验证码不正确';
                                        showError(ele, msg);
                                        isErr = 1;
                                }
                                else
                                {
                                        msg = data.msg;
                                        showError(ele, msg);
                                        isErr = 1;
                                }
                            } else if (data == null) {
                                    msg = '系统发生错误';
                                    showError(ele, msg);
                                    isErr = 1;
                            } else {
                                    captcha_hash[val] = true;
                                    showTrue(ele);
                            }
                            unlock(btn_r, 'gray');
                        },
                        error: function(e) {
                            unlock(btn_r, 'gray');
                            console.log('error', e);
                        }
                    });
                    return !!isErr;
                    //图形验证码 end
                },
                "mobile" : function(tag){
                    var isErr = 0, msg = '';
                    var ele = $('#input-mobile');
                    var val = $.trim(ele.val());
                     if (!$.trim($('#input-mobile').val())) {
                         showError($('#input-mobile'), '手机号不能为空');
                         hasError = true;
                     }else if (!mobileRegEx.test($.trim($('#input-mobile').val()))) {
                         showError($('#input-mobile'), '手机号格式不正确');
                         hasError = true;
                     } else if ( val in mobile_hash ) {
                        msg = '手机号码已存在';
                        showError(ele, msg);
                        hasError = true;
                    } else {
                         showTrue($('#input-mobile'));
                         hasError = false;
                     }
                     //mobile_hash
                    if (hasError) {
                        return hasError;
                    }

                    if (typeof tag != 'undefined') {
                        return tag;
                    }
                    return !!isErr;

                },
                "code" : function(el){
                      if (!$.trim($('#input-code').val())) {
                         showError($('#input-code'), '请填写6位数字验证码');
                         hasError = true;
                      }else if(!/^\d{6}$/.test($('#input-code').val())){
                         showError($('#input-code'), '请填写6位数字验证码');
                         hasError = true;
                      }else {
                         showTrue($('#input-code'));
                         hasError = false;
                      }
                      return hasError;
                },
                "invite" : function(tag){

                     var url = '/user/CheckInvitecode';
                     var invite_code = $('#input-invite').val();
                     var ele = $('#input-invite');
                     hasError = false;

                     if (ele.length == 0) {
                        return hasError;
                     }
                    if (invite_code in invite_hash) {
                        showError(ele, '推荐人邀请码不正确');
                        return true;
                    }

                    if (typeof tag != 'undefined') {
                        return tag;
                    }

                     if(invite_code != '')
                     {

                            lock(btn_r, 'gray');
                            showError(ele, '');

                            $.ajax({
                                type: "get",
                                data: {code:invite_code},
                                dataType: "json",
                                url: url,
                                success: function(data) {
                                    if(data.errno == '0')
                                    {
                                        if (data.data.userName != '' && data.data.userName.toLowerCase() != 'null') {
                                            showTrueMsg(ele, '<span style="color:#000;">' + data.data.userName + '&nbsp;邀请您来到网信</span>');
                                        } else {
                                            showTrue(ele);
                                        }
                                        hasError = false;
                                    }
                                    else
                                    {
                                        invite_hash[invite_code] = true;
                                        hasError = true;
                                        //ele.val('');
                                        showError(ele, '推荐人邀请码不正确');
                                    }
                                    unlock(btn_r, 'gray');
                             }
                       });
                     } else {
                        showError(ele, '');
                     }
                     return hasError;
                },
                "agreement" : function(el){
                     if (!$('#agree').is(':checked')) {
                         var $td = $('#agree').closest("td");
                         $td.find(".errorDiv").html('不同意注册协议无法完成注册');
                         hasError = true;
                     } else {
                        var $td = $('#agree').closest("td");
                         $td.find(".errorDiv").html('');
                         hasError = false;
                     }
                     return hasError;
                }
            };

         var valid = function(ev) {
             var hasError = false,
             $user = $('#input-username');
             $('#register-form').find(".errorDiv").html("");
             $('#register-form').find("i").removeClass();
             for (var k in vhash) {
                if (typeof vhash[k] == 'function' && vhash[k](false) && !hasError) {
                    hasError = true;
                }
             }

                //有错误 true
             if ( hasError ) {
                 ev.preventDefault();
             }
             return hasError;

         };
         $('#register-btn').click(function(ev) {
//         $('#register-form').submit(function(ev) {
             if (valid(ev)) {
                 return false;
             }
             else
             {
                //防止重复点击
                $('#register-form').submit();
                $('.register-btn').addClass("gray").attr('disabled',true);
             }
         });

         //blur focus
         $('#register-form').on("blur", ".text", function(ev) {
            var name = this.name;
            vhash[name] && vhash[name]();
         });

         $('#register-form').on("click", "#agree", function(ev) {
            vhash["agreement"]()
            //valid(ev);
         });

         //发送短信请求ajax

         var sendNum = 1;  //判断是否第一次点击发送
         //actionSendCodeSwt(false);
         $('#action-send-code').click(function(ev) {
             var phone = $("#input-mobile").val();
             var mobileRegEx = /^0?(13[0-9]|15[0-9]|18[0-9]|14[57]|17[0-9])[0-9]{8}$/;
             var errorSpan = $(this).parent().find(".errorDiv");
             var button = $(this);
             var token_id = $("#token_id").val();
             var token = $("#token").val();
             var captcha = $("#input-captcha").val();
             var btGray = function(){
                    button.addClass("gray");
                    button.val("正在获取中...");
                    button.attr('disabled', 'disabled');
                    //updateTimeLabel(180);
             };
             errorSpan.html('');

             if (phone == '') {
                 errorSpan.html('手机号不能为空');
                 return false;
             } else if (!mobileRegEx.test(phone)) {
                 errorSpan.html('手机号格式不正确');
                 return false;
             }
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
             var sendMsg = function(url,isrsms){
                   sendNum = 2;
                   lock(btn_r, 'gray');
                   $.ajax({
                       type: "post",
                       data: {
                          type : '1',
                          isrsms : isrsms,
                          t : new Date().getTime(),
                          mobile : phone,
                          token : token,
                          token_id : token_id,
                          captcha : captcha
                       },
                       url: url,
                       //async: false,
                       dataType: "json",
                       success: function(data) {
                           unlock(btn_r, 'gray');
                           if (data.code == 1) {
                               updateTimeLabel(60, 'action-send-code');
                               return;
                           } else {
                               //$.showErr(data.message, function() {}, "提示");
                               errorSpan.html(data.message);
                           }
                           button.val('重新发送').removeAttr('disabled').removeClass("gray");
                       }
                   });
             };
             //updateTimeLabel(180, 'action-send-code');
             if(sendNum == 1){
                   sendMsg(button.data("url"),0);
             }else{
                   $("#input-mobile").parent().find(".errorDiv").text('*如未收到验证码，我们将以18401558140~18401558149号段再次发送');
                   sendMsg(button.data("url2"),1);
             }

         });
     })
 })(jQuery);
 //用于未来扩展的提示正确错误的JS
$.showErr = function(str,func,title)
{
    $.weeboxs.open(str, {boxid:'fanwe_error_box',contentType:'text',showButton:true, showCancel:false, showOk:true,title:title,width:250,type:'wee',onclose:func});
};
