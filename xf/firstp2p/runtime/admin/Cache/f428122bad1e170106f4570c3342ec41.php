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
    function create(id)
    {
        location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=create&id="+id;
    }

    var createlock = false;

    function create_batch(btn)
    {
        $(btn).attr("disabled", "disabled");
        if (!createlock) {
            createlock = true;
            if (window.confirm('???????????????\n?????????????????????????????????????????????????????????????????????????????????????????????????????????????????????')) {
                idBox = $(".key:checked");
                idArray = new Array();
                $.each( idBox, function(i, n){
                    idArray.push($(n).val());
                });
                id = idArray.join(",");

                var inputs = $(".search_row").find("input");
                var selects = $(".search_row").find("select");
                var param = '';
                for(i=0;i<inputs.length;i++)
                {
                    if(inputs[i].name!='m'&&inputs[i].name!='a')
                        param += "&"+inputs[i].name+"="+$(inputs[i]).val();
                }
                for(i=0;i<selects.length;i++)
                {
                    param += "&"+selects[i].name+"="+$(selects[i]).val();
                }
                var url= ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=index&ids="+id;
                location.href = url+param;
            }
            createlock = false;
        } else {
            alert("?????????????????????");
        }
        $(btn).removeAttr("disabled");
    }
</script>
<div class="main">
    <div class="main_title">??????????????????</div>
    <div class="blank5"></div>
    <div class="search_row">
        <form name="search" action="__APP__" method="get">
            ??????????????????<input type="text" class="textbox" name="loan_batch_no" value="<?php echo trim($_REQUEST['loan_batch_no']);?>" style="width:100px;" />

            ?????????<input type="text" class="textbox" name="id" value="<?php echo trim($_REQUEST['id']);?>" style="width:100px;" />

            ???????????????<input type="text" class="textbox" name="deal_name" value="<?php echo trim($_REQUEST['deal_name']);?>" />
            ???????????????<input type="text" class="textbox" name="project_name" value="<?php echo trim($_REQUEST['project_name']);?>" />
            ?????????????????????
            <input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" size="10" />
            <br />
            ???????????????
            <input type="text" class="textbox" style="width:140px;" name="op_time_start" id="op_time_start" value="<?php echo ($_REQUEST['op_time_start']); ?>" onfocus="this.blur(); return showCalendar('op_time_start', '%Y-%m-%d 00:00:00', false, false, 'btn_op_time_start');" title="??????????????????" />
            <input type="button" class="button" id="btn_op_time_start" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('op_time_start', '%Y-%m-%d %H:%M:00', false, false, 'btn_op_time_start');" />
            ???
            <input type="text" class="textbox" style="width:140px;" name="op_time_end" id="op_time_end" value="<?php echo ($_REQUEST['op_time_end']); ?>" onfocus="this.blur(); return showCalendar('op_time_end', '%Y-%m-%d 23:59:59', false, false, 'btn_op_time_end');" title="??????????????????" />
            <input type="button" class="button" id="btn_op_time_end" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('op_time_end', '%Y-%m-%d %H:%M:59', false, false, 'btn_op_time_end');" />

            ????????????
            <select name="loan_money_type">
                <option value="">??????</option>
                <option value="1" <?php if ($_REQUEST['loan_money_type'] == 1) { ?>selected<?php } ?>>????????????</option>
                <option value="2" <?php if ($_REQUEST['loan_money_type'] == 2) { ?>selected<?php } ?>>???????????????</option>
                <option value="3" <?php if ($_REQUEST['loan_money_type'] == 3) { ?>selected<?php } ?>>????????????</option>
            </select>

            ???????????????
            <input type="text" class="textbox" name="admin_name" value="<?php echo trim($_REQUEST['admin_name']);?>" size="10" />
            ???????????????
            <select name="op_type">
                <option value="9999" <?php if($_REQUEST['op_type'] == 9999): ?>selected<?php endif; ?>>?????????</option>
                <?php if(is_array($op_type_list)): foreach($op_type_list as $key=>$item): ?><option value="<?php echo ($key); ?>" <?php if($_REQUEST['op_type'] == $key): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; ?>
            </select>
            ???????????????
            <select name="return_type">
                <option value="0" <?php if($_REQUEST['return_type'] == 0): ?>selected<?php endif; ?>>?????????</option>
                <?php if(is_array($return_type_list)): foreach($return_type_list as $key=>$item): ?><option value="<?php echo ($key); ?>" <?php if($_REQUEST['return_type'] == $key): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; ?>
            </select>


            <input type="hidden" id="page_now" value="<?php echo ($_GET["p"]); ?>" name="p" />
            <input type="hidden" value="LoanOplog" name="m" />
            <input type="hidden" value="index" name="a" />
            <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
            <input type="button" class="button" value="??????" onclick="export_csv();" />
            <input type="button" class="button" value="???????????????????????????" onclick="create_batch(this);" />
        </form>
    </div>
    <div class="blank5"></div>
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
        <tr>
            <td colspan="19" class="topTd" >&nbsp; </td>
        </tr>
        <tr class="row">
            <th width="8">
                <input type="checkbox" id="check" onclick="CheckAll('dataTable')">
            </th>
            <th>??????</th>
            <th>???????????????</th>
            <th>???ID</th>
            <th>????????????</th>
            <th>??????????????????</th>
            <th width="100">????????????</th>
            <th width="100">????????????</th>
            <th width="100">????????????</th>
            <th width="100">????????????</th>
            <th>????????????</th>
            <th>??????????????????</th>
            <th>????????????</th>
            <th width="120">??????????????????</th>
            <th width="100">???????????????</th>
            <th>????????????</th>
            <th>????????????</th>
            <th>????????????</th>
            <th>????????????</th>
            <th>?????????</th>
            <th>????????????</th>
            <th>????????????</th>

            <th style="width:150px">
                ??????
            </th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$log): ++$i;$mod = ($i % 2 )?><tr class="row">
                <td>
                    <input type="checkbox" name="key" class="key" value="<?php echo ($log["id"]); ?>">
                </td>
                <td>
                    &nbsp;<?php echo ($log["id"]); ?>
                </td>

                <td>
                    &nbsp;<?php echo ($log["loan_batch_no"]); ?>
                </td>
                <td width="60">
                    &nbsp;<?php echo ($log["deal_id"]); ?>
                </td>
                <td>
                    &nbsp;<a href="?m=Deal&id=<?php echo ($log["deal_id"]); ?>"><?php echo ($log["deal_name"]); ?></a>
                </td>
                <td>
                    &nbsp;<?php echo (getOldDealNameWithPrefix($log["deal_id"])); ?>
                </td>
                <td>
                    &nbsp;<?php echo number_format($log['borrow_amount'], 2); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["repay_time"]); ?><?php if($log["loan_type"] == 5): ?>???<?php else: ?>??????<?php endif; ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["loan_money_type_name"]); ?>
                </td>
                <td>
                    <?php echo ($loan_types[$log['ext_loan_type']]); ?>
                </td>
                <td>
                    &nbsp;<?php echo (get_loantype($log["loan_type"])); ?>
                </td>
                <td>&nbsp;<?php echo (getDealReportStatus($log["deal_id"])); ?></td>
                <td>
                    &nbsp;<?php echo (getUserTypeName($log["borrow_user_id"])); ?>
                </td>
                <td>
                    &nbsp;<a href="?m=User&a=index&user_id=<?php echo ($log["borrow_user_id"]); ?>"><?php echo ($log["user_name"]); ?></a>
                </td>
                <td>
                    &nbsp;<a href="?m=User&a=index&user_id=<?php echo ($log["borrow_user_id"]); ?>"><?php echo ($log["real_name"]); ?></a>
                </td>
                <td>
                    &nbsp;<?php echo number_format($log['loan_money'], 2); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["op_type"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["return_type"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["return_reason"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($log["submit_user_name"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo (get_admin_name($log["op_user_id"])); ?>
                </td>
                <td>
                    &nbsp;<?php echo (to_date($log["op_time"])); ?>
                </td>
                <!-- td>
                    &nbsp;{log.project_id|get_project_name}
                </td -->
                <td>
                    <?php if($log["loan_batch_no"] == ''): ?><?php else: ?><a href="?m=LoanOplog&a=print_batch&batch_no=<?php echo ($log["loan_batch_no"]); ?>"  target='_blank'>???????????????</a><?php endif; ?>
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