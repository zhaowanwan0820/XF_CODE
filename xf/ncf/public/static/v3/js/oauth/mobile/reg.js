;
(function($) {
    $(function() {
        // banner判断
        if($('.reg-banner img').length == 0){
          $('.reg-banner').hide();  
        } 

        // 错误提示
        $.each(['username', 'captcha', 'mobile', 'password', 'code'], function(k, v) {
            var $node = $('.no-' + v);
            if ($node.find('.msg').html() != '') {
                $node.addClass('msg-show');
            }
        });

        var validator = new FormValidator('register_form', [{
            name: 'username',
            rules: 'required|username'
        }, {
            name: 'captcha',
            rules: 'required'
        }, {
            name: 'mobile',
            rules: 'required|phone'
        }, {
            name: 'password',
            rules: 'required|password'
        }, {
            name: 'code',
            rules: 'required|code'
        }], function(errors, evt) {
        	var el = evt.target;
        	var name = el.getAttribute("name");
        	if(typeof validatorFn[name] == "function"){
        		return validatorFn[name](el);
        	}
        });

        var validatorFn = {
        	"username": function(el) {
        		var token_id = $("#token_id").val();
            	var token = $("#token").val();
        		var val = $(el).val();
        		var numRegEx = /^[_\d]+$/;
                if(val.length == 0){
                    showError('username', '不能为空');
                }else if(val.length < 4){
        			showError('username', '用户名至少需要 4 个字符');
        		}else if(val.length > 16){
        			showError('username', '用户名最多 16 个字符');
        		}else if(!WXLC.ValidateConf.userName[3].test(val)){
        			showError('username', '用户名请输入4-16位字母、数字、下划线、横线，首位只能为字母');
        		}else if(numRegEx.test(val)){
        			showError('username', '不能只有数字或下划线');
        		}else{
        			lock();
	                $.ajax({
	                    type: "get",
	                    data: {
	                        username: val
	                    },
	                    dataType: "json",
	                    url: './userExist',
	                    success: function(data) {
	                        unlock();
	                        if (data.code != '0') {
	                            showError('username', data.msg);
	                        }
	                    },
	                    error: function() {
	                        unlock();
	                        showError('username', '系统发生错误');
	                    }
	                })
        		}
        	}
        }

        // 手机验证码
        $('#action-send-code').click(function() {
            lock();
            var $self = $(this);
            var token_id = $("#token_id").val();
            var token = $("#token").val();
            var phone = $("#mobile").val();
            var captcha = $("#captcha").val();
            if (WXLC.ValidateConf.mobile[3].test(phone)) {
                $('#action-send-code').val('发送中...').addClass('ui-btn-subing').attr('disabled', 'disabled');
                $.ajax({
                    type: 'POST',
                    url: './MCode',
                    data: {
                        type: '1',
                        isrsms: '0',
                        t: new Date().getTime(),
                        mobile: phone,
                        token: token,
                        token_id: token_id,
                        captcha: captcha
                    },
                    dataType: 'json',
                    success: function(data) {
                        unlock();
                        if (data.code == 1) {
                            updateTimeLabel(60);
                        } else {
                            $self.val('重新发送').removeClass('ui-btn-subing').removeAttr('disabled');
                            if(data.code == -10 || data.code == -9){
                            	hideError('mobile');
                            	showError('captcha', data.message);	
                            }else{
                            	showError('mobile', data.message);	
                            }
                        }
                    },
                    error: function() {
                        alert('发送失败，请重试!');
                        $self.val('获取验证码').removeClass('ui-btn-subing').removeAttr('disabled');
                    }
                });
            } else {
                unlock();
                showError('mobile', '手机号码不正确');
            }
            // 倒计时
            function updateTimeLabel(duration) {
                var timeRemained = duration;
                var timer = setInterval(function() {
                    $self.val(timeRemained + '秒重送');
                    timeRemained -= 1;
                    if (timeRemained == -1) {
                        clearInterval(timer);
                        $self.val('重新发送').removeClass('ui-btn-subing').removeAttr('disabled');
                    }
                }, 1000);
            }
        });
        // 图形状验证码
        $('#captcha').blur(function() {
            var token_id = $("#token_id").val();
            var token = $("#token").val();
            var val = $.trim($("#captcha").val());
            if (val != '') {
                lock();
                $.ajax({
                    type: "get",
                    data: {
                        captcha: val
                    },
                    dataType: "json",
                    url: './captchaCheck',
                    success: function(data) {
                        unlock();
                        if (data.code != '0') {
                            showError('captcha', data.msg);
                        }
                    },
                    error: function() {
                        unlock();
                        showError('captcha', '图形状验证码不正确');
                    }
                })
            }
        });
        // 邀请码
        $('#invite').blur(function() {
            var token_id = $("#token_id").val();
            var token = $("#token").val();
            var code = $.trim($("#invite").val());
            if (code != '') {
                lock();
                $.ajax({
                    type: "get",
                    data: {
                        code: code
                    },
                    dataType: "json",
                    url: './CheckInvitecode',
                    success: function(data) {
                        unlock();
                        if (data.errno == '0') {
                            if (data.data.userName != '' && data.data.userName.toLowerCase() != 'null') {
                                showError('invite', data.data.userName + '&nbsp;邀请您来到网信');
                            } else {
                                showTrue(ele);
                            }
                        } else {
                            showError('invite', '推荐人邀请码不正确');
                        }
                    },
                    error: function() {
                        unlock();
                        showError('invite', '推荐人邀请码不正确');
                    }
                })
            }
        });

        // 提交锁
        function lock() {
            $('#register-btn').addClass('ui-btn-subing').attr('disabled', 'disabled');
        }

        // 提交解锁
        function unlock() {
            $('#register-btn').removeClass('ui-btn-subing').removeAttr('disabled', 'disabled');
        }

        // 显示错误
        function showError(cls, msg) {
            var $ele = $('.no-' + cls),hei;
            $ele.removeClass('msg-hide').find('span.msg').html(msg);
            hei = $ele.find('span.msg').height();
            $ele.height(hei).addClass('msg-show');
        }

        // 隐藏错误
        function hideError(cls) {
            $('.no-' + cls).removeClass('msg-show').addClass('msg-hide');
        }

        // 显示密码
        var pwdFlag = false;
        $('#pwd_show_btn').click(function(event) {
            if(!pwdFlag){
                $('#password').attr('type', 'text');
                pwdFlag = true;
            }else{
                $('#password').attr('type', 'password');
                pwdFlag = false
            }
        });

        // 图形验证码
        $('.dl_yanzhengma img').attr('src', '/verify.php?w=40&h=20&rb=0&rand=' + new Date().valueOf());
        $('.dl_yanzhengma img').click(function() {
            $(this).attr('src', '/verify.php?w=40&h=20&rb=0&rand=' + new Date().valueOf());
        });
    });
})(Zepto);
