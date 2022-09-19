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
<div class="x-nav">
    <span class="layui-breadcrumb">
        <a href="">分案管理</a>
        <a>
            <cite>设置电话优惠政策管理</cite>
        </a>
            </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #259ed8"
       onclick="location.reload()" title="刷新">
        <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i></a>
</div>
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <form class="layui-form" method="post" action="/borrower/company/FavouredPolicy" enctype="multipart/form-data" id="my_form">
        <div class="layui-col-md12">
            <div class="layui-card">

                <div class="layui-card-body ">
                </div>
                <div class="layui-card-body ">
                    <table class="layui-table layui-form" lay-filter="list" id="list">
                </div>

                <div class="layui-form-item">
                    <label for="username" class="layui-form-label">本金优惠政策</label>
                    <div class="layui-input-inline">
                        <input type="text" id="capital_policy" name="capital_policy" autocomplete="off" class="layui-input"  value="<{$info['capital_policy']}>" >
                    </div>
                    <div class="layui-form-mid layui-word-aux"> % </div>
                </div>
                <div class="layui-form-item">
                    <label for="username" class="layui-form-label">利息优惠政策</label>
                    <div class="layui-input-inline">
                        <input type="text" id="interest_policy" name="interest_policy" autocomplete="off" class="layui-input"  value="<{$info['interest_policy']}>" >
                    </div>
                    <div class="layui-form-mid layui-word-aux"> % </div>
                </div>
                <div class="layui-form-item">
                    <label for="username" class="layui-form-label">滞纳金优惠政策</label>
                    <div class="layui-input-inline">
                        <input type="text" id="late_fee_policy" name="late_fee_policy" autocomplete="off" class="layui-input"  value="<{$info['late_fee_policy']}>" >
                    </div>
                    <div class="layui-form-mid layui-word-aux"> % </div>
                </div>
                <div class="layui-form-item">
                    <label for="username" class="layui-form-label">罚息优惠政策</label>
                    <div class="layui-input-inline">
                        <input type="text" id="penalty_interest_policy" name="penalty_interest_policy" autocomplete="off" class="layui-input"  value="<{$info['penalty_interest_policy']}>" >
                    </div>
                    <div class="layui-form-mid layui-word-aux"> % </div>
                </div>
                <div class="layui-form-item" style="color: red; margin-left: 80px;">
                    <ul>
                        <li>录入规则：</li>
                        <li>1、优惠幅度范围：0%--100%。</li>
                        <li>2、0%代表不优惠，100%代表完全减免。</li>
                        <li>3、输入数字，小数点保留两位。</li>
                    </ul>
                </div>
                <div class="layui-form-item" style="text-align: center">
                    <button type="button" class="layui-btn" onclick="add()">保存</button>
                </div>
           </div>
        </div>
        </form>
    </div>
</div>
</body>


<style>
    .layui-input-inline {
        margin-top: 9px;
    }
</style>
<script>
    function add() {
        var capital_policy = $("#capital_policy").val();
        var interest_policy   = $("#interest_policy").val();
        var late_fee_policy    = $("#late_fee_policy").val();
        var penalty_interest_policy  = $("#penalty_interest_policy").val();

        if (capital_policy == '') {
            layer.msg('请输入本金优惠政策' , {icon:2 , time:2000});
        } else if (interest_policy == '') {
            layer.msg('请输入利息优惠政策' , {icon:2 , time:2000});
        } else if (late_fee_policy == '') {
            layer.msg('请输入滞纳金优惠政策' , {icon:2 , time:2000});
        }else if (penalty_interest_policy == '') {
            layer.msg('请输入罚息优惠政策' , {icon:2 , time:2000});
        } else {
            $("#my_form").submit();
        }
    }
</script>
</html>