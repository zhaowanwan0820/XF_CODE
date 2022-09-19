(function($){
  $(function() {
    var emailRegEx = /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/
      , mobileRegEx = /^1[3456789]\d{9}$/;
    var errorSpan = $('#error-row td')
    $('#register-form').submit(function(ev) {
      var hasError = false;
      $('#register-form input.input-text').each(function() {
        if (!hasError && $(this).val() == '') {
          errorSpan.html($(this).attr('data-label') + '不能为空');
          hasError = true;
        }
      });
      if (!hasError) {
        if (!emailRegEx.test($('#input-email').val())) {
          errorSpan.html('邮箱格式不正确');
          hasError = true;
        } else if (!mobileRegEx.test($('#input-mobile').val())) {
          errorSpan.html('手机号格式不正确');
          hasError = true;
        } else if ($('#input-password').val() != $('#input-retype').val()) {
          errorSpan.html('两次输入的密码不一致');
          hasError = true;
        } else if (!$('#form-agreement-row input').is(':checked')) {
          errorSpan.html('不同意注册协议无法完成注册');
          hasError = true;
        }
      }
      if (hasError) {
        ev.preventDefault();
        return false;
      }
      return true;
    });

    $('#action-send-code').click(function(ev) {
      var phone = $("#input-mobile") .val();
      var mobileRegEx = /^1[3456789]\d{9}$/;
      var errorSpan = $('#error-row td');
      var button = $(this);

      errorSpan.html('');
      if (!mobileRegEx.test(phone))
      {
        errorSpan.html('手机号格式不正确');
        return false;
      }

      button.attr('disabled', 'disabled');
      function updateTimeLabel(duration) {
        var timeRemained = duration;
        var timer = setInterval(function() {
          button.val(timeRemained + '秒后重新发送').attr('disabled', 'disabled');
          timeRemained -= 1;
          if (timeRemained == -1) {
            clearInterval(timer);
            button.val('获取手机验证码').removeAttr('disabled');
          }
        }, 1000);
      }

      $.ajax({
        type: "get",
        url: 'first_register.php?action=sendCode&ajax=1&phone=' + phone,
        async: false,
        success: function(data) {
          if (data == 1) {
            updateTimeLabel(180, 'action-send-code');
            return;
          } else if (data == 2) {
            alert("验证短信发送失败请重新发送");
          } else if (data == 3) {
            alert("手机号格式有误");
          } else if (data == 4) {
            alert("手机已被使用，可直接用金融工场账号登录");
          } else if (data == 5) {
            alert("您获取验证码过于频繁，请稍后再进行获取");
          } else if (data == 6) {
            alert("一天最多可以获取10次验证码");
          }
          button.removeAttr('disabled');
        }
      });
    });
  })
})(jQuery);

function startMove(obj, json, fn) {
  clearInterval(obj.iTimer);
  var iCur = 0;
  var iSpeed = 0;
  obj.iTimer = setInterval(function() {
    var iBtn = true;
    for (var attr in json) {
      if (attr == 'opacity') {
        iCur = Math.round(css(obj, 'opacity') * 100);
      } else {
        iCur = parseInt(css(obj, attr));
      }
      iSpeed = (json[attr] - iCur) / 8;
      iSpeed = iSpeed > 0 ? Math.ceil(iSpeed) : Math.floor(iSpeed);
      if (iCur != json[attr]) {
        iBtn = false;
        if (attr == 'opacity') {
          obj.style.opacity = (iCur + iSpeed) / 100;
          obj.style.filter = 'alpha(opacity='+(iCur + iSpeed)+')';
        } else {
          obj.style[attr] = iCur + iSpeed + 'px';
        }
      }
    }
    if (iBtn) {
      clearInterval(obj.iTimer);
      fn && fn.call(obj);
    }
  }, 15);
}
function css(obj, attr) {
  if (obj.currentStyle) {
    return obj.currentStyle[attr];
  } else {
    return getComputedStyle(obj, false)[attr];
  }
}
/** 以上为运动库 **/
var oTb=document.getElementById("tb_img");
var aImg=oTb.getElementsByTagName("img");
var aA=oTb.getElementsByTagName("a");
var bStop=true;
var arrImg=[];
var zIndex = 1;
var zIndex2 = 5;
var iNum=0;
for(var i=0;i<aImg.length;i++){
  arrImg[i]={left:aImg[i].offsetLeft,top:aImg[i].offsetTop}
}
for(var i=0;i<aImg.length;i++){
  aImg[i].index=i;
  aImg[i].style.position="absolute";
  aImg[i].style.left=arrImg[i].left+"px";
  aImg[i].style.top=arrImg[i].top+"px";
}
for(var i=0;i<aImg.length;i++){
  aImg[i].onclick=function(){
    iNum=this.index;
    if(bStop){
      bStop=false;
      this.style.zIndex = zIndex++;
      this.style.background ="#ddd";
      this.style.padding ="5px";
      startMove(this, {
        width   : 354,
        height    : 277,
        left    : -arrImg[iNum].left,
        top     : -arrImg[iNum].top
      },function(){
        aA[iNum].style.display="inline";
        aA[iNum].style.zIndex=zIndex2++;
        aA[iNum].style.top=-6+"px";
        aA[iNum].style.left=353+"px";
      });
    }else{
      aA[iNum].style.display="none";
      aImg[iNum].style.background ="";
      aImg[iNum].style.paddingTop ="20px";
      aImg[iNum].style.paddingLeft ="10px";
      startMove(aImg[iNum], {
        width   : 70,
        height    : 45,
        left    : arrImg[iNum].left,
        top     : arrImg[iNum].top
      },function(){
        bStop=true;
      });

    }
  }
}
for(var i=0;i<aA.length;i++){
  aA[i].index=i;
  aA[i].onclick=function(){
    iNum=this.index;
    this.style.display="none";
    aImg[iNum].style.background ="";
    aImg[iNum].style.padding ="10px 0 0 10px";
    startMove(aImg[iNum], {
      width   : 70,
      height    : 45,
      left    : arrImg[iNum].left,
      top     : arrImg[iNum].top
    },function(){
      bStop=true;
    });
  }
}