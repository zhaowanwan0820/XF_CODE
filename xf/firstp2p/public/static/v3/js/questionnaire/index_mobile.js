$(function(){
    var clickNum=0;
    var c = $("#code").val();
    var token = $("#token").val();
    var tokenId = $("#tokenId").val();
    var tokenCSRF = $("#tokenCSRF").val();
    $(".p_start").show();

    //toast提示
    function showToast(tip) {
        var toastTip = $('#site_toastTip');
        if (toastTip.size() == 0) {
            toastTip = $('<div class="site_toastTip" id="site_toastTip"><div class="textTip"></div></div>').appendTo(document.body);
        }
        var textTip = toastTip.find('.textTip');
        textTip.text(tip);
        toastTip.show();
        setTimeout(function () {
            toastTip.hide();
        }, 2000);
    }

    //记录首次答题时间
    function start() {
        $.post("/questionnaire/start", {
            c: c,
            token: token,
            tokenId: tokenId,
            tokenCSRF: tokenCSRF
        }, function (result) {
            tokenId = result.data.tokenId;
            tokenCSRF = result.data.tokenCSRF;
            if (result.code != 0) {
                showToast(result.msg);
            }
        }, 'json');
    }

    // 点击开始答题
    $(".start").click(function(){
        $(".p_start").hide();
        $(".p_question").show();
    })

    $(".item[key=0]").addClass("active");
    checkNext();

    function checkNext(){
        var type = $(".item.active").attr("data-type");
        var isRequire = $(".item.active").attr("isRequire");
        if (isRequire){//必填
            if (type == 2) { //问答题 
                if ($(".item.active").find(".inputArea").val().trim().length > 0) { //问答题已答，可点击下一题
                    $(".next").removeClass("disabled");
                }
            } else { //选择题
                if ($(".item.active").find(".questionLi.active").length >= 1) { //选择题已答，可点击下一题
                    $(".next").removeClass("disabled");
                }
            }
        }else{
            $(".next").removeClass("disabled");
        }
    }

    // 点击下一题
    $(".next").click(function(){
        var length=$(".item").length;
        var index=$(".item.active").attr("key");
        var nextIndex = parseInt(index) + 1;
        if(!$(this).hasClass("disabled")){//可点击
            if (nextIndex <= parseInt(length) - 1) { //还有下一题
                $(".next").addClass("disabled");
                $(".item.active").removeClass("active");
                $(".item[key=" + nextIndex + "]").addClass("active");
                checkNext();
            } else { //当前一题是最后一题，再点击下一题，即为提交
                var answers = {};
                var type = 0;
                $(".questionBox .item").each(function () {
                    var answerArr = [];
                    var key = $(this).attr("data-id");
                    type = $(this).attr("data-type");
                    if (type == 2) { //问答题
                        var val = $(this).find(".inputArea").val();
                        answers[key] = val;
                    } else { //选择题
                        var others={};
                        $(this).find(".questionLi.active").each(function () {
                            answerArr.push($(this).attr("data-id"));
                            if ($(this).attr("isShowInput")) { //其他
                                var otherId = $(this).attr("data-id");
                                var otnerVal = $(this).parent("ul").find(".writeArea").val();
                                others[otherId] = otnerVal;
                            }
                        })
                        answers[key] = {
                            ids: answerArr,
                            others: others
                        }
                    }
                })
                $.post("/questionnaire/answer", {
                    c: c,
                    token: token,
                    tokenId: tokenId,
                    tokenCSRF: tokenCSRF,
                    answer: answers
                }, function (result) {
                    tokenId = result.data.tokenId;
                    tokenCSRF = result.data.tokenCSRF;
                    if (result.code == 0) {
                        $(".p_question").hide();
                        $(".p_finish").show();
                        if (result.data.prizeType == 0) { //无奖品
                            $(".finishBox").show();
                        } else {
                            $(".bonusBox").show();
                            result.data.info.img?$(".bonusBox .img").attr("src", result.data.info.img):'';
                            if (result.data.prizeType==1){//投资券
                                $(".bonusBox .bonusTip").html('<span class="bonusCon">' + result.data.info.desc + '</span>优惠券');
                            }else{//礼券
                                $(".bonusBox .bonusTip").html('<span class="bonusCon">' + result.data.info.desc + '</span>');
                            }
                        }
                    }else{
                        showToast(result.msg);
                    }
                }, 'json');
            }
        }
    })

    // 点击上一题
    $(".prev").click(function(){
        var index = $(".item.active").attr("key");
        var prevIndex = parseInt(index) - 1;
        if (prevIndex >= 0) {
            $(".item.active").removeClass("active");
            $(".item[key=" + prevIndex + "]").addClass("active");
            checkNext();
        }
    })

    // 点击每个选项
    $(".questionLi").each(function () {
        $(this).click(function (e) {
            clickNum++;
            if (clickNum==1){//记录首次答题时间
                start();
            }
            var $this = $(e.target);
            var type = $this.parents(".item").attr("data-type");
            var isrequire = $this.parents(".item").attr("isrequire");
            if (type == 0) { //单选题
                $this.siblings().removeClass("active");
            }
            $this.toggleClass("active");
            if ($this.attr("isShowInput")) {
                $this.parent("ul").find(".writeArea").toggleClass("show").val('');
            } else {
                $this.parent("ul").find(".writeArea").removeClass("show").val('');
            }
            if (isrequire){
                if ($(".item.active").find(".questionLi.active").length >= 1) { //该题已答，可点击下一题
                    $(".next").removeClass("disabled");
                } else { //该题未答，不可点击下一题
                    $(".next").addClass("disabled");
                }
            }
        })
    })

    function inputArea($this){
        var val = $this.val().trim();
        var isRequire = $this.parents(".item").attr("isRequire");
        if (isRequire){
            if (val.length > 0) {
                $(".next").removeClass("disabled");
            } else {
                $(".next").addClass("disabled");
            }
        }else{
            $(".next").removeClass("disabled");
        }
        // 字数统计
        var length = $this.val().length;
        if (length > 0) {
            clickNum++;
            if (clickNum == 1) { //记录首次答题时间
                start();
            }
        }
        $this.parents(".inputBox").find(".leftNum").text(length >= 300 ? length : length);
        var restNum = 300 - length;
        $this.parents(".inputBox").find(".rightNum").text(restNum < 0 ? 0 : restNum);
    }

    // 问答题
    $(".inputArea").on('blur keyup input',function () {
        inputArea($(this));
    })



    // 关闭按钮链接配置
    var isApp = p2pBrowser.app;
    if (isApp) { //app
        $(".closeBtn").attr("href", "firstp2p://api?type=native&name=home");
    } else { //wap站
        $(".closeBtn").attr("href", "/");
    }

    $(window).resize(function(){
        initSize();
    })

    function initSize(){
        var screenHeight = $(window).height(); // 窗口高度
        var screenWidth = $(window).width(); // 窗口宽度
        if (screenHeight - screenWidth<50){
            $(".p_question").css('height','200%');
        }else{
            $(".p_question").css('height', '100%');
        }
    }

})
