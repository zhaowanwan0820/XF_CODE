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

<script type="text/javascript" src="__TMPL__Common/js/user_edit.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/conf.js"></script>

<script type="text/javascript" src="__TMPL__ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="__TMPL__ueditor/ueditor.all.min.js"></script>
<script type="text/javascript" src="__TMPL__ueditor/lang/zh-cn/zh-cn.js"></script>

<script type="text/javascript" src="__TMPL__chosen/js/chosen.jquery.min.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__chosen/css/chosen.min.css" />

<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<script type="text/javascript" src="//static.firstp2p.com/attachment/region.js?v=<?php echo app_conf('APP_SUB_VER'); ?>"></script>
<div class="main">
<div class="main_title"><?php echo L("ADD");?> <a href="<?php echo u("User/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form name="edit" action="__APP__" method="post" enctype="multipart/form-data">

<div class="blank5"></div>
<table class="form conf_tab" cellpadding=0 cellspacing=0 rel="1">
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <select name="user_purpose" id="user_purpose" class="require">
                <?php if(is_array($user_purpose_list)): foreach($user_purpose_list as $key=>$purpose_item): ?><option value="<?php echo ($purpose_item["bizId"]); ?>"><?php echo ($purpose_item["bizName"]); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_NAME");?>:</td>
        <td class="item_input"><input type="text" class="textbox require" name="user_name" /></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_EMAIL");?>:</td>
        <td class="item_input"><input type="text" class="textbox require" name="email" /></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_MOBILE");?>:</td>
        <td class="item_input"><input type="text" class="textbox <?php if(intval(app_conf("MOBILE_MUST"))==1) echo 'require'; ?>" name="mobile" /></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_PASSWORD");?>:</td>
        <td class="item_input"><input type="password" class="textbox require" name="user_pwd" /></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_CONFIRM_PASSWORD");?>:</td>
        <td class="item_input"><input type="password" class="textbox require" name="user_confirm_pwd" /></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_GROUP");?>:</td>
        <td class="item_input">
            <select name="group_id" id="group_id" class="require">
                <?php if(is_array($group_list)): foreach($group_list as $key=>$group_item): ?><option value="<?php echo ($group_item["id"]); ?>"><?php echo ($group_item["name"]); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <select id="new_coupon_level_id" name="new_coupon_level_id">
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">??????????????????:</td>
        <td class="item_input">
            <label><input type="radio" class="f-radio" value="1" name="is_staff" >???</label>
            <label><input type="radio" class="f-radio" value="0" name="is_staff"  checked="checked" >???</label>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>????????????</b></td>
    </tr>
    <tr>
        <td class="item_title">??????:</td>
        <td class="item_input"><input size="100" type="text"  class="textbox" name="real_name" /></td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
        <select id="id_type" name="id_type">
        <?php if(is_array($idTypes)): foreach($idTypes as $key=>$type): ?><option value="<?php echo ($key); ?>"><?php echo ($type); ?></option><?php endforeach; endif; ?>
        </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input"><input type="text" class="textbox" name="idno" /></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_BIRTHDAY");?>:</td>
        <td class="item_input">
            <input type="text" name="byear" class="textbox" value=""  style="width:40px" maxlength="4" /><?php echo L("USER_BYEAR");?>
            <input type="text" name="bmonth" class="textbox" value="" style="width:20px" maxlength="2"/><?php echo L("USER_BMONTH");?>
            <input type="text" name="bday" class="textbox" value=""  style="width:20px" maxlength="2" /><?php echo L("USER_BDAY");?>
        </td>
    </tr>
    <tr>
        <td class="item_title">??????:</td>
        <td class="item_input">
            <select name="sex">
                <option value="0">???</option>
                <option value="1">???</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <script id="editor" name="info" type="text/plain" style="width:800px;height:200px; float:left;"><?php echo ($vo['info']); ?></script>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>????????????</b></td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <select name="graduation">
                <option value="">?????????</option>
                <option value="???????????????">???????????????</option>
                <option value="??????">??????</option>
                <option value="??????">??????</option>
                <option value="??????????????????">??????????????????</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <select name="graduatedyear">
                <?php $y = date("Y"); for($i=$y;$i>=$y-100;$i--): ?>
                    <option value="<?php echo $i;?>"><?php echo $i;?></option>
                <?php endfor; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <input type="text" name="university" class="textbox" value="" />
        </td>
    </tr>
    <tr>
        <td class="item_title">12??????????????????:</td>
        <td class="item_input">
            <input type="text" name="edu_validcode" class="textbox" value="" />
        </td>
    </tr>
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>????????????</b></td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <label><input type="radio" class="f-radio" value="??????" name="marriage">??????</label>
            <label><input type="radio" class="f-radio" value="??????" name="marriage">??????</label>
            <label><input type="radio" class="f-radio" value="??????" name="marriage">??????</label>
            <label><input type="radio" class="f-radio" value="??????" name="marriage">??????</label>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>????????????</b></td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <label><input type="radio" class="f-radio" value="1" name="hashouse">???</label>
            <label><input type="radio" class="f-radio" value="0" name="hashouse" checked="checked">???</label>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <label><input type="radio" class="f-radio" value="1" name="houseloan">???</label>
            <label><input type="radio" class="f-radio" value="0" name="houseloan" checked="checked">???</label>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>????????????</b></td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <label><input type="radio" class="f-radio" value="1" name="hascar"  <?php if($vo['hascar'] == 1): ?>checked="checked"<?php endif; ?>>???</label>
            <label><input type="radio" class="f-radio" value="0" name="hascar"  <?php if($vo['hascar'] == 0): ?>checked="checked"<?php endif; ?>>???</label>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <label><input type="radio" class="f-radio" value="1" name="carloan"  <?php if($vo['carloan'] == 1): ?>checked="checked"<?php endif; ?>>???</label>
            <label><input type="radio" class="f-radio" value="0" name="carloan"  <?php if($vo['carloan'] == 0): ?>checked="checked"<?php endif; ?>>???</label>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <input type="text" name="car_brand" class="textbox" value="" />
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <input type="text" name="car_year" class="textbox" value="" />
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <input type="text" name="car_number" class="textbox" value="" />
        </td>
    </tr>

    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>??????</b></td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <label><input type="radio" class="f-radio" value="1" name="haschild" />???</label>
            <label><input type="radio" class="f-radio" value="0" name="haschild" />???</label>
        </td>
    </tr>
    <tr>
        <td class="item_title">??????:</td>
        <td class="item_input">
            <select name="n_province_id">
                <option value="0">=<?php echo L("PLEASE_SELECT");?>=</option>
                <?php if(is_array($region_lv2)): foreach($region_lv2 as $key=>$lv2): ?><option value="<?php echo ($lv2["id"]); ?>"><?php echo ($lv2["name"]); ?></option><?php endforeach; endif; ?>
            </select>

            <select name="n_city_id">
                <option value="0">=<?php echo L("PLEASE_SELECT");?>=</option>
                <?php if(is_array($region_lv3)): foreach($region_lv3 as $key=>$lv3): ?><option value="<?php echo ($lv3["id"]); ?>"><?php echo ($lv3["name"]); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">???????????????:</td>
        <td class="item_input">
            <select name="province_id">
                <option value="0">=<?php echo L("PLEASE_SELECT");?>=</option>
                <?php if(is_array($region_lv2)): foreach($region_lv2 as $key=>$lv2): ?><option value="<?php echo ($lv2["id"]); ?>"><?php echo ($lv2["name"]); ?></option><?php endforeach; endif; ?>
            </select>

            <select name="city_id">
                <option value="0">=<?php echo L("PLEASE_SELECT");?>=</option>
                <?php if(is_array($region_lv3)): foreach($region_lv3 as $key=>$lv3): ?><option value="<?php echo ($lv3["id"]); ?>"><?php echo ($lv3["name"]); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <input type="text" value="" class="textbox" name="address" size="50">
        </td>
    </tr>
    <tr>
        <td class="item_title">??????:</td>
        <td class="item_input">
            <input type="text" class="textbox"  value="" name="phone">
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <input type="text" class="textbox"  value="" name="postcode">
        </td>
    </tr>
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("IS_EFFECT");?>:</td>
        <td class="item_input">
            <lable><?php echo L("IS_EFFECT_1");?><input type="radio" name="is_effect" value="1" checked="checked" /></lable>
            <lable><?php echo L("IS_EFFECT_0");?><input type="radio" name="is_effect" value="0" /></lable>
        </td>
    </tr>
    <?php if(is_array($field_list)): foreach($field_list as $key=>$field_item): ?><tr>
        <td class="item_title"><?php echo ($field_item["field_show_name"]); ?>:</td>
        <td class="item_input">
             <?php if($field_item['input_type'] == 0): ?><input type="text" class="textbox <?php if($field_item['is_must'] == 1): ?>require<?php endif; ?>" name="<?php echo ($field_item["field_name"]); ?>" /><?php endif; ?>

             <?php if($field_item['input_type'] == 1): ?><select name="<?php echo ($field_item["field_name"]); ?>">
                     <?php if(is_array($field_item["value_scope"])): foreach($field_item["value_scope"] as $key=>$value_item): ?><option value="<?php echo ($value_item); ?>"><?php echo ($value_item); ?></option><?php endforeach; endif; ?>
                 </select><?php endif; ?>
        </td>
    </tr><?php endforeach; endif; ?>
    <tr>
        <td colspan=2 class="bottomTd"></td>
    </tr>
