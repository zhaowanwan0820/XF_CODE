<?php if (!defined('THINK_PATH')) exit();?>﻿

<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/style.css" />
<script type="text/javascript">
 	var VAR_MODULE = "<?php echo conf("VAR_MODULE");?>";
	var VAR_ACTION = "<?php echo conf("VAR_ACTION");?>";
	var MODULE_NAME	=	'<?php echo MODULE_NAME; ?>';
	var ACTION_NAME	=	'<?php echo ACTION_NAME; ?>';
	var ROOT = '__APP__';
	var ROOT_PATH = '<?php echo APP_ROOT; ?>';
	var CURRENT_URL = '<?php echo trim($_SERVER['REQUEST_URI']);?>';
	var INPUT_KEY_PLEASE = "<?php echo L("INPUT_KEY_PLEASE");?>";
	var TMPL = '__TMPL__';
	var APP_ROOT = '<?php echo APP_ROOT; ?>';
    var IMAGE_SIZE_LIMIT = '1';
</script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.timer.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/script.js"></script>
<script type="text/javascript" src="__ROOT__/static/admin/lang.js"></script>
<script type='text/javascript'  src='__ROOT__/static/admin/kindeditor/kindeditor.js'></script>
</head>
<body>
<div id="info"></div>

<script type="text/javascript" src="__TMPL__Common/js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.weebox.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<div class="main">
<div class="main_title">短信任务列表</div>
<div class="blank5"></div>
<?php if (!$_isChecker) { ?>
    <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();" />
<?php } ?>

<form name="search" action="/m.php?m=SmsTask&a=index" method="post" id="form1">
    <div class="search_row">
    短信内容：<input type="text" id="content" name="content" value="<?php echo trim($_REQUEST['content']);?>" style="width:160px;" />
    短信发送时间：<input type="text" id="begin" onclick="show_cal(this)" class="textbox" name="begin" value="<?php echo trim($_REQUEST['begin']);?>" style="width:135px;" />
    至<input type="text" id="end" class="textbox" onclick="show_cal(this)" name="end" value="<?php echo trim($_REQUEST['end']);?>" style="width:135px;" />
    工单编号：<input type="text" name="work_num" class="textbox" value="<?php echo trim($_REQUEST['work_num']);?>">
    任务状态：
    <select name="task_status" id="task_status">
      <option value="0" <?php if(intval($_REQUEST['task_status']) == 0): ?>selected="selected"<?php endif; ?>>全部</option>
      <?php if($_isChecker != 1): ?><option value="1" <?php if(intval($_REQUEST['task_status']) == 1): ?>selected="selected"<?php endif; ?>>待提交</option><?php endif; ?>
      <option value="2" <?php if(intval($_REQUEST['task_status']) == 2): ?>selected="selected"<?php endif; ?>>审核中</option>
      <option value="3" <?php if(intval($_REQUEST['task_status']) == 3): ?>selected="selected"<?php endif; ?>>审核通过</option>
      <option value="4" <?php if(intval($_REQUEST['task_status']) == 4): ?>selected="selected"<?php endif; ?>>审核未通过</option>
      <option value="5" <?php if(intval($_REQUEST['task_status']) == 5): ?>selected="selected"<?php endif; ?>>短信发送中</option>
      <option value="6" <?php if(intval($_REQUEST['task_status']) == 6): ?>selected="selected"<?php endif; ?>>发送成功</option>
    </select>
    <input type="hidden" value="SmsTask" name="m" /> 
    <input type="hidden" value="index" name="a" />
    <input type="submit" name="search" class="button" value="<?php echo L("SEARCH");?>" />
    </div>

