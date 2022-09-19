<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>出借记录详情</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport"
          content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi"/>
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <!--[if lt IE 9]>
    <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
    <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
        .layui-table-cell {
            height: inherit;
        }

    </style>
</head>

<body>
<div class="x-nav">
            <span class="layui-breadcrumb">
                <{if $p==3 }>
                <a href="">工场微金数据录入</a>
        <{elseif $p==4 }>
                <a href="">智多新数据录入</a>
        <{elseif $p==5 }>
                <a href="">交易所数据录入</a>
        <{elseif $p==6}>
        <a href="">中国龙数据录入</a>
        <{/if}>
                <a href="">出借记录录入</a>
                <a>
                    <cite>详情</cite>
                </a>
            </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38"
       onclick="location.reload()" title="刷新">
        <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i>
    </a>
</div>
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">

        <div class="layui-col-md12">

            <div class="layui-card">
                <div class="layui-card-body">
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">条件筛选<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" action="">

                                    <div class="layui-form-item">
                  <{if $p==3 }>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">项目名称</label>
                                            <div class="layui-input-inline">
                                                <input class="layui-input" placeholder="请输入项目名称" name="p_name">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">标的号</label>
                                            <div class="layui-input-inline">
                                                <input class="layui-input" placeholder="请输入标的号" name="object_sn">
                                            </div>
                                        </div>
                                        <div class="layui-inline">
                                            <label class="layui-form-label">手机号</label>
                                            <div class="layui-input-inline">
                                                <input class="layui-input" placeholder="请输入手机号" name="mobile_phone">
                                            </div>
                                        </div>
                                    </div>

               <{else}>
                                    <div class="layui-inline">
                                            <label class="layui-form-label">user_id</label>
                                            <div class="layui-input-inline">
                                                <input class="layui-input" placeholder="请输入user_id" name="old_user_id">
                                            </div>
                                        </div>

                                     <div class="layui-inline">
                                            <label class="layui-form-label">出借记录id</label>
                                            <div class="layui-input-inline">
                                                <input class="layui-input" placeholder="请输入出借记录id" name="zdx_deal_load_id">
                                            </div>
                                        </div>


                <{/if}>
                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                            <button type="reset" class="layui-btn layui-btn-primary">重置</button>

                                            <button type="button" class="layui-btn layui-btn-danger" lay-submit="export"
                                                    lay-filter="export">导出
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="layui-card-body">
                    <{if $auth_status == 0}>
                    <button type="button" class="layui-btn" onclick="batchAgree(1)">审核通过</button>
                    <button type="button" class="layui-btn" onclick="batchAgree(2)">审核拒绝</button>
                    <{/if}>
                    <input type="hidden" id="platform_id">
                    <input type="hidden" id="file_id">
                    <div class="layui-tab layui-tab-brief" lay-filter="table-all">
                        <ul class="layui-tab-title" id="type_list" >
                            <li data-status="0" class="layui-this">全部 <span id="all"></span></li>
                            <li data-status="1">录入成功 <span id="l_success"></span></li>
                            <li data-status="2">录入失败 <span id="l_fail"></span></li>
                            <li data-status="4">入库成功 <span id="r_success"></span></li>
                            <li data-status="5">入库失败 <span id="r_fail"></span></li
                        </ul>
                    </div>
                    <table class="layui-table layui-form" lay-filter="list" id="list">
                        <script type="text/html" id="user_type">
                            {{# if(d.user_type == 1){ }}
                            个人
                            {{# } else { }}
                            机构
                            {{# } }}
                        </script>
                        <script type="text/html" id="idno_type">
                            {{# if(d.idno_type == 1){ }}身份证
                            {{# }else if(d.idno_type == 2){ }}护照
                            {{# }else if(d.idno_type == 3){ }}军官证
                            {{# }else if(d.idno_type == 4){ }}港澳台证件
                            {{# }else{ }}营业执照
                            {{# } }}
                        </script>
                        <script type="text/html" id="status">
                            {{# if(d.status==1){ }}待入库
                            {{# }else if(d.status==2){ }}录入失败
                            {{# }else if(d.status==3){ }}取消
                            {{# }else if(d.status==4){ }}入库成功
                            {{# }else if(d.status==5){ }}入库失败
                            {{# }else{ }}待处理
                            {{# } }}
                        </script>
                        <script type="text/html" id="loantype">
                            {{# if(d.loantype==1){ }}按季等额还款
                            {{# }else if(d.loantype==2){ }}按月等额还款
                            {{# }else if(d.loantype==3){ }}一次性还本付息
                            {{# }else if(d.loantype==4){ }}按月付息一次还本
                            {{# }else if(d.loantype==5){ }}按天一次性还款
                            {{# }else if(d.loantype==9){ }}按季付息
                            {{# }else if(d.loantype==10){ }}半年付息
                            {{# }else{ }}未知
                            {{# } }}
                        </script>
                        <script type="text/html" id="p_limit_type">
                            {{# if(d.p_limit_type==1){ }}天
                            {{# }else if(d.p_limit_type==2){ }}月
                            {{# }else{ }}未知
                            {{# } }}
                        </script>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script>
    layui.use(['form', 'layer', 'table', 'laydate', 'element', 'laytpl'], function () {
        form = layui.form;
        layer = layui.layer;
        table = layui.table;
        laydate = layui.laydate;
        element = layui.element
        laytpl = layui.laytpl;
        var file_id = "<{$_GET['file_id']}>"
        var p = <{$_GET['p']}>
        var join = new Array();
        switch (parseInt(p)) {
            case 3:
                join = [
                    {field: 'old_user_id',title: 'user_id',width: 180},
                    {field: 'user_name',title: '出借人姓名',width: 180},
                    {field: 'user_type',title: '出借人类型',width: 180, templet:'#user_type'},
                    {field: 'idno_type',title: '证件类型',width: 180,templet:'#idno_type'},
                    {field: 'idno',title: '证件号',width: 180},
                    {field: 'bank_id',title: '银行id',width: 180},
                    {field: 'bank_number',title: '银行卡号',width: 180},
                    {field: 'bankzone',title: '开户行',width: 180},
                    {field: 'cardholder',title: '持卡人姓名',width: 180},
                    {field: 'mobile_phone',title: '手机号',width: 180},
                    {field: 'object_sn',title: '标的号',width: 180},
                    {field: 'order_sn',title: '订单号',width: 180},
                    {field: 'p_name',title: '项目名称',width: 180},
                    {field: 'p_type',title: '项目类型',width: 180},
                    {field: 'p_limit_type',title: '项目期限类型',width: 180},
                    {field: 'p_limit_num',title: '项目期限',width: 180},
                    {field: 'rate',title: '年化利率',width: 180},
                    {field: 'loantype',title: '还款方式',width: 180,templet:'#loantype'},
                    {field: 'value_date',title: '起息时间',width: 180,templet: '<div>{{ layui.laytpl.toDateString(d.value_date*1000) }}</div>'},
                    {field: 'repayment_time',title: '还本时间',width: 180,templet: '<div>{{ layui.laytpl.toDateString(d.repayment_time*1000) }}</div>'},
                    {field: 'raise_money',title: '募集资金',width: 180},
                    {field: 'p_number',title: '项目编号',width: 180},
                    {field: 'p_desc',title: '项目简介',width: 180},
                    {field: 'rg_amount',title: '认购金额',width: 180},
                    {field: 'rg_time',title: '认购时间',width: 180,templet: '<div>{{ layui.laytpl.toDateString(d.rg_time*1000) }}</div>'},
                    {field: 'contract_number',title: '合同编号',width: 180},
                    {field: 'download',title: '借款合同下载地址',width: 180},
                    {field: 'danbao_download',title: '担保合同下载地址',width: 180},
                    {field: 'zixun_fuwu_download',title: '咨询服务合同下载地址',width: 180},
                    {field: 'wait_date',title: '待还期数',width: 180},
                    {field: 'wait_capital',title: '待还本金',width: 180},
                    {field: 'wait_interest',title: '待还利息',width: 180},
                    {field: 'end_repay_time',title: '最后还款时间',width: 180,templet: '<div>{{ layui.laytpl.toDateString(d.end_repay_time*1000) }}</div>'},
                    {field: 'borrower_name',title: '借款方名称',width: 180},
                    {field: 'b_type',title: '借款方类型',width: 180},
                    {field: 'b_idno_type',title: '借款方证件类型',width: 180},
                    {field: 'b_idno',title: '借款方证件号',width: 180},
                    {field: 'b_address',title: '借款方地址',width: 180},
                    {field: 'b_mobile_phone',title: '借款方联系方式',width: 180},
                    {field: 'b_desc',title: '借款方简介',width: 180},
                    {field: 'b_bankzone',title: '借款方开户行',width: 180},
                    {field: 'b_bank_number',title: '借款方银行卡号',width: 180},
                    {field: 'b_legal_person',title: '借款方法定代表人',width: 180},
                    {field: 'guarantee_name',title: '担保方名称',width: 180},
                    {field: 'g_license',title: '担保方营业执照号',width: 180},
                    {field: 'g_adress',title: '担保方地址',width: 180},
                    {field: 'g_mobile_phone',title: '担保方联系方式',width: 180},
                    {field: 'g_desc',title: '担保方简介',width: 180},
                    {field: 'g_bankzone',title: '担保方开户行',width: 180},
                    {field: 'g_bank_number',title: '担保方银行卡号',width: 180},
                    {field: 'g_legal_person',title: '担保方法定代表人',width: 180},
                    {field: 'status', title: '录入状态', fixed: 'right', width: 180,templet:'#status'},
                    {field: 'remark', title: '失败原因', fixed: 'right', width: 180,templet:'<div>{{strReplace(d.remark)}}</div>'},
                ];
                break;
            case 4:
                join = [
                    {field: 'old_user_id',title: 'user_id',width: 180},
                    {field: 'order_sn',title: '出借记录id',width: 180},
                    {field: 'rg_amount',title: '投资金额',width: 180},
                    {field: 'rg_time',title: '投资时间',width: 180,templet: '<div>{{ layui.laytpl.toDateString(d.rg_time*1000) }}</div>'},
                    {field: 'wait_capital',title: '剩余待还本金',width: 180},
                    {field: 'status', title: '录入状态', fixed: 'right', width: 180,templet:'#status'},
                    {field: 'remark', title: '失败原因', fixed: 'right', width: 180,templet:'<div>{{strReplace(d.remark)}}</div>'},
                ];
                break;
            case 5:
                join = [
                    {field: 'user_name',title: '投资人姓名',width: 180},
                    {field: 'user_type',title: '投资人类型',width: 180, templet:'#user_type'},
                    {field: 'idno_type',title: '证件类型',width: 180,templet:'#idno_type'},
                    {field: 'idno',title: '证件号',width: 180},
                    {field: 'bank_id',title: '银行id',width: 180},
                    {field: 'bank_number',title: '银行卡号',width: 180},
                    {field: 'bankzone',title: '开户行',width: 180},
                    {field: 'cardholder',title: '持卡人姓名',width: 180},
                    {field: 'mobile_phone',title: '手机号',width: 180},
                    {field: 'p_name',title: '产品名称',width: 180},
                    {field: 'p_limit_type',title: '产品期限类型',width: 180,templet:'#p_limit_type'},
                    {field: 'p_limit_num',title: '产品期限',width: 180},
                    {field: 'rate',title: '最小年化利率',width: 180},
                    {field: 'max_rate',title: '最大年化利率',width: 180},
                    {field: 'loantype',title: '还款方式',width: 180,templet:'#loantype'},
                    {field: 'value_date',title: '起息日',width: 180,templet: '<div>{{ layui.laytpl.toDateString(d.value_date*1000) }}</div>'},
                    {field: 'repayment_time',title: '到期日',width: 180,templet: '<div>{{ layui.laytpl.toDateString(d.repayment_time*1000) }}</div>'},
                    {field: 'raise_money',title: '产品募集资金',width: 180},
                    {field: 'rg_amount',title: '投资金额',width: 180},
                    {field: 'wait_capital',title: '待还本金',width: 180},
                    {field: 'wait_interest',title: '待还利息',width: 180},
                    {field: 'borrower_name',title: '发行人/融资方简称',width: 180},
                    {field: 'b_address',title: '登记备案场所',width: 180},
                    {field: 'status', title: '录入状态', fixed: 'right', width: 180,templet:'#status'},
                    {field: 'remark', title: '失败原因', fixed: 'right', width: 180,templet:'<div>{{strReplace(d.remark)}}</div>'},
                ];
                break;
        }

        laydate.render({
            elem: '#start'
        });

        laydate.render({
            elem: '#end'
        });

        getList(file_id,join,p);

        element.on('tab(table-all)', function () {
            var type = $(this).attr('data-status')
            table.reload('list', {
                where: {type:type},
                page:{curr:1}
            })
        })

        form.on('submit(sreach)', function (obj) {
            table.reload('list', {
                where:obj.field,
                page:{curr:1}
            });
            return false;
        });

        form.on('submit(export)', function (where) {
            where = where.field
            var type = $("#type_list li.layui-this ").attr('data-status')
            layer.confirm('确认要根据当前筛选条件导出吗？',
                function (index) {
                    layer.close(index);
                    location.href = "/offline/importContent/list?execl=1" +
                        "&p_name=" + where.p_name + "&object_sn=" + where.object_sn + "&mobile_phone=" + where.mobile_phone + "&file_id=" + file_id + "&p="+p+ "&type="+type;
                })
        });

        layui.laytpl.toDateString = function(d, format){
            var date = new Date(d || new Date())
                ,ymd = [
                this.digit(date.getFullYear(), 4)
                ,this.digit(date.getMonth() + 1)
                ,this.digit(date.getDate())
            ]
                ,hms = [
                this.digit(date.getHours())
                ,this.digit(date.getMinutes())
                ,this.digit(date.getSeconds())
            ];

            format = format || 'yyyy-MM-dd HH:mm:ss';

            return format.replace(/yyyy/g, ymd[0])
                .replace(/MM/g, ymd[1])
                .replace(/dd/g, ymd[2])
                .replace(/HH/g, hms[0])
                .replace(/mm/g, hms[1])
                .replace(/ss/g, hms[2]);
        };

        //数字前置补零
        layui.laytpl.digit = function(num, length, end){
            var str = '';
            num = String(num);
            length = length || 2;
            for(var i = num.length; i < length; i++){
                str += '0';
            }
            return num < Math.pow(10, length) ? str + (num|0) : num;
        };

    });

    function getList(file_id, join, p, type = 0) {
        table.render({
            elem: '#list',
            toolbar: 'true',
            defaultToolbar: ['filter'],
            page: true,
            limit: 10,
            where: {
                file_id: file_id,
                type: type,
                p: p,
            },
            limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            autoSort: false,
            cols: [join],
            url: '/offline/importContent/list',
            method: 'post',
            done: function (res) {
                if (res.code == 0) {
                    $("#all").html('(' + res.all_num + ')')
                    $("#l_success").html('(' + res.l_success_num + ')')
                    $("#l_fail").html('(' + res.l_fail_num + ')')
                    $("#r_success").html('(' + res.r_success_num + ')')
                    $("#r_fail").html('(' + res.r_fail_num + ')')
                    $("#platform_id").val(res.platform_id)
                    $("#file_id").val(res.file_id)
                }
            },
            response:
                {
                    statusName: 'code',
                    statusCode: 0,
                    msgName: 'info',
                    countName: 'count',
                    dataName: 'data'
                }
        });
    }

    function batchAgree(n) {
        var info = n == 1 ? '通过' : '拒绝'
        layer.confirm('是否确定审核'+info,
            function () {
                var p = $("#platform_id").val()
                var id = $("#file_id").val()
                if (!p || !id) {
                    layer.alert('文件不存在');
                    return false;
                }
                $.ajax({
                    url: '/offline/importFile/authFileP'+p,
                    data: {p:p,id:id,auth_status:n},
                    type: "POST",
                    success: function (res) {
                        if (res.code == 0) {
                            layer.confirm(res.info, function () {
                                window.parent.location.reload();
                            })
                        } else {
                            layer.alert(res.info);
                        }
                    }
                });
            })
    }

    function strReplace(str) {
        if (str == 0) {
            return '';
        }
        return str.replace(/,/g,"<br>")
    }

</script>
</html>