</table>

<div class="blank5"></div>
    <table class="form" cellpadding=0 cellspacing=0>
        <tr>
            <td colspan=2 class="topTd"></td>
        </tr>
        <tr>
            <td class="item_title"></td>
            <td class="item_input">
            <!--????????????-->
            <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="User" />
            <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="insert" />
            <!--????????????-->
            <input type="submit" class="button" value="<?php echo L("ADD");?>" />
            <input type="reset" class="button" value="<?php echo L("RESET");?>" />
            </td>
        </tr>
        <tr>
            <td colspan=2 class="bottomTd"></td>
        </tr>
    </table>
</form>
</div>

<script>
  $(document).ready(function(){
        $("select[name='province_id']").bind("change",function(){
            load_city($("select[name='province_id']"),$("select[name='city_id']"));
        });
        $("select[name='n_province_id']").bind("change",function(){
            load_city($("select[name='n_province_id']"),$("select[name='n_city_id']"));
        });

    });

    function load_city(pname,cname)
    {
        var id = pname.val();
        var evalStr="regionConf.r"+id+".c";

        if(id==0)
        {
            var html = "<option value='0'>=?????????=</option>";
        }
        else
        {
            var regionConfs=eval(evalStr);
            evalStr+=".";
            var html = "<option value='0'>=?????????=</option>";
            for(var key in regionConfs)
            {
                html+="<option value='"+eval(evalStr+key+".i")+"'>"+eval(evalStr+key+".n")+"</option>";
            }
        }
        cname.html(html);
    }

  //??????????????????
  UE.getEditor('editor');

