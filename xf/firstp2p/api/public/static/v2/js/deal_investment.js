;
(function($) {
    $(function() {

        var accMul = function (arg1,arg2){
            var m=0,s1=arg1.toString(),s2=arg2.toString();
            try{m+=s1.split(".")[1].length}catch(e){};
            try{m+=s2.split(".")[1].length}catch(e){};
            return Number(s1.replace(".",""))*Number(s2.replace(".",""))/Math.pow(10,m);
        }
        var showDou = function(val) {
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

        var vir_input = new virtualKey($(".key_input"),{
            delayHiden : function(){
                getshouyi();
                genUrl();
            },
            focusFn: function(){
                disableHref();
                removeUrl();
            }
        });
        var yuer = $(".ketou_money").html().trim();
        var dealLeft = $(".deal_money").html().trim();
        var fenge = 1;
        var fengFlag = false;
        var money = 0;

        $(".dit_fenbtn>span").bind("tap", function () {
            if (fengFlag) return;
            fengFlag = true;
            fenge = $(this).attr("data-key");
            //修复全投精度
            if (fenge == 1) {
                money = yuer;
            } else {
                money = (yuer / fenge).toFixed(2);
            }
            $(".btn_key").html(Math.min(dealLeft, money));
            $(".inp_text").addClass("disnone");
            getshouyi();
            genUrl();
        });

        var val_money = $(".val_money").html();
        if (val_money > 0) {
            $(".btn_key").html(val_money);
            $(".inp_text").addClass("disnone");
            enableHref();
        } else {
            disableHref();
        }

        var dealcookie = "wx_touzi_phone_v3_cookie" + "{$deal.productID}";
        /***************** 添加cookie *********************/
        function _addCookie(name, value, second) {
            var exdate = new Date((new Date()).getTime() + second * 1000);
            document.cookie = name + "=" + escape(value) + ";path=/" +
                ((second == null) ? "" : ";expires=" + exdate.toGMTString());
        }

        /***************** 获取cookie *********************/

        function _getCookie(c_name) {
            if (document.cookie.length > 0) {
                var c_start = document.cookie.indexOf(c_name + "=");
                if (c_start != -1) {
                    c_start = c_start + c_name.length + 1;
                    var c_end = document.cookie.indexOf(";", c_start);
                    if (c_end == -1) c_end = document.cookie.length;
                    return unescape(document.cookie.substring(c_start, c_end));
                }
            }
            return "";
        }

        genUrl();

        //计算预期收益

        getshouyi();

        function getshouyi() {
            var investmentID = $(".investmentID").html();
            var int_merry = $('.key_input .btn_key').html();
            var mini_money = $('.val_mini').html().replace(/\,|元/g, '') * 1;
            var _perpent = $(".perpent").html();
            var per = 0;
            if (_perpent != ""){
                per = parseFloat($(".perpent").html())
            }
            if (/^(\d+|\d+\.|\d+\.\d{1,2})$/.test(int_merry)) {
                int_merry *= 1;
                if(int_merry == 0){
                    fengFlag = false;
                    disableHref();
                    $(".dit_yq").html("");
                    return;
                }
                if (int_merry < mini_money) {
                    $(".dit_yq").html('起投金额为' + mini_money + '元').addClass("ai_color");
                    disableHref();
                    fengFlag = false;
                    return;
                }
                // js 计算预期收益
                if ($(".istongzhi").html() != "1") {
                    var _earning = showDou((accMul(int_merry, per) / 100).toFixed(2));
                    $(".dit_yq").html("预期收益" + _earning + "元").removeClass("ai_color");
                }else{
                    $(".dit_yq").html("");
                }
                fengFlag = false;
                enableHref();

//                $.ajax({
//                    url: "/deal/expireEarning?id=" + investmentID + "&money=" + int_merry,
//                    type: 'POST',
//                    dataType: 'json',
//                    success: function(data) {
//                        fengFlag = false;
//                        if ($(".istongzhi").html() != "1") {
//                            $(".dit_yq").html("预期收益" + data.data.earning + "元").removeClass("ai_color");
//                        } else {
//                            $(".dit_yq").html("");
//                        }
//                        enableHref();
//                    },
//                    error: function() {
//                        fengFlag = false;
//                        $(".dit_yq").html("");
//                        disableHref();
//                    }
//                });
            } else {
                if(int_merry == ''){
                    $(".dit_yq").html("");
                }else{
                    $(".dit_yq").html("输入有误").addClass("ai_color");
                }
                disableHref();
            }
        }

        function genUrl() {
            var moneyVal = $('.key_input .btn_key').html();
            if(moneyVal == '') moneyVal = 0;
            $('.ditf_list a.to_coupon').attr('href', 'invest://api?type=searchCoupon&id=' + $(".investmentID").html() + '&money=' + moneyVal + '&code=' + $(".val_code").html() + '&couponIsFixed=' + $(".is_fixed").html());
            $('a.to_recharge').attr('href', 'invest://api?type=recharge&id=' + $(".investmentID").html() + '&money=' + moneyVal + '&code=' + $(".val_code").html());
            $('a.to_contractList').attr('href', 'invest://api?type=contractList&id=' + $(".investmentID").html() + '&money=' + moneyVal + '&code=' + $(".val_code").html());
        }

        function removeUrl() {
            $('.ditf_list a.to_coupon').attr('href', 'javascript:void(0);');
            $('a.to_recharge').attr('href', 'javascript:void(0);');
            $('a.to_contractList').attr('href', 'javascript:void(0);');
        }

        function enableHref() {
            //获取当前的ID
            var investmentID = $(".investmentID").html();
            var merry = $(".btn_key").html();
            var ma = $(".val_code").html();
            var newhref = "invest://api?type=invest&id=" + investmentID + "&money=" + merry + "&code=" + ma + "&use_bonus=1";
            $(".sub_btn").removeClass("sub_gay").addClass("sub_red").attr("href", newhref);
        }

        function disableHref() {
            $(".sub_btn").removeClass("sub_red").addClass("sub_gay").attr("href", 'javascript:void(0);');
        }

    });
})(Zepto)
