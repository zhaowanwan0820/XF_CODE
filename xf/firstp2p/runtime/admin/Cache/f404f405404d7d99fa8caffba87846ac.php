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
    function preview(id) {
        window.open("https://www.ncfwx.com/d/"+id+"?preview=1&debug=<?php echo app_conf('DEAL_DETAIL_VIEW_CODE');?>");
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


/*
     function apply_prepay(id) {
         $("#tiqian1").css({ "color": "grey" }).attr("disabled", "disabled");
         if (window.confirm('确认执行提前还款？')) {
             window.location.href = ROOT + '?m=Deal&a=apply_prepay&deal_id=' + id;
         } else {
             $("#tiqian1").css({ "color": "#4e6a81" }).removeAttr("disabled");
         }
    }
*/

    function apply_prepay(id,loantype, type) {
        if(loantype==7) {
            alert('提前还款不支持公益标');
            return false;
        }
        window.location.href = ROOT + '?m=DealPrepay&a=prepay_index&deal_id=' + id + '&type=' + type + '&not_ab=1';
    }

    var fuzhilock = false;
    function copy_deal(id, btn) {
        $(btn).css({ "color": "grey" }).attr("disabled", "disabled");
        if (!fuzhilock) {
            fuzhilock = true;
            $.ajax({
                url: ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=copy_deal&id=" + id,
                data: "ajax=1",
                dataType: "json",
                success: function (obj) {
                    fuzhilock = false;
                    $("#info").html(obj.info);

                }
            });
            fuzhilock = false;
        } else {
            alert("请不要重复点击");
        }
        $(btn).css({ "color": "#4e6a81" }).removeAttr("disabled");
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
<div class="main_title"><?php echo ($main_title); ?></div>
<div class="blank5"></div>
<div class="button_row">
    <input type="button" class="button" value="<?php echo L("DEL");?>" onclick="del();" />
    <span style="color:red;">注：不可单独删除子单，删除母单后对应的子单也会一起删掉。</span>
</div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        编号：<input type="text" class="textbox" name="id" value="<?php echo trim($_REQUEST['id']);?>" style="width:100px;" />
        借款标题：<input type="text" class="textbox" name="name" value="<?php echo trim($_REQUEST['name']);?>" />
        项目名称：<input type="text" class="textbox" name="project_name" value="<?php echo trim($_REQUEST['project_name']);?>" />
        交易所产品备案编号：<input type="text" class="textbox" name="jys_record_number" value="<?php echo trim($_REQUEST['jys_record_number']);?>" />
        <!-- <?php echo L("CATE_TREE");?>：
        <select name="cate_id">
            <option value="0" <?php if(intval($_REQUEST['cate_id']) == 0): ?>selected="selected"<?php endif; ?>><?php echo L("NO_SELECT_CATE");?></option>
            <?php if(is_array($cate_tree)): foreach($cate_tree as $key=>$cate_item): ?><option value="<?php echo ($cate_item["id"]); ?>" <?php if(intval($_REQUEST['cate_id']) == $cate_item['id']): ?>selected="selected"<?php endif; ?>><?php echo ($cate_item["title_show"]); ?></option><?php endforeach; endif; ?>
        </select> -->
        上标平台：
        <select name="site_id">
            <option value="0" <?php if(intval($_REQUEST['site_id']) == 0): ?>selected="selected"<?php endif; ?>> 所有平台 </option>
            <?php if(is_array($sitelist)): $i = 0; $__LIST__ = $sitelist;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$sitem): ++$i;$mod = ($i % 2 )?><option value="<?php echo ($sitem); ?>" <?php if(intval($_REQUEST['site_id']) == $sitem): ?>selected="selected"<?php endif; ?>><?php echo ($key); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
        </select>
        借款人姓名：
        <input type="text" class="textbox" name="real_name" value="<?php echo trim($_REQUEST['real_name']);?>" size="10" />
        放款审批单编号：
        <input type="text" class="textbox" name="approve_number" value="<?php echo trim($_REQUEST['approve_number']);?>" size="10" />
        借款状态
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
        贷款类型：
        <select name="deal_type">
            <option value="0" <?php if($_REQUEST['deal_type'] == 0): ?>selected<?php endif; ?>>网贷</option>
            <?php if(!$is_cn): ?><option value="2" <?php if($_REQUEST['deal_type'] == 2): ?>selected<?php endif; ?>>交易所</option>
            <option value="3" <?php if($_REQUEST['deal_type'] == 3): ?>selected<?php endif; ?>>专享</option>
            <option value="5" <?php if($_REQUEST['deal_type'] == 5): ?>selected<?php endif; ?>>小贷</option><?php endif; ?>
        </select>
        <input type="hidden" id="page_now" value="<?php echo ($_GET["p"]); ?>" name="p" />
        <input type="hidden" value="Deal" name="m" />
        <input type="hidden" value="index" name="a" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" class="button" value="导出" onclick="export_csv();" />
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
            <th width="50px   ">
                <a href="javascript:sortBy('id','1','Deal','index')" title="按照编号升序排列 ">
                    编号
                    <img src="/static/admin/Common/images/desc.gif" width="12" height="17"
                    border="0" align="absmiddle">
                </a>
            </th>
            <th>项目名称</th>
            <th>
                <a href="javascript:sortBy('name','1','Deal','index')" title="按照借款标题升序排列 ">
                    借款标题
                </a>
            </th>

            <!-- <th>
                <a href="javascript:sortBy('cate_id','1','Deal','index')" title="按照投资类型   升序排列 ">
                    投资类型
                </a>
            </th>
            <th>
                <a href="javascript:sortBy('type_id','1','Deal','index')" title="按照借款用途   升序排列 ">
                    借款用途
                </a>
            </th> -->

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
                借款人/代理人ID,姓名
                <!--a href="javascript:sortBy('user_id','1','Deal','index')" title="按照借款人   升序排列 ">
                    姓名
                </a>,
                <a href="javascript:void(0)">
                    手机
                </a-->
            </th>
            <th>
                借款企业
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
                交易所产品备案编号
            </th>
            <!--
            <th>
                借款人合同委托签署
            </th>
            <th>
                借款人合同委托签署代理人
            </th>
            -->
            <th>
                借款人签署状态
            </th>
            <!--
            <th>
                担保方合同委托签署
            </th>
            <th>
                担保方合同委托签署代理人
            </th>
            -->
            <th>
                担保方签署状态
            </th>
            <!--
            <th>
                资产管理方合同委托签署
            </th>
            <th>
                资产管理方合同委托签署代理人
            </th>
            <!-- <th>
                <a href="javascript:sortBy('is_update','1','Deal','index')" title="按照修改状态   升序排列 ">
                    修改状态
                </a>
            </th>
            <th>
                <a href="javascript:sortBy('is_recommend','1','Deal','index')" title="按照推荐   升序排列 ">
                    推荐
                </a>
            </th>
            <th>
                <a href="javascript:sortBy('sort','1','Deal','index')" title="按照排序升序排列 ">
                    排序
                </a>
            </th> -->
            <th>
                资产管理方签署状态
            </th>
            <th>
                受托方签署状态
            </th>
            <th style="width:250px">
                操作
            </th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$deal): ++$i;$mod = ($i % 2 )?><tr class="row">
            <td>
                <input type="checkbox" name="key" class="key" value="<?php echo ($deal["id"]); ?>"
                <?php if (($dea['deal_status ']== 3) || (intval($deal['load_money']) == 0)) {
                } else {
                    echo 'disabled="disabled"';
                } ?>
                >
            </td>
            <td>
                &nbsp;<?php echo ($deal["id"]); ?>
            </td>
            <td><?php echo (get_project_name($deal["project_id"])); ?></td>
            <td>
                &nbsp;
                <a href="javascript:deal_view('<?php echo ($deal["id"]); ?>')">
                    <?php echo ($deal["name"]); ?>
                </a>
            </td>

            <!-- <td>
                &nbsp;<?php echo (get_deal_cate_name($deal["cate_id"])); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_loan_type_name($deal["type_id"])); ?>
            </td> -->

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
                <?php echo !empty($listOfBorrower[$deal['user_id']]['company_name']) ? getUserFieldUrl($listOfBorrower[$deal['user_id']], 'company_name') : getUserFieldUrl($listOfBorrower[$deal['user_id']], 'real_name');?>
                <!--/<?php echo (getUserFieldUrl($listOfBorrower[$deal['user_id']],'mobile')); ?>-->
            </td>
            <td>
                <?php echo ($listOfBorrower[$deal['user_id']]['borrowName']); ?>
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
            <td><?php echo ($deal["jys_record_number"]); ?></td>
            <!--
            <td>
                &nbsp;<?php echo (get_project_entrust_sign($deal["project_id"],'entrust_sign')); ?>
            </td>
            <td>
                &nbsp;<?php echo get_entrustor_name($deal['id'], $deal['user_id']);?>
            </td>
            -->
            <td>
                &nbsp;<?php echo (get_deal_contract_status($deal["id"],"0")); ?>
            </td>
            <!--
            <td>
                &nbsp;<?php echo (get_project_entrust_sign($deal["project_id"],'entrust_agency_sign')); ?>
            </td>
            <td>
                &nbsp;<?php echo get_entrustor_name($deal['id'], 0, $deal[agency_id]);?>
            </td>
            -->
            <td>
                &nbsp;<?php echo (get_deal_contract_sign_status($deal["id"],$deal[agency_id])); ?>
            </td>
            <!--
            <td>
                &nbsp;<?php echo (get_project_entrust_sign($deal["project_id"],'entrust_advisory_sign')); ?>
            </td>
            <td>
                &nbsp;<?php echo get_entrustor_name($deal['id'], 0, $deal[advisory_id]);?>
            </td>
            -->
            <td>
                &nbsp;<?php echo (get_deal_contract_sign_status($deal["id"],$deal[advisory_id])); ?>
            </td>
            <td>
                <?php if($deal["entrust_agency_id"] > 0): ?>&nbsp;<?php echo (get_deal_contract_sign_status($deal["id"],$deal[entrust_agency_id])); ?>
                <?php else: ?>
                 &nbsp;/<?php endif; ?>
            </td>

            <!-- <td>
                &nbsp;<?php echo (get_is_update($deal["is_update"])); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_toogle_status($deal["is_recommend"],$deal['id'],is_recommend)); ?>
            </td>
            <td>
                &nbsp;<?php echo (get_sort($deal["sort"],$deal['id'])); ?>
            </td> -->
            <td>
                <a href="javascript:edit('<?php echo ($deal["id"]); ?>')">编辑</a>
                &nbsp;
                <a href="javascript:deal_view('<?php echo ($deal["id"]); ?>')">查看</a>
                &nbsp;
                <a href="javascript:show_detail('<?php echo ($deal["id"]); ?>')">投资列表</a>
                &nbsp;
                <a href="javascript:show_adviser_list('<?php echo ($deal["id"]); ?>')">顾问列表</a>
                &nbsp;
                <a href="javascript: preview('<?php echo ($deal["ecid"]); ?>')">预览</a>
                <br />
                <!-- <a href="javascript:file_operate('<?php echo ($deal["id"]); ?>')">文件管理</a>
                &nbsp; -->
                <input type="button" class="ts-input"  onclick="copy_deal('<?php echo ($deal["id"]); ?>',this)" value="复制"></input>
                &nbsp;
                <a href="javascript:contract('<?php echo ($deal["id"]); ?>')">合同列表</a>
                &nbsp;
                <?php if($deal['is_entrust_zx'] != 1 ): ?><?php if(($deal["deal_status"] == 4) && ($deal["parent_id"] != 0)): ?><a  href="javascript:force_repay('<?php echo ($deal["id"]); ?>')">强制还款</a>
                    &nbsp;<?php endif; ?>
                    <?php if(($deal["deal_status"] == 4) && ($deal["parent_id"] != 0)): ?><input type="button" id="prepay_compute" class="ts-input"  onclick="apply_prepay('<?php echo ($deal["id"]); ?>', '<?php echo ($deal["loantype"]); ?>', 2)" value="提前还款试算"/>
                        &nbsp;
                        <input type="button" id="tiqian1" class="ts-input" data-id="<?php echo ($deal["id"]); ?>" onclick="apply_prepay('<?php echo ($deal["id"]); ?>','<?php echo ($deal["loantype"]); ?>', 1)" value="提前还款"></input>
                        &nbsp;<?php endif; ?><?php endif; ?>

                <a href="javascript:edit_note('<?php echo ($deal["id"]); ?>')">备注</a>
                &nbsp;
                <?php if(($deal["deal_status"] == 0) || ($deal["deal_status"] == 1)): ?><a href="javascript:make_appointment('<?php echo ($deal["id"]); ?>')">预约投标</a>
                &nbsp;<?php endif; ?>
                <a href="javascript:volid(0)" onclick='open_coupon_list(<?php echo ($deal["id"]); ?>)'>优惠码列表</a>
                &nbsp;
                <?php if(($deal["deal_status"] == 3) || ($deal["load_money"] == 0)): ?><a href="javascript: del('<?php echo ($deal["id"]); ?>')">删除</a>
                    &nbsp;<?php endif; ?>

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
function deal_view(id) {
    window.location.href=ROOT+'?m=Deal&a=deal_view&id='+id;
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