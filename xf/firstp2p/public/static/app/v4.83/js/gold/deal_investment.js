$(function() {
    var minLimit=$('#minLimitInput').val();//起购克重
    minLimit = minLimit.replace(/,/g,"");
    var remainNum=$('#remainNum').val();
    remainNum = remainNum.replace(/,/g,"");
    var consume_type = "";
    if($("#consume_type").length>0){
        consume_type = 0;
    }
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

    /**
     * 精确运算到分
     * @param val
     */
    function exactCompute(val) {
        val=Number(val);
        val = Math.round(val*100)/100;
        return val.toFixed(2);
    }




    // 小数变整乘法
    function accMul(arg1, arg2 ,arg3) {
        var m = 0,
            s1 = arg1.toString(),
            s2 = arg2.toString();
            s3 = arg3.toString();
        try {
            m += s1.split(".")[1].length
        } catch (e) {};
        try {
            m += s2.split(".")[1].length
        } catch (e) {};
        try {
            m += s3.split(".")[1].length
        } catch (e) {};
        return Number(s1.replace(".", "")) * Number(s2.replace(".", "")) * Number(s3.replace(".", "")) / Math.pow(10, m);
    }

    

    var addParams;
    var discount_goodprice,discountListNum,discountListTotalPage=0;
    var investParams;//投资接口scheme额外参数
    var moneyValidate = false,investChooseValidate = false;
    var isChoose = $("input[name='isChoose']").val();
    var code,couponIsFixed ;
    var val_discount_id = $(".val_discount_id").html();
    function cancleDefault(evt) {
        if(!evt._isScroller) {
            evt.preventDefault();
        }
    }

    //获取url参数
    function GetRequestnew(hrefD) {
       var url = hrefD; //获取url中"?"符后的字串
       var theRequest = new Object();
       if (url.indexOf("?") != -1) {
          var str = url.substr(1);
          strs = str.split("&");
          for(var i = 0; i < strs.length; i ++) {
             theRequest[strs[i].split("=")[0]]=(strs[i].split("=")[1]);
          }
       }
       return theRequest;
    }

    //预期收益计算
    function ExpectProfit(){
        var int_merry = $(".ui_input .btn_key").html() * 1;
        var _perpent = $("#rateInput").val();
        var goldPrice = $('#goldPrice').val()*1;;
        var per = 0;
        var days = 1;
        if($("#days").val()){
            days = $("#days").val();
        }
        if (_perpent != "") {
            per = parseFloat($("#rateInput").val());
        }
        if($(".icon_select").length>0){
            var hrefD = $(".icon_select").parent().parent(".j-selectA").attr("data-href");
            var Request = new Object();
            Request = GetRequestnew(hrefD);
            var discountbidAmount = Request["discount_bidAmount"];
        }else{
            var discountbidAmount = $(".val_discount_bidAmount").html() * 1;
        }
        var deal_min = Math.max(minLimit, discountbidAmount);
        if (moneyValidate) {
            var _earning = showDou((accMul(int_merry, per,days) / 36000).toFixed(3));
            if($("#gold_current").val() == "gold_current"){
                var _earning = showDou((accMul(int_merry, per,days)*goldPrice / 36000).toFixed(2));
            }
            if($("#gold_current").val() != "gold_current"){
                $(".dit_yq").html("预期收益"+_earning+"克");
            }else {
                $(".dit_yq").html("");
            }
        }
    }

    // 更新按钮状态和链接
    function updateState() {

        var int_merry = $(".ui_input .btn_key").html() * 1;
        if($(".icon_select").length>0){
            var hrefD = $(".icon_select").parent().parent(".j-selectA").attr("data-href");
            var Request = new Object();
            Request = GetRequestnew(hrefD);
            var discountbidAmount = Request["discount_bidAmount"];
        }else{
            var discountbidAmount = $(".val_discount_bidAmount").html() * 1;
        }
        var deal_min = Math.max(minLimit, discountbidAmount);
        deal_min = deal_min.toFixed(3);
        code = $('#valCode').val();
        couponIsFixed = $('#couponIsFixed').val();

        $(".inp_text").html(deal_min + '克起购');

        // 金额判断
        if (int_merry == '') {
            $(".dit_yq").html("");
            moneyValidate = false;
        } else if (/^(\d+|\d+\.|\d+\.\d{1,3})$/.test(int_merry)) {
            if (int_merry < deal_min) {
                $(".dit_yq").html('起购克重为' + deal_min + '克').addClass("ai_color");
                moneyValidate = false;
            } else {
                moneyValidate = true;
            }
        } else {
            $(".dit_yq").html("输入有误").addClass("ai_color");
            moneyValidate = false;
        }

        addParams = "&money=" + int_merry + "&code=" + code + "&couponIsFixed=" + couponIsFixed;
        investParams="&token="+usertoken+"&dealId="+dealId+'&buyAmount='+int_merry +'&money='+int_merry
            +'&buyPrice='+$('#goldPrice').val()+'&coupon='+$('#valCode').val()+ "&ticket=" + $('#ticket').val();

        if($(".icon_select").length>0){
            var hrefD = $(".icon_select").parent().parent(".j-selectA").attr("data-href");
            function GetRequest() {
              var url = hrefD; //获取url中"?"符后的字串
               var theRequest = new Object();
               if (url.indexOf("?") != -1) {
                  var str = url.substr(1);
                  strs = str.split("&");
                  for(var i = 0; i < strs.length; i ++) {
                     theRequest[strs[i].split("=")[0]]=(strs[i].split("=")[1]);
                  }
               }
               return theRequest;
            }
            var Request = new Object();
            Request = GetRequest();
            var discount_id = Request["discount_id"];
            var discount_group_id = Request["discount_group_id"];
            var discount_sign = Request["discount_sign"];
            var discount_bidAmount = Request["discount_bidAmount"];
            investParams += "&discount_id=" + discount_id
                        + "&discount_group_id=" + discount_group_id
                        + "&discount_sign=" + discount_sign
                        + "&discount_bidAmount=" + discount_bidAmount
                        + "&discount_goodprice=" + $(".val_discount_goodprice").html();
        }else{
            investParams += "&discount_id=" + $(".val_discount_id").html()
            + "&discount_group_id=" + $(".val_discount_group_id").html()
            + "&discount_sign=" + $(".val_discount_sign").html()
            + "&discount_bidAmount=" + $(".val_discount_bidAmount").html()
            + "&discount_goodprice=" + $(".val_discount_goodprice").html();
        }


        $('.ditf_list a.to_coupon').attr('href', 'invest://api?type=searchCoupon' + investParams);
        // $('a.to_contractList').attr('href', 'invest://api?type=contractList=' + investParams);
        if($("#gold_current").val() == "gold_current"){
            var gold_current = 1;
        }else{
            var gold_current = 0;
        }
        var urlencode = location.origin + "/gold/contractList?token=" + usertoken + "&dealId=" + $("#dealId").val() + "&buyAmount=" + int_merry+'&buyPrice='+$('#goldPrice').val() + "&type=" + gold_current;
        $('a.to_contractList').attr('href', 'firstp2p://api?type=webview&gobackrefresh=true' + investParams + '&dealId='+dealId+'&buyAmount='+int_merry+'&gobackrefresh=true&url=' + encodeURIComponent(urlencode));
        $('a.to_youhuiquanList').attr('href', 'invest://api?type=selectCoupon&deal_id=' + dealId + investParams + "&discount_type=3");
        $('.charge').attr('href','firstp2p://api?type=recharge&channel=main' + investParams);
        $(".sub_btn").removeClass("sub_golden").addClass("sub_gay").attr("disabled","disabled");
        if (moneyValidate) {
            ExpectProfit();
            //投资按钮
            // if( int_merry > remainNum ){
            //     if(int_merry > remainNum){
            //         investChooseValidate = false;
            //     }
            //     $(".sub_btn").attr("disabled" , "disabled");
            // }else{
            //     $(".sub_btn").removeClass("sub_gay").addClass("sub_golden").removeAttr("disabled");
            // }
            $(".sub_btn").removeClass("sub_gay").addClass("sub_golden").removeAttr("disabled");
        } else {
            //投资按钮
            $(".sub_btn").removeClass("sub_golden").addClass("sub_gay").attr("disabled","disabled");
        }
        //拼接开户url
        var _is_open_p2p_param = '{"srv":"register" , "return_url":"storemanager://api?type=closecgpages"}';//开户参数
        var _openp2pUlr = location.origin + "/payment/Transit?params=" + encodeURIComponent(_is_open_p2p_param);
        //开户url
        $(".JS_open_p2p_btn").attr({"href":'storemanager://api?type=webview'+ addParams +'&gobackrefresh=true&url='+encodeURIComponent(_openp2pUlr)});

        //拼接授权url
        var _is_freePayment_param = '{"return_url":"storemanager://api?type=closecgpages","srv":"freePaymentQuickBid"}'
        var _freePayment = location.origin + "/payment/Transit?params=" + encodeURIComponent(_is_freePayment_param);
        $(".JS_is_freepayment_btn").attr("href",'storemanager://api?type=webview'+ addParams +'&gobackrefresh=true&url=' + encodeURIComponent(_freePayment));
    };

    // 判断选择加息券，调接口计算实时收益
    function getPrice(int_merry,val_discount_id){
        $.ajax({
            type: "post",
            dataType: "json",
            async: false,
            url: "/discount/AjaxExpectedEarningInfo?token=" + usertoken + '&id=' + dealId + '&money=' + int_merry + "&discount_id=" + val_discount_id + '&consume_type=' + consume_type + '&appversion=' + $("#appversion").val(),
            success: function(json){
                if(!!json.data){
                    $(".can_use").hide();
                    $(".JS-couponnum_label").html("已选择");
                    $(".JS-selected_discount").show();
                    $(".coupon_detail .con").html(json.data.discountDetail+($('#appversion').val()!=460?'':'，请到新版客户端优金宝账户中查看'));
                    $(".val_discount_goodprice").html(json.data.discountGoodPrice);
                    $(".val_discount_amount").html(json.data.discountAmount);
                    discount_goodprice = $(".val_discount_goodprice").html();
                    updateState();
                }
            }
        });
    }

    function getDiscountNum() {
        $.ajax({
            type: "post",
            dataType: "json",
            url: "/discount/AjaxAvaliableCount?token=" + usertoken + '&deal_id='+ dealId  +'&discount_type=3&consume_type=' + consume_type,
            success: function(json){
                investChooseValidate = true;
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

    var computeIncome = function() {
        discountListTotalPage = 0;
        var int_merry = $(".ui_input .btn_key").html();
        if(val_discount_id){
            getPrice(int_merry,val_discount_id);
        }
        var o2oDiscountSwitch = $("#o2oDiscountSwitch").val();
        var o2oGoldDiscountSwitch = $("#o2oGoldDiscountSwitch").val();
        //投资券选择优化     start
        //投资券列表弹框 展示
        var _level = Math.max(minLimit * 1, $('.val_bidAmount').html() * 1);
        int_merry = parseFloat(int_merry);
        val_discount_id = $('.val_discount_id').html();
        if(o2oDiscountSwitch == 0 || o2oGoldDiscountSwitch == 0) return;
        if((int_merry != 0 && int_merry >= _level && int_merry <= remainNum) || int_merry > remainNum  ){
            if(!val_discount_id){
                investChooseValidate = true;
                $(".load_box").empty();
                $(".tab_con").scrollTop(0);
                loadmoreC = new WXP2P.UI.P2PLoadMore($(".load_box")[0], $('.tb0-more')[0], '/discount/AjaxConfirmPickList?token='+$("#usertoken").val()+'&deal_id='+ dealId +'&site_id='+siteId+'&money='+int_merry + '&discount_type=3&consume_type=' + consume_type, 1, 'post', 10);
                loadmoreC.createItem = function(item){
                    var href = 'invest://api?type=invest&id='+ dealId+'&money='+int_merry +'&code='+code + "&couponIsFixed=" + couponIsFixed;
                    href += '&discount_id=' + item.id + '&discount_group_id=' + item.discountGroupId + '&discount_sign=' + item.sign
                        + '&discount_bidAmount=' + item.bidAmount + '&discount_type=' + item.type;

                    var icon_select = "";
                    var unit = ""
                    var dis_type = ""
                    if(item.type == 1 || item.type == 2){
                        unit = "元";
                        dis_type = "投资"
                    }else{
                        unit = "克";
                        dis_type = "购买"
                    }
                    var dl = document.createElement("div");
                    var html = "";
                    html +='<div class="con">';
                    html += '<a class="j-selectA" data-id="'+ item.id +'" href="javascript:;" data-href="'+ href + '" data-profit="'+ item.goodsPrice +'" data-goodstype="'+ item.goodsType + '" data-type="' + item.type + '">';
                    html += '<dl>';
                    if(item.recommend ==1){//不等于一的时候表示可赠送
                        if(item.type == 1){
                            html += '    <div class="icon_kzs_blue">';
                        }else if(item.type == 2){
                            html += '    <div class="icon_kzs_yellow">';
                        }else if(item.type == 3){
                            html += '<div class="icon_kzs_gold">'
                        }
                        html += '    </div>';
                    }
                    if (dealId == item.id) {
                        icon_select = " icon_select" ;
                    }
                    html += '<div class="j-icon-select'+ icon_select +'"></div>';
                    html += '<dt>';
                    if(item.type == 1){
                        html += '        <h2><span class="f28">'+ item.goodsPrice+'</span>元</h2>返现券';
                    }else if(item.type == 2){
                        html += '        <h2>+<span class="f28">'+ item.goodsPrice+'</span>%</h2>加息券';
                    }else if(item.type == 3){
                        html += '        <h2><span class="f25">'+ item.goodsPrice+'</span>克</h2>黄金券';
                    }
                    html +='</dt>';
                    html +='<dd>';
                    html +='<p>'+item.name+'</p>';
                    if(item.type == 1){
                        html +='<p class="color_blue">';
                    }else if(item.type == 2){
                        html +='<p class="color_yellow">';
                    }else if(item.type == 3){
                        html +='<p class="color_gold">';
                    }
                    if(item.bidDayLimit != "" && item.bidDayLimit > 0) {
                        if (item.type == 3) {
                            html += '购买满'+item.bidAmount + unit +'，期限满'+item.bidDayLimit+'天可用</p>';
                        } else {
                            html += '金额满'+item.bidAmount + unit +'，期限满'+item.bidDayLimit+'天可用</p>';
                        }
                    }else{
                        html += dis_type + '满'+item.bidAmount + unit +'可用';
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
                    }else if(item.type == 3){
                        dl.className="card rate_gold";
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
                        $(".sub_btn").attr("href", "invest://api?type=invest" + investParams);
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
        href = href +"&discount_goodprice="+discount_goodprice+"&fromOptimize=1";
        // $(".chooseYes").addClass("chooseConfirm").attr("href", href);

        $(".chooseYes").addClass("chooseConfirm").unbind("click").bind("click",function(event) {
            investChooseValidate = false;
            $(".investBg").addClass('disnone');
            $(".investChoose").addClass('disnone');
            $(".investList").removeClass('show');
            $(".val_discount_id").html(val_discount_id);
            $(".val_discount_goodprice").html(discount_goodprice);
            $(".sub_btn").attr("href" , href);
            getPrice(int_merry,val_discount_id);
            showPredict();
        });

    });

    function cleardiscount(){
        $(".investBg").addClass('disnone');
        $(".investChoose").addClass('disnone');
        $(".investList").removeClass('show');
        $('.JS-selected_discount').hide();
        $('.val_discount_id').html('');
        $('.val_discount_group_id').html('');
        $('.val_discount_type').html('');
        $('.val_discount_sign').html('');
        $('.val_discount_bidAmount').html('');
        $(".val_discount_goodprice").html('');
        $('.j-icon-select').removeClass('icon_select');
        val_discount_id='';
        deal_min = $('#minLimitInput').html().replace(/\,|元/g, '') * 1;
        // oldMoney = newMoney;
        // computeIncome();
        updateState();
        $(".chooseYes").removeClass("chooseConfirm").attr("href", "javascript:void(0);");
        document.body.removeEventListener('touchmove', cancleDefault);
        $(".j-icon-select").removeClass('icon_select');
        $(".tab_con").scrollTop(0);
    }

    $("#closeInvest,.investBg").bind('click', function(event) {
        investChooseValidate = true;
        cleardiscount();
    });

    //删除优惠券
    $('.JS-selected_discount .JS_close').bind('click', function() {
        cleardiscount();
        val_discount_id='';
        deal_min = minLimit * 1;
        // computeIncome();
        updateState();
        getDiscountNum();
        showPredict();
    });

    if(val_discount_id){
        var int_merry = $(".ui_input .btn_key").html() * 1
        getPrice(int_merry,val_discount_id);
    }else{
        getDiscountNum();
    }

    // 初始化键盘
    var vir_input = new virtualKey($(".ui_input"), {
        placeholder: minLimit+'克起购',
        decimalNum:3,
        delayHiden: function() {
            updateState();
            showPredict();
            document.body.removeEventListener('touchmove', cancleDefault);
            var ipt_val = $(".ui_input .btn_key").html();
            if(ipt_val == ''){
                $('.input_deal').removeClass('borer_gold');
            }
            computeIncome();
        },
        focusFn: function() {
            $(".sub_btn").removeClass("sub_golden").addClass("sub_gay").attr("disabled","disabled");
            $('.input_deal').addClass('borer_gold');
        },
        changeFn: function() {
            iptChangeFn();
            showPredict();
            // computeIncome();
        }
    });

    function iptChangeFn() {
        var ipt_val = $(".ui_input .btn_key").html();
        $(".show_daxie").empty().append($.getformatMoney(ipt_val, "show_money_ul", "active"));
        updateState();
    }
    // 初始化金额
    var val_money = $("#initBuyAmount").val();
    if (val_money > 0) {
        $(" .ui_input .btn_key").html(val_money);
        $(".inp_text").addClass("disnone");
        iptChangeFn();
    }
    // updateState();
    //存管逻辑
    //总资产明细、双账户
    var specialMoneyNew = ($('#specialMoney').val().replace(/,/g,"")) * 1;
    var bonusMoneyNew = ($('#JS_bonus').val().replace(/,/g,"")) * 1;
    var p2pMoneyNew = ($('#p2pMoney').val().replace(/,/g,"")) * 1;
    var useable_wxMoney = specialMoneyNew + bonusMoneyNew
    var wx_ableMoney = showDou(useable_wxMoney.toFixed(2))
    $('.JS_remian_money').html(wx_ableMoney)
    $('.JS_maskBack').click(function() {
        $('.p_affirm .mask').hide()
        $(".sub_btn").removeClass("sub_gay").addClass("sub_golden").removeAttr("disabled");
    });
    function supervision(){
        $(".JS_bid_btn").remove();
        var discountAmount = $('.val_discount_amount').html();
        if (discountAmount == '') {
            discountAmount = 0;
        }
        $.ajax({
            url: '/gold/pre_bid',
            type: 'post',
            data:{
                "token":usertoken,
                "buy_amount":$(".ui_input .btn_key").html(),
                "coupon":$('#valCode').val(),
                "buy_price":$('#goldPrice').val(),
                "id":dealId,
                "discount_amount":discountAmount
            },
            dataType: 'json',
            success: function(json){
                //开户url
                if(json.data.status == 3){
                    $(".JS_is_transfer").show();
                    $(".JS_trans_money").html(json.data.data.transfer+"元");
                    $(".remain_m").html(json.data.data.remain);
                    //拼接划转url
                    var _is_transfer_param = '{"srv":"transfer" , "amount":"'+json.data.data.transfer+'","return_url":"storemanager://api?type=closecgpages"}';
                    //开户参数
                    var _en_is_transfer_param = encodeURIComponent(_is_transfer_param);
                    var _istransferUlr = location.origin + "/payment/Transit?params=" + _en_is_transfer_param;
                    $(".JS_transfer_btn").attr({"href":'storemanager://api?type=webview'+ addParams +'&gobackrefresh=true&url='+encodeURIComponent(_istransferUlr)});
                } else if(json.data.status == 7){
                    $(".JS_is_open_p2p").show();
                    updateState();
                } else if(json.data.status == -1) {
                    var pre_money = ($('.pre_money').html().replace(/,/g,"")) * 1;
                    var all_money = useable_wxMoney + p2pMoneyNew;
                    var sub_money = showDou((Math.round((pre_money - useable_wxMoney)*100)/100).toFixed(2));
                    if (pre_money <= all_money) {
                        $('.JS_dealDetail').show();
                        $('.JS_recharge').html(sub_money)
                    }else {
                        WXP2P.UI.showErrorTip(json.data.data);
                    }
                } else if(json.data.status == 2){
                    var _is_bid_param = '{"srv":"bid" , "money":"'+$(".ui_input .btn_key").html()+'" , "couponId":"'+ $('#valCode').val() +'" , "couponIsFixed":"'+ $('#couponIsFixed').val() +'" , "discountId":"'+ $(".val_discount_id").html() +'" , "discount_group_id":"'+ $(".val_discount_group_id").html() +'" ,"discountType":"'+$(".val_discount_type").html()+'" , "discountSign":"'+ $(".val_discount_sign").html() +'" , "discountGoodPrice":"'+ $(".val_discount_goodprice").html() +'","return_url":"storemanager://api?type=cginvest"}';

                    // var discount_goodprice = Request["discount_goodprice"];

                    if($(".icon_select").length>0){
                        var hrefD = $(".icon_select").parent().parent(".j-selectA").attr("data-href");
                        function GetRequest() {
                          var url = hrefD; //获取url中"?"符后的字串
                           var theRequest = new Object();
                           if (url.indexOf("?") != -1) {
                              var str = url.substr(1);
                              strs = str.split("&");
                              for(var i = 0; i < strs.length; i ++) {
                                 theRequest[strs[i].split("=")[0]]=(strs[i].split("=")[1]);
                              }
                           }
                           return theRequest;
                        }
                        var Request = new Object();
                        Request = GetRequest();
                        var discount_id = Request["discount_id"];
                        var discount_group_id = Request["discount_group_id"];
                        var discount_sign = Request["discount_sign"];
                        var discount_bidAmount = Request["discount_bidAmount"];
                        var discount_type = Request["discount_type"];
                        _is_bid_param = '{"srv":"bid" , "money":"'+$(".ui_input .btn_key").html()+'" , "dealId":"'+ $(".investmentID").html() +'" , "couponId":"'+ $('.val_code').html() +'" , "couponIsFixed":"'+ $('.is_fixed').html() +'" , "discountId":"'+ discount_id +'" , "discountGroupId":"'+ discount_group_id +'" ,"discountType":"'+ discount_type +'" , "discountSign":"'+ discount_sign +'" , "discountGoodPrice":"'+ discount_goodprice +'","return_url":"storemanager://api?type=cginvest"}';
                    }

                    var _en_bid_param = encodeURIComponent(_is_bid_param);
                    var _bidUlr = location.origin + "/payment/Transit?params=" + _en_bid_param;
                    var p2pbid_href = 'storemanager://api?type=webview&gobackrefresh=true&url=' + encodeURIComponent(_bidUlr);
                    $("body").append('<a href="'+p2pbid_href+'" class="JS_bid_btn"></a>');
                    $(".JS_bid_btn").click();
                }else if(json.data.status == 4 || json.data.status == 5){
                    $(".JS_is_transfer_tips").show();
                    $(".JS_is_transfer_tips .JS_trans_money").html(json.data.data.transfer+"元");
                    $(".JS_is_transfer_tips .remain_m").html(json.data.data.remain);
                    var transfer_type = "";
                    if(json.data.status == 4){
                        transfer_type = 1;
                    }else{
                        transfer_type = 2;
                    }
                    $(".JS_is_transfer_tips .JS_transfer_btn").unbind("click");
                    $(".JS_is_transfer_tips .JS_transfer_btn").bind("click" ,function(){
                        $.ajax({
                            url:"/payment/Transfer?money=" + json.data.data.transfer + "&type=" + transfer_type + "&dontTip=" + $(".no_tip_checkbox").val() +"&token=" + $('#usertoken').val() + "&biz=gold",
                            type: 'post',
                            dataType: 'json',
                            beforeSend:function(){
                                $(".JS_is_transfer_tips .JS_transfer_btn").attr("disabled","disabled");
                            },
                            success:function(subjosn){
                                if(subjosn.errno == 0){
                                    WXP2P.UI.showErrorTip("余额划转成功");
                                    var val_svBalance = $(".val_svBalance").html();
                                    var val_wxMoney = $(".val_wxMoney").html();
                                    val_svBalance = val_svBalance.replace(/,/g,'');
                                    val_wxMoney = val_wxMoney.replace(/,/g,'');
                                    if(json.data.status == 4){
                                        val_svBalance = (val_svBalance*1 + json.data.data.transfer*1);
                                        $(".val_svBalance").html(showDou((val_svBalance).toFixed(2)));
                                        $(".val_wxMoney").html(showDou(json.data.data.remain));
                                    }else{
                                        val_wxMoney = (val_wxMoney*1 + json.data.data.transfer*1);
                                        $(".val_wxMoney").html(showDou((val_wxMoney).toFixed(2)));
                                        $(".val_svBalance").html(showDou(json.data.data.remain));
                                    }
                                }else{
                                    WXP2P.UI.showErrorTip(subjosn.error);
                                    $(".sub_btn").removeClass("sub_gay").addClass("sub_golden").removeAttr("disabled");
                                }
                                $(".JS_is_transfer_tips").hide();
                                $(".JS_is_transfer_tips .JS_transfer_btn").removeAttr('disabled');
                                $(".sub_btn").removeClass("sub_gay").addClass("sub_golden").removeAttr("disabled");
                            },
                            error:function() {
                                WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
                                $(".JS_is_transfer_tips .JS_transfer_btn").removeAttr('disabled');
                                $(".sub_btn").removeClass("sub_gay").addClass("sub_golden").removeAttr("disabled");
                            }
                        })
                        
                    })
                }else if(json.data.status == 1){
                    var showDiscount = $(".sub_btn").attr("data-showDiscount");
                    if(!val_discount_id && investChooseValidate && discountListTotalPage>0 && showDiscount != 1 && $("#gold_current").val() != "gold_current"){
                        $(".investBg").removeClass('disnone');
                        $(".investChoose").removeClass('disnone');
                        $(".investList").addClass('show');
                        $(".chooseNo").click(function(event) {
                            investChooseValidate = false;
                            $(".sub_btn").attr("data-showDiscount","1");
                            cleardiscount();
                        });
                        return false;
                    }else{
                        var href = "invest://api?type=invest&id=" + investParams;
                        $("body").append('<a href="'+href+'" class="JS_bid_btn"></a>');
                        $(".JS_bid_btn").click();
                    }
                }else{
                    WXP2P.UI.showErrorTip(json.data.data);
                    $(".sub_btn").removeClass("sub_gay").addClass("sub_golden").removeAttr("disabled");
                }
            },
            error:function() {
                WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
                $(".sub_btn").removeClass("sub_gay").addClass("sub_golden").removeAttr("disabled");
            }
        })
    }
    //不在提示划转弹窗
    $(".JS_is_transfer_tips .tips_icon").removeClass('JS_active');
    $(".no_tip_checkbox").val(0);
    $(".JS_is_transfer_tips").on("click",".tips_icon",function(event) {
        $(".tips_icon").toggleClass('JS_active');
        if($(".no_tip_checkbox").is(':checked')){
            $(".no_tip_checkbox").val(1);
        }else{
            $(".no_tip_checkbox").val(0);
        }
    });

    //投资按钮click事件
    $(".sub_btn").bind("click", function() {
        var $t = $(this);
        $t.attr("disabled","disabled");
        if (!moneyValidate) return true;
        supervision()
        return false;
    });

    $(".point_open").click(function() {
        $(".account_money").toggle();
        $(this).toggleClass('down_img');
    });

    //关闭划转
    $(".JS_close_transfer").click(function(event) {
        $(".JS_is_transfer").hide();
        $(".sub_btn").removeAttr("disabled");
    });

    $(".JS_close_transfer_tips").click(function(){
        $(".JS_is_transfer_tips").hide();
        $(".sub_btn").removeAttr("disabled");
    });
    $(".JS_close_open_p2p").click(function(){
        $(".JS_is_open_p2p").hide();
    })

    function switchToNum(str) {
        if (!isNaN(str)){
            str=Number(str);
        }else{
            str=0;
        }
        return str;
    }

    //阻止弹窗滚动
    $(".cunguan_bg").bind("touchmove",function(event){
        event.preventDefault();
    });
    $(".alert_evaluate").on('touchstart',function(){
        $(".alert_evaluate").on('touchmove',function(event) {
            event.preventDefault();
        }, false);
    })
    $(".alert_evaluate").on('touchend',function(){
        $(".alert_evaluate").unbind('touchmove');
    });
//    计算手续费
    $('#chargeComputed').on('click',function () {
        $('#goldComputedRuleBox').css('display','flex');
        $('#goldComputedRuleBox').css('display','-webkit-box');
        $('#goldComputedRuleBox').css('display','-ms-flexbox');
    });
    $('#goldComputedRuleBox').find('.closeA').on('click',function () {
        $('#goldComputedRuleBox').hide();
    });

    /**
     * 取两位小数，不四舍五入
     */
    function numFloor(val) {
        var valnew = parseFloat(val).toFixed(3);
            valnew = valnew.toString();
        var result = valnew.substring(0,valnew.toString().length - 1);
        return result;
    }

    /**
     * 显示预计金额
     */
    function showPredict() {
        var currentVal=Number($('.btn_key').html());//当前金额
        var realTimeVal=$('#realTime_val');
        var rateVal=$('#buyerFee').val()*currentVal;
        var goldPrice = $("#goldPrice").val();
        var discountAmount = $('.val_discount_amount').html();
        if (!isValidNum(currentVal) || currentVal==0){
            realTimeVal.hide();
            return;//不是合法数字或者值为0时，直接返回，不显示预计金额
        }else{
            realTimeVal.show();
        }
        realTimeVal.find('p:eq(0)').find('span').text(showDou(
                numFloor(rateVal + currentVal * goldPrice)
            ));

        realTimeVal.find('p:eq(1)').show();
        realTimeVal.find('p:eq(1)').find('span').text(showDou(
                numFloor(rateVal)));
    }

    /**
     * 判断是不是合法数字
     * @param num
     * @returns {boolean}
     */
    function isValidNum(num) {
        var flag=true;
        if (/^\s*$/.test(num) || isNaN(num)){
            flag=false;
        }
        return flag;
    }
});
