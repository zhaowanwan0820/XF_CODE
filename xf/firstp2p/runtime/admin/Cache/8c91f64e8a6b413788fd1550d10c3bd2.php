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
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<?php function formatDate($timestamp)
    {
        return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : ' - ';
    }

    function formatStatus($status)
    {
        return core\dao\LoanAccountAdjustMoneyModel::$loan_account_adjust_money_status[$status];
    }

    function formatType($type)
    {
        return core\dao\LoanAccountAdjustMoneyModel::$loan_account_adjust_money_type[$type];
    }

    function formatAccountType($accountType)
    {
        return NCFGroup\Protos\Ptp\Enum\UserAccountEnum::$accountDesc[1][$accountType];
    }

    function createOp($status,$row)
    {
        switch ($status)
        {
            case 1:
                return '<a href="javascript:;" onclick="apass('.$row['id'].')">A????????????</a>&nbsp;&nbsp;<a href="javascript:;" onclick="arefuse('.$row['id'].')">A????????????</a>';
            case 2:
                return '<a href="javascript:;" onclick="bpass('.$row['id'].')">B????????????</a>&nbsp;&nbsp;<a href="javascript:;" onclick="brefuse('.$row['id'].')">B????????????</a>';

        }
    } ?>
<div class="main">
<div class="main_title">??????????????????</div>
<div class="blank5"></div>
<div class="button_row">
    <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="addm();" />
    <input type="button" class="button" value="????????????" onclick="importCsv();" />
    <input type="button" class="button" value="A????????????" onclick="apass()" />
    <input type="button" class="button" value="A????????????" onclick="arefuse()" />
    <input type="button" class="button" value="B????????????" onclick="bpass()" />
    <input type="button" class="button" value="B????????????" onclick="brefuse()" />
</div>

