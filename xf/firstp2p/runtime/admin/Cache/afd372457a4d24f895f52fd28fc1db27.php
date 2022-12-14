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
<script type="text/javascript" src="__TMPL__Common/js/input-click.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>

<script type="text/javascript">
    function contract(id){
        window.location.href = ROOT + '?m=Contract&a=index&deal_id='+id;
    }

    function edit(id, role, readonly)
    {
        location.href = ROOT+"?m=Deal&a=lent&id="+id+"&role="+role+"&readonly="+readonly;
    }
</script>

<?php function a_get_deal_type($type,$id)
    {
        $deal = M("Deal")->getById($id);
        if($deal['is_coupon'])
        return l("COUNT_TYPE_".$deal['deal_type']);
        else
        return l("NO_DEAL_COUPON_GEN");

    }

    //function get_project_name($id) {
    //    return $GLOBALS['db']->getOne("SELECT `name` FROM firstp2p_deal_project WHERE `id`='" . $id . "'");
    //}

    function get_real_name($id) {
        return $GLOBALS['db']->getOne("SELECT `real_name` FROM firstp2p_user WHERE `id`='" . $id . "'");
    }

    function get_loan_money_type_by_project_id($id) {
        $loanMoneyType = $GLOBALS['db']->getOne("SELECT `loan_money_type` FROM firstp2p_deal_project WHERE `id`='" . $id . "'");
        if($loanMoneyType == 0 || $loanMoneyType == 1) {
            $result = "????????????";
        } else if($loanMoneyType == 2) {
            $result = "???????????????";
        } else if($loanMoneyType == 3) {
            $result = "????????????";
        }
        return $result;
    } ?>
<div class="main">
    <div class="main_title"><?php if($role == 'b'): ?>???????????????<?php else: ?>???????????????<?php endif; ?></div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        ?????????<input type="text" class="textbox" name="deal_id" value="<?php echo trim($_REQUEST['deal_id']);?>" style="width:100px;" />

        ???????????????<input type="text" class="textbox" name="name" value="<?php echo trim($_REQUEST['name']);?>" />
        ???????????????<input type="text" class="textbox" name="project_name" value="<?php echo trim($_REQUEST['project_name']);?>" />
        ??????????????????
        <input type="text" class="textbox" name="real_name" value="<?php echo trim($_REQUEST['real_name']);?>" size="10" />

        ?????????????????????
        <input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" size="10"/>

        ???????????????
        <select name="loan_money_type">
            <option value="0" selected="selected">=?????????=</option>
            <option value="1" <?php if($_REQUEST['loan_money_type'] == 1): ?>selected<?php endif; ?>>????????????</option>
            <option value="2" <?php if($_REQUEST['loan_money_type'] == 2): ?>selected<?php endif; ?>>???????????????</option>
            <option value="3" <?php if($_REQUEST['loan_money_type'] == 3): ?>selected<?php endif; ?>>????????????</option>
        </select>

        ??????/??????I?????????
        <select name="agency_id">
            <option value="0" <?php if($_REQUEST['agency_id'] == 0): ?>selected<?php endif; ?>>?????????</option>
            <?php if(is_array($deal_agency_list)): $i = 0; $__LIST__ = $deal_agency_list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$i;$mod = ($i % 2 )?><option value="<?php echo ($item["id"]); ?>" <?php if($_REQUEST['agency_id'] == $item['id']): ?>selected<?php endif; ?>><?php echo ($item['short_name']); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
        </select>

        ???????????????
        <select name="loan_type">
            <option value="" <?php if($_REQUEST['loan_type'] == -1 ): ?>selected<?php endif; ?>>?????????</option>
            <?php if(is_array($loan_types)): $i = 0; $__LIST__ = $loan_types;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$i;$mod = ($i % 2 )?><option value="<?php echo ($key); ?>" <?php if($_REQUEST['loan_type'] == $key): ?>selected<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
        </select>

        <br/>
        ???????????????
            <input type="text" class="textbox" style="width:140px;" name="success_time_start" id="success_time_start" value="<?php echo ($_REQUEST['success_time_start']); ?>" onfocus="this.blur(); return showCalendar('success_time_start', '%Y-%m-%d 00:00:00', false, false, 'btn_success_time_start');" title="??????????????????" />
            <input type="button" class="button" id="btn_success_time_start" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('success_time_start', '%Y-%m-%d %H:%M:00', false, false, 'btn_success_time_start');" />
            ???
            <input type="text" class="textbox" style="width:140px;" name="success_time_end" id="success_time_end" value="<?php echo ($_REQUEST['success_time_end']); ?>" onfocus="this.blur(); return showCalendar('success_time_end', '%Y-%m-%d 23:59:59', false, false, 'btn_success_time_end');" title="??????????????????" />
            <input type="button" class="button" id="btn_success_time_end" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('success_time_end', '%Y-%m-%d %H:%M:59', false, false, 'btn_success_time_end');" />

        <br/>
        ??????????????????????????????
        <select name="sign_borrow">
            <option value="0" <?php if($_REQUEST['sign_borrow'] == 0): ?>selected<?php endif; ?>>??????</option>
            <option value="1" <?php if($_REQUEST['sign_borrow'] == 1): ?>selected<?php endif; ?>>??????</option>
            <option value="2" <?php if($_REQUEST['sign_borrow'] == 2): ?>selected<?php endif; ?>>??????</option>
        </select>
        ??????????????????????????????
        <select name="sign_agency">
            <option value="0" <?php if($_REQUEST['sign_agency'] == 0): ?>selected<?php endif; ?>>??????</option>
            <option value="1" <?php if($_REQUEST['sign_agency'] == 1): ?>selected<?php endif; ?>>??????</option>
            <option value="2" <?php if($_REQUEST['sign_agency'] == 2): ?>selected<?php endif; ?>>??????</option>
        </select>
        ????????????????????????????????????
        <select name="sign_advisory">
            <option value="0" <?php if($_REQUEST['sign_advisory'] == 0): ?>selected<?php endif; ?>>??????</option>
            <option value="1" <?php if($_REQUEST['sign_advisory'] == 1): ?>selected<?php endif; ?>>??????</option>
            <option value="2" <?php if($_REQUEST['sign_advisory'] == 2): ?>selected<?php endif; ?>>??????</option>
        </select>

        <?php if($role != 'b'): ?>???????????????
        <select name="audit_status">
            <option value="9999" <?php if($_REQUEST['audit_status'] == 9999): ?>selected<?php endif; ?>>?????????</option>
            <?php if(is_array($audit_status_list)): foreach($audit_status_list as $key=>$item): ?><option value="<?php echo ($key); ?>" <?php if($_REQUEST['audit_status'] == $key): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; ?>
        </select>
        <?php else: ?>
            ?????????:
            <input type="text" class="textbox" name="admin_name" value="<?php echo ($_REQUEST['admin_name']); ?>" size="10"/><?php endif; ?>
        ???????????????
        <select name="deal_type">
            <?php if(!$is_cn): ?><option value="2" <?php if($_REQUEST['deal_type'] == 2): ?>selected<?php endif; ?>>?????????</option>
            <option value="3" <?php if($_REQUEST['deal_type'] == 3): ?>selected<?php endif; ?>>??????</option>
            <option value="5" <?php if($_REQUEST['deal_type'] == 5): ?>selected<?php endif; ?>>??????</option><?php endif; ?>
        </select>
        <input type="hidden" id="page_now" value="<?php echo ($_GET["p"]); ?>" name="p" />
        <input type="hidden" value="DealLoan" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="hidden" value="<?php echo ($role); ?>" name="role" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" class="button" value="??????" onclick="export_csv();" />
        <?php if($_REQUEST['role'] == 'b'): ?><input type="button" class="button" value="????????????" id="batch_submit" />
        <?php else: ?>
            <input type="button" class="button" value="????????????" id="batch_submit" /><?php endif; ?>

    </form>
