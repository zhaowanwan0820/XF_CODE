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

<script type="text/javascript">
    function show_detail(id) {
        $.weeboxs.open(ROOT+'?m=Deal&a=show_detail&id='+id, {contentType:'ajax',showButton:false,title:LANG['COUNT_TOTAL_DEAL'],width:600,height:330});
    }
    function show_adviser_list(id) {
        $.weeboxs.open(ROOT+'?m=Deal&a=show_adviser_list&id='+id, {contentType:'ajax',showButton:false,title:'顾问列表',width:600,height:330});
    }
    function file_operate(id){
        window.location.href = ROOT + '?m=Deal&a=file_operate&id='+id;
    }

    function contract(id){
        window.location.href = ROOT + '?m=Contract&a=index&deal_id='+id;
    }
    function force_repay(id){
        window.location.href = ROOT + '?m=Deal&a=force_repay&deal_id='+id;
    }
    function edit_note(id) {
        $.weeboxs.open(ROOT+'?m=Deal&a=edit_note&id='+id, {contentType:'ajax',showButton:false,title:'备注',width:600,height:300});
    }
    function make_appointment(id){
        window.location.href = ROOT + '?m=Deal&a=make_appointment&deal_id='+id;
    }

    var fuzhilock = false;
    function copy_deal(id, btn) {
        $(btn).css({ "color": "grey" }).attr("disabled", "disabled");
        if (!fuzhilock) {
            fuzhilock = true;
            if (window.confirm('确认复制？\n如果该标有优惠码返利规则，新标也会复制其优惠码返利规则，否则会新标会复制全局优惠码返利规则。')) {
                $.ajax({
                    url: ROOT + "?" + VAR_MODULE + "=" + 'Deal' + "&" + VAR_ACTION + "=copy_deal&id=" + id,
                    data: "ajax=1",
                    dataType: "json",
                    success: function (obj) {
                        fuzhilock = false;
                        $("#info").html(obj.info);

                    }
                });
            }
            fuzhilock = false;
        } else {
            alert("请不要重复点击");
        }
        $(btn).css({ "color": "#4e6a81" }).removeAttr("disabled");
    }



</script>

