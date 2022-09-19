<!DOCTYPE html>
<html class="x-admin-sm">
    
    <head>
        <meta charset="UTF-8">
        <title>消息详情</title>
        <meta name="renderer" content="webkit">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
        <link rel="stylesheet" href="<{$CONST.cssPath}>/font.css">
        <link rel="stylesheet" href="<{$CONST.cssPath}>/xadmin.css">
        <script type="text/javascript" src="<{$CONST.layuiPath}>/layui.js" charset="utf-8"></script>
        <script type="text/javascript" src="<{$CONST.jsPath}>/xadmin.js"></script>
        <!-- 让IE8/9支持媒体查询，从而兼容栅格 -->
        <!--[if lt IE 9]>
            <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
            <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
        <![endif]--></head>
    
    <body>
        <div class="layui-fluid">
            <div class="layui-row">
              <form class="layui-form">

                <input type="hidden" name="id" value="<{$res['id']}>">

                <div class="layui-form-item" id="time_div">
                    <label for="start_time" class="layui-form-label">
                        <span class="x-red">*</span>发布时间</label>
                    <div class="layui-input-inline">
                        <input type="text" id="start_time" name="start_time" disabled autocomplete="off" class="layui-input" value="<{$res['start_time']}>"></div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        <span class="x-red">*</span>推送用户范围</label>
                    <div class="layui-input-inline">
                        <input type="radio" title="全量在途用户" lay-filter="user_1" <{if $res['user_scope'] == 1 }>checked<{/if}>>
                        <input type="radio" title="指定在途用户" lay-filter="user_1" <{if $res['user_scope'] == 2 }>checked<{/if}>>
                    </div>
                </div>

                <div id="zhiding" <{if $res['user_scope'] == 1 }>style="display: none;"<{/if}>>

                  <div class="layui-form-item">
                      <label class="layui-form-label">
                          <span class="x-red">*</span>指定用户方式</label>
                      <div class="layui-input-inline">
                          <!-- <input type="radio" title="输入项目ID" lay-filter="user_2" <{if $res['type'] == 1 }>checked<{/if}>> -->
                          <input type="radio" title="输入借款编号" lay-filter="user_2" <{if $res['type'] == 2 }>checked<{/if}>>
                          <input type="radio" title="输入用户ID" lay-filter="user_2" <{if $res['type'] == 3 }>checked<{/if}>>
                          <input type="radio" title="上传用户ID" lay-filter="user_2" <{if $res['type'] == 4 }>checked<{/if}>>
                      </div>
                  </div>

                  <div class="layui-form-item" id="platform_div" <{if $res['type'] gt 2 }>style="display: none;"<{/if}>>
                      <label class="layui-form-label">
                          <span class="x-red">*</span>指定平台</label>
                      <div class="layui-input-inline">
                          <input type="radio" title="尊享" lay-filter="platform" <{if $res['platform'] == 1 }>checked<{/if}>>
                          <input type="radio" title="普惠" lay-filter="platform" <{if $res['platform'] == 2 }>checked<{/if}>>
                      </div>
                  </div>

                  <div class="layui-form-item layui-form-text" id="project_id_div" <{if $res['type'] != 1 }>style="display: none;"<{/if}>>
                      <label for="project_id" class="layui-form-label">
                          <span class="x-red">*</span>输入项目ID</label>
                      <div class="layui-input-inline">
                          <textarea placeholder="请输入项目ID（多个以英文逗号,分隔）" id="project_id" name="project_id" class="layui-textarea" style="width: 400px;" disabled><{$res['project_id']}></textarea>
                      </div>
                  </div>

                  <div class="layui-form-item layui-form-text" id="deal_id_div" <{if $res['type'] != 2 }>style="display: none;"<{/if}>>
                      <label for="deal_id" class="layui-form-label">
                          <span class="x-red">*</span>输入借款编号</label>
                      <div class="layui-input-inline">
                          <textarea placeholder="请输入借款编号（多个以英文逗号,分隔）" id="deal_id" name="deal_id" class="layui-textarea" style="width: 400px;" disabled><{$res['deal_id']}></textarea>
                      </div>
                  </div>

                  <div class="layui-form-item layui-form-text" id="user_id_div" <{if $res['type'] != 3 }>style="display: none;"<{/if}>>
                      <label for="user_id" class="layui-form-label">
                          <span class="x-red">*</span>输入用户ID</label>
                      <div class="layui-input-inline">
                          <textarea placeholder="请输入用户ID（多个以英文逗号,分隔）" id="user_id" name="user_id" class="layui-textarea" style="width: 400px;" disabled><{$res['user_id']}></textarea>
                      </div>
                  </div>

                  <div class="layui-form-item layui-form-text" id="user_id_file_div" <{if $res['type'] != 4 }>style="display: none;"<{/if}>>
                      <label for="user_id" class="layui-form-label">
                          <span class="x-red">*</span>上传用户ID</label>
                      <div class="layui-input-inline">
                          <button type="button" class="layui-btn" id="upload" disabled>
                            <i class="layui-icon">&#xe67c;</i>上传文件
                          </button>
                          <input type="hidden" name="user_id_file" id="user_id_file" value="<{$res['upload_file']}>">
                          <span id="file_name"><{$res['basename']}></span>
                      </div>
                      <div class="layui-form-mid layui-word-aux"><span class="x-red">*</span>请上传 xls 文件（数据量不可超过一万行） <a href="/user/Message/DownloadUserIdTemplate" style="color: blue;">下载模板</a></div>
                  </div>

                </div>

                <div class="layui-form-item">
                    <label for="title" class="layui-form-label">
                        <span class="x-red">*</span>标题</label>
                    <div class="layui-input-inline">
                        <input type="text" id="title" name="title" lay-verify="required" autocomplete="off" class="layui-input" style="width: 400px;" value="<{$res['title']}>" disabled></div>
                </div>

                <div class="layui-form-item">
                    <label for="abstract" class="layui-form-label">
                        <span class="x-red">*</span>摘要</label>
                    <div class="layui-input-inline">
                        <input type="text" id="abstract" name="abstract" lay-verify="required" autocomplete="off" class="layui-input" style="width: 400px;" value="<{$res['abstract']}>" disabled></div>
                </div>

                <div class="layui-form-item layui-form-text">
                    <label for="user_id" class="layui-form-label">
                        <span class="x-red">*</span>内容</label>
                    <div class="layui-input-inline" style="background-color: white;width: 800px;">
                      <textarea name="content" id="content" style="display: none;"><{$res['content']}></textarea>
                    </div>
                </div>

              </form>
            </div>
        </div>
        <script>
        layui.use(['layedit' , 'layer' , 'form' , 'laydate' , 'upload' , 'element'] , function(){
          var layedit = layui.layedit;
          var laydate = layui.laydate;
          var form    = layui.form;
          var upload  = layui.upload;
          var element = layui.element;

          laydate.render({
            elem: '#start_time'
            ,type: 'datetime'
          });

          var index = layedit.build('content' , {tool:[]}); //建立编辑器

        });

      </script>
    </body>

</html>