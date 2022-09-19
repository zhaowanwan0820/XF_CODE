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
<script type="text/javascript" src="__TMPL__Common/js/conf.js"></script>
<style type="text/css">
.flipped {
    transform: scale(-1, 1);
    -moz-transform: scale(-1, 1);
    -webkit-transform: scale(-1, 1);
    -o-transform: scale(-1, 1);
    -khtml-transform: scale(-1, 1);
    -ms-transform: scale(-1, 1);
}
</style>

<div class="main">
<div class="main_title"><?php echo ($user_info["user_name"]); ?> 资料审核</div>
<div class="blank5"></div>
<table class="form" cellpadding=0 cellspacing=0>
    <tr>
        <td colspan=2 class="topTd"></td>
    </tr>
    <tr>
        <td class="item_title"><span style="color:red">*</span>身份认证:</td>
        <td class="item_input">
            <?php if($user_info['idcardpassed'] == 0): ?>未审核<?php endif; ?>
            <?php if($user_info['idcardpassed'] == 1): ?><span style="color:red">审核通过</span><?php endif; ?>
            <?php if($user_info['idcardpassed'] == 2): ?>审核失败<?php endif; ?>
            <?php if($credit_file['credit_identificationscanning']['file_list']): ?>资料已上传 &nbsp;<a href="###" data-title="身份认证" class="ViewCreditFile">查看</a><?php endif; ?>
            <?php if($passport['id']): ?>护照已提交 &nbsp;<a href="###" data-title="身份认证" class="ViewCreditFile">查看</a><?php endif; ?>
                

            <?php if(!$passport['id'] && !$credit_file['credit_identificationscanning']['file_list']): ?>未上传资料<?php endif; ?>
            <div id="tempFile" style="display:none;">
                <a href="###" class="CreditEdit" onclick="opcredit('idcardpassed','<?php echo ($user_info["id"]); ?>')">操作</a><br><br>

                <?php if($passport['id']): ?>证件归属地:<?php echo ($passport["region"]); ?><br>
                    姓名:<?php echo ($passport["name"]); ?><br>
                    通行证号码:<?php echo ($passport["passportid"]); ?><br>
                    通行证有效期至:<?php echo ($passport["valid_date"]); ?><br>
                    通行证正面:<a href="javascript:void(0)" class="_js_trans_pic" >反转</a>&nbsp;&nbsp;<a href="javascript:void(0)" class="_js_show_pic" >查证</a><br>
                    <a class="img" href="<?php echo ($passport["file"]["pass1"]); ?>" target="_blank" style="display:none;">
                    <img src="<?php echo ($passport["file"]["pass1"]); ?>" border="0" width="370">
                    </a>
                    通行证反面:<a href="javascript:void(0)" class="_js_trans_pic" >反转</a>&nbsp;&nbsp;<a href="javascript:void(0)" class="_js_show_pic">查证</a><br>
                    <a class="img" href="<?php echo ($passport["file"]["pass2"]); ?>" target="_blank" style="display:none;">
                    <img src="<?php echo ($passport["file"]["pass2"]); ?>" border="0" width="370">
                    </a>
                    <br>
                    性别:<?php if($passport['sex'] == 0): ?>女<?php else: ?>男<?php endif; ?><br>
                    出生日期：<?php echo ($passport["birthday"]); ?><br>
                    身份证号：<?php echo ($passport["idno"]); ?><br>
                    身份证正面 <a href="javascript:void(0)" class="_js_trans_pic" >反转</a>&nbsp;&nbsp;<a href="javascript:void(0)" class="_js_show_pic">查证</a><br>
                    <a class="img" href="<?php echo ($passport["file"]["idno1"]); ?>" target="_blank" style="display:none;">
                    <img src="<?php echo ($passport["file"]["idno1"]); ?>" border="0" width="370">
                    </a>
                    身份证反面 <a href="javascript:void(0)" class="_js_trans_pic" >反转</a>&nbsp;&nbsp;<a href="javascript:void(0)" class="_js_show_pic">查证</a><br>
                    <a class="img" href="<?php echo ($passport["file"]["idno2"]); ?>" target="_blank" style="display:none;">
                    <img src="<?php echo ($passport["file"]["idno2"]); ?>" border="0" width="370">
                    </a>
                <?php else: ?><?php endif; ?>
                    <?php if($user_info['idcardpassed'] != 1 && $credit_file['credit_identificationscanning']['file'] != ''): ?><a href="###" class="CreditEdit" onclick="opcredit('idcardpassed','<?php echo ($user_info["id"]); ?>')">操作</a>
                    <div class="blank5"></div><?php endif; ?>
                    <br><br>
                    <hr>
                    姓名:<?php echo ($user_info["real_name"]); ?><br>
                    身份证号码:<?php echo ($user_info["idno"]); ?><a href="javascript:void(0)" class="_js_trans_pic" >反转</a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="checkidcrad('<?php echo ($user_info["idno"]); ?>')">查证</a><br>
                    籍贯:<?php echo ($user_info["n_province"]); ?>&nbsp;<?php echo ($user_info["n_city"]); ?><br>
                    户口所在地:<?php echo ($user_info["province"]); ?>&nbsp;<?php echo ($user_info["city"]); ?><br>
                    出生日期：<?php echo ($user_info["byear"]); ?>-<?php echo ($user_info["bmonth"]); ?>-<?php echo ($user_info["bday"]); ?><br>
                    性别:<?php if($user_info['sex'] == 0): ?>女<?php else: ?>男<?php endif; ?><br>
                    <?php if(is_array($credit_file["credit_identificationscanning"]["file_list"])): foreach($credit_file["credit_identificationscanning"]["file_list"] as $key=>$item): ?><a href="<?php echo (get_www_url($item)); ?>" target="_blank"><img src="<?php echo (get_www_url($item)); ?>" border="0" width="370"></a>
                        <div class="blank5"></div><?php endforeach; endif; ?>
                
            </div>
            
            <a href="###" class="CreditEdit" onclick="opcredit('idcardpassed','<?php echo ($user_info["id"]); ?>')">操作</a>
            
        </td>
    </tr>
       <tr>
        <td class="item_title"><span style="color:red">*</span>视频认证:</td>
        <td class="item_input">
            <?php if($user_info['videopassed'] == 0): ?>未审核<?php endif; ?>
            <?php if($user_info['videopassed'] == 1): ?><span style="color:red">审核通过</span><?php endif; ?>
            <?php if($user_info['videopassed'] == 2): ?>审核失败<?php endif; ?>
            <?php if($user_info['has_send_video'] == 1): ?><span class="tip_span">资料已上传到邮箱:<?php echo C('REPLY_ADDRESS');?></span><?php endif; ?>
            
            <a href="###" class="CreditEdit" onclick="opcredit('videopassed','<?php echo ($user_info["id"]); ?>')">操作</a>
            
        </td>
    </tr>
    <tr>
        <td class="item_title">工作认证:</td>
        <td class="item_input">
            <?php if($user_info['workpassed'] == 0): ?>未审核<?php endif; ?>
            <?php if($user_info['workpassed'] == 1): ?><span style="color:red">审核通过</span><?php endif; ?>
            <?php if($user_info['workpassed'] == 2): ?>审核失败<?php endif; ?>
            <?php if($credit_file['credit_contact']['file_list']): ?>资料已上传 <a href="###" class="ViewCreditFile">查看</a><?php else: ?>未上传资料<?php endif; ?>
            <div id="tempFile" style="display:none;">
                <?php if($user_info['workpassed'] != 1 && $credit_file['credit_contact']['file'] != ''): ?><a href="###" class="CreditEdit" onclick="opcredit('workpassed','<?php echo ($user_info["id"]); ?>')">操作</a>
                <div class="blank5"></div><?php endif; ?>
                <?php if(is_array($credit_file["credit_contact"]["file_list"])): foreach($credit_file["credit_contact"]["file_list"] as $key=>$item): ?><a href="<?php echo (get_www_url($item)); ?>" target="_blank"><img src="<?php echo (get_www_url($item)); ?>" border="0" width="370"></a>
                    <div class="blank5"></div><?php endforeach; endif; ?>
            </div>
            
            <a href="###" class="CreditEdit" onclick="opcredit('workpassed','<?php echo ($user_info["id"]); ?>')">操作</a>
            
        </td>
    </tr>
    <tr>
        <td class="item_title">信用报告:</td>
        <td class="item_input">
            <?php if($user_info['creditpassed'] == 0): ?>未审核<?php endif; ?>
            <?php if($user_info['creditpassed'] == 1): ?><span style="color:red">审核通过</span><?php endif; ?>
            <?php if($user_info['creditpassed'] == 2): ?>审核失败<?php endif; ?>
            <?php if($credit_file['credit_credit']['file_list']): ?>资料已上传 <a href="###" class="ViewCreditFile">查看</a><?php else: ?>未上传资料<?php endif; ?>
            <div id="tempFile" style="display:none;">
                <?php if($user_info['creditpassed'] != 1 && $credit_file['credit_credit']['file'] != ''): ?><a href="###" class="CreditEdit" onclick="opcredit('creditpassed','<?php echo ($user_info["id"]); ?>')">操作</a>
                <div class="blank5"></div><?php endif; ?>
                <?php if(is_array($credit_file["credit_credit"]["file_list"])): foreach($credit_file["credit_credit"]["file_list"] as $key=>$item): ?><a href="<?php echo (get_www_url($item)); ?>" target="_blank"><img src="<?php echo (get_www_url($item)); ?>" border="0" width="370"></a>
                    <div class="blank5"></div><?php endforeach; endif; ?>
            </div>
            
            <a href="###" class="CreditEdit" onclick="opcredit('creditpassed','<?php echo ($user_info["id"]); ?>')">操作</a>
            
        </td>
    </tr>
    <tr>
        <td class="item_title">收入认证:</td>
        <td class="item_input">
            <?php if($user_info['incomepassed'] == 0): ?>未审核<?php endif; ?>
            <?php if($user_info['incomepassed'] == 1): ?><span style="color:red">审核通过</span><?php endif; ?>
            <?php if($user_info['incomepassed'] == 2): ?>审核失败<?php endif; ?>
            <?php if($credit_file['credit_incomeduty']['file_list']): ?>资料已上传 <a href="###" class="ViewCreditFile">查看</a><?php else: ?>未上传资料<?php endif; ?>
            <div id="tempFile" style="display:none;">
                <?php if($user_info['incomepassed'] != 1 && $credit_file['credit_incomeduty']['file'] != ''): ?><a href="###" class="CreditEdit" onclick="opcredit('incomepassed','<?php echo ($user_info["id"]); ?>')">操作</a>
                <div class="blank5"></div><?php endif; ?>
                <?php if(is_array($credit_file["credit_incomeduty"]["file_list"])): foreach($credit_file["credit_incomeduty"]["file_list"] as $key=>$item): ?><a href="<?php echo (get_www_url($item)); ?>" target="_blank"><img src="<?php echo (get_www_url($item)); ?>" border="0" width="370"></a>
                    <div class="blank5"></div><?php endforeach; endif; ?>
            </div>
            <a href="###" class="CreditEdit" onclick="opcredit('incomepassed','<?php echo ($user_info["id"]); ?>')">操作</a>
        </td>
    </tr>
    
    <?php if($user_info['hashouse'] == 1): ?><tr>
        <td class="item_title">房产认证:</td>
        <td class="item_input">
            <?php if($user_info['housepassed'] == 0): ?>未审核<?php endif; ?>
            <?php if($user_info['housepassed'] == 1): ?><span style="color:red">审核通过</span><?php endif; ?>
            <?php if($user_info['housepassed'] == 2): ?>审核失败<?php endif; ?>
            <?php if($credit_file['credit_house']['file_list']): ?>资料已上传 <a href="###" class="ViewCreditFile">查看</a><?php else: ?>未上传资料<?php endif; ?>
            <div id="tempFile" style="display:none;">
                <?php if($user_info['housepassed'] != 1 && $credit_file['credit_house']['file'] != ''): ?><a href="###" class="CreditEdit" onclick="opcredit('housepassed','<?php echo ($user_info["id"]); ?>')">操作</a>
                <div class="blank5"></div><?php endif; ?>
                <?php if(is_array($credit_file["credit_house"]["file_list"])): foreach($credit_file["credit_house"]["file_list"] as $key=>$item): ?><a href="<?php echo (get_www_url($item)); ?>" target="_blank"><img src="<?php echo (get_www_url($item)); ?>" border="0" width="370"></a>
                    <div class="blank5"></div><?php endforeach; endif; ?>
            </div>
            
            <a href="###" class="CreditEdit" onclick="opcredit('housepassed','<?php echo ($user_info["id"]); ?>')">操作</a>
            
        </td>
    </tr><?php endif; ?>
    <?php if($user_info['hascar'] == 1): ?><tr>
        <td class="item_title">购车认证:</td>
        <td class="item_input">
            <?php if($user_info['carpassed'] == 0): ?>未审核<?php endif; ?>
            <?php if($user_info['carpassed'] == 1): ?><span style="color:red">审核通过</span><?php endif; ?>
            <?php if($user_info['carpassed'] == 2): ?>审核失败<?php endif; ?>
            <?php if($credit_file['credit_car']['file_list']): ?>资料已上传 <a href="###" class="ViewCreditFile">查看</a><?php else: ?>未上传资料<?php endif; ?>
            <div id="tempFile" style="display:none;">
                <?php if($user_info['carpassed'] != 1 && $credit_file['credit_car']['file'] != ''): ?><a href="###" class="CreditEdit" onclick="opcredit('carpassed','<?php echo ($user_info["id"]); ?>')">操作</a>
                <div class="blank5"></div><?php endif; ?>
                汽车品牌:<?php echo ($user_info["car_brand"]); ?><br/>
                购车年份:<?php echo ($user_info["car_year"]); ?><br/>
                车牌号码:<?php echo ($user_info["car_number"]); ?><br/>
                <?php if(is_array($credit_file["credit_car"]["file_list"])): foreach($credit_file["credit_car"]["file_list"] as $key=>$item): ?><a href="<?php echo (get_www_url($item)); ?>" target="_blank"><img src="<?php echo (get_www_url($item)); ?>" border="0" width="370"></a>
                    <div class="blank5"></div><?php endforeach; endif; ?>
            </div>
            
            <a href="###" class="CreditEdit" onclick="opcredit('carpassed','<?php echo ($user_info["id"]); ?>')">操作</a>
            
        </td>
    </tr><?php endif; ?>
    <?php if($user_info['marriage'] == '已婚'): ?><tr>
        <td class="item_title">结婚认证:</td>
        <td class="item_input">
            <?php if($user_info['marrypassed'] == 0): ?>未审核<?php endif; ?>
            <?php if($user_info['marrypassed'] == 1): ?><span style="color:red">审核通过</span><?php endif; ?>
            <?php if($user_info['marrypassed'] == 2): ?>审核失败<?php endif; ?>
            <?php if($credit_file['credit_marriage']['file_list']): ?>资料已上传 <a href="###" class="ViewCreditFile">查看</a><?php else: ?>未上传资料<?php endif; ?>
            <div id="tempFile" style="display:none;">
                <?php if($user_info['marrypassed'] != 1 && $credit_file['credit_marriage']['file'] != ''): ?><a href="###" class="CreditEdit" onclick="opcredit('marrypassed','<?php echo ($user_info["id"]); ?>')">操作</a><?php endif; ?>
                <div class="blank5"></div>
                <?php if(is_array($credit_file["credit_marriage"]["file_list"])): foreach($credit_file["credit_marriage"]["file_list"] as $key=>$item): ?><a href="<?php echo (get_www_url($item)); ?>" target="_blank"><img src="<?php echo (get_www_url($item)); ?>" border="0" width="370"></a>
                    <div class="blank5"></div><?php endforeach; endif; ?>
            </div>
            
            <a href="###" class="CreditEdit" onclick="opcredit('marrypassed','<?php echo ($user_info["id"]); ?>')">操作</a>
            
        </td>
    </tr><?php endif; ?>
    <tr>
        <td class="item_title">学历认证:</td>
        <td class="item_input">
            <?php if($user_info['edupassed'] == 0): ?>未审核<?php endif; ?>
            <?php if($user_info['edupassed'] == 1): ?><span style="color:red">审核通过</span><?php endif; ?>
            <?php if($user_info['edupassed'] == 2): ?>审核失败<?php endif; ?>
            <?php if($user_info['edu_validcode']): ?>在线验证码已输入 <a href="###" class="ViewCreditFile">查看</a><?php else: ?>未输入在线验证码<?php endif; ?>
            <div id="tempFile" style="display:none;">
                <?php if($user_info['edupassed'] != 1 ): ?><a href="###" class="CreditEdit" onclick="opcredit('edupassed','<?php echo ($user_info["id"]); ?>')">操作</a>
                <div class="blank5"></div><?php endif; ?>
                最高学历:<?php echo ($user_info["graduation"]); ?><br/>
                入学年份:<?php echo ($user_info["graduatedyear"]); ?><br/>
                毕业院校:<?php echo ($user_info["university"]); ?><br/>
                12位在线验证码:<?php echo ($user_info["edu_validcode"]); ?><br/>
                <div>
                    点击 <a href="http://www.chsi.com.cn/xlcx/" target="_blank">网上学历查询</a>。
                </div>
                <?php if(is_array($credit_file["credit_graducation"]["file_list"])): foreach($credit_file["credit_graducation"]["file_list"] as $key=>$item): ?><a href="<?php echo (get_www_url($item)); ?>" target="_blank"><img src="<?php echo (get_www_url($item)); ?>" border="0" width="370"></a>
                    <div class="blank5"></div><?php endforeach; endif; ?>
            </div>
            
            <a href="###" class="CreditEdit" onclick="opcredit('edupassed','<?php echo ($user_info["id"]); ?>')">操作</a>
            
        </td>
    </tr>
    <tr>
        <td class="item_title">技术职称认证:</td>
        <td class="item_input">
            <?php if($user_info['skillpassed'] == 0): ?>未审核<?php endif; ?>
            <?php if($user_info['skillpassed'] == 1): ?><span style="color:red">审核通过</span><?php endif; ?>
            <?php if($user_info['skillpassed'] == 2): ?>审核失败<?php endif; ?>
            <?php if($credit_file['credit_titles']['file_list']): ?>资料已上传 <a href="###" class="ViewCreditFile">查看</a><?php else: ?>未上传资料<?php endif; ?>
            <div id="tempFile" style="display:none;">
                <?php if($user_info['skillpassed'] != 1 && $credit_file['credit_titles']['file'] != ''): ?><a href="###" class="CreditEdit" onclick="opcredit('skillpassed','<?php echo ($user_info["id"]); ?>')">操作</a>
                <div class="blank5"></div><?php endif; ?>
                <?php if(is_array($credit_file["credit_titles"]["file_list"])): foreach($credit_file["credit_titles"]["file_list"] as $key=>$item): ?><a href="<?php echo (get_www_url($item)); ?>" target="_blank"><img src="<?php echo (get_www_url($item)); ?>" border="0" width="370"></a>
                    <div class="blank5"></div><?php endforeach; endif; ?>
            </div>
            
            <a href="###" class="CreditEdit" onclick="opcredit('skillpassed','<?php echo ($user_info["id"]); ?>')">操作</a>
            
        </td>
    </tr>
    
    <tr>
        <td class="item_title">手机实名认证:</td>
        <td class="item_input">
            <?php if($user_info['mobiletruepassed'] == 0): ?>未审核<?php endif; ?>
            <?php if($user_info['mobiletruepassed'] == 1): ?><span style="color:red">审核通过</span><?php endif; ?>
            <?php if($user_info['mobiletruepassed'] == 2): ?>审核失败<?php endif; ?>
            <?php if($credit_file['credit_mobilereceipt']['file_list']): ?>资料已上传 <a href="###" class="ViewCreditFile">查看</a><?php else: ?>未上传资料<?php endif; ?>
            <div id="tempFile" style="display:none;">
                <?php if($user_info['mobiletruepassed'] != 1 && $credit_file['credit_mobilereceipt']['file_list'] != ''): ?><a href="###" class="CreditEdit" onclick="opcredit('mobiletruepassed','<?php echo ($user_info["id"]); ?>')">操作</a>
                <div class="blank5"></div><?php endif; ?>
                手机号码:<?php echo ($user_info["mobile"]); ?><br>
                <?php if(is_array($credit_file["credit_mobilereceipt"]["file_list"])): foreach($credit_file["credit_mobilereceipt"]["file_list"] as $key=>$item): ?><a href="<?php echo (get_www_url($item)); ?>" target="_blank"><img src="<?php echo (get_www_url($item)); ?>" border="0" width="370"></a>
                    <div class="blank5"></div><?php endforeach; endif; ?>
            </div>
            
            <a href="###" class="CreditEdit" onclick="opcredit('mobiletruepassed','<?php echo ($user_info["id"]); ?>')">操作</a>
            
        </td>
    </tr>
    <tr>
        <td class="item_title">居住地证明:</td>
        <td class="item_input">
            <?php if($user_info['residencepassed'] == 0): ?>未审核<?php endif; ?>
            <?php if($user_info['residencepassed'] == 1): ?><span style="color:red">审核通过</span><?php endif; ?>
            <?php if($user_info['residencepassed'] == 2): ?>审核失败<?php endif; ?>
            </select>
            <?php if($credit_file['credit_residence']['file_list']): ?>资料已上传 <a href="###" class="ViewCreditFile">查看</a><?php else: ?>未上传资料<?php endif; ?>
            <div id="tempFile" style="display:none;">
                <?php if($user_info['residencepassed'] != 1 && $credit_file['credit_residence']['file_list'] != ''): ?><a href="###" class="CreditEdit" onclick="opcredit('residencepassed','<?php echo ($user_info["id"]); ?>')">操作</a>
                <div class="blank5"></div><?php endif; ?>
                居住地址:<?php echo ($user_info["address"]); ?><br>
                <?php if(is_array($credit_file["credit_residence"]["file_list"])): foreach($credit_file["credit_residence"]["file_list"] as $key=>$item): ?><a href="<?php echo (get_www_url($item)); ?>" target="_blank"><img src="<?php echo (get_www_url($item)); ?>" border="0" width="370"></a>
                    <div class="blank5"></div><?php endforeach; endif; ?>
            </div>
            
            <a href="###" class="CreditEdit" onclick="opcredit('residencepassed','<?php echo ($user_info["id"]); ?>')">操作</a>
            
        </td>
    </tr>
    <tr>
        <td colspan=2 class="bottomTd"></td>
    </tr>
