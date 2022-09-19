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
            $result = "实际放款";
        } else if($loanMoneyType == 2) {
            $result = "非实际放款";
        } else if($loanMoneyType == 3) {
            $result = "受托支付";
        }
        return $result;
    } ?>
<div class="main">
    <div class="main_title"><?php if($role == 'b'): ?>待审核列表<?php else: ?>待放款列表<?php endif; ?></div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        编号：<input type="text" class="textbox" name="deal_id" value="<?php echo trim($_REQUEST['deal_id']);?>" style="width:100px;" />

        借款标题：<input type="text" class="textbox" name="name" value="<?php echo trim($_REQUEST['name']);?>" />
        项目名称：<input type="text" class="textbox" name="project_name" value="<?php echo trim($_REQUEST['project_name']);?>" />
        借款人姓名：
        <input type="text" class="textbox" name="real_name" value="<?php echo trim($_REQUEST['real_name']);?>" size="10" />

        借款人用户名：
        <input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" size="10"/>

        放款方式：
        <select name="loan_money_type">
            <option value="0" selected="selected">=请选择=</option>
            <option value="1" <?php if($_REQUEST['loan_money_type'] == 1): ?>selected<?php endif; ?>>实际放款</option>
            <option value="2" <?php if($_REQUEST['loan_money_type'] == 2): ?>selected<?php endif; ?>>非实际放款</option>
            <option value="3" <?php if($_REQUEST['loan_money_type'] == 3): ?>selected<?php endif; ?>>受托支付</option>
        </select>

        担保/代偿I机构：
        <select name="agency_id">
            <option value="0" <?php if($_REQUEST['agency_id'] == 0): ?>selected<?php endif; ?>>请选择</option>
            <?php if(is_array($deal_agency_list)): $i = 0; $__LIST__ = $deal_agency_list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$i;$mod = ($i % 2 )?><option value="<?php echo ($item["id"]); ?>" <?php if($_REQUEST['agency_id'] == $item['id']): ?>selected<?php endif; ?>><?php echo ($item['short_name']); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
        </select>

        放款类型：
        <select name="loan_type">
            <option value="" <?php if($_REQUEST['loan_type'] == -1 ): ?>selected<?php endif; ?>>请选择</option>
            <?php if(is_array($loan_types)): $i = 0; $__LIST__ = $loan_types;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): ++$i;$mod = ($i % 2 )?><option value="<?php echo ($key); ?>" <?php if($_REQUEST['loan_type'] == $key): ?>selected<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
        </select>

        <br/>
        满标时间：
            <input type="text" class="textbox" style="width:140px;" name="success_time_start" id="success_time_start" value="<?php echo ($_REQUEST['success_time_start']); ?>" onfocus="this.blur(); return showCalendar('success_time_start', '%Y-%m-%d 00:00:00', false, false, 'btn_success_time_start');" title="满标时间开始" />
            <input type="button" class="button" id="btn_success_time_start" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('success_time_start', '%Y-%m-%d %H:%M:00', false, false, 'btn_success_time_start');" />
            到
            <input type="text" class="textbox" style="width:140px;" name="success_time_end" id="success_time_end" value="<?php echo ($_REQUEST['success_time_end']); ?>" onfocus="this.blur(); return showCalendar('success_time_end', '%Y-%m-%d 23:59:59', false, false, 'btn_success_time_end');" title="满标时间结束" />
            <input type="button" class="button" id="btn_success_time_end" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('success_time_end', '%Y-%m-%d %H:%M:59', false, false, 'btn_success_time_end');" />

        <br/>
        借款人是否签署合同：
        <select name="sign_borrow">
            <option value="0" <?php if($_REQUEST['sign_borrow'] == 0): ?>selected<?php endif; ?>>全部</option>
            <option value="1" <?php if($_REQUEST['sign_borrow'] == 1): ?>selected<?php endif; ?>>已签</option>
            <option value="2" <?php if($_REQUEST['sign_borrow'] == 2): ?>selected<?php endif; ?>>未签</option>
        </select>
        担保方是否签署合同：
        <select name="sign_agency">
            <option value="0" <?php if($_REQUEST['sign_agency'] == 0): ?>selected<?php endif; ?>>全部</option>
            <option value="1" <?php if($_REQUEST['sign_agency'] == 1): ?>selected<?php endif; ?>>已签</option>
            <option value="2" <?php if($_REQUEST['sign_agency'] == 2): ?>selected<?php endif; ?>>未签</option>
        </select>
        资产管理方是否签署合同：
        <select name="sign_advisory">
            <option value="0" <?php if($_REQUEST['sign_advisory'] == 0): ?>selected<?php endif; ?>>全部</option>
            <option value="1" <?php if($_REQUEST['sign_advisory'] == 1): ?>selected<?php endif; ?>>已签</option>
            <option value="2" <?php if($_REQUEST['sign_advisory'] == 2): ?>selected<?php endif; ?>>未签</option>
        </select>

        <?php if($role != 'b'): ?>审核状态：
        <select name="audit_status">
            <option value="9999" <?php if($_REQUEST['audit_status'] == 9999): ?>selected<?php endif; ?>>请选择</option>
            <?php if(is_array($audit_status_list)): foreach($audit_status_list as $key=>$item): ?><option value="<?php echo ($key); ?>" <?php if($_REQUEST['audit_status'] == $key): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option><?php endforeach; endif; ?>
        </select>
        <?php else: ?>
            申请人:
            <input type="text" class="textbox" name="admin_name" value="<?php echo ($_REQUEST['admin_name']); ?>" size="10"/><?php endif; ?>
        贷款类型：
        <select name="deal_type">
            <?php if(!$is_cn): ?><option value="2" <?php if($_REQUEST['deal_type'] == 2): ?>selected<?php endif; ?>>交易所</option>
            <option value="3" <?php if($_REQUEST['deal_type'] == 3): ?>selected<?php endif; ?>>专享</option>
            <option value="5" <?php if($_REQUEST['deal_type'] == 5): ?>selected<?php endif; ?>>小贷</option><?php endif; ?>
        </select>
        <input type="hidden" id="page_now" value="<?php echo ($_GET["p"]); ?>" name="p" />
        <input type="hidden" value="DealLoan" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="hidden" value="<?php echo ($role); ?>" name="role" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" class="button" value="导出" onclick="export_csv();" />
        <?php if($_REQUEST['role'] == 'b'): ?><input type="button" class="button" value="一键放款" id="batch_submit" />
        <?php else: ?>
            <input type="button" class="button" value="一键提交" id="batch_submit" /><?php endif; ?>

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
            <th>编号</th>
            <th>借款标题</th>
            <th>旧版借款标题</th>
            <th>项目名称</th>
            <th>借款金额</th>
            <th>借款期限</th>
            <th>还款方式</th>
            <th>标的状态</th>
            <th>满标时间</th>
            <th>费用收取方式</th>
            <th>放款方式</th>
            <th>结算方式</th>
            <th>放款类型</th>
            <th>用户类型</th>
            <th>借款人姓名</th>
            <th>借款人用户名</th>
            <th>担保/代偿I机构名称</th>
            <!--
            <th>借款人合同委托签署</th>
            <th>借款人合同委托签署代理人</th>
              -->
            <th>借款人签署状态</th>
            <!-- <th>担保方合同委托签署</th>
            <th>担保方合同委托签署代理人</th> -->
            <th>担保方签署状态</th>
            <!-- <th>资产管理方合同委托签署</th>
            <th>资产管理方合同委托签署代理人</th> -->
            <th>资产管理方签署状态</th>
            <th>受托方签署状态</th>
            <?php if($role == b): ?><th>申请人</th><?php endif; ?>
            <th>审核状态</th>
            <th style="width:150px">
                操作
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
                &nbsp;<?php echo ($deal["repay_time"]); ?><?php if($deal["loantype"] == 5): ?>天<?php else: ?>个月<?php endif; ?>
            </td>
            <td>
                &nbsp;<?php echo (get_loantype($deal["loantype"])); ?>
            </td>
            <td>&nbsp;满标</td>
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
                    <?php if($role == b): ?>&nbsp;放款待审核
                    <?php else: ?>
                        <?php if(isset($audit_deal_list[$deal['id']])): ?>&nbsp;<?php echo ($audit_deal_list[$deal['id']]['status']); ?>
                        <?php else: ?>
                            &nbsp;放款待处理<?php endif; ?><?php endif; ?>
            </td>

            <td>
                <?php if($deal["is_entrust_zx"] != 1): ?><?php if($role == 'b'): ?><a href="javascript:edit('<?php echo ($deal["id"]); ?>','b','0')">操作放款</a>
                <?php else: ?>
                    <?php if($audit_deal_list[$deal['id']]['status'] == '放款待审核'): ?><!--<a href="javascript:edit('<?php echo ($deal["id"]); ?>', 'a', '0')">审核中</a>-->
                    <?php else: ?>
                        <a href="javascript:edit('<?php echo ($deal["id"]); ?>', 'a', '0')">操作放款</a><?php endif; ?><?php endif; ?>
                &nbsp;
                <a href="javascript:contract('<?php echo ($deal["id"]); ?>')">合同列表</a><?php endif; ?>
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
                alert('请至少选择一个标的');
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
                        alert('提交成功' + res.succ_num +'笔；提交失败' + res.fail_num + '笔，标id为'+res.deal_ids);
                        location.reload();
                    }else{
                        alert('提交失败');
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