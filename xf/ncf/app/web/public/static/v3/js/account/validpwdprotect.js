(function($) {
  $(function() {    
     (function(){
    	//密保回答页
    	 $('#passForm').validator({
             isNormalSubmit: false,
              messages: {
                required: "请填写{0}",
                length: "{0}为1-20位常用字符"
             },
             fields: {
                'answer1': '答案:required;length;',
                'answer2': '答案:required;length;',
                'answer3': '答案:required;length;'
            },
            rules: {
                length: function(el) {
                    return /^[,，。：\.\-\"\“\”\(\)（）A-Za-z\u0391-\uFFE5\d\u0020]{1,20}$/.test(el.value);
                }
            },
             valid: function(form){
                 $.ajax({
                    type : "post",
                    dataType : "json",
                    data : $(form).serialize(),
                    url : $(form).attr("action"),
                    success : function(data){
                        if(data.errorCode === 0){
                            location.href = data.url;
                        }else {
                            if(!!data.errorData){
                                $.each(data.errorData , function(i , v){
                                    $(form).validator("showMsg","#" + i ,{
                                        "type" : "error",
                                        "msg"  : v
                                    });
                                });
                            }else{
                                Firstp2p.alert({
                                    text : '<div class="tc">'+  data.errorMsg +'</div>',
                                    ok : function(dialog){
                                        dialog.close();
                                    }
                                });
                            }
                        }
                    }
                });
             }
         });     
     })();
      
   

  });
})(jQuery);