;
(function($) {
    $(function() {
        $("#JS_o2o_username,#JS_o2o_tel,#JS_o2o_zipCode,#JS_o2o_address").each(function() {
            this.value = "";
        });
        var o2o_username= $("#JS_o2o_username");
        var o2o_tel= $("#JS_o2o_tel");
        var o2o_zipCode= $("#JS_o2o_zipCode");
        var o2o_address= $("#JS_o2o_address");
        var mobileRegEx = /^1[3456789]\d{9}$/;
        var usernameRegEx = /^[A-Za-z\u0391-\uFFE5]{2,40}$/;
        var zipCodeRegEx = /^\d{6}$/;
        var returnVal=false;
        function getErTip(el){
            if(!(el instanceof jQuery)){
                el=$(el);
            }
            return el.next('.error_tip');
        }
        function o2o_username_verify(){
            var inputVal=$(this).val();
            var errorTip=getErTip(this);

            if(inputVal.length==0){
                returnVal=false;
                errorTip.text('收货人姓名不能为空');
            }else if(!usernameRegEx.test(inputVal)){
                returnVal=false;
                errorTip.text('收货人姓名只能为2-40个汉字或字母');
            }else{
                returnVal=true;
            }
            return returnVal;
        }
        function o2o_tel_verify(){
            var inputVal=$(this).val();
            var errorTip=getErTip(this);
            if(inputVal.length==0){
                errorTip.text('手机号不能为空');
                returnVal=false;
                // alert("手机号不能为空");
            }else if(!mobileRegEx.test(inputVal)){
                errorTip.text('手机号格式不正确');
                returnVal=false;
            }else{
                returnVal=true;
            }
            return returnVal;
        }
        function o2o_zipCode_verify(){
            var inputVal=$(this).val();
            var errorTip=getErTip(this);
            if(inputVal.length==0){
                errorTip.text('');
            }else if(!zipCodeRegEx.test(inputVal)){
                errorTip.text('邮政编码为6位数字');
                returnVal=false;
            }else{
                returnVal=true;
            }
            return returnVal;
        }
        function o2o_address_verify(){
            var inputVal=$(this).val();
            var errorTip=getErTip(this);
            if(inputVal.length==0){
                errorTip.text('地址不能为空');
                returnVal=false;
            }else if(!(inputVal.length <= 80 && inputVal.length >= 5)){
                errorTip.text('详细地址：5-80个字符限制');
                returnVal=false;
            }else{
                returnVal=true;
            }
            return returnVal;
        }
        o2o_username.on({
            'focus':function(){
                getErTip(this).text("");
            },
            'blur':function(){
                o2o_username_verify.call(this);
            }
        });
        o2o_tel.on({
            'focus':function(){
                getErTip(this).text("");
            },
            'blur':function(){
                o2o_tel_verify.call(this);
            }
        });
        o2o_zipCode.on({
            'focus':function(){
                getErTip(this).text("");
            },
            'blur':function(){
                o2o_zipCode_verify.call(this);
            }
        });
        o2o_address.on({
            'focus':function(){
                getErTip(this).text("");
            },
            'blur':function(){
                o2o_address_verify.call(this);
            }
        });
        function valid(){
            var i = 0;
            if(!o2o_username_verify.call(o2o_username.get(0))){
                returnVal=false;

            }else{
                i++;
            }
            if(!o2o_tel_verify.call(o2o_tel.get(0))){
                returnVal=false;

            }else{
                i++;
            }
            if(!o2o_address_verify.call(o2o_address.get(0))){
                returnVal=false;

            }else {
                i++;
            }
            if(i == 3){
                returnVal=true;
            }else{
                returnVal=false;
            }
        }
        $("#goods_form").submit(function() {
            valid();
            if (!returnVal) {
                return false;
            } else {
                return true;
            }
        });
    });
})(jQuery);


;
(function($) {
    $(function() {
        $("#JS_o2o_tel").each(function() {
            this.value = "";
        });
        var o2o_tel= $("#JS_o2o_tel");
        var j_o2o_input = $(".j_o2o_input");
        var mobileRegEx = /^0?(13[0-9]|15[0-9]|18[0-9]|17[0678]|14[457])[0-9]{8}$/;
        var returnVal=false;
        function getErTip(el){
            if(!(el instanceof jQuery)){
                el=$(el);
            }
            return el.next('.error_tip');
        }
        function o2o_tel_verify(){
            var inputVal=$(this).val();
            var errorTip=getErTip(this);

            if(inputVal.length==0){
                errorTip.text('不能为空');
                errorTip.show();
                returnVal=false;
                // alert("手机号不能为空");
            }else if(!mobileRegEx.test(inputVal)){
                errorTip.text('手机号格式不正确');
                errorTip.show();
                returnVal=false;
            }else{
                returnVal=true;
            }
            return returnVal;
        }

        function o2o_valid_kong(){
            var errorTip=getErTip(this);
            if($.trim($(this).val()).length <= 0){
                errorTip.text('不能为空');
                returnVal=false;
            }
            return returnVal;
        }

        o2o_tel.on({
            'focus':function(){
                getErTip(this).text("");
            },
            'blur':function(event){

                o2o_tel_verify.call(this);
            }
        });

        j_o2o_input.on({
            'focus':function(){
                getErTip(this).text("");
            },
            'blur':function(event){

                o2o_valid_kong.call(this);
            }
        });

        function valid(){
            if(o2o_tel[0] && !o2o_tel_verify.call(o2o_tel)){
                returnVal=false;
            }
            var i = 0;
            j_o2o_input.each(function(){
                if(!!o2o_valid_kong.call($(this))){
                    i++;
                };

            });
            if(j_o2o_input.length > 0){
                if(i == j_o2o_input.length){
                    returnVal=true;
                }else{
                    returnVal=false;
                }
            }

        }


        $('#JS_o2o_tel').bind('input propertychange focus', function() {
            var telNum = $('#JS_o2o_tel').val();
            var num1 = telNum.substr(0,3),
                num2 = telNum.substr(3,4),
                num3 = telNum.substr(7,4);
            if(telNum.length>0){
                $('.tel_big_box').html(num1+" "+ num2+" "+ num3).css({
                    display: 'block'
                });
            }else{
                $('.tel_big_box').html("").css({
                    display: 'none'
                });
            }
        });
        $('#JS_o2o_tel').blur(function(event){

            $('.tel_big_box').html("").css({
                display: 'none'
            });
        });


        $("#coupon_form").bind("submit" ,function(event) {
            $(".error_tip").text("");
            valid();
            if (!returnVal) {
                return false;
            } else {
                return true;
            }
        });
    });
})(jQuery);



