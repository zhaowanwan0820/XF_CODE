;(function($) {
    var config = null;

    $(function() {
            //启动我的账户逻辑
            try {
                if (typeof (USER_INFO) != 'undefined' && USER_INFO == 1) {
                    myAccount();
                }
            } catch (e) {

            }
            if(location.pathname.indexOf("/product") > -1){
                $("#top_nav li").removeClass("select");
                $("#top_nav li:eq(1)").addClass("select");
            }else if(location.pathname.indexOf("/id-aboutp2p") > -1){
                $("#top_nav li").removeClass("select");
                $("#top_nav li:eq(3)").addClass("select");
            }
        })
        //我的账户
    function myAccount() {
        var ele = $('.my_account');
        var ele_msg = $('.j_showMenu2');
        if(ele.find("ul").length > 0){
            ele.hover(
                function() {
                    ele.addClass("select");
                },
                function() {
                    ele.removeClass("select");
                }
            );
        }

        ele_msg.hover(
            function() {
                ele_msg.addClass("select");
            },
            function() {
                ele_msg.removeClass("select");
            }
        )

    }
})(jQuery, "firstp2p common.js");

var Firstp2p = {
    mobileReg: {
        'cn': /^1[3456789]\d{9}$/,
        'hk': /^[968]\d{7}$/,
        'mo': /^[68]\d{7}$/,
        'tw': /^09\d{8}$/,
        'us': /^\d{10}$/,
        'ca': /^\d{10}$/,
        'uk': /^7\d{9}$/
    },
    isIE7 : function(){
       return navigator.appVersion.search(/MSIE 7/i) != -1;
    },
    // 3秒后自动跳转公用js
    goPay: function(options){
        var defaultSettings = {
            number: 3,
            $obj: $("#second"),
            callback : function(){
                $("#bindCardForm").submit();
            }
        },
        settings = $.extend(true, defaultSettings, options),
        num = settings.number;
        $(settings.$obj).html(num);
        var goes = setInterval(function(){
                num--;
                $(settings.$obj).html(num);
                if(num === 0 ){
                    clearInterval(goes);
                    settings.callback();
                }
        },1000);
    }
};

//登录后强制修改密码提示
(function($) {
    $(function() {
        if (typeof forceChangePwd !== 'undefined' && !!forceChangePwd&&isEnterprise!=1) {
            //如果是保存密码就不弹出
            if( window.location.pathname!='/user/savepwd'){
                //弹出层
                var promptStr = '';
                promptStr = '<div class="pop-tit"><i></i>为了您的账户安全，首次登录系统时请修改您的登录密码！</div>' +
                    '<div class="tc">点击<a href="javascript:void(0)" class="blue" id="edit-btn">修改登录密码</a></div>';
                Firstp2p.alert({
                    text: '<div class="f16">' + promptStr + '</div>',
                    ok: function(dialog) {
                        dialog.close();
                    },
                    width: 560,
                    showButton: false,
                    boxclass: "commpany-popbox"
                });
            }
            $("body").on("click", ".dialog-close , #edit-btn" ,function() {
                $.weeboxs.close();
                if( window.location.pathname!='/user/editpwd'){
                    location.href = '/user/editpwd';
                }
            });
        }

    });
})(jQuery);

//登录后根据is_dflh值判断是否弹出用户迁移协议
(function($) {
    $(function() {
    //is_dflh值为1时弹出用户迁移协议
        if (typeof user_info_is_dflh !== 'undefined' && user_info_is_dflh == 1) {
            var transDialogStr = $('#trans_protocal_str').val();
            $('.transDialog .dialog_user').prepend(transDialogStr);
            $('.transDialog').removeClass('tsfDgHide');
            $("body").css({"overflow-y": "hidden"});
            $('.transDialog').on('click','#agreeTrans', function(){
                var _this = $(this),
                lock =  _this.data('lock');
                if(lock == 0){
                    _this.data('lock','1');
                    $.ajax({
                        url: '/user/transferConfirm',
                        type: 'post',
                        data: {},
                        dataType: 'json',
                        success: function(data) {
                            if (data.code == 0) {
                                $('.transDialog').addClass('tsfDgHide');
                                $('.transDialog').remove();
                                $("body").css({"overflow-y": "auto"});
                            }else{
                                alert(data.msg);
                            }
                             _this.data('lock','0');
                        },
                        error: function() {
                            alert("网络异常，稍后重试");
                            _this.data('lock','0');
                        }
                    })
                }
            });
        }
    });
})(jQuery);




