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
<script type="text/javascript" src="__TMPL__Common/js/carry.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>

<script type="text/javascript">
    function import_rdm(id) {
        $.ajax({
            url: ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=import_rdm&id=" + id,
            data: "ajax=1",
            dataType: "json",
            success: function (obj) {
                if (obj.status == 1) {
                    alert("???????????????");
                } else {
                    alert("???????????????");
                }
                return true;
            }
        });
    }
    function down_csv() {
        var id = '';

        $(".key").each(function () {
            if ($(this).attr("checked") == true)
                id += $(this).val() + ",";
        });

        var url = ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=getYbCsv&id=" + id;
        location.href = url;
    }

    function cfm($type,btn){
        $(btn).css("color","grey").attr("disabled", "disabled");
        if($type == 'del')
            str = "???????????????";
        else
            str = "???????????????";

        if(confirm(str)){
            window.location.href = $(btn).attr('data-href') + get_query_string('search_id');
        }else{
            $(btn).css("color","#4e6a81").removeAttr("disabled"); }
    }

    function wdel(id) {
        if (!id) {
            idBox = $(".key:checked");
            if (idBox.length == 0) {
                alert(LANG['DELETE_EMPTY_WARNING']);
                return;
            }
            idArray = new Array();
            $.each(idBox, function (i, n) {
                idArray.push($(n).val());
            });
            id = idArray.join(",");
        }
        if (confirm(LANG['CONFIRM_DELETE']))
            $.ajax({
                url: ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=del&id=" + id,
                data: "ajax=1",
                dataType: "json",
                success: function (obj) {
                    $("#info").html(obj.info);
                    if (obj.status == 1)
                        location.href = location.href;
                }
            });
    }

    //???????????? ????????????
    var lock = false;
    function waitPass(audit, btn) {
        if (!!lock) return;
        lock = true;
        $(btn).css("background-color", "gray").attr("disabled", "disabled");

        idBox = $(".key:checked");
        if (idBox.length == 0) {
            alert('??????????????????????????????????????????');
            lock = false;
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
            return;
        }
        idArray = new Array();
        $.each(idBox, function (i, n) {
            idArray.push($(n).val());
        });
        id = idArray.join(",");

        var str = '';
        if (audit == "pass") {
            str = '?????????????????????????????????????????????';
        }
        if (audit == "refuse") {
            str = '?????????????????????????????????????????????';
        }

        if (confirm(str)) {
            $.ajax({
                url: ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=doAudit&batch=1&id=" + id + "&audit=" + audit,
                data: "ajax=1",
                dataType: "json",
                success: function (obj) {
                    // $("#info").html(obj.info);
                    if (obj.status == 1) {
                        alert(obj.info);
                        location.href = location.href;
                    } else {
                        alert(obj.info);
                    }
                    lock = false;
                    $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
                },
                error: function (obj) {
                    if (obj.info) {
                        alert(obj.info);
                    } else {
                        alert('???????????????');
                    }
                    lock = false;
                    $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
                }
            });
        } else {
            lock = false;
            $(btn).css({ "color": "#fff", "background-color": "#4E6A81" }).removeAttr("disabled");
        }
    }


</script>

<div class="main">
<div class="main_title"><?php if(isset($_REQUEST['roll']) and $_REQUEST['roll'] == '1'): ?>??????????????????<?php else: ?>??????????????????<?php endif; ?></div>
<div class="blank5"></div>
<div class="button_row">
    <!--
    <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();" />
    -->
    <?php if(!isset($_REQUEST['loanway']) or $_REQUEST['loanway'] != '2'): ?><input type="button" class="button" value="??????" onclick="waitPass('pass',this);" /><?php endif; ?>
    <input type="button" class="button" value="????????????????????????" onclick="import_rec();" />
    <input type="button" class="button" value="??????" onclick="waitPass('refuse',this);" />
