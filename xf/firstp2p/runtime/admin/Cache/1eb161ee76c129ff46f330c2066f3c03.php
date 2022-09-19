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
        $.weeboxs.open(ROOT+'?m=GoldDeal&a=show_detail&id='+id, {contentType:'ajax',showButton:false,title:LANG['COUNT_TOTAL_DEAL'],width:600,height:330});
    }
    function edit_note(id) {
        $.weeboxs.open(ROOT+'?m=GoldDeal&a=edit_note&id='+id, {contentType:'ajax',showButton:false,title:'备注',width:600,height:300});
    }
    var fuzhilock = false;
    function copy_deal(id, btn) {
        $(btn).css({ "color": "grey" }).attr("disabled", "disabled");
        if (!fuzhilock) {
            fuzhilock = true;
            if (window.confirm('确认复制？\n如果该标有优惠码返利规则，新标也会复制其优惠码返利规则，否则会新标会复制全局优惠码返利规则。')) {
                $.ajax({
                    url: ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=copy_deal&id=" + id,
                    data: "ajax=1",
                    dataType: "json",
                    success: function (obj) {
                        fuzhilock = false;
                        //alert(obj.info);
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
    function contract(id){
        window.location.href = ROOT + '?m=ContractGold&a=index&deal_id='+id;
    }
    // csv导出
    function export_csv_file()
    {
        var confirm_msg = "\n\r大数据量请增加筛选条件缩小结果集条数，以免导出失败";
        confirm_msg = "确认要导出csv文件数据吗？" + confirm_msg + "\n\r导出过程中请耐心等待，不要关闭页面。";
        if (!confirm(confirm_msg)) {
            return;
        }
        var parm = $('#search_form').serialize();
        window.open(ROOT+'?'+parm+'&a=export_csv');
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

    function get_buy_type_title($buy_type)
    {
    return l("DEAL_BUY_TYPE_".$buy_type);
    }

    function get_is_update($is_update){
    if($is_update == 1){
    return '已修改，等待用户确认';
    }else{
    return '未修改';
    }
    } ?>
<div class="main">
    <div class="main_title">黄金标的列表</div>
    <div class="blank5"></div>
    <div class="button_row">
        <input type="button" class="button" value="<?php echo L("ADD");?>" onclick="add();" />
        <input type="button" class="button" value="<?php echo L("DEL");?>" onclick="del();" />
        <span style="color:red;">注：不可单独删除子单，删除母单后对应的子单也会一起删掉。</span>
    </div>
    <div class="blank5"></div>
    <div class="search_row">
        <form name="search" id="search_form" action="__APP__" method="get">
            编号：<input type="text" class="textbox" name="id" value="<?php echo trim($_REQUEST['id']);?>" style="width:100px;" />
            标的产品名称：<input type="text" class="textbox" name="name" value="<?php echo trim($_REQUEST['name']);?>" />
           <!-- 上标平台：
            <select name="site_id">
                <option value="0" <?php if(intval($_REQUEST['site_id']) == 0): ?>selected="selected"<?php endif; ?>> 所有平台 </option>
                <?php if(is_array($sitelist)): $i = 0; $__LIST__ = $sitelist;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$sitem): ++$i;$mod = ($i % 2 )?><option value="<?php echo ($sitem); ?>" <?php if(intval($_REQUEST['site_id']) == $sitem): ?>selected="selected"<?php endif; ?>><?php echo ($key); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
            </select>-->
            运营方姓名：
            <input type="text" class="textbox" name="real_name" value="<?php echo trim($_REQUEST['real_name']);?>" size="10" />
            标的售卖状态
            <select name="deal_status">
                <option value="all" <?php if($_REQUEST['deal_status'] == 'all' || trim($_REQUEST['deal_status']) == ''): ?>selected="selected"<?php endif; ?>>所有状态</option>
                <option value="0" <?php if($_REQUEST['deal_status'] != 'all' && trim($_REQUEST['deal_status']) != '' && intval($_REQUEST['deal_status']) == 0): ?>selected="selected"<?php endif; ?>><?php echo L("DEAL_STATUS_0");?></option>
                <option value="1" <?php if(intval($_REQUEST['deal_status']) == 1): ?>selected="selected"<?php endif; ?>><?php echo L("DEAL_STATUS_1");?></option>
                <option value="2" <?php if(intval($_REQUEST['deal_status']) == 2): ?>selected="selected"<?php endif; ?>><?php echo L("DEAL_STATUS_2");?></option>
                <option value="3" <?php if(intval($_REQUEST['deal_status']) == 3): ?>selected="selected"<?php endif; ?>><?php echo L("DEAL_STATUS_3");?></option>
                <option value="4" <?php if(intval($_REQUEST['deal_status']) == 4): ?>selected="selected"<?php endif; ?>><?php echo L("DEAL_STATUS_4");?></option>
                <option value="5" <?php if(intval($_REQUEST['deal_status']) == 5): ?>selected="selected"<?php endif; ?>><?php echo L("DEAL_STATUS_5");?></option>
                <option value="6" <?php if(intval($_REQUEST['deal_status']) == 6): ?>selected="selected"<?php endif; ?>><?php echo L("DEAL_STATUS_6");?></option>
            </select>
            <input type="hidden" id="page_now" value="<?php echo ($_GET["p"]); ?>" name="p" />
            <input type="hidden" value="GoldDeal" name="m" />
            <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
           <input type="button" class="button" value="导出" onclick="export_csv_file();" />
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
            <th width="50px">编号</th>
            <th>标的名称</th>
            <th>上标平台</th>
            <th>所属队列名称</th>
            <th>单次上线克重</th>
            <th>延期提货补偿率</th>
            <th>期限</th>
            <th>黄金及支付补偿方式</th>
            <th>用户类型</th>
            <th>运营方ID/姓名/手机号</th>
            <th>标的售卖状态</th>
            <th>状态</th>
            <th style="width:250px">
                操作
            </th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$deal): ++$i;$mod = ($i % 2 )?><tr class="row">
                <td>
                    <input type="checkbox" name="key" class="key" value="<?php echo ($deal["id"]); ?>"
                </td>
                <td>
                    &nbsp;<?php echo ($deal["id"]); ?>
                </td>

                <td>
                    &nbsp;
                        <?php echo ($deal["name"]); ?>
                    </a>
                </td>
                <td>
                    &nbsp;<?php echo (get_gold_deal_domain($deal["siteId"],'true')); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($deal["queueName"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($deal["borrowAmount"]); ?>
                </td>
                <td>
                    &nbsp;<?php echo ($deal["rate"]); ?>%
                </td>
                <td>
                    &nbsp;<?php echo ($deal["repayTime"]); ?><?php if($deal["loantype"] == 5): ?>天<?php else: ?>个月<?php endif; ?>
                </td>
                <td>
                    &nbsp;<?php if($deal["loantype"] == 5): ?>已购黄金及补偿克重到期一次性交付<?php else: ?>已购黄金到期交付，补偿克重按季度交付<?php endif; ?>
                    <!--<?php echo (get_loantype($deal["loantype"])); ?>-->
                </td>
                <td>
                    &nbsp;<?php echo (getUserTypeName($deal["userId"])); ?>
                </td>
                <td>
                    &nbsp;
                    <?php echo ($deal['userId']); ?>/
                     <?php echo ($listOfBorrower[$deal['userId']]['real_name']); ?> /
                    <?php echo (getUserFieldUrl($listOfBorrower[$deal['userId']],'mobile')); ?>
                </td>
                <td>
                    &nbsp;
                    <?php if($deal["dealStatus"] == 4): ?><?php if(($deal["dealStatus"] == 4) && ($deal["isHasLoans"] == 2)): ?>正在放款<?php endif; ?>
                    <?php if(($deal["dealStatus"] == 4) && ($deal["isHasLoans"] == 1)): ?>已放款<?php endif; ?>
                    <?php else: ?>

                        <?php echo (a_get_buy_status($deal["dealStatus"],$deal.id)); ?><?php endif; ?>
                </td>
                <td>
                    &nbsp;<?php echo (get_is_effect($deal["isEffect"],$deal[id])); ?>
                </td>


                <td>
                    <a href="javascript:edit('<?php echo ($deal["id"]); ?>')">编辑</a>
                    &nbsp;
                    <?php if(($deal["dealStatus"] == 3) || ($deal["loadMoney"] == 0)): ?><a href="javascript: del('<?php echo ($deal["id"]); ?>')">删除</a>
                        &nbsp;<?php endif; ?>
                    <a href="javascript:show_detail('<?php echo ($deal["id"]); ?>')">购买列表</a>
                    <br />
                 <input type="button" class="ts-input"  onclick="copy_deal('<?php echo ($deal["id"]); ?>',this)" value="复制"></input>
                    &nbsp;
                    <a href="javascript:contract('<?php echo ($deal["id"]); ?>')">合同列表</a>
                    &nbsp;
                    <a href="javascript:edit_note('<?php echo ($deal["id"]); ?>')">备注</a>
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