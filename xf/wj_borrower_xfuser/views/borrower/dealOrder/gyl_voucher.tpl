<!DOCTYPE html>
<html class="x-admin-sm">
<head>
    <meta charset="UTF-8">
    <title>还款录入-</title>
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
                        <div class="layui-col-md4">
                            <span style="font-size: 13px;">核心担保企业：<{$dealInfo['agency_name']}></span>
                        </div>
                      </div>
                </div>
            </div>
            <{if $add_voucher == 1}>
            <div class="layui-card" >
                <div class="layui-card-body res_div" style="padding: 0px 0px; margin:0px 0px;" >
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">还款录入<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" method="post" action="/borrower/DealOrder/AddGylVoucher" id="my_form" enctype="multipart/form-data">
                                    <!--div class="layui-form-item">
                                        <label class="layui-form-label"><span class="x-red">*</span>还款内容：</label>
                                        <div class="layui-input-inline" style="width: 400px;">
                                            <input lay-filter="repay_content" type="radio" class="repay_content" name="repay_content" value="1" title="本金" checked>
                                            <input lay-filter="repay_content" type="radio" class="repay_content" name="repay_content" value="2" title="利息">
                                            <input lay-filter="repay_content" type="radio" class="repay_content" name="repay_content" value="3" title="本金+利息">
                                        </div>
                                    </div-->

                                    <div class="layui-form-item">
                                        <label class="layui-form-label">  还款金额：</label>
                                        <div class="layui-input-inline">
                                            <input class="layui-input" type="text" name="repay_capital"  placeholder="元"  autocomplete="off" id="repay_capital" >
                                        </div>
                                    </div>

                                    <!--div class="layui-form-item">
                                        <label class="layui-form-label">  还款利息：</label>
                                        <div class="layui-input-inline">
                                            <input class="layui-input" type="text" name="repay_interest"   placeholder="元"  autocomplete="off" id="repay_interest" >
                                        </div>
                                    </div-->

                                    <div class="layui-form-item">
                                        <label class="layui-form-label"> <span class="x-red">*</span>还款时间：</label>
                                        <div class="layui-input-inline">
                                            <input class="layui-input" name="repay_time"   lay-verify="date" autocomplete="off" id="repay_time" >
                                        </div>
                                    </div>



                                    <div class="layui-form-item" id="file_path_div"  >
                                        <label for="pay_user" class="layui-form-label">
                                            <span class="x-red">*</span>还款凭证：</label>
                                        <div class="layui-input-inline" style="  margin-top: 10px;">
                                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_template()">上传</button>
                                            <span id="template_name"></span>
                                            <input type="file" id="file_path" name="file_path" autocomplete="off" class="layui-input" style="display: none;" onchange="change_template(this.value)">
                                        </div>
                                    </div>
                                    <input type="hidden" id='deal_id' name="deal_id" value="<{$_GET['deal_id']}>" >
                                    <input type="hidden" id='deal_capital' name="deal_capital" value="<{$dealInfo['principal']}>" >
                                    <input type="hidden" id='deal_interest' name="deal_interest" value="<{$dealInfo['interest']}>" >
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
            elem: '#repay_time'
        });
        
    });

    function view_reply_slip(reply_slip){
        console.log(reply_slip,9999);
        window.location.href = reply_slip;
        return false;
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
        //var repay_content = $(".repay_content:checked").val();
        var repay_capital = $("#repay_capital").val();
        //var repay_interest = $("#repay_interest").val();
        var repay_time = $("#repay_time").val();
        var file_path = $("#file_path").val();
        var deal_id = $("#deal_id").val();
        var capital = $("#deal_capital").val();
        var interest = $("#deal_interest").val();
        if ( repay_capital==''|| file_path==''  || deal_id=='' || repay_time==''  ) {
            layer.msg('必选项不能为空' , {icon:2 , time:2000});
        }  else if (!$.isNumeric(repay_capital) && repay_capital !== '' ){
            layer.msg('还款金额请填写数字' , {icon:2 , time:2000});
        }  else if ( repay_capital>(capital+interest)){
            layer.msg('还款金额错误，不可大于待还金额' , {icon:2 , time:2000});
        } else{
            $("#my_form").submit();
        }
    }


</script>

<script type="text/html" id="operate">
    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
</script>
</html>