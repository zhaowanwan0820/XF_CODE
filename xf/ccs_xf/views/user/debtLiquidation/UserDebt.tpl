<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>用户债权查询</title>
        <meta name="renderer" content="webkit">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
        <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
        <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
        <script type="text/javascript" src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
        <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
        <style type="text/css">
          .layui-form-item {
            margin-bottom: 7px;
          }
          .layui-form-label {
            width: 200px;
            text-align: left;
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
                <a href="">下车专栏</a>
                <a>
                    <cite>用户债权查询</cite></a>
            </span>
          <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38" onclick="location.reload()" title="刷新">
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
                                  <label for="user_id" class="layui-form-label">
                                      <span class="x-red">*</span>用户ID</label>
                                  <div class="layui-input-inline" id="user_id_div">
                                      <input type="text" name="user_id" id="user_id" autocomplete="off" class="layui-input" value="">
                                  </div>
                              </div>

                              <div class="res_div" style="display: none;">

                                <div class="layui-form-item">
                                    <label class="layui-form-label">
                                        用户姓名</label>
                                    <div class="layui-input-inline">
                                        <div id="user_real_name" style="padding: 7px;"></div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">
                                        用户手机号</label>
                                    <div class="layui-input-inline">
                                        <div id="user_mobile" style="padding: 7px;"></div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">
                                        用户在途份额</label>
                                    <div class="layui-input-inline">
                                        <div id="deal_load_wait_capital" style="padding: 7px;"></div>
                                    </div>
                                    <div class="layui-form-mid layui-word-aux">普惠 + 智多新</div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">
                                        用户所在礼包档位</label>
                                    <div class="layui-input-inline">
                                        <div id="gift_name" style="padding: 7px;"></div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">
                                        此档位兑换区间</label>
                                    <div class="layui-input-inline">
                                        <div id="gift_min_max" style="padding: 7px;"></div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">
                                        此档位兑换总数</label>
                                    <div class="layui-input-inline">
                                        <div id="gift_exchange_user" style="padding: 7px;"></div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">
                                        此档位商品预览</label>
                                    <div class="layui-input-inline" id="goods_url" style="padding: 7px;"></div>
                                </div>

                              </div>

                              <div class="layui-form-item">
                                  <label for="L_repass" class="layui-form-label"></label>
                                  <button type="button" class="layui-btn" onclick="select()" id="select_div">查询</button>
                                  <button type="button" class="layui-btn layui-btn-primary" onclick="reset_select()" id="reset_select_div" style="display: none;">重置</button>
                              </div>
                          </form>
                        </div>

                        <div class="layui-card-body res_div" style="display: none;">
                          <table class="layui-table layui-form" lay-filter="list" id="list_1">
                          </table>
                        </div>

                        <div class="layui-card-body res_div" style="display: none;">
                          <div class="layui-collapse" lay-filter="test">
                            <div class="layui-colla-item">
                              <h2 class="layui-colla-title"><i class="layui-icon layui-colla-icon"></i></h2>
                              <div class="layui-colla-content layui-show">
                                <form class="layui-form">

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red"></span><b>一、用户联系状态</b></label>
                                    <div class="layui-input-inline" style="width: 700px;">
                                    </div>
                                  </div>

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

                                  <div class="layui-form-item" id="question_2_div">
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
                                      <span class="x-red"></span><b>二、沟通内容记录</b></label>
                                    <div class="layui-input-inline" style="width: 700px;">
                                    </div>
                                  </div>

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red"></span>3.客户（出借人）对当前债权的态度</label>
                                    <div class="layui-input-inline" style="width: 700px;">
                                      <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="1" title="不要了，就当钱没了">
                                      <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="2" title="可以接收商品兑付">
                                      <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="3" title="要求现金兑付，坚持不要商品兑付">
                                      <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="4" title="等立案，目前不选择任何兑付方式">
                                      <input type="radio" class="question_3" lay-filter="question_3" name="question_3" value="5" title="其他">
                                    </div>
                                  </div>

                                  <div class="layui-form-item" id="question_3_else_div" style="display: none;">
                                      <label for="question_3_else" class="layui-form-label">
                                          <span class="x-red"></span>其他</label>
                                      <div class="layui-input-inline">
                                          <textarea id="question_3_else" class="layui-textarea" style="width: 700px;height: 100px;"></textarea>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red"></span>4.客户（出借人）换购意愿</label>
                                    <div class="layui-input-inline" style="width: 700px;">
                                      <input type="radio" class="question_4" lay-filter="question_4" name="question_4" value="1" title="愿意接受换购商品，觉得商品性价比可以">
                                      <input type="radio" class="question_4" lay-filter="question_4" name="question_4" value="2" title="不愿意接受换购商品，觉得商品性价比不高">
                                      <input type="radio" class="question_4" lay-filter="question_4" name="question_4" value="3" title="不愿意接受换购商品，没有满意的商品">
                                      <input type="radio" class="question_4" lay-filter="question_4" name="question_4" value="4" title="其他">
                                    </div>
                                  </div>

                                  <div class="layui-form-item" id="question_4_else_div" style="display: none;">
                                      <label for="question_4_else" class="layui-form-label">
                                          <span class="x-red"></span>其他</label>
                                      <div class="layui-input-inline">
                                          <textarea id="question_4_else" class="layui-textarea" style="width: 700px;height: 100px;"></textarea>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                    <label class="layui-form-label">
                                      <span class="x-red"></span>5.客户（出借人）对商品的选择偏好</label>
                                    <div class="layui-input-inline" style="width: 700px;">
                                      <input type="radio" class="question_5" lay-filter="question_5" name="question_5" value="1" title="生活日用品类">
                                      <input type="radio" class="question_5" lay-filter="question_5" name="question_5" value="2" title="家具电器类">
                                      <input type="radio" class="question_5" lay-filter="question_5" name="question_5" value="3" title="服装服饰类">
                                      <input type="radio" class="question_5" lay-filter="question_5" name="question_5" value="4" title="其他">
                                    </div>
                                  </div>

                                  <div class="layui-form-item" id="question_5_else_div" style="display: none;">
                                      <label for="question_5_else" class="layui-form-label">
                                          <span class="x-red"></span>其他</label>
                                      <div class="layui-input-inline">
                                          <textarea id="question_5_else" class="layui-textarea" style="width: 700px;height: 100px;"></textarea>
                                      </div>
                                  </div>

                                  <div class="layui-form-item">
                                      <label for="remark" class="layui-form-label">
                                          <span class="x-red"></span><b>三、备注</b></label>
                                      <div class="layui-input-inline">
                                          <textarea id="remark" class="layui-textarea" style="width: 700px;height: 100px;"></textarea>
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
        layui.use(['form', 'layer', 'table', 'laydate'] , function(){
          form    = layui.form;
          layer   = layui.layer;
          table   = layui.table;
          laydate = layui.laydate;

          table.render({
            elem : '#list_1',
            cols : [[]]
          });

          table.on('tool(list)' , function(obj){
            var layEvent  = obj.event;
            var data      = obj.data;

            if (layEvent === 'info') {
              xadmin.open('呼叫记录问题详情' , '/user/DebtLiquidation/CallBackLogInfo?id='+data.id);
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
            if (val == 5) {
              $("#question_3_else_div").show();
            } else {
              $("#question_3_else_div").hide();
              $("#question_3_else").val('');
            }
          });

          form.on('radio(question_4)', function(data){
            var val=data.value;
            if (val == 4) {
              $("#question_4_else_div").show();
            } else {
              $("#question_4_else_div").hide();
              $("#question_4_else").val('');
            }
          });

          form.on('radio(question_5)', function(data){
            var val=data.value;
            if (val == 4) {
              $("#question_5_else_div").show();
            } else {
              $("#question_5_else_div").hide();
              $("#question_5_else").val('');
            }
          });
        });

        function select() {
          var user_id = $("#user_id").val();
          if (isNaN(user_id) || user_id < 1) {
            layer.msg('请正确输入用户ID' , {icon:2 , time:2000});
          } else {
            $.ajax({
              url:'/user/DebtLiquidation/UserDebt',
              type:'post',
              data:{
                'user_id':user_id
              },
              dataType:'json',
              success:function(res) {
                if (res['code'] === 0) {
                  $("#user_id_div").html('<div style="padding: 7px;">'+user_id+'</div><input type="hidden" name="user_id" id="user_id" value='+user_id+'>');
                  $("#user_real_name").html(res['data']['user_real_name']);
                  $("#user_mobile").html(res['data']['user_mobile']);
                  $("#deal_load_wait_capital").html(res['data']['deal_load_wait_capital']);
                  $("#gift_name").html(res['data']['gift_name']);
                  $("#gift_min_max").html(res['data']['gift_min_max']);
                  $("#gift_exchange_user").html(res['data']['gift_exchange_user']);
                  if (res['data']['goods_url']) {
                    $("#goods_url").html('<a href="'+res['data']['goods_url']+'" target="_blank" style="color: blue;">点击预览商品</a>');
                  } else {
                    $("#goods_url").html('');
                  }
                  $("#remark").html(res['data']['remark']);
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
                        title : '客服姓名',
                        width   : 150
                      },
                      {
                        field : 'add_time',
                        title : '呼叫时间',
                        width   : 150
                      },
                      {
                        field : 'question_1',
                        title : '号码状态',
                        width   : 150
                      },
                      {
                        field : 'question_2',
                        title : '是否本人'
                      },
                      {
                        title   : '操作',
                        toolbar : '#operate',
                        width   : 100
                      }
                    ]],
                    url      : '/user/DebtLiquidation/UserCallBackLogList',
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
          var user_id         = $("#user_id").val();
          var question_1_1           = $(".question_1_1:checked").val();
          var question_1_2           = $(".question_1_2:checked").val();
          var question_2             = $(".question_2:checked").val();
          var question_2_else_1      = $("#question_2_else_1").val();
          var question_2_else_2      = $("#question_2_else_2").val();

          // var question_1      = $(".question_1:checked").val();
          // var question_1_else = $("#question_1_else").val();
          // var question_2      = $(".question_2:checked").val();
          // var question_2_else = $("#question_2_else").val();
          var question_3      = $(".question_3:checked").val();
          var question_3_else = $("#question_3_else").val();
          var question_4      = $(".question_4:checked").val();
          var question_4_else = $("#question_4_else").val();
          var question_5      = $(".question_5:checked").val();
          var question_5_else = $("#question_5_else").val();
          var remark          = $("#remark").val();

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
          } else {
            var loading = layer.load(2, {
              shade: [0.3],
              time: 3600000
            });
            $.ajax({
              url:'/user/DebtLiquidation/AddCallBackLog',
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
                question_5_else        : question_5_else,
                remark                 : remark
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