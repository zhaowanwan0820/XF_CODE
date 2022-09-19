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

<script type="text/javascript" src="__TMPL__Common/js/conf.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar_lang.js" ></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.weebox.js"></script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/weebox.css" />
<script type="text/javascript">
    var checkLoanMoney=function()
    {
        var min=parseFloat($('#min_loan_money').val());
        var max=parseFloat($('#max_loan_money').val());
        if(max > 0 && min >0)
        {
            if(max<min)
            {
                alert('最大金额不能小于最小金额');
                return false;
            }
        }
    };
    window.onload = function()
    {
        dealcrowd();
        $('#min_loan_money').blur(checkLoanMoney);
        $('#max_loan_money').blur(checkLoanMoney);
        $('#deal_crowd').change(dealcrowd);
        //$('#min_loan_money').val(Math.ceil($("#apr").val()/200));
    }

    $(document).ready(function () {
        // 年化借款平台手续费-收费方式 显示不同的标签
        if ($('#loan_fee_type_3').attr('checked') || $('#loan_fee_type_7').attr('checked')) { // 分期收、固定比例分期收
            $('#loan_fee_custom_input').html($('#loan_fee_installment').html());
        } else if ($('#loan_fee_type_4').attr('checked')) {
            $('#loan_fee_custom_input').html($('#loan_fee_proxy').html());
        }

        // 更新分期收总金额
        calc_fenqi_fee('loan_fee', 0);
    });
    function view(id) {
        location.href = ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=view&id=" + id;
    }
</script>

<link rel="stylesheet" type="text/css" href="__TMPL__Common/js/calendar/calendar.css" />
<script type="text/javascript" src="__TMPL__Common/js/calendar/calendar.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/deal.js"></script>