</table>
</div>
<script type="text/javascript">
    jQuery(function(){
        $(".ViewCreditFile").bind("click",function(){
            var html = $(this).parent().find("#tempFile").html();
            var title = $(this).attr("data-title");
            if(title == ""){
                title = LANG['USER_WORK'];
            }
            $.weeboxs.open(html, {contentType:'html',showButton:false,title:title,width:400,height:400});
        });
        //查看图片
        $("._js_show_pic").live("click",function(){
            if($(this).html() == "关闭"){
                $(this).next().next().hide();
                $(this).html("查看");
            }else{
                $(this).next().next().show();
                $(this).html("关闭");
            }
        });
        //反转图片
        $("._js_trans_pic").live("click",function(){
            $(this).next().next().next().children().toggleClass('flipped');
        });

    });
    function opcredit(act,uid){

        var forms_lock = function() {
            var forms = $('form[name=edit]');
            forms.each(function(i, el){
                var btn = $(el).find('input[type=submit]');
                console.log('btn', btn);
                //删除行内onclick事件
                btn.attr('onclick', '');
                btn.click(function(){
                    $(btn).css({"color":"gray","background":"#ccc"}).attr("disabled","disabled");
                    if (confirm("确定此操作吗？")) {
                        $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
                        return true;
                    } else {
                        $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
                        return false;
                    }

                });
            })
        }


        $.weeboxs.open(ROOT+'?m=User&a=op_passed&user_id='+uid+"&field="+act, {contentType:'ajax',showButton:false,title:LANG['USER_PASSED'],width:600,height:300, onopen: function(){forms_lock();}});
    }
    function checkidcrad(card){
        $.ajax({
            url:ROOT+"?m=Public&a=checkIdCard&card="+card,
            dataType:"json",
            success:function(result){
                if(result.status == 0){
                    alert(result.info);
                }
                else{
                    var alt = "身份证号："+result.code+"\n";
                    alt += "籍贯："+result.location+"\n";
                        alt +="生日："+result.birthday+"\n";
                    if(result.gender=="m")
                        alt += "性别：男";
                    else
                        alt += "性别：女";
                    alert(alt);
                }
            },
            error:function(){
                alert("网络不通，或者当前接口查询次数已满，请等待下个小时！");
            }
        });
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