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

<script type="text/javascript" src="__TMPL__widget/mulselect/cityData.js"></script>
<script type="text/javascript" src="__TMPL__widget/mulselect/mulselect.v1.js"></script>

<?php function getUserSite($siteid)
    {
        $sitename = array_search($siteid,$GLOBALS['sys_config']['TEMPLATE_LIST']);
        if($sitename)
        {
            return $sitename;
        }
        else
        {
            return '?????????';
        }
    } ?>
<script type="text/javascript" src="//static.firstp2p.com/attachment/region.js?v=<?php echo app_conf('APP_SUB_VER'); ?>"></script>
<div class="main">
<div class="main_title"><?php echo L("EDIT");?> <a href="<?php echo u("User/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form name="edit"  id="Jcarry_From_2" action="__APP__" method="post" enctype="multipart/form-data">

<div class="blank5"></div>
<table class="form conf_tab" cellpadding=0 cellspacing=0 rel="1">
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <select name="user_purpose" id="user_purpose" class="require">
                <?php if(is_array($user_purpose_list)): foreach($user_purpose_list as $key=>$purpose_item): ?><option value="<?php echo ($purpose_item["bizId"]); ?>" <?php if($vo['user_purpose'] == $purpose_item['bizId']): ?>selected="selected"<?php endif; ?>><?php echo ($purpose_item["bizName"]); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_NAME");?>:</td>
        <td class="item_input"><input type="hidden" class="textbox require" name="user_name" value="<?php echo ($vo["user_name"]); ?>" /><?php echo ($vo["user_name"]); ?></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_EMAIL");?>:</td>
                <td class="item_input"><input type="text" class="textbox" name="email" value="<?php echo ($vo["email"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_MOBILE");?>:</td>
        <td class="item_input">
            <select name="country_code" id="country_code" class="require">
                <?php if(is_array($mobile_code_list)): foreach($mobile_code_list as $key=>$mobile_code_item): ?><option value="<?php echo ($mobile_code_item["country"]); ?>"  <?php if($vo['country_code'] == $mobile_code_item['country']): ?>selected="selected"<?php endif; ?>><?php echo ($mobile_code_item["name"]); ?> <?php echo ($mobile_code_item["code"]); ?></option><?php endforeach; endif; ?>
            </select>
         - <input type="text" class="textbox" name="mobile" value="<?php echo ($vo["mobile"]); ?>" size="13"/></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_GROUP");?>:</td>
        <td class="item_input">
            <select name="group_id" id="group_id" class="require">
                <?php if(is_array($group_list)): foreach($group_list as $key=>$group_item): ?><option value="<?php echo ($group_item["id"]); ?>" factor="<?php echo ($group_item["channel_pay_factor"]); ?>" <?php if($vo['group_id'] == $group_item['id']): ?>selected="selected"<?php endif; ?>><?php echo ($group_item["name"]); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <select id="new_coupon_level_id" name="new_coupon_level_id" class="require">
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
                <td class="item_input"><span class="textbox" ><?php echo (getUserSite($vo["site_id"])); ?></span></td>
    </tr>
    <tr>
        <td class="item_title">??????????????????:</td>
        <td class="item_input">
            <label><input type="radio" class="f-radio" value="1" name="is_staff"  <?php if($vo['is_staff'] == 1): ?>checked="checked"<?php endif; ?>>???</label>
            <label><input type="radio" class="f-radio" value="0" name="is_staff"  <?php if($vo['is_staff'] == 0): ?>checked="checked"<?php endif; ?>>???</label>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>????????????</b></td>
    </tr>
    <tr>
        <td class="item_title">??????:</td>
        <td class="item_input"><input size="100" type="text" value="<?php echo ($vo["real_name"]); ?>" class="textbox" name="real_name" /> <button id="_js_edit_identity">????????????</button></td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
        <select id="id_type" name="id_type">
        <?php if(is_array($idTypes)): foreach($idTypes as $key=>$type): ?><option value="<?php echo ($key); ?>" <?php if($vo["id_type"] == $key): ?>selected<?php endif; ?>><?php echo ($type); ?></option><?php endforeach; endif; ?>
        </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <input type="text" value="<?php echo ($vo["idno"]); ?>" class="textbox" name="idno" <?php if($stock == 1): ?>disabled<?php endif; ?>/></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("USER_BIRTHDAY");?>:</td>
        <td class="item_input">
            <input type="text" name="byear" class="textbox" value="<?php echo ($vo["byear"]); ?>"  style="width:40px" maxlength="4" /><?php echo L("USER_BYEAR");?>
            <input type="text" name="bmonth" class="textbox" value="<?php echo ($vo["bmonth"]); ?>" style="width:20px" maxlength="2"/><?php echo L("USER_BMONTH");?>
            <input type="text" name="bday" class="textbox" value="<?php echo ($vo["bday"]); ?>"  style="width:20px" maxlength="2" /><?php echo L("USER_BDAY");?>
        </td>
    </tr>
    <tr>
        <td class="item_title">??????:</td>
        <td class="item_input">
            <select name="sex">
                <option value="0" <?php if($vo['sex'] == 0): ?>selected="selected"<?php endif; ?>>???</option>
                <option value="1" <?php if($vo['sex'] == 1): ?>selected="selected"<?php endif; ?>>???</option>
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
        <td class="item_title">??????/????????????:</td>
        <td class="item_input">
           <?php echo ($comments); ?>
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
                <option value="???????????????" <?php if($vo['graduation'] == '???????????????'): ?>selected="selected"<?php endif; ?>>???????????????</option>
                <option value="??????" <?php if($vo['graduation'] == '??????'): ?>selected="selected"<?php endif; ?>>??????</option>
                <option value="??????" <?php if($vo['graduation'] == '??????'): ?>selected="selected"<?php endif; ?>>??????</option>
                <option value="??????????????????" <?php if($vo['graduation'] == '??????????????????'): ?>selected="selected"<?php endif; ?>>??????????????????</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <select name="graduatedyear">
                <?php $y = date("Y"); for($i=$y;$i>=$y-100;$i--): ?>
                    <option value="<?php echo $i;?>" <?php if($i == intval($vo['graduatedyear'])):?>selected="selected"<?php endif; ?>><?php echo $i;?></option>
                <?php endfor; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <input type="text" name="university" class="textbox" value="<?php echo ($vo["university"]); ?>" />
        </td>
    </tr>
    <tr>
        <td class="item_title">12??????????????????:</td>
        <td class="item_input">
            <input type="text" name="edu_validcode" class="textbox" value="<?php echo ($vo["edu_validcode"]); ?>" />
        </td>
    </tr>
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>????????????</b></td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <label><input type="radio" class="f-radio" value="??????" name="marriage" <?php if($vo['marriage'] == '??????'): ?>checked="checked"<?php endif; ?>>??????</label>
            <label><input type="radio" class="f-radio" value="??????" name="marriage" <?php if($vo['marriage'] == '??????'): ?>checked="checked"<?php endif; ?>>??????</label>
            <label><input type="radio" class="f-radio" value="??????" name="marriage" <?php if($vo['marriage'] == '??????'): ?>checked="checked"<?php endif; ?>>??????</label>
            <label><input type="radio" class="f-radio" value="??????" name="marriage" <?php if($vo['marriage'] == '??????'): ?>checked="checked"<?php endif; ?>>??????</label>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>????????????</b></td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <label><input type="radio" class="f-radio" value="1" name="hashouse"  <?php if($vo['hashouse'] == 1): ?>checked="checked"<?php endif; ?>>???</label>
            <label><input type="radio" class="f-radio" value="0" name="hashouse"  <?php if($vo['hashouse'] == 0): ?>checked="checked"<?php endif; ?>>???</label>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <label><input type="radio" class="f-radio" value="1" name="houseloan"  <?php if($vo['houseloan'] == 1): ?>checked="checked"<?php endif; ?>>???</label>
            <label><input type="radio" class="f-radio" value="0" name="houseloan"  <?php if($vo['houseloan'] == 0): ?>checked="checked"<?php endif; ?>>???</label>
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
            <input type="text" name="car_brand" class="textbox" value="<?php echo ($vo["car_brand"]); ?>" />
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <input type="text" name="car_year" class="textbox" value="<?php echo ($vo["car_year"]); ?>" />
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <input type="text" name="car_number" class="textbox" value="<?php echo ($vo["car_number"]); ?>" />
        </td>
    </tr>


    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>???????????????</b></td>
    </tr>
    <tr>
        <td class="item_title">?????????:</td>
        <td class="item_input">
            <span class="_js_bankinfo" ><?php echo ($bankcard_info["card_name"]); ?></span>&nbsp;<button id="_js_reset_bankinfo">???????????????</button>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            ?????????
        </td>
    </tr>
    <tr>
        <td class="item_title">??????:</td>
        <td class="item_input">
                <?php echo ($bankcard_info["bank_name"]); ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">??????????????????:</td>
        <td class="item_input">
            <?php echo ($fullregion); ?>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td>
        <?php echo ($bankcard_info["bankzone"]); ?>
        </td>
    </tr>
    <tr>
        <td class="item_title">?????????:</td>
        <td id="bankIssue">
        <?php echo ($bankcard_info["bankcode"]); ?>
        </td>
    </tr>

    <tr>
        <td class="item_title">??????:</td>
        <td class="item_input">
           <?php echo ($bankcard_info["bankcard"]); ?>
        </td>
    </tr>
    <tr>
        <td class="item_title">???????????????:</td>
        <td class="item_input">
           <?php if($bankcard_info['card_type'] == 0): ?>????????????
           <?php elseif($bankcard_info['card_type'] == 1): ?> ????????????
           <?php else: ?> ???;<?php endif; ?>
        </td>
    </tr>

<tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>????????????</b></td>
    </tr>
    <tr>
        <td class="item_title">???????????????</td>
        <td class="item_input">
            <input type="text" value="<?php echo ($vo1["name"]); ?>"  name="deli_name" id="name" class="txt int_placeholder" >
        </td>
    </tr>
    <tr>
        <td class="item_title">?????????</td>
        <td class="item_input">
            <input type="text" value="<?php echo ($vo1["mobile"]); ?>"  name="deli_mobile" id="mobile" class="txt int_placeholder" >
        </td>
    </tr>

   <tr>
        <td class="item_title">????????????</td>
        <td class="item_input">
           <div class="cityDom" id="cityDom"  data-selectname="deli_province:deli_city:deli_areaA" data-defaultdata='<?php echo ($vo1["area"]); ?>' ></div>
        </td>
    </tr>
    <script>
        // ??????????????????
          new Firstp2p.mulselect(".cityDom", {
               mulDom: ".cityDom",
               defaultdata: !!$("#cityDom").attr("data-defaultdata") ? $("#cityDom").attr("data-defaultdata").split(":") : ["????????????", "????????????", "????????????"],
               selectsClass: "select",
               url: json,
               jsonsingle: "n",
               jsonmany: "s"
               //selectName : $("#cityDom").data("name")
          });
    </script>
    <tr>
        <td class="item_title">????????????</td>
        <td class="item_input">
            <textarea name="deli_address" id="address" class="txt addarea int_placeholder" placeholder="???????????????????????????????????????????????????????????????"  style="width:400px;height:50px;" data-placeholder="???????????????????????????????????????????????????????????????" ><?php echo ($vo1["address"]); ?></textarea >
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????</td>
        <td class="item_input">
           <input type="text" value="<?php echo ($vo1["postalcode"]); ?>" placeholder="?????????????????????"  data-placeholder="?????????????????????" name="deli_postalcode" id="postalcode" class="txt int_placeholder">
        </td>
    </tr>





    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>??????</b></td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <label><input type="radio" class="f-radio" value="1" name="haschild"  <?php if($vo['haschild'] == 1): ?>checked="checked"<?php endif; ?>>???</label>
            <label><input type="radio" class="f-radio" value="0" name="haschild"  <?php if($vo['haschild'] == 0): ?>checked="checked"<?php endif; ?>>???</label>
        </td>
    </tr>
    <tr>
        <td class="item_title">??????:</td>
        <td class="item_input">
            <select name="n_province_id">
                <option value="0">=<?php echo L("PLEASE_SELECT");?>=</option>
                <?php if(is_array($region_lv2)): foreach($region_lv2 as $key=>$lv2): ?><option <?php if($lv2['id'] == $vo['n_province_id']): ?>selected="selected"<?php endif; ?> value="<?php echo ($lv2["id"]); ?>"><?php echo ($lv2["name"]); ?></option><?php endforeach; endif; ?>
            </select>

            <select name="n_city_id">
                <option value="0">=<?php echo L("PLEASE_SELECT");?>=</option>
                <?php if(is_array($n_region_lv3)): foreach($n_region_lv3 as $key=>$lv3): ?><option <?php if($lv3['id'] == $vo['n_city_id']): ?>selected="selected"<?php endif; ?> value="<?php echo ($lv3["id"]); ?>"><?php echo ($lv3["name"]); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">???????????????:</td>
        <td class="item_input">
            <select name="province_id">
                <option value="0">=<?php echo L("PLEASE_SELECT");?>=</option>
                <?php if(is_array($region_lv2)): foreach($region_lv2 as $key=>$lv2): ?><option <?php if($lv2['id'] == $vo['province_id']): ?>selected="selected"<?php endif; ?> value="<?php echo ($lv2["id"]); ?>"><?php echo ($lv2["name"]); ?></option><?php endforeach; endif; ?>
            </select>

            <select name="city_id">
                <option value="0">=<?php echo L("PLEASE_SELECT");?>=</option>
                <?php if(is_array($region_lv3)): foreach($region_lv3 as $key=>$lv3): ?><option <?php if($lv3['id'] == $vo['city_id']): ?>selected="selected"<?php endif; ?> value="<?php echo ($lv3["id"]); ?>"><?php echo ($lv3["name"]); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <input value="<?php echo ($vo["address"]); ?>" class="textbox" name="address" size="50">
        </td>
    </tr>
    <tr>
        <td class="item_title">??????:</td>
        <td class="item_input">
            <input type="text" class="textbox"  value="<?php echo ($vo["phone"]); ?>" name="phone">
        </td>
    </tr>
    <tr>
        <td class="item_title">????????????:</td>
        <td class="item_input">
            <input type="text" class="textbox"  value="<?php echo ($vo["postcode"]); ?>" name="postcode">
        </td>
    </tr>
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"></td>
    </tr>
    <tr>
        <td class="item_title"><?php echo L("IS_EFFECT");?>:</td>
        <td class="item_input">
            <lable><?php echo L("IS_EFFECT_1");?><input type="radio" name="is_effect" value="1" <?php if($vo['is_effect'] == 1): ?>checked="checked"<?php endif; ?> /></lable>
            <lable><?php echo L("IS_EFFECT_0");?><input type="radio" name="is_effect" value="0" <?php if($vo['is_effect'] == 0): ?>checked="checked"<?php endif; ?> /></lable>
        </td>
    </tr>
    <?php if(is_array($field_list)): foreach($field_list as $key=>$field_item): ?><tr>
        <td class="item_title"><?php echo ($field_item["field_show_name"]); ?>:</td>
        <td class="item_input">
             <?php if($field_item['input_type'] == 0): ?><input type="text" class="textbox <?php if($field_item['is_must'] == 1): ?>require<?php endif; ?>" name="<?php echo ($field_item["field_name"]); ?>" value="<?php echo ($field_item["value"]); ?>" /><?php endif; ?>

             <?php if($field_item['input_type'] == 1): ?><select name="<?php echo ($field_item["field_name"]); ?>">
                     <?php if(is_array($field_item["value_scope"])): foreach($field_item["value_scope"] as $key=>$value_item): ?><option value="<?php echo ($value_item); ?>" <?php if($field_item['value'] == $value_item): ?>selected="selected"<?php endif; ?>><?php echo ($value_item); ?></option><?php endforeach; endif; ?>
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
            <input type="hidden" name="id" value="<?php echo ($vo["id"]); ?>" />
            <input type="hidden" name="bankcard_id" id='bankcard_id' value="<?php echo ($bankcard_info["id"]); ?>" />
            <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="User" />
            <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="update" />
            <!--????????????-->
            <input type="button" class="button" value="<?php echo L("EDIT");?>" onclick="sub()" />
            <input type="reset" class="button" value="<?php echo L("RESET");?>" />
            </td>
        </tr>
        <tr>
            <td colspan=2 class="bottomTd"></td>
        </tr>
    </table>
