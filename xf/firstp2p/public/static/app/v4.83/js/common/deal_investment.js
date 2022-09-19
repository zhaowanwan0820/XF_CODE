 $(function() {
    // 邀请码
    var invite_num = $('.invite_num').html();
    var QX_content;
    if(invite_num !== '' ){
        $('.JS_place').hide();
        $('.JS_icon_arrow').show()
    }else{
        $('.JS_icon_arrow').hide();
        $('.JS_place').show();
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

    function numAdd(arg1, arg2) {
        var m1,m2,
              s1 = arg1.toString(),
              s2 = arg2.toString();
          try {
              m1 = s1.split(".")[1].length
          } catch (e) {};
          try {
              m2 = s2.split(".")[1].length
          } catch (e) {};
          return ((Number(s1.replace(".", "")) + Number(s2.replace(".", ""))) / Math.pow(10, Math.max(m1,m2))).toFixed(2);
      }

    //总资产明细、双账户
    var deal_type = $('#deal_type').val()
    var specialMoneyNew = $('#specialMoney').val().replace(/,/g,"");
    var bonusMoneyNew = $('#JS_bonus').val().replace(/,/g,"");
    var p2pMoneyNew = $('#p2pMoney').val().replace(/,/g,"");
    var useable_p2pMoney =  numAdd(p2pMoneyNew,bonusMoneyNew);
    var p2p_ableMoney = showDou(useable_p2pMoney);
    var useable_wxMoney = numAdd(specialMoneyNew,bonusMoneyNew);
    var wx_ableMoney = showDou(useable_wxMoney)
    var able = ($(".JS_remian_money").html().replace(/,/g,""))*1;
    $('.JS_maskBack').unbind().click(function() {
        $('.p_affirm .mask').hide()
    });
    function spliteQX() {
        QX_content = window['JS_timelimit']
        QX_num = QX_content.replace(/[^0-9]/ig,"");
        QX_txt = QX_content.replace(/[0-9]/ig,"");
        $('.JS_num').html(QX_num)
        $('.JS_unit').html(QX_txt)
    }
    spliteQX()
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

    //获取url参数
    function GetRequestURL(hrefD) {
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

    // 更新按钮状态和链接
    function updateState() {
        investmentID = $(".investmentID").html();
        var int_merry = $(".ui_input .btn_key").html() * 1;
        if($(".icon_select").length>0){
            var hrefD = $(".icon_select").parent().parent(".j-selectA").attr("data-href");
            var Request = new Object();
            Request = GetRequestURL(hrefD);
            var discount_id = Request["discount_id"];
            var discount_group_id = Request["discount_group_id"];
            var discount_sign = Request["discount_sign"];
            var discount_type = Request["discount_type"];
            var discountbidAmount = Request["discount_bidAmount"];
        }else{
            var discountbidAmount = $(".val_discount_bidAmount").html() * 1;
        }
        var deal_min = Math.max($('.val_mini').html().replace(/\,|元/g, '') * 1, discountbidAmount);
        var dealMinXS = deal_min.toString().split(".").length > 1 ? deal_min : deal_min + '.00'
        code = $('.val_code').html();
        couponIsFixed = $('.is_fixed').html();
        var _perpent = $(".perpent").html();
        var per = 0;
        if (_perpent != "") {
            per = parseFloat($(".perpent").html());
        }
        if(window['deal_type'] == 0){
          $(".inp_text").html('<span class="fs30 din_alternate">' + dealMinXS + '</span> 元起');
        }else{
          $(".inp_text").html('<span class="fs30 din_alternate">' + dealMinXS + '</span> 元起投');
        }

        // 金额判断
        if (int_merry == '') {
            $(".dit_yq").html("");
            moneyValidate = false;
        } else if (/^(\d+|\d+\.|\d+\.\d{1,2})$/.test(int_merry)) {
            if (int_merry < deal_min) {
                if(window['deal_type'] == 0){
                  $(".dit_yq").html('最低出借金额为' + deal_min + '元').addClass("ai_color");
                }else{
                  $(".dit_yq").html('起投金额为' + deal_min + '元').addClass("ai_color");
                }
                moneyValidate = false;
            } else {
                moneyValidate = true;
            }
        } else {
            $(".dit_yq").html("输入有误").addClass("ai_color");
            moneyValidate = false;
        }

        addParams = "&money=" + int_merry + "&code=" + code + "&couponIsFixed=" + couponIsFixed;
        if($(".icon_select").length>0){
            addParams = addParams + "&discount_id=" + discount_id + "&discount_group_id=" + discount_group_id
            + "&discount_type=" + discount_type
            + "&discount_sign=" + discount_sign
            + "&discount_bidAmount=" + discountbidAmount
            + "&discount_goodprice=" + $(".val_discount_goodprice").html();
        }else{
            addParams = addParams + "&discount_id=" + $(".val_discount_id").html() + "&discount_group_id=" + $(".val_discount_group_id").html()
            + "&discount_type=" + $(".val_discount_type").html()
            + "&discount_sign=" + $(".val_discount_sign").html()
            + "&discount_bidAmount=" + $(".val_discount_bidAmount").html()
            + "&discount_goodprice=" + $(".val_discount_goodprice").html();
        }

        $('.ditf_list a.to_coupon').attr('href', 'invest://api?type=searchCoupon&id=' + investmentID + addParams);
        $('a.to_recharge').attr('href', 'invest://api?type=recharge&id=' + investmentID + addParams);
        $('a.to_contractList').attr('href', 'invest://api?type=contractList&id=' + investmentID + addParams);
        $('a.to_youhuiquanList').attr('href', 'invest://api?type=selectCoupon&deal_id=' + investmentID + addParams + "&discount_type=0");
        if (moneyValidate) {
            // 收益
            if ($(".istongzhi").html() != "1") {
                var _earning = showDou((accMul(int_merry, per) / 100).toFixed(2));
                if(window['deal_type'] == 0){
                    $(".dit_yq").html("借款利息" + _earning + "元").removeClass("ai_color");
                }else{
                    $(".dit_yq").html("预期收益" + _earning + "元").removeClass("ai_color");
                }
            } else {
                $(".dit_yq").html("");
            }
            if(int_merry > totalMoney){
                investChooseValidate = false;
            }
            //投资按钮
            // $(".sub_btn").removeClass("sub_red").addClass("sub_gay").attr("disabled" , "disabled");
            // if(val_discount_id || int_merry > totalMoney || (val_discount_id && int_merry <= totalMoney)){
            //     if(int_merry > totalMoney){
            //         investChooseValidate = false;
            //     }
            //     if(window["_needForceAssess_"] == 0 || window["_is_check_risk_"] == 0){//0代表不需要强制测评或者不需要校验个人评级
            //         $(".sub_btn").attr("href", "invest://api?type=invest&id=" + investmentID + addParams);
            //     }
            // }else{
            //     $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
            // }
            $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
        } else {
            //投资按钮
            $(".sub_btn").removeClass("sub_red").addClass("sub_gay").attr("disabled" , "disabled");
        }
        //拼接开户url
        if(window['isBankcard'] == 1){//已绑卡
            var _is_open_p2p_param = '{"srv":"register" , "return_url":"storemanager://api?type=closecgpages"}';//开户参数
        }else{//未绑卡
            var _is_open_p2p_param = '{"srv":"registerStandard" , "return_url":"storemanager://api?type=closecgpages"}';//开户参数
        }
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
            url: "/discount/AjaxExpectedEarningInfo?token=" + $('.token').html() + '&id=' + $('.investmentID').html() + '&money=' + int_merry + "&discount_id=" + val_discount_id,
            success: function(json){
                if(!!json.data){
                    $('.JS_display').hide();
                    $(".JS-selected_discount").show();
                    $(".coupon_detail .con").html(json.data.discountDetail);
                    $(".val_discount_goodprice").html(json.data.discountGoodPrice);
                    discount_goodprice = $(".val_discount_goodprice").html();
                    updateState();
                }
            }
        });
    }
    var computeIncome = function(callBack) {
        var callBackFlag=false;
        discountListTotalPage = 0;
        var int_merry = $(".ui_input .btn_key").html();
        if(val_discount_id){
            getPrice(int_merry,val_discount_id);
        }
        //投资券选择优化     start
        //投资券列表弹框 展示
        var _level = Math.max($('.val_mini').html().replace(/\,|元/g, '') * 1, $('.val_bidAmount').html() * 1);
        int_merry = parseFloat(int_merry);
        val_discount_id = $('.val_discount_id').html();
        var o2oDiscountSwitch = $("#o2oDiscountSwitch").val();
        if((int_merry != 0 && int_merry >= _level && int_merry <= totalMoney && o2oDiscountSwitch == 1) || int_merry > totalMoney ){
            if(!val_discount_id){
                investChooseValidate = true;
                $(".load_box").empty();
                $(".tab_con").scrollTop(0);
                loadmoreC = new WXP2P.UI.P2PLoadMore($(".load_box")[0], $('.tb0-more')[0], '/discount/AjaxConfirmPickList?token='+usertoken+'&deal_id='+ discount_id +'&site_id='+siteId+'&money='+int_merry, 1, 'post', 10);
                callBackFlag=true;
                loadmoreC.createItem = function(item){
                    var href = 'invest://api?type=invest&id='+ discount_id+'&money='+int_merry +'&code='+code + "&couponIsFixed=" + couponIsFixed;
                    href += '&discount_id=' + item.id + '&discount_group_id=' + item.discountGroupId + '&discount_sign=' + item.sign
                        + '&discount_bidAmount=' + item.bidAmount + '&discount_type=' + item.type;

                    var icon_select = "";
                    var dl = document.createElement("div");
                    var html = "";
                    html +='<div class="con"> ';
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
                        html += '</div>'
                    }
                    if (discount_id == item.id) {
                        icon_select = " icon_select" ;
                    }
                    html += '<div class="j-icon-select'+ icon_select +'"></div>';
                    html += '<dt>';
                    if(item.type == 1){
                        html += '        <h2><span class="f28">'+ item.goodsPrice+'</span>元</h2>返现券';
                    }else if(item.type == 2){
                        html += '        <h2>+<span class="f28">'+ item.goodsPrice+'</span>%</h2>加息券';
                    }else if(item.type == 3){
                        html += '        <h2><span class="f28">'+ item.goodsPrice+'</span>克</h2>黄金券';
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
                        if(window['deal_type'] == 0){
                            html += '出借满'+item.bidAmount+'元，期限满'+item.bidDayLimit+'天可用';
                        }else{
                            html += '金额满'+item.bidAmount+'元，期限满'+item.bidDayLimit+'天可用';
                        }
                    }else{
                        if(window['deal_type'] == 0){
                            html += '出借满'+item.bidAmount+'元可用';
                        }else{
                            html += '金额满'+item.bidAmount+'元可用';
                        }
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
                    callBackFlag && callBack && callBack();

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
        !callBackFlag && callBack && callBack();
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
            $("body").css({
              "overflow": 'auto',
              "position": "static"
            })
            $(".val_discount_id").html(val_discount_id);
            $(".val_discount_goodprice").html(discount_goodprice);
            $(".sub_btn").attr("href" , href);
            getPrice(int_merry,val_discount_id);
        });

    });
    //去抖函数，相邻操作500ms内禁止发请求,防止频繁发送请求
    var getPrice_debounce=function(idle,action){
        var last=null;
        return function(){
            var ctx = this, args = arguments;
            clearTimeout(last);
            last = setTimeout(function(){
                action.apply(ctx, args);
            }, idle);
        }
    }(500,function (int_merry,val_discount_id) {
        getPrice(int_merry,val_discount_id);
    });
    if(window['deal_type'] == 0){
      var qitou_text = '起';
    }else{
      var qitou_text = '起投';
    }
    // 初始化键盘
    var vir_input = new virtualKey($(".ui_input"), {
        placeholder: Math.max($('.val_mini').html().replace(/\,|元/g, '') * 1, $('.val_bidAmount').html() * 1) + qitou_text,
        delayHiden: function() {
            // computeIncome();
            updateState();
            document.body.removeEventListener('touchmove', cancleDefault);
            var ipt_val = $(".ui_input .btn_key").html();
            if(ipt_val == ''){
                $('.input_deal').removeClass('borer_yellow');
            }
            $("body").css({
                "overflow": 'auto',
                "position": "static"
            })
        },
        focusFn: function() {
            discountListTotalPage = 0;
            $(".sub_btn").removeClass("sub_red").addClass("sub_gay").attr("disabled", 'disabled');
            $('.input_deal').addClass('borer_yellow');
        },
        changeFn: function() {
            iptChangeFn();
            $("body").css({
                "overflow": 'hidden',
                "position": "fixed",
                "width": "100%"
            })
            var int_merry = $(".ui_input .btn_key").html()*1;
            val_discount_id = $(".val_discount_id").html();
            if(val_discount_id){
                // getPrice(int_merry,val_discount_id);
                getPrice_debounce(int_merry,val_discount_id);//由原来的直接调用，改为调用去抖函数
            }

        }
    });
    function iptChangeFn() {
        var ipt_val = $(".ui_input .btn_key").html();
        $(".show_daxie").empty().append($.getformatMoney(ipt_val, "show_money_ul", "active"));
    }
    // 初始化金额
    var val_money = $(".val_money").html();
    if (val_money > 0) {
        $(" .ui_input .btn_key").html(val_money);
        $(".inp_text").addClass("disnone");
    }
    iptChangeFn();
    updateState();
    function start(){
      //全投
        var wait = 2;
        $("#quantou_all").bind("click", function() {

            time();
            var yuer = $(".deal_money").html().trim();
            yuer = yuer.replace(/,/g,'');
            var dealLeft = $(".deal_money").html().trim();
            $(".ui_input .btn_key").html(Math.min(able, yuer));
            $(".inp_text").addClass("disnone");
            iptChangeFn();
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
                investChooseValidate = true;
                $('.JS_display').show();
                $(".JS_coupon_num").text(json.data);
                if (json.data < 1) {
                    $(".JS_red").removeClass('color_red')
                }
                if(json.data > 0){
                    $(".JS_red").addClass('color_red')
                    var _TOUZIQUAN_GUIDE_COOKIE_NAME_ = '_app_touziquanguide_';
                    function tryShowTouziQuanGuide() {
                        var guidecokkiestr = WXP2P.APP.getCookie(_TOUZIQUAN_GUIDE_COOKIE_NAME_);
                        var guideList = guidecokkiestr != null && guidecokkiestr != "" ? guidecokkiestr.split(",") : [];
                        for (var i = guideList.length - 1; i>= 0; i--) {
                            if (guideList[i] == window['_userid_']) return;
                        }
                        $('.JS-touziyindao').show();
                        $('.dit_btn').removeClass('index1000').addClass('index100')
                        $('.ui_mask_white').click(function() {
                            $('.JS-touziyindao').hide();
                            $('.dit_btn').removeClass('index100').addClass('index1000')
                        });
                        guideList.push(window['_userid_']);
                        WXP2P.APP.setCookie(_TOUZIQUAN_GUIDE_COOKIE_NAME_, guideList.join(","), 365);
                    }
                    tryShowTouziQuanGuide();
                }
            }
        })
    }
    if(val_discount_id){
        var int_merry = $(".ui_input .btn_key").html() * 1
        getPrice(int_merry,val_discount_id);
    }else{
        getDiscountNum();
    }
    //删除优惠券
    $('.JS-selected_discount .JS_close').bind('click', function() {
        $('.JS-selected_discount').hide();
        $('.val_discount_id').html('');
        $('.val_discount_group_id').html('');
        $('.val_discount_type').html('');
        $('.val_discount_sign').html('');
        $('.val_discount_bidAmount').html('');
        $(".val_discount_goodprice").html('');
        $(".card").remove();
        $(".j-icon-select").removeClass('icon_select');
        val_discount_id='';
        deal_min = $('.val_mini').html().replace(/\,|元/g, '') * 1;
        computeIncome();
        updateState();
        getDiscountNum();
        cleardiscount();
    });

    $('.JS_charge').bind('click',function() {
        zhuge.track('投资确认页(尊享)-点击充值');
    })

    function cleardiscount(){
        $(".investBg").addClass('disnone');
        $(".investChoose").addClass('disnone');
        $(".investList").removeClass('show');
        $("body").css({
          "overflow": 'auto',
          "position": "static"
        })
        $('.JS-selected_discount').hide();
        $('.val_discount_id').html('');
        $('.val_discount_group_id').html('');
        $('.val_discount_type').html('');
        $('.val_discount_sign').html('');
        $('.val_discount_bidAmount').html('');
        $(".val_discount_goodprice").html('');
        val_discount_id='';
        deal_min = $('.val_mini').html().replace(/\,|元/g, '') * 1;
        // oldMoney = newMoney;
        computeIncome();
        updateState();
        $(".chooseYes").removeClass("chooseConfirm").unbind("click").attr("href", "javascript:void(0);");
        document.body.removeEventListener('touchmove', cancleDefault);
        $(".j-icon-select").removeClass('icon_select');
        $(".tab_con").scrollTop(0);
    }

    $("#closeInvest,.investBg").bind('click', function(event) {
        investChooseValidate = true;
        cleardiscount();
    });

    //存管逻辑
    function supervision(){
        var showDiscount = $(".sub_btn").attr("data-showDiscount");
        supervision.isPending=true;
        computeIncome(function () {
            $.ajax({
                url: '/deal/pre_bid?token='+ $('.token').html() + '&id=' + $(".investmentID").html() + '&money=' + $(".ui_input .btn_key").html() + '&coupon=' + $('.val_code').html(),
                type: 'post',
                dataType: 'json',
                success: function(json){
                    $(".JS_bid_btn").remove();
                    //开户url
                    if(json.data.status == 3 || json.data.status == 6){//验密划转，需要去银行验密页面划转
                        $(".JS_is_transfer").show();
                        $(".JS_trans_money").html(json.data.data.transfer+"元");
                        $(".remain_m").html(json.data.data.remain);
                        //拼接划转url
                        if(json.data.status == 3){//网贷-网信 专享标
                            var _is_transfer_param = '{"srv":"transfer" , "amount":"'+json.data.data.transfer+'","return_url":"storemanager://api?type=closecgpages"}';
                        }else if(json.data.status == 6){ //网信-网贷 p2p
                            var _is_transfer_param = '{"srv":"transferWx","amount":"'+json.data.data.transfer+'","return_url":"storemanager://api?type=closecgpages"}';
                        }
                        var _en_is_transfer_param = encodeURIComponent(_is_transfer_param);
                        var _istransferUlr = location.origin + "/payment/Transit?params=" + _en_is_transfer_param;
                        $(".JS_transfer_btn").attr({"href":'storemanager://api?type=webview'+ addParams +'&gobackrefresh=true&url='+encodeURIComponent(_istransferUlr)});
                    }else if(json.data.status == 7){
                        $(".JS_is_open_p2p").show();
                    } else if(json.data.status == -1){
                        var input_money = $('.btn_key').html();
                        var all_money = parseFloat(specialMoneyNew) + parseFloat(bonusMoneyNew) + parseFloat(p2pMoneyNew);
                        if ( (parseFloat(able) < input_money) && (input_money <= all_money)) {
                            $('.JS_dealDetail').show();
                            if (deal_type == 0){
                                var sub_money = parseFloat(input_money) - parseFloat(useable_p2pMoney);
                                var sub_moneyN = (Math.round(sub_money*100))/100;
                                var showDou_money = showDou(sub_moneyN.toFixed(2))
                                $('.JS_recharge').html(showDou_money)
                            }else {
                                var sub_money = parseFloat(input_money) - parseFloat(useable_wxMoney);
                                console.log(sub_money)
                                var sub_moneyN = (Math.round(sub_money*100))/100;
                                var showDou_money = showDou(sub_moneyN.toFixed(2))
                                $('.JS_recharge').html(showDou_money)
                            }
                            $('.JS_maskBack').click(function() {
                                $('.p_affirm .mask').hide()
                            });
                        }else {
                            WXP2P.UI.showErrorTip(json.data.data);
                        }
                    } else if(json.data.status == 2){
                        var _is_bid_param = '{"srv":"bid" , "money":"'+$(".ui_input .btn_key").html()+'" , "dealId":"'+ $(".investmentID").html() +'" , "couponId":"'+ $('.val_code').html() +'" , "couponIsFixed":"'+ $('.is_fixed').html() +'" , "discountId":"'+ $(".val_discount_id").html() +'" , "discount_group_id":"'+ $(".val_discount_group_id").html() +'" ,"discountType":"'+$(".val_discount_type").html()+'" , "discountSign":"'+ $(".val_discount_sign").html() +'" , "discountGoodsPrice":"'+ $(".val_discount_goodprice").html() +'","return_url":"storemanager://api?type=cginvest","sourceType":"' + window['source_type'] + '"}';

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
                            _is_bid_param = '{"srv":"bid" , "money":"'+$(".ui_input .btn_key").html()+'" , "dealId":"'+ $(".investmentID").html() +'" , "couponId":"'+ $('.val_code').html() +'" , "couponIsFixed":"'+ $('.is_fixed').html() +'" , "discountId":"'+ discount_id +'" , "discountGroupId":"'+ discount_group_id +'" ,"discountType":"'+ discount_type +'" , "discountSign":"'+ discount_sign +'" , "discountGoodsPrice":"'+ discount_goodprice +'","return_url":"storemanager://api?type=cginvest"}';
                        }
                        //开户参数
                        var _en_bid_param = encodeURIComponent(_is_bid_param);
                        var _bidUlr = location.origin + "/payment/Transit?params=" + _en_bid_param;
                        var p2pbid_href = 'storemanager://api?type=webview&gobackrefresh=true&url=' + encodeURIComponent(_bidUlr);


                        if(window["_BIDTYPE_"] == "7") {
                            $('.JS-gongyiconfirm.ui_mask').show();
                            $('#JS-confirmdonate').show();
                            $('#JS-confirmdonate .J_ok').attr("href", p2pbid_href);
                            $('#JS-confirmdonate .J_no').click(function() {
                                $('#JS-confirmdonate').hide();
                                $('.JS-gongyiconfirm.ui_mask').hide();
                                $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
                            });
                        }else if(!val_discount_id && investChooseValidate && discountListTotalPage>0 && showDiscount != 1 && window['siteId'] != 100){
                            $(".investBg").removeClass('disnone');
                            $(".investChoose").removeClass('disnone');
                            $(".investList").addClass('show');
                            $("body").css({
                                "overflow": 'hidden',
                                "position": "fixed",
                                "width": "100%"
                            })
                            $(".chooseNo").click(function(event) {
                                investChooseValidate = false;
                                $(".sub_btn").attr("data-showDiscount","1");
                                cleardiscount();
                            });
                            return false;
                        }else{
                            console.log(p2pbid_href,"p2pbid_href");
                            $("body").append('<a href="'+p2pbid_href+'" class="JS_bid_btn"></a>');
                            $(".JS_bid_btn").click();
                        }

                    }else if(json.data.status == 4 || json.data.status == 5){
                        $(".JS_is_transfer_tips").show();
                        $(".JS_is_transfer_tips .JS_trans_money").html(json.data.data.transfer+"元");
                        $(".JS_is_transfer_tips .remain_m").html(json.data.data.remain);
                        var transfer_type = "";
                        if(json.data.status == 4){
                            transfer_type = 1;
                            $(".JS_close_transfer_tips").wrap('<a href="javascript:void(0);" class="MD_trans_to_p2p_cancel"></a>');
                            $(".JS_select_point").wrap('<a href="javascript:void(0);" class="MD_trans_to_p2p_ok"></a>');
                        }else{
                            transfer_type = 2;
                            $(".JS_close_transfer_tips").wrap('<a href="javascript:void(0);" class="MD_trans_to_super_cancel"></a>');
                            $(".JS_select_point").wrap('<a href="javascript:void(0);" class="MD_trans_to_super_ok"></a>');
                        }
                        $(".JS_is_transfer_tips .JS_transfer_btn").unbind("click");
                        $(".JS_is_transfer_tips .JS_transfer_btn").bind("click" ,function(){
                            $.ajax({
                                url:"/payment/Transfer?money=" + json.data.data.transfer + "&type=" + transfer_type + "&dontTip=" + $(".no_tip_checkbox").val() +"&token=" + $('.token').html(),
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
                                    }
                                    $(".JS_is_transfer_tips").hide();
                                    $(".JS_is_transfer_tips .JS_transfer_btn").removeAttr('disabled');
                                    $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
                                },
                                error:function() {
                                    WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
                                    $(".JS_is_transfer_tips .JS_transfer_btn").removeAttr('disabled');
                                    $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
                                }
                            })
                        })
                    }else if(json.data.status == 1){
                        var href = "invest://api?type=invest&id=" + investmentID + addParams
                        var icon_select = $(".icon_select").length;
                        if($(".icon_select").length>0){
                            href += "&discount_goodprice="+discount_goodprice;
                        }

                        if(window["_BIDTYPE_"] == "7") {
                            $('.JS-gongyiconfirm.ui_mask').show();
                            $('#JS-confirmdonate').show();
                            $('#JS-confirmdonate .J_ok').attr("href", href);
                            $('#JS-confirmdonate .J_no').click(function() {
                                $('#JS-confirmdonate').hide();
                                $('.JS-gongyiconfirm.ui_mask').hide();
                                $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
                            });
                        }else if(!val_discount_id && investChooseValidate && discountListTotalPage>0 && showDiscount != 1 && window['siteId'] != 100){
                            $(".investBg").removeClass('disnone');
                            $(".investChoose").removeClass('disnone');
                            $(".investList").addClass('show');
                            $("body").css({
                              "overflow": 'hidden',
                              "position": "fixed",
                              "width": "100%"
                            })
                            $(".chooseNo").click(function(event) {
                                investChooseValidate = false;
                                $(".sub_btn").attr("data-showDiscount","1");
                                cleardiscount();
                            });
                            return false;
                        }else{
                            $("body").append('<a href="'+href+'" class="JS_bid_btn"></a>');
                            $(".JS_bid_btn").click();
                        }
                    } else {
                        WXP2P.UI.showErrorTip(json.data.data);
                        $(".sub_btn").removeClass("sub_gay").addClass("sub_red").removeAttr("disabled");
                    }
                },
                error:function() {
                    WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
                },
                complete:function () {
                    supervision.isPending=false;
                }
            })
        });
    }
    supervision.isPending=false;
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

    // 公益标
    $(".sub_btn").bind("click", function(event) {
        var int_merry = $(".ui_input .btn_key").html() * 1;
        zhuge.track('尊享投资确认页_点击投资',{
            "期限": int_merry,
            "投资金额": QX_content
        })
        if (supervision.isPending){
            return;
        }
        var $t = $(this);
        if (!moneyValidate) return true;
        if(window['allowBid'] != 1){//非投资户不可投资
            if(window['deal_type'] == 0){
              WXP2P.UI.showErrorTip("非投资账户不允许出借");
            }else{
              WXP2P.UI.showErrorTip("非投资账户不允许投资");
            }
            return false;
        }
        $t.attr("disabled","disabled");
        if (window['_needForceAssess_']==1) { //强制风险测评弹窗
            $(".is_eval").show();
            $("#JS-is-evaluate").show();
            var l_origin = location.origin;
            var urlencode = l_origin + "/user/risk_assess?token=" + $('.token').html() + "&from_confirm=1";
            $(".eval_btn").attr('href', 'firstp2p://api?type=webview&money=&discount_id=&discount_bidAmount=&gobackrefresh=true&url=' + encodeURIComponent(urlencode));
            $(".no_eval,.eval_btn").click(function(){
                $(".is_eval").hide();
                $("#JS-is-evaluate").hide();
                $t.removeAttr("disabled");
            });
            return false;
        } else if(window['siteId'] == 100 && ($(".ui_input .btn_key").html()*1 > $(".JS_remian_money").html().replace(/,/g,'')*1)){
            //输入金额大于网贷账户余额加红包
            WXP2P.UI.showErrorTip("余额不足，请充值");
            return false;
        } else if(window['_is_check_risk_']==1){
            var l_origin = location.origin;
            var urlencode = l_origin + "/user/risk_assess?token=" + $('.token').html() + "&from_confirm=1";
            $("#ui_conf_risk").css('display','block');
            $("#JS-confirm").attr('href', 'firstp2p://api?type=webview&money=&discount_id=&discount_bidAmount=&gobackrefresh=true&url=' + encodeURIComponent(urlencode));
            $("#JS-cancel,#JS-know,#JS-confirm").click(function(){
              $("#ui_conf_risk").hide();
            });
            $t.removeAttr("disabled");
            return false;
        } else{
            $t.removeAttr("disabled");
            if($('#dealType').val()==0){//判断是不是p2p
                if (!singleLimit()){
                    return false;
                }
            }
            supervision();
            return false;
        }
        event.preventDefault();
        return true;
    });

    $(".point_open").click(function() {
        $(".account_money").toggle();
        $(this).toggleClass('down_img');
    });




    //关闭划转
    $(".JS_close_transfer").click(function(event) {
        $(".JS_is_transfer").hide();
    });

    $(".JS_close_transfer_tips").click(function(){
        $(".JS_is_transfer_tips").hide();
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
                if(window['deal_type'] == 0){
                  promptStr='您的风险承受能力为 '+dataJson.levelName+' 型,<br/>单笔最高出借额为 '+dataJson.limitMoney/10000+' 万元';
                }else{
                  promptStr='您的风险承受能力为 '+dataJson.levelName+' 型,<br/>单笔最高投资额度为 '+dataJson.limitMoney/10000+' 万元';
                }
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

    //点击同意埋点
    $('.sub_btn').click(function() {
        var input_money = $('.btn_key').html();
        zhuge.track('点击同意合同和协议并出借',{
            '产品类型':'随心约尊享',
            '出借金额': input_money
        })
    });
    
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
    
    if(zx_p2p == 0){
        $(".cz_btn").on('click',function(){
            zhuge.track('投资确认页-点击充值',{
                '产品类型':'p2p'
            })
        });
    }else{
        $(".cz_btn").on('click',function(){
            zhuge.track('投资确认页-点击充值',{
                '产品类型':'尊享'
            })
        });
    }
    
});


