<!DOCTYPE html>
<html class="x-admin-sm">

<head>
    <meta charset="UTF-8">
    <title>标的列表</title>
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
                <a href="">债权管理</a>
                <a>
                    <cite>标的列表</cite></a>
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
                                          <label class="layui-form-label">平台</label>
                                          <div class="layui-input-inline">
                                            <select name="platform_id" id="platform_id" lay-search="">
                                                <option value="">全部</option>
                                              <option value="2">普惠</option>
                                              <option value="4">智多新</option>
                                            </select>
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款编号</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="deal_id" id="deal_id" placeholder="请输入借款编号" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款标题</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="deal_name" id="deal_name" placeholder="请输入借款标题" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">项目ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="project_id" id="project_id" placeholder="请输入项目ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">项目名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="project_name" id="project_name" placeholder="请输入项目名称" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款方ID</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="company_id" id="company_id" placeholder="请输入借款方ID" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">借款方名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="user_name" id="user_name" placeholder="请输入借款方名称" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">融资经办机构名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="advisory_name" id="advisory_name" placeholder="请输入融资经办机构名称" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">融资担保机构名称</label>
                                            <div class="layui-input-inline">
                                                <input type="text" name="agency_name" id="agency_name" placeholder="请输入融资担保机构名称" autocomplete="off" class="layui-input">
                                            </div>
                                        </div>

                                        <div class="layui-inline">
                                            <label class="layui-form-label">标的来源</label>
                                            <div class="layui-input-inline">
                                                <select name="deal_src" id="deal_src" lay-search="">
                                                    <option value="">全部</option>
                                                    <option value="1">先锋基础数据导入</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="layui-form-item">
                                        <div class="layui-input-block">
                                            <button class="layui-btn" lay-submit="" lay-filter="sreach">立即搜索</button>
                                            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                            <{if $DealLoadBYDeal2Excel == 1 }>
                                            <button type="button" class="layui-btn layui-btn-danger" onclick="DealLoadBYDeal2ExcelA()">导出</button>
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
                                            <option value="">请选择查询类型</option>
                                            <option value="1">上传借款方名称</option>
                                            <option value="2">上传融资经办机构名称</option>
                                            <option value="3">上传融资担保机构名称</option>
                                            <option value="4">上传借款标题</option>
                                            <option value="5">上传项目名称</option>
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
                                        <button class="layui-btn" lay-submit="" lay-filter="sreach_b">立即搜索</button>
                                        <button type="button" class="layui-btn layui-btn-primary" onclick="reset_condition()">重置</button>
                                        <{if $DealLoadBYDeal2Excel == 1 }>
                                        <button type="button" class="layui-btn layui-btn-danger" onclick="DealLoadBYDeal2ExcelB()">导出</button>
                                        <{/if}>
                                      </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="layui-card-body">
                  <button class="layui-btn layui-btn-danger"   lay-submit="" lay-filter="download_contract">批量导出合同</button>
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
        laydate = layui.laydate;

        table.render({
          elem           : '#list',
          defaultToolbar : ['filter'],
          page           : true,
          limit          : 10,
          limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
          autoSort       : false,
          cols:[[
            {
              field : 'deal_id',
              title : '借款编号',
              fixed : 'left',
              width : 100
            },
            {
              field : 'platform_name',
              title : '平台',
              width : 60
            },
            {
              field : 'deal_name',
              title : '借款标题',
              width : 150
            },
            {
              field : 'project_id',
              title : '项目ID',
              width : 100
            },
            {
              field : 'project_name',
              title : '项目名称',
              width : 120
            } ,
            {
              field : 'project_product_class',
              title : '产品大类',
              width : 100
            },
            {
              field : 'deal_repay_time',
              title : '借款期限',
              width : 100
            },
            {
              field : 'deal_loantype',
              title : '还款方式',
              width : 150
            },
            {
              field : 'max_repay_time',
              title : '应还款日期',
              width : 130
            },
            {
              field : 'overdue_day',
              title : '逾期天数',
              width : 100
            },
            {
              field : 'overdue_interest',
              title : '逾期利息',
              width : 150,
              align : 'right'
            },
              {
                  field : 'ph_wait_capital',
                  title : '原普惠在途本金',
                  width : 150,
                  align : 'right'
              },
              {
                  field : 'sign_capital',
                  title : '万峻持有总授权',
                  align : 'right',
                  width : 140
              },
              {
                  field : 'sign_capital_wait_capital',
                  title : '授权与在途比例',
                  align : 'right',
                  width : 140
              },
            {
              field : 'deal_user_id',
              title : '借款方ID',
              width : 100
            },
            {
              field : 'deal_user_real_name',
              title : '借款方名称',
              width : 200
            },
            {
              field : 'deal_advisory_name',
              title : '融资经办机构名称',
              width : 200
            },
            {
              field : 'deal_agency_name',
              title : '融资担保机构名称',
              width : 200
            },
            {
              field : 'investor_number',
              title : '当前债权人数',
              width : 150
            },
            {
              field : 'deal_src',
              title : '标的来源',
              width : 150
            }
          ]],
          url      : '/user/Loan/DealLoadBYDeal',
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
                platform_id          : obj.field.platform_id,
              deal_id           : obj.field.deal_id,
              deal_name         : obj.field.deal_name,
              project_id        : obj.field.project_id,
              project_name      : obj.field.project_name,
              user_name         : obj.field.user_name,
                company_id         : obj.field.company_id,
                deal_src:obj.field.deal_src,
              advisory_name     : obj.field.advisory_name,
              agency_name       : obj.field.agency_name,
              condition_id      : ''
            },
            page:{curr:1}
          });
          return false;
        });

        form.on('submit(download_contract)', function (obj) {
            xadmin.open('批量导出合同', '/user/Contract/Upload',900,380);
            return false;
        });


        form.on('submit(sreach_a)', function(obj){

          if (obj.field.type == 0) {
            layer.msg('请选择查询类型');
          } else if (obj.field.type == 1) {
            xadmin.open('通过上传借款方名称查询','/user/Loan/addDealLoadBYDealCondition?type=1');
          } else if (obj.field.type == 2) {
            xadmin.open('通过上传融资经办机构名称查询','/user/Loan/addDealLoadBYDealCondition?type=2');
          } else if (obj.field.type == 3) {
            xadmin.open('通过上传融资担保机构名称查询','/user/Loan/addDealLoadBYDealCondition?type=3');
          } else if (obj.field.type == 4) {
            xadmin.open('通过上传借款标题查询','/user/Loan/addDealLoadBYDealCondition?type=4');
          } else if (obj.field.type == 5) {
            xadmin.open('通过上传项目名称查询','/user/Loan/addDealLoadBYDealCondition?type=5');
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
                  platform_id          : '',
                deal_id           : '',
                deal_name         : '',
                project_id        : '',
                project_name      : '',
                user_name         : '',
                  deal_src:'',
                  company_id         : '',
                advisory_name     : '',
                agency_name       : '',
                condition_id      : obj.field.condition_id
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

      function DealLoadBYDeal2ExcelA()
      {
        var platform_id          = $("#platform_id").val();
        var deal_id           = $("#deal_id").val();
        var deal_name         = $("#deal_name").val();
        var project_id        = $("#project_id").val();
        var project_name      = $("#project_name").val();
        var user_name         = $("#user_name").val();
        var deal_src = $("#deal_src").val();
          var company_id         = $("#company_id").val();
        var advisory_name     = $("#advisory_name").val();
        var agency_name       = $("#agency_name").val();
        if (deal_src == '' && company_id=='' && platform_id == '' &&  deal_id == '' && deal_name == '' && project_id == '' && project_name == '' && jys_record_number == '' && user_name == '' && advisory_name == '' && agency_name == '') {
          layer.msg('请输入至少一个查询条件');
        } else {
          layer.confirm('确认要根据当前筛选条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/Loan/DealLoadBYDeal2Excel?deal_src="+deal_src+"&company_id="+company_id+"&platform_id="+platform_id+"&deal_id="+deal_id+"&deal_name="+deal_name+"&project_id="+project_id+"&project_name="+project_name+"&jys_record_number="+jys_record_number+"&user_name="+user_name+"&advisory_name="+advisory_name+"&agency_name="+agency_name , "_blank");
          });
        }
      }

      function DealLoadBYDeal2ExcelB()
      {
        var condition_id = $("#condition_id").val();
        if (condition_id == '') {
          layer.msg('缺少查询条件，请先上传文件！');
        } else {
          layer.confirm('确认要根据当前上传的批量条件导出吗？',
          function(index) {
            layer.close(index);
            window.open("/user/Loan/DealLoadBYDeal2Excel?condition_id="+condition_id , "_blank");
            reset_condition();
          });
        }
      }
    </script>
    <script type="text/html" id="operate">
    </script>
</html>