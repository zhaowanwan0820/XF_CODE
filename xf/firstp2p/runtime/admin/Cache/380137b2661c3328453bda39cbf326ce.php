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

<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>

<?php function subtraction($num1,$num2) {
    $num = $num1 - $num2;
    $num = format_price($num,false);
    return $num;
}
function get_rate($rate) {
    return $rate.'%';
}
function get_link($link){
    return "<a href='$link' target='_blank'>链接</a>";
} ?>
<style>
 a {
     white-space:nowrap;
 }
</style>

<div class="main">
<div class="main_title">项目列表</div>
<div class="blank5"></div>
<div class="button_row">
    <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();" />
    <input type="button" class="button" value="<?php echo L("DELETE");?>" onclick="del();" />
    <?php if($isSvDown): ?><input type="button" class="button" value="将掌众标的置为无效" onclick="setZhangzhongInvalid()" /><?php endif; ?>
</div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        项目编号：<input type="text" class="textbox" name="pro_id" value="<?php echo trim($_REQUEST['pro_id']);?>" size="8"/>
        项目名称：<input type="text" class="textbox" name="pro_name" value="<?php echo trim($_REQUEST['pro_name']);?>" size="8"/>
        借款人会员ID：<input type="text" class="textbox" name="user_id" value="<?php echo trim($_REQUEST['user_id']);?>" size="8"/>
        借款人会员名称：<input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" size="8"/>
        借款人姓名：<input type="text" class="textbox" name="real_name" value="<?php echo trim($_REQUEST['real_name']);?>" size="8"/>
        放款审批单编号：<input type="text" class="textbox" name="approve_number" value="<?php echo trim($_REQUEST['approve_number']);?>" size="10"/>
        贷款类型：
        <select name="deal_type">
            <option value="0" <?php if($_REQUEST['deal_type'] == 0): ?>selected<?php endif; ?>>网贷</option>
            <?php if(!$is_cn): ?><option value="2" <?php if($_REQUEST['deal_type'] == 2): ?>selected<?php endif; ?>>交易所</option>
            <option value="3" <?php if($_REQUEST['deal_type'] == 3): ?>selected<?php endif; ?>>专享</option>
            <option value="5" <?php if($_REQUEST['deal_type'] == 5): ?>selected<?php endif; ?>>小贷</option><?php endif; ?>
        </select>
        业务状态：
        <select name="business_status">
            <option value="999" <?php if($_REQUEST['business_status'] == 999): ?>selected="selected"<?php endif; ?>>全部</option>
            <?php if(is_array($project_business_status)): foreach($project_business_status as $status_value=>$status_name): ?><option value="<?php echo ($status_value); ?>" <?php if($_REQUEST['business_status'] == $status_value): ?>selected="selected"<?php endif; ?>><?php echo ($status_name); ?></option><?php endforeach; endif; ?>
        </select>
        固定起息日：
            <input type="text" class="textbox" style="width:140px;" name="fixed_value_date_start" id="fixed_value_date_start" value="<?php echo ($_REQUEST['fixed_value_date_start']); ?>" onfocus="this.blur(); return showCalendar('fixed_value_date_start', '%Y-%m-%d', false, false, 'btn_fixed_value_date_start');" title="固定起息日开始" />
            <input type="button" class="button" id="btn_fixed_value_date_start" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('fixed_value_date_start', '%Y-%m-%d', false, false, 'btn_fixed_value_date_start');" />
            到
            <input type="text" class="textbox" style="width:140px;" name="fixed_value_date_end" id="fixed_value_date_end" value="<?php echo ($_REQUEST['fixed_value_date_end']); ?>" onfocus="this.blur(); return showCalendar('fixed_value_date_end', '%Y-%m-%d', false, false, 'btn_fixed_value_date_end');" title="固定起息日结束" />
            <input type="button" class="button" id="btn_fixed_value_date_end" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('fixed_value_date_end', '%Y-%m-%d', false, false, 'btn_fixed_value_date_end');" />
        <input type="hidden" value="DealProject" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" class="button" value="导出" onclick="export_csv_file('');" />
    </form>
