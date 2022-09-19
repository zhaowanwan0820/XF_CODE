$(function() {

    // window.zhuge = window.zhuge || [];window.zhuge.methods = "_init debug identify track trackLink trackForm page".split(" ");
    // window.zhuge.factory = function(b) {return function() {var a = Array.prototype.slice.call(arguments);a.unshift(b);
    // window.zhuge.push(a);return window.zhuge;}};for (var i = 0; i < window.zhuge.methods.length; i++) {
    // var key = window.zhuge.methods[i];window.zhuge[key] = window.zhuge.factory(key);}window.zhuge.load = function(b, x) {
    // if (!document.getElementById("zhuge-js")) {var a = document.createElement("script");var verDate = new Date();
    // var verStr = verDate.getFullYear().toString()+ verDate.getMonth().toString() + verDate.getDate().toString();
    // a.type = "text/javascript";a.id = "zhuge-js";a.async = !0;a.src ='https://stat.ncfwx.com/zhuge.js?v=' + verStr;
    // a.onerror = function(){window.zhuge.identify = window.zhuge.track = function(ename, props, callback){if(callback && Object.prototype.toString.call(callback) === '[object Function]')callback();};};
    // var c = document.getElementsByTagName("script")[0];c.parentNode.insertBefore(a, c);window.zhuge._init(b, x)}};
    // window.zhuge.load('da1ad45dbe1e4583a9db20c0df763d0f',{superProperty:{PlatformType:'web'},autoTrack:true,visualizer: true});//配置应用的AppKey

    // var buttonCz = document.getElementsByClassName("button_cz")[0];
    // console.log(buttonCz);
    // buttonCz.onclick = function(){
    // zhuge.track('账户充值');
    // console.log('22');
    // }
    (function() {
       // 勋章点亮弹出提示（新手+普通）
           $.ajax({
               url: '/medal/message',
               type: 'GET',
               data: {},
               dataType: 'json',
               success: function(result) {
                   if(result.data.length > 0){
                       // 新手任务完成提示
                       if (result.data[0].isForBeginner == 1) {
                            var promptStr = '';
                            promptStr = '<div class="pop-tit"><img src="' + result.data[0].icon.iconSmall + '" alt="" width="234" height="234"></div>' +
                                '<div class="pt-name red f24">' + result.data[0].name + '</div>' +
                                '<div class="gongxi"><span class="yellow2 f32 mb5">恭喜点亮新手勋章</span>' +
                                '<span class="f18 gray">' + result.data[0].remark + '</span>' +
                                '</div>'
                            Firstp2p.alert({
                                text: promptStr,
                                ok: function(dialog) {
                                    dialog.close();
                                    location.href = '/medal/wall';
                                },
                                width: 488,
                                okBtnName: '领取奖励',
                                boxclass: "medal-popbox light-popbox"
                            });
                       } else if(result.data[0].isForBeginner == 0){
                           // 点亮勋章提示
                           var promptStr = '';
                           promptStr = '<div class="pop-tit"><img src="' + result.data[0].icon.iconSmall + '" alt="" width="234" height="234"></div>' +
                               '<div class="pt-name red f24">'+ result.data[0].name +'</div>'+
                               '<div class="gongxi"><span class="yellow2 f32 mb5">恭喜点亮勋章</span>' +
                               '<span class="f18 gray">'+ result.data[0].remark +'</span>' +
                               '</div>'                 
                           Firstp2p.alert({
                               text: promptStr,
                               ok: function(dialog) {
                                   dialog.close();
                                   location.href = '/medal/wall';
                               },
                               width: 488,
                               okBtnName:'前往查看',
                               boxclass: "medal-popbox light-popbox"
                           });
                       }
                   }
               },
               error: function() { }
           })
    })();
    
});



