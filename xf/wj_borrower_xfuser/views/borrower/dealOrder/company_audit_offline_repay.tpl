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
                <div class="layui-card-header">借款人信息</div>
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
                <div class="layui-card-header">标的信息</div>
                <div class="layui-card-body ">
                    <div class="layui-row">
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款金额：<{$dealInfo['loan_amount']}>元</span>
                        </div>
                        <div class="layui-col-md4">
                            <span class="x-red">*</span> <span style="font-size: 13px;">待还本金和：<{$dealInfo['principal']}>元</span>
                        </div>
                        <div class="layui-col-md4">
                            <span class="x-red">*</span><span style="font-size: 13px;">待还利息和：<{$dealInfo['interest']}>元</span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">借款时间：<{$dealInfo['d_create_time']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">核心担保企业：<{$dealInfo['agency_name']}></span>
                        </div>
                        <!-- <div class="layui-col-md4">
                            <span class="x-red">*</span><span style="font-size: 13px;">调整后待还本金和：<{$dealInfo['new_principal']}>元</span>
                        </div>
                        <div class="layui-col-md4">
                            <span class="x-red">*</span> <span style="font-size: 13px;">调整后待还利息和：<{$dealInfo['new_interest']}>元</span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">咨询方：<{$dealInfo['organization_name']}></span>
                        </div>
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">待还咨询费：<{$dealInfo['consult_fee']}>元</span>
                        </div> -->
                      </div>
                </div>
            </div>
            <div class="layui-card">
                <form class="layui-form"  id="my_form">
                    <div class="layui-card-header">
                        还款信息                
                    </div>
                    <div class="layui-form-item" id="use_repay_amount">
                        <label for="name" class="layui-form-label">还款金额</label>
                        <div class="layui-input-inline">
                            <span for="name" class="layui-form-label"><{$gyl_offline_repay['repay_capital']}> 元</span>
                        </div>
                    </div>
                    <!--div class="layui-form-item" id="use_repay_amount">
                        <label for="name" class="layui-form-label">还款利息</label>
                        <div class="layui-input-inline">
                            <span for="name" class="layui-form-label"><{$gyl_offline_repay['repay_interest']}> 元</span>
                        </div>
                    </div-->
                    <div class="layui-form-item">
                        <label class="layui-form-label">还款时间</label>
                        <div class="layui-input-inline">
                            <span for="name" class="layui-form-label"><{$gyl_offline_repay['repay_time']}></span>
                        </div>
                    </div>
                    <!--div class="layui-form-item">
                        <label class="layui-form-label">还款内容</label>
                        <div class="layui-input-inline" style="width: 400px;">
                            <span for="name" class="layui-form-label"><{$gyl_offline_repay['repay_content']}></span>
                        </div>
                    </div-->
                    <div class="layui-form-item">
                        <label class="layui-form-label">还款凭证</label>
                        <div class="layui-input-inline">
                            <a class="layui-form-label" style="color: blue;" href="<{$gyl_offline_repay['reply_slip']}>" target="_blank">点击查看></a>
                        </div>
                      </div>
                <div class="layui-row layui-col-space10">
                    <div class="layui-col-md4">
                  
                    </div>
                    <div class="layui-col-md4">
                        <div class="layui-form-item">
                            <label for="L_repass" class="layui-form-label"></label>
                            <{if $gyl_offline_repay['status'] == 0 }>
                            <button  type="button" class="layui-btn" onclick="submit_audit(<{$gyl_offline_repay['id']}>)" >审核通过</button>
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
        layer.confirm('确认要审核通过吗？', function (index) {
            $.ajax({
                url: '/borrower/dealOrder/DoCompanyAuditOfflineRepay',
                data: {id:offline_repay_id},
                type: "POST",
                success: function (res) {
                    if (res.code == 0) {
                        layer.msg(res.info,{time:1000,icon:1},function(){
                            location.reload();
                        });

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