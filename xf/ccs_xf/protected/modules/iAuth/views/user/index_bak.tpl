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
            <a href="">首页<{$userList}></a>
            <a href="">演示</a>
            <a>
                <cite>导航元素</cite></a>
          </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38" onclick="location.reload()" title="刷新">
        <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i></a>
</div>
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-body ">
                   <!-- <form class="layui-form layui-col-space5">
                                <div class="layui-inline layui-show-xs-block">
                            <input class="layui-input"  autocomplete="off" placeholder="开始日" name="start" id="start">
                        </div>
                        <div class="layui-inline layui-show-xs-block">
                            <input class="layui-input"  autocomplete="off" placeholder="截止日" name="end" id="end">
                        </div>
                        <div class="layui-inline layui-show-xs-block">
                            <input type="text" name="username"  placeholder="请输入用户名" autocomplete="off" class="layui-input">
                        </div>
                        <div class="layui-inline layui-show-xs-block">
                            <button class="layui-btn"  lay-submit="" lay-filter="sreach"><i class="layui-icon">&#xe615;</i></button>
                        </div>
                    </form>-->
                </div>
                <div class="layui-card-header">
                    <input type="hidden" value="<{$pageSize}>" id="pageSize">
                    <button class="layui-btn" onclick="xadmin.open('添加用户','/iauth/user/UserAdd',600,400)"><i class="layui-icon"></i>添加</button>
                </div>
                <div class="layui-card-body ">
                    <table class="layui-table layui-form" id="myTable">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>登录名</th>
                            <th>手机</th>
                            <th>邮箱</th>
                            <th>角色</th>
                            <th>加入时间</th>
                            <th>状态</th>
                            <th>操作</th>
                        </thead>
                        <tbody>
                        <{foreach $brand as $key => $val}>
                            <tr>
                                <td><{$val['id']}></td>
                                <td><{$val['username']}></td>
                                <td><{$val['phone']}></td>
                                <td><{$val['email']}></td>
                                <td><{$val['rolename']}></td>
                                <td><{$val['addtime']}></td>
                                <td class="td-status">
                                    <span class="layui-btn layui-btn-normal layui-btn-mini <{if $val['status_info'] != '正常'}>layui-btn-disabled<{/if}>"><{$val['status_info']}></span></td>
                                <td class="td-manage">
                                    <a onclick="member_stop(this,<{$val['id']}>)" href="javascript:;"  title="<{$val['status_info']}>">
                                        <i class="layui-icon">&#xe601;</i>
                                    </a>
                                    <a title="编辑"  onclick="xadmin.open('编辑','/iauth/user/useredit?id=<{$val['id']}>')" href="javascript:;">
                                        <i class="layui-icon">&#xe642;</i>
                                    </a>
                                </td>
                            </tr>
                            <{/foreach}>
                        </tbody>
                    </table>
                </div>
                <div class="layui-card-body ">
                    <div class="page">

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script>
    // 表格汉化列表
    var table_lang = {
        "sProcessing": "处理中...",
        "sLengthMenu": "每页 _MENU_ 项",
        "sZeroRecords": "没有匹配结果",
        "sInfo": "当前显示第 _START_ 至 _END_ 项，共 _TOTAL_ 项。",
        "sInfoEmpty": "当前显示第 0 至 0 项，共 0 项",
        "sInfoFiltered": "(由 _MAX_ 项结果过滤)",
        "sInfoPostFix": "",
        "sSearch": "搜索:",
        "sUrl": "",
        "sEmptyTable": "表中数据为空",
        "sLoadingRecords": "载入中...",
        "sInfoThousands": ",",
        "oPaginate": {
            "sFirst": "首页",
            "sPrevious": "上页",
            "sNext": "下页",
            "sLast": "末页",
            "sJump": "跳转"
        },
        "oAria": {
            "sSortAscending": ": 以升序排列此列",
            "sSortDescending": ": 以降序排列此列"
        }
    };
    $(document).ready(function() {
        var pagrSize = $("#pageSize").val();
        $('#myTable').DataTable({
            language:table_lang,  // 提示信息
            autoWidth: false,   // 禁用自动调整列宽
            lengthMenu: [pagrSize, pagrSize*2, pagrSize*3],
            stripeClasses: ["odd", "even"], // 为奇偶行加上样式，兼容不支持CSS伪类的场合
            processing: true,   // 隐藏加载提示,自行处理
            serverSide: true,   // 启用服务器端分页
            searching: false,    // 启用原生搜索
            orderMulti: true,   // 启用多列排序
            order: [],       // 取消默认排序查询,否则复选框一列会出现小箭头
            renderer: "bootstrap", // 渲染样式：Bootstrap和jquery-ui
            pagingType: "simple_numbers", //分页样式：simple,simple_numbers,full,full_numbers
            columnDefs: [{
                "targets": 'nosort', // 列的样式名
                "orderable": false  // 包含上样式名‘nosort'的禁止排序
            }],
            "bAutoWidth": false,//自动宽度
            ajax: function (data, callback, settings) {
                //封装请求参数
                var param = data;
                param.pageSize = data.length;  // 页面显示记录条数，在页面显示每页显示多少项的时候
                param.start = data.start; // 开始的记录序号
                param.page = (data.start / data.length)+1; // 当前页码
                //ajax请求数据
                $.ajax({
                    type: "GET",
                    url: "/iauth/user/UserList",
                    cache: false, // 禁用缓存
                    data: param, // 传入组装的参数
                    dataType: "json",
                    success: function (res) {
                        setTimeout(function () {
                            var out = {};
                            out.draw = data.draw;
                            out.recordsTotal = res.countNum;
                            out.recordsFiltered = res.countNum;
                            out.data = res.userData;
                            callback(out);
                        }, 50);
                    }
                })},
            "columns": [
                {data : "id"},
                {data : "username"},
                {data : "phone"},
                {data : "email"},
                {data : "rolename"},
                {data : "addtime"},
            {data : "status",render(data, type, full, meta) {
                return `
                    <td class="td-status">
                    <span class="layui-btn layui-btn-normal layui-btn-mini ">${full.status_info}</span></td>
                     `
            }},
    {data : "text",render(data, type, full, meta) {
             return `
               <td class="td-manage"><div></div>
             <a onclick="member_stop(this,${full.id})" href="javascript:;"  title="${full.status_info}">
                 <i class="layui-icon">&#xe601;</i>
             </a>
             <a title="编辑"  onclick="xadmin.open('编辑','/iauth/user/useredit?id=${full.id}')" href="javascript:;">
                 <i class="layui-icon">&#xe642;</i>
             </a>
         </td>
         `
         }},
    ]
    })
    });
