<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>SQL查询</title>
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
                <a href="">首页</a>
                <a>
                    <cite>SQL查询</cite></a>
            </span>
            <a class="layui-btn layui-btn-small" style="line-height:1.6em;margin-top:3px;float:right;background-color: #f36b38" onclick="location.reload()" title="刷新">
                <i class="layui-icon layui-icon-refresh" style="line-height:30px"></i>
            </a>
        </div>
        <div class="layui-fluid">
            <div class="layui-row layui-col-space15">

                <div class="layui-col-md12" style="overflow-x:auto;">

                    <div class="layui-card">
                        <div class="layui-card-body">
                            <div class="layui-collapse" lay-filter="test">
                                <div class="layui-colla-item">
                                <h2 class="layui-colla-title">SQL查询<i class="layui-icon layui-colla-icon"></i></h2>
                                <div class="layui-colla-content layui-show">
                                  <form class="layui-form">

                                      <div class="layui-form-item layui-block">
                                        <div class="layui-inline">
                                          <label class="layui-form-label">数据库</label>
                                          <div class="layui-input-inline" style="width: 1200px">
                                            <input type="radio" class="db" name="db" value="1" title="尊享" checked>
                                            <input type="radio" class="db" name="db" value="2" title="普惠">
                                            <input type="radio" class="db" name="db" value="3" title="合同">
                                            <input type="radio" class="db" name="db" value="4" title="offline">
                                          </div>
                                        </div>

                                        <div class="layui-inline">
                                          <label class="layui-form-label">SQL语句</label>
                                          <div class="layui-input-inline" style="width: 1200px">
                                            <textarea  name="sql" id="sql" class="layui-textarea" style="font-size:17px"></textarea>
                                          </div>
                                        </div>

                                      </div>
                                      
                                      <div class="layui-form-item">
                                        <div class="layui-input-block">
                                          <button type="button" class="layui-btn" lay-filter="demo1" onclick="change_sql()">立即查询</button>
                                          <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                        </div>
                                      </div>
                                    </form>
                                </div>
                              </div>
                            </div>
                        </div>

                        <div class="layui-card-body" id="body">
                            <table class="layui-table">
                                <thead>
                                    <tr id="title">
                                    </tr>
                                </thead>
                                <tbody id="data">
                                </tbody>
                            </table>
                        </div>
                        <div class="layui-card-body ">
                            <div class="page">
                                <div>
                                    <{$pages}></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    <script>
    layui.use(['laydate', 'form']);

    function change_sql(sql = '') {
      if (sql == '') {
        sql = $("#sql").val();
      }
      var db = $(".db:checked").val();
      if (db != 1 && db != 2 && db != 3 && db != 4) {
        layer.alert('请正确选择数据库');
      } else {
        db = parseInt(db)-1;
        var loading = layer.load(2, {
            shade: [0.3],
            time: 3600000
        });
        $("#body").prop('style','width: 0px;');
        $("#title").html('');
        $("#data").html('');
        $.ajax({
          url:"/user/SqlQuery/Index",
          type:"post",
          data:{'db':db,'sql':sql},
          dataType:'json',
          success:function(res){
            if (res['code'] == 0) {
              var width = 0;
              var title = '';
              for (var t in res['data'][0]) {
                width = width + 150;
                title += '<th>'+t+'</th>';
              }
              $("#body").prop('style','width:'+width+'px;');
              $("#title").html(title);
              var data = res['data'];
              var str  = '';
              for (var k in data) {
                str += '<tr>';
                for (var v in data[k]) {
                  str += '<td>'+data[k][v]+'</td>';
                }
                str += '</tr>';
              }
              $("#data").html(str);
            } else {
              $("#body").prop('style','width: 0px;');
              $("#title").html('');
              $("#data").html('');
              layer.alert(res['info']);
            }
            layer.close(loading);
          }
        });
      }
    }
    </script>
</html>