<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>编辑用户银行卡</title>
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

                    <input type="hidden" name="id" value="<{$info['id']}>" id="id">

                    <div class="layui-form-item">
                        <label for="user_id" class="layui-form-label">
                            用户ID</label>
                        <div class="layui-input-inline">
                            <input type="text" id="user_id" autocomplete="off" class="layui-input" disabled value="<{$info['user_id']}>">
                        </div>
                        <div class="layui-form-mid layui-word-aux">
                          不可修改
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="mobile" class="layui-form-label">
                            用户手机号</label>
                        <div class="layui-input-inline">
                            <input type="text" id="mobile" autocomplete="off" class="layui-input" disabled value="<{$user['mobile']}>">
                        </div>
                        <div class="layui-form-mid layui-word-aux">
                          不可修改
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="name" class="layui-form-label">
                            银行名称</label>
                        <div class="layui-input-inline">
                            <input type="text" id="name" autocomplete="off" class="layui-input" disabled value="<{$bank['name']}>">
                        </div>
                        <div class="layui-form-mid layui-word-aux">
                          不可修改
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="bankzone" class="layui-form-label">
                            开户行名称</label>
                        <div class="layui-input-inline" style="width: 500px">
                          <select id="bankzone" xm-select="select1" xm-select-search="" xm-select-max="50" xm-select-search-type="dl" xm-select-radio="">
                          </select>
                        </div>
                        <div class="layui-form-mid layui-word-aux" id="bankzone_status"><i class="iconfont" style="color: #FF5722;">&#xe6b6;</i></div>
                    </div>

                    <div class="layui-form-item">
                        <label for="card_name" class="layui-form-label">
                            开户人姓名</label>
                        <div class="layui-input-inline">
                            <input type="text" id="card_name" autocomplete="off" class="layui-input" disabled value="<{$info['card_name']}>">
                        </div>
                        <div class="layui-form-mid layui-word-aux">
                          不可修改
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="bankcard" class="layui-form-label">
                            银行卡号</label>
                        <div class="layui-input-inline">
                            <input type="text" id="bankcard" autocomplete="off" class="layui-input" disabled value="<{$info['bankcard']}>">
                        </div>
                        <div class="layui-form-mid layui-word-aux">
                          不可修改
                        </div>
                    </div>

                    <div class="layui-form-item">
                      <label class="layui-form-label">
                            <span class="x-red">*</span>验证状态</label>
                      <div class="layui-input-inline">
                        <input type="radio" class="verify_status" name="verify_status" value="1" title="有效" <{if $info['verify_status'] eq '1'}>checked<{/if}>>
                        <input type="radio" class="verify_status" name="verify_status" value="2" title="无效" <{if $info['verify_status'] eq '0'}>checked<{/if}>>
                      </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="notes" class="layui-form-label">
                            <span class="x-red">*</span>备注</label>
                        <div class="layui-input-inline">
                            <textarea id="notes" class="layui-textarea"><{$info['notes']}></textarea>
                        </div>
                        <div class="layui-form-mid layui-word-aux">
                          1-30个字符
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label for="L_repass" class="layui-form-label"></label>
                        <button type="button" class="layui-btn"  onclick="do_add()">保存</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        layui.use(['form', 'layer'] , function(){

          var formSelects = layui.formSelects;

          formSelects.config('select1', {
            type: 'post',               //请求方式: post, get, put, delete...
            header: {},                 //自定义请求头
            data: {},                   //自定义除搜索内容外的其他数据
            searchUrl: '/user/Debt/CheckBankzone',
                                        //搜索地址, 默认使用xm-select-search的值, 此参数优先级高
            searchName: 'bankzone',     //自定义搜索内容的key值
            searchVal: '<{$info['bankzone']}>',              //自定义搜索内容, 搜素一次后失效, 优先级高于搜索框中的值
            keyName: 'name',            //自定义返回数据中name的key, 默认 name
            keyVal: 'id',               //自定义返回数据中value的key, 默认 value
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

        function chenge_bankzone(value) {
          $.ajax({
            url:'/user/Debt/CheckBankzone',
            type:'post',
            data:{
              'bankzone':value
            },
            dataType:'json',
            success:function(res) {
              if (res['code'] === 0) {
                $("#bankzone_status").html('<i class="iconfont" style="color: #009688;">&#xe6b1;</i>');
              } else {
                $("#bankzone_status").html('<i class="iconfont" style="color: #FF5722;">&#xe6b6;</i>');
              }
            }
          });
        }

        function do_add() {
          var id            = $("#id").val();
          var formSelects   = layui.formSelects;
          var bankzone      = formSelects.value('select1', 'val')[0];
          var verify_status = $(".verify_status:checked").val();
          var notes         = $("#notes").val();
          if (id == '') {
            layer.alert('请输入银行卡ID');
          } else if (bankzone == '') {
            layer.alert('请输入开户行名称');
          } else if (verify_status != 1 && verify_status != 2) {
            layer.alert('请选择验证状态');
          } else if (notes == '') {
            layer.alert('请输入备注');
          } else if (notes.length > 30) {
            layer.alert('备注不能超过30个字符');
          } else {
            $.ajax({
              url:'/user/Debt/EditUserBankCard',
              type:'post',
              data:{
                'id':id,
                'bankzone':bankzone,
                'verify_status':verify_status,
                'notes':notes
              },
              dataType:'json',
              success:function(res) {
                if (res['code'] === 0) {
                  layer.msg(res['info'] , {time:1000,icon:1} , function(){
                    parent.location.reload();
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.layer.close(index);
                  });
                } else {
                  layer.alert(res['info']);
                }
              }
            });
          }
        }

        window.onload = function () {
          var value = "<{$info['bankzone']}>";
          setTimeout(function () {
            chenge_bankzone(value);
          }, 500);
        }
      </script>
    </body>

</html>