<div class="search_row">
    <form name="search" action="__APP__" method="get">
        ??????ID???<input type="text" class="textbox" name="user_id" value="<?php echo trim($_REQUEST['user_id']);?>" style="width:100px;" />
        ???????????????<input type="text" class="textbox" name="vip_name" value="<?php echo trim($_REQUEST['vip_name']);?>" style="width:100px;" />
        ???????????????<input type="text" class="textbox" name="order_id" value="<?php echo trim($_REQUEST['order_id']);?>" style="width:100px;" />
        ????????????:
        <select name="status" id="js_type">
            <option value="0" <?php if(intval($_REQUEST['status']) == 0 ): ?>selected="selected"<?php endif; ?>>==?????????==</option> <?php if(is_array($loan_account_adjust_money_status)): foreach($loan_account_adjust_money_status as $key=>$status): ?><option value="<?php echo ($key); ?>" <?php if(intval($_REQUEST['status']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($status); ?></option><?php endforeach; endif; ?>
        </select>

        <input type="hidden" value="LoanAccountAdjustMoney" name="m" />
        <input type="hidden" value="index" name="a" />

        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />

    </form>
</div>

<div class="blank5"></div>
<!-- Think ???????????????????????? -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="13" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th><a href="javascript:sortBy('order_id','<?php echo ($sort); ?>','LoanAccountAdjustMoney','index')" title="??????????????????<?php echo ($sortType); ?> ">????????????<?php if(($order)  ==  "order_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="80px"><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','LoanAccountAdjustMoney','index')" title="????????????ID<?php echo ($sortType); ?> ">??????ID<?php if(($order)  ==  "user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="80px"><a href="javascript:sortBy('vip_name','<?php echo ($sort); ?>','LoanAccountAdjustMoney','index')" title="??????????????????<?php echo ($sortType); ?> ">????????????<?php if(($order)  ==  "vip_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="80px"><a href="javascript:sortBy('user_name','<?php echo ($sort); ?>','LoanAccountAdjustMoney','index')" title="??????????????????<?php echo ($sortType); ?> ">????????????<?php if(($order)  ==  "user_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="100px"><a href="javascript:sortBy('account_type','<?php echo ($sort); ?>','LoanAccountAdjustMoney','index')" title="??????????????????<?php echo ($sortType); ?> ">????????????<?php if(($order)  ==  "account_type"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('money','<?php echo ($sort); ?>','LoanAccountAdjustMoney','index')" title="??????????????????<?php echo ($sortType); ?> ">????????????<?php if(($order)  ==  "money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('type','<?php echo ($sort); ?>','LoanAccountAdjustMoney','index')" title="????????????<?php echo ($sortType); ?> ">??????<?php if(($order)  ==  "type"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','LoanAccountAdjustMoney','index')" title="??????????????????<?php echo ($sortType); ?> ">????????????<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('note','<?php echo ($sort); ?>','LoanAccountAdjustMoney','index')" title="????????????<?php echo ($sortType); ?> ">??????<?php if(($order)  ==  "note"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('status','<?php echo ($sort); ?>','LoanAccountAdjustMoney','index')" title="??????????????????<?php echo ($sortType); ?> ">????????????<?php if(($order)  ==  "status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('log','<?php echo ($sort); ?>','LoanAccountAdjustMoney','index')" title="??????????????????<?php echo ($sortType); ?> ">????????????<?php if(($order)  ==  "log"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="120px"><a href="javascript:sortBy('status','<?php echo ($sort); ?>','LoanAccountAdjustMoney','index')" title="????????????<?php echo ($sortType); ?> ">??????<?php if(($order)  ==  "status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$data): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($data["id"]); ?>"></td><td>&nbsp;<?php echo ($data["order_id"]); ?></td><td>&nbsp;<?php echo ($data["user_id"]); ?></td><td>&nbsp;<?php echo ($data["vip_name"]); ?></td><td>&nbsp;<?php echo ($data["user_name"]); ?></td><td>&nbsp;<?php echo (formatAccountType($data["account_type"])); ?></td><td>&nbsp;<?php echo ($data["money"]); ?></td><td>&nbsp;<?php echo (formatType($data["type"])); ?></td><td>&nbsp;<?php echo (formatDate($data["create_time"])); ?></td><td>&nbsp;<?php echo ($data["note"]); ?></td><td>&nbsp;<?php echo (formatStatus($data["status"])); ?></td><td>&nbsp;<?php echo (nl2br($data["log"])); ?></td><td>&nbsp;<?php echo (createOp($data["status"],$data)); ?></td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="13" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think ???????????????????????? -->

<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
</div>
<script>
        function apass(id)
        {
            // ????????????????????????
            var url = '/m.php?m=LoanAccountAdjustMoney&a=audit';
            if (typeof(id) == 'undefined')
            {
                getCheckedRecord();
                id = checkIds;
                if (id.length == 0)
                {
                    return false;
                }
                url += '&id='+id.join(',');
            } else {
                url += '&id='+id;
            }
            window.location.href= url;
        }
        // ?????????id???
        var checkIds = [];

        function getCheckedRecord()
        {
            checkIds = [];
            $('input.key:checked').each(function(){checkIds.push($(this).val());});
            if (checkIds.length == 0)
            {
                alert('???????????????????????????');
                return [];
            }
        }

        function addm()
        {
            location.href = "?" + VAR_MODULE + "=LoanAccountAdjustMoney&" + VAR_ACTION + "=add";
        }

        function importCsv(){
            location.href = "?" + VAR_MODULE + "=LoanAccountAdjustMoney&" + VAR_ACTION + "=import";
        }

        function arefuse(id)
        {
            // ??????????????????
            var url = '/m.php?m=LoanAccountAdjustMoney&a=disagree';
            if (typeof(id) == 'undefined')
            {
                getCheckedRecord();
                id = checkIds;
                if (id.length == 0)
                {
                    return false;
                }
                url += '&id='+id.join(',');
            } else {
                url += '&id='+id;
            }
            window.location.href= url;

        }

        function bpass(id)
        {
            // ??????B????????????
            var url = '/m.php?m=LoanAccountAdjustMoney&a=finalAudit';
            if (typeof(id) == 'undefined')
            {
                getCheckedRecord();
                id = checkIds;
                if (id.length == 0)
                {
                    return false;
                }
                url += '&id='+id.join(',');
            } else {
                url += '&id='+id;
            }
            window.location.href= url;
        }

        function brefuse(id)
        {
            // ??????????????????
            var url = '/m.php?m=LoanAccountAdjustMoney&a=refuse';
            if (typeof(id) == 'undefined')
            {
                getCheckedRecord();
                id = checkIds;
                if (id.length == 0)
                {
                    return false;
                }
                url += '&id='+id.join(',');
            } else {
                url += '&id='+id;
            }
            window.location.href = url;
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