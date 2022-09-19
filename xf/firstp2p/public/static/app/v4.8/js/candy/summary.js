$(function(){
    $('#signInBtn').on('click',function () {
        var infor=$(this).data('infor');
        if (infor.checkInStatus==0){
            if (infor.userHasLoan==0){
                P2PWAP.ui.toast('投资1次即可签到获信力！');
            }else{
                zhuge.track('信宝签到');
                P2PWAP.app.triggerScheme('firstp2p://api?type=native&name=other&pageno=28');
            }
        }
    });
  // 信宝中心
  // 抽奖弹窗
  var token = $(".JS_token").val();
  $('#JS_price').click(function(){
    // WXP2P.UI.pullUpRefresh("a");
    zhuge.track('信宝抽奖');
    var _Data = {
        token:$(".JS_token").val()
    }
    $.ajax({
        url:"/candy/Lottery",
        type:"post",
        dataType:"json",
        data:_Data,
        success:function(json){
            if(json.errno == 0){
              $('.mask_price').show();
              $('.mask_price .num').html(json.data.activity)
              zhuge.track('抽奖成功',{
                  '获取信力值':json.data.activity
              });
            } else{
              zhuge.track('抽奖失败',{
                  '抽奖失败原因':json.error,
              });
              P2PWAP.ui.toast(json.error)
            }
        }
    })
  })
  function getAdvConf(advName){
    $.ajax({
        url:"/common/GetApiConf",
        type:"post",
        dataType:"json",
        data:{
            token:token,
            module:"api_adv_conf"
        },
        success:function(json){
            var bannerAll = json.data.all
            var bannerImg = [];
            var html = ""
            for(var i = 0; i<bannerAll.length;i++){
                if(bannerAll[i].name == advName){
                    bannerImg = bannerAll[i].value;
                }
            }
            for(var k=0; k<bannerImg.length;k++){
                if(bannerImg[k].url != undefined){
                    html += '<li class="world_cup_home_top swiper-slide"><a data-href="' + bannerImg[k].url + '" class="JS_go_game" href="'+ bannerImg[k].url +'"><img src="'+ bannerImg[k].imageUrl +'" width="100%" height="100%"></a></li>';
                } else {
                    html += '<li class="world_cup_home_top swiper-slide"><img src="'+ bannerImg[k].imageUrl +'" width="100%" height="100%"></li>';
                }
                
            }
            $(".peak_night_banner").html(html);
            if($(".world_cup_home_top").length > 1){
                var mySwiper = new Swiper ('.game_banner', {
                    direction: 'horizontal',
                    loop: true,
                    // 如果需要分页器
                    pagination: {
                    el: '.swiper-pagination',
                    },
                    autoplay:{
                    delay:3000,
                    stopOnLastSlide: false,/*如果设置为true，当切换到最后一个slide时停止自动切换.（loop模式下无效）。*/
                    disableOnInteraction: false,/*用户操作swiper之后，是否禁止autoplay。默认为true：停止。*/
                    }
                })
            }
            $(".JS_go_game").each(function(){
                var link = $(this).data("href");
                if(link.indexOf("?")>0){
                    $(this).attr("href", "firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(link + "&token=" + token));
                } else {
                    $(this).attr("href", "firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(link + "?token=" + token));
                }
            })
        }
     })
  }
  getAdvConf("candy_carousel");
  var agreementCheck = $(".agreementCheck").val()
  $(".JS_to_mine").attr("href","firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(location.origin + "/candy/mine?token=" + token))
  $(".JS_go_shop").attr("href","firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(location.origin + "/candy/shop?token=" + token))
  $(".JS_to_bucex").attr("href","firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(location.origin + "/candy/buc_exchange?token=" + token))
  $(".JS_to_creex").attr("href","firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(location.origin + "/candy/CreConvert?token=" + token)).on("click",function(){
    zhuge.track('信宝中心cre入口')
  })
  $(".JS_task_tips").attr("href","firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(location.origin + "/candy/task_intro?token=" + token))
  if(agreementCheck){
    $(".JS_go_life").attr("href","storemanager://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent($(".shopUrl").val() + "&token=" + token));
  } else {
    $(".JS_go_life").attr("href","firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(location.origin + "/candy/shop?token=" + token));
  }
  $('.JS_goto_game').attr("href","firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent($(".gameUrl").val() + "token=" + token))
  $(".JS_to_product").attr("href", function () {
    if(agreementCheck){
        return "firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(location.origin+ "/candy/product_detail?token=" + token + "&productId=" + $(this).data('id'));
    } else {
        return "firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(location.origin+ "/candy/shop?token=" + token);
    }
  })
  $('.entityList').attr('href',function () {
      return $(this).attr('href')+encodeURIComponent($(this).data('url') + "&token=" + $(".JS_token").val());
  })
  $(".JS_yue_tips").click(function(){
    P2PWAP.ui.toast("网贷-网信普惠和网信账户中任一账户余额满4000元即可每2小时结算1信力（不含红包）")
  })
  $('.mall').click(function (event) {
      if(agreementCheck){
        window.location.replace("storemanager://api?type=webview&gobackrefresh=true&url="+encodeURIComponent($('.shopUrl').val() + "&token=" + $('.JS_token').val()))
      }else{
        $('.JS_pop').show();
        $(document.body).css("position","fixed");
      }
  });
  $('.candy_snatch').attr("href","firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(location.origin+"/candysnatch/SnatchAuction?clearCookie=1&token="+token));
  $('.gameAll').attr("href","firstp2p://api?type=webview&gobackrefresh=true&url=" + encodeURIComponent(location.origin+"/candy/game?token="+token));
  /*埋点*/
  $('.entrance_wrap').click(function(ele){
    zhuge.track('点击信宝中心快捷入口',{
        　"快捷入口位置":ele.path[0].innerText
    });
  });
  $('.JS_xlrw').click(function(e){
    zhuge.track('信宝_点击信力任务',{
        　"位置":e.path[0].innerText
    });
  })
  $('.JS_xlrwsm').click(function(){
    zhuge.track('信宝_点击信力任务',{
        　"位置":"信力任务说明"
    });
  })
  $('.JS_banner').on('click','li',function(e){
      zhuge.track("信宝_点击信宝banner情况",{
          "位置":"banner"+(+e.currentTarget.getAttribute("data-swiper-slide-index")+1)
      })
  })
  $('.JS_xnsp').click(function(){
      zhuge.track("信宝_虚拟商品_点击更多商品");
  })
  $('.JS_to_product').click(function(e){
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
      zhuge.track("信宝_虚拟商品_点击区域",{
        "虚拟商品名称":a[x-1],
        "所需信宝值":xb
      })
  })
  $(".JS_jxsp").click(function(){
    zhuge.track("信宝_精选商品_点击更多商品");
  })
  $(".JS_jxshqy").click(function(){
      zhuge.track("信宝_精选商品_点击区域")
  })
  $(".JS_close").click(function(){
    $('.JS_pop').css("display","none");
    $(document.body).css("position","static");
  })
  var flag = true;
  $(".JS_authorization").unbind().click(function(){
    if(flag){
        flag = false;
        $.ajax({
            url:"/agreement/agree",
            type:"post",
            dataType:"json",
            data:{
                type:"candy",
                token:token
            },
            success:function(json){
                flag = true;
                $(document.body).css("position","static");
                if(json.errno == 0){
                    window.location.replace("storemanager://api?type=webview&gobackrefresh=true&url="+encodeURIComponent($('.shopUrl').val() + "&token=" + $('.JS_token').val()))
                } else {
                    WXP2P.UI.toast(json.error)
                }
            },
            error:function(){
                WXP2P.UI.toast("网络错误，请重试！")
            }
        })
    }
})
})
