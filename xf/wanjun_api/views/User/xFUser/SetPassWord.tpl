<!DOCTYPE html>
<html>
<head>
    <title>设置交易密码</title>
    <script type="text/javascript" src="/js/jquery-2.2.4.min.js"></script>
</head>
<body>
    <div style="display: none;" id="password_div">
        <input type="hidden" name="token" value="<{$_GET['token']}>" id="token">
        交易密码：<br><input type="password" name="password_a" id="password_a"><br>
        重复输入交易密码：<br><input type="password" name="password_b" id="password_b"><br><br>
        <button type="button" onclick="do_submit()">提交</button>
    </div>
    <script type="text/javascript">
        window.onload = function()
        {
            var token = $("#token").val();
            if (token == '') {
                alert('页面地址错误');
            } else {
                $.ajax({
                    url:'/user/XFUser/SetPassWordPage',
                    type:'post',
                    dataType:'json',
                    data:{token:token},
                    success:function(res) {
                        if (res['code'] === 0) {
                            $("#password_div").prop('style','display:inline;');
                        } else {
                            $("#password_div").prop('style','display:none;');
                            alert(res['info']);
                        }
                    }
                });
            }
        };

        function do_submit()
        {
            var token      = $("#token").val();
            var password_a = $("#password_a").val();
            var password_b = $("#password_b").val();
            var pattern    = /^[0-9]{6}$/;
            if (token == '') {
                alert('页面地址错误');
            } else if (password_a == '' || !pattern.test(password_a)) {
                alert('请正确输入交易密码');
            } else if (password_b == '' || !pattern.test(password_b)) {
                alert('请正确重复输入交易密码');
            } else if (password_a != password_b) {
                alert('两次输入的密码不一致');
            } else {
                $.ajax({
                    url:'/user/XFUser/SetPassWord',
                    type:'post',
                    dataType:'json',
                    data:{token:token,new_password:password_a},
                    success:function(res) {
                        if (res['code'] === 0) {
                            alert(res['info']);
                            location.href = res['data']['url'];
                        } else {
                            alert(res['info']);
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>