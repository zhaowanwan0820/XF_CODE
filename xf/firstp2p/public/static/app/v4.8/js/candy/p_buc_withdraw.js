$(function () {
  P2PWAP.app.triggerScheme("firstp2p://api?type=rightbtn&title=");
  $('.JS_liji_draw').on('click', function () {
    $('#JS_ui_pop_ex').show();
  });

  $('.JS_close_pop').on('click', function () {
    $('#JS_ui_pop_ex').hide();
  });
  $('#JS_draw_add').on('input',function(){
    if($('#JS_draw_add').val() != ''){
      $('.btn_h40_red').css('opacity','1').addClass('JS_confirm_draw');
    } else {
      $('.btn_h40_red').css('opacity','0.2').removeClass('JS_confirm_draw');
    }
  })
  $('.p_extract').on("click",".JS_confirm_draw",function () {

    showSendCode();
  })

  var currentSeconds = 60;
  var countDownSeconds = 60;
  var interval_id;
  var codeIpt = $(".code_input");
  var token = $("#token").val();
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
        token: token,
        type: 21,
        phone: $(".JS_mobile_num").html()
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
          token: token,
          type: 2,
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
            var data = {
              token: $("#token").val(),
              address: $('#JS_draw_add').val(),
              bucAmount: $('#buc_amount').val(),
              code:codeIpt.val()
            }
            $.ajax({
              url: "/candy/BucDoWithdraw",
              type: "post",
              dataType: "json",
              data: data,
              success: function (json) {
                if (json.errno == 0) {
                  WXP2P.UI.toast('提取成功');
                  zhuge.track('提取成功');
                  setTimeout(function(){
                      location.href = "/candy/mine?token=" + $("#token").val();
                  },2000);
                }  else if (json.errno == 201005) { 
                  $(".error_pop_mask").show();
                  $(".error_pop .con").html("提取BUC您需要在bitUN的APP内完成<br/>实名认证：<br/>进入bitUN的APP > 我的 > 安全认证 > 实名认证");
                } else if (json.errno == 201006) {
                  $(".error_pop_mask").show();
                  $(".error_pop .con").html("请使用正确的提取地址<br/>提取BUC必须提取到自己的账户下");
                } else {
                  WXP2P.UI.toast(json.error)
                  zhuge.track('提取失败', {
                    '提取失败原因': json.error
                  });
                }
              },
              error: function(){
                WXP2P.UI.toast('网络连接错误，请检查网络');
              }
            })
            closeMobileCode();
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
    $(".p_extract .input_error").hide();
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

  function closeMobileCode(){
    $(".mobile_code_box_mask").hide();
    clearInterval(interval_id);
    $(".countdown").show();
    $(".JS_again_send,.error_tips,.input_error").hide();
    codeIpt.val("");
    $(".input_focus").html("");
  }

  $(".mobile_code_close").click(function () {
    closeMobileCode();
  })

  $(".error_pop .close_btn").click(function(){
    $(".error_pop_mask").hide();
  })

});
