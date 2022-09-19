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
<script type="text/javascript" src="__TMPL__Common/js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.weebox.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/user.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<style type="text/css">
.require1 { border-left:4px solid red;}
</style>

<?php function getBusType($type)
    {
        switch($type)
        {
            case 'COMMON_LARGE':
                return '大额转账充值';
            case 'OFFLINE':
                return '后台转账充值';
            default:
                return '未知';
        }
    }

    function getAccType($type)
    {
        switch($type)
        {
            case 'NUCC':
                return '网联';
            case 'UPOPJS':
                return '银联';
            case 'HKBC':
                return '海口专户';
            default:
                return '未知';
        }
    }

    function getStatus($status)
    {
        switch ($status)
        {
            case 'I':
                return '处理中';
            case 'S':
                return '成功';
            case 'F':
                return '失败';
            default:
                return '未知';
        }
    } ?>
<div class="main">
<div class="main_title">订单信息查询（只能查询近期一个月的数据）</div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get" onsubmit="return checkForm()">
        用户ID：<input type="text" class="textbox require1" placeholder="用户id" name="userId" id="searchUserId" value="<?php echo trim($_REQUEST['userId']);?>" style="width:100px;" />
        起始日期：<input type="text" class="textbox require1" name="startDate" id="startDate" value="<?php echo ($startDate); ?>" style="width:100px;" onfocus="return showCalendar('startDate', '%Y-%m-%d %H:%M:%S', false, false, 'startDate');" style="width:150px;" onclick="return showCalendar('startDate', '%Y-%m-%d %H:%M:%S', false, false, 'startDate');"/>
        终止日期：<input type="text" class="textbox require1" name="endDate" id="endDate" value="<?php echo ($endDate); ?>" style="width:100px;"  onfocus="return showCalendar('endDate', '%Y-%m-%d %H:%M:%S', false, false, 'endDate');" style="width:150px;" onclick="return showCalendar('endDate', '%Y-%m-%d %H:%M:%S', false, false, 'endDate');"/>
        业务类型：
        <select name="busType" class="require1" width="100px;" id="searchBizType" >
            <option value="">全部</option>
            <option value="COMMON_LARGE" <?php if ($_REQUEST['busType'] == 'COMMON_LARGE') echo "selected"; ?>>大额充值</option>
            <option value="OFFLINE" <?php if ($_REQUEST['busType'] == 'OFFLINE') echo "selected"; ?>>后台转账充值</option>
        </select>
        订单状态：
        <select name="orderStatus" class="require1" width="100px;" id="searchOrderStatus">
            <option value="all" >全部</option>
            <option value="I" <?php if ($_REQUEST['orderStatus'] == 'I') echo "selected"; ?>>处理中</option>
            <option value="S" <?php if ($_REQUEST['orderStatus'] == 'S') echo "selected"; ?>>成功</option>
            <option value="F" <?php if ($_REQUEST['orderStatus'] == 'F') echo "selected"; ?>>失败</option>
        </select>
        银行卡号：<input type="text" name="bankCardNo" value="<?php echo trim($_REQUEST['bankCardNo']);?>" style="width:200px;" />

        <input type="hidden" value="TransferOrderQuery" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="SUBMIT" id="subBtn" class="button searchBtn" value="<?php echo L("SEARCH");?>" />
    </form>
</div>
<div class="blank5"></div>
    <table class="dataTable">
    <tr>
    <th>用户id</th>
    <th>订单号</th>
    <th>银行卡号</th>
    <th>银行卡户名</th>
    <th>金额</th>
    <th>订单状态</th>
    <th>订单时间</th>
    <th>订单完成时间</th>
    <th>业务类型</th>
    <th>收款账户类型</th>
    </tr>
<?php if($list['pageCnt'] > 0): ?><?php if(is_array($list["pageList"])): foreach($list["pageList"] as $key=>$item): ?><tr>
        <td><?php echo $_REQUEST['userId']?></td>
        <td><?php echo ($item["outOrderId"]); ?></td>
        <td><?php echo ($item["bankCardNo"]); ?></td>
        <td><?php echo ($item["bankCardName"]); ?></td>
        <td><?php echo bcdiv($item['amount'], 100, 2)?></td>
        <td><?php echo getStatus($item['orderStatus']);?></td>
        <td><?php echo ($item["gmtCreate"]); ?></td>
        <td><?php echo ($item["gmtFinished"]); ?></td>
        <td><?php echo getBusType($item['busType']);?></td>
        <td><?php echo getAccType($item['channel']);?></td>
    </tr><?php endforeach; endif; ?>
<?php else: ?>
<tr> <td colspan="10" textalign="center">暂无数据</td></tr><?php endif; ?>

        <?php
            $request = $_REQUEST;
            $pageNext = isset($_REQUEST['pageNo']) ? intval($request['pageNo']) + 1 : 2;
            $request['pageNo'] = $pageNext;
            $urlNext = http_build_query($request);
            $pagePrev = isset($_REQUEST['pageNo']) ? (intval($request['pageNo']) - 2 > 0 ? intval($request['pageNo']) - 2 : 1) : 1;
            $request['pageNo'] = $pagePrev;
            $urlPrev = http_build_query($request);
        ?>
    <tr>
    <td colspan="10" textalign="right"> <a href="/m.php?m=TransferOrderQuery&a=index&<?php echo $urlPrev;?>">上一页</a>&nbsp;<a  href="/m.php?m=TransferOrderQuery&a=index&<?php echo $urlNext;?>">下一页</a>
    </tr>


</table>
<script>
function checkForm()
{
    if (document.getElementById("searchUserId").value == '')
    {
        alert("请输入用户Id");
        return false;
    }
    if (document.getElementById("startDate").value == '')
    {
        alert("请输入开始时间YYYY-mm-dd格式");
        return false;
    }
    if (document.getElementById("endDate").value == '')
    {
        alert("请输入结束时间YYYY-mm-dd格式");
        return false;
    }
    if (document.getElementById("searchBizType").value == '0')
    {
        alert("请选择查询业务类型");
        return false;
    }
    if (document.getElementById("searchOrderStatus").value == '0')
    {
        alert("请选择订单状态");
        return false;
    }

    return true;
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