</div>
<div class="blank5"></div>
    <table id="dataTable" class="dataTable" cellpadding=0 cellspacing=0 >
        <tr>
            <td colspan="19" class="topTd" >&nbsp; </td>
        </tr>
        <tr class="row">
            <th width="8">
                <input type="checkbox" id="check" onclick="CheckAll('dataTable')">
            </th>
            <th width="50px">
                <a href="javascript:sortBy('id','1','DealProject','index')" title="按照编号升序排列 ">
                    编号
                    <img src="/static/admin/Common/images/desc.gif" width="12" height="17"
                    border="0" align="absmiddle">
                </a>
            </th>
            <th>
                <a href="javascript:sortBy('name','1','DealProject','index')" title="按照借款标题升序排列 ">
                    项目名称
                </a>
            </th>
            <th>
                期限
            </th>
            <th>
                还款方式
            </th>
            <th>
                借款综合成本（年化）
            </th>
            <th>
                用户类型
            </th>
            <th>
                借款人会员ID
            </th>
            <th>
                借款人会员名称
            </th>
            <th>
                借款人姓名
            </th>
            <th>
                借款总额
            </th>
            <th>
                已上标金额
            </th>
            <th>
                待上标金额
            </th>
            <th>
                已投资金额
            </th>
            <th>
                差额
            </th>
            <th>
                放款审批单编号
            </th>
            <th>
                项目授信额度
            </th>
            <th>
                固定起息日
            </th>
            <th>
                借款人合同委托签署
            </th>
            <th>
                担保方合同委托签署
            </th>
            <th>
                资产管理方合同委托签署
            </th>
            <th>
                项目简介
            </th>
            <th>
                项目要素
            </th>
            <th>
                业务状态
            </th>
            <th>
                状态
            </th>
            <th style="width:250px">
                操作
            </th>

        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$project): ++$i;$mod = ($i % 2 )?><tr class="row">
            <td>
                <input type="checkbox" name="key" class="key" value="<?php echo ($project["id"]); ?>" />
            </td>
            <td>
                &nbsp;<?php echo ($project["id"]); ?>
            </td>
            <td>
                &nbsp;
                <a href="javascript:view('<?php echo ($project["id"]); ?>')">
                    <?php echo ($project["name"]); ?>
                </a>
            </td>
            <td>
                &nbsp;<?php echo getRepayTime($project['repay_time'], $project['loantype']);?>
            </td>
            <td>
                &nbsp;<?php echo (get_loantype($project["loantype"])); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_rate($project["rate"])); ?>
            </td>
            <td>
                &nbsp;<?php echo ((getUserTypeName($project["user_id"],0))?(getUserTypeName($project["user_id"],0)):''); ?>
            </td>
            <td>
                &nbsp;<?php echo ($project["user_id"]); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_user_name($project["user_id"])); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_user_name($project["user_id"],'real_name')); ?>
            </td>
            <td>
                &nbsp;<?php echo (format_price($project["borrow_amount"],false)); ?>
            </td>
            <td>
                &nbsp;<?php echo (format_price($project["money_borrowed"],false)); ?>
            </td>
            <td>
                &nbsp;<?php echo (subtraction($project["borrow_amount"],$project['money_borrowed'])); ?>
            </td>
            <td>
                &nbsp;<?php echo (format_price($project["money_loaned"],false)); ?>
            </td>
            <td>
                &nbsp;<?php echo (format_price($project["diff"],false)); ?>
            </td>
            <td>
                &nbsp;<?php echo ($project["approve_number"]); ?>
            </td>
            <td>
                &nbsp;<?php echo (format_price($project["credit"],false)); ?>
            </td>
            <td>
                &nbsp;<?php echo (to_date($project["fixed_value_date"],'Y-m-d')); ?>
            </td>
            <td>
                &nbsp;<?php echo ($project["entrust_sign"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($project["entrust_agency_sign"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($project["entrust_advisory_sign"]); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_link($project["project_info_url"])); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_link($project["project_extrainfo_url"])); ?>
            </td>
            <td>
                <?php if($project['is_entrust_zx']): ?>&nbsp;<?php echo (getProjectBusinessStatusNameByValue($project["business_status"])); ?>
                <?php else: ?>
                    --<?php endif; ?>
            </td>
            <td>
                &nbsp;<?php echo ($project["status"]); ?>
            </td>
            <td>
                <a href="javascript:add_deal(<?php echo ($project["id"]); ?>)">上标</a>&nbsp;
                <a href="javascript:view(<?php echo ($project["id"]); ?>)">查看</a>&nbsp;
                <a href="javascript:edit(<?php echo ($project["id"]); ?>)">编辑</a>&nbsp;
                <a href="javascript:del(<?php echo ($project["id"]); ?>)">彻底删除</a>&nbsp;
                <a href="javascript:copy(<?php echo ($project["id"]); ?>)">复制</a>&nbsp;
                <a href="javascript:show_deals(<?php echo ($project["id"]); ?>)">标的列表</a>&nbsp;
                <?php if( $project['business_status'] >= 3): ?><a href="javascript:show_contract(<?php echo ($project["id"]); ?>)">合同列表</a><br />
                    <?php if( $project['business_status'] == 5): ?><a href="javascript:show_loan_log('<?php echo ($project["name"]); ?>')">标的放款操作记录</a><br /><?php endif; ?>
                    <?php if( $project['business_status'] > 6): ?><a href="javascript:show_repay_log('<?php echo ($project["id"]); ?>')">标的还款操作记录</a><br /><?php endif; ?><?php endif; ?>
            </td>

        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>

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


<script type="text/javascript">
    function del(id) {

        if(!id) {

            idBox = $(".key:checked");
            if(idBox.length == 0)
            {
                alert(LANG['DELETE_EMPTY_WARNING']);
                return;
            }
            idArray = new Array();
            $.each( idBox, function(i, n){
                idArray.push($(n).val());
            });
            id = idArray.join(",");
        }
        if(confirm(LANG['CONFIRM_DELETE'])){
            //判断该项目是否有子标  如没有才能删除
            var is_submit = 0;
            $.ajax({
                url:ROOT+"?"+VAR_MODULE+"=DealProject&"+VAR_ACTION+"=checkSave&id="+id,
                dataType:"json",
                async:false,
                success:function(rs){
                    if(rs.status ==1)
                    {
                        if(rs.data.sum > 0) {
                            //alert('项目id:'+id+'下有子标，不能直接删除！');
                            alert('该项目下有子标，不能直接删除！');
                            return;
                        }
                        is_submit = 1;
                    }else{
                        is_submit = 0;
                    }
                }
            });

            if(is_submit == 1) {
                $.ajax({
                    url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=delete&id="+id,
                    data: "ajax=1",
                    dataType: "json",
                    success: function(obj){
                        $("#info").html(obj.info);
                        if(obj.status==1) {
                            location.href=location.href;
                        }
                    }
                });
            }


        }
    }

    function add_deal(id) {
        location.href = ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=add&proid="+id;
    }
    function view(id) {
        location.href = ROOT+"?"+VAR_MODULE+"=DealProject&"+VAR_ACTION+"=view&id="+id;
    }

    function copy(id) {
        $.ajax({
            url: ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=copy&id=" + id,
            data: "ajax=1",
            dataType: "json",
            success: function (obj) {
                $("#info").html(obj.info);
            }
        });
    }

    // csv导出
    function export_csv_file()
    {
        var confirm_msg = "\n\r大数据量请增加筛选条件缩小结果集条数，以免导出失败";
        confirm_msg = "确认要导出csv文件数据吗？" + confirm_msg + "\n\r导出过程中请耐心等待，不要关闭页面。";
        if (!confirm(confirm_msg)) {
            return;
        }
        return export_csv();
    }

    function show_contract(id) {
        location.href = ROOT+"?"+VAR_MODULE+"=ProjectContract&"+VAR_ACTION+"=index&project_id="+id;
    }

    function show_deals(id) {
        location.href = ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=deals&project_id="+id;
    }

    function show_loan_log(name) {
        location.href = ROOT+"?"+VAR_MODULE+"=LoanOplog&"+VAR_ACTION+"=index&project_name="+name;
    }

    function show_repay_log(id) {
        location.href = ROOT+"?"+VAR_MODULE+"=DealRepayOplog&"+VAR_ACTION+"=index&project_id="+id;
    }

    function setZhangzhongInvalid() {
        if (confirm('确认操作？（处理时间较长，请耐心等待）')) {
            location.href='/m.php?m=DealProject&a=setZhangzhongInvalid'
        }
    }
</script>