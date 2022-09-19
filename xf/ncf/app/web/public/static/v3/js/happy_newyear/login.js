$(function(){

    var submitBtn=$('#submitBtn');//登录按钮
    var accountInput=$('#accountInput');//账户输入框
    var pasInput=$('#pasInput');//密码输入框
    // var codeInput=$('#codeInput');//验证码输入框
    var formTip=$('#formTip');//表单提示盒子
    // var preErrorTip=$('#preErrorTip');
    // if(preErrorTip.size()!=0){
    //     showTip(preErrorTip.text());
    // }
    submitBtn.click(function(){
        // alert('00');
        var tmpReg=/^\s*$/;
        if(tmpReg.test(accountInput.val())){
            showTip('帐号不能为空');
            return;
        }
        if(tmpReg.test(pasInput.val())){
            showTip('密码不能为空');
            return;
        }
        // if(codeInput.size()!=0 && tmpReg.test(codeInput.val())){
        //     showTip('验证码不能为空');
        //     return;
        // }

        $('#loginForm').submit();
    });
    // var tipTimer=null;
    //TODO 显示表单提示函数
    function showTip(inforText){
        // clearTimeout(tipTimer);
        if(inforText.length>0){
            formTip.find('span').text(inforText);
        }else{
            formTip.find('span').text("");
        }
    }
});
