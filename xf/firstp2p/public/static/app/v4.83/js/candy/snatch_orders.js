
var timeStamp = document.getElementsByClassName("records_time");
var timeCode = document.getElementsByClassName("time_code");
for(var i = 0; i < timeStamp.length ;i++){
    time = parseInt(timeStamp[i].innerText);
    var date = new Date(time);
    Y = date.getFullYear();
    M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1);
    D = (date.getDate() < 10 ? '0'+ (date.getDate()):date.getDate());
    h = (date.getHours() < 10 ? '0'+ (date.getHours()):date.getHours());
    m = (date.getMinutes() < 10 ? '0'+ (date.getMinutes()):date.getMinutes());
    s = (date.getSeconds() < 10 ? '0'+ (date.getSeconds()):date.getSeconds());
    if(time % 1000 < 10){
        ms = '00' + time % 1000;
    }else if(time % 1000 < 100){
        ms = '0' + time % 1000;
    }else{
        ms = time % 1000;
    }
    var x0 = Y+'-'+ M +"-"+D+" "+h+":"+m+":"+s+"."+ms;
    var x1 = ""+h+m+s+ms;
    timeStamp[i].innerText = x0;
    timeCode[i].innerText = x1;
}
//点击加载更多进行ajax请求
var offset = 0, flag = true;
// if($('.records_terms').length < 200){
//     $('.js_no').css("display","block");
// }else{
    var num = 0;
    $('.tip_wrap').on('click','.cli_more', function(){
        if (flag) {
            flag = false;
            offset++;
            $.ajax({
                type: "post",
                dataType: "json",
                url: "/candysnatch/snatchJoinOrders",
                data: {
                    token: token,
                    periodId:periodId,
                    offset: offset
                },
                success: function (data) {
                    var len = data.data.periodOrders.length;
                    var html = "";
                    for (var i = 0; i < len; i++) {
                        html+="<div class='r_terms'>";
                        html+="<div class='r_time r_times'>"+data.data.periodOrders[i]["create_time"]+"</div>";
                        html+="<div class='r_desc'>"
                        if(data.data.periodOrders[i]["userInfo"].sex == 1){
                            html+="<span class='r_name'><span class='r_sex'>" + data.data.periodOrders[i]["userInfo"].real_name+ "先生</span><span class='time_code_inn'>" + data.data.periodOrders[i]["time"] + "</span></span>";
                        } else {
                            html+="<span class='r_name'><span class='r_sex'>" + data.data.periodOrders[i]["userInfo"].real_name+ "女士</span><span class='time_code_inn'>" + data.data.periodOrders[i]["time"] + "</span></span>";
                        }
                        html+="<span class='r_num'>投入了"+data.data.periodOrders[i]["code_count"]+"个信宝</span></div></div>";
                    }
                    $(html).appendTo(".records_wrap");
                    // 一次返回30条信息
                    if (len < 30) {
                        $('.js_more').css("display","none");
                        $('.js_no').css("display","block");
                    }
                    flag = true;
                },
                error: function(){
                    WXP2P.UI.showErrorTip("网络繁忙，请稍后重试！");
                }
            })
        }
    })
// }

