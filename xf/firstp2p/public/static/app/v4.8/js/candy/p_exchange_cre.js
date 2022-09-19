$(function(){
  zhuge.track('进入cre');
  var token = $(".JS_token").val();
  var totalLimitNum = $('#totalLimitNum').length ? Number($('#totalLimitNum').text().replace(/,/g,'')) : -1;
  var userLimitNum = $('#userLimitNum').length ? Number($('#userLimitNum').text().replace(/,/g,'')) : -1;
  var bucAmount = Number($("#bucAmount").text().replace(/,/g,''));
  var currentSeconds = 60;
  var countDownSeconds = 60;
  var interval_id;
  var codeIpt = $(".code_input");
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
        type: 21
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
              token: token,
              creAmount: $('.exchange_num').val(),
              code:codeIpt.val()
            }
            $.ajax({
                url: "/candy/CreDoConvert",
                type: "post",
                dataType: "json",
                data: data,
                beforeSend: function () {
                    $(".confirm_exchange").attr("disabled", "disabled")
                },
                success: function (json) {
                    if (json.errno == 0) {
                        var sucHtml = '';
                        sucHtml += '<div class="exchange_suc_pop">\
                          <div class="suc_icon"></div>\
                          <div class="tips_title">兑换并提取成功</div>\
                          <div class="tips">\
                            <p>CRE小助手：</p>\
                            <p>您当前CRE余额：'+ json.data.balance +'</p>\
                            <p>锁定余额：'+ json.data.freeze +'</p>\
                            <p>最近释放期：'+ json.data.release_time +'</p>\
                            <p>详情请登录CRE网站https://portal.coinreal.io查看</p>\
                          </div>\
                        </div>';
                        WXP2P.UI.popup(sucHtml,"",true,false,"我知道啦","",function(){
                          window.location.href = "/candy/summary?token=" + token;
                        })
                        zhuge.track('cre兑换成功')
                    } else {
                      var failHtml = '';
                        failHtml += '<div class="exchange_suc_pop exchange_fail_pop">\
                          <div class="fail_icon"></div>\
                          <div class="tips_title ">兑换并提取失败</div>\
                          <div class="error_msg">' + json.error + '</div>\
                          <div class="tips">\
                            <p>详情请登录CRE网站https://portal.coinreal.io查看</p>\
                          </div>\
                        </div>';
                      WXP2P.UI.popup(failHtml,"",true,false,"我知道啦","",function(){
                        window.location.href = "/candy/summary?token=" + token;
                      });
                      $(".confirm_exchange").removeAttr("disabled");
                      zhuge.track('cre兑换失败')
                    }
                },
                error: function () {
                    $(".confirm_exchange").removeAttr("disabled");
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
    $(".mobile_code_box_mask .input_error").hide();
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
  $('.confirm_exchange').click(function () {
      var valStr = $(".exchange_num").val();
      console.log(valStr);
      if (!valStr || valStr == 0) {
          P2PWAP.ui.toast("请输入正确的兑换数量")
          return;
      }
      valStr=Number(valStr);
      if(!limitVerify(valStr)){
          return;
      }
      WXP2P.UI.popup("本期兑换CRE锁定期为6个月<br>此后分18个月等额释放","温馨提示",true,true,"确认","取消",function(){
        showSendCode();
      })
  });

  /**
   * 额度限制
   */
  function limitVerify(valStr) {
      if(totalLimitNum>=0){
          if (valStr>totalLimitNum){
              P2PWAP.ui.toast("您申请兑换CRE数量超出当前库存");
              return false;
          }
      }
      if(valStr>bucAmount){
          P2PWAP.ui.toast("您的信宝额度不足");
          return false;
      }
      if(userLimitNum>=0){
          if (valStr>userLimitNum){
              P2PWAP.ui.toast("您申请兑换CRE数量超出今日用户可兑额度");
              return false;
          }
      }
      return true;
  }

  $('.allIn').click(function () {
      var amount = 0;
      if(userLimitNum < 0 && totalLimitNum < 0){
          amount=bucAmount;
      }else if(userLimitNum < 0 && totalLimitNum > 0){
          amount=Math.min(bucAmount,totalLimitNum);
      }else if (userLimitNum > 0 && totalLimitNum < 0) {
          amount=Math.min(bucAmount,userLimitNum);
      }else{
          amount=Math.min(bucAmount,userLimitNum,totalLimitNum);
      }
      $(".exchange_num").val(amount);
  });
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
})