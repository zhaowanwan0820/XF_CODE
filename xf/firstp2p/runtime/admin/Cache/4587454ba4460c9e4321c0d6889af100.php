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


<link rel="stylesheet" type="text/css" href="/static/admin/Common/js/calendar/calendar.css" />
<script type="text/javascript" src="/static/admin/Common/js/calendar/calendar_lang.js" ></script>
<script type="text/javascript" src="/static/admin/Common/js/calendar/calendar.js"></script>

<style>
#dataTable th { text-align: left; }
</style>

<div class="main">
    <div class="main_title">
        <label>待还款列表</label>
    </div>
    <div class="blank5"></div>

    <div class="search_row">
        <form name="search" action="/m.php" method="get">
            批次编号：<input type="text" class="textbox" name="batch_id" value="<?php echo trim($_REQUEST['batch_id']);?>" style="width:100px;"/>
            项目名称：<input type="text" class="textbox" name="pro_name" value="<?php echo trim($_REQUEST['pro_name']);?>" style="width:150px;"/>
            交易所备案产品编号：<input type="text" class="textbox" name="jys_num" value="<?php echo trim($_REQUEST['jys_num']);?>" style="width:150px;"/>
            最近一期还款日开始：<input type="text" class="textbox" name="repay_time_start" id="repay_time_start" value="<?php echo trim($_REQUEST['repay_time_start']);?>"
                                       onclick="return showCalendar('repay_time_start', '%Y-%m-%d', false, false, 'repay_time_start');" style="width:80px;"/>
            最近一期还款日结束：<input type="text" class="textbox" name="repay_time_end" id="repay_time_end" value="<?php echo trim($_REQUEST['repay_time_end']);?>"
                                       onclick="return showCalendar('repay_time_end', '%Y-%m-%d', false, false, 'repay_time_end');" style="width:80px;"/>
            发行人名称：<input type="text" class="textbox" name="fx_name" value="<?php echo trim($_REQUEST['fx_name']);?>" style="width:150px;"/>
            发行人id：<input type="text" class="textbox" name="fx_uid" value="<?php echo trim($_REQUEST['fx_uid']);?>" style="width:100px;"/>
            还款方式：<select name="repay_type">
                        <option value="">全部</option>
                        <option value="1" <?php if($_REQUEST['repay_type'] == 1): ?>selected="selected"<?php endif; ?>>到期支付本金收益(天)</option>
                        <option value="2" <?php if($_REQUEST['repay_type'] == 2): ?>selected="selected"<?php endif; ?>>到期支付本金收益(月)</option>
                        <option value="3" <?php if($_REQUEST['repay_type'] == 3): ?>selected="selected"<?php endif; ?>>按月支付收益到期还本</option>
                        <option value="4" <?php if($_REQUEST['repay_type'] == 4): ?>selected="selected"<?php endif; ?>>按季支付收益到期还本</option>
                     </select>
            咨询机构：<input type="text" class="textbox" name="consult_name" value="<?php echo trim($_REQUEST['consult_name']);?>" /> 
            交易所: <select name="jys_id">
                    <option value=""></option>
                    <?php if(is_array($jysList)): foreach($jysList as $id_value=>$item_value): ?><option value="<?php echo ($item_value['id']); ?>" <?php if($item_value['id'] == $_REQUEST['jys_id']): ?>selected="selected"<?php endif; ?>><?php echo ($item_value['name']); ?></option><?php endforeach; endif; ?>
                    </select> &nbsp;
            <input type="hidden" value="index" name="a" />
            <input type="hidden" value="ExchangeRepayList" name="m" />
            <input type="submit" id="submit_btn" value="搜索" class="button"/>
        </form>
    </div>
    <div class="blank5"></div>
    <div class="blank5"></div>

    <table id="dataTable" class="dataTable">
        <tr class="row">
            <th>序号</th>
            <th>项目名称</th>
            <th>交易所备案产品编号</th>
            <th>期限</th>
            <th>咨询机构</th>
            <th>发行人/发行人id</th>
            <th>交易所</th>
            <th>期数</th>
            <th>还款方式</th>
            <th>批次金额（元）</th>
            <th>本期还款金额（元）</th>
            <th>最近一期还款日</th>
            <th>操作</th>
        </tr>
        <?php if(is_array($list)): $index = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$index;$mod = ($index % 2 )?><tr class="row">
            <td><?php echo ($pageSize * ($nowPage - 1) + $index); ?></td>
            <td><?php echo ($projectList[$item['pro_id']]['name']); ?></td>
            <td><?php echo ($projectList[$item['pro_id']]['jys_number']); ?></td>
            <td>
                <?php if($projectList[$item['pro_id']]['repay_type'] == 1): ?><?php echo ($projectList[$item['pro_id']]['repay_time']); ?>天
                <?php else: ?>
                    <?php echo ($projectList[$item['pro_id']]['repay_time']); ?>月<?php endif; ?>
            </td>
            <td><?php echo ($agencyList[$projectList[$item['pro_id']]['consult_id']]['name']); ?></td>
            <td><?php echo ($publishList[$projectList[$item['pro_id']]['fx_uid']]['real_name']); ?> / <?php echo ($projectList[$item['pro_id']]['fx_uid']); ?></td>
            <td><?php echo ($jysList[$projectList[$item['pro_id']]['jys_id']]['name']); ?></td>
            <td><?php echo ($item['batch_number']); ?>期</td>
            <td>
                <?php if($projectList[$item['pro_id']]['repay_type'] == 1): ?>到期支付本金收益(天)
                <?php elseif($projectList[$item['pro_id']]['repay_type'] == 2): ?>
                到期支付本金收益(月)
                <?php elseif($projectList[$item['pro_id']]['repay_type'] == 3): ?>
                按月支付收益到期还本
                <?php else: ?>
                按季支付收益到期还本<?php endif; ?>
            </td>
            <td><?php echo ($item['amount'] / 100); ?></td>
            <td><?php echo ($repayList[$item['id']]['repay_money'] / 100); ?></td>
            <td><?php echo date("Y-m-d", $repayList[$item['id']]['repay_time']);?></td>
            <td>
                <a href="<?php echo u('ExchangeRepayList/normalPay?batch_id=' . $item['id']);?>">强制还款</a>
                <a href="<?php echo u('ExchangeRepayList/prePay?batch_id=' . $item['id']);?>">提前还款</a>
            </td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>
    <div class="blank5"></div>

    <div class="page"><?php echo ($page); ?></div>
    <div class="blank5"></div>
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