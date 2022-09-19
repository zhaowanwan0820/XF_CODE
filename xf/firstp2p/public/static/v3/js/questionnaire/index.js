$(function(){
    var clickNum = 0;
    var c = $("#code").val();
    var token = $("#token").val();
    var tokenId = $("#tokenId").val();
    var tokenCSRF = $("#tokenCSRF").val();

    //记录首次答题时间
    function start(){
        $.post("/questionnaire/start", {
            c: c,
            token: token,
            tokenId: tokenId,
            tokenCSRF: tokenCSRF
        }, function (result) {
            $(".failTip").hide();
            tokenId = result.data.tokenId;
            tokenCSRF = result.data.tokenCSRF;
            if (result.code != 0) {
                 $("#ajaxFail").text(result.msg).show();
            }
        }, 'json');
    }


    // 点击选项
    $(".questionLi").each(function(){
        $(this).click(function(e){
            var $this=$(e.target);
            $this = $this.hasClass("questionLi") ? $this : $this.parents(".questionLi");
            clickNum++;
            if (clickNum == 1) { //记录首次答题时间
                start();
            }
            var type = $this.parents(".item").attr("data-type");
            if (type == 0) { //单选题
                $this.siblings().removeClass("active");
            }
            $this.toggleClass("active");
            if ($this.attr("isShowInput")){
                $this.parent("ul").find(".writeArea").toggleClass("show").val('');
            }else{
                $this.parent("ul").find(".writeArea").removeClass("show").val('');
            }
        })
    })

    // 字数统计
    $(".inputArea").keyup(function(){
        var length=$(this).val().length;
        if(length>0){
            clickNum++;
            if (clickNum == 1) { //记录首次答题时间
                start();
            }
        }
        $(this).parents(".inputBox").find(".leftNum").text(length >= 300 ? length : length);
        var restNum=300-length;
        $(this).parents(".inputBox").find(".rightNum").text(restNum < 0 ? 0 : restNum);
    })

    // 点击提交按钮
    $(".submitBtn").click(function () {
        var flag=true;
        var answers={};
        var type=0;
        $(".failTip").hide();
        $(".questionBox .item").each(function(){
            type = $(this).attr("data-type");
            var isRequire = $(this).attr("isRequire");
            if (isRequire){
                if (type == 2) { //问答题
                    if ($(this).find(".inputArea").val().trim().length == 0) {
                        flag = false;
                    }
                } else { //选择题
                    if ($(this).find(".questionLi.active").length == 0) {
                        flag = false;
                    }
                }
            }
        })
        if(flag){//问题已答完
            $(".questionBox .item").each(function () {
                var answerArr=[];
                var key = $(this).attr("data-id");
                type = $(this).attr("data-type");
                if (type==2){//问答题
                    var val = $(this).find(".inputArea").val();
                    answers[key] = val;
                }else{//选择题
                    var others = {};
                    $(this).find(".questionLi.active").each(function(){
                        answerArr.push($(this).attr("data-id"));
                        if ($(this).attr("isShowInput")) { //其他
                            var otherId = $(this).attr("data-id");
                            var otnerVal = $(this).parent("ul").find(".writeArea").val();
                            others[otherId] = otnerVal;
                        }
                    })
                    if (answerArr.length>0){
                        answers[key] = {
                            ids: answerArr,
                            others: others
                        }
                    }else{
                        answers[key] = '';
                    }
                }
            })
            $.post("/questionnaire/answer", { c: c, token: token, tokenId: tokenId, tokenCSRF:tokenCSRF,answer: answers}, function (result) {
                $(".failTip").hide();
                tokenId = result.data.tokenId;
                tokenCSRF = result.data.tokenCSRF;
                if (result.code==0){
                    if (result.data.prizeType==0){//无奖品
                        $(".mask").show();
                        $(".finishBox").show();
                    }else{
                        $(".mask").show();
                        $(".bonusBox").show();
                        result.data.info.img?$(".bonusBox .img").attr("src", result.data.info.img):'';
                         if (result.data.prizeType == 1) { //投资券
                             $(".bonusBox .bonusTip").html('<span class="bonusCon">' + result.data.info.desc + '</span>优惠券');
                         } else { //礼券
                             $(".bonusBox .bonusTip").html('<span class="bonusCon">' + result.data.info.desc + '</span>');
                         }
                    }
                }else{
                    $("#ajaxFail").text(result.msg).show();
                }
            }, 'json');
        }else{
             $(".failTip").hide();
            $("#emptyTip").show();
        }
    })

    //弹窗点击关闭
    $(".closeBtn").click(function () {
        location.href = '/';
        $(".mask").hide();
        $(this).parents(".itemBox").hide();
    })
})
