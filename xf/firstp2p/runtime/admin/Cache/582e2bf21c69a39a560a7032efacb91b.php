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

<script type="text/javascript" src="__TMPL__Common/js/deal.js"></script>

<!-- swfupload -->
<script type="text/javascript" src="__TMPL__/swfupload_plugn/js/swfupload.js"></script>
<script type="text/javascript" src="__TMPL__/swfupload_plugn/js/handlers.js"></script>
<link href="__TMPL__/swfupload_plugn/css/default.css" rel="stylesheet" type="text/css" />
<!--  -->

<div class="main">
<div class="main_title"><?php echo L("EDIT");?> <a href="<?php echo u("DealAgency/index");?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<form name="edit" action="__APP__" method="post" enctype="multipart/form-data">
<table class="form" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title">机构名称:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="name" value="<?php echo ($vo["name"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">关联用户ID:</td>
        <td class="item_input"><input size="100" type="text" class="textbox require" name="user_id" value="<?php echo ($vo["user_id"]); ?>" /> &nbsp;&nbsp;<a href="<?php echo u("$userListUrl");?>" target="_blank">会员列表</a></td>
    </tr>

    <tr>
        <td class="item_title">缩略名:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="short_name" value="<?php echo ($vo["short_name"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">LOGO:</td>
        <td class="item_input">
            &emsp;<span class="tip_span">图片小于100K，分辨率不小于110*110</span>
            <br />
            <div style='width:120px; height:40px; margin-left:10px; display:inline-block;  float:left;' class='none_border'><script type='text/javascript'>var eid = 'logo';KE.show({id : eid,items : ['upload_image'],skinType: 'tinymce',allowFileManager : true,resizeMode : 0});</script><div style='font-size:0px;'><textarea id='logo' name='logo' style='width:120px; height:25px;' ><?php echo ($logo); ?></textarea> </div></div><input type='text' id='focus_logo' style='font-size:0px; border:0px; padding:0px; margin:0px; line-height:0px; width:0px; height:0px;' /></div><img src='<?php if($logo == ''): ?>./static/admin/Common/images/no_pic.gif<?php else: ?><?php echo ($logo); ?><?php endif; ?>' <?php if($logo != ''): ?>onclick='openimg("logo")'<?php endif; ?> style='display:inline-block; float:left; cursor:pointer; margin-left:10px; border:#ccc solid 1px; width:35px; height:35px;' id='img_logo' /><img src='/static/admin/Common/images/del.gif' style='<?php if($logo == ''): ?>display:none;<?php else: ?>display:inline-block;<?php endif; ?> margin-left:10px; float:left; border:#ccc solid 1px; width:35px; height:35px; cursor:pointer;' id='img_del_logo' onclick='delimg("logo")' title='删除' />
        </td>
    </tr>
    <tr>
        <td class="item_title">机构确认帐号:</td>
        <td class="item_input"><input size="100" type="text" class="textbox" name="user" id="user_name" value="<?php echo ($vo["user"]); ?>" onchange="updateAgencyUser()" />&emsp;<span class="tip_span">签署合同账户</span></td>
    </tr>
    <tr>
        <td class="item_title">地址:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="address" value="<?php echo ($vo["address"]); ?>" /></td>
    </tr>

    <tr>
        <td class="item_title">营业执照号:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="license" value="<?php echo ($vo["license"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">营业执照图片:</td>
        <td class="item_input">
            &emsp;<span class="tip_span">图片小于1M，分辨率不小于640*480</span>
            <br />
            <div style='width:120px; height:40px; margin-left:10px; display:inline-block;  float:left;' class='none_border'><script type='text/javascript'>var eid = 'license_img';KE.show({id : eid,items : ['upload_image'],skinType: 'tinymce',allowFileManager : true,resizeMode : 0});</script><div style='font-size:0px;'><textarea id='license_img' name='license_img' style='width:120px; height:25px;' ><?php echo ($license_img); ?></textarea> </div></div><input type='text' id='focus_license_img' style='font-size:0px; border:0px; padding:0px; margin:0px; line-height:0px; width:0px; height:0px;' /></div><img src='<?php if($license_img == ''): ?>./static/admin/Common/images/no_pic.gif<?php else: ?><?php echo ($license_img); ?><?php endif; ?>' <?php if($license_img != ''): ?>onclick='openimg("license_img")'<?php endif; ?> style='display:inline-block; float:left; cursor:pointer; margin-left:10px; border:#ccc solid 1px; width:35px; height:35px;' id='img_license_img' /><img src='/static/admin/Common/images/del.gif' style='<?php if($license_img == ''): ?>display:none;<?php else: ?>display:inline-block;<?php endif; ?> margin-left:10px; float:left; border:#ccc solid 1px; width:35px; height:35px; cursor:pointer;' id='img_del_license_img' onclick='delimg("license_img")' title='删除' />
        </td>
    </tr>
    <tr>
        <td class="item_title">法定代表人:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="realname" value="<?php echo ($vo["realname"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">手机号:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="mobile" value="<?php echo ($vo["mobile"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">邮件:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="email" value="<?php echo ($vo["email"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">邮编:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="postcode" value="<?php echo ($vo["postcode"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">传真:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="fax" value="<?php echo ($vo["fax"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">评审费:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="review" value="<?php echo ($vo["review"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">保费:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="premium" value="<?php echo ($vo["premium"]); ?>" /></td>
    </tr>
    <tr>
        <td class="item_title">履约保证金:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="caution_money" value="<?php echo ($vo["caution_money"]); ?>" /></td>
    </tr>


    <tr>
        <td class="item_title">机构选择:</td>
        <td class="item_input">
        <select name="type" class="" id="type" onchange="javascript:addSignImgTag();">
            <option value="0">==<?php echo L("NO_SELECT_CATE");?>==</option>
            <?php if(is_array($type)): foreach($type as $dkey=>$type_item): ?><option value="<?php echo ($dkey); ?>" <?php if($vo['type'] == $dkey): ?>selected="selected"<?php endif; ?> ><?php echo ($type_item); ?></option><?php endforeach; endif; ?>
        </select>
        </td>
    </tr>
    <tr id="tr_sign_img">
        <td class="item_title">合同电子签章:</td>
        <td class="item_input">
            <input id="sign_img" name="sign_img" type="file" accept="image/*">
            <br />
            <br />
            <img id="sign_img_show" <?php if($sign_img): ?>src="<?php echo ($sign_img); ?>" has_img="1"<?php else: ?>src="./static/admin/Common/images/no_pic.gif" has_img="0"<?php endif; ?> style="display:inline-block; float:left; cursor:pointer; margin-left:10px; border:#ccc solid 1px; width:35px; height:35px;" id="sign_img_thum">
            <!-- 是否需要电子签章 -->
            <input name="need_sign_img" id="need_sign_img" hidden style="display:none;" value="1"/>
            <input id="old_sign_img" name="old_sign_img" type="text" hidden style="display:none;" value="<?php echo ($sign_img); ?>">
        </td>
    </tr>

    <tr>
        <td class="item_title">是否独立ICP:</td>
        <td class="item_input">
            <select name="is_icp" class="" id="type_name">
                <option value="0" <?php if($vo['is_icp'] == 0): ?>selected="selected"<?php endif; ?> >否</option>
                <option value="1" <?php if($vo['is_icp'] == 1): ?>selected="selected"<?php endif; ?> >是</option>
            </select>&nbsp; <span class="tip_span">只在平台机构中使用</span>
        </td>
    </tr>

    <tr>
        <td class="item_title">关联分站:</td>
        <td class="item_input">
            <select name="site_id" class="" id="type_name">
                <option value="0">未选择</option>
                <?php if(is_array($site_list)): foreach($site_list as $site_id=>$site_name): ?><option value="<?php echo ($site_id); ?>" <?php if($vo['site_id'] == $site_id): ?>selected="selected"<?php endif; ?> ><?php echo ($site_name); ?></option><?php endforeach; endif; ?>
            </select>&nbsp; <span class="tip_span">只在平台机构中使用并且（是否独立ICP选项为"是"）时才可以生效</span>
        </td>
    </tr>

    <tr>
        <td class="item_title">机构代理人用户ID:</td>
        <td class="item_input"><input size="100" type="text" class="textbox" name="agency_user_id" value="<?php echo ($vo["agency_user_id"]); ?>" /> &nbsp;&nbsp;<a href="/m.php?m=User&a=index&" target="_blank">会员列表</a></td>
    </tr>
    <tr>
        <td class="item_title">到期还款通知邮箱(咨询机构):</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="repay_inform_email" value="<?php echo ($vo["repay_inform_email"]); ?>" /> &nbsp; <span class="tip_span">填写多个时，请以逗号分隔</span></td>
    </tr>
    <tr>
        <td class="item_title">还款提醒邮箱(线下交易所):</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="exchange_repay_notice_email" value="<?php echo ($vo["exchange_repay_notice_email"]); ?>" /> &nbsp; <span class="tip_span">填写多个时，请以逗号分隔</span></td>
    </tr>
    <tr>
        <td class="item_title">还款计划表邮箱(线下交易所):</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="exchange_repay_plan_email" value="<?php echo ($vo["exchange_repay_plan_email"]); ?>" /> &nbsp; <span class="tip_span">填写多个时，请以逗号分隔</span></td>
    </tr>
    <tr>
        <td class="item_title">银行开户行:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="bankzone" value="<?php echo ($vo["bankzone"]); ?>" /></td>
    </tr>

     <tr>
        <td class="item_title">银行卡号:</td>
        <td class="item_input"><input size="100" type="text" class="textbox " name="bankcard" value="<?php echo ($vo["bankcard"]); ?>" /></td>
    </tr>

    <tr>
        <td class="item_title">担保方介绍:</td>
        <td class="item_input"><textarea class="textarea" name="brief" style="heigth:200px;width:800px" ><?php echo ($vo["brief"]); ?></textarea></td>
    </tr>

    <tr>
        <td class="item_title">头部:</td>
        <td class="item_input">
            <script type='text/javascript'> var eid = 'header';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='header' name='header' style=' height:150px;width:800px;' ><?php echo ($vo["header"]); ?></textarea> </div>
        </td>
    </tr>

    <tr>
        <td class="item_title">公司概况:</td>
        <td class="item_input"><textarea class="textarea" name="company_brief" style="heigth:200px;width:800px"><?php echo ($vo["company_brief"]); ?></textarea></td>
    </tr>

    <tr>
        <td class="item_title">发展史:</td>
        <td class="item_input">
            <script type='text/javascript'> var eid = 'history';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='history' name='history' style=' height:150px;width:800px;' ><?php echo ($vo["history"]); ?></textarea> </div>
        </td>
    </tr>

    <tr>
        <td class="item_title">内容:</td>
        <td class="item_input">
            <script type='text/javascript'> var eid = 'content';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='content' name='content' style=' height:350px;width:800px;' ><?php echo ($vo["content"]); ?></textarea> </div>
        </td>
    </tr>
    <tr>
        <td class="item_title">协议:</td>
        <td class="item_input">
            <script type='text/javascript'> var eid = 'content';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='content' name='agreement' style=' height:350px;width:800px;' ><?php echo ($vo["agreement"]); ?></textarea> </div>
        </td>
    </tr>
    <tr>
        <td class="item_title">常见问题:</td>
        <td class="item_input">
            <script type='text/javascript'> var eid = 'mechanism';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='mechanism' name='mechanism' style=' height:350px;width:800px;' ><?php echo ($vo["mechanism"]); ?></textarea> </div>
        </td>
    </tr>

    <!-- JIRA 3627 1+N信息披露后台功能 -->
    <tr>
        <td class="item_title">经营场所状况:</td>
        <td class="item_input">
            <script type='text/javascript'> var eid = 'business_place_state';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='business_place_state' name='business_place_state' style=' height:350px;width:800px;' ><?php echo ($vo["business_place_state"]); ?></textarea> </div>
        </td>
    </tr>
    <tr>
        <td class="item_title">经营场所图片:</td>
        <td>
            <div style="width:115px;margin:0"><span id="spanButtonPlaceholder"  ></span></div>
            <div style="width: 610px; height: auto; border: 1px solid #e1e1e1; font-size: 12px; padding: 10px;margin:0">
            <div id="divFileProgressContainer"></div>
            <div id="thumbnails">
                <input id="imgs_num_limit" value="10" style="display: none;">
                <ul id="pic_list" style="margin: 5px;">
                    <?php if(is_array($business_place_imgs)): $i = 0; $__LIST__ = $business_place_imgs;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$business_place_img): ++$i;$mod = ($i % 2 )?><script type="text/javascript">
                        uploadSuccess("", "<?php echo ($business_place_img); ?>");
                    </script><?php endforeach; endif; else: echo "" ;endif; ?>
                </ul>
                <div style="clear: both;"></div>
            </div>
                <span class="tip_span">(注：请保证单个图片小于1M，分辨率不小于640*480，图片总数不超过10张，支持类型：JPEG/GIF/PNG)</span>
            </div>
        </td>
    </tr>
    <tr>
        <td class="item_title">风险控制:</td>
        <td class="item_input">
            <script type='text/javascript'> var eid = 'risk_control';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='risk_control' name='risk_control' style=' height:350px;width:800px;' ><?php echo ($vo["risk_control"]); ?></textarea> </div>
        </td>
    </tr>
    <tr>
        <td class="item_title">机构简介:</td>
        <td class="item_input">
            <script type='text/javascript'> var eid = 'agency_brief';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='agency_brief' name='agency_brief' style=' height:350px;width:800px;' ><?php echo ($vo["agency_brief"]); ?></textarea> </div>
        </td>
    </tr>
    <tr>
        <td class="item_title">主要产品:</td>
        <td class="item_input">
            <script type='text/javascript'> var eid = 'man_product';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='man_product' name='man_product' style=' height:350px;width:800px;' ><?php echo ($vo["man_product"]); ?></textarea> </div>
        </td>
    </tr>
    <tr>
        <td class="item_title">团队介绍:</td>
        <td class="item_input">
            <script type='text/javascript'> var eid = 'team_brief';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='team_brief' name='team_brief' style=' height:350px;width:800px;' ><?php echo ($vo["team_brief"]); ?></textarea> </div>
        </td>
    </tr>
    <tr>
        <td class="item_title">企业愿景:</td>
        <td class="item_input">
            <script type='text/javascript'> var eid = 'future_expectation';KE.show({id : eid,skinType: 'tinymce',allowFileManager : true,resizeMode : 0,filterMode : false,items : [
							'source','fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
							'plainpaste', 'wordpaste', 'justifyleft', 'justifycenter', 'justifyright',
							'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
							'superscript', 'selectall', '-',
							'title', 'fontname', 'fontsize', 'textcolor', 'bgcolor', 'bold',
							'italic', 'underline', 'strikethrough', 'removeformat', 'image',
							'flash', 'media', 'table', 'hr', 'emoticons', 'link', 'unlink'
						]});</script><div  style='margin-bottom:5px; '><textarea id='future_expectation' name='future_expectation' style=' height:350px;width:800px;' ><?php echo ($vo["future_expectation"]); ?></textarea> </div>
        </td>
    </tr>
    <tr>
        <td class="item_title">信贷系统是否可见:</td>
        <td class="item_input">
            <lable>可见<input type="radio" name="is_credit_display" value="1" <?php if($vo['is_credit_display'] == 1): ?>checked="checked"<?php endif; ?> /></lable>
            <lable>不可见<input type="radio" name="is_credit_display" value="0" <?php if($vo['is_credit_display'] == 0): ?>checked="checked"<?php endif; ?> /></lable>
        </td>
    </tr>

    <tr>
        <td class="item_title"><?php echo L("IS_EFFECT");?>:</td>
        <td class="item_input">
            <lable><?php echo L("IS_EFFECT_1");?><input type="radio" name="is_effect" value="1" <?php if($vo['is_effect'] == 1): ?>checked="checked"<?php endif; ?> /></lable>
            <lable><?php echo L("IS_EFFECT_0");?><input type="radio" name="is_effect" value="0" <?php if($vo['is_effect'] == 0): ?>checked="checked"<?php endif; ?> /></lable>
        </td>
    </tr>


    <tr>
        <td class="item_title"><?php echo L("SORT");?>:</td>
        <td class="item_input"><input size="100" type="text" class="textbox" name="sort" value="<?php echo ($vo["sort"]); ?>" /></td>
    </tr>

    <tr>
        <td class="item_title"></td>
        <td class="item_input">
            <!--隐藏元素-->
            <input type="hidden" name="id" value="<?php echo ($vo["id"]); ?>" />
            <input type="hidden" name="<?php echo conf("VAR_MODULE");?>" value="DealAgency" />
            <input type="hidden" name="<?php echo conf("VAR_ACTION");?>" value="update" />
            <input type="hidden" name="showMsg" value="确认修改该机构信息吗?" id="showMsg" />
            <!--隐藏元素-->
            <input type="submit" class="button" onclick="return confirm($('#showMsg').val())"  value="<?php echo L("EDIT");?>" />
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
    function addSignImgTag()
    {
        if ((7 == $('#type').val())||(9 == $('#type').val())) {
            $('#tr_sign_img').show();
            $('#need_sign_img').val(1);
            if (0 == $('#sign_img_show').attr('has_img')) {
                $('#sign_img').addClass('require');
            }
            if (9 == $('#type').val()) {
                $('#sign_img').removeClass('require');
            }
        } else {
            $('#tr_sign_img').hide();
            $('#need_sign_img').val(0);
            $('#sign_img').removeClass('require');
        }
    }

    function updateAgencyUser(){
        var user = $('#user_name').val();
        var type_name = $('#type_name').val();
        $.ajax({
            url:'m.php?m=DealAgency&a=checkUserAgency&user='+user+'&type='+type_name,
            type:'get',
            cache:false,
            dataType:'text',
            success:function(data) {
                var msg = data+"确认新增该机构吗？\n";
                $('#showMsg').val(msg);
            }
        });
    }

    // thinkphp + swfupload 图片上传插件
    var swfu;
    var swfuAttribute = {
        upload_url: "m.php?m=DealAgency&a=uploadImg",
        post_params: {"PHPSESSID": "<?php echo session_id();?>"},
        file_size_limit : 1024,
        file_types : "*.jpg;*.png;*.gif;",
        file_types_description : "JPG Images",
        file_dialog_start_handler : fileDialogStart,
        file_queued_handler : fileQueued,
        file_queue_error_handler : fileQueueError,
        file_dialog_complete_handler : fileDialogComplete,
        upload_progress_handler : uploadProgress,
        upload_error_handler : uploadError,
        upload_success_handler : uploadSuccess,
        upload_complete_handler : uploadComplete,
        button_image_url : "__TMPL__/swfupload_plugn/images/upload.png",
        button_placeholder_id : "spanButtonPlaceholder",
        button_width: 113,
        button_height: 45,
        button_text : '',
        button_text_style : '.spanButtonPlaceholder { font-family: Helvetica, Arial, sans-serif; font-size: 14pt;} ',
        button_text_top_padding: 0,
        button_text_left_padding: 0,
        button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
        button_cursor: SWFUpload.CURSOR.HAND,
        flash_url : "__TMPL__/swfupload_plugn/swf/swfupload.swf",
        custom_settings : {
          upload_target : "divFileProgressContainer"
        },
        debug: false
    };
    window.onload = function () {
      swfu = new SWFUpload(swfuAttribute);
      addSignImgTag();
    };

    // upload_sign_img
    $("#sign_img").change(function(){
        $("#sign_img_thum").attr("src", getObjectURL(this.files[0]));
    });

    // file 添加之后的预览url
    function getObjectURL(file) {
        var url = null;
        if (window.createObjectURL != undefined) {
            url = window.createObjectURL(file)
        } else if (window.URL != undefined) {
            url = window.URL.createObjectURL(file)
        } else if (window.webkitURL != undefined) {
            url = window.webkitURL.createObjectURL(file)
        }
        return url
    };
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