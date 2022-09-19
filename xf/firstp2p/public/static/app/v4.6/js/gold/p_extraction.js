$(function () {
  //手机号格式化 344
  function phoneSeparated(phoneNumber) {
    var tel;
    tel = phoneNumber.substring(0, 3) + ' ' + phoneNumber.substring(3, 7) + ' ' + phoneNumber.substring(7, 11);
    return tel;
  }
  var mobile = $(".JS_mobile_num").html()
  $(".JS_mobile_num").html(phoneSeparated(mobile));

  // tofixed
  Number.prototype.toFixed = function (len) {
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
  };
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

  var add, reduce, gold_num, num_txt;
  add = $(".J_jia");//添加数量btn
  reduce = $(".J_jian");//减少数量btn
  gold_num = "";
  num_txt = $(".gold_num");

  var totalNum = function (indexN) {
    var item_weight = $(".weight").eq(indexN).html();
    item_weight = (gold_num * item_weight);
    $(".item_total").eq(indexN).val(item_weight)
    var total_num = 0,total_fee = 0;
    for (var i = 0; i < $(".item_total").length; i++) {
      total_num += ($(".item_total").eq(i).val()) * 1;
      total_fee +=  (num_txt.eq(i).data("fee") * $(".item_total").eq(i).val()) * 1
    }
    $(".total_num").html(total_num.toFixed(3));
    $(".JS_fee").html(total_fee.toFixed(2));
    if (total_num > 0) {
      $(".JS_comfirm_btn").removeAttr("disabled");
    } else {
      $(".JS_comfirm_btn").attr("disabled", "disabled");
    }
  }
  
  //回填
  num_txt.each(function(i){
    if($(this).val()>0){
      $(this).parents(".num_box").find(".label, .J_jian").show();
      $(this).parents(".num_box").find(".item_total").val($(this).val());
    }
    gold_num = $(this).val();
    totalNum(i);
  })
  //输入金条数量
  num_txt.on("input",function(){
    var changeIndex = num_txt.index(this);
    gold_num = $(".num").eq(changeIndex).val();
    totalNum(changeIndex);
  })

  num_txt.on("blur",function(){
    var $this = $(this).val();
    if($this <= 0 || $this == ""){
      $(this).parents(".num_box").find(".J_jian,.label").hide();
    }
  })

  /*添加数量的方法*/
  add.click(function () {
    var addIndex = add.index(this);
    var item_weight = $(".weight").eq(addIndex).html();
    gold_num = $(".num").eq(addIndex).val();
    gold_num++;
    if(gold_num > 999) return;
    num_txt.eq(addIndex).val(gold_num);
    totalNum(addIndex);
    if (gold_num > 0) {
      $(".J_jian").eq(addIndex).show();
      $(".label").eq(addIndex).show();
    }
  });

  /*减少数量的方法*/
  reduce.click(function () {
    var reduceIndex = reduce.index(this);
    gold_num = $(".num").eq(reduceIndex).val();
    if (gold_num > 0 || gold_num == "") {
      gold_num--;
      num_txt.eq(reduceIndex).val(gold_num);
      totalNum(reduceIndex);
    }
    if (gold_num == 0) {
      $(".J_jian").eq(reduceIndex).hide();
      $(".label").eq(reduceIndex).hide();
    }
  });

  var currentSeconds = 60;
  var countDownSeconds = 60;
  var interval_id;
  var codeIpt = $(".code_input")
  //canvas倒计时
  var drawDoubleCircle = function () {
    if (currentSeconds <= 0) {
      clearInterval(interval_id);
      $(".JS_again_send").show();
      $(".countdown").hide();
      currentSeconds = 60;
      countDownSeconds = 60;
    }
    var canvasElement = document.getElementById('canvas');
    var context = canvasElement.getContext('2d');
    progress = 360 * currentSeconds / countDownSeconds;
    progress_pi = Math.PI * (progress / 180 - 1 / 2);

    context.beginPath();
    context.moveTo(20, 20);
    context.arc(40, 40, 40, 0, Math.PI * 2, false);
    context.closePath();
    context.fillStyle = '#f2f2f2';
    context.fill();

    context.beginPath();
    context.moveTo(20, 20);
    context.arc(40, 40, 40, -Math.PI * 1 / 2, progress_pi, false);
    context.closePath();
    context.fillStyle = '#f16151'
    context.lineCap = 'round';
    context.fill();

    context.beginPath();
    context.arc(40, 40, 36, 0, Math.PI * 2, false);
    context.closePath();
    context.fillStyle = 'white';
    context.fill();

    context.font = "30px Arial";
    context.fillStyle = "#f16151";
    context.textAlign = "center";
    context.textBaseline = 'middle';
    context.fillText(currentSeconds, 40, 40);
    // 抗锯齿
    context.globalCompositeOperation = 'source-atop';
    currentSeconds--;
  }

  //发送验证码
  $(".JS_comfirm_btn").click(function () {
    confirm();
  })

  //提交提金
  function confirm() {
    var goodsArr = [];
    var goodsDetails = ""
    num_txt.each(function (k, v) {
      var val = $(this).val();
      if (val <= 0) return;
      var jsonObj = "";
      jsonObj += '"' + $(this).attr('data-id') + '":"' + val + '"'
      goodsArr.push(jsonObj);
      goodsDetails = encodeURIComponent("{" + goodsArr.join() + "}");
      localStorage.setItem("goodsDetails", goodsDetails)
    });

    $.ajax({
      type: "post",
      dataType: "json",
      url: '/gold/PreDeliver',
      data: {
        token: $("#usertoken").val(),
        goodsAmount: $(".total_num").html(),
        goodsDetails: goodsDetails
      },
      success: function (json) {
        //status==0 余额不足，需要充值
        //status==1 余额足够
        //status==3 去银行划转
        //status==5 划转提示 存管to网信
        if (json.data.status == 0) {
          $(".JS_balance_tips").show();
          return false;
        } else if (json.data.status == 1) {
          showSendCode()
        }else if(json.data.status == 7){
          $(".JS_is_open_p2p").show();
            //拼接开户url
            var _is_open_p2p_param = '{"srv":"register" , "return_url":"storemanager://api?type=closecgpages"}';//开户参数
            var _openp2pUlr = location.origin + "/payment/Transit?params=" + encodeURIComponent(_is_open_p2p_param);
            //开户url
            $(".JS_open_p2p_btn").attr({"href":'storemanager://api?type=webview&gobackrefresh=true&url='+encodeURIComponent(_openp2pUlr)});
        } else if (json.data.status == 3) {
          $(".JS_is_transfer").show();
          $(".JS_trans_money").html(json.data.data.transfer + "元");
          $(".remain_m").html(json.data.data.remain);
          //拼接划转url
          var _is_transfer_param = '{"srv":"transfer" , "amount":"' + json.data.data.transfer + '","return_url":"storemanager://api?type=closecgpages"}';
          //开户参数
          var _en_is_transfer_param = encodeURIComponent(_is_transfer_param);
          var _istransferUlr = location.origin + "/payment/Transit?params=" + _en_is_transfer_param;
          $(".JS_transfer_btn").attr({ "href": 'storemanager://api?type=webview&gobackrefresh=true&url=' + encodeURIComponent(_istransferUlr) });
        } else if (json.data.status == 4 || json.data.status == 5) {
          $(".JS_is_transfer_tips").show();
          $(".JS_is_transfer_tips .JS_trans_money").html(json.data.data.transfer + "元");
          $(".JS_is_transfer_tips .remain_m").html(json.data.data.remain);
          var transfer_type = "";
          if (json.data.status == 4) {
            transfer_type = 1;
          } else {
            transfer_type = 2;
          }
          $(".JS_is_transfer_tips .JS_transfer_btn").unbind("click");
          $(".JS_is_transfer_tips .JS_transfer_btn").bind("click", function () {
            $.ajax({
              url: "/payment/Transfer?money=" + json.data.data.transfer + "&type=" + transfer_type + "&dontTip=" + $(".no_tip_checkbox").val() + "&token=" + $('#usertoken').val(),
              type: 'post',
              dataType: 'json',
              beforeSend: function () {
                $(".JS_is_transfer_tips .JS_transfer_btn").attr("disabled", "disabled");
              },
              success: function (subjosn) {
                if (subjosn.errno == 0) {
                  WXP2P.UI.showErrorTip("余额划转成功");
                } else {
                  WXP2P.UI.showErrorTip(subjosn.error);
                }
                $(".JS_is_transfer_tips").hide();
                $(".JS_is_transfer_tips .JS_transfer_btn").removeAttr('disabled');
              },
              error: function () {
                WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
                $(".JS_is_transfer_tips .JS_transfer_btn").removeAttr('disabled');
              }
            })
          })
        } else {
          WXP2P.UI.showErrorTip(json.data.data);
        }
      },
      error: function(){
        WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
      }
    })
  }


  //重新发送
  $(".JS_again_send").click(function () {
    $(".countdown").show();
    $(this).hide();
    showSendCode();
  })

  //执行倒计时发送验证码
  function showSendCode() {
    currentSeconds = 60;
    countDownSeconds = 60;
    $.ajax({
      type: "post",
      dataType: "json",
      url: '/user/SendVerifyCode',
      data: {
        token: $("#usertoken").val(),
        type: 18
      },
      success: function (json) {
        if (json.errno != 0) {
          WXP2P.UI.showErrorTip(json.error);
          currentSeconds = 60;
          countDownSeconds = 60;
        } else {
          drawDoubleCircle();
          interval_id = setInterval(function () {
            drawDoubleCircle();
          }, 1000);
          $(".mobile_code_box_mask").show();
          codeIpt.focus();
        }
      },
      error: function(){
        WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
      }
    })
  }

  //请求检查验证码
  codeIpt.on("input", function () {
    //根据input值的长度改变背景
    console.log(codeIpt.val());
    codeIpt_len();
    if (codeIpt.val().length == 6) {
      $.ajax({
        type: "post",
        dataType: "json",
        url: '/user/CheckVerifyCode',
        data: {
          token: $("#usertoken").val(),
          type: 1,
          code: codeIpt.val()
        },
        success: function (json) {
          if (json.errno != 0) {
            $(".error_tips").html(json.error).show();
            $(".input_error").show();
            codeIpt.val("");
            $(".input_focus").html("");
            codeIpt.blur();
          } else {
            $.ajax({
              type: "post",
              dataType: "json",
              url: '/gold/Deliver',
              data: {
                token: $("#usertoken").val(),
                goodsAmount: $(".total_num").html(),
                goodsDetails: localStorage.getItem("goodsDetails"),
                ticket: $("#ticket").val(),
                code: codeIpt.val()
              },
              success: function (res) {
                $(".JS_deliver_btn").remove();
                if (res.errno != 0) {
                  WXP2P.UI.showErrorTip(res.error);
                } else {
                  $(".item_total").val(0);
                  localStorage.setItem("goodsDetails", "")
                  var goHref = 'firstp2p://api?type=webview&gobackrefresh=true&openinself=true&url=' + encodeURIComponent(res.data)
                  $("body").append('<a href="' + goHref + '" class="JS_deliver_btn"></a>');
                  $(".JS_deliver_btn").click();
                }
              },
              error: function(){
                WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
              }
            })
          }
        },
        error: function(){
          WXP2P.UI.showErrorTip("网络连接错误，请检查网络");
        }
      })
    }
  })


  codeIpt.focus(function () {
    var codeIpt_len = codeIpt.val().length
    if (codeIpt_len == 0) {
      $(".input_focus").show().width(25);
    }
  })

  codeIpt.click(function () {
    var codeIpt_len = codeIpt.val().length
    if (codeIpt_len == 0) {
      $(".input_focus").show().width(25);
    }
    $(".p_extraction .input_error").hide();
    $(".error_tips").hide();
    codeIpt.focus();
  })

  function codeIpt_len() {
    var codeIpt_len = codeIpt.val().length;
    $(".input_focus").html(codeIpt.val());
    switch (codeIpt_len) {
      case 0:
        $(".input_focus").width(25)
        break;
      case 1:
        $(".input_focus").width(25)
        break;
      case 2:
        $(".input_focus").width(65)
        break;
      case 3:
        $(".input_focus").width(105)
        break;
      case 4:
        $(".input_focus").width(145)
        break;
      case 5:
        $(".input_focus").width(185)
        break;
      case 6:
        $(".input_focus").width(225)
        break;
    }
  }

  //关闭弹窗
  $(".mobile_code_close").click(function () {
    $(".mobile_code_box_mask").hide();
    clearInterval(interval_id);
    $(".countdown").show();
    $(".JS_again_send,.error_tips,.input_error").hide();
    codeIpt.val("");
    $(".input_focus").html("");
  })

  //关闭划转
  $(".JS_close_transfer").click(function (event) {
    $(".JS_is_transfer").hide();
    $(".sub_btn").removeAttr("disabled");
  });

  $(".JS_close_transfer_tips").click(function () {
    $(".JS_is_transfer_tips").hide();
    $(".sub_btn").removeAttr("disabled");
  });
  $(".JS_close_open_p2p").click(function(){
    $(".JS_is_open_p2p").hide();
  })

  //阻止弹窗滚动
  $(".cunguan_bg ,.mobile_code_box_mask").bind("touchmove", function (event) {
    event.preventDefault();
  });

  //关闭充值弹窗
  $(".no_charge ,.yes_charge").click(function () {
    $(".JS_balance_tips").hide();
  })

   //不在提示划转弹窗
   $(".JS_is_transfer_tips .tips_icon").removeClass('JS_active');
   $(".no_tip_checkbox").val(0);
   $(".JS_is_transfer_tips").on("click",".tips_icon",function(event) {
       $(".tips_icon").toggleClass('JS_active');
       if($(".no_tip_checkbox").is(':checked')){
           $(".no_tip_checkbox").val(1);
       }else{
           $(".no_tip_checkbox").val(0);
       }
   });
   
  //监听resize事件获取andriod键盘弹出收起
  if(navigator.userAgent.indexOf("Android")>-1){
    var winHeight = $(window).height();
    $(window).resize(function(){
       var thisHeight=$(this).height();
        if(winHeight - thisHeight >50){
          $('.gold_total').css({'position':'static'});
        }else{
          $('.gold_total').css({'position':'fixed'});
        }
    });
  }

   //商品详情链接
  $(".good_url").each(function(){
    $(this).attr("href","firstp2p://api?type=webview&gobackrefresh=false&openinself=true&url=" + encodeURIComponent($(this).data("url")))
  })
});
