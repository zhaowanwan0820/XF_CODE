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

<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<div class="main">
    <div class="main_title">
        <p>交易列表查询条件</p>
    </div>
    <div class="blank5"></div>
    <div>
        <form name="search" action="__APP__" method="get">
            &nbsp;预约ID：<input type="text" class="textbox" name="reserve_id" value="<?php echo trim($_REQUEST['reserve_id']);?>" />
            &nbsp;投资交易ID：<input type="text" class="textbox" name="id" value="<?php echo trim($_REQUEST['id']);?>" />
            &nbsp;投资期限：
            <select name="invest_deadline_opt" class="textbox selectW">
                <option value="">全部</option>
                <?php if(is_array($data["deadlineConf"])): foreach($data["deadlineConf"] as $key=>$invest_conf): ?><option value="<?php echo ($invest_conf['deadline']); ?>|<?php echo ($invest_conf['deadline_unit']); ?>" <?php if($_REQUEST['invest_deadline_opt'] == $invest_conf['deadline'] . '|' . $invest_conf['deadline_unit']): ?>selected="selected"<?php endif; ?>><?php echo ($invest_conf['deadline_format']); ?></option><?php endforeach; endif; ?>
            </select>
            &nbsp;投资标的:
            <input type="text" class="textbox" name="deal_name" value="<?php echo trim($_REQUEST['deal_name']);?>" />
            <br />
            &nbsp;投资交易时间:
            <input type="text" class="textbox" style="width:120px;" name="invest_date_from" id="invest_date_from" value="<?php echo ($_REQUEST['invest_date_from']); ?>" onfocus="this.blur(); return showCalendar('invest_date_from', '%Y-%m-%d 00:00:00', false, false, 'btn_invest_date_from');" title="<?php echo L("COUPON_TIPS_LEVEL_REBATE_VALID_BEGIN");?>"/>
            <input type="button" class="button" id="btn_invest_date_from" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('invest_date_from', '%Y-%m-%d %H:%M:%S', false, false, 'btn_invest_date_from');" />
            － <input type="text" class="textbox" style="width:120px;" name="invest_date_to" id="invest_date_to" value="<?php echo ($_REQUEST['invest_date_to']); ?>" onfocus="this.blur(); return showCalendar('invest_date_to', '%Y-%m-%d 23:59:59', false, false, 'btn_invest_date_to');" title="<?php echo L("COUPON_TIPS_LEVEL_REBATE_VALID_END");?>" />
            <input type="button" class="button" id="btn_invest_date_to" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('invest_date_to', '%Y-%m-%d %H:%M:%S', false, false, 'btn_invest_date_to');" />

            <input type="hidden" value="UserReservation" name="m" />
            <input type="hidden" value="trans" name="a" />
            <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
            <input type="button" class="button" value="导出" onclick="export_csv();" />
        </form>
    </div>
    <div class="blank5"></div>
    <div class="main_title">
        <p>交易列表</p>
    </div>
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
        <tr>
            <td colspan="15" class="topTd" >&nbsp; </td>
        </tr>
        <tr class="row textDecNone">
            <th width="100px">预约ID</th>
            <th>投资交易ID</th>
            <th>投资交易时间</th>
            <th>用户ID</th>
            <th>用户名</th>
            <th>手机号</th>
            <th>交易状态</th>
            <th>投资标的</th>
            <th>投资金额</th>
            <th>预约投资期限</th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$tran): ++$i;$mod = ($i % 2 )?><tr class="row">
            <td>
                &nbsp;<?php echo ($tran["reserve_id"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($tran["id"]); ?>
            </td>
            <td>
                &nbsp;<?php echo (format_date($tran["create_time"])); ?>
            </td>
            <td>
                &nbsp;<?php echo ($tran["user_id"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($tran["real_name"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($tran["mobile"]); ?>
            </td>
            <td>
                &nbsp;交易成功
            </td>
            <td>
                &nbsp;<?php echo ($tran["deal_name"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($tran["money_format"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($tran["invest_deadline_format"]); ?>
            </td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>
</div>
<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
<script charset="utf-8">
function get_query_string(){
    querystring = '';
    querystring += "&reserve_id="+$("input[name='reserve_id']").val();
    querystring += "&id="+$("input[name='id']").val();
    querystring += "&invest_deadline_opt="+$("select[name='invest_deadline_opt']").val();
    querystring += "&deal_name="+$("input[name='deal_name']").val();
    querystring += "&invest_date_from="+$("input[name='invest_date_from']").val();
    querystring += "&invest_date_to="+$("input[name='invest_date_to']").val();
    return querystring;
}
function export_csv() {
    window.location.href = ROOT+'?m=UserReservation&a=export_trans'+get_query_string();
}
</script>