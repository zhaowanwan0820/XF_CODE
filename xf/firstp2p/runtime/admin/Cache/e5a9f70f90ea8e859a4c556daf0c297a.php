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
    }
    function get_user_group($group_id)
    {
        $group_name = MI("UserGroup")->where("id=".$group_id)->getField("name");
        if($group_name)
        {
            return $group_name;
        }
        else
        {
            return l("NO_GROUP");
        }
    }
    function get_user_level($id)
    {
        $level_name = MI("UserLevel")->where("id=".$id)->getField("name");
        if($level_name)
        {
            return $level_name;
        }
        else
        {
            return "????????????";
        }
    }
    function get_referrals_name($user_id)
    {
        $user_name = MI("User")->where("id=".$user_id)->getField("user_name");
        if($user_name)
        return $user_name;
        else
        return l("NO_REFERRALS");
    }
    function f_to_date($date){
        return to_date($date,"Y-m-d H:i");
    }
    function lock_money_func($money,$id){
        //return "<a href='javascript:eidt_lock_money(".$id.");'>".format_price($money)."</a>";
        return format_price($money);
    }
    function money_func($money,$user_id){
        //return "<a href='/m.php?m=MoneyApply&a=add&user_id=".$user_id."'>".format_price($money)."</a>";
        return format_price($money);
    }
    function getPassportInfo($userId) {
        $passportService = new \core\service\PassportService();
        $passportInfo = $passportService->getPassportByUser($userId);
        if (empty($passportInfo)) {
            return '';
        }
        $info = $passportInfo['local_flag'] ? '??????' : '?????????';
        $info .= '|' . $passportInfo['biz_name'];
        return $info;

    }

    function get_purpose_desc($purpose) {
        return $GLOBALS['dict']['ENTERPRISE_PURPOSE'][$purpose]['bizName'];
    } ?>
<div class="main">
<div class="main_title"><?php echo ($main_title); ?></div>
<div class="blank5"></div>
<div class="button_row">
    <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();" />
    <input type="button" class="button" value="<?php echo L("DEL");?>" onclick="del();" />
    <!--input type="button" class="button" value="????????????<?php echo L("USER_COUPON_LEVEL");?>" onclick="update_user_coupon_level();" /-->
</div>

<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        ?????????<input type="text" class="textbox" name="user_id" value="<?php echo trim($_REQUEST['user_id']);?>" style="width:100px;" />
        <?php echo L("USER_NAME");?>???<input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" style="width:100px;" />
       ???????????????<input type="text" class="textbox" name="user_num" value="<?php echo trim($_REQUEST['user_num']);?>" style="width:100px;" />
        ?????????<input type="text" class="textbox" name="real_name" value="<?php echo trim($_REQUEST['real_name']);?>" style="width:100px;" />
        <?php echo L("USER_MOBILE");?>???<input type="text" class="textbox" name="mobile" value="<?php echo trim($_REQUEST['mobile']);?>" style="width:100px;" />

        ????????????<input type="text" class="textbox" name="idno" value="<?php echo trim($_REQUEST['idno']);?>" style="width:100px;" />

        <input type="hidden" value="User" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <!-- <input type="button" class="button" value="<?php echo L("EXPORT");?>" onclick="export_csv();" /> -->
        <input type="button" class="button" value="????????????" onclick="javascript:location.href='./m.php?m=UserGroupManage&a=index'" />
    </form>
</div>
<div class="blank5"></div>
<table id="dataTable" class="dataTable" cellpadding="0" cellspacing="0">
    <tbody>
        <tr><td colspan="14" class="topTd">&nbsp; </td></tr>
        <tr class="row">
            <th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th>
            <th width="50px">??????</th>
            <th>????????????</th>
            <th>????????????</th>
            <th>??????</th>
            <th>?????????</th>
            <th>??????P2P????????????</th>
            <th>??????P2P??????????????????</th>
            <th>??????P2P????????????</th>
            <th>?????????</th>
            <th>????????????</th>
            <th>??????</th>
            <th>??????</th>
        </tr>
        <?php if(is_array($list)): foreach($list as $key=>$item): ?><tr class="row">
            <td><input type="checkbox" name="key" class="key" value="<?php echo ($item["id"]); ?>"></td>
            <td>&nbsp;<?php echo ($item["id"]); ?></td>
            <td>&nbsp;<?php echo (numTo32($item["id"])); ?></td>
            <td>&nbsp;<a href="javascript:edit('<?php echo ($item["id"]); ?>')"><?php echo ($item["user_name"]); ?></a></td>
            <td>&nbsp;<?php echo ($item["real_name"]); ?></td>
            <td>&nbsp;<?php echo ($item["mobile"]); ?></td>
            <td>&nbsp;<?php echo ($item["sv_money"]); ?></td>
            <td>&nbsp;<?php echo ($item["sv_lock_money"]); ?></td>
            <td>&nbsp;<?php echo ($item["sv_account_desc"]); ?></td>
            <td>&nbsp;<?php echo ($item["user_bankcard"]); ?></td>
            <td>&nbsp;<?php echo (f_to_date($item["create_time"])); ?></td>
            <td>&nbsp;<span class="is_effect" onclick="set_effect(<?php echo ($item["id"]); ?>,this);"><?php echo (get_is_effect($item["is_effect"],$user['id'])); ?></span></td>
            <td>
                <a href="javascript:edit('<?php echo ($item["id"]); ?>')">??????</a>&nbsp;
                <a href="javascript:edit_password('<?php echo ($item["id"]); ?>')">????????????</a>&nbsp;
                <a href="javascript:user_passed('<?php echo ($item["id"]); ?>')">??????</a>&nbsp;
                <a href="javascript:edit_identity('<?php echo ($item["id"]); ?>')">????????????</a>&nbsp;
                <a href="javascript:edit_bank('<?php echo ($item["id"]); ?>')">???????????????</a>&nbsp;
                <a href="javascript:user_balance('<?php echo ($item["id"]); ?>')">????????????</a>&nbsp;
                <a href="javascript:view_third_balance('<?php echo ($item["id"]); ?>')">??????????????????</a>&nbsp;
                <a href="javascript:copy_user('<?php echo ($item["id"]); ?>')">??????</a>&nbsp;
                <a href="javascript:view_account_auth('<?php echo ($item["id"]); ?>')">????????????</a>&nbsp;
            </td>
        </tr><?php endforeach; endif; ?>
        <tr><td colspan="13" class="bottomTd"> &nbsp;</td></tr>
    </tbody>