</script>
<script type="text/javascript">
    //???????????????????????????
    $('#group_id').chosen();

    function changeLevelSelect(){
        var url = "/m.php?m=UserCouponLevel&a=get_level_select";
        var current_coupon_level_id = '<?php echo ($vo["new_coupon_level_id"]); ?>';
        $.getJSON(url,{group_id:$("#group_id").val()},function(json){
            var coupon_level_id = $("#new_coupon_level_id");
            $("option",coupon_level_id).remove(); //?????????????????????
            $.each(json,function(index,array){
                var selected_str = '';
                if(array['id'] == current_coupon_level_id){
                    selected_str = 'selected="selected"';
                }
                var option = "<option value='"+array['id']+"' "+selected_str+">"+array['level']+"</option>";
                coupon_level_id.append(option);
            });
        });
    }
    
    changeLevelSelect();
    $("#group_id").change(function(){
        $("#group_factor_text").html($(this).find("option:selected").attr("factor"));
        changeLevelSelect();
    });

    function checkParams() {

        var idno = $("input[name='idno']").val();
        var id_type = $("#id_type").val();

        if (idno = '' || idno == 'undefined') {
            alert("????????????????????????!");
        }

        if (id_type == 1) {
            if (!/(^\d{15}$)|(^\d{17}([0-9]|X)$)/.test(idno)) {
                alert("????????????????????????");
                return false;
            }
        }


        if (id_type == 4) {
            var patt=new RegExp(/^[A-Z][A-Z]?\d{6}(\((\d|[A-Z])\))?$/);
            if (!patt.test(idno)) {
                alert("??????????????????????????????");
                return false;
            }
        }

        if (id_type == 5) {
            var patt = new RegExp(/^\d{7}\(\d\)$/);
            if (!patt.test(idno)) {
                alert("??????????????????????????????");
                return false;
            }
        }

        if (id_type == 6) {
            if (!/^[A-Z]\d{9}$/.test(idno)) {
                alert("??????????????????????????????");
                return false;
            }
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