</div>
<?php function get_ucfpay_status($status, $memo = '')
    {
        if ($status == 2 && empty($memo))
        {
            return '????????????';
        }
        else
        {
            return get_withdraw_status($status);
        }
    }
    function get_carry_status($status, $id)
    {
        $str = l("CARRY_STATUS_".$status);
        if($status == 3)
        {
           $str .= " <a target='_blank' href='?m=UserCarry&a=getYbCsv&id=$id'>????????????????????????</a>";
        }
        return $str;
    }

    function get_carray_type($type,$id){
        if($type == 1){
            return '??????';
        }
        if($type == 2){
            return '???????????????';
        }
        if($type == 3){
            return '?????????';
        }
    }

    function get_print_link($param, $item){
        if ($item['deal_id'] && intval($item['type']) === 1) {
            return '<a href="?m=UserCarry&a=print_carry&dealid='.$item["deal_id"].'" target="_blank">??????</a>';
        }else{
            //return "<a target='_blank' href='m.php?m=UserCarry&a=print_user&create_time=".$item['create_time']."&user_id=".$item['user_id']."&id=".$item['id']."' >??????</a>";
            return '';
        }

    }

    //??????????????????
    function get_user_real_name($user_id){
        $user_name =  M("User")->where("id=".$user_id." and is_delete = 0")->getField("real_name");
        if(!$user_name)
            return l("NO_USER");
        else
            return $user_name;
    }

    //??????????????????
    function get_user_money($user_id){
        $money =  M("User")->where("id=".$user_id." and is_delete = 0")->getField("money");
        if($money){
            return format_price($money);
        }else{
            return '';
        }
    }

    function show_withdraw_time($status, $time)
    {
        if(($status == 1 || $status == 2) && $time) {
            return '<br>'.to_date($time);
        } else {
            return '';
        }
    }

    function get_opt($id, $status, $loan_money_type = 0)
    {
        if($status == 0 || $status == 1) {
            // ???????????????+???????????????????????????????????????
            $passHtml = '';
            if ($loan_money_type != 2) {
                $passHtml = "<input class='ts-input' type='button' data-href='m.php?m=UserCarry&a=doAudit&id=$id&audit=pass' onClick=\"return cfm('',this);\" value='??????'/> ";
            }
            return $passHtml . "<input class='ts-input' type='button' data-href='m.php?m=UserCarry&a=doAudit&id=$id&audit=refuse' onClick=\"javascript:modify_carry_new(".$id.")\" value='??????'/>";
        } else {
            return '';
        }
    }

    function get_print_user($user){
        return "<a target='_blank' href='m.php?m=UserCarry&a=print_user&create_time=".$user['create_time']."&user_id=".$user['user_id']."&id=".$user['id']."' >??????</a>";
    } ?>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        <select name="backup" id="backup">
            <option value="0" <?php if(intval($_REQUEST['backup']) == 0): ?>selected="selected"<?php endif; ?>>???3??????</option>
            <option value="1" <?php if($_REQUEST['backup'] == 1): ?>selected="selected"<?php endif; ?>>3?????????</option>
        </select>
        ?????????<input type="text" class="textbox" name="id" value="<?php echo trim($_REQUEST['id']);?>" style="width:100px;" />
        ?????????????????????
        <select name="timeType" id="timeType">
            <option value="update_time_step2" <?php if($_REQUEST['timeType'] == 'update_time_step2'): ?>selected="selected"<?php endif; ?>>??????????????????</option>
            <option value="withdraw_time" <?php if($_REQUEST['timeType'] == 'withdraw_time'): ?>selected="selected"<?php endif; ?>>??????????????????</option>
            <option value="create_time" <?php if($_REQUEST['timeType'] == 'create_time'): ?>selected="selected"<?php endif; ?>>????????????</option>
        </select>
        ?????????<input type="text" class="textbox" id="withdraw_time_start"  name="withdraw_time_start" value="<?php echo trim($_REQUEST['withdraw_time_start']);?>" style="width:150px;" onfocus="return showCalendar('withdraw_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" style="width:120px;" />
        <input type="button" class="button" id="btn_time_start" value="??????" onclick="return showCalendar('withdraw_time_start', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_start');" />
