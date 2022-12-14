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


    function show_detail(id) {
        $.weeboxs.open(ROOT+'?m=Deal&a=show_detail&id='+id, {contentType:'ajax',showButton:false,title:LANG['COUNT_TOTAL_DEAL'],width:600,height:330});
    }

    function force_repay(id){
        window.location.href = ROOT + '?m=Deal&a=force_repay&deal_id='+id+"&role="+$("#role").val()+'&<?php echo ($querystring); ?>';
    }

    //function apply_prepay(id) {
    //    $("#prepay_btn").css({ "color": "grey" }).attr("disabled", "disabled");
    //    if (window.confirm('???????????????????????????')) {
    //        window.location.href = ROOT + '?m=Deal&a=apply_prepay&deal_id=' + id;
    //    } else {
    //        $("#prepay_btn").css({ "color": "#4e6a81" }).removeAttr("disabled");
    //    }
    //}

    function apply_prepay(id,loantype, type) {
        if(loantype==7) {
            alert('??????????????????????????????');
            return false;
        }
        window.location.href = ROOT + '?m=DealPrepay&a=prepay_index&deal_id=' + id + '&type=' + type+"&role="+$("#role").val()+'&<?php echo ($querystring); ?>';
    }

    function export_csv()
    {
        idBox = $(".key:checked");
        idArray = new Array();
        $.each( idBox, function(i, n){
            idArray.push($(n).val());
        });
        id = idArray.join(",");

        var inputs = $(".search_row").find("input");
        var selects = $(".search_row").find("select");
        var param = '';
        for(i=0;i<inputs.length;i++)
        {
            if(inputs[i].name != '' && inputs[i].name != 'm'&&inputs[i].name != 'a')
                param += "&"+inputs[i].name+"="+$(inputs[i]).val();
        }

        // ??????select
        for (var i = selects.length - 1; i >= 0; --i) {
            if (selects[i].name != '') {
                param += "&" + selects[i].name + "=" + $(selects[i]).val();
            }
        }
        var url= ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=export_repay_list&id="+id;
        location.href = url+param;
    }

</script>

