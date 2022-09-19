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

<script type="text/javascript" src="__TMPL__Common/js/conf.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js"></script>

<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<div class="main">
    <div class="main_title"><?php echo L("EDIT");?> <a href="<?php echo u(" Dictionary/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
    <div class="blank5"></div>
    <form name="edit" action="__APP__" method="post" enctype="multipart/form-data">

            <tr>
                    <!--隐藏元素-->
                    <input type="hidden" name="publish_wait" value="1"/>
                    <input type="hidden" name="m" value="Dictionary" />
                    <input type="hidden" name="a" value="<?php echo ($act); ?>" />
                    <input type="hidden" name="id" value="<?php echo ($dict["id"]); ?>"/>
                    <!--隐藏元素-->
                    <input type="submit" class="button" value="<?php echo L("SAVE");?>"/>
                    <input type="reset" class="button" value="<?php echo L("RESET");?>"/>
            </tr>
        
        <div class="blank5"></div>
        <table class="form conf_tab" cellpadding=0 cellspacing=0 rel="1">
            <tr>
                <td colspan=2 class="topTd"></td>
            </tr>
            <tr>
                <td class="item_title">字典键:</td>
                <td class="item_input"><input type="text" class="textbox require" name="key"
                    <?php if($act=='update'): ?>readonly="readonly"<?php endif; ?>
                    style="width:500px;" value="<?php echo ($dict["key"]); ?>" />
                </td>
            </tr>
            <tr>
                <td class="item_title">描述:</td>
                <td class="item_input"><input type="text" class="textbox" name="note" style="width:500px;"
                                              value="<?php echo ($dict["note"]); ?>"/></td>
            </tr>

            <tr>
                <td class="item_title">字典值:</td>
                <td class="item_input"><input type="button" class="button" value="增加字典值" id="addvalue"/></td>
            </tr>

            <tr>
                <td class=""></td>
                <td>
                    <div id="valuetable">
                        <?php if(is_array($dict["value"])): foreach($dict["value"] as $key=>$item): ?><div class="pid var-box">
                                值：<input type="text" class="textbox" name="value[]" style="width:300px;"
                                         value="<?php echo ($item["value"]); ?>"/>
                                描述：<input type="text" class="textbox" name="desc[]" style="width:300px;"
                                          value="<?php echo ($item["desc"]); ?>"/>
                                <input type="button" class="button" value="删除" onclick="delvalue($(this));"/>
                                <hr>
                            </div><?php endforeach; endif; ?>
                    </div>
                </td>
            </tr>

            <tr>
                <td colspan=2 class="bottomTd"></td>
            </tr>
        </table>
        <div class="blank5"></div>

        <div style="display:none" id="hidevalue">
            <div class="pid var-box">
                <tr>
                    <td class="item_input">
                        值：<input type="text" class="textbox" name="value[]" style="width:300px;"/>
                        描述：<input type="text" class="textbox" name="desc[]" style="width:300px;"/>
                        <input type="button" class="button" value="删除" onclick="delvalue($(this));"/></td>
                </tr>
                <hr>
            </div>
        </div>
    </form>
    <script>

        $('#addvalue').click(function () {
            $("#valuetable").prepend($("#hidevalue").html());
        });

        function delvalue(obj) {
            var msg = "确认删除字典值";
            if (confirm(msg) == true) {
                obj.parent().remove();
                return true;
            } else {
                return false;
            }
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