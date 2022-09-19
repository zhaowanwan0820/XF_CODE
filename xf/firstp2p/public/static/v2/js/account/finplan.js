(function($) {
     $(function() {
   function clickopen(id) {
       var url = '/account/DtContShow?tag=show&amp;ajax=1&amp;id=' + id;
       $.weeboxs.open(url, {
           boxid: null,
           contentType: 'iframe',
           showButton: true,
           showCancel: false,
           showOk: false,
           title: '合同详情',
           width: 750,
           height: 550,
           type: 'wee',
           onclose: function() {
               null
           }
       });
   }   
   function redeem(id, thiz) {
       var url = '/account/redeem';
       $(thiz).addClass('but-disabled').attr("disabled", "disabled").css({
           "cursor": "default"
       });
       $.post(url, {
           id: id
       }, function(rs) {
           if (rs.status == 0) {
               alert('正在放款中，请稍后重试！');
               $(thiz).removeClass('but-disabled').removeAttr("disabled", "disabled").css({
                   "cursor": "pointer"
               });
           } else {
               window.location.href = '/account/success/?id=' + id + '&amp;gS=' + rs.jump.gS;
           }
       });
   }

   // 管理费提示
   $(".ico_sigh").tooltip({
       position: {
           my: "center-25 top+5",
           at: "center-25 bottom"
       }
   });

   function showdetail(id,dealId,status) {
       var contractId = '#contract' + id;
       if(! $(contractId).innerHtml) {
           $.ajax({
               url: '/account/FinplanContract',
               data: 'loanId='+id+'&dealId='+dealId+'&status='+status,
               dataType: "json",
               success: function(res){
                   if(res) {
                       var contractNum = res.length;
                       var html = '';
                       html += '<h2 class="sub_title"><span>合同</span></h2>';
                       html += '<div class="contract clearfix">';
                       for (var i=0;i<contractNum;i++)
                       {
                           var contract = res[i];
                           if(i == 0) {
                               html += '<div class="jk_contract" >';
                           } else {
                               html += '<div class="jk_contract xy_contract" >';
                           }
                               
                           html += '<i class="ico_jilu fl"></i>';
                           html += '<div class="con_title">';
                           html += '<p title="'+contract.title+'">'+contract.title+'</p>';
                           html += '<span class="con_nub">'+contract.number+'</span>';
                           html += '<a class="action action-view j_view" href="javascript:clickopen(\''+contract.number
                                   +'\',true);" data-id="'+contract.id+'" title="查看"><i class="ico_see"></i></a>';
                           html += '<a class="action action-download" href="{url x="index" r="account"}/DtContShow?number='
                                   +contract.number.toString()+'&tag=download&ajax=0&ctype=1" title="下载PDF"><i class="ico_down"></i></a>';
                           html += '</div>';
                           html += '</div>';
                       }
                       html += '</div>';
                       $(contractId).html(html);
                   }
               }
           });
       }
       
       
       var repayListId = '#repayList' + id;
       var repayListHeadId = '#repayListHead' + id;
       
       if(status ==2 && !$(repayListId).innerHtml) {
           $.ajax({
               url: '/account/FinplanP2P',
               data: 'loanId='+id+'&dealId='+dealId+'&p=0',
               dataType: "json",
               success: function(res){
                   if(res) {
                       $(repayListHeadId).html('<h2 class="sub_title_dt mt15 f20"><span class="mr10">投资列表</span><span class="f16">（待投本金:<span class="color-yellow1">'+res.noMappingMoney+'元</span>）</span></h2>');
                       
                       var listNum = res['list'].length;
                       var html = '';
                       html += '<table class="plan">';
                       html += '<colgroup>';
                       html += '<col width="145">';
                       html += '<col width="110">';
                       html += '<col width="110">';
                       html += '<col width="110">';
                       html += '<col width="110">';
                       html += '<col width="270">';
                       html += '</colgroup>';
                       html += '<thead>';
                       html += '<tr>';
                       html += '<th>项目标题</th>';
                       html += '<th>投资金额（元）</th>';
                       html += '<th>投资人</th>';
                       html += '<th>融资人</th>';
                       html += '<th>成交日期</th>';
                       html += '<th>合同类型</th>';
                       html += '</tr>';
                       html += '</thead>';
                       for (var i=0;i<listNum;i++) {
                           var repay = res['list'][i];
                           html += '<tbody class="tabContent">';
                           if(i%2 == 0) {
                               html += '<tr>';
                           }else {
                               html += '<tr class="tr_bg">';
                           }
                           html += '<td>'+repay.name+'</td>';
                           html += '<td>'+repay.money+'</td>';
                           html += '<td>'+repay.loanUsername+'</td>';
                           html += '<td>'+repay.borrowUsername+'</td>';
                           html += '<td>'+repay.loanTime+'</td>';
                           if(repay.contractType == 0) {
                               html += '<td>借款协议'+repay.contractNo+'</td>';
                           }
   //                        else if(repay.contractType == 2){
   //                            html += '<td>咨询服务协议'+repay.contractNo+'</td>';
   //                        }
                           else{
                               html += '<td>债权转让协议'+repay.contractNo+'</td>';
                           }
                           html += '<td><a class="blue" href="javascript:clickopen(\''+repay.contractNo
                           +'\');">查看</a></td>';
                           html += '<td><a class="blue" href="DtContShow?number='+repay.contractNo+'&tag=download&ajax=0">下载</a></td>';
                           html += '</tr>';
                           html += '</tbody>';
                       }
                       html += '</table>';
                       $(repayListId).html(html);
                       
                       var pageId = '#page' + id;
                       $(pageId).html(res.page);
                   }
               }
           });
       }
   }

   function showDetailPage(laonId,dealId,p) {
       var repayListId = '#repayList' + laonId;
       $.ajax({
           url: '/account/FinplanP2P',
           data: 'loanId='+laonId+'&dealId='+dealId+'&p='+p,
           dataType: "json",
           success: function(res){
               if(res) {
                   $(repayListId).empty();
                   var listNum = res['list'].length;
                   var html = '';
                   html += '<table class="plan">';
                   html += '<colgroup>';
                   html += '<col width="145">';
                   html += '<col width="110">';
                   html += '<col width="110">';
                   html += '<col width="110">';
                   html += '<col width="110">';
                   html += '<col width="270">';
                   html += '</colgroup>';
                   html += '<thead>';
                   html += '<tr>';
                   html += '<th>项目标题</th>';
                   html += '<th>投资金额（元）</th>';
                   html += '<th>投资人</th>';
                   html += '<th>融资人</th>';
                   html += '<th>成交日期</th>';
                   html += '<th>合同类型</th>';
                   html += '</tr>';
                   html += '</thead>';
                   for (var i=0;i<listNum;i++) {
                       var repay = res['list'][i];
                       html += '<tbody class="tabContent">';
                       if(i%2 == 0) {
                           html += '<tr>';
                       }else {
                           html += '<tr class="tr_bg">';
                       }
                       html += '<td>'+repay.name+'</td>';
                       html += '<td>'+repay.money+'</td>';
                       html += '<td>'+repay.loanUsername+'</td>';
                       html += '<td>'+repay.borrowUsername+'</td>';
                       html += '<td>'+repay.loanTime+'</td>';
                       if(repay.contractType == 0) {
                           html += '<td>借款协议'+repay.contractNo+'</td>';
                       } else {
                           html += '<td>债权转让协议'+repay.contractNo+'</td>';
                       }
                       html += '<td><a class="blue" href="'+repay.contractNo+'">下载</a></td>';
                       html += '</tr>';
                       html += '</tbody>';
                   }
                   html += '</table>';
                   $(repayListId).html(html);
                   
                   var pageId = '#page' + laonId;
                   $(pageId).empty();
                   $(pageId).html(res.page);
               }
           }
       });
   }

   function clickopen(number,ctype){
       if(ctype == true){
           var url = '/account/DtContShow?tag=show&amp;ajax=1&amp;ctype=1&amp;number=' + number;
       }else{
           var url = '/account/DtContShow?tag=show&amp;ajax=1&amp;number=' + number;
       }
       $.weeboxs.open(url, {boxid:null,contentType:'iframe',showButton:true, showCancel:false, showOk:false,title:'合同详情',width:750,height:550,type:'wee',onclose:function(){null}});
   }
   $(".j_shuhui").click(function(){
       var id = $(this).attr('data-id');
       if(!id){
           return false;
       }
       var url = '/account/finplanshow?id='+id;
       $.getJSON(url,function(data){
           if(!data.status){
               $.showErr(data.info, function() {}, "提示");
               return false;
           }
           if(data.info.is_holiday){
               $.showErr('请您在每周工作日'+data.info.redemptionStartTime+'-'+data.info.redemptionEndTime+'进行赎回。谢谢您的谅解！', function() {}, "提示");
               return false;
           }
           $("#_js_name").html(data.info.name);
           $("#_js_money").html(data.info.money);
           $("#_js_sum").html(data.info.sum);
           $("#_js_title").html(data.info.title);
           $("#_js_date").html(data.info.date);

           if(data.info.feeDays != 0) {
               $("#js_mange_fee").html('无');
           } else {
               $("#js_mange_fee").html('本金的'+data.info.feeRate+'%,持有满'+data.info.feeDays+'天免费'+'(您已持有'+data.info.ownDay+'天)'+'<i class="ico_sigh" title="管理费='+ data.info.feeRate +'% *投资金额*实际持有天数 /360。实际持有天数=到帐日-计息日。持有满'+ data.info.feeDays +'天免费"></i>');
           }
           
           if(data.info.is_holiday){
               //$("#_js_is_holiday").html(data.info.is_holiday);
           }else{
               //$("#_js_is_holiday").hide();
           }
           $.weeboxs.open('.redemption', {contentType:'selector',boxclass:"weebox_dt_sh",onok:function(){redeem(id,this);$.weeboxs.close();},showButton:true,okBtnName: '确认赎回', showCancel:true, showOk:true,title:'赎回详情',width:450,type:'wee',onclose:function(){null}});
           $(".weebox_dt_sh .ico_sigh").tooltip({
               position: {
                   my: "center-100 top+10",
                   at: "center bottom"
               }
           });
       });

       function redeem(id,thiz){
           var url = '/account/finplanRedeem';
           $(thiz).addClass('but-disabled').attr("disabled","disabled").css({"cursor":"default"});
           $.post(url,{id:id},function(rs){
               if(rs.status == 0){
                   $.showErr(rs.info, function() {}, "提示");
                   $(thiz).removeClass('but-disabled').removeAttr("disabled","disabled").css({"cursor":"pointer"});
               }else{
                  //alert("您的申请已提交，我们将尽快处理您的申请。请您耐心的等待。如有疑问请致电400-890-9888");
                  window.location.href = '/account/finplanSuccess/?id='+id+'&gS='+rs.jump.gS;
               }
           });
       }
   });


     });
})(jQuery);