</table>
<div class="page"><?php echo ($page); ?></div>
</div>
<script>

function edit_password(id) {
    window.location.href='/m.php?m=User&a=edit_password&id='+id;
    return;
}

function withdraw_limit(id) {
    window.location.href='/m.php?m=User&a=limitpage&id='+id;
    return;
}

function edit_tag(id) {
    window.location.href = "/m.php?m=UserTag&a=edit_relation&uid="+id;
}

function edit_bank(id) {
    window.location.href = "/m.php?m=User&a=edit_bank&uid="+id;
}

function edit_identity(id) {
    window.location.href = "/m.php?m=User&a=edit_identity&uid="+id;
}

function user_carry_wait(id){
    window.location.href = "/m.php?m=UserCarry&a=add&uid="+id;
}

function user_balance(id){
    window.location.href = "/m.php?m=User&a=balance&uid="+id;
}

function user_summary(id) {
    window.open("/m.php?m=User&a=user_summary&uid="+id);
}

function user_log_summary(id) {
    window.open("/m.php?m=User&a=user_log_summary&uid="+id);
}
// ??????????????????
function copy_user(id) {
    window.location.href = "/m.php?m=User&a=copy_user&id="+id;
}
function changeLevelSelect(){
    var url = "/m.php?m=CouponLevel&a=get_level_select";
    var current_coupon_level_id = '<?php echo ($_REQUEST["coupon_level_id"]); ?>';
    $.getJSON(url,{group_id:$("#group_id").val()},function(json){
        var coupon_level_id = $("#coupon_level_id");
        $("option",coupon_level_id).remove(); //?????????????????????
        var option = "<option value=''>==?????????==</option>";
        coupon_level_id.append(option);
        $.each(json,function(index,array){
            var selected_str = '';
            if(array['id'] == current_coupon_level_id){
                selected_str = 'selected="selected"';
            }
            option = "<option value='"+array['id']+"' "+selected_str+">"+array['level']+"</option>";
            coupon_level_id.append(option);
        });
    });
}

changeLevelSelect();
$("#group_id").change(function(){
    $("#group_factor_text").html($(this).find("option:selected").attr("factor"));
    changeLevelSelect();
});

function update_user_coupon_level(id){
    if(confirm("?????????????????????<?php echo L("USER_COUPON_LEVEL");?>??????")){
        var url = "/m.php?m=CouponLevel&a=update_user_coupon_level";
        $.getJSON(url,'',function(json){
            var msg = "??????"+json.update+"???,??????"+json.keep+"???.";
            if(json.error > 0){
                alert("????????????\n"+msg+"\n??????:"+json.error);
            }else{
                alert("????????????\n"+msg);
            }
            window.location.reload();
        });
    }
}
function view_remote_tag(id) {
    window.location.href = '/m.php?m=RemoteTag&a=edit_user_tag&uid='+id;
}

function view_supervision_userinfo(id) {
    window.open("/m.php?m=Supervision&a=userInfo&id="+id, false);
}
function add_promotion(userId)
{
    $.weeboxs.open(ROOT+'?m=Nongdan&a=addPromotions&id='+userId, {contentType:'ajax',showButton:false,title:'??????P2P??????????????????',width:450,height:300,onopen: function(){forms_lock();}});
}

function view_supervision_userlog(id) {
    window.open("/m.php?m=Supervision&a=userLog&id="+id, false);
}
function view_gold_detail(id) {
    window.open("/m.php?m=User&a=account_detail_gold&id="+id, false);
}
function view_third_balance(id) {
    window.location.href = '/m.php?m=UserThirdBalance&a=index&userId='+id;
}
function cancel_user_account(id) {
    if(confirm("?????????????????????????????????")){
        window.location.href = '/m.php?m=User&a=cancelUserAccount&userId='+id;
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