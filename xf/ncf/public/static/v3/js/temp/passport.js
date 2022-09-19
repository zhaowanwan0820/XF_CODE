 function authorization(obj) {
     var promptStr2 = '<div class="f18 tc"><i class="icon-pop-suc"></i>提示信息</div><div class="mt28 mb13 tc"><span class="color-green mr5" id="second"></span></div>',
         promptStr = "<div class='wangxin-logo'></div><div class='pop-tit'><i></i></div>" +
         "<div class='tc'>"+obj.companyTitle +'申请获得以下权限：'+"</div>" +
         "<ul class='des'><li>获取您的账户基本信息（包括但不限于您的姓名、手机号）</li></ul>";

     Firstp2p.confirm({
         text: '<div class="f16 authorization_logo">' + promptStr + '</div>',
         title: "网信通行证授权",
         ok: function(dialog) {
             dialog.close();
             $.ajax({
                 url: '/passport/bind',
                 type: 'post',
                 data: "",
                 dataType: 'json',
                 success: function(result) {
                     if (result.errno== 0) {
                         window.location.href = '/account/index';
                     }else{
                         Firstp2p.alert({
                             text: result.error,
                             ok: function(dialog) {
                                 dialog.close();
                             },
                             cancel: function(dialog) {
                                 dialog.close();
                             },
                             width: 436,
                             title: "提示",
                             boxclass: "suc-popbox"
                         });
                     }
                 },
                 error: function() {}
             });
         },
         width: 410,
         okBtnName: '允许',
         cancelBtnName: '拒绝',
         boxclass: "commpany-popbox"
     });
 }

 function gochange(obj) {
     var promptStr = '';
     promptStr =
         '<div class = "gochange_wrapper clearfix"><span class = "gochange_warn"></span><div class ="gochange_info">您正在使用网信通信证，请前往<b class = "gochange_info_b">' + obj.address + '</b>修改密码</div></div>';
     Firstp2p.alert({
         text: '<div class="f16 gochange_text">' + promptStr + '</div>',
         title: "通知",
         ok: function(dialog) {
             dialog.close();
             location.href = obj.location;
         },
         width: 410,
         showButton: true,
         okBtnName: '前往',
         // cancelBtnName: '取消',
         boxclass: "commpany-popbox"
     });

 }

 function guidance(e) {
     var supernatant = document.getElementById("supernatant"),
         box = document.getElementById("box");
     box.style.overflow = "hidden";
     supernatant.style.display = "block";
     // e.preventDefault();
     // e.stopPropagation();
     supernatant.onclick = function() {
         box.style.overflow = "auto";
         this.style.display = "none";
     }
 }

