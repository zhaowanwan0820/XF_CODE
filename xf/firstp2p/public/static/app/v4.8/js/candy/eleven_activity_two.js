// 分享
P2PWAP.app.triggerScheme('firstp2p://api?type=rightbtn&title=' + encodeURIComponent("分享") + '&callback=gotoIntrction');
// setTimeout(function(){
//   P2PWAP.app.triggerScheme('firstp2p://api?type=leftbtn&jsfunc=goback')
// },200);
// function goback(){
//   P2PWAP.app.triggerScheme('firstp2p://api?type=native&name=home');
// }

var imgUrl = "http://backend.event.ncfwx.com/upload/image/20181109/19-09-WechatIMG11.jpeg";
var lineLink = 'http://event.ncfwx.com/zt/double_happy';
var descContent = "72H欢乐趴已经开始，更有锦鲤大奖心愿好礼等你来开！";
var shareTitle = "网信72H欢乐趴";

function gotoIntrction() {

  zhuge.track('分享网信72H欢乐趴');
  P2PWAP.app.triggerScheme('bonus://api?title='+ encodeURIComponent(shareTitle) +'&content='+ encodeURIComponent(descContent) +'&face=' + encodeURIComponent(imgUrl) + '&url='+ encodeURIComponent(lineLink));
  setTimeout(function(){
    P2PWAP.app.triggerScheme('firstp2p://api?type=rightbtn&title=' + encodeURIComponent("分享") + '&callback=gotoIntrction');
  },0);
}
$(function () {
  var token = $('#token').val()
  //前两趴活动结束
  function closeToast(btn_href){
    $.ajax({
      url:"/candyevent/Double11Status",
      type: 'POST',
      data:{
        token:token
      },
      dataType: 'json',
      success: function(res) {
        if(res.data.isGuideHide){
          WXP2P.UI.toast("活动已经结束")  
        }else {
          window.location.href = '/candyevent/' + btn_href +'?token=' + token
        }
      }
    })
  }
  $('.btn_1').click(function(){
    closeToast('double11h72')
  })
  $('.btn_2').click(function(){
    closeToast('double11')
  })

  var flag = $("#openPrize").val() ? $("#openPrize").val() : 1
  function openPrize(){
    $.ajax({
      url:"/candyevent/Double11Status",
      type: 'POST',
      data:{
        token:token
      },
      dataType: 'json',
      success: function(res) {
        if(res.data.isLotteryStart){
          clearInterval(openPrize);
          window.location.href = '/candyevent/wish_lottery?token=' + token
        }
      }
    })
  }
  if(flag == 1){
    setInterval(openPrize,5000)
  }
  
  var token = $('#token').val()
  var myDate = new Date();
  var myDay = myDate.getDate()
  // if(myDay >= 16){
  //   $('.btn_box').hide()
  //   $('.bottom_txt').css('padding','0 18% 9%')
  // }
  $('.help a').attr('href','firstp2p://api?type=webview&url=' + encodeURIComponent('http://event.ncfwx.com/zt/241') + '&gobackrefresh=true')
  $('.JS_fish').click(function(){
    $('.mask_content').show();
  })
  $('.JS_btn').click(function(){
    $('.mask_content').hide()
    $('.mask_no_surprise').hide()
    $('.mask_surprise').hide()
  })
  $.ajax({
    url:"/candyevent/Double11Status",
    type: 'POST',
    data:{
      token:token
    },
    dataType: 'json',
    success: function(res) {
      //判断进度条的亮暗
      if(res.data.step == 1){
        $(".JS_led_1").show()
      }else if(res.data.step == 2){
        $(".JS_led_2").show()
      }else if(res.data.step == 3) {
        $(".JS_led_3").show()
      }
      //底部导航（惊喜揭晓）显示判断
      if(res.data.isLotteryStart){
        $('.JS_btn_box_11').removeClass('btn_box_11').addClass('btn_box_11_')
        $('.JS_btn_box_72H').removeClass('btn_box_72H').addClass('btn_box_72H_')
        $('.btn_3 a').attr('href','/candyevent/wish_lottery?token=' + token)
      }
      if(res.data.isSnatchStart){
        $('.JS_duobao').click(function(){
          location.href = "firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(location.origin + '/candysnatch/SnatchAuction?token=' + token + "&clearCookie=1")
        })
      } else {
        $('.JS_duobao').click(function(){
          WXP2P.UI.toast("11月5日抢先开幕，敬请期待！")
        })                                                                                                                                                            
      }
      if(res.data.isSeckillStart){
        $('.JS_miaosha').click(function(){
          location.href = "storemanager://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(shopUrl + "&token=" + token)
        })
      } else {
        $('.JS_miaosha').click(function(){
          WXP2P.UI.toast("11月份投资过的用户(智多新除外)即可参加11月11日限时秒杀，敬请期待！")
        })
      }
    }
  })
// ----------列表页逻辑----------
  var index = 1;
  // 礼物索引
  if($('.test').attr('data-key') == "1"){
    $(".test[data-key='1']").addClass('active');
  }
  $('.test').click(function(){
    index = $(this).attr("data-key");
    $('.test').removeClass('active');
    $(this).addClass('active')
  })

  // 提交心愿
  $('.sub_wish').unbind().click(function(){
    if(myDay < 16){
      $(".mask_submit .txt").html("许愿礼物一旦选择不可更改哦~ ")
      $(".mask_submit .txt").css("text-align","center;")
      $('.JS_testbtn').removeClass('to_tou').addClass("JS_sub")
      $('.mask_submit').show()
      $('.JS_sub').unbind().click(function(){
        $.ajax({
          url:"/candyevent/WishMake",
          type: 'POST',
          dataType: 'json',
          data:{
            token: token, 
            productId: index
          },
          success: function(res) {
            if(res.errno == 1001){
              $(".mask_submit .txt").html("单笔投资金额≥1万元即可设置许愿好礼!更多精彩活动尽在双11主会场 ！赶紧行动吧")
              $(".mask_submit .txt").css("text-align","justify;")
              $('.JS_sub').hide();
              $('.JS_tou').show()
            }else {
              window.location.href = '/candyevent/wish_detail?token=' + token
            }
          },
        })
      })
    }else {
      $.ajax({
        url:"/candyevent/WishCheck",
        type: 'POST',
        dataType: 'json',
        data:{
          token: token, 
          productId: index
        },
        success: function(res) {
          if(res.errno == 1001){
            $(".mask_submit .txt").html("本次活动投资有效截止时间为11.15晚 24:00，欢迎下次参与！")
            $(".mask_submit .txt").css("text-align","justify;")
            $('.mask_submit').show()
            $('.JS_sub').click(function(){
              window.location.href = 'firstp2p://api?type=native&name=home'
            })
          }else {
            $(".mask_submit .txt").html("许愿礼物一旦选择不可更改哦~ ")
            $(".mask_submit .txt").css("text-align","center;")
            $('.JS_testbtn').removeClass('to_tou').addClass("JS_sub")
            $('.mask_submit').show()
            $('.JS_sub').unbind().click(function(){
              $.ajax({
                url:"/candyevent/WishMake",
                type: 'POST',
                dataType: 'json',
                data:{
                  token: token, 
                  productId: index
                },
                success: function(res) {
                  window.location.href = '/candyevent/wish_detail?token=' + token
                },
              })
            })
          }
        },
      })
    }
  })

   $('.JS_back').click(function(){
    $('.mask_submit').hide()
    $('.JS_tou').hide();
    $('.JS_sub').show();
   })
})