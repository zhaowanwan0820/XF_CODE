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

<style type="text/css">
    input[disabled]{color:#000;opacity:1;border:none;background-color:transparent;}
</style>

<div class="main">
    <div class="main_title">投资客户明细</div>
    <div class="main_title">交易所备案产品编号：<?php echo ($jys_record_number); ?></div>
    <div class="blank5"></div>
    <div class="blank5"></div>
    <div class="search_row">
        <form method="post"  action="__APP__" enctype="multipart/form-data">
            投资客户明细：
            <?php if($is_show_upload == 1): ?><input type="file" name="batch_update_file" id="batch_update_file" accept=".csv"/>
                <input type="submit" class="button" value="导入" /> &nbsp;&nbsp;&nbsp;&nbsp;<a href="/m.php?m=DarkMoonDealLoad&a=get_upload_tpl">下载模板</a>
                <input type="hidden" value="<?php echo ($dealid); ?>" name="dealid" />
                <input type="hidden" value="DarkMoonDealLoad" name="m" />
                <input type="hidden" value="do_upload" name="a" /><?php endif; ?>
        </form>
    </div>
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
        <tr>
            <td colspan="9" class="topTd" >&nbsp; </td>
        </tr>
        <tr class="row">
            <th>
                客户ID
            </th>
            <th>
                客户姓名
            </th>
            <th>
                身份证号
            </th>
            <th>
                手机号
            </th>
            <th>
                银行卡号
            </th>
            <th>
                开户行
            </th>
            <th>
                邀请码上传
            </th>
            <th>
                邀请码
            </th>
            <th>
                认购金额
            </th>
            <th>
                状态
            </th>
            <th>
                操作
            </th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$data): ++$i;$mod = ($i % 2 )?><tr class="row">
                <td class="item_input" style="display:none"><input type="hidden" class="textbox" name="id" value="<?php echo ($data["id"]); ?>"></td>
                <td class="item_input"><?php echo ($data["user_id"]); ?></td>
                <td class="item_input"><input type="text" class="textbox" name="real_name" disabled="disabled"  value="<?php echo ($data["real_name"]); ?>"></td>
                <td class="item_input"><input type="text" class="textbox" name="idno" disabled="disabled"  value="<?php echo ($data["idno"]); ?>"></td>
                <td class="item_input"><input type="text" class="textbox" name="mobile" disabled="disabled"  value="<?php echo ($data["mobile"]); ?>"></td>
                <td class="item_input"><input type="text" class="textbox" name="bank_id" disabled="disabled"  value="<?php echo ($data["bank_id"]); ?>"></td>
                <td class="item_input"><input type="text" class="textbox" name="bank_name" disabled="disabled"  value="<?php echo ($data["bank_name"]); ?>"></td>
                <td class="item_input"><input type="text" class="textbox" name="short_alias_csv" disabled="disabled"  value="<?php echo ($data["short_alias_csv"]); ?>"></td>
                <td class="item_input"><?php echo ($data["short_alias"]); ?></td>
                <td class="item_input"><input type="text" class="textbox" name="money" disabled="disabled"  value="<?php echo ($data["money"]); ?>"></td>
                <td>
                    <?php if($data["status"] == 1): ?><br />未签署<?php endif; ?>
                    <?php if($data["status"] == 2): ?><br />已签署<?php endif; ?>
                    <?php if($data["status"] == 3): ?><br />置废<?php endif; ?>
                </td>
                <td>
                    <?php if($data["status"] == 1): ?><a href='javascript:void(0);' onclick="edit('<?php echo ($data["id"]); ?>',this);">编辑</a><?php endif; ?>
                    <?php if($data["status"] != 3): ?><a href='javascript:void(0);' onclick="invalid('<?php echo ($data["id"]); ?>',this);">置废</a><?php endif; ?>
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


<script type="text/javascript">

    $("input").blur(function(){
        var param= [];
        $(this).parents().filter("tr").children("td").each(function () {
            input = $(this).find("input");
            if(input.length > 0) {
                $(input).attr("disabled","disabled");
                param.push($(input).attr("name") +"=" +$(input).val());
            }
        });

        if(param.length >0) {
            var paramString = param.join("&");
            $.ajax({
                url: ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=edit&" + paramString,
                data: "ajax=1",
                dataType: "json",
                success: function (obj) {
                    alert(obj.info);
                    location=location;
                }
            });
        }
    });
    function edit(loadId,element) {
        $(element).parents().filter("tr").children("td").each(function () {
            $(this).find("input").removeAttr('disabled');
        });
    }

    function invalid(loadId,element) {
        $.ajax({
            url: ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=invalid&loadId=" + loadId,
            data: "ajax=1",
            dataType: "json",
            success: function (obj) {
                alert(obj.info);
                location.reload();
            }
        });
    }
</script>