$(function(){
    var uid = $("#unique_id").val(),
        bankObj = P2PWAP.cache.get(uid);

    //点击选择银行卡，列表滑出
    $("#bank_l").bind('click', function() {
        $("#p_bank_choice").addClass('p_show');
        $(".bg_cover").removeClass('dis_none');
        if(!!bankObj){
            $("#p_bank_choice .point_bank").each(function(){
                if($(this).data("id") == bankObj.id){
                    $(this).addClass("bg_img").siblings().removeClass("bg_img");
                }
            });
        }
    });

    $("#p_bank_choice").bind("touchmove",function(event){
            event.preventDefault();
     });

    $(".bg_cover").bind("touchmove",function(event){
            //console.log(event);
            event.preventDefault();
     });
    //点击遮盖层的时候隐藏弹窗
    $(".bg_cover").bind('click', function() {
        $("#p_bank_choice").removeClass('p_show');
        $(".bg_cover").addClass('dis_none');
    })
    
    //点击返回，列表滑走
    $("#back_but").bind('click', function() {
        $("#p_bank_choice").removeClass('p_show');
        $(".bg_cover").addClass('dis_none');
    });
    //使不可以左右滑，只有一屏
    $("body").css({"overflow-x":"hidden","width":"100%"});
    //判断是否选择银行
    function updateNextBtnState() {
        var bankname = $("#bankname").val();
        if (bankname!='') {
            $("#next_but").removeAttr("disabled"); 
        } else {
            $("#next_but").attr("disabled");
        }    
    }

    //判断是否有本地存储
    if(!!bankObj){
        $("#bank_list_choice").html(bankObj.name).removeClass("holder");
        $("#bankname").val(bankObj.id);
        updateNextBtnState();
    }


    //选择银行后打钩，选择后使按钮置红
    $("#p_bank_choice .point_bank").bind('click', function() {
        var id = $(this).data("id");
        $(this).addClass("bg_img").siblings().removeClass("bg_img");
        $("#p_bank_choice").removeClass('p_show');
        var name = $.trim($(this).text());
        $("#bank_list_choice").html(name).removeClass("holder");
        P2PWAP.cache.set(uid , {
            name : name ,
            id : id
        });
        
        $("#bankname").val(id);
        updateNextBtnState();
        $(".bg_cover").addClass('dis_none');
    }); 


    // $("#postForm").submit(function(){
    //     if(!$("#bankname").val()){
    //         P2PWAP.ui.showErrorTip("请选择银行");
    //         return false;
    //     }
    // });



    /*
    数字序列化
    exp: showDou(2888888);
    输出：2,888,888
    */
    var showDou = function(val) {
        if(typeof val == 'undefined' || !val){
            return ;
        }
        var arr = val.toString().split("."),
            arrInt = arr[0].split("").reverse(),
            temp = 0,
            j = arrInt.length / 3;

        for (var i = 1; i < j; i++) {
            arrInt.splice(i * 3 + temp, 0, ",");
            temp++;
        }
        if(arr[1]){
            return arrInt.reverse().concat(".", arr[1]).join("");
        }else{
            return arrInt.reverse().concat(".", "00").join("");
        }
        
    };
    var format=$("#format_num");
    var no_blank = $.trim(format.text());
    var num = Number(no_blank);
    var num_2 = num.toFixed(2);
    format.html(showDou(num_2));
    
});