???
                  <input type="text" class="textbox" name="withdraw_time_end" id="withdraw_time_end" value="<?php echo trim($_REQUEST['withdraw_time_end']);?>" style="width:150px;" onfocus="return showCalendar('withdraw_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" style="width:120px;" />
        <input type="button" class="button" id="btn_time_end" value="??????" onclick="return showCalendar('withdraw_time_end', '%Y-%m-%d %H:%M:%S', false, false, 'btn_time_end');" />
        <?php echo L("USER_NAME");?>???<input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" style="width:100px;" />
               ???????????????<input type="text" class="textbox" name="user_num" value="<?php echo trim($_REQUEST['user_num']);?>" style="width:100px;" />
        ?????????<select id="status" name="status">
            <option value=""><?php echo L("ALL");?></option>
            <option value="0" <?php if($_REQUEST['status']!='' && intval($_REQUEST['status']) == 0): ?>selected="selected"<?php endif; ?>><?php echo L("CARRY_STATUS_0");?></option>
            <option value="1" <?php if(intval($_REQUEST['status']) == 1): ?>selected="selected"<?php endif; ?>><?php echo L("CARRY_STATUS_1");?></option>
            <option value="2" <?php if(intval($_REQUEST['status']) == 2): ?>selected="selected"<?php endif; ?>><?php echo L("CARRY_STATUS_2");?></option>
            <option value="3" <?php if(intval($_REQUEST['status']) == 3): ?>selected="selected"<?php endif; ?>><?php echo L("CARRY_STATUS_3");?></option>
            <option value="4" <?php if(intval($_REQUEST['status']) == 4): ?>selected="selected"<?php endif; ?>><?php echo L("CARRY_STATUS_4");?></option>
        </select>
        ???????????????<select id="withdraw_status" name="withdraw_status">
            <option value=""><?php echo L("ALL");?></option>
            <?php if(is_array($withdraw_status)): foreach($withdraw_status as $key=>$withdraw): ?><option value="<?php echo ($key); ?>" <?php if(isset($_REQUEST['withdraw_status']) and $_REQUEST['withdraw_status'] != '' and intval($_REQUEST['withdraw_status']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($withdraw); ?></option><?php endforeach; endif; ?>
        </select>
        ????????????????????????<select id="applyer" name="roll">
            <option value=""><?php echo L("ALL");?></option>
            <?php if(is_array($rolltype)): foreach($rolltype as $key=>$roll): ?><option value="<?php echo ($key); ?>" <?php if(isset($_REQUEST['roll']) and $_REQUEST['roll'] != '' and intval($_REQUEST['roll']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($roll); ?></option><?php endforeach; endif; ?>
        </select>

        ???????????????<input type="text" value="<?php echo ($_REQUEST['deal_name']); ?>" name="deal_name" />
        ???????????????<input type="text" class="textbox" name="project_name" value="<?php echo trim($_REQUEST['project_name']);?>" />
        ???????????????
        <select name="deal_type_id" id='deal_type_id' >
            <option value=""><?php echo L("ALL");?></option>
            <?php if(is_array($deal_type_tree)): foreach($deal_type_tree as $key=>$type_item): ?><option value="<?php echo ($type_item["id"]); ?>" <?php if($type_item['id'] == $_REQUEST['deal_type_id']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item["name"]); ?></option><?php endforeach; endif; ?>
        </select>
        ???????????????
        <select id="loanway" name="loanway">
            <option value=""><?php echo L("ALL");?></option>
            <?php if(is_array($loan_money_type)): foreach($loan_money_type as $key=>$item): ?><option value="<?php echo ($key); ?>" <?php if(isset($_REQUEST['loanway']) and $_REQUEST['loanway'] != '' and intval($_REQUEST['loanway']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; ?>
        </select>
        ???????????????
        <select id="loantype" name="loantype">
            <option value=""><?php echo L("ALL");?></option>
            <?php if(is_array($loantype)): foreach($loantype as $key=>$item): ?><option value="<?php echo ($key); ?>" <?php if(isset($_REQUEST['loantype']) and $_REQUEST['loantype'] != '' and intval($_REQUEST['loantype']) == $key): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; ?>
        </select>
        <br />
        <input type="hidden" value="UserCarry" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <!-- <input type="button" class="button" value="<?php echo L("EXPORT");?>" onclick="location.href = '/m.php?m=UserCarry&a=get_carry_cvs'" />-->
        <input type="button" class="button" value="<?php echo L("EXPORT");?>" onclick="export_cvs()" />
        <input type="button" class="button" value="??????????????????(?????????????????????)" onclick="down_csv();" />
    </form>
</div>
<div class="blank5"></div>
<table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
<tr><td colspan="21" class="topTd" >&nbsp; </td></tr>
    <tr class="row" >
        <th width="8"><input type="checkbox" id="check" onclick="CheckAll('dataTable')"></th>
        <th width="50px"><a href="javascript:sortBy('id','<?php echo ($sort); ?>','UserCarry','index')" title="??????<?php echo L("ID");?>
        <?php echo ($sortType); ?> "><?php echo L("ID");?><?php if(($order)  ==  "id"): ?>
        <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle">
        <?php endif; ?></a>
        </th>
        <th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','UserCarry','index')" title="????????????ID<?php echo ($sortType); ?> ">??????ID<?php if(($order)  ==  "user_id"): ?>
        <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a>
        </th>

        <th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','UserCarry','index')" title="??????<?php echo L("USER_NAME");?>
        <?php echo ($sortType); ?> "><?php echo L("USER_NAME");?><?php if(($order)  ==  "user_id"): ?>
        <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a>
        </th>

        </th>
        <th><a href="javascript:sortBy('user_id','<?php echo ($sort); ?>','UserCarry','index')" title="????????????ID<?php echo ($sortType); ?> ">????????????<?php if(($order)  ==  "user_id"): ?>
        <img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle"><?php endif; ?></a>
        </th>

        <th>????????????</th>
        <th>?????????</th>
        <th>????????????</th>
        <th>????????????</th>
        <th>????????????</th>
        <th width="70">????????????</th>
        <th width="70">????????????</th>
        <th><a href="javascript:sortBy('money','<?php echo ($sort); ?>','UserCarry','index')" title="??????????????????<?php echo ($sortType); ?> ">????????????
        <?php if(($order)  ==  "money"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle">
        <?php endif; ?></a>
        </th>
        <th>????????????</th>
        <th><a href="javascript:sortBy('create_time','<?php echo ($sort); ?>','UserCarry','index')" title="??????????????????<?php echo ($sortType); ?> ">????????????
        <?php if(($order)  ==  "create_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle">
        <?php endif; ?></a>
        </th>
        <th><a href="javascript:sortBy('status','<?php echo ($sort); ?>','UserCarry','index')" title="????????????<?php echo ($sortType); ?> ">??????
        <?php if(($order)  ==  "status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle">
        <?php endif; ?></a></th>
        <th><a href="javascript:sortBy('desc','<?php echo ($sort); ?>','UserCarry','index')" title="????????????<?php echo ($sortType); ?> ">??????
        <?php if(($order)  ==  "desc"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle">
        <?php endif; ?></a>
        </th>
        <th><a href="javascript:sortBy('update_time','<?php echo ($sort); ?>','UserCarry','index')" title="??????????????????<?php echo ($sortType); ?> ">????????????
        <?php if(($order)  ==  "update_time"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle">
        <?php endif; ?></a>
        </th>
        <th><a href="javascript:sortBy('withdraw_status','<?php echo ($sort); ?>','UserCarry','index')" title="??????????????????<?php echo ($sortType); ?> ">????????????
        <?php if(($order)  ==  "withdraw_status"): ?><img src="__TMPL__Common/images/<?php echo ($sortImg); ?>.gif" width="12" height="17" border="0" align="absmiddle">
        <?php endif; ?></a>
        </th>
        <th>????????????</th>
        <th style="width:">??????</th>
    </tr>
<?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$user): ++$i;$mod = ($i % 2 )?>
<tr class="row" >
    <td><input type="checkbox" name="key" class="key" value="<?php echo ($user["id"]); ?>">
        </td>
    <td>&nbsp;<?php echo ($user["id"]); ?></td><td>&nbsp;<?php echo ($user["user_id"]); ?></td>
    <td>&nbsp;<?php echo (get_user_name($user["user_id"])); ?></td>
    <td>&nbsp;<?php echo (numTo32($user["user_id"], $user['user_type'])); ?></td>
    <td>&nbsp;<?php echo (get_user_real_name($user["user_id"])); ?></td>
    <td>&nbsp;<?php echo (get_user_bank_info($user["user_id"], 'card_name')); ?></td>
    <td><?php echo $user['deal_name']; ?></td>
    <td><?php echo $user['project_name']; ?></td>
    <td><?php echo $user['deal_loan_type']; ?></td>
    <td><?php echo $user['loan_money_type_name'] == '???????????????' ? '??????' : ($user['loan_money_type_name'] == '????????????' ? '????????????' : $user['loan_money_type_name']); ?></td>
    <td><?php echo $user['loan_type_name']; ?></td>
    <td>&nbsp;<?php echo (format_price($user["money"])); ?></td>
    <td>&nbsp;<?php echo get_user_money($user["user_id"]); ?></td>
    <td>&nbsp;<?php echo (to_date($user["create_time"])); ?></td>
    <td>&nbsp;<?php echo (get_carry_status($user["status"],$user['id'])); ?></td>
    <td>&nbsp;<?php echo ($user["desc"]); ?></td>
    <td>&nbsp;
    <?php if(intval($user['update_time_step1']) != 0): ?><?php echo ("??????:".to_date($user["update_time_step1"])); ?>
    <br><?php endif; ?>
    <?php if(intval($user['update_time_step2']) != 0): ?><?php echo ("??????:".to_date($user["update_time_step2"])); ?>
    <br><?php endif; ?>
    <?php if(intval($user['pay_process_time']) != 0): ?><?php echo ("??????:".to_date($user["pay_process_time"])); ?><?php endif; ?>
    </td>
    <td>&nbsp;<?php echo get_ucfpay_status($user["withdraw_status"], $user['withdraw_msg']),show_withdraw_time($user["withdraw_status"],$user["withdraw_time"]); ?></td>
    <td>&nbsp;<?php echo $user['warning_stat'] == 1 ? '<span style="color:red;">???</span>' : '???';?></td>
    <td>
        <?php echo (get_opt($user["id"],$user['status'],$user['loan_money_type'])); ?>
        <?php echo (get_print_user($user)); ?>
        <?php if($user['status'] == 3 AND $user['withdraw_status'] == 0): ?><a href="javascript:accelerate('<?php echo ($user["id"]); ?>')">????????????</a><?php endif;?>
        <a href="javascript:modify_carry_new('<?php echo ($user["id"]); ?>',1)">??????</a>
        <?php echo (get_print_link($user[" "],$user)); ?>
        <a href="javascript:import_rdm('<?php echo ($user["id"]); ?>')">??????RDM</a>
        <?php if ($user['can_redo_withdraw']) { ?><a href="javascript:redo_withdraw('<?php echo ($user["id"]); ?>')">????????????</a><?php } //endif ?>
        <?php if ($user['loan_money_type'] == 3) { ?><a href="?m=DealProject&a=editBankInfo&id=<?php echo $user['project_id']; ?>">??????</a><?php } //endif ?>
        <?php if ((!isset($_REQUEST['roll']) OR $_REQUEST['roll'] != '1') AND $user['status'] == 3 AND $user['withdraw_status'] == 0): ?><a href="javascript:cancel_withdraw('<?php echo ($user["id"]); ?>')">????????????</a><?php endif;?>
    </td>
</tr>
    <?php endforeach; endif; else: echo "" ;endif; ?>
<tr>
    <td colspan="21" class="bottomTd">&nbsp; </td>
</tr>
</table>
<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
</div>
<script>
    function edit_fee(obj) {

        var obj_p = obj.parent();
        var fee_num = obj.html().replace("???", "");
        var input_html = '<input type="text" value="' + fee_num + '" old_fee="' + fee_num + '" onkeyup="check_fee($(this))" onblur="input_fee($(this))" size="5"/>???';

        obj_p.html(input_html);
        obj_p.find('input').focus();
    }

    function check_fee(obj) {
        var new_fee = obj.val();
        if (isNaN(new_fee)) {
            new_fee = 0;
        }
        obj.val(new_fee);
    }

    function input_fee(obj) {

        var new_fee = parseFloat(obj.val()).toFixed(2);
        var old_fee = obj.attr('old_fee');
        var obj_p = obj.parent();
        var id = obj_p.attr('cid');

        if (isNaN(new_fee)) {
            new_fee = 0;
        }

        var fee_html = '&nbsp;<span onclick="edit_fee($(this))">' + new_fee + '???</span>';

        if (new_fee == old_fee) {
            obj_p.html(fee_html);
            return;
        }

        if (window.confirm('?????????????????? ' + id + ' ???????????????????????? ' + new_fee + '??? ??????')) {
            url = "/m.php?m=UserCarry&a=edit_fee";
            $.getJSON(url, { old_fee: old_fee, new_fee: new_fee, id: id }, function (data) {
                if (data.status == 0) {
                    $("#info").html(data.data);
                } else {
                    $("#info").html('????????? ' + id + ' ??????????????????????????? ' + new_fee + '???');
                    obj_p.html(fee_html);
                }
            });
        } else {
            obj_p.html('&nbsp;<span onclick="edit_fee($(this))">' + old_fee + '???</span>');
        }
    }

    function get_query_string() {
        var id_str = arguments[0] || 'id';
        querystring = '';
        querystring += "&"+id_str+"="+$("input[name='id']").val();
        querystring += "&withdraw_time_start="+$("input[name='withdraw_time_start']").val();
        querystring += "&withdraw_time_end="+$("input[name='withdraw_time_end']").val();
        querystring += "&user_name="+$("input[name='user_name']").val();
        querystring += "&user_num="+$("input[name='user_num']").val();
        querystring += "&deal_name="+$("input[name='deal_name']").val();
        querystring += "&deal_type_id="+$("#deal_type_id").val();
        querystring += "&timeType="+$("#timeType").val();
        querystring += "&status="+$("#status").val();
        querystring += "&type="+$("#type").val();
        querystring += "&withdraw_status="+$("#withdraw_status").val();
        querystring += "&loanway="+$("#loanway").val();
        querystring += "&loantype="+$("#loantype").val();
        querystring += "&roll="+$("#applyer").val();
        querystring += "&backup="+$("#backup").val();
        querystring += "&project_name="+$("input[name='project_name']").val();


        return querystring;
    }

    function export_cvs() {
        window.location.href = ROOT+'?m=UserCarry&a=get_carry_cvs'+get_query_string();
    }
    function accelerate(id) {
        window.location.href = ROOT+'?m=UserCarry&a=accelerate&id='+id;
    }
    function import_rec() {
        window.location.href = ROOT+'?m=UserCarry&a=import';
    }

    function modify_carry_new(id, view) {
        querystring = "&isView="+view+"&id="+id+get_query_string('search_id');
        $.weeboxs.open(ROOT+'?m=UserCarry&a=edit'+querystring, {contentType:'ajax',showButton:false,title:"??????????????????",width:600,height:400});
    }

    function redo_withdraw(id) {
        if (!window.confirm('?????????????????? ' + id + ' ??????????????????????????? ??????')) {
            return;
        }
        window.location.href = ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=redoWithdraw&id=" + id;
    }
    function cancel_withdraw(id) {
        if (!window.confirm('????????????????????? ' + id + ' ?????????????????????')) {
            return;
        }
        window.location.href = ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=doCancel&id=" + id;
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