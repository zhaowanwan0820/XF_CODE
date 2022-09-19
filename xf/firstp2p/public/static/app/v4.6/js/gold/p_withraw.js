$(function() {
    // tofixed
    /*Number.prototype.toFixed = function(len) {
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
    };*/
    var originFixed=Number.prototype.toFixed;
    /**
     * fixed方法重构
     * @param len
     * @param method 可能为round,ceil,floor
     * @returns {*}
     */
    Number.prototype.toFixed=function (len,method) {
        var baseNum=Math.pow(10,len);
        var num=0;
        if (typeof method=="undefined"){
            return originFixed.call(this,len);
        }else{
            num=Math.floor(this*baseNum*10)/10;//进行method处理前，只保留一位小数
            num=Math[method](num)/baseNum;
            return originFixed.call(num,len);
        }
    }
    //var num=1.0149;
    // var num=2;
    //console.log(num.toFixed(2,'round'));
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

    //全部提现
    function start(){
        var wait = 2;
        $("#quantou_all").bind("click", function() {
            time();
            var yuer = $(".tatal_realize").html().trim();//可变现克重
            yuer = yuer.replace(/,/g,'');
            var dealLeft = $(".day_realize").html().trim();//今日可变现克重
                dealLeft = dealLeft.replace(/,/g,'');
            var uiInputVal=Math.min(dealLeft, yuer);
            $(".ui_input .btn_key").html(uiInputVal.toFixed(3));
            $(".inp_text").addClass("disnone");
            iptChangeFn();
            updateState();
            $('.input_deal').addClass('borer_gold');
        });
        function time() {
            if (wait == 0) {
                $("#quantou_all")[0].removeAttribute("disabled");
                wait = 2;
            } else {
                $("#quantou_all")[0].setAttribute("disabled", true);
                $("#quantou_all").css("opacity","1");
                wait--;
                setTimeout(function() {
                    time()
                }, 1000)
            }
        }

    }
    start();



    var addParams;
    var investParams;//投资接口scheme额外参数
    var moneyValidate = false;
    var tradValidFlag=$('#tradFlag').val()==="";//是否在交易时段，在交易时段就为true,非交易时段为false
    !tradValidFlag && $('.sub_btn').text('非交易时段');
    var code,couponIsFixed ;
    function cancleDefault(evt) {
        if(!evt._isScroller) {
            evt.preventDefault();
        }
    }


    //预期收益计算
    function ExpectProfit(){
        var int_merry = $(".ui_input .btn_key").html() * 1;//输入变现克重
        var _perpent = $("#goldPrice").val();//实时金价
        var buyerFee = $("#buyerFee").val();//手续费
        
        var per = 0;
        var days = 1;
        if (_perpent != "") {
            per = parseFloat($("#goldPrice").val());
        }
        if (moneyValidate) {
            var _earning = showDou((accMul(int_merry, per ,days)).toFixed(2,'ceil'));
            var feeMoney = showDou((accMul(int_merry, buyerFee ,days)).toFixed(2,'ceil'));
            var minFree = $(".min_free").html();
            if(feeMoney.replace(/,/g,"") < minFree ){
                feeMoney = minFree;
                feeMoney = feeMoney.replace(/,/g,"");
            }
            $(".dit_yq").html("预期金额"+_earning+"元" + "(含手续费" + feeMoney + "元)").removeClass("ai_color").addClass("color_yellow");
        }
    }


    // 更新按钮状态和链接
    function updateState() {
        var day_realize = $(".day_realize").html();
            day_realize = day_realize.replace(/,/g,"");
        var tatal_realize = $(".tatal_realize").html();
            tatal_realize = tatal_realize.replace(/,/g,"");
        var int_merry = $(".ui_input .btn_key").html() * 1;
        var min_realize = Math.min(day_realize,tatal_realize);
        var _min_tips = "今日可变现"
        if(day_realize>tatal_realize){
            _min_tips = "可变现"
        }
        // 金额判断
        if (int_merry == '') {
            $(".dit_yq").html("");
            moneyValidate = false;
        } else if (/^(\d+|\d+\.|\d+\.\d{1,3})$/.test(int_merry)) {
            if(int_merry > min_realize){
                $(".dit_yq").html("输入克重超过" + _min_tips + "克重").removeClass("color_yellow").addClass("ai_color");
                moneyValidate = false;
            }else{
                moneyValidate = true;
            }
        } else {
            $(".dit_yq").html("输入有误").removeClass("color_yellow").addClass("ai_color");
            moneyValidate = false;
        }
        investParams="&token="+usertoken+'&gold='+int_merry + "&ticket=" + $('#ticket').val() + "&goldPrice="+$("#goldPrice").val();   
        if (moneyValidate && tradValidFlag) {
            $(".sub_btn").removeClass("sub_gay").addClass("sub_golden").attr("href", 'invest://api?type=goldwithdraw'+ investParams);
        } else {
            $(".sub_btn").removeClass("sub_golden").addClass("sub_gay").attr("href", 'javascript:void(0);');
        }
        if (moneyValidate){
            //体现按钮
            ExpectProfit();
        }
    };
    // 初始化键盘
    var vir_input = new virtualKey($(".ui_input"), {
        placeholder: '输入变现克重',
        decimalNum:3,
        delayHiden: function() {
            updateState();
            document.body.removeEventListener('touchmove', cancleDefault);
            var ipt_val = $(".ui_input .btn_key").html();
            if(ipt_val == ''){
                $('.input_deal').removeClass('borer_gold');
            }
        },
        focusFn: function() {
            $(".sub_btn").removeClass("sub_golden").addClass("sub_gay").attr("href", 'javascript:void(0);');
            $('.input_deal').addClass('borer_gold');
        },
        changeFn: function() {
            iptChangeFn();
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
    updateState();
    //投资按钮click事件
    $(".sub_btn").bind("click", function() {
        var $t = $(this);
        if (moneyValidate && tradValidFlag){
            return true;
        }else{
            return false;
        }
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
    //变现提示
    $('#chargeComputed').on('click',function () {
        $('#goldComputedRuleBox').css('display','flex');
        $('#goldComputedRuleBox').css('display','-webkit-box');
        $('#goldComputedRuleBox').css('display','-ms-flexbox');
    });
    $('#goldComputedRuleBox').find('.closeA').on('click',function () {
        $('#goldComputedRuleBox').hide();
    });
});