<div class="main">
<div class="main_title"><?php echo ($main_title); ?><a href="javascript:window.history.go(-1);" class="back_list"><?php echo L("BACK_LIST");?></a></div>
<div class="blank5"></div>
<div class="blank5"></div>
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
        <tr>
            <td colspan="19" class="topTd" >&nbsp; </td>
        </tr>
        <tr class="row">
            <th width="50px   ">
                <a href="javascript:sortBy('id','1','Deal','index')" title="按照编号升序排列 ">
                    编号
                    <img src="/static/admin/Common/images/desc.gif" width="12" height="17"
                    border="0" align="absmiddle">
                </a>
            </th>
            <th>
                <a href="javascript:sortBy('name','1','Deal','index')" title="按照借款标题升序排列 ">
                    借款标题
                </a>
            </th>
            <th>
                旧版借款标题
            </th>
            <th><a href="javascript:void(0)">上标平台</a></th>
            <th><a href="javascript:void(0)">所属队列</a></th>
            <th>
                <a href="javascript:sortBy('borrow_amount','1','Deal','index')">
                    借款金额
                </a>
            </th>
            <th>
                <a href="javascript:sortBy('rate','1','Deal','index')">
                    年化借款利率
                </a>
            </th>
            <th>
                <a href="javascript:sortBy('repay_time','1','Deal','index')">
                    借款期限
                </a>
            </th>
            <th>
                <a href="javascript:sortBy('loantype','1','Deal','index')">
                   还款方式
                </a>
            </th>
            <th>
                用户类型
            </th>
            <th>
                借款人id/
                <a href="javascript:sortBy('user_id','1','Deal','index')" title="按照借款人   升序排列 ">
                    姓名
                </a>/
                <a href="javascript:void(0)">
                    手机
                </a>
            </th>
            <th>
                    放款审批单编号
            </th>
            <th>
                <a href="javascript:sortBy('deal_status','1','Deal','index')" title="按照投资状态   升序排列 ">
                    投资状态
                </a>
            </th>
            <th>
                <a href="javascript:sortBy('is_effect','1','Deal','index')" title="按照状态   升序排列 ">
                    状态
                </a>
            </th>
            <th>
                借款人签署状态
            </th>
            <th>
                担保方签署状态
            </th>
            <th>
                资产管理方签署状态
            </th>
            <th>
                受托方签署状态
            </th>
            <th>
                渠道方签署状态
            </th>
            <th style="width:250px">
                操作
            </th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$deal): ++$i;$mod = ($i % 2 )?><tr class="row">
            <td>
                &nbsp;<?php echo ($deal["id"]); ?>
            </td>
            <td>
                &nbsp;
                <a href="javascript:edit   ('<?php echo ($deal["id"]); ?>')">
                    <?php echo ($deal["name"]); ?>
                </a>
            </td>
            <td>
                &nbsp;<?php echo getOldDealNameWithPrefix($deal['id'], $deal['project_id']);?>
            </td>
            <!-- <td>
                &nbsp;<?php echo (get_deal_cate_name($deal["cate_id"])); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_loan_type_name($deal["type_id"])); ?>
            </td> -->
            <td>
                &nbsp;<?php echo (get_deal_domain($deal["id"],'true')); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_deal_queue($deal["id"])); ?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["borrow_amount"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["rate"]); ?>%
            </td>
            <td>
                &nbsp;<?php echo ($deal["repay_time"]); ?><?php if($deal["loantype"] == 5): ?>天<?php else: ?>个月<?php endif; ?>
            </td>
            <td>
                &nbsp;<?php echo (get_loantype($deal["loantype"])); ?>
            </td>
            <td>
                &nbsp;<?php echo (getUserTypeName($deal["user_id"])); ?>
            </td>
            <td>
                &nbsp;
                <?php echo ($deal['user_id']); ?>/
                <?php echo !empty($listOfBorrower[$deal['user_id']]['company_name']) ? getUserFieldUrl($listOfBorrower[$deal['user_id']], 'company_name') : getUserFieldUrl($listOfBorrower[$deal['user_id']], 'real_name');?>/
                <?php echo (getUserFieldUrl($listOfBorrower[$deal['user_id']],'mobile')); ?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["approve_number"]); ?>
            </td>
            <td>
                &nbsp;<?php echo (a_get_buy_status($deal["deal_status"],$deal.id)); ?>
                <?php if(($deal["deal_status"] == 4) && ($deal["is_has_loans"] == 2)): ?><br />正在放款<?php endif; ?>
                <?php if($deal["is_during_repay"] == 1): ?><br />正在还款<?php endif; ?>
                <?php if(($deal["deal_status"] == 3) && ($deal["is_doing"] == 1)): ?><br />正在流标<?php endif; ?>
            </td>
            <td>
                &nbsp;<?php echo (get_is_effect($deal["is_effect"],$deal[id])); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_deal_contract_status($deal["id"],"0")); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_deal_contract_sign_status($deal["id"],$deal[agency_id])); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_deal_contract_sign_status($deal["id"],$deal[advisory_id])); ?>
            </td>
            <td>
                <?php if($deal["entrust_agency_id"] > 0): ?>&nbsp;<?php echo (get_deal_contract_sign_status($deal["id"],$deal[entrust_agency_id])); ?>
                <?php else: ?>
                 &nbsp;/<?php endif; ?>
            </td>
            <td>
                <?php if($deal["entrust_agency_id"] > 0): ?>&nbsp;<?php echo (get_deal_contract_sign_status($deal["id"],$deal[canal_agency_id])); ?>
                    <?php else: ?>
                    &nbsp;/<?php endif; ?>
            </td>
            <td>
                <a href="javascript:edit('<?php echo ($deal["id"]); ?>')">编辑</a>
                &nbsp;
                <?php if(($deal["deal_status"] == 3) || ($deal["load_money"] == 0)): ?><a href="javascript: del('<?php echo ($deal["id"]); ?>')">删除</a>
                &nbsp;<?php endif; ?>
                <a href="javascript:show_detail('<?php echo ($deal["id"]); ?>')">投资列表</a>
                &nbsp;
                <a href="javascript:show_adviser_list('<?php echo ($deal["id"]); ?>')">顾问列表</a>
                &nbsp;
                <br />
                <!-- <a href="javascript:file_operate('<?php echo ($deal["id"]); ?>')">文件管理</a>
                &nbsp; -->
                <input type="button" class="ts-input"  onclick="copy_deal('<?php echo ($deal["id"]); ?>',this)" value="复制"></input>
                &nbsp;
                <a href="javascript:contract('<?php echo ($deal["id"]); ?>')">合同列表</a>
                &nbsp;
                <?php if(($deal["deal_status"] == 4) && ($deal["parent_id"] != 0)): ?><a  href="javascript:force_repay('<?php echo ($deal["id"]); ?>')">强制还款</a>
                &nbsp;<?php endif; ?>
                <?php if(($deal["deal_status"] == 4) && ($deal["parent_id"] != 0)): ?><a href="javascript:edit_note('<?php echo ($deal["id"]); ?>')">备注</a>
                &nbsp;
                <if condition="($deal.deal_status eq 0) || ($deal.deal_status eq 1)">
                <a href="javascript:make_appointment('<?php echo ($deal["id"]); ?>')">预约投标</a>
                &nbsp;<?php endif; ?>
                <a href="javascript:volid(0)" onclick='open_coupon_list(<?php echo ($deal["id"]); ?>)'>优惠码列表</a>
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