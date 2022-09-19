<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>编辑借款企业</title>
        <meta name="renderer" content="webkit">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
        <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
        <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
        <script type="text/javascript" src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
        <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
        <script type="text/javascript" src="/js/formSelects-v4.js"></script>
        <link rel="stylesheet" type="text/css" href="/js/formSelects-v4.css">

        <!-- 让IE8/9支持媒体查询，从而兼容栅格 -->
        <!--[if lt IE 9]>
            <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
            <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
        <![endif]--></head>
    
    <body>
        <div class="layui-fluid">
            <div class="layui-row">
                <form class="layui-form">

                    <br>
                    <div class="layui-form-item">
                        <label for="name" class="layui-form-label">
                            用款方</label>
                        <div class="layui-input-inline">
                            <input type="hidden" value="<{$res['id']}>" id="id" autocomplete="off" class="layui-input" disabled>
                            <input type="text" value="<{$res['name']}>" id="name" autocomplete="off" class="layui-input" disabled style="background:#c2c2c2;">
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label  class="layui-form-label">
                            借款企业</label>
                        <div class="layui-input-block">
                          <select name="city" xm-select="select1" xm-select-search="#" xm-select-max="50" xm-select-search-type="dl">
                          </select>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="company" class="layui-form-label"></label>
                        <div class="layui-input-inline">
                          <button type="button" class="layui-btn" onclick="add()">添加</button>
                        </div>
                    </div>

                    <div class="layui-card-body ">
                        <table class="layui-table layui-form">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" lay-skin="primary" id="check_all" lay-filter="check_all">
                                    </th>
                                    <th>序号</th>
                                    <th>借款企业</th>
                                </tr>
                            </thead>
                            <tbody>
                              <{foreach $result as $k => $v}>
                                <tr>
                                    <td>
                                        <input type="checkbox" lay-skin="primary" class="check" lay-filter="check" value="<{$v['id']}>">
                                    </td>
                                    <td><{$v['id']}></td>
                                    <td><{$v['company_name']}></td>
                                </tr>
                              <{/foreach}>
                            </tbody>
                        </table>
                    </div>

                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn layui-btn-danger" onclick="del_company()">批量删除</button>
                        <button type="button" class="layui-btn" onclick="set_close()">返回</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        function set_close()
        {
          var index = parent.layer.getFrameIndex(window.name);
          parent.layer.close(index);
        }

        function del_company()
        {
          var data = $(".check:checked");
          if (data.length == 0) {
            layer.alert('请选择要删除的借款企业');
          } else {
            var id = new Array;
            for (var i = 0; i < data.length; i++) {
              id[i] = data[i].value;
            }
            layer.confirm('确认要删除'+data.length+'个借款企业吗？',
              function(index) {
                $.ajax({
                  url:'/user/Debt/EditUsingMoneySide',
                  type:'post',
                  data:{
                    'id':id,
                    'type':2
                  },
                  dataType:'json',
                  success:function(res) {
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
            );
          }
        }

        layui.use(['form' , 'layer'], function(){
          var form = layui.form;
          //全选
          form.on('checkbox(check_all)', function (data) {
              var a = data.elem.checked;
              if (a == true) {
                  $(".check").prop("checked", true);
                  form.render('checkbox');
              } else {
                  $(".check").prop("checked", false);
                  form.render('checkbox');
              }
   
          });
          //有一个未选中全选取消选中
          form.on('checkbox(check)', function (data) {
              var item = $(".check");
              for (var i = 0; i < item.length; i++) {
                  if (item[i].checked == false) {
                      $("#check_all").prop("checked", false);
                      form.render('checkbox');
                      break;
                  }
              }
              //如果都勾选了  勾上全选
              var  all = item.length;
              for (var i = 0; i < item.length; i++) {
                  if (item[i].checked == true) {
                      all--;
                  }
              }
              if(all == 0){
                  $("#check_all").prop("checked", true);
                  form.render('checkbox');
              }
          });

          var formSelects = layui.formSelects;

          formSelects.config('select1', {
            type: 'post',               //请求方式: post, get, put, delete...
            header: {},                 //自定义请求头
            data: {'type':3},           //自定义除搜索内容外的其他数据
            searchUrl: '/user/Debt/EditUsingMoneySide',
                                        //搜索地址, 默认使用xm-select-search的值, 此参数优先级高
            searchName: 'name',         //自定义搜索内容的key值
            searchVal: '',              //自定义搜索内容, 搜素一次后失效, 优先级高于搜索框中的值
            keyName: 'title',           //自定义返回数据中name的key, 默认 name
            keyVal: 'value',            //自定义返回数据中value的key, 默认 value
            keySel: 'selected',         //自定义返回数据中selected的key, 默认 selected
            keyDis: 'disabled',         //自定义返回数据中disabled的key, 默认 disabled
            keyChildren: 'children',    //联动多选自定义children
            delay: 500,                 //搜索延迟时间, 默认停止输入500ms后开始搜索
            direction: 'auto',          //多选下拉方向, auto|up|down
            response: {
                statusCode: 0,          //成功状态码
                statusName: 'code',     //code key
                msgName: 'info',        //msg key
                dataName: 'data'        //data key
            },
            success: function(id, url, searchVal, result){      //使用远程方式的success回调
                // console.log(id);        //组件ID xm-select
                // console.log(url);       //URL
                // console.log(searchVal); //搜索的value
                // console.log(result);    //返回的结果
            },
            error: function(id, url, searchVal, err){           //使用远程方式的error回调
                //同上
                // console.log(err);   //err对象
            },
            beforeSuccess: function(id, url, searchVal, result){        //success之前的回调, 干嘛呢? 处理数据的, 如果后台不想修改数据, 你也不想修改源码, 那就用这种方式处理下数据结构吧
                // console.log(id);        //组件ID xm-select
                // console.log(url);       //URL
                // console.log(searchVal); //搜索的value
                // console.log(result);    //返回的结果
                 
                return result;  //必须return一个结果, 这个结果要符合对应的数据结构
            },
            beforeSearch: function(id, url, searchVal){         //搜索前调用此方法, return true将触发搜索, 否则不触发
                if(!searchVal){//如果搜索内容为空,就不触发搜索
                    return false;
                }
                return true;
            },
            clearInput: false,          //当有搜索内容时, 点击选项是否清空搜索内容, 默认不清空
          }, false);
        });
        // formSelects.value('select1', 'val');

        function add(){
          var formSelects = layui.formSelects;
          var company     = formSelects.value('select1', 'val');
          var id          = $("#id").val();
          $.ajax({
            url:'/user/Debt/EditUsingMoneySide',
            type:'post',
            data:{
              'id':id,
              'company':company,
              'type':1
            },
            dataType:'json',
            success:function(res) {
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
      </script>
    </body>

</html>