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
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>

<?php function get_username($user1,$row=null){
        if($row['type'] !=3){
            $str = '投资人：<a target="_blank" href="/m.php?m=User&a=index&user_name='.urlencode($user1).'">'.$user1.'</a>';
            if($row['attach_name']){
                $str .=  '&nbsp;<br>&nbsp;推荐人：<a target="_blank" href="/m.php?m=User&a=index&user_name='.urlencode($row['attach_name']).'">'.$row['attach_name'].'</a>';
            }
            if($row['agency_name']){
                $str .=  '&nbsp;<br>&nbsp;机构：<a target="_blank" href="/m.php?m=User&a=index&user_name='.urlencode($row['agency_name']).'">'.$row['agency_name'].'</a>';
            }
            return $str;
        }else{
            $str = '注册人：<a target="_blank" href="/m.php?m=User&a=index&user_name='.urlencode($user1).'">'.$user1.'</a>';
            if($row['attach_name']){
                $user_info = M('User')->where("user_name='".$row['attach_name']."'")->select();
                $user_info = $user_info[0];
                //$coupon_level_service = new coreserviceCouponLevelService();
                //$user_level = $coupon_level_service->getUserLevel($user_info['id']);
                $group_name = M("UserGroup")->where("id=".$user_info['group_id'])->getField("name");
                $str .=  '&nbsp;<br>&nbsp;邀请人：<a target="_blank" href="/m.php?m=User&a=index&user_name='.urlencode($row['attach_name']).'">'.$row['attach_name'].'</a>（'.$group_name.'-'.$user_level.'）';
            }
            if($row['agency_name']){
                $str .=  '&nbsp;<br>&nbsp;机构：<a target="_blank" href="/m.php?m=User&a=index&user_name='.urlencode($row['agency_name']).'">'.$row['agency_name'].'</a>';
            }
            return $str;
        }
    }
    function get_type($type){
        if($type == 2){
            return '优惠码结算';
        }elseif($type == 1){
            return '会员转账';
        }elseif($type == 3){
            return '注册返利';
        }
    }
    function get_money($money,$row){
        $str = $row['money'].'&nbsp;&nbsp;'.$row['into_name'];
        if($row['attach_name']){
            $str .= '<br>&nbsp;'.$row['attach_money'].'&nbsp;&nbsp;'.$row['attach_name'];
        }
        if($row['agency_name']){
            $str .= '<br>&nbsp;'.$row['agency_money'].'&nbsp;&nbsp;'.$row['agency_name'];
        }
        return $str;
    }
    function get_finance_status($status){
        if($status == 1){
            return "A角色待审核";
        }elseif($status == 2){
            return "B角色待审核";
        }elseif($status == 3){
            return "审核通过";
        }elseif($status == -1){
            return "已拒绝";
        }
    }

    function get_action_list($status,$row){
    	if ($row['type'] == 2 && ($status == 1 || $status == 2)){
    		$refuse = ($status ==1 )? 'refuse1' : 'refuse2';
    		return '<a href="javascript:finance_edit(' . $row['id'] . ",'".$refuse."'" . ');">拒绝</a>';
    	}
        if($status == 1){
            return '<a href="javascript:finance_edit(' . $row['id'] . ",'step1'" . ');">A角色通过</a>  <a href="javascript:finance_edit(' . $row['id'] . ",'refuse1'" . ');">拒绝</a>';
        }elseif($status == 2){
            return '<a href="javascript:finance_edit(' . $row['id'] . ",'step2'" . ');">B角色通过</a>  <a href="javascript:finance_edit(' . $row['id'] . ",'refuse2'" . ');">拒绝</a>';
        }elseif($status == 3){
            return "审核通过";
        }elseif($status == -1){
            return "已拒绝";
        }
        
    } ?>