</form>
</div>
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
</script>

<script type="text/javascript">
jQuery(function(){
    setTimeout("bank_site();",1000);
    //?????????????????????
    $("#_js_reset_bankinfo").click(function(){
        window.location.href = "/m.php?m=User&a=edit_bank&uid="+$('input[name=id]').val();
        return false;
    });

    //??????????????????
    $("#_js_edit_identity").click(function(){
        window.location.href = "/m.php?m=User&a=edit_identity&uid="+$('input[name=id]').val();
        return false;
    });


    //??????????????????
    $("#_js_reset_status").click(function(){
        var id = $("#_js_bankinfo_id").val();
        var uid = $('input[name="id"]').val();
        var status = $('#status').val();
        if(id == 0){
            alert("?????????????????????");
            return false;
        }
           if(id>0 ){
               $.ajax({
                      type: "POST",
                      url: ROOT+'?m=User&a=resetStatus',
                      data: "id="+id+"&status="+status+"&uid="+uid,
                      dataType:"json",
                      success: function(msg){
                        if (msg.code !== '0000')
                        {
                            return alert(msg.msg);
                        }
                        $('#status').val(msg.msg);
                        var status_tips = msg.msg == 0 ? '?????????': '?????????';
                        $('#status_tips').text(status_tips);
                      }
               });
           }
        return false;
    });

    //????????????
    $("select[name='bank_id']").bind("change",function(){
        bank_site();
    });
    //????????????
    $("#_js_shoudong").live("click",function(){
       //$("#_js_shoudong_input").show();
    });

    $("#_js_bankone").live("change",function(){
        $("#_js_shoudong_input").val('');
        $("#_js_shoudong_input").hide();
    });

});
 //??????????????????