<div class="main">
<?php if($role == 'b'): ?><div class="main_title">???????????????</div>
<?php else: ?>
<div class="main_title"><?php echo ($main_title); ?></div><?php endif; ?>
<div class="blank5"></div>
<div class="blank5"></div>
<div class="search_row">
    <form name="search" action="__APP__" method="get">
        ?????????<input type="text" class="textbox" name="deal_id" value="<?php echo trim($_REQUEST['deal_id']);?>" size="10"/>
        ???????????????<input type="text" class="textbox" name="name" value="<?php echo trim($_REQUEST['name']);?>" size="10"/>
        ???????????????<input type="text" class="textbox" name="project_name" value="<?php echo trim($_REQUEST['project_name']);?>" />
        ?????????????????????
        <input type="text" class="textbox" name="user_name" value="<?php echo trim($_REQUEST['user_name']);?>" size="10" />
        ?????????????????????????????????
            <input type="text" class="textbox" name="repay_time_begin" id="repay_time_begin" value="<?php echo trim($_REQUEST['repay_time_begin']);?>" size="10" onfocus="this.blur();" />
        ?????????????????????????????????
            <input type="text" class="textbox" name="repay_time_end" id="repay_time_end" value="<?php echo trim($_REQUEST['repay_time_end']);?>" size="10" onfocus="this.blur();" />
        <?php if($role == 'b'): ?>???????????????
            <input type="text" class="textbox" name="submit_uid" value="<?php echo ($_REQUEST['submit_uid']); ?>" size="10" />
            ???????????????
            <select class="require" name="service_type">
                <option value="0" <?php if($_REQUEST['service_type'] == 0): ?>selected="selected"<?php endif; ?>>??????</option>
                <option value="4" <?php if($_REQUEST['service_type'] == 4): ?>selected="selected"<?php endif; ?>>????????????</option>
                <option value="5" <?php if($_REQUEST['service_type'] == 5): ?>selected="selected"<?php endif; ?>>????????????</option>
            </select>
        <?php else: ?>
            ???????????????
            <select class="require" name="audit_status">
                <option value="0" <?php if($_REQUEST['audit_status'] == 0): ?>selected="selected"<?php endif; ?>>??????</option>
                <option value="1" <?php if($_REQUEST['audit_status'] == 1): ?>selected="selected"<?php endif; ?>>???????????????</option>
                <option value="2" <?php if($_REQUEST['audit_status'] == 2): ?>selected="selected"<?php endif; ?>>???????????????</option>
                <option value="3" <?php if($_REQUEST['audit_status'] == 3): ?>selected="selected"<?php endif; ?>>???????????????</option>
                <option value="4" <?php if($_REQUEST['audit_status'] == 4): ?>selected="selected"<?php endif; ?>>???????????????</option>
            </select><?php endif; ?>
       ???????????????
        <select name="deal_type" id="deal_type">
            <?php if(!$is_cn): ?><option value="2" <?php if($_REQUEST['deal_type'] == 2): ?>selected<?php endif; ?>>?????????</option>
            <option value="3" <?php if($_REQUEST['deal_type'] == 3): ?>selected<?php endif; ?>>??????</option>
            <option value="5" <?php if($_REQUEST['deal_type'] == 5): ?>selected<?php endif; ?>>??????</option><?php endif; ?>
        </select>
        <!--?????????????????????
        <select name="report_status" id="report_status">
            <option value="1" <?php if($_REQUEST['report_status'] == '1'): ?>selected<?php endif; ?>>?????????</option>
            <option value="0" <?php if($_REQUEST['report_status'] == '0'): ?>selected<?php endif; ?>>?????????</option>
        </select>-->
        </select>

        ???????????????
        <select name="repay_mode_holiday" id="repay_mode_holiday">
            <option value="0" <?php if($_REQUEST['repay_mode_holiday'] == '0'): ?>selected<?php endif; ?>>??????</option>
            <option value="1" <?php if($_REQUEST['repay_mode_holiday'] == '1'): ?>selected<?php endif; ?>>??????</option>
            <option value="2" <?php if($_REQUEST['repay_mode_holiday'] == '2'): ?>selected<?php endif; ?>>??????</option>
        </select>

        ?????????????????????
        <select name="repay_type" id="repay_type">
          <option value="" <?php if(!isset($_REQUEST['repay_type']) || strlen($_REQUEST['repay_type']) == 0): ?>selected="selected"<?php endif; ?>>??????</option>option>
          <?php if(is_array($deal_repay_type)): foreach($deal_repay_type as $key=>$item): ?><option value="<?php echo ($key); ?>"<?php if(strlen($_REQUEST['repay_type']) > 0 &&  $_REQUEST['repay_type'] == $key): ?>selected="selected"<?php endif; ?>><?php echo ($item); ?></option>option><?php endforeach; endif; ?>
        </select>

        ???????????????
        <select name="type_id" id='type_id'>
          <option value="0"<?php if(!isset($_REQUEST['type_id']) || $_REQUEST['type_id'] == 0): ?>selected="selected"<?php endif; ?>>??????</option>
          <?php if(is_array($deal_loan_type)): foreach($deal_loan_type as $key=>$type_item): ?><option value="<?php echo ($type_item["id"]); ?>"<?php if($_REQUEST['type_id'] == $type_item['id']): ?>selected="selected"<?php endif; ?>><?php echo ($type_item["name"]); ?></option><?php endforeach; endif; ?>
        </select>
        ???????????????
        <select name="is_during_repay" id="is_during_repay">
          <option value="" <?php if(!isset($_REQUEST['is_during_repay'])): ?>selected="selected"<?php endif; ?>>??????</option>option>
          <option value="0" <?php if(strlen($_REQUEST['is_during_repay']) > 0 &&  $_REQUEST['is_during_repay'] == 0): ?>selected="selected"<?php endif; ?>>?????????</option>option>
          <option value="1" <?php if(strlen($_REQUEST['is_during_repay']) > 0 &&  $_REQUEST['is_during_repay'] == 1): ?>selected="selected"<?php endif; ?>>?????????????????????</option>option>
        </select>

        <input type="hidden" value="Deal" name="m" />
        <input type="hidden" value="yuqi" name="a" />
        <input type="hidden" value="<?php echo ($role); ?>" id="role" name="role" />
        <input type="submit" class="button" value="<?php echo L("SEARCH");?>" />
        <input type="button" class="button" value="??????" onclick="export_csv();" />
        &nbsp;<a href="m.php?m=Deal&a=download_repay_account">????????????????????????</a>
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
            <th width="50px   ">
                <a href="javascript:sortBy('id','1','Deal','index')" title="???????????????????????? ">
                    ??????
                    <img src="/static/admin/Common/images/desc.gif" width="12" height="17"
                    border="0" align="absmiddle">
                </a>
            </th>
            <th>
                ????????????
            </th>
            <th style="width:150px">
                    ????????????
            </th>
            <th>
                ??????????????????
            </th>
            <th>
                    ????????????
            </th>
            <th>
                    ??????????????????
            </th>
            <th>
                    ????????????
            </th>
            <th>
                ????????????
            </th>
            <th>
                ??????????????????
            </th>
            <th>
                   ????????????
            </th>
            <th>
                ????????????
            </th>
            <th>
                   ???????????????
            </th>

                <th>????????????</th>

            <th>
                ????????????
            </th>
            <th>
                    ??????????????????
            </th>
            <th>
                    ???????????????
            </th>
            <th>
                    ?????????id
            </th>
            <th>
                <?php if(!isset($_REQUEST['report_status']) || $_REQUEST['report_status'] == '1'){
                        echo '??????P2P????????????';
                    }else{
                        echo '????????????????????????';
                    } ?>

            </th>
            <th style="width:100px">
                    ?????????????????????
            </th>
            <th>
                    ??????????????????
            </th>
            <th>
                    ????????????
            </th>
            <th>
                ????????????????????????
            </th>
            <th>
                    ????????????
            </th>
            <th>
                    ??????????????????
            </th>
            <?php if($role == 'b'): ?><th>
                    ????????????
            </th>
            <th>
                    ????????????
            </th><?php endif; ?>
            <th style="width:150px">
                    ??????
            </th>
        </tr>
        <?php if(is_array($list)): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$deal): ++$i;$mod = ($i % 2 )?><tr class="row" >
            <td>
                <input type="checkbox" name="key" class="key" value="<?php echo ($deal["id"]); ?>">
            </td>
            <td>
                &nbsp;<?php echo ($deal["id"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["type_name"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["name"]); ?>
            </td>
            <td>
                &nbsp;<?php echo getOldDealNameWithPrefix($deal['id'], $deal['project_id']);?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["borrow_amount"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["rate"]); ?>%
            </td>
            <td>
                &nbsp;<?php echo ($deal["repay_period"]); ?>
            </td>
            <td>

                <?php echo to_date($deal['deal_info']['repay_start_time'],'Y-m-d');?>
            </td>
            <td>
                <?php echo (get_deal_ext_fee_type($deal["id"])); ?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["loantype"]); ?>
            </td>
            <td>
                <?php echo ($deal["repay_mode_name"]); ?>
            </td>
            <td>
                <?php if($deal['advisory_id'] && $dealAgency[$deal['advisory_id']]): ?>&nbsp;<?php echo ($dealAgency[$deal['advisory_id']]); ?>
                <?php else: ?>
                    &nbsp;-<?php endif; ?>
            </td>

                <td>
                    <?php echo ($deal["clearing_type_name"]); ?>
                </td>

            <td>
                &nbsp;<?php echo (getUserTypeName($deal["user_id"])); ?>
            </td>
            <td>
                &nbsp;<a href="?m=User&a=index&user_id=<?php echo ($deal["user_id"]); ?>" target="_blank"><?php echo ($deal["user_name"]); ?></a>
            </td>
            <td>
                &nbsp;<a href="?m=User&a=index&user_id=<?php echo ($deal["user_id"]); ?>" target="_blank"><?php echo ($deal["real_name"]); ?></a>
            </td>
            <td>
                &nbsp;<?php echo ($deal["user_id"]); ?>
            </td>
            <td <?php if($deal['insufficient']) echo 'style="background: yellow"';?>>
                &nbsp;<?php echo ($deal["money"]); ?>
            </td>
            <td <?php if($deal['is_repay_delayed']) echo 'style="background: red"';?>>
                &nbsp;<?php echo ($deal["repay_time"]); ?>
            </td>
            <td>
                &nbsp;<?php echo ($deal["repay_money"]); ?>
            </td>
            <td <?php if($deal['repay_alarm'] == 1) echo 'style="background: red"';?> >
                &nbsp;<?php echo (a_get_buy_status($deal["deal_status"],$deal.id)); ?>
                <?php if($deal["is_during_repay"] == 1): ?><br />????????????<?php endif; ?>
            </td>
            <td>
            <?php if($deal["is_part_user_repay"] == 1): ?>???<?php else: ?> ???<?php endif; ?>
            </td>
            <!--<td>-->
                <!--<?php if($audits[$deal['deal_repay_id']] or $prepays[$deal['id']]): ?>-->
                    <!--<?php if($audits[$deal['deal_repay_id']]['status'] == 1 or $prepays[$deal['id']]['status'] == 1): ?>-->
                        <!--???????????????-->
                    <!--<?php endif; ?>-->
                    <!--<?php if($audits[$deal['deal_repay_id']]['status'] == 2 or $prepays[$deal['id']]['status'] == 2): ?>-->
                        <!--???????????????-->
                    <!--<?php endif; ?>-->
                    <!--<?php if($audits[$deal['deal_repay_id']]['status'] == 3 or $prepays[$deal['id']]['status'] == 3): ?>-->
                       <!--???????????????-->
                    <!--<?php endif; ?>-->
                <!--<?php else: ?>-->
                    <!--???????????????-->
                <!--<?php endif; ?>-->
            <!--</td>-->
            <td>
                <?php if($repays[$deal['id']]['status'] or $prepays[$deal['id']]['status']): ?><?php if($repays[$deal['id']]['status'] == 1 or $prepays[$deal['id']]['status'] == 1): ?>???????????????<?php endif; ?>
                    <?php if($repays[$deal['id']]['status'] == 2 or $prepays[$deal['id']]['status'] == 2): ?>???????????????<?php endif; ?>
                    <?php if($repays[$deal['id']]['status'] == 3 or $prepays[$deal['id']]['status'] == 3): ?>???????????????<?php endif; ?>
                <?php else: ?>
                    ???????????????<?php endif; ?>

            </td>
            <?php if($role == 'b'): ?><td>
                <?php if($repays[$deal['id']]['service_type'] == 4): ?>????????????<?php endif; ?>
                <?php if($prepays[$deal['id']]['service_type'] == 5): ?>????????????<?php endif; ?>
                <!--<?php if($audits[$deal['deal_repay_id']]['service_type'] == 4): ?>-->
                    <!--????????????-->
                <!--<?php else: ?>-->
                    <!--????????????-->
                <!--<?php endif; ?>-->
            </td>
            <td>
                <?php if($repays[$deal['id']]['submit_user_name']): ?><?php echo ($repays[$deal['id']]['submit_user_name']); ?><?php endif; ?>
                <?php if($prepays[$deal['id']]['submit_user_name']): ?><?php echo ($prepays[$deal['id']]['submit_user_name']); ?><?php endif; ?>
                <!--<?php if($audits[$deal['deal_repay_id']]['submit_user_name']): ?>-->
                    <!--<?php echo ($audits[$deal['deal_repay_id']]['submit_user_name']); ?>-->
                <!--<?php endif; ?>-->
                <!--<?php if($prepays[$deal['id']]['submit_user_name']): ?>-->
                    <!--<?php echo ($prepays[$deal['id']]['submit_user_name']); ?>-->
                <!--<?php endif; ?>-->
            </td><?php endif; ?>
            <td>
              <?php echo ($deal_repay_type[$deal['repay_type']]); ?>
            </td>
            <td>
                &nbsp;
                <?php if(($deal["deal_status"] == 4) && ($deal["parent_id"] != 0) && ($deal["is_entrust_zx"] != 1)): ?><a href="javascript:show_detail('<?php echo ($deal["id"]); ?>')">????????????</a>&nbsp;
                    <?php if($audits[$deal['id']]['status'] != 2): ?><?php if($role == 'b'): ?><?php if($repays[$deal['id']]['status'] == 1): ?><a href="javascript:force_repay('<?php echo ($deal["id"]); ?>')">??????</a>&nbsp;<?php endif; ?>
                            <?php if($prepays[$deal['id']]['status'] == 1): ?><a href="javascript:apply_prepay('<?php echo ($deal["id"]); ?>','<?php echo ($deal["loantype"]); ?>', '1');">??????</a><?php endif; ?>
                        <?php else: ?>
                            <?php if($repays[$deal['id']]['status'] != 1 and $prepays[$deal['id']]['status'] != 1 and $repays[$deal['id']]['status'] != 2 and $prepays[$deal['id']]['status'] != 2): ?><a href="javascript:force_repay('<?php echo ($deal["id"]); ?>')">????????????</a>&nbsp;
                            <a href="javascript:apply_prepay('<?php echo ($deal["id"]); ?>','<?php echo ($deal["loantype"]); ?>', 1)">????????????</a><?php endif; ?><?php endif; ?><?php endif; ?>

                <!--<?php if(($deal["deal_status"] == 4) && ($deal["parent_id"] != 0)): ?>-->
                    <!--<a href="javascript:show_detail('<?php echo ($deal["id"]); ?>')">????????????</a>&nbsp;-->
                    <!--<?php if($audits[$deal['id']]['status'] != 2): ?>-->
                        <!--<?php if($role == 'b'): ?>-->
                            <!--<if condition="$audits[$deal['deal_repay_id']]['service_type'] eq 4">-->
                                <!--<a href="javascript:force_repay('<?php echo ($deal["id"]); ?>')">??????</a>&nbsp;-->
                            <!--<?php else: ?>-->
                                <!--<a href="javascript:apply_prepay('<?php echo ($deal["id"]); ?>','<?php echo ($deal["loantype"]); ?>', '1');">??????</a>-->
                            <!--<?php endif; ?>-->
                        <!--<?php else: ?>-->
                            <!--<?php if($audits[$deal['deal_repay_id']]['status'] == 1 or $prepays[$deal['id']]['status'] == 1 or $audits[$deal['deal_repay_id']]['status'] == 2 or $prepays[$deal['id']]['status'] == 2): ?>-->
                            <!--<?php else: ?>-->
                            <!--<a href="javascript:force_repay('<?php echo ($deal["id"]); ?>')">????????????</a>&nbsp;-->
                            <!--<a href="javascript:apply_prepay('<?php echo ($deal["id"]); ?>','<?php echo ($deal["loantype"]); ?>', 1)">????????????</a>-->
                            <!--<?php endif; ?>-->
                        <!--<?php endif; ?>-->
                    <!--<?php endif; ?>-->
                <!--<input type="button" id="prepay_btn" class="ts-input" data-id="<?php echo ($deal["id"]); ?>" onclick="apply_prepay('<?php echo ($deal["id"]); ?>')" value="????????????"/>&nbsp;--><?php endif; ?>
            </td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>
<div class="blank5"></div>
<div class="page"><?php echo ($page); ?></div>
</div>
<script>
    $(document).ready(function(){
        //????????????????????????????????????????????????
        $("#repay_time_begin").blur(function(){
            return showCalendar('repay_time_begin', '%Y-%m-%d', false, false, 'repay_time_begin',function() {
                if('' == $("#repay_time_end").val()) {
                    return true;
                }
                var repay_start_times = get_unix_time($("#repay_time_begin").val());
                var repay_end_times = get_unix_time($("#repay_time_end").val());
                if(repay_start_times > repay_end_times) {
                    alert('???????????????????????????????????????????????????');
                    $("#repay_time_begin").val('');
                }
            });
        });

        //????????????????????????????????????????????????
        $("#repay_time_end").blur(function(){
            return showCalendar('repay_time_end', '%Y-%m-%d', false, false, 'repay_time_end',function() {
                if('' == $("#repay_time_begin").val()) {
                    return true;
                }
                var repay_start_times = get_unix_time($("#repay_time_begin").val());
                var repay_end_times = get_unix_time($("#repay_time_end").val());
                if(repay_start_times > repay_end_times) {
                    alert('???????????????????????????????????????????????????');
                    $("#repay_time_end").val('');
                }
            });
        });
    });
    function get_unix_time(hm) {
        var date = new  Date();
        var hms = hm.split('-');
        var year = hms[0];
        var month = hms[1];
        var day = hms[2];
        var today = new Date(year,month,day,'0','0','00');
        return today.getTime();
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