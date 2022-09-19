<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>还款凭证补录-</title>
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
                            <th>待还本金</th>
                            <th>本金还款金额</th>
                            <th>本金还款状态</th>
                            <th>本金还款方式</th>
                            <th>线下还款金额</th>
                            <th>待还利息</th>
                            <th>利息还款金额</th>
                            <th>利息还款状态</th>
                            <th>利息还款方式</th>
                            <th>应还款时间</th>
                            <th>实际还款时间</th>
                            <{if $is_from_clear == 0}>
                                <th style="width: 60px;">操作</th>
                            <{/if}>
                          
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
                                <td><{$val['interest']}></td>
                                <td><{$val['paid_interest']}></td>
                                <td><{$val['is_repay_interest']}></td>
                                <td><{$val['interest_repay_type_cn']}></td>
                                <td><{$val['repay_time']}></td>
                                <td><{$val['true_repay_time']}></td>
                                <{if $is_from_clear == 0}>
                                    <{if $val['is_repay_principal'] == '未还' && !$val['reply_slip_id'] && !$is_show_audit}>
                                        <td> <button class="layui-btn layui-btn-danger" onclick="add_voucher(<{$val['id']}>)" lay-submit="" >凭证补录</button> </td>
                                    <{else if $is_show_audit &&  $val['reply_slip_id'] }>
                                        <{if $val['reply_slip_status'] == 0}>
                                        <td> <button class="layui-btn layui-btn-danger" onclick="audit_voucher(<{$val['id']}>)" lay-submit="" >审核</button> </td>
                                        <{else}>
                                        <!-- <td> <button class="layui-btn layui-btn-danger" onclick="audit_voucher(<{$val['id']}>)" lay-submit="" >详情</button> </td> -->
                                            <td> 
                                                <a href="<{$val['reply_slip']}>" style="color: blue; margin-right: 5px;" >查看凭证</a>
                                                <!-- <button class="layui-btn layui-btn-danger" onclick="">查看凭证</button>  -->
                                            </td>
                                        <{/if}>
                                    <{else}>
                                        <td></td>
                                    <{/if}>
                                 <{/if}>          
                            </tr>
                            <{/foreach}>
                        </tbody>
                    </table>
                </div>
            </div>

            <{if $add_voucher == 1}>
            <div class="layui-card" >
                <div class="layui-card-body res_div" style="padding: 0px 0px; margin:0px 0px;" >
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">还款凭证录入<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" method="post" action="/borrower/DealOrder/AddNewVoucher" id="my_form" enctype="multipart/form-data">
                                    <div class="layui-form-item">
                                        <label class="layui-form-label">
                                            <span class="x-red">*</span>请选择还款期数</label>
                                        <div class="layui-input-inline" id="repay_ids_radio">
                                            <{foreach $repayPlan as $key => $val}>
                                                <{if $val['repay_slip_status'] == 1  && $val['is_repay_principal'] == '未还' }>
                                                <input type="checkbox"   name="repay_ids[]" value='<{$val['id']}>' title='期数：<{$val['repay_num']}>    应还款时间:<{$val['repay_time']}>' lay-filter="repay_ids">
                                                <{/if}>
                                            <{/foreach}>
                                        </div>
                                    </div>

                                    <div class="layui-form-item">
                                        <label class="layui-form-label">
                                            <span class="x-red">*</span>凭证中还款日期
                                        </label>
                                        <div class="layui-input-inline">
                                            <input class="layui-input" name="repay_date"   lay-verify="date" autocomplete="off" id="repay_date" >
                                        </div>
                                    </div>

                                    <div class="layui-form-item" id="file_path_div"  >
                                        <label for="pay_user" class="layui-form-label">
                                            <span class="x-red">*</span>客户付款凭证</label>
                                        <div class="layui-input-inline" style="  margin-top: 10px;">
                                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_template()">上传</button>
                                            <span id="template_name"></span>
                                            <input type="file" id="file_path" name="file_path" autocomplete="off" class="layui-input" style="display: none;" onchange="change_template(this.value)">
                                        </div>
                                    </div>
                                    <input type="hidden" id='deal_id' name="deal_id" value="<{$_GET['deal_id']}>" >
                                    <div class="layui-form-item">
                                        <label for="L_repass" class="layui-form-label"></label>
                                        <button type="button" class="layui-btn"  onclick="do_add()">保存</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <{/if}>

        </div>
    </div>
</div>
</body>
<script>
    layui.use(['laydate', 'table', 'layer', 'form'], function () {
        $ = layui.jquery;
        var laydate = layui.laydate;
        var form = layui.form;
        var table = layui.table;
        laydate.render({
            elem: '#repay_date'
        });
        
    });

    function view_reply_slip(reply_slip){
        console.log(reply_slip,9999);
        window.location.href = reply_slip;
        return false;
    }
    function add_voucher(repay_id){
        console.log(repay_id,9999);
        xadmin.open('凭证补录', '/borrower/DealOrder/addVoucher?repay_id='+repay_id);
        return false;
    }

    function audit_voucher(repay_id){
        
        xadmin.open('审核凭证', '/borrower/DealOrder/auditVoucher?repay_id='+repay_id,900,400);
        return false;
    }

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
    function add_template() {
        $("#file_path").click();
    }
    function change_template(name) {
        var string   = name.lastIndexOf("\\");
        var new_name = name.substring(string+1);
        $("#template_name").html(new_name);
    }

    function do_add() {
        //var repay_ids = $(".repay_ids:checked").val();
        var repay_ids = [];
        $("input[name='repay_ids']:checked").each(function(){
            repay_ids.push($(this).val)
        });
        var file_path = $("#file_path").val();
        var repay_date = $("#repay_date").val();
        var deal_id = $("#deal_id").val();
        if (!repay_ids  || file_path==''  || deal_id=='' || repay_date=='' ) {
            layer.msg('必选项不能为空' , {icon:2 , time:2000});
        } else{
            $("#my_form").submit();
        }
    }


</script>

<script type="text/html" id="operate">
    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
</script>
</html>