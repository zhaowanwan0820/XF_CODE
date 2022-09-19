<?php if (!defined('THINK_PATH')) exit();?>
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
<script type="text/javascript" src="__TMPL__Common/js/input-click.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>

<script type="text/javascript">
</script>

<div class="main">
    <div class="main_title">还款操作记录</div>
    <div class="blank5"></div>
    <div class="search_row">
        <form name="search" action="__APP__" method="get">
            编号：<input type="text" class="textbox" name="deal_id" value="<?php echo trim($_REQUEST['deal_id']);?>" size="10" />

            借款标题：<input type="text" class="textbox" name="deal_name" value="<?php echo trim($_REQUEST['deal_name']);?>" size="10"/>
            项目名称：<input type="text" class="textbox" name="project_name" value="<?php echo trim($_REQUEST['project_name']);?>" size="10"/>
            借款人用户名：
            <input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" size="10" />

            借款人姓名：
            <input type="text" class="textbox" name="real_name" value="<?php echo trim($_REQUEST['real_name']);?>" size="10" />

            还款类型:
            <select name="operation_type">
                <option value="0">不限</option>
                <option value="1" <?php if ($_REQUEST['operation_type'] == 1) { ?>selected<?php } ?>>正常还款</option>
                <option value="2" <?php if ($_REQUEST['operation_type'] == 2) { ?>selected<?php } ?>>提前还款</option>
                <option value="3" <?php if ($_REQUEST['operation_type'] == 3) { ?>selected<?php } ?>>自助还款</option>
                <option value="4" <?php if ($_REQUEST['operation_type'] == 4) { ?>selected<?php } ?>>代发还款</option>
                <option value="5" <?php if ($_REQUEST['operation_type'] == 5) { ?>selected<?php } ?>>部分还款</option>
            </select>

            操作类型:
            <select name="audit_type">
                <option value="9999" <?php if ($_REQUEST['audit_type'] == 9999) { ?>selected<?php } ?>>全部</option>
                <option value="0" <?php if ($_REQUEST['audit_type'] == 0) { ?>selected<?php } ?>>还款</option>
                <option value="1" <?php if ($_REQUEST['audit_type'] == 1) { ?>selected<?php } ?>>提交</option>
                <option value="2" <?php if ($_REQUEST['audit_type'] == 2) { ?>selected<?php } ?>>退回</option>
                <option value="3" <?php if ($_REQUEST['audit_type'] == 3) { ?>selected<?php } ?>>自动还款</option>
            </select>
            退回类型:
            <select name="return_type">
                <option value="0" <?php if ($_REQUEST['return_type'] == 0) { ?>selected<?php } ?>>全部</option>
                <option value="1" <?php if ($_REQUEST['return_type'] == 1) { ?>selected<?php } ?>>差错</option>
                <option value="2" <?php if ($_REQUEST['return_type'] == 2) { ?>selected<?php } ?>>其他</option>
            </select>

            申请人：
            <input type="text" class="textbox" name="submit_user_name" value="<?php echo trim($_REQUEST['submit_user_name']);?>" size="10" />

           <!-- 报备状态:
            <select name="report_status" id="report_status">
                <option value="" <?php if($_REQUEST['report_status'] == ''): ?>selected<?php endif; ?>>请选择</option>
                <option value="1" <?php if($_REQUEST['report_status'] == '1'): ?>selected<?php endif; ?>>已报备</option>
                <option value="0" <?php if($_REQUEST['report_status'] == '0'): ?>selected<?php endif; ?>>未报备</option>
            </select>-->
            本期还款形式：
            <select name="repay_type" id="repay_type">
                <option value="" <?php if(!isset($_REQUEST['repay_type']) || strlen($_REQUEST['repay_type']) == 0): ?>selected="selected"<?php endif; ?>>全部</option>option>
                <?php if(is_array($deal_repay_type)): foreach($deal_repay_type as $key=>$item): ?><option value="<?php echo ($key); ?>"<?php if(strlen($_REQUEST['repay_type']) > 0 &&  $_REQUEST['repay_type'] == $key): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option>option><?php endforeach; endif; ?>
            </select>

            <br />
            实际还款日期：
            <input type="text" class="textbox" style="width:140px;" name="real_repay_time" id="real_repay_time" value="<?php echo ($_REQUEST['real_repay_time']); ?>" onfocus="this.blur(); return showCalendar('real_repay_time', '%Y-%m-%d', false, false, 'btn_real_repay_time');" title="实际还款日期" />
            <input type="button" class="button" id="btn_real_repay_time" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('real_repay_time', '%Y-%m-%d', false, false, 'btn_real_repay_time');" />

            操作人员：
            <input type="text" class="textbox" name="operator" value="<?php echo trim($_REQUEST['operator']);?>" size="10" />

            操作时间：
            <input type="text" class="textbox" style="width:140px;" name="operation_time" id="operation_time" value="<?php echo ($_REQUEST['operation_time']); ?>" onfocus="this.blur(); return showCalendar('operation_time', '%Y-%m-%d', false, false, 'btn_operation_time');" title="操作时间" />
            <input type="button" class="button" id="btn_operation_time" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('operation_time', '%Y-%m-%d', false, false, 'btn_operation_time');" />
            <input type="text" class="textbox" style="width:140px;" name="operation_time_end" id="operation_time_end" value="<?php echo ($_REQUEST['operation_time_end']); ?>" onfocus="this.blur(); return showCalendar('operation_time_end', '%Y-%m-%d', false, false, 'btn_operation_time');" title="操作时间" />
            <input type="button" class="button" id="btn_operation_time_end" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('operation_time_end', '%Y-%m-%d', false, false, 'btn_operation_time');" />

            <input type="hidden" id="page_now" value="<?php echo ($_GET["p"]); ?>" name="p" />
            <input type="hidden" value="DealRepayOplog" name="m" />
            <input type="hidden" value="index" name="a" />
            <input type="hidden" value="<?php echo intval($_REQUEST['project_id']);?>" name="project_id" />
            <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
            <input type="button" class="button" value="导出" onclick="export_csv();" />
        </form>
    </div>
    <div class="blank5"></div>
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
        <tr>
            <td colspan="22" class="topTd" >&nbsp; </td>
        </tr>
        <tr class="row">
            <th width="8">
                <input type="checkbox" id="check" onclick="CheckAll('dataTable')">
            </th>
            <th>
                <a href="javascript:sortBy('deal_id','1','Deal','index')" title="按照编号升序排列 ">
                编号
                <img src="/static/admin/Common/images/desc.gif" width="12" height="17"
                     border="0" align="absmiddle">
                </a>
            </th>
            <th>产品类别</th>
            <th width="150">借款标题</th>
            <th>旧版借款标题</th>
            <th width="100">借款金额</th>
            <th>年化借款利率</th>
            <th >借款期限</th>
            <th>还款方式</th>
            <th>还款模式</th>
            <th>用户类型</th>
            <th width="120">借款人用户名</th>
            <th>借款人姓名</th>
            <th>借款人ID</th>
            <th>实际还款日期</th>
            <th>本期已还款金额</th>
            <th>还款类型</th>
            <th>操作类型</th>
            <!--<th>是否报备</th>-->
            <th>本期还款形式</th>
            <th>退回类型</th>
            <th>退回原因</th>
            <th>申请人</th>
            <th>操作人员</th>
            <th>操作时间</th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$log): ++$i;$mod = ($i % 2 )?><tr class="row">
                <td>
                    <input type="checkbox" name="key" class="key" value="<?php echo ($log["id"]); ?>">
                </td>
                <td width="60">
                    &nbsp;<?php echo ($log["deal_id"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["loanTypeName"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["deal_name"]); ?>
                </td>
                <td>
                    <?php echo (getOldDealNameWithPrefix($log["deal_id"])); ?>
                </td>
                <td>
                    &nbsp;<?php echo number_format($log['borrow_amount'], 2); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["rate"]); ?>%
                </td>
                <td>
                    &nbsp;<?php echo ($log["repay_period"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["loantype"]); ?>
                </td>
                <td>
                    <?php echo ($log["repay_mode_name"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo (getUserTypeName($log["user_id"])); ?>
                </td>
                <td>
                    &nbsp;<a href="?m=User&a=index&user_id=<?php echo ($log["user_id"]); ?>" target="_blank"><?php echo ($log["user_name"]); ?></a>
                </td>
                <td>
                    &nbsp;<a href="?m=User&a=index&user_id=<?php echo ($log["user_id"]); ?>" target="_blank"><?php echo ($log["real_name"]); ?></a>
                </td>
                <td>
                    &nbsp;<?php echo ($log["user_id"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["real_repay_time"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["repay_money"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["operation_type"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["audit_type"]); ?>
                </td>
                <!--
                <td>
                    &nbsp;<?php echo (getDealReportStatus($log["deal_id"])); ?>
                </td>-->
                <td>
                    &nbsp;<?php echo ($deal_repay_type[$log['repay_type']]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["return_type"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["return_reason"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["submit_uid"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["operator"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["operation_time"]); ?>
                </td>
            </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>

    <div class="blank5"></div>
    <div class="page"><?php echo ($page); ?></div>
</div>
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