//存管-弹窗
(function($) {
    $(function() {

       Firstp2p.supervision = {
          // 开通网贷P2P账户
           kaihu : function(data){
           var str = " <div class='p2pImg'></div><p class='openTips'>开通"+account_p2p+"</p><p class='acDetail'>根据国家相关法律法规要求，需开立"+account_p2p+"</p>";
           var settings = $.extend({
                  title: "开通"+account_p2p,
                  ok: $.noop,
                  text: str,
                  close: $.noop
              }, data),
              html = '',
              instance = null;
              html += '' + settings.text + '';
              instance = $.weeboxs.open(html, {
                  boxid: 'cg_openP2pAccount',
                  boxclass: 'p2pAccountDg',
                  contentType: 'text',
                  showButton: true,
                  showOk: true,
                  showCancel:true,
                  title: settings.title,
                  width: 300,
                  type: 'wee',
                  onok: function(settings) {
                      settings.close(settings);
                      window.open("/payment/transit?srv=register");
                      Firstp2p.supervision.wancheng();
                  }
           });
          },
           //余额划转-网贷p2p账户到网信账户
           zhuanlicai : function(data){
               var dynamicMap={
                   "1002":{
                       "title":account_wx + "余额不足，需进行余额划转",
                       "boxclass":"transferBl"
                   },
                   "1004":{
                       "title":account_p2p+"余额不足，需进行余额划转",
                       "boxclass":"transferBl_top2p"
                   }
               }
               var inforJson=dynamicMap[data.status];
              var that = this;
              var str = "<p class='hz_less'>"+inforJson.title+"</p>\
                <div style='display:inline-block;' class='p2pImg'></div><div style='display:inline-block;' class='fxImg'></div><div style='display:inline-block;' class='transferImg'></div><p class='openTips'>划转金额："+ data.data.transferMoney +"</p>";
              var settings = $.extend({
                    title: "余额划转",
                    text : str,
                    boxclass: inforJson.boxclass,
                    close: $.noop
               }, data),
              html = '',
              instance = null;
              html += '' + settings.text + '';
              var dialog = null;
              instance = $.weeboxs.open(html, {
                  boxid: null,
                  boxclass: settings.boxclass,
                  contentType: 'text',
                  showButton: true,
                  showOk: true,
                  showCancel:true,
                  title: settings.title,
                  width: 300,
                  type: 'wee',
                  onok: function(settings) {
                     if(!!data.data){
                          window.open(data.data.url);
                          Firstp2p.supervision.lunxun({
                              sCallback : function(obj){

                                  if(obj.status == 1){
                                      !!dialog && dialog.close();
                                      !!settings && settings.close();
                                      that.resetsubmit();
                                      clearInterval(lunxunTimer);
                                  }else if(obj.status == 2){
                                      Firstp2p.alert({
                                         text : obj.msg
                                      });
                                      that.resetsubmit();
                                      clearInterval(lunxunTimer);
                                  }else if(obj.status == 0){
                                      that.resetsubmit();
                                  }

                              },
                              url : "/supervision/RechargeQuery",
                              data : {
                                orderId : data.data.orderId
                              }

                          });
                          settings.close(settings);
                          dialog = that.finish();
                      }
                  },
                  onclose : function(){
                      that.resetsubmit();
                      $("#J_bid_submit").data("wancheng_dialog_lock" , "1");

                  }
           });
          },
          //余额划转-网信账户到网贷p2p账户
          zhuanwdp2p : function(data){
              var that = this;
              var str = "<p class='hz_less'></p>\
                <div style='display:inline-block;' class='p2pImg'></div><div style='display:inline-block;' class='fxImg'></div><div style='display:inline-block;' class='transferImg'></div><p class='openTips'>划转金额："+ data.data.transferMoney +"</p>\
                <div class='notipsCont'><input name='missTips' id='missTips' class='missTips' type='checkbox' value='不再提示'/><label class='hznotips' for='missTips'></label><span class='bztsTxt'>不再提示，下次自动划转</span></div>";
              var settings = $.extend({
                    title: "余额划转",
                    text : str,
                    boxclass: '',
                    close: $.noop
               }, data),
              html = '',
              instance = null;
              html += '' + settings.text + '';
              var dialog = null;
              instance = $.weeboxs.open(html, {
                  boxid: null,
                  boxclass: 'transfer_hz transferBl_notips',
                  contentType: 'text',
                  showButton: true,
                  showOk: true,
                  showCancel:false,
                  title: settings.title,
                  width: 300,
                  type: 'wee',
                  onok: function(settings) {
                    $.ajax({
                        url: '/supervision/MoneyTransfer?money='+ data.data.transferMoney +'&direction='+ data.data.direction +'',
                        type: 'post',
                        data: {},
                        dataType: 'json',
                        success: function(data) {
                          Firstp2p.alert({
                              text : '<div class="tc">'+  data.info +'</div>',
                              ok : function(dialog){
                                  dialog.close(dialog);
                              }
                          });
                        },
                        error: function() {
                          Firstp2p.alert({
                              text : '<div class="tc">网络错误，请稍后重试！</div>',
                              ok : function(dialog){
                                  dialog.close(dialog);
                              }
                          });
                        }
                    })
                    settings.close(settings);
                  },
                  onclose : function(){
                      that.resetsubmit();
                  }
              });
          },


           //开通服务授权
            shouquan : function(data){
            var str = "<div class='psdImg'></div><p class='openTips'>开通《快捷投资服务》</p><p class='acDetail'>该授权用于当您使用"+account_p2p+"资金进行投资、余额划转至"+account_wx+"时，无需输入交易密码。</p>";
           var settings = $.extend({
                  title: "开通服务授权",
                  text : str,
                  boxclass: 'transferBl_top2p'
              }, data),
              html = '',
              instance = null;
              html += '' + settings.text + '';
              instance = $.weeboxs.open(html, {
                   boxid: 'cg_password_free',
                   boxclass: 'passwordFree',
                   contentType: 'text',
                   showButton: true,
                   showOk: true,
                   showCancel:true,
                   okBtnName: '开通',
                   cancelBtnName: '忽略',
                   showCancel: false,
                   title: settings.title,
                   width: 300,
                   type: 'wee',
                   onok: function(obj) {
                       window.open('/payment/Transit?srv=freePaymentQuickBid');
                       obj.close(obj);
                   }
               });
               return instance;
          },

          //充值 提现 账户中心完成弹窗
           wancheng : function(obj) {
                var that = this;
                var settings = $.extend({
                       title: "完成操作",
                       ok: $.noop,
                       text: '<p class="czwc">请您在新打开的网银或第三方支付页面完成操作</p><p class="doneDgTt" style="color:red;">完成操作后请根据情况点击下面的按钮</p>',
                       close: $.noop
                   }, obj),
                   html = '',
                   instance = null;
                   html += '' + settings.text + '';
                   instance = $.weeboxs.open(html, {
                       boxid: null,
                       boxclass: 'done_Confirm',
                       contentType: 'text',
                       showButton: true,
                       showOk: true,
                       showCancel:true,
                       okBtnName: '操作已完成',
                       cancelBtnName: '操作遇到问题',
                       title: settings.title,
                       width: 300,
                       type: 'wee',
                       onok: function(object) {
                            if(typeof(doneBankOperate_url) != "undefined" && doneBankOperate_url != ""){
                              window.location.href = doneBankOperate_url;
                            }else{
                              location.reload();
                            }
                       },
                       oncancel: function(object) {
                           location.reload();
                       }
                });
                return instance;
           },

           finish : function(obj) {
                var that = this;
                var settings = $.extend({
                       title: "完成操作",
                       ok: $.noop,
                       text: '<p class="czwc">请您在新打开的网银或第三方支付页面完成操作</p><p class="doneDgTt" style="color:red;">完成操作后请根据情况点击下面的按钮</p>',
                       close: $.noop
                   }, obj),
                   html = '',
                   instance = null;
                   html += '' + settings.text + '';
                   instance = $.weeboxs.open(html, {
                       boxid: null,
                       boxclass: 'done_Confirm',
                       contentType: 'text',
                       showButton: true,
                       showOk: true,
                       showCancel:true,
                       okBtnName: '操作已完成',
                       cancelBtnName: '操作遇到问题',
                       title: settings.title,
                       width: 300,
                       type: 'wee',
                       onclose: function(object) {
                          //object.close(object);
                          that.resetsubmit();

                       },
                       onok: function(object) {
                          that.resetsubmit();
                          clearInterval(lunxunTimer);
                          object.close(settings);
                       },
                       oncancel: function(object) {
                          that.resetsubmit();
                          clearInterval(lunxunTimer);
                          object.close(object);
                       }
                });
                return instance;
           },
           //js长轮询
           lunxun : function(obj){

              var that = this ,
              ram = new Date().getTime(),
              settings = $.extend({
                 time : 5 ,
                 url : location.href,
                 data : "",
                 dataType : "json" ,
                 sCallback : $.noop ,
                 eCallback : $.noop ,
                 type : "get",
                 timer : 3000
              } , obj),
              lunxun = function(){
                $.ajax({
                   url : settings.url ,
                   type : settings.type ,
                   data : settings.data ,
                   dataType : settings.dataType ,
                   success : function(data){
                      settings.sCallback(data);
                   },
                   error : function(data){
                      //eCallback(data);
                      clearInterval(lunxunTimer);
                      console.log(settings.url + '接口出错了，请检查接口！');
                   }

                });
              };
              if(typeof lunxunTimer != 'undefined'){
                clearInterval(lunxunTimer);
              }
              //lunxun();
              window["lunxunTimer"] = setInterval(function(){
                 lunxun();
              }, settings.timer);
           },
           //判断是否开户
           isKaihu : function(){
                var that = this;
                $.ajax({
                   url: '/account/isOpenAccount',
                   type: "get",
                   data: '',
                   dataType: "json",
                   success: function(json) {
                        if(json.errno == 0){
                            if(json.data.status == 0){
                                that.kaihu();
                            }
                        }else{
                            Firstp2p.alert({
                                text : '<div class="tc">'+  json.error +'</div>',
                                ok : function(dialog){
                                    dialog.close(dialog);
                                }
                            });
                        }
                   }
                });
           },
           resetsubmit : function(){
               $("#J_bid_submit").removeAttr('disabled').val('提交');
           },

           ph_account : function(data,lockObj){
               var ph_location_href = data,
                   _this = lockObj,
                   _lock = _this.data('lock');
               if(isSvUser == 1){
                   window.location.href = ""+ ph_location_href;
               }else{
                  if(_lock == 0){
                   _this.data('lock','1');
                   $.ajax({
                      url: '/deal/isOpenAccount',
                      type: "get",
                      dataType: "json",
                      success: function(json) {
                           if(json.errno === 0){
                               if(json.data.status == 1 || json.data.wxStatus == 0){
                                   window.location.href = ""+ ph_location_href;
                               }else{
                                   Firstp2p.supervision.kaihu();
                                   $('#cg_openP2pAccount .dialog-close').wrap("<a class='btn-base dialog-cancel'></a>");
                                   if(typeof window["_openSvButton_"] !== 'undefined' && window["_openSvButton_"] == 1){
                                       $('.p2pAccountDg .dialog-title').html("升级"+account_p2p);
                                       $('.p2pAccountDg .openTips').html("升级"+account_p2p);
                                   }
                               }
                           }else{
                               Firstp2p.alert({
                                   text : '<div class="tc">'+  json.error +'</div>',
                                   ok : function(dialog){
                                       dialog.close();
                                   }
                               });
                           }
                        _this.data('lock','0');
                      },
                      error: function(){
                           Firstp2p.alert({
                               text : '<div class="tc">网络错误，请稍后重试！</div>',
                               ok : function(dialog){
                                   dialog.close();
                               }
                           });
                           _this.data('lock','0');
                      }
                   });
                  }
               }

           },
           ph_account_newWindow : function(data,lockObj){
               var ph_location_href = data,
                   _this = lockObj,
                   _lock = _this.data('lock');
               if(isSvUser == 1){
                   window.open(ph_location_href);
               }else{
                  if(_lock == 0){
                     _this.data('lock','1');
                     $.ajax({
                        url: '/deal/isOpenAccount',
                        type: "get",
                        dataType: "json",
                        async:false,
                        success: function(json) {
                             if(json.errno === 0){
                                 if(json.data.status == 1){
                                    window.open(ph_location_href);
                                 }else{
                                     Firstp2p.supervision.kaihu();
                                     $('#cg_openP2pAccount .dialog-close').wrap("<a class='btn-base dialog-cancel'></a>");
                                     if(typeof window["_openSvButton_"] !== 'undefined' && window["_openSvButton_"] == 1){
                                        $('.p2pAccountDg .dialog-title').html("升级"+account_p2p);
                                        $('.p2pAccountDg .openTips').html("升级"+account_p2p);
                                     }
                                 }
                             }else{
                                 Firstp2p.alert({
                                     text : '<div class="tc">'+  json.error +'</div>',
                                     ok : function(dialog){
                                         dialog.close();
                                     }
                                 });
                             }
                          _this.data('lock','0');
                        },
                        error: function(){
                             Firstp2p.alert({
                                 text : '<div class="tc">网络错误，请稍后重试！</div>',
                                 ok : function(dialog){
                                     dialog.close();
                                 }
                             });
                             _this.data('lock','0');
                        }
                     });
                  }
               }

           }


       }
       //划转弹窗-不再提示逻辑
       $("body").on('click','.missTips',function(){
          if($(this).is(":checked")){
              $.ajax({
                 url: '/payment/setNotPromptTransfer',
                 type: "get",
                 dataType: "json",
                 success: function(json) {
                 },
                 error: function(){
                 }
              });
          }
       });

        
       //登录后根据_wxFreepayment_值判断是0时弹出存管协议
       if (typeof _wxFreepayment_ !== 'undefined' && _wxFreepayment_ !== '' && _wxFreepayment_ == 0) {
           var cgDialogStr = $('#cg_protocal_str').val(),
               cgDialogTitle = $('#cg_protocal_title').val();
           $('#cgProtocol .dialog_user').prepend(cgDialogStr);
           $('#cgProtocol .cgTitle').prepend(cgDialogTitle);
           $('#cgProtocol').removeClass('none1');
           $("body").css({"overflow-y": "hidden"});
           $('#cgProtocol').on('click','#agreeCgPt', function(){
               var _this = $(this),
               lock =  _this.data('lock');
               if(lock == 0){
                   _this.data('lock','1');
                   $.ajax({
                       url: '/user/sign_wxfreepayment',
                       type: 'post',
                       data: {},
                       dataType: 'json',
                       success: function(data) {
                           if (data.errno == 0 && data.data.status == 1) {
                               $('#cgProtocol').addClass('none1');
                               $('#cgProtocol').remove();
                               $("body").css({"overflow-y": "auto"});
                           }else if(data.errno ==1045){
                              Firstp2p.alert({
                                    text : '<div class="tc">'+  data.error +'</div>',
                                    ok : function(dialog){
                                        dialog.close();
                                        window.location.href="/user/login";
                                    }
                              });
                           }else{
                               Firstp2p.alert({
                                     text : '<div class="tc">'+  data.error +'</div>',
                                     ok : function(dialog){
                                         dialog.close();
                                     }
                               });
                           }
                            _this.data('lock','0');
                       },
                       error: function() {
                           Firstp2p.alert({
                                 text : '<div class="tc">网络异常，稍后重试</div>',
                                 ok : function(dialog){
                                     dialog.close();
                                 }
                           });
                           _this.data('lock','0');
                       }
                   })
               }
           });
       }

    });
})(jQuery);


var p2pBrowser = (function () {
    var u = navigator.userAgent
    return {
    wx: /MicroMessenger/i.test(u),
    webkit: /AppleWebKit/i.test(u),
    gecko: /gecko/i.test(u),
    ios: /\(i[^;]+;( U;)? CPU.+Mac OS X/.test(u),
    android: /android/i.test(u),
    iPhone: /iPhone/i.test(u),
    iPad: /iPad/i.test(u),
    app: /wx/i.test(u),
    androidApp: /wxAndroid/i.test(u),
    iosApp: /wxiOS/i.test(u)
    }
})()