<div class="main">
<div class="main_title">财务复核</div>
    <div class="blank5"></div>
    <div class="button_row">
    <input type="button" class="button" value="通过" onclick="batch_edit('<?php echo ($auth_action["p"]); ?>',this);" />
    <input type="button" class="button" value="拒绝" onclick="batch_edit('<?php echo ($auth_action["r"]); ?>',this);" />
    </div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" id="search_form" action="__APP__" method="get">
        <select name="backup" id="backup">
            <option value="0" <?php if(intval($_REQUEST['backup']) == 0): ?>selected="selected"<?php endif; ?>>近3个月</option>
            <option value="1" <?php if($_REQUEST['backup'] == 1): ?>selected="selected"<?php endif; ?>>3个月前</option>
        </select>
        转入账户会员名称：<input type="text" class="textbox" name="into_name" value="<?php echo trim($_REQUEST['into_name']);?>" style="width:100px;" />
        转入账户会员编号：<input type="text" class="textbox" name="into_num" value="<?php echo trim($_REQUEST['into_num']);?>" style="width:100px;" />
        转出账户会员名称：<input type="text" class="textbox" name="out_name" value="<?php echo trim($_REQUEST['out_name']);?>" style="width:100px;" />
        转出账户会员编号：<input type="text" class="textbox" name="out_num" value="<?php echo trim($_REQUEST['out_num']);?>" style="width:100px;" />
        投资ID区间：<input type="text" style="width:50px;" class="textbox" value="<?php echo trim($_REQUEST['deal_id_s']);?>" name="deal_id_s" /> 至 <input type="text" style="width:50px;" value="<?php echo trim($_REQUEST['deal_id_e']);?>" name="deal_id_e" class="textbox" />
        申请时间：<input type="text" style="width:150px;" class="textbox" value="<?php echo trim($_REQUEST['apply_time_start']);?>" name="apply_time_start" id="apply_time_start"  onfocus="return showCalendar('apply_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
        <input type="button" class="button" id="btn_time_start" value="选择" onclick="return showCalendar('apply_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
        至 <input type="text" value="<?php echo trim($_REQUEST['apply_time_end']);?>" name="apply_time_end" id="apply_time_end" class="textbox" onfocus="return showCalendar('apply_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:150px;" />
        <input type="button" class="button" id="btn_time_end" value="选择" onclick="return showCalendar('apply_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" />
 
        申请人：<input type="text" class="textbox" name="apply_user" value="<?php echo trim($_REQUEST['apply_user']);?>" style="width:100px;" />
        类型:
        <select name="type" id="js_type">
            <option value="0" <?php if(intval($_REQUEST['type']) == 0 ): ?>selected="selected"<?php endif; ?>>全部</option>
            <option value="1" <?php if(intval($_REQUEST['type']) == 1 ): ?>selected="selected"<?php endif; ?>>会员转账</option>
            <option value="2" <?php if(intval($_REQUEST['type']) == 2 ): ?>selected="selected"<?php endif; ?>>优惠码结算</option>
            <option value="3" <?php if(intval($_REQUEST['type']) == 3 ): ?>selected="selected"<?php endif; ?>>注册返利</option>
        </select>
        状态:
        <select name="status">
            <option value="0" <?php if(intval($_REQUEST['status']) == 0 ): ?>selected="selected"<?php endif; ?>>全部</option>
            <option value="1" <?php if(intval($_REQUEST['status']) == 1 ): ?>selected="selected"<?php endif; ?>>A角色待审核</option>
            <option value="2" <?php if(intval($_REQUEST['status']) == 2 ): ?>selected="selected"<?php endif; ?>>B角色待审核</option>
            <option value="3" <?php if(intval($_REQUEST['status']) == 3 ): ?>selected="selected"<?php endif; ?>>审核通过</option>
            <option value="-1" <?php if(intval($_REQUEST['status']) == -1 ): ?>selected="selected"<?php endif; ?>>已拒绝</option>
        </select>

        <input type="hidden" value="FinanceAudit" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
    </form>

    <input type="button" class="button" value="导入" onclick="import_csv(this);" />
    <input type="button" class="button" value="导出" onclick="export_csv();" />
