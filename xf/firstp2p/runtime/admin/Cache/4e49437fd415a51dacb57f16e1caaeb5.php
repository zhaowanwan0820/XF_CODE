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

<!-- <script type="text/javascript" src="__TMPL__ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="__TMPL__ueditor/ueditor.all.min.js"></script>
<script type="text/javascript" src="__TMPL__ueditor/lang/zh-cn/zh-cn.js"></script> -->
<script type="text/javascript" src="__TMPL__Common/js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.weebox.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />

<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>

<script type="text/javascript" src="__TMPL__chosen/js/chosen.jquery.min.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__chosen/css/chosen.min.css" />

<script type="text/javascript" src="__TMPL__region.js?v=<?php echo app_conf('APP_SUB_VER'); ?>"></script>
<div class="main">
<div class="main_title"><?php echo L("EDIT");?> <a href=<?php echo u("Enterprise/index?p=$currentPage");?> class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form id="addForm" name="addForm" action="__APP__" method="post" enctype="multipart/form-data">
<div class="blank5"></div>
<table class="form conf_tab" cellpadding=0 cellspacing=0 rel="1">
    <!-- 企业用户信息Start -->
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title">账户类型:</td>
        <td class="item_input">
            <select name="company_purpose" id="company_purpose" class="textbox require" style="width:180px;" onchange="javascript:change_company_purpose()">
                <?php if(is_array($company_purpose_list)): foreach($company_purpose_list as $key=>$purpose_item): ?><option value="<?php echo ($purpose_item["bizId"]); ?>" <?php if($vo['user_purpose'] == $purpose_item['bizId']): ?>selected="selected"<?php endif; ?>><?php echo ($purpose_item["bizName"]); ?></option><?php endforeach; endif; ?>
            </select>
            <font color='red'>*</font>
        </td>
    </tr>
    <tr id="id_privilege_note" <?php if(empty($enterpriseBaseInfo['privilege_note'])){echo 'style="display:none;"';}?> >
        <td class="item_title">其他用途说明:</td>
        <td class="item_input"><input type="text" class="textbox" id="privilege_note" name="privilege_note" value="<?php echo ($enterpriseBaseInfo["privilege_note"]); ?>" />（如“企业会员账户用途”选择“其他”，需在此录入用途描述）</td>
    </tr>
    <tr>
        <td class="item_title">企业会员编号:</td>
        <td class="item_input"><input type="text" class="textbox" id="member_sn" name="member_sn" value="<?php echo ($companyMemberSn); ?>" readonly="readonly" /></td>
    </tr>
    <tr>
        <td class="item_title">企业会员标识:</td>
        <td class="item_input"><input type="text" class="textbox require" id="identifier" name="identifier" value="<?php echo ($enterpriseBaseInfo["identifier"]); ?>" ondragenter="return false" onkeyup="this.value=check(this.value)"/></td>
    </tr>
    <tr>
        <td class="item_title">企业会员名称:</td>
        <td class="item_input"><input type="text" class="textbox" id="user_name" name="user_name" readonly="readonly" value="<?php echo ($vo["user_name"]); ?>" ondragenter="return false" onpaste="return false" onkeyup="this.value=check(this.value)"/></td>
    </tr>
    <tr>
        <td class="item_title">状态:</td>
        <td class="item_input">
            <select name="is_effect" id="is_effect" class="textbox require" style="width:180px;">
                <option value="1" <?php if($vo['is_effect'] == 1): ?>selected="selected"<?php endif; ?>>有效</option>
                <option value="0" <?php if($vo['is_effect'] == 0): ?>selected="selected"<?php endif; ?>>无效</option>
            </select>
            <font color='red'>*</font>
        </td>
    </tr>
    <tr>
        <td class="item_title">会员所属网站:</td>
        <td class="item_input">
            <select name="group_id" id="group_id" class="textbox require" style="width:180px;">
                <?php if(is_array($groupList)): foreach($groupList as $key=>$group_item): ?><option value="<?php echo ($group_item["id"]); ?>" <?php if($group_item["id"] == $vo['group_id']){echo 'selected';}?> ><?php echo ($group_item["name"]); ?></option><?php endforeach; endif; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="item_title">服务等级:</td>
        <td class="item_input">
            <select name="new_coupon_level_id" id="new_coupon_level_id" class="textbox require" style="width:180px;">
            </select>
        </td>
    </tr>
    <!-- 企业用户信息End -->
    <!-- 基本信息Start -->
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>基本信息</b></td>
    </tr>
    <tr>
        <td class="item_title">企业全称:</td>
        <td class="item_input"><input type="text" class="textbox require" id="company_name" name="company_name" value="<?php echo ($enterpriseBaseInfo["company_name"]); ?>" /><font color='red'>*</font></td>
    </tr>
    <tr>
        <td class="item_title">企业简称:</td>
        <td class="item_input"><input type="text" class="textbox require" id="company_shortname" name="company_shortname" value="<?php echo ($enterpriseBaseInfo["company_shortname"]); ?>" /><font color='red'>*</font></td>
    </tr>
    <tr>
        <td class="item_title">企业证件类别:</td>
        <td class="item_input">
        <select id="credentials_type" name="credentials_type" class="textbox require" style="width:180px;">
        <?php if(is_array($credentialsTypes)): foreach($credentialsTypes as $key=>$type): ?><option value="<?php echo ($key); ?>" <?php if($enterpriseBaseInfo["credentials_type"] == $key): ?>selected="selected"<?php endif; ?>><?php echo ($type); ?></option><?php endforeach; endif; ?>
        </select>
        <font color='red'>*</font>
        </td>
    </tr>
    <tr>
        <td class="item_title">企业证件号码:</td>
        <td class="item_input"><input type="text" class="textbox require" id="credentials_no" name="credentials_no" value="<?php echo ($enterpriseBaseInfo["credentials_no"]); ?>" onkeyup="value=value.replace(/[\W]/g,'')" onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^\d]/g,''))" /><font color='red'>*</font></td>
    </tr>
    <tr>
        <td class="item_title">企业证件有效期:</td>
        <td class="item_input">
            <input type="text" class="textbox require" style="width:170px;" name="credentials_expire_date" id="credentials_expire_date" value="<?php echo ($enterpriseBaseInfo["credentials_expire_date"]); ?>" maxlength="10" onkeyup="value=value.replace(/[^\d][-\{2}]/,'')" />
            <input type="button" class="button" id="btn_credentials_expire_date" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('credentials_expire_date', '%Y-%m-%d', false, false, 'btn_credentials_expire_date');" />
                        至<input type="text" class="textbox" style="width:170px;" name="credentials_expire_at" id="credentials_expire_at" value="<?php echo ($enterpriseBaseInfo["credentials_expire_at"]); ?>" maxlength="10" onkeyup="value=value.replace(/[^\d][-\{2}]/,'')" />
            <input type="button" class="button" id="btn_credentials_expire_at" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('credentials_expire_at', '%Y-%m-%d', false, false, 'btn_credentials_expire_at');" /><font color='red'>*</font>
            <input type="checkbox" id="is_permanent" name="is_permanent" value="1" onclick="checkPermanent(this)" <?php if($enterpriseBaseInfo["is_permanent"] == 1): ?>checked<?php endif; ?>>长期有效
        </td>
    </tr>
    <tr>
        <td class="item_title">法定代表人姓名:</td>
        <td class="item_input"><input type="text"  class="textbox require" id="legalbody_name" name="legalbody_name" value="<?php echo ($enterpriseBaseInfo["legalbody_name"]); ?>" /><font color='red'>*</font></td>
    </tr>
    <tr>
        <td class="item_title" style="width:150px;">法定代表人证件类别:</td>
        <td class="item_input">
        <select id="legalbody_credentials_type" name="legalbody_credentials_type" class="textbox require" style="width:180px;">
        <?php if(is_array($idTypes)): foreach($idTypes as $key=>$type): ?><option value="<?php echo ($key); ?>" <?php if($enterpriseBaseInfo["legalbody_credentials_type"] == $key): ?>selected="selected"<?php endif; ?>><?php echo ($type); ?></option><?php endforeach; endif; ?>
        </select>
        <font color='red'>*</font>
        </td>
    </tr>
    <tr>
        <td class="item_title">法定代表人证件号码:</td>
        <td class="item_input"><input type="text" class="textbox require" id="legalbody_credentials_no" name="legalbody_credentials_no" value="<?php echo ($enterpriseBaseInfo["legalbody_credentials_no"]); ?>" onkeyup="value=value.replace(/[\W]/g,'')" onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^\d]/g,''))" /><font color='red'>*</font></td>
    </tr>
    <tr>
        <td class="item_title">法定代表人手机号码:</td>
        <td class="item_input">
            <select name="legalbody_mobile_code" id="legalbody_mobile_code" class="textbox" >
                <?php if(is_array($mobileCodeList)): foreach($mobileCodeList as $key=>$mobile_code_item): ?><option value="<?php echo ($mobile_code_item["country"]); ?>|<?php echo ($mobile_code_item["code"]); ?>" <?php if($enterpriseBaseInfo["legalbody_mobile_code"] == $mobile_code_item.code): ?>selected="selected"<?php endif; ?>><?php echo ($mobile_code_item["name"]); ?> <?php echo ($mobile_code_item["code"]); ?></option><?php endforeach; endif; ?>
            </select>
         - <input type="text" class="textbox" id="legalbody_mobile" name="legalbody_mobile" value="<?php echo ($enterpriseBaseInfo["legalbody_mobile"]); ?>" onkeyup="value=value.replace(/[^\d\.]/g,'')" style="ime-mode:Disabled" maxlength="11" /></td>
    </tr>
    <tr>
        <td class="item_title">法定代表人邮箱地址:</td>
        <td class="item_input"><input type="text" class="textbox" id="legalbody_email" name="legalbody_email" value="<?php echo ($enterpriseBaseInfo["legalbody_email"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">企业联系方式:</td>
        <td class="item_input">
            <select name="contract_mobile_code" id="contract_mobile_code1" class="textbox">
                <?php if(is_array($mobileCodeList)): foreach($mobileCodeList as $key=>$mobile_code_item): ?><option value="<?php echo ($mobile_code_item["country"]); ?>|<?php echo ($mobile_code_item["code"]); ?>" <?php if($enterpriseContactInfo['contract_mobile_code'] == $mobile_code_item['code']){echo 'selected';}?>><?php echo ($mobile_code_item["name"]); ?> <?php echo ($mobile_code_item["code"]); ?></option><?php endforeach; endif; ?>
            </select>
            - <input type="text" class="textbox" id="contract_mobile1" name="contract_mobile" value="<?php echo ($enterpriseContactInfo["contract_mobile"]); ?>" onkeyup="value=value.replace(/[^\d-\.]/g,'')" style="ime-mode:Disabled" maxlength="13" /><font color='red'>*</font></td>
    </tr>
    <tr>
        <td class="item_title">企业注册资金</td>
        <td class="item_input">
            <input type="number"  class="textbox require" name="reg_amt" id="reg_amt" value="<?php echo ($enterpriseBaseInfo["reg_amt"]); ?>" placeholder="输入小写数字,单位为万" />万元<font color='red'>*</font></td>
        </td>
    </tr>

    <tr>
        <td class="item_title">企业行业类别</td>
        <td class="item_input">
            <select name="indu_cate" class="textbox require" id="indu_cate" style="width:180px;">
                <?php if(is_array($inducateTypes)): foreach($inducateTypes as $key=>$type): ?><option value="<?php echo ($key); ?>"<?php if($enterpriseBaseInfo["indu_cate"] == $key): ?>selected="selected"<?php endif; ?>><?php echo ($type); ?></option><?php endforeach; endif; ?>
            </select>
            <font color='red'>*</font>
        </td>
    </tr>

    <tr>
        <td class="item_title">企业开户许可证核准号</td>
        <td class="item_input">
            <input type="text" class="textbox require" id="app_no" name="app_no" value="<?php echo ($enterpriseBaseInfo["app_no"]); ?>"/><font color="red">*</font>
        </td>
    </tr>
    <tr>
        <td class="item_title">企业注册地址:</td>
        <td class="item_input">
                <input type="hidden" value="<?php echo ($enterpriseBaseInfo["registration_region_lv1_1"]); ?>" id="registration_region_lv1_1">
                <input type="hidden" value="<?php echo ($enterpriseBaseInfo["registration_region_lv1_2"]); ?>" id="registration_region_lv1_2">
                <input type="hidden" value="<?php echo ($enterpriseBaseInfo["registration_region_lv1_3"]); ?>" id="registration_region_lv1_3">
                <input type="hidden" value="<?php echo ($enterpriseBaseInfo["registration_region_lv1_4"]); ?>" id="registration_region_lv1_4">
                <select id="registration_region_lv1" name="registration_region_lv1" class="_js_bankinfo textbox">
                    <option value="0">=<?php echo L("REGION_LV1");?>=</option>
                    <?php if(is_array($nRegionLv1)): foreach($nRegionLv1 as $key=>$lv1): ?><option value="<?php echo ($lv1["id"]); ?>" <?php if($enterpriseBaseInfo['registration_region_lv1_1'] == $lv1['id']){echo 'selected';}?>><?php echo ($lv1["name"]); ?></option><?php endforeach; endif; ?>
                </select>
                <select id="registration_region_lv2" name="registration_region_lv2" class="_js_bankinfo textbox">
                    <option value="0">=<?php echo L("REGION_LV2");?>=</option>
                </select>
                <select id="registration_region_lv3" name="registration_region_lv3" class="_js_bankinfo textbox">
                    <option value="0">=<?php echo L("REGION_LV3");?>=</option>
                </select>
                <select id="registration_region_lv4" name="registration_region_lv4" id="Jcarry_registration_region_lv4" class="_js_bankinfo textbox">
                    <option value="0">=<?php echo L("REGION_LV4");?>=</option>
                </select>
                <input type="text" class="textbox" id="registration_address" name="registration_address" value="<?php echo ($enterpriseBaseInfo["registration_address"]); ?>" style="display:none;" />
        </td>
    </tr>
    <script type="text/javascript">
        function check(str){
            var temp=""
            for(var i=0;i<str.length;i++)
                if(str.charCodeAt(i)>0&&str.charCodeAt(i)<255)
                    temp+=str.charAt(i)
            return temp
        }
        function testStr(str){
            var reg = /^J[0-9]{13}$/
            if (!reg.test(str)){
                alert("企业许可证核准号格式不正确")
                return ''
            } else {
                return str
            }
        }
    </script>
    <script type="text/javascript">
            $(document).ready(function(){
                $("select[name='registration_region_lv1']").bind("change",function(){
                    load_select_registration("1");
                    $("#registration_region_lv1_2").val(0);
                    $("#registration_region_lv1_3").val(0);
                    $("#registration_region_lv1_4").val(0);
                    // 下拉列表onchange的时候，需要把后面的列表清空
                    var devOption2 = $("select[name='registration_region_lv2'] option[value='0']")[0];
                    if (devOption2) {devOption2.selected = true;}
                    var devOption3 = $("select[name='registration_region_lv3'] option[value='0']")[0];
                    if (devOption3) {devOption3.selected = true;load_select_registration('2');}
                    var devOption4 = $("select[name='registration_region_lv4'] option[value='0']")[0];
                    if (devOption4) {devOption4.selected = true;load_select_registration('3');$('#registration_address').hide();$('#registration_address').val('');}
                });
                $("select[name='registration_region_lv2']").bind("change",function(){
                    load_select_registration("2");
                    $("#registration_region_lv1_3").val(0);
                    $("#registration_region_lv1_4").val(0);
                    // 下拉列表onchange的时候，需要把后面的列表清空
                    var devOption3 = $("select[name='registration_region_lv3'] option[value='0']")[0];
                    if (devOption3) {devOption3.selected = true;}
                    var devOption4 = $("select[name='registration_region_lv4'] option[value='0']")[0];
                    if (devOption4) {devOption4.selected = true;load_select_registration('3');$('#registration_address').hide();$('#registration_address').val('');}
                });
                $("select[name='registration_region_lv3']").bind("change",function(){
                    load_select_registration("3");
                    $("#registration_region_lv1_4").val(0);
                    // 下拉列表onchange的时候，需要把后面的列表清空
                    var devOption4 = $("select[name='registration_region_lv4'] option[value='0']")[0];
                    if (devOption4) {devOption4.selected = true;load_select_registration('3');$('#registration_address').hide();$('#registration_address').val('');}
                });
                $("select[name='registration_region_lv4']").bind("change",function(){
                    load_select_registration("4");
                    if ($("#registration_region_lv1_4").val() <= 0) {
                        $('#registration_address').hide();
                        $('#registration_address').val('');
                    }else{
                        $('#registration_address').show();
                    }
                });

                // init region
                var devlv1Option = $("select[name='registration_region_lv1'] option[value='" + $("#registration_region_lv1_1").val() + "']")[0];
                if (devlv1Option) {
                    devlv1Option.selected = true;
                    load_select_registration("1");
                    var devlv2Option = $("select[name='registration_region_lv2'] option[value='" + $("#registration_region_lv1_2").val() + "']")[0];
                    if (devlv2Option) {
                        devlv2Option.selected = true;
                        load_select_registration("2");
                        var devlv3Option = $("select[name='registration_region_lv3'] option[value='" + $("#registration_region_lv1_3").val() + "']")[0];
                        if (devlv3Option) {
                            devlv3Option.selected = true;
                            load_select_registration("3");
                            var devlv4Option = $("select[name='registration_region_lv4'] option[value='" + $("#registration_region_lv1_4").val() + "']")[0];
                            if (devlv4Option) {
                                devlv4Option.selected = true;
                                if ($("#registration_region_lv1_4").val() > 0) {
                                    $("#registration_address").show();
                                }
                            }
                        }
                    }
                }
                change_company_purpose();
            });
            function load_select_registration(lv)
            {
                var name = "registration_region_lv"+lv;
                var next_name = "registration_region_lv"+(parseInt(lv)+1);
                var id = $("select[name='"+name+"']").val();

                if(lv==1)
                var evalStr="regionConf.r"+id+".c";
                if(lv==2)
                var evalStr="regionConf.r"+$("select[name='registration_region_lv1']").val()+".c.r"+id+".c";
                if(lv==3)
                var evalStr="regionConf.r"+$("select[name='registration_region_lv1']").val()+".c.r"+$("select[name='registration_region_lv2']").val()+".c.r"+id+".c";

                if(id==0)
                {
                    var html = "<option value='0'>="+LANG['PLEASE_SELECT']+"=</option>";
                }
                else
                {
                    var regionConfs=eval(evalStr);
                    evalStr+=".";
                    var html = "<option value='0'>="+LANG['PLEASE_SELECT']+"=</option>";
                    for(var key in regionConfs)
                    {
                        html+="<option value='"+eval(evalStr+key+".i")+"'>"+eval(evalStr+key+".n")+"</option>";
                    }
                }
                $("select[name='"+next_name+"']").html(html);
                $("#registration_region_lv1_" + lv).val(id);
            }
    </script>

    <tr>
        <td class="item_title">企业联系地址:</td>
        <td class="item_input">
                <input type="hidden" value="<?php echo ($enterpriseBaseInfo["contract_region_lv1_1"]); ?>" id="contract_region_lv1_1">
                <input type="hidden" value="<?php echo ($enterpriseBaseInfo["contract_region_lv1_2"]); ?>" id="contract_region_lv1_2">
                <input type="hidden" value="<?php echo ($enterpriseBaseInfo["contract_region_lv1_3"]); ?>" id="contract_region_lv1_3">
                <input type="hidden" value="<?php echo ($enterpriseBaseInfo["contract_region_lv1_4"]); ?>" id="contract_region_lv1_4">
                <input type="hidden" id="input_contract_region_name1" name="input_contract_region_name1" value="">
                <input type="hidden" id="input_contract_region_name2" name="input_contract_region_name2" value="">
                <input type="hidden" id="input_contract_region_name3" name="input_contract_region_name3" value="">
                <input type="hidden" id="input_contract_region_name4" name="input_contract_region_name4" value="">
                <select id="contract_region_lv1" name="contract_region_lv1" class="_js_bankinfo textbox">
                    <option value="0">=<?php echo L("REGION_LV1");?>=</option>
                    <?php if(is_array($nRegionLv1)): foreach($nRegionLv1 as $key=>$lv1): ?><option value="<?php echo ($lv1["id"]); ?>" <?php if($enterpriseBaseInfo['contract_region_lv1_1'] == $lv1['id']){echo 'selected';}?>><?php echo ($lv1["name"]); ?></option><?php endforeach; endif; ?>
                </select>
                <select id="contract_region_lv2" name="contract_region_lv2" class="_js_bankinfo textbox">
                    <option value="0">=<?php echo L("REGION_LV2");?>=</option>
                </select>
                <select id="contract_region_lv3" name="contract_region_lv3" class="_js_bankinfo textbox">
                    <option value="0">=<?php echo L("REGION_LV3");?>=</option>
                </select>
                <select id="contract_region_lv4" name="contract_region_lv4" id="Jcarry_contract_region_lv4" class="_js_bankinfo textbox">
                    <option value="0">=<?php echo L("REGION_LV4");?>=</option>
                </select>
                <input type="text" class="textbox" id="contract_address" name="contract_address" value="<?php echo ($enterpriseBaseInfo["contract_address"]); ?>" style="display:none;" />
        </td>
    </tr>
    <script type="text/javascript">
            $(document).ready(function(){
                $("select[name='contract_region_lv1']").bind("change",function(){
                    load_select_contract("1");
                    $("#contract_region_lv1_2").val(0);
                    $("#contract_region_lv1_3").val(0);
                    $("#contract_region_lv1_4").val(0);
                    $("#input_contract_region_name2").val('');
                    $("#input_contract_region_name3").val('');
                    $("#input_contract_region_name4").val('');
                    // 下拉列表onchange的时候，需要把后面的列表清空
                    var devOption2 = $("select[name='contract_region_lv2'] option[value='0']")[0];
                    if (devOption2) {devOption2.selected = true;}
                    var devOption3 = $("select[name='contract_region_lv3'] option[value='0']")[0];
                    if (devOption3) {devOption3.selected = true;load_select_contract('2');}
                    var devOption4 = $("select[name='contract_region_lv4'] option[value='0']")[0];
                    if (devOption4) {devOption4.selected = true;load_select_contract('3');$('#contract_address').hide();$('#contract_address').val('');}
                });
                $("select[name='contract_region_lv2']").bind("change",function(){
                    load_select_contract("2");
                    $("#contract_region_lv1_3").val(0);
                    $("#contract_region_lv1_4").val(0);
                    $("#input_contract_region_name3").val('');
                    $("#input_contract_region_name4").val('');
                    // 下拉列表onchange的时候，需要把后面的列表清空
                    var devOption3 = $("select[name='contract_region_lv3'] option[value='0']")[0];
                    if (devOption3) {devOption3.selected = true;load_select_contract('2');}
                    var devOption4 = $("select[name='contract_region_lv4'] option[value='0']")[0];
                    if (devOption4) {devOption4.selected = true;load_select_contract('3');$('#contract_address').hide();$('#contract_address').val('');}
                });
                $("select[name='contract_region_lv3']").bind("change",function(){
                    load_select_contract("3");
                    $("#contract_region_lv1_4").val(0);
                    $("#input_contract_region_name4").val('');
                    // 下拉列表onchange的时候，需要把后面的列表清空
                    var devOption4 = $("select[name='contract_region_lv4'] option[value='0']")[0];
                    if (devOption4) {devOption4.selected = true;load_select_contract('3');$('#contract_address').hide();$('#contract_address').val('');}
                });
                $("select[name='contract_region_lv4']").bind("change",function(){
                    load_select_contract("4");
                    if ($("#contract_region_lv1_4").val() <= 0) {
                        $('#contract_address').hide();
                        $('#contract_address').val('');
                    }else{
                        $('#contract_address').show();
                    }
                });

                // init region
                var devlv1Option = $("select[name='contract_region_lv1'] option[value='" + $("#contract_region_lv1_1").val() + "']")[0];
                if (devlv1Option) {
                    devlv1Option.selected = true;
                    load_select_contract("1");
                    var devlv2Option = $("select[name='contract_region_lv2'] option[value='" + $("#contract_region_lv1_2").val() + "']")[0];
                    if (devlv2Option) {
                        devlv2Option.selected = true;
                        load_select_contract("2");
                        var devlv3Option = $("select[name='contract_region_lv3'] option[value='" + $("#contract_region_lv1_3").val() + "']")[0];
                        if (devlv3Option) {
                            devlv3Option.selected = true;
                            load_select_contract("3");
                            var devlv4Option = $("select[name='contract_region_lv4'] option[value='" + $("#contract_region_lv1_4").val() + "']")[0];
                            if (devlv4Option) {
                                devlv4Option.selected = true;
                                if ($("#contract_region_lv1_4").val() > 0) {
                                    $("#input_contract_region_name4").val($("select[name='contract_region_lv4']").find("option:selected").text());
                                    $("#contract_address").show();
                                }
                            }
                        }
                    }
                }

                //会员所属网站
                $("#group_id").chosen();
            });
            function load_select_contract(lv)
            {
                var name = "contract_region_lv"+lv;
                var next_name = "contract_region_lv"+(parseInt(lv)+1);
                var id = $("select[name='"+name+"']").val();

                if(lv==1)
                    var evalStr="regionConf.r"+id+".c";
                if(lv==2)
                    var evalStr="regionConf.r"+$("select[name='contract_region_lv1']").val()+".c.r"+id+".c";
                if(lv==3)
                    var evalStr="regionConf.r"+$("select[name='contract_region_lv1']").val()+".c.r"+$("select[name='contract_region_lv2']").val()+".c.r"+id+".c";

                if(id==0)
                {
                    var html = "<option value='0'>="+LANG['PLEASE_SELECT']+"=</option>";
                }
                else
                {
                    var regionConfs=eval(evalStr);
                    evalStr+=".";
                    var html = "<option value='0'>="+LANG['PLEASE_SELECT']+"=</option>";
                    for(var key in regionConfs)
                    {
                        html+="<option value='"+eval(evalStr+key+".i")+"'>"+eval(evalStr+key+".n")+"</option>";
                    }
                }
                $("select[name='"+next_name+"']").html(html);
                $("#contract_region_lv1_" + lv).val(id);
                if (id > 0) {
                    $("#input_contract_region_name" + lv).val($("select[name='"+name+"']").find("option:selected").text());
                }
            }
    </script>
    <tr>
        <td class="item_title">用户简介:</td>
        <td class="item_input">
            <script type='text/javascript'> var eid = 'editor';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='editor' name='info' style='width:580px; height:300px;' ><?php echo ($vo["info"]); ?></textarea> </div>
            <!-- <script id="editor" name="info" type="text/plain" style="width:800px;height:200px; float:left;"><?php echo ($vo["info"]); ?></script> -->
        </td>
    </tr>
    <tr>
        <td class="item_title">备注</td>
        <td class="item_input">
            <textarea name="memo" id="memo" class="txt addarea int_placeholder" placeholder=""  style="width:400px;height:80px;" data-placeholder="" ><?php echo ($enterpriseBaseInfo["memo"]); ?></textarea >
        </td>
    </tr>
    <!-- 基本信息End -->
    <!-- 银行账户Start -->
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>银行账户信息</b></td>
    </tr>
    <tr>
        <td class="item_title">开户名:</td>
        <td class="item_input">
            <input type="text" id="card_name" name="card_name" class="textbox _js_bankinfo" value="<?php echo ($bankcardInfo["card_name"]); ?>" readonly="readonly" />&nbsp;<button id="_js_edit_bankinfo">编辑银行账户</button>
        </td>
    </tr>
    <tr>
        <td class="item_title">银行帐号:</td>
        <td class="item_input">
            <input type="text" id="bankcard" name="bankcard" class="textbox _js_bankinfo" onkeyup="value=value.replace(/[^\d]/,'')" value="<?php echo ($bankcardInfo["bankcard"]); ?>" readonly="readonly" />
            <input type="hidden" id="bankcardHidden" name="bankcardHidden" class="textbox _js_bankinfo" value="<?php echo ($bankcardInfo["bankcard"]); ?>" />
        </td>
    </tr>
    <tr>
        <td class="item_title">银行卡类型:</td>
        <td class="item_input">
            <?php if($bankcardInfo['card_type'] == '' OR $bankcardInfo['card_type'] == 1): ?>公司账户
            <?php elseif($bankcardInfo['card_type'] != '' AND $bankcardInfo['card_type'] == 0): ?>个人账户<?php endif; ?>
        </td>
    </tr>

    <tr>
        <td class="item_title">开户行名称:</td>
        <td class="item_input">
            <select id="bank_id" name="bank_id" class="_js_bankinfo textbox" style="width:180px;" disabled="disabled">
                <option value="0">=<?php echo L("PLEASE_SELECT");?>=</option>
                <?php if(is_array($bankList)): foreach($bankList as $key=>$item): ?><option value="<?php echo ($item["id"]); ?>|<?php echo ($item["short_name"]); ?>|<?php echo ($item["name"]); ?>" <?php if($bankcardInfo['bank_id'] == $item['id']){echo 'selected';$bank_id_value=$item['id'].'|'.$item['short_name'];$bank_short_name=$item['short_name'];}?>><?php echo ($item["name"]); ?></option><?php endforeach; endif; ?>
            </select>
            <input type="hidden" id="bank_id_value" name="bank_id_value" value="<?php echo ($bank_id_value); ?>">
        </td>
    </tr>
    <tr>
        <td class="item_title">开户行简码:</td>
        <td class="item_input">
            <input type="text" id="bank_shortno" name="bank_shortno" class="textbox _js_bankinfo" value="<?php echo ($bank_short_name); ?>" readonly="readonly" />
        </td>
    </tr>

    <tr>
        <td class="item_title">开户行所在地:</td>
        <td class="item_input">
                <input type="hidden" id="bank_region_input_lv1" name="bank_region_input_lv1" value="<?php echo ($bankcardInfo["region_lv1"]); ?>">
                <input type="hidden" id="bank_region_input_lv2" name="bank_region_input_lv2" value="<?php echo ($bankcardInfo["region_lv2"]); ?>">
                <input type="hidden" id="bank_region_input_lv3" name="bank_region_input_lv3" value="<?php echo ($bankcardInfo["region_lv3"]); ?>">
                <input type="hidden" id="bank_region_input_lv4" name="bank_region_input_lv4" value="<?php echo ($bankcardInfo["region_lv4"]); ?>">
                <select id="bank_region_lv1" name="bank_region_lv1" class="_js_bankinfo textbox" disabled="disabled">
                    <option value="0">=<?php echo L("REGION_LV1");?>=</option>
                    <?php if(is_array($nRegionLv1)): foreach($nRegionLv1 as $key=>$lv1): ?><option value="<?php echo ($lv1["id"]); ?>" <?php if($bankcardInfo['region_lv1'] == $lv1['id']){echo 'selected';}?>><?php echo ($lv1["name"]); ?></option><?php endforeach; endif; ?>
                </select>
                <select id="bank_region_lv2" name="bank_region_lv2" class="_js_bankinfo textbox" disabled="disabled">
                    <option value="0">=<?php echo L("REGION_LV2");?>=</option>
                </select>
                <select id="bank_region_lv3" name="bank_region_lv3" class="_js_bankinfo textbox" disabled="disabled">
                    <option value="0">=<?php echo L("REGION_LV3");?>=</option>
                </select>
                <select id="bank_region_lv4" name="bank_region_lv4" id="Jcarry_region_lv4" class="_js_bankinfo textbox" disabled="disabled">
                    <option value="0">=<?php echo L("REGION_LV4");?>=</option>
                </select>
        </td>
    </tr>
    <script type="text/javascript">
         $(document).ready(function(){
             $("select[name='bank_region_lv1']").bind("change",function(){
                 load_select_bank("1");
                 clear_bank_site();
             });
             $("select[name='bank_region_lv2']").bind("change",function(){
                 load_select_bank("2");
                 clear_bank_site();
             });
             $("select[name='bank_region_lv3']").bind("change",function(){
                 load_select_bank("3");
                 clear_bank_site();
                 bank_site_bank();
             });
             $("select[name='bank_region_lv4']").bind("change",function(){
                 load_select_bank("4");
             });

             // init region
             var devlv1Option = $("select[name='bank_region_lv1'] option[value='" + $("#bank_region_input_lv1").val() + "']")[0];
             if (devlv1Option) {
                 devlv1Option.selected = true;
                 load_select_bank("1");
                 var devlv2Option = $("select[name='bank_region_lv2'] option[value='" + $("#bank_region_input_lv2").val() + "']")[0];
                 if (devlv2Option) {
                     devlv2Option.selected = true;
                     load_select_bank("2");
                     var devlv3Option = $("select[name='bank_region_lv3'] option[value='" + $("#bank_region_input_lv3").val() + "']")[0];
                     if (devlv3Option) {
                         devlv3Option.selected = true;
                         load_select_bank("3");
                         bank_site_bank();
                         var devlv4Option = $("select[name='bank_region_lv4'] option[value='" + $("#bank_region_input_lv4").val() + "']")[0];
                         if (devlv4Option) {
                             devlv4Option.selected = true;
                         }
                     }
                 }
             }
         });
         function load_select_bank(lv)
         {
             var name = "bank_region_lv"+lv;
             var next_name = "bank_region_lv"+(parseInt(lv)+1);
             var id = $("select[name='"+name+"']").val();

             if(lv==1) {
                 var evalStr="regionConf.r"+id+".c";
             }
             if(lv==2) {
                 var evalStr="regionConf.r"+$("select[name='bank_region_lv1']").val()+".c.r"+id+".c";
             }
             if(lv==3) {
                 var evalStr="regionConf.r"+$("select[name='bank_region_lv1']").val()+".c.r"+$("select[name='bank_region_lv2']").val()+".c.r"+id+".c";
             }

             if(id==0)
             {
                 var html = "<option value='0'>="+LANG['PLEASE_SELECT']+"=</option>";
             }
             else
             {
                 var regionConfs=eval(evalStr);
                 evalStr+=".";
                 var html = "<option value='0'>="+LANG['PLEASE_SELECT']+"=</option>";
                 for(var key in regionConfs)
                 {
                     html+="<option value='"+eval(evalStr+key+".i")+"'>"+eval(evalStr+key+".n")+"</option>";
                 }
             }
             $("select[name='"+next_name+"']").html(html);
         }
     </script>
    <tr>
        <td class="item_title">开户网点:</td>
        <td id="_js_bank_site">
            <input type="text" class="textbox _js_bankinfo" id="bank_bankzone" name="bank_bankzone" value="<?php echo ($bankcardInfo["bankzone"]); ?>" readonly="readonly" />
        </td>
    </tr>
    <tr>
        <td class="item_title">联行号码:</td>
        <td class="item_input" id="_js_bank_no" >
            <input type="text" id="branch_no" name="branch_no" class="textbox _js_bankinfo" value="<?php echo ($bankcardInfo["branch_no"]); ?>" readonly="readonly" />
        </td>
    </tr>
    <tr>
        <td class="item_title">验证状态</td>
        <td class="item_input">
            <input type="hidden" id="bankzone_value" name="bankzone_value" value="<?php echo ($bankcardInfo["status"]); ?>" />
            <select class="textbox" id="bankzone" name="bankzone" disabled="disabled">
                <option value="1" <?php if($bankcardInfo['status'] == 1){echo 'selected';}?>>是</option>
                <option value="0" <?php if($bankcardInfo['status'] == 0){echo 'selected';}?>>否</option>
            </select>
        </td>
    </tr>
    <!-- 银行账户End -->
    <!-- 支付账户Start -->
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>支付账户信息</b></td>
    </tr>
    <tr>
        <td class="item_title">支付账户ID:</td>
        <td class="item_input"><?php echo ($paymentStatusMsg); ?></td>
    </tr>
    <!-- 支付账户End -->
    <!-- 联系人Start -->
    <tr>
        <td colspan="2" class="item_title" style="text-align:center;"><b>联系人信息</b></td>
    </tr>
    <tr>
        <td class="item_title">代理人姓名:</td>
        <td class="item_input"><input type="text" class="textbox require" id="major_name" name="major_name" value="<?php echo ($enterpriseContactInfo["major_name"]); ?>" /><font color='red'>*</font></td>
    </tr>
    <tr>
        <td class="item_title" style="width:170px;">代理人证件类别:</td>
        <td class="item_input">
        <select id="major_condentials_type" name="major_condentials_type" class="textbox require" style="width:180px;">
        <?php if(is_array($idTypes)): foreach($idTypes as $key=>$type): ?><option value="<?php echo ($key); ?>" <?php if($enterpriseContactInfo['major_condentials_type'] == $key){echo 'selected';}?>><?php echo ($type); ?></option><?php endforeach; endif; ?>
        </select>
        <font color='red'>*</font>
        </td>
    </tr>
    <tr>
        <td class="item_title">代理人人证件号码:</td>
        <td class="item_input"><input type="text" class="textbox require" id="major_condentials_no" name="major_condentials_no" value="<?php echo ($enterpriseContactInfo["major_condentials_no"]); ?>" onkeyup="value=value.replace(/[\W]/g,'')" onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^\d]/g,''))" /><font color='red'>*</font></td>
    </tr>
    <tr>
        <td class="item_title">代理人手机号码:</td>
        <td class="item_input">
            <select name="major_mobile_code" id="major_mobile_code" class="require textbox">
                <?php if(is_array($mobileCodeList)): foreach($mobileCodeList as $key=>$mobile_code_item): ?><option value="<?php echo ($mobile_code_item["country"]); ?>|<?php echo ($mobile_code_item["code"]); ?>" <?php if($enterpriseContactInfo['major_mobile_code'] == $mobile_code_item['code']){echo 'selected';}?>><?php echo ($mobile_code_item["name"]); ?> <?php echo ($mobile_code_item["code"]); ?></option><?php endforeach; endif; ?>
            </select>
         - <input type="text" class="textbox require" id="major_mobile" name="major_mobile" value="<?php echo ($enterpriseContactInfo["major_mobile"]); ?>" onkeyup="value=value.replace(/[^\d\.]/g,'')" style="ime-mode:Disabled" maxlength="11" /><font color='red'>*</font></td>
    </tr>
    <tr>
        <td class="item_title">接收邮件地址:</td>
        <td class="item_input"><input type="text" class="textbox require" id="major_email" name="major_email" value="<?php echo ($enterpriseContactInfo["major_email"]); ?>" /><font color='red'>*</font></td>
    </tr>
    <tr>
        <td class="item_title">企业账户负责人联系地址:</td>
        <td class="item_input">
                <input type="hidden" value="<?php echo ($enterpriseContactInfo["major_contract_region_lv1_1"]); ?>" id="major_contract_region_lv1_1">
                <input type="hidden" value="<?php echo ($enterpriseContactInfo["major_contract_region_lv1_2"]); ?>" id="major_contract_region_lv1_2">
                <input type="hidden" value="<?php echo ($enterpriseContactInfo["major_contract_region_lv1_3"]); ?>" id="major_contract_region_lv1_3">
                <input type="hidden" value="<?php echo ($enterpriseContactInfo["major_contract_region_lv1_4"]); ?>" id="major_contract_region_lv1_4">
                <select id="major_contract_region_lv1" name="major_contract_region_lv1" class="_js_bankinfo textbox">
                    <option value="0">=<?php echo L("REGION_LV1");?>=</option>
                    <?php if(is_array($nRegionLv1)): foreach($nRegionLv1 as $key=>$lv1): ?><option value="<?php echo ($lv1["id"]); ?>" <?php if($enterpriseContactInfo['major_contract_region_lv1_1'] == $lv1['id']){echo 'selected';}?>><?php echo ($lv1["name"]); ?></option><?php endforeach; endif; ?>
                </select>
                <select id="major_contract_region_lv2" name="major_contract_region_lv2" class="_js_bankinfo textbox">
                    <option value="0">=<?php echo L("REGION_LV2");?>=</option>
                </select>
                <select id="major_contract_region_lv3" name="major_contract_region_lv3" class="_js_bankinfo textbox">
                    <option value="0">=<?php echo L("REGION_LV3");?>=</option>
                </select>
                <select id="major_contract_region_lv4" name="major_contract_region_lv4" id="Jcarry_major_contract_region_lv4" class="_js_bankinfo textbox">
                    <option value="0">=<?php echo L("REGION_LV4");?>=</option>
                </select>
                <input type="text" class="textbox" id="major_contract_address" name="major_contract_address" value="<?php echo ($enterpriseContactInfo["major_contract_address"]); ?>" style="display:none;" />
        </td>
    </tr>
    <script type="text/javascript">
            $(document).ready(function(){
                $("select[name='major_contract_region_lv1']").bind("change",function(){
                    load_select_major_contract("1");
                    $("#major_contract_region_lv1_2").val(0);
                    $("#major_contract_region_lv1_3").val(0);
                    $("#major_contract_region_lv1_4").val(0);
                    // 下拉列表onchange的时候，需要把后面的列表清空
                    var devOption2 = $("select[name='major_contract_region_lv2'] option[value='0']")[0];
                    if (devOption2) {devOption2.selected = true;}
                    var devOption3 = $("select[name='major_contract_region_lv3'] option[value='0']")[0];
                    if (devOption3) {devOption3.selected = true;load_select_major_contract('2');}
                    var devOption4 = $("select[name='major_contract_region_lv4'] option[value='0']")[0];
                    if (devOption4) {devOption4.selected = true;load_select_major_contract('3');$('#major_contract_address').hide();$('#major_contract_address').val('');}
                });
                $("select[name='major_contract_region_lv2']").bind("change",function(){
                    load_select_major_contract("2");
                    $("#major_contract_region_lv1_3").val(0);
                    $("#major_contract_region_lv1_4").val(0);
                    // 下拉列表onchange的时候，需要把后面的列表清空
                    var devOption3 = $("select[name='major_contract_region_lv3'] option[value='0']")[0];
                    if (devOption3) {devOption3.selected = true;load_select_major_contract('2');}
                    var devOption4 = $("select[name='major_contract_region_lv4'] option[value='0']")[0];
                    if (devOption4) {devOption4.selected = true;load_select_major_contract('3');$('#major_contract_address').hide();$('#major_contract_address').val('');}
                });
                $("select[name='major_contract_region_lv3']").bind("change",function(){
                    load_select_major_contract("3");
                    $("#major_contract_region_lv1_4").val(0);
                    // 下拉列表onchange的时候，需要把后面的列表清空
                    var devOption4 = $("select[name='major_contract_region_lv4'] option[value='0']")[0];
                    if (devOption4) {devOption4.selected = true;load_select_major_contract('3');$('#major_contract_address').hide();$('#major_contract_address').val('');}
                });
                $("select[name='major_contract_region_lv4']").bind("change",function(){
                    load_select_major_contract("4");
                    if ($("#major_contract_region_lv1_4").val() <= 0) {
                        $('#major_contract_address').hide();
                        $('#major_contract_address').val('');
                    }else{
                        $('#major_contract_address').show();
                    }
                });

                // init region
                var devlv1Option = $("select[name='major_contract_region_lv1'] option[value='" + $("#major_contract_region_lv1_1").val() + "']")[0];
                if (devlv1Option) {
                    devlv1Option.selected = true;
                    load_select_major_contract("1");
                    var devlv2Option = $("select[name='major_contract_region_lv2'] option[value='" + $("#major_contract_region_lv1_2").val() + "']")[0];
                    if (devlv2Option) {
                        devlv2Option.selected = true;
                        load_select_major_contract("2");
                        var devlv3Option = $("select[name='major_contract_region_lv3'] option[value='" + $("#major_contract_region_lv1_3").val() + "']")[0];
                        if (devlv3Option) {
                            devlv3Option.selected = true;
                            load_select_major_contract("3");
                            var devlv4Option = $("select[name='major_contract_region_lv4'] option[value='" + $("#major_contract_region_lv1_4").val() + "']")[0];
                            if (devlv4Option) {
                                devlv4Option.selected = true;
                                if ($("#major_contract_region_lv1_4").val() > 0) {
                                    $("#major_contract_address").show();
                                }
                            }
                        }
                    }
                }
            });
            function load_select_major_contract(lv)
            {
                var name = "major_contract_region_lv"+lv;
                var next_name = "major_contract_region_lv"+(parseInt(lv)+1);
                var id = $("select[name='"+name+"']").val();

                if(lv==1)
                var evalStr="regionConf.r"+id+".c";
                if(lv==2)
                var evalStr="regionConf.r"+$("select[name='major_contract_region_lv1']").val()+".c.r"+id+".c";
                if(lv==3)
                var evalStr="regionConf.r"+$("select[name='major_contract_region_lv1']").val()+".c.r"+$("select[name='major_contract_region_lv2']").val()+".c.r"+id+".c";

                if(id==0)
                {
                    var html = "<option value='0'>="+LANG['PLEASE_SELECT']+"=</option>";
                }
                else
                {
                    var regionConfs=eval(evalStr);
                    evalStr+=".";
                    var html = "<option value='0'>="+LANG['PLEASE_SELECT']+"=</option>";
                    for(var key in regionConfs)
                    {
                        html+="<option value='"+eval(evalStr+key+".i")+"'>"+eval(evalStr+key+".n")+"</option>";
                    }
                }
                $("select[name='"+next_name+"']").html(html);
                $("#major_contract_region_lv1_" + lv).val(id);
            }
    </script>
    <tr>
        <td class="item_title">企业联系人2姓名:</td>
        <td class="item_input"><input type="text" class="textbox" id="contract_name" name="contract_name" value="<?php echo ($enterpriseContactInfo["contract_name"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">企业联系人2手机号码:</td>
        <td class="item_input">
            <select name="contract_mobile_code" id="contract_mobile_code" class="textbox">
                <?php if(is_array($mobileCodeList)): foreach($mobileCodeList as $key=>$mobile_code_item): ?><option value="<?php echo ($mobile_code_item["country"]); ?>|<?php echo ($mobile_code_item["code"]); ?>" <?php if($enterpriseContactInfo['contract_mobile_code'] == $mobile_code_item['code']){echo 'selected';}?>><?php echo ($mobile_code_item["name"]); ?> <?php echo ($mobile_code_item["code"]); ?></option><?php endforeach; endif; ?>
            </select>
          - <input type="text" class="textbox" id="contract_mobile" name="contract_mobile" value="<?php echo ($enterpriseContactInfo["contract_mobile"]); ?>" onkeyup="value=value.replace(/[^\d-\.]/g,'')" style="ime-mode:Disabled" maxlength="13" /></td>
    </tr>
    <tr>
        <td class="item_title">企业联络人号码:</td>
        <td class="item_input">
            <select name="consignee_phone_code" id="consignee_phone_code" class="textbox">
                <?php if(is_array($mobileCodeList)): foreach($mobileCodeList as $key=>$mobile_code_item): ?><option value="<?php echo ($mobile_code_item["country"]); ?>|<?php echo ($mobile_code_item["code"]); ?>" <?php if($enterpriseContactInfo['consignee_phone_code'] == $mobile_code_item['country']){echo 'selected';}?>><?php echo ($mobile_code_item["name"]); ?> <?php echo ($mobile_code_item["code"]); ?></option><?php endforeach; endif; ?>
            </select>
         - <input type="text" class="textbox" id="consignee_phone" name="consignee_phone" value="<?php echo ($enterpriseContactInfo["consignee_phone"]); ?>" onkeyup="value=value.replace(/[^\d\.]/g,'')" style="ime-mode:Disabled" maxlength="11" /></td>
    </tr>
    <tr>
        <td class="item_title">业务接洽联系号码:</td>
        <td class="item_input">
            <select name="consignee_phone_code" id="consignee_phone_code1" class="textbox">
                <?php if(is_array($mobileCodeList)): foreach($mobileCodeList as $key=>$mobile_code_item): ?><option value="<?php echo ($mobile_code_item["country"]); ?>|<?php echo ($mobile_code_item["code"]); ?>" <?php if($enterpriseContactInfo['consignee_phone_code'] == $mobile_code_item['country']){echo 'selected';}?>><?php echo ($mobile_code_item["name"]); ?> <?php echo ($mobile_code_item["code"]); ?></option><?php endforeach; endif; ?>
            </select>
            - <input type="text" class="textbox" id="consignee_phone1" name="consignee_phone1" value="<?php echo ($enterpriseContactInfo["consignee_phone"]); ?>" onkeyup="value=value.replace(/[^\d\.]/g,'')" style="ime-mode:Disabled" maxlength="11" /></td>
    </tr>
    <tr>
        <td class="item_title">接收短信通知号码:</td>
        <td class="item_input"><input type="text" class="textbox require" id="receive_msg_mobile" name="receive_msg_mobile" value="<?php echo ($enterpriseContactInfo["receive_msg_mobile"]); ?>" /><font color='red'>*</font></td>
    </tr>
    <tr>
        <td class="item_title">邀请人姓名:</td>
        <td class="item_input"><input type="text" class="textbox" id="inviter_name" name="inviter_name" value="<?php echo ($enterpriseContactInfo["inviter_name"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">邀请人手机号码:</td>
        <td class="item_input">
            <select name="inviter_country_code" id="inviter_country_code" class="textbox">
                <?php if(is_array($mobileCodeList)): foreach($mobileCodeList as $key=>$mobile_code_item): ?><option value="<?php echo ($mobile_code_item["country"]); ?>|<?php echo ($mobile_code_item["code"]); ?>" <?php if($enterpriseContactInfo['inviter_country_code'] == $mobile_code_item['country']){echo 'selected';}?>><?php echo ($mobile_code_item["name"]); ?> <?php echo ($mobile_code_item["code"]); ?></option><?php endforeach; endif; ?>
            </select>
         - <input type="text" class="textbox" id="inviter_phone" name="inviter_phone" value="<?php echo ($enterpriseContactInfo["inviter_phone"]); ?>" onkeyup="value=value.replace(/[^\d\.]/g,'')" style="ime-mode:Disabled" maxlength="11" /></td>
    </tr>
    <tr>
        <td class="item_title">邀请人所在机构:</td>
        <td class="item_input"><input type="text" class="textbox" name="inviter_organization" value="<?php echo ($enterpriseContactInfo["inviter_organization"]); ?>" id="inviter_organization"/></td>
    </tr>
    <tr>
        <td class="item_title">经办人姓名:</td>
        <td class="item_input"><input type="text" class="textbox" id="employee_name" name="employee_name" value="<?php echo ($enterpriseContactInfo["employee_name"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">经办人手机号码:</td>
        <td class="item_input">
            <select name="employee_mobile_code" id="employee_mobile_code" class="textbox">
                <?php if(is_array($mobileCodeList)): foreach($mobileCodeList as $key=>$mobile_code_item): ?><option value="<?php echo ($mobile_code_item["country"]); ?>|<?php echo ($mobile_code_item["code"]); ?>" <?php if($enterpriseContactInfo['employee_mobile_code'] == $mobile_code_item['code']){echo 'selected';}?>><?php echo ($mobile_code_item["name"]); ?> <?php echo ($mobile_code_item["code"]); ?></option><?php endforeach; endif; ?>
            </select>
         - <input type="text" class="textbox" id="employee_mobile" name="employee_mobile" value="<?php echo ($enterpriseContactInfo["employee_mobile"]); ?>" onkeyup="value=value.replace(/[^\d\.]/g,'')" style="ime-mode:Disabled" maxlength="11" /></td>
    </tr>
    <tr>
        <td class="item_title">经办人所属机构:</td>
        <td class="item_input"><input type="text" class="textbox" id="employee_department" name="employee_department" value="<?php echo ($enterpriseContactInfo["employee_department"]); ?>" /></td>
    </tr>
    <!-- 联系人End -->
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
            <!--隐藏元素-->
            <input type="hidden" id="id" name="id" value="<?php echo ($vo["id"]); ?>" />
            <input type="hidden" id='idcardpassed' name="idcardpassed" value="<?php echo ($vo["idcardpassed"]); ?>" />
            <input type="hidden" id='bankcard_id' name="bankcard_id" value="<?php echo ($bankcardInfo["id"]); ?>" />
            <input type="hidden" id="<?php echo conf("VAR_MODULE");?>" name="<?php echo conf("VAR_MODULE");?>" value="Enterprise" />
            <input type="hidden" id="<?php echo conf("VAR_ACTION");?>" name="<?php echo conf("VAR_ACTION");?>" value="update" />
            <!--隐藏元素-->
            <input type="button" class="button" id="submitBtn" value="<?php echo L("EDIT");?>" onclick="checkParams();" />&nbsp;&nbsp;
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
    //实例化编辑器
    //UE.getEditor('editor');
    jQuery(function(){
        setTimeout("bank_site_bank();",1000);
        checkPermanent();
        //重置银行卡信息
        $("#_js_edit_bankinfo").click(function(){
            if(confirm("您确定要编辑银行账户信息？")){
                edit_bank_account();
            }
            return false;
        });
        //重置银行卡信息
        $("#_js_reset_bankinfo").click(function(){
            var id = $("#_js_bankinfo_id").val();
            var uid = $("#_js_bankinfo_uid").val();

            if(id == 0){
                alert("没有银行信息！");
                return false;
            }
            if(confirm("确认要重置银行卡信息？此操作不可恢复!!!!")){
                $("._js_bankinfo").val("");
                if(id>0 && uid>0){
                    //
                    $.ajax({
                          type: "POST",
                          url: ROOT+'?m=User&a=resetbank',
                          data: "id="+id+"&uid="+uid,
                          dataType:"json",
                          success: function(msg){
                              if(msg.code == '0000') {
                                   $('#status').val(0);
                                   $('#status_tips').text('未验证');
                                   //$('#bankcard_id').val('');
                              }else{
                                  alert(msg.msg);
                              }
                          }
                       });
                }
            }
            return false;
        });
        //银行网点
        $("select[name='bank_id']").bind("change",function(){
            clear_bank_site();
            bank_site_bank();
            $("#bank_shortno").val('');
            var bankIdString = $("select[name='bank_id']").find("option:selected").val();
            if (bankIdString !== undefined) {
                var bankIdArray = bankIdString.split("|");
                if (bankIdArray[1] !== undefined && bankIdArray[1] != '') {
                    $("#bank_shortno").val(bankIdArray[1]);
                }
            }
        });
//        //法定代表人手机号码-前缀
//        $("#legalbody_mobile_code").live("change",function(){
//            getReceiveMobileCode();
//        });
//        //法定代表人手机号码
//        $("#legalbody_mobile").live("change",function(){
//            getReceiveMobileCode();
//        });
        //企业账户负责人手机号码-前缀
        $("#major_mobile_code").live("change",function(){
            getReceiveMobileCode();
        });
        //企业账户负责人手机号码
        $("#major_mobile").live("change",function(){
            getReceiveMobileCode();
        });
        //企业联系方式-前缀
        $("#contract_mobile_code1").live("change",function(){
            getContractMobile();
        });
        //企业联系方式
        $("#contract_mobile1").live("change",function(){
            getContractMobile();
        });
    });
    // 整理接收短信通知号码
    function getReceiveMobileCode() {
        var receiveMobileFullValue = '';
        //var receiveMobileFullValue = legalbodyMobileCodeFullValue = majorMobileCodeFullValue = '';
//        receiveMobileFullValue = $("#receive_msg_mobile").val();
//        var legalbodyMobileValue = $("#legalbody_mobile").val();
//        if (legalbodyMobileValue.length > 0) {
//            var legalbodyMobileCodeArray = $("#legalbody_mobile_code").val().split("|");
//            if (legalbodyMobileCodeArray[1] !== undefined && legalbodyMobileCodeArray[1] > 0) {
//                var legalbodyMobileCode = legalbodyMobileCodeArray[1] + '-';
//            }else{
//                var legalbodyMobileCode = '86-';
//            }
//            var legalbodyMobileCodeFullValue = legalbodyMobileCode + legalbodyMobileValue;
//        }
//        receiveMobileFullValue = receiveMobileFullValue.length > 0 ? (receiveMobileFullValue + ',' + legalbodyMobileCodeFullValue) : legalbodyMobileCodeFullValue;
        var majorMobileValue = $("#major_mobile").val();
        if (majorMobileValue.length > 0) {
            var majorMobileCodeArray = $("#major_mobile_code").val().split("|");
            if (majorMobileCodeArray[1] !== undefined && majorMobileCodeArray[1] > 0) {
                var majorMobileCode = majorMobileCodeArray[1] + '-';
            }else{
                var majorMobileCode = '86-';
            }
            var majorMobileCodeFullValue = majorMobileCode + majorMobileValue;
        }
        //receiveMobileFullValue = receiveMobileFullValue.length > 0 ? (receiveMobileFullValue + (majorMobileCodeFullValue.length > 0 ? ',' + majorMobileCodeFullValue : '')) : majorMobileCodeFullValue;
        receiveMobileFullValue = majorMobileCodeFullValue;
        $("#receive_msg_mobile").val(receiveMobileFullValue);
    }
    function getContractMobile(){
        var contractMobileCode = contractMobilePhone = '';
        contractMobileCode = $("#contract_mobile_code1").val();
        contractMobilePhone = $("#contract_mobile1").val();
        $("#contract_mobile_code").val(contractMobileCode);
        $("#contract_mobile").val(contractMobilePhone);
    }
    function onlyNum() {
        if(!(event.keyCode==46)&&!(event.keyCode==8)&&!(event.keyCode==37)&&!(event.keyCode==39))
        if(!((event.keyCode>=48&&event.keyCode<=57)||(event.keyCode>=96&&event.keyCode<=105)))
        event.returnValue=false;
    }
    //银行开户网点
    function bank_site_bank(){
      var c = encodeURIComponent($("select[name='bank_region_lv3']").find("option:selected").text());
      var p = encodeURIComponent($("select[name='bank_region_lv2']").find("option:selected").text());
      var b = encodeURIComponent($("select[name='bank_id']").find("option:selected").text());
      var n = '';
      var data = {c:c,p:p,n:n};

      $.get("/m.php?m=Enterprise&a=getBankListHtml&c="+c+"&p="+p+"&n="+n+"&b="+b+"&jsonpCallback=?",function(rs){
          var rsobj = eval( "(" + rs +  ")");
          //$("#_js_bank_site").html(rsobj.bankListHtml);
          var bankString = $("select[name='bank_bankzone']").find("option:selected").val();
          if (bankString !== undefined) {
              $("#branch_no").show();
              var bankArray = bankString.split("|");
              if (bankArray[0] !== undefined && bankArray[0] > 0) {
                  $("#branch_no").val(bankArray[0]);
              }
          }
      });
    }

    //清空银行开户网点
    function clear_bank_site(){
        //$("#_js_bank_site").html('');
        $("#branch_no").val('');
    }

    function changeLevelSelect(){
        var url = "/m.php?m=UserCouponLevel&a=get_level_select";
        var current_coupon_level_id = '<?php echo ($vo["new_coupon_level_id"]); ?>';
        $.getJSON(url,{group_id:$("#group_id").val()},function(json){
            var coupon_level_id = $("#new_coupon_level_id");
            $("option",coupon_level_id).remove(); //清空原有的选项
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

    function edit_bank_account(){
        $.weeboxs.open(ROOT+'?m=Enterprise&a=editBankAccount&s=edit&uid=<?php echo ($userId); ?>', {contentType:'ajax',showButton:false,title:'编辑银行账户',modal:true,overlay:5,width:600,height:500,onopen: function(){}});
    }
    function checkPermanent(){
        if ($('#is_permanent').attr("checked") == true){
            $('#credentials_expire_at').attr("disabled","disabled");
            $('#btn_credentials_expire_at').attr("disabled","disabled");
        }else{
            $('#credentials_expire_at').removeAttr("disabled");
            $('#btn_credentials_expire_at').removeAttr("disabled");
        }
    }
    function checkParams() {
        var btn = $('#submitBtn');
        var $input = $('#hkop');
        $(btn).css({"color":"gray","background":"#ccc"}).attr("disabled","disabled");

        var doms = $(".require");
        var check_ok = true;
        $.each(doms,function(i, dom){
            if($.trim($(dom).val()) == '')
            {
                var title = $(dom).parent().parent().find(".item_title").html();
                if(!title)
                {
                    title = '';
                }
                if(title.substr(title.length-1,title.length)==':')
                {
                    title = title.substr(0,title.length-1);
                }
                if($(dom).val()=='')
                TIP = LANG['PLEASE_FILL'];
                if($(dom).val()=='0')
                TIP = LANG['PLEASE_SELECT'];
                alert(TIP+title);
                $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
                $(dom).focus();
                check_ok = false;
                return false;
            }
        });
        if (!check_ok)
            return false;

        //用户密码、确认密码
        /*if ($("#user_pwd").val() != $("#user_confirm_pwd").val()) {
            alert("密码与确认密码不一致!");
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            $("#user_pwd").focus();
            return false;
        }*/
        //企业全称
        var regPattern = /^[\u4E00-\u9FA5\（\）]+$/;
        var regResult = regPattern.test($("#company_name").val());
        if (regResult == false)
        {
            alert("企业全称只允许输入中文跟中文括号!");
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            $("#company_name").focus();
            return false;
        }
        //企业简称
        var regResult = regPattern.test($("#company_shortname").val());
        if (regResult == false)
        {
            alert("企业简称只允许输入中文跟中文括号!");
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            $("#company_shortname").focus();
            return false;
        }

        var isTradecenter = false;
        if($("#company_purpose").find("option:selected").val() == 10) {
            isTradecenter = true;
        }

        //法定代表人证件号码
        var legalbody_credentials_no = $("#legalbody_credentials_no").val();
        if (!isTradecenter && (legalbody_credentials_no == '' || legalbody_credentials_no == 'undefined')) {
            alert("法定代表人证件号码不能为空!");
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            $("#legalbody_credentials_no").focus();
            return false;
        }
        //企业证件有效期，是否长期有效
        var is_permanent = 0;
        if ($('#is_permanent').attr("checked") == true){
            is_permanent = $("#is_permanent").val();
        }
        var cardreg = /(^\d{15}$)|(^\d{17}([0-9]|X)$)/;
        var legalbody_credentials_type = $("#legalbody_credentials_type").val();
        if (!isTradecenter && legalbody_credentials_type == 1) {
            if (!cardreg.test(legalbody_credentials_no)) {
                alert("请输入有效的法定代表人身份证件号码！");
                $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
                $("#legalbody_credentials_no").focus();
                return false;
            }
        }
        // 企业开户许可证核准号
        if (!isTradecenter && !testStr($("#app_no").val())) {
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            $("#app_no"),focus();
            return false;
        }
        //法定代表人手机号码
        var myreg = /^((13[0-9]|15[0-9]|17[0-9]|18[0-9]|14[57])[0-9]{8})$/;
        var legalbody_mobile = $("#legalbody_mobile").val();
        if (!isTradecenter && (legalbody_mobile!= '' && !myreg.test($("#legalbody_mobile").val())))
        {
            alert("请输入有效的法定代表人手机号码!");
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            $("#legalbody_mobile").focus();
            return false;
        }
        //企业账户负责人姓名
        var regPatternMajor = /^[\u4E00-\u9FA5]+$/;
        var regResult = regPatternMajor.test($("#major_name").val());
        if (!isTradecenter && regResult == false)
        {
            alert("代理人姓名只允许输入中文!");
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            $("#major_name").focus();
            return false;
        }
        //企业账户负责人证件号码
        var major_condentials_no = $("#major_condentials_no").val();
        if (!isTradecenter && (major_condentials_no == '' || major_condentials_no == 'undefined')) {
            alert("代理人证件号码不能为空!");
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            $("#major_condentials_no").focus();
            return false;
        }
        var major_condentials_type = $("#major_condentials_type").val();
        if (!isTradecenter && major_condentials_type == 1) {
            if (!cardreg.test(major_condentials_no)) {
                alert("请输入有效的代理人身份证件号码！");
                $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
                $("#major_condentials_no").focus();
                return false;
            }
        }
        //企业账户负责人手机号码
        if (!isTradecenter && !myreg.test($("#major_mobile").val()))
        {
            alert("请输入有效的代理人手机号码!");
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            $("#major_mobile").focus();
            return false;
        }

        error = checkRiskInfo();
        if (confirm(error+"确定要进行此操作吗？")) {
             //document.getElementById('addForm').submit();
            $.post("/m.php?m=Enterprise&a=update", {
                'id':$("#id").val(),
                'idcardpassed':$("#idcardpassed").val(),
                'bankcard_id':$("#bankcard_id").val(),
                'company_purpose':$("#company_purpose").val(),
                'privilege_note':$("#privilege_note").val(),
                'identifier':$("#identifier").val(),
                'user_name':$("#user_name").val(),
                'is_effect':$("#is_effect").val(),
                'group_id':$("#group_id").val(),
                'new_coupon_level_id':$("#new_coupon_level_id").val(),
                'channel_pay_factor':$("#channel_pay_factor").val(),
                'company_name':$("#company_name").val(),
                'company_shortname':$("#company_shortname").val(),
                'credentials_type':$("#credentials_type").val(),
                'credentials_no':$("#credentials_no").val(),
                'credentials_expire_date':$("#credentials_expire_date").val(),
                'credentials_expire_at':$("#credentials_expire_at").val(),
                'is_permanent':is_permanent,
                'legalbody_name':$("#legalbody_name").val(),
                'legalbody_credentials_type':$("#legalbody_credentials_type").val(),
                'legalbody_credentials_no':$("#legalbody_credentials_no").val(),
                'legalbody_mobile_code':$("#legalbody_mobile_code").val(),
                'legalbody_mobile':$("#legalbody_mobile").val(),
                'legalbody_email':$("#legalbody_email").val(),
                'contract_mobile_code':$("#contract_mobile_code1").val(),
                'contract_mobile':$("#contract_mobile1").val(),
                'reg_amt':$("#reg_amt").val(),
                'indu_cate':$("#indu_cate").val(),
                'app_no':$("#app_no").val(),
                'registration_region_lv1':$("#registration_region_lv1").val(),
                'registration_region_lv2':$("#registration_region_lv2").val(),
                'registration_region_lv3':$("#registration_region_lv3").val(),
                'registration_region_lv4':$("#registration_region_lv4").val(),
                'registration_address':$("#registration_address").val(),
                'input_contract_region_name1':$("#input_contract_region_name1").val(),
                'input_contract_region_name2':$("#input_contract_region_name2").val(),
                'input_contract_region_name3':$("#input_contract_region_name3").val(),
                'input_contract_region_name4':$("#input_contract_region_name4").val(),
                'contract_region_lv1':$("#contract_region_lv1").val(),
                'contract_region_lv2':$("#contract_region_lv2").val(),
                'contract_region_lv3':$("#contract_region_lv3").val(),
                'contract_region_lv4':$("#contract_region_lv4").val(),
                'contract_address':$("#contract_address").val(),
                'info':KE.util.getData('editor'),
                'memo':$("#memo").val(),
                'bankcard':$("#bankcardHidden").val(),
                'major_name':$("#major_name").val(),
                'major_condentials_type':$("#major_condentials_type").val(),
                'major_condentials_no':$("#major_condentials_no").val(),
                'major_mobile_code':$("#major_mobile_code").val(),
                'major_mobile':$("#major_mobile").val(),
                'major_email':$("#major_email").val(),
                'major_contract_region_lv1':$("#major_contract_region_lv1").val(),
                'major_contract_region_lv2':$("#major_contract_region_lv2").val(),
                'major_contract_region_lv3':$("#major_contract_region_lv3").val(),
                'major_contract_region_lv4':$("#major_contract_region_lv4").val(),
                'major_contract_address':$("#major_contract_address").val(),
                'contract_name':$("#contract_name").val(),
                'contract_mobile_code':$("#contract_mobile_code").val(),
                'contract_mobile':$("#contract_mobile").val(),
                'consignee_phone_code':$("#consignee_phone_code").val(),
                'consignee_phone':$("#consignee_phone").val(),
                'consignee_phone_code':$("#consignee_phone_code1").val(),
                'consignee_phone':$("#consignee_phone1").val(),
                'receive_msg_mobile':$("#receive_msg_mobile").val(),
                'inviter_name':$("#inviter_name").val(),
                'inviter_country_code':$("#inviter_country_code").val(),
                'inviter_phone':$("#inviter_phone").val(),
                'inviter_organization':$("#inviter_organization").val(),
                'employee_name':$("#employee_name").val(),
                'employee_mobile_code':$("#employee_mobile_code").val(),
                'employee_mobile':$("#employee_mobile").val(),
                'employee_department':$("#employee_department").val(),
            }, function(rs){
                var rsobj = eval( "(" + rs +  ")");
                alert(rsobj.msg);
                if (rsobj.code >= 1) { // 支付系统开户成功
                    window.location.href = '<?php echo ($jumpUrl); ?>';
                }else if (rsobj.code == -2) { // 支付系统开户失败
                }
                $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            });
            return true;
        } else {
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            return false;
        }
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

    function inner_require_handle(key,isShow) {
        expr = '#'+key;
        if(isShow) {
            $(expr).addClass('require');
            $(expr).next().show();
        } else {
            $(expr).removeClass('require');
            $(expr).next().hide();
        }
    }

    function change_company_purpose(){
        var company_purpose = $("#company_purpose").find("option:selected").val();
        if(company_purpose == 10) {
            inner_require_handle('user_pwd',false);
            inner_require_handle('user_confirm_pwd',false);
            inner_require_handle('legalbody_credentials_type',false);
            inner_require_handle('legalbody_credentials_no',false);
            inner_require_handle('contract_mobile1',false);
            inner_require_handle('reg_amt',false);
            inner_require_handle('indu_cate',false);
            inner_require_handle('app_no',false);
            inner_require_handle('major_name',false);
            inner_require_handle('major_condentials_type',false);
            inner_require_handle('major_condentials_no',false);
            inner_require_handle('major_mobile',false);
            inner_require_handle('major_email',false);
            inner_require_handle('receive_msg_mobile',false);
        } else {
            inner_require_handle('user_pwd',true);
            inner_require_handle('user_confirm_pwd',true);
            inner_require_handle('legalbody_credentials_type',true);
            inner_require_handle('legalbody_credentials_no',true);
            inner_require_handle('contract_mobile1',true);
            inner_require_handle('reg_amt',true);
            inner_require_handle('indu_cate',true);
            inner_require_handle('app_no',true);
            inner_require_handle('major_name',true);
            inner_require_handle('major_condentials_type',true);
            inner_require_handle('major_condentials_no',true);
            inner_require_handle('major_mobile',true);
            inner_require_handle('major_email',true);
            inner_require_handle('receive_msg_mobile',true);
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