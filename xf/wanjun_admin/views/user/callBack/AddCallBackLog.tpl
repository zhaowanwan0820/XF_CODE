<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>客服录入</title>
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
      <div class="x-nav">
            <span class="layui-breadcrumb">
                <a href="">借款人呼叫管理</a>
                <a>
                    <cite>客服录入</cite></a>
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
                          <form class="layui-form">

                              <div class="layui-form-item">
                                  <label for="user_id_name" class="layui-form-label">
                                      <span class="x-red">*</span>用户ID</label>
                                  <div class="layui-input-inline" id="user_id_name_div">
                                      <input type="text" id="user_id_name" autocomplete="off" class="layui-input" value="">
                                  </div>
                                  <input type="hidden" id="user_id">
                              </div>

                              <div class="layui-form-item">
                                  <label for="L_repass" class="layui-form-label"></label>
                                  <button type="button" class="layui-btn" onclick="select()" id="select_div">查询</button>
                                  <button type="button" class="layui-btn layui-btn-primary" onclick="reset_select()" id="reset_select_div">重置</button>
                              </div>

                              <div class="res_div" style="display: none;">

                                <div class="layui-form-item">
                                    <label class="layui-form-label">
                                        用户姓名</label>
                                    <div class="layui-input-inline">
                                        <div id="real_name" style="padding: 7px;"></div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">
                                        用户证件号</label>
                                    <div class="layui-input-inline">
                                        <div id="idno" style="padding: 7px;"></div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">
                                        用户手机号</label>
                                    <div class="layui-input-inline">
                                        <div id="mobile" style="padding: 7px;"></div>
                                    </div>
                                </div>

                              </div>
                              
                          </form>
                        </div>

                        <div class="layui-card-body res_div" style="display: none;">
                          <table class="layui-table layui-form" lay-filter="list" id="list">
                          </table>
                        </div>

                        <div class="layui-card-body res_div" style="display: none;">
                          <table class="layui-table layui-form" lay-filter="list" id="list_1">
                          </table>
                        </div>

                        <div class="layui-card-body res_div" style="display: none;">
                          <div class="layui-collapse" lay-filter="test">
                            <div class="layui-colla-item">
                              <h2 class="layui-colla-title">用户状态<i class="layui-icon layui-colla-icon"></i></h2>
                              <div class="layui-colla-content layui-show">
                                <form class="layui-form">

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red">*</span>1.号码状态</label>
                                    <div class="layui-input-inline" style="width: 500px;">
                                      <input type="radio" class="question_1_1" lay-filter="question_1_1" name="question_1_1" value="1" title="可联">
                                      <input type="radio" class="question_1_1" lay-filter="question_1_1" name="question_1_1" value="2" title="失联">
                                    </div>
                                  </div>

                                  <div class="layui-form-item" id="question_1_2_div" style="display: none;">
                                    <label class="layui-form-label"></label>
                                    <div class="layui-input-inline" style="width: 500px;">
                                      <input type="radio" class="question_1_2" lay-filter="question_1_2" name="question_1_2" value="2" title="空号">
                                      <input type="radio" class="question_1_2" lay-filter="question_1_2" name="question_1_2" value="3" title="停机">
                                      <input type="radio" class="question_1_2" lay-filter="question_1_2" name="question_1_2" value="4" title="关机">
                                      <input type="radio" class="question_1_2" lay-filter="question_1_2" name="question_1_2" value="5" title="无法接通">
                                      <input type="radio" class="question_1_2" lay-filter="question_1_2" name="question_1_2" value="6" title="占线">
                                      <input type="radio" class="question_1_2" lay-filter="question_1_2" name="question_1_2" value="7" title="挂断">
                                      <input type="radio" class="question_1_2" lay-filter="question_1_2" name="question_1_2" value="8" title="无人接听">
                                      <input type="radio" class="question_1_2" lay-filter="question_1_2" name="question_1_2" value="9" title="暂停服务">
                                    </div>
                                  </div>

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red star"></span>2.接听人是否本人</label>
                                    <div class="layui-input-inline" style="width: 500px;">
                                      <input type="radio" class="question_2" lay-filter="question_2" name="question_2" value="1" title="是">
                                      <input type="radio" class="question_2" lay-filter="question_2" name="question_2" value="2" title="否">
                                    </div>
                                  </div>

                                  <div class="layui-form-item" id="question_2_else_div" style="display: none;">
                                      <label for="question_2_else_1" class="layui-form-label"></label>
                                      <div class="layui-input-inline" style="width: 500px;">
                                          <input type="text" id="question_2_else_1" placeholder="接听人" autocomplete="off" class="layui-input" style="width: 248px;display: inline;">
                                          <input type="text" id="question_2_else_2" placeholder="接听人与本人关系" autocomplete="off" class="layui-input" style="width: 248px;display: inline;">
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red">*</span>3.拨打工具</label>
                                    <div class="layui-input-inline" style="width: 500px;">
                                      <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="1" title="度言">
                                      <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="2" title="属地">
                                      <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="3" title="私人">
                                      <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="4" title="微信">
                                      <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="5" title="云客">
                                      <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="6" title="其他">
                                    </div>
                                  </div>

                                  <div class="layui-form-item" id="question_3_else_div" style="display: none;">
                                      <label for="question_3_else" class="layui-form-label">
                                          <span class="x-red">*</span>其他</label>
                                      <div class="layui-input-inline">
                                          <textarea id="question_3_else" class="layui-textarea" style="width: 500px;height: 100px;"></textarea>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red">*</span>4.客户状态</label>
                                    <div class="layui-input-inline" style="width: 500px;">
                                      <input type="radio" class="question_4" lay-filter="question_4" name="question_4" value="1" title="本人失联，无法代偿">
                                      <input type="radio" class="question_4" lay-filter="question_4" name="question_4" value="2" title="恶意拖欠/质疑合同、金额等">
                                      <input type="radio" class="question_4" lay-filter="question_4" name="question_4" value="3" title="工资拖欠">
                                      <input type="radio" class="question_4" lay-filter="question_4" name="question_4" value="4" title="有意偿还，积极筹措资金">
                                      <input type="radio" class="question_4" lay-filter="question_4" name="question_4" value="5" title="资金短缺，敷衍跳票">
                                      <input type="radio" class="question_4" lay-filter="question_4" name="question_4" value="6" title="其他">
                                    </div>
                                  </div>

                                  <div class="layui-form-item" id="question_4_else_div" style="display: none;">
                                      <label for="question_4_else" class="layui-form-label">
                                          <span class="x-red">*</span>其他</label>
                                      <div class="layui-input-inline">
                                          <textarea id="question_4_else" class="layui-textarea" style="width: 500px;height: 100px;"></textarea>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red">*</span>5.客户标签</label>
                                    <div class="layui-input-inline" style="width: 500px;">
                                      <input type="radio" class="question_5" lay-filter="question_5" name="question_5" value="1" title="可联">
                                      <input type="radio" class="question_5" lay-filter="question_5" name="question_5" value="2" title="失联">
                                      <input type="radio" class="question_5" lay-filter="question_5" name="question_5" value="3" title="不可联可修复">
                                      <input type="radio" class="question_5" lay-filter="question_5" name="question_5" value="4" title="拒绝还款">
                                    </div>
                                  </div>

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red star"></span>6.是否添加微信</label>
                                    <div class="layui-input-inline" style="width: 500px;">
                                      <input type="radio" class="question_6" lay-filter="question_6" name="question_6" value="1" title="已添加">
                                      <input type="radio" class="question_6" lay-filter="question_6" name="question_6" value="2" title="未添加">
                                      <input type="radio" class="question_6" lay-filter="question_6" name="question_6" value="3" title="等待验证">
                                      <input type="radio" class="question_6" lay-filter="question_6" name="question_6" value="4" title="搜索不到">
                                    </div>
                                  </div>

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red star"></span>7.网查记录</label>
                                    <div class="layui-input-inline" style="width: 500px;">
                                    </div>
                                  </div>

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red star"></span>(1)关联公司</label>
                                    <div class="layui-input-inline" style="width: 500px;">
                                      <input type="text" id="question_7" autocomplete="off" class="layui-input">
                                      <div style="margin: 6px 0px 0px 0px;display: inline-block;font-size: 14px;vertical-align: middle;">公司是否存续</div>
                                      <input type="radio" class="question_7_status" lay-filter="question_7_status" name="question_7_status" value="1" title="是">
                                      <input type="radio" class="question_7_status" lay-filter="question_7_status" name="question_7_status" value="2" title="否">
                                      <input type="radio" class="question_7_status" lay-filter="question_7_status" name="question_7_status" value="3" title="其他">
                                    </div>
                                  </div>

                                  <div class="layui-form-item" id="question_7_status_else_div" style="display: none;">
                                      <label for="question_7_status_else" class="layui-form-label">
                                          <span class="x-red star"></span>其他</label>
                                      <div class="layui-input-inline">
                                          <textarea id="question_7_status_else" class="layui-textarea" style="width: 500px;height: 100px;"></textarea>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red star"></span>(2)支付宝认证</label>
                                    <div class="layui-input-inline" style="width: 500px;">
                                      <input type="radio" class="question_8" lay-filter="question_8" name="question_8" value="1" title="支付宝认证是本人">
                                      <input type="radio" class="question_8" lay-filter="question_8" name="question_8" value="2" title="支付宝认证非本人">
                                      <input type="radio" class="question_8" lay-filter="question_8" name="question_8" value="3" title="支付宝搜不到">
                                      <input type="radio" class="question_8" lay-filter="question_8" name="question_8" value="4" title="其他">
                                    </div>
                                  </div>

                                  <div class="layui-form-item" id="question_8_else_div" style="display: none;">
                                      <label for="question_8_else" class="layui-form-label">
                                          <span class="x-red star"></span>其他</label>
                                      <div class="layui-input-inline">
                                          <textarea id="question_8_else" class="layui-textarea" style="width: 500px;height: 100px;"></textarea>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                      <label for="remark" class="layui-form-label">
                                          <span class="x-red star"></span>8.跟进记录</label>
                                      <div class="layui-input-inline">
                                          <textarea id="remark" class="layui-textarea" style="width: 500px;height: 100px;"></textarea>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red star"></span>9.问题类型</label>
                                    <div class="layui-input-inline" style="width: 500px;">
                                      <input type="radio" class="type" lay-filter="type" name="type" value="1" title="结清类">
                                      <input type="radio" class="type" lay-filter="type" name="type" value="2" title="还款纠纷类">
                                      <input type="radio" class="type" lay-filter="type" name="type" value="3" title="借款核实类">
                                      <input type="radio" class="type" lay-filter="type" name="type" value="4" title="还款渠道身份类">
                                      <input type="radio" class="type" lay-filter="type" name="type" value="5" title="负面影响类">
                                      <input type="radio" class="type" lay-filter="type" name="type" value="6" title="拒绝还款类">
                                      <input type="radio" class="type" lay-filter="type" name="type" value="7" title="减免类">
                                      <input type="radio" class="type" lay-filter="type" name="type" value="8" title="死亡类">
                                      <input type="radio" class="type" lay-filter="type" name="type" value="9" title="其他">
                                    </div>
                                  </div>

                                  <div class="layui-form-item" id="type_else_div" style="display: none;">
                                      <label for="type_else" class="layui-form-label">
                                          <span class="x-red star"></span>其他</label>
                                      <div class="layui-input-inline">
                                          <textarea id="type_else" class="layui-textarea" style="width: 500px;height: 100px;"></textarea>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                      <label for="L_repass" class="layui-form-label"></label>
                                      <button type="button" class="layui-btn"  onclick="do_add()">增加</button>
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

          table.render({
            elem : '#list',
            cols : [[]]
          });

          table.render({
            elem : '#list_1',
            cols : [[]]
          });

          table.on('tool(list)' , function(obj){
            var layEvent  = obj.event;
            var data      = obj.data;

            if (layEvent === 'info') {
              xadmin.open('呼叫记录问题详情' , '/user/CallBack/CallBackLogInfo?id='+data.id);
            }
          });

          form.on('radio(question_1_1)', function(data){
            var val=data.value;
            if (val == 1) {
              $("#question_1_2_div").hide();
              $(".question_1_2:checked").prop('checked' , false);
              $(".star").html('*');
              form.render();
            } else if (val == 2) {
              $("#question_1_2_div").show();
              $(".star").html('');
            }
          });

          form.on('radio(question_2)', function(data){
            var val=data.value;
            if (val == 1) {
              $("#question_2_else_div").hide();
              $("#question_2_else_1").val('');
              $("#question_2_else_2").val('');
            } else if (val == 2) {
              $("#question_2_else_div").show();
            }
          });

          form.on('radio(question_3)', function(data){
            var val=data.value;
            if (val == 6) {
              $("#question_3_else_div").show();
              $("#question_3_else").val('');
            } else {
              $("#question_3_else_div").hide();
            }
          });

          form.on('radio(question_4)', function(data){
            var val=data.value;
            if (val == 6) {
              $("#question_4_else_div").show();
              $("#question_4_else").val('');
            } else {
              $("#question_4_else_div").hide();
            }
          });

          form.on('radio(question_7_status)', function(data){
            var val=data.value;
            if (val == 3) {
              $("#question_7_status_else_div").show();
              $("#question_7_status_else").val('');
            } else {
              $("#question_7_status_else_div").hide();
            }
          });

          form.on('radio(question_8)', function(data){
            var val=data.value;
            if (val == 4) {
              $("#question_8_else_div").show();
              $("#question_8_else").val('');
            } else {
              $("#question_8_else_div").hide();
            }
          });

          form.on('radio(type)', function(data){
            var val=data.value;
            if (val == 9) {
              $("#type_else_div").show();
              $("#type_else").val('');
            } else {
              $("#type_else_div").hide();
            }
          });

        });

        function select() {
          var user_id = $("#user_id_name").val();
          if (isNaN(user_id) || user_id < 1) {
            layer.msg('请正确输入用户ID' , {icon:2 , time:2000});
          } else {
            $.ajax({
              url:'/user/CallBack/BorrowerInfo',
              type:'post',
              data:{
                'user_id':user_id
              },
              dataType:'json',
              success:function(res) {
                if (res['code'] === 0) {
                  $("#user_id").val(user_id);
                  $("#real_name").html(res['data']['real_name']);
                  $("#idno").html(res['data']['idno']);
                  $("#mobile").html(res['data']['mobile']);
                  res_data = [];
                  for (var i = 0; i < res['data']['deal_info'].length; i++) {
                    res_data[i] = {
                      'user_id'        : res['data']['deal_info'][i]['user_id'],
                      'name'           : res['data']['deal_info'][i]['name'],
                      'borrow_amount'  : res['data']['deal_info'][i]['borrow_amount'],
                      'success_time'   : res['data']['deal_info'][i]['success_time'],
                      'repay_time'     : res['data']['deal_info'][i]['repay_time'],
                      'wait_capital'   : res['data']['deal_info'][i]['wait_capital'],
                      'yes_capital'    : res['data']['deal_info'][i]['yes_capital'],
                      'wait_interest'  : res['data']['deal_info'][i]['wait_interest'],
                      'yes_interest'   : res['data']['deal_info'][i]['yes_interest'],
                      'max_repay_time' : res['data']['deal_info'][i]['max_repay_time'],
                      'overdue_day'    : res['data']['deal_info'][i]['overdue_day']
                    };
                  }
                  table.render({
                    elem           : '#list',
                    toolbar        : '<div>债权信息</div>',
                    defaultToolbar : ['filter'],
                    cols:[[
                      {
                        field : 'user_id',
                        title : '用户ID',
                        align : 'right'
                      },
                      {
                        field : 'name',
                        title : '借款标题',
                        align : 'right'
                      },
                      {
                        field : 'borrow_amount',
                        title : '借款金额',
                        align : 'right'
                      },
                      {
                        field : 'success_time',
                        title : '满标时间',
                        align : 'right'
                      },
                      {
                        field : 'repay_time',
                        title : '借款期限',
                        align : 'right'
                      },
                      {
                        field : 'wait_capital',
                        title : '在途本金',
                        align : 'right'
                      },
                      {
                        field : 'yes_capital',
                        title : '已还本金',
                        align : 'right'
                      },
                      {
                        field : 'wait_interest',
                        title : '在途利息',
                        align : 'right'
                      },
                      {
                        field : 'yes_interest',
                        title : '已还利息',
                        align : 'right'
                      },
                      {
                        field : 'max_repay_time',
                        title : '计划最大还款时间',
                        align : 'right'
                      },
                      {
                        field : 'overdue_day',
                        title : '逾期天数',
                        align : 'right'
                      }
                    ]],
                    data:res_data
                  });

                  table.render({
                    elem           : '#list_1',
                    toolbar        : '<div>呼叫记录</div>',
                    defaultToolbar : ['filter'],
                    page           : true,
                    limit          : 10,
                    limits         : [10 , 20 , 30 , 40 , 50 , 60 , 70 , 80 , 90 , 100],
                    autoSort       : false,
                    cols:[[
                      {
                        field : 'add_user_name',
                        title : '客服姓名'
                      },
                      {
                        field : 'add_time',
                        title : '呼叫时间'
                      },
                      {
                        field : 'question_1',
                        title : '号码状态'
                      },
                      {
                        field : 'question_4',
                        title : '客户状态'
                      },
                      {
                        field : 'question_5',
                        title : '客户标签'
                      },
                      {
                        field : 'remark',
                        title : '跟进记录'
                      },
                      {
                        title   : '操作',
                        toolbar : '#operate',
                        width   : 100
                      }
                    ]],
                    url      : '/user/CallBack/UserCallBackLogList',
                    method   : 'post',
                    where    : {user_id : user_id},
                    response :
                    {
                      statusName : 'code',
                      statusCode : 0,
                      msgName    : 'info',
                      countName  : 'count',
                      dataName   : 'data'
                    }
                  });
                  $(".res_div").show();
                  $("#select_div").hide();
                  $("#reset_select_div").show();
                  $("#user_id_name_div").html('<div style="padding: 7px;">'+user_id+'</div>');
                  if (res['data']['question_6'] > 0) {
                    $(".question_6[value="+res['data']['question_6']+"]").prop('checked' ,true);
                  }
                  if (res['data']['question_7'] != '') {
                    $("#question_7").val(res['data']['question_7']);
                  }
                  if (res['data']['question_7_status'] > 0) {
                    $(".question_7_status[value="+res['data']['question_7_status']+"]").prop('checked' ,true);
                  }
                  if (res['data']['question_7_status'] == 3) {
                    $("#question_7_status_else").val(res['data']['question_7_status_else']);
                    $("#question_7_status_else_div").show();
                  }
                  if (res['data']['question_8'] > 0) {
                    $(".question_8[value="+res['data']['question_8']+"]").prop('checked' ,true);
                  }
                  if (res['data']['question_8'] == 4) {
                    $("#question_8_else").val(res['data']['question_8_else']);
                    $("#question_8_else_div").show();
                  }
                  if (res['data']['type'] > 0) {
                    $(".type[value="+res['data']['type']+"]").prop('checked' ,true);
                  }
                  if (res['data']['type'] == 9) {
                    $("#type_else").val(res['data']['type_else']);
                    $("#type_else_div").show();
                  }
                  form.render();
                  layer.msg(res['info'] , {icon:1 , time:1000});
                } else {
                  layer.msg(res['info'] , {icon:2 , time:2000});
                }
              }
            });
          }
        }

        function reset_select() {
          location.reload();
        }

        function do_add() {
          var user_id                = $("#user_id").val();
          var question_1_1           = $(".question_1_1:checked").val();
          var question_1_2           = $(".question_1_2:checked").val();
          var question_2             = $(".question_2:checked").val();
          var question_2_else_1      = $("#question_2_else_1").val();
          var question_2_else_2      = $("#question_2_else_2").val();
          var question_3             = $(".question_3:checked").val();
          var question_3_else        = $("#question_3_else").val();
          var question_4             = $(".question_4:checked").val();
          var question_4_else        = $("#question_4_else").val();
          var question_5             = $(".question_5:checked").val();
          var question_6             = $(".question_6:checked").val();
          var question_7             = $("#question_7").val();
          var question_7_status      = $(".question_7_status:checked").val();
          var question_7_status_else = $("#question_7_status_else").val();
          var question_8             = $(".question_8:checked").val();
          var question_8_else        = $("#question_8_else").val();
          var remark                 = $("#remark").val();
          var type                   = $(".type:checked").val();
          var type_else              = $("#type_else").val();

          if (user_id == '' || isNaN(user_id)) {
            layer.msg('请正确输入用户ID' , {icon:2 , time:2000});
          } else if (question_1_1 == undefined) {
            layer.msg('请选择号码状态' , {icon:2 , time:2000});
          } else if (question_1_1 == 2 && question_1_2 == undefined) {
            layer.msg('请选择号码状态' , {icon:2 , time:2000});
          } else if (question_1_1 == 1 && question_2 == undefined) {
            layer.msg('请选择接听人是否本人' , {icon:2 , time:2000});
          } else if (question_1_1 == 1 && question_2 == 2 && question_2_else_1 == '') {
            layer.msg('请输入接听人' , {icon:2 , time:2000});
          } else if (question_1_1 == 1 && question_2 == 2 && question_2_else_2 == '') {
            layer.msg('请输入接听人与本人关系' , {icon:2 , time:2000});
          } else if (question_3 == undefined) {
            layer.msg('请选择拨打工具' , {icon:2 , time:2000});
          } else if (question_3 == 6 && question_3_else == '') {
            layer.msg('请输入拨打工具' , {icon:2 , time:2000});
          } else if (question_4 == undefined) {
            layer.msg('请选择客户状态' , {icon:2 , time:2000});
          } else if (question_4 == 6 && question_4_else == '') {
            layer.msg('请输入客户状态' , {icon:2 , time:2000});
          } else if (question_5 == undefined) {
            layer.msg('请选择客户标签' , {icon:2 , time:2000});
          } else if (question_1_1 == 1 && question_6 == undefined) {
            layer.msg('请选择是否添加微信' , {icon:2 , time:2000});
          } else if (question_1_1 == 1 && question_7 == '') {
            layer.msg('请输入关联公司' , {icon:2 , time:2000});
          } else if (question_1_1 == 1 && question_7_status == undefined) {
            layer.msg('请选择公司是否存续' , {icon:2 , time:2000});
          } else if (question_1_1 == 1 && question_7_status == 3 && question_7_status_else == '') {
            layer.msg('请输入公司是否存续的其他' , {icon:2 , time:2000});
          } else if (question_1_1 == 1 && question_8 == undefined) {
            layer.msg('请选择支付宝认证' , {icon:2 , time:2000});
          } else if (question_1_1 == 1 && question_8 == 4 && question_8_else == '') {
            layer.msg('请输入支付宝认证的其他' , {icon:2 , time:2000});
          } else if (question_1_1 == 1 && remark == '') {
            layer.msg('请输入跟进记录' , {icon:2 , time:2000});
          } else if (question_1_1 == 1 && type == undefined) {
            layer.msg('请选择问题类型' , {icon:2 , time:2000});
          } else if (question_1_1 == 1 && type == 9 && type_else == '') {
            layer.msg('请输入问题类型' , {icon:2 , time:2000});
          } else {
            var loading = layer.load(2, {
              shade: [0.3],
              time: 3600000
            });
            $.ajax({
              url:'/user/CallBack/AddCallBackLog',
              type:'post',
              data:{
                user_id                : user_id,
                question_1_1           : question_1_1,
                question_1_2           : question_1_2,
                question_2             : question_2,
                question_2_else_1      : question_2_else_1,
                question_2_else_2      : question_2_else_2,
                question_3             : question_3,
                question_3_else        : question_3_else,
                question_4             : question_4,
                question_4_else        : question_4_else,
                question_5             : question_5,
                question_6             : question_6,
                question_7             : question_7,
                question_7_status      : question_7_status,
                question_7_status_else : question_7_status_else,
                question_8             : question_8,
                question_8_else        : question_8_else,
                remark                 : remark,
                type                   : type,
                type_else              : type_else
              },
              dataType:'json',
              success:function(res){
                layer.close(loading);
                if (res['code'] === 0) {
                  layer.msg(res['info'] , {time:1000,icon:1} , function(){
                    location.reload();
                  });
                } else {
                  layer.alert(res['info']);
                }
              }
            });
          }
        }
      </script>
    </body>
    <script type="text/html" id="operate">
      {{# if(d.info_status == 1){ }}
      <button class="layui-btn layui-btn-xs" title="呼叫记录问题详情" lay-event="info">详情</button>
      {{# } }}
    </script>
</html>