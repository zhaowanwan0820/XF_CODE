//时间格式标准化
function timeSta(num) {
  var time = new Date(num);
  return time.getFullYear() + "." + ((time.getMonth() + 1 > 9) ? (time.getMonth() + 1) : '0' + (time.getMonth() + 1)) + "." + (time.getDate() > 9 ? time.getDate() : '0' + time.getDate()) + "  " + (time.getHours() > 9 ? time.getHours() : '0' + time.getHours()) + ":" + (time.getMinutes() > 9 ? time.getMinutes() : '0' + time.getMinutes()) + ":" + (time.getSeconds() > 9 ? time.getSeconds() : '0' + time.getSeconds());
};

var offset = 0,
  flag = true;
var more_dom = document.createElement('div'),
  no_more = document.createElement('div');
no_more.innerHTML = "<div class='no_more_wrap'><span class='no_more'>仅显示最近30天记录</span></div>";
more_dom.innerHTML = "<div class='cli_more_wrap'><span class='cli_more'>点击加载更多</span></div>";
//一进入我的夺宝页面加载往期记录中的最多30条记录
$.ajax({
  type: "post",
  dataType: "json",
  url: "/candysnatch/SnatchUserProductsPaging",
  data: {
    offset: offset,
    token: $('.token').val()
  },
  success: function (json) {
    if (!!json.data) {
      var len = json.data[0].length;
      var data = json.data[0];
      var html = "";
      if (len == 0) {
        $('.product_list .my_prize_null').css('display', 'block');
      } else if (len == 30) {
        for (var i = 0; i < len; i++) {
          html += "<a class='snatch_jump_productDetail' href='storemanager://api?type=webview&gobackrefresh=true&url="
          html += encodeURIComponent(location.origin + "/candysnatch/SnatchProduct/?token=" + $('.token').val() + "&periodId=" + data[i].id)
          html += "'><li><div class='product_left'>"
          html += (currentUserId == (data[i].userInfo ? data[i].userInfo.id : "")) ? "<span class='product_status'>成功获奖</span>" : (data[i].status == 1) ? "<span class='product_status product_status_notkonw'>尚未揭晓</span>" : "<span class='product_status product_status_over'>未获奖</span>"
          html += "<img src=" + data[i].image_main
          html += " class='product_img'>"
          if(data[i].productInfo.type == 2){
            html+="<img class='wddb_inviter_icon' src='https://event.ncfwx.com/upload/image/20190102/14-59-only_inviter_icon.png' alt='only_inviter_icon'>"
          }
          html+="</div><div class='product_right'><p class='product_detail'><span class='product_name'>" + data[i].productInfo.title + "</span></p>"
          html += (data[i].status == 1) ? "<p class='prize_info'>我的夺宝码：" + data[i].myCode[0] + "~" + data[i].myCode[data[i].myCode.length-1] + "</p><p class='prize_time'>点击查看更多</p><p class='product_issue'><span>第"+data[i].id+"期</span></p><button class='start_snatch'>继续夺宝</button>" : "<p class='prize_info'>恭喜" + (data[i].userInfo ? data[i].userInfo.real_name : "") + (((data[i].userInfo ? data[i].userInfo.sex : "") == 1) ? "先生" : "女士") + " (" + (data[i].userInfo ? data[i].userInfo.mobile : "") + ") 获奖</p><p class='prize_time'>开奖时间：" + timeSta(data[i].prize_time * 1000) + "</p><p class='product_issue'><span>第" + data[i].id + "期</span></p><p class='prize_code'>幸运号码:<span>" + data[i].prize_code + "</span></p>"
          html += "</div></li></a>"
        }
        $('.product_list').html(html);
        $('.product_list').append(more_dom);
      } else {
        for (var i = 0; i < len; i++) {
          html += "<a class='snatch_jump_productDetail' href='storemanager://api?type=webview&gobackrefresh=true&url="
          html += encodeURIComponent(location.origin + "/candysnatch/SnatchProduct/?token=" + $('.token').val() + "&periodId=" + data[i].id)
          html += "'><li><div class='product_left'>"
          html += (currentUserId == (data[i].userInfo ? data[i].userInfo.id : "")) ? "<span class='product_status'>成功获奖</span>" : (data[i].status == 1) ? "<span class='product_status product_status_notkonw'>尚未揭晓</span>" : "<span class='product_status product_status_over'>未获奖</span>"
          html += "<img src=" + data[i].image_main
          html += " class='product_img'>"
          if(data[i].productInfo.type == 2){
            html+="<img class='wddb_inviter_icon' src='https://event.ncfwx.com/upload/image/20190102/14-59-only_inviter_icon.png' alt='only_inviter_icon'>"
          }
          html+="</div><div class='product_right'><p class='product_detail'><span class='product_name'>" + data[i].productInfo.title + "</span></p>"
          html += (data[i].status == 1) ? "<p class='prize_info'>我的夺宝码：" + data[i].myCode[0] + "~" + data[i].myCode[data[i].myCode.length-1] + "</p><p class='prize_time'>点击查看更多</p><p class='product_issue'><span>第"+data[i].id+"期</span></p><button class='start_snatch'>继续夺宝</button>" : "<p class='prize_info'>恭喜" + (data[i].userInfo ? data[i].userInfo.real_name : "") + (((data[i].userInfo ? data[i].userInfo.sex : "") == 1) ? "先生" : "女士") + " (" + (data[i].userInfo ? data[i].userInfo.mobile : "") + ") 获奖</p><p class='prize_time'>开奖时间：" + timeSta(data[i].prize_time * 1000) + "</p><p class='product_issue'><span>第" + data[i].id + "期</span></p><p class='prize_code'>幸运号码:<span>" + data[i].prize_code + "</span></p>"
          html += "</div></li></a>"
        }
        $('.product_list').html(html);
        $('.product_list').append(no_more);
      }
    }
  },
  error: function () {
    WXP2P.UI.showErrorTip("没网络了，请稍后重试！");
    console.log("没网络了，请稍后重试！");
  }
});

