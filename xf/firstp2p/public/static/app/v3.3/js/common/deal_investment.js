$(function() {
    // tofixed
    Number.prototype.toFixed = function(len) {
        if (len <= 0) {
            return parseInt(Number(this));
        }
        var tmpNum1 = Number(this) * Math.pow(10, len);
        var tmpNum2 = parseInt(tmpNum1) / Math.pow(10, len);
        if (tmpNum2.toString().indexOf('.') == '-1') {
            tmpNum2 = tmpNum2.toString() + '.';
        }
        var dotLen = tmpNum2.toString().split('.')[1].length;
        if (dotLen < len) {
            for (var i = 0; i < len - dotLen; i++) {
                tmpNum2 = tmpNum2.toString() + '0';
            }
        }
        return tmpNum2;
    };

    // 三位显示逗号
    function showDou(val) {
        var arr = val.toString().split("."),
            arrInt = arr[0].split("").reverse(),
            temp = 0,
            j = arrInt.length / 3;
        for (var i = 1; i < j; i++) {
            arrInt.splice(i * 3 + temp, 0, ",");
            temp++;
        }
        return arrInt.reverse().concat(".", arr[1]).join("");
    };

    // 小数变整乘法
    function accMul(arg1, arg2) {
        var m = 0,
            s1 = arg1.toString(),
            s2 = arg2.toString();
        try {
            m += s1.split(".")[1].length
        } catch (e) {};
        try {
            m += s2.split(".")[1].length
        } catch (e) {};
        return Number(s1.replace(".", "")) * Number(s2.replace(".", "")) / Math.pow(10, m);
    }

    // 更新按钮状态和链接
    function updateState() {
        var investmentID = $(".investmentID").html();
        var int_merry = $(".ui_input .btn_key").html() * 1;
        var discountbidAmount = $(".val_discount_bidAmount").html() * 1;
        var deal_min = Math.max($('.val_mini').html().replace(/\,|元/g, '') * 1, discountbidAmount);
        var code = $('.val_code').html();
        var couponIsFixed = $('.is_fixed').html();
        var _perpent = $(".perpent").html();
        var per = 0;
        if (_perpent != "") {
            per = parseFloat($(".perpent").html());
        }
        $(".inp_text").html(deal_min + '元起投');

        // 金额判断
        if (int_merry == '') {
            $(".dit_yq").html("");
            moneyValidate = false;
        } else if (/^(\d+|\d+\.|\d+\.\d{1,2})$/.test(int_merry)) {
            if (int_merry < deal_min) {
                $(".dit_yq").html('起投金额为' + deal_min + '元').addClass("ai_color");
                moneyValidate = false;
            } else {
                moneyValidate = true;
            }
        } else {
            $(".dit_yq").html("输入有误").addClass("ai_color");
            moneyValidate = false;
        }

        var discount_goodprice = $(".val_discount_goodprice").html();
        var addParams = "&money=" + int_merry + "&code=" + code + "&couponIsFixed=" + couponIsFixed;
        addParams = addParams + "&discount_id=" + $(".val_discount_id").html() + "&discount_group_id=" + $(".val_discount_group_id").html()
            + "&discount_type=" + $(".val_discount_type").html()
            + "&discount_sign=" + $(".val_discount_sign").html()
            + "&discount_bidAmount=" + $(".val_discount_bidAmount").html();

        $('.ditf_list a.to_coupon').attr('href', 'invest://api?type=searchCoupon&id=' + investmentID + addParams);
        $('a.to_recharge').attr('href', 'invest://api?type=recharge&id=' + investmentID + addParams);
        $('a.to_contractList').attr('href', 'invest://api?type=contractList&id=' + investmentID + addParams);
        $('a.to_youhuiquanList').attr('href', 'invest://api?type=selectCoupon&deal_id=' + investmentID + addParams + "&discount_type=0");

        if (moneyValidate) {
            // 收益
            if ($(".istongzhi").html() != "1") {
                var _earning = showDou((accMul(int_merry, per) / 100).toFixed(2));
                if(window['deal_type'] == 0){
                    $(".dit_yq").html("借款利息" + ":" + _earning + "元").removeClass("ai_color");
                }else{
                    $(".dit_yq").html("预期收益" + ":" + _earning + "元").removeClass("ai_color");
                }
            } else {
                $(".dit_yq").html("");
            }
            $(".sub_btn").removeClass("sub_gay").addClass("sub_red");
            if(window["_needForceAssess_"] == 0 || window["_is_check_risk_"] == 0){//0代表不需要强制测评或者不需要校验个人评级
                //投资按钮
                $(".sub_btn").attr("href", "invest://api?type=invest&id=" + investmentID + addParams+ "&discount_goodprice=" + discount_goodprice);
            }
        } else {
            //投资按钮
            $(".sub_btn").removeClass("sub_red").addClass("sub_gay").attr("href", 'javascript:void(0);');
        }
    };

    var moneyValidate = false;
    // 判断选择加息券，调接口计算实时收益
    var computeIncome = function() {
        var int_merry = $(".ui_input .btn_key").html();
        $.ajax({
            type: "post",
            dataType: "json",
            url: "/discount/AjaxExpectedEarningInfo?token=" + $('.token').html() + '&id=' + $('.investmentID').html() + '&money=' + int_merry + "&discount_id=" + $(".val_discount_id").html(),
            success: function(json){
                if(!!json.data){
                    $(".coupon_detail .con").html(json.data.discountDetail);
                    $(".val_discount_goodprice").html(json.data.discountGoodPrice);
                    updateState();
                }
            }
        });
    };

    // 初始化键盘
    var vir_input = new virtualKey($(".ui_input"), {
        placeholder: Math.max($('.val_mini').html().replace(/\,|元/g, '') * 1, $('.val_bidAmount').html() * 1) + '起投',
        delayHiden: function() {
            computeIncome();
            updateState();
        },
        focusFn: function() {
            $(".sub_btn").removeClass("sub_red").addClass("sub_gay").attr("href", 'javascript:void(0);');
        }
    });
    // 初始化金额
    var val_money = $(".val_money").html();
    if (val_money > 0) {
        $(" .ui_input .btn_key").html(val_money);
        $(".inp_text").addClass("disnone");
    }
    updateState();

    //全投
    $(".quantou_all").bind("tap", function() {
        var yuer = $(".ketou_money").html().trim();
        var dealLeft = $(".deal_money").html().trim();
        $(".ui_input .btn_key").html(Math.min(dealLeft, yuer));
        $(".inp_text").addClass("disnone");
        computeIncome();
        updateState();
    });

    function getDiscountNum() {
        $.ajax({
            type: "post",
            dataType: "json",
            url: "/discount/AjaxAvaliableCount?token=" + $('.token').html() + '&deal_id=' + $('.investmentID').html(),
            success: function(json){
                $(".JS-couponnum_label").html("未选择");
                //$(".JS-couponnum_label").show();
                $(".can_use").show();
                $(".JS_coupon_num").text(json.data);
                if (json.data < 1) {
                    $(".JS_coupon_num").removeClass('num_canuse');
                    $(".can_use").removeClass('color_red');
                }
                if(json.data > 0){
                    // $(".JS-couponnum_label , .JS_coupon_num").css({
                    //     color : "#ee4634"
                    // });
                    var _TOUZIQUAN_GUIDE_COOKIE_NAME_ = '_app_touziquanguide_';
                    function tryShowTouziQuanGuide() {
                        var guidecokkiestr = WXP2P.APP.getCookie(_TOUZIQUAN_GUIDE_COOKIE_NAME_);
                        var guideList = guidecokkiestr != null && guidecokkiestr != "" ? guidecokkiestr.split(",") : [];
                        for (var i = guideList.length - 1; i>= 0; i--) {
                            if (guideList[i] == window['_userid_']) return;
                        }
                        $('.JS-touziyindao').show();
                        $('.ui_mask_white').click(function() {
                            $('.JS-touziyindao').hide();
                        });
                        guideList.push(window['_userid_']);
                        WXP2P.APP.setCookie(_TOUZIQUAN_GUIDE_COOKIE_NAME_, guideList.join(","), 365);
                    }
                    tryShowTouziQuanGuide();
                }
            }
        })
    }
    if ($('.JS-selected_discount').length < 1) {
        getDiscountNum();
    }
    //删除优惠券
    $('.JS-selected_discount .JS_close').bind('click', function() {
        $('.JS-selected_discount').remove();
        $('.val_discount_id').html('');
        $('.val_discount_group_id').html('');
        $('.val_discount_type').html('');

        $('.val_discount_sign').html('');
        $('.val_discount_bidAmount').html('');
        $(".val_discount_goodprice").html('');
        computeIncome();
        updateState();
        getDiscountNum();
    });

    // 公益标
    $(".sub_btn").bind("click", function() {
        var $t = $(this);
        if (!moneyValidate) return true;
        if (window['_needForceAssess_']==1) { //强制风险测评弹窗
            $(".is_eval").show();
            $("#JS-is-evaluate").show();
            var urlencode = location.origin + "/user/risk_assess?token=" + $('.token').html() + "&from_confirm=1";
            $(".eval_btn").attr('href', 'firstp2p://api?type=webview&url=' + encodeURIComponent(urlencode));
            $(".no_eval,.eval_btn").click(function(){
                $(".is_eval").hide();
                $("#JS-is-evaluate").hide();
            });
            return false;
        } else if(window['_is_check_risk_']==1){
            var l_origin = location.origin;
            var urlencode = l_origin + "/user/risk_assess?token=" + $('.token').html() + "&from_confirm=1";
            $("#ui_conf_risk").css('display','block');
            $("#JS-confirm").attr('href', 'firstp2p://api?type=webview&url=' + encodeURIComponent(urlencode));
            $("#JS-cancel,#JS-know,#JS-confirm").click(function(){
              $("#ui_conf_risk").hide();
              //返回上一级页面firstp2p://api?type=closeall
              $("#JS_cancel_container,#JS_know_container").attr("href","firstp2p://api?type=closeall")
            });
            return false;
        }else{
            if($('#dealType').val()==0){//判断是不是p2p
                if (!singleLimit()){
                    return false;
                }
            }
            if (window["_BIDTYPE_"] == "7") {
                $('.JS-gongyiconfirm.ui_mask').show();
                $('#JS-confirmdonate').show();
                $('#JS-confirmdonate .J_ok').attr("href", $t.attr("href"));
                $('#JS-confirmdonate .J_no').click(function() {
                    $('#JS-confirmdonate').hide();
                    $('.JS-gongyiconfirm.ui_mask').hide();
                });
                return false;
            }
        }
        return true;
    });
    function switchToNum(str) {
        if (!isNaN(str)){
            str=Number(str);
        }else{
            str=0;
        }
        return str;
    }
    //单笔限额的判断的函数
    function singleLimit() {
        var returnVal=true;
        var canTest=false;//是否可以重新测试
        var bidmoney = $(".ui_input .btn_key").html();
        bidmoney=switchToNum($.trim(bidmoney));
        var dataJson=function () {
            var data={};
            var moneyVal=$('#limitMoney').val();
            var levelName=$('#levelName').val();
            var num=$('#remainingAssessNum').val();
            if (moneyVal === "" | levelName === "") {
                data=null;
            }else{
                data.limitMoney=switchToNum(moneyVal);
                data.levelName=levelName;
                if (num !== "") {
                    data.remainingAssessNum=num;
                }
            }
            return data;
        }();
        var promptStr ='';//弹层上面的html布局

        if (dataJson != null) {
            if (dataJson.limitMoney < bidmoney) {
                returnVal=false;
                dataJson.levelName=function () {
                    var str=dataJson.levelName;
                    if (str.charAt(str.length-1)=="型"){
                        str=str.slice(0,-1);
                    }
                    return str;
                }();
                promptStr='您的风险承受能力为 '+dataJson.levelName+' 型,<br/>单笔最高投资额度为 '+dataJson.limitMoney/10000+' 万元';
                if($.type(dataJson.remainingAssessNum)!='undefined'){
                    promptStr+='<br/><span class="sy_num color_gray f13">本年度剩余评估'+dataJson.remainingAssessNum+'次</span>';
                    if (dataJson.remainingAssessNum>0){
                        canTest=true;
                    }
                }else{
                    canTest=true;
                }
                $('#ui_confirm').find('.confirm_donate_text').html(promptStr);
                var btns=$('#ui_confirm .confirm_donate_but a');
                btns.unbind('click').hide();
                btns.on('click',function () {
                    $('#ui_confirm').hide();
                });
                btns.eq(1).on('click',function () {
                    var l_origin = location.origin;
                    var urlencode = l_origin + "/user/risk_assess?token=" + $('.token').html() + "&from_confirm=1";
                    $(this).attr('href','firstp2p://api?type=webview&gobackrefresh=true&url=' + encodeURIComponent(urlencode));
                });
                if(canTest){
                    btns.eq(0).add(btns.eq(1)).show();
                }else{
                    btns.eq(2).show();
                }
                $('#ui_confirm').css('display','block');
            }
        }
        return returnVal;
    }
});