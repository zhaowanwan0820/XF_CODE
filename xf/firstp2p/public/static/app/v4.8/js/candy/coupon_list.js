$(function () {
    var candy_amount=Number($('#candy_amount').val().replace(',',''));//信宝总额度
    var token=$('#token_hidden').val();
    var confirm_exchange_mask=$('.confirm_exchange_mask');//确认兑换弹窗
    var flase;

    $(".useDayLimit").each(function() {
        var useDayLimit = $(this).html() * 1;
        useDayLimit = (useDayLimit / 86400);
        $(this).html(useDayLimit);
    });
    $('.ling').on('click',function () {
        var product_type = $(this).data("type");
        var product_name = $(this).data("name");
        var xb_stock = $(this).data("stock");
        var product_is_limited = $(this).data("is_limited");
        var xbprice = Number($(this).data('price'));
        var id=$(this).data("id");
        if(xb_stock == 0 && product_is_limited){
            P2PWAP.ui.toast("该优惠券可兑换库存量不足");
            return flase;
        }
        [confirm_exchange_mask.find('.btn2')].forEach((function (item) {
            item.data('id',id);
            item.data('type',product_type);
            item.data('name',product_name);
        }));

        if (xbprice > candy_amount) {
            P2PWAP.ui.toast("可用信宝不足")
        } else {
            if (product_type == 1) {
                confirm_exchange_mask.show();
            }
        }
    });
    confirm_exchange_mask.find('.btn1').click(function() {
        $('.'+$(this).data('closeClass')).hide();
    })
    confirm_exchange_mask.find('.btn2').click(function() {
        confirm_exchange_mask.hide();
        var type = $(this).data("type");
        var productId = $(this).data("id");
        var productName = $(this).data("name");
        var address=$(this).data("address");
        var _tmpRegData = {
            token: token,
            productId: productId,
            addressId: address
        }
        $.ajax({
            url: "/candy/Exchange",
            type: "post",
            dataType: "json",
            data: _tmpRegData,
            success: function(json) {
                if (json.errno == -1) {
                    P2PWAP.ui.toast(json.error);
                } else {
                    P2PWAP.ui.toast('<span class="give_suc_icon"></span><p>兑换成功</p>');
                    setTimeout(function(){
                        location.reload();
                    },2000);
                }
            }
        })
    });
})