</div>
<div class="blank5"></div>
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
        <tr>
            <td colspan="22" class="topTd" >&nbsp; </td>
        </tr>
        <tr class="row">
            <th width="8">
                <input type="checkbox" id="check" onclick="CheckAll('dataTable')">
            </th>
            <th>??????</th>
            <th>????????????</th>
            <th>??????????????????</th>
            <th>????????????</th>
            <th>????????????</th>
            <th>????????????</th>
            <th>????????????</th>
            <th>????????????</th>
            <th>????????????</th>
            <th>??????????????????</th>
            <th>????????????</th>
            <th>????????????</th>
            <th>????????????</th>
            <th>????????????</th>
            <th>???????????????</th>
            <th>??????????????????</th>
            <th>??????/??????I????????????</th>
            <!--
            <th>???????????????????????????</th>
            <th>????????????????????????????????????</th>
              -->
            <th>?????????????????????</th>
            <!-- <th>???????????????????????????</th>
            <th>????????????????????????????????????</th> -->
            <th>?????????????????????</th>
            <!-- <th>?????????????????????????????????</th>
            <th>??????????????????????????????????????????</th> -->
            <th>???????????????????????????</th>
            <th>?????????????????????</th>
            <?php if($role == b): ?><th>?????????</th><?php endif; ?>
            <th>????????????</th>
            <th style="width:150px">
                ??????
            </th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$deal): ++$i;$mod = ($i % 2 )?><tr class="row">
            <td>
                <input type="checkbox" name="key" class="key" value="<?php echo ($deal["id"]); ?>">
            </td>
            <td>
                &nbsp;<?php echo ($deal["id"]); ?>
            </td>
            <td>
                &nbsp;
                <a href="javascript:edit('<?php echo ($deal["id"]); ?>')">
                    <?php echo ($deal["name"]); ?>
                </a>
            </td>
            <td>
                &nbsp;<?php echo getOldDealNameWithPrefix($deal['id'], $deal['project_id']);?>
            </td>
            <td>
                &nbsp;<?php echo (get_project_name($deal["project_id"])); ?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["borrow_amount"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["repay_time"]); ?><?php if($deal["loantype"] == 5): ?>???<?php else: ?>??????<?php endif; ?>
            </td>
            <td>
                &nbsp;<?php echo (get_loantype($deal["loantype"])); ?>
            </td>
            <td>&nbsp;??????</td>
            <td>
                &nbsp;<?php echo (to_date($deal["success_time"])); ?>
            </td>
            <td>
                <?php echo (get_deal_ext_fee_type($deal["id"])); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_loan_money_type_by_project_id($deal["project_id"])); ?>
            </td>
            <td>
            <?php echo ($deal["clearing_type_name"]); ?>
            </td>
            <td>
                <?php echo ($loan_types[$deal['loan_type']]); ?>
            </td>
            <td>
                &nbsp;<?php echo (getUserTypeName($deal["user_id"])); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_real_name($deal["user_id"])); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_user_name($deal["user_id"])); ?>
            </td>
            <td>
                &nbsp;<?php echo ($deal_agency_list[$deal['agency_id']]['short_name']); ?>
            </td>
            <!-- <td>
                &nbsp;<?php echo (get_project_entrust_sign($deal["project_id"],'entrust_sign')); ?>
            </td>
            <td>
                &nbsp;<?php echo get_entrustor_name($deal['id'], $deal['user_id']);?>
            </td> -->
            <td>
                &nbsp;<?php echo (get_deal_contract_status($deal["id"],"0")); ?>
            </td>
            <!-- <td>
                &nbsp;<?php echo (get_project_entrust_sign($deal["project_id"],'entrust_agency_sign')); ?>
            </td>
            <td>
                &nbsp;<?php echo get_entrustor_name($deal['id'], 0, $deal[agency_id]);?>
            </td> -->
            <td>
                &nbsp;<?php echo (get_deal_contract_sign_status($deal["id"],$deal[agency_id])); ?>
            </td>
            <!-- <td>
                &nbsp;<?php echo (get_project_entrust_sign($deal["project_id"],'entrust_advisory_sign')); ?>
            </td>
            <td>
                &nbsp;<?php echo get_entrustor_name($deal['id'], 0, $deal[advisory_id]);?>
            </td> -->
            <td>
                &nbsp;<?php echo (get_deal_contract_sign_status($deal["id"],$deal[advisory_id])); ?>
            </td>
            <td>
                <?php if($deal["entrust_agency_id"] > 0): ?>&nbsp;<?php echo (get_deal_contract_sign_status($deal["id"],$deal[entrust_agency_id])); ?>
                <?php else: ?>
                &nbsp;/<?php endif; ?>
            </td>
            <?php if($role == b): ?><td>
                <?php if(isset($audit_deal_list[$deal['id']])): ?>&nbsp;<?php echo ($audit_deal_list[$deal['id']]['submit_user_name']); ?><?php endif; ?>
            </td><?php endif; ?>
            <td>
                    <?php if($role == b): ?>&nbsp;???????????????
                    <?php else: ?>
                        <?php if(isset($audit_deal_list[$deal['id']])): ?>&nbsp;<?php echo ($audit_deal_list[$deal['id']]['status']); ?>
                        <?php else: ?>
                            &nbsp;???????????????<?php endif; ?><?php endif; ?>
            </td>

            <td>
                <?php if($deal["is_entrust_zx"] != 1): ?><?php if($role == 'b'): ?><a href="javascript:edit('<?php echo ($deal["id"]); ?>','b','0')">????????????</a>
                <?php else: ?>
                    <?php if($audit_deal_list[$deal['id']]['status'] == '???????????????'): ?><!--<a href="javascript:edit('<?php echo ($deal["id"]); ?>', 'a', '0')">?????????</a>-->
                    <?php else: ?>
                        <a href="javascript:edit('<?php echo ($deal["id"]); ?>', 'a', '0')">????????????</a><?php endif; ?><?php endif; ?>
                &nbsp;
                <a href="javascript:contract('<?php echo ($deal["id"]); ?>')">????????????</a><?php endif; ?>
            </td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>

    <div class="blank5"></div>
    <div class="page"><?php echo ($page); ?></div>
</div>
<script>
function open_coupon_list(id) {
    window.location.href=ROOT+'?m=CouponLog&a=index&deal_id='+id;
}
    $(function(){
        $("#batch_submit").click(function(){
            if($("input[name='key']:checked").length <=0) {
                alert('???????????????????????????');
                return false;
            }

            deal_ids = new Array;
            $($("input[name='key']:checked").each(function(){
                deal_ids.push($(this).val());
            }));

            var act = "<?php echo ($role); ?>";
            url = (act == 'b') ? 'm.php?m=Deal&a=batch_qnqueue' : 'm.php?m=Deal&a=batch_submit';

            $.ajax({
                type:"POST",
                url:url,
                dataType:'json',
                data:{
                    "deal_ids":deal_ids.join(","),
                },
                success:function(res){
                    if(res.status == 1){
                        if('' !=  res.fail_batch_info) {
                            alert(res.fail_batch_info);
                        }
                        alert('????????????' + res.succ_num +'??????????????????' + res.fail_num + '?????????id???'+res.deal_ids);
                        location.reload();
                    }else{
                        alert('????????????');
                    }
                }
            });

        });
    })
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