</div>
<div class="blank5"></div>
<!-- Think 系统列表组件开始 -->
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 ><tr><td colspan="15" class="topTd" >&nbsp; </td></tr><tr class="row" ><th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th><th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照<?php echo L("ID");?><?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('deal_load_id','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照投资ID<?php echo ($sortType); ?> ">投资ID<?php if(($order)  ==  "deal_load_id"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('type','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照类型<?php echo ($sortType); ?> ">类型<?php if(($order)  ==  "type"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="200px"><a href="javascript:sortBy('into_user_names','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照转入账户会员<?php echo ($sortType); ?> ">转入账户会员<?php if(($order)  ==  "into_user_names"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('into_name','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照转入账户编号<?php echo ($sortType); ?> ">转入账户编号<?php if(($order)  ==  "into_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('out_user_names','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照转出账户会员<?php echo ($sortType); ?> ">转出账户会员<?php if(($order)  ==  "out_user_names"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('out_name','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照转出账户编号<?php echo ($sortType); ?> ">转出账户编号<?php if(($order)  ==  "out_name"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('money','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照转账金额<?php echo ($sortType); ?> ">转账金额<?php if(($order)  ==  "money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('status','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照审核状态<?php echo ($sortType); ?> ">审核状态<?php if(($order)  ==  "status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('log','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照审批记录<?php echo ($sortType); ?> ">审批记录<?php if(($order)  ==  "log"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('apply_user','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照申请人<?php echo ($sortType); ?> ">申请人<?php if(($order)  ==  "apply_user"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="80px"><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照申请时间<?php echo ($sortType); ?> ">申请时间<?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th><a href="javascript:sortBy('info','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照备注<?php echo ($sortType); ?> ">备注<?php if(($order)  ==  "info"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th><th width="110px"><a href="javascript:sortBy('status','<?php echo ($sort); ?>','FinanceAudit','index')" title="按照操作<?php echo ($sortType); ?> ">操作<?php if(($order)  ==  "status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a></th></tr><?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$link): ++$i;$mod = ($i % 2 )?><tr class="row" ><td><input type="checkbox" name="key" class="key" value="<?php echo ($link["id"]); ?>"></td><td>&nbsp;<?php echo ($link["id"]); ?></td><td>&nbsp;<?php echo ($link["deal_load_id"]); ?></td><td>&nbsp;<?php echo (get_type($link["type"])); ?></td><td>&nbsp;<?php echo ($link["into_user_names"]); ?></td><td>&nbsp;<?php echo (userNameToUserNum($link["into_name"])); ?></td><td>&nbsp;<?php echo ($link["out_user_names"]); ?></td><td>&nbsp;<?php echo (userNameToUserNum($link["out_name"])); ?></td><td>&nbsp;<?php echo (get_money($link["money"],$link)); ?></td><td>&nbsp;<?php echo (get_finance_status($link["status"])); ?></td><td>&nbsp;<?php echo ($link["log"]); ?></td><td>&nbsp;<?php echo ($link["apply_user"]); ?></td><td>&nbsp;<?php echo (to_date($link["create_time"])); ?></td><td>&nbsp;<?php echo ($link["info"]); ?></td><td>&nbsp;<?php echo (get_action_list($link["status"],$link)); ?></td></tr><?php endforeach; endif; else: echo "" ;endif; ?><tr><td colspan="15" class="bottomTd"> &nbsp;</td></tr></table>
<!-- Think 系统列表组件结束 -->


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


<SCRIPT type="text/javascript">
    var bool = false;
    function finance_edit(id, action, is_ajax) {
        if (is_ajax != 1) {
            if (!confirm("确认要进行操作？")) {
                return;
            }
        }
        if (!arguments[2]) is_ajax = 0;
        if (!bool) {
            bool = true;
            $.post("/m.php?m=FinanceAudit&a=" + action, { id: id, ajax: is_ajax }, function(rs) {
                var rs = $.parseJSON(rs);
                if (rs.status) {
                    //alert("操作成功！");
                    alert(rs.data);  
                    window.location.reload();
                } else {
                    alert("操作失败！" + rs.data + rs.info);
                }
            });
            bool = false;
        } else {
            alert("请不要重复点击");
            return false;
        }
        
    }

    //csv导入
    function import_csv(btn) {
        $(btn).css({ "color": "grey", "background-color": "#CCC" }).attr("disabled", "disabled");
        if (confirm("确定此操作吗？")) {
            $.weeboxs.open(ROOT + '?m=FinanceAudit&a=import', { contentType: 'ajax', showButton: false, title: '导入', width: 550, height: 200 });
        } 
        $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
    }
    //-->
    //csv 导出
    function export_csv(){
        var type = $('#js_type').val();
        if(type !=1 && type !=2 ){
            alert("请选择导出类型！");
            return false;
        }
        var parm = $('#search_form').serialize();
        window.open(ROOT+'?'+parm+'&a=export');
    }

    //通过拒绝 批量操作
    function batch_edit(action,btn) {
        $(btn).css({ "color": "grey",  "background-color":"#CCC" }).attr("disabled", "disabled");
        idBox = $(".key:checked");
        if(idBox.length == 0)
        {
            alert('请选择未处理的提现申请记录！');
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            return;
        }
        idArray = new Array();
        $.each( idBox, function(i, n){
            idArray.push($(n).val());
        });
        id = idArray.join(",");
        str = '确认批量处理您选择的记录？';
        if(confirm(str)){
            finance_edit(id,action,1);
        }
        $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
    }
</SCRIPT>