//点击显示更多重新请求接口
$('.product_list').on('click', '.cli_more', function () {
  if (flag) {
    flag = false;
    offset++;
    $.ajax({
      type: "post",
      dataType: "json",
      url: "/candysnatch/SnatchUserProductsPaging",
      data: {
        offset: offset,
        token: $('.token').val()
      },
      success: function (json) {
        if (!!json.data) {
          var html = "";
          var len = json.data[0].length;
          var data = json.data[0];
          if (len == 30) {
            for (var i = 0; i < len; i++) {
              html += "<a class='snatch_jump_productDetail' href='storemanager://api?type=webview&gobackrefresh=true&url="
              html += encodeURIComponent(location.origin + "/candysnatch/SnatchProduct/?token=" + $('.token').val() + "&periodId=" + data[i].id)
              html += "'><li><div class='product_left'>"
              html += (currentUserId == (data[i].userInfo ? data[i].userInfo.id : "")) ? "<span class='product_status'>成功获奖</span>" : (data[i].status == 1) ? "<span class='product_status product_status_notkonw'>尚未揭晓</span>" : "<span class='product_status product_status_over'>未获奖</span>"
              html += "<img src=" + data[i].image_main
              html += " class='product_img'>"
              if(data[i].productInfo.type == 2){
                html+="<img class='wddb_inviter_icon' src='https://event.ncfwx.com/upload/image/20190102/14-59-only_inviter_icon.png' alt='only_inviter_icon'>"
              }
              html+="</div><div class='product_right'><p class='product_detail'><span class='product_name'>" + data[i].productInfo.title + "</span></p>"
              html += (data[i].status == 1) ? "<p class='prize_info'>我的夺宝码：" + data[i].myCode[0] + "~" + data[i].myCode[data[i].myCode.length-1] + "</p><p class='prize_time'>点击查看更多</p><p class='product_issue'><span>第"+data[i].id+"期</span></p><button class='start_snatch'>继续夺宝</button>" : "<p class='prize_info'>恭喜" + (data[i].userInfo ? data[i].userInfo.real_name : "") + (((data[i].userInfo ? data[i].userInfo.sex : "") == 1) ? "先生" : "女士") + " (" + (data[i].userInfo ? data[i].userInfo.mobile : "") + ") 获奖</p><p class='prize_time'>开奖时间：" + timeSta(data[i].prize_time * 1000) + "</p><p class='product_issue'><span>第" + data[i].id + "期</span></p><p class='prize_code'>幸运号码:<span>" + data[i].prize_code + "</span></p>"
              html += "</div></li></a>"
            }
            var div_dom = document.createElement('div');
            div_dom.innerHTML = html;
            $(div_dom).insertBefore(more_dom);
            // $(more_dom).replaceWith(no_more);
          } else {
            for (var i = 0; i < len; i++) {
              html += "<a class='snatch_jump_productDetail' href='storemanager://api?type=webview&gobackrefresh=true&url="
              html += encodeURIComponent(location.origin + "/candysnatch/SnatchProduct/?token=" + $('.token').val() + "&periodId=" + data[i].id)
              html += "'><li><div class='product_left'>"
              html += (currentUserId == (data[i].userInfo ? data[i].userInfo.id : "")) ? "<span class='product_status'>成功获奖</span>" : (data[i].status == 1) ? "<span class='product_status product_status_notkonw'>尚未揭晓</span>" : "<span class='product_status product_status_over'>未获奖</span>"
              html += "<img src=" + data[i].image_main
              html += " class='product_img'>"
              if(data[i].productInfo.type == 2){
                html+="<img class='wddb_inviter_icon' src='https://event.ncfwx.com/upload/image/20190102/14-59-only_inviter_icon.png' alt='only_inviter_icon'>"
              }
              html+="</div><div class='product_right'><p class='product_detail'><span class='product_name'>" + data[i].productInfo.title + "</span></p>"
              html += (data[i].status == 1) ? "<p class='prize_info'>我的夺宝码：" + data[i].myCode[0] + "~" + data[i].myCode[data[i].myCode.length-1] + "</p><p class='prize_time'>点击查看更多</p><p class='product_issue'><span>第"+data[i].id+"期</span></p><button class='start_snatch'>继续夺宝</button>" : "<p class='prize_info'>恭喜" + (data[i].userInfo ? data[i].userInfo.real_name : "") + (((data[i].userInfo ? data[i].userInfo.sex : "") == 1) ? "先生" : "女士") + " (" + (data[i].userInfo ? data[i].userInfo.mobile : "") + ") 获奖</p><p class='prize_time'>开奖时间：" + timeSta(data[i].prize_time * 1000) + "</p><p class='product_issue'><span>第" + data[i].id + "期</span></p><p class='prize_code'>幸运号码:<span>" + data[i].prize_code + "</span></p>"
              html += "</div></li></a>"
            }
            var div_dom = document.createElement('div');
            div_dom.innerHTML = html;
            $(more_dom).replaceWith(no_more);
            $(div_dom).insertBefore(no_more);
          }
          flag = true;
        }
      }
    })
  }
})