UE.getEditor('editor');

//????????????
function bank_site(){
    var c = $("select[name='c_region_lv3']").find("option:selected").text();
    var p = $("select[name='c_region_lv2']").find("option:selected").text();
    var b = $("select[name='bank_id']").find("option:selected").text();
    var n = '<?php echo ($bankcard_info["bankzone"]); ?>';
    var data = {c:c,p:p,n:n};

    $.getJSON("<?php echo ($wxlc_domain); ?>/api/banklist?c="+c+"&p="+p+"&n="+n+"&b="+b+"&jsonpCallback=?",function(rs){
        $("#_js_bank_site").html(rs);
        qIssue(n);
    });
}

function checkParams() {

    var idno = $("input[name='idno']").val();
    var id_type = $("#id_type").val();

    if (idno == '' || idno == 'undefined') {
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

$('#_js_bankone').live('change', function(){
    qIssue($(this).val());
});


function qIssue(issueName)
{
    if (issueName == '') {
        $('#bankIssue').html('<span style="color:red;">???????????????????????????</span>');
        return;
    }
    $.getJSON('/m.php?m=BankList&a=qIssue&bankIssueName='+encodeURI(issueName), function(data){
        if (data.code == 0)
            $('#bankIssue').html(data.issue);
        else
            $('#bankIssue').html('<span style="color:red;">???????????????????????????</span>');
    });
}

qIssue('<?php echo ($bankcard_info["bankzone"]); ?>');


function sub(){
    $("form").unbind("submit");
    error = checkRiskInfo();
    if(error != ''){
        if(!confirm(error+"????????????????????????")){
            return false;
        }
    }
    $("form").submit();
}

function checkRiskInfo(){
    group_id = $("[name='group_id']").val();
    id = $("[name='id']").val();
    error='';
    $.ajax({
      type: "POST",
      async: false,
      url: ROOT+'?m=User&a=ajaxCheckRiskInfo',
      data: "id="+id+"&group_id="+group_id,
      dataType:"json",
      success: function(msg){
        if (msg.status == '0')
        {
            error = msg.info;
        }
      }
    });
    return error;
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