</script>
<script>
    layui.use(['laydate','form'], function(){
        var laydate = layui.laydate;
        var form = layui.form;

        //执行一个laydate实例
        laydate.render({
            elem: '#start' //指定元素
        });

        //执行一个laydate实例
        laydate.render({
            elem: '#end' //指定元素
        });
    });

    /*用户-停用*/
    function member_stop(obj,id){
        if($(obj).attr('title')=='正常'){var str = "注销";}else{var str = "启用";}
        layer.confirm('确认要'+str+'吗？',function(index){
            if($(obj).attr('title')=='正常'){
                //发异步把用户状态进行更改
                $.ajax({
                    url: '/iauth/user/UpdateStatus?status=2&pkId='+id,
                    type:"GET",
                    success: function (res) {
                        if(res.code == 0){
                            $(obj).attr('title','已注销')
                            $(obj).find('i').html('&#xe62f;');
                            $(obj).parents("tr").find(".td-status").find('span').addClass('layui-btn-disabled').html('已注销');
                            layer.msg('已注销!',{icon: 5,time:1000});
                        }
                    }
                })
            }else{
                $.ajax({
                    url: '/iauth/user/UpdateStatus?status=1&pkId='+id,
                    type:"GET",
                    success: function (res) {
                        if(res.code == 0){

                            $(obj).attr('title','正常');
                            $(obj).find('i').html('&#xe601;');
                            $(obj).parents("tr").find(".td-status").find('span').removeClass('layui-btn-disabled').html('正常');
                            layer.msg('已启动',{icon: 1,time:1000});
                        }
                    }
                })
            }
        });
    }
</script>
</html>