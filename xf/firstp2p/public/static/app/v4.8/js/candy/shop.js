$(function() {
    localStorage.clear();
    zhuge.track('进入商城页面');

    P2PWAP.app.triggerScheme("firstp2p://api?type=rightbtn&title=");
    if(!p2pBrowser.iosApp){
        P2PWAP.app.triggerScheme("firstp2p://api?method=updatebacktype&param=1");
    }

    $('.p_pick_discount .ling').click(function() {
        zhuge.track('信宝兑换商品');
        var product_type = $(this).data("type");
        var product_name = $(this).data("name");
        var product_stock = $(this).data("stock");
        var product_is_limited = $(this).data("is_limited");
        $('.mask .btn2').attr("data-id", $(this).data("id"));
        $('.mask .btn2').attr("data-type", product_type);
        $('.mask .btn2').attr("data-name", product_name);
        $('.mask_01 .btn2').attr("data-id", $(this).data("id"));
        $('.mask_01 .btn2').attr("data-type", product_type);
        $('.mask_01 .btn2').attr("data-name", product_name);
        var xbprice = $(this).data('price');
        var candy_amount = $('.JS_candy').val();
        if(product_stock == 0 && product_is_limited){
            P2PWAP.ui.toast("该优惠券可兑换库存量不足")
            return false;
        }
        if (xbprice > candy_amount) {
            P2PWAP.ui.toast("可用信宝不足")
        } else {
            if (product_type == 1) {
                $('.mask').show();
            }
            if (product_type == 2) {

                $.ajax({
                    url: "/address/listAll",
                    type: "post",
                    dataType: "json",
                    data: {
                        token: $(".JS_token").val(),
                    },
                    success: function(json) {
                        if (json.errno == 0) {
                            if (!json.data.list) {
                                $('.mask_02').show()
                            } else {
                                $('.mask_01').show()
                                $('.mask_01 .name').html(json.data.list[0].consignee + " " + json.data.list[0].mobile);
                                $('.mask_01 .deatail').html(json.data.list[0].area + " " + json.data.list[0].address);
                                $('.mask_01 .btn2').attr("data-address", json.data.list[0].id);
                            }
                        } else {
                            P2PWAP.ui.toast(json.error)
                        }
                    }
                })
            }
        }

    })
    $('.mask .btn1').click(function() {
        $('.mask').hide();
    })
    $('.mask .btn2,.mask_01 .btn2').click(function() {
        $('.mask').hide();
        var type = $(this).data("type");
        var productId = $(this).data("id");
        var productName = $(this).data("name");
        var _tmpRegData = {
            token: $('.JS_token').val(),
            productId: productId,
            addressId: $(this).data("address")
        }
        $.ajax({
            url: "/candy/Exchange",
            type: "post",
            dataType: "json",
            data: _tmpRegData,
            success: function(json) {
                $('.mask_01').hide();
                if (json.errno == -1) {
                    P2PWAP.ui.toast(json.error);
                    zhuge.track('信宝兑换失败', {
                        '商品id': productId,
                        '商品名称': productName,
                        '失败原因': json.error
                    });
                } else {
                    zhuge.track('信宝兑换成功', {
                        '商品id': productId,
                        '商品名称': productName
                    });
                    if (type == 1) {
                        P2PWAP.ui.toast('<span class="give_suc_icon"></span><p>兑换成功</p>');
                    } else {
                        P2PWAP.ui.toast('<span class="give_suc_icon"></span><p>恭喜您兑换成功，稍后会有</p><p>客服人员与您联系</p>');
                    }
                    setTimeout(function(){
                        location.reload();
                    },2000);
                }
            }
        })
    })

    $(".useDayLimit").each(function() {
        var useDayLimit = $(this).html() * 1;
        useDayLimit = (useDayLimit / 86400);
        $(this).html(useDayLimit);
    })
    $(".useStartTime").each(function() {
        var useStartTime = $(this).html() * 1;
        $(this).html(WXP2P.UTIL.dataFormat(useStartTime, "", 1));

    })
    $(".useEndTime").each(function() {
        var useEndTime = $(this).html() * 1;
        $(this).html(WXP2P.UTIL.dataFormat(useEndTime, "", 1));
    })
    // 取消收货地址
    $('.mask_02 .btn1').click(function() {
        $('.mask_02').hide();
    })

    $('#goToLive').attr('href',function () {
        return $(this).attr('href')+encodeURIComponent($('#shopUrl_hidden').val() + "&token=" + $('.JS_token').val());
    });
    $('.entityList').attr('href',function () {
        return $(this).attr('href')+encodeURIComponent($(this).data('url') + "&token=" + $('.JS_token').val());
    })
    $(".JS_cre").click(function(){
        zhuge.track('商城cre入口')
    })
    $('.JS_xnsp').click(function(e){
        var a =  e.currentTarget.childNodes[2].parentElement.innerText.split("\n");
        var reg = /([0-9](\u4fe1)(\u5b9d))/;
        var xb = '';
        var x = 0;
        for(var i = 0 ; i < a.length;i++){
            if( reg.test(a[i])){
                xb = a[i];
                x = i;
            }
        }
        zhuge.track("信宝商城集合页_虚拟商品点击情况",{
          "虚拟商品名称":a[x-1],
          "所需信宝值":xb
        })
    })
    $('.JS_jxsp').click(function(e){
        var a =  e.currentTarget.childNodes[2].parentElement.innerText.split("\n");
        var reg = /([0-9](\u4fe1)(\u5b9d))/;
        var xb = '';
        var x = 0;
        for(var i = 0 ; i < a.length;i++){
            if( reg.test(a[i])){
                xb = a[i];
            }
        }
        zhuge.track("信宝商城集合页_精选商品点击情况",{
          "所需信宝值":xb
        })
    })
    $('.JS_more').click(function(){
        zhuge.track("信宝商城集合页_精选商品_点击更多")
    })
})