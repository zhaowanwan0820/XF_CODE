;
(function($) {
    $(function() {
    	$("#old_password,#new_password,#re_new_password").val('');
        var old_pas= $("#old_password");
        var new_pas= $("#new_password");
        var re_pas= $("#re_new_password");
        function getErTip(el){
            if(!(el instanceof jQuery)){
                el=$(el);
            }
            return el.next('.error_tip');
        }
        function old_pas_verify(){
            var inputVal=$(this).val();
            var errorTip=getErTip(this);
            var returnVal=false;
            if(inputVal.length==0){
                errorTip.text('旧密码不能为空');
            }else{
                returnVal=true;
                errorTip.text('');
            }
            return returnVal;
        }
        function new_pas_verify(){
            var pasVal=new_pas.val(),
                errorTip=getErTip(this);
                checkResult={},
                returnVal=false;
            if(pasVal==""){
                errorTip.text('密码不能为空');
            }else {
                checkResult = checkPasStrenth(pasVal);
                if (!checkResult.isValid) {
                    errorTip.text(checkResult.textTip);
                } else {
                    errorTip.text('');
                    $.ajax({
                        type: "post",
                        data: {
                            pwd: pasVal,
                            flag:1
                        },
                        async: false,
                        url: '/user/PasswordCheck',
                        dataType: "json",
                        success: function (data) {
                            if (data.errorCode == 0) {
                                errorTip.text('密码安全程度：' + data.errorMsg);
                                returnVal = true;
                            } else {
                                errorTip.text(data.errorMsg);
                            }
                        },
                        error: function (e) {

                        }
                    });
                }
            }
            return returnVal;
        }
        function re_pas_verify(){
            var inputVal=$(this).val();
            var errorTip=getErTip(this);
            var returnVal=false;
            if(inputVal.length==0){
                errorTip.text('密码不能为空');
            }else if(new_pas.val() == old_pas.val()){
                errorTip.text('新密码不能与旧密码相同');
            }else if(new_pas.val()!=inputVal){
                errorTip.text('确认密码和新密码不一致');
            }else{
                returnVal=true;
                errorTip.text('');
            }
            return returnVal;
        }
        old_pas.on({
            'blur':function(){
                old_pas_verify.call(this);
            }
        });
        new_pas.blur(function(){
            var re_pas_val=re_pas.val();
            var inputVal=$(this).val();
            var re_pas_tip=getErTip(re_pas);
            if(new_pas_verify.call(this)){
                if(re_pas_val!=""){
                    if(inputVal!=re_pas_val){
                        re_pas_tip.text('确认密码和新密码不一致');
                    }else{
                        re_pas_tip.text('');
                    }
                }
            }
        });
        re_pas.on({
            'blur':function(){
                if(!new_pas_verify()){
                    return;
                }
                re_pas_verify.call(this);
            }
        });
        function valid(){
            var returnVal=true;
            if(!old_pas_verify.call(old_pas.get(0))){
                returnVal=false;
            }
            if(!new_pas_verify.call(new_pas.get(0))){
                returnVal=false;
            }
            if(!re_pas_verify.call(re_pas.get(0))){
                returnVal=false;
            }
            return returnVal;
        }
        $("#modify").submit(function() {
            if (!valid()) {
                return false;
            } else {
                return true;
            }
        });
    });
})(jQuery);