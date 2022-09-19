jQuery(function(){
    //会员ID检测
    var doms = $(".check_user_id");
    $.each(doms,function(i, dom){
        $(dom).bind("blur",function(){
            var title = $(dom).parent().parent().find(".item_title").html();
            if(isNaN($(this).val())){
                alert(title+"必须为数字");
                return false;
            }
            if($(this).val()==0){
                $(dom).parent().find(".check_user_name").html('');
                return;
            }

            if($(this).val().length>0){
                $.ajax({
                    url:ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=load_user&id="+$(this).val(),
                    dataType:"json",
                    success:function(result){
                        if(result.status ==1){
                            $(dom).parent().find(".check_user_name").html(result.user.user_name)
                        }else{
                            $(dom).parent().find(".check_user_name").html('');
                            alert(title+"不存在");
                        }
                    }
                });
            }
        });
        $(dom).blur();
    });


});