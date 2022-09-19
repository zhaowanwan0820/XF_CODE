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

    var investmentID,addParams,discount_goodprice,discountListNum,discountListTotalPage=0;
    var moneyValidate = false,investChooseValidate = false;
    var isChoose = $("input[name='isChoose']").val();
    var val_discount_id = $(".val_discount_id").html();
    var code,couponIsFixed ;
    function cancleDefault(evt) {
      if(!evt._isScroller) {
        evt.preventDefault();
      }
    }
    // 更新按钮状态和链接
    function updateState() {

        investmentID = $(".investmentID").html();
        var int_merry = $(".ui_input .btn_key").html() * 1;
        var discountbidAmount = $(".val_discount_bidAmount").html() * 1;
        var deal_min = Math.max($('.val_mini').html().replace(/\,|元/g, '') * 1, discountbidAmount);
        code = $('.val_code').html();
        couponIsFixed = $('.is_fixed').html();
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

        addParams = "&money=" + int_merry + "&code=" + code + "&couponIsFixed=" + couponIsFixed;
        addParams = addParams + "&discount_id=" + $(".val_discount_id").html() + "&discount_group_id=" + $(".val_discount_group_id").html()
            + "&discount_type=" + $(".val_discount_type").html()
            + "&discount_sign=" + $(".val_discount_sign").html()
            + "&discount_bidAmount=" + $(".val_discount_bidAmount").html()
            + "&discount_goodprice=" + $(".val_discount_goodprice").html();

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
            //投资按钮
            $(".sub_btn").removeClass("sub_gay").addClass("sub_red");
            if(val_discount_id || int_merry > remainNum || (val_discount_id && int_merry <= remainNum)){
                if(int_merry > remainNum){
                    investChooseValidate = false;
                }
                if(window["_needForceAssess_"] == 0 || window["_is_check_risk_"] == 0){//0代表不需要强制测评或者不需要校验个人评级
                    $(".sub_btn").attr("href", "invest://api?type=invest&id=" + investmentID + addParams);
                }
            }else{
                $(".sub_btn").attr("href", 'javascript:void(0);');
            }
        } else {
            //投资按钮
            $(".sub_btn").removeClass("sub_red").addClass("sub_gay").attr("href", 'javascript:void(0);');
        }
    };
    // 判断选择加息券，调接口计算实时收益
    function getPrice(int_merry,val_discount_id){
        $.ajax({
            type: "post",
            dataType: "json",
            async: false,
            url: "/discount/AjaxExpectedEarningInfo?token=" + $('.token').html() + '&id=' + $('.investmentID').html() + '&money=' + int_merry + "&discount_id=" + val_discount_id,
            success: function(json){
                if(!!json.data){
                    $(".coupon_detail .con").html(json.data.discountDetail);
                    $(".val_discount_goodprice").html(json.data.discountGoodPrice);
                    discount_goodprice = $(".val_discount_goodprice").html();
                    updateState();
                }
            }
        });
    }
    var computeIncome = function() {
        discountListTotalPage = 0;
        var int_merry = $(".ui_input .btn_key").html();
        getPrice(int_merry,val_discount_id);
        //投资券选择优化     start
        //投资券列表弹框 展示
        var _level = Math.max($('.val_mini').html().replace(/\,|元/g, '') * 1, $('.val_bidAmount').html() * 1);
        int_merry = parseFloat(int_merry);
        if((int_merry != 0 && int_merry >= _level && int_merry <= remainNum) || int_merry > remainNum ){
            if(!val_discount_id){
                investChooseValidate = true;
                $(".load_box").empty();
                $(".tab_con").scrollTop(0);
                loadmoreC = new WXP2P.UI.P2PLoadMore($(".load_box")[0], $('.tb0-more')[0], '/discount/AjaxConfirmPickList?token='+usertoken+'&deal_id='+ discount_id +'&site_id='+siteId+'&money='+int_merry, 1, 'post', 10);
                loadmoreC.createItem = function(item){
                    var href = 'invest://api?type=invest&id='+ discount_id+'&money='+int_merry +'&code='+code + "&couponIsFixed=" + couponIsFixed;
                    href += '&discount_id=' + item.id + '&discount_group_id=' + item.discountGroupId + '&discount_sign=' + item.sign
                        + '&discount_bidAmount=' + item.bidAmount + '&discount_type=' + item.type;

                    var icon_select = "";
                    var dl = document.createElement("div");
                    var html = "";
                    html +='<div class="con">';
                    html += '<a class="j-selectA" data-id="'+ item.id +'" href="javascript:;" data-href="'+ href + '" data-profit="'+ item.goodsPrice +'" data-goodstype="'+ item.goodsType + '" data-type="' + item.type + '">';
                    html += '<dl>';
                    if(item.recommend ==1){//不等于一的时候表示可赠送
                        if(item.type == 1){
                            html += '<div class="icon_kzs_blue"></div>';
                        }else if(item.type == 2){
                            html += '<div class="icon_kzs_yellow"></div>';
                        }
                    }
                    if (discount_id == item.id) {
                        icon_select = " icon_select" ;
                    }
                    html += '<div class="j-icon-select'+ icon_select +'"></div>';
                    html += '<dt>';
                    if(item.type == 1){
                        html += '<h2><span class="f28">'+ item.goodsPrice+'</span>元</h2>返现券';
                    }else if(item.type == 2){
                        html += '<h2>+<span class="f28">'+ item.goodsPrice+'</span>%</h2>加息券';
                    }
                    html +='</dt>';
                    html +='<dd>';
                    html +='<p>'+item.name+'</p>';
                    if (item.type == 1) {
                        html += '<p class="color_blue">';
                    } else if (item.type == 2) {
                        html += '<p class="color_yellow">';
                    }

                    if(item.bidDayLimit != "" && item.bidDayLimit > 0) {
                        html += '金额满'+item.bidAmount+'元，期限满'+item.bidDayLimit+'天可用';
                    }else{
                        html += '金额满'+item.bidAmount+'元可用';
                    }
                    html +='</p><p>'+WXP2P.UTIL.dataFormat(item.useStartTime,"", 1)+'至'+WXP2P.UTIL.dataFormat(item.useEndTime,"", 1)+'有效</p>';
                    html +='</dd>';
                    html +='</dl>';
                    html +='</a>';
                    dl.innerHTML = html;
                    if(item.type == 1){
                        dl.className="card";
                    }else if(item.type == 2){
                        dl.className="card rate_increases";
                    }else{
                        dl.className="card";
                    }
                    return dl;
                };
                loadmoreC.preProcessData = function(ajaxData) {
                    discountListNum = ajaxData['data']['list'].length;
                    discountListTotalPage = ajaxData['data']['totalPage'];

                    // if(pThis.page == 1){
                    //     pThis.container.innerHTML="";
                    // }
                    if(!discountListNum){
                        // setTimeout(function(){
                        //     $(".sub_btn").removeClass("sub_gay").addClass("sub_red");
                        // })
                        if(window["_needForceAssess_"] == 0 || window["_is_check_risk_"] == 0){//0代表不需要强制测评或者不需要校验个人评级
                            $(".sub_btn").attr("href", "invest://api?type=invest&id=" + investmentID + addParams);
                        }
                    }
                    var listItems = ajaxData['data'] ? ajaxData['data']['list'] : [];
                    return {"data": listItems, "errno": ajaxData['errno'], "error": ajaxData["error"]}
                };
                loadmoreC.processData = function(ajaxData) {
                    var pThis = this;
                    ajaxData = this.preProcessData(ajaxData);
                    if (!ajaxData.data) {
                        //NOTE: 添加处理错误
                        return;
                    }
                    pThis.page++;
                    var listDataItem = ajaxData.data;
                    if (listDataItem.length > 0) {
                        for(var index = 0; index < listDataItem.length; index++) {
                            pThis.container.appendChild(pThis.createItem(listDataItem[index]));
                        }
                    }
                    if (this.page > discountListTotalPage) {
                        pThis.loadmorepanel.innerHTML = "没有更多了";
                    }else{
                        pThis.loadmorepanel.innerHTML = '<a href="javascript:void(0);">点击加载更多</a>';
                        $(pThis.loadmorepanel).find("a").unbind('click').bind("click", function(){
                          pThis.loadNextPage();
                        });
                    }
                };
                loadmoreC.loadNextPage();
                var tabHash = {
                    'tab0': loadmoreC
                };
                var overscroll = function(el) {
                  el.addEventListener('touchstart', function() {
                    var top = el.scrollTop
                      , totalScroll = el.scrollHeight
                      , currentScroll = top + el.offsetHeight
                    if(top === 0) {
                      el.scrollTop = 1
                    } else if(currentScroll === totalScroll) {
                      el.scrollTop = top - 1
                    }
                  })
                  el.addEventListener('touchmove', function(evt) {
                    if(el.offsetHeight < el.scrollHeight){
                        evt._isScroller = true;
                    }
                    document.body.addEventListener('touchmove', cancleDefault);
                  });
                }
                overscroll(document.querySelector('.tab_con'));
                var investBg = document.querySelector('.investBg');
                var investChoose = document.querySelector('.investChoose');
                var investList  = document.querySelector('.investList');
                investBg.addEventListener('touchmove', cancleDefault);
                investChoose.addEventListener('touchmove', cancleDefault);
                investList.addEventListener('touchmove', cancleDefault);
            }

        }
    };
    $(".tab_con").on("tap" , ".j-selectA" , function(){
        var int_merry = $(".ui_input .btn_key").html();
        var $t = $(this),
        href = $t.data("href"),
        val_discount_id = $t.data("id");
        $(".j-icon-select").removeClass('icon_select');
        $t.find(".j-icon-select").addClass('icon_select');
        getPrice(int_merry,val_discount_id);
        href = href +"&discount_goodprice="+discount_goodprice+"&fromOptimize=1";
        $(".chooseYes").addClass("chooseConfirm").attr("href", href);
    });
    // 初始化键盘
    var vir_input = new virtualKey($(".ui_input"), {
        placeholder: Math.max($('.val_mini').html().replace(/\,|元/g, '') * 1, $('.val_bidAmount').html() * 1) + '起投',
        delayHiden: function() {
            computeIncome();
            updateState();
            document.body.removeEventListener('touchmove', cancleDefault);
        },
        focusFn: function() {
            discountListTotalPage = 0;
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
    function start(){
      //全投
        var wait = 2;
        $("#quantou_all").bind("click", function() {
            time();
            var yuer = $(".ketou_money").html().trim();
            var dealLeft = $(".deal_money").html().trim();
            $(".ui_input .btn_key").html(Math.min(dealLeft, yuer));

            $(".inp_text").addClass("disnone");
            computeIncome();
            updateState();
        });

        function time() {
            if (wait == 0) {
                $("#quantou_all")[0].removeAttribute("disabled");
                wait = 2;
            } else {
                $("#quantou_all")[0].setAttribute("disabled", true);
                wait--;
                setTimeout(function() {
                    time()
                }, 1000)
            }
        }

    }
    start();


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
    }else{
        $(".JS-couponnum_label").html("已选择");
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
        val_discount_id='';
        deal_min = $('.val_mini').html().replace(/\,|元/g, '') * 1;
        computeIncome();
        updateState();
        getDiscountNum();
    });
    $("#closeInvest,.investBg").bind('click', function(event) {
        investChooseValidate = true;
        $(".investBg").addClass('disnone');
        $(".investChoose").addClass('disnone');
        $(".investList").removeClass('show');


        $('.JS-selected_discount').remove();
        $('.val_discount_id').html('');
        $('.val_discount_group_id').html('');
        $('.val_discount_type').html('');
        $('.val_discount_sign').html('');
        $('.val_discount_bidAmount').html('');
        $(".val_discount_goodprice").html('');
        val_discount_id='';
        deal_min = $('.val_mini').html().replace(/\,|元/g, '') * 1;
        oldMoney = newMoney;
        computeIncome();
        updateState();
        $(".chooseYes").removeClass("chooseConfirm").attr("href", "javascript:void(0);");
        document.body.removeEventListener('touchmove', cancleDefault);
        $(".j-icon-select").removeClass('icon_select');
        $(".tab_con").scrollTop(0);


    });
    // 公益标
    $(".sub_btn").bind("click", function() {
        var $t = $(this);
        if (!moneyValidate) return true;
        if (window['_needForceAssess_']==1) { //强制风险测评弹窗
            $(".is_eval").show();
            $("#JS-is-evaluate").show();
            var l_origin = location.origin;
            var urlencode = l_origin + "/user/risk_assess?token=" + $('.token').html() + "&from_confirm=1";
            $(".eval_btn").attr('href', 'firstp2p://api?type=webview&money=&discount_id=&discount_bidAmount=&gobackrefresh=true&url=' + encodeURIComponent(urlencode));
            $(".no_eval,.eval_btn").click(function(){
                $(".is_eval").hide();
                $("#JS-is-evaluate").hide();
            });
            return false;
        } else if(window['_is_check_risk_']==1){
            var l_origin = location.origin;
            var urlencode = l_origin + "/user/risk_assess?token=" + $('.token').html() + "&from_confirm=1";
            $("#ui_conf_risk").css('display','block');
            $("#JS-confirm").attr('href', 'firstp2p://api?type=webview&money=&discount_id=&discount_bidAmount=&gobackrefresh=true&url=' + encodeURIComponent(urlencode));
            $("#JS-cancel,#JS-know,#JS-confirm").click(function(){
              $("#ui_conf_risk").hide();
            });
            return false;
        } else{
            if($('#dealType').val()==0){//判断是不是p2p
                if (!singleLimit()){
                    return false;
                }
            }
            if(window["_BIDTYPE_"] == "7") {
                $('.JS-gongyiconfirm.ui_mask').show();
                $('#JS-confirmdonate').show();
                $('#JS-confirmdonate .J_ok').attr("href", $t.attr("href"));
                $('#JS-confirmdonate .J_no').click(function() {
                    $('#JS-confirmdonate').hide();
                    $('.JS-gongyiconfirm.ui_mask').hide();
                });
                return false;
            } else if(!val_discount_id ){
                if(investChooseValidate){
                    if(discountListTotalPage>0){
                        $(".investBg").removeClass('disnone');
                        $(".investChoose").removeClass('disnone');
                        $(".investList").addClass('show');
                        $(".chooseNo").attr("href", "invest://api?type=invest&id=" + investmentID + addParams);
                    }
                }
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
                    $(this).attr('href','firstp2p://api?type=webview&money=&discount_id=&discount_bidAmount=&gobackrefresh=true&url=' + encodeURIComponent(urlencode));
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
