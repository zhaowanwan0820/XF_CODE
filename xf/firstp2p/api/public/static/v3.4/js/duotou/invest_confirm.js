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
        var unit = $(".val_unitLoanMoney").html();
        var per = 0;
        if (_perpent != "") {
            per = parseFloat($(".perpent").html());
        }
        $(".inp_text").html(deal_min + '元起投，且为'+ unit +'的整数倍');

        // 金额判断
        if (int_merry == '') {
            $(".dit_yq").html("");
            moneyValidate = false;
        } else if (/^(\d+|\d+\.|\d+\.\d{1,2})$/.test(int_merry)) {
            if (int_merry < deal_min || !(int_merry % unit === 0)) {
                $(".dit_yq").html('起投金额为' + deal_min + '元,且为'+ unit +'的整数倍').addClass("ai_color");
                moneyValidate = false;
            } else {
                moneyValidate = true;
            }
        }else {
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
        //给合同和协议增加地址
        var web_url = encodeURIComponent(window.location.protocol + "//" +window.location.hostname +"/duotou/Contractpre?token=" + $('.token').html() + '&deal_id=' + $('.investmentID').html()),
        Contractpre_url = "firstp2p://api?type=webview&url="+web_url+ addParams;
        $(".Contractpre").attr('href', Contractpre_url);
        $('a.to_youhuiquanList').attr('href', 'invest://api?type=selectCoupon&deal_id=' + investmentID + addParams);
        if (moneyValidate) {
            // 收益
            if ($(".istongzhi").html() != "1") {
                var _earning = showDou((accMul(int_merry, per) / 360 / 100).toFixed(2));
                $(".dit_yq").html("预期收益" + _earning + "元").removeClass("ai_color");
            } else {
                $(".dit_yq").html("");
            }

            var $j_errorMsgBtn = $(".j-errorMsgBtn");
            var errorData = $j_errorMsgBtn.data("errmsg");
            if(!!errorData){
                $(".sub_btn").css("background" ,"#cccccc").attr("href" ,"#")
            }else{
                //投资按钮
                $(".sub_btn").removeClass("sub_gay").addClass("sub_red").attr("href", "invest://api?type=invest&deal_id=" + investmentID + addParams+ "&discount_goodprice=" + discount_goodprice);
            }
        } else {
            //投资按钮
            $(".sub_btn").removeClass("sub_red").addClass("sub_gay").attr("href", 'javascript:void(0);');
        }
    };

    var moneyValidate = false;

    // 初始化键盘
    var vir_input = new virtualKey($(".ui_input"), {
        placeholder: Math.max($('.val_mini').html().replace(/\,|元/g, '') * 1, $('.val_bidAmount').html() * 1) + '起投',
        delayHiden: function() {
            // computeIncome();
            updateState();
        },
        focusFn: function() {
            updateState();
            //$(".sub_btn").removeClass("sub_red").addClass("sub_gay").attr("href", 'javascript:void(0);');
        },
        changeFn:function(){
            var _ele = $(".ui_input .btn_key");
            var _val = _ele.text(); 
            // var min = $('.val_mini').html();
            P2PWAP.throttle(500 ,updateState)();
        }
    });
    // 初始化金额

    function getUrl(){
        var aQuery = window.location.href.split("?");  //取得Get参数
        var aGET = new Array();
        if(aQuery.length > 1)
        {
            var aBuf = aQuery[1].split("&");
            for(var i=0, iLoop = aBuf.length; i<iLoop; i++)
            {
                var aTmp = aBuf[i].split("=");  //分离key与Value
                aGET[aTmp[0]] = aTmp[1];
            }
        }
        return parseInt(aGET["money"]);
    }
    var val_money = $(".val_money").html() || getUrl();

    if (val_money > 0 ) {
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
        updateState();
    });

    function getDiscountNum() {
        $.ajax({
            type: "post",
            dataType: "json",
            url: "/discount/AjaxAvaliableCount?token=" + $('.token').html() + '&deal_id=' + $('.investmentID').html(),
            success: function(json){
                $(".JS-couponnum_label").show();
                $(".JS_coupon_num").text(json.data);
                if(json.data > 0){
                    $(".JS-couponnum_label , .JS_coupon_num").css({
                        color : "#ee4634"
                    });
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

    //绑定taps提示
    $(document).on("tap" , function(e){
        var pop_bottom = $(".pop_bottom");
        var $e = $(e.target);
        if($e.attr("id") == 'payment' || $e.hasClass('sxf')){
            if($e.data("lock")  != '1'){
                pop_bottom.removeClass('dis_none');
                $e.data("lock" , 1)
            }else{
                pop_bottom.addClass('dis_none');
                $e.data("lock" , 0)
            }
        }else if($e.hasClass('pop_bottom') || $e.hasClass('triangle')){
            pop_bottom.removeClass('dis_none');
            $e.data("lock" , 1);
        }else{
            pop_bottom.addClass('dis_none');
            $e.data("lock" , 0)
        }  
    });
});
