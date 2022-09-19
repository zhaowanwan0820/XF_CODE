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
                <div class="layui-card-header">原借款人信息</div>
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
                            <span style="font-size: 13px;">借款时间：<{$dealInfo['o_create_time']}></span>
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
                            <li data-status="0" class="layui-this" style="font-size: 14px;" id='repay_log' onclick="repayLogShow()">原还款计划</li>
                            <!-- <li data-status="1" id='modify_log' style="font-size: 14px;" onclick="modifyLogShow()">修改记录</li> -->
                            
                        </ul>
                    </div>
                    <table class="layui-table layui-form" id="repayLogTable">
                        <thead>
                        <tr>
                            <th>期数</th>
                            <th>原待还本金</th>
                            <th>原待还利息</th>
                            <!-- <th>修改后待还本金</th>
                            <th>修改后待还利息</th> -->
                            <th>应还款时间</th>
                            <th>实际还款时间</th>
                            <th>还款状态</th>
                            <th>操作</th>
                        </thead>
                        <tbody>
                        <{foreach $repayPlan as $key => $val}>
                            <tr>
                                <td><{$val['repay_num']}></td>
                                <td><{$val['principal']}></td>
                                <td><{$val['interest']}></td>
                                <!-- <td><{$val['new_principal']}></td>
                                <td><{$val['new_interest']}></td> -->
                                <td><{$val['repay_time']}></td>
                                <td><{$val['true_repay_time']}></td>
                                <td><{$val['is_repay']}></td>
                                <!-- <td class="td-manage">
                                    <{if $val['is_repay'] == '未还' &&  $dealInfo['repay_auth_flag'] != 2}>
                                        <a title="编辑"  onclick="xadmin.open('编辑','/borrower/DealOrder/editRepayPlan?id=<{$val['id']}>',600,500)" href="javascript:;">
                                            <button class="layui-btn">修改</button>
                                        </a>
                                    <{/if}>
                                </td> -->
                             
                            </tr>
                            <{/foreach}>
                        </tbody>
                    </table>
                    <table class="layui-table layui-form hidden" id="modifyLogTable" style="display: none;">
                        <thead>
                        <tr>
                            <th>修改时间</th>
                            <th>修改期数</th>
                            <th>修改内容</th>
                            <th>修改人</th>
                        </thead>
                        <tbody>
                        <{foreach $modifyLog as $key => $val}>
                            <tr>
                                <td><{$val['add_time']}></td>
                                <td><{$val['repay_num']}></td>
                                <td><{$val['remark']}></td>
                                <td><{$val['add_user_name']}></td>
                            </tr>
                            <{/foreach}>
                        </tbody>
                    </table>
                </div>
            </div>
<!-- 
            <div class="layui-card">
                <div class="layui-card-header">修改记录</div>

                <div class="layui-card-body ">
                    <table class="layui-table layui-form" id="myTable">
                        <thead>
                        <tr>
                            <th>修改时间</th>
                            <th>修改期数</th>
                            <th>修改内容</th>
                            <th>修改人</th>
                        </thead>
                        <tbody>
                        <{foreach $modifyLog as $key => $val}>
                            <tr>
                                <td><{$val['add_time']}></td>
                                <td><{$val['repay_num']}></td>
                                <td><{$val['remark']}></td>
                                <td><{$val['add_user_name']}></td>
                            </tr>
                            <{/foreach}>
                        </tbody>
                    </table>
                </div>
            </div> -->

            <div >
                <!-- <div style="width: 100%;height: 30px;text-align:center;">
                    <{if $dealInfo['repay_auth_flag'] == 0 }>
                        <button style="width: 80px;" class="layui-btn" onclick="addAuth(<{$dealInfo['id']}>)"  lay-submit="">提交审核</button>
                    <{/if}>
                    <{if $dealInfo['repay_auth_flag'] == 1 && $auth_type==1}>
                        <button style="width: 80px;" class="layui-btn" onclick="authDeal(2,<{$dealInfo['id']}>)"  lay-submit="">审核通过</button>
                        <button style="width: 80px;" class="layui-btn" onclick="authDeal(3,<{$dealInfo['id']}>)"  lay-submit="">拒绝</button>
                    <{/if}>
                    <{if $dealInfo['repay_auth_flag'] == 3}>
                        <button style="width: 80px;" class="layui-btn" onclick="addAuth(1,<{$dealInfo['id']}>)"  lay-submit="">提交审核</button>
                    <{/if}>
                   
                </div> -->
            </div>
        </div>
    </div>
</div>
</body>
<script>
    layui.use(['laydate', 'table', 'layer', 'form'], function () {

       
    });
    function addAuth(deal_id) {
        
        layer.confirm('确认提交审核吗？', function (index) {
            $.ajax({
                url: '/borrower/DealOrder/addAuth',
                data: {id:deal_id},
                type: "POST",
                dataType:'json',
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

    function authDeal(params,deal_id) {
        if(params==1){
            var title = '确认提交审核吗？'
        }else if(params ==2){
            var title = '确认审核通过吗？'
        }else{
            var title = '确认拒绝通过吗？'
        }
        layer.confirm(title, function (index) {
            $.ajax({
                url: '/borrower/DealOrder/authDeal',
                data: {id:deal_id,type:params},
                type: "POST",
                dataType:'json',
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

    function resetSearch() {
        $("#user_id").val("");
        $("#mobile").val("");
    }

    function repayLogShow(params) {
        $('#modifyLogTable').css('display','none');
        $('#repayLogTable').css('display','table');
    }
    function modifyLogShow(params) {
        $('#modifyLogTable').css('display','table');
        $('#repayLogTable').css('display','none');
    }

</script>

<script type="text/html" id="operate">

    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
</script>
</html>