$(function() {
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



