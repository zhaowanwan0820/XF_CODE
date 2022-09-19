<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>法诉状态维护</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script type="text/javascript" src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <style type="text/css">
        .layui-form-label {
            width: 190px
        }
    </style>
    <!-- 让IE8/9支持媒体查询，从而兼容栅格 -->
    <!--[if lt IE 9]>
    <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
    <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
    <![endif]--></head>

<body>
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body ">
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">借款人信息<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                    <div class="layui-form-item">
                                        <div class="layui-inline">
                                            <label class="layui-form-label" style="width: 80px;">用户ID：</label>
                                            <div class="layui-input-inline">
                                                <div id="countNum" style="margin-top: 8px;"><{$user_id}></div>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label" style="width: 80px;">借款方名称：</label>
                                            <div class="layui-input-inline">
                                                <div id="countNum" style="margin-top: 8px;"><{$real_name}></div>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label" style="width: 80px;">联系电话：</label>
                                            <div class="layui-input-inline">
                                                <div id="total_loan_amount" style="margin-top: 8px;"><{$mobile}></div>
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label" style="width: 80px;">统一识别码：</label>
                                            <div class="layui-input-inline">
                                                <div id="countNum" style="margin-top: 8px;"><{$idno}></div>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="layui-card-body res_div"  >
                    <table class="layui-table layui-form" lay-filter="list" id="list">
                    </table>
                </div>

                <div class="layui-card-body res_div"  >
                    <table class="layui-table layui-form" lay-filter="list" id="list_1">
                    </table>
                </div>

                <div class="layui-card-body res_div"  >
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">新增维护记录<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" method="post" action="/borrower/dealOrder/AddCompanyCallLog" id="my_form" enctype="multipart/form-data">

                                <div class="layui-form-item" id="question_1_2_div"  >
                                        <label class="layui-form-label"> 当前法催状态</label>
                                        <div class="layui-input-inline" style="width: 500px; margin-top: 10px;">
                                              <{$old_legal_status}>
                                        </div>
                                    </div>

                                    <div class="layui-form-item">
                                        <label class="layui-form-label"> <span class="x-red">*</span>法催状态</label>
                                        <div class="layui-input-inline" style="  margin-top: 10px;">
                                            <select name="legal_status" id="legal_status" lay-search="">
                                                <{foreach $legal_status as $key=>$val }>
                                                <option value="<{$key}>"><{$val}></option>
                                                <{/foreach}>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="layui-form-item">
                                        <label for="remark" class="layui-form-label">
                                            <span class="x-red">*</span>情况说明</label>
                                        <div class="layui-input-inline" style="  margin-top: 10px;">
                                            <textarea id="remark" name="remark" class="layui-textarea" style="width: 500px;height: 100px;"></textarea>
                                        </div>
                                    </div>

                                    <div class="layui-form-item" id="file_path_div"  >
                                        <label for="pay_user" class="layui-form-label">
                                            <span class="x-red">*</span>相关文件</label>
                                        <div class="layui-input-inline" style="  margin-top: 10px;">
                                            <button type="button" class="layui-btn layui-btn-normal"  onclick="add_template()">上传</button>
                                            <span id="template_name"></span>
                                            <input type="file" id="file_path" name="file_path" autocomplete="off" class="layui-input" style="display: none;" onchange="change_template(this.value)">
                                        </div>
                                    </div>

                                    <input type="hidden" id='id_type' name="id_type" value="2" >
                                    <input type="hidden" id='user_id' name="user_id" value="<{$_GET['user_id']}>" >
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
        </div>
    </div>
</div>
<script>
    layui.use(['form' , 'layer' , 'table' , 'laydate'] , function(){
        form    = layui.form;
        layer   = layui.layer;
        table   = layui.table;
        laydate = layui.laydate;

        table.on('tool(list)', function (obj) {
            var layEvent = obj.event;
            var data = obj.data;
            if (layEvent === 'detail') {
                xadmin.open('详情', '/borrower/DealOrder/repayPlan?deal_id=' + data.id );
            }
        });


        var join1 = [
            {field: 'add_time', title: '维护时间', width: 150},
            {field: 'legal_status_cn', title: '法催状态', width: 120},
            {field: 'remark', title: '情况说明' },
            {field: 'add_user_name', title: '维护人', width: 150},
            {field: 'legal_files_html', title: '相关文件', width: 150}
        ];

        table.render({
            elem: '#list_1',
            toolbar : '<div>维护记录</div>',
            defaultToolbar: ['filter'],
            page: true,
            limit: 10,
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [join1],
            url: '/borrower/DealOrder/callLog',
            method: 'post',
            where: {user_id:<{$_GET['user_id']}>},
            response:
                {
                    statusName: 'code',
                    statusCode: 0,
                    msgName: 'info',
                    countName: 'countNum',
                    dataName: 'list'
                }
        });

        form.on('radio(question_3)', function(data){
            var val=data.value;
            if (val == 6) {
                $("#other_div").show();
                $("#other_div").val('');
            } else {
                $("#other_div").hide();
            }
        });

    });



//===========================================================
    function add_template() {
        $("#file_path").click();
    }

    function change_template(name) {
        var string   = name.lastIndexOf("\\");
        var new_name = name.substring(string+1);
        $("#template_name").html(new_name);
    }


    function do_add() {
        var file_path = $("#file_path").val();
        var legal_status = $("#legal_status").val();
        var remark = $("#remark").val();
        var user_id = $("#user_id").val();
        if (legal_status=='' || file_path==''  || remark==''  || user_id=='' ) {
            layer.msg('必选项不能为空' , {icon:2 , time:2000});
        } else{
            $("#my_form").submit();
        }
    }
</script>
</body>
<script type="text/html" id="operate">
    <button class="layui-btn" title="详情" lay-event="detail">详情</button>
</script>
</html>