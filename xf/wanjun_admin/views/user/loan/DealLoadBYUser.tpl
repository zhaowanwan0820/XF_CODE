<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>出借人列表</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
    <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
    <script src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
    <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
    <!--[if lt IE 9]>
    <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
    <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>
<div class="x-nav">
            <span class="layui-breadcrumb">
                <a href="">出借人管理</a>
                <a>
                    <cite>出借人列表</cite></a>
            </span>
    <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #259ed8" onclick="location.reload()" title="刷新">
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
                            <h2 class="layui-colla-title">条件筛选（实时筛选）<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content layui-show">
                                <form class="layui-form" action="">
                                    <div class="layui-form-item">
                                        <div class="layui-inline">
                                            <label class="layui-form-label">出借人ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="id" id="id"   autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">出借人名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="real_name" id="real_name"  autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">手机号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="mobile" id="mobile"  autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">证件号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="idno" id="idno"   autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">出借人归属地(省)</label>
                                            <div class="layui-input-inline">
                                                <select name="province_name" id="province_name" lay-search="">
                                                    <option value="">全部</option>
                                                    <{foreach $province_name_list as $key=>$val}>
                                                    <option value='<{$key}>'><{$val}></option>
                                                    <{/foreach}>
                                                </select>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                            <{if $DealLoadBYUser2Excel == 1 }>
                                            <button type="button" class="layui-btn layui-btn-danger" onclick="DealLoadBYUser2Excel()">导出</button>
                                            <{/if}>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-card-body">
                    <div class="layui-collapse" lay-filter="test">
                        <div class="layui-colla-item">
                            <h2 class="layui-colla-title">批量条件上传<i class="layui-icon layui-colla-icon"></i></h2>
                            <div class="layui-colla-content">
                                <form class="layui-form" action="">
                                    <div class="layui-form-item">

                                      <div class="layui-inline">
                                        <label class="layui-form-label">查询类型</label>
                                        <div class="layui-input-inline">
                                          <select name="type" lay-search="">
                                            <option value="1">上传出借人ID</option>
                                          </select>
                                        </div>
                                      </div>

                                      <div class="layui-inline">
                                        <label class="layui-form-label">当前查询条件</label>
                                        <div class="layui-input-inline">
                                          <input type="text" class="layui-input" id="condition_name" value="" readonly style="width: 400px">
                                        </div>
                                        <input type="hidden" name="condition_id" id="condition_id" value="">
                                      </div>

                                    </div>
                                    <div class="layui-form-item">
                                      <div class="layui-input-block">
                                        <button class="layui-btn" lay-submit="" lay-filter="sreach_a">上传文件</button>
                                        <!-- <button class="layui-btn" lay-submit="" lay-filter="sreach_b">立即搜索</button> -->
                                        <button type="button" class="layui-btn layui-btn-primary" onclick="reset_condition()">重置</button>
                                        <{if $DealLoadBYUser2Excel == 1 }>
                                        <button type="button" class="layui-btn layui-btn-danger" onclick="DealLoadBYUser2ExcelA()">导出</button>
                                        <{/if}>
                                      </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="layui-card-body">
                  <table class="layui-table layui-form" lay-filter="list" id="list">
                  </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script>
    
</script>
    <script>
      layui.use(['form' , 'layer' , 'table' , 'laydate'] , function(){
        form    = layui.form;
        layer   = layui.layer;
        table   = layui.table;

        table.render({
          elem           : '#list',
          toolbar        : '#toolbar',
          defaultToolbar : ['filter'],
          page           : true,
          limit          : 10,
          limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
          autoSort       : false,
          cols:[[
            {
              field : 'id',
              title : '出借人ID',
              fixed : 'left',
              width : 100
            },
            {
              field : 'real_name',
              title : '出借人名称',
              width : 180
            },
             {
               field : 'mobile',
               title : '手机号',
               width : 120
             },
             {
               field : 'idno',
               title : '证件号',
               width : 180
            },
            {
              field : 'bankcard',
              title : '银行卡号',
              width : 130
            },
            {
              field : 'name',
              title : '开户行',
              width : 100
            },
            {
              field : 'province_name_name',
              title : '归属地(省)',
              width : 100
            },
              {
                  field : 'card_address',
                  title : '详细地址',
                  width : 200
              },
            {
              field : 'wait_capital',
              title : '在途金额',
              width : 120,
                align : 'right',
            },
            {
              field : 'ph_increase_reduce',
              title : '原普惠充提差',
              width : 120,
                align : 'right',
            },
            {
              field : 'c_deal_load',
              title : '持有债权数量',
              width : 130,
                align : 'right',
            },
            {
              field : 'repay_amount',
              title : '已分配回款金额',
              width : 120,
                align : 'right',
            }
          ]],
          url      : '/user/Loan/DealLoadBYUser',
          method   : 'post',
          response :
          {
            statusName : 'code',
            statusCode : 0,
            msgName    : 'info',
            countName  : 'count',
            dataName   : 'data'
          }
        });

        form.on('submit(sreach)', function(obj){
          table.reload('list', {
            where    :
            {
                id : obj.field.id,
                real_name : obj.field.real_name,
                mobile : obj.field.mobile,
                idno : obj.field.idno,
                province_name : obj.field.province_name,
              condition_id : ''
            },
            page:{curr:1}
          });
          return false;
        });


        form.on('submit(sreach_a)', function(obj){

          if (obj.field.type == 0) {
            layer.msg('请选择查询类型');
          } else if (obj.field.type == 1) {
            xadmin.open('通过上传出借人ID查询','/user/Loan/addDealLoadBYUserCondition?type=1');
          }
          return false;
        });

        form.on('submit(sreach_b)', function(obj){

          if (obj.field.condition_id == '') {
            layer.msg('缺少查询条件，请先上传文件！');
          } else {
            table.reload('list', {
              where    :
              {
                  id     : '',
                  real_name      : '',
                  mobile  : '',
                  idno    : '',
                  province_name    : '',
                condition_id : obj.field.condition_id
              },
              page:{curr:1}
            });
          }
          return false;
        });

      });

      function reset_condition() {
        $("#condition_id").val('');
        $("#condition_name").val('');
      }

      function show_condition(id , name) {
        $("#condition_id").val(id);
        $("#condition_name").val(name);
      }

      function DealLoadBYUser2Excel()
      {
          var id = $("#id").val();
          var real_name = $("#real_name").val();
          var mobile = $("#mobile").val();
          var idno = $("#idno").val();
          var province_name = $("#province_name").val();
            if (id == '' && real_name == '' && mobile == '' && idno == '' && province_name == '') {
              layer.msg('请输入至少一个查询条件');
            } else {
              layer.confirm('确认要根据当前筛选条件导出吗？',
              function(index) {
                layer.close(index);
                window.open("/user/Loan/DealLoadBYUser2Excel?id="+id+"&real_name="+real_name+"&mobile="+mobile+"&idno="+idno+"&province_name="+province_name , "_blank");
              });
            }
      }

      function DealLoadBYUser2ExcelA()
      {
        var condition_id = $("#condition_id").val();
        if (condition_id == '') {
          layer.msg('缺少查询条件，请先上传文件！');
        } else {
          layer.confirm('确认要根据当前上传的批量条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/Loan/DealLoadBYUser2Excel?condition_id="+condition_id , "_blank");
            reset_condition();
          });
        }
      }
    </script>
    <script type="text/html" id="operate">
    </script>
    <script type="text/html" id="toolbar">
      <div class = "layui-btn-container" >
      </div >
    </script>
</html>