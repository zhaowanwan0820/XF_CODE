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
<script type="text/javascript" src="__TMPL__Common/js/user.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<script type="text/javascript" src="__TMPL__searchselect/jquery.searchableselect.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__searchselect/searchableselect.css" />
<style>
     .alarm { padding-top:100px; text-align:center; color:#F00; font-size:14px;}
     .right{text-align:right;}
     .left {text-align:left;}
     .center{text-align:center;}
</style>

<?php  ?>
<div class="main">
    <div class="main_title">添加限制提现规则</div>
    <div class="blank5"></div>
    <?php if($errorMsg != ''): ?><p class="alarm center"><?php echo ($errorMsg); ?></p>
        <p class="center"><a href="javascript:window.history.go(-1);">返回上一页</a></p>
    <?php else: ?>
    <form name="userForm" action="/m.php" method="POST">
        <input type="hidden" id="withdraw_limit_user_id" name="userId" value ="<?php echo ($userId); ?>"/>
        <input type="hidden" id="isAllowAmount" name="isWhiteList" value ="0"/>
        <input type="hidden" name="a" value ="doWithdrawLimitApply"/>
        <input type="hidden" name="m" value ="User"/>
    <table class="dataTable">
    <tr>
        <th width="200">限制提现用户类型</th>
        <td>
            <select name="platform_account_type" id="platform_account_type">
                <option value="-1">请选择</option>
                <?php echo ($optionHtml); ?>
            </select>
        </td>
    </tr>
    <tr>
        <th id="limitAmount">限制提现金额</th>
        <td><input type="TEXT" size="30" name="limit_amount" id="withdraw_limit_amount" /><span style="color:#F00;padding-left:10px;" id="error_tips"></span></td>
    </tr>
    <tr>
        <th>限制提现类型</th>
        <td>
            <select name="withdraw_limit_type" id="withdraw_limit_type">
            <option value="-1">请选择</option>
            <?php if(is_array($limit_types)): foreach($limit_types as $value=>$item): ?><option value="<?php echo ($value); ?>" <?php if(intval($_REQUEST['withdraw_limit_type']) == $value): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; ?>
        </select>
        </td>
    </tr>
    <tr>
        <th>限制提现备注</th>
        <td><textarea cols="100" rows="10" name="memo" id="memo"></textarea> </td>
    </tr>
    <tr>
        <td colspan="2"> <input type="SUBMIT" value="提交申请"/>
    </table>
    </form><?php endif; ?>
<div class="blank5"></div>
<script>
    $('#platform_account_type').change(function(){
        var selectVal = $(this).val();
        var opt = selectVal.split('_');
        console.log(opt);
        if (opt.length == 1) {
            return ;
        }
        if (opt[0] == '1' && opt[1] == '2') {
            $('#limitAmount').html('允许提现金额');
            $('#isAllowAmount').val(1);
            return;
        }
        $('#limitAmount').html('限制提现金额');
    });
    //监听键盘，只允许输入数字和小数点
    $("#withdraw_limit_amount").keypress(function(event) {
        var keyCode = event.which;
        if (keyCode == 46 || (keyCode >= 48 && keyCode <=57))
        {
            return true;
        } else {
            return false;
        }
        }).focus(function() {
                this.style.imeMode='disabled';
        });
    $(function(){
        $("#withdraw_limit_amount").bind("input propertychange",function(){
            var regx = /^[0-9]+([.]{1}[0-9]{1,2})?$/
            var value = $(this).val()
            if(!regx.test(value) && value.split(".")[1]){
            value = value.split(".")[0] + "." + value.split(".")[1].slice(0,2);
            console.log(value);
            $(this).val(value);
            }
        })
    })
</script>