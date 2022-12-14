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
                return '??????????????????';
            case 'OFFLINE':
                return '??????????????????';
            default:
                return '??????';
        }
    }

    function getAccType($type)
    {
        switch($type)
        {
            case 'NUCC':
                return '??????';
            case 'UPOPJS':
                return '??????';
            case 'HKBC':
                return '????????????';
            default:
                return '??????';
        }
    }

    function getStatus($status)
    {
        switch ($status)
        {
            case 'I':
                return '?????????';
            case 'S':
                return '??????';
            case 'F':
                return '??????';
            default:
                return '??????';
        }
    } ?>
<div class="main">
<div class="main_title">????????????????????????????????????????????????????????????</div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get" onsubmit="return checkForm()">
        ??????ID???<input type="text" class="textbox require1" placeholder="??????id" name="userId" id="searchUserId" value="<?php echo trim($_REQUEST['userId']);?>" style="width:100px;" />
        ???????????????<input type="text" class="textbox require1" name="startDate" id="startDate" value="<?php echo ($startDate); ?>" style="width:100px;" onfocus="return showCalendar('startDate', '%Y-%m-%d %H:%M:%S', false, false, 'startDate');" style="width:150px;" onclick="return showCalendar('startDate', '%Y-%m-%d %H:%M:%S', false, false, 'startDate');"/>
        ???????????????<input type="text" class="textbox require1" name="endDate" id="endDate" value="<?php echo ($endDate); ?>" style="width:100px;"  onfocus="return showCalendar('endDate', '%Y-%m-%d %H:%M:%S', false, false, 'endDate');" style="width:150px;" onclick="return showCalendar('endDate', '%Y-%m-%d %H:%M:%S', false, false, 'endDate');"/>
        ???????????????
        <select name="busType" class="require1" width="100px;" id="searchBizType" >
            <option value="">??????</option>
            <option value="COMMON_LARGE" <?php if ($_REQUEST['busType'] == 'COMMON_LARGE') echo "selected"; ?>>????????????</option>
            <option value="OFFLINE" <?php if ($_REQUEST['busType'] == 'OFFLINE') echo "selected"; ?>>??????????????????</option>
        </select>
        ???????????????
        <select name="orderStatus" class="require1" width="100px;" id="searchOrderStatus">
            <option value="all" >??????</option>
            <option value="I" <?php if ($_REQUEST['orderStatus'] == 'I') echo "selected"; ?>>?????????</option>
            <option value="S" <?php if ($_REQUEST['orderStatus'] == 'S') echo "selected"; ?>>??????</option>
            <option value="F" <?php if ($_REQUEST['orderStatus'] == 'F') echo "selected"; ?>>??????</option>
        </select>
        ???????????????<input type="text" name="bankCardNo" value="<?php echo trim($_REQUEST['bankCardNo']);?>" style="width:200px;" />

        <input type="hidden" value="TransferOrderQuery" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="SUBMIT" id="subBtn" class="button searchBtn" value="<?php echo L("SEARCH");?>" />
    </form>
</div>
<div class="blank5"></div>
    <table class="dataTable">
    <tr>
    <th>??????id</th>
    <th>?????????</th>
    <th>????????????</th>
    <th>???????????????</th>
    <th>??????</th>
    <th>????????????</th>
    <th>????????????</th>
    <th>??????????????????</th>
    <th>????????????</th>
    <th>??????????????????</th>
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
<tr> <td colspan="10" textalign="center">????????????</td></tr><?php endif; ?>

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
    <td colspan="10" textalign="right"> <a href="/m.php?m=TransferOrderQuery&a=index&<?php echo $urlPrev;?>">?????????</a>&nbsp;<a  href="/m.php?m=TransferOrderQuery&a=index&<?php echo $urlNext;?>">?????????</a>
    </tr>


</table>
<script>
function checkForm()
{
    if (document.getElementById("searchUserId").value == '')
    {
        alert("???????????????Id");
        return false;
    }
    if (document.getElementById("startDate").value == '')
    {
        alert("?????????????????????YYYY-mm-dd??????");
        return false;
    }
    if (document.getElementById("endDate").value == '')
    {
        alert("?????????????????????YYYY-mm-dd??????");
        return false;
    }
    if (document.getElementById("searchBizType").value == '0')
    {
        alert("???????????????????????????");
        return false;
    }
    if (document.getElementById("searchOrderStatus").value == '0')
    {
        alert("?????????????????????");
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