$(function () {

    //取消授权
    $('.privileges_btn.uninstall').on('click',function () {
        //首先判断msg是不是非空
        var errorMsg=$(this).data('errorMsg').split('##');
        var confirmMsg=$(this).data('confirmMsg').split('##');
        errorMsg=function(){
            var newArr=[];
            $.each(errorMsg,function (index,value) {
                if(!/^\s*$/.test(value)){
                    newArr.push(value);
                }
            });
            return newArr;
        }();
        confirmMsg=function(){
            var newArr=[];
            $.each(confirmMsg,function (index,value) {
                if(!/^\s*$/.test(value)){
                    newArr.push(value);
                }
            });
            return newArr;
        }();
        if (errorMsg.length>0){
            //本地直接阻拦“取消授权”操作
            errorMsg=function () {
                var html="";
                for (var i=0;i<errorMsg.length;i++){
                    html+="<p>"+errorMsg[i]+"</p>";
                }
                return html;
            }();
            Firstp2p.alert({
                text: '<div class="privilege_cont">'+errorMsg+'</div>',
                ok: function(dialog) {
                    dialog.close();
                },
                width: 440,
                okBtnName: '知道了'
            });
        }else{
            var triggerAjaxDefer=$.Deferred();
            var　_this=$(this);
            triggerAjaxDefer.done(function () {
                //走ajax接口“取消授权”
                if (_this.data('ajaxLock')){
                    return;
                }
                _this.data('ajaxLock',true);
                var ajaxData={
                    accountId:_this.data('accountid'),
                    privilege:_this.data('privilege')
                }
                $.ajax({
                    url: '/account/privilegesRemove',
                    type: 'GET',
                    data: ajaxData,
                    dataType: 'json',
                    success: function(result) {
                        var tipText=result[(result.code==0)?"msg":'info'];
                        Firstp2p.alert({
                            text: tipText,
                            ok: function(dialog) {
                                dialog.close();
                                location.reload();
                            },
                            width: 440,
                            okBtnName: '确定'
                        });
                    },
                    error: function() {
                        Firstp2p.alert({
                            text: '服务器端异常，就稍后重试!',
                            ok: function(dialog) {
                                dialog.close();
                            },
                            width: 440,
                            okBtnName: '确定'
                        });
                    },
                    complete:function () {
                        _this.data('ajaxLock',false);
                    }
                });
            });
            if (confirmMsg.length>0){
                confirmMsg=function () {
                    var html="";
                    for (var i=0;i<confirmMsg.length;i++){
                        html+="<p>"+confirmMsg[i]+"</p>";
                    }
                    return html;
                }();
                Firstp2p.confirm({
                    text: '<div class="privilege_cont">'+confirmMsg+'</div>',
                    ok: function(dialog) {
                        dialog.close();
                        triggerAjaxDefer.resolve();
                    },
                    width: 440,
                    okBtnName: '确定'
                });
            }else{
                triggerAjaxDefer.resolve();
            }
        }
    });

    //取消授权按钮设置链接
    $('.privileges_btn.install').on('click',function () {
        var preStr="/payment/transit?srv=authCreate&grant_list=";
        preStr+=$(this).data('grant_list');
        window.open(preStr);
        Firstp2p.supervision.finish();
        Firstp2p.supervision.lunxun({
            sCallback : function(returnVal){
                if (returnVal.code==0){
                    clearInterval(lunxunTimer);
                    location.reload();
                }
            },
            url : "/account/privilegesCheck",
            data : {
                privilege : $(this).data('privilege')
            }
        });
    });
    
});