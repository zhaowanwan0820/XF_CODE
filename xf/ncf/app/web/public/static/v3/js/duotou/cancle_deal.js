$(function() {
    function cancleDealDialog(cancleText,cancleTitle,loanid){
        var settings = $.extend({
                ok: $.noop,
                close: $.noop
            });
            instance = null;
            instance = $.weeboxs.open(cancleText, {
                boxclass: 'zdx_info_dialog',
                contentType: 'text',
                showButton: true,
                showOk: true,
                showCancel:true,
                okBtnName: '确定',
                cancelBtnName: '取消',
                title: cancleTitle,
                width: 440,
                type: 'wee',
                onok: function(settings) {
                    $.ajax({
                        url: '/finplan/CancelLoad',
                        type: "post",
                        data: {loan_id:loanid},
                        dataType: "json",
                        success: function(json) {
                            if (json.data.errCode == 0) {
                                settings.close(settings);
                                Firstp2p.alert({
                                    text : '<div class="tc">取消成功</div>',
                                    ok : function(dialog){
                                        location.reload();
                                    },
                                    close: function(dialog) {
                                        location.reload();
                                    },
                                });
                            } else {
                                settings.close(settings);
                                Firstp2p.alert({
                                    text : '<div class="tc">'+  json.data.errMsg +'</div>',
                                    ok : function(dialog){
                                        dialog.close();
                                    }
                                });
                            }
                        },
                        error: function(){
                            settings.close(settings);
                            Firstp2p.alert({
                                text : '<div class="tc">网络错误</div>',
                                ok : function(dialog){
                                    dialog.close();
                                }
                            });
                        }
                    });
                }
            });
    }
    $(".j_cancle").on("click",function(){
        var cancleinfo = $(this).data("cancleinfo"),
            cancle_type = cancleinfo.cancelType,
            loanid = cancleinfo.loanid,
            cancleTitle = "提示",
            cancleText = "";
        if(cancle_type == 0){
            cancleText = "<div class='zdxInfoText'>确认取消后，您该笔的待匹配本金系统今天将不会为其匹配。</div>";
        }else if(cancle_type ==2){
            cancleText = "<div class='zdxInfoText'>确认取消后，您该笔的本金将自动返回到您的账户余额中。</div>";
        }else{
            cancleTitle = "取消";
            cancleText ="<div>\
                            <div class='mt15'>您申请的取消系统将自动为您发起转让/退出</div>\
                            <div class='cancle_middle'>\
                                <div class='pb5'><span>待转让/退出本金</span><span class='fr'>"+ cancleinfo.money +"</span></div>\
                                <div class='pb5'><span>管理服务费</span><span class='fr'>"+ cancleinfo.manageFee +"</span></div>\
                                <div>待结利息将按加入资产还款日发放到您的账户中</div>\
                            </div>\
                         </div>"
        }
        cancleDealDialog(cancleText,cancleTitle,loanid);
    })
});