<div class="main">
    <div class="main_title">借款查看 <a href="<?php if($vo['deal_type'] == 1): ?><?php echo u("Deal/compound");?><?php else: ?><?php echo u("Deal/index");?><?php endif; ?>" class="back_list"><?php echo L("BACK_LIST");?></a></div>
    <div class="blank5"></div>
    <form name="edit" action="__APP__" id='editform' method="post" enctype="multipart/form-data">
        <div class="blank5"></div>
        <table class="form conf_tab" cellpadding=0 cellspacing=0 rel="1">
            <tr>
                <td colspan=2 class="topTd"></td>
            </tr>
            <tr>
                <td class="item_title">旧版借款标题:</td>
                <td class="item_input">
                    <?php if($vo['deal_status'] == 0): ?>前&nbsp;缀：<input  type="text" class="textbox" name="prefix_title" style="width:500px;" value="<?php echo ($vo["prefix_title"]); ?>" <?php if($vo['publish_wait'] == 0 and $vo['parent_id'] != -1): ?>disabled title='只有审核之后的子母标不可以编辑'<?php endif; ?>/>&nbsp;<font color="#ff2121">文本最末端请输入逗号(中文全角)</font>
                        <br />
                        主标题：<input type="text" class="textbox" name="main_title" style="width:500px;" readonly="readonly" value="<?php echo ($vo["main_title"]); ?>"/>
                        <?php else: ?>
                        <input type="text" class="textbox" name="main_title" style="width:500px;" disabled="disabled" value="<?php echo ($vo["prefix_title"]); ?><?php echo ($vo["main_title"]); ?>"/><?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="item_title">标的产品名称:</td>
                <td class="item_input">
                    <input type="text" name="name" value="<?php echo ($vo["name"]); ?>" readOnly="true" style="width:500px;" />
                </td>
            </tr>
            <tr>
        <td class="item_title">会员:</td>
        <td class="item_input">
            <span id='borrower_info'><?php echo get_user_name($vo['user_id']);?>
                <a href="__APP__?m=User&a=passed&id=<?php echo ($vo["user_id"]); ?>" target="_blank">资料认证</a>
                <?php
                    if(intval($userInfo['idcardpassed']) !== 1) {
                        echo '<span style="color:red;">身份未认证</span>';
                    }else{
                        echo '身份已认证';
                    }
                ?>
                <?php
                    if(intval($userInfo['audit']['status']) !== 1) {
                        echo '<span style="color:red;">银行卡未审核</span>';
                    }else{
                        echo '银行卡已验证';
                    }
                ?>
                用户姓名:<?php echo !empty($userInfo['company_name']) ? $userInfo['company_name'] : $userInfo['real_name'];;?>
                &nbsp;
                用户类型:<?php echo ($userInfo['user_type_name']); ?>
            </span>
                    <?php if($pro['id']): ?><!-- 不可修改 -->
                        <?php elseif($vo['deal_status'] == 0 or $vo['deal_status'] == 1): ?>
                        <a href="javascript:void(0)" onclick='edit_borrower()'>修改</a><?php endif; ?>
                </td>
            </tr>
            <?php if(!empty($guarantor)): ?><tr>
                    <td class="item_title">借款保证人:</td>
                    <td class="item_input">
                        <?php if(is_array($guarantor)): foreach($guarantor as $key=>$gu): ?><div style="width: 80px;float: left;"><?php echo ($gu["name"]); ?></div>
                            <a href="__APP__?m=DealGuarantor&a=index&did=<?php echo ($vo["id"]); ?>&gid=<?php echo ($gu["id"]); ?>" target="_blank"><?php echo ($gu["status_name"]); ?></a>
                            <?php if(!empty($gu['to_user_id'])): ?><a href="__APP__?m=User&a=passed&id=<?php echo ($gu["to_user_id"]); ?>" target="_blank">身份审核</a><?php endif; ?>
                            <br/><?php endforeach; endif; ?>
                </tr><?php endif; ?>
            <tr>
                <td class="item_title"><?php echo L("CATE_TREE");?>:</td>
                <td class="item_input">
                    <select name="cate_id" class="require">
                        <option value="0">==<?php echo L("NO_SELECT_CATE");?>==</option>
                        <?php if(is_array($deal_cate_tree)): foreach($deal_cate_tree as $dkey=>$cate_item): ?><option value="<?php echo ($cate_item["id"]); ?>" <?php if($vo['cate_id'] == $cate_item['id']): ?>selected="selected"<?php endif; ?> <?php if($vo['publish_wait'] == 1 and $vo['cate_id'] == 0 and $dkey == 2): ?>selected="selected"<?php endif; ?>><?php echo ($cate_item["title_show"]); ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="item_title">借款客群:</td>
                <td class="item_input">
                    <input type="text" class="textbox"  name="loan_user_customer" value="<?php echo ($vo['loan_user_customer']); ?>" disabled="disabled" />
                </td>
            </tr>
            <tr>
                <td class="item_title">担保/代偿I机构</td>
                <td class="item_input">
                    <select name="agency_id" class="require" onchange="javascript:change_agency(this.options[selectedIndex].value)"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                        <option value="0">无</option>
                        <?php if(is_array($deal_agency)): foreach($deal_agency as $key=>$agency_item): ?><option value="<?php echo ($agency_item["id"]); ?>" <?php if($vo['agency_id'] == $agency_item['id']): ?>selected="selected"<?php endif; ?>><?php if($agency_item['short_name'] != ''): ?><?php echo ($agency_item["short_name"]); ?>(<?php echo ($agency_item["name"]); ?>)<?php else: ?><?php echo ($agency_item["name"]); ?><?php endif; ?></option><?php endforeach; endif; ?>
                    </select>
                    <span class="tip_span">机构担保标时可选择</span>
                </td>
            </tr>
            <tr>
                <td class="item_title">咨询机构:</td>
                <td class="item_input">
                    <select name="advisory_id" class="require"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                        <option value="0">无</option>
                        <?php if(is_array($deal_advisory)): foreach($deal_advisory as $key=>$advisory_item): ?><option value="<?php echo ($advisory_item["id"]); ?>" <?php if($vo['advisory_id'] == $advisory_item['id']): ?>selected="selected"<?php endif; ?>><?php if($advisory_item['short_name'] != ''): ?><?php echo ($advisory_item["short_name"]); ?><?php else: ?><?php echo ($advisory_item["name"]); ?><?php endif; ?></option><?php endforeach; endif; ?>
                    </select>
                    <!--  <span class="tip_span">机构担保标时可选择</span>
                    -->
                </td>
            </tr>
            <tr>
                <td class="item_title">渠道机构:</td>
                <td class="item_input">
                    <select name="canal_agency_id" class="require" onchange="javascript:change_canal(this.options[selectedIndex].value)"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                    <option value="0">无</option>
                    <?php if(is_array($canal_agency)): foreach($canal_agency as $key=>$canal_item): ?><option value="<?php echo ($canal_item["id"]); ?>" <?php if($vo['canal_agency_id'] == $canal_item['id']): ?>selected="selected"<?php endif; ?>><?php if($canal_item['short_name'] != ''): ?><?php echo ($canal_item["short_name"]); ?><?php else: ?><?php echo ($canal_item["name"]); ?><?php endif; ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="item_title">支付机构:</td>
                <td class="item_input">
                    <select name="pay_agency_id" class="require"  <?php if($pro['business_status'] != $project_business_status['waitting']  and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                        <?php if(is_array($pay_agency)): foreach($pay_agency as $key=>$pay_agency_item): ?><option value="<?php echo ($pay_agency_item["id"]); ?>" <?php if($vo['pay_agency_id'] == $pay_agency_item['id']): ?>selected="selected"<?php endif; ?>><?php if($pay_agency_item['short_name'] != ''): ?><?php echo ($pay_agency_item["short_name"]); ?><?php else: ?><?php echo ($pay_agency_item["name"]); ?><?php endif; ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>
            <tr id="management_agency_tr" style="display:none">
                <td class="item_title">管理机构:</td>
                <td class="item_input">
                    <select name="management_agency_id" class="require"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                        <?php if(is_array($management_agency)): foreach($management_agency as $key=>$management_agency_item): ?><option value="<?php echo ($management_agency_item["id"]); ?>" <?php if($vo['management_agency_id'] == $management_agency_item['id']): ?>selected="selected"<?php endif; ?>><?php if($management_agency_item['short_name'] != ''): ?><?php echo ($management_agency_item["short_name"]); ?><?php else: ?><?php echo ($management_agency_item["name"]); ?><?php endif; ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="item_title">担保/代偿II-b机构</td>
                <td class="item_input">
                    <select name="advance_agency_id"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                        <option value="0">==<?php echo L("NO_SELECT_AGENCY");?>==</option>
                        <?php if(is_array($advance_agency)): foreach($advance_agency as $key=>$advance_agency_item): ?><option value="<?php echo ($advance_agency_item["id"]); ?>" <?php if($vo['advance_agency_id'] == $advance_agency_item['id']): ?>selected="selected"<?php endif; ?>><?php if($advance_agency_item['short_name'] != ''): ?><?php echo ($advance_agency_item["short_name"]); ?><?php else: ?><?php echo ($advance_agency_item["name"]); ?><?php endif; ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>
            <?php if($vo['deal_type'] == 2): ?><tr id="jys">
                    <td class="item_title">交易所:</td>
                    <td class="item_input">
                        <select name="jys_id" id="jys_id"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                        <option value="0">==<?php echo L("NO_SELECT_AGENCY");?>==</option>
                        <?php if(is_array($jys)): foreach($jys as $key=>$jys_item): ?><option value="<?php echo ($jys_item["id"]); ?>" <?php if($vo['jys_id'] == $jys_item['id']): ?>selected="selected"<?php endif; ?>><?php if($jys_item['short_name'] != ''): ?><?php echo ($jys_item["short_name"]); ?><?php else: ?><?php echo ($jys_item["name"]); ?><?php endif; ?></option><?php endforeach; endif; ?>
                        </select>
                    </td>
                </tr><?php endif; ?>
            <?php if($vo['deal_type'] == 0): ?><tr>
                <td class="item_title">代充值机构:</td>
                <td class="item_input">
                    <select name="generation_recharge_id"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                    <option value="0">==<?php echo L("NO_SELECT_AGENCY");?>==</option>
                    <?php if(is_array($generation_recharge)): foreach($generation_recharge as $key=>$generation_recharge_item): ?><option value="<?php echo ($generation_recharge_item["id"]); ?>" <?php if($vo['generation_recharge_id'] == $generation_recharge_item['id']): ?>selected="selected"<?php endif; ?>><?php if($generation_recharge_item['short_name'] != ''): ?><?php echo ($generation_recharge_item["short_name"]); ?><?php else: ?><?php echo ($generation_recharge_item["name"]); ?><?php endif; ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr><?php endif; ?>
            <?php if($vo['deal_type'] == 3): ?><tr id="entrust_agency_tr">
                <td class="item_title">受托机构:</td>
                <td class="item_input">
                    <select name="entrust_agency_id" id="entrust_agency_id" class="require"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                        <option value="">==<?php echo L("NO_SELECT_AGENCY");?>==</option>
                        <?php if(is_array($entrust_agency)): foreach($entrust_agency as $key=>$entrust_agency_item): ?><option value="<?php echo ($entrust_agency_item["id"]); ?>" <?php if($vo['entrust_agency_id'] == $entrust_agency_item['id']): ?>selected="selected"<?php endif; ?>><?php if($entrust_agency_item['short_name'] != ''): ?><?php echo ($entrust_agency_item["short_name"]); ?><?php else: ?><?php echo ($entrust_agency_item["name"]); ?><?php endif; ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr><?php endif; ?>
            <tr id="warrant_info" <?php if(($vo["agency_id"] == 0 or $vo["agency_id"] == '') and ($vo["deal_type"] == 2)): ?>style="display:none"<?php endif; ?>>
                <td class="item_title">担保范围:</td>
                <td class="item_input">
                    <select name="warrant" id = "warrant_select" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                        <option value="0" <?php if($vo['warrant'] == 0): ?>selected="selected"<?php endif; ?>>无</option>
                        <option value="1" <?php if($vo['warrant'] == 1): ?>selected="selected"<?php endif; ?>>本金</option>
                        <option value="2" <?php if($vo['warrant'] == 2): ?>selected="selected"<?php endif; ?>>本金及利息</option>
                        <option value="3" <?php if($vo['warrant'] == 3): ?>selected="selected"<?php endif; ?>>有</option>
                        <option value="4" <?php if($vo['warrant'] == 4): ?>selected="selected"<?php endif; ?>>第三方资产收购</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="item_title">产品类别:</td>
                <td class="item_input">
                    <input type='hidden' id='lgl_type_tag' value='<?php echo ($lgl_tag); ?>' />
                    <input type='hidden' id='bxt_type_tag' value='<?php echo ($bxt_tag); ?>' />
                    <input type='hidden' id='dtb_type_tag' value='<?php echo ($dtb_tag); ?>' />
                    <input type='hidden' id='xffq_type_tag' value='<?php echo ($xffq_tag); ?>' />
                    <input type='hidden' id='zcgl_type_tag' value='<?php echo ($zcgl_tag); ?>' />
                    <select name="type_id" id='type_id' onchange="javascript:change_lgl_input()" <?php if(($vo['deal_status'] != 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled<?php endif; ?>>
                    <?php if(is_array($deal_type_tree)): foreach($deal_type_tree as $key=>$type_item): ?><option value="<?php echo ($type_item["id"]); ?>" type_tag='<?php echo ($type_item["type_tag"]); ?>' <?php if($type_item['id'] == $vo['type_id']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item["name"]); ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="item_title">借款用途分类:</td>
                <td class="item_input">
                    <select name="loan_application_type"class="require"  id='loan_application_type'  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                        <option value="0" <?php if($deal_ext[loan_application_type] == 0): ?>selected="selected"<?php endif; ?>>其他</option>
                        <option value="1" <?php if($deal_ext[loan_application_type] == 1): ?>selected="selected"<?php endif; ?>>企业经营</option>
                        <option value="2" <?php if($deal_ext[loan_application_type] == 2): ?>selected="selected"<?php endif; ?>>短期周转</option>
                        <option value="3" <?php if($deal_ext[loan_application_type] == 3): ?>selected="selected"<?php endif; ?>>日常消费</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="item_title">上线网站编号</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="line_site_id"  value="<?php echo ($deal_ext["line_site_id"]); ?>" maxlength="120"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>
                </td>
            </tr>
            <tr>
                <td class="item_title">上线网站名称</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="line_site_name"  value="<?php echo ($deal_ext["line_site_name"]); ?>" maxlength="120"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>
                </td>
            </tr>
            <?php if($vo['deal_type'] == 1): ?><tr>
                    <td class="item_title">锁定周期:</td>
                    <td class="item_input">
                        <input type="text" class="textbox" name="lock_period"  id="lock_period" value="<?php echo ($vo['lock_period']); ?>"/> 天
                    </td>
                </tr>

                <tr>
                    <td class="item_title">赎回周期:</td>
                    <td class="item_input">
                        <input type="text" class="textbox" name="redemption_period"  id="redemption_period" value="<?php echo ($vo['redemption_period']); ?>"  onchange="javascript:changeRate('annualized_rate');"/> 天
                    </td>
                </tr>

                <tr class='lgl_input'>
                    <td class="item_title">终止日期:</td>
                    <td class="item_input">
                        <input type="text" class="textbox" name="end_date" id="end_date" value='<?php echo ($vo["end_date"]); ?>' onfocus="this.blur(); return showCalendar('end_date', '%Y-%m-%d', true,true, 'btn_end_date');" />
                        <input type="button" class="button" id=btn_end_date value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('end_date', '%Y-%m-%d', false, false, 'btn_end_date');" />
                    </td>
                </tr>

                <tr>
                    <td class="item_title">是否自动提现:</td>
                    <td class="item_input">
                        <label><input type="radio" name="is_auto_withdrawal" value="1" <?php if($deal_ext['is_auto_withdrawal'] == 1): ?>checked="checked"<?php endif; ?> />是</label>
                        <label><input type="radio" name="is_auto_withdrawal" value="0" <?php if($deal_ext['is_auto_withdrawal'] == 0): ?>checked="checked"<?php endif; ?> />否</label>
                    </td>
                </tr><?php endif; ?>
            <tr>
                <td class="item_title">借款用途详述:</td>
                <td class="item_input">
                    <textarea id="use_info" style="width:500px;height:45px" name="use_info" ><?php echo ($deal_ext["use_info"]); ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="item_title">投资限定条件1:</td>
                <td class="item_input">
                    <select name="deal_crowd" id="deal_crowd" style="float: left;"  <?php if(($pro['business_status'] > $project_business_status['process'] and $vo['deal_status'] > 1) or $disabled_deal_crowd_34): ?>disabled<?php endif; ?>>
                        <?php if(is_array($deal_crowd)): foreach($deal_crowd as $crow_key=>$crow_item): ?><option value="<?php echo ($crow_key); ?>" <?php if($crow_key == $vo['deal_crowd']): ?>selected="selected"<?php endif; ?>>
                            <?php echo ($crow_item); ?></option><?php endforeach; endif; ?>
                    </select>&nbsp;
                    <div style="float:left;margin-left:10px;display:none" id="specify_uid_dev">
                        <input placeholder="输入指定用户ID" value="<?php echo ($specify_uid_info["id"]); ?>" id="specify_uid" name="specify_uid" style="width:100px;" onblur="specify_blur()"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>>
                        <span id="specify_user" style="color:red;font-size: 12px;">
                            <?php if($specify_uid_info != ''): ?>姓名:<?php echo ($specify_uid_info["real_name"]); ?> 手机:<?php echo ($specify_uid_info["mobile"]); ?><?php endif; ?>
                        </span>
                    </div>
                    <div style="float:left;margin-left:10px;display:none" id="specify_vip">
                        <?php if(is_array($vipGrades)): foreach($vipGrades as $grade=>$gradeName): ?><input type="radio" value="<?php echo ($grade); ?>" name="specify_vip" <?php if(($grade == 1) or ($vo['deal_crowd'] == 33 and $deal_ext['deal_specify_uid'] == $grade)): ?>checked<?php endif; ?> <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>><?php if($grade < 6): ?><?php echo ($gradeName); ?>及以上<?php else: ?><?php echo ($gradeName); ?><?php endif; ?><?php endforeach; endif; ?>
                    </div>
                    <div id='user_group'>
                        <?php if(is_array($usergroupList)): foreach($usergroupList as $ug_key=>$ug_item): ?><input type="checkbox"  autocomplete='off' value="<?php echo ($ug_item["id"]); ?>" name="user_group[]" <?php if(array_search($ug_item['id'],$vo['user_group']) !== FALSE): ?>checked="checked"<?php endif; ?>  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>><?php echo ($ug_item["name"]); ?><?php endforeach; endif; ?>
                    </div>
                    <div style="float:left;margin-left:10px;display:none" id="upload_csv_datas">
                        <input type="hidden" name="deal_id" value="<?php echo ($vo["id"]); ?>" />
                        <input type="file" name="upfile" id="upfile" style="width:150px; <?php if(($pro['business_status'] > $project_business_status['process'] and $vo['deal_status'] > 1) or $disabled_deal_crowd_34): ?>display:none<?php endif; ?>">
                        <a href="javascript:view('<?php echo ($vo["id"]); ?>')">查看已选择用户</a>
                        <a href="/static/admin/Common/special_deal_user_data_template.csv">模板下载</a>
                        <strong style="color:#ff0000">每次导入最多1000条，请导入csv格式。</strong>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="item_title">投资限定条件2:</td>
                <td class="item_input">
                    <select name="bid_restrict" id="bid_restrict"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                        <?php if(is_array($bid_restrict)): foreach($bid_restrict as $restrict_key=>$restrict_item): ?><option value="<?php echo ($restrict_key); ?>" <?php if($restrict_key == $vo['bid_restrict']): ?>selected="selected"<?php endif; ?>>
                            <?php echo ($restrict_item); ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="item_title">自定义标签:</td>
                <td class="item_input">
                    名称:<input type="text" class="textbox" name="deal_tag_name"  id = "deal_tag_name" value="<?php echo ($vo["deal_tag_name"]); ?>"   <?php if($pro['business_status'] > $project_business_status['process']): ?>readonly<?php endif; ?>/>
                    描述:<input type="text" class="textbox" size="60" name="deal_tag_desc" id = "deal_tag_desc" value="<?php echo ($vo["deal_tag_desc"]); ?>" <?php if($pro['business_status'] > $project_business_status['process']): ?>readonly<?php endif; ?>>
                </td>
            </tr>
            <tr>
                <td class="item_title">tag:</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="deal_tags"  id = "deal_tags" size="90" value="<?php echo ($vo["tags"]); ?>"  <?php if($pro['business_status'] > $project_business_status['process']): ?>readonly<?php endif; ?>/>
                    <span class="tip_span">tag之间以半角逗号分隔</span>
                </td>
            </tr>
            <tr>
                <td class="item_title">是否必须使用服务人邀请码:</td>
                <td>
                    <select name="must_coupon" id="must_coupon"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                        <option value="0" <?php if(0 == $deal_ext['must_coupon']): ?>selected="selected"<?php endif; ?>>否 </option>
                        <option value="1" <?php if(1 == $deal_ext['must_coupon']): ?>selected="selected"<?php endif; ?>>是 </option>
                    </select>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">服务人邀请码是否有效:</td>
                <td>
                    <select name="is_rebate" id="is_rebate"  <?php if($vo['deal_status'] != 0): ?>disabled="disabled"<?php endif; ?> >
                        <option value="1" <?php if(1 == $deal_coupon['is_rebate']): ?>selected="selected"<?php endif; ?>>有效</option>
                        <option value="2" <?php if(2 == $deal_coupon['is_rebate']): ?>selected="selected"<?php endif; ?>>无效</option>
                    </select>
                </td>
            </tr>
            <tr >
                <td class="item_title">达人专享:</td>
                <td class="item_input">
                    <input type="checkbox" id="daren" <?php if($vo['min_loan_total_count'] > 0 || $vo['min_loan_total_amount'] > 0): ?>checked<?php endif; ?>  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>
                    <span class="loan_limit" <?php if($vo['min_loan_total_count'] == 0 && $vo['min_loan_total_amount'] == 0): ?>style="display:none"<?php endif; ?> >
                    投资次数不少于:
                    <input type="text" class="textbox" name="min_loan_total_count"  id = "min_loan_total_count" value="<?php echo ($vo["min_loan_total_count"]); ?>"   <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>
                    <select name="min_loan_total_limit_relation" id="min_loan_total_limit_relation"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                        <option value="0" <?php if($vo['min_loan_total_limit_relation'] == 0): ?>selected="selected"<?php endif; ?>> 或者 </option>
                        <option value="1" <?php if($vo['min_loan_total_limit_relation'] == 1): ?>selected="selected"<?php endif; ?>> 并且</option>
                    </select>
                    累计投资金额不少于:
                    <input type="text" class="textbox" name="min_loan_total_amount" id="min_loan_total_amount"  value="<?php echo ($vo["min_loan_total_amount"]); ?>"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>

                    </span>
                </td>
            </tr>
            <tr>
                <td class="item_title">还款提醒短信</td>
                <td class="item_input">
                    <input type="checkbox" name="need_repay_notice" <?php if($deal_ext['need_repay_notice'] == 1): ?>checked="checked"<?php endif; ?> value="1"  <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("BORROW_AMOUNT");?>:</td>
                <td class="item_input">
                    <input type="text" class="textbox require" name="borrow_amount"  id = "apr" value="<?php echo ($vo["borrow_amount"]); ?>"   onchange="javascript:update_proxy_loan_info();" <?php if(($vo['publish_wait'] == 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>readonly title='只有审核之后的子母标不可以编辑'<?php endif; ?>/><?php if($pro['id']): ?><font color='red'> 待上标金额： <?php echo ($pro["left_money"]); ?></font> &nbsp; 项目总额：<?php echo ($pro["borrow_amount"]); ?> &nbsp; 已上标金额：<?php echo ($pro["money_borrowed"]); ?><?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("MIN_LOAN_MONEY");?>:</td>
                <td class="item_input">
                    <input type="text" class="textbox require" name="min_loan_money" id="min_loan_money"  value="<?php echo ($vo["min_loan_money"]); ?>" <?php if(($vo['deal_status'] != 0 and $vo['parent_id'] != -1) or ($pro['business_status'] > $project_business_status['process'])): ?>disabled title='只有 进行中或之后状态的子母标 不可以编辑'<?php endif; ?>/>

                    <?php if(($vo['deal_type'] == 2 or $vo['deal_type'] == 3)): ?><input type="checkbox" name="is_float_min_loan" value="1" <?php if($vo['is_float_min_loan'] == 1): ?>checked<?php endif; ?>>启用浮动起投金额<?php endif; ?>

                </td>
            </tr>
            <tr>
                <td class="item_title">最高投资金额:</td>
                <td class="item_input">
                    <input type="text" class="textbox" id="max_loan_money" name="max_loan_money"  <?php if(empty($vo["max_loan_money"])): ?>value="0"<?php else: ?> value="<?php echo ($vo["max_loan_money"]); ?>"<?php endif; ?> <?php if($pro['business_status'] > $project_business_status['process']): ?>readonly<?php endif; ?>/>
                    <span class="tip_span">为0或为空时表示不做限制</span>
                </td>
            </tr>
            <tr>
                <td class="item_title">筹标期限:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" name="enddate" value="<?php echo ($vo["enddate"]); ?>" <?php if(($vo['deal_status'] != 0 and $vo['deal_status'] != 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='只有 等待确认和进行中的标 才可以编辑'<?php endif; ?>/>
                </td>
            </tr>
            <tr>
                <td class="item_title">还款方式:</td>
                <td class="item_input">
                    <select name="loantype" id="repay_mode" onchange="javascript:changeRepay('chg');" <?php if(($vo['deal_status'] > 1 or $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='等待确认和进行中可以编辑'<?php endif; ?>>
                    <?php if(is_array($loan_type)): foreach($loan_type as $type_key=>$type_item): ?><?php if($vo['deal_type'] == 1): ?><?php if($type_key == 5): ?><option value="<?php echo ($type_key); ?>" <?php if($type_key == $vo['loantype']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item); ?></option><?php endif; ?>
                            <?php else: ?>
                            <option value="<?php echo ($type_key); ?>" <?php if($type_key == $vo['loantype']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item); ?></option><?php endif; ?><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="item_title">借款/转让期限:</td>
                <td class="item_input">
                    <select id="repay_period" name="repay_time" onchange="javascript:changeRepay('chg');" <?php if(($vo['deal_status'] > 1  or $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='等待确认和进行中可以编辑'<?php endif; ?>>
                    <?php if(is_array($repay_time)): foreach($repay_time as $time_key=>$time_item): ?><option value="<?php echo ($time_key); ?>" <?php if($time_key == $vo['repay_time']): ?>selected="selected"<?php endif; ?>><?php echo ($time_item); ?></option><?php endforeach; endif; ?>
                    </select>
                    <input type="text" class="changepmt textbox" SIZE="8" onchange="javascript:changeRepay();" name="repay_time" id="repay_period2" <?php if($vo["loantype"] == 5): ?>value="<?php echo ($vo["repay_time"]); ?>"<?php endif; ?> <?php if(($vo['deal_status'] > 1 or $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='等待确认和进行中可以编辑'<?php endif; ?>/> <span id='tian'>天</span>
                    <select id="repay_period3" name="repay_time" onchange="javascript:changeRepay();"  <?php if(($vo['deal_status'] > 1  or $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='等待确认和进行中可以编辑'<?php endif; ?>>
                    <?php if(is_array($repay_time_month)): foreach($repay_time_month as $time_key=>$time_item): ?><option value="<?php echo ($time_key); ?>" <?php if($time_key == $vo['repay_time']): ?>selected="selected"<?php endif; ?>><?php echo ($time_item); ?></option><?php endforeach; endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="item_title">房产地址:</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="house_address"  value="<?php echo ($deal_ext["house_address"]); ?>" maxlength="120" <?php if(($vo['deal_status'] != 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='只有 进行中或之后状态的子母标 不可以编辑'<?php endif; ?>/>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">房产证编号:</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="house_sn"  value="<?php echo ($deal_ext["house_sn"]); ?>" maxlength="120" <?php if(($vo['deal_status'] != 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='只有 进行中或之后状态的子母标 不可以编辑'<?php endif; ?>/>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">年化收益基本利率:</td>
                <td class="item_input">
                    <input type="text" class="textbox require" SIZE="8" name="income_base_rate" id='income_base_rate' value="<?php echo ($deal_ext["income_base_rate"]); ?>" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>% <span class="tip_span"></span>
                </td>
            </tr>
            <tr>
                <td class="item_title">年化收益浮动利率:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" name="income_float_rate" id='income_float_rate' value="<?php echo ($deal_ext["income_float_rate"]); ?>" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>% <span class="tip_span"></span>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <?php if($vo['deal_type'] == 0 or $vo['deal_type'] == 2): ?><tr style="display:none;" id="bianxiantong">
                    <td class="item_title">最高预期收益:</td>
                    <td class="item_inpalign="dissplay:none;"ut">
                    <input type="text" class="textbox require" value="<?php echo ($deal_ext["max_rate"]); ?>" SIZE="8" name="max_rate" id="max_rate"/>%<!--借款综合成本（年化）<span id='yearly_rate' class="tip_span"></span>%  &nbsp;  <font color='red'>项目借款综合成本（年化）：<?php echo ($vo["rate"]); ?> %</font>-->
                    </td>
                </tr><?php endif; ?>
            <tr>
                <td class="item_title">借款平台手续费:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" name="loan_fee_rate" id="loan_fee_rate" value="<?php echo ($vo["loan_fee_rate"]); ?>" onchange="javascript:get_period_rate('loan_fee_rate');" <?php if(($vo['deal_status'] != 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='只有 进行中或之后状态的子母标 不可以编辑'<?php endif; ?>/>% 期间：<span id='period_loan_fee_rate' class="tip_span"></span>%&nbsp;&nbsp;&nbsp;&nbsp;
                    <font color='red'>（借款人给p2p平台的手续费）</font>
                    <br/>
                    <?php if($vo['deal_status'] <= 2): ?><label><input type="radio" name="loan_fee_rate_type" id="loan_fee_type_1" value="1" onchange="chg_loan_fee();" onclick="cli_loan_fee(this);" <?php if($deal_ext["loan_fee_rate_type"] < 2): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />年化前收</label>
                        <label><input type="radio" name="loan_fee_rate_type" id="loan_fee_type_2" value="2" onchange="chg_loan_fee();" onclick="cli_loan_fee(this);" <?php if($deal_ext["loan_fee_rate_type"] == 2): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />年化后收</label>
                        <label><input type="radio" name="loan_fee_rate_type" id="loan_fee_type_3" value="3" onchange="chg_loan_fee();" onclick="cli_loan_fee(this);" <?php if($deal_ext["loan_fee_rate_type"] == 3): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />年化分期收</label>
                        <label><input type="radio" name="loan_fee_rate_type" id="loan_fee_type_4" value="4" onchange="chg_loan_fee();" onclick="cli_loan_fee(this);" <?php if($deal_ext["loan_fee_rate_type"] == 4): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />代销分期</label>
                        <label><input type="radio" name="loan_fee_rate_type" id="loan_fee_type_5" value="5" onchange="chg_loan_fee();" onclick="cli_loan_fee(this);" <?php if($deal_ext["loan_fee_rate_type"] == 5): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />固定比例前收</label>
                        <label><input type="radio" name="loan_fee_rate_type" id="loan_fee_type_6" value="6" onchange="chg_loan_fee();" onclick="cli_loan_fee(this);" <?php if($deal_ext["loan_fee_rate_type"] == 6): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />固定比例后收</label>
                        <label style="display:none"><input type="radio" name="loan_fee_rate_type" id="loan_fee_type_7" value="7" onchange="chg_loan_fee();" onclick="cli_loan_fee(this);" <?php if($deal_ext["loan_fee_rate_type"] == 7): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?>/>固定比例分期收</label><?php endif; ?>
                </td>
            </tr>
            <tr id="loan_fee_custom" >
            <td class="item_title">平台手续费自定义:</td>
            <td class="item_input" id="loan_fee_custom_input"></td>
            </tr>
            <tr>
                <td class="item_title">平台费折扣率:</td>
                <td class="item_input">
                  <?php if((in_array($vo['deal_status'], array(0,1)))): ?><?php if(($deal_ext["discount_rate"] <= 100) AND ($deal_ext["discount_rate"] >= 0)): ?><input type="text" class="textbox" SIZE="8"  name="discount_rate" id='discount_rate' value="<?php echo ($deal_ext["discount_rate"]); ?>" onchange="changeDiscountRate()" />%<span class="tip_span"></span>
                    <?php else: ?>
                      <input type="text" class="textbox" SIZE="8"  name="discount_rate" style="color:red"  id='discount_rate' value="<?php echo ($deal_ext["discount_rate"]); ?>" onchange="changeDiscountRate()" />%<span class="tip_span"></span><?php endif; ?>
                  <?php else: ?>
                    <?php if(($deal_ext["discount_rate"] <= 100) AND ($deal_ext["discount_rate"] >= 0)): ?><input type="text" class="textbox" SIZE="8"  name="discount_rate"  value="<?php echo ($deal_ext["discount_rate"]); ?>" readonly />%<span class="tip_span"></span>
                    <?php else: ?>
                      <input type="text" class="textbox" SIZE="8"  name="discount_rate" style="color:red"  value="<?php echo ($deal_ext["discount_rate"]); ?>" readonly />%<span class="tip_span"></span><?php endif; ?><?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="item_title">年化借款咨询费:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" name="consult_fee_rate" id="consult_fee_rate" value="<?php echo ($vo["consult_fee_rate"]); ?>" onchange="javascript:get_period_rate('consult_fee_rate');"  <?php if(($vo['deal_status'] != 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='只有 进行中或之后状态的子母标 不可以编辑'<?php endif; ?>/>% 期间：<span id='period_consult_fee_rate' class="tip_span"></span>%&nbsp;&nbsp;&nbsp;&nbsp;
                    <font color='red'>（借款人给咨询机构的咨询费）</font> <?php if($vo['consult_fee_period_rate'] > 0): ?>分期咨询费率 <?php echo ($vo["consult_fee_period_rate"]); ?>%,每期收取分期咨询费<?php echo ($consult_fee_period); ?><?php endif; ?>
                    <br/>
                    <?php if($vo['deal_status'] <= 2): ?><label><input type="radio" name="consult_fee_rate_type" id="consult_fee_type" value="1" onchange="chg_consult_fee();" <?php if($deal_ext["consult_fee_rate_type"] < 2): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />前收</label>
                        <label><input type="radio" name="consult_fee_rate_type" id="consult_fee_type" value="2" onchange="chg_consult_fee();" <?php if($deal_ext["consult_fee_rate_type"] == 2): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />后收</label>
                        <label><input type="radio" name="consult_fee_rate_type" id="consult_fee_type" value="3" onchange="chg_consult_fee();" onclick="calc_fenqi_fee('consult_fee');" <?php if($deal_ext["consult_fee_rate_type"] == 3): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />分期收</label><?php endif; ?>
                </td>
            </tr>
            <tr id="consult_fee_custom" <?php if($deal_ext["consult_fee_ext"] == ''): ?>style="display:none"<?php endif; ?>>
            <td class="item_title">借款咨询费自定义:</td>
            <td class="item_input">
                &nbsp;&nbsp;&nbsp;&nbsp;应收金额：<span id="consult_fee"><?php echo ($consult_fee); ?></span>&nbsp;&nbsp;&nbsp;&nbsp;应收比例：100% <br/><br/>
                <table class="form" cellpadding=0 cellspacing=0>
                    <tr>
                        <td width="100px">期次</td><td width="150px">金额(元)</td><td>比例</td>
                    </tr>
                    <?php if($deal_ext["consult_fee_ext"] == ''): ?><tr>
                            <td>0 (起息日)</td><td><input type='text' name='consult_fee_arr[0]' class='consult_fee_arr' id='consult_fee_arr[]' value='<?php echo ($consult_fee); ?>' /></td><td class='consult_p'>100%</td>
                        </tr>
                        <?php for ($i=1;$i<=$repay_times;$i++) {
                            echo "<tr><td>".$i."</td><td><input type='text' class='consult_fee_arr' name='consult_fee_arr[".$i."]' id='consult_fee_arr[]' value='0.00' /></td><td class='consult_p'>0%</td></tr>";
                            } ?>
                        <?php else: ?>
                        <?php $consult_fee_arr = json_decode($deal_ext['consult_fee_ext'], true);
                            foreach ($consult_fee_arr as $kk => $vv) {
                            if ($kk == 0) {
                            echo "<tr><td>0 (起息日)</td>";
                            } else {
                            echo "<tr><td>".$kk."</td>";
                            }
                            echo "<td><input type='text' class='consult_fee_arr' name='consult_fee_arr[".$kk."]' id='consult_fee_arr[]' value='".$vv."' /></td><td class='consult_p'>0%</td></tr>";
                            } ?><?php endif; ?>
                    <tr>
                        <td>总计</td><td><span id="total_consult_fee"><?php echo ($consult_fee); ?></span></td><td id="total_consult_p">100%</td>
                    </tr>
                </table>
                <font color='red'>（通知贷暂不支持）</font>
            </td>
            </tr>
            <tr id="agency_fee_info" <?php if(($vo["agency_id"] == 0 or $vo["agency_id"] == '') and ($vo["deal_type"] == 2)): ?>style="display:none"<?php endif; ?>>
                <td class="item_title">年化借款担保费:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" name="guarantee_fee_rate" id="guarantee_fee_rate" value="<?php echo ($vo["guarantee_fee_rate"]); ?>" onchange="javascript:get_period_rate('guarantee_fee_rate');"  <?php if(($vo['deal_status'] != 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='只有 进行中或之后状态的子母标 不可以编辑'<?php endif; ?>/>% 期间：<span id='period_guarantee_fee_rate' class="tip_span"></span>%&nbsp;&nbsp;&nbsp;&nbsp;
                    <font color='red'>（借款人给担保机构的担保费）</font>
                    <br/>
                    <?php if($vo['deal_status'] <= 2): ?><label><input type="radio" name="guarantee_fee_rate_type" id="guarantee_fee_type" value="1" onchange="chg_guarantee_fee();" <?php if($deal_ext["guarantee_fee_rate_type"] < 2): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />前收</label>
                        <label><input type="radio" name="guarantee_fee_rate_type" id="guarantee_fee_type" value="2" onchange="chg_guarantee_fee();" <?php if($deal_ext["guarantee_fee_rate_type"] == 2): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />后收</label>
                        <label><input type="radio" name="guarantee_fee_rate_type" id="guarantee_fee_type" value="3" onchange="chg_guarantee_fee();" onclick="calc_fenqi_fee('guarantee_fee');" <?php if($deal_ext["guarantee_fee_rate_type"] == 3): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />分期收</label><?php endif; ?>
                </td>
            </tr>
            <tr id="guarantee_fee_custom" <?php if($deal_ext["guarantee_fee_ext"] == ''): ?>style="display:none"<?php endif; ?>>
            <td class="item_title">借款担保费自定义:</td>
            <td class="item_input">
                &nbsp;&nbsp;&nbsp;&nbsp;应收金额：<span id="guarantee_fee"><?php echo ($guarantee_fee); ?></span>&nbsp;&nbsp;&nbsp;&nbsp;应收比例：100% <br/><br/>
                <table class="form" cellpadding=0 cellspacing=0>
                    <tr>
                        <td width="100px">期次</td><td width="150px">金额(元)</td><td>比例</td>
                    </tr>
                    <?php if($deal_ext["guarantee_fee_ext"] == ''): ?><tr>
                            <td>0 (起息日)</td><td><input type='text' name='guarantee_fee_arr[0]' class='guarantee_fee_arr' id='guarantee_fee_arr[]' value='<?php echo ($guarantee_fee); ?>' /></td><td class='guarantee_p'>100%</td>
                        </tr>
                        <?php for ($i=1;$i<=$repay_times;$i++) {
                            echo "<tr><td>".$i."</td><td><input type='text' class='guarantee_fee_arr' name='guarantee_fee_arr[".$i."]' id='guarantee_fee_arr[]' value='0.00' /></td><td class='guarantee_p'>0%</td></tr>";
                            } ?>
                        <?php else: ?>
                        <?php $guarantee_fee_arr = json_decode($deal_ext['guarantee_fee_ext'], true);
                            foreach ($guarantee_fee_arr as $kk => $vv) {
                            if ($kk == 0) {
                            echo "<tr><td>0 (起息日)</td>";
                            } else {
                            echo "<tr><td>".$kk."</td>";
                            }
                            echo "<td><input type='text' class='guarantee_fee_arr' name='guarantee_fee_arr[".$kk."]' id='guarantee_fee_arr[]' value='".$vv."' /></td><td class='guarantee_p'>0%</td></tr>";
                            } ?><?php endif; ?>
                    <tr>
                        <td>总计</td><td><span id="total_guarantee_fee"><?php echo ($guarantee_fee); ?></span></td><td id="total_guarantee_p">100%</td>
                    </tr>
                </table>
                <font color='red'>（通知贷暂不支持）</font>
            </td>
            </tr>
            <tr id="canal_fee_info" <?php if($vo["canal_agency_id"] == 0 or $vo["canal_agency_id"] == ''): ?>style="display:none"<?php endif; ?>>
                <td class="item_title">年化借款渠道费:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" name="canal_fee_rate" id="canal_fee_rate" value="<?php echo ($vo["canal_fee_rate"]); ?>" onchange="javascript:get_period_rate('canal_fee_rate');"  <?php if(($vo['deal_status'] != 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='只有 进行中或之后状态的子母标 不可以编辑'<?php endif; ?>/>% 期间：<span id='period_canal_fee_rate' class="tip_span"></span>%&nbsp;&nbsp;&nbsp;&nbsp;
                    <font color='red'>（借款人给渠道机构的渠道费）</font>
                    <br/>
                    <?php if($vo['deal_status'] <= 2): ?><label><input type="radio" name="canal_fee_rate_type" id="canal_fee_type" value="1" onchange="chg_canal_fee();" <?php if($deal_ext["canal_fee_rate_type"] < 2): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />前收</label>
                        <label><input type="radio" name="canal_fee_rate_type" id="canal_fee_type" value="2" onchange="chg_canal_fee();" <?php if($deal_ext["canal_fee_rate_type"] == 2): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />后收</label>
                        <label><input type="radio" name="canal_fee_rate_type" id="canal_fee_type" value="3" onchange="chg_canal_fee();" onclick="calc_fenqi_fee('canal_fee');" <?php if($deal_ext["canal_fee_rate_type"] == 3): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />分期收</label><?php endif; ?>
                </td>
            </tr>
            <tr id="canal_fee_custom" <?php if($deal_ext["canal_fee_ext"] == ''): ?>style="display:none"<?php endif; ?>>
            <td class="item_title">借款渠道费自定义:</td>
            <td class="item_input">
                &nbsp;&nbsp;&nbsp;&nbsp;应收金额：<span id="canal_fee"><?php echo ($canal_fee); ?></span>&nbsp;&nbsp;&nbsp;&nbsp;应收比例：100% <br/><br/>
                <table class="form" cellpadding=0 cellspacing=0>
                    <tr>
                        <td width="100px">期次</td><td width="150px">金额(元)</td><td>比例</td>
                    </tr>
                    <?php if($deal_ext["canal_fee_ext"] == ''): ?><tr>
                            <td>0 (起息日)</td><td><input type='text' name='canal_fee_arr[0]' class='canal_fee_arr' id='canal_fee_arr[]' value='<?php echo ($canal_fee); ?>' /></td><td class='canal_p'>100%</td>
                        </tr>
                        <?php for ($i=1;$i<=$repay_times;$i++) {
                            echo "<tr><td>".$i."</td><td><input type='text' class='canal_fee_arr' name='canal_fee_arr[".$i."]' id='canal_fee_arr[]' value='0.00' /></td><td class='canal_p'>0%</td></tr>";
                            } ?>
                        <?php else: ?>
                        <?php $canal_fee_arr = json_decode($deal_ext['canal_fee_ext'], true);
                            foreach ($canal_fee_arr as $kk => $vv) {
                            if ($kk == 0) {
                            echo "<tr><td>0 (起息日)</td>";
                            } else {
                            echo "<tr><td>".$kk."</td>";
                            }
                            echo "<td><input type='text' class='canal_fee_arr' name='canal_fee_arr[".$kk."]' id='canal_fee_arr[]' value='".$vv."' /></td><td class='canal_p'>0%</td></tr>";
                            } ?><?php endif; ?>
                    <tr>
                        <td>总计</td><td><span id="total_canal_fee"><?php echo ($canal_fee); ?></span></td><td id="total_canal_p">100%</td>
                    </tr>
                </table>
                <font color='red'>（通知贷暂不支持）</font>
            </td>
            </tr>
            <tr>
                <td class="item_title">年化支付服务费:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" name="pay_fee_rate" id="pay_fee_rate" value="<?php echo ($vo["pay_fee_rate"]); ?>" onchange="javascript:get_period_rate('pay_fee_rate');"  <?php if(($vo['deal_status'] != 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='只有 进行中或之后状态的子母标 不可以编辑'<?php endif; ?>/>% 期间：<span id='period_pay_fee_rate' class="tip_span"></span>%&nbsp;&nbsp;&nbsp;&nbsp;
                    <font color='red'>（年化支付服务费）</font>
                    <br/>
                    <?php if($vo['deal_status'] <= 2): ?><label><input type="radio" name="pay_fee_rate_type" id="pay_fee_type" value="1" onchange="chg_pay_fee();" <?php if($deal_ext["pay_fee_rate_type"] < 2): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />前收</label>
                        <label><input type="radio" name="pay_fee_rate_type" id="pay_fee_type" value="2" onchange="chg_pay_fee();" <?php if($deal_ext["pay_fee_rate_type"] == 2): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />后收</label>
                        <label><input type="radio" name="pay_fee_rate_type" id="pay_fee_type" value="3" onchange="chg_pay_fee();" onclick="calc_fenqi_fee('pay_fee');" <?php if($deal_ext["pay_fee_rate_type"] == 3): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />分期收</label><?php endif; ?>
                </td>
            </tr>
            <tr id="pay_fee_custom" <?php if($deal_ext["pay_fee_ext"] == ''): ?>style="display:none"<?php endif; ?>>
            <td class="item_title">年化支付服务费自定义:</td>
            <td class="item_input">
                &nbsp;&nbsp;&nbsp;&nbsp;应收金额：<span id="pay_fee"><?php echo ($pay_fee); ?></span>&nbsp;&nbsp;&nbsp;&nbsp;应收比例：100% <br/><br/>
                <table class="form" cellpadding=0 cellspacing=0>
                    <tr>
                        <td width="100px">期次</td><td width="150px">金额(元)</td><td>比例</td>
                    </tr>
                    <?php if($deal_ext["pay_fee_ext"] == ''): ?><tr>
                            <td>0 (起息日)</td><td><input type='text' name='pay_fee_arr[0]' class='pay_fee_arr' id='pay_fee_arr[]' value='<?php echo ($pay_fee); ?>' /></td><td class='pay_p'>100%</td>
                        </tr>
                        <?php for ($i=1;$i<=$repay_times;$i++) {
                            echo "<tr><td>".$i."</td><td><input type='text' class='pay_fee_arr' name='pay_fee_arr[".$i."]' id='pay_fee_arr[]' value='0.00' /></td><td class='pay_p'>0%</td></tr>";
                            } ?>
                        <?php else: ?>
                        <?php $pay_fee_arr = json_decode($deal_ext['pay_fee_ext'], true);
                            foreach ($pay_fee_arr as $kk => $vv) {
                            if ($kk == 0) {
                            echo "<tr><td>0 (起息日)</td>";
                            } else {
                            echo "<tr><td>".$kk."</td>";
                            }
                            echo "<td><input type='text' class='pay_fee_arr' name='pay_fee_arr[".$kk."]' id='pay_fee_arr[]' value='".$vv."' /></td><td class='pay_p'>0%</td></tr>";
                            } ?><?php endif; ?>
                    <tr>
                        <td>总计</td><td><span id="total_pay_fee"><?php echo ($pay_fee); ?></span></td><td id="total_pay_p">100%</td>
                    </tr>
                </table>
                <font color='red'>（通知贷暂不支持）</font>
            </td>
            </tr>
            <tr id="management_fee_rate_tr" style="display:none">
                <td class="item_title">年化管理服务费:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" name="management_fee_rate" id="management_fee_rate" value="<?php echo ($vo["management_fee_rate"]); ?>" onchange="javascript:get_period_rate('management_fee_rate');"  <?php if(($vo['deal_status'] != 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='只有 进行中或之后状态的子母标 不可以编辑'<?php endif; ?>/>% 期间：<span id='period_management_fee_rate' class="tip_span"></span>%&nbsp;&nbsp;&nbsp;&nbsp;
                    <font color='red'>（年化管理服务费）</font>
                    <br/>
                    <?php if($vo['deal_status'] <= 2): ?><label><input type="radio" name="management_fee_rate_type" id="management_fee_type" value="1" onchange="chg_management_fee();" <?php if($deal_ext["management_fee_rate_type"] < 2): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />前收</label>
                        <label><input type="radio" name="management_fee_rate_type" id="management_fee_type" value="2" onchange="chg_management_fee();" <?php if($deal_ext["management_fee_rate_type"] == 2): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />后收</label>
                        <label><input type="radio" name="management_fee_rate_type" id="management_fee_type" value="3" onchange="chg_management_fee();" onclick="calc_fenqi_fee('management_fee');" <?php if($deal_ext["management_fee_rate_type"] == 3): ?>checked="checked"<?php endif; ?> <?php if(($vo['is_has_loans'] == 1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled="disabled"<?php endif; ?> />分期收</label><?php endif; ?>
                </td>
            </tr>
            <tr id="management_fee_custom" <?php if($deal_ext["management_fee_ext"] == ''): ?>style="display:none"<?php endif; ?>>
            <td class="item_title">年化管理服务费自定义:</td>
            <td class="item_input">
                &nbsp;&nbsp;&nbsp;&nbsp;应收金额：<span id="management_fee"><?php echo ($management_fee); ?></span>&nbsp;&nbsp;&nbsp;&nbsp;应收比例：100% <br/><br/>
                <table class="form" cellpadding=0 cellspacing=0>
                    <tr>
                        <td width="100px">期次</td><td width="150px">金额(元)</td><td>比例</td>
                    </tr>
                    <?php if($deal_ext["management_fee_ext"] == ''): ?><tr>
                            <td>0 (起息日)</td><td><input type='text' name='management_fee_arr[0]' class='management_fee_arr' id='management_fee_arr[]' value='<?php echo ($management_fee); ?>' /></td><td class='management_p'>100%</td>
                        </tr>
                        <?php for ($i=1;$i<=$repay_times;$i++) {
                            echo "<tr><td>".$i."</td><td><input type='text' class='management_fee_arr' name='management_fee_arr[".$i."]' id='management_fee_arr[]' value='0.00' /></td><td class='management_p'>0%</td></tr>";
                            } ?>
                        <?php else: ?>
                        <?php $management_fee_arr = json_decode($deal_ext['management_fee_ext'], true);
                            foreach ($management_fee_arr as $kk => $vv) {
                            if ($kk == 0) {
                            echo "<tr><td>0 (起息日)</td>";
                            } else {
                            echo "<tr><td>".$kk."</td>";
                            }
                            echo "<td><input type='text' class='management_fee_arr' name='management_fee_arr[".$kk."]' id='management_fee_arr[]' value='".$vv."' /></td><td class='management_p'>0%</td></tr>";
                            } ?><?php endif; ?>
                    <tr>
                        <td>总计</td><td><span id="total_management_fee"><?php echo ($management_fee); ?></span></td><td id="total_management_p">100%</td>
                    </tr>
                </table>
                <font color='red'>（通知贷暂不支持）</font>
            </td>
            </tr>
            <tr style="display:none">
                <td class="item_title">年化出借人平台管理费:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" onchange="javascript:changeRate('annualized_rate');" name="manage_fee_rate" id="manage_fee_rate" class="changepmt" value="<?php echo ($vo["manage_fee_rate"]); ?>"  <?php if(($vo['publish_wait'] == 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='只有审核之后的子母标不可以编辑'<?php endif; ?>/>%
                    <input type="text" class="textbox" SIZE="50" name="manage_fee_text" value="<?php echo ($vo["manage_fee_text"]); ?>" <?php if(($vo['deal_status'] != 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='只有 进行中或之后状态的子母标 不可以编辑'<?php endif; ?>>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr style="display:none">
                <td class="item_title">年化收益平台补贴利率:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="10" name="income_subsidy_rate" id='income_subsidy_rate' value="<?php echo ($deal_ext["income_subsidy_rate"]); ?>" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>/>% <span class="tip_span"></span>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr style="display:none">
                <td class="item_title">年化顾问利率:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" name="advisor_fee_rate" id='advisor_fee_rate' value="0" onchange="javascript:get_period_rate('advisor_fee_rate');" <?php if(($vo['publish_wait'] == 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='只有审核之后的子母标不可以编辑'<?php endif; ?>/>% 期间：<span id='period_advisor_fee_rate' class="tip_span"></span>%
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">借款年利率:</td>
                <td class="item_input">
                    <?php if($vo['deal_type'] == 1): ?><input type="text" class="textbox" style="border: 1px solid #DDD;background-color: #F5F5F5;" readonly value="<?php echo ($vo['rate']); ?>" onchange="javascript:changeRate('income_fee_rate');" SIZE="8" name="rate" id="annualized_rate"/>% &nbsp;  <font color='red' id ="day_rate">日利率：<?php echo ($vo["rate_day"]); ?> </font>%
                        <?php else: ?>
                        <input type="text" class="textbox" style="border: 1px solid #DDD;background-color: #F5F5F5;" readonly SIZE="8" onchange="javascript:changeRate('income_fee_rate');" name="rate" value="<?php echo ($vo["rate"]); ?>"  id="annualized_rate" <?php if(($vo['publish_wait'] == 0 and $vo['parent_id'] != -1)): ?>disabled title='只有审核之后的子母标不可以编辑'<?php endif; ?>/>%
                        借款综合成本（年化）：<span id='yearly_rate' class="tip_span"></span>%  &nbsp; <?php if($pro['id']): ?><font color='red'>项目借款综合成本（年化）：<?php echo ($pro["rate"]); ?> %</font><?php endif; ?><?php endif; ?>

                </td>
            </tr>
            <tr>
                <td class="item_title">年化出借人收益率:</td>
                <td class="item_input">
                    <input type="text" class="textbox" readonly style="border: 1px solid #DDD;background-color: #F5F5F5;" SIZE="8" onchange="javascript:changeRate('annualized_rate');" name="income_fee_rate" id='income_fee_rate' value="<?php echo ($vo["income_fee_rate"]); ?>" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>%
                </td>
            </tr>
            <tr>
                <td class="item_title">提前还款违约金系数:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" name="prepay_rate" id='prepay_rate' value="<?php echo ($vo["prepay_rate"]); ?>" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>%<span class="tip_span"></span>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">提前还款罚息天数:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" name="prepay_penalty_days" id='prepay_penalty_days' value="<?php echo ($vo["prepay_penalty_days"]); ?>" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/><span class="tip_span"></span>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">提前还款/回购限制:</td>
                <td class="item_input">
                    <input type="text" class="textbox require" SIZE="8" name="prepay_days_limit" id='prepay_days_limit' value="<?php echo ($vo["prepay_days_limit"]); ?>" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/><span class="tip_span">天之内，不可提前还款</span>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">逾期还款罚息系数:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" name="overdue_rate" id='overdue_rate' value="<?php echo ($vo["overdue_rate"]); ?>" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>%
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">代偿时间:</td>
                <td class="item_input">
                    <input type="text" class="textbox" SIZE="8" name="overdue_day" id='overdue_day' value="<?php echo ($vo["overdue_day"]); ?>" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>天之后，代偿应还本金和逾期罚息(从应还款之日起算)
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">合同类型</td>
                <td class="item_input">
                    <select id="contract_tpl_type" name="contract_tpl_type" <?php if(($vo['deal_status'] != 0 and $vo['parent_id'] != -1) or ($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0)): ?>disabled title='只有 进行中或之后状态的子母标 不可以编辑'<?php endif; ?>>
                    <?php if(is_array($contract_tpl_type)): foreach($contract_tpl_type as $t_key=>$t_item): ?><option value="<?php echo ($t_item["id"]); ?>" <?php if($t_item["id"] == $vo['contract_tpl_type']): ?>selected="selected"<?php endif; ?>><?php echo ($t_item["typeName"]); ?></option><?php endforeach; endif; ?>
                    <option value="" <?php if($vo['contract_tpl_type'] == ''): ?>selected="selected"<?php endif; ?>>没有合同</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="item_title">基础合同的编号</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="leasing_contract_num"  value="<?php echo ($deal_ext["leasing_contract_num"]); ?>" maxlength="120" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">转让资产类别</td>
                <td class="item_input">
                    <select id="contract_transfer_type"  name="contract_transfer_type" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>disabled<?php endif; ?>>
                        <option value="0" <?php if($deal_ext["contract_transfer_type"] == 0): ?>selected="selected"<?php endif; ?>>无</option>
                        <option value="1"  <?php if($deal_ext["contract_transfer_type"] == 1): ?>selected="selected"<?php endif; ?>>债权</option>
                        <option value="2" <?php if($deal_ext["contract_transfer_type"] == 2): ?>selected="selected"<?php endif; ?>>资产收益权</option>
                    </select>
                    <font color='red'>（债权转让合同显示专用）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">原始债务人</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="lessee_real_name"  value="<?php echo ($deal_ext["lessee_real_name"]); ?>" maxlength="120" size="100" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">基础合同交易金额</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="leasing_money"  value="<?php echo ($deal_ext["leasing_money"]); ?>" maxlength="120" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>元
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">基础合同名称</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="leasing_contract_title"  value="<?php echo ($deal_ext["leasing_contract_title"]); ?>" maxlength="120" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">委托贷款委托合同的编号</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="entrusted_loan_entrusted_contract_num"  value="<?php echo ($deal_ext["entrusted_loan_entrusted_contract_num"]); ?>" maxlength="120" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">委托贷款借款合同的编号</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="entrusted_loan_borrow_contract_num"  value="<?php echo ($deal_ext["entrusted_loan_borrow_contract_num"]); ?>" maxlength="120" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">基础合同的借款到期日:</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="base_contract_repay_time" id="base_contract_repay_time" value="<?php echo ($deal_ext["base_contract_repay_time"]); ?>" onfocus="this.blur(); return showCalendar('base_contract_repay_time', '%Y-%m-%d', false, false, 'btn_base_contract_repay_time');" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?> />
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr>
                <td class="item_title">中途逾期强还天数</td>
                <td class="item_input">
                    <input type="text" class="textbox require" name="overdue_break_days"  value="<?php echo ($deal_ext["overdue_break_days"]); ?>" maxlength="120" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>天<span class="tip_span">（逾期还款T日以上，视为借款人违约，出借人有权提前解除合同）</span>
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr style="display:none">
                <td class="item_title">提前还款年化出借人平台管理费</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="prepay_manage_fee_rate"  value="<?php echo ($deal_ext["prepay_manage_fee_rate"]); ?>" maxlength="120" <?php if($pro['business_status'] != $project_business_status['waitting'] and $vo['deal_status'] != 0): ?>readonly<?php endif; ?>/>%
                    <font color='red'>（通知贷暂不支持）</font>
                </td>
            </tr>
            <tr style="display:none">
                <td class="item_title"><?php echo L("DEAL_DESCRIPTION");?>:</td>
                <td class="item_input">
                    <textarea id="description" style="width:500px;height:100px" name="description" ><?php echo ($vo["description"]); ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="item_title">借款状态:</td>
                <td class="item_input">
                    <?php if($vo['deal_status'] == 5): ?>已还清
                        <?php elseif($vo['deal_status'] == 3): ?>流标
                        <?php else: ?>
                        <label>
                            <input type="radio" name="deal_status" class="deal_status" value="0" onclick="cli_deal_status(this)" <?php if($vo['deal_status'] == 0): ?>checked="checked"<?php endif; ?> /><?php echo L("DEAL_STATUS_0");?>
                        </label>

                        <label>
                            <input type="radio" name="deal_status" class="deal_status" value="1" onclick="cli_deal_status(this)" <?php if($vo['deal_status'] == 1): ?>checked="checked"<?php endif; ?> /><?php echo L("DEAL_STATUS_1");?>
                        </label>

                        <?php if ($vo['deal_status'] <> 4): ?>
                        <label>
                            <input type="radio" name="deal_status" class="deal_status" value="3" onclick="cli_deal_status(this)" <?php if($vo['deal_status'] == 3): ?>checked="checked"<?php endif; ?> /><?php echo L("DEAL_STATUS_3");?>
                        </label>
                        <?php endif; ?>

                        <label>
                            <input type="radio" name="deal_status" class="deal_status" value="4" onclick="cli_deal_status(this)" <?php if($vo['deal_status'] == 4): ?>checked="checked"<?php endif; ?> /><?php echo L("DEAL_STATUS_4");?>
                        </label><?php endif; ?>
                </td>
            </tr>
            <tr id="start_time_box" <?php if($vo['deal_status'] != 1): ?>style="display:none"<?php endif; ?>>
            <td class="item_title">开始时间:</td>
            <td class="item_input">
                <input type="text" class="textbox <?php if($vo['deal_status'] == 1): ?>require<?php endif; ?>" name="start_time" value="<?php echo ($vo["start_time"]); ?>" id="start_time"  onfocus="this.blur(); return showCalendar('start_time', '%Y-%m-%d %H:%M:%S', false, false, 'btn_start_time');" />
            </td>
            </tr>
            <tr id="bad_time_box" <?php if($vo['deal_status'] != 3): ?>style="display:none"<?php endif; ?>>
            <td class="item_title"><?php echo L("DEAL_STATUS_3");?>时间:</td>
            <td class="item_input">
                <input type="text" class="textbox <?php if($vo['deal_status'] == 3): ?>require<?php endif; ?>" name="bad_time" id="bad_time" value="<?php echo ($vo["bad_time"]); ?>" onfocus="this.blur(); return showCalendar('bad_time', '%Y-%m-%d %H:%M:%S', false, false, 'btn_bad_time');" />
            </td>
            </tr>
            <tr id="bad_info_box" <?php if($vo['deal_status'] != 3): ?>style="display:none"<?php endif; ?>>
            <td class="item_title"><?php echo L("DEAL_STATUS_3");?>原因:</td>
            <td class="item_input">
                <textarea type="text" class="textbox" name="bad_msg" id="bad_msg" value="" rows="3" cols="50"><?php echo ($vo["bad_msg"]); ?></textarea>
            </td>
            </tr>
            <tr id="repay_start_time_box" <?php if($vo['deal_status'] != 4): ?>style="display:none"<?php endif; ?>>
            <td class="item_title">确认时间:</td>
            <td class="item_input">
                <input type="text" class="textbox <?php if($vo['deal_status'] == 4): ?>require<?php endif; ?>" name="repay_start_time" id="repay_start_time" value="<?php echo ($vo["repay_start_time"]); ?>" onfocus="this.blur(); return showCalendar('repay_start_time', '%Y-%m-%d', false, false, 'btn_repay_start_time');" />
                <input type="button" class="button" id="btn_repay_start_time" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('repay_start_time', '%Y-%m-%d', false, false, 'btn_repay_start_time');" />
                <input type="button" class="button" value="<?php echo L("CLEAR_TIME");?>" onclick="$('#repay_start_time').val('');" />
                <br>
                <span class="tip_span">还款日从确认时间开始的下个月算起，确认时间不要设置为29,30,31号</span>
            </td>
            </tr>
            <tr id="first_repay_day_box" <?php if(($vo['loantype'] != 8 && $vo['deal_status'] != 4) || ($vo['loantype'] != 8 && $vo['loantype'] != 4 && $vo['loantype'] != 6) ): ?>style="display:none"<?php endif; ?>>
            <td class="item_title">第一期还款日:</td>
            <td class="item_input">
                <?php if($vo['loantype'] == 8): ?><input id="first_repay_interest_day" type="text" class="textbox" name="first_repay_interest_day" disabled value="<?php if($deal_ext['first_repay_interest_day'] != 0): ?><?php echo ($deal_ext["first_repay_interest_day"]); ?><?php endif; ?>"/>
                <?php else: ?>
                <input type="text" class="textbox" name="first_repay_interest_day" <?php if($vo['deal_status'] == 4 || $vo['deal_status'] == 5 ): ?>disable="true"<?php endif; ?> value="<?php if($deal_ext['first_repay_interest_day'] != 0): ?><?php echo ($deal_ext["first_repay_interest_day"]); ?><?php endif; ?>" id="first_repay_interest_day"  onfocus="this.blur(); return showCalendar('first_repay_interest_day', '%Y-%m-%d', false, false, 'btn_first_repay_interest_day');" />
                <?php if($vo['deal_status'] == 2): ?><input type="button" class="button" id="btn_first_repay_interest_day" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('first_repay_interest_day', '%Y-%m-%d', false, false, 'btn_first_repay_interest_day');" />
                    <input type="button" class="button" value="<?php echo L("CLEAR_TIME");?>" onclick="$('#first_repay_interest_day').val('');" /><?php endif; ?><?php endif; ?>
            </td>
            </tr>
            <tr id='start_loan_time_box' <?php if($vo['deal_status'] != 0): ?>style="display:none"<?php endif; ?>>
            <td class="item_title">开标时间:</td>
            <td class="item_input">
                <input type="text" class="textbox" name="start_loan_time" value="<?php echo ($deal_ext["start_loan_time"]); ?>" id="start_loan_time"  onfocus="this.blur(); return showCalendar('start_loan_time', '%Y-%m-%d %H:%M:00', false, false, 'btn_start_loan_time');" />
                <input type="button" class="button" id="btn_start_loan_time" value="<?php echo L("SELECT_TIME");?>" onclick="return showCalendar('start_loan_time', '%Y-%m-%d %H:%M:00', false, false, 'btn_start_loan_time');" />
                <input type="button" class="button" value="<?php echo L("CLEAR_TIME");?>" onclick="$('#start_loan_time').val('');" />
                <span class="tip_span" style="color:red;">（定时标适用）</span>
            </td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("IS_EFFECT");?>:</td>
                <td class="item_input">
                    <lable><input type="radio" name="is_effect" value="1" <?php if($vo['is_effect'] == 1): ?>checked="checked"<?php endif; ?> /><?php echo L("IS_EFFECT_1");?></lable>
                    <lable><input type="radio" name="is_effect" value="0" <?php if($vo['is_effect'] == 0): ?>checked="checked"<?php endif; ?> /><?php echo L("IS_EFFECT_0");?></lable>
                    <!-- <span class="tip_span">您如果修改了除“借款状态”和“开始时间”之外的其他内容，该状态将保存为无效。</span> -->
                </td>
            </tr>
            <tr style="display:none">
                <td class="item_title">是否并发投资:</td>
                <td class="item_input">
                    <lable><input type="radio" name="is_bid_new" value="1" <?php if($deal_ext['is_bid_new'] == 1): ?>checked="checked"<?php endif; ?> />是</lable>
                    <lable><input type="radio" name="is_bid_new" value="0" <?php if($deal_ext['is_bid_new'] == 0): ?>checked="checked"<?php endif; ?> />否</lable>
                </td>
            </tr>
            <?php if($vo['deal_type'] != 1): ?><tr>

                    <td class="item_title">优惠码结算时间:</td>
                    <td class="item_input">
                        <label><input type="radio"  name="pay_type" class="pay_type" value="0" <?php if($deal_coupon['pay_type'] == 0): ?>checked="checked"<?php endif; ?> <?php if($pro['business_status'] > $project_business_status['repaying']): ?>readonly<?php endif; ?>/>放款时结算</label>
                        <label><input type="radio"  name="pay_type" class="pay_type" value="1" <?php if($deal_coupon['pay_type'] == 1): ?>checked="checked"<?php endif; ?> <?php if($pro['business_status'] > $project_business_status['repaying']): ?>readonly<?php endif; ?>/>还清时结算</label>
                    </td>
                </tr>
                <tr>
                    <td class="item_title">优惠码结算方式</td>
                    <td class="item_input">
                        <label><input type="radio"  name="pay_auto" value="2" <?php if(empty($deal_coupon['pay_auto']) || $deal_coupon['pay_auto'] == 2): ?>checked="checked"<?php endif; ?> <?php if($pro['business_status'] > $project_business_status['repaying']): ?>readonly<?php endif; ?>/>手工结算</label>
                        <label><input type="radio"  name="pay_auto" value="1" <?php if($deal_coupon['pay_auto'] == 1): ?>checked="checked"<?php endif; ?> <?php if($pro['business_status'] > $project_business_status['repaying']): ?>readonly<?php endif; ?>/>自动结算</label>
                    </td>
                </tr>
                <tr>
                    <td class="item_title">优惠码返利天数</td>
                    <td class="item_input">
                        <input type="text" class="textbox" name="rebate_days" id="rebate_days" value="<?php echo ($deal_coupon["rebate_days"]); ?>" <?php if(($deal_coupon['is_paid'] == 1) OR ($vo['deal_status'] < 2)): ?>readonly="true"<?php endif; ?> <?php if($pro['business_status'] > $project_business_status['repaying']): ?>readonly<?php endif; ?>/>
                        <span class="tip_span" style="color:red;">按月计算的标，返利天数=月数*30(如3个月填90天，一年填360天)。该值变更后，相应的优惠码记录的返点比例金额才会按该返利天数重新计算，但返点金额不变。</span>
                    </td>
                </tr>
                <?php if($loan_type_info["type_tag"] == $xffq_tag): ?><tr>
                    <td class="item_title">优惠码返利结算延迟时间</td>
                    <td class="item_input">
                        <input type="text" class="textbox" name="delay_days" id="delay_days" value="7" disabled="true" />
                        <span class="tip_span" style="color:red;">天后结算返利。</span>
                    </td>
                </tr><?php endif; ?><?php endif; ?>
            <tr>
                <td class="item_title">备注:</td>
                <td class="item_input">
                    <textarea id="note" style="width:500px;height:30px" name="note" ><?php echo ($vo["note"]); ?></textarea>
                </td>
            </tr>
            <tr <?php if($vo['deal_type'] != 2): ?>style="display:none"<?php endif; ?>>
                <td class="item_title">交易所备案产品编号:</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="jys_record_number" value="<?php echo ($vo["jys_record_number"]); ?>" />
                </td>
            </tr>
            <tr>
                <td class="item_title">放款审批单编号:</td>
                <td class="item_input">
                    <input type="text" class="textbox" name="approve_number" value="<?php echo ($vo["approve_number"]); ?>" />
                </td>
            </tr>
            <?php if($vo['deal_type'] == 0): ?><tr>
                    <td class="item_title">存管报备:</td>
                    <td class="item_input">
                        <?php if($vo['report_status'] == 1): ?><span style="color:red">已经成功报备到存管行不可撤销</span>
                            <?php else: ?>
                            报备<input type="radio"  name="report_type" value="1" checked>&nbsp;
                            不报备<input type="radio" name="report_type" value="0">&nbsp;<?php endif; ?>
                    </td>
                </tr><?php endif; ?>
            <!-- deal muti-site start -->
            <tr>
                <td class="item_title">所属网站:</td>
                <td class="item_input">
                    <?php if(is_array($site_list)): foreach($site_list as $site_name=>$site_id): ?><label><input type="radio" name="deal_site[]" value="<?php echo ($site_id); ?>" <?php if(isset($deal_site_list[$site_id])): ?>checked<?php endif; ?> ><?php echo ($site_name); ?></label><?php endforeach; endif; ?>
                </td>
            </tr>
            <!-- end -->
            <tr>
                <td colspan=2 class="bottomTd"></td>
            </tr>
        </table>
        <table class="form conf_tab" cellpadding=0 cellspacing=0 rel="2">
            <tr>
                <td colspan=2 class="topTd"></td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("DEAL_SEO_TITLE");?>:</td>
                <td class="item_input"><textarea class="textarea" name="seo_title" ><?php echo ($vo["seo_title"]); ?></textarea></td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("DEAL_SEO_KEYWORD");?>:</td>
                <td class="item_input"><textarea class="textarea" name="seo_keyword" ><?php echo ($vo["seo_keyword"]); ?></textarea></td>
            </tr>
            <tr>
                <td class="item_title"><?php echo L("DEAL_SEO_DESCRIPTION");?>:</td>
                <td class="item_input"><textarea class="textarea" name="seo_description" ><?php echo ($vo["seo_description"]); ?></textarea></td>
            </tr>
            <tr>
                <td colspan=2 class="bottomTd"></td>
            </tr>
        </table>
        <div class="blank5"></div>
        <table class="form" cellpadding=0 cellspacing=0>
            <tr>
                <td colspan=2 class="topTd"></td>
            </tr>
            <tr>
                <td colspan=2 class="bottomTd"></td>
            </tr>
        </table>
    </form>
</div>

<script type="text/javascript" language="javascript">
    function iFrameHeight(frame_id) {
        var ifm = document.getElementById(frame_id);
        var subWeb = document.frames ? document.frames[frame_id].document : ifm.contentDocument;
        if (ifm != null && subWeb != null && subWeb.body != null) {
            //ifm.height = subWeb.body.scrollHeight;
            //ifm.height = subWeb.body.clientHeight;
            ifm.height = /chrome/gi.test(window.navigator.userAgent) ? subWeb.body.clientHeight : subWeb.body.scrollHeight;

        }
    }
</script>

<script type="text/javascript">
    var auto_changeRate = false;
    var auto_change_loanrate = false;
    var auto_update_income_base_rate = false;
    var isP2P = false;
    var hasReportToBank = false;

    <?php if($deal_ext['income_base_rate'] == 0 and $deal_ext['income_float_rate'] == 0): ?>auto_update_income_base_rate = true;<?php endif; ?>

    <?php if($vo['publish_wait'] == 1): ?>auto_changeRate = true;<?php endif; ?>

    <?php if($vo['deal_type'] == 0): ?>isP2P = true;<?php endif; ?>

    <?php if($vo['report_status'] == 1): ?>hasReportToBank = true;<?php endif; ?>


    $(document).ready(function(){

        chg_fee();
        //自动执行
        changeRepay();

        /* if(auto_changeRate){
         changeRate('income_fee_rate');
         } */

        change_year_to_period();

        $("#income_base_rate,#income_float_rate").change(function(){
            income_fee_rate = parseFloat($("#income_fee_rate").val()); // 年化出借人收益率
            annualized_rate = parseFloat($("#annualized_rate").val()); // 借款年利率
            income_float_rate = parseFloat($("#income_float_rate").val()); //年化收益浮动利率
            income_base_rate = parseFloat($("#income_base_rate").val()); // 年化收益基本利率

            if(isNaN(income_base_rate)) {
                income_base_rate = 0;
            }
            if(isNaN(income_float_rate)) {
                income_float_rate = 0;
            }
            total_rate = (income_float_rate + income_base_rate).toFixed(5);

            $("#income_fee_rate").val(total_rate);
            $("#annualized_rate").val(total_rate);
            get_complex_rate();

            if ($("#deal_type").val() == 1) {
                var year_rate = $("#annualized_rate").val();
                var redemption_period = $("#redemption_period").val();
                $.ajax({
                    url: ROOT + "?" + VAR_MODULE + "=DealProject&" + VAR_ACTION + "=convertRateYearToDay&rate=" + year_rate + "&redemption_period=" + redemption_period,
                    dataType: "json",
                    async: false,
                    success: function(rs) {
                        $("#day_rate").html(rs.day_rate)
                    }
                });
            }
        });
    });

    function changeRate(tag){

        if(!tag){
            return false;
        }
        var income_rate = parseFloat($('#income_fee_rate').val());
        var rate = parseFloat($('#annualized_rate').val());
        var manage_rate = parseFloat($('#manage_fee_rate').val());
        var repay_time = $("select[name='repay_time']").val();
        var loantype = $('#repay_mode').val();
        var income_base_rate = $('#income_base_rate').val();
        var income_float_rate = $('#income_float_rate').val();

        if(loantype == 5){
            repay_time = $("input[name='repay_time']").val();
        }

        /* $.get("/m.php?m=Ajax&a=getDailyRate&rate="+rate,function(dt){
         $('#rate_day').val(dt);
         }) */

        var tem_lock = false;
        $.get("/m.php?m=Ajax&a=get_fee_rate&rate="+rate+"&manage_rate="+manage_rate+"&income_rate="+income_rate+"&tag="+tag+"&repay_time="+repay_time+"&loantype="+loantype,function(dt){

            $('#'+tag).val(dt);
            tem_lock = true;

            if(auto_update_income_base_rate){
                var income_base_rate_val = dt;
                if(tag != 'income_fee_rate'){
                    income_base_rate_val = income_rate;
                }

                $('#income_base_rate').val(income_base_rate_val);
                $('#income_float_rate').val('0');
            }else{
                auto_update_income_base_rate = true;
            }

            if(tem_lock == true){
                get_complex_rate();
                change_year_to_period();
            }
            var year_rate = $("#annualized_rate").val();
            // 修改日利率
            if ($("#deal_type").val() == 1) {
                var redemption_period = $("#redemption_period").val();
                $.ajax({
                    url: ROOT + "?" + VAR_MODULE + "=DealProject&" + VAR_ACTION + "=convertRateYearToDay&rate=" + year_rate + "&redemption_period=" + redemption_period,
                    dataType: "json",
                    async: false,
                    success: function(rs) {
                        $("#day_rate").html(rs.day_rate)
                    }
                });
            }
        })

    }

    function changeLoantype() {
        var loantype = $("#repay_mode").val();
        var deal_status = $("input[name='deal_status']:checked").val();
        if((((loantype == 4 || loantype == 6) && deal_status == 4) || loantype == 8)) {
            $("#first_repay_day_box").show();
        } else {
            $("#first_repay_day_box").hide();
        }
    }

    function changeRepay(tag){
        var repay_mode = $('#repay_mode').val();
        changeRepay.is_index_rebate_days = changeRepay.is_index_rebate_days || 0;
    <?php if($vo['deal_type'] != 1): ?>// 自动填写返利天数
            if (repay_mode !=5){
                var repay_period_v = $("#repay_period3").val();
                switch(repay_mode){
                    case '1':
                    case '6':
                    case '7':
                        repay_period_v = $("#repay_period").val();
                        break;
                    case '8': //固定日还款特殊算法
                        repay_period_v = $("#repay_period3").val();
                        break;
                }

                $("#rebate_days").val(repay_period_v*30);
            }else if (repay_mode == 5){
                repay_period_v = $("#repay_period2").val();
                $("#rebate_days").val(repay_period_v);
            }
    <?php if(isset($deal_coupon['rebate_days'])): ?>if (changeRepay.is_index_rebate_days == 0){
                $("#rebate_days").val(<?php echo ($deal_coupon['rebate_days']); ?>);
            }

        changeRepay.is_index_rebate_days++;<?php endif; ?><?php endif; ?>
        changeLoantype();

        //切换html
        if(repay_mode == 5){
            $('.xhsoi').hide();
            $('.xhsot').show();

            var repay_period = $('#repay_period2').val();
            $('#repay_period').hide();
            $('#repay_period').removeAttr('name');
            $('#repay_period2,#tian').show();
            $('#repay_period2').attr('name', 'repay_time');
            $('#repay_period3').hide();
            $('#repay_period3').removeAttr('name');
            //change_lgl_time();
        }else if(repay_mode == 4 || repay_mode == 3 || repay_mode == 2 || repay_mode == 8){
            $('.xhsoi').show();
            $('.xhsot').hide();

            var repay_period = $("#repay_period3").val();
            $('#repay_period3').show();
            $('#repay_period3').attr('name', 'repay_time');
            $('#repay_period2,#tian').hide();
            $('#repay_period2').removeAttr('name');
            $('#repay_period').hide();
            $('#repay_period').removeAttr('name');
        }else{
            $('.xhsoi').show();
            $('.xhsot').hide();

            var repay_period = $("#repay_period").val();
            $('#repay_period').show();
            $('#repay_period').attr('name', 'repay_time');
            $('#repay_period2,#tian').hide();
            $('#repay_period2').removeAttr('name');
            $('#repay_period3').hide();
            $('#repay_period3').removeAttr('name');
        }

        changeRate('income_fee_rate');

        // 更新代销分期数据
        if (document.readyState == 'complete' && is_proxy_sale()) {
            update_proxy_loan_info();
        }
    }



    function getYearlyRate(){
        var number_scale_length = 5;
        var repay_mode = $('#repay_mode').val();
        var rate = parseFloat($('#annualized_rate').val());
        var loan_fee_rate = parseFloat($("input[name='loan_fee_rate']").val());
        var guarantee_fee_rate = parseFloat($("input[name='guarantee_fee_rate']").val());

        if(repay_mode == 5){
            var repay_time = $('#repay_period2').val();
        }else if(repay_mode == 4 || repay_mode == 3 || repay_mode == 2){
            var repay_time = $('#repay_period3').val();
        }else{
            var repay_time = $("#repay_period").val();
        }

        var time = 12;
        if(repay_mode == 5){
            time = 360;
        }

        if(repay_time > 0){
            var yearly_loan_fee_rate = (loan_fee_rate / repay_time * time).toFixed(number_scale_length);
            var yearly_guarantee_fee_rate = (guarantee_fee_rate / repay_time * time).toFixed(number_scale_length);
            var yearly_rate = rate+parseFloat(yearly_loan_fee_rate)+parseFloat(yearly_guarantee_fee_rate);
        }else{
            var yearly_loan_fee_rate = '';
            var yearly_guarantee_fee_rate = '';
            var yearly_rate = rate;
        }

        $('#yearly_loan_fee_rate').html(yearly_loan_fee_rate);
        $('#yearly_guarantee_fee_rate').html(yearly_guarantee_fee_rate);
        $('#yearly_rate').html(yearly_rate.toFixed(number_scale_length));
    }

    function button_edit(){
        $("#button_ff").html('<input type="submit" class="button" value="<?php echo L("EDIT");?>" /><input type="reset" class="button" value="<?php echo L("RESET");?>" />');
    }

    function confirmSubmit() {
        //JIRA#2925  合同变更需求，新增“资产转让类别”
        //转让资产类别数据校验,若资产转让类别选择为“无”，则此五项为非必填项。若资产转让类别选择为“债权”或“资产收益权”，则“基础合同的编号”、“原始债务人”、“基础合同交易金额”、“基础合同名称”四项为必填项
        if ( $('select[name="contract_transfer_type"]: option:selected').val() > 0) {
            $("input[name='leasing_contract_num']").addClass('require');
            $("input[name='lessee_real_name']").addClass('require');
            $("input[name='leasing_money']").addClass('require');
            $("input[name='leasing_contract_title']").addClass('require');
        } else {
            $("input[name='leasing_contract_num']").removeClass('require');
            $("input[name='lessee_real_name']").removeClass('require');
            $("input[name='leasing_money']").removeClass('require');
            $("input[name='leasing_contract_title']").removeClass('require');
        }

        income_fee_rate = parseFloat($("#income_fee_rate").val()); // 年化出借人收益率
        annualized_rate = parseFloat($("#annualized_rate").val());    // 借款年利率
        income_float_rate = parseFloat($("#income_float_rate").val()); //年化收益浮动利率
        income_base_rate = parseFloat($("#income_base_rate").val()); // 年化收益基本利率


        if(isNaN(income_base_rate)) {
            alert('年化收益基本利率不能为空');
            $("#income_base_rate").focus();
            return false;
        }

        if(income_fee_rate != annualized_rate || annualized_rate.toFixed(5) !=(income_float_rate + income_base_rate).toFixed(5)) {
            alert("请注意 ： 借款年利率＝年化出借人收益率 = (年化收益基本利率 + 年化收益浮动利率");
            return false;
        }


        var deal_status = $("input[name='deal_status']:checked").val();
        if(isP2P && deal_status==1){
            if($("input[name='report_type']:checked").val() == 0){
                return confirm("请注意： 该标是网贷类型的标的您确定不要到存管行报备么？");
            }else{
                if(hasReportToBank == false){
                    if(deal_status == 1){
                        return confirm("请注意： 确定需要将该标的报备到存管行么？");
                    }
                }
            }
        }

        var deal_type = $("#deal_type").val();
        if(deal_type == 2){
            var jys_val = $("#jys_id option:selected").val();
            if(jys_val == 0){
                $("#jys_id").focus();
                alert('请选择对应的交易所');
                return false;
            }
        }
        return true;
    }

    function edit_borrower() {
        $.weeboxs.open(ROOT+'?m=Deal&a=edit_borrower&deal_id='+<?php echo ($vo["id"]); ?>, {contentType:'ajax',showButton:false,title:'修改借款人',width:500,height:140});
    }

    input_change($("#total_loan_fee") , $("#loan_fee_custom .loan_fee_arr") , ".loan_fee_arr", "loan");
    input_change($("#total_consult_fee") , $("#consult_fee_custom .consult_fee_arr") , ".consult_fee_arr", "consult");
    input_change($("#total_guarantee_fee") , $("#guarantee_fee_custom .guarantee_fee_arr") , ".guarantee_fee_arr", "guarantee");
    input_change($("#total_pay_fee") , $("#pay_fee_custom .pay_fee_arr") , ".pay_fee_arr", "pay");
    input_change($("#total_management_fee") , $("#management_fee_custom .management_fee_arr") , ".management_fee_arr", "management");

    $("#loan_fee_custom .loan_fee_arr").live("input" , function(){
        input_change($("#total_loan_fee") , $(this) , ".loan_fee_arr", "loan");
    });

    $("#consult_fee_custom .consult_fee_arr").live("input" , function(){
        input_change($("#total_consult_fee") , $(this) , ".consult_fee_arr", "consult");
    });

    $("#guarantee_fee_custom .guarantee_fee_arr").live("input" , function(){
        input_change($("#total_guarantee_fee") , $(this) , ".guarantee_fee_arr", "guarantee");
    });

    $("#pay_fee_custom .pay_fee_arr").live("input" , function(){
        input_change($("#total_pay_fee") , $(this) , ".pay_fee_arr", "pay");
    });
    $("#management_fee_custom .management_fee_arr").live("input" , function(){
        input_change($("#total_management_fee") , $(this) , ".management_fee_arr", "management");
    });

    function input_change ($total , $t ,str, type) {
        var num=0;
        var whole = $("#"+type+"_fee").html();
        $t.parents("table").find(str).each(function(){
            num += parseFloat(this.value);
            if (whole > 0) {
                var p = this.value / whole * 100;
            } else {
                var p = 100;
            }
            $(this).parent().parent().find("."+type+"_p").html(p.toFixed(5)+"%");
        });

        $total.html(num.toFixed(2));

        if (whole > 0) {
            var pt = num / whole * 100;
        } else {
            var pt = 100;
        }
        $("#total_"+type+"_p").html(pt.toFixed(5)+"%");
    }

    function chg_fee() {
        chg_loan_fee();
        chg_consult_fee();
        chg_guarantee_fee();
        chg_canal_fee();
        chg_pay_fee();
        chg_management_fee();
    }

    function chg_loan_fee() {
        var type = $('input:radio[name="loan_fee_rate_type"]:checked').val();
        if (type!=3 || type != 4) {
            $("#loan_fee_custom").hide();
        }
        if (type==3 || type == 4 || type == 7) {
            $("#loan_fee_custom").show();
        }
    }
    function chg_consult_fee() {
        var type = $('input:radio[name="consult_fee_rate_type"]:checked').val();
        if (type!=3) {
            $("#consult_fee_custom").hide();
        }
        if (type==3) {
            $("#consult_fee_custom").show();
        }
    }
    function chg_guarantee_fee() {
        var type = $('input:radio[name="guarantee_fee_rate_type"]:checked').val();
        if (type!=3) {
            $("#guarantee_fee_custom").hide();
        }
        if (type==3) {
            $("#guarantee_fee_custom").show();
        }
    }
    function chg_canal_fee() {
        var type = $('input:radio[name="canal_fee_rate_type"]:checked').val();
        if (type!=3) {
            $("#canal_fee_custom").hide();
        }
        if (type==3) {
            $("#canal_fee_custom").show();
        }
    }
    function chg_pay_fee() {
        var type = $('input:radio[name="pay_fee_rate_type"]:checked').val();
        if (type!=3) {
            $("#pay_fee_custom").hide();
        }
        if (type==3) {
            $("#pay_fee_custom").show();
        }
    }
    function chg_management_fee() {
        var type = $('input:radio[name="management_fee_rate_type"]:checked').val();
        if (type!=3) {
            $("#management_fee_custom").hide();
        }
        if (type==3) {
            $("#management_fee_custom").show();
        }
    }
    //修改“平台费折扣率”时，提示不能输入大于100%
    function changeDiscountRate(){
      var rate = Number($('input:text[id="discount_rate"]').val());
      if(isNaN(rate) || (rate > 100.00) || (0 > rate)) {
          $('input:text[id="discount_rate"]').val("100.00000");
          alert("平台费折扣率只能是数值，不能超过100%，且不能小于0");
      }
    }

    // onclick 事件响应函数
    // 点击 loan_fee_rate_type
    function cli_loan_fee(obj)
    {
        // loan_fee_type 与 pay_type 对应值关系
        var fee_pay_hash = new Array();
        fee_pay_hash[1] = 0; // 年化前收 -> 放款时结算
        fee_pay_hash[2] = 1; // 年化后收 -> 还清时结算
        fee_pay_hash[3] = 1; // 年化分期收 -> 还清时结算
        fee_pay_hash[4] = 0; // 代销分期 -> 放款时结算
        fee_pay_hash[5] = 0; // 固定比例前收 -> 放款时结算
        fee_pay_hash[6] = 1; // 固定比例后收 -> 还清时结算
        fee_pay_hash[7] = 0; // 固定比例分期收 -> 放款时结算

        var pay_type_objs = $("input.pay_type");
        for (var i = pay_type_objs.length - 1; i >= 0; --i) {
            if (pay_type_objs[i].value == fee_pay_hash[obj.value]) {
                pay_type_objs[i].checked = true;
            } else {
                pay_type_objs[i].checked = false;
            }
        }
        calc_fenqi_fee("loan_fee");
    }

    // 点击 deal_status
    function cli_deal_status(obj)
    {
        if (0 == obj.value || 1 == obj.value) {
            $("input#rebate_days")[0].readOnly = true;
        } else {
            $("input#rebate_days")[0].readOnly = false;
        }
    }

    // 年化借款平台手续费 - 不同情况切换
    $('#loan_fee_type_1').click(function () {
        update_loan_fee(1);
    });
    $('#loan_fee_type_2').click(function () {
        update_loan_fee(2);
    });
    $('#loan_fee_type_3').click(function () {
        $('#loan_fee_custom_input').html($('#loan_fee_installment').html());
        update_loan_fee(3);
    });
    $('#loan_fee_type_4').click(function () {
        $('#loan_fee_custom_input').html($('#loan_fee_proxy').html());
        update_loan_fee(4);
    });
    $('#loan_fee_type_5').click(function () {
        update_loan_fee(5);
    });
    $('#loan_fee_type_6').click(function () {
        update_loan_fee(6);
    });
    $('#loan_fee_type_7').click(function () {
        $('#loan_fee_custom_input').html($('#loan_fee_installment').html());
        update_loan_fee(7);
    });

    // 更新代销分期 收益率 和 金额
    function update_proxy_loan_info()
    {
        if ($('#loan_fee_type_4').attr('checked')) {
            var repay_mode = $('#repay_mode').val();

            if(repay_mode == 5){
                var repay_time = $('#repay_period2').val();
            }else if(repay_mode == 4 || repay_mode == 3 || repay_mode == 2){
                var repay_time = $('#repay_period3').val();
            }else{
                var repay_time = $('#repay_period').val();
            }

            $.post(
                '/m.php?m=Ajax&a=getPeriodInfo',
                {"loantype" : repay_mode,
                 "loan_fee_rate" : $('#loan_fee_rate').val(),
                 "repay_time" : repay_time,
                 "loan_money" : $("#apr").val(),
                 "loan_first_rate" : $('#proxy_loan_fee_rate_first').val()},
                function (data) {
                    $('#proxy_loan_fee_first').val(data.loan_first_fee);
                    $('#proxy_loan_fee_rate_last').val(data.loan_last_rate);
                    $('#proxy_loan_fee_last').val(data.loan_last_fee);

                    $('#proxy_loan_rate_sum').val(data.loan_rate_sum);
                    $('#proxy_loan_fee_sum').val(data.loan_fee_sum);
                },
                'json'
            );
        }
    }

    // 判断是否为代销分期
    function is_proxy_sale()
    {
        return $('#loan_fee_type_4').attr('checked');
    }

    // 更新平台手续费金额
    function update_loan_fee(loan_fee_rate_type)
    {
        $.post(
            '/m.php?m=Ajax&a=getLoanFee',
            {
                "deal_id" : $('#deal_id').val(),
                "loan_fee_rate_type" : loan_fee_rate_type
            },
            function (data) {
                $('#total_loan_fee').html(data.fee);
                $('#loan_fee').html(data.fee);
                $("input[name=loan_fee_arr[0]]").val(data.fee);
            },
            'json'
        );
    }

    //渠道费显示
    function change_canal(selectstr){
        if(selectstr == "0"){
            canal_fee_info.style.display = "none";
        }else{
            canal_fee_info.style.display = "";
        }
    }

    //担保费显示 是交易所才会进行agencyId为0的判断
    //deal_type为交易所，并且agency_id为空时,担保费类型默认为1-前收,其他为0
    function change_agency(selectstr){
        if($("#deal_type").val() != 2){
          return true;
        }
        if(selectstr == "0"){
            $("#warrant_select").val(0);
            $("#guarantee_fee_rate").val("0.00000");
            get_period_rate('guarantee_fee_rate');
            $("input[type='radio'][name='guarantee_fee_rate_type'][value='1']").attr("checked", true);
            chg_guarantee_fee();
            agency_fee_info.style.display = "none";
            warrant_info.style.display = "none";
        }else{
            agency_fee_info.style.display = "";
            warrant_info.style.display = "";
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


<!-- 平台手续分 分期收-->
<script type="text/html" id="loan_fee_installment">
    &nbsp;&nbsp;&nbsp;&nbsp;应收金额：<span id="loan_fee"><?php echo ($loan_fee); ?></span>&nbsp;&nbsp;&nbsp;&nbsp;应收比例：100% <br/><br/>
    <table class="form" cellpadding=0 cellspacing=0>
        <tr>
            <td width="100px">期次</td><td width="150px">金额(元)</td><td>比例</td>
        </tr>
        <?php if($deal_ext["loan_fee_ext"] == ''): ?><tr>
                <td>0 (起息日)</td><td><input type='text' name='loan_fee_arr[0]' class='loan_fee_arr' id='loan_fee_arr[]' value='<?php echo ($loan_fee); ?>' /></td><td class='loan_p'>100%</td>
            </tr>
            <?php for ($i=1;$i<=$repay_times;$i++) {
                echo "<tr><td>".$i."</td><td><input type='text' class='loan_fee_arr' name='loan_fee_arr[".$i."]' id='loan_fee_arr[]' value='0.00' /></td><td class='loan_p'>0%</td></tr>";
                } ?>
            <?php else: ?>
            <?php $loan_fee_arr = json_decode($deal_ext['loan_fee_ext'], true);
                foreach ($loan_fee_arr as $kk => $vv) {
                if ($kk == 0) {
                echo "<tr><td>0 (起息日)</td>";
                } else {
                echo "<tr><td>".$kk."</td>";
                }
                echo "<td><input type='text' class='loan_fee_arr' name='loan_fee_arr[".$kk."]' id='loan_fee_arr[]' value='".$vv."' /></td><td class='loan_p'>0%</td></tr>";
                } ?><?php endif; ?>
        <tr>
            <td>总计</td><td><span id="total_loan_fee"><?php echo ($loan_fee); ?></span></td><td id="total_loan_p">100%</td>
        </tr>
    </table>
    <font color='red'>（通知贷暂不支持）</font>
</script>

<!-- 平台手续分 代销分期-->
<script type="text/html" id="loan_fee_proxy">
    <table class="form" cellpadding="0" cellspacing="0">
        <tr>
            <td width="100px">期次</td>
            <td width="150px">年华收益率(%)</td>
            <td>应收金额(元)</td>
        </tr>
        <tr>
            <td>(起息日)</td>
            <td><input type='text' name='proxy_loan_fee_rate_first' id='proxy_loan_fee_rate_first' onchange='update_proxy_loan_info()' value='<?php echo ($proxy_sale["loan_first_rate"]); ?>'/></td>
            <td class='loan_p'><input type="text" name='loan_fee_arr[]' id='proxy_loan_fee_first' class='loan_fee_arr' readonly='true' value='<?php echo ($loan_fee_arr[0]); ?>'></td>
        </tr>
        <tr>
            <td>(最后一期)</td>
            <td><input type='text' name='proxy_loan_fee_rate_last' id='proxy_loan_fee_rate_last' readonly='true' value='<?php echo ($proxy_sale["loan_last_rate"]); ?>'/></td>
            <td class='loan_p'><input type="text" name='loan_fee_arr[]' id='proxy_loan_fee_last' class='loan_fee_arr' readonly='true' value='<?php echo ($loan_fee_arr[$repay_times]); ?>'></td>
        </tr>
        <tr>
            <td>总计</td>
            <td><input type='text' id='proxy_loan_rate_sum' readonly='true' value='<?php echo ($proxy_sale["loan_rate_sum"]); ?>'/></td>
            <td><input type='text' id='proxy_loan_fee_sum' readonly='true' value='<?php echo ($proxy_sale["loan_fee_sum"]); ?>'/></td>
        </tr>
    </table>
    <font color='red'>（通知贷暂不支持）</font>
</script>