<!-- Think 系统列表组件开始 -->
    <table id="dataTable" class="dataTable" cellpadding=“0” cellspacing=“0”>
        <tr>
            <td colspan="15" class="topTd">&nbsp;</td>
        </tr>
        <tr class="row">
            <!--
            <th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th>
            -->
            <th>编号</th>
            <th>短信内容</th>
            <th>创建人</th>
            <th>创建时间</th>
            <th>数据操作员</th>
            <th>数据条数</th>
            <th>工单编号</th>
            <th>定时/及时发送</th>
            <th>发送时间</th>
            <th>审核人</th>
            <th>审核通过时间</th>
            <th>短信发送时间</th>
            <th>任务状态</th>
            <th>操作</th>
        </tr>
    <?php
        foreach($list as $item) {
    ?>
        <tr class="row" align="center">
            <!--
            <td><input type="checkbox" name="taskIds[<?php echo $item['id'];?>]" class="key" value="<?php echo $item['id'];?>"></td>
            -->
            <td><?php echo $item['id']?></td>
            <td><?php echo mb_strlen($item['content']) > 50 ? (mb_substr($item['content'], 0, 50) . '...') : $item['content'];?></td>
            <td><?php echo $adminInfo[$item['creator']] ? : '-';?></td>
            <td><?php echo date('Y-m-d H:i:s', $item['create_time']);?></td>
            <td><?php echo empty($item['extinfo']['create_user']) ? "-" : $item['extinfo']['create_user'];?></td>
            <td><?php echo empty($item['extinfo']['spark_count']) ? "-" : $item['extinfo']['spark_count'];?></td>
            <td><?php echo $item['work_num'];?></td>
            <td><?php echo empty($item['expect_send_time']) ? "及时发送" : "定时发送"; ?></td>
            <td><?php echo empty($item['expect_send_time']) ? "通过后及时发送" : date("Y-m-d H:i", $item['expect_send_time']); ?></td>
            <td><?php echo $adminInfo[$item['checker']] ? : '-';?></td>
            <td><?php echo ($item['check_pass_time'] == 0) ? '-' : date('Y-m-d H:i:s', $item['check_pass_time']);?></td>
            <td><?php echo ($item['send_time'] == 0) ? '-' : date('Y-m-d H:i:s', $item['send_time']);?></td>
            <td>
          <?php
          if(!empty($item['task_status']))
          {
            switch($item['task_status']) 
              {
                case 1:
                    echo '待提交';
                    break;
                case 2:
                    echo '审核中';
                    break;
                case 3:
                    echo '审核通过';
                    break;
                case 4:
                    echo '审核未通过';
                    break;
                case 5:
                    echo '短信发送中';
                    break;
                case 6:
                    echo '发送成功';
                    break;
                case 7:
                    echo '已删除';
                    break;
                case 8:
                    echo '等待发送';
                    break;
              }
          }
          ?>
         </td>

         <td>
          <?php
          if(!$_isChecker) {
              if(in_array($item['task_status'], [1, 4])) {
          ?>
                <a href="/m.php?m=SmsTask&a=edit&id=<?php echo $item['id'];?>">编辑</a>
                <a href="/m.php?m=SmsTask&a=del&id=<?php echo $item['id'];?>">删除</a>
              <?php } elseif(in_array($item['task_status'], [2, 3, 5, 8])) { ?>
                <a href="/m.php?m=SmsTask&a=show&id=<?php echo $item['id'];?>">查看</a>
              <?php } else { ?>
                <a href="/m.php?m=SmsTask&a=show&id=<?php echo $item['id'];?>">查看</a>
              <?php }
          } else {
             if(in_array($item['task_status'], [3, 4, 5, 6, 8])) {
          ?>
                <a href="/m.php?m=SmsTask&a=show&id=<?php echo $item['id'];?>">查看</a>
          <?php } elseif($item['task_status'] == 2) { ?>
                <a href="/m.php?m=SmsTask&a=check&id=<?php echo $item['id'];?>">审核</a>
          <?php
              }
          }
          ?>
          </td>
        </tr>
        <?php
            }
        ?>
        <tr>
         <td colspan="15" class="bottomTd">
           <!--
           <input name="delSelect" type="button" value="删除所选" onclick="delSel();">
           -->
         </td>
        </tr>
    </table>
    <!-- Think 系统列表组件结束 -->
    <div class="page"><?php echo ($page); ?></div>
</form>

<script type="text/javascript">
function show_cal(obj) {
    obj.blur();
    return showCalendar(obj.id, '%Y-%m-%d %H:%M:%S', true, false, obj.id);
}
function delSel()
{
    $('#form1').attr('action', ROOT+'?m=SmsTask&a=batchDelete').submit();
}
</script>
<!--logId:<?php echo \libs\utils\Logger::getLogId(); ?>-->

<script>
jQuery.browser={};
(function(){
    jQuery.browser.msie=false;
    jQuery.browser.version=0;
    if(navigator.userAgent.match(/MSIE ([0-9]+)./)){
        jQuery.browser.msie=true;
        jQuery.browser.version=RegExp.$1;}
})();
</script>

</body>
</html>