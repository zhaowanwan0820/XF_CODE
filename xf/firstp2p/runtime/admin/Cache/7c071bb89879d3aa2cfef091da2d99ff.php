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
            return '未知的';
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
            return "没有等级";
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
        $info = $passportInfo['local_flag'] ? '网信' : '通行证';
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
    <!--input type="button" class="button" value="更新全部<?php echo L("USER_COUPON_LEVEL");?>" onclick="update_user_coupon_level();" /-->
</div>

<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        编号：<input type="text" class="textbox" name="user_id" value="<?php echo trim($_REQUEST['user_id']);?>" style="width:100px;" />
        <?php echo L("USER_NAME");?>：<input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" style="width:100px;" />
       会员编号：<input type="text" class="textbox" name="user_num" value="<?php echo trim($_REQUEST['user_num']);?>" style="width:100px;" />
        姓名：<input type="text" class="textbox" name="real_name" value="<?php echo trim($_REQUEST['real_name']);?>" style="width:100px;" />
        <?php echo L("USER_EMAIL");?>：<input type="text" class="textbox" name="email" value="<?php echo trim($_REQUEST['email']);?>" style="width:100px;" />
        <?php echo L("USER_MOBILE");?>：<input type="text" class="textbox" name="mobile" value="<?php echo trim($_REQUEST['mobile']);?>" style="width:100px;" />
        邀请码：<input type="text" class="textbox" name="coupon" value="<?php echo trim($_REQUEST['coupon']);?>" style="width:100px;" />
        注册填写邀请码：<input type="text" class="textbox" name="invite_code" value="<?php echo trim($_REQUEST['invite_code']);?>" style="width:100px;" />

        银行卡号：<input type="text" class="textbox" name="bankcard" value="<?php echo trim($_REQUEST['bankcard']);?>" style="width:100px;" />

        证件号：<input type="text" class="textbox" name="idno" value="<?php echo trim($_REQUEST['idno']);?>" style="width:100px;" />
        <?php echo L("USER_GROUP");?>:
        <select name="group_id" id="group_id">
                <option value="0" <?php if(intval($_REQUEST['group_id']) == 0): ?>selected="selected"<?php endif; ?>>==请选择==</option>
                <?php if(is_array($group_list)): foreach($group_list as $key=>$group_item): ?><option value="<?php echo ($group_item["id"]); ?>" <?php if(intval($_REQUEST['group_id']) == $group_item['id']): ?>selected="selected"<?php endif; ?>><?php echo ($group_item["name"]); ?></option><?php endforeach; endif; ?>
        </select>

        服务等级:<select id="new_coupon_level_id" name="new_coupon_level_id">
                <option value="0" <?php if(intval($_REQUEST['new_coupon_level_id']) == 0): ?>selected="selected"<?php endif; ?>>==请选择==</option>
                <?php if(is_array($new_coupon_level)): foreach($new_coupon_level as $key=>$item): ?><option value="<?php echo ($item["id"]); ?>" <?php if(intval($_REQUEST['new_coupon_level_id']) == $item['id']): ?>selected="selected"<?php endif; ?>><?php echo ($item["name"]); ?></option><?php endforeach; endif; ?>
        </select>

        用户标签列表:
        <select name="tag_id" id="tag_id">
                <option value="" <?php if(intval($_REQUEST['tag_id']) == ''): ?>selected="selected"<?php endif; ?>>==请选择==</option>
                <?php if(is_array($user_tags)): foreach($user_tags as $key=>$item): ?><option value="<?php echo ($item["id"]); ?>" <?php if(intval($_REQUEST['tag_id']) == $item['id']): ?>selected="selected"<?php endif; ?>><?php echo ($item["name"]); ?></option><?php endforeach; endif; ?>
        </select>
        远程TAG键名:
        <select name="remote_tag_key" id="remote_tag_key">
                <option value="" <?php if(intval($_REQUEST['remote_tag_key']) == ''): ?>selected="selected"<?php endif; ?>>==请选择==</option>
                <?php if(is_array($user_remote_tags)): foreach($user_remote_tags as $key=>$item): ?><option value="<?php echo ($key); ?>" <?php if(trim($_REQUEST['remote_tag_key']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($key); ?></option><?php endforeach; endif; ?>
        </select>
        远程TAG值：<input type="text" class="textbox" name="remote_tag_value" value="<?php echo trim($_REQUEST['remote_tag_value']);?>" style="width:100px;" />
        开通网贷P2P账户标识:
        <select name="supervision_account" id="supervision_account">
                <option value="0" <?php if(intval($_REQUEST['supervision_account']) == 0): ?>selected="selected"<?php endif; ?>>==请选择==</option>
                <?php if(is_array($supervision_account_list)): foreach($supervision_account_list as $key=>$item): ?><option value="<?php echo ($item["id"]); ?>" <?php if(intval($_REQUEST['supervision_account']) == $item['id']): ?>selected="selected"<?php endif; ?>><?php echo ($item["name"]); ?></option><?php endforeach; endif; ?>
        </select>

        <input type="hidden" value="User" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <!-- <input type="button" class="button" value="<?php echo L("EXPORT");?>" onclick="export_csv();" /> -->
        <input type="button" class="button" value="批量整理" onclick="javascript:location.href='./m.php?m=UserGroupManage&a=index'" />
    </form>
</div>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="31" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','User','index')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('id','<?php echo ($sort); ?>','User','index')" title="按照会员编号<?php echo ($sortType); ?> ">会员编号<?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_name','<?php echo ($sort); ?>','User','index')" title="按照<?php echo L("USER_NAME");?><?php echo ($sortType); ?> "><?php echo L("USER_NAME");?><?php if(($order)  ==  "user_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('real_name','<?php echo ($sort); ?>','User','index')" title="按照姓名<?php echo ($sortType); ?> ">姓名<?php if(($order)  ==  "real_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('email','<?php echo ($sort); ?>','User','index')" title="按照<?php echo L("USER_EMAIL");?><?php echo ($sortType); ?> "><?php echo L("USER_EMAIL");?><?php if(($order)  ==  "email"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('mobile','<?php echo ($sort); ?>','User','index')" title="按照<?php echo L("USER_MOBILE");?><?php echo ($sortType); ?> "><?php echo L("USER_MOBILE");?><?php if(($order)  ==  "mobile"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('money','<?php echo ($sort); ?>','User','index')" title="按照非网贷账户余额<?php echo ($sortType); ?> ">非网贷账户余额<?php if(($order)  ==  "money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('lock_money','<?php echo ($sort); ?>','User','index')" title="按照非网贷账户冻结金额<?php echo ($sortType); ?> ">非网贷账户冻结金额<?php if(($order)  ==  "lock_money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_purpose','<?php echo ($sort); ?>','User','index')" title="按照账户类型<?php echo ($sortType); ?> ">账户类型<?php if(($order)  ==  "user_purpose"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('sv_money','<?php echo ($sort); ?>','User','index')" title="按照网贷P2P账户余额<?php echo ($sortType); ?> ">网贷P2P账户余额<?php if(($order)  ==  "sv_money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('sv_lock_money','<?php echo ($sort); ?>','User','index')" title="按照网贷P2P账户冻结金额<?php echo ($sortType); ?> ">网贷P2P账户冻结金额<?php if(($order)  ==  "sv_lock_money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('sv_account_desc','<?php echo ($sort); ?>','User','index')" title="按照网贷P2P账户类型<?php echo ($sortType); ?> ">网贷P2P账户类型<?php if(($order)  ==  "sv_account_desc"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_bankcard','<?php echo ($sort); ?>','User','index')" title="按照银行卡<?php echo ($sortType); ?> ">银行卡<?php if(($order)  ==  "user_bankcard"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','User','index')" title="按照注册时间<?php echo ($sortType); ?> ">注册时间<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('invite_code','<?php echo ($sort); ?>','User','index')" title="按照注册填写邀请码<?php echo ($sortType); ?> ">注册填写邀请码<?php if(($order)  ==  "invite_code"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('coupon','<?php echo ($sort); ?>','User','index')" title="按照邀请码<?php echo ($sortType); ?> ">邀请码<?php if(($order)  ==  "coupon"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('coupon_disable','<?php echo ($sort); ?>','User','index')" title="按照邀请码状态<?php echo ($sortType); ?> ">邀请码状态<?php if(($order)  ==  "coupon_disable"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('service_status','<?php echo ($sort); ?>','User','index')" title="按照服务标识<?php echo ($sortType); ?> ">服务标识<?php if(($order)  ==  "service_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('new_coupon_level_name','<?php echo ($sort); ?>','User','index')" title="按照服务等级<?php echo ($sortType); ?> ">服务等级<?php if(($order)  ==  "new_coupon_level_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('is_effect','<?php echo ($sort); ?>','User','index')" title="按照用户状态<?php echo ($sortType); ?> ">用户状态<?php if(($order)  ==  "is_effect"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('group','<?php echo ($sort); ?>','User','index')" title="按照<?php echo L("USER_GROUP");?><?php echo ($sortType); ?> "><?php echo L("USER_GROUP");?><?php if(($order)  ==  "group"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('invite_user_id','<?php echo ($sort); ?>','User','index')" title="按照邀请人ID<?php echo ($sortType); ?> ">邀请人ID<?php if(($order)  ==  "invite_user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('invite_user_code','<?php echo ($sort); ?>','User','index')" title="按照邀请人邀请码<?php echo ($sortType); ?> ">邀请人邀请码<?php if(($order)  ==  "invite_user_code"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('refer_user_id','<?php echo ($sort); ?>','User','index')" title="按照服务人ID<?php echo ($sortType); ?> ">服务人ID<?php if(($order)  ==  "refer_user_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('refer_user_code','<?php echo ($sort); ?>','User','index')" title="按照服务人邀请码<?php echo ($sortType); ?> ">服务人邀请码<?php if(($order)  ==  "refer_user_code"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('refer_user_group_name','<?php echo ($sort); ?>','User','index')" title="按照服务人所在会员组<?php echo ($sortType); ?> ">服务人所在会员组<?php if(($order)  ==  "refer_user_group_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('user_tag','<?php echo ($sort); ?>','User','index')" title="按照标签<?php echo ($sortType); ?> ">标签<?php if(($order)  ==  "user_tag"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('sv_status_desc','<?php echo ($sort); ?>','User','index')" title="按照开通网贷P2P账户标识<?php echo ($sortType); ?> ">开通网贷P2P账户标识<?php if(($order)  ==  "sv_status_desc"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('id','<?php echo ($sort); ?>','User','index')" title="按照通行证信息<?php echo ($sortType); ?> ">通行证信息<?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th style="width:">操作</th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$user): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($user["id"]); ?>"></td><td>&nbsp;<?php echo ($user["id"]); ?></td><td>&nbsp;<?php echo (numTo32($user["id"])); ?></td><td>&nbsp;<a href="javascript:edit('<?php echo (addslashes($user["id"])); ?>')"><?php echo ($user["user_name"]); ?></a></td><td>&nbsp;<?php echo ($user["real_name"]); ?></td><td>&nbsp;<?php echo ($user["email"]); ?></td><td>&nbsp;<?php echo ($user["mobile"]); ?></td><td>&nbsp;<?php echo (money_func($user["money"],$user['id'])); ?></td><td>&nbsp;<?php echo (lock_money_func($user["lock_money"],$user['id'])); ?></td><td>&nbsp;<?php echo (get_purpose_desc($user["user_purpose"])); ?></td><td>&nbsp;<?php echo ($user["sv_money"]); ?></td><td>&nbsp;<?php echo ($user["sv_lock_money"]); ?></td><td>&nbsp;<?php echo ($user["sv_account_desc"]); ?></td><td>&nbsp;<?php echo ($user["user_bankcard"]); ?></td><td>&nbsp;<a href="javascript:<?php echo L("LOGIN_TIME");?>('<?php echo (addslashes($user["id"])); ?>')"><?php echo (f_to_date($user["create_time"])); ?></a></td><td>&nbsp;<?php echo ($user["invite_code"]); ?></td><td>&nbsp;<?php echo ($user["coupon"]); ?></td><td>&nbsp;<?php echo (get_coupon_disbale($user["coupon_disable"],$user['id'])); ?></td><td>&nbsp;<?php echo ($user["service_status"]); ?></td><td>&nbsp;<?php echo ($user["new_coupon_level_name"]); ?></td><td>&nbsp;<?php echo (get_is_effect($user["is_effect"],$user['id'])); ?></td><td>&nbsp;<?php echo ($user["group"]); ?></td><td>&nbsp;<?php echo ($user["invite_user_id"]); ?></td><td>&nbsp;<?php echo ($user["invite_user_code"]); ?></td><td>&nbsp;<?php echo ($user["refer_user_id"]); ?></td><td>&nbsp;<?php echo ($user["refer_user_code"]); ?></td><td>&nbsp;<?php echo ($user["refer_user_group_name"]); ?></td><td>&nbsp;<?php echo ($user["user_tag"]); ?></td><td>&nbsp;<?php echo ($user["sv_status_desc"]); ?></td><td>&nbsp;<?php echo (getPassportInfo($user["id"])); ?></td><td><a href="javascript:edit('<?php echo ($user["id"]); ?>')"><?php echo L("EDIT");?></a>&nbsp;<a href="javascript:edit_bank('<?php echo ($user["id"]); ?>')">编辑银行卡</a>&nbsp;<a href="javascript:edit_identity('<?php echo ($user["id"]); ?>')">编辑实名</a>&nbsp;<a href="javascript:del('<?php echo ($user["id"]); ?>')"><?php echo L("DEL");?></a>&nbsp;<a href="javascript:edit_password('<?php echo ($user["id"]); ?>')">重置密码</a>&nbsp;<a href="javascript:user_work('<?php echo ($user["id"]); ?>')"><?php echo L("USER_WORK_SHORT");?></a>&nbsp;<a href="javascript:contact('<?php echo ($user["id"]); ?>')">联系人</a>&nbsp;<a href="javascript:account('<?php echo ($user["id"]); ?>')"><?php echo L("USER_ACCOUNT_SHORT");?></a>&nbsp;<a href="javascript:money_transfer('<?php echo ($user["id"]); ?>')">转账</a>&nbsp;<a href="javascript:user_passed('<?php echo ($user["id"]); ?>')"><?php echo L("USER_PASSED_SHORT");?></a>&nbsp;<a href="javascript:account_detail('<?php echo ($user["id"]); ?>')">网信理财账户明细</a>&nbsp;<a href="javascript:account_detail_supervision('<?php echo ($user["id"]); ?>')">网贷P2P账户明细</a>&nbsp;<a href="javascript:view_supervision_userinfo('<?php echo ($user["id"]); ?>')">存管行账户明细</a>&nbsp;<a href="javascript:view_gold_detail('<?php echo ($user["id"]); ?>')">黄金账户明细</a>&nbsp;<a href="javascript:view_life_detail('<?php echo ($user["id"]); ?>')">网信生活明细</a>&nbsp;<a href="javascript:user_summary('<?php echo ($user["id"]); ?>')">资产总额</a>&nbsp;<a href="javascript:user_log_summary('<?php echo ($user["id"]); ?>')">资金记录汇总</a>&nbsp;<a href="javascript:user_company('<?php echo ($user["id"]); ?>')"><?php echo L("USER_COMPANY");?></a>&nbsp;<a href="javascript:user_carry_wait('<?php echo ($user["id"]); ?>')">提现申请</a>&nbsp;<a href="javascript:user_balance('<?php echo ($user["id"]); ?>')">查看余额</a>&nbsp;<a href="javascript:edit_tag('<?php echo ($user["id"]); ?>')">编辑标签</a>&nbsp;<a href="javascript:set_withdraw_amount('<?php echo ($user["id"]); ?>')">设置可提现额度</a>&nbsp;<a href="javascript: withdraw_limit('<?php echo ($user["id"]); ?>')">设置限制提现</a>&nbsp;<a href="javascript:view_remote_tag('<?php echo ($user["id"]); ?>')">查看远程标签</a>&nbsp;<a href="javascript:copy_user('<?php echo ($user["id"]); ?>')">复制</a>&nbsp;<a href="javascript:view_third_balance('<?php echo ($user["id"]); ?>')">资产中心余额</a>&nbsp;<a href="javascript:cancel_user_account('<?php echo ($user["id"]); ?>')">注销账户</a>&nbsp;<a href="javascript:view_account_auth('<?php echo ($user["id"]); ?>')">授权展示</a>&nbsp;<a href="javascript:edit_agency_user_info('<?php echo ($user["id"]); ?>')">更新代理人手机号及法人信息</a>&nbsp;<a href="javascript:add_promotion('<?php echo ($user["id"]); ?>')">网贷P2P账户划转申请</a>&nbsp;<a href="javascript:view_supervision_userlog('<?php echo ($user["id"]); ?>')">存管资金记录明细</a>&nbsp;<a href="javascript:view_incomplete_large_charge('<?php echo ($user["id"]); ?>')">未完成大额充值订单查询</a>&nbsp;</td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="31" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->

<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
</div>
<script>

function edit_password(id) {
    window.location.href='/m.php?m=User&a=edit_password&id='+id;
    return;
}

function set_withdraw_amount(id) {
    window.location.href='/m.php?m=User&a=withdrawAmount&id='+id;
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
// 复制用户数据
function copy_user(id) {
    window.location.href = "/m.php?m=User&a=copy_user&id="+id;
}

function update_user_coupon_level(id){
    if(confirm("确定要更新全部<?php echo L("USER_COUPON_LEVEL");?>吗？")){
        var url = "/m.php?m=CouponLevel&a=update_user_coupon_level";
        $.getJSON(url,'',function(json){
            var msg = "更新"+json.update+"条,不变"+json.keep+"条.";
            if(json.error > 0){
                alert("更新失败\n"+msg+"\n失败:"+json.error);
            }else{
                alert("更新成功\n"+msg);
            }
            window.location.reload();
        });
    }
}
function view_remote_tag(id) {
    window.location.href = '/m.php?m=RemoteTag&a=edit_user_tag&uid='+id;
}
function view_incomplete_large_charge(id) {
    window.open("/m.php?m=Payment&a=view_incomplete_large_charge&id="+id, false);
}
function view_supervision_userinfo(id) {
    window.open("/m.php?m=Supervision&a=userInfo&id="+id, false);
}
function view_supervision_userlog(id) {
    window.open("/m.php?m=Supervision&a=userLog&id="+id, false);
}
function view_gold_detail(id) {
    window.open("/m.php?m=User&a=account_detail_gold&id="+id, false);
}
function view_life_detail(id) {
    window.open("/m.php?m=User&a=account_detail_life&id="+id, false);
}
function view_third_balance(id) {
    window.location.href = '/m.php?m=UserThirdBalance&a=index&userId='+id;
}
function cancel_user_account(id) {
    if(confirm("你确定要注销该账户吗？")){
        window.location.href = '/m.php?m=User&a=cancelUserAccount&userId='+id;
    }
}

function edit_agency_user_info(user_id){
    $.weeboxs.open(ROOT+'?m=User&a=editAgencyUserInfo&id='+user_id, {contentType:'ajax',showButton:false,title:'更新代理人手机号及法人信息',width:450,height:300,onopen: function(){forms_lock();}});
}
function add_promotion(userId)
{
    $.weeboxs.open(ROOT+'?m=Nongdan&a=addPromotions&id='+userId, {contentType:'ajax',showButton:false,title:'网贷P2P账户划转申请',width:450,height:300,onopen: function(){forms_lock();}});
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