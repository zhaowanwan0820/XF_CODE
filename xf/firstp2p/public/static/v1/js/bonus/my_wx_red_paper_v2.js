(function($) {
    $(function() {
        // tab事件绑定
        $('#tabs li a').click(function() {
            var $that = $(this),
                status = $that.attr('data-tab');
            if ($that.parent('li').hasClass('ui-tabs-active')) return;

            $('#tabs li').removeClass('ui-tabs-active');
            $that.parent('li').addClass('ui-tabs-active');

            reqData('own', status, 1, '#pagination_00');
        });
		
		
		
        // ajax请求
        function reqData(type, status, page, pageSelector) {
            $.ajax({
                url: '/account/bonusAsync',
                type: 'GET',
                data: {
                    p: page,
                    type: type,
                    status: status
                },
                success: function(data) {
                    data = $.parseJSON(data);

                    if (type == 'own'){
                        ownHtml(data,status);
                    }else if (type == 'send'){
                        sendHtml(data);
                    }

                    if(data.pagecount <= 0){
                        $(pageSelector).hide();
                        return;
                    }else{
                        $(pageSelector).show();
                        Firstp2p.paginate($(pageSelector), {
                            pages: data.pagecount,
                            currentPage: page,
                            onPageClick: function(pageNumber, $obj) {
                                reqData(type, status, pageNumber, pageSelector);
                            }
                        });
                    }
                },
                error: function() {}
            })
        }
        // 数据拼接
        function ownHtml(data,status) {
            var html = '';
            if(status==1){
                html += ' <div>';
                html += '<table border="0" class="tab-thead">';
                html += '<thead>';  
                html += '<tr>';   
                html += '<th width="93">面值</th>';   
                html += ' <th width="118">类型</th>';   
                html += '<th width="116">投资限制</th>';   
                html += ' <th width="288">有效期</th>';   
                html += '<th width="147">红包来源</th>';   
                html += '</tr>';   
                html += ' </thead>';   
                html += ' </table>';   
                html += '<table width="100%" border="0" class="tab-tbody">';    
                html += '<thead>'; 
                html += '</thead>';
                html += '<tbody>';    

                if(data.list.length<=0){
                    html += '<div class="empty-box">没有记录</div>';
                }else{
                    for (var i = 0; i < data.list.length; i++) {
                        html += '<tr>';
                        html += '<td width="83"><span class="color-yellow1">￥' + data.list[i].money + '</span></td>';
                        html += '<td width="128">' + data.list[i].bonus_type + '</td>';
                        html += '<td width="96">' + data.list[i].bid_limit + '</td>';
                        html += '<td width="268">' + data.list[i].created_format + '<br/>' + data.list[i].expired_format + '</td>';
                        html += '<td width="147">' + data.list[i].from_type + '</td>';
                        html += '</tr>';
                    }
                    html += '</tbody></table></div>';
                }
            }

            if(status==2){
                html += ' <div>';
                html += '<table border="0" class="tab-thead">';
				html += '<thead >';  
                html += '<tr>';   
                html += '<th width="93">面值</th>';   
                html += ' <th width="98">类型</th>';   
                html += '<th width="116">投资限制</th>';   
                html += '<th width="144">使用时间</th>';
                html += '<th width="144">投资项目</th>';
                html += '<th width="147">红包来源</th>';   
                html += '</tr>';   
                html += ' </thead>'; 
                html += ' </table>';   
                html += '<table width="100%" border="0" class="tab-tbody">';    
                html += '<tbody>';    
                if(data.list.length<=0){
                    html += '<div class="empty-box">没有记录</div>';
                }else{
                    for (var i = 0; i < data.list.length; i++) {
                        html += '<tr>';
                        html += '<td width="93"><span class="color-yellow1" >￥' + data.list[i].money + '</span></td>';
                        html += '<td  width="98">' + data.list[i].bonus_type + '</td>';
                        html += '<td width="116">' + data.list[i].bid_limit + '</td>';
                        html += '<td width="144">' + data.list[i].use_time_format + '</td>';
                        html += '<td width="144" class="text-left">' + data.list[i].deal_name + '</td>';
                        html += '<td width="147">' + data.list[i].from_type + '</td>';
                        html += '</tr>';
                    }
                    html += '</tbody></table></div>';
                }
            }

            if(status==3){
                html += ' <div>';
                html += '<table border="0" class="tab-thead">';
                html += '<thead>';  
                html += '<tr>';   
                html += '<th width="83">面值</th>';   
                html += ' <th width="98">类型</th>';   
                html += '<th width="116">投资限制</th>';   
                html += ' <th width="288">有效期</th>';   
                html += '<th width="137">红包来源</th>';   
                html += '</tr>';   
                html += ' </thead>';   
                html += ' </table>';   
                html += '<table width="100%" border="0" class="tab-tbody">';    
                html += '<thead>'; 
                html += '<colgroup>';        
                html += '<col width="93"/>';
                html += '<col width="98"/>';
                html += '<col width="116"/>';
                html += '<col width="288"/>';
                html += '<col width="147"/>';
                html += '</colgroup>';
                html += '</thead>';
                html += '<tbody>';    

                if(data.list.length<=0){
                    html += '<div class="empty-box">没有记录</div>';
                }else{
                    for (var i = 0; i < data.list.length; i++) {
                        html += '<tr>';
                        html += '<td width="93">￥' + data.list[i].money + '</td>';
                        html += '<td width="98">' + data.list[i].bonus_type + '</td>';
                        html += '<td width="116">' + data.list[i].bid_limit + '</td>';
                        html += '<td width="288">' + data.list[i].created_format + '<br/>' + data.list[i].expired_format + '</td>';
                        html += '<td width="147">' + data.list[i].from_type + '</td>';
                        html += '</tr>';
                    }
                    html += '</tbody></table></div>';
                }
            }

            $('#tabs_content').html(html);
        }

        function sendHtml(data) {
            var html = '';
            if(data.list.length<=0){
                html = '<tr><td colspan="6" class="bot0"><div class="empty-box">没有记录</div></td></tr>';
            }else{
                for (var i = 0; i < data.list.length; i++) {
                    html += '<tr>';
                    html += '<td><div class="tl pl30">' + data.list[i].bonus_from + '<br />'+ data.list[i].bonus_name +'</div></td>';
                    html += '<td>' + data.list[i].count + '个</td>';
                    html += '<td><span class="color-yellow1">￥' + data.list[i].money + '</span></td>';
                    html += '<td>' + data.list[i].created_at_time + '<br/>' + data.list[i].expired_at_time + '</td>';
                    html += '<td>领取' + data.list[i].send_num + '个使用' + data.list[i].use_num + '个</td>';
                    if(data.list[i].can_send_again){
                        html += '<td><a href="javascript:void(0)" class="but-blue j-qcode" code="'+data.list[i].id_encrypt+'">发红包</a></td>';
                    }else{
                        html += '<td><a class="btn-gray">发红包</a></td>';
                    }
                    html += '</tr>';
                }
            }
            $('.red-send tbody').html(html);
            
            $('.j-qcode').off('click').click(function(){
                dialog({
                    title: '发送红包',
                    //加上随机数时，每次点击“发红包”二维码都会变化。去掉随机数，只在第一次比较慢。
                    content: '<p class="img_loading" align="center"><img src="/deal/bonusQrcode?code='+$(this).attr('code')+'" alt="qrcode" width="220" height="220"/></p><br/><p>打开微信，点击底部的“发现”，使用 “扫一扫” 即可将当前红包的二维码发送到微信。</p>',
                    fixed: true,
                    cancelDisplay:false,
                    okDisplay:false,
                    width:'360px',
                    height:'290px'
                }).show();
            })
        }
        // 初始数据
        reqData('own', 1, 1, '#pagination_00');
        reqData('send', 1, 1, '#pagination_03');
    });
})(jQuery)
