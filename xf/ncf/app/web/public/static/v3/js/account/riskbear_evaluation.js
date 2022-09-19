(function($) {
    $(function() {
        //风险承受能力评估
        //评估结果弹出层
        
        //配广告位的时候，选项可自动勾选
        var adv_answer = $("#adv_answer").html();
        if( adv_answer && $('#isFirstp2pHidden').val()!=1 ){
            var obj = eval('(' + adv_answer + ')');
            var li_length = $(".wq_list li").length;
            var obj_length = obj.question.length;
            if(obj_length >= li_length){
                for(var j = 0;j<li_length;j++){
                    var select = obj.question[j].answer;//第j个答案
                    $(".wq_list li").eq(j).find("div").eq(select-1).find("input").attr("checked",true);
                }
            }else{
                for(var j = 0;j<obj_length;j++){
                    var select = obj.question[j].answer;//第j个答案
                    $(".wq_list li").eq(j).find("div").eq(select-1).find("input").attr("checked",true);
                }
            }
        }
        

        $('#j_wqForm').validator({
            messages: {
                checked: "请选择{0}",
            },
            valid: function(form) {
                var $f = $(form),
                    me = this;
                me.holdSubmit();
                $.ajax({
                    type: $f.attr("method"),
                    dataType: "json",
                    data: $f.serialize(),
                    url: $f.attr("action"),
                    success: function(data) {
                        // console.log(data);
                        if (data.status == 1) {
                            var promptStr = '';
                            promptStr = '<div class="pop-tit tc"><i></i><div class="f20 mt5">评估完成，您的风险承受能力为</div></div>' +
                                '<div class="f30 mt15 tc">' + data.data.name + '</div><div class="f16 color-gray mt20 tc">时间：' + data.data.assess_date + '</div>';
                            Firstp2p.alert({
                                text: promptStr,
                                ok: function(dialog) {
                                    dialog.close();
                                    if (data.jump == '') {
                                        location.reload();
                                    } else {
                                        location.href = data.jump;
                                    }
                                },
                                width: 435,
                                showButton: true,
                                okBtnName: "我知道了",
                                title: "评估结果",
                                boxclass: "wq-popbox"
                            });
                        } else {
                            var promptStr = '';
                            promptStr = '<div class="pop-tit tc"><i class="fail"></i><div class="f20 mt5">' + data.info + '</div></div>';
                            Firstp2p.alert({
                                text: promptStr,
                                ok: function(dialog) {
                                    dialog.close();
                                    if (data.jump == '') {
                                        location.reload();
                                    } else {
                                        location.href = data.jump;
                                    }
                                },
                                width: 435,
                                showButton: true,
                                okBtnName: "我知道了",
                                title: "评估失败",
                                boxclass: "wq-popbox"
                            });

                        }
                        me.holdSubmit(false);
                    },
                    error: function(data) {
                        me.holdSubmit(false);
                    }
                });

            }
        });
    });
})(jQuery);
