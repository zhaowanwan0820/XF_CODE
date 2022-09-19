$(function(){
  $(".JS_exchange").click(function(){
    var token=$(".JS_token").val();
    var productId = $(this).data("id");
    var productType =$(".JS_product_type").val();
    var productName,postUrl;
    if(productType==3){//productType(3是房租抵扣，4外币抵扣，其他是返现券)
        productName = "房租抵扣券";
        postUrl = "/candy/ExchangeYifangCoupon";
    } else if(productType==4) {
        productName = "外币抵扣券";
        postUrl = "/candy/ExchangeUnitedmoneyCoupon";
    } else if(productType==5) {
        productName = "每日瑜伽VIP会员";
        postUrl = "/candy/ExchangeYogaVIPCoupon";
    }else {
        productName = "返现券";
        postUrl = "/candy/Exchange";
    }
    var _tmpRegData = {
      token: token,
      productId: productId
    }
    WXP2P.UI.popup("确定要兑换该" + productName + "吗？","",true,true,"确认","取消",function(){
        $.ajax({
            url: postUrl,
            type: "post",
            dataType: "json",
            data: _tmpRegData,
            success: function(json) {
                if (json.errno == 0) {
                    zhuge.track('信宝兑换成功', {
                        '商品id': productId,
                        '商品名称': productName
                    });
                    WXP2P.UI.toast('<span class="give_suc_icon"></span><p>兑换成功</p>');
                    setTimeout(function(){
                        if(productType == 3 || productType == 4){
                            $(".JS_goto_coupon").remove();
                            $("body").append('<a class="JS_goto_coupon" href="firstp2p://api?type=native&name=other&pageno=17"></a>');
                            $(".JS_goto_coupon").click();
                        } else {
                            location.reload();
                        }
                    },2000);
                } else {
                    WXP2P.UI.toast(json.error);
                    zhuge.track('信宝兑换失败', {
                        '商品id': productId,
                        '商品名称': productName,
                        '失败原因': json.error
                    });
                }
            }
        })
    });
  })
})