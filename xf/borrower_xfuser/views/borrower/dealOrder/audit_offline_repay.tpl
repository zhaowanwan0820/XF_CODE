<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>欢迎页面-</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <!--datatables-->
    <link rel="stylesheet" href="<{$CONST.cssPath}>/jquery.dataTables.min.css">
    <script src="<{$CONST.jsPath}>/jquery-2.1.4.min.js"></script>
    <script src="<{$CONST.jsPath}>/jquery.dataTables.min.js"></script>
</head>

<body>

<div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
            
            <div class="layui-card">
                <div class="layui-card-header">零售系统导出借款人信息</div>
                <div class="layui-card-body">
                    <div class="layui-row">
                        <div class="layui-col-md4">
                           <span style="font-size: 13px;">借款人姓名：<{$userInfo['customer_name']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款人手机号：<{$userInfo['phone']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款人证件号：<{$userInfo['id_number']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款人银行卡号：<{$userInfo['card_number']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">所属银行：<{$userInfo['bank_name']}></span>
                        </div>
                      </div>
                </div>
            </div>

               
            <div class="layui-card">
                <div class="layui-card-header">通过出借人关联出的借款人信息</div>
                <div class="layui-card-body">
                    <div class="layui-row">
                        <div class="layui-col-md4">
                           <span style="font-size: 13px;">借款人姓名：<{$firstp2pUserInfo['real_name']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款人手机号：<{$firstp2pUserInfo['mobile']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款人证件号：<{$firstp2pUserInfo['idno']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款人银行卡号：<{$firstp2pUserInfo['card_number']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">所属银行：<{$firstp2pUserInfo['bank_name']}></span>
                        </div>
                      </div>
                </div>
            </div>
            <div class="layui-card">
                <div class="layui-card-header">原标的信息</div>
                <div class="layui-card-body ">
                    <div class="layui-row">
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款金额：<{$dealInfo['loan_amount']}>元</span>
                        </div>
                        <div class="layui-col-md4">
                            <span class="x-red">*</span> <span style="font-size: 13px;">原待还本金和：<{$dealInfo['principal']}>元</span>
                        </div>
                        <div class="layui-col-md4">
                            <span class="x-red">*</span><span style="font-size: 13px;">原待还利息和：<{$dealInfo['interest']}>元</span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款时间：<{$dealInfo['d_create_time']}></span>
                        </div>
                        <!-- <div class="layui-col-md4">
                            <span class="x-red">*</span><span style="font-size: 13px;">调整后待还本金和：<{$dealInfo['new_principal']}>元</span>
                        </div>
                        <div class="layui-col-md4">
                            <span class="x-red">*</span> <span style="font-size: 13px;">调整后待还利息和：<{$dealInfo['new_interest']}>元</span>
                        </div> -->
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">咨询方：<{$dealInfo['organization_name']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">待还咨询费：<{$dealInfo['consult_fee']}>元</span>
                        </div>
                      </div>
                </div>
            </div>
            <div class="layui-card">
                <!-- <div class="layui-card-header">原还款计划</div> -->

                <div class="layui-card-body ">
                    <div class="layui-tab layui-tab-brief" lay-filter="table-all">
                        <ul class="layui-tab-title">
                            <li data-status="0" class="layui-this" style="font-size: 14px;" id='repay_log' onclick="repayLogShow()">本次还款期数</li>
                            <!-- <li data-status="1" id='modify_log' style="font-size: 14px;" onclick="modifyLogShow()">修改记录</li> -->
                            
                        </ul>
                    </div>
                    <table class="layui-table layui-form" id="repayLogTable">
                        <thead>
                        <tr>
                           
                            <th>期数</th>
                            <th>原待还本金</th>
                            <th>本金还款金额</th>
                            <th>本金还款状态</th>
                            <th>本金还款方式</th>
                            <th>线下还款金额</th>
                            <th>本金退款状态</th>
                            <th>原待还利息</th>
                            <th>利息还款金额</th>
                            <th>利息还款状态</th>
                            <th>利息还款方式</th>
                            <th>利息退款状态</th>
                            <th>应还款时间</th>
                            <th>实际还款时间</th>
                            <th>退款时间</th>
                            <th></th>
                        </thead>
                        <tbody>
                        <{foreach $repayPlan as $key => $val}>
                       
                            

                            <tr>
                                <td><{$val['repay_num']}></td>
                                <td><{$val['principal']}></td>
                                <td><{$val['paid_principal']}></td>
                                <td><{$val['is_repay_principal']}></td>
                                <td><{$val['principal_repay_type_cn']}></td>
                                <td><{$val['offline_repay_amount']}></td>
                                <td><{$val['principal_refund_status_cn']}></td>
                                <td><{$val['interest']}></td>
                                <td><{$val['paid_interest']}></td>
                                <td><{$val['is_repay_interest']}></td>
                                <td><{$val['interest_repay_type_cn']}></td>
                                <td><{$val['interest_refund_status_cn']}></td>
                                <td><{$val['repay_time']}></td>
                                <td><{$val['true_repay_time']}></td>
                                <td><{$val['principal_refund_time']}></td>
                                <{if $val['is_repay_principal'] == '已还' && $val['is_repay_interest'] == '已还'}>
                                <td></td>
                                <{else}>
                                <td> 
                                    <{if $val['is_selected'] == 1}>
                                    <input name= "checkbox" class="repay_ids" type="checkbox" checked ="checked" disabled  value="<{$val['id']}>" lay-skin="primary">
                                    <{/if}>
                                    <{if $val['is_selected'] == 0}>
                                    <input name= "checkbox" class="repay_ids" type="checkbox" disabled  value="<{$val['id']}>" lay-skin="primary">
                                    <{/if}>
                                </td>

                                <{/if}>
                            </tr>
                       
                            <{/foreach}>
                        </tbody>
                    </table>

                </div>
            </div>
            <div class="layui-card">
                <form class="layui-form"  id="my_form">
                    <div class="layui-card-header">
                        还款信息                
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">还款方式</label>
                        <div class="layui-input-inline" style="width: 400px;">
                            <span for="name" class="layui-form-label"><{$offline_repay_info['repay_type_cn']}></span>

                        </div>
                    </div>
                    <div class="layui-form-item" id="use_repay_amount">
                        <label for="name" class="layui-form-label">还款金额</label>
                        <div class="layui-input-inline">
                            <span for="name" class="layui-form-label"><{$offline_repay_info['repay_amount']}> 元</span>
                        </div>
                       
                    </div>
                    <div class="layui-form-item" id="use_discount">
                        <label for="name" class="layui-form-label">还款折扣</label>
                        <div class="layui-input-inline">
                            <span for="name" class="layui-form-label"><{$offline_repay_info['repay_discount']}> 折</span>

                        </div>
                      
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">还款内容</label>
                        <div class="layui-input-inline" style="width: 400px;">
                            <span for="name" class="layui-form-label"><{$offline_repay_info['repay_content_cn']}></span>

                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">还款时间</label>
                        <div class="layui-input-inline">
                            <span for="name" class="layui-form-label"><{$offline_repay_info['repay_time']}></span>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">付款凭证</label>
                        
                        <div class="layui-input-inline">
                            <!-- <img class="layui-upload-img" id="logo_url" style="height:100px" src="<{$offline_repay_info['reply_slip']}>"> -->
                            <a class="layui-form-label" style="color: blue;" href="<{$offline_repay_info['reply_slip']}>" target="_blank">点击查看></a>

                        </div>

                      </div>

                
                    
                <div class="layui-row layui-col-space10">
                    <div class="layui-col-md4">
                  
                    </div>
                    <div class="layui-col-md4">
                        <div class="layui-form-item">
                            <label for="L_repass" class="layui-form-label"></label>
                            <{if $offline_repay_info['status'] == 0 }>
                            <button  type="button" class="layui-btn" onclick="submit_audit(<{$offline_repay_info['id']}>)" >审核通过</button>
                            <{/if}>
                        </div>
                    </div>
                    <div class="layui-col-md4">
                     
                    </div>
                  </div>
               
            </form>
            </div>
        </div>
    </div>
</div>
</body>
<script>

    
layui.use(['laydate', 'table', 'layer', 'form','upload'], function () {
        var laydate = layui.laydate;
        $ = layui.jquery;
    var form = layui.form;
    var layer = layui.layer;
    var upload = layui.upload;
    //自定义验证规则
    var element = layui.element;
        

    
          
        
        
});
function submit_audit(offline_repay_id) {
        layer.confirm('确认要通过吗？', function (index) {
            $.ajax({
                url: '/borrower/dealOrder/DoAuditOfflineRepay',
                data: {id:offline_repay_id},
                type: "POST",
                success: function (res) {
                    if (res.code == 0) {
                        layer.alert(res.info);
                        location.reload()
                    } else {
                        layer.alert(res.info);
                    }
                }
            });
        })
    }







</script>

<script type="text/html" id="operate">

    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
</script>
</html>