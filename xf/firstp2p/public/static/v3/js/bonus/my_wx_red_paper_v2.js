(function($) {
    $(function() {
        var inforStr = '<div style="text-align:center;margin:30px 0 30px;line-height:25px;color:#888;font-size:16px;">仅显示最近30天内的'+ new_bonus_title +'</div>';
        var inforStr1 = '<div style="text-align:center;margin:30px 0 30px;line-height:25px;color:#888;font-size:16px;">仅显示最近30天内的'+ new_bonus_title +'</div>';
        // tab事件绑定
        $('#tabs li a').click(function() {
            var $that = $(this),
                _type = $that.attr('data-red');
            // if ($that.parent('li').hasClass('select')) return;
            $that.closest('li').hide().siblings().show();
            reqData(_type, 1, '#pagination_00');
        });
        // ajax请求
        function reqData(type, page, pageSelector) {
            var last = false;
            $.ajax({
                url: '/account/bonusAsync',
                type: 'GET',
                data: {
                    p: page,
                    type: type
                },
                success: function(data) {
                    if (typeof data=='string') data = $.parseJSON(data);
                    if(page == data.pagecount){
                        last = true;
                    }

                    if (type == 'log'){
                        ownHtml(data,last);
                    }else if (type == 'send'){
                        sendHtml(data, last);
                    }

                    if(data.pagecount <= 0){
                        $(pageSelector).hide();
                        return;
                    }else{
                        $(pageSelector).show();
                        Firstp2p.paginate($(pageSelector), {
                            pages: data.pagecount,
                            currentPage: page,
                            displayedPages:3,
                            onPageClick: function(pageNumber, $obj) {
                                reqData(type, pageNumber, pageSelector);
                            }
                        });
                        //分页结构二次加工
                        if(page==1){
                            indexPage=$('<li><span class="index" title="首页">首页</span></li>');
                        }else{
                            indexPage=$('<li><a href="#page=1" class="page-link index" title="首页">首页</a></li>');
                        }
                        if(page==data.pagecount){
                            lastPage=$('<li><span class="last" title="尾页">尾页</span></li>');
                        }else{
                            lastPage=$('<li><a href="#page='+data.pagecount+'" class="page-link last" title="尾页">尾页</a></li>');
                        }
                        pageText = '<li><span class="total">共<i>'+data.pagecount+'</i>页</span></li>';
                        $(pageSelector).find("ul").append(lastPage,pageText).prepend(indexPage);
                        $(pageSelector).find('a.last,a.index').on('click',function (event) {
                            event.preventDefault();
                            var page_number=$(this).attr('href').match(/.*#page=(\d*)$/)[1];
                            reqData(type, page_number, pageSelector);
                        });
                    }
                },
                error: function() {}
            })
        }
        //千位分隔符
        function _thousandBitSeparator(num) {
          return num && (num.toString().indexOf('.') != -1 ? num.toString().replace(/(\d)(?=(\d{3})+\.)/g, function($0, $1) {
              return $1 + ",";
            }) : num.toString().replace(/(\d)(?=(\d{3}))/g, function($0, $1) {
              return $1 + ",";
            }));
        }
        // 我的红包数据拼接
        function ownHtml(data,last) {
            $('#tabs_content').empty();
            var get_unused_money = _thousandBitSeparator(data.bonus_user.get_unused_money),
                get_used_money = _thousandBitSeparator(data.bonus_user.get_used_money);
            var html = '';
            $('#receBonus').html(inforStr);
            $('#j_share_num').html(data.share_count);
            if(data.share_count > 0){
                $('#j_daifx').show();
            } else {
                $('#j_daifx').hide();
            }
            html += ' <div class="tab_cont">';
            html += '     <div class="sy_title">';
            html += '         <a href="javascript:void(0);">我的'+ new_bonus_title;
            if(new_bonus_unit){
            html += '         ('+ new_bonus_unit +')';
            }
            html += '         </a>';
            html += '         <p class="zs">' + data.bonus_user.usableMoney + '</p>';
            html += '     </div>';
            html += '     <div class="lq_title">';
            html += '         <a href="javascript:void(0);">累计使用';
            if(new_bonus_unit){
            html += '         ('+ new_bonus_unit +')';
            }
            html += '         </a>';
            html += '         <p class="zs">' + data.bonus_user.usedMoney + '</p>';
            html += '     </div>';
            html += ' </div>';
            html += ' <div class="jjgq clearfix">';
            if(data.bonus_user.expireSoon != null){
                html += ' <div class="fl">'+ data.bonus_user.expireSoon.expireDate +'&nbsp;即将过期：<span class="color_red">'+ data.bonus_user.expireSoon.money + new_bonus_unit +'</span></div>';
            }
            html += ' </div>';

            if(data.list.length<=0){
                html += '<div class="empty-box">'+ inforStr1 +'</div>';
            }else{
                html += ' <table width="100%" border="0" class="my_bonus">';
                html += '   <thead>';
                html += '       <tr>';
                html += '           <th width="223">来源/时间</th>';
                html += '           <th width="372">收/支</th>';
                html += '           <th width="225">备注</th>';
                html += '       </tr>';
                html += '   </thead>';
                html += '   <tbody>';
                for (var i = 0; i < data.list.length; i++) {
                    if(i%2 == 0){
                        html += '       <tr class="gray_bg">';
                    } else {
                        html += '       <tr>';
                    }
                    html += '           <td><span class="tit">' + data.list[i].title + '</span>' + data.list[i].createTime + '</td>';
                    if(data.list[i].status == 1){
                        html +='           <td class="color_red f24">+';
                    } else if(data.list[i].status == 2 ||data.list[i].status == 3){
                        html +='           <td class="color_green f24">-';
                    }
                    html += data.list[i].money +'</td>';
                    if (data.list[i].status == 3) {
                        html += '           <td>-</td>';
                    } else {
                        html += '           <td>' + data.list[i].info + '</td>';
                    }
                    html += '       </tr>';
                }
                html += '</tbody></table>';
                $('#receBonus').html(inforStr1);
            }
            if(!!last){
                $('#receBonus').show();
                $('#sendBonus').hide();
            }else{
                 $('#receBonus').hide();
                 $('#sendBonus').hide();
            }
            $('#tabs_content').html(html);
        }
        // 分享列表数据拼接
        function sendHtml(data,last) {
            $('#tabs_content').empty();
            $('#sendBonus').html(inforStr);
            var html = '';
            if(data.list.length<=0){
                html += '<div class="empty-box">'+ inforStr +'</div>';
            }else{
                html += ' <table width="100%" border="0" class="red-send zj_tab newRedTb">';
                html += '   <thead>';
                html += '   </thead>';
                html += '   <tbody>';
                for (var i = 0; i < data.list.length; i++) {
                    html += '<tr>';
                    if(data.list[i].send_status==1) {
                        html += '   <td class="redbg"><div class="mt20">' + data.list[i].count + '<span class="dwy">个</span></div></td>';
                    } else {
                        html += '   <td class="redbg graybg"><div class="mt20">' + data.list[i].count + '<span class="dwy">个</span></div></td>';
                    }

                    html += '   <td class="text-left redmess">';
                    html += '       <p class="lx"><span class="t_red">' + data.list[i].bonus_from + '</span>';
                    if(data.list[i].send_status==2){
                        html += data.list[i].send_num + '个已全部被领取</p>';
                    } else {
                        html += data.list[i].send_num + '个已被领取，剩余' + (data.list[i].count-data.list[i].send_num) + '个</p>';
                    }
                    html += '       <p class="yx_time">有效期至：' + data.list[i].expired_at_time + '    领取时间：' + data.list[i].created_at_time + '</p>';
                    html += '   </td>';
                    if(data.list[i].send_status==1) {
                        html += '   <td><a href="javascript:void(0)" class="useBtn j-qcode" code="'+data.list[i].sn+'" is_n="' + data.list[i].isNew + '">去分享</a></td>';
                    } else if(data.list[i].send_status==2) {
                        html += '   <td>发光了</td>';
                    } else {
                        html += '   <td>已过期</td>';
                    }
                    html += '</tr>';
                }
                html += '</tbody></table>';
                $('#sendBonus').html(inforStr);
            }
            $('#tabs_content').html(html);
            if(!!last){
                $('#sendBonus').show();
                $('#receBonus').hide();
            }else{
                 $('#sendBonus').hide();
                 $('#receBonus').hide();
            }
            $('.j-qcode').off('click').click(function(){
                dialog({
                    title: '发送'+ new_bonus_title,
                    //加上随机数时，每次点击“去分享”二维码都会变化。去掉随机数，只在第一次比较慢。
                    content: '<p class="img_loading" align="center"><img src="/deal/bonusQrcode?is_n=' + $(this).attr('is_n') + '&code='+$(this).attr('code')+'" alt="qrcode" width="220" height="220"/></p><br/><p>打开微信，点击底部的“发现”，使用 “扫一扫” 即可将当前'+ new_bonus_title +'的二维码发送到微信。</p>',
                    fixed: true,
                    cancelDisplay:false,
                    okDisplay:false,
                    width:'360px',
                    height:'290px'
                }).show();
            })
        }
        // 初始数据
        reqData('log', 1, '#pagination_00');
        // reqData('send', 1, '#pagination_03');
    });
})(jQuery)