function getPhoneMessage(obj){
     var quhao = obj.mobile_code;
     quhao = quhao.replace(/[^0-9]/, "");
     quhao = quhao == "86" ? "" : (quhao + "-");
     var phone = obj.mobile;
     var phonelabel = quhao + phone;
     var redirectUrl = obj.redirect_url;
     phonelabel = phonelabel.replace(/([0-9\-]*)([0-9]{4})([0-9]{4})/, function(_0, _1, _2, _3) {return _1 + "****" + _3 });
     var settings = $.extend({
             title : '请输入短信验证码进行身份验证',
             html : '<div class="wee-send">\
                        <div class="send-input">\
                            <div class="error-box" style="visibility:hidden;">\
                                <div class="error-wrap">\
                                    <div class="e-text" style="width: 305px;">请填写6位数字验证码</div>\
                                </div>\
                            </div>\
                            <p>已向&nbsp;<span class="color_green">' + phonelabel + ' </span>&nbsp;发送验证短信</p>\
                            <input type="text" class="ipt-txt w150" id="pop_code" placeholder="短信验证码" maxlength="10" value="">\
                            <input type="button"  class="reg-sprite btn-blue-h34 btn-gray-h34 j_sendMessage" value="发送">\
                        </div>\
                        </div>',
             msgUrl : '/user/MCode',
             postUrl : '/passport/bind'
         } , obj ),
         errorSpan = "",
         status = "",
         timer = null,
         msglock = false,
         setProperty = function () {
             var button = $(".ui_send_msg .j_sendMessage");
             bgGray();
             _reset();
         },
         bgGray = function() {
             var button = $(".ui_send_msg .j_sendMessage");
             button.addClass("btn-gray-h34");
             button.val("正在获取中...");
             button.attr("disabled", "disabled");
         },
         _set = function(msg) {
             var errorSpan = $(".ui_send_msg .error-box");
             errorSpan.css('visibility', 'visible');
             errorSpan.find('.e-text').html(msg);
             $(".ui_send_msg .ipt-txt").addClass("err-shadow");
         },
         _reset = function() {
             var errorSpan = $(".ui_send_msg .error-box");
             errorSpan.css('visibility', 'hidden');
             errorSpan.find('.e-text').html('');
         },
         updateTimeLabel = function(duration) {
             var timeRemained = duration;
             var button = $(".ui_send_msg .j_sendMessage");
             timer = setInterval(function() {
                 button.val(timeRemained + '秒后重新发送');
                 timeRemained -= 1;
                 if (timeRemained == -1) {
                     clearInterval(timer);
                     msglock = false;
                     button.val('重新发送').removeAttr('disabled').removeClass("btn-gray-h34");
                 }
             }, 1000);
         },
         callback = function(data) {
             if (!msglock) {
                 updateTimeLabel(60);
                 msglock = true;
             }
             if (!!data && data.code != 1) {
                 _set(data.message);
             } else {
                 _reset();
             }
         },
         getCode = function() {
             var data = {
                 "type": 9,
                 "mobile" : $("#valid_phone").val(),
                 "token" : $("#token").val(),
                 "token_id" : $("#token_id").val(),
                 "country_code" : $("#country_code").val()
             };
             var getcodeUrl = settings.msgUrl;
             $.ajax({
                 url: getcodeUrl,
                 type: "post",
                 data: data,
                 dataType: "json",
                 beforeSend: function() {
                 },
                 success: function(result) {
                     setProperty();
                     callback(result);
                 },
                 error: function() {

                 }
             });
         };

     $.weeboxs.open(settings.html , {
         boxid: null,
         boxclass: 'ui_send_msg',
         contentType: 'text',
         showButton: true,
         showOk: true,
         okBtnName: '确定',
         showCancel: false,
         title: settings.title,
         width: 463,
         height: 125,
         type: 'wee',
         onopen : function(){
             getCode();
         },
         onclose: function() {
             //location.href = "/user/login"
         },
         onok: function() {
             var $text = $(".ui_send_msg .error-box").find('.e-text'),
                 showError = function() {
                     $(".ui_send_msg .error-box").css({
                         'display': 'block',
                         'visibility': 'visible'
                     });
                     $(".ui_send_msg .ipt-txt").addClass("err-shadow");
                 };

             if (!/^\d{6}$/.test($(".ui_send_msg #pop_code").val())) {
                 showError();
                 $text.html("请填写6位数字验证码");
                 return;
             }
             var data = {
                 "code": $(".ui_send_msg #pop_code").val(),
                 "mobile" : $("#valid_phone").val()
             };
             $.ajax({
                 url: settings.postUrl,
                 type: "post",
                 data: data,
                 dataType: "json",
                 beforeSend: function() {
                     // $text.html("正在提交，请稍候...");
                 },
                 success: function(data) {
                     // alert(JSON.stringify(data));
                     if (data.errno == 0) {
                         $("#loginForm").append('<input type="hidden" name="code" value="'+ $("#pop_code").val() +'" >').unbind("submit").submit();
                         $.weeboxs.close();
                         //window.location.href = "/account/index";
                         window.location.href = redirectUrl;
                     } else {
                         showError();
                         $text.html(data.error);
                     }
                 },
                 error: function() {
                     showError();
                     $text.html("服务器错误，请重新再试！");
                 }
             });
             // $.weeboxs.close();
         }
     });
     // 点击“重新发送”按钮获取短信验证码
     $('body').on("click", ".j_sendMessage", function() {
         getCode();
     });

 }

 // 通行证修改密码确认弹窗
 function confirmChange() {
     var promptStr = '';
     promptStr = '<div style="line-height:25px;font-size:16px">您已更新您的通行证密码，请及时修改您的网信密码，避免泄露</div>';
     Firstp2p.alert({
         text: '<div class="f16">' + promptStr + '</div>',
         title: "提示",
         ok: function(dialog) {
             dialog.close();
         },
         width: 410,
         showButton: true,
         okBtnName: '确定',
         // cancelBtnName: '取消',
         boxclass: "commpany-popbox"
     });
 };

