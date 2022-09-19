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
        <a href="">第三方管理</a>
        <a>
            <cite>优惠政策管理</cite>
        </a>
            </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38"
       onclick="location.reload()" title="刷新">
        <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i></a>
</div>
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">
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
                        <{$info['capital_policy']}> %
                    </div>
                </div>
                <div class="layui-form-item">
                    <label for="username" class="layui-form-label">利息优惠政策</label>
                    <div class="layui-input-inline">
                         <{$info['interest_policy']}> %
                    </div>
                </div>
                <div class="layui-form-item">
                    <label for="username" class="layui-form-label">滞纳金优惠政策</label>
                    <div class="layui-input-inline">
                        <{$info['late_fee_policy']}> %
                    </div>
                </div>
                <div class="layui-form-item">
                    <label for="username" class="layui-form-label">罚息优惠政策</label>
                    <div class="layui-input-inline">
                        <{$info['penalty_interest_policy']}> %
                    </div>
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
                <a href="/borrower/company/FavouredPolicyEdit">
                    <button type="button" class="layui-btn layui-btn-xs layui-btn-normal" >编辑</button>
                </a>
                </div>
           </div>
        </div>
    </div>
</div>
</body>


<style>
    .layui-input-inline {
        margin-top: 9px;
    }
</style>
</html>